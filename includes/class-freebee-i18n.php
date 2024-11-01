<?php

/**
 * Define the internationalization functionality
 *
 */

class Freebee_Il_i18n {

	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'freebee-il',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}