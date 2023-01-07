<?php
namespace motopress_demo\classes\modules;

use Motopress_Demo;
use motopress_demo\classes\Core;
use motopress_demo\classes\models\General_Settings;
use motopress_demo\classes\models\Sandbox;

class Back_Compatibility {

	var $wpdb;
	var $is_suitable_site;
	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;
		$this->is_suitable_site = Motopress_Demo::is_suitable_site();
	}

	/**
	 * Display Upgrade Notices
	 *
	 * @return void
	 */
	function show_upgrade_notices() {
		if (!$this->is_suitable_site) {
			return;
		}

		$mp_version = $this->get_plugin_version();
		$version_exists = $mp_version;

		if (!$mp_version) {
			$mp_version = '1.1';
		}

		$mp_version = preg_replace('/[^0-9.].*/', '', $mp_version);

		// v1.2 check first install
		$old_tables = array(
				'mpdemo_data' => $this->v11_get_demo_table()
		);
		$v11_table_exists = $this->table_exists($old_tables['mpdemo_data']) ? true : false;
		if (version_compare( $mp_version, '1.2', '<' ) && !$version_exists && $v11_table_exists) {

			// The db update for version 1.2
			$url = add_query_arg('mp-demo-action', 'trigger_upgrades');
			$upgrade_notice = sprintf(__('Demo Builder needs to complete a database upgrade. Click <a id="mp-demo-upgrate-database" href="%s">here</a> to start the upgrade.', 'mp-demo'),  wp_nonce_url($url, 'edd_upgrade_payments_nonce'));
			add_settings_error('mp-demo-notices', 'mp-demo-upgrade-database', $upgrade_notice, 'error');
		}

		settings_errors( 'mp-demo-notices' );
	}

	/**
	 * Triggers all upgrade functions
	 *
	 * This function is usually triggered via AJAX
	 *
	 * @since 1.2
	 * @return void
	 */
	function trigger_upgrades() {

		$mp_version = $this->get_plugin_version();
		$current_version = Core::get_version();

		if (!$mp_version) {
			// 1.1.4 is the first version to use this option so we must add it
			$mp_version = '1.1';
		    $this->add_plugin_version($mp_version);
		}

		$mp_version = preg_replace('/[^0-9.].*/', '', $mp_version);
		$current_version = preg_replace('/[^0-9.].*/', '', $current_version);

		if (version_compare($mp_version, $current_version, '=')) {
			return false;
		}

		if (version_compare($mp_version, '1.2', '<')) {
			$this->v12_db_upgrades();
		}

		$this->update_plugin_version($current_version);

		return true;
	}

	function table_exists($tbl_name) {
		$tbl_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$tbl_name}'") == $tbl_name;

		return $tbl_exists;
	}

	function delete_blogs($blogs_ids, $drop = true) {
		foreach ($blogs_ids as $blog_id) {
			if ($blog_id == 0 || MP_DEMO_MAIN_BLOG_ID){
				continue;
			}

			Sandbox::get_instance()->delete($blog_id, $drop);
		}
	}

	function drop_tables($tables_name) {
		foreach ($tables_name as $name) {
			$this->wpdb->query("DROP TABLE IF EXISTS {$name}");
		}
	}

	function v12_db_upgrades() {

		if (!$this->is_suitable_site) {
			return false;
		}

		$old_tables = array(
				'mpdemo_data' => $this->v11_get_demo_table()
		);

		$new_tables = array(
				'users' =>$this->wpdb->prefix . 'mp_demo_users',
				'sandboxes' => $this->wpdb->prefix . 'mp_demo_sandboxes'
		);

		// DO UPGRADE
		$this->v12_migrate_tables($old_tables, $new_tables);

		return true;
	}

	function v11_get_demo_table() {

		if ($this->is_suitable_site) {
			switch_to_blog(1);
			$name = $this->wpdb->prefix . 'mpdemo_data';
			restore_current_blog();
		} else {
			$name = $this->wpdb->prefix . 'mpdemo_data';
		}

		return $name;
	}

	function v12_is_empty_table($table) {
		$old_table_rows = $this->wpdb->get_results(
				"SELECT * FROM " . $table,
				ARRAY_A);

		return $old_table_rows == false;
	}

	function v12_migrate_tables($old_tables_name, $new_tables_names) {

		$old_tbl_name = $old_tables_name['mpdemo_data'];

		if (!$this->table_exists($new_tables_names['users']) || !$this->table_exists($new_tables_names['sandboxes'])) {

			Motopress_Demo::create_tables();
		}

		if (!$this->v12_is_empty_table($new_tables_names['sandboxes'])) {
			return 0;
		}

		//Start migration
		$sql = "";
		$query_count = apply_filters('mp_demo_query_count', 4);
		$query = "SELECT * FROM " . $old_tbl_name;
		$old_table_rows = $this->wpdb->get_results($query, ARRAY_A);
		$cur_time = current_time('mysql');
		$blogs_to_delete = array();
		$options = General_Settings::get_instance()->get_options();
		$lifespan = $options['lifespan'];
		$is_lifetime = $options['is_lifetime'] ? 1 : 0;

		if ($old_table_rows == false) {
			return 0;
		}

		// query part
		$query_user_part = "INSERT INTO `{$new_tables_names['users']}` (`email`, `is_valid`, `password`) VALUES ";
		$query_sand_part = "INSERT INTO `{$new_tables_names['sandboxes']}` (`blog_id`, `source_blog_id`, `user_id`, `status`, `secret`, `site_url`, `creation_date`, `activation_date`, `expiration_date`, `is_lifetime`) VALUES ";

		$current_sand_query = '';

		foreach ($old_table_rows as $row_number => $old_table_row) {
			$current_user_query = " ('"
					. $old_table_row['email'] . "', "
					. $old_table_row['activated'] . ", '"
					. Core::get_instance()->encode_pass($old_table_row['password'])
					. "')";
			$result = $this->wpdb->query($query_user_part . $current_user_query);
			$user_id = $this->wpdb->insert_id;

			// Sandboxe values
			$modified_time = strtotime($old_table_row['date_modified']);

			if ($old_table_row['activated'] != 1) {
				$old_table_row['date_modified'] = MP_DEMO_EMPTY_DATE;
				$expiration_time = MP_DEMO_EMPTY_DATE;
				$status = MP_DEMO_STATUS_PENDING;
			} else {
				$expiration_time = date("Y-m-d H:i:s", ($modified_time + $lifespan));

				if ($expiration_time <= $cur_time) {
					$status = MP_DEMO_STATUS_DELETED;
					$blogs_to_delete[] = $old_table_row['target_id'];
				} else {
					$status = ($old_table_row['target_id']) ? MP_DEMO_STATUS_ACTIVE : MP_DEMO_STATUS_PENDING;
				}

			}

			if ($status === MP_DEMO_STATUS_PENDING
			|| $old_table_row['activated'] != 1) {
				$old_table_row['date_modified'] = MP_DEMO_EMPTY_DATE;
				$expiration_time = MP_DEMO_EMPTY_DATE;
			}

			$current_sand_query .= " ("
				. $old_table_row['target_id'] . ", "
				. $old_table_row['source_id'] . ", "
				. $user_id . ", "
				. "'" . $status . "', "
				. "'" . $old_table_row['secret'] . "', "
				. "'" . $old_table_row['siteurl'] . "', "
				. "'" . $old_table_row['date_created'] . "', "
				. "'" . $old_table_row['date_modified'] . "', "
				. "'" . $expiration_time . "', "
				. "{$is_lifetime}"
				. "),";

			if (($row_number % $query_count) == 0) {
				$result = $this->wpdb->query($query_sand_part . rtrim($current_sand_query, ','));
				$current_sand_query = '';
			}
		}

		if ($current_sand_query) {
			$result = $this->wpdb->query($query_sand_part . rtrim($current_sand_query, ','));
		}

		$this->delete_blogs($blogs_to_delete);

		return 1;
	}

	function get_plugin_version() {
		$version = get_blog_option(MP_DEMO_MAIN_BLOG_ID, 'mp_demo_version');

		return $version;
	}

	function add_plugin_version($mp_version) {
		$added = add_blog_option(MP_DEMO_MAIN_BLOG_ID,'mp_demo_version', $mp_version);

		return $added;
	}

	function update_plugin_version($mp_version) {
		$updat = update_blog_option(MP_DEMO_MAIN_BLOG_ID,'mp_demo_version', $mp_version);

		return $updat;
	}


}
