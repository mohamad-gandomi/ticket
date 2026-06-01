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

// --------------------------------------------------------------------------------------------------- Start RTL License
$rtlLicenseClassName  = 'RTL_License_94bdc51e29c098e1';
$rtlLicenseFilePath   = __DIR__ . DIRECTORY_SEPARATOR . $rtlLicenseClassName . '.php';
$rtlLicenseFileHash   = @sha1_file($rtlLicenseFilePath);

if ( $rtlLicenseFileHash === 'c5dafeb01a140ca6468f8d9b34cb2caa953013d4' && file_exists($rtlLicenseFilePath) ) {
	require_once $rtlLicenseFilePath;

	if ( class_exists($rtlLicenseClassName) && method_exists($rtlLicenseClassName, 'isActive') ) {
		$rtlLicenseClass = new $rtlLicenseClassName();

		if ( $rtlLicenseClass->{'isActive'}() === true ) {
			// Product is Active Now, Enable Pro Features
			add_action('ats_admin_active', '__return_true');
		}
	}
}
// ----------------------------------------------------------------------------------------------------- End RTL License

ATS\Plugin::instance()->boot();
