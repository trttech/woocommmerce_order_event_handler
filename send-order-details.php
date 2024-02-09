<?php
/*
Plugin Name: Send Order Details
Description: Sends complete order details to an Express.js server upon order creation in WooCommerce.
Version: 1.0
Author: Tousif
*/

add_action('woocommerce_checkout_order_processed', 'send_complete_order_to_express_server', 10, 3);

function send_complete_order_to_express_server($order_id, $posted_data, $order) {
    if (!$order_id || !is_a($order, 'WC_Order')) {
        return;
    }

    // Initialize the structured data array
    $structured_data = array(
        'id' => $order->get_id(),
        'parent_id' => $order->get_parent_id(),
        'status' => $order->get_status(),
        'currency' => $order->get_currency(),
        'currency_symbol' => html_entity_decode(get_woocommerce_currency_symbol($order->get_currency())),
        'date_created' => $order->get_date_created()->date('c'), // ISO 8601 format
        'date_modified' => $order->get_date_modified()->date('c'), // ISO 8601 format
        'discount_total' => $order->get_discount_total(),
        'discount_tax' => $order->get_discount_tax(),
        'shipping_total' => $order->get_shipping_total(),
        'shipping_tax' => $order->get_shipping_tax(),
        'cart_tax' => $order->get_cart_tax(),
        'total' => $order->get_total(),
        'total_tax' => $order->get_total_tax(),
        'customer_id' => $order->get_customer_id(),
        'order_key' => $order->get_order_key(),
        'billing' => array(
            'first_name' => $order->get_billing_first_name(),
            'last_name' => $order->get_billing_last_name(),
            'company' => $order->get_billing_company(),
            'address_1' => $order->get_billing_address_1(),
            'address_2' => $order->get_billing_address_2(),
            'city' => $order->get_billing_city(),
            'state' => $order->get_billing_state(),
            'postcode' => $order->get_billing_postcode(),
            'country' => $order->get_billing_country(),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
        ),
        
        'shipping' => array(
            'first_name' => $order->get_shipping_first_name(),
            'last_name' => $order->get_shipping_last_name(),
            'company' => $order->get_shipping_company(),
            'address_1' => $order->get_shipping_address_1(),
            'address_2' => $order->get_shipping_address_2(),
            'city' => $order->get_shipping_city(),
            'state' => $order->get_shipping_state(),
            'postcode' => $order->get_shipping_postcode(),
            'country' => $order->get_shipping_country(),
        ),
        'payment_method' => $order->get_payment_method(),
        'payment_method_title' => $order->get_payment_method_title(),
        'customer_ip_address' => $order->get_customer_ip_address(),
        'customer_user_agent' => $order->get_customer_user_agent(),
        'created_via' => $order->get_created_via(),
        'customer_note' => $order->get_customer_note(),
        'date_completed' => $order->get_date_completed() ? $order->get_date_completed()->date('c') : null,
        'date_paid' => $order->get_date_paid() ? $order->get_date_paid()->date('c') : null,
        'cart_hash' => $order->get_cart_hash(),
        'number' => $order->get_order_number(),
        // Meta data and line items will need to be added here

    );

    // Include meta_data
    $structured_data['meta_data'] = array_map(function($meta) {
        return array(
            'id' => $meta->id,
            'key' => $meta->key,
            'value' => $meta->value,
        );
    }, $order->get_meta_data());

    // Include line items
    $structured_data['line_items'] = array();
    foreach ($order->get_items() as $item_id => $item) {
        $item_data = $item->get_data();
        $structured_data['line_items'][] = array(
            'id' => $item_id,
            'name' => $item->get_name(),
            'product_id' => $item->get_product_id(),
            'variation_id' => $item->get_variation_id(),
            'quantity' => $item->get_quantity(),
            'tax_class' => $item->get_tax_class(),
            'subtotal' => $item->get_subtotal(),
            'subtotal_tax' => $item->get_subtotal_tax(),
            'total' => $item->get_total(),
            'total_tax' => $item->get_total_tax(),
            // Include additional item details as needed
            'meta_data' => array_map(function($meta) {
                return array(
                    'id' => $meta->id,
                    'key' => $meta->key,
                    'value' => $meta->value,
                );
            }, $item->get_meta_data()),
        );
    }

    // Include tax lines using WC_Order_Item_Tax objects
    $structured_data['tax_lines'] = array();
    foreach ($order->get_items('tax') as $item_id => $item) {
        $structured_data['tax_lines'][] = array(
            'rate_id' => $item->get_rate_id(),
            'rate_code' => $item->get_rate_code(),
            'label' => $item->get_label(),
            'name' => $item->get_name(),
            'tax_total' => $item->get_tax_total(),
            'shipping_tax_total' => $item->get_shipping_tax_total(),
            'compound' => $item->get_compound(),
            'rate_percent' => WC_Tax::get_rate_percent($item->get_rate_id()),
        );
    }

    // Convert the structured data to JSON
    $json_order = wp_json_encode($structured_data);

    // Define the URL of your Express.js server endpoint
    $url = 'https://middleware.trttechnologies.ca/send-data';

    // Use wp_remote_post to send the JSON to your server
    $response = wp_remote_post($url, array(
        'headers' => array('Content-Type' => 'application/json; charset=utf-8'),
        'body' => $json_order,
        'method' => 'POST',
        'data_format' => 'body',
    ));

    // Handle the response from your server as needed
}



