<?php
/**
 * Plugin Name: AffiChat - WhatsApp Gateway for WordPress & WooCommerce
 * Plugin URI:  https://chat.affidev.com
 * Description: Integrasi WhatsApp Gateway serbaguna untuk WordPress & WooCommerce: Kirim notifikasi transaksi toko, pesan cepat langsung dari WP-Admin, auto-reply formulir website (Elementor, JetFormBuilder, CF7, WPForms, Fluent Forms), dan REST API pengembang.
 * Version:     1.0.0
 * Author:      AffiChat
 * Author URI:  https://chat.affidev.com
 * License:     GPL-2.0+
 * Text Domain: affichat-wp
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.3
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AFFICHAT_WP_VERSION', '1.0.0');
define('AFFICHAT_WP_PATH', plugin_dir_path(__FILE__));
define('AFFICHAT_WP_URL', plugin_dir_url(__FILE__));
define('AFFICHAT_WP_BASENAME', plugin_basename(__FILE__));
define('AFFICHAT_WP_API_BASE_URL', 'https://chat.affidev.com');
define('AFFICHAT_WP_GITHUB_REPO', 'eabdalmufid/affichat-integrations');

// Declare compatibility with WooCommerce High-Performance Order Storage (HPOS).
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Initializes AffiChat for WordPress.
 */
function affichat_wp_init() {
    require_once AFFICHAT_WP_PATH . 'includes/class-affichat-api.php';
    require_once AFFICHAT_WP_PATH . 'includes/class-affichat-tags.php';
    require_once AFFICHAT_WP_PATH . 'includes/class-affichat-forms.php';
    require_once AFFICHAT_WP_PATH . 'includes/class-affichat-woocommerce.php';
    require_once AFFICHAT_WP_PATH . 'includes/class-affichat-admin.php';
    require_once AFFICHAT_WP_PATH . 'includes/class-affichat-updater.php';

    AffiChat_WP_Forms::init();
    AffiChat_WP_Admin::init();
    AffiChat_WP_Updater::init();

    if (class_exists('WooCommerce')) {
        AffiChat_WP_WooCommerce::init();
    }
}
add_action('plugins_loaded', 'affichat_wp_init');

add_filter('plugin_action_links_' . AFFICHAT_WP_BASENAME, function ($links) {
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=affichat-wp')) . '">' . esc_html__('Pengaturan', 'affichat-wp') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
});
