<div class="mp-start-demo">
	<?php do_action('mp_demo_before_popup_link'); ?>
	<?php $source_id = is_array($source_id) ? $source_id : explode(',', $source_id); ?>
	<button class="mp-demo-popup-link-popup mfp-ajax<?php echo (empty($wrapper_class)) ? '' : ' ' . $wrapper_class; ?>" data-slug="try-demo-popup"><?php echo $launch_btn; ?></button>
	<?php do_action('mp_demo_after_popup_link'); ?>

	<form action="<?php echo the_permalink(); ?>" method="post" enctype="multipart/form-data" id="try-demo-popup"
	      class="<?php echo apply_filters('mp_demo_popup_form_class', 'try-demo-popup') ?>" style="display: none;">
		<input name="mp_demo_create_sandbox" type="hidden" value="1">

		<?php do_action('mp_demo_popup_before_content'); ?>

		<?php if (!empty($title)): ?>
			<h3><?php echo $title; ?></h3>
		<?php endif; ?>
		<p class="input-wrapper">
			<?php if (!empty($label)): ?>
				<label for="mp_email"><?php echo $label; ?></label>
			<?php endif; ?>
			<input type="email" id="mp_email" name="mp_email" class="mp-demo-email" placeholder="<?php echo $placeholder; ?>" required>
			<?php if ( !empty($content) ) : ?>
				<br>
				<i><?php echo htmlspecialchars_decode($content); ?></i>
			<?php endif; ?>
		</p>
		<?php if (is_array($source_id) && count($source_id) > 1): ?>
			<p>
				<?php if (!empty($select_label)): ?>
					<label for="mp_source_id"><?php echo $select_label; ?></label><br/>
				<?php endif; ?>
				<select name="mp_source_id" id="mp_source_id" class="mp-demo-source-blog">
					<?php foreach ($source_id as $source_key => $source): ?>
						<?php
						$is_selected = $source_key == 0;
						$blog_details = get_blog_details($source);
						if ($blog_details):
						?>
							<option value="<?php echo $source; ?>" <?php selected($is_selected); ?>><?php echo $blog_details->blogname; ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</p>
		<?php else: ?>
			<input name="mp_source_id" type="hidden" value="<?php echo is_array($source_id) ? $source_id[0] : $source_id; ?>">
		<?php endif; ?>
		<?php if (\motopress_demo\classes\models\General_Settings::get_instance()->is_captcha_enabled()
					&& isset($captcha)
					&& ($captcha == 1) ): ?>
		<div class="g-recaptcha  mp-recaptcha" data-sitekey="<?php echo $captcha_options['site_key']; ?>"></div>
		<p>
		</p>
		<?php endif; ?>
		<p>
			<input type="submit" name="submit" id="mp_submit" value="<?php echo $submit_btn; ?>"
				class="<?php echo apply_filters('mp_demo_form_submit_button_class', '') ?>">
			<img src="<?php echo $loader_url; ?>" class="mp-loader">
		</p>
		<div class="mp-message">
			<p class="mp-body">
				<span class="mp-demo-success"><?php echo $success; ?></span>
				<span class="mp-demo-fail"><?php echo $fail; ?> <span class="mp-errors"></span></span>
			</p>
		</div>
		
		<?php do_action('mp_demo_popup_after_content'); ?>

	</form>
</div>
