<?php
/**
 * Check if the plugin was just activated. If so, go to the welcome page.
 */
if( get_option( 'uf_activated' ) ) {
	# Remove the option and redirect
	delete_option( 'uf_activated' );
	wp_redirect( admin_url( 'edit.php?post_type=ultimatefields&page=welcome' ) );
	exit;
}

/**
 * If we are on the welcome URL, add the welcome page.
 */
if(
	basename( $_SERVER[ 'SCRIPT_FILENAME' ] ) == 'edit.php'
	&& ( isset( $_GET[ 'post_type' ] ) && $_GET[ 'post_type' ] == 'ultimatefields' )
	&& ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == 'welcome' )
) {
	add_action( 'admin_menu', 'uf_add_welcome_page' );
}

function uf_add_welcome_page() {
	$title = __( 'Welcome', 'uf' );
	add_submenu_page( 'edit.php?post_type=ultimatefields', $title, $title, 'manage_options', 'welcome', 'uf_welcome_page' );
}

/**
 * Displays the options page.
 */
function uf_welcome_page() {
	global $ultimatefields;
	
	include( $ultimatefields->themes->path( 'welcome' ) );	
}