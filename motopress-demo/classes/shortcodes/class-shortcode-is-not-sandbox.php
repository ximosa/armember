<?php

namespace motopress_demo\classes\shortcodes;

use motopress_demo\classes\models\Sandbox;
use motopress_demo\classes\Shortcodes;

class Shortcode_Is_Not_Sandbox extends Shortcodes {

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function render_shortcode($attrs, $content = null) {
		if (!Sandbox::get_instance()->is_sandbox()) {
			return do_shortcode($content);
		} else {
			return '';
		}
	}

	public function get_options() {
		$params = array(
			'form_id' => 'mce-mp-demo-is-not-sandbox',
			'popup_title' => __('Content is not visible in the sandbox', 'mp-demo'),
			'options' => array(
				0 => array(
					'type' => 'textarea',
					'name' => 'content',
					'label' => 'This content wont be visible in a created sandbox',
					'value' => ''
				)
			)
		);

		return $params;
	}

}
