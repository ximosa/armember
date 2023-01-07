<?php
/**
 * class Mail_Settings
 */

namespace motopress_demo\classes\models;

use motopress_demo\classes\Shortcodes;

class Mail_Settings extends \motopress_demo\classes\Core {

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function render_admin_tab() {
		$settings = $this->get_options();

		$this->get_view()->render_html("admin/settings/mail/to-admin", array('settings' => $settings), true);
	}

	public function render_customer_tab() {
		$settings = $this->get_options();

		$this->get_view()->render_html("admin/settings/mail/to-customer", array('settings' => $settings), true);
	}

	public function render_delay_tab() {
		$settings = $this->get_options();

		$this->get_view()->render_html("admin/settings/mail/delay", array('settings' => $settings), true);
	}

	public function get_options() {
		$defaults = array(
			'customer' => array(
				'template' => 'default',
				'from_name' => $blog_details = get_blog_details(MP_DEMO_MAIN_BLOG_ID)->blogname,
				'from_email' => get_option('admin_email'),
				'subject' => 'Activate your personal demo website',
				'body' => stripslashes('<h1>Welcome to your demo website!</h1><p>It will help you test drive Your Product. You should be automatically logged in as a demo user, just activate your account below and get started.</p>'
					. '<a href="{demo_url}"><b>Confirm your account</b></a><br><br>Demo is available for {demo_lifetime} hours only so go ahead.<br><br>Login: {login}<br>Password: {password}<br><br>If you have any questions we are always ready to help!'),
			),
			'admin' => array(
				'disable_admin_notices' => '0',
				'template' => 'default',
				'from_name' => $blog_details = get_blog_details(MP_DEMO_MAIN_BLOG_ID)->blogname,
				'from_email' => get_option('admin_email'),
				'to_email' => get_option('admin_email'),
				'subject' => 'Demo website created',
				'body' => stripslashes('<h1 style="padding-top:0;margin-top:0;font-size:16px">One more demo created on {site_title}!</h1><p>Created by: {login}<br><br>Have a great day.</p>'),
			)
		);

		switch_to_blog(MP_DEMO_MAIN_BLOG_ID);
		$options = get_option('mp_demo_mail');
		restore_current_blog();

		$options = ($options === false) ? array() : $options;
		$options = array_merge($defaults, $options);

		return $options;
	}

	public function get_option($group, $key) {
		$options = $this->get_options();
		return isset($options[$group][$key]) ? $options[$group][$key] : '';
	}

	/**
	 * Save options
	 */
	public function save_customer_options() {

		if (isset($_POST)) {
			$options = $this->get_options();

			$curTabId = isset($_GET['subtab']) ? $_GET['subtab'] : 'to-customer';

			if (isset($_POST['test-email-receiver'])) {
				unset($_POST['test-email-receiver']);
			}

			switch ($curTabId) {
				case 'to-customer' :
					if (isset($_POST['customer'])) {
						$options['customer'] = $_POST['customer'];
						$options['customer']['body'] = stripslashes($options['customer']['body']);
						$options['customer']['from_name'] = htmlspecialchars(stripslashes($options['customer']['from_name']));
					}
					break;
				case 'to-admin' :
					if (isset($_POST['admin'])) {
						$options['admin'] = $_POST['admin'];
						$options['admin']['body'] = stripslashes($options['admin']['body']);
						$options['admin']['from_name'] = htmlspecialchars(stripslashes($options['admin']['from_name']));
					}
					break;
				case 'delay' :
					if (isset($_POST['delay'])) {
						$_POST['delay']['delay'] = $_POST['delay']['delay'] * 60 * 60;
						$options['delay'] = $_POST['delay'];
						$options['delay']['from_name'] = htmlspecialchars(stripslashes($options['delay']['from_name']));
					}
					break;
			}

			switch_to_blog(MP_DEMO_MAIN_BLOG_ID);
			update_option('mp_demo_mail', $options);
			restore_current_blog();
		}
	}

	/**
	 * Save options
	 */
	public function save_admin_options() {

		$options = $this->get_options();

		$curTabId = isset($_GET['subtab']) ? $_GET['subtab'] : 'to-customer';

		if (isset($_POST['test-email-receiver'])) {
			unset($_POST['test-email-receiver']);
		}

		switch ($curTabId) {
			case 'to-customer' :
				if (isset($_POST['customer'])) {
					$options['customer'] = $_POST['customer'];
					$options['customer']['body'] = stripslashes($options['customer']['body']);
					$options['customer']['from_name'] = htmlspecialchars(stripslashes($options['customer']['from_name']));
				}
				break;
			case 'to-admin' :
				if (isset($_POST['admin'])) {
					$options['admin'] = $_POST['admin'];
					$options['admin']['body'] = stripslashes($options['admin']['body']);
					$options['admin']['from_name'] = htmlspecialchars(stripslashes($options['admin']['from_name']));
				}
				break;
			case 'delay' :
				if (isset($_POST['delay'])) {
					$_POST['delay']['delay'] = $_POST['delay']['delay'] * 60 * 60;
					$options['delay'] = $_POST['delay'];
					$options['delay']['from_name'] = htmlspecialchars(stripslashes($options['delay']['from_name']));
				}
				break;
		}

		switch_to_blog(MP_DEMO_MAIN_BLOG_ID);
		update_option('mp_demo_mail', $options);
		restore_current_blog();
	}

}