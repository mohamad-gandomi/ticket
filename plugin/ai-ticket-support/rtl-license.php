<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

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
