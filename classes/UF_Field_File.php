<?php
/**
 * Displays a field, which lets the user upload files.
 *
 * The field is using the media uploader that is used by WordPress 3.5, but has
 * a fallback to the media gallery, that was used in the verions before that.
 *
 * @since 1.3
 * @package ultimatefields
 */
UF_Field::add_field( 'file', __( 'File', 'uf' ) );
class UF_Field_File extends UF_Field {
	/** @type boolean Indicates if the field supports multilingual values */
	protected $multilingual_support = true;

	/**
	 * Prepares strings & classes + enqueueing media
	 */
	protected function after_constructor() {		
		# All of the settings above apply only in the admin
		if( ! is_admin() )
			return;

		# Prepare texts
		$this->strings = apply_filters( 'uf_file_field_texts', array(
			'btn_text'     => __( 'Select', 'uf' ),
			'delete_text'  => __( 'Remove', 'uf' ),
			'confirm_text' => __( 'Are you sure you want to remove this file?', 'uf' ),
			'use_text'     => __( 'Save & Use', 'uf' )
		), $this );

		# Add some data to the HTML elements
		$this->html_attributes = array(
			'data-confirm-deletion' => $this->strings[ 'confirm_text' ]
		);

		# Enqueue media scripts on admin_init
		remove_action( 'admin_enqueue_scripts', array( 'UF_Field_File', 'enqueue_media' ) );
		add_action( 'admin_enqueue_scripts', array( 'UF_Field_File', 'enqueue_media' ) );
	}

	/**
	 * Makes sure that the needed media scripts are required.
	 */
	static public function enqueue_media() {		
		# Enqueue custom scripts
		wp_enqueue_media();
	}

	/**
	 * Displays a preview and a file chooser
	 */
	function display_input() {
		echo '<div class="uf-file-wrap">';
		
		# Display the preview, this is different for each file type - generic, image and audio.
		$this->display_preview();

		# Add button
		echo '<span class="buttons">';
			# Select button
			echo '<a href="#" class="button-primary">' . $this->strings[ 'btn_text' ] . '</a>';

			# Remove button
			$style = $this->value ? '' : ' style="display:none"';
			echo '<a href="#" class="button-secondary uf-remove-file"' . $style . '>'
				. $this->strings[ 'delete_text' ] .
				'</a>';
		echo '</span>';

		# Hidden ID input
		printf(
			'<input type="hidden" id="%s" name="%s" value="%s" />',
			esc_attr( $this->input_id ),
			esc_attr( $this->input_id ),
			esc_attr( $this->value )
		);

		echo '</div>';
	}

	/**
	 * Displays a preview of the chosen file if one is set or
	 * a placeholder for that preview.
	 */
	protected function display_preview() {
		if( $this->value ) {
			# Prepares values
			$link      = wp_get_attachment_url( $this->value );
			$text      = get_the_title( $this->value );
			$edit_link = get_edit_post_link( $this->value );
			$icon      = wp_get_attachment_image( $this->value, '', true );

			// The preview should be visible
			$style = 'display:block';
		} else {
			// Blank title, link and edit link
			$text      = '';
			$link      = '';
			$edit_link = '';
			$icon      = '';

			// The preview should be hidden
			$style = 'display:none';
		}

		# Display the preview itself
		echo '<div class="uf-file-preview" style="' . $style . '"">';
			# Output the file's icon
			echo $icon;

			// Show the file name + a blank space to let the text breathe
			echo '<span class="file-title">' . $text . '</span> ';

			// This button links to the file itself
			echo '<a href="' . $link . '" target="_blank" class="button-secondary file-link">' . __( 'View', 'uf' ) . '</a> ';

			// This button links to the file's edit screen
			echo '<a href="' . $edit_link . '" target="_blank" class="button-secondary edit-link">' . __( 'Edit', 'uf' ) . '</a>';
		echo '</div>';
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Enables uploading and chosing any file through the media uploader.', 'uf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return UF_Field[]
	 */
	static public function additional_settings() {
		return array(
			UF_Field::factory( 'separator', 'output_data_separator', __( 'Output Settings', 'uf' ) )
				->set_description( __( 'The following settings will affect the output of this field when using the &quot;uf&quot; function or shortcode.', 'uf' ) ),
			UF_Field::factory( 'select', 'output_type', __( 'Output Type', 'uf' ) )
				->add_options( array(
					'link' => __( 'A link to the file', 'uf' ),
					'url'  => __( 'The URL of the file', 'uf' ),
					'id'   => __( 'The ID of the file', 'uf' ),
				) )
		);
	}

	/**
	 * Process the value based on the settings in the admin
	 * 
	 * @param int $value The ID of the image
	 * @param mixed $data The settings of the field
	 */
	public function process_value( $value, $data ) {
		if( ! isset( $data[ 'output_type' ] ) ) {
			$data[ 'output_type' ] = 'link';
		}

		switch( $data[ 'output_type' ] ) {
			case 'link':
				$value = wp_get_attachment_link( $value );
				break;

			case 'url':
				$value = wp_get_attachment_url( $value );
				break;
		}

		return $value;
	}
}