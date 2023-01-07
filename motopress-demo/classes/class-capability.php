<?php
namespace motopress_demo\classes;

use Motopress_Demo;
use motopress_demo\classes\models\Sandbox_DAO;
use motopress_demo\classes\models\Restrictions_Settings;
use motopress_demo\classes\models\Sandbox;

class Capabilities {

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Get things going
	 *
	 * @since 1.0
	 */
	public function __construct() {
	}

	public function init() {
		if (Sandbox::get_instance()->is_sandbox()) {
			add_action('current_screen', array($this, 'remove_pages'), 999);
			add_filter('show_password_fields', array($this, 'disable_passwords'));
			add_filter('allow_password_reset', array($this, 'disable_passwords'));
			add_action('personal_options_update', array($this, 'disable_email_editing'), 1);
			add_action('edit_user_profile_update', array($this, 'disable_email_editing'), 1);
			add_action('admin_bar_menu', array($this, 'remove_menu_bar_items'), 999);
		}

		add_action('delete_blog', array($this, 'prevent_delete_blog'), 10, 2);
	}

	/**
	 * Prevent the user from visiting various pages
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function remove_pages() {
		global $menu, $submenu;

		if (!Motopress_Demo::is_admin_user() && is_admin()) {

			remove_meta_box('dashboard_primary', 'dashboard', 'side');
			remove_meta_box('dashboard_secondary', 'dashboard', 'side');

			$sub_menu = Core::get_instance()->decode_special_chars($submenu);
			$allowed_pages = apply_filters('mp_demo_allowed_pages', array('options.php', 'index.php', 'async-upload.php'));
			$allowed_subpages = apply_filters('mp_demo_allowed_subpages', array());
			$options = Restrictions_Settings::get_instance()->get_options(Sandbox_DAO::get_instance()->get_blog_source(get_current_blog_id()));
			$options['parent_pages'][] = 'index.php';
			$not_allowed = $options['child_disabled_pages'];

			$options['parent_pages'][] = 'mp-demo-sandbox';
			$allowed_menu_links = apply_filters('mp_demo_show_menu_pages', $options['parent_pages']);
			$allowed_submenu_links = apply_filters('mp_demo_show_submenu_pages', $options['child_pages']);

			//remove menu items
			foreach ($menu as $item) {
				$parent_slug = $item[2];
				if (in_array($parent_slug, $allowed_menu_links) && !in_array($parent_slug, $options['black_list'])) {
					$allowed_pages[] = $parent_slug;
				} else {
					remove_menu_page($parent_slug);
				}
			}

			/*foreach ($sub_menu as $parent => $parent_item) {

				if (in_array($parent, $allowed_menu_links)) {

					foreach ($parent_item as $item) {
						$child_slug = $item[2];
						$generated_child_slug = mp_demo_generate_submenu_uri($parent, $item[2]);

						if(in_array($generated_child_slug, $options['black_list'])) {
							$this->remove_submenu_item($parent, $child_slug);
							continue;
						}

						if (!in_array($generated_child_slug, $allowed_submenu_links)) {
							if ($parent === 'themes.php') {
								if (!$this->in_restrictions($generated_child_slug, $allowed_submenu_links)) {
									$this->remove_submenu_item($parent, $child_slug);
								}
							} else {
								$this->remove_submenu_item($parent, $child_slug);
							}
						}
					}
				}
			}*/

			// Filter allowed pages.
			if ($not_allowed) {
				$this->pages_proxy($not_allowed, $options['black_list']);
			}
		}

		return '';
	}

	/**
	 * Hide not accessable pages
	 *
	 * @param $allowed_pages
	 * @param $allowed_subpages
	 */
	public function pages_proxy($not_allowed_pages, $black_list) {

		$current_page = $this->get_current_page();

		if (is_array($black_list) && in_array($current_page, $black_list)) {
			wp_die(__(apply_filters('mp_demo_block_msg', ' You do not have sufficient permissions to access this page.'), 'mp-demo'));
		}

		if (is_array($not_allowed_pages) && in_array($current_page, $not_allowed_pages)) {
			wp_die(__(apply_filters('mp_demo_block_msg', ' You do not have sufficient permissions to access this page.'), 'mp-demo'));
		}

	}

	/**
	 * Returns suitable menu slug
	 * Example : url = 'http://test.com/wp-admin/admin.php?page=motopress-slider&view=slide&id=1' , returns = 'motopress-slider'
	 * Example : url = 'http://test.com/wp-admin/post-new.php' , returns = 'post-new.php'
	 *
	 * @return string
	 */
	function get_current_page() {
		global $pagenow;

		$current_page = $pagenow;

		if (!empty($_GET)) {
			$current_page = basename(remove_query_arg('XDEBUG_SESSION_START'));
		}

		return $current_page;
	}

	/**
	 * Search in multi-dimensional array by field
	 *
	 * @param $elem to find
	 * @param $array source
	 * @param $field name
	 *
	 * @return bool
	 */
	function in_multiarray($elem, $array, $field) {
		$top = sizeof($array) - 1;
		$bottom = 0;
		while ($bottom <= $top) {
			if ($array[$bottom][$field] == $elem)
				return true;
			else
				if (is_array($array[$bottom][$field]))
					if ($this->in_multiarray($elem, ($array[$bottom][$field])))
						return true;

			$bottom++;
		}
		return false;
	}

	/**
	 * It compares with function mp_demo_compare_restrictions
	 *
	 * @param $elem
	 * @param $array
	 * @param $field
	 *
	 * @return bool
	 */
	function in_restrictions($elem, $restrictions) {

		foreach ($restrictions as $restriction) {
			if (mp_demo_compare_restrictions($elem, $restriction))
				return true;
		}

		return false;
	}

	/**
	 * Remove from admin submenu array submenu page
	 *
	 * @param $parent_slug
	 * @param $child_slug
	 */
	function remove_submenu_item($parent_slug, $child_slug) {
		global $submenu;

		remove_submenu_page(htmlentities($parent_slug), htmlentities($child_slug));
		foreach ($submenu[$parent_slug] as $priority => $sub) {
			if ($sub[2] == $child_slug) {
				unset($submenu[$parent_slug][$priority]);
			}
		}
	}


	/**
	 * Disable the password field on our profile page if this isn't the admin user.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function disable_passwords() {
		if (Motopress_Demo::is_admin_user())
			return true;

		return false;
	}

	/**
	 * Remove the email address from the profile page if this isn't the admin user.
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function disable_email_editing($user_id) {
		$user_info = get_userdata($user_id);

		if (!Motopress_Demo::is_admin_user())
			$_POST['first_name'] = $user_info->user_firstname;
		$_POST['last_name'] = $user_info->user_lastname;
		$_POST['nickname'] = $user_info->nickname;
		$_POST['display_name'] = $user_info->display_name;
		$_POST['email'] = $user_info->user_email;
	}

	/**
	 * Remove items from our admin bar if the user isn't our network admin
	 *
	 * @access public
	 * @return void
	 */
	public function remove_menu_bar_items($wp_admin_bar) {
		if (!Motopress_Demo::is_admin_user()) {
			$wp_admin_bar->remove_node('my-sites');
			$wp_admin_bar->remove_node('new-content');
		} else {
			$elements = $wp_admin_bar->get_nodes();
			if (is_array($elements)) {
				foreach ($elements as $element) {

					if ($element->parent == 'my-sites-list') {
						$blog_id = str_replace('blog-', '', $element->id);
						if (Sandbox::get_instance()->is_sandbox($blog_id)) {
							$wp_admin_bar->remove_node($element->id);
						}
					}
				}
			}
		}
	}

	/**
	 * Prevent a user from deleting the main blog
	 *
	 * @access public
	 * @return void
	 */
	public function prevent_delete_blog($blog_id, $drop) {
		if ($blog_id == MP_DEMO_MAIN_BLOG_ID && !Motopress_Demo::is_admin_user())
			wp_die(apply_filters('mp_demo_block_msg', __('You do not have sufficient permissions to access this page.', 'mp-demo')));
	}
}