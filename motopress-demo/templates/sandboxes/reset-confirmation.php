
<!--<h3>--><?php //_e('Reset your demo', 'mp-demo'); ?><!--</h3>-->
<form method="POST">
	<p>
		<?php _e("Reset your demo to default (syncronized with Administrator's updates).", 'mp-demo'); ?>
	</p>
	<?php submit_button(__('Confirm', 'mp-demo')); ?>

	<?php wp_nonce_field('mp_demo_save', 'mp_demo_save'); ?>
	<input type="hidden" name="tab" value="reset">
</form>
