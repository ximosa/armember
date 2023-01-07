<?php
@error_reporting(E_ERROR | E_WARNING | E_PARSE);
//@error_reporting(E_ALL);
/*
Plugin Name: ARMember - Affiliate PRO Addon
Description: Extension for ARMember plugin to integration with AffiliatePRO
Version: 1.3
Plugin URI: https://www.armemberplugin.com
Author: Repute InfoSystems
Author URI: https://www.armemberplugin.com
*/

define('ARM_AFFILIATEPRO_DIR_NAME', 'armemberaffiliatepro');
define('ARM_AFFILIATEPRO_DIR', WP_PLUGIN_DIR . '/' . ARM_AFFILIATEPRO_DIR_NAME);

if (is_ssl()) {
    define('ARM_AFFILIATEPRO_URL', str_replace('http://', 'https://', WP_PLUGIN_URL . '/' . ARM_AFFILIATEPRO_DIR_NAME));
} else {
    define('ARM_AFFILIATEPRO_URL', WP_PLUGIN_URL . '/' . ARM_AFFILIATEPRO_DIR_NAME);
}

define('ARM_AFFILIATEPRO_TEXTDOMAIN','ARM_AFFILIATEPRO');

global $arm_affiliatepro_version;
$arm_affiliatepro_version = '1.3';

global $armaffiliatepro_api_url, $armaffiliatepro_plugin_slug, $wp_version;

if (!class_exists('ARM_Affiliatepro'))
{
    class ARM_Affiliatepro
    {
        var $affiliate_dir;
        var $affiliate_file;
        var $affiliate_service_file;
        var $affiliate_pro_dir;
        var $affiliate_pro_file;
        var $affiliate_pro_service_file;
        function __construct(){
            
            $this->affiliate_dir = 'affiliates/';
            $this->affiliate_file = 'affiliates.php';
            $this->affiliate_service_file = 'lib/core/class-affiliates-service.php';
            $this->affiliate_pro_dir = 'affiliates-pro/';
            $this->affiliate_pro_file = 'affiliates-pro.php';
            $this->affiliate_pro_service_file = 'lib/core/class-affiliates-service.php';
            
            add_action( 'init', array( &$this, 'arm_affiliatepro_db_check' ) );

            register_activation_hook( __FILE__, array( 'ARM_Affiliatepro', 'install' ) );

            register_uninstall_hook( __FILE__, array( 'ARM_Affiliatepro', 'uninstall' ) );
            
            add_action( 'admin_notices', array( &$this, 'arm_admin_notices' ) );
            
            add_action( 'plugins_loaded', array( &$this, 'arm_affiliatepro_load_textdomain' ) );
            
            add_action( 'admin_enqueue_scripts', array( &$this, 'arm_afffiliatepro_scripts' ), 20 );
            
            add_action( 'arm_display_field_add_membership_plan', array( $this, 'display_field_add_membership_plan_page' ) );
            
            add_filter( 'arm_befor_save_field_membership_plan', array( $this, 'before_save_field_membership_plan' ), 10, 2 );
            
            add_filter( 'arm_add_arm_entries_value', array( $this, 'add_affiliate_in_arm_entries' ), 10, 1 );
        
            add_action( 'arm_after_add_new_user', array( $this, 'add_pending_referral' ), 10, 2 );
			
			add_action('admin_init', array(&$this, 'upgrade_data_affiliatepro'));
            
        }
        
		function upgrade_data_affiliatepro() {
			global $arm_affiliatepro_newdbversion;
	
			if (!isset($arm_affiliatepro_newdbversion) || $arm_affiliatepro_newdbversion == "")
				$arm_affiliatepro_newdbversion = get_option('arm_affiliatepro_version');
	
            if (version_compare($arm_affiliatepro_newdbversion, '1.3', '<')) {
				$path = ARM_AFFILIATEPRO_DIR . '/upgrade_latest_data_affiliatepro.php';
				include($path);
			}
		}
		
        public static function arm_affiliatepro_db_check() {
            global $arm_affiliatepro; 
            $arm_affiliatepro_version = get_option( 'arm_affiliatepro_version' );

            if ( !isset( $arm_affiliatepro_version ) || $arm_affiliatepro_version == '' )
                $arm_affiliatepro->install();
        }

        public static function install() {
            global $arm_affiliatepro;
            $arm_affiliatepro_version = get_option( 'arm_affiliatepro_version' );

            if ( !isset( $arm_affiliatepro_version ) || $arm_affiliatepro_version == '' ) {

                global $wpdb, $arm_affiliatepro_version;

                update_option( 'arm_affiliatepro_version', $arm_affiliatepro_version );
            }
        }

        public static function uninstall() {
            delete_option( 'arm_affiliatepro_version' );
        }
        
        function arm_admin_notices(){
            global $pagenow, $arm_slugs;    
            if($pagenow == 'plugins.php' || (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs))){
                if( !$this->is_armember_support() )
                    echo "<div class='updated updated_notices'><p>" . __('ARMember - Affiliate PRO plugin requires ARMember Plugin installed and active.', ARM_AFFILIATEPRO_TEXTDOMAIN) . "</p></div>";

                else if ( !$this->is_version_compatible() )
                    echo "<div class='updated updated_notices'><p>" . __('ARMember - Affiliate PRO plugin requires ARMember plugin installed with version 2.0 or higher.', ARM_AFFILIATEPRO_TEXTDOMAIN) . "</p></div>";

                if( ! ( ( $this->is_affiliate_support() && $this->is_affiliate_version_compatible() )
                   || ( $this->is_affiliate_pro_support() && $this->is_affiliate_pro_version_compatible() ) ) )
                {
                    if( !$this->is_affiliate_support() || !$this->is_affiliate_pro_support())
                        echo "<div class='updated updated_notices'><p>" . __('You must install and activate Affiliate 2.16.4 OR Affiliate PRO 2.16.4 or higher to use ARMember - Affiliate PRO Addon.', ARM_AFFILIATEPRO_TEXTDOMAIN) . "</p></div>";

                    else if( !$this->is_affiliate_version_compatible() || !$this->is_affiliate_pro_version_compatible()  )
                        echo "<div class='updated updated_notices'><p>" . __('ARmember - Affiliate PRO Add-on requires Affiliate 2.16.4 OR Affiliate PRO 2.16.4 or higher. Please update Affiliate OR Affiliate PRO.', ARM_AFFILIATEPRO_TEXTDOMAIN) . "</p></div>";
                }
            }
        }
        
        function is_armember_support() {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

            return is_plugin_active( 'armember/armember.php' );
        }
		
		function armaffiliatepro_getapiurl() {
            $api_url = 'https://www.arpluginshop.com/';
			return $api_url;
		}
        
        function get_armember_version() {
            $arm_db_version = get_option( 'arm_version' );

            return ( isset( $arm_db_version ) ) ? $arm_db_version : 0;
        }
        
		function armaffiliatepro_get_remote_post_params($plugin_info = "") {
			global $wpdb;
	
			$action = "";
			$action = $plugin_info;
	
			if (!function_exists('get_plugins')) {
				require_once(ABSPATH . 'wp-admin/includes/plugin.php');
			}
			$plugin_list = get_plugins();
			$site_url = home_url();
			$plugins = array();
	
			$active_plugins = get_option('active_plugins');
	
			foreach ($plugin_list as $key => $plugin) {
				$is_active = in_array($key, $active_plugins);
	
				//filter for only armember ones, may get some others if using our naming convention
				if (strpos(strtolower($plugin["Title"]), "armemberaffiliatepro") !== false) {
					$name = substr($key, 0, strpos($key, "/"));
					$plugins[] = array("name" => $name, "version" => $plugin["Version"], "is_active" => $is_active);
				}
			}
			$plugins = json_encode($plugins);
	
			//get theme info
			$theme = wp_get_theme();
			$theme_name = $theme->get("Name");
			$theme_uri = $theme->get("ThemeURI");
			$theme_version = $theme->get("Version");
			$theme_author = $theme->get("Author");
			$theme_author_uri = $theme->get("AuthorURI");
	
			$im = is_multisite();
			$sortorder = get_option("armSortOrder");
	
			$post = array("wp" => get_bloginfo("version"), "php" => phpversion(), "mysql" => $wpdb->db_version(), "plugins" => $plugins, "tn" => $theme_name, "tu" => $theme_uri, "tv" => $theme_version, "ta" => $theme_author, "tau" => $theme_author_uri, "im" => $im, "sortorder" => $sortorder);
	
			return $post;
		}
		
        function is_version_compatible() {
            if ( !version_compare( $this->get_armember_version(), '2.0', '>=' ) || !$this->is_armember_support() ) :
                return false;
            else : 
                return true;
            endif;
        }
        
        function is_affiliate_support() {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

            return is_plugin_active( $this->affiliate_dir . $this->affiliate_file );
        }
        
        function is_affiliate_version_compatible() {
            $affiliate_pro_path = WP_PLUGIN_DIR . '/' . $this->affiliate_dir . $this->affiliate_file;
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            $affwp_plugin_data = get_plugin_data( $affiliate_pro_path, false, false );

            return $affwp_plugin_data['Version'] < '2.16.4' ? false : true;
        }
        
        function is_affiliate_pro_support() {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

            return is_plugin_active( $this->affiliate_pro_dir . $this->affiliate_pro_file );
        }
        
        function is_affiliate_pro_version_compatible() {
            $affiliate_pro_path = WP_PLUGIN_DIR . '/' . $this->affiliate_pro_dir . $this->affiliate_pro_file;
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            $affwp_plugin_data = get_plugin_data( $affiliate_pro_path, false, false );

            return $affwp_plugin_data['Version'] < '2.16.4' ? false : true;
        }
        
        function is_compatible() {
            if( $this->is_armember_support() && $this->is_version_compatible()
             && ( ( $this->is_affiliate_support() && $this->is_affiliate_version_compatible() )
               || ( $this->is_affiliate_pro_support() && $this->is_affiliate_pro_version_compatible() ) ) )
                return true;
            else
                return false;
        }
        
        function arm_affiliatepro_load_textdomain() {
            load_plugin_textdomain(ARM_AFFILIATEPRO_TEXTDOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }
        
        function arm_afffiliatepro_scripts() {
            if( $this->is_compatible() )
            {
                global $arm_affiliatepro_version;
                wp_register_script('arm-affpro-admin-js', ARM_AFFILIATEPRO_URL . '/js/arm_aff_pro_admin.js', array(), $arm_affiliatepro_version);

                if( isset($_REQUEST['page']) && $_REQUEST['page'] == 'arm_manage_plans')
                {
                    wp_enqueue_script( 'arm-affpro-admin-js' );
                }
            }
        }
        
        function display_field_add_membership_plan_page( $plan_options ) {
            if( $this->is_compatible() )
            {
                global $arm_payment_gateways;
                $currency = $arm_payment_gateways->arm_get_global_currency();
                
                $arm_affiliatepro_referral_disable = (!empty($plan_options["affiliatepro_referral_disable"])) ? $plan_options["affiliatepro_referral_disable"] : 0;
                $arm_affiliatepro_referral_type = (!empty($plan_options["affiliatepro_referral_type"])) ? $plan_options["affiliatepro_referral_type"] : 'percentage';
                $arm_affiliatepro_referral_rate = (!empty($plan_options["affiliatepro_referral_rate"])) ? $plan_options["affiliatepro_referral_rate"] : 0;
                $display_percentage = '';
                $display_currency = '';
                if($arm_affiliatepro_referral_type == 'percentage')
                {
                    $display_currency = 'hidden_section';
                }
                else
                {
                    $display_percentage = 'hidden_section';
                }
                ?>
                <div class="arm_solid_divider"></div>
                <div id="arm_plan_price_box_content" class="arm_plan_price_box">
                    <div class="page_sub_content">
                        <div class="page_sub_title"><?php _e('Affiliate PRO Settings',ARM_AFFILIATEPRO_TEXTDOMAIN);?></div>
                        <table class="form-table">
                            <tr class="form-field form-required">
                                <th><label><?php _e('Enable Affiliate PRO Referral' ,ARM_AFFILIATEPRO_TEXTDOMAIN);?></label></th>   
                                <td>
                                    <div class="armclear"></div>
                                    <div class="armswitch arm_global_setting_switch" style="vertical-align: middle;">
                                        <input type="checkbox" id="affiliatepro_disable_referral" <?php checked($arm_affiliatepro_referral_disable, 1);?> value="1" class="armswitch_input" name="arm_subscription_plan_options[affiliatepro_disable_referral]"/>
                                        <label for="affiliatepro_disable_referral" class="armswitch_label" style="min-width:40px;"></label>
                                    </div>
                                    &nbsp;
                                    <span style="float:left;width:100%;position:relative;top:5px;left:5px;"><?php _e('Enable Affiliate PRO Referral if you want to give affiliate commission user will be signup with this plan.',ARM_AFFILIATEPRO_TEXTDOMAIN); ?></span>
                                    <div class="armclear"></div>
                                </td>
                            </tr>
                            <tr class="form-field form-required">
                                <th><label><?php _e('Referral Type' ,ARM_AFFILIATEPRO_TEXTDOMAIN);?></label></th>   
                                <td>
                                    <div class="arm_affilite_price_type_box">
                                        <span class="arm_affilaite_price_types_container" id="arm_affiliatepro_price_container">
                                            <input type="radio" class="arm_iradio" <?php checked($arm_affiliatepro_referral_type, 'percentage'); ?> value="percentage" name="arm_subscription_plan_options[arm_affiliate_price_type]" id="arm_affiliate_price_type_percentage" />
                                            <label for="arm_affiliate_price_type_percentage"><?php _e('Percentage', ARM_AFFILIATEPRO_TEXTDOMAIN); ?></label>
                                            <input type="radio" class="arm_iradio" <?php checked($arm_affiliatepro_referral_type, 'fixed_rate'); ?> value="fixed_rate" name="arm_subscription_plan_options[arm_affiliate_price_type]" id="arm_affiliate_price_type_fixed_rate" />
                                            <label for="arm_affiliate_price_type_fixed_rate"><?php _e('Fixed Rate', ARM_AFFILIATEPRO_TEXTDOMAIN); ?></label>
                                        </span>
                                        <div class="armclear"></div>
                                    </div>                                                            
                                </td>
                            </tr>
                            <tr class="form-field form-required">
                                <th><label><?php _e('Referral Rate' ,ARM_AFFILIATEPRO_TEXTDOMAIN);?></label></th>   
                                <td>
                                    <div class="arm_affilite_pro_rate_box">
                                        <input name="arm_subscription_plan_options[arm_affilaite_pro_rate]" id="arm_affilaite_pro_rate" type="text" size="50" class="arm_affilaite_pro_rate" title="Referral Rate" value="<?php echo $arm_affiliatepro_referral_rate; ?>" />
                                        <span class="arm_affpro_price_type_percentage <?php echo $display_percentage; ?>"> <?php _e('%', ARM_AFFILIATEPRO_TEXTDOMAIN); ?> </span>
                                        <span class="arm_affpro_price_type_currency <?php echo $display_currency; ?>"> <?php echo $currency; ?> </span>
                                        <div class="armclear"></div>
                                    </div>                                                            
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php 
                
                // below five text domain is use in affilaite pro plugin.
                __('Transaction Id #' ,ARM_AFFILIATEPRO_TEXTDOMAIN);
                __('Transaction Amount' ,ARM_AFFILIATEPRO_TEXTDOMAIN);
                __('Currency' ,ARM_AFFILIATEPRO_TEXTDOMAIN);
                __('Plan Name' ,ARM_AFFILIATEPRO_TEXTDOMAIN);
                __('Register User Email' ,ARM_AFFILIATEPRO_TEXTDOMAIN);
            }
        }
        
        function before_save_field_membership_plan($plan_options, $posted_data){
            $plan_options['affiliatepro_referral_disable'] = isset($posted_data['arm_subscription_plan_options']['affiliatepro_disable_referral']) ? $posted_data['arm_subscription_plan_options']['affiliatepro_disable_referral'] : 0;
            $plan_options['affiliatepro_referral_type'] = isset($posted_data['arm_subscription_plan_options']['arm_affiliate_price_type']) ? $posted_data['arm_subscription_plan_options']['arm_affiliate_price_type'] : 'percentage';
            $plan_options['affiliatepro_referral_rate'] = isset($posted_data['arm_subscription_plan_options']['arm_affilaite_pro_rate']) ? $posted_data['arm_subscription_plan_options']['arm_affilaite_pro_rate'] : 0;
            return $plan_options;
        }
        
        function add_affiliate_in_arm_entries( $entry_post_data ) {
            if( $this->is_compatible() )
            {
                $entry_post_data['ref_affiliatepro_id'] = $this->get_affiliate_id();
                $entry_post_data['ref_affiliatepro_post_id'] = url_to_postid( $entry_post_data['referral_url'] );
            }
            return $entry_post_data;
        }
        
        function get_affiliate_id() {
            if( $this->is_affiliate_support() && $this->is_affiliate_version_compatible() )
            {
                $affiliate_pro_service_file = WP_PLUGIN_DIR . '/' . $this->affiliate_dir . $this->affiliate_service_file;
                require_once( $affiliate_pro_service_file );
                return Affiliates_Service::get_referrer_id();
            }
            else if( $this->is_affiliate_pro_support() && $this->is_affiliate_pro_version_compatible() ){
                $affiliate_pro_service_file = WP_PLUGIN_DIR . '/' . $this->affiliate_pro_dir . $this->affiliate_pro_service_file;
                require_once( $affiliate_pro_service_file );
                return Affiliates_Service::get_referrer_id();
            }
        }
        
        function add_pending_referral( $user_id, $posted_data ) {
            if( $this->is_compatible() )
            {
                global $wpdb, $ARMember, $arm_payment_gateways;
                
                $bank_log = $ARMember->tbl_arm_bank_transfer_log;
                $payment_log = $ARMember->tbl_arm_payment_log;
                $plan_table = $ARMember->tbl_arm_subscription_plans;
                $referral_disable = 0;
                
                $plan_id = isset($posted_data['subscription_plan']) ? $posted_data['subscription_plan'] : 0;
                if ($plan_id == 0) {
                    $plan_id = isset($posted_data['_subscription_plan']) ? $posted_data['_subscription_plan'] : 0;
                }
                
                $plan_amount = 0;
                $orderid = $wpdb->get_row($wpdb->prepare("SELECT `arm_log_id`, `arm_transaction_id`, `arm_amount` FROM `{$bank_log}` WHERE `arm_plan_id` = %d and `arm_user_id` = %d ", $plan_id, $user_id));
                if($wpdb->num_rows > 0) {
                    $affiliate_id     = 'B'.$orderid->arm_log_id;
                    $transaction_id   = $orderid->arm_transaction_id;
                    $plan_amount = $orderid->arm_amount;
                } else {
                    $orderid = $wpdb->get_row($wpdb->prepare("SELECT `arm_log_id`, `arm_transaction_id`, `arm_amount` FROM `{$payment_log}` WHERE `arm_plan_id` = %d and `arm_user_id` = %d ", $plan_id, $user_id), OBJECT );
                    $affiliate_id     = isset($orderid->arm_log_id) ? $orderid->arm_log_id : '' ;
                    $transaction_id   = isset($orderid->arm_transaction_id) ? $orderid->arm_transaction_id : '';
                    $plan_amount = isset($orderid->arm_amount) ? $orderid->arm_amount : '';
                }
                
                $user_info = get_user_by('ID', $user_id);
                $user_email = $user_info->user_email;
                
                $plan_data = $wpdb->get_row($wpdb->prepare("SELECT `arm_subscription_plan_id`, `arm_subscription_plan_name`, `arm_subscription_plan_options`, `arm_subscription_plan_amount` FROM `{$plan_table}` WHERE `arm_subscription_plan_id` = %d ", $plan_id), OBJECT );
                $arm_subscription_plan_options = maybe_unserialize(isset($plan_data->arm_subscription_plan_options) ? $plan_data->arm_subscription_plan_options : '');

                //$plan_amount = $plan_data->arm_subscription_plan_amount;
                $referral_disable = isset($arm_subscription_plan_options['affiliatepro_referral_disable']) ? $arm_subscription_plan_options['affiliatepro_referral_disable'] : 0;
                $referral_type = isset($arm_subscription_plan_options['affiliatepro_referral_type']) ? $arm_subscription_plan_options['affiliatepro_referral_type'] : 0;
                $referral_rate = isset($arm_subscription_plan_options['affiliatepro_referral_rate']) ? $arm_subscription_plan_options['affiliatepro_referral_rate'] : 0;
                
                $amount = 0;
                if($referral_type == 'fixed_rate')
                {
                    $amount = $referral_rate;
                }
                else if($referral_type == 'percentage')
                {
                    $amount = ($plan_amount * $referral_rate) / 100;
                }
                
                if( $referral_disable == 1)
                {
                    $enable_register     = get_option( 'aff_user_registration_enabled', 'no' );
                    if($enable_register == 'yes')
                    {
                        $userdata = array();
                        $userdata['first_name'] = $user_info->user_login;
                        $userdata['last_name'] = '';
                        $userdata['user_email'] = $user_email;
                        Affiliates_Registration::store_affiliate( $user_id, $userdata );
                    }
                    
                    $description = $plan_data->arm_subscription_plan_name;              
                    $data = array();
                    $currency = $arm_payment_gateways->arm_get_global_currency();

                    $ref_affiliate_id = $this->get_affiliate_id();
                    $ref_post_id = url_to_postid( $posted_data['referral_url'] );
                    if(($ref_affiliate_id <= 0 || $ref_affiliate_id != '') && isset($posted_data['ref_affiliatepro_id']))
                    {
                        $ref_affiliate_id = isset($posted_data['ref_affiliatepro_id']) ? $posted_data['ref_affiliatepro_id'] : 0 ;
                        $ref_post_id = isset($posted_data['ref_affiliatepro_post_id']) ? $posted_data['ref_affiliatepro_post_id'] : 0 ;
                    }
                    
                    $order_link = '<a href="' . admin_url( 'admin.php?page=arm_manage_members&action=view_member&id='.$user_id ) . '">';
                    $order_link .= $user_email;
                    $order_link .= "</a>";

                    $data = array(
                        'transaction_id' => array(
                            'title' => 'Transaction Id #',
                            'domain' => ARM_AFFILIATEPRO_TEXTDOMAIN,
                            'value' => esc_sql( $transaction_id )
                        ),
                        'order_total' => array(
                            'title' => 'Transaction Amount',
                            'domain' =>  ARM_AFFILIATEPRO_TEXTDOMAIN,
                            'value' => esc_sql( number_format($plan_amount,2) )
                        ),
                        'order_currency' => array(
                            'title' => 'Currency',
                            'domain' =>  ARM_AFFILIATEPRO_TEXTDOMAIN,
                            'value' => esc_sql( $currency )
                        ),
                        'plan_detail' => array(
                            'title' => 'Plan Name',
                            'domain' =>  ARM_AFFILIATEPRO_TEXTDOMAIN,
                            'value' => esc_sql( $description )
                        ),
                        'register_user_email' => array(
                            'title' => 'Register User Email',
                            'domain' =>  ARM_AFFILIATEPRO_TEXTDOMAIN,
                            'value' => esc_sql( $order_link )
                        )
                    );

                    // amount - pass commission valude
                    // data - order detail
                    // description - order Id
                    // order_id - order_id
                    // affiliates_suggest_referral( $order_id, $description, $data, $amount, $currency );
                    // demo - affiliates_suggest_referral( '5', 'order id - 5', 'this is data', 5, 'usd' );

                    // amount - pass commission valude
                    // data - order detail
                    // description - order Id
                    // order_id - order_id
                    // post_id - order id
                    //affiliates_add_referral($affiliate_id, $post_id, $description, $data, $amount, $currency );
                    affiliates_add_referral($ref_affiliate_id, $ref_post_id, $description, $data, $amount, $currency );
                }
            }
        }
        
    }    
}
global $arm_affiliatepro;
$arm_affiliatepro = new ARM_Affiliatepro();


global $armaffiliatepro_api_url, $armaffiliatepro_plugin_slug;

$armaffiliatepro_api_url = $arm_affiliatepro->armaffiliatepro_getapiurl();
$armaffiliatepro_plugin_slug = basename(dirname(__FILE__));

add_filter('pre_set_site_transient_update_plugins', 'armaffiliatepro_check_for_plugin_update');

function armaffiliatepro_check_for_plugin_update($checked_data) {
    global $armaffiliatepro_api_url, $armaffiliatepro_plugin_slug, $wp_version, $arm_affiliatepro_version,$arm_affiliatepro;

    //Comment out these two lines during testing.
    if (empty($checked_data->checked))
        return $checked_data;

    $args = array(
        'slug' => $armaffiliatepro_plugin_slug,
        'version' => $arm_affiliatepro_version,
        'other_variables' => $arm_affiliatepro->armaffiliatepro_get_remote_post_params(),
    );

    $request_string = array(
        'body' => array(
            'action' => 'basic_check',
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMAFFILIATEPRO-WordPress/' . $wp_version . '; ' . home_url()
    );

    // Start checking for an update
    $raw_response = wp_remote_post($armaffiliatepro_api_url, $request_string);

    if (!is_wp_error($raw_response) && ($raw_response['response']['code'] == 200))
        $response = @unserialize($raw_response['body']);

    if (isset($response) && !empty($response) && isset($response->token) && $response->token != "")
        update_option('armaffiliatepro_update_token', $response->token);

    if (isset($response) && is_object($response) && is_object($checked_data) && !empty($response)) // Feed the update data into WP updater
        $checked_data->response[$armaffiliatepro_plugin_slug . '/' . $armaffiliatepro_plugin_slug . '.php'] = $response;

    return $checked_data;
}

add_filter('plugins_api', 'armaffiliatepro_plugin_api_call', 10, 3);

function armaffiliatepro_plugin_api_call($def, $action, $args) {
    global $armaffiliatepro_plugin_slug, $armaffiliatepro_api_url, $wp_version;

    if (!isset($args->slug) || ($args->slug != $armaffiliatepro_plugin_slug))
        return false;

    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$armaffiliatepro_plugin_slug . '/' . $armaffiliatepro_plugin_slug . '.php'];
    $args->version = $current_version;

    $request_string = array(
        'body' => array(
            'action' => $action,
            'update_token' => get_site_option('armaffiliatepro_update_token'),
            'request' => serialize($args),
            'api-key' => md5(home_url())
        ),
        'user-agent' => 'ARMAFFILIATEPRO-WordPress/' . $wp_version . '; ' . home_url()
    );

    $request = wp_remote_post($armaffiliatepro_api_url, $request_string);

    if (is_wp_error($request)) {
        $res = new WP_Error('plugins_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', ARM_AFFILIATEPRO_TEXTDOMAIN), $request->get_error_message());
    } else {
        $res = unserialize($request['body']);

        if ($res === false)
            $res = new WP_Error('plugins_api_failed', __('An unknown error occurred', ARM_AFFILIATEPRO_TEXTDOMAIN), $request['body']);
    }

    return $res;
}
