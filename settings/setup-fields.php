<?php
/**
 * Create field objects by a plain array
 * 
 * @param mixed[] $fields - The plain array, containing raw data
 * @param string $container_type The type of the container those fields will be in. Class name
 * @param UF_Field_Repeater $parent If there is a parent set, all processing callbacks will be sent there.
 * @return UF_Field[] $prepared - The fields, ready to be added to a container
 */
function uf_setup_fields( $fields, $container_type, $parent = '' ) {
	if( is_a( $parent, 'UF_Field_Repeater' ) ) {
		$uf_processors = array();
	} else {
		$uf_processors = & $GLOBALS[ 'uf_datastore_getter' ]->processors;
	}

	$prepared = array( );

	if( ! is_array( $fields ) ) {
		return;
	}

	foreach( $fields as $field ) {
		if( $field[ 'type' ] == 'tab_start' || $field[ 'type' ] == 'tab_end' ) {
			# Add the icon as a path
			if( $field[ 'type' ] == 'tab_start' )
				if( $type = $field[ 'icon_type' ] ) {
					if( $type == 'image' && $field[ 'icon_image' ] )
						$field[ 'icon' ] = wp_get_attachment_url( $field['icon'] );
					elseif( $type == 'font' && $field[ 'icon_class' ] )
						$field[ 'icon' ] = $field[ 'icon_class' ];
				}

			$prepared[] = $field;
		} else {
			$obj = null;

			switch( $field['type'] ) {
				case 'separator':
					$obj = UF_Field::factory( 'separator', 'separator_' . md5( microtime() ) );
					break;

				case 'text':
					$obj = UF_Field::factory( $field[ 'type' ], $field['field_id'] );

					if( isset( $field['autocomplete_suggestions'] ) ) {
						$obj->add_suggestions( explode( "\n", $field['autocomplete_suggestions'] ) );
					}

					break;

				case 'select':
				case 'set':
				case 'radio':
					$obj = UF_Field::factory( $field['type'], $field['field_id'] );

					if( $field['values_source'] == 'textarea' ) {
						$values = array( );

						if( isset( $field['options'] ) )
						foreach( $field['options'] as $option ) {
							$values[ $option['key'] ] = $option['value'];
						}

						$obj->add_options( $values );
					} else {
						$obj->add_posts( array( 
							'posts_per_page' => -1,
							'order'          => 'ASC',
							'orderby'        => 'post_title',
							'post_type'      => $field['post_type']
						 ) );
					}

					if( isset( $field['sortable'] ) && $field['sortable'] && $field['type'] == 'set' ) {
						$obj->sortable( true );
					}

					if( isset( $field[ 'jquery_plugin' ] ) && $field[ 'jquery_plugin' ] ) {
						$obj->chosen();
					}

					break;

				case 'textarea':
				case 'richtext':
					$obj = UF_Field::factory( $field[ 'type' ], $field['field_id'] );
					$obj->set_rows( $field['rows'] );

					break;

				case 'checkbox':
					$obj = UF_Field::factory( 'checkbox', $field['field_id'] );

					if( isset( $field['text'] ) ) {
						$obj->set_text( $field['text'] );
					}

					break;

				case 'select_term':
					$obj = UF_Field::factory( 'select_term', $field['field_id'] );
					$obj->set_taxonomy( $field['taxonomy'] );

					break;

				case 'repeater':
					$obj = UF_Field::factory( 'repeater', $field[ 'field_id' ] );

					if( isset( $field[ 'repeater_fields' ] ) )
					foreach( $field[ 'repeater_fields' ] as $group ) {
						$sub_fields_arr = uf_setup_fields( $group[ 'group_fields' ], 'UF_Field_Repeater', $obj );

						$obj->add_fields( $group[ 'key' ], array(
							'title' => UF_ML::split( $group[ 'title' ] )
						), $sub_fields_arr );
					}

					break;

				default:
					$obj = UF_Field::factory( $field[ 'type' ], $field[ 'field_id' ] );
			}

			if( $obj ) {
				foreach( $field as $key => $value ) {
					switch( $key ) {
						case 'title': case 'field_title':   $obj->set_title( UF_ML::split( $value ) );         break;
						case 'default_value': $obj->set_default_value( $value ); break;
						case 'help_text':     $obj->set_help_text( UF_ML::split( $value ) );     break;
						case 'description':   $obj->set_description( UF_ML::split( $value ) );   break;
						case 'multilingual':  if( $value ) $obj->multilingual();                 break;
					}
				}

				# Add the field as a processor
				if( method_exists( $obj, 'process_value' ) ) {
					if( ! isset( $uf_processors[ $container_type ] ) ) {
						$uf_processors[ $container_type ] = array();
					}

					if( ! isset( $uf_processors[ $container_type ][ $field[ 'field_id' ] ] ) ) {
						$uf_processors[ $container_type ][ $field[ 'field_id' ] ] = array(
							10 => array()
						);
					}

					$uf_processors[ $container_type ][ $field[ 'field_id' ] ][ 10 ][] = array(
						'callback' => array( $obj, 'process_value' ),
						'data'     => $field
					);
				}

				/**
				 * Modifies the field.
				 * 
				 * When the field is created, additional information might need
				 * to be set up. You can do it here, as the object is passed by reference.
				 * 
				 * @since 2.0
				 * 
				 * @param UF_Field $object The generated field.
				 * @param mixed[] $field_data The all settings of the field as saved in the admin.
				 */
				do_action( 'uf_setup_field', $obj, $field );

				$prepared[] = $obj;
			}
		}
	}

	if( is_a( $parent, 'UF_Field_Repeater' ) ) {
		$parent->processors = $uf_processors[ 'UF_Field_Repeater' ];
	}

	return $prepared;
}