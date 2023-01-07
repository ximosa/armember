<?php

if (!class_exists('ARM_updates_cron')) {

    class ARM_updates_cron {

        function __construct() {

            global $wpdb, $ARMember, $arm_slugs;
            $arm_updates_cron_db_initialize = get_option('arm_updates_cron_db_initialize');
            if(!empty($arm_updates_cron_db_initialize))
            {
                add_filter('cron_schedules', array($this, 'arm_updates_cron_schedules'));
                add_action('init', array($this, 'arm_add_updates_cron'), 10);

                add_action('arm_handle_updates_db_migrate_data',array($this,'arm_handle_updates_db_migrate_data_func'));


                add_action('wp_ajax_arm_updates_cron_db_processing_notice',array($this,'arm_updates_cron_db_processing_notice'),10);
                add_action('wp_ajax_arm_updates_cron_db_completed_notice',array($this,'arm_updates_cron_db_completed_notice'),10);

                add_action('admin_init', array($this, 'arm_show_updates_cron_notice'), 1);

            }
            

        }

        function arm_show_updates_cron_notice() {
            global $arm_slugs;
            if (isset($_REQUEST['page']) && in_array($_REQUEST['page'], (array) $arm_slugs)) {
                if (!in_array($_REQUEST['page'], array($arm_slugs->manage_forms))) {
                    add_action('admin_notices', array($this, 'arm_updates_cron_admin_notices'));
                }
            }
        }

        function arm_updates_cron_admin_notices() {
            global $arm_slugs;

            $notice_html = '';
            $arm_allowed_slugs = (array) $arm_slugs;

            if(isset($_REQUEST['page']) && in_array($_REQUEST['page'], $arm_allowed_slugs))
            {
                $arm_updates_cron_db_notice = get_option('arm_updates_cron_db_notice');

                if( empty($arm_updates_cron_db_notice) )
                {
                    printf("<div class='notice notice-info arm_dismiss_update_db_notice' style='display:block;margin: 40px 40px 0px;border-left-color: var(--arm-pt-orange) !important;min-height:150px'><p style='font-size:calc(24px);'>". __('ARMember Database Update Required', 'ARMember')."</p><p>ARMember has been updated! To keep things running smoothly, we have to update your database to the newest version. The database update process runs in the background and may take a little while, so please be patient.</p><button class='armemailaddbtn' style='margin-top:15px;margin-bottom:20px;'>". __('Update ARMember Database', 'ARMember')."</button></div>");

                    printf("<div class='notice notice-info arm_dismiss_updated_db_notice' style='display:none;margin: 40px 40px 0px;border-left-color: var(--arm-pt-orange) !important;height:110px'><p style='font-size:calc(24px);'>". __('ARMember database Updation in Progress', 'ARMember')."</p><p>ARMember has started a data updation in background. The database update process may take a little while, so please be patient.</p></div>");
                }
                else if($arm_updates_cron_db_notice=="1")
                {
                    printf("<div class='notice notice-info arm_dismiss_updated_db_notice' style='display:block;margin: 40px 40px 0px;border-left-color: var(--arm-pt-orange) !important;height:110px'><p style='font-size:calc(24px);'>". __('ARMember database Updation in Progress', 'ARMember')."</p><p>ARMember has started a data updation in background. The database update process may take a little while, so please be patient.</p></div>");
                }
                else if($arm_updates_cron_db_notice=="2")
                {
                    printf("<div class='notice notice-info arm_dismiss_updated_data_notice' style='display:block;margin: 40px 40px 0px;border-left-color: var(--arm-pt-orange) !important;min-height:170px'><p style='font-size:calc(24px);'>". __('ARMember database updation process done', 'ARMember')."</p><p>ARMember database update complete. Thank you for updating to the latest version!</p><button class='armemailaddbtn' style='margin-top:15px;margin-bottom:20px;'>". __('Thanks!', 'ARMember')."</button></div>");
                }

                echo $notice_html;
            }
        }

        function arm_updates_cron_schedules($schedules)
        {
            if (!is_array($schedules)) {
                $schedules = array();
            }
            $schedules['arm_every_minute']=array('interval' => 60,'display'=>__('One Minute', 'ARMember'));
            return $schedules;
        }

        function arm_add_updates_cron() {
            global $wpdb, $ARMember, $arm_slugs, $arm_cron_hooks_interval, $arm_global_settings;
            //wp_get_schedules();
            
            $arm_updates_cron_db_notice = get_option('arm_updates_cron_db_notice');
            if($arm_updates_cron_db_notice < 2)
            {
                $hook = "arm_handle_updates_db_migrate_data";
                if (!wp_next_scheduled($hook)) {
                    wp_schedule_event(time(), 'arm_every_minute', $hook);
                }
            }
        }

        function arm_handle_updates_db_migrate_data_func()
        {
            $arm_updates_cron_db_notice = get_option('arm_updates_cron_db_notice');
            set_time_limit(0);
            if($arm_updates_cron_db_notice < 1)
            {
                return;
            }
            global $wp, $wpdb, $ARMember;
            
            $user_update_limit = 1000;
            $total_updated_users = get_option('arm_updates_cron_db_total_users_updated');
            $total_updated_users = empty($total_updated_users) ? 0 : $total_updated_users;
            
            $args = array(
                        'offset' => $total_updated_users,
                        'number' => $user_update_limit,
                        'order_by'=>'ASC'
                    );
            $users = get_users( $args );
            
            $total_users = count($users);
            if($total_users>0)
            {
                foreach($users as $user)
                {
                    $user_id = $user->data->ID;
                    //$user_id = $user['ID'];
                    $arm_user_plan_ids_value = get_user_meta($user_id,'arm_user_plan_ids',true);

                    $user_meta_value_array = array();
                    if(!empty($arm_user_plan_ids_value))
                    {
                        $user_meta_value_arr = maybe_unserialize($arm_user_plan_ids_value);
                        if(!empty($user_meta_value_arr) && is_array($user_meta_value_arr))
                        {
                            foreach($user_meta_value_arr as $arm_user_plan_id)
                            {
                                $user_meta_value_array[] = (int)$arm_user_plan_id;
                            }
                        }
                    }
                    $user_meta_value_array = maybe_serialize($user_meta_value_array);

                    $arm_user_suspended_plan_ids_value = get_user_meta($user_id,'arm_user_suspended_plan_ids',true);

                    $user_suspended_plan_meta_value_array = array();
                    if(!empty($arm_user_suspended_plan_ids_value))
                    {
                        $user_suspended_plan_meta_value_arr = maybe_unserialize($arm_user_suspended_plan_ids_value);
                        if(!empty($user_suspended_plan_meta_value_arr) && is_array($user_suspended_plan_meta_value_arr))
                        {
                            foreach($user_suspended_plan_meta_value_arr as $arm_user_plan_id)
                            {
                                $user_suspended_plan_meta_value_array[] = (int)$arm_user_plan_id;
                            }
                        }
                    }
                    $user_suspended_plan_meta_value_array = maybe_serialize($user_suspended_plan_meta_value_array);

                    $wpdb->update(
                            $ARMember->tbl_arm_members, 
                            array('arm_user_plan_ids' => $user_meta_value_array, 'arm_user_suspended_plan_ids' => $user_suspended_plan_meta_value_array), 
                            array('arm_user_id' => $user_id)
                    );

                    // update total user migrated to database
                    $total_updated_users = (int)$total_updated_users + 1;
                    update_option('arm_updates_cron_db_total_users_updated',$total_updated_users);
                }
            }
            else 
            {
                update_option('arm_updates_cron_db_notice',2);
                wp_clear_scheduled_hook("arm_handle_updates_db_migrate_data");
            }
            
        }

        function arm_updates_cron_db_processing_notice()
        {
            update_option('arm_updates_cron_db_notice',1);
            die();
        }

        function arm_updates_cron_db_completed_notice()
        {
            update_option('arm_updates_cron_db_notice',2);
            update_option('arm_updates_cron_db_initialize',0);
            wp_clear_scheduled_hook("arm_handle_updates_db_migrate_data");
            die();
        }
    }
}
global $arm_updates_cron;
$arm_updates_cron = new ARM_updates_cron();