
<div class="wrap">
	<?php screen_icon('options-general'); ?>
	<h2><?php _e('Plugin License', 'mp-demo'); ?></h2>
	<i><?php printf( __('The License Key is required in order to get automatic plugin updates and support. You can manage your License Key in your personal account. <a %s>Learn more</a>.', 'mp-demo'),
				'href="https://motopress.zendesk.com/hc/en-us/articles/202812996-How-to-use-your-personal-MotoPress-account" target="blank"'); ?></i>
	<?php settings_errors('mpDemoLicenseSettings', false); ?>
	<form action="" method="POST" autocomplete="off">

		<table class="form-table">
			<tbody>
			<tr valign="top">
				<th scope="row" valign="top">
					<?php echo __('License Key', 'mp-demo'); ?>
				</th>
				<td>
					<input id="edd_mp_demo_license_key" name="edd_mp_demo_license_key" type="password"
					       class="regular-text" value="<?php esc_attr_e($license); ?>" >
					<?php if ($license) { ?>
						<i style="display:block;"><?php echo str_repeat("&#8226;", 20) . substr($license, -7); ?></i>
					<?php } ?>
				</td>
			</tr>
			<?php if ($license) { ?>
				<tr valign="top">
					<th scope="row" valign="top">
						<?php _e('Status', 'mp-demo'); ?>
					</th>
					<td>
						<?php
						if ($licenseData) {
							switch($licenseData->license) {
								case 'inactive' :
								case 'site_inactive' :
									_e('Inactive', 'mp-demo');
									break;
								case 'valid' :
									if ($licenseData->expires !== 'lifetime') {
										$date = ($licenseData->expires) ? new DateTime($licenseData->expires) : false;
										$expires = ($date) ? ' ' . $date->format('d.m.Y') : '';
										echo __('Valid until', 'mp-demo') . $expires;
									} else {
										echo __('Valid', 'mp-demo');
									}
									break;
								case 'disabled' :
									_e('Disabled', 'mp-demo');
									break;
								case 'expired' :
									__e('Expired', 'mp-demo');
									break;
								case 'invalid' :
									_e('Invalid', 'mp-demo');
									break;
								case 'item_name_mismatch' :
									printf( __("Your License Key does not match the installed plugin. <a %s>How to fix this.</a>",
											'mp-demo'),
											'href="https://motopress.zendesk.com/hc/en-us/articles/202957243-What-to-do-if-the-license-key-doesn-t-correspond-with-the-plugin-license" target="_blank"');
									break;
							}
						}
						?>
					</td>
				</tr>
				<?php if (isset($licenseData->license) && in_array($licenseData->license, array('inactive', 'site_inactive', 'valid', 'expired'))) { ?>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php _e('Action', 'mp-demo'); ?>
						</th>
						<td>
							<?php
							if ($licenseData) {
								if ($licenseData->license === 'inactive' || $licenseData->license === 'site_inactive') {?>
									<input type="submit" class="button-secondary" name="edd_license_activate"
									       value="<?php _e('Activate License', 'mp-demo'); ?>">
									<?php
								} elseif ($licenseData->license === 'valid') { ?>
									<input type="submit" class="button-secondary" name="edd_license_deactivate"
									       value="<?php _e('Deactivate License', 'mp-demo'); ?>">
									<?php
								} elseif ($licenseData->license === 'expired') { ?>
									<a href="<?php echo  Motopress_Demo::get_plugin_store_url(). 'buy/'; ?>"
									   class="button-secondary"
									   target="_blank"><?php _e('Renew License', 'mp-demo'); ?></a>
									<?php
								}
							}
							?>
						</td>
					</tr>
				<?php } ?>
			<?php } ?>
			</tbody>
		</table>

		<?php submit_button(__('Save', 'mp-demo')); ?>
		<?php wp_nonce_field('mp_demo_save', 'mp_demo_save'); ?>
		<input type="hidden" name="tab" value="license">

	</form>
</div>
<?php
