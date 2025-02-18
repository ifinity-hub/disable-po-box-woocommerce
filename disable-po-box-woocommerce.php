<?php
/**
 * Plugin Name: Disable PO Box Addresses in WooCommerce
 * Description: Prevents customers from using PO Box addresses in shipping/billing fields
 * Version: 2.0
 * Author: Waqar Hassan
 * Author URI: http://ifinityhub.com
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_PO_Box_Restrictions {

    public function __construct() {
        // Initialize settings
        add_action('admin_init', [$this, 'init_settings']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_plugin_action_links']);
        add_action('woocommerce_after_checkout_validation', [$this, 'validate_address_for_po_boxes'], 10, 2);
    }

    // Add settings link on plugins page
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-po-box-settings') . '">' . __('Settings', 'woocommerce') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    // Add admin menu under WooCommerce
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('PO Box Settings', 'woocommerce'),
            __('PO Box Settings', 'woocommerce'),
            'manage_options',
            'wc-po-box-settings',
            [$this, 'settings_page']
        );
    }

    // Settings page content
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('PO Box Restrictions Settings', 'woocommerce'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wc_po_box_settings_group');
                do_settings_sections('wc-po-box-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // Initialize settings
    public function init_settings() {
        register_setting('wc_po_box_settings_group', 'wc_po_box_settings');

        add_settings_section(
            'wc_po_box_main_section',
            __('PO Box Restrictions Configuration', 'woocommerce'),
            null,
            'wc-po-box-settings'
        );

        add_settings_field(
            'disable_shipping_po_box',
            __('Disable Shipping PO Box', 'woocommerce'),
            [$this, 'checkbox_field_callback'],
            'wc-po-box-settings',
            'wc_po_box_main_section',
            ['label_for' => 'disable_shipping_po_box']
        );

        add_settings_field(
            'disable_billing_po_box',
            __('Disable Billing PO Box', 'woocommerce'),
            [$this, 'checkbox_field_callback'],
            'wc-po-box-settings',
            'wc_po_box_main_section',
            ['label_for' => 'disable_billing_po_box']
        );
    }

    // Checkbox field callback
    public function checkbox_field_callback($args) {
        $options = get_option('wc_po_box_settings');
        $id = $args['label_for'];
        $checked = isset($options[$id]) ? checked(1, $options[$id], false) : '';
        echo '<input type="checkbox" id="' . esc_attr($id) . '" name="wc_po_box_settings[' . esc_attr($id) . ']" value="1" ' . $checked . '/>';
    }

    // Validation function
    public function validate_address_for_po_boxes($data, $errors) {
        $options = get_option('wc_po_box_settings');
        $po_box_pattern = '/\b(?:p\.?\s*o\.?\s*box|post\s*(?:office)?\s*box)\b/i';

        if (isset($options['disable_shipping_po_box']) && $options['disable_shipping_po_box']) {
            $this->validate_field($data, $errors, $po_box_pattern, 'shipping');
        }

        if (isset($options['disable_billing_po_box']) && $options['disable_billing_po_box']) {
            $this->validate_field($data, $errors, $po_box_pattern, 'billing');
        }
    }

    private function validate_field($data, $errors, $pattern, $type) {
        $fields = [
            $type . '_address_1',
            $type . '_address_2'
        ];

        foreach ($fields as $field) {
            if (!empty($data[$field]) && preg_match($pattern, $data[$field])) {
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
}

new WC_PO_Box_Restrictions();
