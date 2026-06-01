<?php
/**
 * Fully standalone HTML shell — zero WordPress theme output.
 * Reads the Vite manifest directly and outputs only this plugin's assets.
 */
declare(strict_types=1);

defined('ABSPATH') || exit;

$manifest_path = ATS_DIR . 'assets/dist/.vite/manifest.json';
if (! file_exists($manifest_path)) {
    wp_die('WP AI Support: asset manifest not found. Run <code>npm run build</code> first.', 500);
}

$manifest = json_decode((string) file_get_contents($manifest_path), true);
$entry    = $manifest['src/main.tsx'] ?? null;
if ($entry === null) {
    wp_die('WP AI Support: entry point missing from manifest.', 500);
}

$dist_url  = ATS_URL . 'assets/dist/';
$mode      = get_query_var('ats_page');
$user      = wp_get_current_user();

// ── Brand color CSS variables ────────────────────────────────────────────────
// Read the saved brand color and compute all derived shades here in PHP so the
// CSS custom properties are already correct on first paint — no JS flash.
$saved_settings = (array) get_option('ats_settings', []);
$brand_hex      = sanitize_hex_color($saved_settings['brandColor'] ?? '') ?: '#0068ff';
$bh             = ltrim($brand_hex, '#');
$r              = hexdec(substr($bh, 0, 2));
$g              = hexdec(substr($bh, 2, 2));
$b              = hexdec(substr($bh, 4, 2));
$brand_css      = sprintf(
    ':root{--brand:%d %d %d;--brand-dark:%d %d %d;--brand-tint:%d %d %d;--brand-soft:%d %d %d}',
    $r, $g, $b,
    (int) round($r * .91), (int) round($g * .91), (int) round($b * .91),
    (int) round(255 * .9 + $r * .1), (int) round(255 * .9 + $g * .1), (int) round(255 * .9 + $b * .1),
    (int) round(255 * .69 + $r * .31), (int) round(255 * .69 + $g * .31), (int) round(255 * .69 + $b * .31)
);

// Parse the path portion so React Router basename works regardless of
// whether WordPress is installed in a subdirectory (e.g. /wordpress/).
$user_path  = (string) parse_url(home_url('helpdesk'),       PHP_URL_PATH);
$admin_path = (string) parse_url(home_url('helpdesk-admin'), PHP_URL_PATH);
$base_path  = $mode === 'admin' ? $admin_path : $user_path;

$config = [
    'mode'       => $mode,
    'restUrl'    => rest_url('ats/v1'),
    'nonce'      => wp_create_nonce('wp_rest'),
    'assetsUrl'  => $dist_url,
    'basePath'   => $base_path,
    'brandColor' => $brand_hex,
    'user'       => [
        'id'      => $user->ID,
        'name'    => $user->display_name,
        'email'   => $user->user_email,
        'isAdmin' => current_user_can('manage_options'),
    ],
];

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($page_title ?? get_bloginfo('name')); ?></title>
    <?php foreach ($entry['css'] ?? [] as $css_file): ?>
    <link rel="stylesheet" href="<?php echo esc_url($dist_url . $css_file); ?>">
    <?php endforeach; ?>
    <?php /* Brand overrides MUST come after the linked stylesheet — the compiled
             Tailwind CSS has :root defaults in it, so this inline block wins. */ ?>
    <style><?php echo $brand_css; // phpcs:ignore WordPress.Security.EscapeOutput ?></style>
    <script>
    (function(){
        var s=getComputedStyle(document.documentElement);
        console.log('[ATS brand]','--brand:',s.getPropertyValue('--brand').trim(),'config:',window.atsConfig&&window.atsConfig.brandColor);
    })();
    </script>
</head>
<body>
    <div id="root"></div>
    <script>window.atsConfig = <?php echo wp_json_encode($config); ?>;</script>
    <script type="module" src="<?php echo esc_url($dist_url . $entry['file']); ?>"></script>
</body>
</html>
