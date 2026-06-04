<?php
declare(strict_types=1);

namespace ATS;

/**
 * Registers two WordPress URLs that bypass the theme entirely:
 *   /helpdesk        → user-facing React app
 *   /helpdesk-admin  → admin React app (requires manage_options capability)
 */
final class Pages {

    private const QUERY_VAR  = 'ats_page';
    private const USER_SLUG  = 'helpdesk';
    private const ADMIN_SLUG = 'helpdesk-admin';

    private static ?self $instance = null;

    private function __construct() {}

    public static function instance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_rewrite_rules(): void {
        add_rewrite_tag('%' . self::QUERY_VAR . '%', '([^&]+)');

        // Match /helpdesk and any sub-path like /helpdesk/tickets/123
        add_rewrite_rule(
            '^' . self::USER_SLUG  . '(/.*)?$',
            'index.php?' . self::QUERY_VAR . '=user',
            'top'
        );
        add_rewrite_rule(
            '^' . self::ADMIN_SLUG . '(/.*)?$',
            'index.php?' . self::QUERY_VAR . '=admin',
            'top'
        );
    }

    /** Called once on plugin activation to ensure rules are written to .htaccess. */
    public function flush_rules(): void {
        $this->register_rewrite_rules();
        flush_rewrite_rules();
    }

    public function maybe_serve_app(): void {
        $mode = get_query_var(self::QUERY_VAR);
        if ($mode === '') {
            return;
        }

        // Require login for both pages
        if (! is_user_logged_in()) {
            $redirect = home_url($mode === 'admin' ? self::ADMIN_SLUG : self::USER_SLUG);
            wp_safe_redirect(wp_login_url($redirect));
            exit;
        }

        // Admin page requires manage_options capability
        if ($mode === 'admin' && ! current_user_can('manage_options')) {
            wp_die(
                esc_html__('Access denied. Administrator privileges required.', 'ai-ticket-support'),
                403
            );
        }

        if ($mode === 'admin' && ! has_action('ats_admin_active')) {
            include ATS_DIR . 'templates/license-required.php';
            exit;
        }

        $page_title = $mode === 'admin'
            ? __('Support Admin', 'ai-ticket-support')
            : __('Helpdesk', 'ai-ticket-support');

        include ATS_DIR . 'templates/app-shell.php';
        exit;
    }
}
