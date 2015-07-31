<?php
/**
 * Displays a select with categories
 */
UF_Field::add_field( 'select_term',__( 'Select Term', 'uf' ) );
class UF_Field_Select_Term extends UF_Field_Select {
	protected $taxonomy = 'category';

	function set_taxonomy( $taxonomy ) {
		if(!taxonomy_exists($taxonomy)) {
			uf_die( __("<strong>UF_Field_Select_Term</strong>: Invalid taxonomy! Please check if you've spelled the name of the taxonomy correctly and that the taxonomy is already registered!", 'uf') );
		}
		$this->taxonomy = $taxonomy;

		return $this;
	}

	protected function check_options( $default_message ) {
		switch($this->taxonomy) {
			case 'category':
				$filter_name = 'single_cat_title';
				break;
			case 'tag':
				$filter_name = 'single_tag_title';
				break;
			default:
				$filter_name = 'single_term_title';
		}

		$terms = get_terms($this->taxonomy, array( 'hide_empty' => false ));
		foreach($terms as $term) {
			$this->options[$term->term_id] = apply_filters($filter_name, $term->name);
		}

		if(empty($this->options)) {
			if(!$this->no_options_message)
				$this->no_options_message = $default_message;

			echo '<p class="only-child">' . $this->no_options_message . '</p>';
			return false;
		}		

		return true;
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a drop-down with all available terms from a taxonomy.', 'uf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return UF_Field[]
	 */
	static public function additional_settings() {
		$taxonomies = array();
		$taxonomies_raw = get_taxonomies( '', 'objects' ); 
		foreach( $taxonomies_raw as $taxonomy ) {
			$taxonomies[$taxonomy->name] = $taxonomy->labels->name;
		}

		return array(
			UF_Field::factory( 'select', 'taxonomy', __( 'Taxonomy', 'uf' ) )
				->add_options( $taxonomies ),
			UF_Field::factory( 'separator', 'output_data_separator', __( 'Output Settings', 'uf' ) )
				->set_description( __( 'The following settings will affect the output of this field when using the &quot;uf&quot; function or shortcode.', 'uf' ) ),
			UF_Field::factory( 'select', 'output_type', __( 'Output Type', 'uf' ) )
				->add_options( array(
					'link'  => __( 'A link to the chosen term', 'uf' ),
					'url'   => __( 'The URL of the chosen term', 'uf' ),
					'title' => __( 'The name of the chosen term', 'uf' ),
					'id'    => __( 'The ID of the chosen term', 'uf' )
				) )
		);
	}

	/**
	 * Process the value based on the settings in the admin.
	 * 
	 * @param int $value The value of the field
	 * @param mixed $data The settings of the field, added through the Ultimate Fields section
	 * 
	 * @return string The content to be shown in the editor.
	 */
	public function process_value( $value, $data ) {
		if( ! isset( $data[ 'output_type' ] ) ) {
			$data[ 'output_type' ] = 'link';
		}

		switch( $data[ 'output_type' ] ) {
			case 'link':
				$title = apply_filters( 'single_cat_title', get_term( intval( $value ), $data[ 'taxonomy' ] )->name );
				$url = get_term_link( intval( $value ), $data[ 'taxonomy' ] );
				return '<a href="' . esc_attr( $url ) . '">' . $title . '</a>';
				break;

			case 'url':
				return get_term_link( intval( $value ), $data[ 'taxonomy' ] );
				break;

			case 'title':
				return apply_filters( 'single_cat_title', get_term( intval( $value ), $data[ 'taxonomy' ] )->name );
				break;
		}

		return $value;	
	}
}