<?php
/**
 * Plugin Name: WP Debug Logging
 * Description: Enable PHP error logging without needing to edit the wp-config file.
 * Author: Caleb Burks
 * Author URI: https://calebburks.com
 * Version: 0.1.0
 * License: GPLv3 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Debug_Logging' ) ) :

/**
 * Main Class.
 */
class WP_Debug_Logging {

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin.
	 */
	protected function __construct() {
		$this->log_errors();

		if ( ! defined( 'WP_DEBUG_LOGGING_PLUGIN_BASENAME' ) ) {
			define( 'WP_DEBUG_LOGGING_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}

		if ( is_admin() ) {
			add_action( 'init', array( $this, 'load_admin' ) );
		}
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Log errors.
	 */
	public function log_errors() {
		error_reporting( E_ALL );
		ini_set( 'log_errors', 1 );
		ini_set( 'error_log', WP_CONTENT_DIR . '/debug.log' );

		// Don't want errors displaying on the frontend unless otherwise configured.
		if ( ! WP_DEBUG || ! WP_DEBUG_DISPLAY ) {
			ini_set( 'display_errors', 0 );
		}
	}

	/**
	 * Load the admin class.
	 */
	public function load_admin() {
		include_once dirname( __FILE__  ) . '/wp-debug-logging-admin.php';
	}

} // WP_Debug_Logging

add_action( 'plugins_loaded', array( 'WP_Debug_Logging', 'get_instance' ) );

endif; // class_exists()
