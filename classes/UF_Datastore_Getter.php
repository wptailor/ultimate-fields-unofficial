<?php
/**
 * Holds functionality that enables easy retrieving of
 * fields' values through a single function
 */
class UF_Datastore_Getter {
	/** @type string[] Hold all available datastores. Format key => classname */
	protected $datastores = array(
		'meta'   => 'UF_Datastore_Postmeta',
		'post'   => 'UF_Datastore_Postmeta',
		'option' => 'UF_Datastore_Options'
	);

	/** @type mixed[] Hold callbacks for processing values based on field type */
	public $processors = array();

	/**
	 * Initiate available datastore and processor list
	 */
	public function __construct() {
		# If additional datastores are added, they should be put here too.
		# Also, containers like term meta will add a key for every taxonomy.
		$this->datastores = apply_filters( 'uf_datastores', $this->datastores );

		# Add callbacks for most field classes (at least the ones that make sense)
		$this->processors = apply_filters( 'uf_processors', $this->processors );

		# Add this as a shortcode too
		add_shortcode( 'uf', array( $this, 'shortcode' ) );
	}

	/**
	 * Prepares type and item_id based on a single string
	 * 
	 * @param string|int The type
	 * @return stdClass An object containing the type and item ID
	 */
	public function prepare_type( $type = null ) {
		$item_id = 0;

		if( $type ) {
			# There is something passed, process it
			if( preg_match( '~^\d+$~', $type ) ) {
				# It's just a number
				$item_id = intval( $type );
				$type    = 'meta';
			} elseif( preg_match( '~^([A-Z_-]+)_(\d+)$~i', $type, $matches ) ) {
				# There is type + ID
				$type = $matches[ 1 ];
				$item_id = $matches[ 2 ];

				if( ! isset( $this->datastores[ $matches[ 1 ] ] ) ) {
					UF_Exceptions::add( __( 'Invalid type argument provided to get_uf!', 'uf' ) );
				}
			} elseif( ! isset( $this->datastores[ $type ] ) ) {
				UF_Exceptions::add( __( 'Invalid type argument provided to get_uf!', 'uf' ) );
			}
		} else {
			$type = 'meta';
			$item_id = get_the_ID();
		}

		$data = new stdClass();
		$data->type = $type;
		$data->id   = $item_id;

		return $data;
	}

	/**
	 * Get the value of a particular field, based on it's type
	 * 
	 * @param string $key The key of the field
	 * @param string|int Either a ID of the requested post or something like <datastore_type>_<id> if another type is needed.
	 * @return mixed The value
	 */
	public function get( $key, $type = '' ) {
		# Prepare the item
		$item = $this->prepare_type( $type );

		# Now that the type and ID are determined, prepare the datastore
		$datastore = new $this->datastores[ $item->type ];
		if( $item->id ) {
			$datastore->set_id( $item->id );
		}

		$value = $datastore->get_value( $key );
		$value = UF_ML::split( $value );

		return $value;
	}

	/**
	 * Output the value of a particular fields. If settings are available, they will be used to tranform the value.
	 *
	 * @param string $key The key of the field
	 * @param string|int Either an ID of the requested post or something like <datastore_type>_<id> if another type is needed
	 */
	public function uf( $key, $type = null, $echo = true ) {
		# Prepare the item and fetch the normal value
		$item = $this->prepare_type( $type );

		# Now that the type and ID are determined, prepare the datastore
		if( method_exists( $this->datastores[ $item->type ], 'get_current_item_value' ) ) {
			# There is a static method that will provide the value
			$value = call_user_func( array( $this->datastores[ $item->type ], 'get_current_item_value' ), $key );
		} else {
			$datastore = new $this->datastores[ $item->type ];
			if( $item->id ) {
				$datastore->set_id( $item->id );
			}
			$value = $datastore->get_value( $key );		
		}

		# Extract the value if it is multilingual
		$value = UF_ML::split( $value );

		# The value will be processed here
		$type_class = $this->datastores[ $item->type ];
		if( isset( $this->processors[ $type_class ] ) && isset( $this->processors[ $type_class ][ $key ] ) ) {
			foreach( $this->processors[ $type_class ][ $key ] as $priority => $processors ) {
				foreach( $processors as $processor ) {
					$value = call_user_func( $processor[ 'callback' ], $value, $processor[ 'data' ] );
				}
			}
		}

		# Output the final result
		if( $echo ) {
			echo $value;
		} else {
			return $value;
		}
	}

	/**
	 * Enables using uf() as a shortcode
	 *
	 * @param mixed $args The args that are provided to the shortcode
	 * @param string $content The content that is in the shortcode. It should be empty
	 * @return string The output of the fields
	 */
	public function shortcode( $atts, $content = null ) {
		extract( shortcode_atts( array(
			'key'  => '',
			'type' => ''
		), $atts ) );

		return uf( $key, $type, false );
	}

	/**
	 * Retrieves repeater values from the database and then processes all
	 * inner repeater fields.
	 * 
	 * @since 1.0.2
	 * @access public
	 * 
	 * @param string $key The key of the repeater
	 * @param string|int Either an ID of the requested post or something
	 * 				like <datastore_type>_<id> if another type is needed
	 * 
	 * @return mixed[] A two-level array with processed rows & items.
	 */
	public function get_repeater( $key, $type ) {
		return $this->uf( $key, $type, false );
	}
}

/**
 * Create an instance of the class
 */
$GLOBALS[ 'uf_datastore_getter' ] = new UF_Datastore_Getter();

/**
 * Add a proxy for uf() and get_uf()
 */
function uf( $key, $type = null, $echo = true ) {
	return $GLOBALS[ 'uf_datastore_getter' ]->uf( $key, $type, $echo );
}

function get_uf( $key, $type = null ) {
	return $GLOBALS[ 'uf_datastore_getter' ]->get( $key, $type );
}

function get_uf_repeater( $key, $type = null ) {
	return $GLOBALS[ 'uf_datastore_getter' ]->get_repeater( $key, $type );
}