<?php

/**
 * Fired during plugin uninstall
 *
 */

class Freebee_Il_Uninstaller {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function uninstall() {
		self::delete_plugin_database_setup('freebee_il_client_id');
		self::delete_plugin_database_setup('freebee_il_auth_code');
		
		$logs_folder = wp_upload_dir()['basedir'].'/woocommerce-freebee-integration';

		if (file_exists($logs_folder)){
			self::remove_files_from_folder($logs_folder);
		}
	}

	private static function delete_plugin_database_setup($field_name){
		delete_option($field_name);
		delete_site_option($field_name);
	}

	private static function remove_files_from_folder($folder){
		$files = glob($folder."/*");
		if (!empty($files)){
			foreach($files as $file){ // iterate files
			  if(is_file($file))
			    unlink($file); // delete file
			}
		}
		rmdir($folder);
	}
}