<?php
/**
 * Date: 5/30/2016
 * Time: 5:49 PM
 */

namespace motopress_demo\classes\modules;

use motopress_demo\classes\Module;

class Mailchimp_API extends Module {
	/**
	 * Cache the user api_key so we only have to log in once per client instantiation
	 */
	var $api_key;
	var $url;
	var $username;
	var $dc;
	var $version = '3.0';
	var $show_all_params = 'count=100&offset=0';

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Connect to the MailChimp API for a given list.
	 *
	 * @param string $apikey Your MailChimp apikey
	 * @param string $secure Whether or not this should use a secure connection
	 */
	public function __construct($apikey = '') {
		$this->api_key = $apikey;
		$this->dc = substr($apikey, strpos($apikey, '-') + 1);;
		$this->url = "https://{$this->dc}.api.mailchimp.com/{$this->version}/";
	}

	public function set_apikey($apikey) {
		$this->api_key = $apikey;
		$this->dc = substr($apikey, strpos($apikey, '-') + 1);
		$this->url = "https://{$this->dc}.api.mailchimp.com/{$this->version}/";
	}

	public function set_username($username) {
		$this->username = $username;
	}

	/**
	 * @return $this installed instanse
	 */
	public function init_options($mailchimp_settings) {
		$this->set_apikey($mailchimp_settings['apikey']);
		$this->set_username($mailchimp_settings['user_name']);

		return $this;
	}

	/**
	 * Get Mailchimp account lists by username & api_key
	 *
	 * @return array|mixed
	 */
	public function get_lists() {

		$request = wp_remote_get($this->url  . "lists". "?" . $this->show_all_params, array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->api_key),
			),
			'sslverify' => false
		));

		if (wp_remote_retrieve_response_code($request) == 200) {
			$body = wp_remote_retrieve_body($request);
			$body = json_decode($body, true);

			if (isset($body['lists'])) {
				$body = array_map(function ($item) {
					return array('id' => $item['id'], 'title' => $item['name']);
				}, $body['lists']);
			} else {
				$body = array();
			}
		} else {
			return $this->get_errors($request);
		}

		return $body;
	}

	/**
	 * Get Mailchimp account categories by list as array('id' => 'id', 'title' => 'title')
	 *
	 * @param $list_id
	 *
	 * @return array|mixed
	 */
	public function get_interest_categories($list_id) {

		$request = wp_remote_get($this->url . "lists/{$list_id}/interest-categories". "?" . $this->show_all_params, array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->api_key),
			),
			'sslverify' => false
		));

		if (wp_remote_retrieve_response_code($request) == 200) {
			$body = wp_remote_retrieve_body($request);
			$body = json_decode($body, true);

			if (isset($body['categories'])) {
				$body = array_map(function ($item) {
					return array('id' => $item['id'], 'title' => $item['title']);
				}, $body['categories']);
			} else {
				$body = array();
			}
		} else {
			return $this->get_errors($request);
		}

		return $body;
	}

	/**
	 * Get list interests
	 *
	 * @param $list_id
	 * @param $category_id
	 *
	 * @return array|mixed
	 */
	public function get_interests($list_id, $category_id) {

		$request = wp_remote_get($this->url . "lists/{$list_id}/interest-categories/{$category_id}/interests". "?" . $this->show_all_params, array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->api_key),
			),
			'sslverify' => false
		));

		if (wp_remote_retrieve_response_code($request) == 200) {
			$body = wp_remote_retrieve_body($request);
			$body = json_decode($body, true);

			if (isset($body['interests'])) {
				$body = array_map(function ($item) {
					return array('id' => $item['id'], 'title' => $item['name']);
				}, $body['interests']);
			} else {
				$body = array();
			}
		} else {
			return $this->get_errors($request);
		}

		return $body;
	}

	/**
	 * Get all Lists => Categories => Interest By the API Key
	 *
	 * @return array
	 */
	public function get_account_subscribe_lists() {
		$result = array();
		$list = $this->get_lists();

		if (isset($list['error']))
			return $list;

		if (count($list) > 0) {
			$result = $list;

			foreach ($list as $key => $list_item) {
				$categories = $this->get_interest_categories($list_item['id']);
				if (isset($categories['error']))
					return $categories;

				$result[$key]['categories'] = $categories;
				foreach ($result[$key]['categories'] as $k => $category_item) {
					$interests = $this->get_interests($list_item['id'], $category_item['id']);
					if (isset($interests['error']))
						return $interests;

					$result[$key]['categories'][$k]['interests'] = $interests;
				}
			}
		}

		return $result;
	}

	/**
	 * Add new member to the list
	 *
	 * @param $email
	 * @param $lists_args
	 *  array( list_id => array ( 0 => array(  ) ) )
	 * @param $send_confirm
	 *
	 * @return array|bool|mixed
	 */
	public function add_to_list($email, $settings, $source_blog_id = 1) {
		$this->init_options($settings);
		$email = sanitize_email($email);

		$lists = array();

		if (isset($settings['subscribe_list'][$source_blog_id]['list_ids'])) {
			$override = isset($settings['subscribe_list'][$source_blog_id]['override'])
					? $settings['subscribe_list'][$source_blog_id]['override'] : 0;

			$lists = ($override == 1)
					? $settings['subscribe_list'][$source_blog_id]['list_ids']
					: $settings['subscribe_list'][MP_DEMO_MAIN_BLOG_ID]['list_ids'];

		} elseif (isset($settings['list_ids'])) {
			// Back compatibility with v1.0.3 and lower
			$lists = $settings['list_ids'];
		}

		$status = ($settings['send_confirm']) ? 'pending' : 'subscribed';
		$request = array();

		if ($email) {
			foreach ($lists as $list_id => $interests) {
				$interests = array_map(function ($item) {
					return $item === "true";
				}, (array)$interests);

				if (isset($interests[0]) && ($interests[0] == false)) {
					$body = json_encode(array(
							'email_address' => $email,
							'status' => $status,
						)
					);
				} else {
					$body = json_encode(array(
							'email_address' => $email,
							'status' => $status,
							'interests' => $interests
						)
					);
				}

				$request[] = wp_remote_post($this->url . "lists/{$list_id}/members/{$this->member_hash($email)}", array(
						'headers' => array(
							'Authorization' => 'Basic ' . base64_encode($this->username . ':' . $this->api_key),
						),
						'body' => $body,
						'method' => 'PUT',
						'sslverify' => false
					)
				);
			}
		}

		$body = array();
		if (is_array($request)) {
			foreach ($request as $key => $val) {
				$body[$key]['response'] = isset($val['response']) ? $val['response'] : __('Unable to add a new member to the list.', 'mp-demo');
				$body[$key]['body'] = isset($val['body']) ? $val['body'] : '';
			}
		}

		return $body;
	}

	/**
	 * @param $request
	 *
	 * @return array('error' => 'message)
	 */
	private function get_errors($request) {
		$content = '';

		if (is_wp_error($request)) {
			$content = $request->get_error_message();
		} else {
			$content = json_decode($request['body'], true);
			$content = (isset($content['detail'])) ? $content['detail'] : __('Income data format error ', 'mp-demo');
		}

		return array('error' => $content);
	}


	private function member_hash($email) {
		return md5(strtolower($email));
	}

}