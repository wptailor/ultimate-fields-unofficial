<?php
/**
 * Saves data about available themes and processes requests for templates
 */
class UF_Themes {
	/** @type mixed[] Holds an array for each active theme */
	protected $themes = array();

	/** @type mixed[] Holds an array for each avalable theme */
	protected $available = array();

	/**
	 * Add hooks
	 */
	function __construct() {
		add_action( 'uf_extend', array( $this, 'set_theme' ), 100 );
	}

	/**
	 * After additional themes are added, choose the right one
	 */
	function set_theme() {
		if( $chosen = get_option( 'uf_theme' ) ) {
			foreach( $this->available as $theme ) {
				if( $theme[ 'id' ] == $chosen ) {
					$this->themes[] = $theme;

					do_action( 'uf_skin_' . $theme[ 'id' ] );

					break;
				}
			}
		}
	}

	/**
	 * Adds a theme with a low priority.
	 * 
	 * This should be used within plugins that extend Ultimate Fields, so their templates can be accessed.
	 * The priority will be the following: Active Theme (Hi), Extensions(Med) and Base(Low)
	 * 
	 * @param string $dir The path to the directory.
	 * 
	 * @access public
	 * @since 1.0
	 */
	public function add_theme( $dir ) {
		$dir = trailingslashit( $dir  );
		array_unshift( $this->themes, compact( 'dir' ) );
	}

	/**
	 * Register a theme through a theme or plugin
	 *
	 * @param array $args - id, title, dir and url of the theme
	 */
	function register( $args ) {
		# Check for all required keys
		$keys = apply_filters( 'uf_theme_args', array( 'id', 'title', 'dir', 'url', 'image' ) );

		if( count( array_intersect_key( $args, array_flip( $keys ) ) ) < count( $keys ) ) {
			uf_die( 'Incorrect keys for uf_register_theme!' );
		}

		# Add trailing slashes where needed
		$args[ 'dir' ] = trailingslashit( $args[ 'dir' ] );
		$args[ 'url' ] = trailingslashit( $args[ 'url' ] );

		# Add the theme to the right place
		array_unshift( $this->available, $args );
	}

	/**
	 * Return the path to a template item. Checks all active themes and defaults to the plugin
	 *
	 * @param string $template Required file
	 * @return string Path to the file
	 */
	function path( $template, $location = null ) {
		$location = apply_filters( 'uf_template_location', $location, $template );
		$template = apply_filters( 'uf_template', $template, $location );

		# Default path
		$path = UF_DIR . 'templates/' . $template . '.php';

		# If there's a specific location, try a recursive call for it
		if( $location ) {
			$temp = $template . '-' . $location . '.php';

			foreach( $this->themes as $theme ) {
				if( file_exists( $theme[ 'dir' ] . $temp ) ) {
					$path = $theme[ 'dir' ] . $temp;
				}
			}

			if( file_exists( UF_DIR . 'templates/' . $temp ) ) {
				$path = UF_DIR . 'templates/' . $temp;
			}
		}
		
		# Go back to the default template
		foreach( $this->themes as $theme ) {
			if( file_exists( $theme[ 'dir' ] . $template . '.php' ) ) {
				$path = $theme[ 'dir' ] . $template . '.php';
			}
		}

		return apply_filters( 'uf_template_path', $path );
	}

	/**
	 * Get a list of available themes
	 * 
	 * @return mixed[] List of themes
	 */
	function get() {
		return apply_filters( 'uf_themes', $this->available );
	}
}

/**
 * Creates an instance of the class that's globally available
 */
global $ultimatefields;
$ultimatefields->themes = new UF_Themes();