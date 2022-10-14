<?php
/*
 * Plugin Name: Fapshi Payments for WooCommerce
 * Plugin URI: https://github.com/Fapshi/Plugins
 * Description: Official WooCommerce payment gateway for Fapshi.
 * Author: Fapshi Developers
 * Author URI: http://fapshi.com
 * Version: 1.0.0
 * text-domain: woo-fapshi-payment
 */

if (!defined('ABSPATH')) {
  exit;
}

// Make sure WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

//if condition use to do nothin while WooCommerce is not installed

/**
 * Add the Settings link to the plugin
 *
 * @param  Array $links Existing links on the plugin page
 *
 * @return Array          Existing links with our settings link added
 */
function fapshi_plugin_action_links($links)
{

  $fapshi_settings_url = esc_url(get_admin_url(null, 'admin.php?page=wc-settings&tab=checkout&section=fapshi'));
  array_unshift($links, "<a title='Fapshi Settings Page' href='$fapshi_settings_url'>Settings</a>");

  return $links;

}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'fapshi_plugin_action_links');

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'fapshi_add_gateway_class' );
function fapshi_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Fapshi_Gateway'; // your class name is here
	return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'fapshi_init_gateway_class' );

function fapshi_init_gateway_class() {

	class WC_Fapshi_Gateway extends WC_Payment_Gateway {

 		//Class constructor
 		
 		public function __construct() {

        $plugin_dir = plugin_dir_url(__FILE__);

		    $this->id = 'fapshi'; // payment gateway plugin ID
        $this->icon = apply_filters( 'woocommerce_gateway_icon', $plugin_dir.'assets\img\options.png' );; // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = false; // in case you need a custom credit card form
        $this->method_title = 'Fapshi';
        $this->method_description = 'Accept credit cards, debit cards, Apple pay, Google pay and Mobile money payments.'; 

        $this->supports = array(
            'products'
        );

        // Method with all the options fields
        $this->init_form_fields();

        // Load settings.
        $this->init_settings();
        $this->title = $this->get_option( 'title' );
        $this->description = $this->get_option( 'description' );
        $this->enabled = $this->get_option( 'enabled' );
        $this->live_api_key = $this->get_option( 'live_api_key' );
        $this->live_api_user = $this->get_option( 'live_api_user' );
        $this->test_api_key = $this->get_option( 'test_api_key' );
        $this->go_live = $this->get_option( 'go_live' );
        $this->test_api_user = $this->get_option( 'test_api_user' );
        $this->webhook = $this->get_option( 'webhook' );
        $this->card = $this->get_option( 'card' );

        add_action( 'admin_notices', array( $this, 'admin_notices' ) );

        // This action hook saves the settings
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        
        // You can also register a webhook here
        add_action( 'woocommerce_api_fapshi_payment_webhook', array( $this, 'webhook' ) );
      }

 		public function init_form_fields(){

			$this->form_fields = array(
                'enabled' => array(
                    'title'       => __( 'Enable/Disable', 'woo-fapshi-payment' ),
                    'label'       => __( 'Enable Fapshi Payments', 'woo-fapshi-payment' ),
                    'type'        => __( 'checkbox', 'woo-fapshi-payment' ),
                    'description' => __( '', 'woo-fapshi-payment' ),
                    'default'     => __( 'no', 'woo-fapshi-payment' ),
                ),
                'title' => array(
                    'title'       => __( 'Title', 'woo-fapshi-payment' ),
                    'type'        => __( 'text', 'woo-fapshi-payment' ),
                    'description' => __( 'This controls the title which the user sees during checkout.', 'woo-fapshi-payment' ),
                    'default'     => __( 'Fapshi', 'woo-fapshi-payment' ),
                    'desc_tip'    => true,
                ),
                'description' => array(
                    'title'       => __( 'Description', 'woo-fapshi-payment' ),
                    'type'        => __( 'textarea', 'woo-fapshi-payment' ),
			              'description' => __( 'This controls the description which the user sees during checkout.', 'woo-fapshi-payment' ),
                    'default'     => __( 'Accept local and international payments with Fapshi.', 'woo-fapshi-payment' ),
                    'desc_tip'    => true,
                    'css' => __( 'max-width:400px;', 'woo-fapshi-payment' ),
                ),
                'go_live' => array(
                    'title'       => __( 'Mode', 'woo-fapshi-payment' ),
                    'label'       => __( 'Enable live mode', 'woo-fapshi-payment' ),
                    'type'        => __( 'checkbox', 'woo-fapshi-payment' ),
                    'description' => __( 'Check this box if you\'re using your live credentials.', 'woo-fapshi-payment' ),
                    'default'     => __( 'yes', 'woo-fapshi-payment' ),
                  ),
                'test_api_user' => array(
                    'title'       => __( 'Test API User', 'woo-fapshi-payment' ),
                    'type'        => __( 'text', 'woo-fapshi-payment' ),
                ),
                'test_api_key' => array(
                    'title'       => __( 'Test API Key', 'woo-fapshi-payment' ),
                    'type'        => __( 'password', 'woo-fapshi-payment' ),
                ),
                'live_api_user' => array(
                    'title'       => __( 'Live API User', 'woo-fapshi-payment' ),
                    'type'        => __( 'text', 'woo-fapshi-payment' ),
                ),
                'live_api_key' => array(
                    'title'       => __( 'Live API Key', 'woo-fapshi-payment' ),
                    'type'        => __( 'password', 'woo-fapshi-payment' ),
                ),
                'webhook' => array(
                    'title'       => __( 'Webhook Instruction', 'woo-fapshi-payment' ),
                    'type'        => __( 'hidden', 'woo-fapshi-payment' ),
                    'description' => __( 'Please copy this webhook URL and paste on the webhook section on your dashboard <strong style="color: red"><pre><code>'.WC()->api_request_url('fapshi_payment_webhook').'</code></pre></strong> (<a href="https://dashboard.fapshi.com/" target="_blank">Fapshi Account</a>)', 'woo-fapshi-payment' ),
                ),
                'card' => array(
                    'title'       => __( 'Disable mobile money payments', 'woo-fapshi-payment' ),
                    'label'       => __( 'Accept just card payments', 'woo-fapshi-payment' ),
                    'type'        => __( 'checkbox', 'woo-fapshi-payment' ),
                    'description' => __( 'Check this box if you want to disable mobile money payments.', 'woo-fapshi-payment' ),
                    'default'     => __( 'no', 'woo-fapshi-payment' ),
                  ),
            );
	 	}

    public function admin_notices() {

      if ( 'no' == $this->enabled ) {
        return;
      }
      
      //Check if live api key is provided
      
      if ( ! $this->live_api_user || ! $this->live_api_key ) {
        echo '<div class="error"><p>';
        echo sprintf(
          'Provide atleast your API test user and test key <a href="%s">here</a> to be able to use the Fapshi Payment Gateway plugin.',
           admin_url( 'admin.php?page=wc-settings&tab=checkout&section=fapshi' )
         );
        echo '</p></div>';
        return;
      }
    }

    public function process_payment($order_id){

     global $woocommerce;
     $order = new WC_Order( $order_id );

     $header_args = array(
        'Content-Type' => 'application/json',
        'apiuser' => $this->live_api_user,
        'apikey' => $this->live_api_key
      );

      $test_header_args = array(
        'Content-Type' => 'application/json',
        'apiuser' => $this->test_api_user,
        'apikey' => $this->test_api_key
      );

      $endpoint = 'https://live.fapshi.com/initiate-pay';
      $test_endpoint = 'https://sandbox.fapshi.com/initiate-pay';

      $amount = intval($order->get_total());
      $currency = $order->get_currency();

      $is_card = $this->card == 'yes' ? true : false;

      $res_rates = wp_remote_get( 'https://api.fapshi.com/rates' );
      $res_decode = $res_rates['body'];
      $rates_data = json_decode($res_decode);
      $rate = $rates_data->rates->dollarRate;

      if($currency == 'USD' || $currency == 'XAF'){
        
        switch ($currency) {
              case 'USD':
                $amount = $amount*$rate;
                break;
              
              default:
                $amount = intval($order->get_total());
                break;
          }

        if($amount < 0)
        throw new Exception('Amount should be greater than 0');

        $body = array (
          "amount" => intval($amount),
          "email" => $order->get_billing_email(),
          "currency" => $order->get_currency(),
          "userId" => $order->get_user_id(),
          "externalId" => $order->get_id(),
          "redirectUrl" => $this->get_return_url($order),
          "cardOnly" => $is_card
        );

        $body = json_encode($body, JSON_UNESCAPED_SLASHES);

        $options = [
          'body'        => $body,
          'headers'     => $header_args,
          'timeout'     => 60,
          'redirection' => 5,
          'blocking'    => true,
          'httpversion' => '1.0',
          'sslverify'   => false,
          'data_format' => 'body',
        ];


        $test_options = [
          'body'        => $body,
          'headers'     => $test_header_args,
          'timeout'     => 60,
          'redirection' => 5,
          'blocking'    => true,
          'httpversion' => '1.0',
          'sslverify'   => false,
          'data_format' => 'body',
        ];
          
        if ($this->go_live == 'no' && $this->test_api_user && $this->test_api_key) {
          $response = wp_remote_post( $test_endpoint, $test_options );
        }

        elseif($this->go_live == 'yes' && $this->live_api_user && $this->live_api_key){
          $response = wp_remote_post( $endpoint, $options );
        }

        else {
          throw new Exception('This payment method has not been configured correctly, Contact support or try a different payment method.');
        }

        if ( is_wp_error( $response ) ) 
        throw new Exception('There is issue with connecting to the payment gateway. Sorry for the inconvenience.');

        if ( empty( $response['body'] ) )
        throw new Exception('There is an issue with processing payments. Check your internet connection.');

        $decoded_res = $response['body'];
        $response_data = json_decode($decoded_res, true);

        $link = $response_data['link'];

        // Mark as on-hold (we're awaiting the payment)
        $order->update_status('processing', __( 'Awaiting payment', 'woo-fapshi-payment' ));

        // notes to customer
        $order->add_order_note( 'Hey, your order is pending payment!', true ); 

        // redirect to fapshi's checkout page
        return array(
          'result' => 'success',
          'redirect' => $link
        );    
      }
      else {
        throw new Exception('Currency not supported, try a different payment method.');
      }
    
  }
		
		public function webhook() {
            
      $body = @file_get_contents("php://input");
      $response = json_decode($body, true);
      $response_data = $response->data;

      global $woocommerce;

      $status = $response_data->status;
      $id = $response_data->externalId;
      $order = wc_get_order( $id );

      if($status == 'SUCCESSFUL'){
    
       // we received the payment
       $order->payment_complete();
       $order->reduce_order_stock();

       // Mark as on-hold (payment completed)
       $order->update_status('completed', __( 'Payment completed', 'woo-fapshi-payment' ));
 
       $order->add_order_note( 'Payment has been completed.', true );
 
       // empty cart
       $woocommerce->cart->empty_cart();
	 	}
    else{
             $order->update_status('failed', __( 'Payments failed', 'woo-fapshi-payment' ));
    }
  }
 }
}
