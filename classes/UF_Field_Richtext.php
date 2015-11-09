<?php
UF_Field::add_field( 'richtext',__( 'Rich Text Editor', 'uf' ) );
class UF_Field_Richtext extends UF_Field_Textarea {
	public $multilingual_support = true;

	public static function dummy_editor() {
		# Avoid creating more than one editor
		remove_action('uf_after_container', array('UF_Field_Richtext', 'dummy_editor'));

		echo '<div style="display:none;">';

		wp_editor('', 'uf_dummy_editor_id', array(
			'textarea_name' => 'uf_dummy_editor_name'
		));

		echo '</div>';
	}

	function after_constructor() {
		if(!is_admin())
			return;

		add_action('uf_after_container', array('UF_Field_Richtext', 'dummy_editor'));
	}

	function display_input() {
		$this->mce_id = md5(microtime());

		ob_start();
		do_action( 'media_buttons', $this->mce_id );
		$media_buttons = ob_get_clean();
		$content = wpautop( stripslashes( $this->value ) );
		$editor_id = esc_attr( $this->mce_id );

		$settings = array(
			'textarea_name' => esc_attr( $this->input_id ),
			'textarea_rows' => 7,
			'teeny' => true
			);

		wp_editor( $content, $editor_id, $settings );
	}

	/**
	 * Add new rows where needed
	 *
	 * @param string
	 */
	public function filter_value( $value ) {
		# First, strip unneeded slashes
		$value = stripslashes( $value );

		return $value;
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 *
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Enables input through the WYSIWYG editor.', 'uf' );
	}
}
