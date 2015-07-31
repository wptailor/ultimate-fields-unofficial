<?php
/**
 * This class processes admin notices and messages.
 */
class UF_Notices {
	/** @type mixed[] Holds all queued notices */
	static protected $notices = array();

	/**
	 * Add a notice which will be displayed in the admin
	 * 
	 * @param string $message The message that will appear, as plain text
	 * @param boolean $fatal Indicates if there is an error or just a notice.
	 */
	public static function add( $message, $fatal = false ) {
		$notice = array(
			'message' => $message,
			'fatal'   => $fatal
		);

		$notice = apply_filters( 'uf_notice', $notice );

		# Push the notice in the array
		UF_Notices::$notices[ md5( $message ) ] = $notice;

		# Setup the hook that will display it, but first remove it to prevent double calls
		remove_action( 'admin_notices', array( 'UF_Notices', 'display' ) );
		add_action( 'admin_notices', array( 'UF_Notices', 'display' ) );
	}

	/**
	 * Displays all added notices
	 */
	public static function display() {
		foreach( UF_Notices::$notices as $notice ) {
			$notice = apply_filters( 'uf_notice_before_display', $notice );
			
			$class = $notice['fatal'] ? 'error' : 'updated fade';
			echo '<div id="message" class="' . $class . '"><span style="padding:5px; display:block;">' . $notice[ 'message' ] . '</span></div>';
		}
	}
}