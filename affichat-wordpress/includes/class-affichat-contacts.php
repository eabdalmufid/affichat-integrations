<?php
/**
 * AffiChat Contact Sync Module.
 *
 * Syncs WooCommerce customers to the AffiChat address book and provides
 * AJAX handlers for the WP-Admin broadcast quick-send panel.
 *
 * @package AffiChat_WP
 */

if (!defined("ABSPATH")) {
    exit;
}

class AffiChat_WP_Contacts {

    public static function init() {
        add_action("wp_ajax_affichat_sync_contacts",     [__CLASS__, "ajax_sync_contacts"]);
        add_action("wp_ajax_affichat_quick_broadcast",   [__CLASS__, "ajax_quick_broadcast"]);
        add_action("wp_ajax_affichat_send_location",     [__CLASS__, "ajax_send_location"]);
        add_action("wp_ajax_affichat_send_satisfaction_poll", [__CLASS__, "ajax_send_satisfaction_poll"]);

        // Auto-sync new customer to AffiChat contacts on WC order creation.
        add_action("woocommerce_checkout_order_processed", [__CLASS__, "auto_sync_order_customer"], 20, 1);

        // Schedule satisfaction poll 2 days after order completion.
        add_action("woocommerce_order_status_completed", [__CLASS__, "schedule_satisfaction_poll"], 20, 1);
        add_action("affichat_send_satisfaction_poll",    [__CLASS__, "dispatch_satisfaction_poll"], 10, 2);
    }

    /**
     * AJAX: Syncs all WooCommerce customers (with billing phone) to AffiChat contacts.
     */
    public static function ajax_sync_contacts() {
        if (!check_ajax_referer("affichat_wp_nonce", "nonce", false) && !check_ajax_referer("affichat_nonce", "nonce", false)) {
            wp_send_json_error(["message" => __("Sesi keamanan kedaluwarsa. Silakan refresh halaman.", "affichat-wp")]);
        }
        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => __("Akses ditolak.", "affichat-wp")]);
        }

        if (!class_exists("WooCommerce")) {
            wp_send_json_error(["message" => __("WooCommerce tidak aktif.", "affichat-wp")]);
        }

        $customers = get_users(["role" => "customer", "number" => -1]);
        $api       = new AffiChat_WP_API();
        $synced    = 0;
        $failed    = 0;

        foreach ($customers as $customer) {
            $phone = get_user_meta($customer->ID, "billing_phone", true);
            if (empty($phone)) {
                continue;
            }
            $name   = trim($customer->first_name . " " . $customer->last_name) ?: $customer->display_name;
            $result = $api->sync_contact($phone, $name, ["woocommerce-customer"]);
            if (!empty($result["success"])) {
                $synced++;
            } else {
                $failed++;
            }
        }

        wp_send_json_success([
            "message" => sprintf(__("%d kontak berhasil disinkronkan, %d gagal.", "affichat-wp"), $synced, $failed),
            "synced"  => $synced,
            "failed"  => $failed,
        ]);
    }

    /**
     * Auto-syncs the customer of a newly placed WooCommerce order.
     *
     * @param int $order_id WooCommerce order ID.
     */
    public static function auto_sync_order_customer($order_id) {
        if (get_option("affichat_contacts_auto_sync", "yes") !== "yes") {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $phone = $order->get_billing_phone();
        if (empty($phone)) {
            return;
        }

        $first = $order->get_billing_first_name();
        $last  = $order->get_billing_last_name();
        $name  = trim($first . " " . $last) ?: $order->get_formatted_billing_full_name();

        try {
            $api = new AffiChat_WP_API();
            $api->sync_contact($phone, $name, ["woocommerce-customer"]);
        } catch (\Throwable $e) {
            // Swallow - contact sync is non-critical.
        }
    }

    /**
     * Schedules a satisfaction poll 2 days after an order completes.
     *
     * @param int $order_id WooCommerce order ID.
     */
    public static function schedule_satisfaction_poll($order_id) {
        if (get_option("affichat_wc_poll_enabled", "no") !== "yes") {
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order || $order->get_meta("_affichat_poll_scheduled")) {
            return;
        }

        $delay = (int) get_option("affichat_wc_poll_delay_days", 2);
        $delay = max(0, min(30, $delay));

        wp_schedule_single_event(time() + ($delay * DAY_IN_SECONDS), "affichat_send_satisfaction_poll", [$order_id, $order->get_billing_phone()]);

        $order->update_meta_data("_affichat_poll_scheduled", current_time("mysql"));
        $order->save();
    }

    /**
     * Dispatches the satisfaction poll WhatsApp message via the scheduled hook.
     *
     * @param int    $order_id WooCommerce order ID.
     * @param string $phone    Customer billing phone.
     */
    public static function dispatch_satisfaction_poll($order_id, $phone) {
        if (empty($phone)) {
            return ["success" => false, "message" => "Nomor telepon kosong"];
        }

        $order = ($order_id && function_exists('wc_get_order')) ? wc_get_order($order_id) : null;
        if ($order && method_exists($order, 'get_meta') && $order->get_meta("_affichat_poll_sent")) {
            return ["success" => true, "message" => "Polling sudah dikirim sebelumnya"];
        }

        $question = get_option("affichat_wc_poll_question", __("Bagaimana pengalaman belanja kamu di {store_name}?", "affichat-wp"));
        $question = str_replace("{store_name}", get_bloginfo("name"), $question);

        $default_options = ["⭐⭐⭐⭐⭐ Sangat Puas", "⭐⭐⭐ Cukup Puas", "👎 Perlu Ditingkatkan"];
        $raw_options     = get_option("affichat_wc_poll_options", implode("\n", $default_options));
        $options         = array_filter(array_map("trim", explode("\n", $raw_options)));

        if (count($options) < 2) {
            $options = $default_options;
        }

        try {
            $api    = new AffiChat_WP_API();
            $result = $api->send_poll($phone, $question, array_values($options));
            if (!empty($result["success"]) && $order && method_exists($order, 'update_meta_data')) {
                $order->update_meta_data("_affichat_poll_sent", current_time("mysql"));
                $order->save();
            }
            return $result;
        } catch (\Throwable $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    /**
     * AJAX: Sends a location pin to a specified WhatsApp number (from WP-Admin).
     */
    public static function ajax_send_location() {
        if (!check_ajax_referer("affichat_wp_nonce", "nonce", false) && !check_ajax_referer("affichat_nonce", "nonce", false)) {
            wp_send_json_error(["message" => __("Sesi keamanan kedaluwarsa. Silakan refresh halaman.", "affichat-wp")]);
        }
        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => __("Akses ditolak.", "affichat-wp")]);
        }

        $to      = sanitize_text_field($_POST["to"] ?? "");
        $lat     = (float) ($_POST["latitude"] ?? 0);
        $lng     = (float) ($_POST["longitude"] ?? 0);
        $name    = sanitize_text_field($_POST["name"] ?? "");
        $address = sanitize_textarea_field($_POST["address"] ?? "");

        if (empty($to) || ($lat == 0 && $lng == 0)) {
            wp_send_json_error(["message" => __("Nomor tujuan dan koordinat wajib diisi.", "affichat-wp")]);
        }

        $api    = new AffiChat_WP_API();
        $result = $api->send_location($to, $lat, $lng, $name, $address);

        if (!empty($result["success"])) {
            wp_send_json_success(["message" => __("Lokasi berhasil dikirim.", "affichat-wp")]);
        } else {
            wp_send_json_error(["message" => $result["message"] ?? __("Gagal mengirim lokasi.", "affichat-wp")]);
        }
    }

    /**
     * AJAX: Sends a satisfaction poll directly from the admin panel (manual trigger / test).
     */
    public static function ajax_send_satisfaction_poll() {
        if (!check_ajax_referer("affichat_wp_nonce", "nonce", false) && !check_ajax_referer("affichat_nonce", "nonce", false)) {
            wp_send_json_error(["message" => __("Sesi keamanan kedaluwarsa. Silakan refresh halaman.", "affichat-wp")]);
        }
        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => __("Akses ditolak.", "affichat-wp")]);
        }

        $order_id = (int) ($_POST["order_id"] ?? 0);
        $phone    = sanitize_text_field($_POST["phone"] ?? "");

        if ($order_id && function_exists('wc_get_order')) {
            $order = wc_get_order($order_id);
            if ($order && method_exists($order, 'get_billing_phone')) {
                $phone = $order->get_billing_phone();
            }
        }

        if (empty($phone)) {
            wp_send_json_error(["message" => __("Nomor tujuan WhatsApp wajib diisi.", "affichat-wp")]);
        }

        $result = self::dispatch_satisfaction_poll($order_id, $phone);
        if (!empty($result["success"])) {
            wp_send_json_success(["message" => __("Polling kepuasan berhasil dikirim.", "affichat-wp")]);
        } else {
            wp_send_json_error(["message" => $result["message"] ?? __("Gagal mengirim polling.", "affichat-wp")]);
        }
    }

    /**
     * AJAX: Quick broadcast to a set of phone numbers from WP-Admin.
     */
    public static function ajax_quick_broadcast() {
        if (!check_ajax_referer("affichat_wp_nonce", "nonce", false) && !check_ajax_referer("affichat_nonce", "nonce", false)) {
            wp_send_json_error(["message" => __("Sesi keamanan kedaluwarsa. Silakan refresh halaman.", "affichat-wp")]);
        }
        if (!current_user_can("manage_options")) {
            wp_send_json_error(["message" => __("Akses ditolak.", "affichat-wp")]);
        }

        $numbers_raw = sanitize_textarea_field($_POST["numbers"] ?? "");
        $message     = sanitize_textarea_field($_POST["message"] ?? "");
        $image_url   = esc_url_raw($_POST["image_url"] ?? "");

        if (empty($numbers_raw) || empty($message)) {
            wp_send_json_error(["message" => __("Nomor tujuan dan pesan wajib diisi.", "affichat-wp")]);
        }

        $lines   = array_filter(array_map("trim", preg_split("/[\r\n,]+/", $numbers_raw)));
        $api     = new AffiChat_WP_API();
        $sent    = 0;
        $failed  = 0;
        $store   = get_bloginfo("name");

        foreach ($lines as $phone) {
            $text = str_replace("{store_name}", $store, $message);

            if (!empty($image_url)) {
                $result = $api->send_image($phone, $image_url, $text);
            } else {
                $result = $api->send_text($phone, $text);
            }

            if (!empty($result["success"])) {
                $sent++;
            } else {
                $failed++;
            }
        }

        wp_send_json_success([
            "message" => sprintf(__("%d pesan terkirim, %d gagal.", "affichat-wp"), $sent, $failed),
            "sent"    => $sent,
            "failed"  => $failed,
        ]);
    }
}
