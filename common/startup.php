<?php
/**
 * Triggers installation for inner and additional modules.
 * Runs only once, based on an option because of mu-plugins.
 */
function uf_install( $old_theme = null ) {
	if( ! get_option( 'uf_installed' ) ) {
		do_action( 'uf_install' );
		add_option( 'uf_installed', true );
	}
}

/**
 * Triggers de-installation for inner and additional modules
 */
function uf_uninstall() {
	do_action( 'uf_uninstall' );
}

/**
 * Adds an action for later setup. When after_setup_theme is executed,
 * all themes, etc. are included so their filters are available
 */
function uf_startup( $file ) {
	# Save the file path for uf_start
	define( 'UF_MAIN_FILE', $file );

	# Add the uninstallation hook
	register_uninstall_hook( $file, 'uf_uninstall' );
	add_action( 'after_setup_theme', 'uf_start' );
}

/**
 * Checks the path of the plugin and adds appropriate constants.
 * Actions vary based on the location - in a theme, normal or must use plugin.
 * 
 * @param string $directory The directory where the plugin is found.
 */
function uf_start() {
	$directory       = str_replace( DIRECTORY_SEPARATOR, '/', dirname( UF_MAIN_FILE ) );
	$wpmu_plugin_dir = str_replace( DIRECTORY_SEPARATOR, '/', WPMU_PLUGIN_DIR );
	$wp_plugin_dir   = str_replace( DIRECTORY_SEPARATOR, '/', WP_PLUGIN_DIR );

	# Add all constants to an array, which will be filtered before being set.
	$constants = array(
		'dir'       => trailingslashit( $directory ),
		'class_dir' => trailingslashit( $directory ) . 'classes/',
		'ver'       => 1.2
	);

	# Check if the plugin is included in a theme, a must-use plugin or simply runs as a normal plugin
	if( strpos( $directory, $wp_plugin_dir ) === 0 ) {
		# It's a plugin
		$plugin = true;
		$must_use = false;
	} elseif( strpos( $directory, $wpmu_plugin_dir ) === 0 ) {
		# It's a must-use plugin
		$plugin = true;
		$must_use = true;
	} else {
		# It's a theme
		$plugin   = false;
		$must_use = false;
	}

	extract( apply_filters( 'uf_install_type', compact( 'directory', 'plugin', 'must_use' ) ) );

	# Determine URLs
	if( $plugin && $must_use ) {
		$constants[ 'url' ] = trailingslashit( str_replace( $wpmu_plugin_dir, WPMU_PLUGIN_URL, $directory ) );
	} elseif( $plugin ) {
		$constants[ 'url' ] = trailingslashit( plugins_url( basename( $directory ) ) );
	} else {
		$url = home_url( preg_replace( '~^.*(' . basename( WP_CONTENT_URL ) . '/[^/]+/[^/]+/.+)$~i', '$1', $directory ) );
		$constants[ 'url' ] = trailingslashit( $url );
	}

	# Apply filters and setup constants
	$constants = apply_filters( 'uf_startup_vars', $constants, $plugin, $must_use, $directory );
	foreach( $constants as $key => $value ) {
		define( 'UF_' . strtoupper( $key ), $value );
	}

	# Start up
	uf_load();
	uf_install();
}