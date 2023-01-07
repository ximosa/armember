<?php

namespace motopress_demo\classes\models;

use motopress_demo\classes;
use motopress_demo\classes\Core;

class Toolbar_Settings extends Core {

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function render_menu_tab() {
		$settings = $this->get_options();
		wp_enqueue_media();
		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script('wp-color-picker');
		wp_enqueue_script('jquery-ui-sortable');

		wp_enqueue_script('mp-demo-admin-settings');

		$this->get_view()->render_html("admin/settings/toolbar", array('settings' => $settings), true);
	}

	public function get_options() {
		$defaults = array(
			'show_toolbar' => '0',
			'unpermitted' => '1',
			'select' => '',
			'btn_text' => '',
			'btn_url' => '',
			'btn_class' => '',
			'logo' => '',
			'theme' => 'dark-theme',
			'background' => '',
		);

		switch_to_blog(MP_DEMO_MAIN_BLOG_ID);
		$options = get_option('mp_demo_toolbar');
		restore_current_blog();
		$options = ($options === false) ? array() : $options;
		$options = array_merge($defaults, $options);

		return $options;
	}

	public function get_option($key) {
		$options = $this->get_options();
		return isset($options[$key]) ? $options[$key] : '';
	}

	/**
	 * Save options
	 */
	public function save_options() {

		if (!isset($_POST['settings'])) {
			return;
		}

		$options = $_POST['settings'];

		if (isset($options['unpermitted'])) {
			$list = explode(',', $options['unpermitted']);
			$list = array_map(function ($item) {
				$item = trim($item);
				return intval($item);
			}, $list);

			$list = array_filter($list, function ($item) {
				return $item > 0;
			});

			$options['unpermitted'] = $list;
		}

		switch_to_blog(MP_DEMO_MAIN_BLOG_ID);
		update_option('mp_demo_toolbar', $options);
		restore_current_blog();
	}

}