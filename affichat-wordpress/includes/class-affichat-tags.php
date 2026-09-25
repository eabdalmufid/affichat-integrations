<?php
/**
 * AffiChat Template Tags Engine for WordPress.
 *
 * @package AffiChat_WP
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiChat_WP_Tags {
    /**
     * Replace dynamic tags in template with order, form, or contextual data.
     *
     * @param string $template Text template containing {tag} placeholders.
     * @param object|null $order Optional WC_Order object.
     * @param array $extra_tags Optional custom tags array ['{custom}' => 'value'].
     * @return string Parsed text.
     */
    public static function replace($template, $order = null, $extra_tags = []) {
        if (empty($template)) {
            return '';
        }

        $tags = [
            '{site_name}'   => get_bloginfo('name'),
            '{site_url}'    => site_url(),
            '{date}'        => date_i18n(get_option('date_format', 'Y-m-d')),
            '{time}'        => date_i18n(get_option('time_format', 'H:i')),
            '{admin_email}' => get_option('admin_email'),
        ];

        if ($order && is_object($order) && method_exists($order, 'get_id')) {
            $items_list = self::format_items_list($order);

            $order_date = '';
            if (method_exists($order, 'get_date_created') && $order->get_date_created()) {
                $format = get_option('date_format') . ' ' . get_option('time_format');
                $order_date = $order->get_date_created()->date_i18n($format);
            }

            $order_total = '';
            if (function_exists('wc_price')) {
                $order_total = html_entity_decode(wp_strip_all_tags(wc_price($order->get_total(), ['currency' => $order->get_currency()])));
            } else {
                $order_total = number_format((float)$order->get_total(), 0, ',', '.');
            }

            $customer_name = method_exists($order, 'get_formatted_billing_full_name') ? trim($order->get_formatted_billing_full_name()) : '';
            if (empty($customer_name) && method_exists($order, 'get_billing_first_name')) {
                $customer_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
            }

            $order_tags = [
                '{order_id}'         => (string)$order->get_id(),
                '{order_number}'     => method_exists($order, 'get_order_number') ? (string)$order->get_order_number() : (string)$order->get_id(),
                '{order_date}'       => $order_date,
                '{order_status}'     => function_exists('wc_get_order_status_name') ? wc_get_order_status_name($order->get_status()) : ucfirst($order->get_status()),
                '{customer_name}'    => $customer_name ?: __('Pelanggan', 'affichat-wp'),
                '{customer_phone}'   => method_exists($order, 'get_billing_phone') ? $order->get_billing_phone() : '',
                '{order_total}'      => $order_total,
                '{payment_method}'   => method_exists($order, 'get_payment_method_title') ? ($order->get_payment_method_title() ?: '-') : '-',
                '{items_list}'       => $items_list,
                '{billing_address}'  => method_exists($order, 'get_formatted_billing_address') ? preg_replace('/<br\s*\/?>/i', ', ', $order->get_formatted_billing_address()) : '',
                '{shipping_address}' => method_exists($order, 'get_formatted_shipping_address') ? preg_replace('/<br\s*\/?>/i', ', ', $order->get_formatted_shipping_address()) : '',
                '{store_name}'       => get_bloginfo('name'),
                '{order_url}'        => method_exists($order, 'get_view_order_url') ? $order->get_view_order_url() : '',
            ];

            $tags = array_merge($tags, $order_tags);
        }

        if (!empty($extra_tags) && is_array($extra_tags)) {
            $tags = array_merge($tags, $extra_tags);
        }

        return str_replace(array_keys($tags), array_values($tags), $template);
    }

    /**
     * Formats order items into clean bulleted WhatsApp text lines.
     *
     * @param object $order WC_Order.
     * @return string Formatted items.
     */
    private static function format_items_list($order) {
        if (!method_exists($order, 'get_items')) {
            return '';
        }

        $lines = [];
        foreach ($order->get_items() as $item) {
            $name = method_exists($item, 'get_name') ? $item->get_name() : 'Item';
            $qty  = method_exists($item, 'get_quantity') ? $item->get_quantity() : 1;
            
            $sub = '';
            if (function_exists('wc_price') && method_exists($item, 'get_total')) {
                $sub = html_entity_decode(wp_strip_all_tags(wc_price($item->get_total(), ['currency' => $order->get_currency()])));
            }
            
            $lines[] = $sub ? sprintf('• %s x %d (%s)', $name, $qty, $sub) : sprintf('• %s x %d', $name, $qty);
        }
        return implode("\n", $lines);
    }

    /**
     * Returns list of available universal dynamic tags for reference.
     *
     * @param bool $include_wc Whether to include WooCommerce tags.
     * @return array Associative array of tag => label.
     */
    public static function get_available_tags($include_wc = true) {
        $tags = [
            '{name}'        => __('Nama Pengirim / Pelanggan', 'affichat-wp'),
            '{phone}'       => __('Nomor WhatsApp', 'affichat-wp'),
            '{email}'       => __('Alamat Email', 'affichat-wp'),
            '{subject}'     => __('Subjek / Halaman Form', 'affichat-wp'),
            '{message}'     => __('Isi Pesan / Catatan', 'affichat-wp'),
            '{site_name}'   => __('Nama Website', 'affichat-wp'),
            '{site_url}'    => __('Alamat URL Website', 'affichat-wp'),
            '{date}'        => __('Tanggal Hari Ini', 'affichat-wp'),
            '{time}'        => __('Jam Sekarang', 'affichat-wp'),
        ];

        if ($include_wc) {
            $wc_tags = [
                '{order_id}'         => __('ID Pesanan', 'affichat-wp'),
                '{order_number}'     => __('Nomor Pesanan', 'affichat-wp'),
                '{order_date}'       => __('Tanggal Pesanan', 'affichat-wp'),
                '{order_status}'     => __('Status Pesanan', 'affichat-wp'),
                '{customer_name}'    => __('Nama Lengkap Pembeli', 'affichat-wp'),
                '{customer_phone}'   => __('Nomor Telepon Pembeli', 'affichat-wp'),
                '{order_total}'      => __('Total Pembayaran', 'affichat-wp'),
                '{payment_method}'   => __('Metode Pembayaran', 'affichat-wp'),
                '{items_list}'       => __('Daftar Produk Pesanan', 'affichat-wp'),
                '{billing_address}'  => __('Alamat Penagihan', 'affichat-wp'),
                '{shipping_address}' => __('Alamat Pengiriman', 'affichat-wp'),
                '{store_name}'       => __('Nama Toko', 'affichat-wp'),
                '{order_url}'        => __('Tautan Cek Pesanan', 'affichat-wp'),
            ];
            $tags = array_merge($tags, $wc_tags);
        }

        return $tags;
    }

    /**
     * Returns universal tags for website form builders.
     *
     * @return array Associative array of tag => label.
     */
    public static function get_form_tags() {
        return [
            '{name}'      => __('Nama Pengirim', 'affichat-wp'),
            '{phone}'     => __('Nomor WhatsApp', 'affichat-wp'),
            '{email}'     => __('Alamat Email', 'affichat-wp'),
            '{subject}'   => __('Subjek / Nama Formulir', 'affichat-wp'),
            '{message}'   => __('Isi Pesan', 'affichat-wp'),
            '{site_name}' => __('Nama Website', 'affichat-wp'),
            '{site_url}'  => __('URL Website', 'affichat-wp'),
            '{date}'      => __('Tanggal Kirim', 'affichat-wp'),
            '{time}'      => __('Waktu Kirim', 'affichat-wp'),
        ];
    }

    /**
     * Returns curated dynamic tags for WooCommerce order templates.
     *
     * @return array Associative array of tag => label.
     */
    public static function get_wc_tags() {
        return [
            '{order_number}'     => __('Nomor Pesanan', 'affichat-wp'),
            '{customer_name}'    => __('Nama Pembeli', 'affichat-wp'),
            '{customer_phone}'   => __('Telepon Pembeli', 'affichat-wp'),
            '{order_total}'      => __('Total Pembayaran', 'affichat-wp'),
            '{payment_method}'   => __('Metode Pembayaran', 'affichat-wp'),
            '{items_list}'       => __('Daftar Produk', 'affichat-wp'),
            '{order_status}'     => __('Status Pesanan', 'affichat-wp'),
            '{order_date}'       => __('Tanggal Pesanan', 'affichat-wp'),
            '{store_name}'       => __('Nama Toko', 'affichat-wp'),
            '{billing_address}'  => __('Alamat Penagihan', 'affichat-wp'),
            '{shipping_address}' => __('Alamat Pengiriman', 'affichat-wp'),
        ];
    }
}
