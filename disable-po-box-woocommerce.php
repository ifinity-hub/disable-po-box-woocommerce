<?php
/**
 * Plugin Name: Disable PO Box Addresses in WooCommerce
 * Description: Prevents customers from using PO Box addresses in shipping/billing fields
 * Version: 1.0
 * Author: Waqar Hassan
 * Author URI: http://ifinityhub.com
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('woocommerce_after_checkout_validation', 'validate_address_for_po_boxes', 10, 2);

function validate_address_for_po_boxes($data, $errors) {
    $po_box_pattern = '/\b(?:p\.?\s*o\.?\s*box|post\s*(?:office)?\s*box)\b/i';
    $address_fields = [
        'shipping_address_1',
        'shipping_address_2',
        'billing_address_1',
        'billing_address_2'
    ];

    foreach ($address_fields as $field) {
        if (!empty($data[$field]) && preg_match($po_box_pattern, $data[$field])) {
            $field_name = str_replace(['_', '1', '2'], ' ', $field);
            $errors->add(
                'validation',
                sprintf(
                    __('%s field contains a PO Box address. We do not accept PO Box addresses. Please provide a physical street address.', 'woocommerce'),
                    ucwords(trim($field_name))
                )
            );
        }
    }
}
