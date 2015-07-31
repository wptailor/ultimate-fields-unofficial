<?php
/**
 * Displays a simple text separator
 */
class UF_Field_HTML extends UF_Field {
	function display( $location = null ) {
		global $ultimatefields;

		include( $ultimatefields->themes->path( 'field/html', $location ) );
	}
}