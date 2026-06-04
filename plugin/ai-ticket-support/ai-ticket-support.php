<?php
/**
 * Plugin Name: WP AI Support
 * Plugin URI:  http://wpaisupport.ir/
 * Description: پشتیبانی هوشمند برای وردپرس — مدیریت تیکت با هوش مصنوعی و پایگاه دانش
 * Version:     1.0.0
 * Author:      Mohamad Gandomi
 * Text Domain: wp-ai-support
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP:      8.1
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

define('ATS_VERSION', '1.0.0');
define('ATS_FILE',    __FILE__);
define('ATS_DIR',     plugin_dir_path(__FILE__));
define('ATS_URL',     plugin_dir_url(__FILE__));
define('ATS_SLUG',    'ai-ticket-support');

// PSR-4 style autoloader for the ATS\ namespace
spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'ATS\\', 4) !== 0) {
        return;
    }
    $relative = substr($class, 4);
    $file = ATS_DIR . 'includes/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

register_activation_hook(__FILE__,   [ATS\Plugin::class, 'activate']);
register_deactivation_hook(__FILE__, [ATS\Plugin::class, 'deactivate']);

require_once ATS_DIR . 'rtl-license.php';

ATS\Plugin::instance()->boot();
