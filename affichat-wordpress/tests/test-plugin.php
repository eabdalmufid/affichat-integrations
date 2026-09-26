<?php
/**
 * Comprehensive Automated Test Suite for AffiChat WordPress & WooCommerce Plugin.
 *
 * Run with: php integrations/affichat-wordpress/tests/test-plugin.php
 */

define('ABSPATH', true);
define('AFFICHAT_WP_VERSION', '1.0.3');
define('AFFICHAT_WP_PATH', dirname(__DIR__) . '/');
define('AFFICHAT_WP_URL', 'http://example.com/wp-content/plugins/affichat-wordpress/');
define('AFFICHAT_WP_BASENAME', 'affichat-wordpress/affichat-wordpress.php');
define('AFFICHAT_WP_API_BASE_URL', 'https://chat.affidev.com');

$GLOBALS['mock_options'] = [];
$GLOBALS['mock_http_calls'] = [];
$GLOBALS['mock_logs'] = [];
$GLOBALS['current_user_can_manage_options'] = true;
$GLOBALS['nonce_valid'] = true;

function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {}
function add_filter($hook, $callback, $priority = 10, $accepted_args = 1) {}

function get_option($key, $default = false) {
    return isset($GLOBALS['mock_options'][$key]) ? $GLOBALS['mock_options'][$key] : $default;
}

function update_option($key, $value) {
    $GLOBALS['mock_options'][$key] = $value;
    return true;
}

function site_url($path = '') {
    return 'https://toko-demo.id' . $path;
}

function get_bloginfo($show = '') {
    return 'AffiChat Official Store';
}

function __($text, $domain = 'default') {
    return $text;
}

function esc_html__($text, $domain = 'default') {
    return $text;
}

function esc_html_e($text, $domain = 'default') {
    echo htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function esc_attr_e($text, $domain = 'default') {
    echo htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function esc_textarea($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function checked($checked, $current = true, $echo = true) {
    $result = ((string)$checked === (string)$current) ? 'checked="checked"' : '';
    if ($echo) {
        echo $result;
    }
    return $result;
}

function esc_attr($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function esc_html($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

function esc_url($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

function current_time($type) {
    return date('Y-m-d H:i:s');
}

function date_i18n($format) {
    return date($format);
}

function wp_json_encode($data) {
    return json_encode($data);
}

function wp_strip_all_tags($string) {
    return strip_tags((string)$string);
}

function wp_kses_post($string) {
    return strip_tags((string)$string, '<b><strong><i><em><a><p><br><span>');
}

function admin_url($path = '') {
    return 'https://toko-demo.id/wp-admin/' . $path;
}

function wp_create_nonce($action) {
    return 'valid_nonce_hash';
}

function check_ajax_referer($action, $query_arg = false, $die = true) {
    if (!$GLOBALS['nonce_valid']) {
        if ($die) {
            echo json_encode(['success' => false, 'data' => ['message' => 'Invalid nonce']]);
            exit;
        }
        return false;
    }
    return true;
}

function current_user_can($cap) {
    if ($cap === 'manage_options' || $cap === 'manage_woocommerce') {
        return $GLOBALS['current_user_can_manage_options'];
    }
    return false;
}

function sanitize_text_field($str) {
    return trim(strip_tags((string)$str));
}

function sanitize_textarea_field($str) {
    return trim(strip_tags((string)$str));
}

function sanitize_email($email) {
    return filter_var($email, FILTER_SANITIZE_EMAIL);
}

function is_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function wp_unslash($val) {
    return $val;
}

function esc_url_raw($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}


$GLOBALS['last_ajax_response'] = null;
function wp_send_json_success($data = null) {
    $GLOBALS['last_ajax_response'] = ['success' => true, 'data' => $data];
}

function wp_send_json_error($data = null) {
    $GLOBALS['last_ajax_response'] = ['success' => false, 'data' => $data];
}

class Mock_WP_Error {
    private $message;
    public function __construct($message) { $this->message = $message; }
    public function get_error_message() { return $this->message; }
}

function is_wp_error($thing) {
    return is_a($thing, 'Mock_WP_Error');
}

function wp_remote_retrieve_response_code($response) {
    return isset($response['response']['code']) ? $response['response']['code'] : 200;
}

function wp_remote_retrieve_body($response) {
    return isset($response['body']) ? $response['body'] : '';
}

$GLOBALS['custom_remote_handler'] = null;

function wp_remote_get($url, $args = []) {
    $GLOBALS['mock_http_calls'][] = ['method' => 'GET', 'url' => $url, 'args' => $args];
    if (is_callable($GLOBALS['custom_remote_handler'])) {
        return call_user_func($GLOBALS['custom_remote_handler'], 'GET', $url, $args);
    }
    return [
        'response' => ['code' => 200],
        'body'     => json_encode(['status' => true, 'message' => 'API Key valid']),
    ];
}

function wp_remote_post($url, $args = []) {
    $GLOBALS['mock_http_calls'][] = ['method' => 'POST', 'url' => $url, 'args' => $args];
    if (is_callable($GLOBALS['custom_remote_handler'])) {
        return call_user_func($GLOBALS['custom_remote_handler'], 'POST', $url, $args);
    }
    return [
        'response' => ['code' => 200],
        'body'     => json_encode(['status' => 'success', 'id' => 'msg_abc123', 'message' => 'Pesan terkirim']),
    ];
}

function wc_price($price, $args = []) {
    return 'Rp ' . number_format((float)$price, 0, ',', '.');
}

function wc_get_order_status_name($status) {
    $map = [
        'pending'    => 'Menunggu Pembayaran',
        'processing' => 'Diproses',
        'completed'  => 'Selesai',
        'cancelled'  => 'Dibatalkan',
    ];
    return isset($map[$status]) ? $map[$status] : ucfirst($status);
}

class Mock_Logger {
    public function error($message, $context = []) {
        $GLOBALS['mock_logs'][] = ['level' => 'error', 'msg' => $message, 'ctx' => $context];
    }
}

function wc_get_logger() {
    return new Mock_Logger();
}

class Mock_WC_Item {
    private $name;
    private $qty;
    private $total;

    public function __construct($name, $qty, $total) {
        $this->name  = $name;
        $this->qty   = $qty;
        $this->total = $total;
    }
    public function get_name() { return $this->name; }
    public function get_quantity() { return $this->qty; }
    public function get_total() { return $this->total; }
}

class Mock_WC_DateTime {
    public function date_i18n($format) {
        return '24 September 2026 15:00';
    }
}

class WC_Order {
    public $id;
    public $status = 'processing';
    public $first_name = 'Ahmad';
    public $last_name = 'Fauzi';
    public $phone = '081234567890';
    public $total = 275000;
    public $currency = 'IDR';
    public $payment_method_title = 'QRIS Instant';
    public $billing_address = "Jl. Merdeka No. 45<br/>Bandung, Jawa Barat";
    public $shipping_address = "Jl. Merdeka No. 45<br/>Bandung, Jawa Barat";
    public $items = [];
    public $meta = [];

    public function __construct($id = 2001) {
        $this->id = $id;
        $this->items = [
            new Mock_WC_Item('Jaket Hoodie Premium', 1, 200000),
            new Mock_WC_Item('Stiker AffiChat Pack', 3, 75000),
        ];
    }
    public function get_id() { return $this->id; }
    public function get_order_number() { return 'ORD-' . $this->id; }
    public function get_date_created() { return new Mock_WC_DateTime(); }
    public function get_status() { return $this->status; }
    public function get_formatted_billing_full_name() { return trim($this->first_name . ' ' . $this->last_name); }
    public function get_billing_phone() { return $this->phone; }
    public function get_total() { return $this->total; }
    public function get_currency() { return $this->currency; }
    public function get_payment_method_title() { return $this->payment_method_title; }
    public function get_formatted_billing_address() { return $this->billing_address; }
    public function get_formatted_shipping_address() { return $this->shipping_address; }
    public function get_view_order_url() { return 'https://toko-demo.id/my-account/view-order/' . $this->id; }
    public function get_items() { return $this->items; }
    public function get_meta($key) { return isset($this->meta[$key]) ? $this->meta[$key] : ''; }
    public function update_meta_data($key, $val) { $this->meta[$key] = $val; }
    public function save() { return true; }
}

$GLOBALS['mock_orders_registry'] = [];
function wc_get_order($id) {
    return isset($GLOBALS['mock_orders_registry'][$id]) ? $GLOBALS['mock_orders_registry'][$id] : null;
}

// Load plugin components
require_once AFFICHAT_WP_PATH . 'includes/class-affichat-api.php';
require_once AFFICHAT_WP_PATH . 'includes/class-affichat-tags.php';
require_once AFFICHAT_WP_PATH . 'includes/class-affichat-forms.php';
require_once AFFICHAT_WP_PATH . 'includes/class-affichat-woocommerce.php';
require_once AFFICHAT_WP_PATH . 'includes/class-affichat-admin.php';

// Assert helpers
$total_asserts = 0;
$passed_asserts = 0;
$failed_asserts = 0;

function it($title, $condition) {
    global $total_asserts, $passed_asserts, $failed_asserts;
    $total_asserts++;
    if ($condition) {
        $passed_asserts++;
        echo "  [PASS] {$title}\n";
    } else {
        $failed_asserts++;
        echo "  [FAIL] {$title}\n";
    }
}

echo "AffiChat for WordPress & WooCommerce - Test Suite\n\n";

// Phone number normalization
it('Converts standard Indonesian format 08123456789 -> 628123456789', AffiChat_WP_API::normalize_phone('08123456789') === '628123456789');
it('Converts international leading plus +628123456789 -> 628123456789', AffiChat_WP_API::normalize_phone('+628123456789') === '628123456789');
it('Preserves already normalized 628123456789', AffiChat_WP_API::normalize_phone('628123456789') === '628123456789');
it('Handles double zero prefix 00628123456789 -> 628123456789', AffiChat_WP_API::normalize_phone('00628123456789') === '628123456789');
it('Strips spaces: 0812 3456 7890 -> 6281234567890', AffiChat_WP_API::normalize_phone('0812 3456 7890') === '6281234567890');
it('Strips hyphens: 0812-3456-7890 -> 6281234567890', AffiChat_WP_API::normalize_phone('0812-3456-7890') === '6281234567890');
it('Strips parentheses: (0812) 3456789 -> 628123456789', AffiChat_WP_API::normalize_phone('(0812) 3456789') === '628123456789');
it('Handles international country code Malaysia: +60123456789 -> 60123456789', AffiChat_WP_API::normalize_phone('+60123456789') === '60123456789');
it('Handles international country code US: +1 (555) 234-5678 -> 15552345678', AffiChat_WP_API::normalize_phone('+1 (555) 234-5678') === '15552345678');
it('Rejects empty phone string', AffiChat_WP_API::normalize_phone('') === false);
it('Rejects null phone', AffiChat_WP_API::normalize_phone(null) === false);
it('Rejects alphabetic phone "abcdefghijk"', AffiChat_WP_API::normalize_phone('abcdefghijk') === false);
it('Rejects short number under 9 digits', AffiChat_WP_API::normalize_phone('0812345') === false);
it('Rejects excessively long string (>16 digits)', AffiChat_WP_API::normalize_phone('08123456789012345678') === false);

// Dynamic template tags
$order = new WC_Order(2001);
it('Replaces {site_name} with bloginfo name', strpos(AffiChat_WP_Tags::replace('Selamat datang di {site_name}'), 'AffiChat Official Store') !== false);
it('Replaces {order_id} with 2001', AffiChat_WP_Tags::replace('{order_id}', $order) === '2001');
it('Replaces {order_number} with ORD-2001', AffiChat_WP_Tags::replace('{order_number}', $order) === 'ORD-2001');
it('Replaces {customer_name} with Ahmad Fauzi', AffiChat_WP_Tags::replace('{customer_name}', $order) === 'Ahmad Fauzi');
it('Replaces {customer_phone} with 081234567890', AffiChat_WP_Tags::replace('{customer_phone}', $order) === '081234567890');
it('Replaces {order_total} with formatted price', strpos(AffiChat_WP_Tags::replace('{order_total}', $order), '275.000') !== false);
it('Replaces {payment_method} with QRIS Instant', AffiChat_WP_Tags::replace('{payment_method}', $order) === 'QRIS Instant');
it('Replaces custom extra tags {rsvp_status}', AffiChat_WP_Tags::replace('Status: {rsvp_status}', null, ['{rsvp_status}' => 'Hadir']) === 'Status: Hadir');

// API client credentials and backward compatibility
$GLOBALS['mock_options'] = [
    'affichat_wc_api_key'    => 'legacy_wc_key_123',
    'affichat_wc_session_id' => 'legacy_session_abc',
];
$api_legacy = new AffiChat_WP_API();
it('Pulls legacy WC API key when WP key not set', (function() use ($api_legacy) {
    $ref = new ReflectionProperty($api_legacy, 'api_key');
    $ref->setAccessible(true);
    return $ref->getValue($api_legacy) === 'legacy_wc_key_123';
})());
it('Pulls legacy WC session ID when WP session not set', (function() use ($api_legacy) {
    $ref = new ReflectionProperty($api_legacy, 'session_id');
    $ref->setAccessible(true);
    return $ref->getValue($api_legacy) === 'legacy_session_abc';
})());

$api_custom = new AffiChat_WP_API('custom_key', 'custom_session', 'https://chat.affidev.com');
it('check_connection succeeds on valid response', $api_custom->check_connection()['success'] === true);
it('send_text succeeds and calls /api/send-text', (function() use ($api_custom) {
    $res = $api_custom->send_text('081234567890', 'Halo dari AffiChat');
    return $res['success'] === true;
})());

// WooCommerce settings page interface contract
$wc_settings = new AffiChat_WP_WC_Settings();
it('WC Settings class implements get_id() returning "affichat_whatsapp"', method_exists($wc_settings, 'get_id') && $wc_settings->get_id() === 'affichat_whatsapp');
it('WC Settings class implements get_label() returning non-empty label', method_exists($wc_settings, 'get_label') && !empty($wc_settings->get_label()));
it('WC Settings fields list is non-empty array', is_array($wc_settings->get_settings()) && count($wc_settings->get_settings()) > 5);

ob_start();
$wc_settings->output();
$wc_output = ob_get_clean();
it('WC Settings output renders unified affichat-wrap container', strpos($wc_output, 'affichat-wrap') !== false);
it('WC Settings output renders modern card headers', strpos($wc_output, 'affichat-card-header') !== false);
it('WC Settings output renders tag chips helper', strpos($wc_output, 'affichat-tag-chip') !== false);

$_POST = [
    'affichat_wc_admin_enabled'          => 'yes',
    'affichat_wc_admin_phone'            => '081233334444',
    'affichat_wc_cust_pending_enabled'   => 'yes',
    'affichat_wc_cust_pending_template'  => 'Pesan khusus pending',
];
$wc_settings->save();
it('WC Settings save updates affichat_wc_admin_phone', get_option('affichat_wc_admin_phone') === '081233334444');
it('WC Settings save updates affichat_wc_cust_pending_template', get_option('affichat_wc_cust_pending_template') === 'Pesan khusus pending');


// Quick send endpoint and permission check
$_POST = [
    'phone'   => '081299991111',
    'message' => 'Halo {site_name}, ini uji coba kirim cepat.',
];
$GLOBALS['last_ajax_response'] = null;
AffiChat_WP_Admin::ajax_quick_send();
it('Quick send AJAX succeeds with valid phone & message', $GLOBALS['last_ajax_response'] !== null && $GLOBALS['last_ajax_response']['success'] === true);

$GLOBALS['current_user_can_manage_options'] = false;
$GLOBALS['last_ajax_response'] = null;
AffiChat_WP_Admin::ajax_quick_send();
it('Quick send blocks unauthorized users without manage_options', $GLOBALS['last_ajax_response'] !== null && $GLOBALS['last_ajax_response']['success'] === false);
$GLOBALS['current_user_can_manage_options'] = true;

// Developer helper functions
$dev_res = affichat_send_whatsapp('081288887777', 'Pesan via fungsi global affichat_send_whatsapp');
it('affichat_send_whatsapp() helper returns success', $dev_res['success'] === true);

$hook_res = AffiChat_WP_Forms::handle_custom_action('081288887777', 'Pesan via hook');
it('AffiChat_WP_Forms::handle_custom_action returns success', $hook_res['success'] === true);

// WooCommerce order notifications and idempotency guards
$GLOBALS['mock_options'] = [
    'affichat_wp_api_key'                => 'test_key',
    'affichat_wp_session_id'             => 'default',
    'affichat_wc_admin_enabled'          => 'yes',
    'affichat_wc_admin_phone'            => '081299998888',
    'affichat_wc_admin_template'         => 'Order #{order_number} created',
    'affichat_wc_cust_pending_enabled'   => 'yes',
    'affichat_wc_cust_pending_template'  => 'Pending #{order_number}',
    'affichat_wc_cust_processing_enabled'=> 'yes',
    'affichat_wc_cust_processing_template'=>'Processing #{order_number}',
    'affichat_wc_cust_completed_enabled' => 'yes',
    'affichat_wc_cust_completed_template'=> 'Completed #{order_number}',
    'affichat_wc_cust_cancelled_enabled' => 'yes',
    'affichat_wc_cust_cancelled_template'=> 'Cancelled #{order_number}',
];

$order4001 = new WC_Order(4001);
$GLOBALS['mock_orders_registry'][4001] = $order4001;

$GLOBALS['mock_http_calls'] = [];

// Trigger Admin new order
AffiChat_WP_WooCommerce::trigger_admin_new_order(4001);
it('Admin receives order checkout WhatsApp alert', count($GLOBALS['mock_http_calls']) === 1);
it('Duplicate admin alert prevented by idempotency', (function() {
    AffiChat_WP_WooCommerce::trigger_admin_new_order(4001);
    return count($GLOBALS['mock_http_calls']) === 1;
})());

// Customer Pending
AffiChat_WP_WooCommerce::trigger_customer_pending(4001);
it('Customer receives pending notification', count($GLOBALS['mock_http_calls']) === 2);
it('Duplicate pending notification prevented', (function() {
    AffiChat_WP_WooCommerce::trigger_customer_pending(4001);
    return count($GLOBALS['mock_http_calls']) === 2;
})());

// Customer Processing
AffiChat_WP_WooCommerce::trigger_customer_processing(4001);
it('Customer receives processing notification', count($GLOBALS['mock_http_calls']) === 3);

// Customer Completed
AffiChat_WP_WooCommerce::trigger_customer_completed(4001);
it('Customer receives completed notification', count($GLOBALS['mock_http_calls']) === 4);

// Universal website form tests
$form_tags = AffiChat_WP_Tags::get_form_tags();
it('AffiChat_WP_Tags::get_form_tags returns array with {name}', isset($form_tags['{name}']));
it('AffiChat_WP_Tags::get_form_tags returns array with {phone}', isset($form_tags['{phone}']));
it('AffiChat_WP_Tags::get_form_tags returns array with {message}', isset($form_tags['{message}']));

$GLOBALS['mock_options']['affichat_wp_form_user_reply_enabled'] = 'yes';
$GLOBALS['mock_options']['affichat_wp_form_admin_notify_enabled'] = 'yes';
$GLOBALS['mock_options']['affichat_wp_admin_phone'] = '081299998888';

$call_count_before = count($GLOBALS['mock_http_calls']);
$form_result = AffiChat_WP_Forms::process_form_submission([
    'name'    => 'Ahmad Fauzi',
    'phone'   => '081234567890',
    'email'   => 'fauzi@example.com',
    'subject' => 'Kontak Layanan',
    'message' => 'Halo, saya ingin bertanya tentang produk ini.',
]);

it('process_form_submission returns success', $form_result['success'] === true);
it('process_form_submission sends messages to both user and admin', count($GLOBALS['mock_http_calls']) === $call_count_before + 2);

$last_user_call = $GLOBALS['mock_http_calls'][$call_count_before];
$user_body = isset($last_user_call['args']['body']) ? $last_user_call['args']['body'] : '';
it('User receives confirmation with replaced tags', strpos($user_body, 'Ahmad Fauzi') !== false);

// Test default opt-in behavior: when not enabled, no messages are dispatched
unset($GLOBALS['mock_options']['affichat_wp_form_user_reply_enabled']);
unset($GLOBALS['mock_options']['affichat_wp_form_admin_notify_enabled']);
$call_count_disabled = count($GLOBALS['mock_http_calls']);
AffiChat_WP_Forms::process_form_submission([
    'name'    => 'Budi Santoso',
    'phone'   => '081211112222',
    'message' => 'Tes pengiriman saat opsi belum diaktifkan',
]);
it('Default state is opt-in (disabled by default when options unset)', count($GLOBALS['mock_http_calls']) === $call_count_disabled);

// Updater tests
require_once __DIR__ . '/../includes/class-affichat-updater.php';
$updater = new AffiChat_WP_Updater();
it('AffiChat_WP_Updater class exists and instantiates', is_object($updater));

$mock_info_args = (object) ['slug' => 'affichat-wordpress'];
$info_res = $updater->plugin_info(false, 'other_action', $mock_info_args);
it('Updater plugin_info ignores other actions', $info_res === false);

$wrong_slug_args = (object) ['slug' => 'other-plugin'];
$wrong_slug_res = $updater->plugin_info(false, 'plugin_information', $wrong_slug_args);
it('Updater plugin_info ignores other slugs', $wrong_slug_res === false);

$uncheck_transient = (object) ['checked' => []];
$uncheck_res = $updater->check_update($uncheck_transient);
it('Updater check_update ignores empty checked transient', empty($uncheck_res->response));

echo "\n=================================================================\n";
echo "SUMMARY: Total Asserts: {$total_asserts} | Passed: {$passed_asserts} | Failed: {$failed_asserts}\n";
echo "=================================================================\n";

exit($failed_asserts === 0 ? 0 : 1);


