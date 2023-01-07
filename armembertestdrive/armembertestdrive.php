<?php

/*
 Plugin Name: ARMember Test Drive Demo Setup
 Description: Setup Demo for ARMember with Add-on
 Plugin URI: https://www.armemberplugin.com
 Author: Repute Infosystems
 Author URI: https://www.reputeinfosystems.com
 Version: 1.0
*/

 global $armember_demo_version;
 $armember_demo_version = '1.0';


 global $armember_demo_setup;
 $armember_demo_setup = new ARMember_Demo_Setup();

if( is_ssl() ){
 	define("ARMEMBER_TESTDRIVE_SETUP_URL",str_replace('http://','https://',WP_PLUGIN_URL.'/armembertestdrive'));
} else {
    define("ARMEMBER_TESTDRIVE_SETUP_URL",WP_PLUGIN_URL.'/armembertestdrive');
}
define("ARMEMBER_TESTDRIVE_SETUP_DIR",WP_PLUGIN_DIR.'/armembertestdrive');

define("ARMEMBER_TESTDRIVE_CSS_URL",ARMEMBER_TESTDRIVE_SETUP_URL.'/css');

 class ARMember_Demo_Setup{



 	function __construct(){



 		register_activation_hook(__FILE__,array('ARMember_Demo_Setup','install'));
 		register_uninstall_hook(__FILE__,array('ARMember_Demo_Setup','uninstall'));

 		//add_action('wp',array(&$this,'armember_demo_setup'));

 		//add_shortcode('armember_test_demo',array(&$this,'armember_display_demo_setup'));

        add_action('wp_head', array(&$this, 'add_armembertestdrive_js'));

        add_action('admin_menu',array(&$this,'arformstestdrive_menu_page'));

        add_action('deleted_blog', array(&$this,'arm_remove_blog_tables'),10,2);


 	}

    function arm_remove_blog_tables($blog_id,$drop){
        global $wpdb;
        
        $orphanTables = array();

        $db_name = $wpdb->dbname;

        $query = "SHOW TABLES FROM ".$db_name." WHERE Tables_in_".$db_name." LIKE '%".$wpdb->prefix."%'";
        $allTables = $wpdb->get_results($query);
        
        foreach( $allTables as $tableName ){
            $column = 'Tables_in_'.$db_name;
            $table_name = $tableName->$column;
            $wpdb->query("DROP TABLE IF EXISTS ".$table_name);
        }

    }

    function arformstestdrive_menu_page(){

        add_menu_page('ARMTestDrive','ARMTestDrive','ARMTestDrive','armtestdrive',array(&$this,'armember_display_demo_setup'));
    }

 	function install(){

 		global $armember_demo_version;



 		if( get_option('armember_demo_setup_version') == '' ){
 			update_option('armember_demo_setup_version',$armember_demo_version);

 		}

 	}



 	function uninstall(){

 		delete_option('armember_demo_setup_version');

 	}





    function add_armembertestdrive_js(){
        if( is_admin() ){
            return;

        }

        wp_register_script('add_armembertestdrive-js',  plugin_dir_url( __FILE__ ).'js/armember_testdrive.js', array('jquery'), '0.9.9', true);
        wp_enqueue_script('add_armembertestdrive-js');

        wp_enqueue_script('jquery');
    }


 	function armember_caps_with_addons(){

 		$demo_armember_array = array();


        $demo_armember_array['armember/armember.php'] = array(
            'name' => "ARMember",
            'caps' => array('arm_manage_members', 'arm_manage_plans', 'arm_manage_setups', 'arm_manage_forms', 'arm_manage_access_rules', 'arm_manage_drip_rules', 'arm_manage_transactions', 'arm_manage_email_notifications', 'arm_manage_communication', 'arm_manage_member_templates', 'arm_manage_general_settings', 'arm_manage_feature_settings', 'arm_manage_block_settings', 'arm_manage_coupons', 'arm_manage_payment_gateways', 'arm_import_export', 'arm_badges', 'arm_report_analytics', 'arm_manage_pay_per_post', 'mycred-settings'),
            'is_display' => 1
        );

        $demo_armember_array['armemberaffiliate/armemberaffiliate.php'] = array(
            'name' => 'ARMember - Individual Affiliate Addon', 
            'caps' => array('arm_affiliate', 'arm_affiliate_option', 'arm_affiliate_referral', 'arm_affiliate_payouts', 'arm_affiliate_visits', 'arm_affiliate_statistics', 'arm_affiliate_banners', 'arm_affiliate_migration', 'arm_affiliate_commision_setup'),
            'is_display' => 1
        );

        $demo_armember_array['armembercommunity/armembercommunity.php'] = array(
            'name' => "ARMember - Social Community Addon",
            'caps' => array(''),
            'is_display' => 1
        );

        $demo_armember_array['armembergroupmembership/armembergroupmembership.php'] = array(
            'name' => "ARMember - Group/Umbrella Membership Addon",
            'caps' => array('arm_gm_membership'),
            'is_display' => 1
        );

        $demo_armember_array['armembermultisite/armembermultisite.php'] = array(
            'name' => "ARMember - Network Site Addon",
            'caps' => array(''),
            'is_display' => 1
        );

        $demo_armember_array = apply_filters('armember_setup_demo_plugin_outside',$demo_armember_array);

        

        $demo_armember_array['armembersms/armembersms.php'] = array(
            'name' => 'ARMember - SMS Notification for ARMember', 
            'caps' => array(),
            'is_display' => 1
        );

        $demo_armember_array['armemberdigitaldownload/armemberdigitaldownload.php'] = array(
            'name' => 'ARMember - Digital Download', 
            'caps' => array('arm_dd_item', 'arm_dd_download', 'arm_dd_setting'),
            'is_display' => 1
        );

        $demo_armember_array['armember-paypalpro/armember-paypalpro.php'] = array(
            'name' => 'ARMember - Paypal Pro Addon',
            'caps' => array(),
            'is_display' => 1
        );

        $demo_armember_array['armembermollie/armembermollie.php'] = array(
            'name' => 'ARMember - Mollie payment gateway Addon',
            'caps' => array(),
            'is_display' => 1
        );

        $demo_armember_array['armemberpaystack/armemberpaystack.php'] = array(
            'name' => 'ARMember - Paystack payment gateway Addon',
            'caps' => array(),
            'is_display' => 1
        );

        $demo_armember_array['armemberpagseguro/armemberpagseguro.php'] = array(
            'name' => 'ARMember - Pagseguro payment gateway Addon',
            'caps' => array(),
            'is_display' => 1
        );

        $demo_armember_array['armemberpayumoney/armemberpayumoney.php'] = array(
            'name' => 'ARMember - PayUmoney payment gateway Addon',
            'caps' => array(),
            'is_display' => 1
        );

        $demo_armember_array['armember-worldpay/armember-worldpay.php'] = array(
            'name' => 'ARMember - Online Worldpay payment gateway Addon',
            'caps' => array(),
            'is_display' => 1
        );

        $demo_armember_array['armemberrazorpay/armemberrazorpay.php'] = array(
            'name' => 'ARMember - Razorpay payment gateway Addon',
            'caps' => array(),
            'is_display' => 1
        );

        $demo_armember_array['armemberpayfast/armemberpayfast.php'] = array(
            'name' => 'ARMember - PayFast payment gateway Addon',
            'caps' => array(),
            'is_display' => 1
        );

        $demo_armember_array['armembersquare/armembersquare.php'] = array(
            'name' => 'ARMember - Square payment gateway Addon',
            'caps' => array(),
            'is_display' => 1
        );

        $demo_armember_array['armemberskrill/armemberskrill.php'] = array(
            'name' => 'ARMember - Skrill payment gateway Addon',
            'caps' => array(),
            'is_display' => 1
        );

       $demo_armember_array['armemberzapier/armemberzapier.php'] = array(
            'name' => 'ARMember - Zapier Addon',
            'caps' => array('arm_zapier_setting'),
            'is_display' => 1
        );

        $demo_armember_array['woocommerce/woocommerce.php'] = array(
            'name' => 'Woocommerce',
            'caps' => array('view_admin_dashboard'),
            'is_display' => 0
        );

        
        $demo_armember_array['armemberdirectlogins/armemberdirectlogins.php'] = array(
            'name' => 'ARMember - Direct Login Addon',
            'caps' => array('arm_direct_logins'),
            'is_display' => 1
       );

       

        $demo_armember_array['armemberwoocommerce/armemberwoocommerce.php'] = array(
            'name' => 'ARMember - WooCommerce Discount Addon',
            'caps' => array(),
            'is_display' => 1
        );

        $demo_armember_array['armemberinfusionsoft/armemberinfusionsoft.php'] = array(
            'name' => 'ARMember - Infusionsoft Addon',
            'caps' => array(),
            'is_display' => 1
        );
    
        $demo_armember_array['armembermautic/armembermautic.php'] = array(
            'name' => 'ARMember - Mautic Addon',
            'caps' => array(),
            'is_display' => 1
        );
        
        $demo_armember_array['buddypress/class-buddypress.php'] = array(
            'name' => 'BuddyPress',
            'caps' => array(),
            'is_display' => 0
        );

        $demo_armember_array['armemberactivecampaign/armemberactivecampaign.php'] = array(
            'name' => "ARMember - Active Campaign Addon",
            'caps' => array(),
            'is_display' => 1
        );
        
        if( defined('AFFILIATES_ACCESS_AFFILIATES') )
        {
            $demo_armember_array['armemberaffiliatepro/armemberaffiliatepro.php'] = array(
                'name' => 'ARMember - Affiliate PRO Addon',
                'caps' => array(AFFILIATES_ACCESS_AFFILIATES, AFFILIATES_ADMINISTER_AFFILIATES, AFFILIATES_ADMINISTER_OPTIONS),
                'is_display' => 1
            );
        }
        else {
            $demo_armember_array['armemberaffiliatepro/armemberaffiliatepro.php'] = array(
                'name' => 'ARMember - Affiliate PRO Addon',
                'caps' => array('aff_access', 'aff_admin_affiliates', 'aff_admin_options'),
                'is_display' => 1
            );
        }

        $demo_armember_array['armemberaffiliatewp/armemberaffiliatewp.php'] = array(
            'name' => 'ARMember - AffiliateWP Addon',
            'caps' => array('view_affiliate_reports', 'export_affiliate_data', 'export_referral_data', 'manage_affiliate_options', 'manage_affiliates', 'manage_referrals', 'manage_visits', 'manage_creatives'),
            'is_display' => 1
        );

        $demo_armember_array['armember-cornerstone/armember-cornerstone.php'] = array(
            'name' => 'ARMember - Cornerstone Integration',
            'caps' => array(),
            'is_display' => 1
        );

        return $demo_armember_array;
 	}

 	function armember_demo_setup(){

 		if( is_admin() ){
 			return;
 		}

 		do_shortcode('[armember_test_demo]');

 	}

 	function armember_display_demo_setup(){
 		
 		require_once ARMEMBER_TESTDRIVE_SETUP_DIR.'/core/view.php';

 	}

 }