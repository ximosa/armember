<?php
namespace motopress_demo\classes;


use Motopress_Demo;
use motopress_demo\classes\models\Sandbox_DAO;
use motopress_demo\classes\models\General_Settings;
use motopress_demo\classes\models\Mail_Settings;
use motopress_demo\classes\models\Mailchimp_Settings;
use motopress_demo\classes\models\Sandbox;
use motopress_demo\classes\models\User_DAO;
use motopress_demo\classes\modules\Mailchimp_API;

class Shortcodes extends Core {

	protected static $instance;
	protected $mail_manager;
	/*
	 * Consists of occurrence errors
	 */
	public $errors;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->mail_manager = Sandbox_DAO::get_instance();

		add_action('template_redirect', array($this, 'template_redirect'));
		add_action('mp_demo_sandbox_creation', array(Sandbox::get_instance(), 'create'), 10, 2);
	}

	/**
	 * install shortcodes
	 */
	public static function install() {
		// include all core controllers
		Core::include_all(Motopress_Demo::get_plugin_part_path('classes/shortcodes/'));
	}

	/*
	 * Redirect user to his personal sandbox in case valid access token
	 */
	public function template_redirect() {
		if (!isset($_GET['demo-access'])) {
			return;
		}

		$secret = filter_input(INPUT_GET, 'demo-access', FILTER_SANITIZE_STRING);

		if (!$this->mail_manager->secret_exists($secret)) {
			do_action('mp_demo_secret_not_exists', $secret);
			return;
		}

		if ($this->mail_manager->is_expired('secret', $secret) && !$this->mail_manager->is_lifetime('secret', $secret)) {
			do_action('mp_demo_sandbox_is_expired', $secret);
			$url = remove_query_arg(array('demo-access'));
			$url = add_query_arg(array('demo-expired' => '1'), $url);
			wp_redirect($url);
			die();
		}

		$data = $this->mail_manager->get_data('secret', "'{$secret}'");
		$user_data = User_DAO::get_instance()->get_data('user_id', $data['user_id']);
		$siteurl = (is_null($data['site_url']) && $data['site_url'] == '') ? '' : $data['site_url'];

		switch ($data['status']) {
			case MP_DEMO_STATUS_PENDING:
				do_action('mp_demo_before_sandbox_creation', $data['blog_id'], $user_data['email']);
				// Bail if the "prevent_clones" has been set to 1
				if (General_Settings::get_instance()->get_option('prevent_clones') != 1) {
					$this->create_sandbox($data);
				}
				break;
			case MP_DEMO_STATUS_ACTIVE:
				if ($siteurl !== '') {
					do_action('mp_demo_before_sandbox_redirect', $siteurl, $secret);
					update_blog_option($data['blog_id'], 'mp_user', $user_data['email']);
					// Sandbox is already activated, so redirect
					wp_redirect($siteurl);
					die();
				}
				break;
			case MP_DEMO_STATUS_DEACTIVATED:
			case MP_DEMO_STATUS_ARCHIVED:
				do_action('mp_demo_sandbox_is_' . $data['status'], $secret);

				if ($siteurl !== '') {
					$url = $siteurl;
				} else {
					$url = remove_query_arg(array('demo-access'));
					$url = add_query_arg(array('demo-' . $data['status'] => '1'), $url);
				}

				wp_redirect($url);
				die();
				break;
		}
	}

	/**
	 * Listen for our login click
	 *
	 * @access public
	 * @return void
	 */
	public function login_listen() {
		// Bail if we aren't in a sandbox.
		if (!Sandbox::get_instance()->is_sandbox()) {
			return false;
		}
		// Bail if we our mp_login querystring isn't set.
		if (!isset ($_GET['mp_login']) || $_GET['mp_login'] != 1) {
			return false;
		}

		// Bail if the "prevent_clones" has been set to 1
		if (General_Settings::get_instance()->get_option('prevent_clones') == 1) {
			return false;
		}

		wp_clear_auth_cookie();
		// Get our user's credentials
		$user = get_option('mp_user');
		$the_user = get_user_by('email', $user);

		if ($the_user) {
			$user_id = $the_user->ID;
			wp_set_auth_cookie($user_id, true);
			wp_set_current_user($user_id);
			wp_redirect(remove_query_arg(array('mp_login')));
			die();
		}
	}

	/**
	 * Create sandbox and activate email
	 *
	 * @param $data
	 */
	public function create_sandbox($data) {


		$this->mail_manager = Sandbox_DAO::get_instance();
		$user_data = User_DAO::get_instance()->get_data('user_id', $data['user_id']);
		// Check if admin wants to get notices
		$disable_admin_notices = Mail_Settings::get_instance()->get_option('admin', 'disable_admin_notices');

		if ($disable_admin_notices != 1) {
			$to = Mail_Settings::get_instance()->get_option('admin', 'to_email');

			$blog_details = get_blog_details(array('blog_id' => $data['source_blog_id']));
			$source_blog_title = $blog_details ? $blog_details->blogname : '';

			$this->send_mail($to, array(
					'demo_url' => '',
					'password' => '',
					'login' => $user_data['email'],
					'role' => 'admin',
					'site_title' => $source_blog_title,
				)
			);
		}

		//MailChimp API integration class to Subscribe email
		$mailchimp_settings = Mailchimp_Settings::get_instance()->get_options();
		if ($mailchimp_settings['subscribe']) {
			Mailchimp_API::get_instance()->add_to_list($user_data['email'], $mailchimp_settings, $data['source_blog_id']);
		}

		if (apply_filters('mp_demo_check_sandbox_creation', true, $data)) {
			$site_name = get_blog_details($data['source_blog_id'])->blogname . ' - ' . $user_data['email'];
			do_action('mp_demo_sandbox_creation', $data['source_blog_id'], $site_name);
		}
	}

	public function get_errors() {
		if (count($this->errors)) {
			return implode("<br>", (array)$this->errors);
		} else {
			return '';
		}
	}

	/*
	 * @return true if success or false otherwise
	 */
	public function send_mail($to, $mail_data, $is_test = false) {

		if ($is_test) {
			$options = $_POST[$mail_data['role']];
		} else {
			/**
			 * Fires on registration form submit.
			 */
			do_action('mp_demo_submit_registration_form', $_POST);

			$options = Mail_Settings::get_instance()->get_options();
			$options = $options[$mail_data['role']];
		}

		$wp_mail = $this->send_tooled_mail($to, $mail_data, $options);

		if (!$wp_mail) {
			$this->errors[] = __('Mail was not sent.', 'mp-demo');
		}

		return $wp_mail;
	}

	/*
	 * @return true if success or false otherwise
	 */
	public function send_tooled_mail($to, $mail_data, $options) {
		$this->mail_manager = Sandbox_DAO::get_instance();

		$data = array();
		$data['{demo_url}'] = $mail_data['demo_url'];
		$data['{email}'] = $mail_data['login'];
		$data['{login}'] = $mail_data['login'];
		$data['{password}'] = $mail_data['password'];
		$data['{site_title}'] = isset($mail_data['site_title']) ? $mail_data['site_title'] : get_bloginfo();
//		$data['{site_title}'] = get_bloginfo();

		//View::get_instance()->get_template_html
		$data['body'] = ($options['template'] === 'default') ? $this->get_view()->get_template_html("letters/default", array('options' => $options)) : $options['body'];
		$body = $this->mail_manager->get_mail_body($data);

		$headers = '';
		$headers .= $options['from_name'] . PHP_EOL;
		$headers .= "Reply-To: " . $options['from_email'] . PHP_EOL;
		$headers .= "Return-Path: " . $options['from_email'] . PHP_EOL;
		$headers .= "Content-Type: text/html; charset=UTF-8" . PHP_EOL;

		add_filter('wp_mail_content_type', array(&$this, 'set_html_content_type'));
		$wp_mail = wp_mail($to, $options['subject'], $body, $headers);
		remove_filter('wp_mail_content_type', array(&$this, 'set_html_content_type'));

		return $wp_mail;
	}


	public function set_html_content_type() {
		return "text/html";
	}


	/**
	 * Collect email and send invintation to verify demo
	 *
	 * @return boolean
	 */
	public function send_invintation() {

		if(!empty($_POST['arf_plugin'])){

			$plugin_list = maybe_serialize($_POST['arf_plugin']);	
			update_option('mp_demo_selected_plugins', $plugin_list);
		}
		
		

		$to = sanitize_email($_POST['mp_email']);
		$secret = false;

		if (!$to) {
			$this->errors[] = __('Wrong e-mail format', 'mp-demo');
		}

		$this->mail_manager = Sandbox_DAO::get_instance();
		$cur_time = current_time('mysql');

		$get_user = $this->prepare_user($to);

		$source_id = (!empty($_POST['mp_source_id'])) ? $_POST['mp_source_id'] : 1;

		$new_data = array(
				'creation_date' => $cur_time,
				'user_id' => $get_user['user_id'],
				'source_blog_id' => $source_id
		);

		$finded_sandbox = $this->mail_manager->get_sandbox_by_params(array(
			'user_id' => $get_user['user_id'],
			'source_blog_id' => $source_id,
		));

		if ($finded_sandbox) {
			// if we found the row
			switch($finded_sandbox['status']) {
				case MP_DEMO_STATUS_PENDING:
					$secret = $finded_sandbox['secret'];
					break;
				case MP_DEMO_STATUS_ACTIVE:
					if ($this->mail_manager->is_expired('secret', $finded_sandbox['secret'])
							&& !$this->mail_manager->is_lifetime('secret', $finded_sandbox['secret'])) {

						/**
						 * Purge the sandbox
						 */
						$this->mail_manager->purge_sandboxes($finded_sandbox['blog_id']);

						$secret = $this->insert_sandbox($new_data);


					} else {
						$secret = $finded_sandbox['secret'];
					}
					break;
				case MP_DEMO_STATUS_DELETED:
				case MP_DEMO_STATUS_ARCHIVED:
				case MP_DEMO_STATUS_DEACTIVATED:
					$secret = $this->insert_sandbox($new_data);
					break;
			}

		} else {
			// Create new
			$secret = $this->insert_sandbox($new_data);

		}

		if ($secret === false) {
			$this->errors[] = __('Server error has happened.', 'mp-demo');
		}

		//send link to user
		$link = $this->_generate_link($secret);
		$password = $this->decode_pass($get_user['password']);

		if ($get_user['wp_user_id'] == 1) {
			$password = apply_filters('mp_demo_network_admin_password', '—');
		}

		$blog_details = get_blog_details(array('blog_id' => $source_id));
		$source_blog_title = $blog_details->blogname;

		$wp_mail = $this->send_mail($to, array(
			'demo_url' => $link,
			'login' => $to,
			'password' => $password,
			'role' => 'customer',
			'site_title' => $source_blog_title,
		));
		//$data['{site_title}'] = get_bloginfo();
		if ($wp_mail && $secret !== false) {
			return true;
		}


		return false;
	}

	private function insert_sandbox($sandbox){

		$to = sanitize_email($_POST['mp_email']);
		$secret = $this->_generate_secret($to);
		$sandbox['secret'] = $secret;
		$sandbox['status'] = MP_DEMO_STATUS_PENDING;
		$sandbox['site_url'] = '';
		$sandbox['blog_id'] = '';

		$this->mail_manager->insert_data($sandbox);

		return $secret;
	}

	private function prepare_user($email) {

		$user_id = User_DAO::get_instance()->mail_exists($email);
		$user_options = array();

		if($user_id == 0) {
			$user_options['email'] = $email;
			$user_options['is_valid'] = 0;
			$user_options['password'] = $this->encode_pass(
					wp_generate_password(12, false)
			);

			$user_id = User_DAO::get_instance()->insert_data($user_options);

		} else {
			$user_data = User_DAO::get_instance()->get_data('user_id', $user_id);

			$the_user = get_user_by('email', $email);

			if ( $the_user && !empty($user_data['password']) && wp_check_password($this->decode_pass($user_data['password']), $the_user->data->user_pass, $the_user->ID) ) {
				// This User's data is valid
			} else {
				if ($the_user) {
					$user_options['wp_user_id'] = $the_user->ID;
				}

				$new_password = wp_generate_password(12, false);

				$user_options['password'] = $this->encode_pass(
						$new_password
				);

				User_DAO::get_instance()->update_data('user_id', $user_id, $user_options);

				if ($the_user && ($user_data['wp_user_id'] != 1)) {
					wp_set_password($new_password, $the_user->ID);
				}
			}
		}

		$user_data = User_DAO::get_instance()->get_data('user_id', $user_id);

		return $user_data;
	}

	/*
	* Token generation
	*/
	private function _generate_secret($mail) {
		$prefix = uniqid('accesstoken', true);
		$secret = uniqid($mail, true);
		$secret = wp_hash($prefix) . wp_hash($secret);

		return $secret;
	}

	private function _generate_link($secret) {
		$url = home_url();
		$url = preg_replace('/#.*/', '', $url);
		$args = array();
		$args['demo-access'] = $secret;

		return add_query_arg($args, $url);
	}

}