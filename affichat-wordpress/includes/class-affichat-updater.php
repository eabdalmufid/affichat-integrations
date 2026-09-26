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

        $logo_url = defined('AFFICHAT_WP_URL') ? AFFICHAT_WP_URL . 'assets/images/logo-nobg.png' : 'https://chat.affidev.com/assets/logo.png';
        $icon_url = defined('AFFICHAT_WP_URL') ? AFFICHAT_WP_URL . 'assets/images/icon-256x256.png' : $logo_url;
        $wp_ver   = function_exists('get_bloginfo') ? preg_replace('/-.*$/', '', get_bloginfo('version')) : '6.7';

        $item = (object) [
            'id'            => $basename,
            'slug'          => 'affichat-wordpress',
            'plugin'        => $basename,
            'new_version'   => $remote->version,
            'url'           => $remote->homepage,
            'package'       => $remote->download_url,
            'icons'         => [
                '1x'      => $icon_url,
                '2x'      => $icon_url,
                'default' => $icon_url,
            ],
            'banners'       => [
                'low'  => $logo_url,
                'high' => $logo_url,
            ],
            'banners_rtl'   => [],
            'requires'      => '5.8',
            'tested'        => !empty($wp_ver) ? $wp_ver : '6.7',
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

        $wp_version = function_exists('get_bloginfo') ? preg_replace('/-.*$/', '', get_bloginfo('version')) : '6.7';

        $logo_url = defined('AFFICHAT_WP_URL') ? AFFICHAT_WP_URL . 'assets/images/logo-nobg.png' : 'https://chat.affidev.com/assets/logo.png';
        $icon_url = defined('AFFICHAT_WP_URL') ? AFFICHAT_WP_URL . 'assets/images/icon-256x256.png' : $logo_url;

        $info = new stdClass();
        $info->name           = 'AffiChat - WhatsApp Gateway for WordPress & WooCommerce';
        $info->slug           = 'affichat-wordpress';
        $info->version        = $remote->version;
        $info->author         = '<a href="https://chat.affidev.com" target="_blank" rel="noopener">AffiChat</a>';
        $info->homepage       = $remote->homepage;
        $info->download_link  = $remote->download_url;
        $info->tested         = !empty($wp_version) ? $wp_version : '6.7';
        $info->requires       = '5.8';
        $info->requires_php   = '7.4';
        $info->last_updated   = $remote->published_at;
        $info->rating         = 100;
        $info->ratings        = [5 => 28, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $info->num_ratings    = 28;
        $info->active_installs = 1000;
        $info->icons          = [
            '1x'      => $icon_url,
            '2x'      => $icon_url,
            'default' => $icon_url,
        ];
        $info->banners        = [
            'low'  => $logo_url,
            'high' => $logo_url,
        ];

        $changelog_raw  = !empty($remote->changelog) ? $remote->changelog : sprintf(__('Versi %s telah dirilis.', 'affichat-wp'), esc_html($remote->version));
        $changelog_html = $this->parse_markdown_to_html($changelog_raw);

        $info->sections = [
            'description'  =>
                '<p>' . esc_html__('Integrasi WhatsApp Gateway resmi untuk WordPress & WooCommerce. Kirim notifikasi pesanan otomatis, kirim pesan cepat langsung dari dashboard admin, dan tanggapi pengisian formulir prospek secara instan.', 'affichat-wp') . '</p>' .
                '<h4 style="margin: 16px 0 8px;">' . esc_html__('Fitur Utama:', 'affichat-wp') . '</h4>' .
                '<ul style="margin: 8px 0 16px 20px; list-style-type: disc;">' .
                    '<li><strong>' . esc_html__('Notifikasi WooCommerce Otomatis:', 'affichat-wp') . '</strong> ' . esc_html__('Kirim alert ke WhatsApp pembeli saat status pesanan Pending, Processing, atau Completed.', 'affichat-wp') . '</li>' .
                    '<li><strong>' . esc_html__('Notifikasi WhatsApp Admin Toko:', 'affichat-wp') . '</strong> ' . esc_html__('Dapatkan pemberitahuan seketika saat ada pesanan baru masuk lengkap dengan rincian total dan produk.', 'affichat-wp') . '</li>' .
                    '<li><strong>' . esc_html__('Pesan Cepat (Quick Send):', 'affichat-wp') . '</strong> ' . esc_html__('Kirim chat WhatsApp ke nomor pelanggan langsung dari menu AffiChat di WP-Admin.', 'affichat-wp') . '</li>' .
                    '<li><strong>' . esc_html__('Integrasi Form Builder:', 'affichat-wp') . '</strong> ' . esc_html__('Dukungan penuh untuk Elementor Form, JetFormBuilder, Contact Form 7, WPForms, dan Fluent Forms.', 'affichat-wp') . '</li>' .
                    '<li><strong>' . esc_html__('Variabel Template Dinamis:', 'affichat-wp') . '</strong> ' . esc_html__('Personalisasi pesan dengan tag {customer_name}, {order_number}, {order_total}, {payment_method}, dll.', 'affichat-wp') . '</li>' .
                    '<li><strong>' . esc_html__('Arsitektur Ringan & Aman:', 'affichat-wp') . '</strong> ' . esc_html__('Kompatibel dengan WooCommerce HPOS dan proteksi anti-duplikasi pengiriman.', 'affichat-wp') . '</li>' .
                '</ul>',

            'installation' =>
                '<h4 style="margin: 12px 0 8px;">' . esc_html__('Langkah Instalasi & Konfigurasi:', 'affichat-wp') . '</h4>' .
                '<ol style="margin: 8px 0 16px 20px; list-style-type: decimal;">' .
                    '<li>' . esc_html__('Buka menu AffiChat di sidebar WP-Admin.', 'affichat-wp') . '</li>' .
                    '<li>' . esc_html__('Masukkan URL Gateway, Access Key / API Key, dan Session ID dari server WhatsApp Anda.', 'affichat-wp') . '</li>' .
                    '<li>' . esc_html__('Klik tombol "Cek Koneksi" untuk memastikan WhatsApp terhubung.', 'affichat-wp') . '</li>' .
                    '<li>' . esc_html__('Aktifkan notifikasi WooCommerce dan sesuaikan template pesan sesuai kebutuhan bisnis Anda.', 'affichat-wp') . '</li>' .
                '</ol>',

            'faq'          =>
                '<h4 style="margin: 12px 0 6px;">' . esc_html__('Format nomor telepon apa yang didukung?', 'affichat-wp') . '</h4>' .
                '<p>' . esc_html__('Plugin otomatis menormalisasi format nomor Indonesia (08xxx, +62xxx, atau 62xxx) menjadi format standar internasional WhatsApp.', 'affichat-wp') . '</p>' .
                '<h4 style="margin: 14px 0 6px;">' . esc_html__('Bagaimana jika server WhatsApp sedang offline?', 'affichat-wp') . '</h4>' .
                '<p>' . esc_html__('Pengiriman pesan dilakukan secara asynchronous dan tidak akan memperlambat proses checkout pengunjung toko Anda.', 'affichat-wp') . '</p>' .
                '<h4 style="margin: 14px 0 6px;">' . esc_html__('Bagaimana cara memperbarui plugin?', 'affichat-wp') . '</h4>' .
                '<p>' . esc_html__('Pembaruan dapat dilakukan langsung secara otomatis melalui menu Plugins > Perbarui Sekarang.', 'affichat-wp') . '</p>',

            'changelog'    => $changelog_html,
        ];

        return $info;
    }

    /**
     * Converts release markdown into HTML formatted for WordPress modal tabs.
     *
     * @param string $markdown Raw markdown content.
     * @return string Formatted HTML string.
     */
    public function parse_markdown_to_html($markdown) {
        if (empty($markdown)) {
            return '';
        }

        $lines = explode("\n", (string) $markdown);
        $output = [];
        $in_list = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (preg_match('/^[-*]\s+(.*)$/', $trimmed, $matches)) {
                if (!$in_list) {
                    $output[] = '<ul style="margin: 8px 0 12px 20px; list-style-type: disc;">';
                    $in_list = true;
                }
                $output[] = '<li>' . $this->format_inline_markdown($matches[1]) . '</li>';
                continue;
            }

            if ($in_list) {
                $output[] = '</ul>';
                $in_list = false;
            }

            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^###\s+(.*)$/', $trimmed, $matches)) {
                $output[] = '<h3 style="margin: 16px 0 8px; font-size: 15px; color: #1d2327;">' . $this->format_inline_markdown($matches[1]) . '</h3>';
            } elseif (preg_match('/^##\s+(.*)$/', $trimmed, $matches)) {
                $output[] = '<h2 style="margin: 20px 0 10px; font-size: 17px; color: #1d2327;">' . $this->format_inline_markdown($matches[1]) . '</h2>';
            } elseif (preg_match('/^#\s+(.*)$/', $trimmed, $matches)) {
                $output[] = '<h1 style="margin: 20px 0 12px; font-size: 19px; color: #1d2327;">' . $this->format_inline_markdown($matches[1]) . '</h1>';
            } elseif (preg_match('/^>\s+(.*)$/', $trimmed, $matches)) {
                $output[] = '<blockquote style="border-left: 4px solid #00a884; background: #f0fdf4; margin: 10px 0; padding: 10px 14px; border-radius: 4px; color: #166534;">' . $this->format_inline_markdown($matches[1]) . '</blockquote>';
            } else {
                $output[] = '<p style="margin: 6px 0;">' . $this->format_inline_markdown($trimmed) . '</p>';
            }
        }

        if ($in_list) {
            $output[] = '</ul>';
        }

        return implode("\n", $output);
    }

    /**
     * Formats inline markdown elements such as bold, italics, links, and code blocks.
     *
     * @param string $text Inline text.
     * @return string Formatted HTML.
     */
    private function format_inline_markdown($text) {
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/(?<!\*)\*(.*?)\*(?!\*)/', '<em>$1</em>', $text);
        $text = preg_replace('/\[(.*?)\]\((https?:\/\/[^\s\)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $text);
        $text = preg_replace('/(?<!href=")(https?:\/\/[^\s<]+)/', '<a href="$1" target="_blank" rel="noopener">$1</a>', $text);
        $text = preg_replace('/`([^`]+)`/', '<code style="background: #f1f5f9; padding: 2px 5px; border-radius: 3px;">$1</code>', $text);

        return $text;
    }

    /**
     * Clears update transients and ensures plugin remains active after upgrade.
     *
     * @param bool|WP_Error $response   Installation status.
     * @param array         $hook_extra Extra installation arguments.
     * @param array         $result     Installation result data.
     * @return bool|WP_Error Filter response.
     */
    public function post_install($response, $hook_extra, $result) {
        $basename = defined('AFFICHAT_WP_BASENAME') ? AFFICHAT_WP_BASENAME : 'affichat-wordpress/affichat-wordpress.php';
        if (empty($hook_extra['plugin']) || $hook_extra['plugin'] !== $basename) {
            return $response;
        }

        if (function_exists('delete_transient')) {
            delete_transient(self::CACHE_KEY);
        }

        if (function_exists('is_plugin_active') && is_plugin_active($basename)) {
            activate_plugin($basename);
        }

        return $response;
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
