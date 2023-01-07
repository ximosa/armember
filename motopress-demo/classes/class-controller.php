<?php

namespace motopress_demo\classes;

use Motopress_Demo;

/**
 * Controller class
 */
class Controller extends Core {

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Install controllers
	 */
	public function install() {
		// include all core controllers
		Core::include_all(Motopress_Demo::get_plugin_part_path('classes/controllers/'));
	}

	/**
	 * Send json data
	 *
	 * @param type $data
	 */
	public function send_json($data) {
		if (is_array($data) && isset($data['success']) && !$data['success']) {
			wp_send_json_error($data);
		} else {
			wp_send_json_success($data['data']);
		}
	}

	public function send_error($messages) {
		$json = array('errors' => $messages, 'success' => false);

		wp_send_json($json);
		die();
	}

}
