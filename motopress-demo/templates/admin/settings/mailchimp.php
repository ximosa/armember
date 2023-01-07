<h3><?php _e('MailChimp Settings', 'mp-demo'); ?></h3>

<form method="POST">
	<table class="form-table">
		<tbody>
		<tr>
			<th scope="row">
			</th>
			<td>
				<fieldset>
					<input type="hidden" name="settings[subscribe]" value="0">
					<label><input type="checkbox" name="settings[subscribe]"
					              value="1" <?php checked(1, $settings['subscribe']); ?>> <?php _e('Subscribe users to Mailchimp after Sandbox activation', 'mp-demo'); ?>
					</label>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="user_name"><?php _e('MailChimp User Name', 'mp-demo'); ?></label>
			</th>
			<td>
				<fieldset>
					<input type="text" name="settings[user_name]" class="regular-text"
					       value="<?php echo $settings['user_name']; ?>">
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row">
				<label for="apikey"><?php _e('MailChimp API Key', 'mp-demo'); ?></label>
			</th>
			<td>
				<fieldset>
					<input type="text" name="settings[apikey]" class="regular-text" value="<?php echo $settings['apikey']; ?>">
				</fieldset>
			</td>
		</tr>

		<!--	MailChimp Settings Details	-->

		<?php if (!empty($settings['apikey']) && !empty($settings['user_name'])): ?>

			<tr>
				<th scope="row">
					<label for="send_confirm"><?php _e('Opt-In Confirmation', 'mp-demo'); ?></label>
				</th>
				<td>
					<fieldset>
						<input type="hidden" name="settings[send_confirm]" value="0">
						<label><input type="checkbox" name="settings[send_confirm]"
						              value="1" <?php checked(1, $settings['send_confirm']); ?>> <?php _e('Send Confirmation Email', 'mp-demo'); ?>
							<p class="description"><?php _e('Inside the confirmation email, the subscriber will see a link to confirm their subscription. To be added to your list, they must click the link.', 'mp-demo') ?></p>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e('Update Lists', 'mp-demo'); ?></label>
				</th>
				<td>
					<fieldset>
						<a class="button-secondary" href="<?php echo add_query_arg(array('update-mailchimp-list' => 1)); ?>"><?php _e('Synchronize', 'mp-demo') ?></a>
						<p class="description"><?php _e('Synchronize with your current MailChimp lists and interests.', 'mp-demo') ?></p>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e('Select the lists to subscribe a new member to', 'mp-demo'); ?></label>
				</th>
				<td class="mp-demo-select-list-wrap">

					<?php if (is_array($sub_tabs)): ?>

						<!-- Tabs -->

					<h2 class="nav-tab-wrapper mp-demo-nav-tabs"><?php
						foreach ($sub_tabs as $tab_id => $tab_name) {
							$class = ($tab_id == 1) ? ' nav-tab-active' : '';

							echo '<a href="' . esc_url('#mp-demo-nav-tab-' . $tab_id)  . '" class="nav-tab' . $class . '">' . $tab_name . '</a>';
						}
					?></h2>
						<!-- Tabs Content -->
						<?php
						foreach ($sub_tabs as $tab_id => $tab_name):
//							$style = ($tab_id != 1) ? ' style="display:none"' : '';
							$hiddenClass = ($tab_id != 1) ? ' mp-demo-hide' : '';
							$name_prefix = 'settings[subscribe_list][' . $tab_id . ']';

							$override = false;

							if (isset($settings['subscribe_list'][$tab_id]['list_ids'])) {
								$list_ids = $settings['subscribe_list'][$tab_id]['list_ids'];
								$override = (isset($settings['subscribe_list'][$tab_id]['override'])) ? $settings['subscribe_list'][$tab_id]['override'] : 0;

							} elseif (isset($settings['list_ids'])) {
								// Back compatibility with v1.0.3 and lower
								$list_ids = $settings['list_ids'];

							} else {
								$list_ids = array();
							}

							?>
							<div id="mp-demo-nav-tab-<?php echo $tab_id; ?>" class="tab-content<?php echo $hiddenClass?>">
								<!-- Content -->

								<?php if ($tab_id != 1) { ?>
									<fieldset>
										<input type="hidden" name="<?php echo $name_prefix; ?>[override]" value="0">
										<label><input type="checkbox" name="<?php echo $name_prefix; ?>[override]"
										              value="1" <?php checked(1, $override); ?>> <?php _e('I would like to subscribe new members to other lists and interests for this sub-site. ', 'mp-demo'); ?>
										</label>
									</fieldset>
								<?php } else { ?>
									<p class="description"><?php _e('Select the lists and interests you wish a new member to be subscribed by default. You may select different lists for each sub-site.', 'mp-demo') ?></p>
								<?php } ?>

								<div class="mp-admin-restrict">
									<?php
									if (count($mailchimp) > 0):
										foreach ($mailchimp as $key => $list_item):

											$interest_name_prefix = $name_prefix . '[list_ids][' . $list_item['id'] . ']';

											// $list_ids = isset($settings['list_ids']) ? $settings['list_ids'] : $settings[$tab_id]['list_ids'];

											?>

											<div class="mp-parent-div box">
												<?php $check = array_key_exists($list_item['id'], $list_ids) ? ' checked' : ''; ?>
												<h4><label><input type="checkbox" name="<?php echo $interest_name_prefix ?>"
												                  value="<?php echo $list_item['id']; ?>"<?php echo $check; ?>><?php echo $list_item['title']; ?>
													</label>
												</h4>
												<ul>
													<?php foreach ($list_item['categories'] as $k => $category_item): ?>
														<li>
															<label><?php _e('Category:', 'mp-demo'); ?><?php echo ' ' . $category_item['title']; ?></label>
															<ul>
																<?php foreach ($category_item['interests'] as $interest): ?>
																	<li>
																		<?php
																		$name = $interest_name_prefix . "[" . $interest['id'] . "]";
																		if (isset($list_ids[$list_item['id']][$interest['id']])) {
																			$check = ($list_ids[$list_item['id']][$interest['id']] == 'true') ? ' checked' : '';
																		} else {
																			$check = '';
																		}
																		?>
																		<label><input type="checkbox"
																		              name="<?php echo $name; ?>"
																		              value="true"<?php echo $check; ?>><?php echo $interest['title']; ?>
																		</label>
																	</li>
																<?php endforeach; ?>
															</ul>
														</li>
													<?php endforeach; ?>
												</ul>
											</div>
										<?php endforeach; ?>
									<?php endif; ?>
								</div>

							</div>

						<?php endforeach; ?>



						<hr>

					<?php endif; ?>

				</td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>

	<?php submit_button(__('Save', 'mp-demo')); ?>
	<?php wp_nonce_field('mp_demo_save', 'mp_demo_save'); ?>
	<input type="hidden" name="tab" value="mailchimp">
</form>
