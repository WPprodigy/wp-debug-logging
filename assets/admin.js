/* global jQuery, wp_debug_logging_admin */
jQuery( function ( $ ) {
	$( '.tools_page_wp-debug-logging' ).on( 'click', 'h1 a.delete-log', function( e ) {
		e.stopImmediatePropagation();
		return window.confirm( wp_debug_logging_admin.delete_log_confirmation );
	});
});
