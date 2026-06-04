<?php
declare(strict_types=1);

namespace ATS;

final class Database {

    private const DB_VERSION = '1.3.0';

    private static ?self $instance = null;

    private function __construct(private readonly \wpdb $db) {}

    public static function instance(): self {
        if (self::$instance === null) {
            global $wpdb;
            self::$instance = new self($wpdb);
        }
        return self::$instance;
    }

    public function maybe_upgrade(): void {
        if (get_option('ats_db_version') !== self::DB_VERSION) {
            $this->install();
        }
    }

    public function install(): void {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $this->db->get_charset_collate();

        dbDelta("CREATE TABLE {$this->db->prefix}ats_categories (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title       VARCHAR(255)    NOT NULL,
            description TEXT,
            created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB {$charset};");

        dbDelta("CREATE TABLE {$this->db->prefix}ats_tickets (
            id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id       BIGINT UNSIGNED NOT NULL,
            title         VARCHAR(255)    NOT NULL,
            status        VARCHAR(20)     NOT NULL DEFAULT 'unreviewed',
            priority      VARCHAR(10)     NOT NULL DEFAULT 'low',
            category_id   BIGINT UNSIGNED DEFAULT NULL,
            ai_status     VARCHAR(10)     NOT NULL DEFAULT 'none',
            ai_suggestion TEXT            NULL,
            ai_resolved   TINYINT(1)      NOT NULL DEFAULT 0,
            created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user   (user_id),
            KEY idx_status (status)
        ) ENGINE=InnoDB {$charset};");

        dbDelta("CREATE TABLE {$this->db->prefix}ats_messages (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_id   BIGINT UNSIGNED NOT NULL,
            user_id     BIGINT UNSIGNED NOT NULL,
            author_type VARCHAR(10)     NOT NULL DEFAULT 'user',
            body        TEXT            NOT NULL,
            created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ticket (ticket_id)
        ) ENGINE=InnoDB {$charset};");

        dbDelta("CREATE TABLE {$this->db->prefix}ats_saved_answers (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            category_id BIGINT UNSIGNED DEFAULT NULL,
            title       VARCHAR(255)    NOT NULL,
            body        TEXT            NOT NULL,
            created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_category (category_id)
        ) ENGINE=InnoDB {$charset};");

        dbDelta("CREATE TABLE {$this->db->prefix}ats_attachments (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_id   BIGINT UNSIGNED NOT NULL,
            message_id  BIGINT UNSIGNED DEFAULT NULL,
            user_id     BIGINT UNSIGNED NOT NULL,
            filename    VARCHAR(255)    NOT NULL,
            stored_name VARCHAR(255)    NOT NULL,
            file_url    VARCHAR(512)    NOT NULL,
            file_size   BIGINT UNSIGNED NOT NULL DEFAULT 0,
            mime_type   VARCHAR(100)    NOT NULL DEFAULT '',
            created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ticket  (ticket_id),
            KEY idx_message (message_id)
        ) ENGINE=InnoDB {$charset};");

        update_option('ats_db_version', self::DB_VERSION);
    }

    // ── Tickets ──────────────────────────────────────────────────────────────

    public function get_tickets_for_user(int $user_id, int $page = 1, int $per_page = 20, array $statuses = []): array {
        $offset = ($page - 1) * $per_page;
        [$where, $values] = $this->user_ticket_where($user_id, $statuses);
        $values[] = $per_page;
        $values[] = $offset;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT t.*, c.title AS category_title,
                        (SELECT body FROM {$this->db->prefix}ats_messages
                         WHERE ticket_id = t.id ORDER BY created_at ASC LIMIT 1) AS first_body
                 FROM {$this->db->prefix}ats_tickets t
                 LEFT JOIN {$this->db->prefix}ats_categories c ON c.id = t.category_id
                 WHERE {$where}
                 ORDER BY t.created_at DESC
                 LIMIT %d OFFSET %d",
                ...$values
            ),
            ARRAY_A
        );
    }

    public function count_tickets_for_user(int $user_id, array $statuses = []): int {
        [$where, $values] = $this->user_ticket_where($user_id, $statuses);
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return (int) $this->db->get_var(
            $this->db->prepare(
                "SELECT COUNT(*) FROM {$this->db->prefix}ats_tickets t WHERE {$where}",
                ...$values
            )
        );
    }

    private function user_ticket_where(int $user_id, array $statuses): array {
        $where  = 't.user_id = %d';
        $values = [$user_id];
        if (! empty($statuses)) {
            $placeholders = implode(', ', array_fill(0, count($statuses), '%s'));
            $where       .= " AND t.status IN ({$placeholders})";
            $values       = array_merge($values, $statuses);
        }
        return [$where, $values];
    }

    public function get_all_tickets(array $filters = [], int $page = 1, int $per_page = 20): array {
        $where  = ['1=1'];
        $values = [];

        if (!empty($filters['status'])) {
            $where[]  = 'status = %s';
            $values[] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $where[]  = 'priority = %s';
            $values[] = $filters['priority'];
        }
        if (!empty($filters['search'])) {
            $where[]  = "(t.title LIKE %s OR CAST(t.id AS CHAR) LIKE %s OR u.display_name LIKE %s)";
            $like     = '%' . $this->db->esc_like($filters['search']) . '%';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        $sql_where = implode(' AND ', $where);
        $offset    = ($page - 1) * $per_page;
        $base      = "SELECT t.*, u.display_name AS user_name, c.title AS category_title,
                             (SELECT body FROM {$this->db->prefix}ats_messages
                              WHERE ticket_id = t.id ORDER BY created_at ASC LIMIT 1) AS first_body
                      FROM {$this->db->prefix}ats_tickets t
                      LEFT JOIN {$this->db->prefix}users u ON u.ID = t.user_id
                      LEFT JOIN {$this->db->prefix}ats_categories c ON c.id = t.category_id
                      WHERE {$sql_where}";

        $query = $values
            ? $this->db->prepare("{$base} ORDER BY t.updated_at DESC LIMIT %d OFFSET %d", ...[...$values, $per_page, $offset])
            : "{$base} ORDER BY t.updated_at DESC LIMIT {$per_page} OFFSET {$offset}";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $this->db->get_results($query, ARRAY_A);
    }

    public function count_all_tickets(array $filters = []): int {
        $where  = ['1=1'];
        $values = [];

        if (!empty($filters['status'])) {
            $where[]  = 't.status = %s';
            $values[] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $where[]  = 't.priority = %s';
            $values[] = $filters['priority'];
        }
        if (!empty($filters['search'])) {
            $where[]  = "(t.title LIKE %s OR CAST(t.id AS CHAR) LIKE %s OR u.display_name LIKE %s)";
            $like     = '%' . $this->db->esc_like($filters['search']) . '%';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        $sql_where = implode(' AND ', $where);
        $base      = "SELECT COUNT(*) FROM {$this->db->prefix}ats_tickets t
                      LEFT JOIN {$this->db->prefix}users u ON u.ID = t.user_id
                      WHERE {$sql_where}";

        $query = $values
            ? $this->db->prepare($base, ...$values)
            : $base;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return (int) $this->db->get_var($query);
    }

    public function get_ticket(int $id): ?array {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT t.*, u.display_name AS user_name, c.title AS category_title
                 FROM {$this->db->prefix}ats_tickets t
                 LEFT JOIN {$this->db->prefix}users u ON u.ID = t.user_id
                 LEFT JOIN {$this->db->prefix}ats_categories c ON c.id = t.category_id
                 WHERE t.id = %d",
                $id
            ),
            ARRAY_A
        ) ?: null;
    }

    public function create_ticket(int $user_id, string $title, string $priority, ?int $category_id): int|false {
        $result = $this->db->insert(
            $this->db->prefix . 'ats_tickets',
            [
                'user_id'     => $user_id,
                'title'       => $title,
                'status'      => 'unreviewed',
                'priority'    => $priority,
                'category_id' => $category_id,
                'updated_at'  => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', $category_id ? '%d' : '%s', '%s']
        );
        return $result !== false ? (int) $this->db->insert_id : false;
    }

    public function update_ticket_status(int $id, string $status): bool {
        return (bool) $this->db->update(
            $this->db->prefix . 'ats_tickets',
            ['status' => $status, 'updated_at' => current_time('mysql')],
            ['id'     => $id],
            ['%s', '%s'],
            ['%d']
        );
    }

    public function delete_ticket(int $id): void {
        $attachments = $this->get_attachments_for_ticket($id);
        foreach ($attachments as $att) {
            $path = $this->resolve_attachment_path($att['file_url']);
            if ($path !== null && file_exists($path)) {
                @unlink($path);
            }
        }
        $this->db->delete($this->db->prefix . 'ats_attachments', ['ticket_id' => $id], ['%d']);
        $this->db->delete($this->db->prefix . 'ats_messages',    ['ticket_id' => $id], ['%d']);
        $this->db->delete($this->db->prefix . 'ats_tickets',     ['id'        => $id], ['%d']);
    }

    public function resolve_attachment_path(string $file_url): ?string {
        $upload_dir = wp_upload_dir();
        // Normalise schemes so http:// vs https:// differences don't break the match.
        $norm_url  = preg_replace('#^https?://#', '//', $file_url);
        $norm_base = preg_replace('#^https?://#', '//', $upload_dir['baseurl']);
        if (str_starts_with($norm_url, $norm_base)) {
            $relative = ltrim(substr($norm_url, strlen($norm_base)), '/');
            return trailingslashit($upload_dir['basedir']) . $relative;
        }
        return null;
    }

    public function resolve_ticket_with_ai(int $ticket_id, string $ai_body): bool {
        $inserted = $this->db->insert(
            $this->db->prefix . 'ats_messages',
            ['ticket_id' => $ticket_id, 'user_id' => 0, 'author_type' => 'support', 'body' => $ai_body],
            ['%d', '%d', '%s', '%s']
        );
        if ($inserted === false) {
            return false;
        }
        return (bool) $this->db->update(
            $this->db->prefix . 'ats_tickets',
            ['status' => 'ai_resolved', 'ai_resolved' => 1, 'updated_at' => current_time('mysql')],
            ['id' => $ticket_id],
            ['%s', '%d', '%s'],
            ['%d']
        );
    }

    public function count_ai_resolved(): int {
        return (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->db->prefix}ats_tickets WHERE ai_resolved = 1"
        );
    }

    public function save_ai_suggestion(int $ticket_id, ?string $suggestion): bool {
        $status = $suggestion !== null ? 'done' : 'failed';
        return (bool) $this->db->update(
            $this->db->prefix . 'ats_tickets',
            ['ai_status' => $status, 'ai_suggestion' => $suggestion, 'updated_at' => current_time('mysql')],
            ['id'        => $ticket_id],
            ['%s', $suggestion !== null ? '%s' : '%s'],
            ['%d']
        );
    }

    // ── Messages ─────────────────────────────────────────────────────────────

    public function get_message(int $id): ?array {
        return $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}ats_messages WHERE id = %d",
                $id
            ),
            ARRAY_A
        ) ?: null;
    }

    public function get_messages(int $ticket_id): array {
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT m.*, u.display_name AS author_name
                 FROM {$this->db->prefix}ats_messages m
                 LEFT JOIN {$this->db->prefix}users u ON u.ID = m.user_id
                 WHERE m.ticket_id = %d
                 ORDER BY m.created_at ASC",
                $ticket_id
            ),
            ARRAY_A
        );
    }

    public function create_message(int $ticket_id, int $user_id, string $author_type, string $body): int|false {
        $result = $this->db->insert(
            $this->db->prefix . 'ats_messages',
            [
                'ticket_id'   => $ticket_id,
                'user_id'     => $user_id,
                'author_type' => $author_type,
                'body'        => $body,
            ],
            ['%d', '%d', '%s', '%s']
        );
        return $result !== false ? (int) $this->db->insert_id : false;
    }

    // ── Categories ───────────────────────────────────────────────────────────

    public function get_categories(): array {
        return $this->db->get_results(
            "SELECT c.*, (SELECT COUNT(*) FROM {$this->db->prefix}ats_saved_answers sa WHERE sa.category_id = c.id) AS answer_count
             FROM {$this->db->prefix}ats_categories c
             ORDER BY c.title ASC",
            ARRAY_A
        );
    }

    public function create_category(string $title, string $description): int|false {
        $result = $this->db->insert(
            $this->db->prefix . 'ats_categories',
            ['title' => $title, 'description' => $description],
            ['%s', '%s']
        );
        return $result !== false ? (int) $this->db->insert_id : false;
    }

    public function update_category(int $id, string $title, string $description): bool {
        return (bool) $this->db->update(
            $this->db->prefix . 'ats_categories',
            ['title' => $title, 'description' => $description],
            ['id'    => $id],
            ['%s', '%s'],
            ['%d']
        );
    }

    public function delete_category(int $id): bool {
        return (bool) $this->db->delete(
            $this->db->prefix . 'ats_categories',
            ['id' => $id],
            ['%d']
        );
    }

    // ── Saved Answers ─────────────────────────────────────────────────────────

    public function get_saved_answers(?int $category_id = null): array {
        if ($category_id !== null) {
            return $this->db->get_results(
                $this->db->prepare(
                    "SELECT a.*, c.title AS category_title
                     FROM {$this->db->prefix}ats_saved_answers a
                     LEFT JOIN {$this->db->prefix}ats_categories c ON c.id = a.category_id
                     WHERE a.category_id = %d
                     ORDER BY a.title ASC",
                    $category_id
                ),
                ARRAY_A
            );
        }
        return $this->db->get_results(
            "SELECT a.*, c.title AS category_title
             FROM {$this->db->prefix}ats_saved_answers a
             LEFT JOIN {$this->db->prefix}ats_categories c ON c.id = a.category_id
             ORDER BY a.title ASC",
            ARRAY_A
        );
    }

    public function create_saved_answer(string $title, string $body, ?int $category_id): int|false {
        $result = $this->db->insert(
            $this->db->prefix . 'ats_saved_answers',
            ['title' => $title, 'body' => $body, 'category_id' => $category_id],
            ['%s', '%s', $category_id ? '%d' : '%s']
        );
        return $result !== false ? (int) $this->db->insert_id : false;
    }

    public function update_saved_answer(int $id, string $title, string $body, ?int $category_id): bool {
        return (bool) $this->db->update(
            $this->db->prefix . 'ats_saved_answers',
            ['title' => $title, 'body' => $body, 'category_id' => $category_id, 'updated_at' => current_time('mysql')],
            ['id' => $id],
            ['%s', '%s', $category_id ? '%d' : '%s', '%s'],
            ['%d']
        );
    }

    public function delete_saved_answer(int $id): bool {
        return (bool) $this->db->delete(
            $this->db->prefix . 'ats_saved_answers',
            ['id' => $id],
            ['%d']
        );
    }

    // ── Attachments ───────────────────────────────────────────────────────────

    public function create_attachment(int $ticket_id, ?int $message_id, int $user_id, string $filename, string $stored_name, string $file_url, int $file_size, string $mime_type): int|false {
        $result = $this->db->insert(
            $this->db->prefix . 'ats_attachments',
            [
                'ticket_id'   => $ticket_id,
                'message_id'  => $message_id,
                'user_id'     => $user_id,
                'filename'    => $filename,
                'stored_name' => $stored_name,
                'file_url'    => $file_url,
                'file_size'   => $file_size,
                'mime_type'   => $mime_type,
            ],
            ['%d', $message_id !== null ? '%d' : '%s', '%d', '%s', '%s', '%s', '%d', '%s']
        );
        return $result !== false ? (int) $this->db->insert_id : false;
    }

    public function get_attachments_for_ticket(int $ticket_id): array {
        return $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->db->prefix}ats_attachments WHERE ticket_id = %d ORDER BY created_at ASC",
                $ticket_id
            ),
            ARRAY_A
        );
    }
}
