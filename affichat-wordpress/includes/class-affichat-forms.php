<?php
/**
 * Universal Form Integrations and REST API Handlers for AffiChat.
 *
 * Supports JetFormBuilder, Elementor Pro Forms, Contact Form 7, WPForms,
 * Fluent Forms, generic website forms, and universal REST API endpoints.
 *
 * @package AffiChat_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiChat_WP_Forms {
    /**
     * Initializes universal form builder hooks and endpoints.
     */
    public static function init() {
        // Direct developer hooks
        add_action('affichat_send_whatsapp', [__CLASS__, 'handle_custom_action'], 10, 2);
        add_action('affichat_send_message', [__CLASS__, 'handle_custom_action'], 10, 2);
        add_action('affichat_form_submitted', [__CLASS__, 'handle_form_action'], 10, 1);

        // Major WordPress form builder hooks
        add_action('jet-form-builder/form-handler/after-send', [__CLASS__, 'handle_jetformbuilder'], 10, 2);
        add_action('elementor_pro/forms/new_record', [__CLASS__, 'handle_elementor_form'], 10, 2);
        add_action('wpcf7_mail_sent', [__CLASS__, 'handle_cf7_form'], 10, 1);
        add_action('wpforms_process_complete', [__CLASS__, 'handle_wpforms'], 10, 4);
        add_action('fluentform/submission_inserted', [__CLASS__, 'handle_fluentform'], 10, 3);

        // Universal AJAX endpoint for any frontend HTML/JS form
        add_action('wp_ajax_affichat_submit_form', [__CLASS__, 'ajax_submit_form']);
        add_action('wp_ajax_nopriv_affichat_submit_form', [__CLASS__, 'ajax_submit_form']);

        // User registration alert
        add_action('user_register', [__CLASS__, 'handle_user_register'], 10, 1);

        // REST API routes
        add_action('rest_api_init', [__CLASS__, 'register_rest_routes']);

        // Universal shortcode
        add_shortcode('affichat_button', [__CLASS__, 'render_wa_button_shortcode']);
    }

    /**
     * Sends WhatsApp message via global action hook.
     */
    public static function handle_custom_action($phone, $message) {
        $api = new AffiChat_WP_API();
        return $api->send_text($phone, $message);
    }

    /**
     * Universal form processor: sends auto-reply to user and alert to admin.
     *
     * @param array $data Form submission payload.
     * @return array Status array.
     */
    public static function process_form_submission($data) {
        $user_auto_reply  = get_option('affichat_wp_form_user_reply_enabled', 'no');
        $admin_alert_mode = get_option('affichat_wp_form_admin_notify_enabled', 'no');
        $admin_phone      = get_option('affichat_wp_admin_phone', get_option('affichat_wc_admin_phone', ''));

        $name    = isset($data['name']) ? sanitize_text_field($data['name']) : '';
        $phone   = isset($data['phone']) ? sanitize_text_field($data['phone']) : '';
        $email   = isset($data['email']) ? sanitize_email($data['email']) : '';
        $subject = isset($data['subject']) ? sanitize_text_field($data['subject']) : get_bloginfo('name');
        $message = isset($data['message']) ? sanitize_textarea_field($data['message']) : '-';

        $tags = [
            '{name}'      => $name ?: __('Pengunjung', 'affichat-wp'),
            '{phone}'     => $phone,
            '{email}'     => $email ?: '-',
            '{subject}'   => $subject,
            '{message}'   => $message,
            '{site_name}' => get_bloginfo('name'),
            '{site_url}'  => site_url(),
            '{date}'      => date_i18n(get_option('date_format', 'Y-m-d')),
            '{time}'      => date_i18n(get_option('time_format', 'H:i')),
        ];

        $api = new AffiChat_WP_API();

        // 1. Send confirmation WhatsApp to user if phone provided
        if ($user_auto_reply === 'yes' && !empty($phone)) {
            $user_template = get_option('affichat_wp_form_user_template', '');
            if (empty(trim($user_template))) {
                $user_template = "Halo {name},\n\nTerima kasih telah menghubungi *{site_name}*. Pesan Anda telah kami terima:\n\n\"{message}\"\n\nTim kami akan segera menindaklanjuti pesan Anda.";
            }
            $user_msg = AffiChat_WP_Tags::replace($user_template, null, $tags);
            $api->send_text($phone, $user_msg);
        }

        // 2. Send alert WhatsApp to admin
        if ($admin_alert_mode === 'yes' && !empty($admin_phone)) {
            $admin_template = get_option('affichat_wp_form_admin_template', '');
            if (empty(trim($admin_template))) {
                $admin_template = "📩 *Pesan Baru Masuk dari Website!*\n\nWebsite: {site_name}\nNama: *{name}*\nWhatsApp: {phone}\nEmail: {email}\nSubjek: {subject}\n\n*Pesan:*\n\"{message}\"\n\n_Waktu: {date} {time}_";
            }
            $admin_msg = AffiChat_WP_Tags::replace($admin_template, null, $tags);
            $api->send_text($admin_phone, $admin_msg);
        }

        return ['success' => true, 'message' => 'Form notifications processed'];
    }

    /**
     * Action hook wrapper: do_action('affichat_form_submitted', $data).
     */
    public static function handle_form_action($data) {
        return self::process_form_submission($data);
    }

    /**
     * JetFormBuilder form handler.
     */
    public static function handle_jetformbuilder($form, $is_success) {
        if (!$is_success) {
            return;
        }

        $enabled = get_option('affichat_wp_jetform_enabled', 'no');
        if ($enabled !== 'yes') {
            return;
        }

        $request = isset($_POST) ? wp_unslash($_POST) : [];
        if (empty($request)) {
            return;
        }

        $extracted = self::extract_common_fields($request);
        if (!empty($extracted['phone']) || !empty($extracted['name'])) {
            self::process_form_submission($extracted);
        }
    }

    /**
     * Elementor Pro Form Action.
     */
    public static function handle_elementor_form($record, $handler) {
        $enabled = get_option('affichat_wp_elementor_enabled', 'no');
        if ($enabled !== 'yes') {
            return;
        }

        $raw_fields = $record->get('fields');
        $raw = [];
        foreach ($raw_fields as $id => $field) {
            $raw[$id] = isset($field['value']) ? $field['value'] : '';
        }

        $extracted = self::extract_common_fields($raw);
        if (!empty($extracted['phone']) || !empty($extracted['name'])) {
            self::process_form_submission($extracted);
        }
    }

    /**
     * Contact Form 7 Handler.
     */
    public static function handle_cf7_form($contact_form) {
        $enabled = get_option('affichat_wp_cf7_enabled', 'no');
        if ($enabled !== 'yes' || !class_exists('WPCF7_Submission')) {
            return;
        }

        $submission = WPCF7_Submission::get_instance();
        if (!$submission) {
            return;
        }

        $data = $submission->get_posted_data();
        $extracted = self::extract_common_fields($data);
        if (!empty($extracted['phone']) || !empty($extracted['name'])) {
            self::process_form_submission($extracted);
        }
    }

    /**
     * WPForms complete handler.
     */
    public static function handle_wpforms($fields, $entry, $form_data, $entry_id) {
        $enabled = get_option('affichat_wp_wpforms_enabled', 'no');
        if ($enabled !== 'yes' || empty($fields)) {
            return;
        }

        $raw = [];
        foreach ($fields as $field) {
            $name = isset($field['name']) ? $field['name'] : (isset($field['id']) ? $field['id'] : '');
            $val  = isset($field['value']) ? $field['value'] : '';
            if ($name) {
                $raw[$name] = $val;
            }
        }

        $extracted = self::extract_common_fields($raw);
        if (!empty($extracted['phone']) || !empty($extracted['name'])) {
            self::process_form_submission($extracted);
        }
    }

    /**
     * Fluent Forms submission handler.
     */
    public static function handle_fluentform($insertId, $formData, $form) {
        $enabled = get_option('affichat_wp_fluentform_enabled', 'no');
        if ($enabled !== 'yes' || empty($formData)) {
            return;
        }

        $extracted = self::extract_common_fields($formData);
        if (!empty($extracted['phone']) || !empty($extracted['name'])) {
            self::process_form_submission($extracted);
        }
    }

    /**
     * Helper to intelligently map form field names to standard keys.
     */
    private static function extract_common_fields($data) {
        $phone   = '';
        $name    = '';
        $email   = '';
        $subject = '';
        $message = '';

        foreach ($data as $key => $val) {
            $val_str = is_array($val) ? implode(', ', $val) : (string)$val;
            $lower = strtolower($key);

            if (empty($phone) && (in_array($lower, ['phone', 'tel', 'telepon', 'whatsapp', 'wa', 'no_wa', 'nomor_wa', 'your-phone', 'your-tel']) || preg_match('/^(08|628|\+628)/', trim($val_str)))) {
                $phone = sanitize_text_field($val_str);
            }

            if (empty($name) && in_array($lower, ['name', 'nama', 'full_name', 'nama_lengkap', 'your-name', 'first_name'])) {
                $name = sanitize_text_field($val_str);
            }

            if (empty($email) && (in_array($lower, ['email', 'your-email', 'alamat_email']) || is_email($val_str))) {
                $email = sanitize_email($val_str);
            }

            if (empty($subject) && in_array($lower, ['subject', 'subjek', 'judul', 'your-subject', 'topic'])) {
                $subject = sanitize_text_field($val_str);
            }

            if (empty($message) && in_array($lower, ['message', 'pesan', 'keterangan', 'your-message', 'catatan', 'comments', 'comment'])) {
                $message = sanitize_textarea_field($val_str);
            }
        }

        return [
            'name'    => $name,
            'phone'   => $phone,
            'email'   => $email,
            'subject' => $subject ?: get_bloginfo('name'),
            'message' => $message ?: '-',
        ];
    }

    /**
     * AJAX endpoint for custom HTML / JS forms: admin-ajax.php?action=affichat_submit_form
     */
    public static function ajax_submit_form() {
        $extracted = self::extract_common_fields($_POST);
        if (empty($extracted['phone']) && empty($extracted['name'])) {
            wp_send_json_error(['message' => 'Nama atau nomor telepon wajib diisi.']);
            return;
        }

        $res = self::process_form_submission($extracted);
        wp_send_json_success($res);
    }

    /**
     * User registration notification.
     */
    public static function handle_user_register($user_id) {
        $admin_notify = get_option('affichat_wp_user_reg_admin_notify', 'no');
        $admin_phone  = get_option('affichat_wp_admin_phone', get_option('affichat_wc_admin_phone', ''));

        if ($admin_notify === 'yes' && !empty($admin_phone)) {
            $user = get_userdata($user_id);
            if ($user) {
                $text = sprintf(
                    "👤 *Pengguna Baru Terdaftar!*\n\nWebsite: %s\nUsername: %s\nEmail: %s\nTanggal: %s",
                    get_bloginfo('name'),
                    $user->user_login,
                    $user->user_email,
                    date_i18n('Y-m-d H:i')
                );
                $api = new AffiChat_WP_API();
                $api->send_text($admin_phone, $text);
            }
        }
    }

    /**
     * Registers public WordPress REST API routes.
     */
    public static function register_rest_routes() {
        register_rest_route('affichat/v1', '/send', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'rest_send_message'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('affichat/v1', '/form', [
            'methods'             => 'POST',
            'callback'            => [__CLASS__, 'rest_submit_form'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * REST endpoint: POST /wp-json/affichat/v1/send
     */
    public static function rest_send_message($request) {
        $phone   = $request->get_param('phone');
        $message = $request->get_param('message');

        if (empty($phone) || empty($message)) {
            return new WP_Error('invalid_param', 'Phone and message are required', ['status' => 400]);
        }

        $api = new AffiChat_WP_API();
        $result = $api->send_text($phone, $message);

        return rest_ensure_response($result);
    }

    /**
     * REST endpoint: POST /wp-json/affichat/v1/form
     */
    public static function rest_submit_form($request) {
        $data = [
            'name'    => $request->get_param('name'),
            'phone'   => $request->get_param('phone'),
            'email'   => $request->get_param('email'),
            'subject' => $request->get_param('subject'),
            'message' => $request->get_param('message'),
        ];

        $res = self::process_form_submission($data);
        return rest_ensure_response($res);
    }

    /**
     * Universal shortcode: [affichat_button phone="0812..." text="Hubungi Kami" message="..."]
     */
    public static function render_wa_button_shortcode($atts) {
        $a = shortcode_atts([
            'phone'   => get_option('affichat_wp_admin_phone', ''),
            'text'    => __('Hubungi via WhatsApp', 'affichat-wp'),
            'message' => 'Halo, saya ingin bertanya tentang layanan Anda.',
            'class'   => '',
        ], $atts);

        $clean_phone = preg_replace('/[^0-9]/', '', $a['phone']);
        if (substr($clean_phone, 0, 1) === '0') {
            $clean_phone = '62' . substr($clean_phone, 1);
        }

        $link = 'https://api.whatsapp.com/send?phone=' . urlencode($clean_phone) . '&text=' . rawurlencode($a['message']);
        return sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer" class="affichat-direct-btn %s">%s</a>',
            esc_url($link),
            esc_attr($a['class']),
            esc_html($a['text'])
        );
    }
}

/**
 * Global helper function to dispatch WhatsApp message from anywhere in WordPress.
 *
 * @param string $phone Destination phone number.
 * @param string $message Text content.
 * @return array Gateway response array.
 */
function affichat_send_whatsapp($phone, $message) {
    $api = new AffiChat_WP_API();
    return $api->send_text($phone, $message);
}
