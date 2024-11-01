<?php

/**
 * Fired during plugin activation
 *
 */
class Freebee_Il_Activator {

	private static $plugin_folder_in_uploads;

	public static function activate() {
		self::$plugin_folder_in_uploads = wp_upload_dir()['basedir'].'/woocommerce-freebee-integration';
		set_transient( 'freebee-il-plugin-start', true, 5 );

		if (! file_exists(self::$plugin_folder_in_uploads)){
			wp_mkdir_p(self::$plugin_folder_in_uploads);
			chmod(self::$plugin_folder_in_uploads, 0777);
		}
	}
}