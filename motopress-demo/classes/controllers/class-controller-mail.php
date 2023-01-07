<?php

namespace motopress_demo\classes\controllers;

use motopress_demo\classes\Controller;
use motopress_demo\classes\models\General_Settings;
use motopress_demo\classes\Shortcodes;

class Controller_Mail extends Controller {

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Action sand Creation Form E-mail
	 */
	public function action_send_response() {
		$json = array('errors' => array(), 'success' => '');
		$result = false;
		
		if (empty($_POST) || !wp_verify_nonce($_POST['security'], 'mp-ajax-nonce')) {
			$this->send_error(array(__('Security error!', 'mp-demo')));
		}

		if (isset($_POST['test-email-receiver'])) {
			$to = sanitize_email($_POST['test-email-receiver']);
			$curTabId = isset($_POST['mp_subtab']) ? $_POST['mp_subtab'] : 'to-customer';

			$role = str_replace('to-', '', $curTabId);
			if ($to) {
				$result = Shortcodes::get_instance()->send_mail($to, array(
					'demo_url' => site_url(),
					'login' => $to,
					'password' => 'password',
					'role' => $role,
				), true);
			} else {
				Shortcodes::get_instance()->errors[] = __('Wrong e-mail format', 'mp-demo');
			}

		} else {
			$options = General_Settings::get_instance()->get_options();

			if (isset($_POST['g-recaptcha-response'])){

				if ($_POST['g-recaptcha-response'] === '') {
					$this->send_error(array(__('You cannot avoid passing Captcha.', 'mp-demo')));
				}

				$captcha_response = $this->captcha_verification($options['recaptcha']);

				if (($captcha_response['success'] != true) && !empty($captcha_response['error-codes'])) {
					$recaptcha_error_codes = array(
							'missing-input-secret' => __('The secret parameter is missing.', 'mp-demo'),
							'invalid-input-secret' => __('The secret parameter is invalid or malformed.', 'mp-demo'),
							'missing-input-response' => __('The response parameter is missing.', 'mp-demo'),
							'invalid-input-response' => __('The response parameter is invalid or malformed.', 'mp-demo'),
					);

					$error_messages = array();

					foreach ($captcha_response['error-codes'] as $error_code) {
						$error_messages[] = $recaptcha_error_codes[$error_code];
					}

					$this->send_error($recaptcha_error_codes);
				}
			}

			$result = Shortcodes::get_instance()->send_invintation();
		}

		$json['errors'] = Shortcodes::get_instance()->get_errors();

		if ($result){
			$data = Shortcodes::get_instance()->mp_demo_get_sent_email_data($_POST['mp_email']);
			/*$res = wp_remote_post($data['demo_url'], array(
				'method'=>'POST'
			));
			if(is_wp_error($res)){
				$json['success'] = false;
				$json['errors'] = Shortcodes::get_instance()->get_errors();	
			}
			else{*/
				$json['success'] = true;
				
				$json['data'] = $data;
			//}
		}
		else{
			$json['success'] = false;
			$json['errors'] = Shortcodes::get_instance()->get_errors();

		}
			

		wp_send_json($json);
		die();
	}

	function captcha_verification($options) {

		$response = isset( $_POST['g-recaptcha-response'] ) ? esc_attr( $_POST['g-recaptcha-response'] ) : '';

		$remote_ip = $_SERVER["REMOTE_ADDR"];

		// make a GET request to the Google reCAPTCHA Server
		$request = wp_remote_get(
				'https://www.google.com/recaptcha/api/siteverify?secret=' . $options['secret_key'] . '&response=' . $response . '&remoteip=' . $remote_ip
		);

		// get the request response body
		$response_body = wp_remote_retrieve_body( $request );

		$result = json_decode( $response_body, true );

		return $result;
	}

}
