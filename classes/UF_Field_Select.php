<?php
UF_Field::add_field( 'select',__( 'Select', 'uf' ) );
class UF_Field_Select extends UF_Field {
	protected $options = array(),
			  $no_options_message,
			  $multilingual_support = true;

	protected function check_options( $default_message ) {
		if(empty($this->options)) {
			if(!$this->no_options_message)
				$this->no_options_message = $default_message;

			echo '<p class="only-child">' . $this->no_options_message . '</p>';
			return false;
		}		

		return true;
	}

	protected function select( $input_id, $options, $active = '' ) {
		$output = '<select name="' . esc_attr( $input_id ) . '" id="' . esc_attr( $input_id ) . '">';
		foreach($options as $key => $option) {
			$selected = $active == $key ? ' selected="selected"' : '';
			$output .= '<option value="' . esc_attr($key) . '"' . $selected  . '>' . $option . '</option>';
		}
		$output .= '</select>';

		return $output;
	}

	public function display_input() {
		if(!$this->check_options( __('This select has no options.', 'uf') )) {
			return;
		}

		echo $this->select( $this->input_id, $this->options, $this->value );
	}

	public function add_options(array $options) {
		$this->options += $options;
		return $this;
	}

	public function set_no_options_message($message) {
		$this->no_options_message = $message;
		return $this;
	}

	public function add_posts(array $args) {
		$args = array_merge( array(
			'posts_per_page' => -1
		), $args );

		$items = get_posts($args);
		foreach($items as $item) {
			$title = apply_filters('the_title', $item->post_title);
			if( ! $title ) {
				$title = sprintf( __( '#%d: [No Title]', 'uf' ), $item->ID );
			}
			$this->options[$item->ID] = $title;
		}
		return $this;
	}

	public function chosen() {
		$this->html_attributes = array(
			'data-chosen' => true
		);

		wp_enqueue_script( 'chosen' );
		wp_enqueue_style( 'chosen-css' );

		return $this;
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a drop-down with predefined options.', 'uf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return UF_Field[]
	 */
	static public function additional_settings() {
		$post_types = array();
		$excluded = apply_filters( 'uf_excluded_post_types', array( 'attachment', 'ultimatefields' ) );
		$raw = get_post_types( array(
			'show_ui' => true
		), 'objects' );
		foreach( $raw as $id => $post_type ) {
			if( in_array( $id, $excluded ) ) {
				continue;
			}

			$post_types[ $id ] = $post_type->labels->name;
		}

		return array(
			'values_source' => UF_Field::factory( 'select', 'values_source', __( 'Values Source', 'uf' ) )
				->add_options(array(
					'textarea' => __( 'Manually Entered', 'uf' ),
					'posttype' => __( 'Automatically add pages/posts', 'uf' )
				)),
			'options' => UF_Field::factory( 'repeater', 'options', __( 'Options', 'uf' ) )
				->set_dependency( 'values_source', 'textarea' )
				->add_fields( 'option', __( 'Option', 'uf' ), array(
					UF_Field::factory( 'text', 'value', __( 'Value', 'uf' ) ),
					UF_Field::factory( 'text', 'key', __( 'Key', 'uf' ) )
				)),
			'post_type' => UF_Field::factory( 'select', 'post_type', __( 'Post Type', 'uf' ) )
				->add_options( $post_types )
				->set_description( __( 'If you&apos;ve choosen &quot;Automatically add pages/posts&quot; above, please choose the required post type.', 'uf' ) )
				->set_dependency( 'values_source', 'posttype' ),
			'jquery_plugin' => UF_Field::factory( 'checkbox', 'jquery_plugin', __( 'jQuery Enchanced', 'uf' ) ),

			'output_data_separator' => UF_Field::factory( 'separator', 'output_data_separator', __( 'Output Settings', 'uf' ) )
				->set_description( __( 'The following settings will affect the output of this field when using the &quot;uf&quot; function or shortcode.', 'uf' ) ),
			'output_data_type' => UF_Field::factory( 'select', 'output_data_type', __( 'Output Item', 'uf' ) )
				->add_options( array(
					'value' => __( 'Output the value of the select, the way it is saved', 'uf' ),
					'text'  => __( 'Output the label of the selected value', 'uf' )
				) )
		);
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
		if( ! isset( $data[ 'output_data_type' ] ) || $data[ 'output_data_type' ] == 'value' ) {
			return $value;
		} else {
			return $this->options[ $value ];
		}
	}

	/**
	 * Displays a text input for default value
	 *
	 * @return string The type of the field
	 */
	static public function get_default_value_type() {
		return 'text';
	}
}