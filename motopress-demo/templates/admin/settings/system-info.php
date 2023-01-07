<?php
/**
 * System Info
 * These are functions are used for displaying System information in admin.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;
global $wpdb;

// Try to identify the hosting provider
$host = false;
if (defined('WPE_APIKEY')) {
	$host = 'WP Engine';
} elseif (defined('PAGELYBIN')) {
	$host = 'Pagely';
}
?>

<h3><?php _e('System Information', 'mp-demo'); ?></h3>
<table class="form-table mp-demo-admin-systeminfo striped">
	<thead>
	<tr>
		<th class="manage-column column-columnname" scope="col"><?php _e('Name', 'mp-demo'); ?></th>
		<th class="manage-column column-columnname" scope="col"><?php _e('Value', 'mp-demo'); ?></th>
		<th class="manage-column column-columnname" scope="col"><?php _e('Status', 'mp-demo'); ?></th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ($mp_settings_options as $key => $value): ?>
		<tr>
			<td>
				<?php echo $value['title']; ?>
			</td>
			<td>
				<?php echo $value['value']; ?>
			</td>
			<td>
				<?php
				if ($value['status'] === false) {
					echo '<span class="mp-demo-error-message">*</span> ';
					echo $value['message'];
				} else {
//								echo '<span>&#10003;</span>' ;
					echo '<span class="mp-demo-success-message">&#9989;</span>';
				}
				?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>

<form id="mp-demo-download-sysinfo-form" enctype="multipart/form-data"
      action="<?php echo add_query_arg(array('page' => 'mp-demo-settings', 'tab' => 'system-info')); ?>"
      method="POST">
<textarea readonly="readonly" name="mp-demo-sysinfo" style="display: none;">

Multisite: <?php echo is_multisite() ? 'Yes' . "\n" : 'No' . "\n" ?>
	Sub-directories installation: <?php echo (SUBDOMAIN_INSTALL == false) ? 'Yes' . "\n" : 'No' . "\n" ?>

	SITE_URL: <?php echo site_url() . "\n"; ?>
	HOME_URL: <?php echo home_url() . "\n"; ?>

	Motopress Demo Version: <?php echo \motopress_demo\classes\Core::get_version() . "\n"; ?>
	WordPress Version: <?php echo get_bloginfo('version') . "\n"; ?>
	<?php if ($host) : ?>
		Host:                     <?php echo $host . "\n"; ?>
	<?php endif; ?>

	PHP Version: <?php echo PHP_VERSION . "\n"; ?>
	Web Server Info: <?php echo $_SERVER['SERVER_SOFTWARE'] . "\n"; ?>

	WP_DEBUG: <?php echo defined('WP_DEBUG') ? WP_DEBUG ? 'Enabled' . "\n" : 'Disabled' . "\n" : 'Not set' . "\n" ?>

	WP Table Prefix: <?php echo "Length: " . strlen($wpdb->prefix);
	echo " Status:";
	if (strlen($wpdb->prefix) > 16) {
		echo " ERROR: Too Long";
	} else {
		echo " Acceptable";
	}
	echo "\n"; ?>

	Session: <?php echo isset($_SESSION) ? 'Enabled' : 'Disabled'; ?><?php echo "\n"; ?>
	Session Name: <?php echo esc_html(ini_get('session.name')); ?><?php echo "\n"; ?>
	Cookie Path: <?php echo esc_html(ini_get('session.cookie_path')); ?><?php echo "\n"; ?>
	Save Path: <?php echo esc_html(ini_get('session.save_path')); ?><?php echo "\n"; ?>
	Use Cookies: <?php echo ini_get('session.use_cookies') ? 'On' : 'Off'; ?><?php echo "\n"; ?>
	Use Only Cookies: <?php echo ini_get('session.use_only_cookies') ? 'On' : 'Off'; ?><?php echo "\n"; ?>

	DISPLAY ERRORS: <?php echo (ini_get('display_errors')) ? 'On (' . ini_get('display_errors') . ')' : 'N/A'; ?><?php echo "\n"; ?>
	FSOCKOPEN: <?php echo (function_exists('fsockopen')) ? 'Your server supports fsockopen.' : 'Your server does not support fsockopen.'; ?><?php echo "\n"; ?>
	cURL: <?php echo (function_exists('curl_init')) ? 'Your server supports cURL.' : 'Your server does not support cURL.'; ?><?php echo "\n"; ?>
	SOAP Client: <?php echo (class_exists('SoapClient')) ? 'Your server has the SOAP Client enabled.' : 'Your server does not have the SOAP Client enabled.'; ?><?php echo "\n"; ?>

	ACTIVE PLUGINS:

	<?php
	$plugins = get_plugins();
	$active_plugins = get_option('active_plugins', array());

	foreach ($plugins as $plugin_path => $plugin) {
// If the plugin isn't active, don't show it.
		if (!in_array($plugin_path, $active_plugins))
			continue;

		echo $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
	}

	if (is_multisite()) :
		?>

		NETWORK ACTIVE PLUGINS:

		<?php
		$plugins = wp_get_active_network_plugins();
		$active_plugins = get_site_option('active_sitewide_plugins', array());

		foreach ($plugins as $plugin_path) {
			$plugin_base = plugin_basename($plugin_path);

// If the plugin isn't active, don't show it.
			if (!array_key_exists($plugin_base, $active_plugins))
				continue;

			$plugin = get_plugin_data($plugin_path);

			echo $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
		}

	endif;
	?></textarea>

	<p class="submit">
		<input type="hidden" name="mp-demo-action" value="download_sysinfo"/>
		<?php submit_button('Download System Information', 'primary', 'mp-demo-download-sysinfo', false); ?>
	</p>
</form>