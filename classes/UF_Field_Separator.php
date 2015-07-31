<?php
/**
 * Displays a simple text separator
 */
UF_Field::add_field( 'separator',__( 'Heading', 'uf' ) );
class UF_Field_Separator extends UF_Field {
	function display( $location = null ) {
		global $ultimatefields;

		include( $ultimatefields->themes->path( 'field/separator', $location ) );
	}

	/**
	 * Get setting fields for the settings page.
	 * Calls static additional_settings() for child classes.
	 * 
	 * @param string $field_type The type of the field.
	 * @return UF_Field[] The fields for the group in the Fields repeater
	 */
	static public function settings_fields( $field_type ) {
		$fields = array(
			UF_Field::factory( 'text', 'title', __( 'Title', 'uf' ) )
				->multilingual()
				->set_description( __( 'This title will separate different kinds of content.', 'uf' ) )
				->make_required(),
			UF_Field::factory( 'text', 'description', __( 'Description', 'uf' ) )
				->multilingual()
				->set_description( __( 'This text will appear under the title and may be used to give users directions what to do.', 'uf' ) )
		);

		return apply_filters( 'uf_field_settings_fields', $fields, $field_type );
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Separates groups of fields with different purposes.', 'uf' );
	}
}