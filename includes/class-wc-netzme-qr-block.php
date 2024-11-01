<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Netzme_Qr_Gateway_Blocks extends AbstractPaymentMethodType {

    private $gateway;
    protected $name = 'netzmeqr_gateway';// your payment gateway name

    public function initialize() {
        $this->settings = get_option( 'netzmeqr_gateway', []);
        $this->gateway = new WC_Gateway_netzmeqr();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {

        wp_register_script(
            'netzmeqr_gateway-blocks-integration',
            plugin_dir_url(__FILE__) . '../assets/js/checkout.js',
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            '1.0.6',
            true
        );
        if( function_exists( 'wp_set_script_translations' ) ) {            
            wp_set_script_translations( 'netzmeqr_gateway-blocks-integration');
            
        }
        return [ 'netzmeqr_gateway-blocks-integration' ];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
        ];
    }

}
?>