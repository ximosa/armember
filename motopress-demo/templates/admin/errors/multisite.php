<?php
/*
use is_multisite() and SUBDOMAIN_INSTALL
 */
?>

<div class="wrap">

	<div class="error">
		<p><b><?php _e('Your WordPress installation does not meet Demo Builder plugin requirements:', 'mp-demo'); ?></b></p>
		<ol>
			<?php if ( !is_multisite() ) :?>
			<li><strong>WordPress Multisite.</strong> To create a network of sites (multisite) from your WordPress installation using sub-directories option follow this <a href="https://codex.wordpress.org/Create_A_Network" target="_blank">guide</a>
			</li>
			<?php endif; ?>
			<?php if ( (defined('SUBDOMAIN_INSTALL') && SUBDOMAIN_INSTALL == true) ): ?>
			<li><strong>WordPress Multisite with sub-domain installation</strong>. Your WordPress Multisite installation should be installed using <a href="https://codex.wordpress.org/Create_A_Network#Step_3:_Installing_a_Network" target="_blank">sub-directories option</a></li>
			<?php endif; ?>
		</ol>
		
		<p>You can find more information in <a href="http://www.getmotopress.com/files/demo-builder-plugin-documentation.pdf" target="_blank">documentation</a></p>
	</div>
</div>