<div class="wrap uf-wrap uf-postmeta <?php if( $tab_links ) echo ' tabs' ?>">
	<?php
	if( $tab_links ) {
		# Display tab links
		echo '<div class="nav-tab-wrapper">';
			echo $tab_links;
			do_action( 'uf_after_tabs', $this );
		echo '</div>';

		# Display a border between the tabs and the content
		echo '<div class="tab-border"></div>';

		# Open a div for the tabs themselves
		echo '<div class="right-tabs">';
	}

	# Display the container's description if any.
	echo wpautop( $this->description );

	# Display fields, tabs, etc.
	$this->display_fields();

	if( $tab_links ) {
		# Eventually close the tabs
		echo '</div>';		
	}

	# Display nonces, etc.
	$this->nonce();
	?>
</div>