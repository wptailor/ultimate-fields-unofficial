<?php
/**
 * Creates an options page, which has it's own display.
 * As the name suggests, by default this container works with
 * the WordPress options API
 */
class UF_Options extends UF_Container_Tabbable {
	/** @type string The capability that's required for the page */
	protected $required_capability = 'manage_options';

	/** @type string An absolute URL for the icon in the menu */
	protected $icon;

	/** @type string The ID of the icon as it appears on the page */
	protected $icon_id = 'themes';

	/** @type int The position in the main menu */
	protected $menu_position = null;

	/** @type string The slug of the parent page. Overrides $type */
	protected $parent_slug;

	/** @type string Indicates where should the page be located - menu/appearance/subpage, etc. */
	protected $type = 'menu';

	/** @type boolean Indicates if the container has already set it's fields up */
	protected $set = false;

	/** @type string Holds the title as to be shown in the page itself */
	protected $page_title = '';

	/**
	 * Creates a page by setting attributes and adding actions.
	 * The second parameter accepts arguments that will be passed to setters
	 * ex. array( 'type' => 'appearance' ) will call ->set_type( 'appearance' )
	 * 
	 * @param string $title The title of the page. Used for ID
	 * @param mixed[] $args Arguments that are passed to setters.
	 */
	function __construct( $title, $args = null ) {
		# Process title
		$this->set_title( $title );

		# Process args. They can be in the format set_{$key} => $value
		# and the appropriate setter will be called.
		if( $args ) {
			if( is_array( $args ) ) {
				$args = apply_filters( 'uf_options_args', $args );

				foreach( $args as $property => $value ) {
					if( method_exists( $this, 'set_' . $property ) ) {
						call_user_func( array( $this, 'set_' . $property ) , $value );
					} else {
						uf_die( '<strong>UF_Options</strong>: ' . $property . ' is not a valid argument!' );
					}
				}
			} else {
				uf_die( '<strong>UF_Options</strong>: Only arrays may be passed as options to the container!' );
			}
		}

		# Add hooks which link the page and save values
		add_action( 'admin_menu', array( $this, 'attach_to_menu' ), isset( $this->parent_slug ) ? 11 : 9 );
		add_action( 'uf_save',   array( $this, 'save' ) );

		# Enqueue required scripts and styles in admin
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		do_action( 'uf_options_created', $this );
	}

	/**
	 * Enqueues scripts for the page in the admin
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'ultimate-fields' );
		wp_enqueue_style( 'ultimatefields-css' );
	}

	/**
	 * Factory method for creating pages.
	 * 
	 * @param string $title The title of the page
	 * @param mixed[] $args The arguments that will be passed to setters
	 * @return UF_Options The handle for the options page
	 */
	public static function page( $title, array $args = null) {
		return new UF_Options( $title, $args);
	}

	/**
	 * A proxy fo the page method above
	 */
	public static function factory( $title, array $args = null ) {
		return self::page( $title, $args );
	}

	/**
	 * Connects fields to the datastore, processes tabs and adds notices.
	 */
	private function setup() {
		if( $this->set )
			return;

		if( ! $this->id ) {
			uf_die( '<strong>UF_Options</strong>: You need to set a title/ID for each page!' );
		}

		if( ! $this->datastore ) {
			$this->datastore = apply_filters( 'uf_options_datastore', new UF_Datastore_Options() );
		}

		foreach( $this->fields as $field ) {
			if( is_a( $field, 'UF_Field' ) && $this->datastore->check_field_id( $field->get_id() ) ) {
				$field->set_datastore( $this->datastore, true );
			}
		}

		$this->end_tab();

		if( $this->tabs_open && ! $this->tab_open ) {
			$this->fields[] = array(
				'item' => 'tabs_end'
			);
		}

		$this->set = true;

		if( isset( $_GET[ 'success' ] ) ) {
			UF_Notices::add( __( 'Your changes were succesufully saved!', 'uf' ) );
		}
	}

	/**
	 * Generates a nonce field and outputs it.
	 */
	private function nonce() {
		$key = wp_create_nonce( $this->id );
		echo '<input type="hidden" name="_options_nonce" value="' . $key . '" />';
	}

	/**
	 * Checks if the nonce field is okay
	 */
	private function check_nonce() {
		if( ! isset( $_POST[ '_options_nonce' ] ) )
			return false;

		return wp_verify_nonce( $_POST[ '_options_nonce' ], $this->id );
	}

	/**
	 * Iterates through all fields and provides them $_POST to save.
	 * In the end redirects the user to the right URL + success parameter.
	 */
	function save() {
		$this->setup();

		if( $this->check_nonce() ) {
			foreach( $this->fields as $field) {
				if( is_a( $field, 'UF_Field' ) )
					$field->save( $_POST );
			}

			wp_redirect( 'admin.php?page=' . $this->id . '&success=true' );
			exit;
		}
	}

	/**
	 * Attaches the page to the menu based on multiple conditions
	 */
	function attach_to_menu() {
		$this->setup();

		# Params for all functions, since they're all the same
		$page_title = $this->title;
		$menu_title = $this->title;
		$capability = $this->required_capability;
		$menu_slug  = $this->id;
		$function   = array( $this, 'display' );

		# Icon and menu position are only available for top-level items
		$icon_url   = $this->icon;
		$position   = $this->menu_position;

		# Do one thing for a regular page
		if( ! $this->parent_slug ) {
			# Call the needed function depending on the type
			switch( $this->type ) {
				case 'tools':
					add_management_page( $page_title, $menu_title, $capability, $menu_slug, $function );
					break;
				case 'settings':
					add_options_page( '$page_title', $menu_title, $capability, $menu_slug, $function );
					break;
				case 'appearance':
					add_theme_page( $page_title, $menu_title, $capability, $menu_slug, $function );
					break;
				default:
				case 'menu':
					add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
					break;
			}
		} else {
			# And something else for a sub-page
			add_submenu_page( $this->parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
		}
	}

	/**
	 * Adds fields to the container
	 * 
	 * @param UF_Field[] $fields The field to be added
	 * @return UF_Options The current instance of the class
	 */
	public function add_fields( array $fields ){
		$fields = apply_filters( 'uf_add_fields', apply_filters( 'uf_options_add_fields', $fields ) );

		if( $this->tabs_open && ! $this->tab_open ) {
			$this->fields[] = array(
				'item' => 'tabs_end'
			);

			$this->tabs_open = false;
		}

		foreach( $fields as $field) {
			$this->add_field( $field);
		}

		return $this;
	}

	/**
	 * Adds a single field to the container
	 * 
	 * @param UF_Field $field The field that will be pushed 
	 */
	public function add_field( $field ) {
		if( $field ) {
			$this->fields[] = apply_filters( 'uf_add_field', $field );			
		}
	}

	/**
	 * Displays the container
	 */
	public function display() {
		global $ultimatefields;
		
		# Close open tabs if needed
		if( $this->tab_open || $this->tabs_open ) {
			$this->end_tab();
		}

		# Get tab's functionality
		include_once( $ultimatefields->themes->path( 'container/tabs' ) );

		# Prepare the standard title
		$title = wp_kses_post( $this->page_title ? $this->page_title : $this->title );

		# Prepare tabs nav links if available
		$links = UF_Tabs::links( $this->tabs );

		# Include the template itself
		include( $ultimatefields->themes->path( 'container/options-page' ) );

		# Do stuff after the container if needed
		do_action( 'uf_after_container' );

		# Output the needed dependencies for the container.
		$this->output_dependencies();
	}

	/**
	 * Sets an icon that will appear in the menu
	 * 
	 * @param string $icon An absolute URL to the icon
	 * @return UF_Options The page instance
	 */
	public function set_icon( $icon ) {
		$this->icon = $icon;
		return $this;
	}

	/**
	 * Sets the type of the page, a.k.a. it's parent in the menu.
	 * The available types can be seen in the first array below.
	 * 
	 * @param string $type One of the types below
	 * @return UF_Options The instance of the page
	 */
	public function set_type( $type ) {
		$available = array(
			'settings',    # In the settings tab
			'appearance',  # In the Appearance tab
			'menu',        # Directly in the menu
			'tools'        # In the tools tab
		);

		if( ! in_array( $type, $available ) ) {
			uf_die( '<strong>UF_Options</strong>: ' . $type . ' is not a valid Options page type!' );
		}

		$this->type = $type;

		return $this;
	}

	/**
	 * Get the type of the page
	 * 
	 * @return string The type
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * If the page is located in the main menu, set it's position
	 * 
	 * @param int $position The position according to the codex
	 * @return UF_Options The instance of the page
	 * @see http://codex.wordpress.org/Function_Reference/add_menu_page
	 */
	public function set_position( $position ) {
		$this->menu_position = intval( $position );
		return $this;
	}

	/**
	 * Set a parent page by either it's slug or it's instance.
	 * Only applies to items that are located directly in the main menu
	 * 
	 * @param string|UF_Options $parent The parent page or it's slug
	 * @return UF_Options The instance of the page
	 */
	public function set_parent( $parent ) {
		if( is_a( $parent, 'UF_Options' ) ) {
			$this->parent_slug = $parent->get_id();
		} else {
			$this->parent_slug = $parent;
		}

		return $this;
	}

	/**
	 * Controls the capability that's required in order for the page to be visible.
	 * 
	 * @param string $capability
	 * @return UF_Options The inance of the class
	 */
	public function set_capability( $capability ) {
		$this->required_capability = $capability;
		return $this;
	}

	/**
	 * Sets an ID for the icon that will appear on the page itself.
	 * This id might be used to style the icon with CSS
	 *
	 * @param string $id
	 * @return UF_Options The instance of the page
	 */
	public function set_icon_id( $id ) {
		$this->icon_id = $id;
		return $this;
	}

	/**
	 * Get the id of the current icon
	 * 
	 * @return string
	 */
	public function get_icon_id() {
		return $this->icon_id();
	}

	/**
	 * Sets the title that will be shon on the page itself.
	 * 
	 * @param string $title The new title.
	 * @return UF_Options The instance of the page for chaining.
	 */
	public function set_page_title( $title ) {
		$this->page_title = $title;

		return $this;
	}
}