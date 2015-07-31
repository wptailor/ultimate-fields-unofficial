<?php
$layout_atts = '';
if( is_a( $this, 'UF_Field_Layout' ) ) {
	$layout_atts = 'data-max-width="' . $data[ 'max_width' ] . ' " data-min-width="' . $data[ 'min_width' ] . ' "';
}
?>

<div class="postbox metabox-holder uf-row closed" data-uf-id="<?php echo esc_attr( $data['type'] ) ?>" data-title-field="<?php echo esc_attr( $data['title_field' ] ) ?>" <?php echo $layout_atts ?>>
	<?php if( is_a( $this, 'UF_Field_Layout' ) ): ?>
	<a class="mp6-text-highlight fa fa-minus" title="<?php echo esc_attr( __( 'Shrink', 'uf' ) ) ?>"></a>
	<a class="mp6-text-highlight fa fa-plus" title="<?php echo esc_attr( __( 'Expand', 'uf' ) ) ?>"></a>
	<a class="mp6-text-highlight fa fa-trash-o" title="<?php echo esc_attr( __( 'Remove Group', 'uf' ) ) ?>"></a>
	<a class="mp6-text-highlight fa fa-edit" title="<?php echo esc_attr( __( 'Edit', 'uf' ) ) ?>"></a>
	<?php else: ?>
	<div class="btn handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br></div>
	<div class="btn dashicons dashicons-post-trash delete-row" title="<?php _e( 'Delete', 'uf' ) ?>"><br></div>
	<?php endif ?>

	<h3><div class="block"><?php echo $data['title'] ?><em>: <span class="group-title"></span></em></div></h3>

	<div class="uf-inside">
		<table class="form-table">
			<?php foreach($data['fields'] as $field) {
				$field->display( 'repeater' );
			} ?>
		</table>

		<input type="hidden" name="<?php echo $this->input_id . '[' . $i . '][__type]' ?>" value="<?php echo $data['type'] ?>" />
		<input type="hidden" class="i" value="<?php echo $i ?>" />
		<?php if( is_a( $this, 'UF_Field_Layout' ) ): ?>
		<input type="hidden" class="width-input" name="<?php echo $this->input_id . '[' . $i . '][width]' ?>" value="<?php echo $data[ 'width' ] ?>" />
		<?php endif ?>
	</div>
</div>