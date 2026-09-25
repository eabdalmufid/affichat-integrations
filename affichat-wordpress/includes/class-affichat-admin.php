<?php
/**
 * AffiChat WordPress Admin UI & Menus.
 *
 * Provides dedicated WordPress admin menus:
 * - Pengaturan & Koneksi Gateway
 * - Kirim Cepat WhatsApp
 * - Notifikasi Toko WooCommerce
 * - Formulir Website & Integrasi REST API
 *
 * @package AffiChat_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiChat_WP_Admin {
    /**
     * Initializes admin menus and assets.
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_admin_menus']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        add_action('admin_init', [__CLASS__, 'register_settings']);

        add_action('wp_ajax_affichat_wp_test_connection', [__CLASS__, 'ajax_test_connection']);
        add_action('wp_ajax_affichat_wp_quick_send', [__CLASS__, 'ajax_quick_send']);
    }

    public static function register_settings() {
        register_setting('affichat_wp_general_group', 'affichat_wp_base_url');
        register_setting('affichat_wp_general_group', 'affichat_wp_api_key');
        register_setting('affichat_wp_general_group', 'affichat_wp_session_id');
        register_setting('affichat_wp_general_group', 'affichat_wp_admin_phone');

        // Universal form settings
        register_setting('affichat_wp_forms_group', 'affichat_wp_form_user_reply_enabled');
        register_setting('affichat_wp_forms_group', 'affichat_wp_form_user_template');
        register_setting('affichat_wp_forms_group', 'affichat_wp_form_admin_notify_enabled');
        register_setting('affichat_wp_forms_group', 'affichat_wp_form_admin_template');

        // Form builder toggles
        register_setting('affichat_wp_forms_group', 'affichat_wp_jetform_enabled');
        register_setting('affichat_wp_forms_group', 'affichat_wp_elementor_enabled');
        register_setting('affichat_wp_forms_group', 'affichat_wp_cf7_enabled');
        register_setting('affichat_wp_forms_group', 'affichat_wp_wpforms_enabled');
        register_setting('affichat_wp_forms_group', 'affichat_wp_fluentform_enabled');
        register_setting('affichat_wp_forms_group', 'affichat_wp_user_reg_admin_notify');
    }

    /**
     * Registers WordPress sidebar menu and submenus.
     */
    public static function register_admin_menus() {
        $icon_svg = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="#059669"><path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.42a8.225 8.225 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.196 8.196 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24m4.52 11.66c-.25.7-.99 1.27-1.74 1.43-.51.11-1.17.2-3.41-.74-2.86-1.19-4.71-4.1-4.85-4.29-.14-.19-1.16-1.55-1.16-2.96 0-1.41.74-2.1 1-2.39.26-.29.58-.36.77-.36.2 0 .39 0 .56.01.18.01.42-.07.66.5.25.59.84 2.06.92 2.21.08.15.13.33.03.53-.1.2-.15.33-.3.51-.15.17-.31.39-.45.52-.15.15-.3.32-.13.62.17.3.77 1.27 1.65 2.05 1.13 1.01 2.08 1.32 2.38 1.47.3.15.47.13.65-.07.17-.2.74-.87.94-1.17.2-.3.4-.25.67-.15.27.1 1.73.82 2.03.96.3.15.5.22.57.34.08.13.08.74-.17 1.44z"/></svg>'
        );

        add_menu_page(
            __('AffiChat WhatsApp', 'affichat-wp'),
            __('AffiChat WA', 'affichat-wp'),
            'manage_options',
            'affichat-wp',
            [__CLASS__, 'render_settings_page'],
            $icon_svg,
            56
        );

        add_submenu_page(
            'affichat-wp',
            __('Pengaturan & Koneksi', 'affichat-wp'),
            __('Pengaturan', 'affichat-wp'),
            'manage_options',
            'affichat-wp',
            [__CLASS__, 'render_settings_page']
        );

        add_submenu_page(
            'affichat-wp',
            __('Kirim Cepat WhatsApp', 'affichat-wp'),
            __('Kirim Cepat', 'affichat-wp'),
            'manage_options',
            'affichat-wp-quick-send',
            [__CLASS__, 'render_quick_send_page']
        );

        add_submenu_page(
            'affichat-wp',
            __('Notifikasi WooCommerce', 'affichat-wp'),
            __('WooCommerce', 'affichat-wp'),
            'manage_options',
            'affichat-wp-woocommerce',
            [__CLASS__, 'render_woocommerce_page']
        );

        add_submenu_page(
            'affichat-wp',
            __('Formulir & Integrasi', 'affichat-wp'),
            __('Form & Integrasi', 'affichat-wp'),
            'manage_options',
            'affichat-wp-integrations',
            [__CLASS__, 'render_integrations_page']
        );
    }

    /**
     * Enqueues CSS and JS on AffiChat admin pages with cache busting.
     */
    public static function enqueue_admin_assets($hook) {
        $valid_hooks = [
            'toplevel_page_affichat-wp',
            'affichat-wa_page_affichat-wp-quick-send',
            'affichat-wa_page_affichat-wp-woocommerce',
            'affichat-wa_page_affichat-wp-integrations',
        ];

        $is_wc_tab = (isset($_GET['page']) && $_GET['page'] === 'wc-settings' && isset($_GET['tab']) && $_GET['tab'] === 'affichat_whatsapp');

        if (!in_array($hook, $valid_hooks) && !$is_wc_tab) {
            return;
        }

        $ver = AFFICHAT_WP_VERSION . '.' . filemtime(AFFICHAT_WP_PATH . 'assets/css/admin.css');

        wp_enqueue_style(
            'affichat-wp-admin-css',
            AFFICHAT_WP_URL . 'assets/css/admin.css',
            [],
            $ver
        );

        wp_enqueue_script(
            'affichat-wp-admin-js',
            AFFICHAT_WP_URL . 'assets/js/admin.js',
            ['jquery'],
            $ver,
            true
        );

        wp_localize_script('affichat-wp-admin-js', 'affichat_wp_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('affichat_wp_nonce'),
            'i18n'     => [
                'testing'   => __('Memeriksa...', 'affichat-wp'),
                'test_btn'  => __('Tes Koneksi WhatsApp', 'affichat-wp'),
                'sending'   => __('Mengirim Pesan...', 'affichat-wp'),
                'send_btn'  => __('Kirim Sekarang', 'affichat-wp'),
                'failed'    => __('Gagal terhubung ke gateway.', 'affichat-wp'),
            ],
        ]);
    }

    /**
     * Renders standard top brand card with logo, badges, and tab navigation.
     */
    public static function render_header($active_tab = 'general') {
        $logo_url  = esc_url(AFFICHAT_WP_URL . 'assets/images/logo-nobg.png');
        $wc_active = class_exists('WooCommerce');

        // Explicit width, height, and inline styles prevent SVG from ever scaling out of control
        $svg_style = 'width:18px;height:18px;min-width:18px;max-width:18px;display:inline-block;vertical-align:middle;flex-shrink:0;';

        $tabs = [
            'general'      => [
                'title' => __('Pengaturan & Sesi', 'affichat-wp'),
                'url'   => admin_url('admin.php?page=affichat-wp'),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="' . $svg_style . '"><path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
            ],
            'quick_send'   => [
                'title' => __('Kirim Cepat', 'affichat-wp'),
                'url'   => admin_url('admin.php?page=affichat-wp-quick-send'),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="' . $svg_style . '"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>',
            ],
            'woocommerce'  => [
                'title' => __('WooCommerce', 'affichat-wp'),
                'url'   => admin_url('admin.php?page=affichat-wp-woocommerce'),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="' . $svg_style . '"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>',
            ],
            'integrations' => [
                'title' => __('Form & Integrasi', 'affichat-wp'),
                'url'   => admin_url('admin.php?page=affichat-wp-integrations'),
                'icon'  => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="' . $svg_style . '"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
            ],
        ];
        ?>
        <div class="affichat-header-card">
            <div class="affichat-header-top">
                <div class="affichat-brand-title">
                    <img src="<?php echo $logo_url; ?>" alt="AffiChat Logo" class="affichat-header-logo" />
                    <div class="affichat-title-text">
                        <h2>AffiChat WhatsApp Gateway</h2>
                        <p><?php esc_html_e('Gateway WhatsApp modern untuk notifikasi toko, form website, dan pesan instan.', 'affichat-wp'); ?></p>
                    </div>
                </div>
                <div class="affichat-header-badges">
                    <span class="affichat-badge affichat-badge-success">v<?php echo esc_html(AFFICHAT_WP_VERSION); ?></span>
                    <?php if ($wc_active) : ?>
                        <span class="affichat-badge affichat-badge-info"><?php esc_html_e('WooCommerce Aktif', 'affichat-wp'); ?></span>
                    <?php else : ?>
                        <span class="affichat-badge affichat-badge-neutral"><?php esc_html_e('Mode Universal', 'affichat-wp'); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <nav class="affichat-nav-tabs" aria-label="<?php esc_attr_e('AffiChat Sub Menu', 'affichat-wp'); ?>">
                <?php foreach ($tabs as $key => $tab) : ?>
                    <a href="<?php echo esc_url($tab['url']); ?>" class="affichat-tab-link <?php echo ($active_tab === $key) ? 'active' : ''; ?>">
                        <?php echo $tab['icon']; ?>
                        <span><?php echo esc_html($tab['title']); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
        <?php
    }

    /**
     * Renders Main Settings Page.
     */
    public static function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['affichat_wp_save_general']) && check_admin_referer('affichat_wp_save_general_action')) {
            $submitted_base = !empty($_POST['affichat_wp_base_url']) ? esc_url_raw($_POST['affichat_wp_base_url']) : 'https://chat.affidev.com';
            update_option('affichat_wp_base_url', $submitted_base);
            update_option('affichat_wp_api_key', sanitize_text_field($_POST['affichat_wp_api_key']));
            update_option('affichat_wp_session_id', sanitize_text_field($_POST['affichat_wp_session_id']));
            update_option('affichat_wp_admin_phone', sanitize_text_field($_POST['affichat_wp_admin_phone']));

            update_option('affichat_wc_api_key', sanitize_text_field($_POST['affichat_wp_api_key']));
            update_option('affichat_wc_session_id', sanitize_text_field($_POST['affichat_wp_session_id']));
            update_option('affichat_wc_admin_phone', sanitize_text_field($_POST['affichat_wp_admin_phone']));

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Pengaturan berhasil disimpan.', 'affichat-wp') . '</p></div>';
        }

        $base_url   = get_option('affichat_wp_base_url', get_option('affichat_wc_base_url', defined('AFFICHAT_WP_API_BASE_URL') ? AFFICHAT_WP_API_BASE_URL : 'https://chat.affidev.com'));
        $api_key    = get_option('affichat_wp_api_key', get_option('affichat_wc_api_key', ''));
        $session_id = get_option('affichat_wp_session_id', get_option('affichat_wc_session_id', 'default'));
        $admin_phone= get_option('affichat_wp_admin_phone', get_option('affichat_wc_admin_phone', ''));
        ?>
        <div class="affichat-wrap">
            <?php self::render_header('general'); ?>

            <div class="affichat-grid-2">
                <div class="affichat-card">
                    <div class="affichat-card-header">
                        <h3><?php esc_html_e('Kredensial API AffiChat', 'affichat-wp'); ?></h3>
                        <p><?php esc_html_e('Dapatkan API Key dan buat Sesi WhatsApp di Dashboard AffiChat (https://chat.affidev.com).', 'affichat-wp'); ?></p>
                    </div>

                    <form method="post" action="">
                        <?php wp_nonce_field('affichat_wp_save_general_action'); ?>

                        <div class="affichat-form-group">
                            <label for="affichat_wp_api_key"><?php esc_html_e('API Key', 'affichat-wp'); ?></label>
                            <input type="password" id="affichat_wp_api_key" name="affichat_wp_api_key" value="<?php echo esc_attr($api_key); ?>" class="affichat-input" placeholder="Masukkan x-api-key..." required />
                            <small class="affichat-help-text"><?php esc_html_e('Kunci rahasia API Key yang dibuat di menu REST API Dashboard AffiChat.', 'affichat-wp'); ?></small>
                        </div>

                        <div class="affichat-form-group">
                            <label for="affichat_wp_session_id"><?php esc_html_e('ID Sesi WhatsApp', 'affichat-wp'); ?></label>
                            <input type="text" id="affichat_wp_session_id" name="affichat_wp_session_id" value="<?php echo esc_attr($session_id); ?>" class="affichat-input" placeholder="default / nomor sesi" required />
                            <small class="affichat-help-text"><?php esc_html_e('ID sesi nomor WhatsApp yang aktif terhubung di Dashboard AffiChat.', 'affichat-wp'); ?></small>
                        </div>

                        <div class="affichat-form-group">
                            <label for="affichat_wp_admin_phone"><?php esc_html_e('Nomor WhatsApp Admin / Pemilik Website', 'affichat-wp'); ?></label>
                            <input type="text" id="affichat_wp_admin_phone" name="affichat_wp_admin_phone" value="<?php echo esc_attr($admin_phone); ?>" class="affichat-input" placeholder="Contoh: 081234567890" />
                            <small class="affichat-help-text"><?php esc_html_e('Nomor WhatsApp untuk menerima alert pesanan toko baru atau pesan formulir masuk.', 'affichat-wp'); ?></small>
                        </div>

                        <div class="affichat-form-actions">
                            <button type="submit" name="affichat_wp_save_general" class="affichat-btn affichat-btn-primary">
                                <?php esc_html_e('Simpan Pengaturan', 'affichat-wp'); ?>
                            </button>
                            <button type="button" class="affichat-btn affichat-btn-secondary affichat-btn-reset-all" data-reset-section="general">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle; margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                <span><?php esc_html_e('Reset Default', 'affichat-wp'); ?></span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="affichat-card">
                    <div class="affichat-card-header">
                        <h3><?php esc_html_e('Diagnostik & Tes Sambungan', 'affichat-wp'); ?></h3>
                        <p><?php esc_html_e('Uji apakah website WordPress Anda dapat berkomunikasi dengan server AffiChat Gateway.', 'affichat-wp'); ?></p>
                    </div>

                    <div class="affichat-test-box">
                        <button type="button" id="affichat-wp-btn-test" class="affichat-btn affichat-btn-test">
                            <svg class="affichat-btn-icon" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" style="width:16px;height:16px;display:inline-block;vertical-align:middle;"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                            <span><?php esc_html_e('Tes Koneksi WhatsApp', 'affichat-wp'); ?></span>
                        </button>
                        <div id="affichat-wp-test-result" class="affichat-result-alert" style="display:none;"></div>
                    </div>

                    <div class="affichat-info-list">
                        <h4><?php esc_html_e('Panduan Langkah Cepat:', 'affichat-wp'); ?></h4>
                        <ul>
                            <li><strong>1.</strong> Pastikan sesi WhatsApp di Dashboard AffiChat sudah di-scan QR dan berstatus <em>Online</em>.</li>
                            <li><strong>2.</strong> Masukkan API Key & ID Sesi pada form di sebelah kiri.</li>
                            <li><strong>3.</strong> Gunakan menu <strong>Kirim Cepat</strong> untuk mengetes kirim pesan ke nomor WhatsApp Anda.</li>
                            <li><strong>4.</strong> Fitur <strong>WooCommerce</strong> otomatis aktif jika toko online terdeteksi.</li>
                            <li><strong>5.</strong> Fitur <strong>Formulir Website</strong> dapat dikonfigurasi di tab <em>Form & Integrasi</em>.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renders Quick Send WhatsApp Page with live WhatsApp preview bubble.
     */
    public static function render_quick_send_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $session_id = get_option('affichat_wp_session_id', get_option('affichat_wc_session_id', 'default'));
        ?>
        <div class="affichat-wrap">
            <?php self::render_header('quick_send'); ?>

            <div class="affichat-grid-2">
                <div class="affichat-card">
                    <div class="affichat-card-header">
                        <h3><?php esc_html_e('Kirim Pesan WhatsApp Instan', 'affichat-wp'); ?></h3>
                        <p><?php esc_html_e('Kirim pesan teks WhatsApp instan langsung ke nomor mana saja.', 'affichat-wp'); ?></p>
                    </div>

                    <form id="affichat-wp-quick-send-form">
                        <div class="affichat-form-group">
                            <label for="affichat_quick_phone"><?php esc_html_e('Nomor WhatsApp Penerima', 'affichat-wp'); ?></label>
                            <input type="text" id="affichat_quick_phone" name="phone" class="affichat-input" placeholder="08123456789 atau 628123456789" required />
                            <small class="affichat-help-text"><?php esc_html_e('Format bebas (08... atau 628...), sistem otomatis memformat nomor ke kode negara.', 'affichat-wp'); ?></small>
                        </div>

                        <div class="affichat-form-group">
                            <label for="affichat_quick_message"><?php esc_html_e('Isi Pesan WhatsApp', 'affichat-wp'); ?></label>
                            <textarea id="affichat_quick_message" name="message" class="affichat-textarea" rows="6" placeholder="Ketik pesan Anda di sini... (mendukung format *tebal*, _miring_, dan emoji)" required></textarea>
                        </div>

                        <div class="affichat-tag-chips-wrapper">
                            <span class="affichat-tag-chips-title"><?php esc_html_e('Sisipkan variabel dinamis:', 'affichat-wp'); ?></span>
                            <div class="affichat-chips-container">
                                <button type="button" class="affichat-tag-chip" data-tag="{site_name}">{site_name}</button>
                                <button type="button" class="affichat-tag-chip" data-tag="{site_url}">{site_url}</button>
                                <button type="button" class="affichat-tag-chip" data-tag="{date}">{date}</button>
                                <button type="button" class="affichat-tag-chip" data-tag="{time}">{time}</button>
                            </div>
                        </div>

                        <div class="affichat-form-actions" style="margin-top:20px;">
                            <button type="submit" id="affichat-btn-quick-send" class="affichat-btn affichat-btn-primary">
                                <?php esc_html_e('Kirim Sekarang', 'affichat-wp'); ?>
                            </button>
                            <span class="affichat-meta-text"><?php printf(esc_html__('Menggunakan sesi: %s', 'affichat-wp'), '<code>' . esc_html($session_id) . '</code>'); ?></span>
                        </div>

                        <div id="affichat-quick-send-result" class="affichat-result-alert" style="display:none; margin-top:16px;"></div>
                    </form>
                </div>

                <div class="affichat-card" style="padding: 0; background: transparent; border: none; box-shadow: none;">
                    <div class="affichat-card-header" style="margin-bottom: 12px; padding: 0 4px 10px 4px;">
                        <h3><?php esc_html_e('Pratinjau Pesan WhatsApp', 'affichat-wp'); ?></h3>
                        <p><?php esc_html_e('Tampilan visual pesan WhatsApp yang akan diterima di smartphone penerima.', 'affichat-wp'); ?></p>
                    </div>

                    <div class="affichat-wa-preview-card">
                        <div class="affichat-wa-preview-header">
                            <div class="affichat-wa-preview-avatar">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" style="width:20px;height:20px;display:inline-block;"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            </div>
                            <div>
                                <div class="affichat-wa-preview-title" id="affichat-preview-title"><?php esc_html_e('Penerima Pesan', 'affichat-wp'); ?></div>
                                <div class="affichat-wa-preview-sub"><?php esc_html_e('online', 'affichat-wp'); ?></div>
                            </div>
                        </div>
                        <div class="affichat-wa-preview-body">
                            <div class="affichat-wa-bubble">
                                <div class="affichat-wa-bubble-text" id="affichat-preview-text"><?php esc_html_e('Ketik pesan di formulir untuk melihat pratinjau langsung...', 'affichat-wp'); ?></div>
                                <div class="affichat-wa-bubble-meta">
                                    <span id="affichat-preview-time"><?php echo date_i18n('H:i'); ?></span>
                                    <svg width="16" height="11" viewBox="0 0 16 11" fill="none" style="width:16px;height:11px;display:inline-block;"><path d="M11.05 1.05L4.5 7.6 1.95 5.05.54 6.46 4.5 10.42l7.96-7.96-1.41-1.41z" fill="currentColor"/><path d="M14.05 1.05L7.5 7.6 6.95 7.05 5.54 8.46 7.5 10.42l7.96-7.96-1.41-1.41z" fill="currentColor"/></svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renders WooCommerce Notification Management Page.
     */
    public static function render_woocommerce_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $wc_active = class_exists('WooCommerce');

        if ($wc_active && isset($_POST['affichat_wp_save_wc']) && check_admin_referer('affichat_wp_save_wc_action')) {
            update_option('affichat_wc_admin_enabled', isset($_POST['affichat_wc_admin_enabled']) ? 'yes' : 'no');
            update_option('affichat_wc_admin_phone', sanitize_text_field($_POST['affichat_wc_admin_phone']));
            update_option('affichat_wc_admin_template', wp_kses_post($_POST['affichat_wc_admin_template']));

            $statuses = ['pending', 'processing', 'completed', 'cancelled'];
            foreach ($statuses as $st) {
                update_option("affichat_wc_cust_{$st}_enabled", isset($_POST["affichat_wc_cust_{$st}_enabled"]) ? 'yes' : 'no');
                update_option("affichat_wc_cust_{$st}_template", wp_kses_post($_POST["affichat_wc_cust_{$st}_template"]));
            }

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Pengaturan notifikasi WooCommerce berhasil disimpan.', 'affichat-wp') . '</p></div>';
        }

        $tags = AffiChat_WP_Tags::get_available_tags(true);
        ?>
        <div class="affichat-wrap">
            <?php self::render_header('woocommerce'); ?>

            <?php if (!$wc_active) : ?>
                <div class="affichat-card affichat-warning-box">
                    <div class="affichat-card-header">
                        <h3><?php esc_html_e('WooCommerce Belum Aktif', 'affichat-wp'); ?></h3>
                        <p><?php esc_html_e('Fitur notifikasi pesanan otomatis ini membutuhkan plugin WooCommerce aktif di website Anda.', 'affichat-wp'); ?></p>
                    </div>
                    <p style="font-size:13px; color:var(--affi-text-muted); line-height:1.5;">
                        <?php esc_html_e('Jika website Anda berfokus pada formulir kontak, pendaftaran, atau leads, silakan konfigurasi notifikasi WhatsApp di menu Form & Integrasi.', 'affichat-wp'); ?>
                    </p>
                </div>
            <?php else : ?>
                <form method="post" action="">
                    <?php wp_nonce_field('affichat_wp_save_wc_action'); ?>
                    <?php AffiChat_WP_WooCommerce::render_wc_settings_fields_html(false); ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renders Universal Forms & Developer Integrations Page.
     */
    public static function render_integrations_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['affichat_wp_save_forms']) && check_admin_referer('affichat_wp_save_forms_action')) {
            update_option('affichat_wp_form_user_reply_enabled', isset($_POST['affichat_wp_form_user_reply_enabled']) ? 'yes' : 'no');
            update_option('affichat_wp_form_user_template', wp_kses_post($_POST['affichat_wp_form_user_template']));
            update_option('affichat_wp_form_admin_notify_enabled', isset($_POST['affichat_wp_form_admin_notify_enabled']) ? 'yes' : 'no');
            update_option('affichat_wp_form_admin_template', wp_kses_post($_POST['affichat_wp_form_admin_template']));

            // Form Builder toggles
            update_option('affichat_wp_jetform_enabled', isset($_POST['affichat_wp_jetform_enabled']) ? 'yes' : 'no');
            update_option('affichat_wp_elementor_enabled', isset($_POST['affichat_wp_elementor_enabled']) ? 'yes' : 'no');
            update_option('affichat_wp_cf7_enabled', isset($_POST['affichat_wp_cf7_enabled']) ? 'yes' : 'no');
            update_option('affichat_wp_wpforms_enabled', isset($_POST['affichat_wp_wpforms_enabled']) ? 'yes' : 'no');
            update_option('affichat_wp_fluentform_enabled', isset($_POST['affichat_wp_fluentform_enabled']) ? 'yes' : 'no');
            update_option('affichat_wp_user_reg_admin_notify', isset($_POST['affichat_wp_user_reg_admin_notify']) ? 'yes' : 'no');

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Pengaturan formulir berhasil disimpan.', 'affichat-wp') . '</p></div>';
        }

        $form_tags = AffiChat_WP_Tags::get_form_tags();
        $jetform_active = class_exists('Jet_Form_Builder\Plugin') || defined('JET_FORM_BUILDER_VERSION');
        $elementor_active = did_action('elementor/loaded') || defined('ELEMENTOR_VERSION');
        $cf7_active = class_exists('WPCF7');
        $wpforms_active = class_exists('WPForms');
        $fluent_active = function_exists('wpFluentForm');
        ?>
        <div class="affichat-wrap">
            <?php self::render_header('integrations'); ?>

            <form method="post" action="">
                <?php wp_nonce_field('affichat_wp_save_forms_action'); ?>

                <!-- 1. Universal Website Form Automations -->
                <div class="affichat-card">
                    <div class="affichat-card-header">
                        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                            <div>
                                <h3><?php esc_html_e('Otomasi Formulir Website Universal', 'affichat-wp'); ?></h3>
                                <p><?php esc_html_e('Kirim auto-reply WhatsApp ke pengunjung dan kirim alert notifikasi ke admin saat ada formulir terkirim.', 'affichat-wp'); ?></p>
                            </div>
                            <span class="affichat-badge affichat-badge-success"><?php esc_html_e('Multi-Form Builder Support', 'affichat-wp'); ?></span>
                        </div>
                    </div>

                    <div class="affichat-section-box">
                        <label class="affichat-toggle-label">
                            <input type="checkbox" name="affichat_wp_form_user_reply_enabled" value="yes" <?php checked(get_option('affichat_wp_form_user_reply_enabled', 'no'), 'yes'); ?> />
                            <strong><?php esc_html_e('Kirim Auto-Reply WhatsApp ke Pengirim Formulir', 'affichat-wp'); ?></strong>
                        </label>
                        <div class="affichat-form-group" style="margin-bottom:0;">
                            <div class="affichat-field-header">
                                <label for="affichat_wp_form_user_template" style="margin-bottom:0;"><?php esc_html_e('Template Balasan WhatsApp ke Pengirim:', 'affichat-wp'); ?></label>
                                <button type="button" class="affichat-btn-reset-single" data-reset-target="affichat_wp_form_user_template" data-default="<?php echo esc_attr("Halo {name},\n\nTerima kasih telah menghubungi *{site_name}*. Pesan Anda telah kami terima:\n\n\"{message}\"\n\nTim kami akan segera menghubungi Anda kembali."); ?>" title="<?php esc_attr_e('Reset template ini ke default', 'affichat-wp'); ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span><?php esc_html_e('Reset', 'affichat-wp'); ?></span>
                                </button>
                            </div>
                            <textarea id="affichat_wp_form_user_template" name="affichat_wp_form_user_template" class="affichat-textarea" rows="4"><?php echo esc_textarea(get_option('affichat_wp_form_user_template', "Halo {name},\n\nTerima kasih telah menghubungi *{site_name}*. Pesan Anda telah kami terima:\n\n\"{message}\"\n\nTim kami akan segera menghubungi Anda kembali.")); ?></textarea>
                        </div>
                    </div>

                    <div class="affichat-section-box" style="margin-top:14px;">
                        <label class="affichat-toggle-label">
                            <input type="checkbox" name="affichat_wp_form_admin_notify_enabled" value="yes" <?php checked(get_option('affichat_wp_form_admin_notify_enabled', 'yes'), 'yes'); ?> />
                            <strong><?php esc_html_e('Kirim Alert WhatsApp ke Admin saat Ada Formulir Masuk', 'affichat-wp'); ?></strong>
                        </label>
                        <div class="affichat-form-group" style="margin-bottom:0;">
                            <div class="affichat-field-header">
                                <label for="affichat_wp_form_admin_template" style="margin-bottom:0;"><?php esc_html_e('Template Alert ke WhatsApp Admin:', 'affichat-wp'); ?></label>
                                <button type="button" class="affichat-btn-reset-single" data-reset-target="affichat_wp_form_admin_template" data-default="<?php echo esc_attr("📩 *Pesan Formulir Baru Masuk!*\n\nWebsite: {site_name}\nNama: *{name}*\nWhatsApp: {phone}\nEmail: {email}\nSubjek: {subject}\n\n*Isi Pesan:*\n\"{message}\"\n\n_Waktu: {date} {time}_"); ?>" title="<?php esc_attr_e('Reset template ini ke default', 'affichat-wp'); ?>">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    <span><?php esc_html_e('Reset', 'affichat-wp'); ?></span>
                                </button>
                            </div>
                            <textarea id="affichat_wp_form_admin_template" name="affichat_wp_form_admin_template" class="affichat-textarea" rows="4"><?php echo esc_textarea(get_option('affichat_wp_form_admin_template', "📩 *Pesan Formulir Baru Masuk!*\n\nWebsite: {site_name}\nNama: *{name}*\nWhatsApp: {phone}\nEmail: {email}\nSubjek: {subject}\n\n*Isi Pesan:*\n\"{message}\"\n\n_Waktu: {date} {time}_")); ?></textarea>
                        </div>
                    </div>

                    <div class="affichat-tag-chips-wrapper" style="margin-top:16px;">
                        <span class="affichat-tag-chips-title"><?php esc_html_e('Tag Dinamis Formulir (Klik untuk menyisipkan ke template yang aktif):', 'affichat-wp'); ?></span>
                        <div class="affichat-chips-container">
                            <?php foreach ($form_tags as $ftag => $flabel) : ?>
                                <button type="button" class="affichat-tag-chip" data-tag="<?php echo esc_attr($ftag); ?>" title="<?php echo esc_attr($flabel); ?>">
                                    <?php echo esc_html($ftag); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- 2. Form Builders Compatibility Switches -->
                <div class="affichat-card">
                    <div class="affichat-card-header">
                        <h3><?php esc_html_e('Form Builders yang Didukung', 'affichat-wp'); ?></h3>
                        <p><?php esc_html_e('Aktifkan form builder yang digunakan di website Anda untuk mendengarkan pengiriman formulir otomatis.', 'affichat-wp'); ?></p>
                    </div>

                    <div class="affichat-grid-2">
                        <!-- JetFormBuilder -->
                        <div class="affichat-section-box">
                            <label class="affichat-toggle-label">
                                <input type="checkbox" name="affichat_wp_jetform_enabled" value="yes" <?php checked(get_option('affichat_wp_jetform_enabled', $jetform_active ? 'yes' : 'no'), 'yes'); ?> />
                                <strong><?php esc_html_e('JetFormBuilder (Crocoblock)', 'affichat-wp'); ?></strong>
                            </label>
                            <p style="margin:0; font-size:12px; color:var(--affi-text-muted);">
                                <?php echo $jetform_active ? '<span style="color:#059669; font-weight:600;">Terdeteksi Aktif</span>' : 'Plugin form JetFormBuilder'; ?>
                            </p>
                        </div>

                        <!-- Elementor Forms -->
                        <div class="affichat-section-box">
                            <label class="affichat-toggle-label">
                                <input type="checkbox" name="affichat_wp_elementor_enabled" value="yes" <?php checked(get_option('affichat_wp_elementor_enabled', $elementor_active ? 'yes' : 'no'), 'yes'); ?> />
                                <strong><?php esc_html_e('Elementor Pro Forms', 'affichat-wp'); ?></strong>
                            </label>
                            <p style="margin:0; font-size:12px; color:var(--affi-text-muted);">
                                <?php echo $elementor_active ? '<span style="color:#059669; font-weight:600;">Terdeteksi Aktif</span>' : 'Widget form bawaan Elementor Pro'; ?>
                            </p>
                        </div>

                        <!-- Contact Form 7 -->
                        <div class="affichat-section-box">
                            <label class="affichat-toggle-label">
                                <input type="checkbox" name="affichat_wp_cf7_enabled" value="yes" <?php checked(get_option('affichat_wp_cf7_enabled', $cf7_active ? 'yes' : 'no'), 'yes'); ?> />
                                <strong><?php esc_html_e('Contact Form 7', 'affichat-wp'); ?></strong>
                            </label>
                            <p style="margin:0; font-size:12px; color:var(--affi-text-muted);">
                                <?php echo $cf7_active ? '<span style="color:#059669; font-weight:600;">Terdeteksi Aktif</span>' : 'Form Contact Form 7'; ?>
                            </p>
                        </div>

                        <!-- WPForms & Fluent Forms -->
                        <div class="affichat-section-box">
                            <label class="affichat-toggle-label">
                                <input type="checkbox" name="affichat_wp_wpforms_enabled" value="yes" <?php checked(get_option('affichat_wp_wpforms_enabled', ($wpforms_active || $fluent_active) ? 'yes' : 'no'), 'yes'); ?> />
                                <strong><?php esc_html_e('WPForms & Fluent Forms', 'affichat-wp'); ?></strong>
                            </label>
                            <p style="margin:0; font-size:12px; color:var(--affi-text-muted);">
                                <?php echo ($wpforms_active || $fluent_active) ? '<span style="color:#059669; font-weight:600;">Terdeteksi Aktif</span>' : 'WPForms & Fluent Forms'; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 3. Universal REST API & Frontend Integration -->
                <div class="affichat-card">
                    <div class="affichat-card-header">
                        <h3><?php esc_html_e('Universal REST API Endpoint (Untuk Frontend / AJAX / Aplikasi Luar)', 'affichat-wp'); ?></h3>
                        <p><?php esc_html_e('Kirim pesan WhatsApp atau submit formulir dari mana saja menggunakan endpoint standar WordPress REST API.', 'affichat-wp'); ?></p>
                    </div>

                    <div class="affichat-code-box">
                        <p><strong><?php esc_html_e('Endpoint 1: Submit Data Form & Kirim Notifikasi via JavaScript (fetch):', 'affichat-wp'); ?></strong></p>
                        <pre><code>fetch('<?php echo esc_url(rest_url('affichat/v1/form')); ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        name: 'Ahmad Fauzi',
        phone: '081234567890',
        email: 'ahmad@example.com',
        subject: 'Permintaan Penawaran',
        message: 'Halo, saya tertarik dengan layanan Anda.'
    })
})
.then(res => res.json())
.then(data => console.log('WhatsApp Sent:', data));</code></pre>

                        <p style="margin-top:16px;"><strong><?php esc_html_e('Endpoint 2: Kirim Pesan Teks Langsung:', 'affichat-wp'); ?></strong></p>
                        <pre><code>POST <?php echo esc_url(rest_url('affichat/v1/send')); ?>

Payload JSON:
{
    "phone": "081234567890",
    "message": "Halo, ini pesan teks langsung dari website Anda!"
}</code></pre>
                    </div>
                </div>

                <!-- 4. Global PHP Helper & Developer Hooks -->
                <div class="affichat-card">
                    <div class="affichat-card-header">
                        <h3><?php esc_html_e('Fungsi Global PHP & Shortcode WordPress', 'affichat-wp'); ?></h3>
                        <p><?php esc_html_e('Panggil fungsi kirim WhatsApp langsung dari file functions.php tema atau gunakan shortcode di halaman.', 'affichat-wp'); ?></p>
                    </div>

                    <div class="affichat-code-box">
                        <p><strong><?php esc_html_e('Kirim WhatsApp dari Kode PHP:', 'affichat-wp'); ?></strong></p>
                        <pre><code>&lt;?php
// Panggil fungsi global dari mana saja:
affichat_send_whatsapp('081234567890', 'Halo, pesan dari sistem website.');

// Atau gunakan action hook:
do_action('affichat_send_whatsapp', '081234567890', 'Pesan notifikasi via hook');
?&gt;</code></pre>

                        <p style="margin-top:16px;"><strong><?php esc_html_e('Shortcode Tombol Chat WhatsApp:', 'affichat-wp'); ?></strong></p>
                        <pre><code>[affichat_button phone="081234567890" text="Hubungi Kami via WhatsApp" message="Halo, saya ingin bertanya tentang produk Anda."]</code></pre>
                    </div>

                    <div class="affichat-form-actions" style="margin-top:24px;">
                        <button type="submit" name="affichat_wp_save_forms" class="affichat-btn affichat-btn-primary">
                            <?php esc_html_e('Simpan Pengaturan Form & Integrasi', 'affichat-wp'); ?>
                        </button>
                        <button type="button" class="affichat-btn affichat-btn-secondary affichat-btn-reset-all" data-reset-section="forms">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle; margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span><?php esc_html_e('Reset Semua ke Default', 'affichat-wp'); ?></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * AJAX handler for connection test.
     */
    public static function ajax_test_connection() {
        check_ajax_referer('affichat_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Akses ditolak.', 'affichat-wp')]);
            return;
        }

        $api_key    = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field(wp_unslash($_POST['session_id'])) : 'default';
        $base_url   = !empty($_POST['base_url']) ? esc_url_raw(wp_unslash($_POST['base_url'])) : 'https://chat.affidev.com';

        $api = new AffiChat_WP_API($api_key, $session_id, $base_url);
        $result = $api->check_connection();

        if ($result['success']) {
            wp_send_json_success(['message' => $result['message']]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }

    /**
     * AJAX handler for Quick Send WhatsApp.
     */
    public static function ajax_quick_send() {
        check_ajax_referer('affichat_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Akses ditolak.', 'affichat-wp')]);
            return;
        }

        $phone   = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';

        if (empty($phone) || empty($message)) {
            wp_send_json_error(['message' => __('Nomor telepon dan pesan tidak boleh kosong.', 'affichat-wp')]);
            return;
        }

        $parsed_message = AffiChat_WP_Tags::replace($message);

        $api = new AffiChat_WP_API();
        $result = $api->send_text($phone, $parsed_message);

        if ($result['success']) {
            wp_send_json_success(['message' => __('Pesan WhatsApp berhasil dikirim!', 'affichat-wp')]);
        } else {
            wp_send_json_error(['message' => $result['message']]);
        }
    }
}
