<?php
/**
 * Toolbar
 *
 * This class handles outputting our front-end theme switcher, toolbar
 */
namespace motopress_demo\classes\modules;

use Motopress_Demo;
use motopress_demo\classes\models\Toolbar_Settings;
use motopress_demo\classes\Module;
use motopress_demo\classes\View;

class Toolbar extends Module {

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 *  Output front-end toolbar
	 *
	 * @param $template
	 *
	 * @return string $template
	 */
	public function template_include($template) {
		if ($this->show_toolbar()) {
			View::get_instance()->get_template('toolbar');

			return;
		}

		return $template;
	}

	public function is_permitted_blog() {
		$unpermitted = Toolbar_Settings::get_instance()->get_option('unpermitted');

		if (is_array($unpermitted)) {

			return !in_array(\get_current_blog_id(), $unpermitted);
		}

		return false;
	}

	/**
	 * If it is possible to show toolbar returns true
	 *
	 * @return bool
	 */
	public function show_toolbar() {

		if (Toolbar_Settings::get_instance()->get_option('show_toolbar') == 1
			&& !is_admin()
			&& isset($_GET['dr'])
			&& $_GET['dr'] == 1
			&& $this->is_permitted_blog()
		) {

			return true;
		}

		return false;
	}

	/**
	 * @param $link
	 * @param $post
	 *
	 * @return string new url
	 */
	public function preview_post_link($link, $post) {

		return add_query_arg(array('dr' => 1), $link);
	}

	/**
	 * Get a reference to the view-site node to modify.
	 *
	 * @param $wp_admin_bar
	 */
	public function admin_bar_menu($wp_admin_bar) {
		$node = $wp_admin_bar->get_node('view-site');
		$url = add_query_arg(array('dr' => 1), $node->href);
		$node->href = $url;
		$wp_admin_bar->add_node($node);
	}

	public function toolbar_head() {
		?>
		<link rel="stylesheet" type="text/css" href="<?php echo Motopress_Demo::get_plugin_url('assets/css/toolbar.min.css'); ?>">
		<?php
	}

	public function toolbar_footer() {
		?>
		<script src="<?php echo Motopress_Demo::get_plugin_url('assets/js/toolbar.min.js'); ?>"></script>
		<?php
	}


} // End Class