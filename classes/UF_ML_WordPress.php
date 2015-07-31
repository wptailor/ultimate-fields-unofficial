<?php
add_filter( 'uf_ml_adapters', 'uf_mlwp_add_adapter' );
function uf_mlwp_add_adapter( $adapters ) {
	$adapters[] = 'UF_ML_WordPress';
	return $adapters;
}

/**
 * Provides multilingual functionality through the Multilingual WordPRess plugin.
 */
class UF_ML_WordPress implements UF_ML_Adapter {
	private $data;
	
	/**
	 * Check for qTranslate existance
	 * 
	 * @return boolean Indicates if the plugin is enabled.
	 */
	static function check() {
		# Check for qTranslate's existance
		return function_exists( '_mlwp' );
	}

	/**
	 * Prepare a proper format for enabled languages and return them
	 * 
	 * @return mixed[] THe languages
	 */
	public function get() {
		global $q_config;

		if( $this->data )
			return $this->data;

		$inactive_languages   = array();
		$active_languages     = array();
		$this->language_codes = array();

		$flag_location = get_option( 'qtranslate_flag_location' );
		if( ! $flag_location ) {
			$flag_location = $q_config[ 'flag_location' ];
		}

		foreach( _mlwp()->get_options( 'languages' ) as $lang => $data ) {
			$language = array(
				'active' => _mlwp()->current_lang == $lang,
				'code'   => $lang,
				'name'   => $data[ 'label' ],
				'flag'   => _mlwp()->plugin_url . 'flags/16/' . $data[ 'icon' ]
			);

			$this->language_codes[] = $lang;

			if( $language['active'] ) {
				$active_languages[] = $language;
			} else {
				$inactive_languages[] = $language;
			}
		}

		$this->data = array_merge( $active_languages, $inactive_languages );
		$this->data = apply_filters( 'uf_ml_q_data', $this->data );
		return $this->data;
	}

	/**
	 * Extract a language from a multilingual string.
	 * Mostly uses qtrans_split, but ensures that it is available
	 * 
	 * @param string $value The value to be splitted
	 * @param string $language The code of the required language
	 * @return mixed The splitted value or the original if it is not multilingual
	 */
	public function split( $value, $language = null ) {
		# If there is no language passed, use the active one
		if( ! $language ) {
			$language = _mlwp()->current_lang;
		}

		# Check if values are actually multilingual
		if( ! is_string( $value ) ) {
			return $value;
		}

		# Split the values
		$value = apply_filters( 'uf_ml_q_before_split', $value, $language );
		$values = _mlwp()->get_translations( $value );
		$values = apply_filters( 'uf_ml_q_after_split', $values , $language);
		
		if( isset( $values[$language] ) ) {
			$nice = array();

			foreach( $values as $lang => $data ) {
				$nice[ $lang ] = maybe_unserialize( $data );
			}

			return apply_filters( 'uf_ml_q_split', $nice[$language], $value, $language );
		} else {
			return apply_filters( 'uf_ml_q_split', $value, $value, $language );
		}
	}

	/**
	 * Merges multiple values into a string.
	 * 
	 * @param mixed[] $values The items to be joined
	 * @return string The joined data.
	 */
	public function join( $values ) {
		$nice = array();

		foreach( $values as $lang => $data ) {
			$nice[ $lang ] = maybe_serialize( $data );
		}

		return empty( $nice ) ? array() : apply_filters( 'uf_ml_q_join', _mlwp()->join_translations( $nice ) );
	}
}