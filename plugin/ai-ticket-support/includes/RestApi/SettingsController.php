<?php
declare(strict_types=1);

namespace ATS\RestApi;

use WP_REST_Request;
use WP_REST_Response;

/**
 * GET  /ats/v1/admin/settings  – fetch all plugin settings
 * POST /ats/v1/admin/settings  – persist all plugin settings
 */
final class SettingsController extends AbstractController {

    private const OPTION_KEY = 'ats_settings';

    private const DEFAULTS = [
        'aiEnabled'      => false,
        'aiMode'         => 'kb_only',
        'brandColor'     => '#0068ff',
        'providers'      => [],
        'aiTopK'         => 4,
        'aiMaxBodyChars' => 1200,
    ];

    public function register_routes(): void {
        $admin = [$this, 'require_admin'];

        register_rest_route(self::NAMESPACE, '/admin/settings', [
            ['methods' => 'GET',  'callback' => [$this, 'show'],  'permission_callback' => $admin],
            ['methods' => 'POST', 'callback' => [$this, 'store'], 'permission_callback' => $admin,
             'args' => [
                'aiEnabled'  => ['type' => 'boolean', 'default' => false],
                'brandColor' => ['type' => 'string',  'default' => '#0068ff',
                                 'sanitize_callback' => 'sanitize_hex_color'],
                // providers, aiTopK, aiMaxBodyChars read from raw JSON body
             ]],
        ]);

        register_rest_route(self::NAMESPACE, '/admin/test-provider', [
            'methods'             => 'POST',
            'callback'            => [$this, 'test_provider'],
            'permission_callback' => $admin,
        ]);
    }

    public function show(): WP_REST_Response {
        $saved    = (array) get_option(self::OPTION_KEY, self::DEFAULTS);
        $settings = array_merge(self::DEFAULTS, $saved);
        // ensure providers is always a JSON object, never a JSON array
        if (empty($settings['providers']) || ! is_array($settings['providers'])) {
            $settings['providers'] = (object) [];
        }
        return $this->ok($settings);
    }

    public function store(WP_REST_Request $req): WP_REST_Response {
        // providers is a JSON object keyed by provider id; get_param() coerces objects to
        // indexed arrays when 'type'=>'array' is declared, so read from raw JSON body instead.
        $json      = $req->get_json_params() ?? [];
        $providers = isset($json['providers']) && is_array($json['providers']) ? $json['providers'] : [];

        $ai_mode = in_array($json['aiMode'] ?? '', ['kb_only', 'ai_enhanced'], true)
            ? $json['aiMode']
            : 'kb_only';

        $settings = [
            'aiEnabled'      => (bool) $req->get_param('aiEnabled'),
            'aiMode'         => $ai_mode,
            'brandColor'     => sanitize_hex_color($req->get_param('brandColor')) ?: '#0068ff',
            'providers'      => $this->sanitize_providers($providers),
            'aiTopK'         => max(1, min(10,   (int) ($json['aiTopK']         ?? 4))),
            'aiMaxBodyChars' => max(100, min(2000, (int) ($json['aiMaxBodyChars'] ?? 1200))),
        ];

        update_option(self::OPTION_KEY, $settings);
        return $this->ok($settings);
    }

    public function test_provider(WP_REST_Request $req): WP_REST_Response {
        $json     = $req->get_json_params() ?? [];
        $provider = sanitize_key($json['provider'] ?? '');
        $api_key  = sanitize_text_field($json['apiKey']  ?? '');
        $model    = sanitize_text_field($json['model']   ?? '');

        if (empty($api_key)) {
            return $this->ok(['success' => false, 'message' => 'کلید API وارد نشده است']);
        }

        if ($provider !== 'gapcode') {
            return $this->ok(['success' => false, 'message' => 'تست مستقیم برای این ارائه‌دهنده پشتیبانی نمی‌شود']);
        }

        $client = new \ATS\GapGptClient($api_key);
        $result = $client->respond(
            $model ?: 'gapgpt-qwen-3.5',
            'You are a helpful assistant.',
            'Reply with exactly one word: OK'
        );

        if ($result !== null) {
            return $this->ok(['success' => true,  'message' => 'اتصال با موفقیت برقرار شد']);
        }

        return $this->ok(['success' => false, 'message' => 'اتصال ناموفق — کلید API را بررسی کنید']);
    }

    private function sanitize_providers(array $providers): array {
        $allowed_ids = ['chatgpt', 'claude', 'gemini', 'gapcode'];
        $sanitized   = [];

        foreach ($providers as $id => $config) {
            if (! in_array($id, $allowed_ids, true) || ! is_array($config)) {
                continue;
            }
            $sanitized[$id] = [
                'enabled' => (bool) ($config['enabled'] ?? false),
                'apiKey'  => sanitize_text_field($config['apiKey'] ?? ''),
                'model'   => sanitize_text_field($config['model']  ?? ''),
            ];
        }

        return $sanitized;
    }
}
