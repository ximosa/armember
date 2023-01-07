<form class="mp-admin-settings-email-form" method="POST">

	<table class="form-table">
		<tr>
			<th scope="row"><?php _e('Email Template', 'mp-demo') ?></th>
			<td>
				<select name="customer[template]">
					<option
						value="default" <?php echo ($settings['customer']['template'] === 'default') ? ' selected' : '' ?>><?php _e('Default', 'mp-demo') ?></option>
					<option
						value="text" <?php echo ($settings['customer']['template'] === 'text') ? ' selected' : '' ?>><?php _e('Plain text', 'mp-demo') ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('From Email', 'mp-demo') ?></th>
			<td>
				<input type="text" class="regular-text" name="customer[from_email]"
				       value="<?php echo $settings['customer']['from_email']; ?>">
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('From Name', 'mp-demo') ?></th>
			<td>
				<input type="text" class="regular-text" name="customer[from_name]"
				       value="<?php echo $settings['customer']['from_name']; ?>">
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Subject', 'mp-demo') ?></th>
			<td>
				<input type="text" class="large-text" name="customer[subject]"
				       value="<?php echo $settings['customer']['subject']; ?>">
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Message', 'mp-demo') ?></th>
			<td>
				<?php wp_editor($settings['customer']['body'], 'customer-body', array(
					'wpautop' => true,
					'media_buttons' => true,
					'textarea_name' => 'customer[body]',
				));
				?>
				<p>
<pre>
{demo_url} - <?php _e('Confirmation url', 'mp-demo') ?><br>
{demo_duration_value} - <?php _e('Demo duration time. From General settings.', 'mp-demo') ?><br>
{demo_duration_period} - <?php _e('Demo duration time measure. From General settings.', 'mp-demo') ?><br>
{demo_lifetime}	- <?php _e('Demo duration in hours', 'mp-demo') ?><br>
{demo_lifetime_days} - <?php _e('Demo duration in days', 'mp-demo') ?><br>
{email} - <?php _e('User e-mail', 'mp-demo') ?><br>
{login} - <?php _e('User login', 'mp-demo') ?><br>
{password} - <?php _e('User passsword', 'mp-demo') ?><br>
</pre>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Send Test Message', 'mp-demo') ?></th>
			<td>
				<input type="email" class="regular-text" name="test-email-receiver" placeholder="example@mail.com">
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
	<input type="hidden" name="tab" value="mail-customer">

</form>

