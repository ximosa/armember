<?php
/**
 * class Mailchimp_Settings
 */
namespace motopress_demo\classes\models;

use motopress_demo\classes\Core;

class Mailchimp_Settings extends Core {

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function render_menu_tab() {

		$settings = $this->get_options();
		$mp_demo_cashed_lists = array();
		$sub_tabs = array();

		if (!empty($settings['apikey']) && !empty($settings['user_name'])){

			// Update MailChimp Lists
			if (isset($_GET['update-mailchimp-list'])
				|| (false === ($mp_demo_cashed_lists = get_transient('mp_demo_mailchimp_lists')))
			) {
				$mailchimp = \motopress_demo\classes\modules\Mailchimp_API::get_instance();
				$mailchimp->set_apikey($settings['apikey']);
				$mailchimp->set_username($settings['user_name']);
				$mp_demo_cashed_lists = $mailchimp->get_account_subscribe_lists();

				if (!isset($mp_demo_cashed_lists['error'])) {
//					set_transient('mp_demo_mailchimp_lists', $mp_demo_cashed_lists, 20 * MINUTE_IN_SECONDS);
					set_transient('mp_demo_mailchimp_lists', $mp_demo_cashed_lists, 0);
				} else {
					add_settings_error(
							'mpDemoMailchimpSettings',
							esc_attr('mailchimp_error'),
							$mp_demo_cashed_lists['error'],
							'error'
					);
					$mp_demo_cashed_lists = array();
					delete_transient('mp_demo_mailchimp_lists');
				}
			}

			// Prepare Tabs
			$sites = Core::get_sites(array('public' => 1));

			foreach ($sites as $site) {
				$sub_tabs[$site['blog_id']] = get_blog_option($site['blog_id'], 'blogname');
			}
		}

		wp_enqueue_script('mp-demo-admin-styles');
		wp_enqueue_script('mp-demo-admin-settings');

		$this->get_view()->render_html("admin/settings/mailchimp", array('settings' => $settings, 'sub_tabs' => $sub_tabs, 'mailchimp' => $mp_demo_cashed_lists), true);
	}

	public function get_options() {
		$defaults = array(
			'subscribe' => '0',
			'send_confirm' => '0',
			'apikey' => '',
			'user_name' => '',
//			'list_ids' => array(),
			'subscribe_list' => array(),
		);

		switch_to_blog(MP_DEMO_MAIN_BLOG_ID);
		$options = get_option('mp_demo_mailchimp');
		restore_current_blog();

		$options = ($options === false) ? array() : $options;
		$options = array_merge($defaults, $options);

		return $options;
	}

	public function get_option($key) {
		$options = $this->get_options();
		return isset($options[$key]) ? $options[$key] : '';
	}

	/**
	 * Save options
	 */
	public function save_options() {

		if (!isset($_POST['settings'])) {
			return;
		}

		$options = $_POST['settings'];

		switch_to_blog(MP_DEMO_MAIN_BLOG_ID);
		update_option('mp_demo_mailchimp', $options);
		restore_current_blog();
	}
}