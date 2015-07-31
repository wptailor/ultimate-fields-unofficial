<?php
/**
 * Registers all scripts that might be used throughout the plugin.
 * Based on SCRIPT_DEBUG, either a minified or a normal version will be used.
 */
function uf_register_scripts() {
	$debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || WP_DEBUG;

	# Prepare ui style for fields
	wp_register_style( 'jquery-ui', UF_URL . 'templates/css/jquery-ui/jquery-ui.css' );
	wp_register_style( 'chosen-css', UF_URL . 'templates/css/select2.css', array(), UF_VER );
	wp_register_style( 'fontawesome-icons', UF_URL . 'templates/css/font-awesome/font-awesome.min.css', null, UF_VER );
	wp_register_style( 'ultimatefields-css', UF_URL . 'templates/css/ultimate-fields.css', array( 'jquery-ui', 'chosen-css' ), '3.3.2' );
	
	# Register all scripts and enqueue needed ones
	wp_register_script( 'jquery-chosen', UF_URL . 'js/select2.min.js', array( 'jquery' ), '3.3.2', true );
	wp_register_script( 'ultimate-fields', UF_URL . 'js/ultimate-fields.js', array( 'jquery', 'underscore', 'jquery-chosen' ), UF_VER, true );
}

/**
 * Add translations to the admin.
 */
add_action( 'admin_footer', 'uf_output_js_strings' );
function uf_output_js_strings() {
	$strings = json_encode( apply_filters( 'uf_js_strings', array(
		'sure'        => __( "Are you sure?", 'uf' ),
		'saveError'   => __( "There was an error while trying to save your data. Please try again.", 'uf' ),
		'selectImage' => __( "Select Image", 'uf' ),
		'selectMedia' => __( "Select Media", 'uf' ),
		'saveAndUse'  => __( "Save & Use", 'uf' ),
	) ) );

	?>
	<script type="text/javascript">
	jQuery( document ).on( 'uf_extend', function(){
		additionalStrings = jQuery.parseJSON( '<?php echo $strings ?>' );
		jQuery.extend( UF.Strings, additionalStrings );
	});
	</script>
	<?php
}

/**
 * Custom die function. Uses wp_die() if no headers sent, otherwise exits
 * 
 * @param string $error The text of the error
 */
function uf_die( $error='' ) {
	$error = apply_filters( 'uf_die_error', $error );

	if( headers_sent() ) {
		echo '<strong>' . $error . '</strong>';
		exit;
	} else {
		wp_die( $error );
	}
}

/**
 * Creates a link by given base and additional params
 * 
 * @param string $base The base link to start building on
 * @param string[] $params The parameters that should be added to the URL
 * @return string The complete URL
 */
function uf_build_link( $base, $params = array() ) {
	# Extract the base link, removing parameters
	$link = preg_replace( '~^(.+)\??$~i', '$1', $base );

	# Add the additional params if any
	if( ! empty( $params ) ) {
		$strs = array();
		foreach($params as $key => $value) {
			$strs[] = $key . '=' . esc_attr($value);
		}
		$link .= '?' . implode( '&amp;', $strs);
	}

	return apply_filters( 'uf_build_link', $link );
}

/**
 * Clones an array recursively
 *
 * @param mixed[] The source array
 * @return mixed[] The cloned array
 */
function uf_clone_array( $array ) {
	$new = array();

	foreach( $array as $key => $value ) {
		$new[ $key ] = is_array( $value ) ? uf_clone_array( $value ) : clone $value;
	}

	return $new;
}