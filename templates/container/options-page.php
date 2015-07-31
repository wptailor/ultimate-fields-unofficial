<div class="wrap uf-wrap uf-options<?php if( $links ) echo ' tabs' ?>" id="<?php echo $this->id ?>">
	<?php
	# Display the title, eventually with tabs
	if( $links ) {
		echo '<h2 class="nav-tab-wrapper">' . $title . '&nbsp;' . $links . '</h2>';
	} else {
		echo '<h2>' . $title . '</h2>';
	}

	# Display the description of the container
	if( $this->description ) {
		echo wpautop( $this->description );
	}

	# Display the error message which is hidden by default.
	include( $ultimatefields->themes->path( 'container/error-message' ) );
	?>

	<form action="" method="POST"><?php
		# Display the fields & tabs.
		$this->display_fields();

		# Display the submit button.
		submit_button( __( 'Save', 'uf' ), 'primary' );

		# Display an ajax loader
		include( $ultimatefields->themes->path( 'container/ajax-loader' ) );

		# Display a nonce field to prevent mistakes
		$this->nonce();
	?></form>
</div>