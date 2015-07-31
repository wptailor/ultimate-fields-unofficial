<?php
/**
 * Only returns the items that are a UF_Field
 * 
 * @param mixed $field The argument to be chebked
 * @return boolean if it's a field
 */
function uf_leave_fields_only( $field ) {
	return is_a( $field, 'UF_Field' );
}

/**
 * Convert an array to a working container
 */
function uf_setup_container( $data ) {
	$args = array(
		'title'       => '',
		'description' => ''
	);
	$data = wp_parse_args( $data, $args );

	$p = new stdClass();
	
	if( isset( $data[ 'uf_title' ] ) )
		$p->post_title   = $data[ 'uf_title' ];
	else
		$p->post_title = '';

	if( isset( $data[ 'uf_description' ] ) )
		$p->post_content = $data[ 'uf_description' ];
	else
		$p->post_content = '';

	$container = array(
		'post' => $p,
		'meta' => $data
	);

	uf_setup_containers( $container );
}

/**
 * Get an array of all registered containers
 */
function uf_setup_containers( $data = null ) {
	static $added_containers;

	if( ! isset( $added_containers ) ) {
		$added_containers = array();
	}

	$containers = $data ? array( $data ) : get_option( 'uf_containers' );
	$containers = apply_filters( 'uf_containers', $containers );

	if( ! $containers || ! is_array( $containers ) ) {
		return;
	}

	# Prevent duplicate ID exits.
	UF_Exceptions::buffer( 'unavailable_field_key' );
	UF_Exceptions::buffer( 'unavailable_container_key' );

	foreach( $containers as $container ) {
		extract( $container );

		if( isset( $added_containers[ $meta[ 'uf_title' ] ] ) ) {
			continue;
		}

		switch( $meta[ 'uf_type' ] ) {
			case 'options':
				uf_setup_options_page( uf_setup_fields( $meta['fields'], 'UF_Datastore_Options' ), $container );
				break;
			case 'post-meta':
				uf_setup_postmeta_box( uf_setup_fields( $meta['fields'], 'UF_Datastore_Postmeta' ), $container );
				break;
		}

		# Add underscores to the type
		$type = str_replace( '-', '_', $meta[ 'uf_type' ] );
		do_action( "uf_setup_" . $type, $container );

		$added_containers[ $meta[ 'uf_title' ] ] = 1;
	}
}

/**
 * Parse args by removing certain prefixes
 *
 * @param mixed[] $data
 * @param string[] $keys The required keys
 * @param string $prefix
 * @return mixed[] Parsed data
 */
function uf_parse_args_array( $source, $keys, $prefix ) {
	$data = array();

	foreach( $keys as $key ) {
		$full_key = $prefix ? $prefix . $key : $key;

		if( isset( $source[ $full_key ] ) ) {
			$data[ $key ] = $source[ $full_key ];
		} else {
			$data[ $key ] = '';
		}
	}

	return $data;
}

/**
 * Setup an options page
 */
function uf_setup_options_page( $fields, $data ) {
	# An array of settings that will be passed to the page
	$args = array();

	# Extract what we actually care about
	$meta = extract( uf_parse_args_array( $data[ 'meta' ], array( 'page_type', 'parent_page', 'page_parent_slug', 'page_slug', 'icon', 'menu_position' ), 'uf_options_' ) );

	# Prepare the title
	$title = UF_ML::split( $data['meta'][ 'uf_title' ] );
	$args[ 'title' ] = $title;

	# Prepare the description
	if( $description = UF_ML::split( $data[ 'post' ]->post_content ) ) {
		$args[ 'description' ] = $description;
	}

	# If there's a slug set, it will be used
	if( $page_slug )
		$args[ 'id' ] = $page_slug;

	if( $page_type == 'menu' ) {
		# Main page, it's default but might have extra options
		if( $menu_position )
			$args[ 'position' ] = $menu_position;

		# Icon
		if( $icon )
			$args[ 'icon' ] = wp_get_attachment_url( $icon );
	} elseif( $page_type == 'other_page' ) {
		$args[ 'parent' ] = $page_parent_slug;
	} elseif( $page_type == 'other_uf_page' ) {
		if( isset( $parent_page ) && $page = get_post( $parent_page ) ) {
			$args[ 'parent' ] = ( $custom = get_post_meta( $page->ID, 'uf_options_page_slug', true ) ) ? $custom : sanitize_title( $page->post_title );	
		}
	} else {
		$args[ 'type' ] = $page_type;
	}

	$page = UF_Options::page( $title, $args );

	# Add fields
	$page->add_fields_array( $fields );
}

/**
 * Setup post meta container
 * 
 * @param UF_Field[] $fields
 * @param mixed[] $data
 */
function uf_setup_postmeta_box( $fields, $data ) {
	$args = array();

	# Extract what we actually care about
	extract( uf_parse_args_array( $data[ 'meta' ], array( 'posttype', 'templates', 'levels' ), 'uf_postmeta_' ) );

	# Don't do anything in some cases
	if( !isset( $posttype) || ! is_array( $posttype ) || empty( $posttype ) ) {
		return;
	}
	
	# Prepare the title
	$title = UF_ML::split( $data['meta'][ 'uf_title' ] );
	$args[ 'title' ] = $title;

	# If there's a slug set, it will be used
	if( isset( $data[ 'meta' ][ 'uf_options_page_slug' ] ) && $data[ 'meta' ][ 'uf_options_page_slug' ] )
		$args[ 'id' ] = $data[ 'meta' ][ 'uf_options_page_slug' ];

	# Create the page
	$classname = apply_filters( 'uf_postmeta_classname', 'UF_Postmeta', $data );
	$container = call_user_func( $classname . '::box', $title, $posttype, $args );

	# Prepare the description
	if( $description = UF_ML::split( $data[ 'post' ]->post_content ) ) {
		$container->set_description( $description );
	}

	# Choose templates if set
	if( in_array( 'page', $posttype ) && isset( $templates ) && is_array( $templates ) && $templates ) {
		$container->set_templates( $templates );
	}

	# Add level
	if( $levels ) {
		$container->set_levels( $levels );
	}

	# Set taxonomy info
	$taxonomies = get_taxonomies( array( 'show_ui' => 1 ), 'objects' );
	foreach( $taxonomies as $id => $taxonomy ) {
		# Only hierarchical taxonomies have checkboxes
		if( ! $taxonomy->hierarchical ) {
			continue;
		}

		if( isset( $data[ 'meta' ][ "uf_postmeta_terms_{$id}" ] ) && is_array( $data[ 'meta' ][ "uf_postmeta_terms_{$id}" ] ) ) {
			$terms = $data[ 'meta' ][ "uf_postmeta_terms_{$id}" ];

			if( ! empty( $terms ) ) {
				foreach( $terms as $term ) {
					$container->add_term( $id, $term );
				}
			}
		}
	}

	# Add the fields
	$container->add_fields_array( $fields );
}

# Do it
uf_setup_containers();