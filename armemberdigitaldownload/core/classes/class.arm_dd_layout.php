<?php
if(!class_exists('arm_dd_layout')){
    
    class arm_dd_layout{
        
        function __construct(){
            
            add_action( 'wp_head', array( $this, 'arm_dd_set_front_js_css' ) );

            add_action('arm_enqueue_js_css_from_outside', array(&$this,'arm_enqueue_dd_js_css_for_model'),10);
            
            add_shortcode( 'arm_download', array( $this, 'arm_dd_shortcode_content' ) );
            
            add_action( 'init', array( $this, 'arm_dd_download_file' ) );
        }
        
        function arm_dd_set_front_js_css( $force_enqueue = false ) {
            global $ARMember, $arm_dd_version;
            $is_arm_front_page = $ARMember->is_arm_front_page();
            if ( $is_arm_front_page === TRUE || $force_enqueue == TRUE )
            {
                
            }
        }

        function arm_enqueue_dd_js_css_for_model() {
            $this->arm_dd_set_front_js_css(true);
        }

        function arm_dd_formatbytes($file, $type = 'byte')
        {
          $filesize = 0;
          $type = 'byte';
          if(file_exists($file)){

             $filesize = filesize($file);

             if($filesize < 1024){
                $type = 'byte';
             }
             else if($filesize >= 1024 && $filesize < 1048576){
                 $filesize = $filesize * .0009765625; 
                 $type = 'KB';
             }
             else if($filesize >= 1048576 && $filesize < 1073741824){
                 $filesize = ($filesize * .0009765625) * .0009765625; 
                 $type = 'MB';
             }
             else if($filesize >= 1073741824 && $filesize < 1099511627776){
                 $filesize = (($filesize * .0009765625) * .0009765625) * .0009765625; 
                 $type = 'GB';
             }
             else if($filesize >= 1099511627776){
                 $filesize = ((($filesize * .0009765625) * .0009765625) * .0009765625)* .0009765625; 
                 $type = 'TB';
             }
            
              // switch($type){
              // case "KB":
              //    $filesize = filesize($file) * .0009765625; // bytes to KB
              // break;
              // case "MB":
              //    $filesize = (filesize($file) * .0009765625) * .0009765625; // bytes to MB
              // break;
              // case "GB":
              //    $filesize = ((filesize($file) * .0009765625) * .0009765625) * .0009765625; // bytes to GB
              // break;
              // } 
          }
           
           if($filesize <= 0){
              return $filesize = '';}
           else{return round($filesize,2).' '.$type;}
        }
    
        
        function arm_dd_shortcode_content($atts, $content, $tag) {
            global $arm_dd, $arm_dd_version, $arm_dd_items, $wpdb;

            $args = shortcode_atts(array(
                'item_id' => '0',
                'link_type' => false,
                //'show_summery' => false,
                'show_description' => false,
                //'show_notes' => false,
                'show_size' => false,
                'show_download_count' => false,
                'css' => ''
            ), $atts, $tag);
            
            $arm_dd_settings = $arm_dd->arm_dd_get_settings();
            
            $arm_no_item_found_msg = __('No any downloads found.', 'ARM_DD');
            $arm_download = (isset($arm_dd_settings['download_zip']) && $arm_dd_settings['download_zip'] == 1 ) ? 'zip' : 'item';
            if($args['item_id'] != 0) {
                $arm_item_data = array();
                $item_id_array = explode(',', $args['item_id']);
                $arm_access_denied_data = array();
                if( is_array($item_id_array) ) {

                   
                    foreach($item_id_array as $item_id) {
                        $item_data = $arm_dd_items->arm_dd_item_data( $item_id );

                        
                        if(isset($item_data['arm_item_id']) && $item_data['arm_item_id']>0 ) {
                            array_push($arm_item_data, $item_data);
                        } else {
                            array_push($arm_access_denied_data, $item_data);
                        }
                    }
                }
                
                if( is_array($arm_item_data) && !empty($arm_item_data) ) {
                    $content .= "<style>" . $this->arm_dd_get_style() . $args['css'] . "</style>";
                    foreach($arm_item_data as $item_data) {
                        
                        if( $arm_dd_items->arm_dd_item_check_permission($item_data) ) {
                            $content_link = '';
                            $arm_item_id = $item_data['arm_item_id'];
                            $arm_item_urls = isset($item_data['arm_item_url']) ? $arm_dd_items->arm_dd_item_file_urls($item_data['arm_item_url'], $arm_download) : array();
                            $arm_item_download_count = isset($item_data['arm_item_download_count']) ? $item_data['arm_item_download_count'] : '';

                            $item_title = $item_data['arm_item_name'];
                          
                            if(!empty($arm_item_urls)){
                            if( is_array($arm_item_urls)) {

                                $file_names = (isset($item_data['arm_file_names']) && $item_data['arm_file_names']!='') ? maybe_unserialize($item_data['arm_file_names']) : array();
                                foreach($arm_item_urls as $arm_url_key => $arm_url) {

                                        $total_count = $arm_item_download_count;

                                    if($arm_dd_items->arm_dd_item_open_browser($arm_url, $arm_dd_settings)) {
                                        /*$arm_item_url = ARM_DD_OUTPUT_URL . $arm_url;*/ 
                                        $arm_item_url = site_url().'?arm_dd_file='.$arm_item_id.'&arm_file_id='.$arm_url_key;
                                        $arm_a_attr = 'target="_blank"';
                                    } else {
                                        $arm_item_url = site_url().'?arm_dd_file='.$arm_item_id.'&arm_file_id='.$arm_url_key;
                                        $arm_a_attr = 'target="_self"';
                                    }
                                    $label = ($total_count > 1) ? __("Downloads", 'ARM_DD') : __("Download", 'ARM_DD');

                                    $download_item_title = (isset($file_names[$arm_url_key]) && $file_names[$arm_url_key] != '') ? $file_names[$arm_url_key] : $item_title;

                                    if ($args['link_type']=="button") {
                                        $content_link.= '<div class="arm_download_file"><a href="'.$arm_item_url.'" '.$arm_a_attr.'><input type="button" value="'.$download_item_title;

                                        if($args['show_size']){
                                            $file_path = ARM_DD_OUTPUT_DIR . $arm_url;
                                            $file_size = ' - '.$this->arm_dd_formatbytes($file_path, "KB");
                                            $content_link.= $file_size;
                                        }

                                        if($args['show_download_count']){
                                            $content_link.= ' ('.$total_count.' '.$label.')';
                                        }

                                        $content_link.= '" style="text-decoration: none;"></a></div>';

                                    }else{
                                        $content_link.= '<div class="arm_download_file"><a href="'.$arm_item_url.'" '.$arm_a_attr.'>'.$download_item_title;

                                        if($args['show_size']){
                                            $file_path = ARM_DD_OUTPUT_DIR . $arm_url;
                                            $file_size = ' - '.$this->arm_dd_formatbytes($file_path, "KB");
                                            $content_link.= $file_size;
                                        }

                                        if($args['show_download_count']){
                                            $content_link.= ' ('.$total_count.' '.$label.')';
                                        }
                                        $content_link.= '</a></div>';
                                    }
                                }
                            } else {
                                    $total_count = $arm_item_download_count;
                                if($arm_dd_items->arm_dd_item_open_browser($arm_item_urls, $arm_dd_settings)) {
                                    /*$arm_item_url = ARM_DD_OUTPUT_URL. $arm_item_urls;*/
                                    $arm_item_url = site_url().'?arm_dd_file='.$arm_item_id.'&arm_file_id=0';
                                    $arm_a_attr = 'target="_blank"';
                                } else {
                                    $arm_item_url = site_url().'?arm_dd_file='.$arm_item_id.'&arm_file_id=0';
                                    $arm_a_attr = 'target="_self"';
                                }
                                $label = ($total_count > 1) ? __("Downloads", 'ARM_DD') : __("Download", 'ARM_DD');
                               $download_item_title = (isset($file_names[0]) && $file_names[$arm_url_key] != '') ? $file_names[0] : $item_title;

                                if ($args['link_type']=='button') {
                                    $content_link.= '<div class="arm_download_file"><a href="'.$arm_item_url.'" '.$arm_a_attr.'><input type="button" value="'.$download_item_title;

                                    if($args['show_size']){
                                        $file_path = ARM_DD_OUTPUT_DIR . $arm_item_urls;
                                        $file_size = ' - '.$this->arm_dd_formatbytes($file_path, "KB");
                                        $content_link.= $file_size;
                                    }

                                    if($args['show_download_count']){
                                        $content_link.= ' ('.$total_count.' '.$label.')';
                                    }

                                    $content_link.= '" style="text-decoration: none;"></a></div>';
                                }else{
                                    $content_link.= '<div class="arm_download_file"><a href="'.$arm_item_url.'" '.$arm_a_attr.'>'.$download_item_title;

                                    if($args['show_size']){
                                        $file_path = ARM_DD_OUTPUT_DIR . $arm_item_urls;
                                        $file_size = ' - '.$this->arm_dd_formatbytes($file_path, "KB");
                                        $content_link.= $file_size;
                                    }

                                    if($args['show_download_count']){
                                        $content_link.= ' ('.$total_count.' '.$label.')';
                                    }

                                    $content_link.= '</a></div>';
                                }                                
                            }

                            $item_wrapper = '<div class="arm_download_wrapper">';
                            $item_wrapper .= '<div class="arm_download_title"><h4>'.$content_link.'</h4></div>';
                            // if($args['show_summery'] && $item_data['arm_item_summery'] != '') {
                            //     $item_wrapper .= '<div class="arm_download_summery"><p>'.$item_data['arm_item_summery'].'</p></div>';
                            // }
                            if($args['show_description'] && $item_data['arm_item_description'] != '') {
                                $item_wrapper .= '<div class="arm_download_description"><p>'.nl2br($item_data['arm_item_description']).'</p></div>';
                            }
                        
                            // if($args['show_notes'] && $item_data['arm_item_note'] != '') {
                            //     $item_wrapper .= '<div class="arm_download_notes"><p>'.$item_data['arm_item_note'].'</p></div>';
                            // }
                            $item_wrapper .= '<hr></div>';
                            $item_wrapper .= '<div class="armclear"></div>';
                    }
                            $content .= $item_wrapper;

                        }
                        else
                        {
                             $arm_access_denied_msg = $item_data['arm_item_msg'];
                            $content.= '<div class="arm_download_permission_denied_msg">' . stripslashes($arm_access_denied_msg) . '</div>';
                        }
                    }
                }
            }
            return $content;
        }
        
        function arm_dd_get_style() {
            $return_style = '';
            $return_style .= '.arm_download_permission_denied_msg{ color:#ff0000; width:100%; display: inline-block; margin: 0 0 20px 0; }';

            $return_style .= '.arm_download_wrapper{ width:100%; display: inline-block; margin: 10px 0px 10px 0px; }';
            $return_style .= '.arm_download_wrapper hr{ width:100%; display: inline-block; margin: 0px 0px 0px 0px; }';

            $return_style .= '.arm_download_wrapper .arm_download_title{ width:100%; display: inline-block; }';
            $return_style .= '.arm_download_wrapper .arm_download_title h4{ margin: 0 0 10px 0; }';
            $return_style .= '.arm_download_wrapper .arm_download_title h4 a{display: inline-block; margin: 0 0 5px 0; color:#000000; font-size:17px;}';
            $return_style .= '.arm_download_wrapper .arm_download_file{width:100%;}';

            $return_style .= '.arm_download_wrapper .arm_download_description{ width:100%; display: inline-block; margin: 0 0 5px 0; }';
            $return_style .= '.arm_download_wrapper .arm_download_description p{ margin: 0 0 5px 0; }';

            return $return_style;
        }

        function arm_dd_download_file() {
            global $arm_dd, $wpdb;
            $referer_url = wp_get_referer();
            if(isset($_REQUEST['arm_dd_file']) && $_REQUEST['arm_dd_file'] != '' && isset($_REQUEST['arm_file_id']) && $_REQUEST['arm_file_id'] != '') {
                global $arm_dd, $arm_dd_downloads, $arm_dd_items, $ARMember;
                $arm_dd_item_id = isset( $_REQUEST['arm_dd_file'] ) ? $_REQUEST['arm_dd_file'] : 0;
                $arm_file_id = isset( $_REQUEST['arm_file_id'] ) ? $_REQUEST['arm_file_id'] : 0;
                $item_data = $arm_dd_items->arm_dd_item_data( $arm_dd_item_id );
                $arm_dd_settings = $arm_dd->arm_dd_get_settings();
                $arm_download = (isset($arm_dd_settings['download_zip']) && $arm_dd_settings['download_zip'] == 1 ) ? 'zip' : 'item';
                $requested_file = '';
                $arm_item_urls = isset($item_data['arm_item_url']) ? $arm_dd_items->arm_dd_item_file_urls($item_data['arm_item_url'], $arm_download) : array();
                if( $arm_dd_items->arm_dd_item_check_permission( $item_data )) {
                    $arm_dd_is_added_download = $arm_dd_downloads->arm_dd_download_add( $item_data, $arm_file_id );
                    if( $arm_dd_is_added_download['status'] == 'success' ) 
                    {
                        if(isset($item_data['arm_item_type']))
                        {
                            if($item_data['arm_item_type']=='external')
                            {
                                $requested_file = $arm_item_urls;
                            }
                            else
                            {
                                if( is_array($arm_item_urls) ) {
                                    $arm_item_file_id = isset($arm_item_urls[$arm_file_id]) ? $arm_item_urls[$arm_file_id] : 0;
                                    $requested_file = ARM_DD_OUTPUT_DIR . $arm_item_file_id;
                                } else {
                                    $requested_file = ARM_DD_OUTPUT_DIR . $arm_item_urls;
                                }
                            }
                            
                            if (!empty($arm_dd_settings['open_file_browser'])) 
                            {
                                $content = file_get_contents($requested_file); 
                                
                                $file_ext = explode('.', $requested_file);
                                $file_ext = strtolower(end($file_ext));

                                $arm_dd_ctype = "application/octet-stream";
                                $arm_dd_get_allowed_mime_types = get_allowed_mime_types();
                                
                                foreach ($arm_dd_get_allowed_mime_types  as $mime => $type ) {
                                    $get_single_mime = explode( '|', $mime );
                                    if ( in_array( $file_ext, $get_single_mime ) ) {
                                        $arm_dd_ctype = $type;
                                        break;
                                    }
                                }
                                header('Content-Type: '.$arm_dd_ctype);
                                echo $content; 
                                exit;
                            }
                            else
                            {

                                $as_file_extension = explode('.', $requested_file);
                                $arm_dd_file_extension = strtolower(end($as_file_extension));
                                $arm_dd_ctype = '';
                                if (wp_is_mobile()) 
                                {
                                    $arm_dd_ctype = 'application/octet-stream';
                                } 
                                else 
                                {
                                    $arm_dd_ctype = "application/force-download";
                                }

                                $arm_dd_get_allowed_mime_types = get_allowed_mime_types();
                                foreach ($arm_dd_get_allowed_mime_types  as $mime => $type ) 
                                {
                                    $get_single_mime = explode( '|', $mime );
                                    if (in_array( $arm_dd_file_extension, $get_single_mime)) 
                                    {
                                        $arm_dd_ctype = $type;
                                        break;
                                    }
                                }

                                $arm_dd_file_name = basename($requested_file);
                                if(strstr($arm_dd_file_name, '?')) 
                                {
                                    $arm_dd_file_name = current(explode('?',$arm_dd_file_name));
                                }
                                                              
                                $arm_dd_file_details = $this->arm_dd_get_file_check( $requested_file);
                                if(!empty($arm_dd_file_details['arm_dd_requested_file']))
                                {
                                    $file_path = $arm_dd_file_details['arm_dd_requested_file'];
                                    $file_content_length = $arm_dd_file_details['arm_dd_file_size'];
                                }
                                else 
                                {
                                    $file_path = $requested_file;
                                    $file_content_length = $this->arm_dd_formatbytes($file_path);
                                }

                                if (function_exists('apache_setenv')) 
                                {
                                    @apache_setenv('no-gzip', 1);
                                }

                                @ini_set('zlib.output_compression', 'Off');
                                header("Robots: none");
                                header("Content-Type: " . $arm_dd_ctype . "");
                                header("Content-Description: File Transfer");
                                header("Content-Disposition: attachment; filename=\"" . $arm_dd_file_name . "\"");
                                header("Content-Transfer-Encoding: binary");
                                if (!empty($file_content_length)) 
                                {
                                    header("Content-Length: ".$file_content_length);
                                    header("Accept-Ranges: bytes");
                                }
                                
                                ob_clean();

                                while ( ob_get_level() > 0 ) {
                                    @ob_end_clean();
                                }
                                flush();

                                readfile($file_path);
                                exit;
                            }
                        }
                        else
                        {
                            @ob_clean();
                            while ( ob_get_level() > 0 ) {
                                @ob_end_clean();
                            }
                            header("location:".$arm_item_urls);
                            exit;
                        }
                    } else if( $arm_dd_is_added_download['status'] == 'failed' ) {

                        echo '<script> alert("'.$arm_dd_is_added_download['message'].'"); </script>';
                        wp_redirect( $referer_url );
                    } else {
                        wp_redirect( $referer_url );
                    }
                } else {
                    echo '<script data-cfasync="false"> alert("'.$item_data['arm_item_msg'].'"); </script>';
                    wp_redirect($referer_url);
                }
            }
        }


        function arm_dd_get_file_check( $arm_dd_requested_file ) {
            if (!empty($arm_dd_requested_file))
            {
                //list( $arm_dd_requested_file, $arm_dd_remote_file ) = $this->parse_file_path( $arm_dd_requested_file );
                $arm_dd_remote_file = true;
                if ( strpos( $arm_dd_requested_file, site_url( '/', 'http' ) ) !== false || strpos( $arm_dd_requested_file, site_url( '/', 'https' ) ) !== false ) {
                    $arm_dd_remote_file = false;
                }
                elseif ( is_multisite() && ( ( strpos( $arm_dd_requested_file, network_site_url( '/', 'http' ) ) !== false ) || ( strpos( $arm_dd_requested_file, network_site_url( '/', 'https' ) ) !== false ) ) ) {
                    $arm_dd_remote_file = false;
                } elseif ( file_exists( $arm_dd_requested_file ) ) {
                    /** Path needs an abspath to work */
                    $arm_dd_remote_file = false;
                    $arm_dd_requested_file   = $arm_dd_requested_file;
                    //$arm_dd_requested_file   = realpath( $arm_dd_requested_file );
                }

                if (!empty($arm_dd_requested_file)) 
                {
                    if($arm_dd_remote_file)
                    {
                        $arm_dd_file = wp_remote_head( $arm_dd_requested_file );

                        if(!is_wp_error($arm_dd_file) && !empty($arm_dd_file['headers']['content-length'])) 
                        {
                            return array( 'arm_dd_requested_file' => $arm_dd_requested_file, 'arm_dd_file_size' => $arm_dd_file['headers']['content-length'] );
                        }
                    } 
                    else 
                    {
                        if(file_exists($arm_dd_requested_file) && ($arm_dd_filesize=filesize($arm_dd_requested_file))) 
                        {
                            return array( 'arm_dd_requested_file' => $arm_dd_requested_file, 'arm_dd_file_size' => $arm_dd_filesize);
                        }
                    }
                }
            }
            return false;
        }

    }
}

global $arm_dd_layout;
$arm_dd_layout = new arm_dd_layout();
?>