<?php
/**
 * WP Debug Logging Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Debug_Logging_Admin Class.
 *
 * Adds a setting's screen with a view for displaying the log.
 */
class WP_Debug_Logging_Admin {

	/**
	 * Location of the debug file on the server.
	 *
	 * @var string
	 */
	protected static $debug_file = WP_CONTENT_DIR . '/debug.log';

	/**
	 * The admin page for this plugin.
	 *
	 * @var string
	 */
	protected static $admin_page = 'tools.php?page=wp-debug-logging';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'plugin_action_links_' . WP_DEBUG_LOGGING_PLUGIN_BASENAME, array( $this, 'plugin_action_links' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_option' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add an action link on the plugins screen.
	 *
	 * @param  array $link An array of plugin action links.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( self::$admin_page ) . '">' . esc_html__( 'View Debug Log', 'wp-debug-logging' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	/**
	 * Enqueue admin CSS and JS.
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();

		if ( 'tools_page_wp-debug-logging' === $screen->id ) {
			wp_enqueue_style( 'wp-debug-logging-styles', plugins_url( '/assets/admin-styles.css' , __FILE__ ), false, '0.1' );

			wp_enqueue_script( 'wp-debug-logging-script', plugins_url( '/assets/admin.js' , __FILE__ ), false, '0.1' );
			wp_localize_script(
				'wp-debug-logging-script',
				'wp_debug_logging_admin',
				array(
					'delete_log_confirmation' => esc_js( __( 'Are you sure you want to delete the debug log?', 'wp-debug-logging' ) ),
				)
			);
		}
	}

	/**
	 * Add menu option underneath "Tools".
	 */
	public function add_menu_option() {
		add_submenu_page( 'tools.php', __( 'WP Debug Logging', 'wp-debug-logging' ), __( 'Debug Log', 'wp-debug-logging' ), 'install_plugins', 'wp-debug-logging', array( $this, 'display_screen' ) );
	}

	/**
	 * Display the admin screen.
	 */
	public function display_screen() {
		$request = false;

		if ( ! empty( $_REQUEST['delete_log'] ) ) {
			$request = $this->delete_log();
		} else if ( ! empty( $_REQUEST['add_to_log'] ) ) {
			$request = $this->add_to_log();
		}
		?>
			<div class="wrap">
				<h1>
					<?php esc_html_e( 'WP Debug Log', 'wp-debug-logging' ); ?>
					<a class="page-title-action delete-log" href="<?php echo esc_url( wp_nonce_url( admin_url( self::$admin_page ), 'delete_log', 'delete_log' ) ); ?>" class="button"><?php esc_html_e( 'Delete log', 'wp-debug-logging' ); ?></a>
					<a class="page-title-action" href="<?php echo esc_url( wp_nonce_url( admin_url( self::$admin_page ), 'add_to_log', 'add_to_log' ) ); ?>" class="button"><?php esc_html_e( 'Add test entry to log', 'wp-debug-logging' ); ?></a>
				</h1>
				<?php if ( is_array( $request ) ) : ?>
					<div class="<?php echo esc_attr( $request['status'] ); ?> notice is-dismissible">
						<p><?php echo esc_html( $request['message'] ); ?></p>
						<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice' ); ?></span></button>
					</div>
				<?php endif; ?>
				<div id="log-viewer">
					<pre><?php echo esc_html( $this->get_log() ); ?></pre>
				</div>
			</div>
		<?php
	}

	/**
	 * Get the log file.
	 *
	 * @return string Debug file contents, or message about there not being a debug file.
	 */
	public function get_log() {
		if ( ! is_file( self::$debug_file ) ) {
			return __( 'There is currently no debug file. Either it cannot be accessed or no errors have occured since enabling this plugin. You can try adding a test entry to the log to make sure everything is working.', 'wp-debug-logging' );
		}

		try {
			$file_contents = file_get_contents( self::$debug_file );
		} catch( Exception $e ) {
			$file_contents = $e->getMessage();
		}

		return $file_contents;
	}

	/**
	 * Add a test entry to the log.
	 *
	 * @return array An associative array containing the status of the request and a message for the user.
	 */
	public function add_to_log() {
		if ( empty( $_REQUEST['add_to_log'] ) || ! wp_verify_nonce( $_REQUEST['add_to_log'], 'add_to_log' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'wp-debug-logging' ) );
		}

		trigger_error( esc_html( 'This is a test PHP notice. It was purposely triggered by the WP Debug Logging plugin', 'wp-debug-logging' ) );

		return array(
			'status' => 'updated',
			'message' => __( 'A test error was triggered. Check to see if it was added to the log.', 'wp-debug-logging' ),
		);
	}

	/**
	 * Delete the log file.
	 *
	 * @return array An associative array containing the status of the deletion and a message for the user.
	 */
	public function delete_log() {
		if ( empty( $_REQUEST['delete_log'] ) || ! wp_verify_nonce( $_REQUEST['delete_log'], 'delete_log' ) ) {
			wp_die( __( 'Action failed. Please refresh the page and retry.', 'wp-debug-logging' ) );
		}

		$file    = self::$debug_file;
		$deleted = false;

		if ( is_file( $file ) && is_writable( $file ) ) {

			// Make sure file is closed.
			if ( is_resource( $file ) ) {
				fclose( $file );
			}

			// Delete the file.
			$deleted = unlink( $file );
		}

		return array(
			'status' => $deleted ? 'updated' : 'error',
			'message' => $deleted ? __( 'Debug log was deleted.', 'wp-debug-logging' ) : __( 'Debug log could not be deleted.', 'wp-debug-logging' ),
		);
	}

}

new WP_Debug_Logging_Admin();
