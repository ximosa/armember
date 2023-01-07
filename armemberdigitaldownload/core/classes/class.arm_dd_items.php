<?php
if(!class_exists('arm_dd_items')){
    
    class arm_dd_items{
        
        function __construct(){
            
            add_action( 'wp_ajax_arm_dd_add_item', array( $this, 'arm_dd_item_file_save' ) );
            
            add_action( 'wp_ajax_arm_dd_item_list', array( $this, 'arm_dd_item_list' ) );
            
            add_action( 'wp_ajax_arm_dd_item_ajax_action', array( $this, 'arm_dd_item_ajax_action' ) );
            
            add_action( 'wp_ajax_arm_dd_item_bulk_action', array( $this, 'arm_dd_item_bulk_action' ) );
            
            add_action( 'wp_ajax_arm_dd_item_update_status', array( $this, 'arm_dd_item_update_status' ) );

            add_action('wp_ajax_arm_handle_import_download', array( $this, 'arm_dd_handle_import_download' ) );

            add_action('wp_ajax_arm_add_import_download', array( $this, 'arm_add_import_download' ) );

            add_action('wp_ajax_arm_import_download_progress', array(&$this, 'arm_import_download_progress'));
            
           // add_action('wp_ajax_arm_dd_download_sample_file', array(&$this, 'arm_dd_download_sample_file'));
            //add_action( 'wp_ajax_arm_dd_item_file_delete', array( $this, 'arm_dd_item_file_delete' ) );

            add_action('wp_ajax_arm_dd_item_filter_ajax_action', array($this, 'arm_dd_item_filter_ajax_action'));

            add_action('wp_ajax_arm_dd_item_user_list_action',array($this, 'arm_dd_item_user_list_action'));
            
        }

        function arm_dd_download_sample_file(){
           global $wp, $wpdb, $ARMember, $arm_global_settings;
                            $sample_data[1] = array(
                                "id" => 1,
                                "download_name" => "Sample Download",
                                "description" => "This is sample description.",
                                "status" => "1",
                                "tag" => "download",
                                "message" => "This file can not be accessible.",
                                "file_name" => "sample_download",
                                "file_url" => "http://weknowyourdreams.com/images/smile/smile-12.jpg",
                            );
                            $this->arm_export_to_download_csv($sample_data, 'ARMember-sample-import-download.csv');
                            exit; 
        }

       

                        function arm_export_to_download_csv($array, $output_file_name = '', $delimiter = ',') {
                            global $wp, $wpdb, $ARMember, $arm_global_settings;
                            if (count($array) == 0) {
                                return null;
                            }
                            if (empty($output_file_name)) {
                                $output_file_name = "ARMember-sample-import-download.csv";
                            }
                            ob_clean();
                            ob_start();
                            //Set Headers
                            $this->download_send_headers($output_file_name);
                            //Open File For Write Data
                            $df = fopen("php://output", 'w');
                            fputcsv($df, array_keys(reset($array)));
                            foreach ($array as $row) {
                                fputcsv($df, $row);
                            }
                            fclose($df);
                            exit;
                        }

                        function download_send_headers($filename) {
                            // disable caching
                            $now = gmdate("D, d M Y H:i:s");
                            header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
                            header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
                            header("Last-Modified: {$now} GMT");
                            // force download  
                            header("Content-Type: application/force-download");
                            header("Content-Type: application/octet-stream");
                            header("Content-Type: application/download");
                            // disposition / encoding on response body
                            header("Content-Disposition: attachment;filename={$filename}");
                            header("Content-Transfer-Encoding: binary");
                        }

        function arm_import_download_progress() {

            global $ARMember;
            $ARMember->arm_session_start();
            $total_members = isset($_REQUEST['total_downloads']) ? (int) $_REQUEST['total_downloads'] : 0;
            $imported_users = isset($_SESSION['imported_downloads']) ? (int) $_SESSION['imported_downloads'] : 0;
            $response = array();
            $response['total_members'] = $total_members;
            $response['currently_imported'] = $imported_users;
            if ($response['total_members'] == 0) {

                
                $response['error'] = true;
                $response['continue'] = false;
            } else {

                
                if ($response['currently_imported'] > 0) {
                    if ($response['currently_imported'] >= $response['total_members']) {
                        $percentage = 100;
                        $response['continue'] = false;
                        $response['msg'] = __('downloads(s) has been imported successfully', 'ARM_DD');
                        $content = '';
                        if(!empty($_SESSION['all_downloads'])){
                            $content .= '<div class="arm_downloaded_items_box"><div class="arm_downloaded_items">
                            '.__('All Downloaded Items', 'ARM_DD').'</div>';
                            $content .= '<ul>';
                            $i= 0;
                            foreach($_SESSION['all_downloads'] as $downloaded_item){
                               // $content .= '<tr class="form-field form-required">';
                                $i++;
                                $content .= '<li>';
                                $content .= '<span class="download_no">'.$i.'.</span><span class="download_name">'.$downloaded_item.'</span>';
                                $content .= '</li>';
                                //$content .= '</tr>';
                            }
                            $content .= '</ul></div>';
                        }

                        if(!empty($_SESSION['not_downloaded'])){
                           $content .= '<div class="arm_downloaded_items_box"><div class="arm_downloaded_items">
                            '.__('Not Downloaded Items', 'ARM_DD').'</div>';
                            $content .= '<ul>';
                             $i= 0;
                            foreach($_SESSION['not_downloaded'] as $not_downloaded_item){
                                //$content .= '<tr class="form-field form-required">';
                                $i++;
                                $content .= '<li>';
                                $content .= '<span class="download_no">'.$i.'.</span><span class="download_name">'.$not_downloaded_item.'</span>';
                                //$content .= '</td>';
                                $content .= '</li>';
                            }
                            $content .= '</ul></div>';
                        }

                        $response['content'] = $content;
                        unset($_SESSION['imported_downloads']);
                        unset($_SESSION['all_downloads']);
                        unset($_SESSION['not_downloaded']);
                    } else {
                        $percentage = (100 * $response['currently_imported']) / $response['total_members'];
                        $percentage = round($percentage);
                        $response['continue'] = true;
                    }
                    $response['percentage'] = $percentage;
                } else {
                    $response['percentage'] = 0;
                    $response['continue'] = true;
                }
                $response['error'] = false;
            }
          
           

            @session_write_close();
            echo json_encode(stripslashes_deep($response));
            die();
        }


        


        function arm_add_import_download() {
                            global $wpdb, $ARMember, $arm_members_class, $arm_global_settings, $arm_subscription_plans, $arm_case_types, $arm_members_badges, $arm_member_forms, $arm_email_settings;
                            if (!isset($_POST)) {
                                return;
                            }

                            if (!is_admin() && !current_user_can('administrator')) {
                                return;
                            }

                            $ARMember->arm_session_start();

                            $download_ids = array();
                            $arm_global_settings->arm_set_ini_for_importing_users();
                            $message = '';
                            $file_data_array = $user_ids = $u_errors = $errors = array();
                            $ip_address = $ARMember->arm_get_ip_address();

                            $postedFormData = json_decode(stripslashes_deep($_POST['filtered_form']), true);

                            $permission_type = $postedFormData['permission_type'];
                            $arm_user_restriction_type = $postedFormData['arm_user_restriction_type'];
                            $selected_plans = isset($postedFormData['selected_plans']) ? $postedFormData['selected_plans'] : '';
                            $selected_roles = isset($postedFormData['selected_roles']) ? $postedFormData['selected_roles'] : '';
                            $selected_users = isset($postedFormData['selected_users']) ? $postedFormData['selected_users'] : '';
                            $posted_user_data = htmlspecialchars($postedFormData['users_data'], ENT_NOQUOTES);
                            $file_data_array = json_decode($posted_user_data, true);
                            if (json_last_error() != JSON_ERROR_NONE) {
                                $file_data_array = maybe_unserialize($posted_user_data);
                            }

                           
                            $ids = isset($postedFormData['item-action']) ? $postedFormData['item-action'] : array();
                            $mail_count = 0;
                            $imp_count = 0;
                            $_SESSION['imported_downloads'] = 0;
                            $_SESSION['all_downloads']= array();
                            $_SESSION['not_downloaded'] = array();
                            if (empty($ids)) {
                                $errors[] = __('Please select one or more records.','ARM_DD');
                            } else {
                                if (!is_array($ids)) {
                                    $ids = explode(',', $ids);
                                }
                                
                                if (is_array($ids)) {
                                    if (!empty($file_data_array)) {
                                        $users_data = array();
                                        foreach ($file_data_array as $k1 => $val1) {
                                            if (!in_array($k1, $ids)) {
                                                continue;
                                            }
                                            foreach ($val1 as $k2 => $val2) {

                                                $users_data[$k1][$k2] = $val2;
                                                
                                            }
                                        }




                                        if (!empty($users_data)) {
                                            $allowed_extensions = $this->arm_dd_allowed_file_types();

                                            if (count($users_data) > 25) {

                                                $chunked_user_data = array_chunk($users_data, 25, false);

                                                $total_chunked_data = count($chunked_user_data);

                                                
                                                for ($ch_data = 0; $ch_data < $total_chunked_data; $ch_data++) {
                                                    $chunked_data = null;
                                                    $chunked_data = $chunked_user_data[$ch_data];
                                                    foreach ($chunked_data as $rkey => $fdaVal) {
                                                        $file_url = isset($fdaVal['file_url']) ? trim($fdaVal['file_url']) :'';
                                                        $file_name = isset($fdaVal['file_name']) ? trim($fdaVal['file_name']) :'Download';

                                                        $_SESSION['all_downloads'][] = $file_name;
                                                        if($file_url != ''){ 
                                                            $exploded_file_url = explode('/', $file_url);
                                                            $file_name_extension = end($exploded_file_url);
                                                            $arm_timestamp = current_time('timestamp');
                                                            if (isset($file_name_extension) && !empty($file_name_extension)) {
                                                                $file_ext = explode('.',$file_name_extension);
                                                                $file_ext = end($file_ext);

                                                                if (!empty($allowed_extensions) && in_array('.'.$file_ext, $allowed_extensions)) {

                                                                    
                                                                    if (!is_dir(ARM_DD_OUTPUT_DIR))
                                                                            wp_mkdir_p(ARM_DD_OUTPUT_DIR);

                                                                    $arm_item_file_url = ARM_DD_OUTPUT_DIR . $arm_timestamp . '_' . $file_name_extension; 



                                                                    if(copy($file_url, $arm_item_file_url)){

                                                                        $item_data['action'] = 'add_item';
                                                                        $item_data['item_id'] = '';
                                                                        $item_data['arm_item_name'] = isset($fdaVal['download_name']) ? $fdaVal['download_name'] : $file_name;
                                                                        $item_data['arm_item_description'] = isset($fdaVal['description']) ? $fdaVal['description'] : '';
                                                                        $item_data['arm_file_no_url'] = 1;
                                                                        $item_data['arm_item_tag'] = isset($fdaVal['tag']) ? $fdaVal['tag'] : '';
                                                                        $item_data['arm_item_msg'] = isset($fdaVal['message']) ? $fdaVal['message'] : __('This File is restricted', 'ARM_DD');
                                                                        $item_data['arm_item_type'] =  'default';
                                                                        $item_data['arm_item_url'] = 1;
                                                                        $item_data['file_names'] = isset($fdaVal['file_name']) ? array($fdaVal['file_name']) : array();
                                                                        $item_data['file_urls'] = isset($file_name_extension) ? array($arm_timestamp . '_' . $file_name_extension) : array();
                                                                        $item_data['arm_item_permission_type'] = $permission_type;
                                                                        $item_data['arm_user_restriction_type'] = $arm_user_restriction_type;
                                                                        $item_data['arm_user_ids'] = explode(',',$selected_users);
                                                                        $item_data['arm_plans'] = explode(',',$selected_plans);
                                                                        $item_data['arm_roles'] = explode(',',$selected_roles);
                                                                        $item_data['arm_item_status'] = isset($fdaVal['status']) ? $fdaVal['status'] : 0;
                                                                        $item_data['arm_remove_file'] = '';
                                                                        $this->arm_dd_item_save($item_data, 1);
                                                                        $_SESSION['imported_downloads']++;
                                                                        $wpdb->flush();
                                                                        @session_write_close();
                                                                        @session_start();
                                                                    }
                                                                }
                                                            }
                                                        }
                                                        else{
                                                            $_SESSION['not_downloaded'][] = $file_name;
                                                            $download_ids[] = $file_name;
                                                        }
                                                    }
                                                }
                                            } else {
                                                
                                                foreach ($users_data as $rkey => $fdaVal) {

                                                    $file_url = isset($fdaVal['file_url']) ? trim($fdaVal['file_url']) :'';
                                                    $file_name = isset($fdaVal['file_name']) ? trim($fdaVal['file_name']) :'Download';

                                                    $_SESSION['all_downloads'][] = $file_name;
                                                    if($file_url != ''){ 
                                                        $exploded_file_url = explode('/', $file_url);
                                                        $file_name_extension = end($exploded_file_url);
                                                        $arm_timestamp = current_time('timestamp');
                                                        if (isset($file_name_extension) && !empty($file_name_extension)) {
                                                            $file_ext = explode('.',$file_name_extension);
                                                            $file_ext = end($file_ext);

                                                            if (!empty($allowed_extensions) && in_array('.'.$file_ext, $allowed_extensions)) {

                                                                
                                                                if (!is_dir(ARM_DD_OUTPUT_DIR))
                                                                        wp_mkdir_p(ARM_DD_OUTPUT_DIR);

                                                                $arm_item_file_url = ARM_DD_OUTPUT_DIR . $arm_timestamp . '_' . $file_name_extension; 



                                                                if(copy($file_url, $arm_item_file_url)){

                                                                    $item_data['action'] = 'add_item';
                                                                    $item_data['item_id'] = '';
                                                                    $item_data['arm_item_name'] = isset($fdaVal['download_name']) ? $fdaVal['download_name'] : $file_name;
                                                                    $item_data['arm_item_description'] = isset($fdaVal['description']) ? $fdaVal['description'] : '';
                                                                    $item_data['arm_file_no_url'] = 1;
                                                                    $item_data['arm_item_tag'] = isset($fdaVal['tag']) ? $fdaVal['tag'] : '';
                                                                    $item_data['arm_item_msg'] = isset($fdaVal['message']) ? $fdaVal['message'] : __('This File is restricted', 'ARM_DD');
                                                                    $item_data['arm_item_type'] =  'default';
                                                                    $item_data['arm_item_url'] = 1;
                                                                    $item_data['file_names'] = isset($fdaVal['file_name']) ? array($fdaVal['file_name']) : array();
                                                                    $item_data['file_urls'] = isset($file_name_extension) ? array($arm_timestamp . '_' . $file_name_extension) : array();
                                                                    $item_data['arm_item_permission_type'] = $permission_type;
                                                                    $item_data['arm_user_restriction_type'] = $arm_user_restriction_type;
                                                                    $item_data['arm_user_ids'] = explode(',',$selected_users);
                                                                    $item_data['arm_plans'] = explode(',',$selected_plans);
                                                                    $item_data['arm_roles'] = explode(',',$selected_roles);
                                                                    $item_data['arm_item_status'] = isset($fdaVal['status']) ? $fdaVal['status'] : 0;
                                                                    $item_data['arm_remove_file'] = '';
                                                                    $this->arm_dd_item_save($item_data, 1);
                                                                    $_SESSION['imported_downloads']++;
                                                                    $wpdb->flush();
                                                                    @session_write_close();
                                                                    @session_start();
                                                                }
                                                            }
                                                        }
                                                    }
                                                    else{
                                                        $_SESSION['not_downloaded'][] = $file_name;
                                                        $download_ids[] = $file_name;
                                                    }
                                                }
                                            }
                                        } else {
                                            $errors[] = __('No download was imported, please check the file.', 'ARM_DD');
                                        }
                                    }
                                }
                            }


                         
                            if (empty($download_ids)) {
                                $message = __('downloads(s) has been imported successfully', 'ARM_DD');
                               // $ARMember->arm_set_message('success', $message);
                                if (!empty($postedFormData['file_url'])) {
                                    $file_path = MEMBERSHIP_UPLOAD_DIR . '/' . basename($postedFormData['file_url']);
                                    if (file_exists($file_path)) {
                                        unlink($file_path);
                                    }
                                }
                            }
                            
                            if (!empty($download_ids) && !empty($errors)) {
                                $errors[] = __('No download was imported.', 'ARM_DD');
                            }
                            
                            $return_array = $arm_global_settings->handle_return_messages(@$errors, @$message);
                            echo json_encode($return_array);
                            exit;
                        }



        function arm_dd_handle_import_download() {


            global $wp, $wpdb, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_case_types, $arm_member_forms, $arm_dd;

            if( method_exists($ARMember, 'arm_check_user_cap') ){
                $arm_dd_capabilities = $arm_dd->arm_dd_page_slug();
                $ARMember->arm_check_user_cap($arm_dd_capabilities['1'],'1');
            }
            set_time_limit(0);

            $file_data_array =  $errors = array();
            $posted_data = $_POST;
            
            $up_file = $posted_data['import_bulk_download'];
            $permission_type = isset($posted_data['arm_item_permission_type']) ? $posted_data['arm_item_permission_type'] : 'any';
            $arm_user_restriction_type = isset($posted_data['arm_user_restriction_type']) ? $posted_data['arm_user_restriction_type'] : '';
            $selected_users = isset($posted_data['arm_user_ids']) ? implode(',', $posted_data['arm_user_ids']) : '';
            $selected_plans = isset($posted_data['arm_plans']) ? implode(',', $posted_data['arm_plans']) : '';
            $selected_roles = isset($posted_data['arm_roles']) ? implode(',', $posted_data['arm_roles']) : '';

            $grid_columns = array();
            $arm_grid_columns = array('download_name', 'description', 'file_name', 'file_url', 'status', 'tag', 'message');
            foreach ($arm_grid_columns as  $val) {
                switch ($val):
                    case 'download_name':
                        $grid_columns[$val] = __('Download Name', 'ARM_DD');
                        break;
                    case 'description':
                        $grid_columns[$val] = __('Description', 'ARM_DD');
                        break;
                    case 'file_name':
                        $grid_columns[$val] = __('File Name', 'ARM_DD');
                        break;
                    case 'file_url':
                        $grid_columns[$val] = __('URL', 'ARM_DD');
                        break;
                    case 'status':
                        $grid_columns[$val] = __('Access', 'ARM_DD').'<br>'.__('(Enable/Disable)', 'ARM_DD');
                        break;
                    case 'tag':
                        $grid_columns[$val] = __('Tag', 'ARM_DD');
                        break;
                    case 'message':
                        $grid_columns[$val] = __('Message', 'ARM_DD');
                        break;
                    default:
                        break;
                endswitch;
            }

           
            $users_data = array();
            if (isset($up_file)) {
                $up_file_ext = pathinfo($up_file, PATHINFO_EXTENSION);
                if (in_array($up_file_ext, array('csv'))) {
                    
                        //Read CSV, XLS Files
                        if (file_exists(ARM_DD_LIBRARY_DIR . '/class-readcsv.php')) {
                            require_once(ARM_DD_LIBRARY_DIR . '/class-readcsv.php');
                        }
                        $csv_reader = new ReadCSV(MEMBERSHIP_UPLOAD_DIR . '/' . basename($up_file));
                        if ($csv_reader->is_file == TRUE) {
                            $file_data_array = $csv_reader->get_data();
                        } else {
                            $errors[] = __('Error during file upload.', 'ARM_DD');
                        }
                    
                    $downloads_array = array();
                    $downloads_data = array();



                    if (!empty($file_data_array)) {
                        $is_password_column = 0;
                        $count_row = 0;
                         
                        foreach ($file_data_array as $fdaVal) {
                            if (isset($fdaVal['file_url']) && !empty(trim($fdaVal['file_url']))) {
                                if(!empty($fdaVal['status']) && $fdaVal['status']>=1){
                                    $fdaVal['status']=1;
                                }
                                foreach ($grid_columns as $key => $val) {
                                    if(!empty($key)){
                                    $downloads_array[$count_row][$key] = htmlspecialchars(utf8_encode($fdaVal[$key]), ENT_NOQUOTES);
                                    }
                                }
                                $count_row++;
                            }
                        }
                    }

                    if(count($downloads_array) > 100){
                        echo '100';
                        exit;
                    }



                    if (!empty($downloads_array)) {

                        ?>
                        <div class="arm_download_listing_class">
                            
                            <table cellspacing="0">
                                <tr class="arm_download_header_tr">
                                    <th class="center cb-select-all-th" style="max-width:60px;text-align:center;"><input id="cb-select-all-1" type="checkbox" class="chkstanard arm_all_import_download_chks"></th>
                                    <?php
                                    if (!empty($grid_columns)){
                                            foreach ($grid_columns as $key => $title){
                                                ?>
                                                            <th data-key="<?php echo $key; ?>" class="arm_grid_th_<?php echo $key; ?>" ><?php echo $title; ?></th>
                                                <?php
                                        }
                                    }
                                    ?>
                                </tr>
                                        <?php
                                        $i = 0;
                                        foreach ($downloads_array as $value) {
                                            $downloads_data[$i] = $value;
                                            ?>
                                                    <tr>

                                                        <td>
                                           
                                                            <input id="cb-item-action-<?php echo $i; ?>"  class="chkstanard arm_import_download_chks" type="checkbox" value="<?php echo $i; ?>" name="item-action[]">
                                                        </td>

                                                        <?php
                                                        foreach ($grid_columns as $key => $val) {
                                                            echo isset($value[$key]) ? (!empty($value[$key])) ? '<td>' . utf8_encode($value[$key]) . '</td>' : '<td>-</td>' : '';
                                                        }
                                                        ?>
                                                    </tr>                                   
                                                    <?php
                                                    $i++;
                                                }
                                                ?>

                                            </table>
                                            <input type="hidden" id="arm_import_file_url" name="file_url" value="<?php echo $up_file; ?>" />
                                            <input type="hidden" id="arm_import_file_url" name="permission_type" value="<?php echo $permission_type; ?>" />
                                            <input type="hidden" id="arm_import_file_url" name="arm_user_restriction_type" value="<?php echo $arm_user_restriction_type; ?>" />
                                            <input type="hidden" id="arm_import_file_url" name="selected_users" value="<?php echo $selected_users; ?>" />
                                            <input type="hidden" id="arm_import_file_url" name="selected_plans" value="<?php echo $selected_plans; ?>" />
                                            <input type="hidden" id="arm_import_file_url" name="selected_roles" value="<?php echo $selected_roles; ?>" />
                                           
                                            <textarea id="arm_import_users_data" name="users_data" style="display:none;"><?php echo json_encode($downloads_data); ?></textarea>
                                        </div>
                                                        <?php
                            }
                        
                    
                    else{

                        
                        echo 'empty';
                        exit;
                    }
                }
            }


                    exit;
        }



        function arm_dd_handle_import_download1(){
            global $wpdb, $arm_global_settings;
            set_time_limit(0);
            $arm_global_settings->arm_set_ini_for_importing_users();

            $posted_data = $_POST;
            $response = array('type'=>'error', 'message'=>__('Sorry, something went wrong.', 'ARM_DD'));
            if(!empty($posted_data) && $posted_data['action'] == 'arm_dd_handle_import_download'){

              
                $permission_type = isset($posted_data['arm_item_permission_type']) ? $posted_data['arm_item_permission_type'] : 'any';
                $arm_user_restriction_type = isset($posted_data['arm_user_restriction_type']) ? $posted_data['arm_user_restriction_type'] : '';
                $selected_users = isset($posted_data['arm_user_ids']) ? $posted_data['arm_user_ids'] : array();
                $selected_plans = isset($posted_data['arm_plans']) ? $posted_data['arm_plans'] : array();
                $selected_roles = isset($posted_data['arm_roles']) ? $posted_data['arm_roles'] : array();

                $up_file = isset($posted_data['import_bulk_download']) ? $posted_data['import_bulk_download'] : '';
                $file_data_array = array();
                if (isset($up_file)) {
                    $up_file_ext = pathinfo($up_file, PATHINFO_EXTENSION);
                    if (in_array($up_file_ext, array('csv', 'xls', 'xlsx', 'xml'))) {
                        if ($up_file_ext == 'csv') {
                            if (file_exists(ARM_DD_LIBRARY_DIR . '/class-readcsv.php')) {
                                require_once(ARM_DD_LIBRARY_DIR . '/class-readcsv.php');
                            }
                            $csv_reader = new ReadCSV(MEMBERSHIP_UPLOAD_DIR . '/' . basename($up_file));
                            if ($csv_reader->is_file == TRUE) {
                                $file_data_array = $csv_reader->get_data();
                            } else {
                                $errors[] = __('Error during file upload.', 'ARM_DD');
                            }

                            $not_imported_file_names = array();

                            if(!empty($file_data_array)){
                                foreach ($file_data_array as $fdaVal) {
                                    $item_data = array();
                                    if(!empty($fdaVal)){

                                        $file_url = isset($fdaVal['file_url']) ? trim($fdaVal['file_url']) : '';

                                        if($file_url != ''){

                                        $file_name =isset($fdaVal['file_name']) ? maybe_serialize($fdaVal['file_name']) : '';
                                        if (!is_dir(ARM_DD_OUTPUT_DIR))
                                            wp_mkdir_p(ARM_DD_OUTPUT_DIR);
                                        
                                        $allowed_extensions = $this->arm_dd_allowed_file_types();
                                        
                                        $arm_item_file_urls = array();
                                        $arm_timestamp = current_time('timestamp');
                                        $arm_item_file_content = '';
                                        $arm_file_no_url = 1; 
                                      
                                        $file_name = 'arm_item_file_' . $arm_file_no_url;
                                        $file_ext = explode('.', $file_url);
                                        $file_ext = end($file_ext);
                                            
                                        if (!empty($allowed_extensions) && !in_array('.'.$file_ext, $allowed_extensions)) {
                                            $not_imported_file_names[] = '';
                                            continue;
                                        }

                                            if (!empty($_FILES[$file_name]["tmp_name"]) && !@is_uploaded_file($_FILES[$file_name]["tmp_name"])) {
                                                $errors = true;
                                                $errors_data[] = __("Please select valid file.", 'ARM_DD');
                                            }

                                            if (!empty($_FILES[$file_name]["tmp_name"]) && isset($_FILES[$file_name]['error']) && !empty($_FILES[$file_name]['error'])) {
                                                $errors = true;
                                                $errors_data[] = $_FILES['arf_item_file_url']['error'];
                                            }

                                            if (isset($_FILES[$file_name]["name"]) && !empty($_FILES[$file_name]["name"])) {
                                                $arm_item_file_name = $arm_timestamp . '_' . $_FILES[$file_name]["name"];
                                                if (@move_uploaded_file($_FILES[$file_name]["tmp_name"], ARM_DD_OUTPUT_DIR . $arm_item_file_name)) {
                                                    $arm_file_no_url++;
                                                    $arm_item_file_url = ARM_DD_OUTPUT_URL . $arm_item_file_name;
                                                    $arm_remove_download_title = __("Remove Download Item", "ARM_DD");
                                                    $arm_item_file_content.='<tr class="arm_dd_itembox arm_dd_item_'.$arm_file_no_url.'">';
                                                    $arm_item_file_content.='<td>';
                                                            $arm_item_file_content.=$arm_item_file_name;
                                                    $arm_item_file_content.='</td>';

                                                    $arm_item_file_content.='<td>';
                                                    $exploded_name = explode(".",$arm_item_file_name);
                                                    $download_file_name = $exploded_name[0];
                                                        $arm_item_file_content.= '<input type="text" name="file_names[]" value="'.$download_file_name.'" class="arm_dd_file_name_input">' ;
                                                        $arm_item_file_content.= '<input type="hidden" name="file_urls[]" id="file_url" value="'.$arm_item_file_name.'" />';
                                                    $arm_item_file_content.='</td>';

                                                    $arm_item_file_content.='<td class="arm_dd_remove_item_wrapper">';
                                                            $arm_item_file_content.= '<div class="arm_dd_upload_item_minus_icon arm_helptip_icon tipso_style arm_dd_remove_selected_itembox" title="'.$arm_remove_download_title.'" data_file_name="'.$arm_item_file_name.'" date_item_id="'.$arm_file_no_url.'"></div>';
                                                    $arm_item_file_content.='</td>';
                                            $arm_item_file_content.='</tr>';
                                                }
                                            }


                                            



                                            
                                            // $arm_item_file_content.= '<div class="arm_dd_itembox arm_dd_item_'.$arm_file_no_url.'">';
                                            // $arm_item_file_content.= '<input type="hidden" name="file_urls[]" id="file_url" value="'.$arm_item_file_name.'" />';
                                            // $arm_item_file_content.= '<label>'.$arm_item_file_name;
                                            // $arm_item_file_content.= '<span class="arm_dd_remove_selected_itembox" data_file_name="'.$arm_item_file_name.'" date_item_id="'.$arm_file_no_url.'">x</span>';
                                            // $arm_item_file_content.= '</label></div>';
                                            
                                            $arm_item_file_urls[] = urlencode($arm_item_file_url);
                                        






                                        $item_data['action'] = 'add_item';
                                        $item_data['item_id'] = '';
                                        $item_data['arm_item_name'] = isset($fdaVal['download_name']) ? $fdaVal['download_name'] : '';
                                        $item_data['arm_item_description'] = isset($fdaVal['description']) ? $fdaVal['description'] : '';
                                        $item_data['arm_item_tag'] = isset($fdaVal['tag']) ? $fdaVal['tag'] : '';
                                        $item_data['arm_item_msg'] = isset($fdaVal['message']) ? $fdaVal['message'] : '';
                                        $item_data['arm_item_type'] =  'default';
                                        $item_data['arm_item_url'] = isset($fdaVal['file_url']) ? $fdaVal['file_url'] : '';
                                        $item_data['file_names'] = isset($fdaVal['file_name']) ? maybe_serialize($fdaVal['file_name']) : '';
                                        $item_data['arm_item_permission_type'] = $permission_type;
                                        $item_data['arm_user_restriction_type'] = $arm_user_restriction_type;
                                        $item_data['arm_user_ids'] = $selected_users;
                                        $item_data['arm_plans'] = $selected_plans;
                                        $item_data['arm_roles'] = $selected_roles;
                                        $item_data['arm_item_status'] = isset($fdaVal['status']) ? $fdaVal['status'] : 0;
                                        $item_data['arm_remove_file'] = '';

                                        $this->arm_dd_item_save($item_data, 1);
                                    }
                                    }
                                }
                            }
                        }
                    }
                }

                 $response = array('type'=>'success', 'message'=>$message);
            }
            
            echo json_encode($response);
            die();


        }

        
        function arm_dd_allowed_file_types() {
            //return array('.jpg','.jpeg','.png','.bmp','.ico','.htm','.html','.pdf','.jpe','.gif','.mp3','.mp4','.flv','.ogg','.webm');

             $mimes = get_allowed_mime_types();
               $mime_types_array = array();
                // Loop through and find the file extension icon.
                foreach ( $mimes as $type => $mime ) {

                    if(strpos('|', $type) !== 0){
                        
                        $exploded_extension = explode("|", $type);
                        foreach($exploded_extension as $ext){
                            $mime_types_array[] = '.'.$ext;
                        }
                    }
                    else{
                        $mime_types_array[] = '.'.$type;
                    }
                                  
                }
                return array_values($mime_types_array);


            //return array();
        }
        
        function arm_dd_item_open_browser( $file_url, $download_options ) {
            $open_file_browser = isset($download_options['open_file_browser']) ? $download_options['open_file_browser'] : '0';
            $arm_open_file_browser = array('.jpg','.jpeg','.png','.bmp','.ico','.htm','.html','.pdf','.jpe','.gif','.mp3','.mp4','.flv','.ogg','.webm');
            $file_ext = explode('.', $file_url);
            $file_ext = end($file_ext);
            if(in_array( '.'.$file_ext, $arm_open_file_browser ) && $open_file_browser == 1) {
                return true;
            } else { 
                return false;
            }
        }
        
        function arm_dd_item_file_save() {
            
            global $ARMember, $arm_dd;
            
            if (!is_dir(ARM_DD_OUTPUT_DIR))
                wp_mkdir_p(ARM_DD_OUTPUT_DIR);
            
            $allowed_extensions = $this->arm_dd_allowed_file_types();
            $errors = false;
            $errors_data = array();
            $arm_item_file_urls = array();
            $arm_timestamp = current_time('timestamp');
            $arm_item_file_content = '';
            $arm_file_no_url = isset($_REQUEST['arm_file_no_url']) ? $_REQUEST['arm_file_no_url'] : 0; 
            $file_name = '';
            
            for($file_no = 0; $file_no < $_REQUEST['arm_item_no_file']; $file_no++)
            {
                $arm_item_file_url = '';
                $file_ext = '';
                $file_name = 'arm_item_file_' . $file_no;
                if(isset($_FILES[$file_name]['name'])&&$_FILES[$file_name]['name']!=''){
                    $file_ext = explode('.', $_FILES[$file_name]['name']);
                    $file_ext = end($file_ext);
                    $files_temp = explode('.', $_FILES[$file_name]['name']);
                    $file_ext = strtolower(end($files_temp));
                }

               

                
                
                if (!empty($allowed_extensions) && !in_array('.'.$file_ext, $allowed_extensions)) {
                    $errors = true;
                    $errors_data[] = __("Sorry! Not able to upload " . $file_ext . " file.", 'ARM_DD');
                    continue;
                }

                if (!empty($_FILES[$file_name]["tmp_name"]) && !@is_uploaded_file($_FILES[$file_name]["tmp_name"])) {
                    $errors = true;
                    $errors_data[] = __("Please select valid file.", 'ARM_DD');
                }

                if (!empty($_FILES[$file_name]["tmp_name"]) && isset($_FILES[$file_name]['error']) && !empty($_FILES[$file_name]['error'])) {
                    $errors = true;
                    $errors_data[] = $_FILES['arf_item_file_url']['error'];
                }

                if (isset($_FILES[$file_name]["name"]) && !empty($_FILES[$file_name]["name"])) {
                    $arm_item_file_name = $arm_timestamp . '_' . $_FILES[$file_name]["name"];
                    if (@move_uploaded_file($_FILES[$file_name]["tmp_name"], ARM_DD_OUTPUT_DIR . $arm_item_file_name)) {
                        $arm_file_no_url++;
                        $arm_item_file_url = ARM_DD_OUTPUT_URL . $arm_item_file_name;
                        $arm_remove_download_title = __("Remove Download Item", "ARM_DD");
                        $arm_item_file_content.='<tr class="arm_dd_itembox arm_dd_item_'.$arm_file_no_url.'">';
                        
                        $arm_item_file_content.='<td>';
                        $exploded_name = explode(".",$arm_item_file_name);
                        $download_file_name = $exploded_name[0];
                            $arm_item_file_content.= '<input type="text" name="file_names[]" value="'.$download_file_name.'" class="arm_dd_file_name_input">' ;
                            $arm_item_file_content.= '<input type="hidden" name="file_urls[]" id="file_url" value="'.$arm_item_file_name.'" />';
                        $arm_item_file_content.='</td>';

                        $arm_item_file_content.='<td class="arm_dd_remove_item_wrapper">';
                                
                                $arm_item_file_content.= '<div class="arm_dd_upload_item_minus_icon arm_helptip_icon tipso_style arm_dd_remove_selected_itembox" title="'.$arm_remove_download_title.'" data_file_name="'.$arm_item_file_name.'" date_item_id="'.$arm_file_no_url.'"></div>';
                        $arm_item_file_content.='</td>';
                $arm_item_file_content.='</tr>';
                    }
                }


                



                
                // $arm_item_file_content.= '<div class="arm_dd_itembox arm_dd_item_'.$arm_file_no_url.'">';
                // $arm_item_file_content.= '<input type="hidden" name="file_urls[]" id="file_url" value="'.$arm_item_file_name.'" />';
                // $arm_item_file_content.= '<label>'.$arm_item_file_name;
                // $arm_item_file_content.= '<span class="arm_dd_remove_selected_itembox" data_file_name="'.$arm_item_file_name.'" date_item_id="'.$arm_file_no_url.'">x</span>';
                // $arm_item_file_content.= '</label></div>';
                
                $arm_item_file_urls[] = urlencode($arm_item_file_url);
            }

            $errors = '<span class="arm_error_msg">'.implode('<br/>',array_unique($errors_data)).'</span>';
            
            if(!empty($arm_item_file_urls)){
                $response = array( 'type' => 'success', 'error_msg'=> $errors, 'no_file'=>$arm_file_no_url, 'content' => $arm_item_file_content);
            }
            else
            {
                $response = array( 'type' => 'error', 'error_msg'=> $errors );
            }
            echo json_encode($response);
            die;
        }
        
        function arm_dd_item_zip( $file_urls = array(), $arm_item_id = 0, $arm_item_name = '' ) {
            $item_data = $this->arm_dd_item_data($arm_item_id);
            if( count($file_urls) > 1 )
            {
                $arm_item_urls = isset($item_data['arm_item_url']) ? $this->arm_dd_item_file_urls($item_data['arm_item_url'], 'zip') : '';
                $arm_zip_file_name = (isset($arm_item_urls) && $arm_item_urls!='' ) ? $this->arm_dd_item_file_name_of_url($arm_item_urls) : '';
                                
                $zip = new ZipArchive();
                if($arm_zip_file_name != '' && file_exists(ARM_DD_OUTPUT_DIR . $arm_zip_file_name)) {
                    unlink (ARM_DD_OUTPUT_DIR . $arm_zip_file_name);
                }

                $arm_remove_special_char_item_name = preg_replace("/[^A-Za-z0-9 ]/", '', $arm_item_name).'.zip';
                $arm_file_zip_name = str_replace(' ', '_', $arm_remove_special_char_item_name);
                
                if ($zip->open(ARM_DD_OUTPUT_DIR . $arm_file_zip_name, ZIPARCHIVE::CREATE) != TRUE) {
                        die ("Could not open archive");
                }

                $arm_file_names = $this->arm_dd_item_file_name_of_url($file_urls);
                foreach($arm_file_names as $file){
                    $arm_item_file_path = ARM_DD_OUTPUT_DIR . $file;
                    if($file != '' && file_exists($arm_item_file_path)) {
                        $zip->addFile($arm_item_file_path , $file);
                    }
                }
                
                $zip->close();
                //$file_urls['arm_file_zip_name'] = ARM_DD_OUTPUT_URL . $arm_file_zip_name;
                $file_urls['arm_file_zip_name'] = $arm_file_zip_name;
            }
            else
            {


                $arm_item_urls = isset($item_data['arm_item_url']) ? $this->arm_dd_item_file_urls($item_data['arm_item_url'], 'zip') : ''; 
 

             
                $arm_zip_file_name_url = (isset($arm_item_urls) && $arm_item_urls!='' ) ? $arm_item_urls : ''; 

              
                if(!is_array($arm_zip_file_name_url)){
                $arm_zip_file_name_url_arr = explode('/', $arm_zip_file_name_url);
                $arm_zip_file_name = end($arm_zip_file_name_url_arr);
                if($arm_zip_file_name != '' && file_exists(ARM_DD_OUTPUT_DIR . $arm_zip_file_name)) {
                    unlink (ARM_DD_OUTPUT_DIR . $arm_zip_file_name);
                }
            }
            }
            return $file_urls;
        }
        
        function arm_dd_item_save( $item_data, $import = '' ) {

            global $wpdb, $ARMember, $arm_dd, $arm_global_settings;
            $arm_item_action = isset($item_data['action']) ? $item_data['action'] : '';
            $arm_item_id = isset($item_data['item_id']) ? $item_data['item_id'] : '';
            $arm_item_name = isset($item_data['arm_item_name']) ? $item_data['arm_item_name'] : '';
            //$arm_item_summery = isset($item_data['arm_item_summery']) ? $item_data['arm_item_summery'] : '';
            $arm_item_description = isset($item_data['arm_item_description']) ? $item_data['arm_item_description'] : '';
            $arm_item_tag = isset($item_data['arm_item_tag']) ? $item_data['arm_item_tag'] : '';
            $arm_item_msg = isset($item_data['arm_item_msg']) ? $item_data['arm_item_msg'] : '';
            $arm_item_download_count = isset($item_data['arm_item_download_count']) ? $item_data['arm_item_download_count'] : '';
            $arm_item_type = isset($item_data['arm_item_type']) ? $item_data['arm_item_type'] : 'default';
            $arm_item_url = isset($item_data['arm_item_url']) ? $item_data['arm_item_url'] : '';
            $arm_file_names = isset($item_data['file_names']) ? maybe_serialize($item_data['file_names']) : '';
            $arm_item_permission_type = isset($item_data['arm_item_permission_type']) ? $item_data['arm_item_permission_type'] : '';
            $arm_user_restriction_type = isset($item_data['arm_user_restriction_type']) ? $item_data['arm_user_restriction_type'] : '';
            $arm_user_ids = isset($item_data['arm_user_ids']) ? $item_data['arm_user_ids'] : '';
            $arm_plans = isset($item_data['arm_plans']) ? $item_data['arm_plans'] : '';
            $arm_roles = isset($item_data['arm_roles']) ? $item_data['arm_roles'] : '';
            //$arm_item_note = isset($item_data['arm_item_note']) ? $item_data['arm_item_note'] : '';
            $arm_item_status = isset($item_data['arm_item_status']) ? $item_data['arm_item_status'] : 0;
            $arm_remove_file = isset($item_data['arm_remove_file']) ? $item_data['arm_remove_file'] : '';
            $arm_item_datetime = current_time('mysql');
            $arm_timestamp = current_time('timestamp');
            $redirect_to = admin_url('admin.php?page=arm_dd_item');
            
            if($arm_item_type == 'default'){
                $arm_item_zip_name = '';
                $arm_item_urls = $this->arm_dd_item_zip( $item_data['file_urls'], $arm_item_id, $arm_item_name );

                $arm_item_url = maybe_serialize($arm_item_urls);
                $arm_file_names = maybe_serialize($item_data['file_names']);
            }
            
            if($arm_item_permission_type == 'user') {
                $arm_user_ids['arm_user_restriction_type'] = $arm_user_restriction_type;
                $arm_item_permission = maybe_serialize($arm_user_ids);
            } else if($arm_item_permission_type == 'plan') {
                $arm_item_permission = maybe_serialize($arm_plans);
            } else if($arm_item_permission_type == 'role') {
                $arm_item_permission = maybe_serialize($arm_roles);
            } else { 
                $arm_item_permission = '';
            }
            
            //remove_file
            if( is_array($arm_remove_file) && $arm_remove_file !='' ) {
                foreach($arm_remove_file as $file_name){
                    if($file_name != '' && file_exists( ARM_DD_OUTPUT_DIR . $file_name )){
                        unlink( ARM_DD_OUTPUT_DIR . $file_name );
                    }
                }
            }
            
            if( $arm_item_action == 'update_item' && $arm_item_id > 0 ) {
                    $item_data = array(
                            'arm_item_name' => $arm_item_name,
                            //'arm_item_summery' => $arm_item_summery,
                            'arm_item_description' => $arm_item_description,
                            'arm_item_type' => $arm_item_type,
                            'arm_item_url' => $arm_item_url,
                            'arm_file_names' => $arm_file_names,
                            'arm_item_permission_type' => $arm_item_permission_type,
                            'arm_item_permission' => $arm_item_permission,
                            'arm_item_tag' => $arm_item_tag,
                            'arm_item_msg' => $arm_item_msg,
                            // 'arm_item_status' => $arm_item_status
                        );   
		$item_data_format = array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );
                if ($arm_item_download_count != '') {
                    $item_data['arm_item_download_count'] = $arm_item_download_count;
		    $item_data_format[] = '%d';
                }
                
                $item_where = array( 'arm_item_id' => $arm_item_id );
                $item_where_formate = array( '%d' );
                
                $wpdb->update( $arm_dd->tbl_arm_dd_items, $item_data, $item_where, $item_data_format, $item_where_formate );
                
                $success_message = __('Item detail has beed updated successfully.', 'ARM_DD');
                $ARMember->arm_set_message('success', $success_message);
                $redirect_to = $arm_global_settings->add_query_arg("action", "edit_item", $redirect_to);
                $redirect_to = $arm_global_settings->add_query_arg("id", $arm_item_id, $redirect_to);
                
            } else {
                
                $item_data = array(
                    'arm_item_name' => $arm_item_name,
                    //'arm_item_summery' => $arm_item_summery,
                    'arm_item_description' => $arm_item_description,
                    'arm_item_type' => $arm_item_type,
                    'arm_item_url' => $arm_item_url,
                    'arm_file_names' => $arm_file_names,
                    'arm_item_permission_type' => $arm_item_permission_type,
                    'arm_item_permission' => $arm_item_permission,
                    'arm_item_tag' => $arm_item_tag,
                    'arm_item_msg' => $arm_item_msg,
                    'arm_item_download_count' => $arm_item_download_count,
                    'arm_item_status' => 1,
                    'arm_item_datetime' => $arm_item_datetime
                );

                $new_item_results = $wpdb->insert($arm_dd->tbl_arm_dd_items, $item_data);
                $arm_item_id = $wpdb->insert_id;
                if( $arm_item_id > 0 ) {
                    $success_message = __('New item has been added successfully.', 'ARM_DD');
                    $ARMember->arm_set_message('success', $success_message);
                    $redirect_to = $arm_global_settings->add_query_arg("action", "edit_item", $redirect_to);
                    $redirect_to = $arm_global_settings->add_query_arg("id", $arm_item_id, $redirect_to);
                }
                
            }
            
            if (!empty($redirect_to) && $import == '') {
                wp_redirect($redirect_to);
                exit;
            }
        }
        
        function arm_dd_item_data( $item_id ) {
            global $wpdb, $ARMember, $arm_dd, $arm_global_settings;
            if($item_id > 0)
            {
                $item_data = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `{$arm_dd->tbl_arm_dd_items}` WHERE arm_item_id = %d AND arm_item_status = %d", $item_id, '1' ), ARRAY_A );
                return $item_data;
            }
            return false;
        }
        
        function arm_dd_item_all_data() {
            global $wpdb, $ARMember, $arm_dd, $arm_global_settings;
            $item_data = $wpdb->get_results( "SELECT * FROM `{$arm_dd->tbl_arm_dd_items}`" , ARRAY_A );
            return $item_data;
        }
        
        function arm_dd_item_file_urls( $arm_item_urls, $file_type = '') {

            $arm_file_urls = array();
            $arm_item_urls = maybe_unserialize($arm_item_urls);


            if( count($arm_item_urls) > 1 ){
                if($file_type == 'zip') {
                    $arm_file_urls = isset($arm_item_urls['arm_file_zip_name']) ? $arm_item_urls['arm_file_zip_name'] : '';
                } else if($file_type == 'item') {
                    if(isset($arm_item_urls['arm_file_zip_name'])) {
                        unset($arm_item_urls['arm_file_zip_name']);
                    }
                    $arm_file_urls = $arm_item_urls;
                } else {
                    $arm_file_urls = $arm_item_urls;
                }
            } else {
                $arm_file_urls = $arm_item_urls;
            }

            return $arm_file_urls;
        }

        function arm_dd_item_file_names( $arm_item_urls, $file_type = '') {
            $arm_file_urls = array();
            $arm_item_urls = maybe_unserialize($arm_item_urls);
            if( count($arm_item_urls) > 1 ){
                if($file_type == 'zip') {
                    $arm_file_urls = isset($arm_item_urls['arm_file_zip_name']) ? $arm_item_urls['arm_file_zip_name'] : '';
                } else if($file_type == 'item') {
                    if(isset($arm_item_urls['arm_file_zip_name'])) {
                        unset($arm_item_urls['arm_file_zip_name']);
                    }
                    $arm_file_urls = $arm_item_urls;
                } else {
                    $arm_file_urls = $arm_item_urls;
                }
            } else {
                $arm_file_urls = $arm_item_urls;
            }
            return $arm_file_urls;
        }
        
        
        function arm_dd_item_file_name_of_url( $arm_file ) {
            $file_name = '';
            if(is_array($arm_file)){
                $file_name = array();
                foreach($arm_file as $file){
                    $file_name_arr = explode('/', $file);
                    array_push($file_name, end($file_name_arr));
                }
            } else {
                $arm_file_arr = explode('/', $arm_file);
                $arm_file_name = end($arm_file_arr);
                $file_name = $arm_file_name;
            }
            return $file_name;
        }
        
        function arm_dd_item_list() {
            global $wpdb, $ARMember, $arm_global_settings, $arm_payment_gateways, $arm_dd, $arm_subscription_plans;

            if( method_exists($ARMember, 'arm_check_user_cap') ){
                $arm_dd_capabilities = $arm_dd->arm_dd_page_slug();
                $ARMember->arm_check_user_cap($arm_dd_capabilities['1'],'1');
            }
            $date_format = $arm_global_settings->arm_get_wp_date_format();
            $nowDate = current_time('mysql');
            
            $grid_columns = array(
                'id'   => __('ID', 'ARM_DD'),
                'name' => __('Name', 'ARM_DD'),
                'permission_type' => __('Permission Type', 'ARM_DD'),
                'permission' => __('Permission', 'ARM_DD'),
                'download' => __('No. Of Downloads', 'ARM_DD'),
                'shortcode' => __('Shortcode', 'ARM_DD'),
                'status' => __('Access (Enable/Disable)', 'ARM_DD'),
                'datetime' => __('Date', 'ARM_DD')
            );

            $arm_pro_tmp_query = "SELECT arm_item_id FROM {$arm_dd->tbl_arm_dd_items}";
            $form_result = $wpdb->get_results($arm_pro_tmp_query);
            $total_before_filter = count($form_result);
            
            $where_condition = '';
            
            $sSearch = isset($_REQUEST['sSearch']) ? $_REQUEST['sSearch'] : '';
            if($sSearch != '')
            { $where_condition.= " AND ( arm_item_name LIKE '%{$sSearch}%' )"; }
            
            $arm_status = isset($_REQUEST['filter_status_id']) ? $_REQUEST['filter_status_id'] : '';
            if($arm_status == '0' || $arm_status == '1')
            { $where_condition.= " AND arm_item_status = '{$arm_status}'"; }
            
            $sorting_ord = isset($_REQUEST['sSortDir_0']) ? $_REQUEST['sSortDir_0'] : 'desc';
            $sorting_col = (isset($_REQUEST['iSortCol_0']) && $_REQUEST['iSortCol_0'] > 0) ? $_REQUEST['iSortCol_0'] : 5;
            $order_by = 'arm_item_id';
            if( $sorting_col == 2 ) {
                $order_by = 'arm_item_name';
            }
            if( $sorting_col == 5 ) {
                $order_by = 'arm_item_download_count';
            }
            if( $sorting_col == 8 ) {
                $order_by = 'arm_item_datetime';
            }
            
            $arm_pro_tmp_query = "SELECT arm_item_id, arm_item_name, arm_item_download_count, arm_item_permission_type, arm_item_permission, arm_item_status, arm_item_datetime FROM `{$arm_dd->tbl_arm_dd_items}` AS i WHERE 1=1 "
                        . $where_condition
                        . " ORDER BY {$order_by} {$sorting_ord}";

            $form_result = $wpdb->get_results($arm_pro_tmp_query);
            $total_after_filter = count($form_result);
            
            $offset = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
            $number = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;
            $arm_pro_query = $arm_pro_tmp_query . " LIMIT {$offset},{$number}";
            $form_result = $wpdb->get_results($arm_pro_query);
            $grid_data = array();
            $ai = 0;
            foreach ($form_result as $items) {
                $arm_item_id = $items->arm_item_id;
                $arm_item_name = $items->arm_item_name;
                //$arm_item_summery = $items->arm_item_summery;
                //$arm_item_description = $items->arm_item_description;
                //$arm_item_type = $items->arm_item_type;
                $arm_item_urls = $items->arm_item_url;
                //$arm_item_note = $items->arm_item_note;
                $arm_item_permission_type = $items->arm_item_permission_type;
                $arm_item_permission = maybe_unserialize($items->arm_item_permission);
                if ($items->arm_item_download_count != '') {
                    $arm_download_count = $items->arm_item_download_count;
                }else{
                    $arm_download_count = $items->arm_item_dd_count;
                }
                
                $arm_item_status = $items->arm_item_status;
                $arm_item_datetime = date($date_format, strtotime($items->arm_item_datetime));
                
                $arm_item_permission_text = '';
                $arm_item_permission_title = '';
                if($arm_item_permission_type == 'user') {
                    $arm_item_permission_type_text = __('User Wise Restriction', 'ARM_DD');
                    $arm_user_restriction_type = isset($arm_item_permission['arm_user_restriction_type']) ? $arm_item_permission['arm_user_restriction_type'] : 'allowed_user';
                    if($arm_user_restriction_type == 'allowed_user'){
                        $arm_item_permission_title = __('Allowed Users', 'ARM_DD');
                    } else {
                        $arm_item_permission_title = __('Denied Users', 'ARM_DD');
                    }
                    $users = get_users( array( 'include' => $arm_item_permission ) );
                    $usernames = array();
                    foreach( $users as $user ) {
                        $usernames[] = $user->user_login;
                    }
                    $arm_item_permission_text = implode( ', ', $usernames );
                } else if($arm_item_permission_type == 'plan') {
                    $arm_item_permission_title = __('Allowed Plans', 'ARM_DD');
                    $arm_item_permission_type_text = __('Plan Wise Restriction', 'ARM_DD');
                    $arm_item_permission_text = stripslashes($arm_subscription_plans->arm_get_comma_plan_names_by_ids($arm_item_permission));
                } else if($arm_item_permission_type == 'role') {
                    $arm_item_permission_title = __('Allowed Roles', 'ARM_DD');
                    $arm_item_permission_type_text = __('Role Wise Restriction', 'ARM_DD');
                    $arm_item_permission_text = implode(', ', array_map('ucfirst', $arm_item_permission));
                } else {
                    $arm_item_permission_type_text = __('All Users', 'ARM_DD');
                }

                $arm_item_permission_text = ($arm_item_permission_text == '') ? '-' : $arm_item_permission_text;

                $arm_item_permission_content = '';
                if(strlen($arm_item_permission_text) > 45){
                    $arm_item_permission_content .= "<div class='armGridActionTD' >";
                    $arm_item_permission_content .= "<a href='javascript:void(0);' onclick='showPermissionBoxCallback({$arm_item_id});'>".substr($arm_item_permission_text, 0, 45)."...</a>";
                    $arm_item_permission_content .= "<div class='arm_confirm_box arm_permission_box_{$arm_item_id}' id='arm_permission_box_{$arm_item_id}'>";
                    $arm_item_permission_content .= "<div class='arm_confirm_box_body'>";
                    $arm_item_permission_content .= "<div class='arm_confirm_box_arrow'></div>";
                    $arm_item_permission_content .= "<div class='arm_confirm_box_text'>";
                    $arm_item_permission_content .= "<div class='arm_item_permission'>";
                    $arm_item_permission_content .= "<b>".$arm_item_permission_title."</b>";
                    $arm_item_permission_content .= "<br/><ul><li>";
                    $arm_item_permission_content .= str_replace(',', '</li><li>', $arm_item_permission_text);
                    $arm_item_permission_content .= "</li></ul>";
                    $arm_item_permission_content .= "</div>";
                    $arm_item_permission_content .= "</div>";
                    $arm_item_permission_content .= "<div class='arm_confirm_box_btn_container'>";
                    $arm_item_permission_content .= "<button type='button' class='arm_confirm_box_btn armemailaddbtn' onclick='hideConfirmBoxCallback();'>" . __('Close', 'ARM_DD') . "</button>";
                    $arm_item_permission_content .= "</div>";
                    $arm_item_permission_content .= "</div>";
                    $arm_item_permission_content .= "</div>";
                    $arm_item_permission_content .= "</div>";
                } else {
                    $arm_item_permission_content = $arm_item_permission_text;   
                }

                
                $arm_item_shortcode = '<button type="button" class="armemailaddbtn arm_dd_generate_shortcode" data-id="'.$arm_item_id.'" data-name="'.$arm_item_name.'" >'.__('Generate Shortcode', 'ARM_DD').'</button>';
                $arm_pro_active = '';
                $arm_pro_active .= '<div class="arm_temp_switch_wrapper" style="width: auto;margin: 5px 0;">';
                $arm_pro_active .= '<div class="armswitch arm_item_active">';
                $arm_pro_active .= '<input type="checkbox" id="arm_item_active_switch_'.$arm_item_id.'" value="1" class="armswitch_input arm_item_active_switch" name="arm_item_active_switch_'.$arm_item_id.'" data-item_id="'.$arm_item_id.'" '.checked($arm_item_status, 1, false).'/>';
                $arm_pro_active .= '<label for="arm_item_active_switch_'.$arm_item_id.'" class="armswitch_label"></label>';
                $arm_pro_active .= '<span class="arm_status_loader_img" style="display: none;"></span>';
                $arm_pro_active .= '</div></div>';
                
                $gridAction = "<div class='arm_grid_action_btn_container'>";

                if( is_array($arm_item_urls)) {
                    foreach($arm_item_urls as $arm_url_key => $arm_url) {
                        $arm_item_url = site_url().'?arm_dd_file='.$arm_item_id.'&arm_file_id='.$arm_url_key;
                    }
                } else {
                    $arm_item_url = site_url().'?arm_dd_file='.$arm_item_id.'&arm_file_id=0';
                }

                $gridAction .= "<a href='javascript:void(0)'><span class='arm_dd_click_to_copy_text' data-code='{$arm_item_url}'><img src='" . ARM_DD_IMAGES_URL . "/grid_copy_icon.png' class='armhelptip' title='" . __('Copy link to clipboard', 'ARM_DD') . "' onmouseover=\"this.src='" . ARM_DD_IMAGES_URL . "/grid_copy_icon_hover.png';\" onmouseout=\"this.src='" . ARM_DD_IMAGES_URL . "/grid_copy_icon.png';\" /></span></a>";

                $editaction = admin_url('admin.php?page=arm_dd_item&action=edit_item&id='.$arm_item_id);
                $gridAction .= "<a href='" . $editaction . "' class='armhelptip' title='" . __('Edit Download', 'ARM_DD') . "' ><img src='" . ARM_DD_IMAGES_URL . "grid_edit.png' onmouseover=\"this.src='" . ARM_DD_IMAGES_URL . "grid_edit_hover.png';\" onmouseout=\"this.src='" . ARM_DD_IMAGES_URL . "grid_edit.png';\" /></a>";
                $gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$arm_item_id});'><img src='" . ARM_DD_IMAGES_URL . "grid_delete.png' class='armhelptip' title='" . __('Delete', 'ARM_DD') . "' onmouseover=\"this.src='" . ARM_DD_IMAGES_URL . "grid_delete_hover.png';\" onmouseout=\"this.src='" . ARM_DD_IMAGES_URL . "grid_delete.png';\" /></a>";
                $gridAction .= $arm_global_settings->arm_get_confirm_box($arm_item_id, __("Are you sure you want to delete this item?", 'ARM_DD'), 'arm_dd_item_delete_btn');
                $gridAction .= "</div>";
                
                
                $grid_data[$ai][0] = "<input id=\"cb-item-action-{$arm_item_id}\" class=\"chkstanard\" type=\"checkbox\" value=\"{$arm_item_id}\" name=\"item-action[]\">";
                $grid_data[$ai][1] = $arm_item_id; 
                $grid_data[$ai][2] = $arm_item_name;
                $grid_data[$ai][3] = $arm_item_permission_type_text;
                $grid_data[$ai][4] = $arm_item_permission_content;
                $grid_data[$ai][5] = $arm_download_count;
                $grid_data[$ai][6] = $arm_item_shortcode;
                $grid_data[$ai][7] = $arm_pro_active;
                $grid_data[$ai][8] = $arm_item_datetime ;    
                $grid_data[$ai][9] = $gridAction;
             
                $ai++;
            }
            
            $sEcho = isset($_REQUEST['sEcho']) ? intval($_REQUEST['sEcho']) : intval(10);
            $response = array(
                'sColumns' => implode(',', $grid_columns),
                'sEcho' => $sEcho,
                'iTotalRecords' => $total_before_filter, // Before Filtered Records
                'iTotalDisplayRecords' => $total_after_filter, // After Filter Records
                'aaData' => $grid_data,
            );
            echo json_encode($response);
            die();
        }

        function arm_dd_item_ajax_action() {
            global $wpdb, $arm_dd, $ARMember;

            if( method_exists($ARMember, 'arm_check_user_cap') ){
                $arm_dd_capabilities = $arm_dd->arm_dd_page_slug();
                $ARMember->arm_check_user_cap($arm_dd_capabilities['1'],'1');
            }
            $action_data = $_POST;
            if( isset( $action_data['act'] ) && $action_data['act'] ){
                if( isset( $action_data['id'] ) && $action_data['id'] != '' && $action_data['act'] == 'delete' )
                {
                    if (!current_user_can('arm_dd_item')) {
                        $response = array( 'type' => 'error', 'msg'=> __( 'Sorry, You do not have permission to perform this action.', 'ARM_DD' ) );
                    } else {
                        $this->arm_dd_item_remove_files($action_data['id']);
                        $delete_item = $wpdb->query( $wpdb->prepare( "DELETE FROM `$arm_dd->tbl_arm_dd_items` WHERE arm_item_id = %d", $action_data['id'] ) );
                        $delete_item = $wpdb->query( $wpdb->prepare( "DELETE FROM `$arm_dd->tbl_arm_dd_downloads` WHERE arm_dd_item_id = %d", $action_data['id'] ) );
                        $response = array( 'type' => 'success', 'msg' => __( 'Item deleted successfully.', 'ARM_DD' ) );                    
                    }
                }
            }
            else
            {
                 $response = array( 'type' => 'error', 'msg'=> __( 'Sorry, Action not found.', 'ARM_DD' ) );
            }
            echo json_encode($response);
            die;
        }
        
        function arm_dd_item_bulk_action() {
            global $wpdb, $ARMember, $arm_global_settings, $arm_dd;
            
            if( method_exists($ARMember, 'arm_check_user_cap') ){
                $arm_dd_capabilities = $arm_dd->arm_dd_page_slug();
                $ARMember->arm_check_user_cap($arm_dd_capabilities['1'],'1');
            }
            if (!isset($_POST)) {
                    return;
            }
            $bulkaction = $arm_global_settings->get_param('action1');
            $ids = $arm_global_settings->get_param('item-action', '');
                        
            if (empty($ids)) {
                $errors[] = __('Please select one or more records.', 'ARM_DD');
            } else {
                if ($bulkaction == '' || $bulkaction == '-1') {
                        $errors[] = __('Please select valid action.', 'ARM_DD');
                } else 
                {
                    if (!is_array($ids)) {
                        $ids = explode(',', $ids);
                    }
                    
                    if (!current_user_can('arm_dd_item')) {
                        $errors[] = __('Sorry, You do not have permission to perform this action', 'ARM_DD');
                    } else {
                        if (is_array($ids)) {
                            foreach($ids as $id){ $this->arm_dd_item_remove_files($id); }
                            $pro_ids = implode(',',$ids);
                            $delete_item = $wpdb->query( "DELETE FROM `$arm_dd->tbl_arm_dd_items` WHERE arm_item_id IN (".$pro_ids.")");
                            $delete_item = $wpdb->query( "DELETE FROM `$arm_dd->tbl_arm_dd_downloads` WHERE arm_dd_item_id IN (".$pro_ids.")");
                            $message = __('Item(s) has been deleted successfully.', 'ARM_DD');
                            $return_array = array( 'type'=>'success', 'msg'=>$message );
                        }
                    }
                }
            }
            if(!isset($return_array))
            {
                $return_array = $arm_global_settings->handle_return_messages(@$errors, @$message);
                $ARMember->arm_set_message('success',$message);
            }
            echo json_encode($return_array);
            die;
        }
        
        function arm_dd_item_remove_files( $item_id ) {
            $item_data = $this->arm_dd_item_data($item_id);
            if( $item_data )
            {
                $arm_item_urls = isset($item_data['arm_item_url']) ? $this->arm_dd_item_file_urls($item_data['arm_item_url']) : '';
                $arm_file_name = (isset($arm_item_urls) && $arm_item_urls!='' ) ? $this->arm_dd_item_file_name_of_url($arm_item_urls) : '';
                if(is_array($arm_file_name)) {
                    foreach($arm_file_name as $file_name)
                    if($file_name != '' && file_exists(ARM_DD_OUTPUT_DIR . $file_name)) {
                        unlink (ARM_DD_OUTPUT_DIR . $file_name);
                    }
                } else if($arm_file_name != '' && file_exists(ARM_DD_OUTPUT_DIR . $arm_file_name)) {
                    unlink (ARM_DD_OUTPUT_DIR . $arm_file_name);
                }
            }
        }
        
        function arm_dd_item_update_status() {
            if (current_user_can('administrator')) {
                global $wpdb, $arm_dd, $ARMember;
                
                if( method_exists($ARMember, 'arm_check_user_cap') ){
                    $arm_dd_capabilities = $arm_dd->arm_dd_page_slug();
                    $ARMember->arm_check_user_cap($arm_dd_capabilities['1'],'1');
                }
                $wpdb->update( 
                        $arm_dd->tbl_arm_dd_items, 
                        array( 'arm_item_status' => $_REQUEST['arm_pro_status'], ), 
                        array( 'arm_item_id' => $_REQUEST['arm_item_id'] ), 
                        array( '%s' ), 
                        array( '%d' ) 
                    );
                
                $response = array( 'type'=>'success' );
            }
            else
            {
                $error_msg = __('Sorry, You do not have permission to perform this action', 'ARM_DD');
                $response = array( 'type'=>'error', 'msg'=>$error_msg );
            }
            echo json_encode($response);
            die;
        }
        
        function arm_dd_item_file_delete() {
            $file_name = $_POST['file_name'];
            if($file_name != '' && file_exists( ARM_DD_OUTPUT_DIR . $file_name )) {
                unlink( ARM_DD_OUTPUT_DIR . $file_name );
                $response = array( 'type'=>'success' );
            } else {
                $error_msg = __('Sorry, File not found.', 'ARM_DD');
                $response = array( 'type'=>'error', 'msg'=>$error_msg );
            }
            echo json_encode($response);
            die;
        }
        
        function arm_dd_item_check_permission( $item_data ) {
            global $wpdb, $ARMember, $arm_dd;
            if(!is_array($item_data) && $item_data > 0) :
                $item_data = $arm_dd_items->arm_dd_item_data( $item_data );
            endif;
            
            if(isset($item_data['arm_item_id']) && $item_data['arm_item_id'] > 0)
            {
                $arm_dd_settings = $arm_dd->arm_dd_get_settings();
                $arm_user_id = 0;
                if( is_user_logged_in() ) {
                    $arm_user_id = get_current_user_id();
                    $user_plan_ids = get_user_meta($arm_user_id, 'arm_user_plan_ids', true);
                    $user_plan_ids = !empty($user_plan_ids) ? $user_plan_ids : array(-2);
                    if(current_user_can('administrator'))
                    {
                        return true;
                    }
                }
                
                $blocked_user = isset($arm_dd_settings['block_users']) ? $arm_dd_settings['block_users'] : 0;
                if( !empty($blocked_user) && in_array( $arm_user_id, $blocked_user ) ) {
                    return false;
                }
                
                $blocked_plans = isset($arm_dd_settings['block_plans']) ? $arm_dd_settings['block_plans'] : 0;
                if( !empty($blocked_plans) && is_user_logged_in() ) {
                    $blocked_user_plans = array_intersect($user_plan_ids, $blocked_plans);
                    
                    if( !empty($blocked_user_plans) ) {

                        return false;
                    }
                }

                $block_ip_address = isset($arm_dd_settings['block_ip_address']) ? $arm_dd_settings['block_ip_address'] : 0;
                if( !empty($block_ip_address) ) {
                    $block_ips = explode(PHP_EOL, $block_ip_address); 
                    $user_ip_address = $ARMember->arm_get_ip_address();
                    if( !empty($block_ips) && in_array( $user_ip_address, $block_ips ) ) {
                        return false;
                    }
                }

                $arm_item_permission_type = isset($item_data['arm_item_permission_type']) ? $item_data['arm_item_permission_type'] : '';
                $arm_item_temp_permission = isset($item_data['arm_item_permission']) ? $item_data['arm_item_permission'] : '';
                $arm_item_permission = maybe_unserialize($arm_item_temp_permission);
                
                if( $arm_item_permission_type == 'any' ) {
                    return true;
                } elseif( $arm_item_permission_type == 'user' && is_user_logged_in() ) {
                    $arm_user_restriction_type = '';
                    if( isset( $arm_item_permission['arm_user_restriction_type'] ) ){
                        $arm_user_restriction_type = $arm_item_permission['arm_user_restriction_type'];
                        unset($arm_item_permission['arm_user_restriction_type']);
                    }
                    if( !empty( $arm_user_restriction_type ) ) {
                        if( $arm_user_restriction_type == 'allowed_user' && is_array( $arm_item_permission ) && in_array( $arm_user_id, $arm_item_permission )) {
                            return true;
                        }
                        else if( $arm_user_restriction_type == 'denied_user' && is_array( $arm_item_permission ) && !in_array( $arm_user_id, $arm_item_permission ) ) {
                            return true;
                        } 
                        else {
                            return false;
                        }
                    }
                } elseif( $arm_item_permission_type == 'plan' && is_user_logged_in() ) {
                    if( is_array( $arm_item_permission ) && !empty( $user_plan_ids ) ) {
                        $plan_ids = array_intersect( $user_plan_ids,$arm_item_permission );
                        if( !empty( $plan_ids ) ) { 
                            return true;
                        }
                    }
                } elseif( $arm_item_permission_type == 'role' && is_user_logged_in() ) {
                    $user_info = get_userdata($arm_user_id);
                    //$user_roles = implode(', ', $user_info->roles);
                    $user_roles = $user_info->roles;
                    if( is_array( $arm_item_permission ) && !empty( $user_roles ) ) {
                        $user_roles = array_intersect( $user_roles,$arm_item_permission );
                        if( !empty( $user_roles ) ) { 
                            return true;
                        }
                    }
                } else {
                    return false;
                }  
            }
            else
            {
                return false;
            }
        }

        function arm_dd_item_filter_ajax_action(){

            global $wpdb, $ARMember, $arm_dd, $arm_global_settings;

            if(isset($_REQUEST['action']) && $_REQUEST['action']=='arm_dd_item_filter_ajax_action') {
                $text = isset($_REQUEST['txt']) ? $_REQUEST['txt'] : '';
                $text = !empty($text) ? '%'.$text.'%' : '';
                global $wpdb;

                $arm_dd_filter_download_items = $wpdb->get_results($wpdb->prepare("SELECT arm_item_id,arm_item_name FROM `{$arm_dd->tbl_arm_dd_items}` WHERE arm_item_name LIKE %s ORDER BY arm_item_datetime DESC LIMIT 10", $text));

                $arm_dd_filter_download_items_array = array();
                if(!empty($arm_dd_filter_download_items)) {

                    foreach ( $arm_dd_filter_download_items as $arm_dd_filter_download_item ) {
                        
                        $arm_dd_filter_download_items_array[] = array(
                                    'id' => $arm_dd_filter_download_item->arm_item_id,
                                    'value' => $arm_dd_filter_download_item->arm_item_name,
                                    'label' => $arm_dd_filter_download_item->arm_item_name,
                                );
                    }
                }
                
                $response = array('status' => 'success', 'data' => $arm_dd_filter_download_items_array);
                echo json_encode($response);
                die;
            }
        }

        function arm_dd_get_bpopup_html($args){

            
            $defaults = array(
                'id' => '',
                'class' => '',
                'title' => '',
                'content' => '',
                'button_id' => '',
                'button_onclick' => '',
                'ok_btn_class' => '',
                'ok_btn_text' => __('Generate Shortcode', 'ARM_DD'),
                'cancel_btn_text' => '',
                'downalod_item' => __("Download Item", "ARM_DD"),
                'select_type' => __("Select Type", "ARM_DD"),
                'link' => __("Link","ARM_DD"),
                'button' => __("Button","ARM_DD"),
                'display_description' => __("Display Description","ARM_DD"),
                'display_file_size' => __("Display File Size", "ARM_DD"),
                'display_download_count' => __("Display Download Count", "ARM_DD"),
                'custom_css' => __("Custom Css", "ARM_DD"),
                'click_to_copy' => __("Click to copy", "ARM_DD"),
                'code_copied' => __("Code Copied", "ARM_DD"),

            );
            extract(shortcode_atts($defaults, $args));

            $popup = '<div class="popup_wrapper ' . $class . '"><form method="post" action="#" id="arm_digital_download_shortcode_form" name="arm_digital_download_shortcode_form" class="arm_digital_download_shortcode_form"><table cellspacing="0"><tr class="popup_wrapper_inner">';
            $popup .= '<td class="dd_generate_shortcode_close_btn arm_popup_close_btn"></td>';
            $popup .= '<td class="popup_header">' . $title . '</td>';        
            $popup .= '<td class="popup_content_text"><div class="arm_group_body">';
            $popup .= '<table class="arm_shortcode_option_table">';
            $popup .= '<tr><th>'.$downalod_item.'</th><td><div id="arm_dd_item_name"></div></td><tr>';
            $popup .= '<tr><th>'.$select_type.'</th>
                           <td>
                                <input id="arm_dd_item_id" class="arm_dd_item_id" type="hidden" name="item_id" value=""  />
                                <input type="hidden" id="arm_dd_link_type"  name="link_type" value="" />
                                <dl class="arm_selectbox column_level_dd">
                                    <dt>
                                        <span></span>
                                        <input type="text" style="display:none;" value="" class="arm_autocomplete"/>
                                        <i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_dd_link_type">
                                            <li data-label='.$select_type.' data-value="">'.$select_type.'</li>

                                            <li data-label='.$link.' data-value="link" >'.$link.'</li>

                                            <li data-label='.$button.' data-value="button">'.$button.'</li>
                                        </ul>
                                    </dd>
                                </dl>
                           </td>
                        </tr>';
            $popup .= '<tr>
                               <th>'.$display_description.'</th>
                               <td>
                                   <div class="arm_form_fields_wrapper">
                                       <div class="armclear"></div>
                                       <div class="armswitch arm_dd_switch" style="vertical-align: middle;">
                                           <input type="checkbox" id="arm_dd_display_desc_input" value="" class="armswitch_input" name="show_description"/>
                                           <label for="arm_dd_display_desc_input" class="armswitch_label"></label>
                                       </div>
                                   </div>

                               </td>
                        </tr>';
            $popup  .= '<tr>
                               <th>'.$display_file_size.'</th>
                               <td>
                                   <div class="arm_form_fields_wrapper">
                                       <div class="armclear"></div>
                                       <div class="armswitch arm_dd_switch" style="vertical-align: middle;">
                                           <input type="checkbox" id="arm_dd_display_files_input" value="" class="armswitch_input" name="show_size"/>
                                           <label for="arm_dd_display_files_input" class="armswitch_label"></label>
                                       </div>
                                   </div>
                               </td>
                        </tr>';
            $popup  .= '<tr>
                               <th>
                                   <label>'.$display_download_count.'</label>
                               </th>
                               <td>
                                   <div class="arm_form_fields_wrapper">
                                       <div class="armclear"></div>
                                       <div class="armswitch arm_dd_switch" style="vertical-align: middle;">
                                           <input type="checkbox" id="arm_dd_display_download_count_input" value="" class="armswitch_input" name="show_download_count"/>
                                           <label for="arm_dd_display_download_count_input" class="armswitch_label"></label>
                                       </div>
                                   </div>
                               </td>
                        </tr>';
            $popup  .=  '<tr>
                               <th>'.$custom_css.'</th>
                               <td>
                                   <textarea class="arm_popup_textarea" name="css" rows="3"></textarea>
                                   
                               </td>
                        </tr>';            
            $popup  .=  '</table></div></td>';
            
            $popup  .=  '<td class="arm_dd_shortcode_btn_wrapper popup_footer">
                        <div class="popup_content_btn_wrapper">                                        
                            <button class="arm_save_btn arm_dd_shortcode_insert_btn" type="submit" data-type="add" data-code="arm_download" >' . $ok_btn_text . '</button>
                        </div>
                        </td>';
            $popup  .=  '<td class="arm_insert_dd_shortcode_main_wrapper popup_footer">
                        <div class="arm_shortcode_text arm_form_shortcode_box arm_insert_dd_shortcode_wrapper" style="width: 50px;">
                            <div class="armCopyText arm_insert_dd_shortcode"></div>
                            <span class="arm_click_to_copy_text">'.$click_to_copy.'</span>
                            <span class="arm_copied_text"><img src="'.ARM_DD_IMAGES_URL.'/copied_ok.png" alt="ok">'.$code_copied.'</span>
                        </div>
                        </td>';
            $popup  .=  '</tr></table><div class="armclear"></div></form></div>';
            return $popup; 

        }

    }
}

global $arm_dd_items;
$arm_dd_items = new arm_dd_items();
?>