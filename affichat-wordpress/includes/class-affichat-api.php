<?php
/**
 * AffiChat API Client for WordPress.
 *
 * Handles HTTP communication with AffiChat WhatsApp Gateway.
 *
 * @package AffiChat_WP
 */

if (!defined("ABSPATH")) {
    exit;
}

class AffiChat_WP_API {
    private $base_url;
    private $api_key;
    private $session_id;
    private $timeout;

    public function __construct($api_key = null, $session_id = null, $base_url = null) {
        $default_base     = defined("AFFICHAT_WP_API_BASE_URL") ? AFFICHAT_WP_API_BASE_URL : "https://chat.affidev.com";
        $saved_base       = get_option("affichat_wp_base_url", "") ?: get_option("affichat_wc_base_url", $default_base);
        $this->base_url   = rtrim($base_url ?: ($saved_base ?: $default_base), "/");
        $saved_key        = get_option("affichat_wp_api_key", "") ?: get_option("affichat_wc_api_key", "");
        $this->api_key    = $api_key ?: $saved_key;
        $saved_session    = get_option("affichat_wp_session_id", "") ?: get_option("affichat_wc_session_id", "default");
        $this->session_id = $session_id ?: $saved_session;
        $this->timeout    = 15;
    }

    public static function normalize_phone($phone) {
        if (empty($phone)) return false;
        $clean = preg_replace("/[^0-9]/", "", (string) $phone);
        if (strpos($clean, "00") === 0) $clean = substr($clean, 2);
        if (strpos($clean, "0") === 0) $clean = "62" . substr($clean, 1);
        if (strlen($clean) < 9 || strlen($clean) > 16) return false;
        return $clean;
    }

    public function check_connection() {
        if (empty($this->base_url) || empty($this->api_key)) {
            return ["success" => false, "message" => __("Base URL dan API Key belum dikonfigurasi.", "affichat-wp")];
        }
        $response = wp_remote_get($this->base_url . "/api/key/check", [
            "timeout" => $this->timeout,
            "headers" => ["x-api-key" => $this->api_key, "Accept" => "application/json"],
        ]);
        if (is_wp_error($response)) return ["success" => false, "message" => $response->get_error_message()];
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($code >= 200 && $code < 300) {
            return ["success" => true, "message" => isset($body["message"]) ? $body["message"] : __("Koneksi ke AffiChat Gateway berhasil.", "affichat-wp"), "data" => $body];
        }
        return ["success" => false, "message" => isset($body["message"]) ? $body["message"] : sprintf(__("Server merespons kode HTTP %d", "affichat-wp"), $code)];
    }

    public function send_text($to, $message) {
        $normalized = self::normalize_phone($to);
        if (!$normalized) return ["success" => false, "message" => __("Nomor telepon tidak valid.", "affichat-wp")];
        if (empty(trim($message))) return ["success" => false, "message" => __("Pesan tidak boleh kosong.", "affichat-wp")];
        return $this->post("/api/send-text", ["sessionId" => $this->session_id, "to" => $normalized, "text" => $message]);
    }

    public function send_image($to, $image_url, $caption = "") {
        $normalized = self::normalize_phone($to);
        if (!$normalized) return ["success" => false, "message" => __("Nomor telepon tidak valid.", "affichat-wp")];
        if (empty($image_url)) return ["success" => false, "message" => __("URL gambar tidak boleh kosong.", "affichat-wp")];
        return $this->post("/api/send-image", ["sessionId" => $this->session_id, "to" => $normalized, "imageUrl" => $image_url, "caption" => (string) $caption]);
    }

    public function send_document($to, $document_url, $filename = "document.pdf", $caption = "") {
        $normalized = self::normalize_phone($to);
        if (!$normalized) return ["success" => false, "message" => __("Nomor telepon tidak valid.", "affichat-wp")];
        if (empty($document_url)) return ["success" => false, "message" => __("URL dokumen tidak boleh kosong.", "affichat-wp")];
        return $this->post("/api/send-document", ["sessionId" => $this->session_id, "to" => $normalized, "documentUrl" => $document_url, "filename" => $filename ?: "document.pdf", "caption" => (string) $caption]);
    }

    public function send_location($to, $latitude, $longitude, $name = "", $address = "") {
        $normalized = self::normalize_phone($to);
        if (!$normalized) return ["success" => false, "message" => __("Nomor telepon tidak valid.", "affichat-wp")];
        return $this->post("/api/send-location", ["sessionId" => $this->session_id, "to" => $normalized, "latitude" => (float) $latitude, "longitude" => (float) $longitude, "title" => (string) $name, "address" => (string) $address]);
    }

    public function send_poll($to, $question, array $options) {
        $normalized = self::normalize_phone($to);
        if (!$normalized) return ["success" => false, "message" => __("Nomor telepon tidak valid.", "affichat-wp")];
        if (count($options) < 2) return ["success" => false, "message" => __("Poll membutuhkan minimal 2 pilihan jawaban.", "affichat-wp")];
        return $this->post("/api/send-poll", ["sessionId" => $this->session_id, "to" => $normalized, "question" => $question, "options" => array_values($options), "multipleAnswers" => false]);
    }

    public function sync_contact($phone, $name, array $tags = []) {
        $normalized = self::normalize_phone($phone);
        if (!$normalized) return ["success" => false, "message" => "Invalid phone: " . $phone];
        return $this->post("/api/contacts", ["phoneNumber" => $normalized, "name" => $name, "tags" => $tags]);
    }

    private function post($endpoint, array $payload) {
        if (empty($this->base_url) || empty($this->api_key)) {
            return ["success" => false, "message" => __("API Key atau Base URL belum dikonfigurasi.", "affichat-wp")];
        }
        $response = wp_remote_post($this->base_url . $endpoint, [
            "timeout" => $this->timeout,
            "headers" => ["x-api-key" => $this->api_key, "Content-Type" => "application/json", "Accept" => "application/json"],
            "body"    => wp_json_encode($payload),
        ]);
        if (is_wp_error($response)) {
            $this->log_error("HTTP error on {$endpoint}: " . $response->get_error_message());
            return ["success" => false, "message" => $response->get_error_message()];
        }
        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($code >= 200 && $code < 300) {
            return ["success" => true, "data" => $body, "message" => isset($body["message"]) ? $body["message"] : __("Berhasil.", "affichat-wp")];
        }
        $err = isset($body["message"]) ? $body["message"] : sprintf("HTTP error %d", $code);
        $this->log_error("API error {$code} on {$endpoint}: {$err}");
        return ["success" => false, "message" => $err];
    }

    private function log_error($message) {
        if (function_exists("wc_get_logger")) {
            wc_get_logger()->error($message, ["source" => "affichat-wp"]);
        } else {
            error_log("[AffiChat-WP] " . $message);
        }
    }
}
