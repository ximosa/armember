<h3><?php _e('Genreal Settings', 'mp-demo'); ?></h3>
<form method="POST">
	<table class="form-table">
		<tbody>
		<tr>
			<th scope="row">
				<label for="prevent_clones"><?php _e('Disable Registration', 'mp-demo'); ?></label>
			</th>
			<td>
				<fieldset>
					<input type="hidden" name="settings[prevent_clones]" value="0">
					<label><input type="checkbox" id="prevent_clones" name="settings[prevent_clones]"
					              value="1" <?php checked(1, $settings['prevent_clones']); ?>> <?php _e('Hide registration forms to prevent new sandboxes from being created', 'mp-demo'); ?>
					</label>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="auto_login"><?php _e('Sandbox User Role', 'mp-demo'); ?></label>
			</th>
			<td>
				<fieldset>
					<select name="settings[login_role]">
						<?php
						$roles = get_editable_roles();
						foreach ($roles as $slug => $role) {
							?>
							<option
								value="<?php echo $slug; ?>" <?php selected($slug, $settings['login_role']); ?>><?php echo $role['name']; ?></option>
							<?php
						}
						?>
					</select>

					<p class="description"><?php _e('Grant this role in Sandbox to the user. If you change this option, it will not be applied to already created Sandboxes.', 'mp-demo'); ?></p>
				</fieldset>
				<span class="howto"></span>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="enable_reset"><?php _e('Reset Demo', 'mp-demo'); ?></label>
			</th>
			<td>
				<fieldset>
					<input type="hidden" name="settings[enable_reset]" value="0">
					<label><input type="checkbox" id="enable_reset" name="settings[enable_reset]"
					              value="1" <?php checked(1, $settings['enable_reset']); ?>>
						<?php _e('User can reset demo', 'mp-demo'); ?>
					</label>
					<p class="description"><?php _e('Let users reset demo content to default. User\'s demo lifetime, ID and login credentials won\'t change.', 'mp-demo'); ?></p>
				</fieldset>
				<span class="howto"></span>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label><?php _e('Sandbox Expiration', 'mp-demo'); ?></label>
			</th>
			<td>
				<fieldset class="mp-demo-lifetime-wrap">
					<label><input type="radio" name="settings[is_lifetime]" value="1" <?php checked(1, $settings['is_lifetime']); ?>><?php _e('Never', 'mp-demo'); ?></label><br />
					<label><input type="radio" name="settings[is_lifetime]" value="0" <?php checked(0, $settings['is_lifetime']); ?>><?php _e('Limited time', 'mp-demo'); ?></label><br />
					<label><input type="number" name="settings[expiration_duration]" value="<?php echo isset($settings['expiration_duration']) ? $settings['expiration_duration'] : 30; ?>"></label>
					<label><select name="settings[expiration_measure]">
						<?php $expiration_measure = isset($settings['expiration_measure']) ? $settings['expiration_measure'] : 'minutes'; ?>
						<option value="minutes" <?php selected("minutes"== $expiration_measure);?>><?php _e('Minutes', 'mp-demo'); ?></option>
						<option value="hours" <?php selected("hours"==$expiration_measure);?>><?php _e('Hours', 'mp-demo'); ?></option>
						<option value="days" <?php selected("days"==$expiration_measure);?>><?php _e('Days', 'mp-demo'); ?></option>
						<option value="weeks" <?php selected("weeks"==$expiration_measure);?>><?php _e('Weeks', 'mp-demo'); ?></option>
						<option value="months" <?php selected("months"== $expiration_measure);?>><?php _e('Months', 'mp-demo'); ?></option>
					</select></label>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label><?php _e('Action After Expiration', 'mp-demo'); ?></label>
			</th>
			<td>
				<select name="settings[expiration_action]">
					<option value="<?php echo MP_DEMO_ACTION_DELETE; ?>" <?php selected(MP_DEMO_ACTION_DELETE== $settings['expiration_action']);?>><?php _e('Delete Sandbox', 'mp-demo'); ?></option>
					<option value="<?php echo MP_DEMO_ACTION_ARCHIVE; ?>" <?php selected(MP_DEMO_ACTION_ARCHIVE==$settings['expiration_action']);?>><?php _e('Archive Sandbox', 'mp-demo'); ?></option>
					<option value="<?php echo MP_DEMO_ACTION_DEACTIVATE; ?>" <?php selected(MP_DEMO_ACTION_DEACTIVATE==$settings['expiration_action']);?>><?php _e('Deactivate Sandbox', 'mp-demo'); ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="redirect"><?php _e('Redirect After Activation', 'mp-demo'); ?></label>
			</th>
			<td>
				<fieldset>
					<i>http://demo.com/sandbox-uid/</i><input type="text" id="redirect" name="settings[redirect]" class="regular-text"
					       value="<?php echo $settings['redirect']; ?>">
					<p class="description"><?php _e('Automatically redirect user to this URL after sandbox activation (optional).', 'mp-demo'); ?></p>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('Google reCAPTCHA', 'mp-demo') ?></th>
			<td>
				<p class="description"><?php _e('Protect registration forms from spam and abuse.', 'mp-demo'); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('reCAPTCHA Site Key', 'mp-demo') ?></th>
			<td>
				<input type="text" class="large-text" name="settings[recaptcha][site_key]"
					   value="<?php echo $settings['recaptcha']['site_key']; ?>">
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('reCAPTCHA Secret Key', 'mp-demo') ?></th>
			<td>
				<input type="password" class="large-text" name="settings[recaptcha][secret_key]"
					   value="<?php echo $settings['recaptcha']['secret_key']; ?>">
			</td>
		</tr>
		<tr>
			<th scope="row"><?php _e('reCAPTCHA Language', 'mp-demo') ?></th>
			<td>
				<input type="text" class="regular-text" name="settings[recaptcha][lang]"
					   value="<?php echo $settings['recaptcha']['lang']; ?>">
			</td>
		</tr>
		<tr>
			<th scope="row">
				<?php _e('Enable Logging', 'mp-demo'); ?>
			</th>
			<td>
				<fieldset>
					<input type="hidden" name="settings[log]" value="0">
					<label><input type="checkbox" name="settings[log]"
					              value="1" <?php checked(1, $settings['log']); ?>> <?php _e('Create a log file when sandbox is created or removed.', 'mp-demo'); ?>
					</label>
				</fieldset>
			</td>
		</tr>
		</tbody>
	</table>

	<?php submit_button(__('Save', 'mp-demo')); ?>
	<?php wp_nonce_field('mp_demo_save', 'mp_demo_save'); ?>
	<input type="hidden" name="tab" value="general">
</form>
