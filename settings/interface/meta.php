<?php
/**
 * Retrieve a repeater with all available fields + a nested repeater
 * 
 * @return UF_Repeater The filled repeater
 */
function uf_get_available_fields( $repeater_id = 'fields' ) {
	# The top
	$repeater = UF_Field::factory( 'repeater', $repeater_id, __( 'Fields', 'uf' ) );
	UF_Field::get_fields( $repeater );

	# The inner repeater
	$inner_repeater = UF_Field::factory( 'repeater', 'group_fields', __( 'Fields', 'uf' ) );
	UF_Field::get_fields( $inner_repeater );

	$inner_settings = array(
		'title'       => __( 'Repeater', 'uf' ),
		'description' => __( 'Enables repeateable field groups. Check the docs for more info.', 'uf' )
	);

	$default_fields = UF_Field::settings_fields( 'repeater' );
	unset( $default_fields[ 'default_value' ] );
	unset( $default_fields[ 'default_value_ml' ] );
	unset( $default_fields[ 'multilingual' ] );

	$repeater_settings = array_merge( $default_fields, array(
		UF_Field::factory( 'repeater', 'repeater_fields', __( 'Repeater Fields', 'uf' ) )
			->add_fields( 'group', __( 'Group', 'uf' ) , array(
				UF_Field::factory( 'text', 'title' )
					->multilingual()
					->make_required(),
				UF_Field::factory( 'text', 'key' )
					->make_required( '/[a-z0-9_]+/' ),
				$inner_repeater
			) )
	) );

	$repeater->add_fields( 'repeater', $inner_settings, $repeater_settings );

	/**
	 * Tab starts & ends
	 */
	$details = array(
		'title' => __( 'Tab Start', 'uf' ),
		'description' => __( 'Adds a tab to the container. Not available in widgets.', 'uf' )
	);
	$repeater->add_fields( 'tab_start', $details, array(
		UF_Field::factory( 'text', 'title', __( 'Title', 'uf' ) )
			->multilingual(),
		UF_Field::factory( 'radio', 'icon_type', __( 'Icon Type', 'uf' ) )
			->add_options(array(
				'image' => __( 'Image', 'uf' ),
				'font'  => __( 'Font Icon', 'uf' )
			)),
		UF_Field::factory( 'file', 'icon_image', __( 'Icon', 'uf' ) )
			->set_dependency( 'icon_type', 'image' ),
		UF_Field::factory( 'text', 'icon_class', __( 'CSS Class', 'uf' ) )
			->set_dependency( 'icon_type', 'font' )
			->set_description( __( 'You can enter the CSS class of the icon here. You could use a <a href="http://fontawesome.io/icons/">FontAwesome</a>, <a href="https://github.com/melchoyce/dashicons">Dashicons</a> or a custom font CSS class. <strong>Example:</strong> dashicons dashicons-align-none', 'uf' ) )
	));

	return $repeater;
}

/**
 * Add the type box
 */

# Create the box
$box = UF_Postmeta::box( 'Ultimate Fields', 'ultimatefields', array(
		'title' => __( 'Container Settings', 'uf' )
	) );

$box_fields = array();

# Fetch other pages that are top-level
$top_level_pages = array();
$args = array(
	'post_type'      => 'ultimatefields',
	'posts_per_page' => -1,
	'order'          => 'ASC',
	'orderby'        => 'post_title',
	'meta_query'     => array(
		array(
			'key'   => 'uf_options_page_type',
			'value' => 'menu'
		),
		array(
			'key'   => 'uf_type',
			'value' => 'options'
		)
	)
);
if( isset( $_GET[ 'post' ] ) ) {
	$args[ 'post__not_in' ] = array( $_GET[ 'post' ] );
}
$raw = get_posts( $args );
foreach( $raw as $container ) {
	$top_level_pages[ $container->ID ] = apply_filters( 'the_title', $container->post_title );
}

/**
 * Basic settings
 */
$box_fields = array_merge( $box_fields, array(
	UF_Field::factory( 'text', 'uf_title', __( 'Title', 'uf' ) )
		->multilingual()
		->set_description( __( 'The title is the key element of a container. It will appear in the menu for options pages, as a box heading for widgets and post meta.', 'uf' ) )
		->make_required(),
	UF_Field::factory( 'richtext', 'uf_description', __( 'Description', 'uf' ) )
		->set_description( __( 'The description is optional and will appear in the beginning of a container.', 'uf' ) )
		->multilingual(),
	UF_Field::factory( 'radio', 'uf_type', __( 'Type', 'uf' ) )
		->add_options( apply_filters( 'uf_container_types', array(
			'options'   => __( 'Options Page', 'uf' ),
			'post-meta' => __( 'Post Meta Box', 'uf' )
		) ) )
));

/**
 * Options page settings
 */
$box_fields = array_merge( $box_fields, array(
	UF_Field::factory( 'select', 'uf_options_page_type', __( 'Show page', 'uf' ) )
		->add_options( array(
			'menu'          => __( 'In the Main Menu', 'uf' ),
			'settings'      => __( 'Under the Settings tab', 'uf' ),
			'appearance'    => __( 'Under the Appearance tab', 'uf' ),
			'tools'         => __( 'Under the Tools Tab', 'uf' ),
			'other_uf_page' => __( 'Under another Ultimate Fields page.', 'uf' ),
			'other_page'    => __( 'Under another page, specified by slug', 'uf' )
		) )
		->set_dependency( 'uf_type', 'options' ),
	UF_Field::factory( 'select', 'uf_options_parent_page', __( 'Parent Page', 'uf' ) )
		->set_dependency( 'uf_type', 'options' )
		->set_dependency( 'uf_options_page_type', 'other_uf_page' )
		->set_no_options_message( __( 'Right now, there are no top level Ultimate Fields options pages. Until you add a top level page, this page will be displayed under in main menu.', 'uf' ) )
		->add_options( $top_level_pages ),
	UF_Field::factory( 'text', 'uf_options_page_parent_slug', __( 'Parent Page Slug' ) )
		->set_dependency( 'uf_type', 'options' )
		->set_dependency( 'uf_options_page_type', 'other_page' ),
	UF_Field::factory( 'text', 'uf_options_page_slug', __( 'Slug', 'uf' ) )
		->set_description( __( '<strong>Required if the container has multilingual title!</strong> The ID of the container is neccessary if you want a specific slug for the page. If you leave this empty, the slug will be generated from the title. If the container has a multilignaul title though, you need to set this, because not all languages might work as slugs.', 'uf' ) )
		->set_dependency( 'uf_type', 'options' ),
	UF_Field::factory( 'file', 'uf_options_icon', __( 'Menu Icon', 'uf' ) )
		->set_dependency( 'uf_type', 'options' )
		->set_dependency( 'uf_options_page_type', 'menu' )
		->set_description( __( 'Top level pages might use a custom icon.', 'uf' ) ),
	UF_Field::factory( 'text', 'uf_options_menu_position', __( 'Menu Position', 'uf' ) )
		->set_default_value( 100 )
		->set_dependency( 'uf_type', 'options' )
		->set_dependency( 'uf_options_page_type', 'menu' )
		->set_description( __( 'Be careful with this setting, because you might silently overwrite another item&apos;s icon as WordPress does not check if there is anything at the particular position.', 'uf' ) )
) );

# Prepare post types
$post_types = array();
$hierarchical_post_types = array();
$excluded = apply_filters( 'uf_excluded_post_types', array( 'attachment', 'ultimatefields' ) );
$raw = get_post_types( array(
	'show_ui' => true
), 'objects' );
foreach( $raw as $id => $post_type ) {
	if( in_array( $id, $excluded ) ) {
		continue;
	}

	$post_types[ $id ] = $post_type->labels->name;
	if( is_post_type_hierarchical( $id ) ) {
		$hierarchical_post_types[ $id ] = $post_type->labels->name;
	}
}

# Prepare page templates
$templates = array(
	'default' => __( 'Default' )
);

$raw = wp_get_theme()->get_page_templates();
foreach( $raw as $template => $name ) {
	$templates[ $template ] = $name;
}

/**
 * Post meta settings
 */
$post_meta_fields = array(
	UF_Field::factory( 'set', 'uf_postmeta_posttype', __( 'Show on post types', 'uf' ))
		->add_options( $post_types )
		->set_dependency( 'uf_type', 'post-meta' ),
	UF_Field::factory( 'set', 'uf_postmeta_templates', __( 'Show on page templates:', 'uf' ) )
		->add_options( $templates )
		->set_dependency( 'uf_type', 'post-meta' )
		->set_dependency( 'uf_postmeta_posttype', array( 'page' ), 'IN' )
		->set_description( __( 'The box will only appear on the checked templates, if any. If none are checked, the container will appear on all pages.', 'uf' ) ),
	UF_Field::factory( 'text' , 'uf_postmeta_levels', __( 'Levels', 'uf' ) )
		->set_dependency( 'uf_type', 'post-meta' )
		->set_dependency( 'uf_postmeta_posttype', array_keys( $hierarchical_post_types ), 'IN' )
		->set_description( __( 'On hierarchical post types, the box will only be visible on the selected levels. Leave 0 for all levels', 'uf' ) )
		->set_default_value( 0 )
);

$taxonomies = get_taxonomies( array( 'show_ui' => 1 ), 'objects' );
foreach( $taxonomies as $id => $taxonomy ) {
	# Only hierarchical taxonomies have checkboxes
	if( ! $taxonomy->hierarchical ) {
		continue;
	}
	
	$options = array();
	$terms = get_terms( $id, array( 'hide_empty' => false ) );
	foreach( $terms as $term ) {
		$options[ $term->term_id ] = apply_filters( 'single_term_title', $term->name );
	}

	$field = UF_Field::factory( 'set', "uf_postmeta_terms_{$id}", sprintf( __( '%s terms', 'uf' ), $taxonomy->labels->name ) )
		->set_dependency( 'uf_type', 'post-meta' )
		->set_dependency( 'uf_postmeta_posttype', $taxonomy->object_type, 'IN' )
		->add_options( $options );

	$post_meta_fields[] = $field;
}

$box_fields = array_merge( $box_fields, $post_meta_fields );

/**
 * Allows adding of additiona fields to the settings tab.
 * 
 * @param UF_Field $fields The already existing (empty) fields.
 */
if( $additional = apply_filters( 'uf_container_settings_fields', array() ) ) {
	$box_fields = array_merge( $box_fields, $additional );
}

$box_fields = apply_filters( 'uf_container_general_tab_fields', $box_fields );

$box->tab( 'general', $box_fields, 'dashicons dashicons-admin-generic', __( 'Settings', 'uf' ) );

/**
 * The fields themselves, but into another box
 */
$box->tab( 'fields', array(
	uf_get_available_fields()
		->set_custom_template( 'field-no-label' )	
), 'dashicons dashicons-list-view', __( 'Fields', 'uf' ) );

/**
 * Hide the default publish box
 */
add_action( 'admin_menu', 'uf_hide_submitdiv' );
function uf_hide_submitdiv() {
	# Remove the default submit div
	remove_meta_box( 'submitdiv', 'ultimatefields', 'side' );

	# Add a separate box which replaces the old one
	// add_meta_box( 'uf_submitdiv', __( 'Actions', 'uf' ), 'uf_submitdiv', 'ultimatefields', 'side', 'high' );
}

/**
 * Display save/delete buttons in the space for tabs when editing a container.
 * 
 * @param UF_Container The container whose tabs are being displayed.
 */
add_action( 'uf_after_tabs', 'uf_container_submit' );
function uf_container_submit( $container ) {
	if( $container->get_id() != 'ultimate-fields' ) {
		return;
	}

	echo '<div class="submitbox">';
		submit_button( __( 'Save' ), 'primary', 'publish', false, array( 'accesskey' => 'p' ) );
	echo '</div>';
}

/**
 * Force a single column layout on the container edit screen for post types.
 * 
 * @param int $cols The current number of cols, as per the current user.
 * @return int The int 1.
 */
add_filter( 'get_user_option_screen_layout_ultimatefields', 'uf_container_layout_cols' );
function uf_container_layout_cols( $cols ) {
	return 1;
}