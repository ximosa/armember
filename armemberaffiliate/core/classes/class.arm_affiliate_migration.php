<?php
if(!class_exists('arm_affiliate_migration')){
    
    class arm_affiliate_migration{

        private $armaff_exist_affiliates;

        function __construct(){

            add_action( 'wp_ajax_arm_affiliate_migrate_data', array( $this, 'arm_affiliate_migrate_data' ) );

            add_action( 'arm_do_affiliate_migrate_data', array( $this, 'arm_affiliate_migrate_data' ), 10, 1 );

            add_action( 'wp_ajax_armaff_get_wpaffiliate_details', array( $this, 'armaff_get_wpaffiliate_details' ) );

            add_action( 'armaff_deactivate_plugin', array( $this, 'armaff_deactivate_plugin' ), 10, 1 );

        }

        function armaff_reset_migrate_session(){
            global $ARMember;
            $ARMember->arm_session_start();
            unset($_SESSION['armaff_finish_affiliates']);
            unset($_SESSION['armaff_finish_referrals']);
            unset($_SESSION['armaff_finish_affiliatesPro']);
            unset($_SESSION['armaff_finish_referralsPro']);
            unset($_SESSION['armaff_finish_creatives']);
            unset($_SESSION['armaff_finish_banners']);
        }

        function arm_affiliate_migrate_data( $armaff_step = 1 ){

            global $arm_affiliate_migration, $arm_affiliate_settings, $ARMember;
            $ARMember->arm_session_start();
            if(!isset($armaff_step) || $armaff_step == ''){
                $armaff_step = 1;
            }

            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_migration';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }

            $response = array( 'type'=>'error', 'message' => __( 'There is an error while migrating data, please try again.', 'ARM_AFFILIATE' ) );

            $armaff_migrate_type = isset($_POST['armaff_migrate']) ? $_POST['armaff_migrate'] : '';
            $armaff_deactivate_wplugin = isset($_POST['armaff_wpdeactivate_plugin']) ? $_POST['armaff_wpdeactivate_plugin'] : 0;
            $armaff_update_url_param = isset($_POST['armaff_update_url_param_name']) ? $_POST['armaff_update_url_param_name'] : 0;
            $armaff_replace_wpshortcodes = isset($_POST['armaff_replace_wpshortcode']) ? $_POST['armaff_replace_wpshortcode'] : 0;
            $armaff_enable_fancyurl = isset($_POST['armaff_enable_fancy_url']) ? $_POST['armaff_enable_fancy_url'] : 0;
            $armaff_enable_idencoding = isset($_POST['armaff_enable_affid_encoding']) ? $_POST['armaff_enable_affid_encoding'] : 0;

            if($armaff_migrate_type == ''){
                $response = array( 'type'=>'error', 'message' => __( 'There is an error while migrating data, please try again.', 'ARM_AFFILIATE' ) );
                $arm_affiliate_migration->armaff_reset_migrate_session();
                echo json_encode($response); exit;
            }

            $is_wpplugin_active = $arm_affiliate_migration->armaff_check_wpplugin_active( $armaff_migrate_type );
            if(!$is_wpplugin_active){
                $response = array( 'type'=>'error', 'message' => __( 'You must install and activate selected plugin to migrate data from that.', 'ARM_AFFILIATE' ) );
                $arm_affiliate_migration->armaff_reset_migrate_session();
                echo json_encode($response); exit;
            }

            switch( $armaff_migrate_type ) {

                case 'affiliateWP' :

                    $armaff_finish = 0;

                    if(!isset($_SESSION['armaff_finish_affiliates']) || $_SESSION['armaff_finish_affiliates'] != 1){
                        /* Migrate Affiliates */
                        $armaff_affiliates = $arm_affiliate_migration->do_armaff_affiliates( $armaff_step );

                        if( !empty( $armaff_affiliates ) && $armaff_affiliates > 0 ) {
                            $armaff_finish = 0;
                            $armaff_step = $armaff_affiliates;
                            unset($_SESSION['armaff_finish_affiliates']);
                            do_action('arm_do_affiliate_migrate_data', $armaff_step);
                        } else {
                            $armaff_finish = 1;
                            $_SESSION['armaff_finish_affiliates'] = 1;
                            $armaff_step = 1;
                        }
                    }

                    if(!isset($_SESSION['armaff_finish_referrals']) || $_SESSION['armaff_finish_referrals'] != 1){
                        /* Migrate Referrals */
                        $armaff_referrals = $arm_affiliate_migration->do_armaff_referrals( $armaff_step );

                        if( !empty( $armaff_referrals ) && $armaff_referrals > 0 ) {
                            $armaff_finish = 0;
                            $armaff_step = $armaff_referrals;
                            unset($_SESSION['armaff_finish_referrals']);
                            do_action('arm_do_affiliate_migrate_data', $armaff_step);
                        } else {
                            $armaff_finish = 1;
                            $_SESSION['armaff_finish_referrals'] = 1;
                            $armaff_step = 1;
                        }
                    }

                    if(!isset($_SESSION['armaff_finish_creatives']) || $_SESSION['armaff_finish_creatives'] != 1){
                        /* Migrate Creatives to Banners */
                        $armaff_creatives = $arm_affiliate_migration->do_armaff_creatives( $armaff_step );

                        if( !empty( $armaff_creatives ) && $armaff_creatives > 0 ) {
                            $armaff_finish = 0;
                            $armaff_step = $armaff_creatives;
                            unset($_SESSION['armaff_finish_creatives']);
                            do_action('arm_do_affiliate_migrate_data', $armaff_step);
                        } else {
                            $armaff_finish = 1;
                            $_SESSION['armaff_finish_creatives'] = 1;
                            $armaff_step = 1;
                        }
                    }

                    if($armaff_finish == 1) {
                        $wpplugin_slug = $arm_affiliate_migration->armaff_get_wp_plugin_slug($armaff_migrate_type);
                        if($armaff_deactivate_wplugin == 1 && $wpplugin_slug != ''){
                            do_action("armaff_deactivate_plugin", $wpplugin_slug);
                        }

                        if($armaff_update_url_param == 1) {
                            $armaff_update_param = $arm_affiliate_migration->armaff_update_url_parameter($armaff_migrate_type);
                        }

                        if( $armaff_enable_fancyurl == 1 || $armaff_enable_idencoding == 1 ){
                            $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
                            if($armaff_enable_fancyurl == 1){
                                $affiliate_options['arm_aff_allow_fancy_url'] = 1;
                            }
                            if($armaff_enable_idencoding == 1){
                                $affiliate_options['arm_aff_id_encoding'] = 'MD5';
                            }
                            update_option( 'arm_affiliate_setting', $affiliate_options );
                            if($armaff_enable_fancyurl == 1){
                                update_option( 'armaff_flush_rewrites' );
                            }
                        }

                        if( $armaff_replace_wpshortcodes == 1 ){
                            $armaff_replace_shortcode = $arm_affiliate_migration->armaff_replace_wp_shortcodes($armaff_migrate_type);
                        }

                        $response = array( 'type'=>'success', 'message' => __( 'Your Data Migrated Successfully.', 'ARM_AFFILIATE' ) );
                        $arm_affiliate_migration->armaff_reset_migrate_session();
                        echo json_encode($response); exit;
                    }

                    break;

                case 'affiliatesPro' :

                    $armaff_finish = 0;

                    if(!isset($_SESSION['armaff_finish_affiliatesPro']) || $_SESSION['armaff_finish_affiliatesPro'] != 1){
                        /* Migrate Affiliates */
                        $armaff_affiliates = $arm_affiliate_migration->do_armaff_affiliatesPro( $armaff_step );

                        if( !empty( $armaff_affiliates ) && $armaff_affiliates > 0 ) {
                            $armaff_finish = 0;
                            $armaff_step = $armaff_affiliates;
                            unset($_SESSION['armaff_finish_affiliatesPro']);
                            do_action('arm_do_affiliate_migrate_data', $armaff_step);
                        } else {
                            $armaff_finish = 1;
                            $_SESSION['armaff_finish_affiliatesPro'] = 1;
                            $armaff_step = 1;
                        }
                    }

                    if(!isset($_SESSION['armaff_finish_referralsPro']) || $_SESSION['armaff_finish_referralsPro'] != 1){
                        /* Migrate Referrals */
                        $armaff_referrals = $arm_affiliate_migration->do_armaff_referralsPro( $armaff_step );

                        if( !empty( $armaff_referrals ) && $armaff_referrals > 0 ) {
                            $armaff_finish = 0;
                            $armaff_step = $armaff_referrals;
                            unset($_SESSION['armaff_finish_referralsPro']);
                            do_action('arm_do_affiliate_migrate_data', $armaff_step);
                        } else {
                            $armaff_finish = 1;
                            $_SESSION['armaff_finish_referralsPro'] = 1;
                            $armaff_step = 1;
                        }
                    }

                    if(!isset($_SESSION['armaff_finish_banners']) || $_SESSION['armaff_finish_banners'] != 1){
                        /* Migrate Banners */
                        $armaff_banners = $arm_affiliate_migration->do_armaff_banners( $armaff_step );

                        if( !empty( $armaff_banners ) && $armaff_banners > 0 ) {
                            $armaff_finish = 0;
                            $armaff_step = $armaff_banners;
                            unset($_SESSION['armaff_finish_banners']);
                            do_action('arm_do_affiliate_migrate_data', $armaff_step);
                        } else {
                            $armaff_finish = 1;
                            $_SESSION['armaff_finish_banners'] = 1;
                            $armaff_step = 1;
                        }
                    }

                    if($armaff_finish == 1) {
                        $wpplugin_slug = $arm_affiliate_migration->armaff_get_wp_plugin_slug($armaff_migrate_type);
                        if($armaff_deactivate_wplugin == 1 && $wpplugin_slug != ''){
                            if( get_option('aff_delete_data') != '' ) {
                                update_option('aff_delete_data', '');
                            }
                            do_action("armaff_deactivate_plugin", $wpplugin_slug);
                        }

                        if($armaff_update_url_param == 1) {
                            $armaff_update_param = $arm_affiliate_migration->armaff_update_url_parameter($armaff_migrate_type);
                        }

                        if( $armaff_enable_fancyurl == 1 || $armaff_enable_idencoding == 1 ){
                            $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
                            if($armaff_enable_fancyurl == 1){
                                $affiliate_options['arm_aff_allow_fancy_url'] = 1;
                            }
                            if($armaff_enable_idencoding == 1){
                                $affiliate_options['arm_aff_id_encoding'] = 'MD5';
                            }
                            update_option( 'arm_affiliate_setting', $affiliate_options );
                            if($armaff_enable_fancyurl == 1){
                                update_option( 'armaff_flush_rewrites' );
                            }
                        }

                        if( $armaff_replace_wpshortcodes == 1 ){
                            $armaff_replace_shortcode = $arm_affiliate_migration->armaff_replace_wp_shortcodes($armaff_migrate_type);
                        }

                        $response = array( 'type'=>'success', 'message' => __( 'Your Data Migrated Successfully.', 'ARM_AFFILIATE' ) );
                        $arm_affiliate_migration->armaff_reset_migrate_session();
                        echo json_encode($response); exit;
                    }

                    break;

                default :
                    $response = array( 'type'=>'success', 'message' => __( 'Your Data Migrated Successfully.', 'ARM_AFFILIATE' ) );
                    $arm_affiliate_migration->armaff_reset_migrate_session();
                    echo json_encode($response); exit;
                    break;

            }


        }

        function do_armaff_affiliates( $armaff_step = 1 ){

            global $wpdb, $arm_affiliate, $arm_affiliate_migration;

            if( $armaff_step == 1 ) {

                $armaff_counts = $wpdb->get_row( "SELECT `arm_affiliate_id` FROM {$arm_affiliate->tbl_arm_aff_affiliates} ORDER BY `arm_affiliate_id` DESC LIMIT 1" );

                if($wpdb->num_rows > 0){

                    $armaff_id_conflicts = 0;
                    $wpaff_lastid = $wpdb->get_row( "SELECT `affiliate_id` FROM {$wpdb->prefix}affiliate_wp_affiliates ORDER BY `affiliate_id` ASC LIMIT 1" );

                    if($wpaff_lastid){
                        $armaff_id_conflicts = ( $armaff_counts->arm_affiliate_id < $wpaff_lastid->affiliate_id ) ? 0 : 1;
                    }

                    if($armaff_id_conflicts){
                        $response = array( 'type'=>'error', 'message' => __( 'Already Exist affiliates in ARMemeber Affilieates.', 'ARM_AFFILIATE' ) );
                        echo json_encode($response); exit;
                    }

                }

            }

            $armaff_offset = ($armaff_step - 1) * 100;
            $armaff_affiliates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}affiliate_wp_affiliates ORDER BY affiliate_id LIMIT $armaff_offset, 100;" );

            $armaff_delete = $armaff_inserted = array();

            if( $armaff_affiliates ) {
                foreach( $armaff_affiliates as $affiliate ) {

                    $aff_user_id = $affiliate->user_id;
                    $aff_payment_email = $affiliate->payment_email;
                    if( !$aff_user_id && $aff_payment_email != '') {
                        $affuser    = get_user_by( 'email', $aff_payment_email );
                        $aff_user_id = ( !empty( $affuser->ID ) ) ? $affuser->ID : 0;
                    }

                    $aff_status = $affiliate->status;
                    if($aff_status == 'active'){
                        $aff_status = 1;
                    } else {
                        $aff_status = 0;
                    }

                    $aff_date_registered = $affiliate->date_registered;

                    $armaff_data = array(
                        'arm_affiliate_id'  => $affiliate->affiliate_id,
                        'arm_user_id'   => $aff_user_id,
                        'arm_status'    => $aff_status,
                        'arm_start_date_time'   => $aff_date_registered,
                    );

                    $armaff_defaults = array(
                        'arm_status'          => 1,
                        'arm_start_date_time' => current_time( 'mysql' )
                    );

                    $armaff_args = wp_parse_args( $armaff_data, $armaff_defaults );

                    $armaff_existing_affiliate = $wpdb->get_row( $wpdb->prepare( "SELECT arm_affiliate_id,arm_status FROM {$arm_affiliate->tbl_arm_aff_affiliates} WHERE `arm_user_id` = '%d' LIMIT 1;", $aff_user_id ) );

                    $armaff_insert_flag = 0;

                    if($armaff_existing_affiliate) {
                        if($armaff_existing_affiliate->arm_status != 1){
                            $armaff_delete[] = $armaff_existing_affiliate->arm_affiliate_id;
                            $armaff_insert_flag = 1;
                        } else {
                            $this->armaff_exist_affiliates[] = $armaff_existing_affiliate->arm_affiliate_id;
                        }
                    } else {
                        $armaff_insert_flag = 1;
                    }

                    if($armaff_insert_flag == 1) {
                        $armaff_insert = $wpdb->insert($arm_affiliate->tbl_arm_aff_affiliates, $armaff_args);
                        if(!$armaff_insert){
                            $response = array( 'type'=>'error', 'message' => __( 'There is a error while migrating data, please try again.', 'ARM_AFFILIATE' ) );
                            $arm_affiliate_migration->armaff_reset_migrate_session();
                            echo json_encode($response); exit;
                        }
                        $armaff_insert_id = $wpdb->insert_id;
                        $armaff_inserted[] = $armaff_insert_id;
                    }

                    if( !empty( $armaff_delete ) ) {
                        foreach( $armaff_delete as $armaff_del_id ) {
                            $armaff_delete_res = $wpdb->query($wpdb->prepare("DELETE FROM " .$arm_affiliate->tbl_arm_aff_affiliates." WHERE arm_affiliate_id = %d", $armaff_del_id));
                        }
                    }
                    
                }

                $armaff_step = $armaff_step + 1;
                return $armaff_step;

            } else {

                return '0';

            }

        }

        function do_armaff_referrals( $armaff_step = 1 ){

            global $wpdb, $arm_affiliate;

            $armaff_offset = ($armaff_step - 1) * 100;
            $armaff_referrals = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}affiliate_wp_referrals ORDER BY referral_id LIMIT $armaff_offset, 100;" );

            if( $armaff_referrals ) {

                $armember_exist_affiliates = array();

                $armember_affiliates = $wpdb->get_results( "SELECT `arm_affiliate_id` FROM {$arm_affiliate->tbl_arm_aff_affiliates} WHERE arm_status = 1 ORDER BY arm_affiliate_id;" );

                if($armember_affiliates) {
                    foreach( $armember_affiliates as $armaffiliate ) {
                        $armember_exist_affiliates[] = $armaffiliate->arm_affiliate_id;
                    }
                }

                /*$armaff_already_migrated = get_option("armaff_referrals_migrated");
                $armaff_already_migrated = ($armaff_already_migrated != '') ? maybe_unserialize($armaff_already_migrated) : array();*/


                foreach( $armaff_referrals as $referral ) {

                    if( !in_array( $referral->affiliate_id, $armember_exist_affiliates ) || in_array( $referral->affiliate_id, $this->armaff_exist_affiliates ) ){
                        continue;
                    }

                    /*if( in_array( $referral->affiliate_id, $armaff_already_migrated) ) {
                        continue;
                    }*/

                    $armaff_status = 0;

                    switch( $referral->status ) {
                        case 'unpaid' :
                            $armaff_status = 1;
                            break;
                        case 'paid' :
                            $armaff_status = 2;
                            break;
                        case 'rejected' :
                            $armaff_status = 3;
                            break;
                        case 'pending' :
                        default :
                            $armaff_status = 0;
                            break;
                    }

                    $armaff_data = array(
                        'arm_affiliate_id'  => $referral->affiliate_id,
                        /*'arm_plan_id'       => $referral->affiliate_id,
                        'arm_ref_affiliate_id'  => $referral->affiliate_id,*/
                        'arm_status'        => $armaff_status,
                        'arm_amount'        => $referral->amount,
                        'arm_currency'      => $referral->currency,
                        'arm_date_time'     => $referral->date
                    );

                    $armaff_defaults = array(
                        'arm_status' => 0,
                        'arm_amount' => 0
                    );

                    $armaff_args = wp_parse_args( $armaff_data, $armaff_defaults );

                    $armaff_insert = $wpdb->insert($arm_affiliate->tbl_arm_aff_referrals, $armaff_args);

                    /*$armaff_already_migrated[] = $referral->affiliate_id;*/

                }

                /*$armaff_already_migrated = maybe_serialize($armaff_already_migrated);
                update_option("armaff_referrals_migrated", $armaff_already_migrated);*/

                $armaff_step = $armaff_step + 1;
                return $armaff_step;

            } else {

                return '0';

            }
        }

        function do_armaff_affiliatesPro( $armaff_step = 1 ){

            global $wpdb, $arm_affiliate, $arm_affiliate_migration;

            if( $armaff_step == 1 ) {

                $armaff_counts = $wpdb->get_row( "SELECT `arm_affiliate_id` FROM {$arm_affiliate->tbl_arm_aff_affiliates} ORDER BY `arm_affiliate_id` DESC LIMIT 1" );

                if($wpdb->num_rows > 0){

                    $armaff_id_conflicts = 0;
                    $wpaff_lastid = $wpdb->get_row( "SELECT `affiliate_id` FROM {$wpdb->prefix}aff_affiliates ORDER BY `affiliate_id` ASC LIMIT 1" );

                    if($wpaff_lastid){
                        $armaff_id_conflicts = ( $armaff_counts->arm_affiliate_id < $wpaff_lastid->affiliate_id ) ? 0 : 1;
                    }

                    if($armaff_id_conflicts){
                        $response = array( 'type'=>'error', 'message' => __( 'Already Exist affiliates in ARMemeber Affilieates.', 'ARM_AFFILIATE' ) );
                        echo json_encode($response); exit;
                    }

                }

            }

            $armaff_offset = ($armaff_step - 1) * 100;
            $armaff_affiliates = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}aff_affiliates ORDER BY affiliate_id LIMIT $armaff_offset, 100;" );

            $armaff_delete = $armaff_inserted = array();

            if( $armaff_affiliates ) {
                foreach( $armaff_affiliates as $affiliate ) {

                    if($affiliate->type == 'direct'){
                        continue;
                    }

                    $aff_user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}aff_affiliates_users WHERE affiliate_id = %d", $affiliate->affiliate_id ) );

                    $aff_payment_email = $affiliate->email;
                    if( !$aff_user_id && $aff_payment_email != '') {
                        $affuser    = get_user_by( 'email', $aff_payment_email );
                        $aff_user_id = ( !empty( $affuser->ID ) ) ? $affuser->ID : 0;
                    }

                    $aff_status = $affiliate->status;
                    if($aff_status == 'active'){
                        $aff_status = 1;
                    } else {
                        $aff_status = 0;
                    }

                    $aff_date_registered = $affiliate->from_date;

                    $armaff_data = array(
                        'arm_affiliate_id'  => $affiliate->affiliate_id,
                        'arm_user_id'   => $aff_user_id,
                        'arm_status'    => $aff_status,
                        'arm_start_date_time'   => $aff_date_registered,
                    );

                    $armaff_defaults = array(
                        'arm_status'          => 1,
                        'arm_start_date_time' => current_time( 'mysql' )
                    );

                    $armaff_args = wp_parse_args( $armaff_data, $armaff_defaults );

                    $armaff_existing_affiliate = $wpdb->get_row( $wpdb->prepare( "SELECT arm_affiliate_id,arm_status FROM {$arm_affiliate->tbl_arm_aff_affiliates} WHERE `arm_user_id` = '%d' LIMIT 1;", $aff_user_id ) );

                    $armaff_insert_flag = 0;

                    if($armaff_existing_affiliate) {
                        if($armaff_existing_affiliate->arm_status != 1){
                            $armaff_delete[] = $armaff_existing_affiliate->arm_affiliate_id;
                            $armaff_insert_flag = 1;
                        } else {
                            $this->armaff_exist_affiliates[] = $armaff_existing_affiliate->arm_affiliate_id;
                        }
                    } else {
                        $armaff_insert_flag = 1;
                    }

                    if($armaff_insert_flag == 1) {
                        $armaff_insert = $wpdb->insert($arm_affiliate->tbl_arm_aff_affiliates, $armaff_args);
                        if(!$armaff_insert){
                            $response = array( 'type'=>'error', 'message' => __( 'There is a error while migrating data, please try again.', 'ARM_AFFILIATE' ) );
                            $arm_affiliate_migration->armaff_reset_migrate_session();
                            echo json_encode($response); exit;
                        }
                        $armaff_insert_id = $wpdb->insert_id;
                        $armaff_inserted[] = $armaff_insert_id;
                    }

                    if( !empty( $armaff_delete ) ) {
                        foreach( $armaff_delete as $armaff_del_id ) {
                            $armaff_delete_res = $wpdb->query($wpdb->prepare("DELETE FROM " .$arm_affiliate->tbl_arm_aff_affiliates." WHERE arm_affiliate_id = %d", $armaff_del_id));
                        }
                    }
                    
                }

                $armaff_step = $armaff_step + 1;
                return $armaff_step;

            } else {

                return '0';

            }

        }

        function do_armaff_referralsPro( $armaff_step = 1 ){

            global $wpdb, $arm_affiliate;

            $armaff_offset = ($armaff_step - 1) * 100;
            $armaff_referrals = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}aff_referrals ORDER BY referral_id LIMIT $armaff_offset, 100;" );

            if( $armaff_referrals ) {

                $armember_exist_affiliates = array();

                $armember_affiliates = $wpdb->get_results( "SELECT `arm_affiliate_id` FROM {$arm_affiliate->tbl_arm_aff_affiliates} WHERE arm_status = 1 ORDER BY arm_affiliate_id;" );

                if($armember_affiliates) {
                    foreach( $armember_affiliates as $armaffiliate ) {
                        $armember_exist_affiliates[] = $armaffiliate->arm_affiliate_id;
                    }
                }

                /*$armaff_already_migrated = get_option("armaff_referralsPro_migrated");
                $armaff_already_migrated = ($armaff_already_migrated != '') ? maybe_unserialize($armaff_already_migrated) : array();*/


                foreach( $armaff_referrals as $referral ) {

                    if( !in_array( $referral->affiliate_id, $armember_exist_affiliates ) || in_array( $referral->affiliate_id, $this->armaff_exist_affiliates ) ){
                        continue;
                    }

                    /*if( in_array( $referral->affiliate_id, $armaff_already_migrated) ) {
                        continue;
                    }*/

                    $armaff_status = 0;

                    switch( $referral->status ) {
                        case 'accepted' :
                            $armaff_status = 1;
                            break;
                        case 'closed' :
                            $armaff_status = 2;
                            break;
                        case 'rejected' :
                            $armaff_status = 3;
                            break;
                        case 'pending' :
                        default :
                            $armaff_status = 0;
                            break;
                    }

                    $armaff_data = array(
                        'arm_affiliate_id'  => $referral->affiliate_id,
                        /*'arm_plan_id'       => $referral->affiliate_id,
                        'arm_ref_affiliate_id'  => $referral->affiliate_id,*/
                        'arm_status'        => $armaff_status,
                        'arm_amount'        => $referral->amount,
                        'arm_currency'      => strtoupper( $referral->currency_id ),
                        'arm_date_time'     => $referral->datetime
                    );

                    $armaff_defaults = array(
                        'arm_status' => 0,
                        'arm_amount' => 0
                    );

                    $armaff_args = wp_parse_args( $armaff_data, $armaff_defaults );

                    $armaff_insert = $wpdb->insert($arm_affiliate->tbl_arm_aff_referrals, $armaff_args);

                    /*$armaff_already_migrated[] = $referral->affiliate_id;*/

                }

                /*$armaff_already_migrated = maybe_serialize($armaff_already_migrated);
                update_option("armaff_referralsPro_migrated", $armaff_already_migrated);*/

                $armaff_step = $armaff_step + 1;
                return $armaff_step;

            } else {

                return '0';

            }
        }

        function do_armaff_creatives( $armaff_step = 1 ){
            global $wpdb, $arm_affiliate;

            $armaff_offset = ($armaff_step - 1) * 100;
            $armaff_creatives = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}affiliate_wp_creatives ORDER BY creative_id LIMIT $armaff_offset, 100;" );

            if( $armaff_creatives ) {

                $armember_exist_banners = array();

                $armember_banners = $wpdb->get_results( "SELECT `arm_banner_id` FROM {$arm_affiliate->tbl_arm_aff_banner} ORDER BY arm_banner_id;" );

                if($armember_banners) {
                    foreach( $armember_banners as $armbanner ) {
                        $armember_exist_banners[] = $armbanner->arm_banner_id;
                    }
                }

                if (!is_dir(ARM_AFF_OUTPUT_DIR)){
                    wp_mkdir_p(ARM_AFF_OUTPUT_DIR);
                }

                foreach( $armaff_creatives as $creative ) {

                    if( in_array( $creative->creative_id, $armember_exist_banners ) ) {
                        continue;
                    }

                    $armaff_status = ( isset($creative->status) && $creative->status == 'active' ) ? 1 : 0;

                    $armaff_image = '';
                    if($creative->image != ''){

                        $wpimage_url = $creative->image;
                        $wpget = wp_remote_get( $wpimage_url );
                        $wpimgtype = wp_remote_retrieve_header( $wpget, 'content-type' );
                        if($wpimgtype) {
                            $armaff_image = basename( $wpimage_url );
                            $armaff_uploaded = wp_upload_bits( $armaff_image, '', wp_remote_retrieve_body( $wpget ) );
                            if(file_exists(ARM_AFF_OUTPUT_DIR.$armaff_image)){
                                $armaff_image = current_time('timestamp') . '_' . $armaff_image;
                            }
                            $armaff_copied = copy($armaff_uploaded['file'] , ARM_AFF_OUTPUT_DIR.$armaff_image);
                            if (!$armaff_copied) {
                                $armaff_image = '';
                            }
                            @unlink( $armaff_uploaded['file'] );
                        }

                    }

                    /*if( $armaff_image == '' ) {
                        continue;
                    }*/

                    $armaff_data = array(
                        'arm_banner_id'  => $creative->creative_id,
                        'arm_title'       => $creative->name,
                        'arm_description'  => $creative->description,
                        'arm_image'        => $armaff_image,
                        'arm_link'      => $creative->url,
                        'arm_status'        => $armaff_status,
                    );

                    $armaff_insert = $wpdb->insert($arm_affiliate->tbl_arm_aff_banner, $armaff_data);

                }

                $armaff_step = $armaff_step + 1;
                return $armaff_step;

            } else {
                return '0';
            }

        }

        function do_armaff_banners( $armaff_step = 1 ){
            global $wpdb, $arm_affiliate;

            // $armaff_offset = ($armaff_step - 1) * 100;
            $armaff_banners = get_posts( array( 'numberposts' => -1, 'post_type' => 'affiliates_banner', 'post_status' => 'publish' ) ); 

            if( $armaff_banners ) {

                $armember_exist_banners = array();

                $armember_banners = $wpdb->get_results( "SELECT `arm_banner_id` FROM {$arm_affiliate->tbl_arm_aff_banner} ORDER BY arm_banner_id;" );

                if($armember_banners) {
                    foreach( $armember_banners as $armbanner ) {
                        $armember_exist_banners[] = $armbanner->arm_banner_id;
                    }
                }

                if (!is_dir(ARM_AFF_OUTPUT_DIR)){
                    wp_mkdir_p(ARM_AFF_OUTPUT_DIR);
                }

                foreach( $armaff_banners as $banner ) {

                    if( in_array( $banner->ID, $armember_exist_banners ) ) {
                        continue;
                    }

                    $armaff_image = '';
                    $armaff_image_id = get_post_thumbnail_id( $banner->ID );
                    if($armaff_image_id != ''){
                        $armaff_image_path = get_attached_file($armaff_image_id);
                        $armaff_image = basename( $armaff_image_path );
                        if(file_exists(ARM_AFF_OUTPUT_DIR.$armaff_image)){
                            $armaff_image = current_time('timestamp') . '_' . $armaff_image;
                        }
                        $armaff_copied = copy($armaff_image_path , ARM_AFF_OUTPUT_DIR.$armaff_image);
                        if (!$armaff_copied) {
                            $armaff_image = '';
                        }
                    }


                    /*if( $armaff_image == '' ) {
                        continue;
                    }*/

                    $armaff_data = array(
                        'arm_banner_id'  => $banner->ID,
                        'arm_title'       => $banner->post_title,
                        'arm_description'  => $banner->post_content,
                        'arm_image'        => $armaff_image,
                        'arm_link'      => '',
                        'arm_status'        => 1,
                    );

                    $armaff_insert = $wpdb->insert($arm_affiliate->tbl_arm_aff_banner, $armaff_data);

                }

                return '0';

            } else {
                return '0';
            }

        }

        function armaff_get_wpaffiliate_details() {

            global $wpdb, $arm_affiliate, $arm_affiliate_settings, $arm_affiliate_migration, $ARMember;

            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_migration';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }

            $armaff_accounts = array();
            $armaff_accounts['armEnable_FancyURL'] = 0;
            $armaff_accounts['armEnable_Encoding'] = 0;
            $armaff_total_affiliates = $wpaff_total_affiliates = 0;

            $response = array( 'type'=>'error', 'message' => __( 'Sorry, your existing affiliate accounts detail are not available.', 'ARM_AFFILIATE' ) );

            $armaff_dberror = array( 'type'=>'database_error', 'message' => __( 'There is no database table exist for plugin from where you are trying to migrate data.', 'ARM_AFFILIATE' ) );

            $armaff_migrate_type = isset($_POST['armaff_migrate']) ? $_POST['armaff_migrate'] : '';

            if($armaff_migrate_type == ''){
                $response = array( 'type'=>'error', 'message' => __( 'Please select plugin from where to migrate affiliate accounts.', 'ARM_AFFILIATE' ) );
                echo json_encode($response); exit;
            }

            $is_wpplugin_active = $arm_affiliate_migration->armaff_check_wpplugin_active( $armaff_migrate_type );
            if(!$is_wpplugin_active){
                $response = array( 'type'=>'plugin_inactive', 'message' => __( 'You must install and activate selected plugin to migrate data from that.', 'ARM_AFFILIATE' ) );
                echo json_encode($response); exit;
            }

            switch ( $armaff_migrate_type ) {
                case 'affiliateWP':

                    $wpaff_table = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}affiliate_wp_affiliates%';" );

                    if ( !$wpaff_table ) {
                        echo json_encode($armaff_dberror); exit;
                    }

                    $armaff_account_id = $wpaff_account_id = $armaff_id_conflicts = 0;

                    $armaff_counts = $wpdb->get_row( "SELECT `arm_affiliate_id`, COUNT(`arm_affiliate_id`) AS armaff_total FROM {$arm_affiliate->tbl_arm_aff_affiliates} ORDER BY `arm_affiliate_id` DESC LIMIT 1" );

                    $wpaff_firstid = $wpdb->get_row( "SELECT `affiliate_id`, COUNT(`affiliate_id`) AS affwp_total FROM {$wpdb->prefix}affiliate_wp_affiliates ORDER BY `affiliate_id` ASC LIMIT 1" );

                    if($armaff_counts){
                        $armaff_account_id = $armaff_counts->arm_affiliate_id;
                        $armaff_total_affiliates = $armaff_counts->armaff_total;
                        if($wpaff_firstid){
                            $armaff_id_conflicts = ( $armaff_account_id < $wpaff_firstid->affiliate_id ) ? 0 : 1;
                        }
                    }

                    if($wpaff_firstid){
                        $wpaff_account_id = $wpaff_firstid->affiliate_id;
                        $wpaff_total_affiliates = $wpaff_firstid->affwp_total;
                    }

                    $armaff_wpsettings = get_option('affwp_settings');
                    $armaff_wpsettings = ($armaff_wpsettings != '') ? maybe_unserialize($armaff_wpsettings) : array();
                    if( isset($armaff_wpsettings['referral_pretty_urls']) && $armaff_wpsettings['referral_pretty_urls'] == 1 ){
                        $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
                        if( isset($affiliate_options['arm_aff_allow_fancy_url']) && $affiliate_options['arm_aff_allow_fancy_url'] == 1){
                        } else {
                            $armaff_accounts['armEnable_FancyURL'] = 1;
                        }
                    }

                    $armaff_accounts['wpAffiliate_id'] = $wpaff_account_id;

                    $armaff_accounts['armAffiliate_id'] = $armaff_account_id;

                    $armaff_accounts['armMigrate_conflict'] = $armaff_id_conflicts;

                    $armaff_accounts['wpTotalAffiliate'] = $wpaff_total_affiliates;

                    $armaff_accounts['armaffTotalAffiliate'] = $armaff_total_affiliates;

                    $armaff_accounts = json_encode($armaff_accounts);

                    $response = array( 'type'=>'success', 'arm_migrate_info' => $armaff_accounts );

                    echo json_encode($response); exit;

                    break;


                case 'affiliatesPro':

                    $wpaff_table = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}aff_affiliates%';" );

                    if ( !$wpaff_table ) {
                        echo json_encode($armaff_dberror); exit;
                    }

                    $armaff_account_id = $wpaff_account_id = $armaff_id_conflicts = 0;

                    $armaff_counts = $wpdb->get_row( "SELECT `arm_affiliate_id`, COUNT(`arm_affiliate_id`) AS armaff_total FROM {$arm_affiliate->tbl_arm_aff_affiliates} ORDER BY `arm_affiliate_id` DESC LIMIT 1" );

                    $wpaff_firstid = $wpdb->get_row( "SELECT `affiliate_id`, COUNT(`affiliate_id`) AS affwp_total FROM {$wpdb->prefix}aff_affiliates ORDER BY `affiliate_id` ASC LIMIT 1" );

                    if($armaff_counts){
                        $armaff_account_id = $armaff_counts->arm_affiliate_id;
                        $armaff_total_affiliates = $armaff_counts->armaff_total;
                        if($wpaff_firstid){
                            $armaff_id_conflicts = ( $armaff_account_id < $wpaff_firstid->affiliate_id ) ? 0 : 1;
                        }
                    }

                    if($wpaff_firstid){
                        $wpaff_account_id = $wpaff_firstid->affiliate_id;
                        $wpaff_total_affiliates = $wpaff_firstid->affwp_total;
                    }

                    $armaff_wpsettings = get_option('aff_id_encoding');
                    if($armaff_wpsettings != '' && $armaff_wpsettings == 2){
                        $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
                        if( isset($affiliate_options['arm_aff_id_encoding']) && $affiliate_options['arm_aff_id_encoding'] == 'MD5'){
                        } else {
                            $armaff_accounts['armEnable_Encoding'] = 1;
                        }
                    }

                    $armaff_accounts['wpAffiliate_id'] = $wpaff_account_id;

                    $armaff_accounts['armAffiliate_id'] = $armaff_account_id;

                    $armaff_accounts['armMigrate_conflict'] = $armaff_id_conflicts;

                    $armaff_accounts['wpTotalAffiliate'] = $wpaff_total_affiliates;

                    $armaff_accounts['armaffTotalAffiliate'] = $armaff_total_affiliates;

                    $armaff_accounts = json_encode($armaff_accounts);

                    $response = array( 'type'=>'success', 'arm_migrate_info' => $armaff_accounts );

                    echo json_encode($response); exit;

                    break;


                default:
                    $response = array( 'type'=>'error', 'message' => __( 'Please select plugin from where to migrate affiliate accounts.', 'ARM_AFFILIATE' ) );
                    echo json_encode($response); exit;
                    break;
            }

        }

        function armaff_check_wpplugin_active( $armaff_plugin = ''){

            if( $armaff_plugin == ''){
                return false;
            }

            global $arm_affiliate_migration;

            $armaff_wp_slug = $arm_affiliate_migration->armaff_get_wp_plugin_slug($armaff_plugin);

            return is_plugin_active( $armaff_wp_slug );

        }

        function armaff_get_wp_plugin_slug( $armaff_plugin = ''){

            if( $armaff_plugin == ''){
                return false;
            }

            $armaff_plugin_slug = '';

            switch ($armaff_plugin) {
                case 'affiliateWP':
                    $affwp_dir = 'affiliate-wp';
                    $affwp_constant = AFFILIATEWP_PLUGIN_DIR;
                    if($affwp_constant != '' && $affwp_constant != 'AFFILIATEWP_PLUGIN_DIR'){
                        $affwp_dir = plugin_basename($affwp_constant);
                    }
                    $armaff_plugin_slug = $affwp_dir."/affiliate-wp.php";
                    break;

                case 'affiliatesPro':
                    $affwp_dir = 'affiliates-pro';
                    $affwp_constant = AFFILIATES_CORE_DIR;
                    if($affwp_constant != '' && $affwp_constant != 'AFFILIATES_CORE_DIR'){
                        $affwp_dir = plugin_basename($affwp_constant);
                    }
                    $armaff_plugin_slug = $affwp_dir."/affiliates-pro.php";
                    break;

                default:
                    $armaff_plugin_slug = '';
                    break;
            }

            return $armaff_plugin_slug;

        }

        function armaff_deactivate_plugin( $armaff_slug = '' ) {
            $plugin = $armaff_slug;
            $silent = false;
            $network_wide = false;
            if (is_multisite()){
                $network_current = get_site_option('active_sitewide_plugins', array());
            }
            $current = get_option('active_plugins', array());
            $do_blog = $do_network = false;

            $plugin = plugin_basename(trim($plugin));

            $network_deactivating = false !== $network_wide && is_plugin_active_for_network($plugin);

            if (!$silent) {
                do_action('deactivate_plugin', $plugin, $network_deactivating);
            }

            if (false != $network_wide) {
                if (is_plugin_active_for_network($plugin)) {
                    $do_network = true;
                    unset($network_current[$plugin]);
                } elseif ($network_wide) {
                    
                }
            }

            if (true != $network_wide) {
                $key = array_search($plugin, $current);
                if (false !== $key) {
                    $do_blog = true;
                    unset($current[$key]);
                }
            }

            if (!$silent) {
                do_action('deactivate_' . $plugin, $network_deactivating);
                do_action('deactivated_plugin', $plugin, $network_deactivating);
            }


            if ($do_blog)
                update_option('active_plugins', $current);
            if ($do_network)
                update_site_option('active_sitewide_plugins', $network_current);
        }

        function armaff_update_url_parameter( $armaff_plugin = ''){

            global $arm_affiliate_settings;

            $armaff_return = false;

            switch( $armaff_plugin ) {

                case 'affiliateWP' :

                    $armaff_new_param = 'ref';
                    $armaff_wpsettings = get_option('affwp_settings');
                    $armaff_wpsettings = ($armaff_wpsettings != '') ? maybe_unserialize($armaff_wpsettings) : array();
                    if( isset($armaff_wpsettings['referral_var']) ){
                        $armaff_new_param = $armaff_wpsettings['referral_var'];
                    }

                    $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
                    $affiliate_options['arm_aff_referral_var'] = $armaff_new_param;
                    update_option( 'arm_affiliate_setting', $affiliate_options );
                    $armaff_return = true;

                    break;

                case 'affiliatesPro' :

                    $armaff_new_param = 'affiliates';
                    $armaff_wpparam = get_option('aff_pname');
                    if( $armaff_wpparam != '' ){
                        $armaff_new_param = $armaff_wpparam;
                    }

                    $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
                    $affiliate_options['arm_aff_referral_var'] = $armaff_new_param;
                    update_option( 'arm_affiliate_setting', $affiliate_options );
                    $armaff_return = true;

                    break;

                default:
                    break;
            }

            return $armaff_return;
        }

        function armaff_replace_wp_shortcodes( $armaff_plugin = ''){
            global $wpdb;

            $armaff_return = false;

            switch( $armaff_plugin ) {

                case 'affiliateWP' :

                    /* Replace Shortcode */

                    $armaff_search_shortcode = '[affiliate_area]';
                    $armaff_replace_shortcode = '[arm_affiliate_register]';

                    $armaff_posts = $wpdb->get_results("SELECT ID,post_content FROM $wpdb->posts WHERE (`post_content` LIKE '%{$armaff_search_shortcode}%') AND `post_status` = 'publish'");

                    if( $armaff_posts ) {
                        foreach ($armaff_posts as $armaff_post) {
                            $armaff_post_content = $armaff_post->post_content;

                            $armaff_post_content = str_replace($armaff_search_shortcode, $armaff_replace_shortcode, $armaff_post_content);

                            $armaff_update_post = array(
                                'ID'    => $armaff_post->ID,
                                'post_content'  => $armaff_post_content
                            );
                            wp_update_post( $armaff_update_post);
                        }
                    }

                    /* Replace Shortcode */

                    $armaff_search_shortcode = '[affiliate_creative ';
                    $armaff_replace_shortcode = '[arm_aff_banner ';

                    $armaff_posts = $wpdb->get_results("SELECT ID,post_content FROM $wpdb->posts WHERE (`post_content` LIKE '%{$armaff_search_shortcode}%') AND `post_status` = 'publish'");

                    if( $armaff_posts ) {

                        foreach ($armaff_posts as $armaff_post) {

                            $armaff_post_content = $armaff_post->post_content;

                            $armaff_count = substr_count($armaff_post_content, $armaff_search_shortcode);

                            for ($icnt=0; $icnt < $armaff_count; $icnt++) { 
                                $armaff_sub_content = strstr($armaff_post_content, $armaff_search_shortcode);

                                if( $armaff_sub_content != '' ){
                                    $armaff_sub_content = strstr($armaff_sub_content, ']', true);
                                    $armaff_sub_content .= ']';
                                    $armaff_find_shortcode = $armaff_sub_content;

                                    $armaff_sub_content = str_replace($armaff_search_shortcode, $armaff_replace_shortcode, $armaff_sub_content);
                                    $armaff_sub_content = str_replace('id', 'item_id', $armaff_sub_content);

                                    $armaff_post_content = str_replace($armaff_find_shortcode, $armaff_sub_content, $armaff_post_content);

                                }

                            }

                            $armaff_update_post = array(
                                'ID'    => $armaff_post->ID,
                                'post_content'  => $armaff_post_content
                            );
                            wp_update_post( $armaff_update_post);

                        }
                    }


                    /* Replace Shortcode */

                    $armaff_search_shortcode = '[affiliate_referral_url';
                    $armaff_replace_shortcode = '[arm_user_referral';

                    $armaff_posts = $wpdb->get_results("SELECT ID,post_content FROM $wpdb->posts WHERE (`post_content` LIKE '%{$armaff_search_shortcode}%') AND `post_status` = 'publish'");

                    if( $armaff_posts ) {

                        foreach ($armaff_posts as $armaff_post) {

                            $armaff_post_content = $armaff_post->post_content;

                            $armaff_count = substr_count($armaff_post_content, $armaff_search_shortcode);

                            for ($icnt=0; $icnt < $armaff_count; $icnt++) { 
                                $armaff_sub_content = strstr($armaff_post_content, $armaff_search_shortcode);

                                if( $armaff_sub_content != '' ){
                                    $armaff_sub_content = strstr($armaff_sub_content, ']', true);
                                    $armaff_sub_content .= ']';
                                    $armaff_find_shortcode = $armaff_sub_content;

                                    $armaff_sub_content = str_replace($armaff_search_shortcode, $armaff_replace_shortcode, $armaff_sub_content);
                                    // $armaff_sub_content = str_replace('id', 'item_id', $armaff_sub_content);

                                    $armaff_post_content = str_replace($armaff_find_shortcode, $armaff_sub_content, $armaff_post_content);

                                }

                            }

                            $armaff_update_post = array(
                                'ID'    => $armaff_post->ID,
                                'post_content'  => $armaff_post_content
                            );
                            wp_update_post( $armaff_update_post);

                        }
                    }


                    break;

                case 'affiliatesPro' :

                    /* Replace Shortcode */

                    $armaff_search_shortcode = '[affiliates_referrals';
                    $armaff_replace_shortcode = '[arm_user_referral';

                    $armaff_posts = $wpdb->get_results("SELECT ID,post_content FROM $wpdb->posts WHERE (`post_content` LIKE '%{$armaff_search_shortcode}%') AND `post_status` = 'publish'");

                    if( $armaff_posts ) {

                        foreach ($armaff_posts as $armaff_post) {

                            $armaff_post_content = $armaff_post->post_content;

                            $armaff_count = substr_count($armaff_post_content, $armaff_search_shortcode);

                            for ($icnt=0; $icnt < $armaff_count; $icnt++) { 
                                $armaff_sub_content = strstr($armaff_post_content, $armaff_search_shortcode);

                                if( $armaff_sub_content != '' ){
                                    $armaff_sub_content = strstr($armaff_sub_content, ']', true);
                                    $armaff_sub_content .= ']';
                                    $armaff_find_shortcode = $armaff_sub_content;

                                    $armaff_sub_content = str_replace($armaff_search_shortcode, $armaff_replace_shortcode, $armaff_sub_content);
                                    // $armaff_sub_content = str_replace('id', 'item_id', $armaff_sub_content);

                                    $armaff_post_content = str_replace($armaff_find_shortcode, $armaff_sub_content, $armaff_post_content);

                                }

                            }

                            $armaff_update_post = array(
                                'ID'    => $armaff_post->ID,
                                'post_content'  => $armaff_post_content
                            );
                            wp_update_post( $armaff_update_post);

                        }
                    }


                    /* Replace Shortcode */

                    $armaff_search_shortcode = '[affiliates_banner ';
                    $armaff_replace_shortcode = '[arm_aff_banner ';

                    $armaff_posts = $wpdb->get_results("SELECT ID,post_content FROM $wpdb->posts WHERE (`post_content` LIKE '%{$armaff_search_shortcode}%') AND `post_status` = 'publish'");

                    if( $armaff_posts ) {

                        foreach ($armaff_posts as $armaff_post) {

                            $armaff_post_content = $armaff_post->post_content;

                            $armaff_count = substr_count($armaff_post_content, $armaff_search_shortcode);

                            for ($icnt=0; $icnt < $armaff_count; $icnt++) { 
                                $armaff_sub_content = strstr($armaff_post_content, $armaff_search_shortcode);

                                if( $armaff_sub_content != '' ){
                                    $armaff_sub_content = strstr($armaff_sub_content, ']', true);
                                    $armaff_sub_content .= ']';
                                    $armaff_find_shortcode = $armaff_sub_content;

                                    $armaff_sub_content = str_replace($armaff_search_shortcode, $armaff_replace_shortcode, $armaff_sub_content);
                                    $armaff_sub_content = str_replace('id', 'item_id', $armaff_sub_content);

                                    $armaff_post_content = str_replace($armaff_find_shortcode, $armaff_sub_content, $armaff_post_content);

                                }

                            }

                            $armaff_update_post = array(
                                'ID'    => $armaff_post->ID,
                                'post_content'  => $armaff_post_content
                            );
                            wp_update_post( $armaff_update_post);

                        }
                    }

                    break;

                default:
                    break;
            }

            return $armaff_return;
        }

    }
}

global $arm_affiliate_migration;
$arm_affiliate_migration = new arm_affiliate_migration();
?>