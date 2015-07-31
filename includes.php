<?php
/**
 * Includes most files of the plugin while doing actions
 * and applying filters so new items can be added in the order
 * of classes or other ones might get replaced.
 * 
 * The array below contains most files that will be used by the plugin.
 */

$files = array(
	'basic' => array(
		UF_CLASS_DIR . 'UF_Notices.php',
		UF_CLASS_DIR . 'UF_Exceptions.php',
		UF_CLASS_DIR . 'UF_Themes.php'
	),

	'multilingual' => array(
		UF_CLASS_DIR . 'UF_ML_Adapter.php',
		UF_CLASS_DIR . 'UF_ML.php',
		UF_CLASS_DIR . 'UF_Qtranslate.php',
		UF_CLASS_DIR . 'UF_ML_WordPress.php'
	),

	'datastores' => array(
		UF_CLASS_DIR . 'UF_Unavailable_Key_Exception.php',
		UF_CLASS_DIR . 'UF_Datastore.php',
		UF_CLASS_DIR . 'UF_Datastore_Options.php',
		UF_CLASS_DIR . 'UF_Datastore_Postmeta.php',
		UF_CLASS_DIR . 'UF_Datastore_Getter.php'
	),

	'containers' => array(
		UF_CLASS_DIR . 'UF_Container.php',
		UF_CLASS_DIR . 'UF_Container_Base.php',
		UF_CLASS_DIR . 'UF_Container_Tabbable.php',
		UF_CLASS_DIR . 'UF_Options.php',
		UF_CLASS_DIR . 'UF_Postmeta.php'
	),

	'fields' => array(
		UF_CLASS_DIR . 'UF_Field.php',
		UF_CLASS_DIR . 'UF_Field_Repeater.php',
		UF_CLASS_DIR . 'UF_Field_Separator.php',
		UF_CLASS_DIR . 'UF_Field_HTML.php',
		UF_CLASS_DIR . 'UF_Field_Text.php',
		UF_CLASS_DIR . 'UF_Field_Select.php',
		UF_CLASS_DIR . 'UF_Field_Set.php',
		UF_CLASS_DIR . 'UF_Field_Textarea.php',
		UF_CLASS_DIR . 'UF_Field_Select_Page.php',
		UF_CLASS_DIR . 'UF_Field_Radio.php',
		UF_CLASS_DIR . 'UF_Field_Checkbox.php',
		UF_CLASS_DIR . 'UF_Field_File.php',
		UF_CLASS_DIR . 'UF_Field_Select_Term.php',
		UF_CLASS_DIR . 'UF_Field_Richtext.php'
	)
);

# Modify files before the start
$fields = apply_filters( 'uf_includes', $files );

foreach( $files as $group => $paths ) {
	# Allow actions before the group is included
	do_action( 'uf_inc_before_' . $group );

	# Include files
	foreach( $paths as $path ) {
		include_once( apply_filters( 'uf_file_path', $path, $group ) );
	}

	# Allow actions after the group is included
	do_action( 'uf_inc_after_' . $group );
}