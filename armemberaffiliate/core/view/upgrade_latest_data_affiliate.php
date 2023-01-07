<?php

    global $armaff_newdbversion;

    if(version_compare($armaff_newdbversion, '2.0', '<'))
    {
        @set_time_limit(0);

        global $wpdb, $arm_affiliate;

        $args = array(
            'role' => 'administrator',
            'fields' => 'id'
        );
        $users = get_users($args);
        if (count($users) > 0) {
            foreach ($users as $key => $user_id) {
                $armaffroles = $arm_affiliate->arm_aff_capabilities();
                $userObj = new WP_User($user_id);
                foreach ($armaffroles as $armaffrole => $armaffroledescription) {
                    $userObj->add_cap($armaffrole);
                }
                unset($armaffrole);
                unset($armaffroles);
                unset($armaffroledescription);
            }
        }

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $charset_collate = '';

        if ($wpdb->has_cap('collation')) {
            if (!empty($wpdb->charset)) {
                $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            }
            

            if (!empty($wpdb->collate)){
                $charset_collate .= " COLLATE $wpdb->collate";
            }
            
        }

        $tbl_forms = $arm_affiliate->tbl_arm_aff_forms;

        $create_tbl_forms = "CREATE TABLE IF NOT EXISTS `{$tbl_forms}` (
            `arm_form_id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `arm_form_title` VARCHAR(255) DEFAULT NULL,
            `arm_form_style` VARCHAR(100) DEFAULT NULL,
            `arm_form_slug` VARCHAR(255) DEFAULT NULL,
            `arm_form_fields` LONGTEXT,
            `arm_added_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
        ) {$charset_collate};";

        dbDelta( $create_tbl_forms );

        do_action('armaff_after_create_database_tables');


        $tbl_affiliates_main = $arm_affiliate->tbl_arm_aff_affiliates;

        $wpdb->query("ALTER TABLE `{$tbl_affiliates_main}` ADD `affiliate_website` VARCHAR( 100 ) NULL AFTER `arm_status`, ADD `affiliate_website_desc` VARCHAR(255) NULL AFTER `affiliate_website`");


        $wpdb->query("ALTER TABLE `{$arm_affiliate->tbl_arm_aff_referrals}` ADD `arm_woo_order` MEDIUMTEXT NULL DEFAULT NULL AFTER `arm_currency`");


        $armaff_affiliates = $wpdb->get_var( "SELECT COUNT(`arm_affiliate_id`) AS total_affiliates FROM {$tbl_affiliates_main}" );

        if($armaff_affiliates > 0) {


            $tbl_affiliates = $wpdb->prefix . 'arm_aff_affiliates_temp';

            $create_tbl_affiliates = "CREATE TABLE IF NOT EXISTS `{$tbl_affiliates}` (
                arm_affiliate_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                arm_user_id bigint(20) NOT NULL,
                arm_status int(1) DEFAULT '1' NOT NULL,
                affiliate_website varchar(100) NULL,
                affiliate_website_desc varchar(255) NULL,
                arm_start_date_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                arm_end_date_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
            ) {$charset_collate};";

            dbDelta( $create_tbl_affiliates );

            $armaff_steps = ( $armaff_affiliates > 100 ) ? ceil($armaff_affiliates / 100) : 1;

            for ($armaffstep = 1; $armaffstep <= $armaff_steps; $armaffstep++) {
                $armaff_offset = ($armaffstep - 1) * 100;

                $armaff_affiliates_records = $wpdb->get_results( "SELECT * FROM {$tbl_affiliates_main} LIMIT $armaff_offset, 100;" );

                if($armaff_affiliates_records) {

                    foreach ($armaff_affiliates_records as $affiliates) {

                        $arm_add_affiliate = array(
                            'arm_affiliate_id' => $affiliates->arm_affiliate_id,
                            'arm_user_id' => $affiliates->arm_user_id,
                            'arm_status' => $affiliates->arm_status,
                            'arm_start_date_time' => $affiliates->arm_start_date_time,
                            'arm_end_date_time' => $affiliates->arm_end_date_time
                        );

                        $wpdb->insert($tbl_affiliates, $arm_add_affiliate);

                    }

                }

            }


            $armaff_temp_affiliates = $wpdb->get_var( "SELECT COUNT(`arm_affiliate_id`) AS total_affiliates FROM {$tbl_affiliates}" );


            if($armaff_temp_affiliates == $armaff_affiliates){


                $wpdb->query("TRUNCATE TABLE `{$tbl_affiliates_main}`");


                for ($armaffstep = 1; $armaffstep <= $armaff_steps; $armaffstep++) {
                    $armaff_offset = ($armaffstep - 1) * 100;

                    $armaff_affiliates_records = $wpdb->get_results( "SELECT * FROM {$tbl_affiliates} ORDER BY `arm_user_id` ASC LIMIT $armaff_offset, 100;" );

                    if($armaff_affiliates_records) {

                        foreach ($armaff_affiliates_records as $affiliates) {

                            $arm_add_affiliate = array(
                                'arm_affiliate_id' => $affiliates->arm_user_id,
                                'arm_user_id' => $affiliates->arm_user_id,
                                'arm_status' => $affiliates->arm_status,
                                'arm_start_date_time' => $affiliates->arm_start_date_time,
                                'arm_end_date_time' => $affiliates->arm_end_date_time
                            );

                            $wpdb->insert($tbl_affiliates_main, $arm_add_affiliate);

                        }

                    }

                }

                $wpdb->query("DROP TABLE IF EXISTS $tbl_affiliates ");

            }

        }

    }

    if(version_compare($armaff_newdbversion, '2.5', '<'))
    {
        global $wpdb, $arm_affiliate, $ARMember;

        $args = array(
            'role' => 'administrator',
            'fields' => 'id'
        );
        $users = get_users($args);
        if (count($users) > 0) {
            foreach ($users as $key => $user_id) {
                $armaffroles = $arm_affiliate->arm_aff_capabilities();
                $userObj = new WP_User($user_id);
                foreach ($armaffroles as $armaffrole => $armaffroledescription) {
                    $userObj->add_cap($armaffrole);
                }
                unset($armaffrole);
                unset($armaffroles);
                unset($armaffroledescription);
            }
        }

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $charset_collate = '';

        if ($wpdb->has_cap('collation')) {
            if (!empty($wpdb->charset)) {
                $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            }
            

            if (!empty($wpdb->collate)){
                $charset_collate .= " COLLATE $wpdb->collate";
            }
            
        }

        $tbl_affiliates_commision = $wpdb->prefix . 'arm_aff_affiliates_commision';

        $create_tbl_affiliates_commision = "CREATE TABLE IF NOT EXISTS `{$tbl_affiliates_commision}` (
                armaff_setup_id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                armaff_affiliate_id bigint(20) NOT NULL,
                armaff_user_id bigint(20) NOT NULL,
                armaff_referral_type tinyint(4) DEFAULT '0' NOT NULL,
                armaff_referral_rate double DEFAULT '0' NOT NULL,
                armaff_recurring_referral_status BOOLEAN DEFAULT FALSE NOT NULL,
                armaff_recurring_referral_type tinyint(4) DEFAULT '0' NOT NULL,
                armaff_recurring_referral_rate double DEFAULT '0' NOT NULL,
                armaff_added_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
            ) {$charset_collate};";

            dbDelta( $create_tbl_affiliates_commision );

            $wpdb->query("ALTER TABLE `{$arm_affiliate->tbl_arm_aff_referrals}` ADD `arm_revenue_amount` DOUBLE NULL DEFAULT NULL AFTER `arm_woo_order`");

            $arm_plans = $wpdb->get_results("SELECT `arm_subscription_plan_id`, `arm_subscription_plan_amount`, `arm_subscription_plan_options` FROM {$ARMember->tbl_arm_subscription_plans}", ARRAY_A);

            $arm_plans_amount = array();

            if(!empty($arm_plans)){
                foreach ($arm_plans as $key => $value) {

                    $arm_plans_amount[$value['arm_subscription_plan_id']] = $value;
                    $arm_plans_amount[$value['arm_subscription_plan_id']]['arm_subscription_plan_options'] = @unserialize($value['arm_subscription_plan_options']);

                }
            }

            $arm_referrals = $wpdb->get_results("SELECT `arm_referral_id`, `arm_affiliate_id`, `arm_plan_id`, `arm_amount`, `arm_ref_affiliate_id` FROM {$arm_affiliate->tbl_arm_aff_referrals} ORDER BY `arm_referral_id` ASC", ARRAY_A);

            $armaffiliates_referral = array();

            if(!empty($arm_referrals)){

                foreach ($arm_referrals as $key => $referal_arr) {
                    $armrevenue_amount = 0;

                    $referral_amount = $referal_arr['arm_amount'];
                    $referral_plan = $referal_arr['arm_plan_id'];

                    if($referral_plan > 0){
                        $armplan_amount = $arm_plans_amount[$referral_plan]['arm_subscription_plan_amount'];

                        $arm_planoptions = $arm_plans_amount[$referral_plan]['arm_subscription_plan_options'];
                        $armplan_affiliate_type = $arm_planoptions['arm_affiliate_referral_type'];
                        $armplan_affiliate_rate = $arm_planoptions['arm_affiliate_referral_rate'];

                        if(isset($armaffiliates_referral["arm_".$referal_arr['arm_affiliate_id']."_".$referal_arr['arm_plan_id']."_".$referal_arr['arm_ref_affiliate_id']])){

                            if(isset($arm_planoptions['arm_affiliate_recurring_referral_disable'])){
                                $armplan_affiliate_type = $arm_planoptions['arm_affiliate_recurring_referral_type'];
                                $armplan_affiliate_rate = $arm_planoptions['arm_affiliate_recurring_referral_rate'];
                            }

                        } else {

                            if(isset($arm_planoptions['trial'])){
                                if(isset($arm_planoptions['trial']['is_trial_period']) && $arm_planoptions['trial']['is_trial_period'] == 1){
                                    $armplan_amount = $arm_planoptions['trial']['amount'];
                                }
                            }

                        }

                        $armrevenue_amount = $armplan_amount;

                        if($armplan_affiliate_type == 'percentage'){
                            $armrevenue_amount = ( $referral_amount * 100 ) / $armplan_affiliate_rate;
                        }
                    }

                    $armaffiliates_referral["arm_".$referal_arr['arm_affiliate_id']."_".$referal_arr['arm_plan_id']."_".$referal_arr['arm_ref_affiliate_id']] = $referal_arr['arm_referral_id'];

                    $armupdate_referrals = $wpdb->query( "UPDATE `$arm_affiliate->tbl_arm_aff_referrals` SET arm_revenue_amount='".$armrevenue_amount."' WHERE arm_referral_id = '" . $referal_arr['arm_referral_id'] ."' ");

                }

            }


    }

    if(version_compare($armaff_newdbversion, '2.7', '<'))
    {
        global $wpdb, $arm_affiliate, $ARMember;

        $tbl_affiliates_arm_coupons= $wpdb->prefix . 'arm_coupons';
        $tbl_arm_coupons_data = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$tbl_affiliates_arm_coupons."' AND column_name = 'arm_coupon_aff_user' AND TABLE_SCHEMA='".DB_NAME."' "  );
        if(empty($tbl_arm_coupons_data)) {
            $add_aff_column_sql = "ALTER TABLE " . $tbl_affiliates_arm_coupons . " ADD arm_coupon_aff_user INT NOT NULL AFTER arm_coupon_status;";
            $wpdb->query($add_aff_column_sql);
        }
    }

    update_option('arm_affiliate_version', '3.2');

    global $armaff_newdbversion;
    $armaff_newdbversion = '3.2';