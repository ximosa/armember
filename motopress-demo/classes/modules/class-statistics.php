<?php
/**
 *  Statistics class
 * This class handles the output statistics
 */

namespace motopress_demo\classes\modules;


use DateInterval;
use DatePeriod;
use DateTime;
use motopress_demo\classes\Module;

class Statistics extends Module {

	protected static $instance;
	private $defaultDateStart = '';
	private $defaultDateEnd = '';
	private $mp_demo_users = '';
	private $mp_demo_sandboxes = '';
	private $wp_users = '';
	private $mp_demo_statistic = '';
	private $wp_blogs = '';

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		global $wpdb;
		$this->wp_users = $wpdb->prefix.'users';

		if(is_plugin_active( 'arformstestdrive/arformstestdrive.php' )){
			$this->mp_demo_statistic = $wpdb->base_prefix.'mp_demo_arforms_statistic';	
		}
		if(is_plugin_active( 'armembertestdrive/armembertestdrive.php' )){
			$this->mp_demo_statistic = $wpdb->base_prefix.'mp_demo_armember_statistic';	
		}
		
		$this->wp_blogs = $wpdb->prefix.'blogs';

		$this->mailManager = $this->get_model('Sandbox_DAO');
		$this->defaultDateEnd = date("Y-m-d"); //  today
		$this->defaultDateStart = date("Y-m-d", strtotime('-6 days')); // last week
	}

	/**
	 * Output our network admin page
	 *
	 * @access public
	 * @return void
	 */
	public function render_tabs() {

		$tabs = apply_filters('mp_demo_statistics_tabs', array(
				'list' => array(
					'label' => __('Statistics', 'mp-demo'),
					'priority' => 0,
					'callback' => array(Statistics::get_instance(), 'render_table')
				),
			)
		);
		$curTabId = isset($_GET['tab']) ? $_GET['tab'] : 'list';

		Settings::get_instance()->enqueue_scripts();

		$this->get_view()->render_html("admin/menu-tabs", array('tabs' => $tabs, 'curTabId' => $curTabId), true);
	}

	public function render_table() {
		global $wpdb , $wp_query;

		$start = $this->getDate('mp-demo-start', $this->defaultDateStart);
		$end = $this->getDate('mp-demo-end', $this->defaultDateEnd);

		$table = $this->getTable($start, $end);
		$total = $this->get_total($start, $end);

		$currentPage = isset($_REQUEST['offset']) ? $_REQUEST['offset'] : 1;
		if( $currentPage < 1 ){
			$currentPage = 1;
		}

		$pagination = "";
		$perPage = 10;

		if( !function_exists('is_plugin_active') ){
			require_once ABSPATH.'/wp-includes/plugins.php';
		}
		if( is_plugin_active('arformstestdrive/arformstestdrive.php') ){
			$testdrive_table = $wpdb->base_prefix.'mp_demo_arforms_statistic';
		} else {
			$testdrive_table = $wpdb->base_prefix.'mp_demo_armember_statistic';
		}

		$offset = ($currentPage - 1) * $perPage;

		$limit = 'LIMIT '.$offset.','.$perPage;

		$start_date = $start." 00:00:00";
		$end_date = $end." 23:59:59";

		$total_records = $wpdb->get_var( "SELECT count(*) as total FROM `".$testdrive_table."` WHERE created_date BETWEEN '".$start_date."' AND '".$end_date."'" );

		$total_pages = ceil($total_records/$perPage);
		
		$statistic_data = $wpdb->get_results( "SELECT * FROM `".$testdrive_table."` WHERE created_date BETWEEN '".$start_date."' AND '".$end_date."' ORDER BY user_id DESC $limit" );

    
		if($total_records > 0){
			
			$page = ceil($total_records / $perPage);
			$max=5;

			if(($currentPage-3)>0)
				$pagination .= "<a class='button' href='".$_SERVER['REQUEST_URI']."&offset=".($currentPage - 1)."'><<</a>&nbsp;";

			if(($currentPage-3) < 0)
			{
				for($i=1; $i<=$max; $i++){
					if($i<=$page && $i>0)
					{
						$selected="";
						if($i==$currentPage)
							$selected="selected";
						else
							$selected="";
						$pagination.="<a class='button ".$selected."' href='".$_SERVER['REQUEST_URI']."&offset=".$i."'>".$i."</a>&nbsp;";
					}
				}
			}
			else
			{
				for($i=($currentPage-2); $i<=($currentPage+2); $i++){
					if($i<=$page && $i>0)
					{

						$selected="";
						if($i==$currentPage)
							$selected="selected";
						else
							$selected="";
						$pagination.="<a class='button ".$selected."' href='".$_SERVER['REQUEST_URI']."&offset=".$i."'>".$i."</a>&nbsp;";
					}
				}
			}

			if(($currentPage+2)<$page && $max<$page)
				$pagination .= "<a class='button' href='".$_SERVER['REQUEST_URI']."&offset=".($currentPage + 1)."'>>></a>";
		}    	

		$this->get_view()->render_html("admin/statistics", array('total'=>$total, 'table'=>$table, 'statistic_data'=>$statistic_data, 'pagination'=>$pagination), true);
	}


	private function getTable($start, $end) {
		$table = array(); // date , created , activated

		$rows = $this->mailManager->get_list_between($start, $end);

		// Requires PHP5.3:
		$begin = new DateTime($start);
		$interval = DateInterval::createFromDateString('1 day');
		$end = new DateTime($end);
		$end->add($interval);

		$period = new DatePeriod($begin, $interval, $end);

		foreach ($period as $dt) {
			$table[$dt->format("Y_m_d")] = array('date' => $dt->format("Y-m-d"), 'created' => 0, 'activated' => 0);
		}

		// NOW FILL TABLE
		foreach ($rows['created'] as $item) {
			$table_key = str_replace('-', '_', $item['date']);
			$table[$table_key]['created'] = $item['created'];
		}
		foreach ($rows['activated'] as $item) {
			$table_key = str_replace('-', '_', $item['date']);
			$table[$table_key]['activated'] = $item['activated'];
		}

		return $table;
	}

	private function get_total($start, $end) {
		$total = array(); // start , end, created , activated

		$created = $this->mailManager->get_count_created_between($start, $end);
		$activated = $this->mailManager->get_count_activated_between($start, $end);

		$total = array('start' => $start, 'end' => $end, 'created' => $created, 'activated' => $activated);

		return $total;
	}

	/**
	 * @param $date_param_name string $_GET index
	 * @param $default string date
	 *
	 * @return string date
	 */
	public function getDate($date_param_name, $default) {
		$regex = "/^(19|20)\d\d[\-.](0[1-9]|1[012])[\-.](0[1-9]|[12][0-9]|3[01])$/";

		if (!empty($_GET[$date_param_name])) {
			$date = $_GET[$date_param_name];

			return  preg_match($regex,$date) ? $date : $default;
		}

		return $default;
	}

}
