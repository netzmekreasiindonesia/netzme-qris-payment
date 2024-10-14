<?php
/**
 * Plugin Name: Payment netzmeqr Gateway
 * Plugin URI: https://www.om4g.us/
 * Description: netzmeqr
 * Author: om4g.us
 * Author URI: https://om4g.us/
 * Version: 1.0.5
 * Text Domain: wc-netzmeqr
 * Domain Path: /i18n/languages/
 *
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Gateway-netzmeqr
 * @author    om4g.us
 * @category  Admin
 * @copyright Copyright (c) 2015-2016, om4g.us, Inc. and WooCommerce
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 * netzmeqr
 */

defined( 'ABSPATH' ) or exit;


// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    return;
}


/**
 * Add the gateway to WC Available Gateways
 *
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways
 */
function wc_netzmeqr_add_to_gateways( $gateways ) {
    $gateways[] = 'WC_Gateway_netzmeqr';
    return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_netzmeqr_add_to_gateways' );


/**
 * Adds plugin page links
 *
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function wc_netzmeqr_gateway_plugin_links( $links ) {

    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=netzmeqr_gateway' ) . '">' . __( 'Configure', 'wc-gateway-netzmeqr' ) . '</a>'
    );

    return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_netzmeqr_gateway_plugin_links' );


/**
 * netzmeqr Payment Gateway
 *
 * Provides an netzmeqr Payment Gateway; mainly for testing purposes.
 * We load it later to ensure WC is loaded first since we're extending it.
 *
 * @class       WC_Gateway_netzmeqr
 * @extends     WC_Payment_Gateway
 * @version     1.0.5
 * @package     WooCommerce/Classes/Payment
 * @author      om4g.us
 */
add_action( 'plugins_loaded', 'wc_netzmeqr_gateway_init', 11 );

function wc_netzmeqr_gateway_init() {

    class WC_Gateway_netzmeqr extends WC_Payment_Gateway {

        /**
         * Constructor for the gateway.
         */
        public function __construct() {
            $this->id                 = 'netzmeqr_gateway';
            $this->icon               = apply_filters('woocommerce_netzmeqr_icon', '');
            $this->has_fields         = false;
            $this->method_title       = __( 'netzmeqr', 'wc-gateway-netzmeqr' );
            $this->method_description = __( 'Allows netzmeqr.', 'wc-gateway-netzmeqr' );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            $this->BaseUrl  = $this->get_option( 'BaseUrl' );
            $this->PayBaseUrl  = $this->get_option( 'PayBaseUrl' );
            $this->ClientID  = $this->get_option( 'ClientID' );
            $this->ClientSecret  = $this->get_option( 'ClientSecret' );
            $this->MerchantID  = $this->get_option( 'MerchantID' );
            $this->PayClientID  = $this->get_option( 'PayClientID' );
            $this->PayToken  = $this->get_option( 'PayToken' );
            $this->AccessToken  = $this->get_option( 'AccessToken' );
            $this->ExpiredTime  = $this->get_option( 'ExpiredTime' );
            $this->FeeType  = $this->get_option( 'FeeType' );
            $this->CommissionPercentage  = $this->get_option( 'CommissionPercentage' );
            $this->PrivateKey = $this->get_option('PrivateKey');
            $this->ChannelID = $this->get_option('ChannelID');
            $this->TokoNetzmeBaseUrl = $this->get_option('TokoNetzmeBaseUrl');

            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
            add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
            //add_action('woocommerce_api_' . $this->id . '_gateway', array($this, 'ipn_response'));
            add_action( 'woocommerce_api_netzmeqr_gateway', array( $this, 'ipn_response'));
            //https://om4g.us/wp/wc-api/netzmeqr_gateway/
        }

        public function getSnapToken() {
            $api_url = $this->BaseUrl . '/api/v1/access-token/b2b';
            $rawBody = [
                'grantType' => 'client_credentials',
                'additionalInfo' => []
            ];
            $body = json_encode($rawBody);

            $clientId = $this->ClientID;
            $clientSecret = $this->ClientSecret;
            $privateKey = $this->PrivateKey;

            $now = date(DATE_ATOM);
            $sign = $this->generateAuthSig($clientId, $clientSecret, $privateKey, $now);

            $headers = array(
                'Content-Type' => 'application/json', 
                'X-TIMESTAMP' => $now, 
                'X-CLIENT-KEY' => $clientId, 
                'X-SIGNATURE' => $sign
            );

            $request = new WP_Http;
            $result = $request->request( $api_url , array( 'method' => 'POST', 'body' => $body, 'headers' => $headers ) );

            $resp = json_decode($result['body']);
            return $resp;
        }

        private function generateAuthSig($clientId, $clientSecret, $privateKey, $now) {
            $privateKey = <<<EOD
            -----BEGIN RSA PRIVATE KEY-----
            $privateKey
            -----END RSA PRIVATE KEY-----
            EOD;
            $hash = $clientId . '|' . $now;
            $signature = '';

            // Sign the data with the private key using SHA256
            if (openssl_sign($hash, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                return base64_encode($signature);
            } else {
                throw new Exception('Unable to sign data.');
            }
        }

        /**
         * Initialize Gateway Settings Form Fields
         */
        public function init_form_fields() {

            $this->form_fields = apply_filters( 'wc_netzmeqr_form_fields', array(

                'enabled' => array(
                    'title'   => __( 'Enable/Disable', 'wc-gateway-netzmeqr' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Enable netzmeqr Payment', 'wc-gateway-netzmeqr' ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Title', 'wc-gateway-netzmeqr' ),
                    'type'        => 'text',
                    'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-netzmeqr' ),
                    'default'     => __( 'QRIS Payment', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                ),
                'BaseUrl' => array(
                    'title'       => __( 'BaseUrl', 'wc-gateway-netzmeqr' ),
                    'type'        => 'text',
                    'description' => __( 'BaseUrl.', 'wc-gateway-netzmeqr' ),
                    'default'     => __( 'https://tokoapi-stg.netzme.com', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                ),
                'PayBaseUrl' => array(
                    'title'       => __( 'PayBaseUrl', 'wc-gateway-netzmeqr' ),
                    'type'        => 'text',
                    'description' => __( 'PayBaseUrl.', 'wc-gateway-netzmeqr' ),
                    'default'     => __( 'https://pay-stg.netzme.com', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                ),
                'TokoNetzmeBaseUrl' => array(
                    'title'       => __( 'TokoNetzmeBaseUrl', 'wc-gateway-netzmeqr' ),
                    'type'        => 'text',
                    'description' => __( 'TokoNetzmeBaseUrl.', 'wc-gateway-netzmeqr' ),
                    'default'     => __( 'https://api-toko-netzme-stg.netzme.com', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                ),
                'ClientID' => array(
                    'title'       => __( 'ClientID', 'wc-gateway-netzmeqr' ),
                    'type'        => 'text',
                    'description' => __( 'ClientID.', 'wc-gateway-netzmeqr' ),
                    'default'     => __( '', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                ),
                'ClientSecret' => array(
                    'title'       => __( 'ClientSecret', 'wc-gateway-netzmeqr' ),
                    'type'        => 'text',
                    'description' => __( 'ClientSecret.', 'wc-gateway-netzmeqr' ),
                    'default'     => __( '', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                ),
                'PrivateKey' => array(
                    'title'       => __( 'PrivateKey', 'wc-gateway-netzmeqr' ),
                    'type'        => 'textarea',
                    'description' => __( 'PrivateKey.', 'wc-gateway-netzmeqr' ),
                    'default'     => __( '', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                ),
                'ChannelID' => array(
                    'title'       => __( 'ChannelID', 'wc-gateway-netzmeqr' ),
                    'type'        => 'text',
                    'description' => __( 'ChannelID.', 'wc-gateway-netzmeqr' ),
                    'default'     => __( '', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                ),
                'MerchantID' => array(
                    'title'       => __( 'MerchantID', 'wc-gateway-netzmeqr' ),
                    'type'        => 'text',
                    'description' => __( 'MerchantID.', 'wc-gateway-netzmeqr' ),
                    'default'     => __( '', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                ),
                'PayClientID' => array(
                    'title'       => __( 'PayClientID', 'wc-gateway-netzmeqr' ),
                    'type'        => 'text',
                    'description' => __( 'PayClientID.', 'wc-gateway-netzmeqr' ),
                    'default'     => __( '', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                ),
                'PayToken' => array(
                    'title'       => __( 'PayToken', 'wc-gateway-netzmeqr' ),
                    'type'        => 'text',
                    'description' => __( 'PayToken.', 'wc-gateway-netzmeqr' ),
                    'default'     => __( '', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                ),
                'AccessToken' => array(
                    'title'       => __( 'AccessToken', 'wc-gateway-netzmeqr' ),
                    'type'        => 'text',
                    'description' => __( 'AccessToken.', 'wc-gateway-netzmeqr' ),
                    'default'     => __( '', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                ),
                'ExpiredTime' => array(
                    'title'       => __( 'QR Expired Time (Minutes)', 'wc-gateway-netzmeqr' ),
                    'type'        => 'text',
                    'description' => __( 'QR Expired Time (Minutes).', 'wc-gateway-netzmeqr' ),
                    'default'     => __( '60', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                ),              
                'FeeType' => array(
                    'title'       => __( 'Fee Type', 'wc-gateway-netzmeqr' ),
                    'type'        => 'select',
                    'description' => __( 'Fee Type.', 'wc-gateway-netzmeqr' ),
                    'default'     => __( 'on_buyer', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                    'options' => array(
                        'on_buyer' => 'on_buyer',
                        'on_seller' => 'on_seller'
                   )
                ),              
                'CommissionPercentage' => array(
                    'title'       => __( 'Commission Percentage', 'wc-gateway-netzmeqr' ),
                    'type'        => 'text',
                    'description' => __( 'Commission Percentage. Example: 0.7', 'wc-gateway-netzmeqr' ),
                    'default'     => __( '0.0', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                ),

                'PaymentMethode' => array(
                    'title'       => __( 'Payment Methode', 'wc-gateway-netzmeqr' ),
                    'type'        => 'multiselect',
                    'description' => __( 'Payment Methode', 'wc-gateway-netzmeqr' ),
                    'default'     => __( 'qris', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                    'options' => array(
                        'BANK_TRANSFER' => 'QRIS',
                        'qris' => 'Bank Transfer'
                   )
                ),

                'PaymentReminderInMinutes' => array(
                    'title'       => __( 'Reminder Payment In Minutes', 'wc-gateway-netzmeqr' ),
                    'type'        => 'text',
                    'description' => __( 'Reminder payment pending After', 'wc-gateway-netzmeqr' ),
                    'default'     => __( '720', 'wc-gateway-netzmeqr' ),
                    'desc_tip'    => true,
                 ),

                /*
                'instructions' => array(
                    'title'       => __( 'Instructions', 'wc-gateway-netzmeqr' ),
                    'type'        => 'textarea',
                    'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-netzmeqr' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                */
            ) );
        }


        /**
         * Output for the order received page.
         */
        public function thankyou_page() {
            // if ( $this->instructions ) {
            //  echo wpautop( wptexturize( $this->instructions ) );
            // }
            echo "Thank you";
        }

        

        function receipt_page($order_id)
        {
            $order = new WC_Order( $order_id );
            if ( $order->get_status() == 'processing' || $order->get_status() == 'completed') {
                echo "t1 = window.setTimeout(function(){ window.location = '".$order->get_checkout_order_received_url()."'; },3);";
                die();
            }
           //check if order alrea
            if ($order->get_transaction_id()) {
                $qrImage = $order->get_meta('qr_image');
                $nzterminalId = $order->get_meta('terminal_id');
                $datenzexpiredTs = date_parse($order->get_meta('expired_ts'));
                include_once("qrpage.php");
                return;
            }

            $desc = '';
            if ( 0 < sizeof( $order->get_items() ) )
            {
                foreach ( $order->get_items() as $item )
                {
                    if ( $item['qty'] )
                    {
                        $item_name = htmlspecialchars($item['name']);
                        $desc .= $item['qty'] .' x '. $item_name . ', ';
                    }
                }
                $desc = substr($desc, 0, -2 );
            }

            $refNo  = date_format($order->get_date_created(), "Ymd") . "-" . $order_id;
            $ExpiredTime = intval($this->ExpiredTime)*60;

            $named_array = [
                "custIdMerchant" => $this->MerchantID,
                "partnerReferenceNo" => $refNo,
                "amount" => [
                    "value" => $order->get_total(),
                    "currency" => "IDR"
                ],
                "amountDetail" => [
                    "basicAmount" => [
                        "value" => $order->get_total(),
                        "currency" => "IDR"
                    ],
                    "shippingAmount" => [
                        "value" => "0",
                        "currency" => "IDR"
                    ]
                ],
                "payMethod" => "QRIS",
                "commissionPercentage" => $this->CommissionPercentage,
                "expireInSecond" => "$ExpiredTime", 
                "feeType" => $this->FeeType,
                "additionalInfo" => [
                    "email" => $order->get_billing_email(),
                    "notes" => $desc,
                    "description" => $desc,
                    "phoneNumber" => $this->normalizePhoneNumber($order->get_billing_phone()),
                    "imageUrl" => "",
                    "fullname" => $order->get_billing_first_name().' '.$order->get_billing_last_name()
                ]
            ];

            //$this->add_debug_log("receipt_page $order_id request " . print_r($named_array, true));
            // Now, the HTTP request:
            $uri = '/api/v1.0/invoice/create-transaction';
            $api_url = $this->BaseUrl . $uri;
            $body = json_encode($named_array);
            $now = date(DATE_ATOM);
            $ts = $this->getSnapToken();
            $clientId = $this->ClientID;
            $clientSecret = $this->ClientSecret;
            $privateKey = $this->PrivateKey;

            $sign = $this->generateServiceSignature($clientSecret, 'POST', $uri, $ts->accessToken, $named_array, $now);
           
            $headers = array(
                'Content-Type' => 'application/json',
                'CHANNEL-ID' => $this->ChannelID,
                'X-EXTERNAL-ID' => $order_id,
                'X-PARTNER-ID' => $this->ClientID,
                'X-TIMESTAMP' => $now, 
                'Authorization' => $ts->tokenType .' ' . $ts->accessToken,
                'X-SIGNATURE' => $sign
            );

            $request = new WP_Http;
            $result = $request->request( $api_url , array( 'method' => 'POST', 'body' => $body, 'headers' => $headers ) );

            $resp = json_decode($result['body']);
            //$this->add_debug_log("receipt_page $order_id response " . print_r($resp, true));
            $nzstatus = $resp->additionalInfo->status;

            $nzmessage      = $resp->responseMessage;
            $nzexpiredTs    = $resp->additionalInfo->expiredTs;
            //[expiredTs] => 2022-06-19T22:29:57.279+07:00
            $datenzexpiredTs = date_parse($nzexpiredTs);
            $nzterminalId   = $resp->additionalInfo->terminalId;
            $qrImage        = $resp->additionalInfo->qrImage;
            if(!$nzstatus){
                global $woocommerce;
                $cart_page_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : $woocommerce->cart->get_cart_url();
                echo "<br/>QR Generation Failed<br/>";
                echo "<br/><a href='$cart_page_url'>Kembali</a><br/>";
            }else{

                
                $order->set_transaction_id($resp->partnerReferenceNo);
                $order->update_meta_data('qr_image', $qrImage);
                $order->update_meta_data('terminal_id', $nzterminalId);
                $order->update_meta_data('expired_ts', $resp->additionalInfo->expiredTs);

                //additional info
                $order->update_meta_data('shipping_provider', '-');
                $order->update_meta_data('awb_number', '-');
                $order->update_meta_data('tracking_url', '-');
                $order->update_meta_data('remainder_email_sent', '0');

                $order->save();

                WC()->mailer()->customer_invoice($order);
                WC()->cart->empty_cart();
                wc_reduce_stock_levels($order_id);
                include_once("qrpage.php");

                //$order->update_status( 'on-hold', __( 'Awaiting netzmeqr payment', 'wc-gateway-netzmeqr' ) );
                // Reduce stock levels
                //$order->reduce_order_stock();
               
				// Remove cart
               
				// Get the WC_Email_New_Order object
				//$email_new_order = WC()->mailer()->get_emails()['WC_Email_New_Order'];
				// Sending the new Order email notification for an $order_id (order ID)
				//$email_new_order->trigger( $order_id );
            }
        }

        function generateServiceSignature($clientSecret, $method, $uri, $token, $body, $now) {
            $data = json_encode($body);
            $signature = '';
            $data = "{$method}:{$uri}:{$token}:". hash('sha256', $data) .":{$now}";

            $signature = hash_hmac('sha512', $data, $clientSecret);

            return $signature;
        }

        function normalizePhoneNumber($phoneNumber) {
            if (isset($phoneNumber) && is_string($phoneNumber)) {
                return preg_replace(['/\s|-|\.|_|=|[aA-zZ]/', '/^0/', '/^[1-9]/'], ['', '+62', '+$0'], $phoneNumber);
            }
            return $phoneNumber;
        }


        /**
         * Add content to the WC emails.
         *
         * @access public
         * @param WC_Order $order
         * @param bool $sent_to_admin
         * @param bool $plain_text
         */
        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {

            if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
            }
        }

        /**
         * Process the payment and return the result
         *
         * @param int $order_id
         * @return array
         */
        public function process_payment( $order_id ) {

            $order = wc_get_order( $order_id );

            // Mark as on-hold (we're awaiting the payment)
            //$order->update_status( 'on-hold', __( 'Awaiting netzmeqr payment', 'wc-gateway-netzmeqr' ) );

            // Reduce stock levels
            //$order->reduce_order_stock();
            // Remove cart
            //WC()->cart->empty_cart();

            $orderkey = !method_exists($order, 'get_order_key') ? $order->order_key : $order->get_order_key();
            $orderid = !method_exists($order, 'get_id') ? $order->id : $order->get_id();

            return array(   'result'    => 'success',
            'redirect'  => add_query_arg('key', $orderkey, add_query_arg(array(
            'order-pay' => $orderid),
            $order->get_checkout_payment_url(true))));

            // Return thankyou redirect
            //return array(
            //  'result'    => 'success',
            //  'redirect'  => $this->get_return_url( $order )
            //);
        }

        function checkstatus(){
            $order_id = $this->decrypt($_GET['key']);
            $order = new WC_Order( $order_id );
            if ( $order->get_status() == 'processing' || $order->get_status() == 'completed' ) {
                echo "1";
                die();
            }
            echo "0";
        }

        private function checkAuth()
        {
          $AUTH_USER = CALBACK_INVOUCE_USERNAME;
          $AUTH_PASS = CALBACK_INVOUCE_PASSWORD;

          header('Cache-Control: no-cache, must-revalidate, max-age=0');
          $has_supplied_credentials = !(empty($_SERVER['PHP_AUTH_USER']) && empty($_SERVER['PHP_AUTH_PW']));
          $is_not_authenticated = (
              !$has_supplied_credentials ||
              $_SERVER['PHP_AUTH_USER'] != $AUTH_USER ||
              $_SERVER['PHP_AUTH_PW']   != $AUTH_PASS
          );
          if ($is_not_authenticated) {
              $data = json_decode(file_get_contents('php://input'), true);
              $this->add_debug_log("ipn_response_invalid_auth " . print_r($data, true));
              ///return false;

          }
          return true;
   
        }

        function ipn_response()
        {
            global $woocommerce;
            //echo "callback here";

            

            $action = $_GET['action'];
            //https://om4g.us/wp/wc-api/netzmeqr_gateway/?action=checkstatus
            if($action=="checkstatus"){
                $this->checkstatus();
                die();
            }

            if ($this->checkAuth() === false) {
                //header('HTTP/1.1 401 Authorization Required');
                //header('WWW-Authenticate: Basic realm="Access denied"');
                //exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
         
            $this->add_debug_log("ipn_response " . print_r($data, true));
            $partner_transaction_id = $data['originalPartnerReferenceNo'];
            $nzid = $data['id'];
            $nzmerchant = $data['merchant'];
            $nzpayment_time = $data['additionalInfo']['paymentTime'];
            $nzstatus = $data['latestTransactionStatus'];
            $nzamount = $data['netAmount']['value'];
            
            $splitReffNo = explode("-", $partner_transaction_id);

            $ReffNo_Date    = $splitReffNo[0];
            $ReffNo_Number  = $splitReffNo[1];
            
            $order = wc_get_order($ReffNo_Number);
         
            if($ReffNo_Number == ""){
                echo "Transaction not found";
                die();
            }
            if($nzstatus != "00"){
                echo "Transaction already paid";
                die();
            }
            if(floatval($nzamount)!=$order->get_total()){
                //echo "Transaction amount not match";
                //die();
            }


            
            if($order->get_status() == 'pending' || $order->get_status() == 'on-hold')
            {

            }
            $order->add_order_note('The Order has been Paid by netzme Payment Gateway with QR.');
            $order->payment_complete();
            $order->update_status('processing');
            $order->save();

			//WC()->mailer()->customer_invoice($order);
            
            WC()->mailer()->get_emails()['WC_Email_Customer_Processing_Order']->trigger( $order->id );

            WC()->mailer()->get_emails()['WC_Email_New_Order']->re_trigger( $order->id );


            $named_array = array(
                    "id" => "$order->id",
                    "transaction_id" => "$partner_transaction_id",
                    "status" => "$nzstatus",
                    "merchant" => "$nzmerchant",
                    "payment_time" => "$nzpayment_time"
            );
            echo json_encode($named_array);

            die();
        }

        function getToken(){
            $api_url = 'https://tokoapi-stg.netzme.com/oauth/merchant/accesstoken';
            $body = json_encode($named_array);
            $headers = array('Authorization' => 'Bearer b5e390df-7df0-4d3a-bdc9-afbe92aeebb3', 'Request-Time' => 'application/json');
            $request = new WP_Http;
            $result = $request->request( $api_url , array( 'method' => 'POST', 'body' => $body, 'headers' => $headers ) );
        }

        function add_debug_log( $message ) {
            /*
            if ( self::is_wc_2_1() ) {
                return new WC_Logger();
            } else {
                    global $woocommerce;
                    return $woocommerce->logger();
            }
            if ( ! is_object( self::$log ) ) {
                self::$log = WC_Compat_iPay88_ATMTransfer::get_wc_logger();
            }
            */
            $log = new WC_Logger();
            $log->add( 'netzme', $message );
        }
        function encrypt($data) {
            $key = "om4gus";
            $plaintext = $data;
            $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
            $iv = openssl_random_pseudo_bytes($ivlen);
            $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
            $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
            $ciphertext = base64_encode($iv . $hmac . $ciphertext_raw);
            return $ciphertext;
        }
        function decrypt($data) {
            $key = "om4gus";
            $c = base64_decode($data);
            $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
            $iv = substr($c, 0, $ivlen);
            $hmac = substr($c, $ivlen, $sha2len = 32);
            $ciphertext_raw = substr($c, $ivlen + $sha2len);
            $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
            $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
            if (hash_equals($hmac, $calcmac))
            {
                return $original_plaintext;
            }
        }

  } // end \WC_Gateway_netzmeqr class
}
