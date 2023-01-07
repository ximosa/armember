<?php
if(!class_exists('arm_affiliate_settings')){
    
    class arm_affiliate_settings{

        function __construct(){
            add_action( 'wp_ajax_arm_affiliate_settings', array( $this, 'save_arm_affiliate_settings' ) );
            
            add_filter('arm_notification_add_message_types', array( $this, 'arm_set_notification_message_type' ), 10, 1 );
            
            add_filter('arm_notification_get_list_msg_type', array( $this, 'arm_set_notification_list_msg_type' ), 10, 1 );

        }

        function save_arm_affiliate_settings(){
            global $wpdb, $arm_affiliate, $arm_global_settings, $ARMember;
            $posted_data = $_POST;

            if(method_exists($ARMember, 'arm_check_user_cap'))
            {
                $arm_affiliate_capabilities = 'arm_affiliate_option';
                $ARMember->arm_check_user_cap($arm_affiliate_capabilities,'1');
            }

            $armaff_form_style = isset($posted_data['armaff_form_style']) ? $posted_data['armaff_form_style'] : 'material';
            $armaff_form_style = ($armaff_form_style != '') ? $armaff_form_style : 'material';
            $armaff_form_title = isset($posted_data['armaff_form_title']) ? $posted_data['armaff_form_title'] : 'Create Affiliate Account';
            $armaff_form_title = ($armaff_form_title != '') ? $armaff_form_title : 'Create Affiliate Account';
            $armaff_display_field_label = isset($posted_data['armaff_field_display_label']) ? $posted_data['armaff_field_display_label'] : array();
            $armaff_required_field = isset($posted_data['armaff_field_required']) ? $posted_data['armaff_field_required'] : array();

            unset($posted_data['armaff_form_style']);
            unset($posted_data['armaff_form_title']);
            unset($posted_data['armaff_field_display_label']);
            unset($posted_data['armaff_field_required']);

            update_option( 'arm_affiliate_setting', $posted_data );
            update_option( 'armaff_flush_rewrites', '1' );

            $armaff_template_affiliate = $wpdb->get_row( $wpdb->prepare( "SELECT `arm_form_id`, `arm_form_fields` FROM {$arm_affiliate->tbl_arm_aff_forms} WHERE `arm_form_slug` = '%s' LIMIT 1;", 'create_user_affiliate' ) );
            if($armaff_template_affiliate){
                $armaff_form_id = $armaff_template_affiliate->arm_form_id;
                $armaff_form_fields = (isset($armaff_template_affiliate->arm_form_fields)) ? $armaff_template_affiliate->arm_form_fields : '';
                $armaff_form_fields = ($armaff_form_fields != '') ? json_decode($armaff_form_fields) : array();
                foreach ($armaff_form_fields as $armaff_field => $armaff_field_options) {

                    if(isset($armaff_display_field_label[$armaff_field])) {
                        $armaff_field_options->display_label = $armaff_display_field_label[$armaff_field];
                    }

                    if(isset($armaff_required_field[$armaff_field])) {
                        $armaff_field_options->required = $armaff_required_field[$armaff_field];
                    } else {
                        $armaff_field_options->required = 0;
                    }

                }

                $armaff_form_fields = json_encode($armaff_form_fields);

                $wpdb->update($arm_affiliate->tbl_arm_aff_forms,
                    array( 'arm_form_title' => $armaff_form_title, 'arm_form_style' => $armaff_form_style, 'arm_form_fields' => $armaff_form_fields ),
                    array( 'arm_form_id' => $armaff_form_id ),
                    array( '%s', '%s', '%s' ),
                    array( '%d' )
                );
            }

            $armaff_ref_url = isset($posted_data['arm_aff_referral_url']) ? $posted_data['arm_aff_referral_url'] : get_home_url();
            $armaff_is_active_fancy_url = isset($posted_data['arm_aff_allow_fancy_url']) ? $posted_data['arm_aff_allow_fancy_url'] : 0;
            $armaff_referral_var = $posted_data['arm_aff_referral_var'];

            $arm_aff_id_encoding = $posted_data['arm_aff_id_encoding'];
            if($arm_aff_id_encoding=='username')
            {
                $arm_aff_id_encoding_str = "{username}";
            }
            else {
                $arm_aff_id_encoding_str = "{affiliate_id}";
            }
            if($armaff_is_active_fancy_url){
                $armaff_url= parse_url( $armaff_ref_url );
                $armaff_query_string = array_key_exists( 'query', $armaff_url ) ? '?' . $armaff_url['query'] : '';
                $armaff_url_scheme      = isset( $armaff_url['scheme'] ) ? $armaff_url['scheme'] : 'http';
                $armaff_url_host        = isset( $armaff_url['host'] ) ? $armaff_url['host'] : '';
                $armaff_constructed_url = $armaff_url_scheme . '://' . $armaff_url_host . $armaff_url['path'];
                $armaff_base_url = $armaff_constructed_url;

                $armaff_ref_url = trailingslashit( $armaff_base_url ) . trailingslashit($armaff_referral_var) . $arm_aff_id_encoding_str . $armaff_query_string;
            } else {
                $armaff_ref_url = $arm_global_settings->add_query_arg($armaff_referral_var, $arm_aff_id_encoding_str, $armaff_ref_url);
            }

            $response = array( 'type' => 'success', 'msg'=> __( 'Affiliate settings saved successfully.', 'ARM_AFFILIATE' ), 'armaff_ref_url' => $armaff_ref_url );
            echo json_encode($response);
            die;
        }
        
        function get_arm_affiliate_settings(){
            return get_option( 'arm_affiliate_setting' );
        }
        
        function share_affiliate_link($url, $allowed_network, $mail_body = '')
        {
            $allowed_network = explode(',', rtrim($allowed_network, ','));
            $content = '';
            if (in_array('facebook', $allowed_network)) {
                $content .= "<div class='arm_aff_share arm_facebook'><a href='javascript:void(0)' onclick='javascript:window.open(\"\https://www.facebook.com/sharer.php?u={$url}\", \"_blank\", \"scrollbars=1,resizable=1,height=475,width=625\");'><i class='armfa armfa-facebook'> </i></a></div>";
            }
            if (in_array('twitter', $allowed_network)) {
                $content .= "<div class='arm_aff_share arm_twitter'><a href='javascript:void(0)' onclick='javascript:window.open(\"\https://twitter.com/share?url={$url}\", \"_blank\", \"scrollbars=1,resizable=1,height=475,width=625\");'><i class='armfa armfa-twitter'> </i></a></div>";
            }
            if (in_array('linkedin', $allowed_network)) {
                $content .= "<div class='arm_aff_share arm_linked'><a href='javascript:void(0)' onclick='javascript:window.open(\"\https://www.linkedin.com/cws/share?url={$url}\", \"_blank\", \"scrollbars=1,resizable=1,height=475,width=625\");'><i class='armfa armfa-linkedin'> </i></a></div>";
            }
            if (in_array('vkontakt', $allowed_network)) {
                $content .= "<div class='arm_aff_share arm_vk'><a href='javascript:void(0)' onclick='javascript:window.open(\"\http://vk.com/share.php?url={$url}\", \"_blank\", \"scrollbars=1,resizable=1,height=475,width=625\");'><i class='armfa armfa-vk'> </i></a></div>";
            }
            if (in_array('email', $allowed_network)) {
                $body = ($mail_body != '') ? urlencode($mail_body) : $url;
                $content .= "<div class='arm_aff_share arm_email'><a href='mailto:?body={$body}' ><i class='armfa armfa-envelope-o'> </i></a></div>";
            }
            return $content;
        }
        
        function arm_affiliate_get_user_id($arm_aff_id_encoding, $logged_in_user)
        {
            if($arm_aff_id_encoding === 'MD5')
            {
                return md5($logged_in_user);
            }
            else if($arm_aff_id_encoding === 'username')
            {
                global $wpdb, $arm_affiliate;
                $arm_actual_affiliate_user_id = $wpdb->get_var( $wpdb->prepare( "SELECT arm_user_id as encoding_id FROM $arm_affiliate->tbl_arm_aff_affiliates WHERE arm_affiliate_id = %s ", $logged_in_user ) );

                if(!empty($arm_actual_affiliate_user_id))
                {
                    $userdetails = get_user_by( 'id', $arm_actual_affiliate_user_id );
                }
                
                return isset($userdetails->user_login) ? $userdetails->user_login : '';
            }
            else {
                return $logged_in_user;
            }
        }
        
        function arm_get_actual_user_id($armaffiliate_id)
        {
            global $wpdb, $arm_affiliate;
            $arm_actual_affiliate_id = $wpdb->get_var( $wpdb->prepare( "SELECT arm_affiliate_id as encoding_id FROM $arm_affiliate->tbl_arm_aff_affiliates WHERE md5(arm_affiliate_id) = %s ", $armaffiliate_id ) );
            return $arm_actual_affiliate_id;
        }

        function arm_get_actual_affiliate_id_from_username($username)
        {
            global $wpdb, $arm_affiliate;
            
            $userdetails = get_user_by( 'login', $username );
            
            $arm_actual_affiliate_id = 0;
            if(!empty($userdetails))
            {
                $arm_user_id = isset($userdetails->ID) ? $userdetails->ID : 0;

                $arm_actual_affiliate_id = $wpdb->get_var( $wpdb->prepare( "SELECT arm_affiliate_id as encoding_id FROM $arm_affiliate->tbl_arm_aff_affiliates WHERE arm_user_id = %s ", $arm_user_id ) );
            }
            return $arm_actual_affiliate_id;
        }

        function arm_set_notification_message_type($message_types)
        {
            $message_types['arm_affiliate_notify_when_credited'] = __('Notify the affiliates when credited with a referral', 'ARM_AFFILIATE');
            $message_types['arm_admin_when_user_refer_to_other'] = __('Notify admin when one user refer to other user', 'ARM_AFFILIATE');
            $message_types['arm_when_user_referrals_invite_friend'] = __('On Affiliate user Refer/Invite friends', 'ARM_AFFILIATE');
	    $message_types['arm_notify_on_register_affiliate_account'] = __('On user register as affiliate account', 'ARM_AFFILIATE');
            return $message_types;
        }
        
        function arm_set_notification_list_msg_type($message_types)
        {
            if($message_types == 'arm_affiliate_notify_when_credited'){
                return __('Notify the affiliates when credited with a referral', 'ARM_AFFILIATE');
            }
            else if($message_types == 'arm_admin_when_user_refer_to_other'){
                return __('Notify admin when one user refer to other user', 'ARM_AFFILIATE');
            }
            else if($message_types == 'arm_when_user_referrals_invite_friend'){
                return __('On Affiliate user Refer/Invite friends', 'ARM_AFFILIATE');
            }
	    else if($message_types == 'arm_notify_on_register_affiliate_account'){
                return __('On user register as affiliate account', 'ARM_AFFILIATE');
            }
            return $message_types;
        }
        
        function arm_affiliate_get_footer() {
            $footer = '<div class="wrap arm_page arm_manage_members_main_wrapper" style="float:right; margin-right:20px;">';
            $footer .= '<a href="'.ARM_AFFILIATE_URL.'/documentation" target="_blank">';
            $footer .= __('Affiliate Add-on Documentation', 'ARM_AFFILIATE');
            $footer .= '</a>';
            $footer .= '</div>';
            echo $footer;
        }
    }
}

global $arm_affiliate_settings;
$arm_affiliate_settings = new arm_affiliate_settings();
?>