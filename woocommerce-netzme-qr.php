<?php
/**
 * Plugin Name: QRIS Invoice Netzme
 * Plugin URI: https://github.com/netzmekreasiindonesia/netzme-qris-payment
 * Description: Accept QRIS payments in Indonesia with Netzme. Seamlessly integrated into WooCommerce.
 * Author: Netzme
 * Author URI: https://www.netzme.id
 * Version: 1.0.6
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
defined( 'ABSPATH' ) or exit;


// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

define('NETZME_APP_KEY', 'netzme_key');
define('NETZME_APP_VERSION', '1.0.6');

/**
 * Add the gateway to WC Available Gateways
 *
 * @since 1.0.0
 * @param array $gateways all available WC gateways
 * @return array $gateways all WC gateways
 */
function netzmeqr_add_to_gateways( $gateways ) {
	$gateways[] = 'WC_Gateway_netzmeqr';
	return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'netzmeqr_add_to_gateways' );

/**
 * function to check compatibility with cart_checkout_blocks feature 
*/
function nqpcart_checkout_blocks_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}
add_action('before_woocommerce_init', 'nqpcart_checkout_blocks_compatibility');

add_action( 'woocommerce_blocks_loaded', 'letsget_payment_method_type' );

/**
 * function to register a payment method type
 */
function letsget_payment_method_type() {
    // Check if the required class exists
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    require_once plugin_dir_path(__FILE__) . 'includes/class-wc-netzme-qr-block.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            $payment_method_registry->register( new Netzme_Qr_Gateway_Blocks );
        }
    );
}

/**
 * function to load a styles and scripts
 */
add_action('init', 'itis_styles_script');
function itis_styles_script() {
	if ( !is_admin() ) {
		wp_register_style( 'qris-css', plugin_dir_url( __FILE__ ).'assets/css/css.css', array(), NETZME_APP_VERSION );
		wp_enqueue_style( 'qris-css' );

		wp_register_style( 'invoice-qris-css', plugin_dir_url( __FILE__ ).'assets/css/invoice-qris.css', array(), NETZME_APP_VERSION );
		wp_enqueue_style( 'invoice-qris-css' );

		wp_register_script( 'qris-js', plugin_dir_url( __FILE__ ).'assets/js/invoice-qris.js', [], NETZME_APP_VERSION, true );
		wp_enqueue_script( 'qris-js' );
	}
}

function must_have_status_script() {
	$order_id = filter_input( INPUT_GET, 'order-pay', FILTER_SANITIZE_NUMBER_INT);
	if ($order_id && !empty($order_id)) {

		$netzmeQRISPaymentGatewayPlugin = new WC_Gateway_netzmeqr();
		$order = new WC_Order($order_id);
		$key = urlencode($netzmeQRISPaymentGatewayPlugin->encrypt($order_id));
		$base_url = esc_url(get_site_url()).'?wc-api=netzmeqr_gateway&action=checkstatus&key='.$key;
		$received_url = $order->get_checkout_order_received_url();

		$js_check_status = 'function checkStatus(e) {
								e.preventDefault();
								jQuery.ajax({
								  type:"get",
								  url:"'.$base_url.'",
								  success:function(data)
								  {
								  	  if (data == 1) {
										window.location = "'.$received_url.'";
									  } else {
									  	window.location.href=window.location.href;
									  }
								  }
								});
								return false;
							}';

		wp_register_script( 'wc-check-status-js', null, array(), NETZME_APP_VERSION, true );
		wp_enqueue_script( 'wc-check-status-js' );
		wp_add_inline_script( 'wc-check-status-js', $js_check_status );

		$js_auto_check_status = 'setInterval(function() {
			jQuery.ajax({
			  type:"get",
			  url:"'.$base_url.'",
			  success:function(data)
			  {
				  if( data == 1){
				  	window.location = "'.$received_url.'";
				  }
			  }
			});
		}, 3000);';

		wp_register_script( 'wc-auto-check-status-js', null, array(), NETZME_APP_VERSION, true );
		wp_enqueue_script( 'wc-auto-check-status-js' );
		wp_add_inline_script( 'wc-auto-check-status-js', $js_auto_check_status );
	}
}
add_action( 'wp_enqueue_scripts', 'must_have_status_script');

/**
 * Adds plugin page links
 *
 * @since 1.0.0
 * @param array $links all plugin links
 * @return array $links all plugin links + our custom links (i.e., "Settings")
 */
function maybe_gateway_plugin_links( $links ) {

	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=netzmeqr_gateway' ) . '">' . __( 'Configure', 'netzme-qris-payment' ) . '</a>'
	);

	return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'maybe_gateway_plugin_links' );

add_action('plugins_loaded', 'qris_payment_init');

function qris_payment_init() {

	/**
	 * netzmeqr Payment Gateway
	 *
	 * Provides an netzmeqr Payment Gateway; mainly for testing purposes.
	 * We load it later to ensure WC is loaded first since we're extending it.
	 *
	 * @class       WC_Gateway_netzmeqr
	 * @extends     WC_Payment_Gateway
	 * @version     1.0.6
	 * @package     WooCommerce/Classes/Payment
	 * @author      Netzme
	 */
	if (!class_exists('WC_Gateway_netzmeqr')) {
		class WC_Gateway_netzmeqr extends WC_Payment_Gateway 
		{
			protected $baseUrl;
			protected $payBaseUrl;
			protected $clientId;
			protected $clientSecret;
			protected $merchantId;
			protected $payClientId;
			protected $feeType;
			protected $commissionPercentage;
			protected $privateKey;
			protected $channelID;
			protected $expiredTime;
			protected $terminalId;

			/**
			 * Constructor for the gateway.
			 */
			public function __construct() 
			{
				$this->id = 'netzmeqr_gateway';
				$this->icon = apply_filters('woocommerce_netzmeqr_icon', '');
				$this->has_fields = false;
				$this->method_title = __( 'netzmeqr', 'netzme-qris-payment' );
				$this->method_description = __( 'Allows Netzme QRIS Payment', 'netzme-qris-payment' );

				// Load the settings.
				$this->init_form_fields();
				$this->init_settings();

				// Define user set variables
				$this->title = $this->get_option( 'title' );
				$this->description = $this->get_option( 'description' );
				$this->baseUrl = $this->get_option( 'baseUrl' );
				$this->clientId = $this->get_option( 'clientId' );
				$this->clientSecret = $this->get_option( 'clientSecret' );
				$this->merchantId = $this->get_option( 'merchantId' );
				$this->feeType = $this->get_option( 'feeType' );
				$this->commissionPercentage = $this->get_option( 'commissionPercentage' );
				$this->privateKey = $this->get_option('privateKey');
				$this->channelID = $this->get_option('channelID');
				$this->expiredTime = $this->get_option( 'expiredTime' );
				$this->terminalId = $this->get_option( 'terminalId' );
			
				// Actions
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
				add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
				add_action( 'woocommerce_api_netzmeqr_gateway', array( $this, 'ipn_response'));
			}

			/**
			 * get Token
			 */
			public function getSnapToken() 
			{
				$api_url = $this->baseUrl . '/api/v1/access-token/b2b';
				$rawBody = [
					'grantType' => 'client_credentials',
					'additionalInfo' => []
				];
				$body = wp_json_encode($rawBody);

				$clientId = $this->clientId;
				$clientSecret = $this->clientSecret;
				$privateKey = $this->privateKey;

				$now = gmdate(DATE_ATOM);
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

			private function generateAuthSig($clientId, $clientSecret, $privateKey, $now) 
			{
				$privateKey = "-----BEGIN RSA PRIVATE KEY-----".PHP_EOL.$privateKey.PHP_EOL."-----END RSA PRIVATE KEY-----";
				$hash = $clientId . '|' . $now;
				$signature = '';

				if (openssl_sign($hash, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
					return base64_encode($signature);
				} else {
					throw new Exception('Unable to sign data.');
				}
			}

			/**
			 * Initialize Gateway Settings Form Fields
			 */
			public function init_form_fields() 
			{

				$this->form_fields = apply_filters( 'wc_netzmeqr_form_fields', array(

					'enabled' => array(
						'title'   => esc_html(__( 'Enable/Disable', 'netzme-qris-payment' )),
						'type'    => 'checkbox',
						'label'   => esc_html(__( 'Enable Netzme QRIS Payment', 'netzme-qris-payment' )),
						'default' => 'yes'
					),
					'title' => array(
						'title'       => esc_html(__( 'Title', 'netzme-qris-payment' )),
						'type'        => 'text',
						'description' => esc_html(__( 'This controls the title for the payment method the customer sees during checkout.', 'netzme-qris-payment' )),
						'default'     => esc_html(__( 'QRIS Netzme Payment', 'netzme-qris-payment' )),
						'desc_tip'    => true,
					),
					'baseUrl' => array(
						'title'       => esc_html(__( 'Base Url', 'netzme-qris-payment' )),
						'type'        => 'text',
						'description' => esc_html(__( 'Base Url', 'netzme-qris-payment' )),
						'default'     => esc_html(__( 'https://tokoapi-stg.netzme.com', 'netzme-qris-payment' )),
						'desc_tip'    => true,
					),
					'payBaseUrl' => array(
						'title'       => esc_html(__( 'Pay Base Url', 'netzme-qris-payment' )),
						'type'        => 'text',
						'description' => esc_html(__( 'Pay BaseUrl.', 'netzme-qris-payment' )),
						'default'     => esc_html(__( 'https://pay-stg.netzme.com', 'netzme-qris-payment' )),
						'desc_tip'    => true,
					),
					'clientId' => array(
						'title'       => esc_html(__( 'Client Id', 'netzme-qris-payment' )),
						'type'        => 'text',
						'description' => esc_html(__( 'clientId.', 'netzme-qris-payment' )),
						'desc_tip'    => true,
					),
					'clientSecret' => array(
						'title'       => esc_html(__( 'Client Secret', 'netzme-qris-payment' )),
						'type'        => 'text',
						'description' => esc_html(__( 'clientSecret.', 'netzme-qris-payment' )),
						'desc_tip'    => true,
					),
					'privateKey' => array(
						'title'       => esc_html(__( 'Private Key', 'netzme-qris-payment' )),
						'type'        => 'textarea',
						'description' => esc_html(__( 'privateKey.', 'netzme-qris-payment' )),
						'desc_tip'    => true,
					),
					'channelID' => array(
						'title'       => esc_html(__( 'Channel ID', 'netzme-qris-payment' )),
						'type'        => 'text',
						'description' => esc_html(__( 'channelID.', 'netzme-qris-payment' )),
						'desc_tip'    => true,
					),
					'merchantId' => array(
						'title'       => esc_html(__( 'Merchant Id', 'netzme-qris-payment' )),
						'type'        => 'text',
						'description' => esc_html(__( 'merchantId.', 'netzme-qris-payment' )),
						'desc_tip'    => true,
					),
					'terminalId' => array(
						'title'       => esc_html(__( 'Terminal Id', 'netzme-qris-payment' )),
						'type'        => 'text',
						'description' => esc_html(__( 'terminalId.', 'netzme-qris-payment' )),
						'desc_tip'    => true,
					),
					'expiredTime' => array(
		                'title'       => esc_html(__( 'QR Expired Time (Minutes)', 'netzme-qris-payment' )),
		                'type'        => 'text',
		                'description' => esc_html(__( 'QR Expired Time (Minutes).', 'netzme-qris-payment' )),
		                'default'     => esc_html(__( '1440', 'netzme-qris-payment' )),
		                'desc_tip'    => true,
		            ),
					'feeType' => array(
						'title'       => esc_html(__( 'Fee Type', 'netzme-qris-payment' )),
						'type'        => 'select',
						'description' => esc_html(__( 'Fee Type.', 'netzme-qris-payment' )),
						'default'     => esc_html(__( 'on_buyer', 'netzme-qris-payment' )),
						'desc_tip'    => true,
						'options' => array(
							'on_buyer' => 'on_buyer',
							'on_seller' => 'on_seller'
					   )
					),              
					'commissionPercentage' => array(
						'title'       => esc_html(__( 'Commission Percentage', 'netzme-qris-payment' )),
						'type'        => 'text',
						'description' => esc_html(__( 'Commission Percentage. Example: 0.7', 'netzme-qris-payment' )),
						'default'     => esc_html(__( '0.0', 'netzme-qris-payment' )),
						'desc_tip'    => true,
					),

					'PaymentMethode' => array(
						'title'       => esc_html(__( 'Payment Methode', 'netzme-qris-payment' )),
						'type'        => 'multiselect',
						'description' => esc_html(__( 'Payment Methode', 'netzme-qris-payment' )),
						'default'     => esc_html(__( 'qris', 'netzme-qris-payment' )),
						'desc_tip'    => true,
						'options' => array(
							'QRIS' => 'QRIS'
					   )
					)
				) );
			}

			/**
			 * Output for the order received page.
			 */
			public function thankyou_page() 
			{
			
			}

			/**
			 * generate random string
			 */
			function generate_ref_string($length = 10) {
			    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
			    $charactersLength = strlen($characters);
			    $randomString = '';

			    for ($i = 0; $i < $length; $i++) {
			        $randomString .= $characters[random_int(0, $charactersLength - 1)];
			    }

			    return $randomString;
			}

			/**
			 * generate random integer
			 */
			function generate_rand_order()
			{
				$int = wp_rand(10,10000);
				return $int;
			}

			/**
			 * Output for the order received page.
			 * @param int $order_id
			 */
			function receipt_page($order_id)
			{
				global $woocommerce;
				$order = new WC_Order( $order_id );
				if ( $order->get_status() == 'processing' || $order->get_status() == 'completed') {
					wp_redirect( $order->get_checkout_order_received_url() );
					exit();
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
				if ( 0 < sizeof( $order->get_items() ) ) {
					foreach ( $order->get_items() as $item ) {
						if ( $item['qty'] ) {
							$item_name = htmlspecialchars($item['name']);
							$desc .= $item['qty'] .' x '. $item_name . ', ';
						}
					}
					$desc = substr($desc, 0, -2 );
				}
				$generate_ref_string = $this->generate_ref_string();
				$refNo  = date_format($order->get_date_created(), "Ymd") .$generate_ref_string. "-" . $order_id;
				$expiredTime = intval($this->expiredTime)*60;
				$invoice_array = [
					"custIdMerchant" => $this->merchantId,
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
					"terminalId" => $this->terminalId,
					"commissionPercentage" => $this->commissionPercentage,
					"expireInSecond" => "$expiredTime", 
					"feeType" => $this->feeType,
					"additionalInfo" => [
						"email" => $order->get_billing_email(),
						"notes" => $desc,
						"description" => $desc,
						"PhoneNumber" => $this->normalizePhoneNumber($order->get_billing_phone()),
						"imageUrl" => "",
						"fullname" => $order->get_billing_first_name().' '.$order->get_billing_last_name()
					]
				];

				$uri = '/api/v1.0/invoice/create-transaction';
				$api_url = $this->baseUrl . $uri;
				$body = wp_json_encode($invoice_array);
				$now = gmdate(DATE_ATOM);

				$ts = $this->getSnapToken();

				if (is_wp_error($ts)) {
				 	$cart_page_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : $woocommerce->cart->get_cart_url();
					include_once("error.php");
					return;
				}

				$clientId = $this->clientId;
				$clientSecret = $this->clientSecret;
				$privateKey = $this->privateKey;

				$sign = $this->generateServiceSignature($clientSecret, 'POST', $uri, $ts->accessToken, $invoice_array, $now);
				$randNumber = $this->generate_rand_order();
				$headers = [
					'Content-Type' => 'application/json',
					'CHANNEL-ID' => $this->channelID,
					'X-EXTERNAL-ID' => $order_id.$randNumber,
					'X-PARTNER-ID' => $this->clientId,
					'X-TIMESTAMP' => $now, 
					'Authorization' => $ts->tokenType .' ' . $ts->accessToken,
					'X-SIGNATURE' => $sign
				];

				$request = new WP_Http;
				$result = $request->request( $api_url , array( 'method' => 'POST', 'body' => $body, 'headers' => $headers ) );
				
				if (is_wp_error($result)) {
					$cart_page_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : $woocommerce->cart->get_cart_url();
					include_once("error.php");
					return;
				}

				$resp = json_decode($result['body']);

				if (is_wp_error($resp)) {
					$cart_page_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : $woocommerce->cart->get_cart_url();
					include_once("error.php");
					return;

				}

				$nzstatus = (isset($resp->additionalInfo->status)) ? $resp->additionalInfo->status : null;
				
				if (!$nzstatus) {
					$cart_page_url = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : $woocommerce->cart->get_cart_url();
					include_once("error.php");
					return;
					
				} else {
					$nzmessage      = $resp->responseMessage;
					$nzexpiredTs    = $resp->additionalInfo->expiredTs;
					$datenzexpiredTs = date_parse($nzexpiredTs);
					$nzterminalId   = $resp->additionalInfo->terminalId;
					$qrImage        = $resp->additionalInfo->qrImage;

					$order->set_transaction_id($resp->partnerReferenceNo);
					$order->update_meta_data('qr_image', $qrImage);
					$order->update_meta_data('terminal_id', $nzterminalId);
					$order->update_meta_data('expired_ts', $resp->additionalInfo->expiredTs);

					$order->save();

					WC()->mailer()->customer_invoice($order);
					WC()->cart->empty_cart();
					wc_reduce_stock_levels($order_id);
					include_once("qrpage.php");
				}
			}

			/**
			 * generate signature
			 * @param string $clientSecret
			 * @param string $method
			 * @param string $uri
			 * @param string $token
			 * @param string $body
			 * @param int $now
			 */

			function generateServiceSignature($clientSecret, $method, $uri, $token, $body, $now) 
			{
				$data = wp_json_encode($body);
				$signature = '';
				$data = "{$method}:{$uri}:{$token}:". hash('sha256', $data) .":{$now}";

				$signature = hash_hmac('sha512', $data, $clientSecret);

				return $signature;
			}

			/**
			 * normalize phone number
			 *
			 * @access private
			 * @param string $phoneNumber
			 */
			private function normalizePhoneNumber($phoneNumber) 
			{
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
			public function email_instructions( $order, $sent_to_admin, $plain_text = false ) 
			{

				if ( $this->instructions && ! $sent_to_admin && $this->id === $order->payment_method && $order->has_status( 'on-hold' ) ) {
					echo esc_html(wpautop( wptexturize( esc_html($this->instructions )) )) . PHP_EOL;
				}
			}

			/**
			 * Process the payment and return the result
			 *
			 * @param int $order_id
			 * @return array
			 */
			public function process_payment( $order_id ) 
			{

				$order = wc_get_order( $order_id );
				$orderkey = !method_exists($order, 'get_order_key') ? $order->order_key : $order->get_order_key();
				$orderid = !method_exists($order, 'get_id') ? $order->id : $order->get_id();

				return array(   'result'    => 'success',
				'redirect'  => add_query_arg('key', $orderkey, add_query_arg(array(
				'order-pay' => $orderid),
				$order->get_checkout_payment_url(true))));
			}

			/**
			 * check status
			 *
			 */
			public function checkstatus() 
			{
				$key = filter_input( INPUT_GET, 'key', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
				$order_id = $this->decrypt($key);
				$order = new WC_Order( $order_id );
				if ( $order->get_status() == 'processing' || $order->get_status() == 'completed' ) {
					echo esc_html("1");
					die();
				}
				echo esc_html("0");
			}

			/**
			 * handle callbacack
			 *
			 */
			function ipn_response()
			{
				global $woocommerce;

				$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
				if ($action == "checkstatus") {
					$this->checkstatus();
					die();
				}

				$data = json_decode(file_get_contents('php://input'), true);
				$partner_transaction_id = sanitize_text_field($data['originalPartnerReferenceNo']);
				$nzid = sanitize_text_field($data['id']);
				$nzmerchant = sanitize_text_field($data['merchant']);
				$nzpayment_time = sanitize_text_field($data['additionalInfo']['paymentTime']);
				$nzstatus = sanitize_text_field($data['latestTransactionStatus']);
				$nzamount = sanitize_text_field($data['netAmount']['value']);
				
				$splitReffNo = explode("-", $partner_transaction_id);

				$ReffNo_Date    = $splitReffNo[0];
				$ReffNo_Number  = $splitReffNo[1];
				
				$order = wc_get_order($ReffNo_Number);
			 
				if ($ReffNo_Number == "") {
					echo esc_html("Transaction not found");
					die();
				}
				
				if ($nzstatus != "00") {
					echo esc_html("Transaction already paid");
					die();
				}
				
				$order->add_order_note('The Order has been Paid by QRIS Netzme Payment.');
				$order->payment_complete();
				$order->update_status('processing');
				$order->save();

				WC()->mailer()->customer_invoice($order);
				
				$resp_array = array(
						"id" => "$order->id",
						"transaction_id" => "$partner_transaction_id",
						"status" => "$nzstatus",
						"merchant" => "$nzmerchant",
						"payment_time" => "$nzpayment_time"
				);
				echo wp_json_encode($resp_array);
				die();
			}
			
			/**
			 * encrypt
			 *
			 * @param string $data
			 */
			public function encrypt($data) 
			{
				$key = NETZME_APP_KEY;
				$plaintext = $data;
				$ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
				$iv = openssl_random_pseudo_bytes($ivlen);
				$ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, $options = OPENSSL_RAW_DATA, $iv);
				$hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary = true);
				$ciphertext = base64_encode($iv . $hmac . $ciphertext_raw);
				return $ciphertext;
			}

			/**
			 * decrypt
			 *
			 * @param string $data
			 */
			public function decrypt($data) 
			{
				$key = NETZME_APP_KEY;
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
}
