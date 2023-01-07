<?php
if(!class_exists('arm_zapier_settings')){
    
    class arm_zapier_settings{

        function __construct(){
            
            add_action( 'wp_ajax_arm_zapier_save_settings', array( $this, 'arm_zapier_save_settings' ) );
            
            add_action( 'arm_after_add_new_user', array( $this, 'arm_zapier_user_register' ), 10, 2 );
            
            add_action( 'arm_after_user_plan_renew', array( $this, 'arm_zapier_user_renew_plan' ), 10, 2);
            //add_action( 'arm_after_renew_user_plans', array( $this, 'arm_zapier_user_renew_plan' ), 10, 2);
            
            add_action( 'arm_after_user_plan_change', array( $this, 'arm_zapier_user_change_plan' ), 10, 2);
            //add_action( 'arm_after_change_user_plans', array( $this, 'arm_zapier_user_change_plan' ), 10, 2);
            
            add_action( 'arm_after_user_plan_change_by_admin', array( $this, 'arm_zapier_user_change_plan'), 10, 2);
            
            add_action( 'delete_user', array( $this, 'arm_zapier_user_delete'), 10, 1 ); 

            add_action( 'arm_update_profile_external', array( $this, 'arm_zapier_user_update_profile' ), 10, 2 );

            add_action( 'arm_cancel_subscription_gateway_action', array( $this, 'arm_zapier_user_cancel_plan_action' ), 10, 2);
            
	        add_action( 'arm_after_cancel_subscription', array( $this, 'arm_zapier_user_plan_expire' ), 10, 4);
        }
        
        function arm_zapier_field_list() {
            global $arm_member_forms, $armform;
            
            $field_list = array('user_login' => 'Username',
                        'first_name' => 'First Name', 
                        'last_name' => 'Last Name',
                        'user_email' => 'Email Address',
                        'user_registered' => 'Joined date',
                        'roles' => 'Role',
                        'plan_id' => 'Plan Id',
                        'plan_name' => 'Plan Name',
                        'member_status' => 'Member Status');
            
            
            $dbFormFields = $arm_member_forms->arm_get_db_form_fields(true);
            $exclude_keys = array(
                'first_name', 'last_name', 'user_login', 'user_email', 'user_pass', 'repeat_pass',
                'arm_user_plan', 'arm_last_login_ip', 'arm_last_login_date', 'roles', 'section', 
                'repeat_pass', 'repeat_email', 'social_fields', 'avatar', 'profile_cover'
            );

            $armform = new ARM_Form();
            if (!empty($arm_form_id) && $arm_form_id != 0) {
                $userRegForm = $arm_member_forms->arm_get_single_member_forms($arm_form_id);
                $arm_exists_form = $armform->arm_is_form_exists($user->arm_form_id);
                if( $arm_exists_form ){
                    $armform->init((object) $userRegForm);
                }
            }

            if (!empty($dbFormFields)) {
                foreach ($dbFormFields as $meta_key => $field) {
                    $field_options = maybe_unserialize($field);
                    $field_options = apply_filters('arm_change_field_options', $field_options);
                    $meta_key = isset($field_options['meta_key']) ? $field_options['meta_key'] : $field_options['id'];
                    $field_id = $meta_key . arm_generate_random_code();
                    if (!in_array($meta_key, $exclude_keys) && !in_array($field_options['type'], array('section', 'roles', 'html', 'hidden', 'submit', 'repeat_pass', 'repeat_email','arm_captcha'))) {
                        $field_options['id'] = ($field_options['id'] == 'user_url') ? 'website_url' : $field_options['id'];
                        $field_options['id'] = ($field_options['id'] == 'description') ? 'biography' : $field_options['id'];

                        /* $field_options['label'] */
                        /* $field_options['value'] */
                        $field_list[$field_options['id']] = $field_options['label'];
                    }
                }
            }
            
            /**** arm get social fields ****/
            $socialProfileFields = $arm_member_forms->arm_social_profile_field_types();
            if (!empty($socialProfileFields)) {
                foreach ($socialProfileFields as $spfKey => $spfLabel) {
                    $spfMetaKey = 'arm_social_field_'.$spfKey;
                    /* $spfMetaValue = get_user_meta($user_id, $spfMetaKey, true); */
                    $field_list[$spfMetaKey] = $spfLabel;
                }
            }
            
            return $field_list;
        }
        
        function arm_zapier_save_settings() {
            global $ARMember;
            
            if(method_exists($ARMember, 'arm_check_user_cap') ){
                $arm_zapier_capabilites = 'arm_zapier_setting';
                $ARMember->arm_check_user_cap($arm_zapier_capabilites,'1');
            }
            $posted_data = $_POST;
            update_option( 'arm_zapier_setting', $posted_data );
            $response = array( 'type' => 'success', 'msg'=> __( 'Zapier settings saved successfully.', 'ARM_ZAPIER' ) );
            echo json_encode($response);
            die;
        }
        
        function arm_zapier_get_settings() {
            return get_option( 'arm_zapier_setting' );
        }
        
        function arm_zapier_get_footer() {
            $footer = '<div class="wrap arm_page arm_manage_members_main_wrapper" style="float:right; margin-right:20px;">';
            $footer .= '<a href="'.ARM_ZAPIER_URL.'/documentation" target="_blank">';
            $footer .= __('Documentation', 'ARM_ZAPIER');
            $footer .= '</a>';
            $footer .= '</div>';
            echo $footer;
        }
        
        /*
        function arm_zapier_webhook_handler_url() {
            return ARM_ZAPIER_URL . '/core/classes/arm_zapier_webhook_handler.php';
        }
        
        function arm_zapier_api_key() {
            $api_key = current_time('timestamp') . uniqid('', true);
            return md5($api_key);
        }
        */
        
        function arm_zapier_user_register( $user_id, $posted_data = '' ) {
            $zapier_data = $this->arm_zapier_get_settings();
            $arm_zapier_user_register = isset($zapier_data['arm_zapier_user_register']) ? $zapier_data['arm_zapier_user_register'] : '0';
            $arm_zapier_user_register_webhook_url = isset($zapier_data['arm_zapier_user_register_webhook_url']) ? $zapier_data['arm_zapier_user_register_webhook_url'] : '';
            if( $arm_zapier_user_register != '0' && $arm_zapier_user_register_webhook_url != '' )
            {                
                $plan_id = (!empty($posted_data['subscription_plan'])) ? $posted_data['subscription_plan'] : 0;
                
                $data = $this->arm_zapier_get_user_data( $zapier_data, $user_id, $plan_id );
                
                $this->arm_zapier_send_data( $arm_zapier_user_register_webhook_url, $data );
            }
        }

        function arm_zapier_user_update_profile( $user_id, $posted_data = '' ) {
            $zapier_data = $this->arm_zapier_get_settings();
            $arm_zapier_update_profile = isset($zapier_data['arm_zapier_update_profile']) ? $zapier_data['arm_zapier_update_profile'] : '0';
            $arm_zapier_user_profile_webhook_url = isset($zapier_data['arm_zapier_user_profile_webhook_url']) ? $zapier_data['arm_zapier_user_profile_webhook_url'] : '';
            if( $arm_zapier_update_profile != '0' && $arm_zapier_user_profile_webhook_url != '' )
            {           
                $plan_ids = get_user_meta($user_id, 'arm_user_plan_ids', true);
                
                $plan_id = (!empty($plan_ids[0])) ? $plan_ids[0] : 0;
                
                $data = $this->arm_zapier_get_user_data( $zapier_data, $user_id, $plan_id );
                
                $this->arm_zapier_send_data( $arm_zapier_user_profile_webhook_url, $data );
            }
        }
        
        function arm_zapier_user_renew_plan( $user_id, $plan_id ) {

            if (is_user_logged_in() || !empty($user_id)) {
                $zapier_data = $this->arm_zapier_get_settings();
                $arm_zapier_user_renew_plan = isset($zapier_data['arm_zapier_user_renew_plan']) ? $zapier_data['arm_zapier_user_renew_plan'] : '0';
                $arm_zapier_user_renew_plan_webhook_url = isset($zapier_data['arm_zapier_user_renew_plan_webhook_url']) ? $zapier_data['arm_zapier_user_renew_plan_webhook_url'] : '';
                if( $arm_zapier_user_renew_plan != '0' && $arm_zapier_user_renew_plan_webhook_url != '' )
                {
                    $data = $this->arm_zapier_get_user_data( $zapier_data, $user_id, $plan_id );

                    $this->arm_zapier_send_data( $arm_zapier_user_renew_plan_webhook_url, $data );
                }
            }
        }
        
        function arm_zapier_user_change_plan( $user_id, $new_plan_id ) {

            if (is_user_logged_in() || !empty($user_id)) {
                $zapier_data = $this->arm_zapier_get_settings();
                $arm_zapier_user_change_plan = isset($zapier_data['arm_zapier_user_change_plan']) ? $zapier_data['arm_zapier_user_change_plan'] : '0';
                $arm_zapier_user_change_plan_webhook_url = isset($zapier_data['arm_zapier_user_change_plan_webhook_url']) ? $zapier_data['arm_zapier_user_change_plan_webhook_url'] : '';
                if( $arm_zapier_user_change_plan != '0' && $arm_zapier_user_change_plan_webhook_url != '' )
                {
                    $data = $this->arm_zapier_get_user_data( $zapier_data, $user_id, $new_plan_id );

                    $this->arm_zapier_send_data( $arm_zapier_user_change_plan_webhook_url, $data );
                }
            }
        }
        
        function arm_zapier_user_delete( $user_id ){
            $zapier_data = $this->arm_zapier_get_settings();
            $arm_zapier_user_delete = isset($zapier_data['arm_zapier_user_delete']) ? $zapier_data['arm_zapier_user_delete'] : '0';
            $arm_zapier_user_delete_webhook_url = isset($zapier_data['arm_zapier_user_delete_webhook_url']) ? $zapier_data['arm_zapier_user_delete_webhook_url'] : '';
            if( $arm_zapier_user_delete != '0' && $arm_zapier_user_delete_webhook_url != '' )
            {
                $data = $this->arm_zapier_get_user_data( $zapier_data, $user_id );
                $this->arm_zapier_send_data( $arm_zapier_user_delete_webhook_url, $data );
            }
        }

        function arm_zapier_user_cancel_plan_action( $user_id, $new_plan_id ) {
            if (!empty($user_id)) {
                $zapier_data = $this->arm_zapier_get_settings();
                $arm_zapier_user_cancel_plan = isset($zapier_data['arm_zapier_user_cancel_plan']) ? $zapier_data['arm_zapier_user_cancel_plan'] : '0';
                $arm_zapier_user_cancel_plan_webhook_url = isset($zapier_data['arm_zapier_user_cancel_plan_webhook_url']) ? $zapier_data['arm_zapier_user_cancel_plan_webhook_url'] : '';
                if( $arm_zapier_user_cancel_plan != '0' && $arm_zapier_user_cancel_plan_webhook_url != '' )
                {
                    $data = $this->arm_zapier_get_user_data( $zapier_data, $user_id, $new_plan_id );

                    $this->arm_zapier_send_data( $arm_zapier_user_cancel_plan_webhook_url, $data );
                }
            }
        }

        function arm_zapier_user_plan_expire($user_id, $plan, $cancel_plan_action, $planData)
        {
            global $arm_subscription_cancel_msg;

            if (!empty($user_id) && $cancel_plan_action != 'on_expire' && empty($arm_subscription_cancel_msg) && !$plan->is_paid() && $plan->is_lifetime() && !$plan->is_recurring()) 
            {
                $zapier_data = $this->arm_zapier_get_settings();
                $arm_zapier_user_cancel_plan = isset($zapier_data['arm_zapier_user_cancel_plan']) ? $zapier_data['arm_zapier_user_cancel_plan'] : '0';
                $arm_zapier_user_cancel_plan_webhook_url = isset($zapier_data['arm_zapier_user_cancel_plan_webhook_url']) ? $zapier_data['arm_zapier_user_cancel_plan_webhook_url'] : '';
                if( $arm_zapier_user_cancel_plan != '0' && $arm_zapier_user_cancel_plan_webhook_url != '' )
                {
                    
                    $data = $this->arm_zapier_get_user_data( $zapier_data, $user_id, $plan->ID );

                    $this->arm_zapier_send_data( $arm_zapier_user_cancel_plan_webhook_url, $data );
                }
            }
        }
        
        function arm_zapier_get_user_data( $zapier_data, $user_id, $plan_id = 0 ){
            global $arm_members_class, $arm_subscription_plans, $armform, $arm_member_forms, $ARMember;
            
            $user = $arm_members_class->arm_get_member_detail($user_id);
            $primary_status = arm_get_member_status($user_id);
            $secondary_status = arm_get_member_status($user_id, 'secondary');
            $planID = ($plan_id > 0) ? $plan_id : get_user_meta($user_id, 'arm_user_plan', true);
            if(empty($planID))
            {
                $planID = get_user_meta($user_id, 'arm_user_last_plan', true);
            }

            $plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($planID, true);

            $data = array();
            $data['first_name']         = $user->first_name;
            $data['last_name']          = $user->last_name;
            $data['user_login']         = $user->user_login;
            $data['user_email']         = $user->user_email;
            $data['user_registered']    = $user->user_registered;
            $data['roles']              = $user->roles[0];
            $data['plan_id']            = $planID;
            $data['plan_name']          = $plan_name;
            $data['member_status']      = $primary_status;

            $dbFormFields = $arm_member_forms->arm_get_db_form_fields(true);
            $exclude_keys = array(
                'first_name', 'last_name', 'user_login', 'user_email', 'user_pass', 'repeat_pass',
                'arm_user_plan', 'arm_last_login_ip', 'arm_last_login_date', 'roles', 'section', 
                'repeat_pass', 'repeat_email', 'social_fields', 'avatar', 'profile_cover'
            );

            $armform = new ARM_Form();
            if (!empty($arm_form_id) && $arm_form_id != 0) {
                $userRegForm = $arm_member_forms->arm_get_single_member_forms($arm_form_id);
                $arm_exists_form = $armform->arm_is_form_exists($user->arm_form_id);
                if( $arm_exists_form ){
                    $armform->init((object) $userRegForm);
                }
            }

            if (!empty($dbFormFields)) {
                foreach ($dbFormFields as $meta_key => $field) {
                    $field_options = maybe_unserialize($field);
                    $field_options = apply_filters('arm_change_field_options', $field_options);
                    $meta_key = isset($field_options['meta_key']) ? $field_options['meta_key'] : $field_options['id'];
                    $field_id = $meta_key . arm_generate_random_code();
                    if (!in_array($meta_key, $exclude_keys) && !in_array($field_options['type'], array('section', 'roles', 'html', 'hidden', 'submit', 'repeat_pass', 'repeat_email'))) {
                        $field_options['id'] = ($field_options['id'] == 'user_url') ? 'website_url' : $field_options['id'];
                        $field_options['id'] = ($field_options['id'] == 'description') ? 'biography' : $field_options['id'];

                        /* $field_options['label'] */
                        /* $field_options['value'] */
                        $data[$field_options['id']] = $user->$meta_key;
                    }
                }
            }
            
            /**** arm get social fields ****/
            $socialProfileFields = $arm_member_forms->arm_social_profile_field_types();
            if (!empty($socialProfileFields)) {
                foreach ($socialProfileFields as $spfKey => $spfLabel) {
                    $spfMetaKey = 'arm_social_field_'.$spfKey;
                    $spfMetaValue = get_user_meta($user_id, $spfMetaKey, true);
                    $data[$spfMetaKey] = $spfMetaValue;
                }
            }
            
            $arm_zapier_selected_fiedls = isset( $zapier_data['arm_zapier_fields'] ) ? $zapier_data['arm_zapier_fields'] : array();
            $filter_data = array();
            if( !empty( $arm_zapier_selected_fiedls ) ) {
                foreach($data as $data_key => $data_val)
                {
                    if(!in_array($data_key, $arm_zapier_selected_fiedls))
                    {
                        unset($data[$data_key]);
                    }
                }
            }
            
            return $data;
        }
        
        function arm_zapier_send_data( $webhook_url, $data ) {
            global $ARMember;
            /* $ARMember->arm_write_response('zapier webhook url '.$webhook_url); */
            $arguments = array(
                'method' => 'POST',
                'timeout' => 120,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
                'body' => json_encode( $data ),
            );

            $response = wp_remote_post($webhook_url, $arguments);
            $res_data = json_decode($response['body'], true);
            
            /* $ARMember->arm_write_response("Zapier Data : ".maybe_serialize($response)); */
            
            if ( is_wp_error( $response ) ) {
                /* $ARMember->arm_write_response("Zapier send data error : ".$response->get_error_message()); */
            }
        }
        
    }
}

global $arm_zapier_settings;
$arm_zapier_settings = new arm_zapier_settings();
?>