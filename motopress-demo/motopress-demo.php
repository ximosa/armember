<?php

/*

* Plugin Name: Demo Builder for any WordPress Product

* Plugin URI: http://www.getmotopress.com

* Description: Provide users with a personal demo of your WordPress products. Enhance your marketing possibilities collecting users' email addresses with MailChimp.

* Version: 1.4.0

* Author: MotoPress

* Author URI: http://www.getmotopress.com

* License: GPLv2 or later

* Text Domain: mp-demo

* Domain Path: /languages

* Network: True

*/



// Exit if accessed directly

if (!defined('ABSPATH'))

	exit;



use motopress_demo\classes;



register_activation_hook(__FILE__, array(Motopress_Demo::get_instance(), 'on_activation'));

register_deactivation_hook(__FILE__, array('Motopress_Demo', 'on_deactivation'));

register_uninstall_hook(__FILE__, array('Motopress_Demo', 'on_uninstall'));

add_action('plugins_loaded', array('Motopress_Demo', 'get_instance'));



class Motopress_Demo {



	protected static $instance;



	public static function get_instance() {

		if (null === self::$instance) {

			self::$instance = new self();

		}

		return self::$instance;

	}



	/**

	 * On activation plugin

	 * Upon activation, setup super admin

	 */

	public static function on_activation() {



		if (Motopress_Demo::has_license() && class_exists('classes\models\License')) {

			$autoLicenseKey = apply_filters('mp_demo_auto_license_key', false);

			if ($autoLicenseKey) {

				classes\models\License::set_and_activate_license_key($autoLicenseKey);

			}

		}



		if (!wp_next_scheduled('mp_demo_purge_event')) {

			wp_schedule_event(time(), 'hourly', 'mp_demo_purge_event');

		}



		Motopress_Demo::create_tables();

	}



	/**

	 * Create / Update tables

	 */

	public static function create_tables() {

		global $wpdb;



		$tables_names = Motopress_Demo::get_tables_names();



		if (Motopress_Demo::is_suitable_site()) {

			$admin = motopress_demo\classes\Core::get_instance()->get_last_subfolder(admin_url(), '/wp-admin/');

			require_once(MP_DEMO_ABSPATH . $admin . 'includes/upgrade.php');

		} else {

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		}



		//$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset} COLLATE {$wpdb->collate}";

		$charset_collate = $wpdb->get_charset_collate();



		$sql_users = "CREATE TABLE " . $tables_names['users'] . " (

			user_id bigint(20) NOT NULL AUTO_INCREMENT,

			email varchar(255) NOT NULL,

			is_valid tinyint(1) NOT NULL,

			first_name varchar(255),

			last_name varchar(255),

			phone varchar(255),

			wp_user_id varchar(255),

			country varchar(255),

			password varchar(255) NOT NULL,

		  PRIMARY KEY  (user_id)

		)  {$charset_collate};";



		dbDelta($sql_users);



		$sql_sandboxes = "CREATE TABLE " . $tables_names['sandboxes'] . " (

			sandbox_id bigint(20) NOT NULL AUTO_INCREMENT,

			blog_id bigint(20),

			source_blog_id bigint(20) NOT NULL,

			user_id bigint(20) NOT NULL,

			status varchar(255) NOT NULL,

			secret varchar(255) NOT NULL,

			site_url varchar(255),

			is_lifetime tinyint(1),

			creation_date datetime DEFAULT '0000-00-00 00:00:00',

			activation_date datetime DEFAULT '0000-00-00 00:00:00',

			expiration_date datetime DEFAULT '0000-00-00 00:00:00',

		  PRIMARY KEY  (sandbox_id)

		)  {$charset_collate};";



		dbDelta($sql_sandboxes);

	}



	public static function setup_constants() {



		/** Absolute path to the WordPress directory. */

		if (!defined('MP_DEMO_ABSPATH'))

			define('MP_DEMO_ABSPATH', rtrim(ABSPATH, '/'));

		if (!defined('MP_DEMO_STATUS_ACTIVE'))

			define('MP_DEMO_STATUS_ACTIVE', 'active');

		if (!defined('MP_DEMO_STATUS_PENDING'))

			define('MP_DEMO_STATUS_PENDING', 'pending');

		if (!defined('MP_DEMO_STATUS_ARCHIVED'))

			define('MP_DEMO_STATUS_ARCHIVED', 'archived');

		if (!defined('MP_DEMO_STATUS_DEACTIVATED'))

			define('MP_DEMO_STATUS_DEACTIVATED', 'deactivated');

		if (!defined('MP_DEMO_STATUS_DELETED'))

			define('MP_DEMO_STATUS_DELETED', 'deleted');

		if (!defined('MP_DEMO_ACTION_DELETE'))

			define('MP_DEMO_ACTION_DELETE', 'delete');

		if (!defined('MP_DEMO_ACTION_ARCHIVE'))

			define('MP_DEMO_ACTION_ARCHIVE', 'archive');

		if (!defined('MP_DEMO_ACTION_DEACTIVATE'))

			define('MP_DEMO_ACTION_DEACTIVATE', 'deactivate');

		if (!defined('MP_DEMO_MAIN_BLOG_ID'))

			define('MP_DEMO_MAIN_BLOG_ID', 1);

		if (!defined('MP_DEMO_EMPTY_DATE'))

			define('MP_DEMO_EMPTY_DATE', '0000-00-00 00:00:00');

	}



	/**

	 * On deactivation plugin

	 */

	public static function on_deactivation() {

		wp_clear_scheduled_hook('mp_demo_purge_event');

	}



	/**

	 * On uninstall

	 */

	public static function on_uninstall() {

		if (Motopress_Demo::is_suitable_site()) {

			classes\modules\Settings::get_instance()->remove_settings();

		}

	}



	/**

	 * Include classes into plugin

	 */

	static function include_all() {

		$plugin_dir = Motopress_Demo::get_plugin_dir();



		/**

		 * Include Gump Validator

		 */

		require_once $plugin_dir . 'classes/libs/gump.class.php';

		/**

		 * Include MP Logger

		 */

		require_once $plugin_dir . 'classes/libs/logs.php';

		/**

		 * Include Plugin Updater

		 */

		require_once $plugin_dir . 'classes/libs/EDD_MP_Demo_Plugin_Updater.php';

		/**

		 * Include classes

		 */

		require_once $plugin_dir . 'classes/class-capability.php';



		require_once $plugin_dir . 'classes/class-state-factory.php';



		require_once $plugin_dir . 'classes/class-core.php';



		require_once $plugin_dir . 'classes/class-model.php';



		require_once $plugin_dir . 'classes/class-controller.php';



		require_once $plugin_dir . 'classes/class-preprocessor.php';



		require_once $plugin_dir . 'classes/class-module.php';



		require_once $plugin_dir . 'classes/class-view.php';



		require_once $plugin_dir . 'classes/class-hooks.php';



		require_once $plugin_dir . 'classes/class-shortcodes.php';



		require_once $plugin_dir . 'templates-functions/admin.php';

	}





	/**

	 * Include classes into plugin

	 */

	static function include_error_info_page() {



		require_once Motopress_Demo::get_plugin_dir() . 'classes/class-site-error.php';



		classes\SiteError::install_hooks();

	}



	/**

	 * @return  Plugin Short Name

	 */

	public static function get_plugin_name() {

		return 'motopress-demo';

	}



	/**

	 * @return  Plugin Name

	 */

	public static function get_plugin_full_name() {

		$plugin_data = get_plugin_data(Motopress_Demo::get_plugin_file());



		return $plugin_data['Name'];

	}



	/**

	 * @return  Plugin STORE_URL

	 */

	public static function get_plugin_store_url() {

		$plugin_data = get_plugin_data(Motopress_Demo::get_plugin_file());



		return $plugin_data['PluginURI'];

	}



	/**

	 * @return  Plugin Author

	 */

	public static function get_plugin_author() {

		$plugin_data = get_plugin_data(Motopress_Demo::get_plugin_file(), false, false);



		return $plugin_data['Author'];

	}



	/**

	 * @return  table mpe_events_data Name

	 */

//	public static function get_statistics_table_name()

	public static function get_tables_names() {

		global $wpdb;

		if (Motopress_Demo::is_suitable_site()) {

			switch_to_blog(MP_DEMO_MAIN_BLOG_ID);

		}



		$name = array(

			'users' =>$wpdb->prefix . 'mp_demo_users',

			'sandboxes' => $wpdb->prefix . 'mp_demo_sandboxes'

		);



		if (Motopress_Demo::is_suitable_site()) {

			restore_current_blog();

		}



		return $name;

	}



	/**

	 * @return Plugin File

	 */

	public static function get_plugin_file() {

		global $wp_version, $network_plugin;

		if (version_compare($wp_version, '3.9', '<') && isset($network_plugin)) {

			$pluginFile = $network_plugin;

		} else {

			$pluginFile = __FILE__;

		}

		return $pluginFile;

	}



	/**

	 * @return Plugin Dir

	 */

	public static function get_plugin_dir() {

		$file = Motopress_Demo::get_plugin_file();

		return trailingslashit(plugin_dir_path($file));

	}



	/**

	 * @return Plugin Url

	 */

	public static function get_plugin_url($path = false, $sync = '') {

		$pluginFile = Motopress_Demo::get_plugin_file();

		$dirName = basename(dirname($pluginFile));

		return plugin_dir_url($dirName . '/' . basename($pluginFile)) . '' . $path . $sync;

	}



	/**

	 * Get plugin part path

	 *

	 * @param string $part

	 *

	 * @return string

	 */

	public static function get_plugin_part_path($part = '') {

		return Motopress_Demo::get_plugin_dir() . $part;

	}



	/**

	 * Retrieve relative to theme root path to templates.

	 *

	 * @return string

	 */

	public static function get_template_path(){

		return apply_filters( 'mp_demo_template_path', 'motopress-demo/' );

	}



	/**

	 * Retrieve relative to plugin root path to templates.

	 *

	 * @return string

	 */

	public static function get_templates_path(){

		return self::get_plugin_dir() . 'templates/';

	}



	/**

	 * Check to see if the current user is our admin user

	 *

	 * @return bool

	 */

	public static function is_admin_user() {



		return current_user_can('manage_network_options');

	}



	/**

	 * Check site settings

	 * @return bool

	 */

	public static function is_suitable_site() {

		$is_suitable_site = is_multisite() && defined('SUBDOMAIN_INSTALL') && (SUBDOMAIN_INSTALL == false);

		return apply_filters('mp_demo_is_suitable_site', $is_suitable_site);

	}



	/**

	 * Check if hide Plugins from sandbox and settings

	 * @return bool

	 */

	public static function hide_plugins() {

		$menu_perms = get_site_option('menu_items');

		$hide_plugins = (isset($menu_perms['plugins']) && $menu_perms['plugins'] == '1') ? false : true;



		return apply_filters('mp_demo_hide_plugins', $hide_plugins, $menu_perms);

	}



	/**

	 * If License is required - return true

	 * @return bool

	 */

	public static function has_license() {

		return false;

	}





	public function __construct() {

		Motopress_Demo::setup_constants();



		if (Motopress_Demo::is_suitable_site()) {

			$this->include_all();

			motopress_demo\classes\Core::get_instance()->init_plugin('motopress_demo');

		} else {

			Motopress_Demo::include_error_info_page();

		}

	}



}

