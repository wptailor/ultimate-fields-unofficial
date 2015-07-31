<?php
UF_Field::add_field( 'text',__( 'Text', 'uf' ) );
class UF_Field_Text extends UF_Field {
	protected $autocomplete = array(),
			$multilingual_support = true;

	public function display_input() {
		echo '<input type="text" name="' . $this->input_id . '" id="' . $this->input_id . '" value="' . esc_attr( stripslashes( $this->value ) ) . '" />';

		if(count($this->autocomplete)) {
			echo '<div style="display:none;" class="uf-autocompletes">' . json_encode($this->autocomplete) . '</div>';
		}
	}

	public function add_suggestions(array $suggestions) {
		$this->autocomplete += $suggestions;

		wp_enqueue_script( 'jquery-ui-autocomplete' );

		return $this;
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a text input with optional autocomplete suggestions.', 'uf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return UF_Field[]
	 */
	static public function additional_settings() {
		return array(
			UF_Field::factory( 'textarea', 'autocomplete_suggestions', __( 'Autocomplete Suggestions', 'uf' ) )
				->set_description( __( 'You may list predefined values here. One value per row.', 'uf' ) ),

			UF_Field::factory( 'separator', 'output_data_separator', __( 'Output Settings', 'uf' ) )
				->set_description( __( 'The following settings will affect the output of this field when using the &quot;uf&quot; function or shortcode.', 'uf' ) ),
			UF_Field::factory( 'select', 'output_format_value', __( 'Format Value', 'uf' ) )
				->add_options( array(
					'none' => __( 'None', 'uf' ),
					'html' => __( 'HTML Entities', 'uf' )
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
		if( isset( $data[ 'output_format_value' ] ) && $data[ 'output_format_value' ] ) {
			return esc_html( $value );
		}	

		return $value;
	}
}