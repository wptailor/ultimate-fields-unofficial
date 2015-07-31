<?php
/**
 * Plugin Name: Ultimate Fields
 * Plugin URI: http://ultimate-fields.com/
 * Description: Custom fields framework which allows for easy adding of fields in most areas of the WordPress administration area.
 * Version: 1.0.3
 * Author: Radoslav Georgiev
 * Author URI: http://rageorgiev.com/
 * Copyright: Radoslav Georgiev
 */

# Setup the plugin - check plugin location, add right hooks and run filters
require_once( 'common/startup.php' );
uf_startup( __FILE__ );

/**
 * Includes core files and adds the init action.
 * 
 * This function is executed on after_setup_theme, so you can add all the hooks you need
 * in functions.php, which is included before the after_setup_theme action is performed.
 */
function uf_load() {
	global $ultimatefields;

	# Register plugin textdomain
	$mofile = UF_DIR . "/languages/uf-" . get_locale() . ".mo";
 	if ( file_exists( $mofile ) )
 		load_textdomain( 'uf', $mofile );

	# Most classes will somehow be saved to $ultimatefields
	$ultimatefields = new stdClass();

	# Common functionality that's used accross the framework
	include_once( 'common/common.php' );

	# Include classes and functions
	include_once( 'includes.php' );

	# Register additional fields, templates, etc.
	do_action( 'uf_extend' );	

	# Buffer certain exceptions, which are not fatal.
	UF_Exceptions::buffer( 'non_existing_field' );

	# Add UF options pages which provide admin interface for fields
	include_once( 'settings/settings.php' );
	
	# Indicate that the plugin is present so themes could check it.
	define( 'UF', true);

	# Fields are set up on init, but only in the admin, with priority 12
	add_action( 'init', 'uf_init', 12 );
}

/**
 * Enqueues scripts, allows adding additional classes and sets up fields.
 */
function uf_init() {
	# Register available scripts and styles
	uf_register_scripts();

	# Init fields through themes and other plugins
	do_action( 'uf_setup' );		

	# Now that the fields are defined, save options and stuff
	do_action( 'uf_save' );
}

/**
 * Add an activation hook and a welcome message.
 */
register_activation_hook( __FILE__, 'uf_activated' );
function uf_activated() {
	add_option( 'uf_activated', true );
}