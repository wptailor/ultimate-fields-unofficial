<?php
/**
 * Registers the post type.
 */
register_post_type( 'ultimatefields', array(
	'public'             => false,
	'publicly_queryable' => false,
	'show_ui'            => true, 
	'show_in_menu'       => true, 
	'query_var'          => false,
	'rewrite'            => false,
	'capability_type'    => 'post',
	'has_archive'        => true, 
	'hierarchical'       => false,
	'menu_position'      => 90,
	'supports'           => array( 'slug' ),
	'labels'             => array(
		'name'               => __( 'Ultimate Fields', 'uf' ),
		'singular_name'      => __( 'Container', 'uf' ),
		'add_new'            => __( 'Add New', 'uf' ),
		'add_new_item'       => __( 'Add New Container', 'uf' ),
		'edit_item'          => __( 'Edit Container', 'uf' ),
		'new_item'           => __( 'New Container', 'uf' ),
		'all_items'          => __( 'All Containers', 'uf' ),
		'view_item'          => __( 'View Container', 'uf' ),
		'search_items'       => __( 'Search Containers', 'uf' ),
		'not_found'          => __( 'No Containers found', 'uf' ),
		'not_found_in_trash' => __( 'No Containers found in Trash', 'uf' ),
		'parent_item_colon'  => '',
		'menu_name'          => __( 'Ultimate Fields', 'uf' )
	)
) );

/**
 * The default title and editor are disabled, because the editor is too big and the title depends on it when qTranslate is on.
 * Because of this, when saving the container, the title and description are replaced.
 * 
 * This function does also do caching so that all info about all fields is in the options table.
 * 
 * @param int $post_id THe ID of the post.
 */
add_action( 'save_post', 'uf_save_ultimatefields', 12 );
add_action( 'import_end', 'uf_save_ultimatefields', 12 );
function uf_save_ultimatefields( $post_id = '' ) {
	global $wpdb;

	# This section will update the posts' title and content when a container is saved.
	# This does not happen on import, so if there is no post_id, it's not needed.
	if( $post_id ) {
		if( get_post_type( $post_id ) != 'ultimatefields' ) {
			return;
		}

		$title   = get_post_meta( $post_id, 'uf_title', true );
		$content = get_post_meta( $post_id, 'uf_description', true );

		# Prevent recursion
		remove_action( 'save_post', 'uf_save_ultimatefields', 12 );

		# Update the post
		wp_update_post( array(
			'ID'           => $post_id,
			'post_title'   => $title,
			'post_content' => $content
		) );
	}
	

	# Now that the post is updated, fetch all fields and cache 'em
	$containers = array();

	$raw = get_posts( array(
		'post_type' => 'ultimatefields',
		'posts_per_page' => -1
	) );

	foreach( $raw as $container ) {
		$meta = array();

		$raw_meta = $wpdb->get_results( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$container->ID" );
		foreach( $raw_meta as $m ) {
			$meta[ $m->meta_key ] = maybe_unserialize( $m->meta_value );
		}

		$containers[ $container->ID ] = array(
			'post' => $container,
			'meta' => $meta
		);
	}

	# Save everything in an option
	update_option( 'uf_containers', $containers );
}

/**
 * Changes the columns of the table with fields.
 * 
 * @param string[] $columns The previous columns, will be ignored.
 * @return string[]
 * 
 * @since 1.0
 */
add_filter( 'manage_edit-ultimatefields_columns', 'uf_edit_ultimatefields_columns' ) ;
function uf_edit_ultimatefields_columns( $columns ) {
	$columns = array(
		'cb'           => '<input type="checkbox" />',
		'title'        => __( 'Title' ),
		'containers'   => __( 'Type', 'uf' ),
		'fields_count' => __( 'Fields', 'uf' )
	);

	return $columns;
}

/**
 * Modifies the content of the cells of the table with all containers.
 * 
 * @param string $column The key of the column.
 * @param int $post_id The ID of the post.
 * 
 * @since 1.0
 */
add_action( 'manage_ultimatefields_posts_custom_column', 'uf_manage_ultimatefields_columns', 10, 2 );
function uf_manage_ultimatefields_columns( $column, $post_id ) {
	# Retrieve the right container from the cached option
	$containers = get_option( 'uf_containers' );
	foreach( $containers as $c ) {
		if( $c[ 'post' ]->ID == $post_id ) {
			$container = $c;
			break;
		}
	}

	# If the container isn't found the normal way, extract it's data
	if( ! isset( $container ) ) {
		global $wpdb;

		$container = array(
			'post' => get_post( $post_id ),
			'meta' => array()
		);

		$raw_meta = $wpdb->get_results( "SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id" );
		foreach( $raw_meta as $m ) {
			$container[ 'meta' ][ $m->meta_key ] = maybe_unserialize( $m->meta_value );
		}
	}

	/**
	 * Output the title of the containers column.
	 */
	if( $column == 'containers' ) {
		if( $container[ 'meta' ][ 'uf_type' ] ) {
			$type = $container[ 'meta' ][ 'uf_type' ];

			/**
			 * Allows adding of type titles of addons and etc.
			 * 
			 * @param string[] $titles The built-in titles.
			 */
			$container_types = apply_filters( 'uf_container_type_titles', array(
				'options'   => __( 'Options Page', 'uf' ),
				'post-meta' => __( 'Post Meta', 'uf' )
			));

			if( isset( $container_types[ $type ] ) ) {
				echo $container_types[ $type ];
			} else {
				echo ucwords( str_replace( '-', ' ', $type ) );
			}
		} else {
			echo __( 'None', 'uf' );
		}				
	}

	/**
	 * Output the fields' count.
	 */
	if( $column == 'fields_count' ) {
		echo count( $container[ 'meta' ][ 'fields' ] );
	}
}

/**
 * Modifies the actions for the Ultimate Fields post type.
 * 
 * The quick edit should not be there, but instead an export button is needed.
 * 
 * @param mixed[] $actions The current actions.
 * @return mixed[]
 *
 * @since 1.0
 */
add_filter( 'post_row_actions', 'uf_change_quick_actions', 10, 2 );
function uf_change_quick_actions( $actions ) {
	global $post;

	# If the post is not an ultimatefields post or there is no edit action, don't do anything.
	if( $post->post_type != 'ultimatefields' || ! isset( $actions[ 'edit' ] ) ) {
		return $actions;
	}

	# This is the export link for that container.
	$export_link = admin_url( 'edit.php?post_type=ultimatefields&page=uf-export&export_container=' . $post->ID );

	# Replace the actions and add the export link.
	$actions = array(
		'edit'        => $actions[ 'edit' ],
		'export-link' => '<a href="' . esc_attr( $export_link ) . '">' . __( 'Export PHP code', 'uf' ) . '</a>',
		'trash'       => $actions[ 'trash' ]
	);

    return $actions;
}

/**
 * When a container is updated, it's message should not be "Post Published".
 *
 * @param mixed[] $messages The current group of messages.
 * @return mixed[]
 */
add_filter( 'post_updated_messages', 'uf_change_updated_message' );
function uf_change_updated_message( $messages ) {
	if( ! isset( $_GET[ 'post' ] ) )
		return $messages;

	$p = get_post( $_GET[ 'post' ] );

	if( $p->post_type != 'ultimatefields' ) {
		return $messages;
	}

	$messages[ 'post' ][ 6 ] = __( 'Container saved.', 'uf' );

	return $messages;
}