<?php
/**
 * Ultimate Fields Settings Post Type and Settings pages
 */

# Register the ultimatefields post type which is represents containers
include_once( 'interface/post-type.php' );

# Change the icon of the post type
include_once( 'interface/icons.php' );

# Add a page for export
include_once( 'interface/export.php' );

# Add a page for general settings
include_once( 'interface/settings-page.php' );

# Change the edit screen and add neccessary fields
include_once( 'interface/meta.php' );

# Enqueue scripts (and eventually styles) for the settings page
add_action( 'admin_enqueue_scripts', 'uf_settings_enqueue_js' );
function uf_settings_enqueue_js() {
	wp_enqueue_script( 'uf-settings', UF_URL . 'settings/interface/settings.js' );
}

# Allow dynamic adding of those elements, like the shortcode.
do_action( 'uf_interface_functions' );