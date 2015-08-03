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
		$content = stripslashes( $this->value );
		$content = wpautop( $content );

		global $wp_version;
		if( version_compare( $wp_version, '3.8.2', '>' ) ) {
			echo '<div id="wp-' . $this->mce_id . '-wrap" class="wp-core-ui wp-editor-wrap tmce-active" data-mce-id="' . $this->mce_id . '">
				<div id="wp-' . $this->mce_id . '-editor-tools" class="wp-editor-tools hide-if-no-js">
					<div id="wp-' . $this->mce_id . '-media-buttons" class="wp-media-buttons">' . $media_buttons . '</div>

					<div class="wp-editor-tabs">
						<a id="' . $this->mce_id . '-html" class="wp-switch-editor switch-html" onclick="switchEditors.switchto(this);">Text</a>
						<a id="' . $this->mce_id . '-tmce" class="wp-switch-editor switch-tmce" onclick="switchEditors.switchto(this);">Visual</a>
					</div>
				</div>

				<div id="wp-' . $this->mce_id . '-editor-container" class="wp-editor-container">
					<textarea class="wp-editor-area" rows="' . $this->rows . '" autocomplete="off" cols="40" name="' . $this->input_id . '" id="' . $this->mce_id . '">' . esc_html( $content ) . '</textarea>
				</div>
			</div>';			
		} else {
			echo '<div id="wp-' . $this->mce_id . '-wrap" class="wp-editor-wrap tmce-active" data-mce-id="' . $this->mce_id . '">
					<div id="wp-' . $this->mce_id . '-editor-tools" class="wp-editor-tools">
						<a id="' . $this->mce_id . '-html" class="hide-if-no-js wp-switch-editor switch-html" onclick="switchEditors.switchto(this);">HTML</a>
						<a id="' . $this->mce_id . '-tmce" class="hide-if-no-js wp-switch-editor switch-tmce" onclick="switchEditors.switchto(this);">Visual</a>
						
						<div id="wp-' . $this->mce_id . '-media-buttons" class="hide-if-no-js wp-media-buttons">' . $media_buttons . '</div>
				</div>

				<div id="wp-' . $this->mce_id . '-editor-container" class="wp-editor-container">
					<textarea class="wp-editor-area" rows="' . $this->rows . '" cols="40" name="' . $this->input_id . '" id="' . $this->mce_id . '">' . esc_html( $content ) . '</textarea>
				</div>
			</div>';			
		}
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
