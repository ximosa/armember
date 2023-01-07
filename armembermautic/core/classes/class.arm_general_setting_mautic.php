<?php

use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;

if (!class_exists('ARM_General_Setting_Mautic') && $arm_mautic->arm_mautic_is_version_compatible()) {

    class ARM_General_Setting_Mautic {

        function __construct() {

            add_action('arm_add_new_optins', array($this, 'arm_add_mautic_general_setting'));

            add_action('wp_ajax_arm_delete_mautic_config', array($this, 'arm_delete_mautic_config_func'), 10, 1);

            add_action('wp_ajax_arm_get_mautic_segemnets', array($this, 'arm_get_mautic_segemnets_func'));

            add_action('arm_update_add_on_opt_in_settings', array($this, 'arm_update_add_on_opt_in_settings_func'), 10, 1);

            add_action('arm_add_on_opt_ins_options', array($this, 'arm_add_on_opt_ins_options_func'), 10, 1);

            add_action('arm_after_add_new_user', array($this, 'arm_mautic_add_user_to_mautic_contact_list'), 10, 2);
            
            add_filter('arm_opt_ins_details', array($this, 'arm_opt_ins_mautic_details'), 10, 1);
        }

        function arm_mautic_add_user_to_mautic_contact_list($user_id = 0, $posted_data = array()) {
            global $arm_is_social_signup, $arm_social_feature;
            $arm_mautic_settings_ser = get_option('arm_mautic_settings');
            $arm_mautic_settings = ($arm_mautic_settings_ser != '') ? maybe_unserialize($arm_mautic_settings_ser) : array();
            if (!empty($arm_mautic_settings)) {

                $arm_mautic_status = (!empty($arm_mautic_settings)) ? $arm_mautic_settings['status'] : 0;
                if ($arm_mautic_status == 1) {

                    if (is_numeric($user_id) && !is_array($user_id) && $user_id != 0 && !empty($posted_data)) {


                        $armform = $posted_data['arform_object'];
                        $userData = $posted_data['user_data'];
                        $segment_id = 0;
                        if ($armform != NULL) {
                            $form_settings = $armform->settings;
                            $segment_id = (isset($form_settings['email']['mautic']['list_id']) && !empty($form_settings['email']['mautic']['list_id'])) ? $form_settings['email']['mautic']['list_id'] : 0;
                        } else if($arm_is_social_signup) {
                            $social_settings = $arm_social_feature->arm_get_social_settings();
                            if(isset($social_settings['options']['optins_name']) && $social_settings['options']['optins_name'] == 'mautic') {
                                $etool_name = isset($social_settings['options']['optins_name']) ? $social_settings['options']['optins_name'] : '';
                                $segment_id = isset($social_settings['options'][$etool_name]['list_id']) ? $social_settings['options'][$etool_name]['list_id'] : 0 ;
                            }
                        } else {
                            return;
                        }
 
                        if ($segment_id != 0 && $segment_id != '') {

                            /* $baseUrl = 'https://reputeinfo.mautic.net'; 
                              $version = 'OAuth2';
                              $publicKey ='15_1qxlq1in6fdwwwcc44sg4ogwgwcc8ssg4kgss80goksw84ccgo';
                              $secretKey = 't6mc9tb66dwokocss4w4gw08ccs8s48gckwwg8scoggg0wsos';
                              $callback = 'http://www.reputeinfosystems.net/wordpress31/wp-content/plugins/armembermautic/auth_mautic.php'; */

                            $baseUrl = ($arm_mautic_settings['base_url'] != '') ? $arm_mautic_settings['base_url'] : '';
                            $publicKey = ($arm_mautic_settings['public_key'] != '') ? $arm_mautic_settings['public_key'] : '';
                            $secretKey = ($arm_mautic_settings['secret_key'] != '') ? $arm_mautic_settings['secret_key'] : '';
                            $version = 'OAuth2';
                            $callback = ARM_MAUTIC_URL . '/auth_mautic.php';

                            if ($baseUrl != '' && $publicKey != '' && $secretKey != '') {
                                $settings = array(
                                    'baseUrl' => $baseUrl, // Base URL of the Mautic instance
                                    'version' => $version, // Version of the OAuth can be OAuth2 or OAuth1a. OAuth2 is the default value.
                                    'clientKey' => $publicKey, // Client/Consumer key from Mautic
                                    'clientSecret' => $secretKey, // Client/Consumer secret key from Mautic
                                    'callback' => $callback   // Redirect URI/Callback URI for this script
                                );


                                $auth = ApiAuth::initiate($settings);



                                $accessTokenData = get_option('arm_mautic_access_token_data');
                                if (isset($accessTokenData) && !empty($accessTokenData)) {
                                    $auth->setAccessTokenDetails(json_decode($accessTokenData, true));
                                }
                                $contactApi = MauticApi::getContext("contacts", $auth, $baseUrl . '/api/');
                                $contact = $contactApi->create($userData);


                                $contact_id = $contact['contact']['id'];

                                $segmentApi = MauticApi::getContext("segments", $auth, $baseUrl . '/api/');
                                $add_to_segment = $segmentApi->addContact($segment_id, $contact_id);
                            }
                        }
                    }
                }
            }
        }

        function arm_add_on_opt_ins_options_func($form_settings = array()) {
            if (!empty($form_settings)) {


                $mauticStatus = (isset($form_settings['email']['mautic']['status'])) ? $form_settings['email']['mautic']['status'] : 0;
                $arm_mautic_settings_ser = get_option('arm_mautic_settings');
                $arm_mautic_settings = ($arm_mautic_settings_ser != '') ? maybe_unserialize($arm_mautic_settings_ser) : array();
                $arm_mautic_lists = (!empty($arm_mautic_settings)) ? $arm_mautic_settings['lists'] : array();
                $arm_mautic_default_list_id = (!empty($arm_mautic_settings)) ? $arm_mautic_settings['default_list'] : 0;
                $arm_mautic_status = (!empty($arm_mautic_settings)) ? $arm_mautic_settings['status'] : 0;
                $mautic_list_id = (isset($form_settings['email']['mautic']['list_id'])) ? $form_settings['email']['mautic']['list_id'] : $arm_mautic_default_list_id;


                if ($arm_mautic_status == 1) {
                    ?>
                    <div class="arm_etool_options_container">
                        <label>
                            <input type="checkbox" id="arm_etool_option_mautic" name="arm_form_settings[email][mautic][status]" value="1" class="arm_icheckbox arm_form_email_tool_radio" data-type="mautic" <?php checked($mauticStatus, '1'); ?>><span><?php _e('Mautic', MEMBERSHIP_TXTDOMAIN); ?></span>
                        </label>
                        <div class="arm_etool_list_container <?php echo ($mauticStatus != 1) ? 'hidden_section' : ''; ?>">
                            <span><?php _e('Segment Name', MEMBERSHIP_TXTDOMAIN); ?>:&nbsp;&nbsp;</span>
                            <input type="hidden" id="mautic_list_name" name="arm_form_settings[email][mautic][list_id]" value="<?php echo $mautic_list_id; ?>"/>

                            <dl class="arm_selectbox column_level_dd">
                                <dt style="width:150px;"><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                <dd>
                                    <ul data-id="mautic_list_name" id="arm_mautic_list">
                                        <li data-label="<?php _e('Select Segment', MEMBERSHIP_TXTDOMAIN); ?>" data-value=""><?php _e('Select Segment', MEMBERSHIP_TXTDOMAIN); ?></li>
                    <?php if (!empty($arm_mautic_lists)) : ?>
                        <?php foreach ($arm_mautic_lists as $key => $format): ?>
                                                <li data-label="<?php echo $format['name']; ?>" data-value="<?php echo $format['id']; ?>"><?php echo $format['name']; ?></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                                    </ul>
                                </dd>
                            </dl>
                        </div>
                    </div>
                    <?php
                }
            }
        }

        function arm_update_add_on_opt_in_settings_func($posted_data) {
            if (isset($posted_data['action']) && $posted_data['action'] == 'arm_update_opt_ins_settings') {
                $arm_mautic_email_tools = isset($posted_data['arm_mautic_settings']) ? $posted_data['arm_mautic_settings'] : array();
                if (!empty($arm_mautic_email_tools)) {
                    $baseUrl = isset($arm_mautic_email_tools['base_url']) ? $arm_mautic_email_tools['base_url'] : '';
                    $publicKey = isset($arm_mautic_email_tools['public_key']) ? $arm_mautic_email_tools['public_key'] : '';
                    $secretKey = isset($arm_mautic_email_tools['secret_key']) ? $arm_mautic_email_tools['secret_key'] : '';
                    $status = isset($arm_mautic_email_tools['status']) ? $arm_mautic_email_tools['status'] : 0;
                    $list_id = isset($arm_mautic_email_tools['list_id']) ? $arm_mautic_email_tools['list_id'] : 0;
                    $arm_mautic_settings_ser = get_option('arm_mautic_settings');
                    $arm_mautic_settings = ($arm_mautic_settings_ser != '') ? maybe_unserialize($arm_mautic_settings_ser) : array();
                    $arm_mautic_lists = (!empty($arm_mautic_settings)) ? $arm_mautic_settings['lists'] : array();

                    $arm_muatic_settings_array = array('base_url' => $baseUrl,
                        'public_key' => $publicKey,
                        'secret_key' => $secretKey,
                        'status' => $status,
                        'lists' => $arm_mautic_lists,
                        'default_list' => $list_id
                    );

                    $arm_muatic_settings = maybe_serialize($arm_muatic_settings_array);
                    update_option('arm_mautic_settings', $arm_muatic_settings);
                }
            }
        }

        function arm_get_mautic_segemnets_func() {
            $arm_mautic_settings_ser = get_option('arm_mautic_settings');
            $arm_mautic_settings = ($arm_mautic_settings_ser != '') ? maybe_unserialize($arm_mautic_settings_ser) : array();
            $arm_mautic_list = (!empty($arm_mautic_settings)) ? $arm_mautic_settings['lists'] : array();
            $list_li = '';
            if (!empty($arm_mautic_list)) {
                foreach ($arm_mautic_list as $list):
                    $list_li.='<li data-label="' . $list['name'] . '" data-value="' . $list['id'] . '">' . $list['name'] . '</li>';
                endforeach;
                $first_option = isset($list[0]['name']) ? $list[0]['name'] : '';
                $first_id = isset($list[0]['id']) ? $list[0]['id'] : '';
                $return = array("type" => "success", 'list' => $list_li, 'first_option' => $first_option, 'first_id' => $first_id);
            }
            else {
                $return = array("type" => "error");
            }

            echo json_encode($return);
            die();
        }

        function arm_delete_mautic_config_func($id = 'mautic') {
            delete_option('arm_mautic_access_token_data');
            delete_option('arm_mautic_settings');
            $statusRes = array('type' => 'success', 'msg' => __('Settings has been verified.', MEMBERSHIP_TXTDOMAIN));
            die();
        }

        function arm_add_mautic_general_setting() {
            if (file_exists(ARM_MAUTIC_VIEWS_DIR . '/arm_general_setting_mautic.php')) {
                require_once( ARM_MAUTIC_VIEWS_DIR . '/arm_general_setting_mautic.php' );
            }
        }

        function arm_opt_ins_mautic_details($email_tools) {
            $mauticStatus = (isset($form_settings['email']['mautic']['status'])) ? $form_settings['email']['mautic']['status'] : 0;
            $arm_mautic_settings_ser = get_option('arm_mautic_settings');
            $arm_mautic_settings = ($arm_mautic_settings_ser != '') ? maybe_unserialize($arm_mautic_settings_ser) : array();
            $arm_mautic_lists = (!empty($arm_mautic_settings)) ? $arm_mautic_settings['lists'] : array();
            $arm_mautic_default_list_id = (!empty($arm_mautic_settings)) ? $arm_mautic_settings['default_list'] : 0;
            $arm_mautic_status = (!empty($arm_mautic_settings)) ? $arm_mautic_settings['status'] : 0;
            $mautic_list_id = (isset($form_settings['email']['mautic']['list_id'])) ? $form_settings['email']['mautic']['list_id'] : $arm_mautic_default_list_id;
            $email_tools['mautic'] = array(
                'base_url' => isset($arm_mautic_settings['base_url']) ? $arm_mautic_settings['base_url'] : '',
                'public_key' => isset($arm_mautic_settings['public_key']) ? $arm_mautic_settings['public_key'] : '',
                'secret_key' => isset($arm_mautic_settings['secret_key']) ? $arm_mautic_settings['secret_key'] : '',
                'optins_name' => 'Mautic',
                'status' => $arm_mautic_status,
                'list_id' => $mautic_list_id,
                'list' => $arm_mautic_lists,
            );
                
            return $email_tools;
        }
    }

    global $arm_general_setting_mautic;
    $arm_general_setting_mautic = new ARM_General_Setting_Mautic();
}
?>