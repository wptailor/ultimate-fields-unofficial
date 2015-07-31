<?php
/**
 * Enables repeatable groups of fields inside a single field.
 * 
 * Works both as a container, a datastore and a field:
 * - It can have fiels, so it can accept them and control them.
 * - It contains the values of it's sub-fields so it's a datastore too.
 * - It has all field functions and can be treated as a normal field.
 * 
 * For more information, check the docs.
 * 
 * @since 1.0
 */
class UF_Field_Repeater extends UF_Field implements UF_Datastore, UF_Container {
	/**
	 * Holds all available field groups as prototypes.
	 * Those groups will not be displayed or saved, but will be cloned as items when they are actually added.
	 * 
	 * @type mixed[]
	 * @access protected
	 */
	protected $field_groups = array();

	/**
	 * Holds the groups, which are already added and set up.
	 * 
	 * @type mixed[]
	 * @access protected
	 */
	protected $fields = array();

	/**
	 * This placeholder is used in field names in prototypes, so it can be
	 * replaced when a new group is created. It's replaced by the group's index.
	 * 
	 * @type string
	 * @access protected
	 */
	protected $i_placeholder = '__ufi__';

	/**
	 * Holds the plain value of the whole field.
	 * As this is a datastore, the value is actively used by datastore methods.
	 * 
	 * @type mixed[]
	 * @access protected
	 */
	protected $value = array();

	/**
	 * When fields are processed, they are iterated row by row and this holds the current one.
	 * 
	 * This index is the index of the row in the $values property.
	 * 
	 * @type int
	 * @access protected
	 */
	protected $current_data_row = -1;

	/**
	 * Holds the type key of the group, which is currently being saved/loaded.
	 * 
	 * @type string
	 * @access protected
	 */
	protected $current_data_type;

	/**
	 * There could be a limit of added groups. This controls it.
	 * 
	 * By default it is set to -1, which means that the number is unlimited.
	 * 
	 * @type int
	 * @access protected
	 */
	protected $rows_limit = -1;

	/**
	 * Hold callbacks for processing values based on field type.
	 * 
	 * @type mixed[]
	 */
	public $processors = array();

	/**
	 * After the UF_Field constructor is run, this sets up additional data.
	 * 
	 * @access protected
	 */
	protected function after_constructor() {
		# Set a coded placeholder based on the ID of the container/
		$this->i_placeholder = 'placeholder_' . substr( md5( $this->input_id ), 0, 10 );

		# HTML attributes that contain the limit and the placeholder to be used in JS
		$this->html_attributes = array(
			 'data-placeholder' => $this->i_placeholder,
			 'data-limit'       => -1
		);

		# In the admin, there are scripts to be enqueued
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueues the scripts that are needed for the container, only in the admin.
	 * 
	 * @access public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_style( 'fontawesome-icons' );
	}

	/**
	 * When a datastore is set, get it's value and setup fields if any
	 *
	 * @access public
	 * @param UF_Datastore $datastore The new datastore
	 * @param boolean $optional If true, the datastore will only be set if there is no other already
	 * @return UF_Field_Layout The field
	 */
	public function set_datastore( UF_Datastore $datastore, $optional = false ) {
		# Don't do anything if this is optional and there is a datastore already
		if( $this->datastore && $optional )
			return $this;

		# Save the datastore
		$this->datastore = $datastore;

		# Load my value
		$this->value = $this->datastore->get_multiple( $this->id );

		# Spread this as datastore to group prototypes.
		# Their actual values will be saved in this->set_value		
		foreach($this->field_groups as $group) {
			foreach($group['fields'] as $field) {
				$field->set_datastore($this, true);
			}
		}

		# If the value is an array, spread it as added groups. If the datastore is
		# another repeater, don't rush it as there surely will be no values yet.
		if( is_array( $this->value ) && ! is_a( $this->datastore, 'UF_Field' ) )
			$this->set_value( $this->value );

		return $this;
	}

	/**
	 * Prepares a group by adding values to it and generating it's fields.
	 * 
	 * @access protected
	 * @param mixed[] $data The values of the group
	 * @param int $index The index of the group for it's field
	 * @return UF_Field An array of fields, ready for output
	 */
	protected function setup_group( $data, $row_index ) {
		# Compatibility with the old version, saving array( 'type' => 'type', 'values' => array() )
		if( count( $data ) == 2 && isset( $data['values'] ) ) {
			$data = array_merge( $data[ 'values' ], array( 'type' => $data[ 'type' ] ) );
		}

		# The base row is the prototype group, which will be cloned
		$base_row = $this->field_groups[ $data['type'] ];

		# There are plain row variables, which contain type, title, etc.
		$row = array(
			'type'   => $data[ 'type' ],
			'title'  => $base_row[ 'title' ],
			'fields' => array()
		);

		# Some of the base group keys need to be added to the group for easy access
		$group_keys = array( 'group_name', 'title_field', 'max_width', 'min_width' );
		foreach( $group_keys as $key )
			if( isset( $base_row[ $key ] ) )
				$row[$key] = $base_row[ $key ];

		# Child classes may need to modify the group's data
		if( method_exists( $this, 'prepare_row' ) ) {
			$row = $this->prepare_row( $row, $base_row, $data );
		}

		# Go through all of the fields of the base group, clone them and set them up
		foreach($base_row['fields'] as $field) {
			# To prevent overlapping fields, the field is being cloned
			$field = clone $field;

			# If there is some data for the field already, set it
			if( isset( $data[ $field->get_id() ] ) ) {
				$field->set_value( $data[ $field->get_id() ] );
			}

			# Set a proper input ID to the sub-field as nothing is changed when it's shown
			$field->set_input_id( "$this->input_id[$row_index][" . $field->get_id() . "]" );

			# Add the ready field to the group
			$row['fields'][] = $field;
		}

		return $row;
	}

	/**
	 * Sets a value to the field externally.
	 * 
	 * When this is done, for each row of the value, a new group is created.
	 * 
	 * @access public
	 * @param mixed[][] $value The new value, an array of arrays
	 * @return UF_Field_Repeater The instance of the class
	 */
	public function set_value( $value ) {
		# Iterate through each group/row
		foreach( $value as $i => $data ) {
			# If there is no type or the group of that information is not present, don't include it
			if( ! isset( $data[ 'type' ] ) || ! isset( $this->field_groups[ $data[ 'type' ] ] ) )
				continue;

			# Setup the group's field
			$this->fields[] = $this->setup_group( $data, $i );
		}

		return $this;
	}

	/**
	 * Sets a default value if there are no items added
	 * 
	 * @param mixed $value The default value.
	 * @return UF_Field_Repeater
	 */
	public function set_default_value($value) {
		if( ! $this->value )
			$this->set_value( $value );

		$this->default_value = $value;

		return $this;
	}

	/**
	 * Sets an ID to the field. That ID is spread as an index to sub-fields.
	 * 
	 * @access public
	 * @param string $id The new ID
	 * @return UF_Field_Repeater The instance of the class
	 */
	public function set_input_id( $id ) {
		# Keep the value
		$this->input_id = $id;

		# Go through the fields and change their IDs too
		$row_index = 0;
		foreach( $this->fields as $row ) {
			foreach( $row['fields'] as $field ) {
				$field->set_input_id( "$this->input_id[$row_index][" . $field->get_id() . "]" );
			}

			$row_index++;
		}

		return $this;
	}

	/**
	 * This is used by inner fields so they can save their values.
	 * 
	 * Saves the value to the $values property in the row pointed by $current_data_row
	 * 
	 * @access public
	 * @param string $key The key of the field
	 * @param mixed[] $value The new value of the field.
	 */
	public function save_value( $key, $value ) {
		$this->value[ $this->current_data_row ][ $key ] = $value;
	}

	/**
	 * This is used by inner fields to retrieve their values.
	 * 
	 * Gets the value from the $values property from the row pointed by $current_data_row.
	 * If that variable is set to -1, the repeater isn't fully set up and the field has no value.
	 * 
	 * @access public
	 * @param string $key The key of the field
	 * @return mixed|false The value of the field
	 */
	public function get_value( $key ) {
		# If the field has no value or is still not fully set up, return false
		if( $this->current_data_row == -1 || ! $this->value )
			return false;

		# If there is a value for this field returns it, otherwise faslse
		if( isset( $this->value[ $this->current_data_row ][ $key ] ) ) {
			return $this->value[ $this->current_data_row ][ $key ];
		} else {
			return false;
		}
	}

	/**
	 * A proxy for get_value()
	 * 
	 * @access public
	 * @param string $key The key of the field
	 * @return mixed|false The value of the field
	 */
	function get_multiple( $key ) {
		return $this->get_value( $key );
	}

	/**
	 * Increases the current data row.
	 * 
	 * Basically, this increases the current_data_row variable, which points to the row that
	 * is being processed right now - saved or preparing.
	 * 
	 * It also creates the row with it's primary attributes, like type.
	 * 
	 * @access protected
	 * @param mixed[] $src_row The row from the _POST, as it's settings are needed.
	 */
	protected function next_data_row( $src_row ) {
		# This will be the row, initially only with type
		$row = array(
			'type' => $src_row['__type']
		);

		# Add the row as a new variable in $this->value
		$this->value[ ++$this->current_data_row ] = $row;

		# If child classes need to, this will add additional properties, like width, etc.
		if( method_exists( $this, 'add_row_data' ) ) {
			$this->add_row_data( $src_row, $this->value[ $this->current_data_row ] );
		}
	}

	/**
	 * Saves the value when the fields' container is being saved.
	 * 
	 * This goes through the available field groups and forces them to process the value.
	 * 
	 * @access public
	 * @param $src The raw values that are being saved, passed from the container
	 */
	public function save( $src ) {
		# Don't save anything if there is no data about this field
		if( ! isset( $src[ $this->id ] ) || ! is_array( $src[ $this->id ] ) ) {
			return;
		}

		# Clear the current value and data row index
		$this->value = array();
		$this->current_data_row = -1;

		# Keeps the original indexes
		$this->original_indexes = array();

		$field_value = $src[ $this->id ];
		if( get_class( $this ) != 'UF_Field_Repeater' ) ksort( $field_value );

		# Go through each row from the source
		foreach( $field_value as $i => $row ) {
			# If this is additional data, don't treat it as a row
			if( ! is_array( $row ) )
				continue;

			# Don't process the placeholder, which is still an HTML element
			if( ($i . '') == $this->i_placeholder || strpos( $i, 'placeholder_' ) === 0 ) {
				continue;
			}

			# If the saved row does not have a type or there is no such group, something seems to be wrong
			if( ! isset( $row[ '__type' ] ) || ! isset( $this->field_groups[ $row[ '__type' ] ] ) ) {
				uf_die( '<strong>UF_Field_Repeater</strong>: Malformed data! Please send this data to your support: <br>' . serialize($src[$this->input_id]) );
			}

			# Load the row
			$this->next_data_row( $row );

			# Save the indexes that came from $src
			$this->original_indexes[ $i ] = $this->current_data_row;

			# If there is width set, set it too
			if( isset( $row[ 'width' ] ) ) {
				$this->value[ $this->current_data_row ][ 'width' ] = intval( $row[ 'width' ] );
			}

			# Get the raw fields from the group and make them process the value.
			$group = $this->field_groups[ $row['__type'] ];
			foreach($group['fields'] as $field) {
				$field->set_input_id("$this->input_id[$i][" . $field->get_id() . "]");
				$field->save($row);
			}
		}

		# If there are inheritants, allow them to modify the value
		if( method_exists( $this, 'modify_saved_value' ) ) {
			$this->modify_saved_value( $src );
		}

		# Send the data up the tree
		$this->datastore->save_multiple( $this->id, $this->value );
	}

	/**
	 * Receives multiple values from a field and saves them. Proxy to save_value.
	 * 
	 * @access public
	 * @param string $key The ID of the field
	 * @param mixed[] $values The values
	 */
	public function save_multiple( $key, $values = array() ) {
		return $this->save_value($key, $values);
	}

	/**
	 * Deletes a value from the current row.
	 * 
	 * @access public
	 * @param string $key The key of the field/value.
	 */
	public function delete_value( $key ) {
		if( isset( $this->value[ $this->current_data_row ][ $key ] ) )
			unset( $this->value[ $this->current_data_row ][ $key ] );
	}

	/**
	 * Adds a fields group. Used internally by this and child classes.
	 * 
	 * @access protected
	 * @param mixed[] $atts The settings of the group.
	 * @param UF_Field[] $fields The fields of the group
	 */
	protected function add_fields_group( $atts, array $fields ) {
		# The key/type is the most important part of the group. It should be valid for attributes
		$group_key   = sanitize_title( $atts[ 'group_key' ] );
		
		# Prepare the main settings
		$field_group = array(
			'title'       => $atts[ 'group_title' ],
			'fields'      => array(),
			'type'        => $group_key,
			'title_field' => isset( $atts['title_field'] ) ? $atts['title_field'] : '',
			'description' => $atts[ 'group_description' ],
			'icon'        => $atts[ 'group_icon' ]
		);

		# Add min/max width for the layout field
		if( isset( $atts[ 'min_width' ] ) )
			$field_group[ 'min_width' ] = $atts[ 'min_width' ];
		if( isset( $atts[ 'max_width' ] ) )
			$field_group[ 'max_width' ] = $atts[ 'max_width' ];

		# Check all fields and then add them to the group
		$field_keys = array();
		foreach($fields as $field) {
			# Skip the field if not existing or null
			if( ! $field ) {
				continue;
			}

			$key = $field->get_id();
			
			if( isset( $field_keys[ $key ] ) ) {
				uf_die( sprintf( __( 'Error: Trying to register a field with the %s key twice in a repeater group!', 'uf' ), $key ) );
			}

			if( $key == 'type' ) {
				uf_die( __( '&quot;type&quot; is a reserved key in repeaters and cannot be overwritten!', 'uf' ) );
			}

			if( ! is_a( $field, 'UF_Field' ) ) {
				uf_die( '<strong>' . get_class( $this ) . '</strong> only supports fields of type UF_Field!' );
			}

			$field_group['fields'][] = $field;

			# Saves the key to prevent duplicate keys
			$field_keys[ $field->get_id() ] = 1;
		}

		# Add the prepared group to the containing ones
		$this->field_groups[ $group_key ] = $field_group;		
	}

	/**
	 * Adds a new group to the container.
	 * 
	 * @access public
	 * @param string $key The type/key of the group
	 * @param mixed[] $data Attributes like title, title field, description, etc.
	 * @param UF_Field[] $fields The fields that should be added to the repeater
	 */
	public function add_fields( $key, $data, $fields = null ){
		$atts = array(
			'group_key'   => $key,
			'group_title' => ucwords( str_replace( '_', ' ', $key) ),
			'group_description' => '',
			'group_icon'        => ''
		);

		if( ! $fields && isset( $data[0] ) && is_a( $data[0], 'UF_Field' ) ) {
			# If the third argument isn's set, the fields are in the second one
			$fields = $data;
		} else {
			# If the third argument is set, it means that the second one contains atts or title
			if( is_array( $data ) ) {
				if( isset( $data['title'] ) )
					$atts[ 'group_title' ] = $data['title'];

				if( isset( $data['description' ] ) )
					$atts[ 'group_description' ] = $data[ 'description' ];

				if( isset( $data[ 'icon' ] ) )
					$atts[ 'group_icon' ] = $data[ 'icon' ];

				if( isset( $data[ 'title_field' ] ) )
					$atts[ 'title_field' ] = $data[ 'title_field' ];

				if( method_exists( $this, 'setup_group_atts' ) )
					$atts = $this->setup_group_atts( $atts, $data );
			} else {
				# Only a title is passed
				$atts[ 'group_title' ] = $data;
			}
		}

		if($key == '') {
			$atts['group_key'] = 0;
			$atts['group_title'] = '';
		}

		# The title field's value will appear in the handle of the group
		if( ! isset( $atts[ 'title_field' ] ) ) {
			foreach( $fields as $field ) {
				if( is_a( $field, 'UF_Field' ) ) {
					$atts[ 'title_field' ] = $field->get_id();
					break;					
				}
			}
		}

		# Internally, add the group
		$this->add_fields_group( $atts, $fields );

		return $this;
	}

	/**
	 * Displays the field in it's container.
	 * 
	 * @access public
	 */
	public function display_input() {
		global $ultimatefields;
		include( $ultimatefields->themes->path( 'repeater/repeater' ) );
	}

	/**
	 * Displays a prototype of a field. 
	 * 
	 * To do this, the input's ID is changed to include the placeholder.
	 * 
	 * @access protected
	 * @param UF_Field $field The field that will be displayed
	 * @return UF_Field
	 */
	protected function display_prototype(UF_Field $field) {
		# Get the normal ID of the field
		$old_input_id = $field->get_id();

		# Set the custom one
		$field->set_input_id( $this->input_id . '[' . $this->i_placeholder . '][' . $old_input_id . ']' );

		# Reset the field
		$field->reset();

		# Display it with the repeater template
		$field->display( 'repeater' );
	}

	/**
	 * Gets the dependencies of fields inside of the repeater and outputs them.
	 * 
	 * @access public
	 * @return mixed[] The dependencies
	 */
	public function get_inner_dependencies() {
		$deps = array();

		foreach( $this->field_groups as $group_key => $group ) {
			foreach( $group['fields'] as $field ) {
				$field_deps = $field->get_dependencies();

				if( ! empty( $field_deps ) ) {
					$deps[ $group_key ][ $field->get_id() ] = $field_deps;
				}

				# For nested repeaters, get the inner dependencies.
				if( method_exists( $field, 'get_inner_dependencies' ) ) {
					$inner = $field->get_inner_dependencies();
					if( count( $inner ) ) {
						$deps[ $group_key ][  $field->get_id() . '__inner' ] = $inner;
					}
				}
			}
		}

		return $deps;
	}

	/**
	 * Set a limit to the available rows.
	 * 
	 * @access public
	 * @param int $limit The maximum count of rows.
	 */
	public function limit_rows( $limit ) {
		$this->rows_limit = $limit;
		$this->html_attributes[ 'data-limit' ] = $limit;

		return $this;
	}

	/**
	 * Processes a sub-field sub-row value
	 * 
	 * @param string $key The key of the field.
	 * @param mixed $value The value of the field.
	 * @param mixed[] $settings The settings for the sub-field.
	 * @return mixed The eventually processed value.
	 * 
	 * @since 1.0.2
	 * @access protected
	 */
	protected function process_subvalue( $key, $value, $settings ) {
		if( ! isset( $this->processors[ $key ] ) )
			return $value;

		foreach( $this->processors[ $key ] as $i => $priority ) {
			foreach( $priority as $processor ) {
				$value = call_user_func( $processor[ 'callback' ], $value, $settings );
			}
		}

		return $value;
	}

	/**
	 * Process the value based on the settings in the admin.
	 * 
	 * @param int $value The value of the field
	 * @param mixed $data The settings of the field, added through the Ultimate Fields section
	 * 
	 * @return string The content to be shown in the editor.
	 */
	public function process_value( $value, $data ) {
		$processed = array();

		# Transform settings a bit
		$fields_settings = array();
		foreach( $data[ 'repeater_fields' ] as $group ) {
			$group_fields = array();

			foreach( $group[ 'group_fields' ] as $field ) {
				$group_fields[ $field[ 'field_id' ] ] = $field;
			}

			$fields_settings[ $group[ 'key' ] ] = $group_fields;
		}

		# Processed the value when possible
		foreach( $value as $row ) {
			$processed_row = array();

			$row_type_settings = $fields_settings[ $row[ 'type' ] ];

			foreach( $row as $key => $val ) {
				if( isset( $row_type_settings[ $key ] ) ) {
					$processed_row[ $key ] = $this->process_subvalue( $key, $val, $row_type_settings[ $key ] );
				} else {
					$processed_row[ $key ] = $val;					
				}
			}

			$processed[] = $processed_row;
		}

		return $processed;
	}
}