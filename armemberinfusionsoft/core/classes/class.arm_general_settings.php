<?php
if (!class_exists('ARM_General_Setting_Infusionsoft')) {

    class ARM_General_Setting_Infusionsoft {

        function __construct() {

            add_action('arm_add_new_optins', array($this, 'arm_infusionsoft_add_general_setting'));

            add_action('arm_update_add_on_opt_in_settings', array($this, 'arm_infusionsoft_update_add_on_opt_in_settings'), 10, 1);

            add_action('arm_add_on_opt_ins_options', array($this, 'arm_infusionsoft_add_on_opt_ins_options'), 10, 1);

            add_action('arm_after_add_new_user', array($this, 'arm_infusionsoft_add_user_to_infusionsoft_contact_list'), 10, 2);
            
            add_filter('arm_opt_ins_details', array($this, 'arm_opt_ins_infusionsoft_details'), 10, 1);
        }
        
        function arm_infusionsoft_add_general_setting() {
            if (file_exists(ARM_INFUSIONSOFT_VIEWS_DIR . '/arm_general_setting_infusionsoft.php')) {
                require_once( ARM_INFUSIONSOFT_VIEWS_DIR . '/arm_general_setting_infusionsoft.php' );
            }
        }

        function arm_infusionsoft_update_add_on_opt_in_settings($posted_data) {
            if (isset($posted_data['action']) && $posted_data['action'] == 'arm_update_opt_ins_settings') {
                $arm_infusionsoft_appname = $posted_data['arm_infusionsoft_appname'];
                $arm_infusionsoft_apikey = $posted_data['arm_infusionsoft_apikey'];
                
                if (isset($arm_infusionsoft_appname))
                {
                    update_option('arm_infusionsoft_appname', $arm_infusionsoft_appname);
                }
                if(isset($arm_infusionsoft_apikey)) {
                    update_option('arm_infusionsoft_apikey', $arm_infusionsoft_apikey);
                }
            }
        }
        
        function arm_infusionsoft_add_user_to_infusionsoft_contact_list($user_id = 0, $posted_data = array()) {
            global $ARMember, $arm_is_social_signup;
            $arm_infusionsoft_app_name = get_option( 'arm_infusionsoft_appname');
            $arm_infusionsoft_api_key = get_option( 'arm_infusionsoft_apikey');

            if ( empty( $arm_infusionsoft_app_name ) || empty( $arm_infusionsoft_api_key ) ) {
                    return;
            }

            if(empty($posted_data))
            {
                return;
            }

            $form_settings = array();
            $armform = $posted_data['arform_object'];

            if ($armform != NULL) {
                $form_settings = $armform->settings;
            }
           
            $infusionsoftStatus = 0;
            if(!empty($form_settings))
            {
                if (isset($form_settings['email']['infusionsoft'])) {
                    $infusionsoftStatus = (isset($form_settings['email']['infusionsoft']['status'])) ? $form_settings['email']['infusionsoft']['status'] : 0;  
                }
            } else if($arm_is_social_signup) {
                $infusionsoftStatus = 1;
            } else {
                return;
            }
            

            if($infusionsoftStatus == 1)
            {
                // Configure a new InfusionSoft connection
                $app = new iSDK();

                // If no connection is made, get out of here.
                if ( !( $app->cfgCon($arm_infusionsoft_app_name) ) ) {
                    return;
                }

                if (is_numeric($user_id) && !is_array($user_id) && $user_id != 0 && !empty($posted_data)) {
                $userData = $posted_data['user_data'];
                // Assemble the contact data
                $contact_data = array(
                        'FirstName' => ( !empty($userData['firstname']) ) ? $userData['firstname'] : '',
                        'LastName' => ( !empty($userData['lastname']) ) ? $userData['lastname'] : '',
                        //'Company' => ( !empty($userData['infusionsoft-company']) ) ? $posted_data['infusionsoft-company'] : '',
                        'Email' => $userData['email'],
                       // 'Phone1' => ( !empty($posted_data['infusionsoft-phone']) ) ? $posted_data['infusionsoft-phone'] : '',
                       // 'ContactNotes' => ( !empty($posted_data['infusionsoft-notes']) ) ? $posted_data['infusionsoft-notes'] : '',
                       // 'Website' => ( !empty($posted_data['infusionsoft-website']) ) ? $posted_data['infusionsoft-website'] : '',
                );
                // Add the contact to InfusionSoft, with a duplicate check
                $contact_id = $app->addWithDupCheck($contact_data, 'EmailAndName');

                $reason = get_bloginfo('name') . ' Website Signup Form';
                // And allow them to receive email marketing
                $set_optin_status = $app->optIn($userData['email'], $reason);
            }
            }
        }
        
        function arm_infusionsoft_add_on_opt_ins_options($form_settings = array()) {
            if (!empty($form_settings)) {
                $arm_infusionsoft_app_name = get_option( 'arm_infusionsoft_appname');
                $arm_infusionsoft_api_key = get_option( 'arm_infusionsoft_apikey');

                if ( empty( $arm_infusionsoft_app_name ) || empty( $arm_infusionsoft_api_key ) ) {
                        return;
                }
                
                $infusionsoftStatus = (isset($form_settings['email']['infusionsoft']['status'])) ? $form_settings['email']['infusionsoft']['status'] : 0;
                    ?>
                    <div class="arm_etool_options_container">
                        <label>
                            <input type="checkbox" id="arm_etool_option_infusionsoft" name="arm_form_settings[email][infusionsoft][status]" value="1" class="arm_icheckbox arm_form_email_tool_radio" data-type="infusionsoft" <?php checked($infusionsoftStatus, '1'); ?>><span><?php _e('Infusionsoft', ARM_INFUSIONSOFT_TXTDOMAIN); ?></span>
                        </label>
                        
                    </div>
                    <?php
            }
        }
        
        function arm_opt_ins_infusionsoft_details($email_tools) {
            $arm_infusionsoft_app_name = get_option( 'arm_infusionsoft_appname');
            $arm_infusionsoft_api_key = get_option( 'arm_infusionsoft_apikey');

            if ( empty( $arm_infusionsoft_app_name ) || empty( $arm_infusionsoft_api_key ) ) {
                    return $email_tools;
            }
            $infusionsoftStatus = 1;
                $email_tools['infusionsoft'] = array(
                'appname' => $arm_infusionsoft_app_name,
                'appkey' => $arm_infusionsoft_api_key,
                'optins_name' => 'Infusionsoft',
                'status' => $infusionsoftStatus,
                'list_id' => 0,
                'list' => Array(),
            );
            return $email_tools;
        }
    }

    global $arm_general_setting_infusionsoft;
    $arm_general_setting_infusionsoft = new ARM_General_Setting_Infusionsoft();
}