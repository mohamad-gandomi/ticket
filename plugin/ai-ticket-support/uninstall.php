<?php
/**
 * Runs when the plugin is deleted (not just deactivated).
 * Drops all custom tables, deletes uploaded attachment files, and removes plugin options.
 */
declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;

// Delete physical attachment files before dropping the table.
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$attachments = $wpdb->get_results(
    "SELECT file_url FROM `{$wpdb->prefix}ats_attachments`",
    ARRAY_A
);
if ($attachments) {
    $upload_dir = wp_upload_dir();
    $base_url   = $upload_dir['baseurl'];
    $base_dir   = $upload_dir['basedir'];
    foreach ($attachments as $att) {
        $norm_url  = preg_replace('#^https?://#', '//', $att['file_url']);
        $norm_base = preg_replace('#^https?://#', '//', $base_url);
        if (str_starts_with($norm_url, $norm_base)) {
            $relative = ltrim(substr($norm_url, strlen($norm_base)), '/');
            $path     = trailingslashit($base_dir) . $relative;
            if (file_exists($path)) {
                @unlink($path);
            }
        }
    }
}

$tables = [
    $wpdb->prefix . 'ats_messages',
    $wpdb->prefix . 'ats_attachments',
    $wpdb->prefix . 'ats_tickets',
    $wpdb->prefix . 'ats_saved_answers',
    $wpdb->prefix . 'ats_categories',
];

foreach ($tables as $table) {
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
}

delete_option('ats_settings');
delete_option('ats_db_version');
