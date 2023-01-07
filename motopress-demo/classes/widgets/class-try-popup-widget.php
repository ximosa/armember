<?php

namespace motopress_demo\classes\widgets;

use motopress_demo\classes\models\General_Settings;
use motopress_demo\classes\models\Sandbox;
use motopress_demo\classes\models\Sandbox_Settings;
use motopress_demo\classes\shortcodes\Shortcode_Try_Demo;
use motopress_demo\classes\shortcodes\Shortcode_Try_Demo_Popup;
use motopress_demo\classes\View;

class Try_Demo_Popup_Widget extends \WP_Widget {

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->widget_cssclass = 'mp_demo_popup_widget';
		$this->widget_description = __('A pop-up form for user to create new demo website.', 'mp-demo');
		$this->widget_id = 'mp_demo_try_popup_widget';
		$this->widget_name = __('Demo Registration Popup', 'mp-demo');
		$widget_ops = array(
			'classname' => $this->widget_cssclass,
			'description' => $this->widget_description
		);
		parent::__construct($this->widget_id, $this->widget_name, $widget_ops);
	}

	/**
	 * Get default data
	 *
	 * @param array $instance
	 *
	 * @return string
	 */
	function get_data($instance) {
		if (!empty($instance)) {
			$data = $instance;
		} else {
			//default configuration
			$data = array(
				'widget_title'  => '',
				'content'       => '',
				'wrapper_class' => '',
			);
		}
		return $data;
	}

	/**
	 *
	 * @param array $instance
	 */
	public function form($instance) {
		$args = array();
		$args['data'] = $this->get_data($instance);
		$args['defaults'] = Shortcode_Try_Demo_Popup::get_instance()->get_options();
		array_unshift($args['defaults']['options'], array(
				'type' => 'input',
				'name' => 'widget_title',
				'label' => __('Title', 'mp-demo'),
				'value' => ''
		));
		$args['widget_object'] = $this;
		Shortcode_Try_Demo_Popup::get_instance()->enqueue_scripts();
		View::get_instance()->render_html('admin/widgets/form', $args, true);
	}

	/**
	 * Display widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget($args, $instance) {
		if (Shortcode_Try_Demo_Popup::get_instance()->hide_shortcode()) {
			return '';
		}

		$content = isset($instance['content']) ? $instance['content'] : '';
		$atts = Shortcode_Try_Demo_Popup::get_instance()->shortcode_atts($instance, $content);

		$atts['widget_title'] = apply_filters('widget_title', !empty($instance['widget_title']) ? $instance['widget_title'] : '');
		$atts['before_widget'] = !empty($args['before_widget']) ? $args['before_widget'] : '';
		$atts['after_widget'] =  !empty($args['after_widget']) ? $args['after_widget'] : '';
		$atts['before_title'] =  !empty($args['before_title']) ? $args['before_title'] : '';
		$atts['after_title'] =  !empty($args['after_title']) ? $args['after_title'] : '';

		$atts['captcha_options'] = General_Settings::get_instance()->get_option('recaptcha');

		View::get_instance()->get_template('widgets/try-demo-popup', $atts);
	}

}
