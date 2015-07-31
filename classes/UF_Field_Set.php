<?php
/**
 * Displays a list of checkboxes
 */
UF_Field::add_field( 'set',__( 'Set', 'uf' ) );
class UF_Field_Set extends UF_Field_Select {
	protected $is_sortable          = false,
			  $order                = array(),
			  $separator            = '_^_',
			  $multilingual_support = true,
			  $html_attributes = array(
			  	'data-sortable' => '0',
			  	'data-separator' => '_^_'
			  );

	function sortable($sortable = true) {
		$this->is_sortable = $sortable;
		$this->html_attributes['data-sortable'] = $this->is_sortable ? '1' : '0';
		wp_enqueue_script( 'jquery-ui-sortable' );
		return $this;
	}

	function prepare_order() {
		if( $this->is_sortable ) {
			$order = $this->datastore->get_value( 'order_' . $this->id );

			if( $this->is_multilingual && $this->language ) {
				$order = UF_ML::split( $order, $this->language );
			}

			if($order) {
				$order_val = $order;
				$order = explode($this->separator, $order);
				$this->options = $this->sort_array_by_array($this->options, $order);
			} else {
				$order_val = implode($this->separator, array_keys($this->options));
			}

			$this->order = $order_val;
		}		
	}

	function display_input() {
		$this->prepare_order();
		?>
		<div class="uf-field-set">
			<input type="hidden" name="<?php echo $this->input_id ?>[__<?php echo md5( $this->id ) ?>__]" value="" />

			<?php
			if( !is_array($this->value) ) {
				$this->value = explode( $this->separator, $this->value );
			}

			foreach( $this->options as $key => $val ):
				$checked = $this->value && in_array($key, $this->value) ? 'checked="checked"' : '';
				?>
				<label>
					<input type="checkbox" name="<?php echo $this->input_id ?>[]" value="<?php echo esc_attr($key) ?>" <?php echo $checked ?> />
					<span><?php echo $val ?></span>
					<br />
				</label>
			<?php endforeach; ?>

			<?php if($this->is_sortable): ?>
			<input type="hidden" name="<?php echo $this->input_id ?>[order]" value="<?php echo $this->order ?>" />
			<?php endif; ?>
		</div>
		<?php
	}

	function save($source) {
		if( isset( $source[$this->id] ) && $source[$this->id] != NULL ) {
			$value = $source[$this->id];

			if( $this->is_multilingual ){
				$result_values = array();
				$result_order  = array();

				foreach( $value as $code => $language ) {
					$result_values[ $code ] = $language;

					unset( $result_values[ $code ][ "__" . md5( $this->id ) . "__" ] );

					if( $this->is_sortable ) {
						$result_order[ $code ] = $source[$this->id][ $code ]['order'];
					}
				}

				if( isset( $result_values[ 'order' ] ) ) {
					unset( $result_values[ 'order' ] );
				}

				$result_values = UF_ML::join( $result_values );
				$result_order  = UF_ML::join( $result_order );

				$this->datastore->save_value($this->id, $result_values);
				$this->datastore->save_value('order_' . $this->id, $result_order);
			} else {
				if( $value == NULL )
					$value = array();

				unset( $value[ "__" . md5( $this->id ) . "__" ] );

				$this->value = $value;
				if( is_array( $this->value ) && isset( $this->value['order'] ) ) {
					unset( $this->value['order'] );
				}

				$this->datastore->save_value($this->id, $this->value);

				if( $this->is_sortable ) {
					$this->order = $source[$this->id]['order'];
					$this->datastore->save_value('order_' . $this->id, $this->order);
				}
			}
		}
	}

	function sort_array_by_array( $array, $order_array ) {
	    $ordered = array();

	    foreach( $order_array as $key ) {
	        if( array_key_exists($key,$array) ) {
                $ordered[$key] = $array[$key];
                unset( $array[$key] );
	        }
	    }
	    return $ordered + $array;
	}	

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays multiple checkboxes, optionally sortable.', 'uf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return UF_Field[]
	 */
	static public function additional_settings() {
		$parent_fields = parent::additional_settings();

		# Disable the jQuery thingy
		unset( $parent_fields[ 'jquery_plugin' ] );
		unset( $parent_fields[ 'output_data_separator' ] );
		unset( $parent_fields[ 'output_data_type' ] );

		return array_merge( $parent_fields, array(
			UF_Field::factory( 'checkbox', 'sortable', __( 'Sortable', 'uf' ) )
				->set_description( __( 'If checked, the user will be able to change the order of the options.', 'uf' ) ),
			UF_Field::factory( 'separator', 'output_data_separator', __( 'Output Settings', 'uf' ) )
				->set_description( __( 'The following settings will affect the output of this field when using the &quot;uf&quot; function or shortcode.', 'uf' ) ),
			UF_Field::factory( 'select', 'output_type', __( 'Output Type', 'uf' ) )
				->add_options(array(
					'unordered_list' => __( 'Unordered List', 'uf' ),
					'join'           => __( 'A single line, joined with commas.', 'uf' )
				)),
			UF_Field::factory( 'select', 'output_item', __( 'Output Item', 'uf' ) )
				->add_options( array(
					'value' => __( 'Output the value of the select, the way it is saved', 'uf' ),
					'text'  => __( 'Output the label of the selected value', 'uf' )
				) )
		) );
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
		if( ! isset( $data[ 'output_type' ] ) ) {
			return $value;
		}

		if( $data[ 'output_item' ] == 'value' ) {
			$values = $value;
		} else {
			$values = array();
			foreach( $value as $key ) {
				$values[] = $this->options[ $key ];
			}
		}

		if( $data[ 'output_type' ] == 'unordered_list' ) {
			$output = '<ul><li>' . implode( '</li><li>', $values ) .  '</li></ul>';
			return $output;
		} else {
			return implode( ', ', $values );
		}
	}
}