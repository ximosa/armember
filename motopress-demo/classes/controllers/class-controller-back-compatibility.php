<?php

namespace motopress_demo\classes\controllers;

use motopress_demo\classes\Controller;
use motopress_demo\classes\modules\Back_Compatibility;
use motopress_demo\classes\Shortcodes;

class Controller_Back_Compatibility extends Controller {

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
	public function action_trigger_upgrades() {
		$upgrated = Back_Compatibility::get_instance()->trigger_upgrades();

		if ($upgrated) {
			wp_send_json_success(array('upgrated' => 1));
		} else {
			wp_send_json_error(array('data' => _('No data', 'mp-demo'), 'status' => false));
		}
	}
}
