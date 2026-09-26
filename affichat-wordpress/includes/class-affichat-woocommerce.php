<?php
/**
 * WooCommerce Integration Module for AffiChat.
 *
 * Automatically hooks into WooCommerce order lifecycle when WooCommerce is active.
 *
 * @package AffiChat_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_Settings_Page')) {
    if (defined('WC_ABSPATH') && file_exists(WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php')) {
        include_once WC_ABSPATH . 'includes/admin/settings/class-wc-settings-page.php';
    }
}

if (class_exists('WC_Settings_Page')) {
    class AffiChat_WP_WC_Settings_Parent extends WC_Settings_Page {}
} else {
    class AffiChat_WP_WC_Settings_Parent {
        public $id = '';
        public $label = '';
        public function __construct() {}
        public function get_id() { return $this->id; }
        public function get_label() { return $this->label; }
        public function get_sections() { return []; }
    }
}

class AffiChat_WP_WC_Settings extends AffiChat_WP_WC_Settings_Parent {
    /**
     * Tab ID.
     * @var string
     */
    public $id;

    /**
     * Tab label.
     * @var string
     */
    public $label;

    public function __construct() {
        $this->id    = 'affichat_whatsapp';
        $this->label = __('WhatsApp AffiChat', 'affichat-wp');

        if (is_subclass_of($this, 'WC_Settings_Page')) {
            parent::__construct();
        } else {
            add_action('woocommerce_settings_' . $this->id, [$this, 'output']);
            add_action('woocommerce_settings_save_' . $this->id, [$this, 'save']);
        }
    }

    public function get_id() {
        return $this->id;
    }

    public function get_label() {
        return $this->label;
    }

    public function get_settings($current_section = '') {
        return AffiChat_WP_WooCommerce::get_wc_settings_fields();
    }

    public function output() {
        AffiChat_WP_WooCommerce::render_wc_settings_fields_html(true);
    }

    public function save() {
        if (!empty($_POST)) {
            update_option('affichat_wc_admin_enabled', isset($_POST['affichat_wc_admin_enabled']) ? 'yes' : 'no');
            if (isset($_POST['affichat_wc_admin_phone'])) {
                update_option('affichat_wc_admin_phone', sanitize_text_field($_POST['affichat_wc_admin_phone']));
            }
            if (isset($_POST['affichat_wc_admin_template'])) {
                update_option('affichat_wc_admin_template', wp_kses_post($_POST['affichat_wc_admin_template']));
            }

            $statuses = ['pending', 'processing', 'onhold', 'completed', 'cancelled', 'refunded'];
            foreach ($statuses as $st) {
                update_option("affichat_wc_cust_{$st}_enabled", isset($_POST["affichat_wc_cust_{$st}_enabled"]) ? 'yes' : 'no');
                if (isset($_POST["affichat_wc_cust_{$st}_template"])) {
                    update_option("affichat_wc_cust_{$st}_template", wp_kses_post($_POST["affichat_wc_cust_{$st}_template"]));
                }
            }
            update_option('affichat_wc_completed_send_image', isset($_POST['affichat_wc_completed_send_image']) ? 'yes' : 'no');

            if (function_exists('woocommerce_update_options')) {
                woocommerce_update_options($this->get_settings());
            }
        }
    }
}

class AffiChat_WP_WooCommerce {
    /**
     * Initializes WooCommerce hooks if WooCommerce is present.
     */
    public static function init() {
        if (!class_exists('WooCommerce')) {
            return;
        }

        add_filter('woocommerce_get_settings_pages', [__CLASS__, 'register_wc_settings_page']);
        add_action('woocommerce_order_status_pending', [__CLASS__, 'trigger_customer_pending'], 10, 1);
        add_action('woocommerce_order_status_processing', [__CLASS__, 'trigger_customer_processing'], 10, 1);
        add_action('woocommerce_order_status_on-hold', [__CLASS__, 'trigger_customer_onhold'], 10, 1);
        add_action('woocommerce_order_status_completed', [__CLASS__, 'trigger_customer_completed'], 10, 1);
        add_action('woocommerce_order_status_cancelled', [__CLASS__, 'trigger_customer_cancelled'], 10, 1);
        add_action('woocommerce_order_status_refunded', [__CLASS__, 'trigger_customer_refunded'], 10, 1);
        add_action('woocommerce_checkout_order_processed', [__CLASS__, 'trigger_admin_new_order'], 10, 1);
        add_action('woocommerce_order_actions', [__CLASS__, 'add_order_wa_actions']);
        add_action('woocommerce_order_action_affichat_send_status_wa', [__CLASS__, 'handle_order_wa_action']);
    }

    public static function register_wc_settings_page($settings) {
        $settings[] = new AffiChat_WP_WC_Settings();
        return $settings;
    }

    /**
     * Renders unified modern settings cards for WooCommerce tab and admin page.
     *
     * @param bool $is_wc_native_tab Whether output is rendered inside native WC Settings tab.
     */
    public static function render_wc_settings_fields_html($is_wc_native_tab = false) {
        $tags = AffiChat_WP_Tags::get_wc_tags();

        $admin_tpl_def = "🔔 *Pesanan Baru Masuk!*\n\nNomor: #{order_number}\nNama: {customer_name}\nTelepon: {customer_phone}\nTotal: {order_total}\nMetode: {payment_method}\n\n*Daftar Produk:*\n{items_list}\n\nMohon segera diproses via admin toko.";
        $pending_tpl_def = "Halo {customer_name},\n\nTerima kasih telah berbelanja di *{store_name}*! Pesanan Anda #{order_number} telah kami terima.\n\n*Rincian Pembayaran:*\nTotal: {order_total}\nMetode: {payment_method}\n\n*Produk:*\n{items_list}\n\nSilakan selesaikan pembayaran agar pesanan dapat segera diproses.";
        $processing_tpl_def = "Halo {customer_name},\n\nPembayaran untuk pesanan #{order_number} telah berhasil kami terima! Pesanan Anda sedang dipersiapkan oleh tim kami.\n\nTotal: {order_total}\nKami akan mengabari Anda kembali setelah pesanan dikirimkan.";
        $onhold_tpl_def = "Halo {customer_name},\n\nPesanan #{order_number} di *{store_name}* sedang *Ditahan (On-Hold)* menunggu verifikasi pembayaran.\n\nTotal: {order_total}\nJika sudah melakukan pembayaran, mohon konfirmasi bukti transfer ke nomor ini.";
        $completed_tpl_def = "Halo {customer_name},\n\nPesanan #{order_number} Anda telah selesai diproses dan dikirimkan!\n\nTerima kasih telah berbelanja di *{store_name}*. Jika pesanan telah sampai dengan baik, kami akan sangat berterima kasih atas ulasan Anda.";
        $cancelled_tpl_def = "Halo {customer_name},\n\nPesanan #{order_number} di *{store_name}* telah dibatalkan. Jika Anda membutuhkan bantuan lebih lanjut, silakan hubungi tim kami.";
        $refunded_tpl_def = "Halo {customer_name},\n\nPesanan #{order_number} di *{store_name}* telah kami kembalikan dananya (*Refunded*).\n\nTotal Pengembalian: {order_total}\nSilakan periksa saldo rekening/metode pembayaran Anda. Terima kasih!";
        ?>
        <div class="affichat-wrap <?php echo $is_wc_native_tab ? 'affichat-wc-tab-wrap' : ''; ?>" style="max-width:<?php echo $is_wc_native_tab ? '1000px' : '100%'; ?>;">
            <?php if ($is_wc_native_tab) : ?>
                <div class="affichat-wc-banner">
                    <div class="affichat-wc-banner-info">
                        <img src="<?php echo esc_url(AFFICHAT_WP_URL . 'assets/images/icon-128x128.png'); ?>" alt="AffiChat Logo" class="affichat-wc-banner-logo" width="22" height="22" />
                        <span class="affichat-wc-banner-title"><?php esc_html_e('Notifikasi WhatsApp WooCommerce', 'affichat-wp'); ?></span>
                        <span class="affichat-badge affichat-badge-success"><?php esc_html_e('Aktif', 'affichat-wp'); ?></span>
                    </div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=affichat-wp')); ?>" class="affichat-wc-banner-link">
                        <?php esc_html_e('Pengaturan Gateway', 'affichat-wp'); ?> &rarr;
                    </a>
                </div>
            <?php endif; ?>

            <!-- Card 1: Admin Notification -->
            <div class="affichat-card">
                <div class="affichat-card-header">
                    <h3><?php esc_html_e('Notifikasi Admin Toko', 'affichat-wp'); ?></h3>
                    <p><?php esc_html_e('Kirim ringkasan otomatis saat ada pesanan baru masuk dari checkout toko.', 'affichat-wp'); ?></p>
                </div>

                <div class="affichat-form-group">
                    <label class="affichat-toggle-label">
                        <input type="checkbox" name="affichat_wc_admin_enabled" value="yes" <?php checked(get_option('affichat_wc_admin_enabled', 'yes'), 'yes'); ?> />
                        <strong><?php esc_html_e('Aktifkan Notifikasi Admin', 'affichat-wp'); ?></strong>
                    </label>
                </div>

                <div class="affichat-form-group">
                    <label for="affichat_wc_admin_phone"><?php esc_html_e('Nomor WhatsApp Admin Toko', 'affichat-wp'); ?></label>
                    <input type="text" id="affichat_wc_admin_phone" name="affichat_wc_admin_phone" value="<?php echo esc_attr(get_option('affichat_wc_admin_phone', '')); ?>" class="affichat-input" placeholder="081234567890" />
                </div>

                <div class="affichat-form-group">
                    <div class="affichat-field-header">
                        <label for="affichat_wc_admin_template"><?php esc_html_e('Template Pesan Admin', 'affichat-wp'); ?></label>
                        <button type="button" class="affichat-btn-reset-single" data-reset-target="affichat_wc_admin_template" data-default="<?php echo esc_attr($admin_tpl_def); ?>" title="<?php esc_attr_e('Reset template ini ke default', 'affichat-wp'); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span><?php esc_html_e('Reset', 'affichat-wp'); ?></span>
                        </button>
                    </div>
                    <textarea id="affichat_wc_admin_template" name="affichat_wc_admin_template" class="affichat-textarea" rows="5"><?php echo esc_textarea(get_option('affichat_wc_admin_template', $admin_tpl_def)); ?></textarea>
                </div>
            </div>

            <!-- Card 2: Customer Notifications -->
            <div class="affichat-card">
                <div class="affichat-card-header">
                    <h3><?php esc_html_e('Notifikasi Pembeli (Customer)', 'affichat-wp'); ?></h3>
                    <p><?php esc_html_e('Pesan otomatis yang dikirim ke nomor WhatsApp pembeli berdasarkan perubahan status pesanan.', 'affichat-wp'); ?></p>
                </div>

                <!-- Status Pending -->
                <div class="affichat-section-box">
                    <div class="affichat-section-box-header">
                        <label class="affichat-toggle-label">
                            <input type="checkbox" name="affichat_wc_cust_pending_enabled" value="yes" <?php checked(get_option('affichat_wc_cust_pending_enabled', 'yes'), 'yes'); ?> />
                            <strong><?php esc_html_e('Status Pending (Menunggu Pembayaran)', 'affichat-wp'); ?></strong>
                        </label>
                        <button type="button" class="affichat-btn-reset-single" data-reset-target="affichat_wc_cust_pending_template" data-default="<?php echo esc_attr($pending_tpl_def); ?>" title="<?php esc_attr_e('Reset template ini ke default', 'affichat-wp'); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span><?php esc_html_e('Reset', 'affichat-wp'); ?></span>
                        </button>
                    </div>
                    <textarea id="affichat_wc_cust_pending_template" name="affichat_wc_cust_pending_template" class="affichat-textarea" rows="4"><?php echo esc_textarea(get_option('affichat_wc_cust_pending_template', $pending_tpl_def)); ?></textarea>
                </div>

                <!-- Status Processing -->
                <div class="affichat-section-box">
                    <div class="affichat-section-box-header">
                        <label class="affichat-toggle-label">
                            <input type="checkbox" name="affichat_wc_cust_processing_enabled" value="yes" <?php checked(get_option('affichat_wc_cust_processing_enabled', 'yes'), 'yes'); ?> />
                            <strong><?php esc_html_e('Status Processing (Pembayaran Diterima / Diproses)', 'affichat-wp'); ?></strong>
                        </label>
                        <button type="button" class="affichat-btn-reset-single" data-reset-target="affichat_wc_cust_processing_template" data-default="<?php echo esc_attr($processing_tpl_def); ?>" title="<?php esc_attr_e('Reset template ini ke default', 'affichat-wp'); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span><?php esc_html_e('Reset', 'affichat-wp'); ?></span>
                        </button>
                    </div>
                    <textarea id="affichat_wc_cust_processing_template" name="affichat_wc_cust_processing_template" class="affichat-textarea" rows="4"><?php echo esc_textarea(get_option('affichat_wc_cust_processing_template', $processing_tpl_def)); ?></textarea>
                </div>

                <!-- Status On-Hold -->
                <div class="affichat-section-box">
                    <div class="affichat-section-box-header">
                        <label class="affichat-toggle-label">
                            <input type="checkbox" name="affichat_wc_cust_onhold_enabled" value="yes" <?php checked(get_option('affichat_wc_cust_onhold_enabled', 'no'), 'yes'); ?> />
                            <strong><?php esc_html_e('Status On-Hold (Menunggu Verifikasi / Ditahan)', 'affichat-wp'); ?></strong>
                        </label>
                        <button type="button" class="affichat-btn-reset-single" data-reset-target="affichat_wc_cust_onhold_template" data-default="<?php echo esc_attr($onhold_tpl_def); ?>" title="<?php esc_attr_e('Reset template ini ke default', 'affichat-wp'); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span><?php esc_html_e('Reset', 'affichat-wp'); ?></span>
                        </button>
                    </div>
                    <textarea id="affichat_wc_cust_onhold_template" name="affichat_wc_cust_onhold_template" class="affichat-textarea" rows="4"><?php echo esc_textarea(get_option('affichat_wc_cust_onhold_template', $onhold_tpl_def)); ?></textarea>
                </div>

                <!-- Status Completed -->
                <div class="affichat-section-box">
                    <div class="affichat-section-box-header">
                        <label class="affichat-toggle-label">
                            <input type="checkbox" name="affichat_wc_cust_completed_enabled" value="yes" <?php checked(get_option('affichat_wc_cust_completed_enabled', 'yes'), 'yes'); ?> />
                            <strong><?php esc_html_e('Status Completed (Pesanan Selesai / Dikirim)', 'affichat-wp'); ?></strong>
                        </label>
                        <button type="button" class="affichat-btn-reset-single" data-reset-target="affichat_wc_cust_completed_template" data-default="<?php echo esc_attr($completed_tpl_def); ?>" title="<?php esc_attr_e('Reset template ini ke default', 'affichat-wp'); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span><?php esc_html_e('Reset', 'affichat-wp'); ?></span>
                        </button>
                    </div>
                    <textarea id="affichat_wc_cust_completed_template" name="affichat_wc_cust_completed_template" class="affichat-textarea" rows="4"><?php echo esc_textarea(get_option('affichat_wc_cust_completed_template', $completed_tpl_def)); ?></textarea>
                    <div style="margin-top:10px;">
                        <label class="affichat-toggle-label" style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="affichat_wc_completed_send_image" value="yes" <?php checked(get_option('affichat_wc_completed_send_image', 'no'), 'yes'); ?> />
                            <span style="font-size:13px; color:var(--affi-text-muted);"><?php esc_html_e('Sertakan foto produk utama (send_image) pada notifikasi pesanan selesai', 'affichat-wp'); ?></span>
                        </label>
                    </div>
                </div>

                <!-- Status Cancelled -->
                <div class="affichat-section-box">
                    <div class="affichat-section-box-header">
                        <label class="affichat-toggle-label">
                            <input type="checkbox" name="affichat_wc_cust_cancelled_enabled" value="yes" <?php checked(get_option('affichat_wc_cust_cancelled_enabled', 'no'), 'yes'); ?> />
                            <strong><?php esc_html_e('Status Cancelled (Pesanan Dibatalkan)', 'affichat-wp'); ?></strong>
                        </label>
                        <button type="button" class="affichat-btn-reset-single" data-reset-target="affichat_wc_cust_cancelled_template" data-default="<?php echo esc_attr($cancelled_tpl_def); ?>" title="<?php esc_attr_e('Reset template ini ke default', 'affichat-wp'); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span><?php esc_html_e('Reset', 'affichat-wp'); ?></span>
                        </button>
                    </div>
                    <textarea id="affichat_wc_cust_cancelled_template" name="affichat_wc_cust_cancelled_template" class="affichat-textarea" rows="3"><?php echo esc_textarea(get_option('affichat_wc_cust_cancelled_template', $cancelled_tpl_def)); ?></textarea>
                </div>

                <!-- Status Refunded -->
                <div class="affichat-section-box">
                    <div class="affichat-section-box-header">
                        <label class="affichat-toggle-label">
                            <input type="checkbox" name="affichat_wc_cust_refunded_enabled" value="yes" <?php checked(get_option('affichat_wc_cust_refunded_enabled', 'no'), 'yes'); ?> />
                            <strong><?php esc_html_e('Status Refunded (Dana Dikembalikan)', 'affichat-wp'); ?></strong>
                        </label>
                        <button type="button" class="affichat-btn-reset-single" data-reset-target="affichat_wc_cust_refunded_template" data-default="<?php echo esc_attr($refunded_tpl_def); ?>" title="<?php esc_attr_e('Reset template ini ke default', 'affichat-wp'); ?>">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span><?php esc_html_e('Reset', 'affichat-wp'); ?></span>
                        </button>
                    </div>
                    <textarea id="affichat_wc_cust_refunded_template" name="affichat_wc_cust_refunded_template" class="affichat-textarea" rows="3"><?php echo esc_textarea(get_option('affichat_wc_cust_refunded_template', $refunded_tpl_def)); ?></textarea>
                </div>

                <!-- Tag Chips Helper -->
                <div class="affichat-tag-chips-wrapper">
                    <span class="affichat-tag-chips-title"><?php esc_html_e('Variabel Pesanan (Klik tag untuk menyisipkan ke pesan):', 'affichat-wp'); ?></span>
                    <div class="affichat-chips-container">
                        <?php foreach ($tags as $tag => $label) : ?>
                            <button type="button" class="affichat-tag-chip" data-tag="<?php echo esc_attr($tag); ?>" title="<?php echo esc_attr($label); ?>">
                                <?php echo esc_html($tag); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="affichat-form-actions">
                    <?php if (!$is_wc_native_tab) : ?>
                        <button type="submit" name="affichat_wp_save_wc" class="affichat-btn affichat-btn-primary">
                            <?php esc_html_e('Simpan Notifikasi WooCommerce', 'affichat-wp'); ?>
                        </button>
                    <?php endif; ?>
                    <button type="button" class="affichat-btn-reset-outline affichat-btn-reset-all" data-reset-section="woocommerce">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span><?php esc_html_e('Reset Template Default', 'affichat-wp'); ?></span>
                    </button>
                </div>
            </div>

            <!-- Preserve legacy connection settings if submitted via WC Settings form -->
            <input type="hidden" name="affichat_wc_api_key" value="<?php echo esc_attr(get_option('affichat_wc_api_key', '')); ?>" />
            <input type="hidden" name="affichat_wc_session_id" value="<?php echo esc_attr(get_option('affichat_wc_session_id', get_option('affichat_wp_session_id', 'default'))); ?>" />
        </div>
        <?php
    }

    public static function trigger_customer_pending($order_id) {
        self::dispatch_customer_notification($order_id, 'pending');
    }

    public static function trigger_customer_processing($order_id) {
        self::dispatch_customer_notification($order_id, 'processing');
    }

    public static function trigger_customer_onhold($order_id) {
        self::dispatch_customer_notification($order_id, 'onhold');
    }

    public static function trigger_customer_completed($order_id) {
        self::dispatch_customer_notification($order_id, 'completed');
    }

    public static function trigger_customer_cancelled($order_id) {
        self::dispatch_customer_notification($order_id, 'cancelled');
    }

    public static function trigger_customer_refunded($order_id) {
        self::dispatch_customer_notification($order_id, 'refunded');
    }

    public static function add_order_wa_actions($actions) {
        $actions['affichat_send_status_wa'] = __('Kirim Ulang Notifikasi WhatsApp (AffiChat)', 'affichat-wp');
        return $actions;
    }

    public static function handle_order_wa_action($order) {
        if (!$order) {
            return;
        }
        $status = method_exists($order, 'get_status') ? $order->get_status() : '';
        if ($status) {
            $order->delete_meta_data("_affichat_sent_{$status}");
            $order->save();
            self::dispatch_customer_notification($order->get_id(), $status);
        }
    }

    public static function trigger_admin_new_order($order_id) {
        if (get_option('affichat_wc_admin_enabled', 'yes') !== 'yes') {
            return;
        }

        $admin_phone = get_option('affichat_wc_admin_phone', '');
        if (empty($admin_phone)) {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        // Prevents duplicate admin alerts if checkout hook runs multiple times.
        if ($order->get_meta('_affichat_admin_sent')) {
            return;
        }

        $template = get_option('affichat_wc_admin_template', '');
        if (empty(trim($template))) {
            return;
        }

        $message = AffiChat_WP_Tags::replace($template, $order);

        try {
            $api = new AffiChat_WP_API();
            $result = $api->send_text($admin_phone, $message);
            if (!empty($result['success'])) {
                $order->update_meta_data('_affichat_admin_sent', current_time('mysql'));
                $order->save();
            }
        } catch (\Throwable $e) {
            self::log_exception('admin_new_order', $e);
        }
    }

    private static function dispatch_customer_notification($order_id, $status) {
        $default = ($status === 'cancelled' || $status === 'onhold' || $status === 'refunded') ? 'no' : 'yes';
        $enabled = get_option("affichat_wc_cust_{$status}_enabled", $default);
        if ($enabled !== 'yes') {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $phone = method_exists($order, 'get_billing_phone') ? $order->get_billing_phone() : '';
        if (empty($phone)) {
            return;
        }

        // Idempotency flag prevents sending repeat status notices for the same order transition.
        $meta_key = "_affichat_sent_{$status}";
        if ($order->get_meta($meta_key)) {
            return;
        }

        $template = get_option("affichat_wc_cust_{$status}_template", '');
        if (empty(trim($template))) {
            return;
        }

        $message = AffiChat_WP_Tags::replace($template, $order);

        try {
            $api = new AffiChat_WP_API();
            $result = null;

            // Optional: send product image for completed orders if enabled
            if ($status === 'completed' && get_option('affichat_wc_completed_send_image', 'no') === 'yes') {
                $items = method_exists($order, 'get_items') ? $order->get_items() : [];
                foreach ($items as $item) {
                    $product = is_object($item) && method_exists($item, 'get_product') ? $item->get_product() : null;
                    if ($product && method_exists($product, 'get_image_id')) {
                        $image_id = $product->get_image_id();
                        if ($image_id) {
                            $image_url = wp_get_attachment_image_url($image_id, 'full');
                            if ($image_url) {
                                $result = $api->send_image($phone, $image_url, $message);
                                break;
                            }
                        }
                    }
                }
            }

            if ($result === null) {
                $result = $api->send_text($phone, $message);
            }

            if (!empty($result['success'])) {
                $order->update_meta_data($meta_key, current_time('mysql'));
                $order->save();
            }
        } catch (\Throwable $e) {
            self::log_exception("customer_{$status}", $e);
        }
    }

    private static function log_exception($context, \Throwable $e) {
        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->error("Exception in {$context}: " . $e->getMessage(), ['source' => 'affichat-wp']);
        }
    }

    /**
     * Default settings fields definitions for WooCommerce settings tab.
     *
     * @return array Form fields configuration.
     */
    public static function get_wc_settings_fields() {
        return [
            [
                'title' => __('Kredensial API AffiChat', 'affichat-wp'),
                'type'  => 'title',
                'desc'  => sprintf(__('Dikelola melalui menu <a href="%s">AffiChat WA &rarr; Pengaturan</a> di sidebar.', 'affichat-wp'), esc_url(admin_url('admin.php?page=affichat-wp'))),
                'id'    => 'affichat_wc_connection_section',
            ],
            [
                'title'    => __('API Key', 'affichat-wp'),
                'desc'     => __('Kunci otorisasi x-api-key dari Dashboard AffiChat (https://chat.affidev.com).', 'affichat-wp'),
                'id'       => 'affichat_wc_api_key',
                'type'     => 'password',
                'default'  => '',
                'css'      => 'min-width:350px;',
                'desc_tip' => true,
            ],
            [
                'title'    => __('ID Sesi WhatsApp', 'affichat-wp'),
                'desc'     => __('ID sesi nomor WhatsApp aktif yang digunakan (default: default).', 'affichat-wp'),
                'id'       => 'affichat_wc_session_id',
                'type'     => 'text',
                'default'  => 'default',
                'css'      => 'min-width:200px;',
                'desc_tip' => true,
            ],
            [
                'type' => 'sectionend',
                'id'   => 'affichat_wc_connection_section',
            ],
            [
                'title' => __('Notifikasi Admin Toko', 'affichat-wp'),
                'type'  => 'title',
                'desc'  => __('Kirim ringkasan otomatis ke WhatsApp admin saat ada pesanan baru masuk.', 'affichat-wp'),
                'id'    => 'affichat_wc_admin_section',
            ],
            [
                'title'   => __('Aktifkan Notifikasi Admin', 'affichat-wp'),
                'id'      => 'affichat_wc_admin_enabled',
                'type'    => 'checkbox',
                'default' => 'yes',
                'desc'    => __('Kirim pesan WhatsApp ke nomor admin toko saat pesanan dibuat.', 'affichat-wp'),
            ],
            [
                'title'    => __('Nomor WhatsApp Admin', 'affichat-wp'),
                'desc'     => __('Nomor WhatsApp penerima (format: 0812... atau 62812...).', 'affichat-wp'),
                'id'       => 'affichat_wc_admin_phone',
                'type'     => 'text',
                'default'  => '',
                'css'      => 'min-width:250px;',
                'desc_tip' => true,
            ],
            [
                'title'   => __('Template Pesan Admin', 'affichat-wp'),
                'id'      => 'affichat_wc_admin_template',
                'type'    => 'textarea',
                'default' => "🔔 *Pesanan Baru Masuk!*\n\nNomor: #{order_number}\nNama: {customer_name}\nTelepon: {customer_phone}\nTotal: {order_total}\nMetode: {payment_method}\n\n*Daftar Produk:*\n{items_list}\n\nMohon segera diproses via admin toko.",
                'css'     => 'width:100%; min-height:140px;',
            ],
            [
                'type' => 'sectionend',
                'id'   => 'affichat_wc_admin_section',
            ],
            [
                'title' => __('Notifikasi Pembeli (Customer)', 'affichat-wp'),
                'type'  => 'title',
                'desc'  => __('Kirim notifikasi otomatis ke nomor WhatsApp pembeli berdasarkan perubahan status pesanan.', 'affichat-wp'),
                'id'    => 'affichat_wc_customer_section',
            ],
            [
                'title'   => __('Pesanan Pending (Menunggu Pembayaran)', 'affichat-wp'),
                'id'      => 'affichat_wc_cust_pending_enabled',
                'type'    => 'checkbox',
                'default' => 'yes',
                'desc'    => __('Kirim rincian invoice & cara pembayaran saat pesanan baru dibuat.', 'affichat-wp'),
            ],
            [
                'title'   => __('Template Pesan Pending', 'affichat-wp'),
                'id'      => 'affichat_wc_cust_pending_template',
                'type'    => 'textarea',
                'default' => "Halo {customer_name},\n\nTerima kasih telah berbelanja di *{store_name}*! Pesanan Anda #{order_number} telah kami terima.\n\n*Rincian Pembayaran:*\nTotal: {order_total}\nMetode: {payment_method}\n\n*Produk:*\n{items_list}\n\nSilakan selesaikan pembayaran agar pesanan dapat segera diproses.",
                'css'     => 'width:100%; min-height:140px;',
            ],
            [
                'title'   => __('Pesanan Processing (Pembayaran Diterima)', 'affichat-wp'),
                'id'      => 'affichat_wc_cust_processing_enabled',
                'type'    => 'checkbox',
                'default' => 'yes',
                'desc'    => __('Kirim konfirmasi saat pembayaran sudah diterima dan pesanan sedang disiapkan.', 'affichat-wp'),
            ],
            [
                'title'   => __('Template Pesan Processing', 'affichat-wp'),
                'id'      => 'affichat_wc_cust_processing_template',
                'type'    => 'textarea',
                'default' => "Halo {customer_name},\n\nPembayaran untuk pesanan #{order_number} telah berhasil kami terima! Pesanan Anda sedang dipersiapkan oleh tim kami.\n\nTotal: {order_total}\nKami akan mengabari Anda kembali setelah pesanan dikirimkan.",
                'css'     => 'width:100%; min-height:120px;',
            ],
            [
                'title'   => __('Pesanan On-Hold (Menunggu Verifikasi / Ditahan)', 'affichat-wp'),
                'id'      => 'affichat_wc_cust_onhold_enabled',
                'type'    => 'checkbox',
                'default' => 'no',
                'desc'    => __('Kirim notifikasi saat pesanan ditahan menunggu verifikasi.', 'affichat-wp'),
            ],
            [
                'title'   => __('Template Pesan On-Hold', 'affichat-wp'),
                'id'      => 'affichat_wc_cust_onhold_template',
                'type'    => 'textarea',
                'default' => "Halo {customer_name},\n\nPesanan #{order_number} di *{store_name}* sedang *Ditahan (On-Hold)* menunggu verifikasi pembayaran.\n\nTotal: {order_total}\nJika sudah melakukan pembayaran, mohon konfirmasi bukti transfer ke nomor ini.",
                'css'     => 'width:100%; min-height:120px;',
            ],
            [
                'title'   => __('Pesanan Completed (Selesai / Dikirim)', 'affichat-wp'),
                'id'      => 'affichat_wc_cust_completed_enabled',
                'type'    => 'checkbox',
                'default' => 'yes',
                'desc'    => __('Kirim notifikasi saat pesanan telah selesai atau dikirimkan ke pembeli.', 'affichat-wp'),
            ],
            [
                'title'   => __('Template Pesan Completed', 'affichat-wp'),
                'id'      => 'affichat_wc_cust_completed_template',
                'type'    => 'textarea',
                'default' => "Halo {customer_name},\n\nPesanan #{order_number} Anda telah selesai diproses dan dikirimkan!\n\nTerima kasih telah berbelanja di *{store_name}*. Jika pesanan telah sampai dengan baik, kami akan sangat berterima kasih atas ulasan Anda.",
                'css'     => 'width:100%; min-height:120px;',
            ],
            [
                'title'   => __('Kirim Foto Produk saat Selesai', 'affichat-wp'),
                'id'      => 'affichat_wc_completed_send_image',
                'type'    => 'checkbox',
                'default' => 'no',
                'desc'    => __('Kirim foto produk utama bersama pesan WhatsApp saat pesanan selesai.', 'affichat-wp'),
            ],
            [
                'title'   => __('Pesanan Cancelled (Dibatalkan)', 'affichat-wp'),
                'id'      => 'affichat_wc_cust_cancelled_enabled',
                'type'    => 'checkbox',
                'default' => 'no',
                'desc'    => __('Kirim notifikasi saat pesanan dibatalkan.', 'affichat-wp'),
            ],
            [
                'title'   => __('Template Pesan Cancelled', 'affichat-wp'),
                'id'      => 'affichat_wc_cust_cancelled_template',
                'type'    => 'textarea',
                'default' => "Halo {customer_name},\n\nPesanan #{order_number} di *{store_name}* telah dibatalkan. Jika Anda membutuhkan bantuan lebih lanjut, silakan hubungi tim kami.",
                'css'     => 'width:100%; min-height:100px;',
            ],
            [
                'title'   => __('Pesanan Refunded (Dana Dikembalikan)', 'affichat-wp'),
                'id'      => 'affichat_wc_cust_refunded_enabled',
                'type'    => 'checkbox',
                'default' => 'no',
                'desc'    => __('Kirim notifikasi saat dana pesanan dikembalikan ke pembeli.', 'affichat-wp'),
            ],
            [
                'title'   => __('Template Pesan Refunded', 'affichat-wp'),
                'id'      => 'affichat_wc_cust_refunded_template',
                'type'    => 'textarea',
                'default' => "Halo {customer_name},\n\nPesanan #{order_number} di *{store_name}* telah kami kembalikan dananya (*Refunded*).\n\nTotal Pengembalian: {order_total}\nSilakan periksa saldo rekening/metode pembayaran Anda. Terima kasih!",
                'css'     => 'width:100%; min-height:100px;',
            ],
            [
                'type' => 'sectionend',
                'id'   => 'affichat_wc_customer_section',
            ],
        ];
    }
}
