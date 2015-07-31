<tr class="uf-field uf-separator">
	<td colspan="2">
		<div class="postbox">
			<div class="metabox-holder">
				<h3 class="hndle"><?php echo wp_kses_post( $this->title ); ?></h3>
			</div>
		</div>

		<?php echo wpautop( $this->description); ?>
	</td>
</tr>