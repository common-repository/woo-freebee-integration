<?php

/**
 * The admin-specific functionality of the plugin.
 *
 */

class Freebee_Il_Admin {

	private $plugin_name;
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$this->loader = new Freebee_Il_Loader();
	}

	/**
	 * Main handler for plugin, initializes all admin side functions
     *
	 */
	public function run_all_features() {
		if (is_admin()) {
			$this->display_initial_api_notice();
			$this->add_options_settings();
			$this->enqueue_scripts_and_styles();
			$this->add_meta_box_to_order_page();
			$this->run_hook_loader();
		}
	}

	/**
	 * Add notice from display_initial_notice_content to admin_notices hook
     *
	 */
	private function display_initial_api_notice(){
		$api_test = new Freebee_Il_API();
        $response = $api_test->get_authorization_token();

        if (!$response){
			$this->loader->add_action('admin_notices', $this, 'initial_api_notice_content' );
		}
    }

	public function initial_api_notice_content(){
	    if( get_transient( 'freebee-il-plugin-start' ) ){ 
	        $class = 'notice notice-warning is-dismissible';
			$message = __('Freebee', 'freebee-il').' - '.__( 'You have to add Your Freebee user data in order for this plugin to work.', 'freebee-il' ).' ';
			$message.= '<a class="freebee-options-link" href="/wp-admin/admin.php?page=freebee_il_options">'.__( 'Options page.', 'freebee-il').'</a>';
			
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message ); 
			delete_transient( 'freebee-il-plugin-start' );
	    }
    }

	/**
	 * Options page setup
     *
	 */
	private function add_options_settings() {
        add_action('admin_menu', array( $this, 'options_page' ));
        add_action('admin_init', array( $this, 'options_inputs_setup'));
	}

    public function options_page() {
        add_menu_page(
            __( 'Freebee', 'freebee-il' ),
            __( 'Freebee Options', 'freebee-il' ),
            'manage_options',
            'freebee_il_options',
            array(
                $this,
                'options_page_html'
            ),
            '',
            20
        );
    }

    public function options_page_html() { ?>
        <form method="post" action="options.php">
		<?php
		    settings_fields("freebee_il_integration");
		    do_settings_sections("freebee_il_options");

		    $this->display_submit_and_logs_buttons();
		?> 
        </form>
    <?php }

	private function display_submit_and_logs_buttons() { ?>
		<table class="form-table freebee-form">
			<tr>
				<th>
					<?php submit_button( __( 'Save Settings', 'freebee-il' ) ); ?>
				</th>
				<td><?php $this->display_redirects_to_logs(); ?></td>
	    	</tr>
	    </table>
	<?php }

	private function display_redirects_to_logs(){ 
		$log_file_ok_path = '/wp-content/uploads/woocommerce-freebee-integration/freebee_il_ok.log';
		$log_file_error_path = '/wp-content/uploads/woocommerce-freebee-integration/freebee_il_error.log';
		?>
		<div class="freebee-il-log-redirects"><p>
			<a href="<?php echo $log_file_ok_path; ?>" class="button button-primary"><?php _e( 'Success logs', 'freebee-il' ) ?></a>
			<a href="<?php echo $log_file_error_path; ?>" class="button button-primary"><?php _e( 'Failure logs', 'freebee-il' ) ?></a>
		</p></div>
	<?php }


    public function options_inputs_setup() {
    	$id_name = __( 'MAM ID', 'freebee-il' );
    	$api_token = __( 'Unique API Token', 'freebee-il' );

    	add_settings_section("freebee_il_integration", "Freebee Api Connect", array( $this, "display_options_header"), "freebee_il_options");

        add_settings_field("freebee_il_client_id", $id_name, array( $this, "display_shop_id_input"), "freebee_il_options", "freebee_il_integration");
        add_settings_field("freebee_il_auth_code", $api_token, array( $this, "display_shop_token_input"), "freebee_il_options", "freebee_il_integration");

        register_setting("freebee_il_integration", "freebee_il_client_id");
        register_setting("freebee_il_integration", "freebee_il_auth_code");
    }

    public function display_options_header() {
		echo __( 'Insert Your Api ID number and secret key', 'freebee-il' );
		$this->display_api_response_box();
	}

	private function display_api_response_box(){ ?>
		<div class="api-response">
			<?php $this->display_api_validation_response(); ?>
		</div>
	<?php }

	private function display_api_validation_response(){
        $api_test = new Freebee_Il_API();
        $response = $api_test->get_authorization_token();

        if ($response){
        	echo '<p class="valid">'.__('Your data is valid, integration with freebee is working.', 'freebee-il').'</p>';
        } else {
        	echo '<p class="invalid">'.__("Something is wrong with connection, try updating fields.", 'freebee-il').'</p>';
        }
	}

	public function display_shop_id_input() { ?>
        <td>
        	<input type="text" name="freebee_il_client_id" value="<?php echo esc_attr( get_option('freebee_il_client_id') ); ?>" />
        </td>
	<?php }

	public function display_shop_token_input() { ?>
        <td>
        	<input type="text" name="freebee_il_auth_code" value="<?php echo esc_attr( get_option('freebee_il_auth_code') ); ?>"/>
        </td>
	<?php }

	private function enqueue_scripts_and_styles() {
		$this->loader->add_action( 'admin_enqueue_scripts', $this, 'enqueue_admin_styles' );
	}

	public function enqueue_admin_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/freebee-admin.css', array(), $this->version, 'all' );
	}

	private function add_meta_box_to_order_page(){
		$this->loader->add_action('add_meta_boxes', $this, 'display_transaction_id_meta_box');
	}

	public function display_transaction_id_meta_box(){
	    add_meta_box( 
	        'freebee-il-transaction-id', 
	        __( 'Freebee transaction ID', 'freebee-il' ), 
	        array($this, 'display_transaction_id_value'), 
	        'shop_order', 
	        'side', 
	        'default' 
	    );
	}

	public function display_transaction_id_value(){
		global $post;
		$id = get_post_meta($post->ID, 'freebee_il_transaction_id', true);
		$error_occurred = get_post_meta($post->ID, 'freebee_il_transaction_error', true);

		if ($error_occurred){
			$result = '<p class="freebee-il-transaction-error">'.$error_occurred.'</p>';
		} else {
			if (!$id){
				$result = __('Freebee integration not used', 'freebee-il');
			} else {
				$result = $id;
			}
		}

		echo $result;		
	}

	private function run_hook_loader() {
		$this->loader->run();
	}
}
