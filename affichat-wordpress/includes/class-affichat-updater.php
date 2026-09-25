<?php
/**
 * Auto-update handler for AffiChat WordPress & WooCommerce plugin.
 *
 * Hooks into the native WordPress update pipeline to enable one-click updates
 * from GitHub Releases or self-hosted package endpoints.
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
        add_filter('plugins_api', [$instance, 'plugin_info'], 20, 3);
        add_filter('upgrader_post_install', [$instance, 'post_install'], 10, 3);
    }

    /**
     * Injects remote update package into WordPress update transient when newer version exists.
     *
     * @param object $transient WordPress update transient.
     * @return object Modified transient.
     */
    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote = $this->get_remote_release();
        if (!$remote || empty($remote->version) || empty($remote->download_url)) {
            return $transient;
        }

        $basename = defined('AFFICHAT_WP_BASENAME') ? AFFICHAT_WP_BASENAME : 'affichat-wordpress/affichat-wordpress.php';
        $current_version = defined('AFFICHAT_WP_VERSION') ? AFFICHAT_WP_VERSION : '1.0.0';

        $item = (object) [
            'id'            => $basename,
            'slug'          => 'affichat-wordpress',
            'plugin'        => $basename,
            'new_version'   => $remote->version,
            'url'           => $remote->homepage,
            'package'       => $remote->download_url,
            'tested'        => '6.7',
            'requires_php'  => '7.4',
            'compatibility' => new stdClass(),
        ];

        if (version_compare($remote->version, $current_version, '>')) {
            $transient->response[$basename] = $item;
            unset($transient->no_update[$basename]);
        } else {
            $transient->no_update[$basename] = $item;
            unset($transient->response[$basename]);
        }

        return $transient;
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
     * GitHub release zips unpack into dynamic directory names that break WordPress plugin paths.
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

        global $wp_filesystem;
        if (!$wp_filesystem) {
            return $result;
        }

        $proper_destination = WP_PLUGIN_DIR . '/affichat-wordpress';
        if ($result['destination'] !== $proper_destination) {
            $wp_filesystem->move($result['destination'], $proper_destination);
            $result['destination'] = $proper_destination;
        }

        return $result;
    }

    /**
     * Fetches release metadata from GitHub Releases API with transient caching.
     *
     * @return object|null Release details or null on network failure.
     */
    private function get_remote_release() {
        $cache_key = 'affichat_wp_release_info';
        $cached = get_transient($cache_key);
        if ($cached !== false && is_object($cached)) {
            return $cached;
        }

        $repo = defined('AFFICHAT_WP_GITHUB_REPO') ? AFFICHAT_WP_GITHUB_REPO : self::GITHUB_REPO;
        $url  = 'https://api.github.com/repos/' . $repo . '/releases';

        $response = wp_remote_get($url, [
            'timeout' => 10,
            'headers' => [
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
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
            $version = preg_replace('/^(wp[-_]?|v)/i', '', $tag);
            if (!preg_match('/^\d+(\.\d+)+/', $version)) {
                continue;
            }

            $download_url = '';
            if (!empty($rel['assets']) && is_array($rel['assets'])) {
                foreach ($rel['assets'] as $asset) {
                    if (isset($asset['name']) && stripos($asset['name'], 'affichat-wordpress') !== false && substr($asset['name'], -4) === '.zip') {
                        $download_url = $asset['browser_download_url'];
                        break;
                    }
                }
            }

            if (empty($download_url) && !empty($rel['zipball_url'])) {
                $download_url = $rel['zipball_url'];
            }

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

        if ($target) {
            set_transient($cache_key, $target, self::CACHE_TTL);
        }

        return $target;
    }
}
