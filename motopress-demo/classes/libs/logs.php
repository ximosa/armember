<?php
/**
 * MP_Demo_Logs
 */
namespace motopress_demo\classes\libs;

class MP_Demo_Logs {

	/**
	 * @var logfile settings
	 */
	var $is_debug = '';
	var $log_file = '';
	var $log_file_url = '';
	var $detail_log_file = '';
	var $detail_log_file_url = '';

	/**
	 * Prepare server
	 */
	public function __construct() {

		if (is_multisite()) {
			switch_to_blog(1);
			$options = get_option('mp_demo_general');
			restore_current_blog();

			$this->is_debug = ($options !== false) ? $options['log'] : 0;
			if ($this->is_debug == 1) {

				$log_dir = trailingslashit(WP_CONTENT_DIR . '/mp-demo-logs');
				$log_url = trailingslashit(WP_CONTENT_URL . '/mp-demo-logs');

				if (!is_dir($log_dir)) {
					mkdir($log_dir);
				}
				$this->log_file = $log_dir . 'mp-cloner.log';
				$this->log_file_url = $log_url . 'mp-cloner.log';
				$this->detail_log_file = $log_dir . 'mp-cloner-' . date("Ymd-His", time()) . '.html';
				$this->detail_log_file_url = $log_url . 'mp-cloner-' . date("Ymd-His", time()) . '.html';

				add_action('admin_notices', array($this, 'check_logfile'));
			}
		}
	}

	/**
	 * Create logfile or display error
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function check_logfile() {
		if (!file_exists($this->log_file) && $this->is_debug == 1) {
			$handle = fopen($this->log_file, 'w') or printf('<div class="error"><p>' . __('Unable to create log file %s. Is its parent directory writable by the server?', 'mp-demo') . '</p></div>', $this->log_file);
			fclose($handle);
		}
	}

	/**
	 * Add message to log
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function log($message) {
		if ($this->is_debug == 1)
			error_log(date_i18n('Y-m-d H:i:s') . " | $message\n", 3, $this->log_file);
	}

	/**
	 * Add a detailed log message
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	function dlog($message) {
		if ($this->is_debug == 1)
			error_log(date_i18n('Y-m-d H:i:s') . " | $message\n", 3, $this->detail_log_file);
	}

}