<?php

/**
 * The core plugin class.
 *
 */

class Freebee_Il {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
     *
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
     *
	 */
	protected $version;

	/**
	 * Path to main plugin file, needed for some hooks.
     *
	 */
	protected $main_plugin_file;

	public function __construct(){
		$this->plugin_name = 'woocommerce-freebee-integration';
		$this->version = '1.0.1';
	}

	/**
	 * Core plugin starting function
     *
	 */
	public function load_plugin($file){
		$this->set_plugin_main_file($file);
		$this->enable_plugin_if_woocommerce_is_active();
	}

	/**
	 * Set path for main plugin file
     *
	 */
	private function set_plugin_main_file($file){
		$this->main_plugin_file = plugin_basename($file);
	}

	private function enable_plugin_if_woocommerce_is_active(){
		//Active WooCommerce
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ){
			$this->load_plugin_hooks();
		} else {
			add_action( 'admin_notices', array($this, 'absent_woocommerce_error_notice' ));
		}
	}

	/**
	 * Load all plugin components
     *
	 */
	private function load_plugin_hooks(){
		$this->load_dependencies();
		$this->set_locale();
        $this->set_wc_api_connector();
		$this->run_hook_loader();

		$this->activate_plugins_admin_side();
		$this->activate_plugins_public_side();
		$this->add_plugin_shortcuts();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 */
	private function load_dependencies(){

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-freebee-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-freebee-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-freebee-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-freebee-public.php';

        /**
         * The class responsible for communication with Freebee API
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-freebee-api.php';

        /**
         * The class responsible for core functions
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-freebee-wc-api-connector.php';

        $this->loader = new Freebee_Il_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 */
	private function set_locale(){
		$plugin_i18n = new Freebee_Il_i18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

    /**
     * Run the core to execute all api functionality
     *
     */
    private function set_wc_api_connector(){
        $plugin_core = new Freebee_Il_WC_API_Connector();
    }

    /**
	 * Run the loader to execute all of the hooks with WordPress.
     *
	 */
	public function run_hook_loader(){
		$this->loader->run();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 */
	private function activate_plugins_admin_side(){
		$plugin_admin = new Freebee_Il_Admin( $this->get_plugin_name(), $this->get_version() );
		$plugin_admin->run_all_features();
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
     *
	 */
	private function activate_plugins_public_side(){
		$plugin_public = new Freebee_Il_Public( $this->get_plugin_name(), $this->get_version() );
		$plugin_public->run_all_features();
	}

	/**
	 * Function responsible for adding shortcuts to plugin in admin view at /plugins.php.
	 */
	private function add_plugin_shortcuts(){
		add_filter( 'plugin_action_links_' . $this->main_plugin_file , array( $this, 'add_plugin_links' ) );
	}

	public function add_plugin_links($links){ 
		$author_link = '<a href="https://inspirelabs.pl">'.__("Author", "freebee-il").'</a>';
		$settings_link = '<a href="admin.php?page=freebee_il_options">'.__("Settings", "freebee-il").'</a>'; 

		array_unshift($links, $author_link);
		array_unshift($links, $settings_link); 

		return $links; 
	}

	public function absent_woocommerce_error_notice(){
	    $class = 'notice notice-error';
		$message = __('Freebee', 'freebee-il').' - '.__( 'integration requires activate WooCommerce plugin to work.', 'freebee-il' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 */
	public function get_plugin_name(){
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 */
	public function get_version(){
		return $this->version;
	}
}