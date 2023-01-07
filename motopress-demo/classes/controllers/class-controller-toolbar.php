<?php

namespace motopress_demo\classes\controllers;

use motopress_demo\classes\Controller;
use motopress_demo\classes\Shortcodes;

class Controller_Toolbar extends Controller {

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
	public function action_add_row() {
		if (isset($_POST['data'])) {
			wp_send_json_success(mp_demo_render_toolbar_table_row($_POST['data'], false));
		} else {
			wp_send_json_error(array('data' => _('No data', 'mp-demo'), 'status' => false));
		}

	}
}
