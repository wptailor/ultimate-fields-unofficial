<?php
/**
 * Provides multilingual functionality through the qTranslate plugin.
 */
class UF_Qtranslate implements UF_ML_Adapter {
	private $data;
	
	/**
	 * Check for qTranslate existance
	 * 
	 * @return boolean Indicates if the plugin is enabled.
	 */
	static function check() {
		# Check for qTranslate's existance
		return function_exists( 'qtrans_getSortedLanguages' ) && count( qtrans_getSortedLanguages() ) > 1;
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

		$inactive_languages = array();
		$active_languages   = array();
		$this->language_codes     = array();

		$flag_location = get_option( 'qtranslate_flag_location' );
		if( ! $flag_location ) {
			$flag_location = $q_config[ 'flag_location' ];
		}

		foreach( $q_config['enabled_languages'] as $lang ) {
			$language = array(
				'active' => $q_config['language'] == $lang,
				'code'   => $lang,
				'name'   => $q_config['language_name'][$lang],
				'flag'   => get_bloginfo( 'url' ) . '/wp-content/' . $flag_location . $q_config['flag'][$lang]
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
		global $q_config;

		# qTranslate is not available
		if( !function_exists( 'qtrans_split' ) )
			return $value;

		# If there is no language passed, use the active one
		if( ! $language ) {
			$language = $q_config['language'];
		}

		# Check if values are actually multilingual
		if( ! is_string( $value ) ) {
			return $value;
		}

		# Split the values
		$values = apply_filters( 'uf_ml_q_before_split', $value, $language );
		$values = qtrans_split( $value );
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

		return empty( $nice ) ? array() : apply_filters( 'uf_ml_q_join', qtrans_join( $nice ) );
	}
}