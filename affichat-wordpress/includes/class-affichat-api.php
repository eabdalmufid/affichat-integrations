<?php
/**
 * AffiChat API Client for WordPress.
 *
 * Handles HTTP communication with AffiChat WhatsApp Gateway.
 *
 * @package AffiChat_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiChat_WP_API {
    /**
     * Base URL for the gateway API.
     * @var string
     */
    private $base_url;

    /**
     * Gateway API Key.
     * @var string
     */
    private $api_key;

    /**
     * Active WhatsApp Session ID.
     * @var string
     */
    private $session_id;

    /**
     * HTTP request timeout in seconds.
     * @var int
     */
    private $timeout;

    /**
     * Constructor.
     *
     * @param string|null $api_key Optional API key override.
     * @param string|null $session_id Optional session ID override.
     * @param string|null $base_url Optional base URL override.
     */
    public function __construct($api_key = null, $session_id = null, $base_url = null) {
        $default_base = defined('AFFICHAT_WP_API_BASE_URL') ? AFFICHAT_WP_API_BASE_URL : 'https://chat.affidev.com';
        
        $saved_base = get_option('affichat_wp_base_url', '');
        if (empty($saved_base)) {
            $saved_base = get_option('affichat_wc_base_url', $default_base);
        }

        $this->base_url = rtrim($base_url ?: ($saved_base ?: $default_base), '/');

        $saved_key = get_option('affichat_wp_api_key', '');
        if (empty($saved_key)) {
            $saved_key = get_option('affichat_wc_api_key', '');
        }
        $this->api_key = $api_key ?: $saved_key;

        $saved_session = get_option('affichat_wp_session_id', '');
        if (empty($saved_session)) {
            $saved_session = get_option('affichat_wc_session_id', 'default');
        }
        $this->session_id = $session_id ?: $saved_session;

        $this->timeout = 15;
    }

    /**
     * Normalizes local and international phone formats into E.164 digits without plus.
     *
     * @param string|int $phone Input phone number.
     * @return string|false Normalized phone or false if invalid.
     */
    public static function normalize_phone($phone) {
        if (empty($phone)) {
            return false;
        }

        $clean = preg_replace('/[^0-9]/', '', (string)$phone);

        if (strpos($clean, '00') === 0) {
            $clean = substr($clean, 2);
        }
        if (strpos($clean, '0') === 0) {
            $clean = '62' . substr($clean, 1);
        }

        if (strlen($clean) < 9 || strlen($clean) > 16) {
            return false;
        }

        return $clean;
    }

    /**
     * Validates connection to AffiChat WhatsApp Gateway.
     *
     * @return array Result array with success status and message.
     */
    public function check_connection() {
        if (empty($this->base_url) || empty($this->api_key)) {
            return [
                'success' => false,
                'message' => __('Base URL dan API Key belum dikonfigurasi.', 'affichat-wp'),
            ];
        }

        $url = $this->base_url . '/api/key/check';
        $response = wp_remote_get($url, [
            'timeout' => $this->timeout,
            'headers' => [
                'x-api-key' => $this->api_key,
                'Accept'    => 'application/json',
            ],
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 200 && $code < 300) {
            return [
                'success' => true,
                'message' => isset($body['message']) ? $body['message'] : __('Koneksi ke AffiChat Gateway berhasil.', 'affichat-wp'),
                'data'    => $body,
            ];
        }

        $err_msg = isset($body['message']) ? $body['message'] : sprintf(__('Server merespons kode HTTP %d', 'affichat-wp'), $code);
        return [
            'success' => false,
            'message' => $err_msg,
        ];
    }

    /**
     * Sends a text message through the gateway.
     *
     * @param string $to Recipient phone number.
     * @param string $message Plain or formatted text message.
     * @return array API response structure.
     */
    public function send_text($to, $message) {
        $normalized_to = self::normalize_phone($to);
        if (!$normalized_to) {
            return [
                'success' => false,
                'message' => __('Nomor telepon tidak valid.', 'affichat-wp'),
            ];
        }

        if (empty(trim($message))) {
            return [
                'success' => false,
                'message' => __('Pesan tidak boleh kosong.', 'affichat-wp'),
            ];
        }

        $url = $this->base_url . '/api/send-text';
        $payload = [
            'sessionId' => $this->session_id,
            'to'        => $normalized_to,
            'text'      => $message,
        ];

        $response = wp_remote_post($url, [
            'timeout' => $this->timeout,
            'headers' => [
                'x-api-key'    => $this->api_key,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'body'    => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            $this->log_error('HTTP request failed: ' . $response->get_error_message());
            return [
                'success' => false,
                'message' => $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 200 && $code < 300) {
            return [
                'success' => true,
                'data'    => $body,
                'message' => isset($body['message']) ? $body['message'] : __('Pesan berhasil dikirim.', 'affichat-wp'),
            ];
        }

        $err_msg = isset($body['message']) ? $body['message'] : sprintf('HTTP error %d', $code);
        $this->log_error("API returned error ({$code}): {$err_msg}");

        return [
            'success' => false,
            'message' => $err_msg,
        ];
    }

    /**
     * Writes diagnostics to WooCommerce logger if available, or error_log.
     *
     * @param string $message Diagnostic message.
     */
    private function log_error($message) {
        if (function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->error($message, ['source' => 'affichat-wp']);
        } else {
            error_log('[AffiChat-WP] ' . $message);
        }
    }
}
