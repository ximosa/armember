<div class="wrap">
	<!-- If we have any error by submiting the form, they will appear here -->
	<?php settings_errors('mpDemoLogoSettings'); ?>

	<h3><?php _e('Toolbar Settings', 'mp-demo'); ?></h3>

	<form method="POST" enctype="multipart/form-data">
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">
					<label for="show_toolbar">
						<?php _e('Show Toolbar', 'mp-demo'); ?></label>
				</th>
				<td>
					<fieldset>
						<input type="hidden" name="settings[show_toolbar]" value="0">
						<label><input type="checkbox" name="settings[show_toolbar]" value="1"
								<?php checked(1, $settings['show_toolbar']); ?>><?php _e('Display Toolbar with your products, responsive switcher and call to action button.', 'mp-demo'); ?></label>
								<p class="description"><?php _e('Add ?dr=1 to this demo webiste URL to view Toolbar. Example http://demo.com/?dr=1', 'mp-demo'); ?></p>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="unpermitted"><?php _e('Hide on Blogs', 'mp-demo'); ?></label>
				</th>
				<td>
					<fieldset>
						<?php
						if (isset($settings['unpermitted']) && is_array($settings['unpermitted'])) {
							$settings['unpermitted'] = implode(', ', $settings['unpermitted']);
						} else {
							$settings['unpermitted'] = '';
						}
						?>
						<input type="text" name="settings[unpermitted]" class="regular-text"
						       value="<?php echo $settings['unpermitted']; ?>">

						<p class="description"><?php _e('Enter blog IDs separeted by comma', 'mp-demo'); ?></p>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th><?php _e('Products List', 'mp-demo'); ?></th>
				<td>
					<table id="mp-source-select-items" class="widefat striped">
						<thead>
						<tr>
							<th class="check-column"></th>
							<th class=""><?php _e('ID', 'mp-demo') ?></th>
							<th><?php _e('Label', 'mp-demo') ?></th>
							<th><?php _e('Link', 'mp-demo') ?></th>
							<th><?php _e('Image', 'mp-demo') ?></th>
							<th><?php _e('Button Text', 'mp-demo') ?></th>
							<th><?php _e('Button Link', 'mp-demo') ?></th>
							<th><?php _e('Actions', 'mp-demo') ?></th>
						</tr>
						</thead>
						<tbody>
						<?php $select_data = $settings['select']; ?>
						<?php if (!empty($select_data)): ?>
							<?php foreach ($select_data as $key => $data):
								$data['link_id'] = $key;
								mp_demo_render_toolbar_table_row($data);
							endforeach; ?>
						<?php else: ?>
							<tr class="mp-demo-no-rows-message">
								<td colspan="8">
									<?php _e('Fill in the form bellow to create a product', 'mp-demo'); ?>
								</td>
							</tr>
							<tr class="mp-demo-no-rows-message" style="display: none" data-id="-1">
								<td colspan="8"></td>
							</tr>
						<?php endif; ?>
						</tbody>
					</table>
					<p class="description"><?php _e('Drag and drop to arrange', 'mp-demo'); ?></p>
				</td>
			</tr>
			<tr>
				<th>
					<label><?php _e('Add/Edit Product', 'mp-demo'); ?></label>
				</th>
				<td>
					<table class="form-table mp-source-select-add-item">
						<tbody>
						<tr>
							<td>
								<label for="select-link_id"><?php _e('ID:', 'mp-demo') ?></label><br>
								<input type="text" id="select-link_id" class="regular-text" value="">
								<p class="description"><?php _e('It is usually all lowercase and contains only letters, numbers, and hyphens.', 'mp-demo'); ?></p>
							</td>
						</tr>
						<tr>
							<td>
								<label for="select-text"><?php _e('Label:', 'mp-demo') ?></label><br>
								<input type="text" id="select-text" class="regular-text" value="">
							</td>
						</tr>
						<tr>
							<td>
								<label for="select-link"><?php _e('Link to product:', 'mp-demo') ?></label><br>
								<input type="text" id="select-link" class="regular-text">
							</td>
						</tr>
						<tr>
							<td>
								<label><?php _e('Thumbnail', 'mp-demo'); ?></label><br>
								<input type="text" id="select-img" class="mp_logo_url large-text" value=""/>

								<p>
									<input type="button" class="button upload_image_button"
									       value="<?php _e('Select Thumbnail', 'mp-demo'); ?>"/><br>
								</p>

								<div class="wrap upload_image_preview" style="display: none;">
									<img src="" height="70px" width="70px"
									     alt="<?php _e('Preview', 'mp-demo'); ?>">
								</div>
							</td>
						</tr>
						<tr>
							<td>
								<label for="select-btn_text"><?php _e('Button text', 'mp-demo'); ?></label><br>
								<input type="text" id="select-btn_text" class="regular-text" value="">
							</td>
						</tr>
						<tr>
							<td scope="row">
								<?php _e('Button URL', 'mp-demo'); ?></label><br>
								<input type="url" id="select-btn_url" class="regular-text" value="">
							</td>
						</tr>
						<tr>
							<td scope="row">
								<label for="select-btn_class"><?php _e('Button CSS class', 'mp-demo'); ?></label><br>
								<input type="text" id="select-btn_class" class="regular-text" value="">
							</td>
						</tr>
						<tr>
							<td>
								<input id="mp_add_table_item"
								       type="button"
								       class="button button-large button-primary"
								       data-action="add"
								       value="<?php _e('Add Product', 'mp-demo'); ?>">
								<input id="mp_cancel_table_editing"
								       type="button"
								       class="button button-large"
								       value="<?php _e('Cancel', 'mp-demo'); ?>">
								<span class="spinner left"></span>
							</td>
						</tr>
						</tbody>
					</table>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e('Logo', 'mp-demo'); ?>
				</th>
				<td>
					<?php $style = (empty($settings['logo'])) ? ' style="display: none;"' : ''; ?>
					<p class="wrap upload_image_preview" <?php echo $style; ?>>
						<img src="<?php echo esc_url($settings['logo']); ?>" height="50">
					</p>

					<p>
						<input type="text" class="mp_logo_url large-text" name="settings[logo]"
						       value="<?php echo esc_url($settings['logo']); ?>">
					</p>

					<p>
						<input type="button" class="button upload_image_button"
						       value="<?php _e('Upload Logo', 'mp-demo'); ?>"/><br>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e('Button text when no product selected', 'mp-demo'); ?>
				</th>
				<td>
					<input type="text" name="settings[btn_text]" class="regular-text"
					       value="<?php echo $settings['btn_text']; ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e('Button URL', 'mp-demo'); ?>
				</th>
				<td scope="row">
					<input type="url" name="settings[btn_url]" class="regular-text"
					       value="<?php echo esc_url($settings['btn_url']); ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e('Button CSS class', 'mp-demo'); ?>
				</th>
				<td>
					<input type="text" name="settings[btn_class]" class="regular-text"
					       value="<?php echo $settings['btn_class']; ?>">
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php _e('Theme', 'mp-demo'); ?>
				</th>
				<td>
					<label><input type="radio" name="settings[theme]" value="dark-theme"
							<?php if (isset($settings['theme']) && $settings['theme'] == "dark-theme") echo "checked"; ?>> <?php _e('Dark', 'mp-demo'); ?>
					</label>
					<label><input type="radio" name="settings[theme]" value="light-theme"
							<?php if (isset($settings['theme']) && $settings['theme'] == "light-theme") echo "checked"; ?>> <?php _e('Light', 'mp-demo'); ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<?php _e('Toolbar Background Color', 'mp-demo'); ?>
				</th>
				<td>
					<fieldset>
						<input type="text" name="settings[background]" class="mp-demo-colorpicker"
						       value="<?php echo $settings['background']; ?>">
						</label>
					</fieldset>
				</td>
			</tr>
			</tbody>
		</table>

		<?php submit_button(__('Save', 'mp-demo')); ?>
		<?php wp_nonce_field('mp_demo_save', 'mp_demo_save'); ?>
		<input type="hidden" name="tab" value="toolbar">

	</form>
</div>