<?php global $ultimatefields ?>

<div class="meta-box-sortables uf-repeater<?php if( is_a( $this->datastore, 'UF_Field_Repeater' ) ) echo ' uf-notable' ?>">
	<!-- Prevent margin top from moving the whole field -->
	<div class="cl">&nbsp;</div>

	<div class="fields">
		<?php foreach( $this->fields as $i => $data ) {
			include( $ultimatefields->themes->path( 'repeater/repeater-row' ) );
		} ?>

		<div class="placeholder"<?php if(!empty($this->fields)) echo ' style="display:none"' ?>>
			<p><?php if( count( $this->field_groups ) > 1 ) {
				_e( 'Drag an item here to add data', 'uf' );
			} else {
				_e( 'Please click the &quot;Add&quot; Button to add data.', 'uf' );
			}?></p>
		</div>
	</div>

	<?php if( count( $this->field_groups ) == 1 ): ?>
	<div class="controls">
		<a href="#" class="button-primary add"><?php _e( 'Add', 'uf' ) ?></a>
	</div>
	<?php else: ?>
	<h4 class="drag-n-drop"><?php _e( 'Add', 'uf' ) ?>: <span><?php _e( 'Drag & Drop into the area above', 'uf' ) ?></span></h4>
	<?php endif; ?>

	<div class="prototypes"<?php if( count( $this->field_groups ) < 2 ) echo ' style="display:none"' ?>>
		<?php foreach($this->field_groups as $group_key => $group): ?>
		<div class="metabox-wrap">
			<div class="postbox metabox-holder uf-prototype uf-row closed" data-key="<?php echo $group_key ?>" data-uf-id="<?php echo esc_attr( $group_key ) ?>" data-title-field="<?php echo esc_attr( $group['title_field' ] ) ?>">
				<div class="btn add-row" title="<?php _e( 'Click to add', 'uf' ) ?>"><br></div>
				<div class="btn handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br></div>
				<div class="btn dashicons dashicons-post-trash delete-row" title="<?php _e( 'Delete', 'uf' ) ?>"><br></div>
				<h3 class="hndle"><?php echo $group['title'] ?><em>: <span class="group-title"></span></em></h3>

				<div class="uf-inside">
					<table class="form-table">
						<?php foreach($group['fields'] as $field) {
							$this->display_prototype($field);
						} ?>
					</table>
				</div>

				<input type="hidden" name="<?php echo $this->input_id . '[' . $this->i_placeholder . '][__type]' ?>" value="<?php echo $group_key ?>" />
			</div>
			<?php echo wpautop( $group['description'] ) ?>
		</div>
		<?php endforeach; ?>
	</div>
</div>