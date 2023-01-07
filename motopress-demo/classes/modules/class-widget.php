<?php
namespace motopress_demo\classes\modules;

use motopress_demo\classes\models\Sandbox;
use motopress_demo\classes\Module;

class Widget extends Module {

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Include all widgets
	 */
	public static function install() {
		self::include_all(\Motopress_Demo::get_plugin_part_path('classes/widgets/'));
 	}

	public function register(){
		if (!Sandbox::get_instance()->is_sandbox()) {
			register_widget('motopress_demo\classes\widgets\Try_Demo_Widget');
			register_widget('motopress_demo\classes\widgets\Try_Demo_Popup_Widget');
		}
	}


	/**
	 * Before widget
	 *
	 * @global array $mprm_widget_args
	 */
	public function before_mprm_widget() {
		global $mprm_widget_args;
		if (!empty($mprm_widget_args)) {
			echo $mprm_widget_args['before_widget'];
		}
	}

	/**
	 * The widget title
	 *
	 * @global array $mprm_widget_args
	 * @global array $mprm_view_args
	 */
	public function the_mprm_widget_title() {
		global $mprm_widget_args, $mprm_view_args;
		if (!empty($mprm_widget_args) && !empty($mprm_view_args['title'])) {
			echo $mprm_widget_args['before_title'] . $mprm_view_args['title'] . $mprm_widget_args['after_title'];
		}
	}

	/**
	 * Afater widget title
	 *
	 * @global array $mprm_widget_args
	 */
	public function after_mprm_widget() {
		global $mprm_widget_args;
		if (!empty($mprm_widget_args)) {
			echo $mprm_widget_args['after_widget'];
		}
	}
}

