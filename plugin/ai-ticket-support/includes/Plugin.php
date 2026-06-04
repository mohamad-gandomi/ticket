<?php
declare(strict_types=1);

namespace ATS;

final class Plugin {

    private static ?self $instance = null;

    private function __construct() {}

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function activate(): void {
        Database::instance()->install();
        Pages::instance()->flush_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    public function boot(): void {
        add_action('init',              [Database::instance(), 'maybe_upgrade']);
        add_action('init',              [Pages::instance(),    'register_rewrite_rules']);
        add_action('template_redirect', [Pages::instance(),    'maybe_serve_app']);
        add_action('rest_api_init',     [$this,                'register_rest_routes']);
        add_action('admin_notices',     [$this,                'maybe_show_permalink_notice']);
    }

    public function maybe_show_permalink_notice(): void {
        // The helpdesk routes only work with pretty permalinks (any structure except plain).
        if (get_option('permalink_structure') !== '') {
            return;
        }
        $settings_url = admin_url('options-permalink.php');
        echo '<div class="notice notice-error"><p>';
        printf(
            /* translators: %s: URL to the Permalinks settings page */
            wp_kses(
                __('<strong>WP AI Support:</strong> آدرس‌های <code>/helpdesk</code> و <code>/helpdesk-admin</code> کار نمی‌کنند چون ساختار پیوند یکتا روی «ساده» تنظیم شده است. لطفاً به <a href="%s">تنظیمات › پیوندهای یکتا</a> بروید و یک ساختار (مثلاً «نام نوشته») انتخاب کرده، سپس ذخیره کنید.', 'ai-ticket-support'),
                [
                    'strong' => [],
                    'code'   => [],
                    'a'      => ['href' => []],
                ]
            ),
            esc_url($settings_url)
        );
        echo '</p></div>';
    }

    public function register_rest_routes(): void {
        (new RestApi\TicketsController())->register_routes();
        (new RestApi\AdminController())->register_routes();
        (new RestApi\SettingsController())->register_routes();
    }
}
