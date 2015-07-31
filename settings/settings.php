<?php
# Same as init, but only called in the admin
add_action( 'init', 'uf_setup_settings', 1000 );
function uf_setup_settings() {
	# The settings section will only appear if WP_DEBUG is on, or UF_ENABLE_SETTINGS is true or undefined
	# This is useful for theme developers if there's need to set up the fields through the admin
	# but disallow access for normal users.
	$enable_settings = false;

	# Turn on on WP_DEBUG
	if( WP_DEBUG ) {
		$enable_settings = true;
	}

	# Turn on if not disabled by UF_ENABLE_SETTINGS
	if( (defined( 'UF_ENABLE_SETTINGS' ) && UF_ENABLE_SETTINGS ) || !defined( 'UF_ENABLE_SETTINGS' ) ) {
		$enable_settings = true;
	} elseif( defined( 'UF_ENABLE_SETTINGS' ) && ! UF_ENABLE_SETTINGS ) {
		$enable_settings = false;
	}

	# Upon setup, scripts should be available
	uf_register_scripts();

	# Allow changing of this value through a filter
	$enable_settings = apply_filters( 'uf_enable_settings', $enable_settings );

	if( $enable_settings ) {
		include_once( 'interface.php' );
		include_once( 'welcome.php' );
	}

	do_action( 'uf_setup_settings' );

	do_action( 'uf_save' );
}

# Setup settings containers early
add_action( 'init', 'uf_setup_settings_containers', 0 );
function uf_setup_settings_containers() {	
	# Get the function that turns an array of settings to an array of fields
	include_once( 'setup-fields.php' );

	# Setup the added options. Even if settings are disabled, some might have already been saved
	include_once( 'setup-containers.php' );

	# Do it for exported code
	do_action( 'uf_setup_containers' );
}