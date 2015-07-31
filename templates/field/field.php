<tr<?php $this->field_atts() ?>>
	<th>
		<label><?php
			echo wp_kses_post( $this->title );
			$this->display_help();
		?></label>
	</th>
	<td class="field-wrap">
		<?php
		$this->base_display_input();
		$this->display_description();
		?>
	</td>
</tr>