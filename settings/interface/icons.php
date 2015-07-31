<?php
add_action( 'admin_enqueue_scripts', 'uf_interface_icon' );
function uf_interface_icon() {
	wp_enqueue_style( 'uf-icon', UF_URL . 'settings/font/ultimate-fields-font.css' );
}

add_action( 'admin_head', 'uf_interface_icon_style' );
function uf_interface_icon_style() {
	?>
	<style type="text/css">
	/*.icon16.icon-post:before,
	#adminmenu .menu-icon-post div.wp-menu-image:before*/
	.ultimate-fields-icon:before,
	#menu-posts-ultimatefields a.menu-icon-post .wp-menu-image:before {
		content: "\e600";
		font-family: 'ultimate-fields' !important;
	}
	</style>
	<?php
}