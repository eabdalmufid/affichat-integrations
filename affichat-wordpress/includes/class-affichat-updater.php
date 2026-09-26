<?php
/**
 * Auto-update handler for AffiChat WordPress & WooCommerce plugin.
 *
 * Hooks into the native WordPress update pipeline to enable one-click updates
 * from GitHub Releases asset packages.
 *
 * @package AffiChat_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiChat_WP_Updater {
    /**
     * GitHub repository path in owner/repo format.
     */
    const GITHUB_REPO = 'eabdalmufid/affichat-integrations';

    /**
     * Transient cache key for release metadata.
     */
    const CACHE_KEY = 'affichat_wp_release_info';

    /**
     * Cache lifetime for remote version checks.
     * Prevents exceeding GitHub API rate limits (60 unauthenticated requests/hour).
     */
    const CACHE_TTL = 21600; // 6 hours

    /**
     * Initializes updater hooks on admin screens and background cron updates.
     */
    public static function init() {
        $instance = new self();
        add_filter('pre_set_site_transient_update_plugins', [$instance, 'check_update']);
        add_filter('site_transient_update_plugins', [$instance, 'check_update']);
        add_filter('plugins_api', [$instance, 'plugin_info'], 20, 3);
        add_filter('upgrader_post_install', [$instance, 'post_install'], 10, 3);
        add_action('admin_post_affichat_check_update', [$instance, 'handle_manual_check']);
        add_action('admin_notices', [$instance, 'render_admin_notice']);
    }

    /**
     * Injects remote update package into WordPress update transient when newer version exists.
     *
     * @param object $transient WordPress update transient.
     * @return object Modified transient.
     */
    public function check_update($transient) {
        if (!is_object($transient)) {
            $transient = new stdClass();
        }

        if (empty($transient->checked) && !isset($_GET['force-check']) && !isset($_GET['affichat_checked'])) {
            return $transient;
        }

        $remote = $this->get_remote_release();
        if (!$remote || empty($remote->version) || empty($remote->download_url)) {
            return $transient;
        }

        $basename = defined('AFFICHAT_WP_BASENAME') ? AFFICHAT_WP_BASENAME : 'affichat-wordpress/affichat-wordpress.php';
        $current_version = defined('AFFICHAT_WP_VERSION') ? AFFICHAT_WP_VERSION : '1.0.0';

        if (!empty($transient->checked[$basename])) {
            $current_version = $transient->checked[$basename];
        }

        $item = (object) [
            'id'            => $basename,
            'slug'          => 'affichat-wordpress',
            'plugin'        => $basename,
            'new_version'   => $remote->version,
            'url'           => $remote->homepage,
            'package'       => $remote->download_url,
            'icons'         => [
                'default' => defined('AFFICHAT_WP_URL') ? AFFICHAT_WP_URL . 'assets/images/icon-256x256.png' : '',
            ],
            'banners'       => [],
            'banners_rtl'   => [],
            'tested'        => '6.7',
            'requires_php'  => '7.4',
            'compatibility' => new stdClass(),
        ];

        if (version_compare($remote->version, $current_version, '>')) {
            if (!isset($transient->response) || !is_array($transient->response)) {
                $transient->response = [];
            }
            $transient->response[$basename] = $item;
            if (isset($transient->no_update[$basename])) {
                unset($transient->no_update[$basename]);
            }
        } else {
            if (!isset($transient->no_update) || !is_array($transient->no_update)) {
                $transient->no_update = [];
            }
            $transient->no_update[$basename] = $item;
            if (isset($transient->response[$basename])) {
                unset($transient->response[$basename]);
            }
        }

        return $transient;
    }

    /**
     * Handles manual update check trigger from plugins list.
     */
    public function handle_manual_check() {
        if (!current_user_can('update_plugins')) {
            wp_die(esc_html__('Akses tidak diizinkan.', 'affichat-wp'));
        }

        check_admin_referer('affichat_check_update_nonce');

        delete_transient(self::CACHE_KEY);
        delete_site_transient('update_plugins');

        if (function_exists('wp_update_plugins')) {
            wp_update_plugins();
        }

        $remote = $this->get_remote_release(true);
        $basename = defined('AFFICHAT_WP_BASENAME') ? AFFICHAT_WP_BASENAME : 'affichat-wordpress/affichat-wordpress.php';
        $current_version = defined('AFFICHAT_WP_VERSION') ? AFFICHAT_WP_VERSION : '1.0.0';

        $has_update = $remote && version_compare($remote->version, $current_version, '>');

        $redirect_url = add_query_arg([
            'affichat_checked' => '1',
            'affichat_latest'  => $remote ? $remote->version : 'unknown',
            'affichat_has_up'  => $has_update ? '1' : '0',
        ], admin_url('plugins.php'));

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Renders admin notice after manual check.
     */
    public function render_admin_notice() {
        if (empty($_GET['affichat_checked'])) {
            return;
        }

        $latest = !empty($_GET['affichat_latest']) ? sanitize_text_field($_GET['affichat_latest']) : '';
        $has_up = !empty($_GET['affichat_has_up']) && $_GET['affichat_has_up'] === '1';
        $current = defined('AFFICHAT_WP_VERSION') ? AFFICHAT_WP_VERSION : '1.0.0';

        if ($has_up) {
            echo '<div class="notice notice-warning is-dismissible"><p>' .
                sprintf(
                    esc_html__('AffiChat: Pembaruan versi %1$s tersedia (versi saat ini: %2$s). Silakan klik "Perbarui Sekarang" di bawah ini.', 'affichat-wp'),
                    '<strong>' . esc_html($latest) . '</strong>',
                    '<strong>' . esc_html($current) . '</strong>'
                ) .
                '</p></div>';
        } else {
            echo '<div class="notice notice-success is-dismissible"><p>' .
                sprintf(
                    esc_html__('AffiChat: Anda sudah menggunakan versi terbaru (%s). Tidak ada pembaruan baru dari GitHub.', 'affichat-wp'),
                    '<strong>' . esc_html($current) . '</strong>'
                ) .
                '</p></div>';
        }
    }

    /**
     * Returns plugin metadata modal details when user clicks "View version details".
     *
     * @param false|object|array $result Default API result.
     * @param string             $action API action name.
     * @param object             $args Arguments object.
     * @return false|object Plugin info object or original result.
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || empty($args->slug) || $args->slug !== 'affichat-wordpress') {
            return $result;
        }

        $remote = $this->get_remote_release();
        if (!$remote) {
            return $result;
        }

        $info = new stdClass();
        $info->name          = 'AffiChat - WhatsApp Gateway for WordPress & WooCommerce';
        $info->slug          = 'affichat-wordpress';
        $info->version       = $remote->version;
        $info->author        = '<a href="https://chat.affidev.com">AffiChat</a>';
        $info->homepage      = $remote->homepage;
        $info->download_link = $remote->download_url;
        $info->tested        = '6.7';
        $info->requires      = '5.8';
        $info->requires_php  = '7.4';
        $info->last_updated  = $remote->published_at;

        $info->sections = [
            'description' => __('Integrasi WhatsApp Gateway serbaguna untuk WordPress & WooCommerce: Kirim notifikasi pesanan toko, pesan cepat langsung dari WP-Admin, dan auto-reply formulir website.', 'affichat-wp'),
            'changelog'   => !empty($remote->changelog) ? $remote->changelog : sprintf(__('Versi terbaru %s tersedia.', 'affichat-wp'), esc_html($remote->version)),
        ];

        return $info;
    }

    /**
     * Guarantees plugin directory remains 'affichat-wordpress' after decompression.
     *
     * @param bool  $true Installation status.
     * @param array $hook_extra Extra installation arguments.
     * @param array $result Installation result data.
     * @return array Modified result.
     */
    public function post_install($true, $hook_extra, $result) {
        $basename = defined('AFFICHAT_WP_BASENAME') ? AFFICHAT_WP_BASENAME : 'affichat-wordpress/affichat-wordpress.php';
        if (empty($hook_extra['plugin']) || $hook_extra['plugin'] !== $basename) {
            return $result;
        }

        delete_transient(self::CACHE_KEY);

        global $wp_filesystem;
        if (!$wp_filesystem) {
            return $result;
        }

        $proper_destination = WP_PLUGIN_DIR . '/affichat-wordpress';
        if (!empty($result['destination']) && $result['destination'] !== $proper_destination) {
            if ($wp_filesystem->exists($proper_destination)) {
                $wp_filesystem->delete($proper_destination, true);
            }
            $wp_filesystem->move($result['destination'], $proper_destination);
            $result['destination'] = $proper_destination;
        }

        if (function_exists('is_plugin_active') && is_plugin_active($basename)) {
            activate_plugin($basename);
        }

        return $result;
    }

    /**
     * Fetches release metadata from GitHub Releases API with transient caching.
     *
     * @param bool $force_refresh Whether to bypass transient cache.
     * @return object|null Release details or null on failure.
     */
    public function get_remote_release($force_refresh = false) {
        $is_force = $force_refresh ||
                    isset($_GET['force-check']) ||
                    (isset($_GET['action']) && $_GET['action'] === 'check-again') ||
                    (isset($_GET['action']) && $_GET['action'] === 'affichat_check_update');

        if ($is_force) {
            if (function_exists('delete_transient')) {
                delete_transient(self::CACHE_KEY);
            }
        } else {
            if (function_exists('get_transient')) {
                $cached = get_transient(self::CACHE_KEY);
                if ($cached !== false && is_object($cached)) {
                    return $cached;
                }
            }
        }

        if (!function_exists('wp_remote_get')) {
            return null;
        }

        $repo = defined('AFFICHAT_WP_GITHUB_REPO') ? AFFICHAT_WP_GITHUB_REPO : self::GITHUB_REPO;
        $url  = 'https://api.github.com/repos/' . $repo . '/releases';

        $home = function_exists('home_url') ? home_url() : 'http://localhost';
        $v    = function_exists('get_bloginfo') ? get_bloginfo('version') : '6.7';

        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . $v . '; ' . $home,
            ],
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $releases = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($releases) || empty($releases)) {
            return null;
        }

        $target = null;
        foreach ($releases as $rel) {
            if (!empty($rel['draft']) || !empty($rel['prerelease'])) {
                continue;
            }

            $tag = $rel['tag_name'] ?? '';
            // Strip any prefix like 'wp-v', 'wp-', 'v' cleanly to get the exact semver version (e.g. '1.0.2')
            $version = preg_replace('/^[^0-9]+/', '', $tag);
            if (!preg_match('/^\d+(\.\d+)+/', $version)) {
                continue;
            }

            // Strictly find the dedicated 'affichat-wordpress.zip' release asset.
            // DO NOT fall back to monorepo zipball_url which contains the whole repository!
            $download_url = '';
            if (!empty($rel['assets']) && is_array($rel['assets'])) {
                foreach ($rel['assets'] as $asset) {
                    if (isset($asset['name']) && stripos($asset['name'], 'affichat-wordpress') !== false && substr($asset['name'], -4) === '.zip') {
                        $download_url = $asset['browser_download_url'];
                        break;
                    }
                }
            }

            // Only consider release valid if dedicated plugin zip asset exists
            if (!empty($download_url)) {
                $target = (object) [
                    'version'      => $version,
                    'download_url' => $download_url,
                    'homepage'     => $rel['html_url'] ?? ('https://github.com/' . $repo),
                    'published_at' => $rel['published_at'] ?? current_time('mysql'),
                    'changelog'    => !empty($rel['body']) ? wp_kses_post(wpautop($rel['body'])) : '',
                ];
                break;
            }
        }

        if ($target && function_exists('set_transient')) {
            set_transient(self::CACHE_KEY, $target, self::CACHE_TTL);
        }

        return $target;
    }
}
