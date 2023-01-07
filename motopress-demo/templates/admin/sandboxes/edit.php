<div class="wrap">
	<form id="mp-demo-edit-sandbox" enctype="multipart/form-data" method="POST">

		<?php
		$expiration_date = false;
		$blog_id = false;
		$expiration_date_placeholder = '';
		$expiration_date_value = '';
		$lifetime_checked = -1;
		$status_checked = -1;

		if ($is_bulk_action) {
			$blog_id = implode(', ', $sandboxes);
			$expiration_date_placeholder = ' placeholder="' . __('- No Change -', 'mp-demo') . '"';
		} else {
			$expiration_date = $sandbox['expiration_date'];
			$expiration_date_value = ' value="' . $expiration_date . '"';
			$blog_id = $sandbox['blog_id'];
			$lifetime_checked = ($sandbox['is_lifetime'] == 1) ? 1 : 0;
			$status_checked = $sandbox['status'];
		}
		?>
		<input type="hidden" name="sandbox" value="<?php echo $blog_id; ?>">
		<table class="form-table">
			<tr>
				<th>
					<?php echo __('Edit Sandbox ID(s)', 'mp-demo') ?>
				</th>
				<td><?php echo $blog_id ?></td>
			</tr>
			<tr>
				<th>
					<label for="expiration_date"><?php _e('Expiration Date', 'mp-demo') ?></label>
				</th>
				<td>
					<input type="text"
					       name="expiration_date"
						   class="regular-text"
						<?php echo $expiration_date_value; ?>
						<?php echo $expiration_date_placeholder; ?>
						   size="19" placeholder="1970-12-31 23:59:59">
					<p class="description"><?php echo __('Format: YYYY-MM-DD HH:MM:SS. Current server time: ', 'mp-demo') . current_time('mysql'); ?></p>
				</td>
			</tr>
			<tr>
				<th>
					<label for="is_lifetime"><?php _e('Is Lifetime', 'mp-demo') ?></label>
				</th>
				<td>
					<select name="is_lifetime">
						<?php if ($is_bulk_action): ?>
							<option
								value="-1" <?php selected(-1 == $lifetime_checked); ?>> <?php _e('- No Change -', 'mp-demo') ?></option>
						<?php endif; ?>
						<option
							value="1" <?php selected(1 == $lifetime_checked); ?>> <?php _e('Yes', 'mp-demo') ?></option>
						<option
							value="0" <?php selected(0 == $lifetime_checked); ?>> <?php _e('No', 'mp-demo') ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th>
					<label><?php _e('Status', 'mp-demo') ?></label>
				</th>
				<td>
					<select  name="status">
						<?php if ($is_bulk_action): ?>
							<option
								value="-1" <?php selected(-1 == $status_checked); ?>> <?php _e('- No Change -', 'mp-demo') ?></option>
						<?php endif; ?>
						<option
							value="<?php echo MP_DEMO_STATUS_ACTIVE; ?>"
							<?php selected(MP_DEMO_STATUS_ACTIVE == $status_checked); ?>> <?php _e('Active', 'mp-demo'); ?></option>
						<option
							value="<?php echo MP_DEMO_STATUS_ARCHIVED; ?>"
							<?php selected(MP_DEMO_STATUS_ARCHIVED == $status_checked); ?>> <?php _e('Archived', 'mp-demo'); ?></option>
						<option
							value="<?php echo MP_DEMO_STATUS_DEACTIVATED; ?>"
							<?php selected(MP_DEMO_STATUS_DEACTIVATED == $status_checked); ?>> <?php _e('Deactivated', 'mp-demo'); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th>
				</th>
				<td>
					<?php wp_nonce_field('mp_demo_save', 'mp_demo_save'); ?>
					<input type="hidden" name="tab" value="<?php echo $context; ?>-sandbox">
					<button type="submit" class="button-primary save"><?php _e('Update', 'mp-demo') ?></button>
					<a href="<?php echo remove_query_arg(array('action')); ?>"
					   class="button-secondary cancel"><?php _e('Cancel', 'mp-demo') ?></a>
				</td>
			</tr>
		</table>
	</form>
</div>

