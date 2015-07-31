<?php
class UF_Tabs {
	/**
	 * Displays the links for the tabs in the big heading.
	 * 
	 * @param mixed[] $tabs The tabs of the container, if any.
	 * 
	 * @access public
	 */
	static public function links( $tabs ) {
		$arr = array();

		foreach( $tabs as $tab ) {
			$arr[] = self::get_link( $tab );
		}

		return implode( ' ', $arr );
	}

	/**
	 * Displays the link that is needed for a tab.
	 * 
	 * @param mixed[] $tab The information about the tab.
	 * @return string The tab's HTML.
	 * 
	 * @access private
	 */
	static private function get_link( $tab ) {
		# Determine the right icon for the tab
		if( preg_match( '~^\d+$~', $tab[ 'icon' ] ) ) {
			$icon = wp_get_attachment_image( $tab[ 'icon' ], 'full' );
		} elseif( strpos( $tab[ 'icon' ], 'http' ) === 0 ) {
			$icon = '<img src="' . esc_attr( $tab[ 'icon' ] ) . '" alt="" />';
		} elseif( $tab[ 'icon' ] ) {
			$icon = '<span class="' . esc_attr( $tab[ 'icon' ] ) . '"></span>';
		} else {
			$icon = '';
		}

		if( $icon ) {
			$icon = '<span class="icon">' . $icon . '</span>';
		}

		return '<a href="#' . $tab['id'] . '" class="nav-tab">' . $icon . '<strong>' . esc_html( $tab[ 'title' ] ) . '</strong></a>';
	}

	public static function end($last) {
		?>
			</form>
		</div>
		<?php if(!$last): ?>
		<form class="form-table">
		<?php endif; ?>
		<?php
	}

	public static function tab_start($tab) {
		?>
		<table class="tab form-table" id="<?php echo $tab['id'] ?>">
		<?php
	}

	public static function tab_end() {
		?>
		</table>
		<?php
	}
}