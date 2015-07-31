<?php
/**
 * Defines a tab-able container to be inherited from options and post meta.
 */
abstract class UF_Container_Tabbable extends UF_Container_Base implements UF_Container {
	/** @type boolean Indicates if there is a tab that's current open */
	protected $tab_open = false;

	/** @type boolean Indicates if there is a tabs group that's current open */
	protected $tabs_open = false;

	/** @type mixed[] Holds tabs that should be shown on top of the page */
	protected $tabs = array();

	/** @type int The ID of the next tabs group */
	protected $current_tab_group = -1;

	/** @type string Sets the location of the tabs navigation. top/left */
	protected $tabs_align = "top";	

	/**
	 * Add a whole tab with it's start, end and fields.
	 * 
	 * @param string $key The ID of the tab
	 * @param UF_Field[] $fields The fields that will appear in the tab
	 * @param string $icon Either a CSS class for the icon or an absolute image URL.
	 * @param string $title The title if needed. Otherwise the key will be used
	 * @return UF_Options The instance of the page
	 */
	public function tab( $key, array $fields, $icon = null, $title = null ) {
		if( ! $title ) {
			$title = ucwords( str_replace( '_', ' ', $key) );
		}

		$key = sanitize_title( $key);

		$this->start_tab( $key, $title, $icon );
		$this->add_fields( $fields );
		$this->end_tab();

		return $this;
	}

	/**
	 * CLoses the current tab.
	 * Only needed if after the last tab, there will be more fields
	 * 
	 * @return UF_Options The instance of the page
	 */
	public function end_tab() {
		if( ! $this->tab_open )
			return $this;

		$this->fields[] = array(
			'item' => 'tab_end'
		);

		$this->tab_open = false;

		return $this;
	}

	/**
	 * Open a new tab. If one is already open, it will be closed.
	 * 
	 * @param string $id The identifier of the tab. Appears in the address
	 * @param string $title The title of the tab
	 * @param string $icon The icon that will appear next to the tabs's title
	 * @return UF_Options The instance of the page
	 */
	public function start_tab( $id, $title, $icon = null ) {
		if( $this->tab_open )
			$this->end_tab();

		if( ! $this->tabs_open ) {
			$this->fields[] = array(
				'item'  => 'tabs_start',
				'group' => ( ++$this->current_tab_group )
			);

			$this->tabs_open = true;
		}

		$this->fields[] = array(
			'item'  => 'tab_start',
			'id'    => $id
		);

		$this->tabs[] = array(
			'title' => $title,
			'icon'  => $icon,
			'id'    => $id,
			'group' => $this->current_tab_group
		);

		$this->tab_open = true;

		return $this;
	}

	/**
	 * Displays fields, opens and closes tabs inside of the template.
	 */
	public function display_fields() {
		# Don't do a thing if there are no fields
		if( ! $this->fields ) {
			return;
		}

		# Open a standard table if there are no tabs, at least in the beginning
		if( is_a( $this->fields[ 0 ], 'UF_Field' ) ) {
			echo '<table class="form-table">';
		}

		# Cycle through fields, display tabs, etc.
		foreach( $this->fields as $i => $field ) {
			if( is_a( $field, 'UF_Field' ) ) {
				$field->display();
			} elseif( $field[ 'item' ] == 'tab_start' ) {
				UF_Tabs::tab_start( $field );
			} elseif( $field[ 'item' ] == 'tab_end' ) {
				UF_Tabs::tab_end();
			}
		}

		# If the last item is a field, we are not in tabs. Close the list.
		if( is_a( $field, 'UF_Field' ) ) {
			echo '</table>';
		}
	}
}