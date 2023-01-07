<?php

namespace motopress_demo\classes;

use Motopress_Demo;

/**
 * View class
 */
class SiteError {

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Init error output
	 */
	public static function install_hooks() {
		add_action('network_admin_menu', array(SiteError::get_instance(), 'network_admin_menu'));
		add_action('admin_menu', array(SiteError::get_instance(), 'admin_menu'));
	}

	public function admin_menu() {
		add_menu_page(
				__('Plugin requirements', 'mp-demo'),
				__('Demo', 'mp-demo'),
				'manage_options',
				'mp-demo',
				array($this, 'render_site_errors'),
				'dashicons-networking',
				'87.11'
		);
	}

	public function network_admin_menu() {
		add_menu_page(
				__('Plugin requirements', 'mp-demo'),
				__('Demo', 'mp-demo'),
				'manage_network_options',
				'mp-demo',
				array($this, 'render_multisite_errors'),
				'dashicons-networking',
				'87.11'
		);
	}

	public function render_site_errors() {
		$data = array(
				'multisite' => is_multisite() ? true : false,
				'subdomains' => defined('SUBDOMAIN_INSTALL') && (SUBDOMAIN_INSTALL == true) ? true : false
		);

		$this->render_html('admin/errors/multisite', $data);
	}

	public function render_multisite_errors() {
		$data = array(
				'multisite' => is_multisite() ? true : false,
				'subdomains' => defined('SUBDOMAIN_INSTALL') && (SUBDOMAIN_INSTALL == true) ? true : false
		);

		$this->render_html('admin/errors/multisite', $data);
	}

	/**
	 * Render html
	 */
	public function render_html($template, $data = null) {
		$includeFile = Motopress_Demo::get_plugin_part_path('templates/') . $template . '.php';
		ob_start();
		if (is_array($data)) {
			extract($data);
		}

		include($includeFile);
		$out = ob_get_clean();

		echo $out;
	}
}
