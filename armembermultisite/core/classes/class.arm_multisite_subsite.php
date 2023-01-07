<?php
if(!class_exists('arm_multisite_subsite')){
    
    class arm_multisite_subsite{
        
        function __construct(){
            
            add_action( 'wp_ajax_arm_multisite_subsite_ajax_action', array( $this, 'arm_multisite_subsite_ajax_action' ) );
            
            add_action( 'wp_ajax_arm_multisite_subsite_bulk_action', array( $this, 'arm_multisite_subsite_bulk_action' ) );
            
            add_action( 'wp_ajax_arm_multisite_subsite_update_status', array( $this, 'arm_multisite_subsite_update_status' ) );

            add_action( 'wp_ajax_arm_multisite_subsite_update_spam', array( $this, 'arm_multisite_subsite_update_spam' ) );
           
           
            
        }

        
        

        function arm_multisite_subsite_ajax_action() {
            global $wpdb, $arm_multisubsite, $ARMember;

            if( method_exists($ARMember, 'arm_check_user_cap') ){
                $arm_multisite_subsite_capabilities = $arm_multisubsite->arm_multisubsite_page_slug();
                $ARMember->arm_check_user_cap($arm_multisite_subsite_capabilities['0'],'0');
            }
            $action_data = $_POST;
            if( isset( $action_data['act'] ) && $action_data['act'] ){
                if( isset( $action_data['id'] ) && $action_data['id'] != '' && isset( $action_data['user_id'] ) && $action_data['user_id'] != '' && $action_data['act'] == 'delete' )
                {
                    if (!current_user_can('arm_manage_subsites')) {
                        $response = array( 'type' => 'error', 'msg'=> esc_html__( 'Sorry, You do not have permission to perform this action.', 'ARM_MULTISUBSITE' ) );
                    } else {
                        $site_id = $action_data['id'];
                        $user_id = $action_data['user_id'];
                        $arm_user_multisite_data = get_user_meta($user_id, 'arm_multisite_id',true);
                        
                        wpmu_delete_blog($site_id,true);
                        if(!empty($arm_user_multisite_data))
                        {
                            foreach ($arm_user_multisite_data as $user_multisite_id_key => $user_multisite_id_value) 
                            {
                                if($site_id==$arm_user_multisite_data[$user_multisite_id_key]['site_id'])
                                {
                                    unset($arm_user_multisite_data[$user_multisite_id_key]);
                                }
                            }
                        }
                        update_user_meta($user_id,'arm_multisite_id' , $arm_user_multisite_data  );
                        
                        $response = array( 'type' => 'success', 'msg' => esc_html__( 'Subsite deleted successfully.', 'ARM_MULTISUBSITE' ) );                    
                    }
                }
            }
            else
            {
                 $response = array( 'type' => 'error', 'msg'=> esc_html__( 'Sorry, Action not found.', 'ARM_MULTISUBSITE' ) );
            }
            echo json_encode($response);
            die;
        }
        
        function arm_multisite_subsite_bulk_action() {
            global $wpdb, $ARMember, $arm_global_settings, $arm_multisubsite;
            
            if( method_exists($ARMember, 'arm_check_user_cap') ){
                $arm_multisite_subsite_capabilities = $arm_multisubsite->arm_multisubsite_page_slug();
                $ARMember->arm_check_user_cap($arm_multisite_subsite_capabilities['0'],'0');
            }
            if (!isset($_POST)) {
                    return;
            }
            $bulkaction = $arm_global_settings->get_param('action1');
            $ids = $arm_global_settings->get_param('item-action', '');
            
            if (empty($ids)) {
                $errors[] = esc_html__('Please select one or more records.', 'ARM_MULTISUBSITE');
            } else {
                if ($bulkaction == '' || $bulkaction == '-1') {
                        $errors[] = esc_html__('Please select valid action.', 'ARM_MULTISUBSITE');
                } else 
                {
                    if (!is_array($ids)) {
                        $ids = explode(',', $ids);
                    }
                    
                    if (!current_user_can('arm_manage_subsites')) {
                        $errors[] = esc_html__('Sorry, You do not have permission to perform this action', 'ARM_MULTISUBSITE');
                    } else {
                        if (is_array($ids)) {
                            foreach($ids as $id)
                            {

                                $arm_multisite_blog_args = array('blog_id' => $id, 'fields' => array( 'ID', 'user_login' ));
                                $arm_multisite_blog_users = get_users( $arm_multisite_blog_args );
                                $arm_multisite_userid = !empty($arm_multisite_blog_users[0]->ID) ? $arm_multisite_blog_users[0]->ID : 0;
                                $arm_multisite_subsite_ids = get_user_meta($arm_multisite_userid,'arm_multisite_id' , TRUE );
                                $arm_multisite_subsite_id = 0;
                                
                                if(!empty($arm_multisite_subsite_ids))
                                {
                                    foreach ($arm_multisite_subsite_ids as $arm_multisite_subsite_id_key => $arm_multisite_subsite_id_value) 
                                    {
                                        $arm_multisite_subsite_id = !empty($arm_multisite_subsite_ids[$arm_multisite_subsite_id_key]['site_id']) ? $arm_multisite_subsite_ids[$arm_multisite_subsite_id_key]['site_id'] : 0;
                                        if($arm_multisite_subsite_id == $id)
                                        {
                                            wpmu_delete_blog($id,true);
                                            unset($arm_multisite_subsite_ids[$arm_multisite_subsite_id_key]);
                                        }

                                    }
                                    update_user_meta($arm_multisite_userid,'arm_multisite_id' , $arm_multisite_subsite_ids  );
                                }
                            }
                            $message = esc_html__('Subsite(s) has been deleted successfully.', 'ARM_MULTISUBSITE');
                            $return_array = array( 'type'=>'success', 'msg'=>$message );
                        }
                    }
                }
            }
            if(!isset($return_array))
            {   
                $errors = (!empty($errors)) ? $errors : '';
                $message = (!empty($message)) ? $message : '';
                $return_array = $arm_global_settings->handle_return_messages($errors, $message);
                $ARMember->arm_set_message('success',$message);
            }
            echo json_encode($return_array);
            die;
        }
        
        function arm_multisite_subsite_update_status() {
            if (current_user_can('administrator')) {
                global $wpdb, $arm_multisubsite, $ARMember;
                
                if( method_exists($ARMember, 'arm_check_user_cap') ){
                    $arm_multisite_subsite_capabilities = $arm_multisubsite->arm_multisubsite_page_slug();
                    $ARMember->arm_check_user_cap($arm_multisite_subsite_capabilities['0'],'0');
                }
                $site_id = !empty($_REQUEST['site_id']) ? $_REQUEST['site_id'] : '';
                $new_status = !empty($_REQUEST['new_status']) ? $_REQUEST['new_status'] : '';
                $response_status = "";
                $new_status_label = "";
                if($new_status == 'deleted')
                {
                    update_blog_status($site_id, 'public', 0);
                    update_blog_status($site_id, 'deleted', 1);
                 
                    $response_status = 'public';
                    $new_status_label = sprintf(esc_html__('%sDeactive%s', 'ARM_MULTISUBSITE'), '<span class="arm_item_status_text inactive banned">', '</span>');
                    $new_status_content = "<img src='" . ARM_MULTISUBSITE_IMAGES_URL  . "active.png' class='armhelptip arm_change_user_status_ok_btn' title='" . esc_html__('Activate', 'ARM_MULTISUBSITE') . "' onmouseover=\"this.src='" . ARM_MULTISUBSITE_IMAGES_URL . "active_hover.png';\" onmouseout=\"this.src='" . ARM_MULTISUBSITE_IMAGES_URL . "active.png';\" data-item_id='{$site_id}' data-status='public' />";
                }
                if($new_status == 'public')
                {
                    update_blog_status($site_id, 'public', 1);
                    update_blog_status($site_id, 'deleted', 0);
                    $response_status = 'deleted';
                    $new_status_label = sprintf(esc_html__('%sActive%s', 'ARM_MULTISUBSITE'), '<span class="arm_item_status_text active">', '</span>');
                    $new_status_content = "<img src='" . ARM_MULTISUBSITE_IMAGES_URL . "deactive.png' class='armhelptip arm_change_user_status_ok_btn' title='" . esc_html__('Deactivate', 'ARM_MULTISUBSITE') . "' onmouseover=\"this.src='" . ARM_MULTISUBSITE_IMAGES_URL . "deactive_hover.png';\" onmouseout=\"this.src='" . ARM_MULTISUBSITE_IMAGES_URL . "deactive.png';\" data-item_id='{$site_id}' data-status='deleted' />";
                }
                
                $response = array( 'type'=>'success', 'status' => $response_status, 'status_label' => $new_status_label, 'status_content' => $new_status_content );
            }
            else
            {
                $error_msg = esc_html__('Sorry, You do not have permission to perform this action', 'ARM_MULTISUBSITE');
                $response = array( 'type'=>'error', 'msg'=>$error_msg );
            }
            echo json_encode($response);
            die;
        }


        function arm_multisite_subsite_update_spam() {
            
            $response = array();
            
            if ( current_user_can('administrator') ) {
                global $wpdb, $arm_multisubsite, $ARMember;
                
                if( method_exists($ARMember, 'arm_check_user_cap') ){
                    $arm_multisite_subsite_capabilities = $arm_multisubsite->arm_multisubsite_page_slug();
                    $ARMember->arm_check_user_cap($arm_multisite_subsite_capabilities['0'],'0');
                }
                $site_id = !empty($_REQUEST['site_id']) ? $_REQUEST['site_id'] : '';
                $status = !empty($_REQUEST['status']) ? $_REQUEST['status'] : '';
                $new_status = "";
                $status_label = "";
                if( "spam" == $status ) {
                    update_blog_status($site_id, 'spam', 1);
                    $new_status = "unspam";
                    $status_label = "Unspam";
                } 

                if( "unspam" == $status ) {
                    update_blog_status($site_id, 'spam', 0);
                    $new_status = "spam";
                    $status_label = "Spam";
                }

                $response['type'] = 'success';
                $response['status'] = $new_status;
                $response['status_label'] = $status_label;

            } else {

                $error_msg = esc_html__('Sorry, You do not have permission to perform this action', 'ARM_MULTISUBSITE');
                $response['type'] = 'error';
                $response['msg'] = $error_msg;
            }
            echo json_encode($response);
            die;
        }
        
    }
}

global $arm_multisite_subsites;
$arm_multisite_subsites = new arm_multisite_subsite();
?>