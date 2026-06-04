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
        set_transient('ats_activation_notice', true, 30);
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
        add_action('admin_notices',     [$this,                'maybe_show_activation_notice']);
        add_filter('plugin_action_links_' . plugin_basename(ATS_FILE), [$this, 'add_plugin_action_links']);
    }

    public function maybe_show_activation_notice(): void {
        if (! get_transient('ats_activation_notice')) {
            return;
        }
        delete_transient('ats_activation_notice');
        $user_url      = esc_url(home_url('helpdesk'));
        $admin_url     = esc_url(home_url('helpdesk-admin'));
        $permalink_url = esc_url(admin_url('options-permalink.php'));
        echo '<div class="notice notice-success is-dismissible"><p>';
        printf(
            wp_kses(
                __('<strong>WP AI Support با موفقیت فعال شد.</strong> لینک‌های دسترسی: <a href="%1$s">پنل کاربری</a> · <a href="%2$s">پنل مدیریت</a> — اگر لینک‌ها ۴۰۴ می‌دهند، یک بار <a href="%3$s">پیوندهای یکتا</a> را ذخیره کنید.', 'ai-ticket-support'),
                ['strong' => [], 'a' => ['href' => []]]
            ),
            $user_url,
            $admin_url,
            $permalink_url
        );
        echo '</p></div>';
    }

    public function add_plugin_action_links(array $links): array {
        $extra = [
            'ats_user_panel'  => sprintf(
                '<a href="%s">%s</a>',
                esc_url(home_url('/helpdesk')),
                esc_html__('پنل کاربری', 'ai-ticket-support')
            ),
            'ats_admin_panel' => sprintf(
                '<a href="%s">%s</a>',
                esc_url(home_url('/helpdesk-admin')),
                esc_html__('پنل مدیریت', 'ai-ticket-support')
            ),
        ];
        return array_merge($extra, $links);
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
