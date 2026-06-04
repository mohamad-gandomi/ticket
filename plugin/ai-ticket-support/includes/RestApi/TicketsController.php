<?php
declare(strict_types=1);

namespace ATS\RestApi;

use ATS\Database;
use ATS\AiService;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Endpoints for the logged-in user (non-admin).
 *
 * GET    /ats/v1/tickets                  – list own tickets
 * POST   /ats/v1/tickets                  – create ticket (first message included)
 * GET    /ats/v1/tickets/:id              – ticket detail + messages
 * POST   /ats/v1/tickets/:id/messages     – reply to own ticket
 */
final class TicketsController extends AbstractController {

    public function register_routes(): void {
        register_rest_route(self::NAMESPACE, '/categories', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'categories_index'],
                'permission_callback' => [$this, 'require_logged_in'],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/tickets', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'index'],
                'permission_callback' => [$this, 'require_logged_in'],
                'args'                => [
                    'page'     => ['type' => 'integer', 'default' => 1, 'minimum' => 1],
                    'per_page' => ['type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100],
                    'status'   => ['type' => 'string',  'default' => ''],
                ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'store'],
                'permission_callback' => [$this, 'require_logged_in'],
                'args'                => [
                    'title'       => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_text_field', 'minLength' => 3, 'maxLength' => 255],
                    'body'        => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'wp_kses_post'],
                    'priority'    => ['type' => 'string', 'default' => 'low',  'enum' => ['low', 'medium', 'high']],
                    'category_id' => ['type' => 'integer', 'default' => null],
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/tickets/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'show'],
                'permission_callback' => [$this, 'require_logged_in'],
                'args'                => [
                    'id' => ['type' => 'integer'],
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/tickets/(?P<id>\d+)/messages', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'send_message'],
                'permission_callback' => [$this, 'require_logged_in'],
                'args'                => [
                    'id'   => ['type' => 'integer'],
                    'body' => ['type' => 'string', 'required' => true, 'sanitize_callback' => 'wp_kses_post'],
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/tickets/(?P<id>\d+)/attachments', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'upload_attachment'],
                'permission_callback' => [$this, 'require_logged_in'],
                'args'                => [
                    'id'         => ['type' => 'integer'],
                    'message_id' => ['type' => 'integer', 'default' => null],
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/tickets/(?P<id>\d+)/ai-resolve', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'ai_resolve'],
                'permission_callback' => [$this, 'require_logged_in'],
                'args'                => [
                    'id' => ['type' => 'integer'],
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/tickets/(?P<id>\d+)/route-to-support', [
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'route_to_support'],
                'permission_callback' => [$this, 'require_logged_in'],
                'args'                => [
                    'id' => ['type' => 'integer'],
                ],
            ],
        ]);
    }

    public function index(WP_REST_Request $req): WP_REST_Response {
        $db      = Database::instance();
        $user_id = get_current_user_id();
        $page    = (int) $req->get_param('page');
        $per     = (int) $req->get_param('per_page');

        // Map user-facing status to internal DB statuses
        $statuses = match ($req->get_param('status')) {
            'pending'  => ['unreviewed', 'reviewing', 'spam'],
            'answered' => ['pending', 'answered'],
            'closed'   => ['closed'],
            default    => [],
        };

        $tickets = $db->get_tickets_for_user($user_id, $page, $per, $statuses);
        $total   = $db->count_tickets_for_user($user_id, $statuses);

        $response = $this->ok([
            'items'      => array_map([$this, 'format_ticket'], $tickets),
            'total'      => $total,
            'page'       => $page,
            'total_pages'=> (int) ceil($total / $per),
        ]);
        $response->header('X-WP-Total',      (string) $total);
        $response->header('X-WP-TotalPages', (string) ceil($total / $per));
        return $response;
    }

    public function store(WP_REST_Request $req): WP_REST_Response|WP_Error {
        $db          = Database::instance();
        $user_id     = get_current_user_id();
        $title       = $req->get_param('title');
        $body        = $req->get_param('body');
        $priority    = $req->get_param('priority');
        $category_id = $req->get_param('category_id') ? (int) $req->get_param('category_id') : null;

        $ticket_id = $db->create_ticket($user_id, $title, $priority, $category_id);
        if ($ticket_id === false) {
            return $this->error('db_error', 'Failed to create ticket.', 500);
        }

        $msg_id = $db->create_message($ticket_id, $user_id, 'user', $body);
        if ($msg_id === false) {
            return $this->error('db_error', 'Ticket created but message failed.', 500);
        }

        $ticket = $db->get_ticket($ticket_id);

        $settings   = (array) get_option('ats_settings', []);
        $ai_enabled = ! empty($settings['aiEnabled']);

        if ($ai_enabled) {
            // Run AI suggestion only when AI is actually enabled.
            $suggestion = (new AiService())->suggest($ticket, $body);
            $db->save_ai_suggestion($ticket_id, $suggestion);
            if ($suggestion === null) {
                $db->create_message($ticket_id, 0, 'system', 'سوال شما به کارشناس ارجاع داده شد و به زودی پاسخ خواهید گرفت.');
            }
        } else {
            // AI disabled — route every ticket to human support immediately.
            $db->create_message($ticket_id, 0, 'system', 'سوال شما به کارشناس ارجاع داده شد و به زودی پاسخ خواهید گرفت.');
        }

        // Re-fetch so ai_status / ai_suggestion columns are fresh
        $ticket    = $db->get_ticket($ticket_id);
        $formatted = $this->format_ticket($ticket);
        $formatted['firstMessageId'] = (string) $msg_id;
        return $this->created($formatted);
    }

    public function show(WP_REST_Request $req): WP_REST_Response|WP_Error {
        $db      = Database::instance();
        $id      = (int) $req->get_param('id');
        $ticket  = $db->get_ticket($id);

        if ($ticket === null) {
            return $this->not_found('Ticket not found.');
        }

        // Users can only view their own tickets
        if ((int) $ticket['user_id'] !== get_current_user_id() && ! current_user_can('manage_options')) {
            return $this->forbidden();
        }

        $messages    = $db->get_messages($id);
        $all_att     = $db->get_attachments_for_ticket($id);
        $att_by_msg  = [];
        $att_no_msg  = [];
        foreach ($all_att as $att) {
            if ($att['message_id']) {
                $att_by_msg[(string) $att['message_id']][] = $this->format_attachment($att);
            } else {
                $att_no_msg[] = $this->format_attachment($att);
            }
        }

        return $this->ok([
            'ticket'      => $this->format_ticket($ticket),
            'messages'    => array_map(function($m) use ($att_by_msg) {
                return $this->format_message($m, $att_by_msg[(string) $m['id']] ?? []);
            }, $messages),
            'attachments' => $att_no_msg,
        ]);
    }

    public function send_message(WP_REST_Request $req): WP_REST_Response|WP_Error {
        $db      = Database::instance();
        $id      = (int) $req->get_param('id');
        $user_id = get_current_user_id();
        $body    = $req->get_param('body');

        $ticket = $db->get_ticket($id);
        if ($ticket === null) {
            return $this->not_found('Ticket not found.');
        }
        if ((int) $ticket['user_id'] !== $user_id && ! current_user_can('manage_options')) {
            return $this->forbidden();
        }
        if (in_array($ticket['status'], ['closed', 'ai_resolved'], true)) {
            return $this->error('ticket_closed', 'This ticket is closed.', 422);
        }

        $is_admin  = current_user_can('manage_options');
        $author    = $is_admin ? 'support' : 'user';
        $new_status = $is_admin ? 'pending' : 'unreviewed';

        $msg_id = $db->create_message($id, $user_id, $author, $body);
        if ($msg_id === false) {
            return $this->error('db_error', 'Failed to send message.', 500);
        }

        $db->update_ticket_status($id, $new_status);

        $messages = $db->get_messages($id);
        return $this->created([
            'messages' => array_map([$this, 'format_message'], $messages),
        ]);
    }

    public function ai_resolve(WP_REST_Request $req): WP_REST_Response|WP_Error {
        $db      = Database::instance();
        $id      = (int) $req->get_param('id');
        $user_id = get_current_user_id();

        $ticket = $db->get_ticket($id);
        if ($ticket === null) {
            return $this->not_found('Ticket not found.');
        }
        if ((int) $ticket['user_id'] !== $user_id) {
            return $this->forbidden();
        }
        if (in_array($ticket['status'], ['closed', 'ai_resolved'], true)) {
            return $this->error('already_closed', 'Ticket is already closed.', 422);
        }
        if (empty($ticket['ai_suggestion'])) {
            return $this->error('no_ai_suggestion', 'No AI suggestion available.', 422);
        }

        $db->resolve_ticket_with_ai($id, $ticket['ai_suggestion']);
        $ticket = $db->get_ticket($id);
        return $this->ok($this->format_ticket($ticket));
    }

    public function route_to_support(WP_REST_Request $req): WP_REST_Response|WP_Error {
        $db      = Database::instance();
        $id      = (int) $req->get_param('id');
        $user_id = get_current_user_id();

        $ticket = $db->get_ticket($id);
        if ($ticket === null) {
            return $this->not_found('Ticket not found.');
        }
        if ((int) $ticket['user_id'] !== $user_id) {
            return $this->forbidden();
        }
        if (in_array($ticket['status'], ['closed', 'ai_resolved', 'unreviewed'], true)) {
            return $this->error('already_routed', 'تیکت قبلاً به کارشناس ارجاع داده شده است.', 422);
        }

        $db->create_message($id, 0, 'system', 'سوال شما به کارشناس ارجاع داده شد و به زودی پاسخ خواهید گرفت.');
        $db->update_ticket_status($id, 'unreviewed');

        $messages = $db->get_messages($id);
        return $this->ok([
            'messages' => array_map([$this, 'format_message'], $messages),
        ]);
    }

    public function upload_attachment(WP_REST_Request $req): WP_REST_Response|WP_Error {
        $db      = Database::instance();
        $id      = (int) $req->get_param('id');
        $user_id = get_current_user_id();

        $ticket = $db->get_ticket($id);
        if ($ticket === null) {
            return $this->not_found('Ticket not found.');
        }
        if ((int) $ticket['user_id'] !== $user_id && ! current_user_can('manage_options')) {
            return $this->forbidden();
        }
        if (empty($_FILES['file'])) {
            return $this->error('no_file', 'No file provided.', 400);
        }

        $settings = (array) get_option('ats_settings', []);

        // Validate file size against admin-configured limit (default 5 MB).
        $max_kb   = max(1, (int) ($settings['maxUploadSize'] ?? 5120));
        $max_bytes = $max_kb * 1024;
        if ($_FILES['file']['size'] > $max_bytes) {
            return $this->error('file_too_large', sprintf('حداکثر حجم مجاز %d کیلوبایت است.', $max_kb), 400);
        }

        // Build allowed MIME list from settings; fall back to safe defaults.
        $all_mimes = self::all_allowed_mimes();
        $saved_keys = isset($settings['allowedMimeTypes']) && is_array($settings['allowedMimeTypes'])
            ? $settings['allowedMimeTypes']
            : array_keys($all_mimes);
        $allowed_mimes = array_intersect_key($all_mimes, array_flip($saved_keys));
        if (empty($allowed_mimes)) {
            $allowed_mimes = $all_mimes;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';

        $upload = wp_handle_upload($_FILES['file'], [
            'test_form' => false,
            'mimes'     => $allowed_mimes,
        ]);
        if (isset($upload['error'])) {
            return $this->error('upload_error', $upload['error'], 400);
        }

        $message_id = $req->get_param('message_id') ? (int) $req->get_param('message_id') : null;

        // Verify message_id belongs to this ticket (prevents cross-ticket IDOR).
        if ($message_id !== null) {
            $msg = $db->get_message($message_id);
            if ($msg === null || (int) $msg['ticket_id'] !== $id) {
                return $this->error('invalid_message', 'پیام مورد نظر متعلق به این تیکت نیست.', 422);
            }
        }
        $att_id = $db->create_attachment(
            $id,
            $message_id,
            $user_id,
            sanitize_file_name($_FILES['file']['name']),
            basename($upload['file']),
            $upload['url'],
            (int) filesize($upload['file']),
            $upload['type']
        );

        if ($att_id === false) {
            return $this->error('db_error', 'Failed to save attachment.', 500);
        }

        return $this->created([
            'id'       => (string) $att_id,
            'url'      => $upload['url'],
            'filename' => sanitize_file_name($_FILES['file']['name']),
            'size'     => (int) filesize($upload['file']),
            'mimeType' => $upload['type'],
        ]);
    }

    /**
     * Every MIME type the plugin is ever willing to accept.
     * Keys must match the format WordPress uses in upload_mimes: "ext1|ext2" => "mime/type".
     * Admin may restrict this list further via settings.
     */
    public static function all_allowed_mimes(): array {
        return [
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'gif'          => 'image/gif',
            'webp'         => 'image/webp',
            'pdf'          => 'application/pdf',
            'txt'          => 'text/plain',
            'zip'          => 'application/zip',
            'doc'          => 'application/msword',
            'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'          => 'application/vnd.ms-excel',
            'xlsx'         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];
    }

    public function categories_index(): WP_REST_Response {
        $cats = Database::instance()->get_categories();
        return $this->ok(array_map(fn($c) => [
            'id'          => (string) $c['id'],
            'title'       => $c['title'],
            'description' => $c['description'],
            'count'       => (int) ($c['answer_count'] ?? 0),
        ], $cats));
    }

    // ── Formatters ────────────────────────────────────────────────────────────

    private function format_ticket(array $t): array {
        $raw_body = strip_tags((string) ($t['first_body'] ?? ''));
        $raw_body = preg_replace('/\s+/', ' ', trim($raw_body));
        $preview  = mb_strlen($raw_body) > 120 ? mb_substr($raw_body, 0, 120) . '…' : $raw_body;

        return [
            'id'             => (string) $t['id'],
            'userId'         => (int) $t['user_id'],
            'userName'       => $t['user_name'] ?? null,
            'title'          => $t['title'],
            'status'         => $this->user_facing_status($t['status']),
            'adminState'     => $t['status'],
            'priority'       => $t['priority'],
            'categoryId'     => $t['category_id'] ? (int) $t['category_id'] : null,
            'categoryTitle'  => $t['category_title'] ?? null,
            'aiStatus'       => $t['ai_status'] ?? 'none',
            'aiSuggestion'   => $t['ai_suggestion'] ?? null,
            'aiResolved'     => (bool) ($t['ai_resolved'] ?? false),
            'preview'        => $preview,
            'createdAt'      => $t['created_at'],
            'updatedAt'      => $t['updated_at'],
        ];
    }

    private function format_message(array $m, array $attachments = []): array {
        return [
            'id'          => (string) $m['id'],
            'ticketId'    => (string) $m['ticket_id'],
            'authorType'  => $m['author_type'],
            'authorName'  => $m['author_name'] ?? ($m['author_type'] === 'support' ? 'پشتیبان' : 'کاربر'),
            'body'        => $m['body'],
            'createdAt'   => $m['created_at'],
            'attachments' => $attachments,
        ];
    }

    private function format_attachment(array $a): array {
        return [
            'id'       => (string) $a['id'],
            'url'      => $a['file_url'],
            'filename' => $a['filename'],
            'size'     => (int) $a['file_size'],
            'mimeType' => $a['mime_type'],
        ];
    }

    /** Maps internal status to what the end-user should see. */
    private function user_facing_status(string $status): string {
        return match ($status) {
            'unreviewed', 'reviewing', 'spam' => 'pending',
            'pending'                          => 'answered',
            'answered'                         => 'answered',
            'closed'                           => 'closed',
            'ai_resolved'                      => 'closed',
            default                            => 'pending',
        };
    }
}
