<?php

namespace motopress_demo\classes;

use EDD_MP_Demo_Plugin_Updater;
use Motopress_Demo;
use motopress_demo\classes\controllers\Controller_Mail;
use motopress_demo\classes\models\General_Settings;
use motopress_demo\classes\models\Restrictions_Settings;
use motopress_demo\classes\models\Sandbox;
use motopress_demo\classes\modules\Back_Compatibility;
use motopress_demo\classes\modules\Menu;
use motopress_demo\classes\modules\Settings;
use motopress_demo\classes\modules\Statistics;
use motopress_demo\classes\modules\Toolbar;
use motopress_demo\classes\modules\Widget;
use motopress_demo\classes\shortcodes\Shortcode_Is_Not_Sandbox;
use motopress_demo\classes\shortcodes\Shortcode_Is_Sandbox;
use motopress_demo\classes\shortcodes\Shortcode_Try_Demo;
use motopress_demo\classes\shortcodes\Shortcode_Try_Demo_Popup;

class Hooks extends Core {

	protected static $instance;

	public static function get_instance() {

		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Init all hooks in projects
	 */
	public static function install_hooks() {

		add_action('init', array(self::get_instance(), 'init'));
		add_action('init', 'mp_demo_generate_sysinfo_download');
		add_action('init', array(Shortcodes::get_instance(), 'login_listen'));
//
		add_action('admin_init', array(self::get_instance(), 'admin_init'));
		add_action('network_admin_menu', array(self::get_instance(), 'network_admin_menu'));
		add_action('admin_menu', array(self::get_instance(), 'admin_menu'));

		add_action('admin_init', array(Settings::get_instance(), 'save_settings'));

		// widgets init
		add_action('widgets_init', array(Widget::get_instance(), 'register'));

		add_action('wp_ajax_nopriv_route_url', array(Core::get_instance(), "wp_ajax_route_url"));
		add_action('wp_ajax_route_url', array(Core::get_instance(), "wp_ajax_route_url"));

		add_action('mp_demo_purge_event', array(Sandbox::get_instance(), 'cron_event'));

		// Toolbar hooks
		if (Toolbar::get_instance()->show_toolbar()) {
			add_filter('template_include', array(Toolbar::get_instance(), 'template_include'), 1);
		}

		// Plugins
//		add_filter('all_plugins', array(Restrictions_Settings::get_instance(), 'get_plugins_data'));

		// Frontend hooks
		add_action('mp_demo_toolbar_head', array(Toolbar::get_instance(), 'toolbar_head'));
		add_action('mp_demo_toolbar_footer', array(Toolbar::get_instance(), 'toolbar_footer'));

		add_filter('set-screen-option', array(self::get_instance(), 'set_screen_option'), 10, 3);

		add_action('init', array(Sandbox::get_instance(), 'deleted_blog_check'));

		/*
		 * Commute WP blogs with Sandboxes
		 */
		add_action('archive_blog', array(Sandbox::get_instance(), 'wp_action_archive_blog'));
		add_action('unarchive_blog', array(Sandbox::get_instance(), 'wp_action_unarchive_blog'));
		add_action('deactivate_blog', array(Sandbox::get_instance(), 'wp_action_deactivate_blog'));
		add_action('activate_blog', array(Sandbox::get_instance(), 'wp_action_activate_blog'));
		add_action('delete_blog', array(Sandbox::get_instance(), 'wp_action_delete_blog'));

		/**
		 * Back Compatibility actions
		 */
		add_action('admin_notices', array(Back_Compatibility::get_instance(), 'show_upgrade_notices'));
		add_action('network_admin_notices', array(Back_Compatibility::get_instance(), 'show_upgrade_notices'));
		add_action('mp_demo_trigger_upgrades', array(Back_Compatibility::get_instance(), 'trigger_upgrades'));

		// Menu My Sites
		add_action('admin_bar_menu', array(self::get_instance(), 'update_admin_bar_menu'), 999);

		/**
		 * Sandbox reset script
		 */

		add_action('wp_enqueue_scripts', array(Core::get_instance(), "wp_enqueue_reset_scripts"));
		add_action('admin_enqueue_scripts', array(Core::get_instance(), "wp_enqueue_reset_scripts"));
	}

	public function update_admin_bar_menu($wp_admin_bar) {

		$this->remove_sandboxes_nodes($wp_admin_bar);

		$this->add_menu_bar_reset($wp_admin_bar);
	}

	public function remove_sandboxes_nodes($wp_admin_bar) {
		$sites_parent_node = 'my-sites-list';
		$sites_nodes = $wp_admin_bar->get_nodes();

		foreach ($sites_nodes as $bar_node => $bar_node_value) {

			if ($bar_node_value->parent == $sites_parent_node) {
				$matches =  preg_match('/^blog-\d+$/', $bar_node);

				if ($matches == 1) {
					$blog_id =  preg_replace('/blog-/', '', $bar_node);

					if (Sandbox::get_instance()->is_sandbox($blog_id)) {
						$wp_admin_bar->remove_node($bar_node);
					}
				}
			}
		}
	}


	/**
	 * Add an item to the menu bar that allows them to reset the sandbox
	 */
	public function add_menu_bar_reset($wp_admin_bar) {

		if (Sandbox::get_instance()->is_sandbox() && (General_Settings::get_instance()->get_option('enable_reset') == 1)) {

			$wp_admin_bar->add_menu( array(
					'id'   => 'mp-reset-demo',
					'meta' => array(
						'onclick' => 'mpConfirmResetDemo()'
					),
					'title' => __( 'Reset Demo', 'mp-demo' ),
					'href' => '#'));
//					'href' => admin_url('admin.php?page=mp-demo-sandbox')));
		}
	}

	/**
	 * Hooks for admin panel
	 */
	public function admin_init() {

		if (Motopress_Demo::has_license()) {
			new EDD_MP_Demo_Plugin_Updater(
				Motopress_Demo::get_plugin_store_url(),
				Motopress_Demo::get_plugin_file(),
				array(
					'version' => Core::get_version(), // current version number
					'license' => get_option('edd_mp_demo_license_key'), // license key (used get_option above to retrieve from DB)
					'item_name' => Motopress_Demo::get_plugin_full_name(), // name of this plugin
					'author' => Motopress_Demo::get_plugin_author() // author of this plugin
				)
			);
		}

//		 ajax redirect
//		add_action('wp_ajax_route_url', array(Core::get_instance(), "wp_ajax_route_url"));

		if (isset($_GET['mp-demo-action'])) {
			do_action( 'mp_demo_' . $_GET['mp-demo-action'], $_GET );
		}

		// add btn to TinyMCE
		if (Motopress_Demo::is_admin_user()) {

			foreach (array('post.php', 'post-new.php') as $hook)
				add_action("admin_head-$hook", array($this, 'admin_head_js_vars'));

			add_filter('mce_external_plugins', array($this, 'mce_external_plugins'));
			add_filter('mce_buttons', array($this, 'mce_buttons'));

			add_action('in_admin_footer', array($this, 'output_popups'));
		}

	}

	/**
	 * Init hook
	 */
	public function init() {
		Capabilities::get_instance()->init();
		//shortcodes
		add_shortcode('try_demo', array(Shortcode_Try_Demo::get_instance(), 'render_shortcode'));
		add_shortcode('try_demo_popup', array(Shortcode_Try_Demo_Popup::get_instance(), 'render_shortcode'));
		add_shortcode('is_sandbox', array(Shortcode_Is_Sandbox::get_instance(), 'render_shortcode'));
		add_shortcode('is_not_sandbox', array(Shortcode_Is_Not_Sandbox::get_instance(), 'render_shortcode'));
		//add media in frontend and Backend WP
		add_action('wp_enqueue_scripts', array(Core::get_instance(), "wp_enqueue_scripts"));
		add_action('admin_enqueue_scripts', array(Core::get_instance(), "wp_admin_enqueue_scripts"));
		add_filter('script_loader_tag', array(Core::get_instance(), "script_loader_tag"), 10, 2);
	}

	/**
	 * Register plugin's menu
	 * Network Admin Menu
	 */
	public function add_menu() {
		global $submenu;
		$params = array(
			'title' => __('Demo', 'mp-demo'),
			'icon_url' => 'dashicons-networking',
			'capability' => 'manage_network_options',
			'function' => array(Settings::get_instance(), 'render_tabs'),
			'menu_slug' => 'mp-demo',
			'position' => '87.11',
		);

		$hook = Menu::add_menu_page($params);

		add_action("load-$hook", array($this, 'screen_option'));

		// Submenu
		$submenu['mp-demo'] = isset($submenu['mp-demo']) ? $submenu['mp-demo'] : array();
		$submenu['mp-demo'][5] = array( __('Sandboxes', 'mp-demo'), 'manage_network_options', 'mp-demo',__('Demo', 'mp-demo'));

		$params = array(
			'title' => __('Statistics', 'mp-demo'),
			'capability' => 'manage_network_options',
			'function' => array(Statistics::get_instance(), 'render_tabs'),
			'parent_slug' => 'mp-demo',
			'menu_slug' => 'mp-demo-statistics',
		);
		Menu::add_submenu_page($params);

		$params['title'] = __('Settings', 'mp-demo');
		$params['function'] = array(Settings::get_instance(), 'render_settings');
		$params['menu_slug'] = 'mp-demo-settings';
		$params['capability'] = 'manage_network_options';
		Menu::add_submenu_page($params);

	}

	public static function set_screen($status, $option, $value) {
		return $value;
	}

	/**
	 * Add sandboxes list to Demo Menu at network admin page
	 */
	public function screen_option() {
		$option = 'per_page';
		$args = array(
			'label' => __('Sandboxes', 'mp-demo'),
			'default' => 10,
			'option' => 'sandboxes_per_page'
		);

		add_screen_option($option, $args);
	}


	public static function network_admin_menu() {
		global $submenu;

		if (is_network_admin()) {
			self::get_instance()->add_menu();
			$submenu ['mp-demo'][] = array(__('Restrictions', 'mp-demo'), 'manage_network_options', admin_url('admin.php?page=mp-demo-restrictions'));
		}

		add_filter('set-screen-option', array(self::get_instance(), 'set_screen'), 10, 3);
	}

	public static function admin_menu() {
		self::get_instance()->sandbox_admin_menu();

		self::get_instance()->administrator_admin_menu();

	}

	public function administrator_admin_menu() {
		global $submenu, $menu;

		if (Sandbox::get_instance()->is_sandbox()) {
			return $menu;
		}

		$network = Core::get_instance()->get_last_subfolder(network_admin_url(), '/network/');

		$params = array(
				'title' => __('Demo', 'mp-demo'),
				'icon_url' => 'dashicons-networking',
				'capability' => 'manage_network_options',
				'function' => array(Settings::get_instance(), 'render_blog_restrictions'),
				'menu_slug' => 'mp-demo-restrictions',
				'position' => '87.21',
		);
		Menu::add_menu_page($params);

		$submenu['mp-demo'] = isset($submenu['mp-demo']) ? $submenu['mp-demo'] : array();
		$submenu['mp-demo-restrictions'][5] = array(__('Restrictions', 'mp-demo'), 'manage_network_options', 'mp-demo-restrictions', __('Restrictions', 'mp-demo'));

		// Submenu
		$params['title'] = __('Sandboxes', 'mp-demo');
		$params['parent_slug'] = 'mp-demo-restrictions';
		$params['menu_slug'] = $network . 'admin.php?page=mp-demo';
		$params['function'] = false;
		Menu::add_submenu_page($params);

		$params['title'] = __('Statistics', 'mp-demo');
		$params['menu_slug'] = $network . 'admin.php?page=mp-demo-statistics';
		$params['function'] = false;
		Menu::add_submenu_page($params);

		$params['title'] = __('Settings', 'mp-demo');
		$params['menu_slug'] = $network . 'admin.php?page=mp-demo-settings';
		$params['function'] = false;
		Menu::add_submenu_page($params);

		return $menu;
	}

	public function sandbox_admin_menu() {

		if (!Sandbox::get_instance()->is_sandbox()) {
			return;
		}

		if (General_Settings::get_instance()->get_option('enable_reset') != 1){
			return;
		}

		/*$params = array(
				'title' => __('Reset Demo', 'mp-demo'),
				'parent_slug' => null,
				'capability' => 'read',
				'function' => array(Settings::get_instance(), 'render_sandbox_menu'),
				'menu_slug' => 'mp-demo-sandbox',
		);
		Menu::add_submenu_page($params);
		
		$params = array(
				'title' => __('Menu title', 'mp-demo'),
				'subtitle' => __('Menu sub title', 'mp-demo'),
				'capability' => 'read',
				'menu_slug' => 'menu-slug',
				'function' => array(Settings::get_instance(), 'render_sandbox_menu')
		);
		Menu::add_menu_page($params);*/
	}

	/**
	 * Add Shortcode-building button in MCE editor
	 *
	 * @param $buttons
	 *
	 * @return mixed
	 */
	public function mce_buttons($buttons) {
		array_push($buttons, 'addMPDemoButton');
		return $buttons;
	}


	/**
	 * Connect js for MCE editor
	 *
	 * @param $plugin_array
	 *
	 * @return mixed
	 */
	public function mce_external_plugins($plugin_array) {
		$path = Motopress_Demo::get_plugin_url('assets/js/shortcodes/try-demo.js');
		$plugin_array['mp_demo'] = $path;
		return $plugin_array;
	}


	/**
	 * Localize TinyMCE btn Script
	 */
	public function admin_head_js_vars() {
		$img = 'wp-menu-image dashicons-before dashicons-networking';
		?>
		<!-- TinyMCE Shortcode Plugin -->
		<script type='text/javascript'>
			var MP_Demo_MCE_Ajax = {
				'image': '<?php echo $img; ?>',
				'mce_menu_title': '<?php _e('Demo Shortcodes', 'mp-demo'); ?>',
				'mce_title_try': '<?php _e('Try Demo Form', 'mp-demo'); ?>',
				'mce_title_popup': '<?php _e('Try Demo Popup', 'mp-demo'); ?>',
				'mce_title_created': '<?php _e('Is Sandbox', 'mp-demo'); ?>',
				'mce_title_not_sandbox': '<?php _e('Is Not Sandbox', 'mp-demo'); ?>',
				'save_btn': '<?php _e('Insert', 'mp-demo'); ?>',
				'cancel_btn': '<?php _e('Cancel', 'mp-demo'); ?>',
			};
		</script>
		<?php
	}

	/**
	 * Output shortcodes builder popups
	 */
	public function output_popups() {
		if (is_admin()) {
			wp_enqueue_script('magnific-popup');
			wp_enqueue_style('magnific-popup-style');
			wp_enqueue_style('mp-demo-admin-style');

			$options = array(
				'try' => Shortcode_Try_Demo::get_instance()->get_options(),
				'popup' => Shortcode_Try_Demo_Popup::get_instance()->get_options(),
				'created' => Shortcode_Is_Sandbox::get_instance()->get_options(),
				'not_sandbox' => Shortcode_Is_Not_Sandbox::get_instance()->get_options(),
			);

			foreach ($options as $option) {
				$this->get_view()->render_html('admin/shortcodes/popup', array('params' => $option), true);
			}
		}

	}

	public function set_screen_option($status, $option, $value) {
		return $value;
	}

}
