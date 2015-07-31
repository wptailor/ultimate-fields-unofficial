<?php
/**
 * The following functions utilize the use of the qTranslate
 * plugin (http://www.qianqin.de/qtranslate/) and make it 
 * easier to display data.
 * 
 * Right now Ultimate Fields supports only qTranslate, but it should be compatible
 * with other plugins too in two cases:
 * - If the plugin displays multiple pages for each language, everything should be fine.
 * - If the plugin works like qTranslate, using a single edit screen, there should be
 *   a way to merge values the same way qTranslate does.
 */

/**
 * This interface must be followed though all adapters if ones are used
 */
interface UF_ML_Adapter {
	/**
	 * Checks if the adapter is available for use.
	 * 
	 * @return boolean
	 */
	static function check();

	/**
	 * Prepares all available languages
	 * 
	 * @return mixed[] A multi-dimensional array that contains language code, flag and name
	 */
	public function get();

	/**
	 * Splits a single string which contains multiple values
	 * 
	 * @param string $value The value to be splieed
	 * @param string $language The language code needed. Falls back to the active one.
	 * @return mixed
	 */
	public function split( $value, $language = null );

	/**
	 * Combines multiple values into a single string
	 * 
	 * @param mixed[] $values The values that need to be joined
	 * @return string Joined values
	 */
	public function join( $values );
}