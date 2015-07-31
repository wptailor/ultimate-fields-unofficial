<?php
interface UF_Container {
	# Each container must contain UF_Fields in order to get displayed
	# public function add_fields(array $fields);
	# This is currently disabled, as repeaters also take titles as args

	# Control the title of the container. This should automatically
	# set an ID to the panel if it's not set explicitly
	public function set_title( $title );
	public function get_title();

	# (Almost) Each container in WordPress needs to have an ID, it
	# would be also required by UF scripts to idenify it. The ID
	# should be automatically set through set_title()
	public function set_id( $id );
	public function get_id();

	# The description would primarily work as Help text for the panel
	public function set_description( $description );
	public function get_description();
}