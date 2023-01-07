<?php
/**
 * class Sandbox
 */
namespace motopress_demo\classes\models;

// Exit if accessed directly
use Motopress_Demo;
use motopress_demo\classes\Core;
use motopress_demo\classes\libs\MP_Demo_Logs;
use motopress_demo\classes\Model;

if (!defined('ABSPATH'))
	exit;

class Sandbox extends Model {

	var $db_name;
	var $target_id;
	var $status;
	var $tables_are_not_copied;
	var $site_address;
	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {

		$this->log = new MP_Demo_Logs();

		$this->tables_are_not_copied = apply_filters('mp_demo_global_tables', array(
			'blogs', 'blog_versions', 'registration_log', 'signups', 'site', 'sitemeta', //default multisite tables
			'usermeta', 'users',
			'bp_.*',
			'3wp_broadcast_.*',
			'mpdemo_data', 'mp_demo_users', 'mp_demo_sandboxes' // this plugin table
		));

		$this->folders_are_not_copied = apply_filters('mp_demo_global_folders', array(
			'sites', 'demo-export' // this plugin table
		));

		$this->db_name = DB_NAME;
		$this->target_id = '';
		$this->status = '';

	}

	/**
	 * Check to see if any of blogs have been marked as "deleted."
	 */
	public function deleted_blog_check() {
		global $wpdb;
		$blogs = $wpdb->get_results("SELECT blog_id FROM {$wpdb->blogs} WHERE deleted = '1'");

		foreach ($blogs as $blog) {
			if (!$this->is_sandbox($blog->blog_id)) {
				$wpdb->update($wpdb->blogs, array('deleted' => '0'), array('blog_id' => $blog->blog_id));
			}
		}
	}

	/**
	 * Count sandboxes
	 *
	 * @return int $count
	 */
	public function sandbox_count($source_id = '') {
		return Sandbox_DAO::get_instance()->count_sandboxes($source_id);
	}

	/**
	 * Delete a sandbox
	 */
	public function delete($blog_id, $drop = true) {
		$admin = Core::get_instance()->get_last_subfolder(admin_url(), '/wp-admin/');

		require_once(MP_DEMO_ABSPATH . $admin . 'includes/ms.php');

		$blog_id = intval($blog_id);

		if (!$this->is_sandbox($blog_id)) {
			return false;
		}

		$blog = get_blog_details($blog_id);

		/**
		 * Fires before a sandbox is deleted.
		 *
		 * @param int $blog_id The blog ID.
		 */
		do_action('mp_demo_delete_sandbox', $blog_id);

		$current_site = get_current_site();

		if ($drop && (MP_DEMO_MAIN_BLOG_ID == $blog_id || is_main_site($blog_id) || ($blog->path == $current_site->path && $blog->domain == $current_site->domain))){
			$drop = false;
		}

		wpmu_delete_blog($blog_id, $drop);

		// Remove uploads folder too
		$this->delete_dir(WP_CONTENT_DIR . '/uploads/sites/' . $blog_id);
		$this->delete_dir($this->get_upload_folder($blog_id));

		if ($this->is_sandbox()) {

			if (!Motopress_Demo::is_admin_user())
				wp_logout();
		}
	}

	public function reset() {
		global $wpdb;

		$target_id = get_current_blog_id();

		if (!$this->is_sandbox($target_id)) {
			return false;
		}

		$replace_array = array();
		$current_sandbox = Sandbox_DAO::get_instance()->get_data('blog_id', $target_id);
		$user_data = $this->prepare_user($current_sandbox['user_id']);
//		$login_role = isset ($mp_settings['login_role']) ? $mp_settings['login_role'] : 'editor';
		$source_id = intval($current_sandbox['source_blog_id']);

		// Get Target site information
		$current_blog_details = get_blog_details(array('blog_id' => $target_id));
		$source_blog_details = get_blog_details(array('blog_id' => $source_id));


		$target_site_name = trim($current_blog_details->path, '/');
		$target_subd = $current_blog_details->domain . get_current_site()->path . $target_site_name;
		$source_subd = untrailingslashit($source_blog_details->domain . $source_blog_details->path);

		$target_site = $source_blog_details->blogname;
		$source_site = $source_blog_details->blogname;

		//configure all the properties
		$source_pre = $source_id == MP_DEMO_MAIN_BLOG_ID ? $wpdb->base_prefix : $wpdb->base_prefix . $source_id . '_';    // the wp id of the source database
		$target_pre = $wpdb->base_prefix . $target_id . '_';    // the wp id of the target database

		/**
		 * Start copying tables
		 */
		//  to allow DROP TABLE IF EXISTS
		update_blog_option($target_id, 'mp_demo_is_sandbox', 0);

		$this->copy_tables($source_pre, $target_pre);

		$replace_array[$source_subd] = $target_subd;
		$replace_array[$source_site] = $target_site;

		$main_uploads_target = '';
		if (MP_DEMO_MAIN_BLOG_ID == $source_id) {
			switch_to_blog(MP_DEMO_MAIN_BLOG_ID);
			$main_uploads_info = wp_upload_dir();
			restore_current_blog();

			$main_uploads_dir = $main_uploads_info['baseurl'];

			$main_uploads_target = WP_CONTENT_DIR . '/uploads/sites/' . $target_id;
			$main_uploads_replace = $main_uploads_info['baseurl'] . '/sites/' . $target_id;

			$replace_array[$main_uploads_dir] = $main_uploads_replace;

			$replace_array[$wpdb->base_prefix . 'user_roles'] = $wpdb->base_prefix . $target_id . '_user_roles';
		} else {

			$replace_array['/sites/' . $source_id . '/'] = '/sites/' . $target_id . '/';
			$replace_array[$wpdb->base_prefix . $source_id . '_user_roles'] = $wpdb->base_prefix . $target_id . '_user_roles';
		}

		$this->update_references($target_pre, $replace_array);

		wp_cache_flush();

		refresh_blog_details($target_id);

		update_blog_option($target_id, 'blog_public', 0);
		update_blog_option($target_id, 'mp_demo_sandbox_id', $target_site_name);
		update_blog_option($target_id, 'mp_demo_is_sandbox', 1);
		update_blog_option($target_id, 'mp_demo_source_id', $source_id);
		update_blog_option($target_id, 'mp_user', $user_data['user_name']);

		/**
		 * Start copying uploads
		 */
		$src_blogs_dir = $this->get_upload_folder($source_id);

		if (MP_DEMO_MAIN_BLOG_ID == $source_id) {
			$dst_blogs_dir = $main_uploads_target;
		} else {
			$dst_blogs_dir = $this->get_upload_folder($target_id);
		}

		$this->delete_dir($dst_blogs_dir);

		if (strpos($src_blogs_dir, '/') !== false && strpos($src_blogs_dir, '\\') !== false) {
			$src_blogs_dir = str_replace('/', '\\', $src_blogs_dir);
			$dst_blogs_dir = str_replace('/', '\\', $dst_blogs_dir);
		}
		if (is_dir($src_blogs_dir)) {
			$num_files = $this->recursive_file_copy($src_blogs_dir, $dst_blogs_dir, 0);
		}

		// Set "last updated" time to the current time.
		$wpdb->update($wpdb->blogs, array('last_updated' => current_time('mysql')), array('blog_id' => $target_id));

		/**
		 * Start reactivate plugins
		 */
		$plugins = get_option('active_plugins');

		if (!empty($plugins)) {
			foreach ($plugins as $plugin) {
				if (apply_filters('mp_activate_plugin', false, $plugin)) {
					deactivate_plugins($plugin);
					activate_plugin($plugin);
				}
			}
		}

		return true;
	} // End Reset

	public function delete_dir($dir) {
		if (!file_exists($dir)) {
			return true;
		}

		if (!is_dir($dir)) {
			return unlink($dir);
		}

		foreach (scandir($dir) as $item) {
			if ($item == '.' || $item == '..') {
				continue;
			}

			if (!$this->delete_dir($dir . DIRECTORY_SEPARATOR . $item)) {
				return false;
			}

		}

		return rmdir($dir);
	}

	public function drop_blog_tables($blog_id) {
		global $wpdb;
		//		drop_tables
		$target_pre = $wpdb->base_prefix . $blog_id . '_';
		$drop_tables = array();
		$tables = $wpdb->get_results('SHOW TABLES LIKE "' . $target_pre . '%"', ARRAY_A);
		foreach ($tables as $table) {
			foreach ($table as $name) {
				$drop_tables[] = $name;
			}
		}

		/**
		 * Filter the tables to drop when the blog is deleted.
		 */
		$drop_tables = apply_filters('wpmu_drop_tables', $drop_tables, $blog_id);

		foreach ((array)$drop_tables as $table) {
			$wpdb->query("DROP TABLE IF EXISTS `$table`");
		}

	}

	/*
	* Clean db from unactive and
	* expired sandboxes every day
	*/
	public function cron_event() {
		$this->log->dlog('Cron Event: Check purge<br><br>');
		Sandbox_DAO::get_instance()->purge_sandboxes();
	}

	/**
	 * Check to see if this sandbox is expired
	 */
	public function is_expired($blog_id = '') {
		if ($blog_id == '')
			$blog_id = get_current_blog_id();

		return Sandbox_DAO::get_instance()->is_expired('blog_id', $blog_id);
	}

	/**
	 * Check to see if we are currently in a sandbox.
	 */
	public function is_sandbox($blog_id = '') {
		if ($blog_id != '') {
			if (get_blog_option($blog_id, 'mp_demo_is_sandbox') == 1) {
				return true;
			}
		} else {
			// Check to see if sandbox option is set.
			if (get_option('mp_demo_is_sandbox') == 1) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Create sandbox
	 */
	public function create($source_id, $target_site = '') {
		global $wpdb;

		$target_id = '';
		$target_subd = '';
		$target_site = '';

		$mp_settings = General_Settings::get_instance()->get_options();// get_blog_option($source_id, 'mp_demo_general');

		$stimer = explode(' ', microtime());
		$stimer = $stimer[1] + $stimer[0];

		$target_site_name = $this->generate_site_name();

		/**
		 * Creating user for this sandbox.
		 */
		$login_role = isset ($mp_settings['login_role']) ? $mp_settings['login_role'] : 'editor';
		$secret = filter_input(INPUT_GET, 'demo-access', FILTER_SANITIZE_STRING);
		if ($secret) {
			$sandbox_data = Sandbox_DAO::get_instance()->get_data('secret', "'{$secret}'");
			$user_data = $this->prepare_user($sandbox_data['user_id']);

		} else {
			return false;
		}

		if ($target_site == '') {
			$target_site = get_blog_details($source_id)->blogname;
		}

		if ($login_role == 'administrator' || $login_role == 'editor') {
			user_can($user_data['wp_user_id'], 'upload_files');
		}

		if ($login_role == 'administrator') {
			$owner_user_id = $user_data['wp_user_id'];
		} else {
			$super_admins = get_super_admins();
			if (!empty($super_admins)) {
				$owner_user_id = get_user_by('login', $super_admins[0]);
				$owner_user_id = $owner_user_id->ID;
			} else {
				$owner_user_id = 1;
			}
		}
        
        
		// Create site
		$this->create_site($target_site_name, $target_site, $source_id, $owner_user_id);
		
		// my changes for statistic module
		if(is_plugin_active( 'arformstestdrive/arformstestdrive.php' )){
			$wpdb->insert($wpdb->base_prefix.'mp_demo_arforms_statistic', 
				array(
					'user_id' => $user_data['wp_user_id'],
					'blog_id' => $this->target_id,	
					'email' => $user_data['email'],
					'ip_address' => $_REQUEST['ip_address'],
					'browser_name' => $_REQUEST['browser_name'],
					'browser_version' => $_REQUEST['browser_version'],
					'country' => $_REQUEST['coutry'],
					'referrer_url' => $_REQUEST['referrer_url'],
					'created_date' => current_time('mysql')
				),
				array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
			);	
		}
		if(is_plugin_active( 'armembertestdrive/armembertestdrive.php' )){
			$wpdb->insert($wpdb->base_prefix.'mp_demo_armember_statistic', 
				array(
					'user_id' => $user_data['wp_user_id'],
					'blog_id' => $this->target_id,
					'email' => $user_data['email'],
					'ip_address' => $_REQUEST['ip_address'],
					'browser_name' => $_REQUEST['browser_name'],
					'browser_version' => $_REQUEST['browser_version'],
					'country' => $_REQUEST['coutry'],
					'referrer_url' => $_REQUEST['referrer_url'],
					'created_date' => current_time('mysql')
				),
				array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
			);	
		}


		$this->log->dlog('RUNNING MP Cloner<br><br>');

		$source_subd = untrailingslashit(get_blog_details($source_id)->domain . get_blog_details($source_id)->path);

		$source_site = get_blog_details($source_id)->blogname;

		$target_id = $this->target_id;
		$target_subd = get_current_site()->domain . get_current_site()->path . $target_site_name;

		//configure all the properties
		$source_pre = $source_id == MP_DEMO_MAIN_BLOG_ID ? $wpdb->base_prefix : $wpdb->base_prefix . $source_id . '_';    // the wp id of the source database
		$target_pre = $wpdb->base_prefix . $target_id . '_';    // the wp id of the target database

		$this->log->dlog('Source Prefix: <b>' . $source_pre . '</b><br>Target Prefix: <b>' . $target_pre . '</b><br>');

		$this->copy_tables($source_pre, $target_pre);

		$target_pre = $wpdb->base_prefix . $target_id . '_';    // the wp id of the target database

		$replace_array[$source_subd] = $target_subd;
		$replace_array[$source_site] = $target_site;

		$main_uploads_target = '';
		if (MP_DEMO_MAIN_BLOG_ID == $source_id) {
			switch_to_blog(MP_DEMO_MAIN_BLOG_ID);
			$main_uploads_info = wp_upload_dir();
			restore_current_blog();

			$main_uploads_dir = $main_uploads_info['baseurl'];

			$main_uploads_target = WP_CONTENT_DIR . '/uploads/sites/' . $target_id;
			$main_uploads_replace = $main_uploads_info['baseurl'] . '/sites/' . $target_id;

			$replace_array[$main_uploads_dir] = $main_uploads_replace;

			$replace_array[$wpdb->base_prefix . 'user_roles'] = $wpdb->base_prefix . $target_id . '_user_roles';
		} else {

			$replace_array['/sites/' . $source_id . '/'] = '/sites/' . $target_id . '/';
			$replace_array[$wpdb->base_prefix . $source_id . '_user_roles'] = $wpdb->base_prefix . $target_id . '_user_roles';
		}

		$this->log->dlog('running replace on Target table prefix: ' . $target_pre . '<br>');
		foreach ($replace_array as $search_for => $replace_with) {
			$this->log->dlog('Replace: <b>' . $search_for . '</b> >> With >> <b>' . $replace_with . '</b><br>');
		}

		$this->update_references($target_pre, $replace_array);

		$src_blogs_dir = $this->get_upload_folder($source_id);

		if (MP_DEMO_MAIN_BLOG_ID == $source_id) {
			$dst_blogs_dir = $main_uploads_target;
		} else {
			$dst_blogs_dir = $this->get_upload_folder($this->target_id);
		}

		if (strpos($src_blogs_dir, '/') !== false && strpos($src_blogs_dir, '\\') !== false) {
			$src_blogs_dir = str_replace('/', '\\', $src_blogs_dir);
			$dst_blogs_dir = str_replace('/', '\\', $dst_blogs_dir);
		}
		if (is_dir($src_blogs_dir)) {

			$num_files = $this->recursive_file_copy($src_blogs_dir, $dst_blogs_dir, 0);
			$this->log->dlog('Copied: <b>' . $num_files . '</b> folders and files!<br>'
				. 'From: <b>' . $src_blogs_dir . '</b><br>'
				. 'To: <b>' . $dst_blogs_dir . '</b><br>');
		}

		switch_to_blog($this->target_id);

		update_blog_option($this->target_id, 'blog_public', 0);

		update_blog_option($this->target_id, 'mp_demo_sandbox_id', $target_site_name);

		update_blog_option($this->target_id, 'mp_demo_is_sandbox', 1);

		update_blog_option($this->target_id, 'mp_demo_source_id', $source_id);

		update_blog_option($this->target_id, 'mp_user', $user_data['user_name']);
		


		// Login user.
		add_user_to_blog($this->target_id, $user_data['wp_user_id'], $login_role);

		remove_user_from_blog($user_data['wp_user_id'], $source_id);

		if (MP_DEMO_MAIN_BLOG_ID != $source_id) {
			remove_user_from_blog($user_data['wp_user_id'], MP_DEMO_MAIN_BLOG_ID);
		}

		wp_clear_auth_cookie();
		//wp_set_auth_cookie($user_data['wp_user_id'], true);
		//wp_set_current_user($user_data['wp_user_id']);

		// Set "last updated" time to the current time.
		$wpdb->update($wpdb->blogs, array('last_updated' => current_time('mysql')), array('blog_id' => $this->target_id));


		// Get a list of active plugins.
		$plugins = get_option('active_plugins');

		if (!empty($plugins)) {
			foreach ($plugins as $plugin) {
				if (apply_filters('mp_activate_plugin', false, $plugin)) {
					deactivate_plugins($plugin);
					activate_plugin($plugin);
				}
			}
		}    	   
        
		do_action('mp_demo_create_sandbox', $this->target_id);

		$etimer = explode(' ', microtime());
		$etimer = $etimer[1] + $etimer[0];
		$this->log->log($target_subd . " cloned in " . ($etimer - $stimer) . " seconds.");
		$this->log->dlog("Entire cloning process took: <strong>" . ($etimer - $stimer) . "</strong> seconds.");

		// Update site url.
		update_blog_option($this->target_id, 'siteurl', $this->site_address);
		update_blog_option($this->target_id, 'home', $this->site_address);

		$siteurl = $this->site_address . $mp_settings['redirect'];

		$site_address = add_query_arg(array('mp_login' => '1'), $siteurl);
		$secret = filter_input(INPUT_GET, 'demo-access', FILTER_SANITIZE_STRING);
		$expiration_time = date("Y-m-d H:i:s", current_time('timestamp') + $mp_settings['lifespan']);

		$new_data = array(
				'is_lifetime' => $mp_settings['is_lifetime'],
				'expiration_date' => $expiration_time,
				'activation_date' => current_time('mysql'),
				'site_url' => $site_address,
				'blog_id' => $target_id,
				'status' => MP_DEMO_STATUS_ACTIVE,
				'secret' => $secret,
		);
		Sandbox_DAO::get_instance()->update_data('secret', $secret, $new_data);
		//echo "<br>site updated";
		

		// to activate plugin and assign a capability of plugin
		if(is_plugin_active( 'arformstestdrive/arformstestdrive.php' )){
			global $arforms_demo_setup;
		    $plugin_list = get_option('mp_demo_selected_plugins');
		    $plugin_list = maybe_unserialize($plugin_list);

		    if(!empty($plugin_list)){
		
				$arfroles = $arforms_demo_setup->arforms_caps_with_addons();
				
				$temp_user = get_user_by('ID', $user_data['wp_user_id']);
				foreach ($plugin_list as $plugin) {	
		    		
		    		activate_plugin($plugin);

		    		if(isset($arfroles[$plugin])){
			    		foreach($arfroles[$plugin]['caps'] as $capabilities){
			    			$temp_user->add_cap($capabilities);
			    		}
		    		}   
	    	    
	    	    }
			}
		}
		if(is_plugin_active( 'armembertestdrive/armembertestdrive.php' )){
			global $armember_demo_setup, $arm_affiliate;
		    $plugin_list = get_option('mp_demo_selected_plugins');
		    $plugin_list = maybe_unserialize($plugin_list);

		    if(!empty($plugin_list)){
		
				$arfroles = $armember_demo_setup->armember_caps_with_addons();
				
				$temp_user = get_user_by('ID', $user_data['wp_user_id']);
				foreach ($plugin_list as $plugin) {	
		    		
		    		activate_plugin($plugin);
				if($plugin=="armemberaffiliate/armemberaffiliate.php")
				{
					$armroles = $arm_affiliate->arm_aff_capabilities();
			                foreach ($armroles as $armrole => $armroledescription) {
			                    $temp_user->add_cap($armrole);
			                }
				}
		    		if(isset($arfroles[$plugin])){
			    		foreach($arfroles[$plugin]['caps'] as $capabilities){
			    			$temp_user->add_cap($capabilities);
			    		}
		    		}   
	    	    
	    	    }
			}
		}
        


		wp_redirect(apply_filters('mp_demo_create_redirect', $siteurl, $this->target_id, $this->site_address, $mp_settings['redirect']));
		die();
	}

	public function prepare_user($user_id) {

		$user_data = User_DAO::get_instance()->get_data('user_id', $user_id);
		$user_data['is_created_user'] = false;

		if (empty($user_data['password'])) {
			$this->show_error_page( array('errorcode' => 'UserDataError',
							'errormsg' => __('Error while creating Demo. Use another email address or contact support.', 'mp-demo'),
							'updated' => false
					)
			);
			die;
		}

		User_DAO::get_instance()->activate_email($user_data['user_id']);

		$user_data['user_name'] = apply_filters('mp_user_name', $user_data['email']);
		$user_email = apply_filters('mp_user_email', $user_data['email']);

		// If user exists
		$the_user = email_exists($user_email) ? get_user_by('email', $user_email) : false;

		if (empty($user_data['wp_user_id']) || !$the_user) {

			if ($the_user) {
				$user_id = $the_user->ID;

			} else {
				$user_data['is_created_user'] = true;
				$user_id = wp_create_user($user_data['user_name'], $this->decode_pass($user_data['password']), $user_email);

				if (is_wp_error($user_id)) {
					$this->show_error_page(
							array('errorcode' => urlencode($user_id->get_error_code()),
									'errormsg' => $user_id->get_error_message(),
									'updated' => false
							)
					);
					die;
				}
			}

			$new_data = array(
					'wp_user_id' => $user_id,
			);
			$user_data['wp_user_id'] = $user_id;

			User_DAO::get_instance()->update_data('user_id', $user_data['user_id'], $new_data);
		}

		return $user_data;
	}

	public function show_error_page($error) {
		echo "Uncaught " . $error['errorcode'] . ": " . $error['errormsg'] . "<br>";
	}

	/**
	 * Edit sandbox from sandbox list
	 */
	public function edit() {
		$post = $_POST;
		$data = array();
		$change_status = false;

		if (!isset($post['sandbox'])) {
			return;
		}

		if ($post['is_lifetime'] != -1) {
			$data['is_lifetime'] = $post['is_lifetime'];
		}

		if ($post['expiration_date']) {
			$data['expiration_date'] = $post['expiration_date'];
		}

		if ($post['status'] != -1) {
			$change_status = true;
			$status = $post['status'];
			$data['status'] = $post['status'];
		}

		if (empty($data)) {
			return;
		}

		if (strpos($post['sandbox'], ',')) {
				foreach (explode(',',$post['sandbox']) as $blog_id) {
					if ($change_status) {
						$this->update_site_status($blog_id, $status);
					}
					Sandbox_DAO::get_instance()->update_data('blog_id', $blog_id, $data);
				}
		} else {
			if ($change_status) {
				$this->update_site_status($post['sandbox'], $status);
			}
			Sandbox_DAO::get_instance()->update_data('blog_id', $post['sandbox'], $data);
		}
	}

	public function update_site_status($blog_id, $status) {
		switch ($status) {
			case MP_DEMO_STATUS_ACTIVE:

				$wp_action = 'unarchive_blog';
				do_action($wp_action, $blog_id);
				update_blog_status($blog_id, MP_DEMO_STATUS_ARCHIVED, 0);

				$wp_action = 'activate_blog';
				update_blog_status($blog_id, 'deleted', 0);
				do_action($wp_action, $blog_id);

				break;
			case MP_DEMO_STATUS_ARCHIVED:
				$wp_action = 'archive_blog';
				do_action($wp_action, $blog_id);
				update_blog_status($blog_id, MP_DEMO_STATUS_ARCHIVED, 1);
				break;
			case MP_DEMO_STATUS_DEACTIVATED:
				$wp_action = 'deactivate_blog';
				do_action($wp_action, $blog_id);
				update_blog_status($blog_id, 'deleted', 1);
				break;
		}
	}

	/**
	 * Build an array of all field names for this table
	 *
	 * @param $table
	 *
	 * @return array
	 */
	public function cols_names($table) {
		global $wpdb;
		$existing_columns = array();

		foreach ($wpdb->get_col("DESC " . $table, 0) as $column_name) {
			$existing_columns[] = '`' . $column_name . '`';
		}

		return $existing_columns;
	}

	/**
	 * Return a random alphanumeric string to serve as site name.
	 */
	private function generate_site_name($length = 8) {
		$key = Core::get_instance()->random_string();

		$site_id = get_id_from_blogname($key);

		if (!empty($site_id)) {
			return $this->generate_site_name($length);
		}

		return $key;
	}

	/**
	 * Create a site for sandbox
	 */
	private function create_site($sitename, $sitetitle, $source_id, $user_id) {
		global $current_site, $wp_version;

		if (version_compare($wp_version, '4.5', '<')) {
			get_currentuserinfo();
		} else {
			wp_get_current_user();
		}

		$base = PATH_CURRENT_SITE;
		$tmp_domain = strtolower(esc_html($sitename));

		if (constant('VHOST') == 'yes') {
			$tmp_site_domain = $tmp_domain . '.' . $current_site->domain;
			$tmp_site_path = $base;
		} else {
			$tmp_site_domain = $current_site->domain;
			$tmp_site_path = $base . $tmp_domain . '/';
		}

		$create_site_name = $sitename;
		$create_site_title = $sitetitle;

		$site_id = get_id_from_blogname($create_site_name);

		$meta['public'] = 1;
		$site_id = wpmu_create_blog($tmp_site_domain, $tmp_site_path, $create_site_title, $user_id, $meta, $current_site->id);

		if (!is_wp_error($site_id)) {
			$this->log->log('Site: ' . $tmp_site_domain . $tmp_site_path . ' created!');
			$this->target_id = $site_id;
		} else {
			$this->log->log('Error creating site: ' . $tmp_site_domain . $tmp_site_path . ' - ' . $site_id->get_error_message());
		}

		if (is_ssl()) {
			$protocol = 'https://';
		} else {
			$protocol = 'http://';
		}

		$this->site_address = $protocol . $tmp_site_domain . $tmp_site_path;

		// Update site url.
		update_blog_option($this->target_id, 'siteurl', $this->site_address);
		update_blog_option($this->target_id, 'home', $this->site_address);

		// Login settings might not be based upon this blog.
		$mp_settings = General_Settings::get_instance()->get_options();
		if (isset ($mp_settings['auto_login']) && isset ($mp_settings)) {
			if ($mp_settings['auto_login'] != '' && $mp_settings['login_role'] != '') {
				$user = $mp_settings['auto_login'];
				$role = $mp_settings['login_role'];
				add_user_to_blog($this->target_id, $user, $role);
			}
		}
	}

	/**
	 * Clone the data from main site to newly created sandbox site
	 */
	private function copy_tables($source_prefix, $target_prefix) {
		global $wpdb;

		// Get a list of current sandbox sites
		$sandboxes = Core::get_sites();

		//get list of source tables when cloning root
		if ($source_prefix == $wpdb->base_prefix) {
			$tables = $wpdb->get_results('SHOW TABLES');
			$global_table_pattern = "/^$wpdb->base_prefix(" . implode('|', $this->tables_are_not_copied) . ")$/";
			$table_names = array();
			foreach ($tables as $table) {
				$table = (array)$table;
				$table_name = array_pop($table);
//				$is_root_table = preg_match("/$wpdb->prefix(?!\d+_)/", $table_name);

				$regex = "/^{$wpdb->base_prefix}[0-9]+_/";
				$is_root_table = (1 == preg_match($regex, $table_name)) ? false : true;
				if ($is_root_table && !preg_match($global_table_pattern, $table_name)) {
					array_push($table_names, $table_name);
				}
			}
			$query = "SHOW TABLES WHERE `Tables_in_" . $this->db_name . "` IN('" . implode("','", $table_names) . "')";
		} else {

			// It is important to escape '_' characters otherwise they will be interpreted as wildcard
			$query = 'SHOW TABLES LIKE \'' . str_replace('_', '\_', $source_prefix) . '%\'';
		}

		$tables_list = $wpdb->get_results($query, ARRAY_N);

		$num_tables = 0;

		if (isset ($tables_list[0]) && !empty ($tables_list[0])) {
			foreach ($tables_list as $tables) {
				$source_table = $tables[0];

				foreach ($sandboxes as $s) {
					if ($this->is_sandbox($s['blog_id']) && strpos($source_table, $wpdb->base_prefix . $s['blog_id']) !== false) {
						continue 2;
					}
				}

				$pos = strpos($source_table, $source_prefix);
				if ($pos === 0) {
					$target_table = substr_replace($source_table, $target_prefix, $pos, strlen($source_prefix));
				}

				$num_tables++;
				//run cloning on current table to target table
				if ($source_table != $target_table) {
					$this->log->dlog('<br><br>Cloning source table: <b>' . $source_table . '</b> (table #' . $num_tables . ') to Target table: <b>' . $target_table . '</b><br>');

					$this->copy_table($source_table, $target_table);

				}
			}

		} else {
			$this->log->dlog('no data for sql - ' . $query);
		}

		$this->log->dlog('<br><br>*****************************************************************************<br><br>'
			. 'Cloned: <b>' . $num_tables . '</b> tables!<br/ >');
	}

	/**
	 * Reads the Database table in $source_table and executes SQL Statements for cloning it to $target_table.
	 */
	private function copy_table($source_table, $target_table) {
		global $wpdb;

		/**
		 * Filter of query count
		 *
		 * Plugin will attempt to insert this many database rows at once when cloning a source.
		 * Higher numbers will result in faster sandbox creation, but lower numbers are less prone to failure. 4 is a good starting point.
		 */
		$query_count = apply_filters('mp_demo_query_count', 4);

		$sql_statements = '';

		$query = "DROP TABLE IF EXISTS " . $this->backquote($target_table);

		$result = $wpdb->query($query);
		if ($result == false) {
			$this->log->dlog('<b>ERROR</b> dropping table with sql - ' . $query . '<br><b>SQL Error</b> - ' . $wpdb->last_error . '<br>');
		}

		// Table structure - Get table structure
		$query = "SHOW CREATE TABLE " . $this->backquote($source_table);
		$result = $wpdb->get_row($query, ARRAY_A);

		if ($result == false) {
			$this->log->dlog('<b>ERROR</b> getting table structure with sql - ' . $query . '<br><b>SQL Error</b> - ' . $wpdb->last_error . '<br>');
		} else {
			if (!empty ($result)) {
				$sql_statements .= $result['Create Table'];
			}
		}

		// Create cloned table structure
		$query = str_replace($source_table, $target_table, $sql_statements);

		$this->log->dlog($query . '<br><br>');

		$result = $wpdb->query($query);

		// Table data contents - Get table contents
		$query = "SELECT * FROM " . $this->backquote($source_table);
		$result = $wpdb->get_results($query, ARRAY_N);

		$fields_cnt = 0;
		if ($result != false) {
			$fields_cnt = count($result[0]);
			$rows_cnt = $wpdb->num_rows;
		}

		// Checks whether the field is an integer or not
		for ($j = 0; $j < $fields_cnt; $j++) {
			$type = $wpdb->get_col_info('type', $j);
			// removed ||$type == 'timestamp' from this check because it's invalid - timestamp values need ' ' surrounding to insert successfully
			if ($type == 'tinyint' || $type == 'smallint' || $type == 'mediumint' || $type == 'int' || $type == 'bigint') {
				$field_num[$j] = true;
			} else {
				$field_num[$j] = false;
			}
		} // end for

		// query part
		$query_part = 'INSERT IGNORE INTO '
			. $this->backquote($target_table)
			. ' ('
			. implode(', ', $this->cols_names($target_table))
			. ') VALUES ';

		$search = array("\x00", "\x0a", "\x0d", "\x1a");    //\x08\\x09, not required
		$replace = array('\0', '\n', '\r', '\Z');

		$table_query = '';
		$table_query_count = 0;

		foreach ($result as $row) {

			// Tracks the _transient_feed_ and _transient_rss_ garbage for exclusion
			$is_trans = false;
			for ($j = 0; $j < $fields_cnt; $j++) {
				if (!isset($row[$j])) {
					$values[] = 'NULL';
				} else if ($row[$j] == '0' || $row[$j] != '') {
					// a number
					if ($field_num[$j]) {
						$values[] = $row[$j];
					} else {
						// don't include _transient_feed_ bloat
						if (!$is_trans && false === strpos($row[$j], '_transient_')) {
							$row[$j] = str_replace("&#039;", "'", $row[$j]);
							$values[] = "'" . str_replace($search, $replace, $this->sql_addslashes($row[$j])) . "'";
						} else {
							$values[] = "''";
							$is_trans = false;
						}
						// set $is_trans for the next field based on the contents of the current field
						(strpos($row[$j], '_transient_') === false && strpos($row[$j], '_transient_timeout_') === false) ? $is_trans = false : $is_trans = true;

					} //if ($field_num[$j])
				} else {
					$values[] = "''";
				} // if (!isset($row[$j]))
			} // for ($j = 0; $j < $fields_cnt; $j++)

			// Execute current statement
			$current_query = ' (' . implode(', ', $values) . '),';
			$table_query .= $current_query;
			$table_query_count++;

			unset($values);

			if ($table_query_count >= $query_count) {
				$wpdb->query($query_part . rtrim($table_query, ','));
				$table_query_count = 0;
				$table_query = '';
			}

		} // while ($row = mysql_fetch_row($result))

		if (!empty($table_query)) {
			$wpdb->query($query_part . rtrim($table_query, ','));
		}

	}

	/**
	 * Replace references to main site within newly cloned sandbox site.
	 */
	private function update_references($target_prefix, $replace_array) {
		global $count_tables_checked, $count_items_checked, $count_items_changed, $wpdb;

		// It is important to escape '_' characters otherwise they will be interpreted as wildcard
		$query = 'SHOW TABLES LIKE \'' . str_replace('_', '\_', $target_prefix) . '%\'';

		$tables_list = $wpdb->get_results($query, ARRAY_N);

		$num_tables = 0;

		if (isset ($tables_list[0]) && !empty ($tables_list[0])) {
			foreach ($tables_list as $table) {

				$table = $table[0];

				$count_tables_checked++;
				$this->log->dlog('<br><br>' . 'Copy table: <b>' . $table . '</b><br>');

				$query = "DESCRIBE " . $table;    // fetch the table description so we know what to do with it
				$fields_list = $wpdb->get_results($query, ARRAY_A);

				// Make a simple array of field column names

				$index_fields = "";  // reset fields for each table.
				$column_name = array();
				$table_index = array();
				$i = 0;

				foreach ($fields_list as $field_rows) {
					$column_name[$i++] = $field_rows['Field'];
					if ($field_rows['Key'] == 'PRI')
						$table_index[] = $field_rows['Field'];
				}

				// skip if no primary key
				if (empty($table_index)) continue;

				// now let's get the data and do search and replaces on it...

				$query = "SELECT * FROM " . $table;     // fetch the table contents
				$data = $wpdb->get_results($query, ARRAY_A);

				if (!isset ($data[0]) || empty ($data[0])) {
					$this->log->dlog("<br><b>ERROR:</b> " . $wpdb->last_error . "<br/>$query<br/>");
				}

				foreach ($data as $row) {

					// Initialize the UPDATE string we're going to build, and we don't do an update for each column...
					$need_to_update = false;
					$update_query = 'UPDATE ' . $table . ' SET ';
					$query_condition = ' WHERE ';
					foreach ($table_index as $index) {
						$query_condition .= "$index = '$row[$index]' AND ";
					}

					$j = 0;

					foreach ($column_name as $current_column) {
						$data_to_fix = $edited_data = $row[$current_column];
						$j++;

						foreach ($replace_array as $search_for => $replace_with) {
							$count_items_checked++;
							if (is_serialized($data_to_fix)) {
								$unserialized = unserialize($edited_data);
								Core::get_instance()->recursive_replace($search_for, $replace_with, $unserialized);
								$edited_data = serialize($unserialized);
							} elseif (is_string($data_to_fix)) {
								$edited_data = str_replace($search_for, $replace_with, $edited_data);
							}
						}
						if ($data_to_fix != $edited_data) {
							$count_items_changed++;
							if ($need_to_update != false) $update_query = $update_query . ',';
							$update_query = $update_query . ' ' . $current_column . ' = "' . esc_sql($edited_data) . '"';
							$need_to_update = true;
						}
					}

					if ($need_to_update) {
						$query_condition = substr($query_condition, 0, -4);
						$update_query = $update_query . $query_condition;

						$result = $wpdb->query($update_query);
						if (!$result) {
							$this->log->dlog(("<br><b>ERROR: </b>" . $wpdb->last_error . "<br/>$update_query<br/>"));
						}
					}
				}
			}
		}
	}

	/**
	 * Returns the count of records in the database.
	 *
	 * @return null|string
	 */
	public static function get_sandbox_list_count() {
		global $wpdb;
		$table = Motopress_Demo::get_tables_names();

		$sql = "SELECT b.`blog_id`, b.`path`, b.`registered`, b.`last_updated`, mp.`email`, mp.`source_blog_id`, mp.`expiration_date`, mp.`status` "
				. "FROM `{$wpdb->blogs}` b INNER JOIN "
				. " ( SELECT `blog_id`, `email`, `source_blog_id`, `expiration_date`, `status` FROM `"
				. $table['sandboxes'] . "` s INNER JOIN `" . $table['users'] . "` u on s.user_id=u.user_id ) mp ON b.`blog_id` = mp.`blog_id`";
		if( isset($_POST['s']) ){
			$sql .= " WHERE mp.`email` LIKE '%" . $_POST['s'] . "%' ";
		}
		if( isset($_GET['status']) ){
			$sql .= " WHERE mp.`status` LIKE '" . $_GET['status'] . "' ";
		}

		$wpdb->get_results($sql, 'ARRAY_A');

		return $wpdb->num_rows;
	}

	/*
	 * Returns sandboxes
	 */
	public function get_sandbox_list($per_page = 10, $page_number = 1) {
		global $wpdb;

		$table = Motopress_Demo::get_tables_names();

		$sql = "SELECT b.`blog_id`, b.`path`, b.`registered`, b.`last_updated`, mp.`email`, mp.`source_blog_id`, mp.`expiration_date`, mp.`status`, mp.`is_lifetime` "
				. "FROM `{$wpdb->blogs}` b INNER JOIN "
				. " ( SELECT `blog_id`, `email`, `source_blog_id`, `expiration_date`, `status`, `is_lifetime` FROM `"
				. $table['sandboxes'] . "` s INNER JOIN `" . $table['users'] . "` u on s.user_id=u.user_id ) mp ON b.`blog_id` = mp.`blog_id`";

		$where = '';
		$and = '';

		if(isset($_POST['s']) ||  isset($_GET['status'])){
			$where = " WHERE";
		}

		if(isset($_POST['s'])) {
			$where .= " mp.`email` LIKE '%" . $_POST['s'] . "%' ";
			$and = " AND ";
		}

		if( isset($_GET['status']) ){
			$where .= $and . " mp.`status` LIKE '" . $_GET['status'] . "' ";
		}

		$sql .= $where;

		if (!empty($_REQUEST['orderby'])) {
			$sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
			$sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
		} else {
			$sql .= ' ORDER BY b.`registered` DESC';
		}


		$sql .= " LIMIT $per_page";
		$sql .= ' OFFSET ' . ($page_number - 1) * $per_page;
		$blogs = $wpdb->get_results($sql, 'ARRAY_A');

		return $blogs;
	}

	public function get_blog_db_table_list($blog_id) {
		global $wpdb;

		$tables = array();
		$target_pre = $wpdb->base_prefix . $blog_id . '_';
		$query = 'SHOW TABLES LIKE \'' . str_replace('_', '\_', $target_pre) . '%\'';
		$tables_list = $wpdb->get_results($query, ARRAY_N);

		foreach ($tables_list as $tbl_name) {
			$tables[] = $tbl_name[0];
		}

		return $tables;
	}

	// Catch WP Actions

	public function wp_action_archive_blog($blog_id) {
		Sandbox_DAO::get_instance()->archive_blog($blog_id);

		return $blog_id;
	}

	public function wp_action_unarchive_blog($blog_id) {
		Sandbox_DAO::get_instance()->archive_blog($blog_id, true);

		return $blog_id;
	}

	public function wp_action_deactivate_blog($blog_id) {
		Sandbox_DAO::get_instance()->activate_blog($blog_id, true);

		return $blog_id;
	}

	public function wp_action_activate_blog($blog_id) {
		Sandbox_DAO::get_instance()->activate_blog($blog_id);

		return $blog_id;
	}

	public function wp_action_delete_blog($blog_id) {
		Sandbox_DAO::get_instance()->remove_sandbox($blog_id);

		return $blog_id;
	}

	/**
	 * Get the uploads folder for the target site
	 */
	public function get_upload_folder($id) {
		switch_to_blog($id);
		$src_upload_dir = wp_upload_dir();
		restore_current_blog();
		$this->log->dlog('Original basedir returned by wp_upload_dir() = <strong>' . $src_upload_dir['basedir'] . '</strong><br>');
		$folder = str_replace('/files', '', $src_upload_dir['basedir']);
		$content_dir = '';
		if ($id != 1 && (strpos($folder, '/' . $id) === false || !file_exists($folder))) {

			$content_dir = WP_CONTENT_DIR; //no trailing slash
			// check for WP < 3.5 location
			$test_dir = $content_dir . '/blogs.dir/' . $id;
			if (file_exists($test_dir)) {
				return $test_dir;
			}
			// check for WP >= 3.5 location
			$test_dir = $content_dir . '/uploads/sites/' . $id;
			if (file_exists($test_dir)) {
				return $test_dir;
			}
		}

		return $folder;
	}

	/**
	 * Get the folder size including subfolders
	 *
	 * @param $dir
	 * @return int
	 */
	function get_folder_size($dir) {
		$size = 0;
		foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
			$size += is_file($each) ? filesize($each) : $this->get_folder_size($each);
		}
		return $size;
	}

	/**
	 * Copy files and directories recursively and return number of copies executed.
	 */
	public function recursive_file_copy($src, $dst, $num) {
		$num = $num + 1;
		if (is_dir($src)) {
			if (!file_exists($dst)) {
				global $wp_filesystem;
				if (empty ($wp_filesystem)) {
					$admin = Core::get_instance()->get_last_subfolder(admin_url(), '/wp-admin/');
					require_once(MP_DEMO_ABSPATH . $admin . 'includes/file.php');
					WP_Filesystem();
				}
				mkdir($dst, 0777, true);
			}
			$files = scandir($src);
			foreach ($files as $file)
				if ($file != "." && $file != ".." && !in_array($file, $this->folders_are_not_copied)) {
					$num = $this->recursive_file_copy("$src/$file", "$dst/$file", $num);
				}
		} else if (file_exists($src)) {
			copy($src, $dst);
		}

		return $num;
	}

	/**
	 * Better addslashes for SQL queries. Example from phpMyAdmin.
	 */
	private function sql_addslashes($a_string = '', $is_like = FALSE) {

		if ($is_like) {
			$a_string = str_replace('\\', '\\\\\\\\', $a_string);
		} else {
			$a_string = str_replace('\\', '\\\\', $a_string);
		}
		$a_string = str_replace('\'', '\\\'', $a_string);

		return $a_string;
	}

	/**
	 * Add backqouotes to tables and db-names in SQL queries. Example from phpMyAdmin.
	 */
	private function backquote($a_name) {

		if (!empty($a_name) && $a_name != '*') {
			if (is_array($a_name)) {
				$result = array();
				reset($a_name);
				while (list($key, $val) = each($a_name)) {
					$result[$key] = '`' . $val . '`';
				}
				return $result;
			} else {
				return '`' . $a_name . '`';
			}
		}

		return $a_name;
	}

}