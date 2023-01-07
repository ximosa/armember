<?php

/**

 * class Sandbox_DAO

 */



namespace motopress_demo\classes\models;



use motopress_demo\classes\libs\MP_Demo_Logs;

use motopress_demo\classes\Model;



class Sandbox_DAO extends Model {



	private $table;

	private $user_table;

	private $options;

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

		$this->table = $tables['sandboxes'];

		$this->user_table = $tables['users'];

		//FIXME lifespan

		$this->expiration_duration = General_Settings::get_instance()->get_option('expiration_duration');

		$this->expiration_measure = General_Settings::get_instance()->get_option('expiration_measure');

		$this->options = General_Settings::get_instance()->get_options();

	}



	/**

	 * @param $args = array()

	 */

	public function get_mail_body($args) {

		$default = array(

			'{email}' => 'test@email.com',

			'{site_title}' => '',

			'{login}' => 'test@email.com',

			'{password}' => 'password',

			'{demo_url}' => 'demourl',

			'{demo_lifetime}' => $this->options['lifespan'] / 60 / 60,

			'{demo_lifetime_days}' => floor($this->options['lifespan'] / 60 / 60 / 24),



			'{demo_duration_value}' => $this->options['expiration_duration'],

			'{demo_duration_period}' => $this->options['expiration_measure'],

		);

		if (!isset($args['body'])) return false;



		$args = array_merge($default, $args);



		$body = $args['body'];

		unset($args['body']);



		foreach ($args as $key => $value) {

			$body = str_replace($key, $value, $body);

		}



		$body = stripslashes($body);



		return \apply_filters('the_content', $body);

	}



	/*

	 *  Insert new email with $data

	 */

	public function insert_data($data) {

		if (empty($data['source_blog_id']))

			return false;



		$result = $this->wpdb->insert(

			$this->table, $data

		);



		return $this->wpdb->insert_id;

	}



	/**

	 * Clean links on sandboxes

	 */

	public function refresh_all() {

		$sql = "UPDATE " . $this->table

			. " SET `site_url`='', `blog_id`=0";



		$rows = $this->wpdb->get_results($sql);



		return $rows;

	}



	public function get_data($key, $value) {

		$result = $this->wpdb->get_row(

			"SELECT * FROM `{$this->table}` WHERE `{$key}` = {$value}",

			ARRAY_A

		);



		return $result;

	}



	public function get_blog_source($blog_id) {

		$results = $this->wpdb->get_row(

			$this->wpdb->prepare("SELECT `source_blog_id` FROM `" . $this->table . "` WHERE `blog_id` = %s", $blog_id),

			ARRAY_A

		);



		return $results['source_blog_id'];

	}



	public function get_sandbox_by_params($params) {

		$where = '';



		foreach ($params as $key => $param) {

			$where .= " `{$key}` = {$param} AND";

		}



		$where = rtrim($where, 'AND');



		$result = $this->wpdb->get_row(

			"SELECT * FROM `{$this->table}` WHERE {$where} ORDER BY CASE `status`"

			. " WHEN '" . MP_DEMO_STATUS_PENDING . "' THEN 1"

			. " WHEN '" . MP_DEMO_STATUS_ACTIVE . "' THEN 2"

			. " ELSE 3"

			. " END",

			ARRAY_A

		);



		return is_null($result) ? 0 : $result;

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

	public function secret_exists($secret) {

		$count = $this->wpdb->get_var(

			$this->wpdb->prepare("SELECT COUNT(*) FROM `" . $this->table . "` WHERE `secret` LIKE '%s'", $secret)

		);



		return $count > 0;

	}



	/*

	 * @returns {Secret} of the mail

	 */

	public function mails_secret($mail) {

		$results = $this->wpdb->get_row(

			$this->wpdb->prepare("SELECT `secret` FROM `" . $this->table . "` WHERE `email` = %s", $mail),

			ARRAY_A

		);



		return $results['secret'];

	}



	/*

	 * @returns {true} is expired

	 */

	public function is_expired($key, $value) {



		$cur_time = current_time('mysql');

		$count = $this->wpdb->get_var(

			$this->wpdb->prepare("SELECT COUNT(*) FROM `" . $this->table . "`"

				. " WHERE (`status` IN ('" . MP_DEMO_STATUS_DELETED . "')"

				. " AND `{$key}` LIKE '%s' )"

				. " OR (`status` LIKE '" . MP_DEMO_STATUS_ACTIVE . "'"

				. " AND `{$key}` LIKE '%s' "

				. " AND `expiration_date` < '%s')",

				$value,

				$value,

				$cur_time

			)

		);



		return $count > 0;

	}



	/*

	 * @returns {true} if date difference bigger than MPND_SANDBOX_LIFESPAN (sec)

	 */

	public function is_lifetime($key, $value) {



		$cur_time = current_time('mysql');

		$count = $this->wpdb->get_var(

			$this->wpdb->prepare("SELECT COUNT(*) FROM `" . $this->table . "`"

				. " WHERE `is_lifetime`=1 "

				. " AND `{$key}` LIKE '%s'",

				$value,

				$cur_time

			)

		);



		return $count > 0;

	}



	public function is_active_sandbox($secret) {

		$blogs = $this->wpdb->blogs;

		/* //find actual sandboxes

				$sql = "SELECT b.blog_id "

				. " FROM " . $blogs . " b JOIN " . $this->table . " m"

				. " ON m.siteurl LIKE CONCAT('%',b.path,'%')"

				. " WHERE b.blog_id <> 1";

		*/

		$sql = "SELECT b.blog_id "

			. " FROM " . $blogs . " b JOIN " . $this->table . " m"

			. " ON m.site_url LIKE CONCAT('%',b.path,'%')"

			. " WHERE (m.secret LIKE '" . $secret . "' AND m.status LIKE '" . MP_DEMO_STATUS_ACTIVE . "')";



		$blogs = $this->wpdb->get_results($sql);



		if ($blogs)

			return true;



		return false;

	}



	public function count_sandboxes($source_id = '') {

		if (is_int($source_id)) {

			$source_id = " AND (`source_blog_id` = {$source_id})";

		}



		$count = $this->wpdb->get_var("SELECT COUNT(*) FROM `" . $this->table . "`"

			. " WHERE (`status` NOT LIKE 'deleted')  AND  (`status` NOT LIKE 'pending') $source_id"

		);



		return $count;

	}



	/**

	 * @return bool purge_sandboxes

	 */

	public function purge_sandboxes($blog_id = '') {

		$blog_id = ($blog_id == '' || $blog_id == 0) ? '' : " AND `blog_id` = {$blog_id}";

		$expiration_action = $this->options['expiration_action'];



		$sql = "SELECT `blog_id`"

			. " FROM `" . $this->table . "` "

			. " WHERE (`expiration_date` < '%s' "

			. " AND `status`='" . MP_DEMO_STATUS_ACTIVE . "'"

			. $blog_id

			. " AND `blog_id`<>0"

			. " AND (`is_lifetime`<>1 OR `is_lifetime` IS NULL)"

			. " )";

		$blogs = $this->purge_sandboxes_get_query_results($sql);



		if (!is_array($blogs) || empty($blogs)) {

			return false;

		}



		switch ($expiration_action) {

			case MP_DEMO_ACTION_DELETE :

				ob_start();

				foreach ($blogs as $blog) {

					$this->get_model('Sandbox')->delete($blog['blog_id']);

				}

				$output = ob_get_clean();

				break;

			case MP_DEMO_ACTION_ARCHIVE :

			case MP_DEMO_ACTION_DEACTIVATE :

				$status = ($expiration_action === MP_DEMO_ACTION_ARCHIVE) ? MP_DEMO_STATUS_ARCHIVED : 'deleted';

				$wp_action = ($expiration_action === MP_DEMO_ACTION_ARCHIVE) ? 'archive_blog' : 'deactivate_blog';



				foreach ($blogs as $blog) {

					do_action($wp_action, $blog['blog_id']);

					update_blog_status($blog['blog_id'], $status, 1);

				}

				break;

		}



		return true;

	}



	public function purge_sandboxes_get_query_results($sql) {

		$blogs = $this->wpdb->get_results(

			$this->wpdb->prepare(

				$sql,

				current_time('mysql')

			),

			ARRAY_A

		);



		return $blogs;

	}



	public function remove_sandbox($blog_id) {

		$result = $this->wpdb->update(

			$this->table,

			array(

				'status' => MP_DEMO_STATUS_DELETED,

				'is_lifetime' => 0,

				'site_url' => '',

				'blog_id' => 0,

			),

			array('blog_id' => $blog_id)

		);



		return $result;

	}



	public function archive_blog($blog_id, $unarchive = false) {

		$status = $unarchive ? MP_DEMO_STATUS_ACTIVE : MP_DEMO_STATUS_ARCHIVED;

		$result = $this->wpdb->update(

			$this->table,

			array('status' => $status),

			array('blog_id' => $blog_id)

		);



		return $result;

	}



	public function activate_blog($blog_id, $deactivate = false) {

		$status = $deactivate ? MP_DEMO_STATUS_DEACTIVATED : MP_DEMO_STATUS_ACTIVE;

		$result = $this->wpdb->update(

			$this->table,

			array('status' => $status),

			array('blog_id' => $blog_id)

		);



		return $result;

	}



	/**

	 * @param $dateStartStr

	 * @param $dateEndStr

	 *

	 * @return int

	 */

	public function get_count_activated_between($dateStartStr, $dateEndStr) {

		$sql = "SELECT COUNT(*) from " . $this->table

			. " WHERE (CAST(`activation_date` AS DATE) BETWEEN '" . $dateStartStr . "' AND '" . $dateEndStr . "') "

			. " AND (`status` NOT LIKE '" . MP_DEMO_STATUS_PENDING . "')";

		$rows = $this->wpdb->get_var($sql);



		return intval($rows);

	}



	/**

	 * for admin.php

	 *

	 * @param $dateStartStr

	 * @param $dateEndStr

	 *

	 * @return int

	 */

	public function get_count_created_between($dateStartStr, $dateEndStr) {

		$sql = "SELECT COUNT(*) FROM " . $this->table

			. " WHERE `status`!='".MP_DEMO_STATUS_PENDING."' AND (CAST(`creation_date` AS DATE) BETWEEN '" . $dateStartStr . "' AND '" . $dateEndStr . "') ";



		$rows = $this->wpdb->get_var($sql);



		return intval($rows);

	}



	/**

	 * Get rows with creation date and count created

	 *

	 * @param $dateStartStr

	 * @param $dateEndStr

	 *

	 * @return mixed

	 */

	public function get_list_between($dateStartStr, $dateEndStr) {



		$sql_created = "SELECT * FROM ( "

			. " SELECT DATE(t.`creation_date`) as date, COUNT( t.`sandbox_id` ) AS created "

			. " FROM `" . $this->table . "` t "

			. " WHERE t.`status` NOT LIKE '".MP_DEMO_STATUS_PENDING."' AND (CAST(t.`creation_date` AS DATE) BETWEEN '" . $dateStartStr . "' AND '" . $dateEndStr . "')"

			. " GROUP BY DATE(t.`creation_date`)"

			. " ORDER BY t.`creation_date`"

			. " ) as tbl ";



		$sql_activated = "SELECT * FROM ( "

			. " SELECT DATE(t.`activation_date`) as date, COUNT( t.`sandbox_id` ) AS activated "

			. " FROM `" . $this->table . "` t "

			. " WHERE (CAST(t.`activation_date` AS DATE) BETWEEN '" . $dateStartStr . "' AND '" . $dateEndStr . "')"

			. " AND t.`status` NOT LIKE '".MP_DEMO_STATUS_DELETED."' AND (t.`status` NOT LIKE '" . MP_DEMO_STATUS_PENDING . "')"

			. " GROUP BY DATE(t.`activation_date`)"

			. " ORDER BY t.`activation_date`"

			. " ) as tbl ";



		$created = $this->wpdb->get_results($sql_created, ARRAY_A);

		$activated = $this->wpdb->get_results($sql_activated, ARRAY_A);



		return array(

			'created' => $created,

			'activated' => $activated

		);

	}



}