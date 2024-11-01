<?php

/**
 * The public-facing functionality of the plugin.
 *
 */

class Freebee_Il_Public {

	/**
	 * The ID of this plugin.
	 *
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 */
	private $version;

	/**
	 * The name of this plugin cookie for API connection.
	 *
	 */
	private $cookie_name;

	/**
	 * Initialize the class and set its properties.
	 *
	 */
	public function __construct( $plugin_name, $version ){
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->cookie_name = 'freebee_il_customer_cookie';

		$this->loader = new Freebee_Il_Loader();
	}

	/**
	 * Core admin-side function, responsible for igniting functions.
     *
	 */
	public function run_all_features(){
		if (!is_admin()){
			$this->add_cookie_to_visitor_redirected_from_freebee();
			$this->add_input_to_woocommerce_checkout();
			$this->run_hook_loader();
            add_action( 'wp_enqueue_scripts', array( $this, 'freebee_il_enqueue_scripts' ) );
		}
	}

	private function add_cookie_to_visitor_redirected_from_freebee(){
		if (isset($_GET["customer_id"])){
			$freebee_visitor_id = $_GET['customer_id'];
			if ($freebee_visitor_id && strlen($freebee_visitor_id) == 9) {
				$this->save_visitors_cookie($freebee_visitor_id);
			}
		}
	}

	private function save_visitors_cookie($freebee_id = false){
		if ($freebee_id){
			setcookie($this->get_cookie_name(), $freebee_id, time() + (86400 * 30), "/");
		}
	}

	private function add_input_to_woocommerce_checkout(){
		add_action('woocommerce_after_order_notes', array($this, 'freebee_il_id_checkout_field'));
		add_action('woocommerce_checkout_process', array($this, 'freebee_il_id_checkout_field_process'));	
		add_action('woocommerce_checkout_update_order_meta', array($this, 'freebee_il_id_checkout_field_update_order_meta'));
	}

	public function freebee_il_id_checkout_field( $checkout ){
		if ($this->check_api_validation_response() == true){
			echo '<div class="freebee-il-checkout-input"><h1>'.__('Personal ID MAM: ', 'freebee-il').'</h1>';
			$client_nvm_id = $this->get_visitors_freebee_id();
		 
		    woocommerce_form_field( 'freebee_il_customer_card_number', array(
		        'type'          => 'text',
		        'class'         => array('input-text'),
		        'label'         => '<h2>'.__('Insert Your client ID MAM.', 'freebee-il').'</h2>',
		        'required'  	=> false,
		        ), $client_nvm_id);
		 
	    echo '</div>';
		}
	}

	private function get_visitors_freebee_id(){
	    if (empty($_COOKIE[$this->get_cookie_name()])){
            return '';
        }

		if (is_user_logged_in()){
			$client_nvm_id = get_user_meta(get_current_user_id(), 'freebee_il_customer_card_number', true);
			if (! $client_nvm_id){
				$client_nvm_id = $_COOKIE[$this->get_cookie_name()];
			}
		} else {
			$client_nvm_id = $_COOKIE[$this->get_cookie_name()];
		}
		return $client_nvm_id;
	}

	private function check_api_validation_response(){
        $api_test = new Freebee_Il_API();
        $response = $api_test->get_authorization_token();
        if ($response){
        	$response = true;
        } else {
        	$response = false;
        }
        return $response;
    }
	
	public function freebee_il_id_checkout_field_process(){
		$field = $_POST['freebee_il_customer_card_number'];
	    if (strlen($field) !== 9 && strlen($field) !== 0){
	    	 wc_add_notice( __('Your ID MAM must consist 9 digits.', 'freebee-il'), 'error' );
	    }
	}
	 
	public function freebee_il_id_checkout_field_update_order_meta( $order_id ){
	    if ($_POST['freebee_il_customer_card_number']){
	    	update_post_meta( $order_id, 'freebee_il_customer_card_number', esc_attr($_POST['freebee_il_customer_card_number']));
	    	$user_id = get_post_meta($order_id, '_customer_user', true);
	    	if ($user_id){
	    		update_user_meta($user_id, 'freebee_il_customer_card_number', esc_attr($_POST['freebee_il_customer_card_number']));
	    	}
	    }
	}

	public function get_cookie_name(){
		return $this->cookie_name;
	}

	private function run_hook_loader(){
		$this->loader->run();
	}

	public function freebee_il_enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/freebee-public.js', array( 'jquery' ), $this->version, false );
		add_filter( 'script_loader_tag' , array( $this, 'freebee_il_async_js_scripts' ) );
	}

	public function freebee_il_async_js_scripts($tag){
		$scripts_to_async = array('js/freebee-public.js');

		foreach ($scripts_to_async as $async_script){
			if (true == strpos($tag, $async_script ) )
			return str_replace( ' src', ' async="async" src', $tag );	
		}
		return $tag;
	}
}