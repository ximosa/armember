<form class="mp-admin-settings-email-form" method="POST">

	<table class="form-table">
		<tr>
			<th scope="row"></th>
			<td>
				<input type="hidden" name="admin[disable_admin_notices]" value="0">
				<input type="checkbox" id="admin-disable_admin_notices" name="admin[disable_admin_notices]"
				       value="1" <?php checked(1, $settings['admin']['disable_admin_notices']); ?>>
				<label
					for="admin-disable_admin_notices"><?php _e('Do not send email when new sandbox is activated', 'mp-demo'); ?></label>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Email Template', 'mp-demo') ?></th>
			<td>
				<select name="admin[template]">
					<option
						value="default" <?php echo ($settings['admin']['template'] === 'default') ? ' selected' : '' ?>><?php _e('Default', 'mp-demo') ?></option>
					<option
						value="text" <?php echo ($settings['admin']['template'] === 'text') ? ' selected' : '' ?>><?php _e('Plain Text', 'mp-demo') ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('To Email', 'mp-demo') ?></th>
			<td>
				<input type="text" class="regular-text" name="admin[to_email]"
				       value="<?php echo $settings['admin']['to_email']; ?>">
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('From Email', 'mp-demo') ?></th>
			<td>
				<input type="text" name="admin[from_email]" class="regular-text"
				       value="<?php echo $settings['admin']['from_email']; ?>">
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('From Name', 'mp-demo') ?></th>
			<td>
				<input type="text" name="admin[from_name]" class="regular-text"
				       value="<?php echo $settings['admin']['from_name']; ?>">
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Subject', 'mp-demo') ?></th>
			<td>
				<input type="text" name="admin[subject]" class="large-text"
				       value="<?php echo $settings['admin']['subject']; ?>">
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Message', 'mp-demo') ?></th>
			<td>
				<?php wp_editor($settings['admin']['body'], 'admin-body', array(
					'wpautop' => true,
					'media_buttons' => true,
					'textarea_name' => 'admin[body]',
				));
				?>
				<p>
<pre>
{site_title} - <?php _e('Site title', 'mp-demo') ?><br>
{email} - <?php _e('User e-mail', 'mp-demo') ?><br>
{login} - <?php _e('User login', 'mp-demo') ?><br>
</pre>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Send Test Message', 'mp-demo') ?></th>
			<td>
				<input type="email" name="test-email-receiver" class="regular-text" placeholder="example@mail.com">
				<input type="button" id="mp-demo-test-email" class="button-secondary"
				       value="<?php _e('Send', 'mp-demo') ?>">
				<span class="spinner" style="float: none; height: 25px;"></span>

				<div class="mp-message">
					<p class="mp-body">
						<span class="mp-demo-success"
						      style="display: none;"><?php _e('Check your e-mail', 'mp-demo'); ?></span>
						<span class="mp-demo-fail"
						      style="display: none;"><?php echo _e('An error occurred', 'mp-demo'); ?> <span
							  class="mp-errors"></span></span>
					</p>
				</div>
			</td>
		</tr>
		</tbody>
	</table>

	<?php submit_button(__('Save', 'mp-demo')); ?>
	<?php wp_nonce_field('mp_demo_save', 'mp_demo_save'); ?>
	<input type="hidden" name="tab" value="mail-admin">
</form>
