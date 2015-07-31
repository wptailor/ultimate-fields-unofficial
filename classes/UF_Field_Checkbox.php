<?php
UF_Field::add_field( 'checkbox',__( 'Checkbox', 'uf' ) );
class UF_Field_Checkbox extends UF_Field {
	public $multilingual_support = true;
	protected $text;

	public function set_text($text) {
		$this->text = $text;
		return $this;
	}

	public function display_input() {
		# Revert to "Yes" if no other text set
		if( ! $this->text ) {
			$this->text = __( 'Yes', 'uf' );
		}

		$checked = $this->value ? ' checked="checked"' : '';
		echo '<input type="checkbox" name="' . $this->input_id . '" id="' . $this->input_id . '" ' . $checked . ' />';
		echo '<label for="' . $this->input_id . '" class="text">' . $this->text . '</label>';
	}

	public function save($data) {
		if( $this->is_multilingual ) {
			$languages = UF_ML::get();
			$this->value = array();

			foreach($languages as $l) {
				$this->value[ $l['code'] ] = isset( $data[$this->id][ $l['code'] ] );
			}

			$this->value = UF_ML::join( $this->value );
		} else {
			$this->value = isset($data[$this->id]);
		}

		$this->datastore->save_value($this->id, $this->value );
	}

	/**
	 * Sets a particular datastore.
	 * 
	 * @param UF_Datastore $datastore The new datastore that should be set.
	 * @param boolean $optional If the datastore is optional and there is one set already, ignore.
	 * @return UF_Field The instance of the field.
	 */
	public function set_datastore( UF_Datastore $datastore, $optional = false ) {
		global $uf_processors;

		if( ( $optional && ! $this->datastore ) || ! $optional ) {
			$this->datastore = $datastore;

			$value = $this->datastore->get_value( $this->id );

			if( $value ) {
				$this->set_value( $value );
			} elseif( is_string( $value ) ) {
				$this->value = $value === '' ? false : true;
			}
		}

		return $this;
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a single checkbox. Useful for toggling functionality.', 'uf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return UF_Field[]
	 */
	static public function additional_settings() {
		return array(
			UF_Field::factory( 'text', 'text', __( 'Text', 'uf' ) )
				->set_description( __( 'This text could appear next to the checkbox itself, ex. &quot;Enable&quot;', 'uf' ) )
				->set_default_value( __( 'Yes', 'uf' ) )
		);
	}
}