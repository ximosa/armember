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

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		global $wpdb;
		$this->wp_users = $wpdb->prefix.'users';
		$this->mp_demo_users = $wpdb->prefix.'mp_demo_users';
		$this->mp_demo_sandboxes = $wpdb->prefix.'mp_demo_sandboxes';
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

		//$user = get_userdatabylogin($login);
    	//$curent_login_time = get_user_meta( $user->ID , 'current_login', true);
		$perPage = 10;
		$offset = (isset($_GET['offset']))?(($_GET['offset'])-1)*$perPage:"0";

		$res = $wpdb->get_row("SELECT COUNT(ID) count FROM ".$this->wp_users);
		$cnt = $res->count;
	
    	$data = array();
    	
    	// user_login,ID
    	$wp_user_list = $wpdb->get_results("SELECT * FROM ".$this->wp_users." ORDER BY ID DESC LIMIT $offset,$perPage");
    	$pagination = '';

    	if(!empty($wp_user_list)){

    		$i = 0;
    		foreach ($wp_user_list as $wp_user) {

    			$user_meta=get_user_meta( $wp_user->ID, 'wp_capabilities');
				if(isset($user_meta[0]['administrator'])){
					continue;
				}
				$user_roles=$user_meta->roles;

    			$data[$i]['id']=$wp_user->ID;
    			$data[$i]['user_login']=$wp_user->user_login;
    			$mpdemo_user = $wpdb->get_row($wpdb->prepare('SELECT user_id,email FROM '.$this->mp_demo_users.' WHERE is_valid=1 AND wp_user_id=%d', $wp_user->ID));
				if(!empty($mpdemo_user)){
				
					$sandboxes = $wpdb->get_results($wpdb->prepare("SELECT site_url FROM ".$this->mp_demo_sandboxes." WHERE user_id=%d AND status='active'", $mpdemo_user->user_id));
					if(!empty($sandboxes)){
						
						$site_arr = array();
						foreach ($sandboxes as $site) {
							
							$login_time = maybe_unserialize(get_user_meta( $wp_user->ID, 'mp_demo_last_login', true ));
							$logout_time = maybe_unserialize(get_user_meta( $wp_user->ID, 'mp_demo_heartbeat_time', true ));
							
							$session_time="";
							if(!empty($logout_time) && !empty($login_time)){
								$datetime1 = new DateTime($login_time['login_time'][1]);
								$datetime2 = new DateTime($logout_time['session_time'][1]);
								$interval = $datetime1->diff($datetime2);
								$session_time = $interval->format('%H:%i:%s');	
							}
							

							//$site_arr[$site->site_url] = array('login_time'=>$login_time['login_time'][1],'logout_time'=>$logout_time['logout_time'][1]);
							$tmp_arr = array('blog_url'=>$site->site_url,'session_time'=>$session_time);
							array_push($site_arr, $tmp_arr);
						}
						$data[$i]['blogs'] = $site_arr;
					}
					//echo "<pre>";print_r($sandboxes);
				}
				$i++;
    		}
    		
    		//echo "<pre>";print_r($data);die();
    		$page = ceil($cnt / $perPage);
			$max=5;

			$currentPage=(isset($_GET['offset']))?$_GET['offset']:"1";

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

			if(($currentPage+2)<$page)
				$pagination .= "<a class='button' href='".$_SERVER['REQUEST_URI']."&offset=".($currentPage + 1)."'>>></a>";

			
			//echo $pagination;die();
    	}
    	//echo "<pre>";print_r($data);die();

		$this->get_view()->render_html("admin/statistics", array('total'=>$total, 'table'=>$table, 'user_data'=>$data, 'pagination'=>$pagination), true);
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
