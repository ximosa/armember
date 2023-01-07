<?php

namespace motopress_demo\classes\models;

use Motopress_Demo;
use motopress_demo\classes\Core;

class Restrictions_Settings extends Core {

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function render_menu_tab() {
		global $menu, $submenu;


		$settings = $this->get_options(get_current_blog_id());
		$sub_menu = $this->decode_special_chars($submenu);
//		$plugins_data = $this->get_all_plugins_data();
		$plugins_data = false;

		wp_enqueue_script('mp-demo-admin-settings');

		$this->get_view()->render_html(
			"admin/menu-restrictions",
			array('menu' => $menu, 'sub_menu' => $sub_menu, 'settings' => $settings, 'plugins_data' => $plugins_data),
			true
		);
	}

	public function get_options($blog_id = 1) {
		$defaults = array(
			'black_list' => array(),
			'parent_pages' => array(),
			'child_pages' => array(),
			'child_disabled_pages' => array(),
			'plugins_data' => array(),
		);

		switch_to_blog($blog_id);
		$options = get_option('mp_demo_restrictions');
		restore_current_blog();
		$options = ($options === false) ? array() : $options;

		$options = array_merge($defaults, $options);

		// backward compatibility, remove in future version
		if (isset($options['child_pages'][0]) && is_array($options['child_pages'][0])) {
			$pages = array();

			foreach ($options['child_pages'] as $child_page) {
				$pages[] = mp_demo_generate_submenu_uri($child_page['parent'], $child_page['child']);
			}
			$options['child_pages'] = $pages;
		}

		return $options;
	}

	public function get_option($key, $blog_id = 1) {
		$options = $this->get_options($blog_id);
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

		if (isset($options['child_pages'])) {

			$child_pages = array();
			$child_disabled_pages = array();

			foreach ($options['child_pages'] as $child_page) {
				if ($child_page['status'] == 1) {
					$child_pages[] = $child_page['slug'];
				} else {
					$child_disabled_pages[] = $child_page['slug'];
				}
			}

			$options['child_pages'] = $child_pages;
			$options['child_disabled_pages'] = $child_disabled_pages;
		}

		if (isset($options['black_list']) && is_string($options['black_list'])) {
			$options['black_list'] = explode(PHP_EOL, $options['black_list']);
		}

		update_option('mp_demo_restrictions', $options);
	}

	/**
	 * All not network plugins
	 *
	 * @return array|bool
	 */
	public function get_all_plugins_data() {
		$plugins_data = (Motopress_Demo::hide_plugins()) ? false : get_plugins();

		if ($plugins_data) {
			$plugins_data = $this->array_filter($plugins_data, 'Network', true);
		}

		return $plugins_data;
	}

	/**
	 * Get Sandbox plugins
	 *
	 * @return array|bool
	 */
	public function get_plugins_data($plugins) {

		if (!Sandbox::get_instance()->is_sandbox() || Motopress_Demo::hide_plugins()) {
			return $plugins;
		}

		$plugins_data = get_plugins();
		$sandbox_plugins = false;

		if ($plugins_data) {
			$options = $this->get_options(
					Sandbox_DAO::get_instance()->get_blog_source(get_current_blog_id())
			);
			$options = $options['plugins_data'];
			$sandbox_plugins = array();

			foreach ($plugins_data as $key => $value) {
				if (in_array($key, $options)) {
					$sandbox_plugins[$key] = $value;
				}
			}
		}

		return $sandbox_plugins;
	}
}