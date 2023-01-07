<?php
/**
 * Date: 5/25/2016
 * Time: 11:54 AM
 */
namespace motopress_demo\classes\models;

use motopress_demo\classes;
use motopress_demo\classes\Core;

class Sandbox_Settings extends Core {

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function command_exists($command) {
		$whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';

		$process = `$whereIsCommand $command`;

		return (!empty($process)) ? true : false;
	}

	public function render_sandbox_reset_confirmation() {
		$this->get_view()->render_html("sandboxes/reset-confirmation",
				array(),
				true);
	}

	public function render_menu_tab($current_action='') {
		$current_action = ($current_action == -1)? '' : $current_action;
		$action = '';
		if (isset($_REQUEST['action'])&& $_REQUEST['action'] != -1) {
			$action = $_REQUEST['action'];
		} elseif (isset($_REQUEST['action2'])&& $_REQUEST['action2'] != -1) {
			$action = $_REQUEST['action2'];
		} else {
			$action = 'sandboxes';
		}

		$context = $current_action == '' ? $action : $current_action;

		classes\modules\Settings::get_instance()->enqueue_scripts();

		switch ($context) {
			case 'delete':
			case 'bulk-delete':
			case 'sandboxes':
				$this->render_sandboxes();
				break;
			case 'edit':
			case 'bulk-edit':
				$this->render_edititing($context);
//				die();
				break;
			case 'export':
				$this->render_export();
//				die();
				break;
		}

//		die();
	}

	public function render_edititing($context) {
		$settings = $this->get_options();
		$sandbox_id = isset($_REQUEST['sandbox']) ? $_REQUEST['sandbox'] : false;

		if ($sandbox_id) {
			if (is_array($sandbox_id)) {
				$settings['is_bulk_action'] = true;
				$settings['sandboxes'] = $sandbox_id;
			} else {
				$settings['is_bulk_action'] = false;
				$settings['sandbox'] = Sandbox_DAO::get_instance()->get_data('blog_id', $sandbox_id);
			}
			$settings['context'] = $context;

			$this->get_view()->render_html("admin/sandboxes/edit", $settings, true);
		} else {
			$this->render_sandboxes();
		}
	}

	public function render_export() {
		global $wpdb;

		$blog_id = isset($_REQUEST['sandbox']) ? $_REQUEST['sandbox'] : false;
		$data = array();
		$blog_details = array();

		if (!$blog_id) {
			$this->render_sandboxes();
		}

		$blog_details = get_blog_details($blog_id);

		$data['sandbox_data'] = Sandbox_DAO::get_instance()->get_data('blog_id', $blog_id);
		$data['user_data'] =  User_DAO::get_instance()->get_data('user_id', $data['sandbox_data']['user_id']);
		$data['sandbox_tables'] = Sandbox::get_instance()->get_blog_db_table_list($blog_id);
		$data['upload_folder'] = array();
		$data['dist_folder'] = wp_upload_dir();
		$data['dist_folder'] = $data['dist_folder']['basedir'] . '/demo-export';
		$data['upload_folder']['path'] = str_replace( '\\', '/', Sandbox::get_instance()->get_upload_folder($blog_id));;
		$data['upload_folder']['size'] = Sandbox::get_instance()->get_folder_size($data['upload_folder']['path']);
		$data['replacements'] = array(
			array(
					'find' => str_replace(get_home_path(), $blog_details->siteurl . '/', $data['upload_folder']['path']),
					'replace' => '%your_url_here%/wp-content/uploads'
			),
			array(
					'find' => $blog_details->siteurl,
					'replace' => '%your_url_here%'
			),
			array(
					'find' => get_site_url(MP_DEMO_MAIN_BLOG_ID),
					'replace' => '%your_url_here%'
			),
			array(
					'find' => $wpdb->base_prefix . $blog_id .'_',
					'replace' => $wpdb->base_prefix
			)
		);
		if (!$this->command_exists('mysqldump')) {
			add_settings_error(
					'mpDemoExport',
					esc_attr('mailchimp_error'),
					__('Database cannot be exported because mysqldump utility is missing.', 'mp-demo'),
					'error'
			);
		}
		if (!extension_loaded('zip')) {
			add_settings_error(
					'mpDemoExport',
					esc_attr('mailchimp_error'),
					__("Your hosting server doesn't support PHP extention 'zip', please contact your hosting provider for details. Exported file won't be compressed but you can download it via FTP.", 'mp-demo'),
					'notice notice-warning is-dismissible'
			);
		}

		$this->get_view()->render_html("admin/sandboxes/export-form", $data, true);
	}

	public function render_sandboxes() {
		$settings = $this->get_options();
		$sandbox_list = new Sandbox_List();

		$this->get_view()->render_html("admin/sandboxes",
				array('sandbox_list' => $sandbox_list, 'settings' => $settings),
				true);
	}

	public function get_options() {
		$defaults = array(
//			'prevent_clones' => '0',
		);

		switch_to_blog(MP_DEMO_MAIN_BLOG_ID);
		$options = get_option('mp_demo_sandbox');
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

		switch_to_blog(MP_DEMO_MAIN_BLOG_ID);
		update_option('mp_demo_sandbox', $options);
		restore_current_blog();
	}
}