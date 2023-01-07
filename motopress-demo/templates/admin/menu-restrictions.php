<h3><?php _e('Allow users to access these Dashboard menus in Sandbox (WordPress user role selected for Sandbox has higher priority).', 'mp-demo'); ?></h3>

<h4><?php _e('Restrictions for', 'mp-demo'); ?> <?php echo get_bloginfo(); ?></h4>

<form method="POST">
	<div class="mp-admin-restrict">
		<?php
		$page_key = 0;


			
		foreach ($menu as $page) {

			if (!empty($page[0]) && !mp_demo_is_forbidden_page($page[2])) {
				$parent_slug = $page[2];
				$class_name = str_replace('.', '', $parent_slug);
				$parent_pages = isset ($settings['parent_pages']) ? $settings['parent_pages'] : array();
				$child_pages = isset ($settings['child_pages']) ? $settings['child_pages'] : array();
				?>
				<div class="mp-parent-div box">
					<h4><label><input type="checkbox" name="settings[parent_pages][]" value="<?php echo $page[2]; ?>"
					                  class="mp-demo-parent" <?php checked(in_array($page[2], $parent_pages));
							if ($parent_slug == 'index.php') {
								echo 'disabled="disabled" checked="checked"';
							} ?> > <?php echo $page[0]; ?></label></h4>
					<?php
					if (isset ($sub_menu[$parent_slug])) {
						?>
						<ul>
							<?php
							foreach ($sub_menu[$parent_slug] as $subpage) {
								$found = \motopress_demo\classes\Capabilities::get_instance()->in_restrictions(mp_demo_generate_submenu_uri($parent_slug, $subpage[2]), $child_pages);

								if ($found !== false) {
									$checked = 'checked="checked"';
								} else {
									$checked = '';
								}
								?>
								<li>
									<?php if ($subpage[2] == 'index.php'): ?>
										<input type="hidden" name="settings[child_pages][<?php echo $page_key; ?>][parent]" value="index.php">
										<input type="hidden" name="settings[child_pages][<?php echo $page_key; ?>][slug]" value="index.php">
										<input type="checkbox"
										       name="settings[child_pages][<?php echo $page_key; ?>][status]"
										       value="1" disabled="disabled" checked="checked"><?php
										       echo $subpage[0];
										       ?></label></li>
										<?php
										continue;
									endif; ?>
									<label>
										<input type="hidden" name="settings[child_pages][<?php echo $page_key; ?>][parent]" value="<?php echo $parent_slug; ?>">
										<input type="hidden" name="settings[child_pages][<?php echo $page_key; ?>][slug]" value="<?php echo  mp_demo_generate_submenu_uri($parent_slug, $subpage[2]); ?>">

										<input type="hidden"
										       name="settings[child_pages][<?php echo $page_key; ?>][status]" value="0">
										<input type="checkbox"
										       name="settings[child_pages][<?php echo $page_key; ?>][status]" value="1" <?php echo $checked; ?>>
										<?php echo $subpage[0]; ?></label></li>
								<?php
								$page_key++;
							}
							?>
						</ul>
						<?php
					}
					?>
				</div>
				<?php
			}
		}
		?>
	</div>

	<h2><?php _e('Disallow users to access these URLs.', 'mp-demo'); ?></h2>
	<p><?php _e('Type in relative links to your main Dashboard separated by new line. For example "post-new.php?post_type=page"', 'mp-demo'); ?></p>
	<textarea name="settings[black_list]" cols="100" rows="5" class="large-text"><?php echo implode(PHP_EOL, $settings['black_list']); ?></textarea>

	<?php if ($plugins_data): ?>

		<hr>
		<h2><?php _e('Plugins', 'mp-demo'); ?></h2>

		<div class="mp-admin-restrict">
			<ul>
				<?php foreach ($plugins_data as $slug => $plugin_data) { ?>
					<li>
						<label>
							<input type="checkbox" name="settings[plugins_data][]" value="<?php echo $slug ?>"
								<?php checked(in_array($slug, $settings['plugins_data'])); ?> >
							<?php echo $plugin_data['Name'] ?> - <?php echo $slug ?>
						</label>
					</li>
					<?php
				}
				?>
			</ul>

		</div>

	<?php endif; ?>

	<?php submit_button(__('Save', 'mp-demo')); ?>
	<?php wp_nonce_field('mp_demo_save', 'mp_demo_save'); ?>
	<input type="hidden" name="tab" value="restrictions">

</form>

