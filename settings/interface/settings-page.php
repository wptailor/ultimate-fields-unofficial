<?php
/**
 * Add a settings page
 */
global $ultimatefields;

$fields = array();

# This will control the multilingual functionality
$fields[] = UF_Field::factory( 'checkbox', 'uf_multilingual', __( 'Multilingual Fields', 'uf' ) )
	->set_text( __( 'Enable', 'uf' ) )
	->set_description( __( 'This plugin is compatible with qTranslate, which is a WordPress multilingual plugin. If you enable the multilingual fields and you use qTranslate, we will automatically get the available languages and display two field versions for each language. Please remember that each field will have it&apos;s own setting and you will need to use it in order to make it multilingual.', 'uf' ) );

# Add a theme chooser field
$themes = $ultimatefields->themes->get();
if( true || $themes ) {
	# Start with the default theme
	$themes = array_merge( array(
		array(
			'id'    => 'default',
			'title' => __( 'Default Theme', 'uf' ),
			'dir'   => UF_DIR . 'templates',
			'url'   => UF_URL . 'templates',
			'image' => UF_URL . 'settings/images/screenshot.png'
		)
	), $themes );

	foreach( $themes as $theme ) {
		$options[ $theme[ 'id' ] ] = $theme[ 'title' ];
	}

	# Add the field
	$fields[] = UF_Field::factory( 'select', 'uf_theme', __( 'Theme', 'uf' ) )->add_options( $options );
}

# Create the page
$page = UF_Options::page( 'Settings', array(
	'parent' => 'edit.php?post_type=ultimatefields',
	'title' => __( 'Settings', 'uf' )
))->tab( 'general', $fields, 'dashicons dashicons-admin-generic', __( 'General', 'uf' ) );

/**
 * Allows adding of additional fields to the container for specific fields.
 * 
 * @param UF_Options $page The page which works with the options.
 * 
 * @since 2.0
 */
do_action( 'uf_settings', $page );