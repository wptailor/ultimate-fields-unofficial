<?php
/**
 * Save Ultimate Fields errors which could be collected into a notice.
 */
class UF_Exceptions {
	static $catchable = array(), $buffer = array();

	/**
	 * Start buffering a certain type of errors
	 * 
	 * @param string $type The type of the exception
	 */
	static function buffer( $type ) {
		self::$catchable[ $type ] = 1;

		if( ! isset( self::$buffer[ $type ] ) ) {
			self::$buffer[ $type ] = array();
		}
	}

	/**
	 * Stop buffering
	 * 
	 * @param string $type The type of the exception
	 */
	static function stop_buffering( $type ){
		if( isset( self::$catchable[ $type ] ) ) {
			unset( self::$catchable[ $type ] );
		}
	}

	/**
	 * Adds an exception to the pool.
	 * 
	 * @param string $message The message of the error
	 * @param string $type The type of the error/notification
	 */
	static function add( $message, $type = null ) {
		# If there's no type set, we can't know if it should be saved
		if( ! $type ) {
			uf_die( $message );
		}

		# If the type is not set to be buffered, die
		if( ! isset( self::$catchable[ $type ] ) ) {
			uf_die( $message );
		} else {
			self::$buffer[ $type ][] = $message;
			UF_Notices::add( $message, true );
		}
	}

	/**
	 * Retrieves all notices from a certain type
	 * 
	 * @param string $type The type of the exception
	 * @return string[] The messages of this type.
	 */
	static function get( $type ) {
		return isset( self::$buffer[ $type ] ) ? self::$buffer[ $type ] : array();
	}
}