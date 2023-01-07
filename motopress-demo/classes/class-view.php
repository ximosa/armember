<?php

namespace motopress_demo\classes;

use Motopress_Demo;

/**
 * View class
 */
class View {

	protected static $instance;
	protected $template_path;
	protected $templates_path;
	protected $prefix = 'mp_demo';
	private $data;

	public function __construct() {
		$this->template_path = Motopress_Demo::get_template_path();
		$this->templates_path = Motopress_Demo::get_templates_path();
	}

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Render template
	 *
	 * @param null $template
	 * @param null $data
	 */
	function render_template($template = null, $data = null) {
		$this->template = $template;
		if (is_array($data)) {
			extract($data);
		}
		$this->data = $data;
		include_once $this->templates_path . 'index.php';
	}

	/**
	 * Render html
	 *
	 * @param string $template
	 * @param array $data
	 * @param bool $output : true - echo , false - return
	 *
	 * @return string
	 */
	public function render_html($template, $data = null, $output = true) {
		$includeFile = $this->templates_path . $template . '.php';

		ob_start();

		if (is_array($data)) {
			extract($data);
		}

		$this->data = $data;

		include($includeFile);

		$out = ob_get_clean();

		if ($output) {
			echo $out;
		} else {
			return $out;
		}
	}


	/**
	 * @param $template_name
	 * @param array $args
	 * @param string $template_path
	 * @param string $default_path
	 *
	 * @return mixed/void
	 */
	public function get_template_html($template_name, $args = array(), $template_path = '', $default_path = '') {
		ob_start();
		$this->get_template($template_name, $args, $template_path, $default_path);
		return ob_get_clean();
	}

	/**
	 * Get template
	 *
	 * @param $template_name
	 * @param array $args
	 * @param string $template_path
	 * @param string $default_path
	 */
	public function get_template($template_name, $args = array(), $template_path = '', $default_path = '') {
		$template_name = $template_name . '.php';

		if (!empty($args) && is_array($args)) {
			extract($args);
		}

		$located = $this->locate_template($template_name, $template_path, $default_path);

		if (!file_exists($located)) {
			_doing_it_wrong(__FUNCTION__, sprintf('<code>%s</code> does not exist.', $located), '2.1');
			return;
		}

		// Allow 3rd party plugin filter template file from their plugin.
		$located = apply_filters($this->prefix . '_get_template', $located, $template_name, $args, $template_path, $default_path);

		do_action($this->prefix . '_before_template_part', $template_name, $template_path, $located, $args);

		include($located);

		do_action($this->prefix . '_after_template_part', $template_name, $template_path, $located, $args);
	}

	/**
	 * Locate template
	 *
	 * @param $template_name
	 * @param string $template_path
	 * @param string $default_path
	 *
	 * @return mixed|void
	 */
	function locate_template($template_name, $template_path = '', $default_path = '') {
		if (!$template_path) {
			$template_path = $this->template_path;
		}

		if (!$default_path) {
			$default_path = $this->templates_path;
		}

		// Look within passed path within the theme - this is priority.
		$template_args = array(trailingslashit($template_path) . $template_name, $template_name);

		$template = locate_template($template_args);

		// Get default template/
		if (!$template) {
			$template = $default_path . $template_name;
		}

		// Return what we found.
		return apply_filters($this->prefix . '_locate_template', $template, $template_name, $template_path);
	}

}
