<?php
/**
 * Toolbar
 *
 * This class handles outputting our front-end theme switcher, toolbar
 */
namespace motopress_demo\classes\models;

use Motopress_Demo;
use motopress_demo\classes\Model;

class License extends Model {

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	function render_menu_tab() {
		$license = get_blog_option(1, 'edd_mp_demo_license_key');
		$licenseData = $license;
		if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
			add_settings_error(
				'mpDemoLicenseSettings',
				esc_attr('settings_updated'),
				__('Settings saved.', 'mp-demo'),
				'updated'
			);
		}

		if ($license) {
			$licenseData = $this->check_license($license);
		}
		$this->get_view()->render_html("admin/settings/license", array('licenseData' => $licenseData, 'license' => $license), true);
	}

	// Plugin Activation
	function license_install($network_wide) {
		$autoLicenseKey = apply_filters('mp_demo_auto_license_key', false);
		if ($autoLicenseKey) {
			self::set_and_activate_license_key($autoLicenseKey);
		}
	}

	// check a license key
	private function check_license($license) {
		$apiParams = array(
			'edd_action' => 'check_license',
			'license' => $license,
			'item_name' => urlencode(Motopress_Demo::get_plugin_full_name())
		);
		// Call the custom API.
		$response = wp_remote_get(add_query_arg($apiParams, Motopress_Demo::get_plugin_store_url()), array('timeout' => 15, 'sslverify' => false));
		if (is_wp_error($response)) {
			return false;
		}
		$licenseData = json_decode(wp_remote_retrieve_body($response));
		return $licenseData;
	}

	public function save_options() {

		if (isset($_POST['edd_mp_demo_license_key'])) {
			$licenseKey = trim($_POST['edd_mp_demo_license_key']);
			self::set_license_key($licenseKey);
		}
		//activate
		if (isset($_POST['edd_license_activate'])) {
			$licenseData = self::activate_license();

			if ($licenseData === false)
				return false;

			if (!$licenseData->success && $licenseData->error === 'item_name_mismatch') {
				$queryArgs['item-name-mismatch'] = 'true';
			}
		}
		//deactivate
		if (isset($_POST['edd_license_deactivate'])) {
						// retrieve the license from the database
			$licenseData = self::deactivate_license();

			if ($licenseData === false)
				return false;
		}

		return true;
	}

	static public function set_license_key($licenseKey) {
		$oldLicenseKey = get_blog_option(1, 'edd_mp_demo_license_key');
		if ($oldLicenseKey && $oldLicenseKey !== $licenseKey) {
			delete_blog_option(1, 'edd_mp_demo_license_status'); // new license has been entered, so must reactivate
		}
		if (!empty($licenseKey)) {
			update_blog_option(1, 'edd_mp_demo_license_key', $licenseKey);
		} else {
			delete_blog_option(1, 'edd_mp_demo_license_key');
		}
	}

	static public function activate_license() {
		$licenseKey = get_blog_option(1, 'edd_mp_demo_license_key');

		// data to send in our API request
		$apiParams = array(
			'edd_action' => 'activate_license',
			'license' => $licenseKey,
			'item_name' => urlencode(Motopress_Demo::get_plugin_full_name()) // the name of our product in EDD
		);
		// Call the custom API.
		$response = wp_remote_get(add_query_arg($apiParams, Motopress_Demo::get_plugin_store_url()), array('timeout' => 15, 'sslverify' => false));
		// make sure the response came back okay
		if (is_wp_error($response)) {
			return false;
		}
		// decode the license data
		$licenseData = json_decode(wp_remote_retrieve_body($response));
		// $licenseData->license will be either "active" or "inactive"
		update_blog_option(1, 'edd_mp_demo_license_status', $licenseData->license);

		return $licenseData;
	}

	static public function deactivate_license() {
		$licenseKey = get_blog_option(1, 'edd_mp_demo_license_key');

		// data to send in our API request
		$apiParams = array(
			'edd_action' => 'deactivate_license',
			'license' => $licenseKey,
			'item_name' => urlencode(Motopress_Demo::get_plugin_full_name()) // the name of our product in EDD
		);
		// Call the custom API.
		$response = wp_remote_get(add_query_arg($apiParams, Motopress_Demo::get_plugin_store_url()), array('timeout' => 15, 'sslverify' => false));
		// make sure the response came back okay
		if (is_wp_error($response)) {
			return false;
		}
		// decode the license data
		$licenseData = json_decode(wp_remote_retrieve_body($response));
		// $license_data->license will be either "deactivated" or "failed"
		if ($licenseData->license == 'deactivated') {
			delete_blog_option(1, 'edd_mp_demo_license_status');
		}

		return $licenseData;
	}

	static public function set_and_activate_license_key($licenseKey) {
		self::set_license_key($licenseKey);
		self::activate_license();
	}

} // End Class