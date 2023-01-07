<?php

namespace motopress_demo\classes\modules;

use Motopress_Demo;
use motopress_demo\classes\Module;

class Settings extends Module {

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get settings
	 * $name array('mp_demo_sandbox','mp_demo_mail','mp_demo_general')
	 * $key
	 *
	 * @return type
	 */
	public function get_settings($name, $key = false) {
		$settings = get_option('mprm_settings');

		if (!empty($name)) {
			switch ($name) {
				case 'mp_demo_sandbox' :
					$options = $this->get_model('Sandbox_Settings')->get_options();
					break;
				case 'mp_demo_mail' :
					$options = $this->get_model('Mail_Settings')->get_options();
					break;
				case 'mp_demo_general' :
					$options = $this->get_model('General_Settings')->get_options();
					break;
				default:
					$options = array();
			}

			$settings = (!empty($key) && isset($options[$key])) ? $options[$key] : $options;
		}

		return $settings;
	}

	public function render_tabs() {
		$tabs = apply_filters('mp_demo_sandbox_tabs', array(
				'sandbox' => array(
					'label' => __('Manage Sandboxes', 'mp-demo'),
					'priority' => 0,
					'callback' => array($this->get_model('Sandbox_Settings'), 'render_menu_tab')
				),
			)
		);
		$curTabId = isset($_GET['tab']) ? $_GET['tab'] : 'sandbox';

		$this->get_view()->render_html("admin/menu-tabs", array('tabs' => $tabs, 'curTabId' => $curTabId), true);

	}

	public function render_settings() {
		$tabs = apply_filters('mp_demo_settings_tabs', array(
				'general' => array(
					'label' => __('General', 'mp-demo'),
					'priority' => 0,
					'callback' => array($this->get_model('General_Settings'), 'render_menu_tab')
				),
				'mail' => array(
					'label' => __('Notifications', 'mp-demo'),
					'priority' => 1,
					'callback' => array($this, 'render_subsettings')
				),
				'toolbar' => array(
						'label' => __('Toolbar', 'mp-demo'),
						'priority' => 2,
						'callback' => array($this->get_model('Toolbar_Settings'), 'render_menu_tab')
				),
				'system-info' => array(
						'label' => __('System Info', 'mp-demo'),
						'priority' => 3,
						'callback' => array($this, 'render_system_info')
				),
				'mailchimp' => array(
					'label' => __('MailChimp', 'mp-demo'),
					'priority' => 4,
					'callback' => array($this->get_model('Mailchimp_Settings'), 'render_menu_tab')
				),
			)
		);

		if (Motopress_Demo::has_license() && get_current_blog_id() == MP_DEMO_MAIN_BLOG_ID) {
			$tabs['license'] = array(
					'label' => __('License', 'mp-demo'),
					'priority' => 5,
					'callback' => array($this->get_model('License'), 'render_menu_tab')
			);
		}

		$curTabId = isset($_GET['tab']) ? $_GET['tab'] : 'general';

		$this->enqueue_scripts();

		$this->get_view()->render_html("admin/menu-tabs", array('tabs' => $tabs, 'curTabId' => $curTabId), true);
	}

	public function render_blog_restrictions() {
		$tabs = apply_filters('mp_demo_blog_settings_tabs', array(
				'restrictions' => array(
					'label' => __('Sandbox Restrictions', 'mp-demo'),
					'priority' => 0,
					'callback' => array($this->get_model('Restrictions_Settings'), 'render_menu_tab')
				)
			)
		);
		$curTabId = isset($_GET['tab']) ? $_GET['tab'] : 'restrictions';

		$this->get_view()->render_html("admin/menu-tabs", array('tabs' => $tabs, 'curTabId' => $curTabId), true);
	}

	public function render_sandbox_menu() {
		$tabs = apply_filters('mp_demo_sandbox_settings_tabs', array(
						'reset' => array(
								'label' => __('Reset Demo', 'mp-demo'),
								'priority' => 0,
								'callback' => array($this->get_model('Sandbox_Settings'), 'render_sandbox_reset_confirmation')
						)
				)
		);
		$curTabId = isset($_GET['tab']) ? $_GET['tab'] : 'reset';

		$this->get_view()->render_html("admin/menu-tabs", array('tabs' => $tabs, 'curTabId' => $curTabId), true);
	}

	public function render_subsettings() {
		$tabs = apply_filters('mp_demo_subsettings_tabs', array(
				'to-customer' => array(
					'label' => __('Demo User Notification', 'mp-demo'),
					'priority' => 0,
					'callback' => array($this->get_model('Mail_Settings'), 'render_customer_tab')
				),
				'to-admin' => array(
					'label' => __('Admin Notification', 'mp-demo'),
					'priority' => 1,
					'callback' => array($this->get_model('Mail_Settings'), 'render_admin_tab')
				),
//						'delay' => array(
//								'label' => __('Delay', 'mp-demo'),
//								'priority' => 2,
//								'callback' => array(Mail_Settings::get_instance(), 'render_delay_tab')
//						)
			)
		);
		$cur_tab_id = isset($_GET['subtab']) ? $_GET['subtab'] : 'to-customer';

//		$css_class_sufix = '';

		$this->enqueue_scripts();

		$this->get_view()->render_html("admin/menu-subtabs", array('tabs' => $tabs, 'curTabId' => $cur_tab_id), true);
	}

	public function render_system_info() {

		$mp_settings_options = array(
			'multisite' => array('title' => 'Multisite', 'value' => is_multisite() ? 'Yes' : 'No', 'status' => is_multisite() ? true : false, 'message' => __('This option does not meet minimum requirements', 'mp-demo')),
			'permalinks' => array(
				'title' => 'Sub-directories installation',
				'value' => (SUBDOMAIN_INSTALL == false) ? 'Yes' : 'No',
				'status' => (SUBDOMAIN_INSTALL == false) ? true : false,
				'message' => __('This option does not meet minimum requirements', 'mp-demo')
			),
			'version' => array(
				'title' => 'Motopress Demo Version',
				'value' => \motopress_demo\classes\Core::get_version(),
				'status' => 'OK',
				'message' => __('This option does not meet minimum requirements', 'mp-demo')
			),
			'wp_version' => array(
				'title' => 'WordPress Version',
				'value' => get_bloginfo('version'),
				'status' => (version_compare(get_bloginfo('version'), '3.6', '>=')) ? true : false,
				'message' => __('This option does not meet minimum requirements', 'mp-demo')
			),
			'php_version' => array(
				'title' => 'PHP Version',
				'value' => PHP_VERSION,
				'status' => (version_compare(PHP_VERSION, '5.3', '>=')) ? true : false,
				'message' => __('This option does not meet minimum requirements', 'mp-demo')
			),
			'disable_wp_cron' => array(
				'title' => 'DISABLE_WP_CRON',
				'value' => (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON == true)?  'true': 'false',
				'status' => (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON == true) ? false : true,
				'message' => __("Expired accounts won't be automatically cleaned up. Set this option to false.", 'mp-demo')
			),
		);

		$this->get_view()->render_html("admin/settings/system-info", array('mp_settings_options' => $mp_settings_options), true);
	}

	/**
	 * Save settings
	 */
	public function save_settings() {

		$redirect = false;

		if (isset($_POST['mp_demo_save']) &&
			wp_verify_nonce($_POST['mp_demo_save'], 'mp_demo_save')
		) {
			$curTabId = isset($_POST['tab']) ? $_POST['tab'] : '';
			$query_arg = array();

			$query_arg['page'] = $_GET['page'];
			$query_arg['tab'] = $curTabId;

			switch ($curTabId) {
				case 'sandbox':
					$this->get_model('Sandbox_Settings')->save_options();
					$redirect = true;
					break;
				case 'general':
					$this->get_model('General_Settings')->save_options();
					$redirect = true;
					break;
				case 'mail-customer':
					$redirect = true;
					$query_arg['tab'] = 'mail';
					$query_arg['subtab'] = 'to-customer';
					$this->get_model('Mail_Settings')->save_customer_options();
					break;
				case 'mail-admin':
					$redirect = true;
					$query_arg['tab'] = 'mail';
					$query_arg['subtab'] = 'to-admin';
					$this->get_model('Mail_Settings')->save_admin_options();
					break;
				case 'toolbar':
					$redirect = true;
					$this->get_model('Toolbar_Settings')->save_options();
					break;
				case 'mailchimp':
					$redirect = true;
					$this->get_model('Mailchimp_Settings')->save_options();
					break;
				case 'restrictions':
					$this->get_model('Restrictions_Settings')->save_options();
					$redirect = true;
					break;
				case 'license':
					$this->get_model('License')->save_options();
					$redirect = true;
					break;
				case 'edit-sandbox':
					$this->get_model('Sandbox')->edit();
					$query_arg['action'] = 'edit';
					unset($query_arg['tab']);
					$redirect = true;
					break;
				case 'bulk-edit-sandbox':
					$this->get_model('Sandbox')->edit();
					unset($query_arg['tab']);
					$redirect = true;
					break;
				case 'reset':
					$this->get_model('Sandbox')->reset();
					$query_arg['sandbox-reset'] = 'true';
					$redirect = true;
					break;
				default:
					do_action('admin_mp_demo_tabs_save-' . $curTabId);
			}
		}

		if ($redirect) {
			$query_arg['settings-updated'] = 'true';
			wp_redirect(add_query_arg($query_arg));
			die();
		}

		/**
		 * Show success message
		 */
		if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
			$_GET['settings-updated'] = false;

			$message = (isset($_GET['sandbox-reset']) && ($_GET['sandbox-reset'] == 'true')) ?
					__('Great! Your demo has been reset successfully.', 'mp-demo') :
					__('Settings saved.', 'mp-demo');

			add_settings_error(
				'mpDemoSettings',
				esc_attr('settings_updated'),
				$message,
				'updated'
			);
		}
	}

	public function enqueue_scripts() {
		wp_enqueue_style('jquery-ui-datepicker-style');
		wp_enqueue_script('jquery-ui-datepicker');
		wp_enqueue_script('mp-demo-admin');

		wp_enqueue_style('mp-demo-admin-style');
		wp_enqueue_script('jquery-ui-sortable');
		wp_enqueue_script('mp-demo-admin-settings');
	}

	/**
	 * Remove all settings
	 */
	public function remove_settings() {

		$options = array('mp_demo_general', 'mp_demo_mail', 'mp_demo_mailchimp', 'mp_demo_restrictions', 'mp_demo_sandbox', 'mp_demo_toolbar');

		foreach($options as $option) {
			delete_option($option);
		}
	}

}
