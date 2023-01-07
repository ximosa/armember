<?php
/**
 * class General_Settings
 */
namespace motopress_demo\classes\models;

use motopress_demo\classes\Core;

class General_Settings extends Core {

	var	$time_base;
	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->time_base = array(
				'minutes' => 60,
				'hours' => 60 * 60,
				'days' => 60 * 60 * 24,
				'weeks' => 60 * 60 * 24 * 7,
				'months' => 60 * 60 * 24 * 30,
		);
	}

	public function is_captcha_enabled() {
		$options = $this->get_options();

		return !empty($options['recaptcha']['secret_key']) && !empty($options['recaptcha']['site_key']);
	}

	public function render_menu_tab() {

		$settings = $this->get_options();

		$this->get_view()->render_html("admin/settings/general", array('settings' => $settings), true);
	}

	public function get_options() {
		$defaults = array(
			'prevent_clones' => '0',
			'log' => '0',
			'redirect' => '',
			'login_role' => 'editor',
			'show_toolbar' => '0',
			'target_url' => '',
			'auto_login' => '1',
			'enable_reset' => '0',
			'admin_id' => '1',
			'is_lifetime' => '0',
			'lifespan' => 30 * 60,
			'expiration_duration' => 30,
			'expiration_measure' => 'minutes',
			'expiration_action' => 'delete',

			'recaptcha' => array(
				'site_key' => '',
				'secret_key' => '',
				'lang' => '',
			)
		);

		switch_to_blog(MP_DEMO_MAIN_BLOG_ID);
		$options = get_option('mp_demo_general');
		restore_current_blog();

		$options = ($options === false) ? array() : $options;
		$options = array_merge($defaults, $options);
		$options['expiration_duration'] = isset($options['lifespan']) ? $options['lifespan'] / $this->time_base[$options['expiration_measure']] : 30;

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

		if (isset($options['is_lifetime'])) {
			if ($options['is_lifetime'] == 0) {

				$options['lifespan'] = $options['expiration_duration'] * $this->time_base[$options['expiration_measure']];
			}
		}

		switch_to_blog(MP_DEMO_MAIN_BLOG_ID);
		update_option('mp_demo_general', $options);
		restore_current_blog();
	}
}