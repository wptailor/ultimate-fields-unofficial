<?php
UF_Field::add_field( 'textarea',__( 'Textarea', 'uf' ) );
class UF_Field_Textarea extends UF_Field {
	protected $rows = 5,
			$multilingual_support = true;

	public function display_input() {
		echo '<textarea name="' . $this->input_id . '" id="' . $this->input_id . '" rows="' . $this->rows . '">' . htmlspecialchars( stripslashes( $this->value ) ) . '</textarea>';
	}

	public function set_rows($rows) {
		$this->rows = $rows;
		return $this;
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a basic text area with adjustable number of rows.', 'uf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return UF_Field[]
	 */
	static public function additional_settings() {
		return array(
			UF_Field::factory( 'text', 'rows', __( 'Rows', 'uf' ) )
				->set_default_value( 5 ),
			UF_Field::factory( 'separator', 'output_data_separator', __( 'Output Settings', 'uf' ) )
				->set_description( __( 'The following settings will affect the output of this field when using the &quot;uf&quot; function or shortcode.', 'uf' ) ),
			UF_Field::factory( 'checkbox', 'output_add_paragraphs', __( 'Add Paragraphs', 'uf' ) )
				->set_text( __( 'Automatically add paragraphs and new lines.', 'uf' ) ),
			UF_Field::factory( 'checkbox', 'output_apply_shortcodes', __( 'Apply Shortcodes', 'uf' ) )
				->set_text( __( 'Apply', 'uf' ) )
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

		$value = convert_chars( wptexturize( $value ) );

		if( isset( $data[ 'output_add_paragraphs' ] ) && $data[ 'output_add_paragraphs' ] ) {
			$value = wpautop( $value );
		}

		if( isset( $data[ 'output_apply_shortcodes' ] ) && $data[ 'output_apply_shortcodes' ] ) {
			$value = do_shortcode( shortcode_unautop( $value ) );
		}
		return $value;
	}
}
