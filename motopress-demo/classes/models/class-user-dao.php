<?php
/**
 * class Sandbox_DAO
 */

namespace motopress_demo\classes\models;

use motopress_demo\classes\libs\MP_Demo_Logs;
use motopress_demo\classes\Model;

class User_DAO extends Model {

	private $table;
	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		global $wpdb;

		$this->wpdb = $wpdb;
		$this->log = new MP_Demo_Logs();
		$tables = \Motopress_Demo::get_tables_names();
		$this->table = $tables['users'];
	}

	/*
	 *  Insert new email with $data
	 */
	public function insert_data($data) {

		$result = $this->wpdb->insert(
				$this->table, $data
		);

		return $this->wpdb->insert_id;
	}

	/*
	 * Set activated in row where `secret` = $secret
	 */
	public function activate_email($user_id) {

		$result = $this->wpdb->update(
				$this->table,
				array('is_valid' => 1),
				array('user_id' => $user_id)
		);

		return $result;
	}

	public function get_data($key, $value) {
		$result = $this->wpdb->get_row(
			"SELECT * FROM `" . $this->table . "` WHERE `{$key}`={$value}",
			ARRAY_A
		);

		return $result;
	}

	/*
	 * Update fields, remember modified date, inc demos quantity
	 */
	public function update_data($key, $value, $data) {
		$result = $this->wpdb->update(
				$this->table,
				$data,
				array(
						$key => $value
				)
		);

		return $result;
	}

	/*
	 * @returns true if mail's hash is in db
	 */
	public function mail_exists($mail) {
		$user_id = $this->wpdb->get_var(
				$this->wpdb->prepare("SELECT `user_id` FROM `" . $this->table . "` WHERE `email` LIKE '%s'", $mail)
		);

		return is_null($user_id) ? 0 : $user_id;
	}

}