<?php
if(!class_exists('arm_aff_layout')){
    
    class arm_aff_layout{

        function __construct(){
            
            add_action('wp_head', array(&$this, 'arm_set_front_js_css'));

            add_action('arm_enqueue_js_css_from_outside', array(&$this,'arm_enqueue_aff_js_css_for_model'),10);

            add_filter('query_vars', array(&$this, 'armaff_user_query_vars'), 12, 1);

            add_shortcode('arm_affiliate', array(&$this, 'arm_aff_shortcode_content'));
            
            add_shortcode('arm_user_referral', array(&$this, 'arm_aff_user_referral'));
            
            add_shortcode('arm_aff_banner', array(&$this, 'arm_aff_banner'));

            add_shortcode('arm_user_payout_transaction', array(&$this, 'arm_user_payout_transaction'));

            add_shortcode('arm_affiliate_register', array(&$this, 'arm_affiliate_register'));

            add_shortcode('arm_if_affiliate', array(&$this, 'arm_if_affiliate_func'));

            add_shortcode('arm_if_non_affiliate', array(&$this, 'arm_if_non_affiliate_func'));

            add_shortcode('arm_aff_visits', array(&$this, 'arm_aff_visits_func'));

            add_shortcode('arm_aff_statistics', array(&$this, 'arm_aff_statistics_func'));

            add_shortcode('arm_aff_earning', array(&$this, 'arm_aff_earning_func'));

            add_shortcode('arm_aff_payment_paid', array(&$this, 'arm_aff_payment_paid_func'));

            add_shortcode('arm_aff_payment_unpaid', array(&$this, 'arm_aff_payment_unpaid_func'));

            add_shortcode('arm_aff_referral', array(&$this, 'arm_aff_referral_func'));

            add_action('wp_ajax_arm_referral_paging_action', array(&$this, 'arm_referral_paging_action'));
            add_action('wp_ajax_nopriv_arm_referral_paging_action', array(&$this, 'arm_referral_paging_action'));

            add_action('wp_ajax_arm_payout_paging_action', array(&$this, 'arm_payout_paging_action'));
            add_action('wp_ajax_nopriv_arm_payout_paging_action', array(&$this, 'arm_payout_paging_action'));
            
            add_action('wp', array(&$this, 'arm_aff_display_crative_banner'));

        }
        
        function arm_set_front_js_css( $force_enqueue = false ) {
            global $ARMember, $arm_affiliate_version;
            $is_arm_front_page = $ARMember->is_arm_front_page();
            if ( $is_arm_front_page === TRUE || $force_enqueue == TRUE )
            {

                wp_register_style( 'arm-aff-css', ARM_AFFILIATE_URL . '/css/arm_aff_front.css', array(), $arm_affiliate_version );
                wp_register_script( 'arm-aff-js', ARM_AFFILIATE_URL . '/js/arm_aff_front.js', array(), $arm_affiliate_version );

                wp_register_script( 'arm-aff-angular-js', ARM_AFFILIATE_URL . '/js/arm_aff_front_angular.js', array(), $arm_affiliate_version );

                wp_enqueue_style( 'arm_fontawesome_css' );
                wp_enqueue_style( 'arm-aff-css' );
                wp_enqueue_script( 'jquery' );
                wp_enqueue_script( 'arm-aff-js' );

                /* Angular Design CSS */
                wp_register_style('arm_angular_material_css', ARM_URL . '/css/arm_angular_material.css', array(), $arm_affiliate_version);
                wp_enqueue_style('arm_angular_material_css');
                /* Angular JS */
                $angularJSFiles = array(
                    'arm_angular_with_material' => ARM_URL . '/js/angular/arm_angular_with_material.js',
                    'arm_form_angular' => ARM_URL . '/js/angular/arm_form_angular.js',
                );
                foreach ($angularJSFiles as $handle => $src) {
                    if (!wp_script_is($handle, 'registered')) {
                        wp_register_script($handle, $src, array(), $arm_affiliate_version, true);
                    }
                    if (!wp_script_is($handle, 'enqueued')) {
                        wp_enqueue_script($handle);
                    }
                }
                wp_enqueue_script( 'arm-aff-angular-js');
                wp_enqueue_style('arm_form_style_css');

            }
        }
        
        function arm_enqueue_aff_js_css_for_model() {
            $this->arm_set_front_js_css(true);
        }

        function armaff_user_query_vars($public_query_vars) {
            global $arm_affiliate_settings;

            $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
            $referral_var = $affiliate_options['arm_aff_referral_var'];

            $public_query_vars[] = $referral_var;
            return $public_query_vars;
        }

        function arm_aff_display_crative_banner(){
            global $arm_affiliate_settings, $arm_aff_banner, $arm_global_settings;
            $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();                
            $referral_var = $affiliate_options['arm_aff_referral_var'];
            
            if(isset($_REQUEST['arm_referral']) && isset($_REQUEST[$referral_var]) && $_REQUEST['arm_referral'] > 0 && $_REQUEST[$referral_var] > 0 ) {
                $arm_item_data = $arm_aff_banner->arm_aff_item_data($_REQUEST['arm_referral']);
                if(isset($arm_item_data['arm_banner_id']) && $arm_item_data['arm_banner_id'] > 0) {
                    
                    $current_page_url = $arm_global_settings->add_query_arg('arm_referral', $_REQUEST['arm_referral'], get_home_url()."/");
                    $current_page_url = $arm_global_settings->add_query_arg('armaff', $_REQUEST['armaff'], $current_page_url);
                    $sitename = get_bloginfo('name');
                    if(!isset($_REQUEST['arm_embed']) || $_REQUEST['arm_embed'] != 'yes' ){
                    $content = '';
                    $meta_content = '';
                    $meta_content .= '<html><head>';
                    $meta_content .= '<title>'.$arm_item_data['arm_title'].'</title>';
                    $meta_content .= '<meta name="Description" content="'.$arm_item_data['arm_description'].'" />';
                    $meta_content .= '<meta name="keywords" content="'.$arm_item_data['arm_title'].'" />';
                    $meta_content .= '<meta charset="UTF-8">';

                    /* Schema.org markup for Google+ */
                    $meta_content .= '<meta itemprop="name" content="'.$arm_item_data['arm_title'].'">';
                    $meta_content .= '<meta itemprop="description" content="'.$arm_item_data['arm_description'].'">';
                    $meta_content .= '<meta itemprop="image" content="'.ARM_AFF_OUTPUT_URL.$arm_item_data['arm_image'].'">';

                    /* Twitter Card data */
                    $meta_content .= '<meta name="twitter:card" content="summary" />';

                    /* Open Graph data */
                    $meta_content .= '<meta property="og:title" content="'.$arm_item_data['arm_title'].'" />';
                    $meta_content .= '<meta property="og:type" content="article" />';
                    $meta_content .= '<meta property="og:url" content="'.$current_page_url.'" />';
                    $meta_content .= '<meta property="og:image" content="'.ARM_AFF_OUTPUT_URL.$arm_item_data['arm_image'].'" />';
                    $meta_content .= '<meta property="og:description" content="'.$arm_item_data['arm_description'].'" />';
                    $meta_content .= '<meta property="og:site_name" content="'.$sitename.'" />';
                    $meta_content .= '</head><body>';
                    $meta_content .= '<img src="'.ARM_AFF_OUTPUT_URL.$arm_item_data['arm_image'].'" />';
                    $meta_content .= '</body></html>';
                    
                    
                    echo $meta_content;
                    }
                    
                    if(isset($_REQUEST['arm_embed']) && $_REQUEST['arm_embed'] == 'yes' ){
                        
                        $current_page_url = $arm_global_settings->add_query_arg('arm_referral', $_REQUEST['arm_referral'], get_home_url()."/");
                        $current_page_url = $arm_global_settings->add_query_arg('armaff', $_REQUEST['armaff'], $current_page_url);
                        $content = '';
                        $img_tag = '<img src="'.ARM_AFF_OUTPUT_URL.$arm_item_data['arm_image'].'" />';
                        $content .= '<h3>'.$arm_item_data['arm_title'].'</h3>';
                        $content .= '<a href="javascript:;" onclick="arm_redirect()">'.$img_tag.'</a>';
                        $content .= '<p>'.nl2br($arm_item_data['arm_description']).'</p>';
                        if($arm_item_data['arm_open_new_tab'] == '1') {
                            $content .= '<script>function arm_redirect(){ window.open("'.$arm_item_data['arm_link'].'", "_blank");}</script>';
                        } else { 
                            $content .= '<script>function arm_redirect(){ window.top.location.href = "'.$arm_item_data['arm_link'].'";}</script>';
                        }

                        echo $content;
                        die;
                        
                    } else {
                        $url = (isset($arm_item_data['arm_link']) && $arm_item_data['arm_link'] != '') ? $arm_item_data['arm_link'] : get_home_url();
                        wp_redirect($url);
                    }
                }
            }
        }

        function arm_aff_banner($atts, $content, $tag) {
            global $ARMember, $arm_aff_banner, $arm_affiliate_settings, $arm_global_settings, $arm_aff_referrals, $arm_affiliate, $wpdb;

            $arm_check_is_gutenberg_page = $ARMember->arm_check_is_gutenberg_page();
            if($arm_check_is_gutenberg_page)
            {
                return;
            }
            
            $logged_in_user = get_current_user_id();
            if(isset($logged_in_user) && $logged_in_user != '' && isset($atts['item_id']) && $atts['item_id'] > 0 && $arm_aff_referrals->arm_check_is_allowed_user($logged_in_user)){
                $args = shortcode_atts(array(
                    'item_id' => '0',
                    'social_fields' => 'facebook,twiiter,linkedin',
                ), $atts, $tag);

                $item_id = $args['item_id'];
                $arm_aff_id = $arm_aff_banner->arm_aff_item_data($item_id);

                if(isset($arm_aff_id['arm_banner_id']) && $arm_aff_id['arm_banner_id'] > 0) {
                    
                    $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();                
                    $referral_var = $affiliate_options['arm_aff_referral_var'];
                    $default_referral_url = (isset($affiliate_options['arm_aff_referral_url']) && $affiliate_options['arm_aff_referral_url'] != '') ? $affiliate_options['arm_aff_referral_url'] : get_home_url() ;
                    $arm_aff_id_encoding = isset($affiliate_options['arm_aff_id_encoding']) ? $affiliate_options['arm_aff_id_encoding'] : 0;

                    $arm_affiliate_id = $this->armaff_get_affiliate_id_by_user( $logged_in_user );

                    $arm_affiliate_id = $arm_affiliate_settings->arm_affiliate_get_user_id($arm_aff_id_encoding, $arm_affiliate_id);
                    $arm_referral_url = '';
                    $arm_referral_url = $arm_global_settings->add_query_arg($referral_var, $arm_affiliate_id, $default_referral_url);

                    
                    $url = $arm_global_settings->add_query_arg('arm_referral', $item_id, get_home_url()."/");
                    $url = $arm_global_settings->add_query_arg($referral_var, $arm_affiliate_id, $url);
                    $embed_url = $arm_global_settings->add_query_arg('arm_embed', 'yes', $url);

                    $image = getimagesize(ARM_AFF_OUTPUT_URL.$arm_aff_id['arm_image']);
                    $image_width = $image[0] + 30;
                    $arm_affiliate_embad_code = '<embed src="'.$embed_url.'" target="_parent" width="'. $image_width .'" height="'.$image[1].'" />';
                    $affiliate_text = '<textarea id="embad_code" name="embad_code">'.$arm_affiliate_embad_code.'</textarea>';
                    $heading = '<h3>'.$arm_aff_id['arm_title'].'</h3>';
                    $img_tag = '<img src="'.ARM_AFF_OUTPUT_URL.$arm_aff_id['arm_image'].'" />';
                    $a_tag = '<a href="javascript:;" onclick="arm_redirect()">'.$img_tag.'</a>';
                    $desc_tag = '<p>'.nl2br($arm_aff_id['arm_description']).'</p>';
                    if($arm_aff_id['arm_open_new_tab'] == '1') {
                        $script = '<script>function arm_redirect(){ window.open("'.$arm_aff_id['arm_link'].'", "_blank");}</script>';
                    } else { 
                        $script = '<script>function arm_redirect(){ window.top.location.href = "'.$arm_aff_id['arm_link'].'";}</script>';
                    }
                    $content .= $heading . $a_tag . $desc_tag . $script;
                    $content .= '<p>'.$affiliate_text.'</p>';
                    
                    
                    $content .= $this->share_banner($arm_aff_id, $url, $args['social_fields']);
                            
                    $content .= '<div class="armclear"></div>';
                }
                echo $content;
            }
        }
        
        function share_banner($banner_data, $url, $allowed_network) {
            global $arm_global_settings;
            $sitename = get_bloginfo('name');
            $shareing_content = '';
            $shareing_content .= "&title=".urlencode($banner_data['arm_title']);
            $shareing_content .= "&description=".urlencode($banner_data['arm_description']);
            $shareing_content .= "&image=".urlencode(ARM_AFF_OUTPUT_URL.$banner_data['arm_image']);
            $shareing_content .= "&site_name=".urlencode($sitename);
            
            $allowed_network = explode(',', rtrim($allowed_network, ','));
            $content = '';
            if (in_array('facebook', $allowed_network)) {
                $shareing_string = "u=".urlencode($url).$shareing_content;
                $content .= "<div class='arm_aff_share arm_facebook'><a href='javascript:void(0)' onclick='javascript:window.open(\"\https://www.facebook.com/sharer.php?{$shareing_string}\", \"_blank\", \"scrollbars=1,resizable=1,height=475,width=625\");'><i class='armfa armfa-facebook'> </i></a></div>";
            }
            if (in_array('twitter', $allowed_network)) {
                $shareing_twitter_content = 'url='.urlencode($url);
                $shareing_twitter_content .= "&title=".urlencode($banner_data['arm_title']);
                $shareing_twitter_content .= "&description=".urlencode($banner_data['arm_description']);
                $shareing_twitter_content .= "&image=".ARM_AFF_OUTPUT_URL.$banner_data['arm_image'];
                $shareing_twitter_content .= "&site_name=".urlencode($sitename);
                $content .= "<div class='arm_aff_share arm_twitter'><a href='javascript:void(0)' onclick='javascript:window.open(\"\https://twitter.com/share?{$shareing_twitter_content}\", \"_blank\", \"scrollbars=1,resizable=1,height=475,width=625\");'><i class='armfa armfa-twitter'> </i></a></div>";
            }
            if (in_array('linkedin', $allowed_network)) {
                $shareing_string = "url=".urlencode($url).$shareing_content;
                $content .= "<div class='arm_aff_share arm_linked'><a href='javascript:void(0)' onclick='javascript:window.open(\"\https://www.linkedin.com/cws/share?{$shareing_string}\", \"_blank\", \"scrollbars=1,resizable=1,height=475,width=625\");'><i class='armfa armfa-linkedin'> </i></a></div>";
            }
            if (in_array('vkontakt', $allowed_network)) {
                $shareing_string = "url=".urlencode($url).$shareing_content;
                $content .= "<div class='arm_aff_share arm_vk'><a href='javascript:void(0)' onclick='javascript:window.open(\"\http://vk.com/share.php?{$shareing_string}\", \"_blank\", \"scrollbars=1,resizable=1,height=475,width=625\");'><i class='armfa armfa-vk'> </i></a></div>";
            }
            if (in_array('email', $allowed_network)) {
                $mailto_content = "?Subject=".urlencode($banner_data['arm_title']);
                $mailto_content .= "&body=".$url;
                $content .= "<div class='arm_aff_share arm_email'><a href='mailto:{$mailto_content}' ><i class='armfa armfa-envelope-o'> </i></a></div>";
            }
            return $content;
        }
        
        function arm_aff_shortcode_content($atts, $content, $tag) {
            global $arm_affiliate_settings, $arm_global_settings, $arm_aff_referrals,$arm_affiliate,$wpdb;

            $logged_in_user = get_current_user_id();
           
            if(isset($logged_in_user) && $logged_in_user != '' && $arm_aff_referrals->arm_check_is_allowed_user($logged_in_user)){
                $args = shortcode_atts(array(
                    'affiliate_text' => 'Your referral URL is: {URL}',
                    'social_fields' => 'facebook,twiiter,linkedin',
                ), $atts, $tag);

                $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
                $referral_var = $affiliate_options['arm_aff_referral_var'];
                $default_referral_url = (isset($affiliate_options['arm_aff_referral_url']) && $affiliate_options['arm_aff_referral_url'] != '') ? $affiliate_options['arm_aff_referral_url'] : get_home_url() ;
                $arm_aff_id_encoding = isset($affiliate_options['arm_aff_id_encoding']) ? $affiliate_options['arm_aff_id_encoding'] : 0;

                $armaff_is_active_fancy_url = isset($affiliate_options['arm_aff_allow_fancy_url']) ? $affiliate_options['arm_aff_allow_fancy_url'] : 0;

                $arm_affiliate_id = $this->armaff_get_affiliate_id_by_user( $logged_in_user );

                $arm_affiliate_id = $arm_affiliate_settings->arm_affiliate_get_user_id($arm_aff_id_encoding, $arm_affiliate_id);
                
                $arm_referral_url = '';

                if($armaff_is_active_fancy_url){
                    $armaff_url= parse_url( $default_referral_url );
                    $armaff_query_string = array_key_exists( 'query', $armaff_url ) ? '?' . $armaff_url['query'] : '';
                    $armaff_url_scheme      = isset( $armaff_url['scheme'] ) ? $armaff_url['scheme'] : 'http';
                    $armaff_url_host        = isset( $armaff_url['host'] ) ? $armaff_url['host'] : '';
                    $armaff_url_path        = isset( $armaff_url['path'] ) ? $armaff_url['path'] : '';
                    
                    $armaff_constructed_url = $armaff_url_scheme . '://' . $armaff_url_host . $armaff_url_path;
                    $armaff_base_url = $armaff_constructed_url;

                    $arm_referral_url = trailingslashit( $armaff_base_url ) . trailingslashit($referral_var) . trailingslashit( $arm_affiliate_id ) . $armaff_query_string;
                } else {
                    $arm_referral_url = $arm_global_settings->add_query_arg($referral_var, $arm_affiliate_id, $default_referral_url);
                }

                $shared_url = urlencode($arm_referral_url);

                $affiliate_text = str_replace('{URL}', $arm_referral_url, $args['affiliate_text']);
                $affiliate_text = str_replace('{url}', $arm_referral_url, $affiliate_text);

                $content = '<p>'.$affiliate_text.'</p>';

                $content .= $arm_affiliate_settings->share_affiliate_link($shared_url, $args['social_fields']);

                $content .= '<div class="armclear"></div>';

                return $content;
            }
        }
        
        function arm_aff_user_referral($atts, $content, $tag) {
            global $ARMember, $arm_affiliate_settings, $arm_payment_gateways, $arm_global_settings, $arm_aff_referrals, $arm_affiliate, $arm_subscription_plans, $wpdb;

            $arm_check_is_gutenberg_page = $ARMember->arm_check_is_gutenberg_page();
            if($arm_check_is_gutenberg_page)
            {
                return;
            }

            $logged_in_user = get_current_user_id();

            $armaff_id = $this->armaff_get_affiliate_id_by_user( $logged_in_user );

            if(isset($logged_in_user) && $logged_in_user != '' && $arm_aff_referrals->arm_check_is_allowed_user($logged_in_user)){
                $default_referral_fields = __('No.', 'ARM_AFFILIATE') . ',' . __('User Name', 'ARM_AFFILIATE') . ',' . __('Amount', 'ARM_AFFILIATE') . ',' . __('Plan', 'ARM_AFFILIATE') . ',' . __('Date', 'ARM_AFFILIATE') . ',' . __('Status', 'ARM_AFFILIATE');
                
                $args = shortcode_atts(array(
                    'title' => '',
                    'per_page' => 10,
                    'current_page' => 0,
                    'message_no_record' => __('There is no any referral found', 'ARM_AFFILIATE'),
                    'label' => 'arm_no,arm_ref_affiliate_id,arm_amount,arm_plan_id,arm_date_time,arm_status',
                    'value' => $default_referral_fields,
                ), $atts, $tag);
                
                $labels = explode(',', rtrim($args['label'], ','));
                $values = explode(',', rtrim($args['value'], ','));
                $per_page = $args['per_page'];
                $current_page = $args['current_page'];
                $title = $args['title'];
                
                $currency = $arm_payment_gateways->arm_get_global_currency();
                $date_format = $arm_global_settings->arm_get_wp_date_format();
                
                $offset = (!empty($current_page) && $current_page > 1) ? (($current_page - 1) * $per_page) : 0;
                $user_referrals = $arm_aff_referrals->get_user_referrals($armaff_id);
                $referrals_count = count($user_referrals);
                $user_referrals = array_slice($user_referrals, $offset, $per_page);
                
                $has_no = true;
                $has_ref_affiliate_id = true;
                $has_amount = true;
                $has_plan = true;
                $has_date = true;
                $has_status = true;
                
                if (in_array('arm_no', $labels)) {
                    $label_key = array_search('arm_no', $labels);
                    $l_no = !empty($values[$label_key]) ? $values[$label_key] : __('No.', 'ARM_AFFILIATE');
                } else {
                    $has_no = false;
                }
                
                if (in_array('arm_ref_affiliate_id', $labels)) {
                    $label_key = array_search('arm_ref_affiliate_id', $labels);
                    $l_referralID = !empty($values[$label_key]) ? $values[$label_key] : __('No.', 'ARM_AFFILIATE');
                } else {
                    $has_ref_affiliate_id = false;
                }
                if (in_array('arm_amount', $labels)) {
                    $label_key = array_search('arm_amount', $labels);
                    $l_amount = !empty($values[$label_key]) ? $values[$label_key] : __('Amount', 'ARM_AFFILIATE');
                } else {
                    $has_amount = false;
                }
                if (in_array('arm_plan_id', $labels)) {
                    $label_key = array_search('arm_plan_id', $labels);
                    $l_plan = !empty($values[$label_key]) ? $values[$label_key] : __('Plan', 'ARM_AFFILIATE');
                } else {
                    $has_plan = false;
                }
                if (in_array('arm_date_time', $labels)) {
                    $label_key = array_search('arm_date_time', $labels);
                    $l_date = !empty($values[$label_key]) ? $values[$label_key] : __('Date', 'ARM_AFFILIATE');
                } else {
                    $has_date = false;
                }
                if (in_array('arm_status', $labels)) {
                    $label_key = array_search('arm_status', $labels);
                    $l_status = !empty($values[$label_key]) ? $values[$label_key] : __('Status', 'ARM_AFFILIATE');
                } else {
                    $has_status = false;
                }    
                
                $frontfontstyle = $arm_global_settings->arm_get_front_font_style();
                $content .=!empty($frontfontstyle['google_font_url']) ? '<link id="google-font" rel="stylesheet" type="text/css" href="' . $frontfontstyle['google_font_url'] . '" />' : '';
                $content .= '<style type="text/css">';
                $transactionsWrapperClass = ".arm_referral_container";
                $content .= "
						$transactionsWrapperClass .arm_referral_heading_main{
							{$frontfontstyle['frontOptions']['level_1_font']['font']}
						}
						$transactionsWrapperClass .arm_referral_list_header th{
							{$frontfontstyle['frontOptions']['level_2_font']['font']}
						}
						$transactionsWrapperClass .arm_referral_list_item td{
							{$frontfontstyle['frontOptions']['level_3_font']['font']}
						}";
                $content .= '</style>';
                
                $content .= '<div class="arm_referral_container">';
                if (!empty($title)) {
                    $content .= '<div class="arm_referral_heading_main">' . $title . '</div>';
                    $content .= '<div class="armclear"></div>';
                }
                $content .= '<form method="POST" class="arm_referral_form_container ">';
                $content .= '<div class="arm_template_loading" style="display: none;"><img src="' . ARM_AFFILIATE_IMAGES_URL . '/loader.gif" alt="Loading.."></div>';
                $content .= "<div class='arm_transactions_wrapper'>";
                if (is_rtl()) {
                    $is_transaction_class_rtl = 'is_transaction_class_rtl';
                } else {
                    $is_transaction_class_rtl = '';
                }
                $content .= "<div class='arm_transaction_content " . $is_transaction_class_rtl . "'>";
                $content .= "<table class='arm_user_transaction_list_table arm_front_grid' cellpadding='0' cellspacing='0' border='0'>";
                $content .= "<thead>";
                $content .= "<tr class='arm_referral_list_header'>";
                $i = 0;
                if($has_no){
                    $i++;
                    $content .= "<th class='arm_sortable_th'> {$l_no} </th>";
                }
                if($has_ref_affiliate_id){
                    $i++;
                    $content .= "<th class='arm_sortable_th'> {$l_referralID} </th>";
                }
                if($has_amount){
                    $i++;
                    $content .= "<th class='arm_sortable_th'> {$l_amount} </th>";
                }
                if($has_plan){
                    $i++;
                    $content .= "<th class='arm_sortable_th'> {$l_plan} </th>";
                }
                if($has_date){
                    $i++;
                    $content .= "<th class='arm_sortable_th'> {$l_date} </th>";
                }
                if($has_status){
                    $i++;
                    $content .= "<th class='arm_sortable_th'> {$l_status} </th>";
                }
                
                $content .= "</tr>";
                $content .= "</thead>";
                if(count($user_referrals) > 0)
                { 
                    $arm_count = $offset;
                    foreach ($user_referrals as $referral)
                    { 
                        $arm_count++;
                        $arm_affiliate_user_name = $arm_affiliate->get_user_login($referral->arm_ref_affiliate_id);
                        //$arm_amount = $arm_payment_gateways->arm_prepare_amount($referral->arm_currency, $referral->arm_amount);
                        $arm_amount = $arm_payment_gateways->arm_prepare_amount($currency, $referral->arm_amount);
                        $arm_plan_name = $arm_subscription_plans->arm_get_plan_name_by_id($referral->arm_plan_id);
                        $arm_plan_name = (!empty($arm_plan_name)) ? $arm_plan_name : '-';
                        $arm_date_time = date( $date_format, strtotime( $referral->arm_date_time ) );
                        $arm_status = $arm_aff_referrals->referral_status[$referral->arm_status];

                        $content .= "<tr class='arm_referral_list_item'> ";
                        if($has_no){
                            $content .= "<td data-label='{$l_no}'> {$arm_count} </td>";
                        }
                        if($has_ref_affiliate_id){
                            $content .= "<td data-label='{$l_referralID}'> {$arm_affiliate_user_name->user_login} </td>";
                        }
                        if($has_amount){
                            $content .= "<td data-label='{$l_amount}'> {$arm_amount} </td>";
                        }
                        if($has_plan){
                            $content .= "<td data-label='{$l_plan}'> {$arm_plan_name} </td>";
                        }
                        if($has_date){
                            $content .= "<td data-label='{$l_date}'> {$arm_date_time} </td>";
                        }
                        if($has_status){
                            $content .= "<td data-label='{$l_status}'><span class='arm_".$arm_status."' > {$arm_status} </span></td>";
                        }
                        $content .= "</tr>";
                    } 
                }
                else
                {
                    $content .="<tr class='arm_transaction_list_item'>";
                    $content .="<td colspan='" . $i . "' class='arm_no_referrals'>{$args['message_no_record']}</td>";
                    $content .="</tr>";
                }

                $content .= "</table>";
                $content .= "</div>";
                $content .= "</div>";
                if(count($user_referrals) > 0)
                {
                    $transPaging = $arm_global_settings->arm_get_paging_links($current_page, $referrals_count, $per_page);
                    $content .= "<div class='arm_referral_paging_container'>$transPaging</div>";
                }
              
                foreach (array('title', 'per_page', 'message_no_record', 'label', 'value') as $k) {
                    $content .= '<input type="hidden" class="arm_trans_field_' . $k . '" name="' . $k . '" value="' . $args[$k] . '">';
                }
                
                $content .= "</form>";
                $content .= "</div>";
                echo $content;
            }
        }
        
        function arm_referral_paging_action() {
            global $wpdb, $ARMember, $arm_global_settings, $arm_members_directory, $arm_members_class;

            if (isset($_POST['action']) && $_POST['action'] == 'arm_referral_paging_action') {
                unset($_POST['action']);
                if (!empty($_POST)) {
                    $shortcode_param = '';
                    foreach ($_POST as $k => $v) {
                        $shortcode_param .= $k . '="' . $v . '" ';
                    }
                    echo do_shortcode("[arm_user_referral $shortcode_param]");
                    exit;
                }
            }
        }

        function arm_user_payout_transaction($atts, $content, $tag) {
            global $ARMember, $arm_affiliate_settings, $arm_payment_gateways, $arm_global_settings, $arm_aff_referrals, $arm_affiliate, $arm_subscription_plans, $arm_aff_payouts, $wpdb;

            $arm_check_is_gutenberg_page = $ARMember->arm_check_is_gutenberg_page();
            if($arm_check_is_gutenberg_page)
            {
                return;
            }

            $logged_in_user = get_current_user_id();
            if(isset($logged_in_user) && $logged_in_user != '' && $arm_aff_referrals->arm_check_is_allowed_user($logged_in_user)){
                $default_referral_fields = __('Tr No', 'ARM_AFFILIATE') . ',' . __('Amount', 'ARM_AFFILIATE') . ',' . __('Payout Date', 'ARM_AFFILIATE') . ',' . __('Balance', 'ARM_AFFILIATE');

                $armaff_id = $this->armaff_get_affiliate_id_by_user( $logged_in_user );

                $args = shortcode_atts(array(
                    'title' => '',
                    'per_page' => 10,
                    'current_page' => 0,
                    'message_no_record' => __('There is no any payout found', 'ARM_AFFILIATE'),
                    'label' => 'arm_tr_no,arm_amount,arm_date_time,arm_balance',
                    'value' => $default_referral_fields,
                ), $atts, $tag);
                
                $labels = explode(',', rtrim($args['label'], ','));
                $values = explode(',', rtrim($args['value'], ','));
                $per_page = $args['per_page'];
                $current_page = $args['current_page'];
                $title = $args['title'];
                
                $currency = $arm_payment_gateways->arm_get_global_currency();
                $date_format = $arm_global_settings->arm_get_wp_date_format();
                
                $offset = (!empty($current_page) && $current_page > 1) ? (($current_page - 1) * $per_page) : 0;
                $user_payouts = $arm_aff_payouts->arm_affiliate_payment_history($armaff_id);
                $payout_count = count($user_payouts);
                $user_payout = array_slice($user_payouts, $offset, $per_page);
                
                $has_ref_affiliate_id = true;
                $has_amount = true;
                $has_date = true;
                $has_status = true;
                $has_balance = true;

                if (in_array('arm_tr_no', $labels)) {
                    $label_key = array_search('arm_tr_no', $labels);
                    $l_referralID = !empty($values[$label_key]) ? $values[$label_key] : __('Payout No.', 'ARM_AFFILIATE');
                } else {
                    $has_ref_affiliate_id = false;
                }
                if (in_array('arm_amount', $labels)) {
                    $label_key = array_search('arm_amount', $labels);
                    $l_amount = !empty($values[$label_key]) ? $values[$label_key] : __('Amount', 'ARM_AFFILIATE');
                } else {
                    $has_amount = false;
                }
                if (in_array('arm_date_time', $labels)) {
                    $label_key = array_search('arm_date_time', $labels);
                    $l_date = !empty($values[$label_key]) ? $values[$label_key] : __('Date', 'ARM_AFFILIATE');
                } else {
                    $has_date = false;
                }
                if (in_array('arm_balance', $labels)) {
                    $label_key = array_search('arm_balance', $labels);
                    $l_balance = !empty($values[$label_key]) ? $values[$label_key] : __('Balance', 'ARM_AFFILIATE');
                } else {
                    $has_balance = false;
                }
                
                $frontfontstyle = $arm_global_settings->arm_get_front_font_style();
                $content .=!empty($frontfontstyle['google_font_url']) ? '<link id="google-font" rel="stylesheet" type="text/css" href="' . $frontfontstyle['google_font_url'] . '" />' : '';
                $content .= '<style type="text/css">';
                $transactionsWrapperClass = ".arm_referral_container";
                $content .= "
						$transactionsWrapperClass .arm_referral_heading_main{
							{$frontfontstyle['frontOptions']['level_1_font']['font']}
						}
						$transactionsWrapperClass .arm_referral_list_header th{
							{$frontfontstyle['frontOptions']['level_2_font']['font']}
						}
						$transactionsWrapperClass .arm_referral_list_item td{
							{$frontfontstyle['frontOptions']['level_3_font']['font']}
						}";
                $content .= '</style>';
                
                $content .= '<div class="arm_payout_container '.$payout_count.'">';
                if (!empty($title)) {
                    $content .= '<div class="arm_referral_heading_main">' . $title . '</div>';
                    $content .= '<div class="armclear"></div>';
                }
                $content .= '<form method="POST" class="arm_payout_form_container ">';
                $content .= '<div class="arm_template_loading" style="display: none;"><img src="' . ARM_AFFILIATE_IMAGES_URL . '/loader.gif" alt="Loading.."></div>';
                $content .= "<div class='arm_transactions_wrapper'>";
                if (is_rtl()) {
                    $is_transaction_class_rtl = 'is_transaction_class_rtl';
                } else {
                    $is_transaction_class_rtl = '';
                }
                $content .= "<div class='arm_transaction_content " . $is_transaction_class_rtl . "'>";
                $content .= "<table class='arm_user_transaction_list_table arm_front_grid' cellpadding='0' cellspacing='0' border='0'>";
                $content .= "<thead>";
                $content .= "<tr class='arm_referral_list_header'>";
                $i = 0;
                if($has_ref_affiliate_id){
                    $i++;
                    $content .= "<th class='arm_sortable_th'> {$l_referralID} </th>";
                }
                if($has_amount){
                    $i++;
                    $content .= "<th class='arm_sortable_th'> {$l_amount} </th>";
                }
                if($has_date){
                    $i++;
                    $content .= "<th class='arm_sortable_th'> {$l_date} </th>";
                }
                if($has_balance){
                    $i++;
                    $content .= "<th class='arm_sortable_th'> {$l_balance} </th>";
                }
                
                $content .= "</tr>";
                $content .= "</thead>";
                if(count($user_payout) > 0)
                { 
                    $row_no = 0;
                    foreach ($user_payout as $payout)
                    {
                        $row_no ++;
                       
                        //$arm_amount = $arm_payment_gateways->arm_prepare_amount($payout->arm_currency, $payout->arm_amount);
                        $arm_amount = $arm_payment_gateways->arm_prepare_amount($currency, $payout['arm_amount']);
                        $arm_date_time = date( $date_format, strtotime( $payout['arm_date_time'] ) );
                        $arm_remaining_balance = ($payout['arm_remaining_balance'] > 0) ? $payout['arm_remaining_balance'] : '0.00';
                        
                        $content .= "<tr class='arm_referral_list_item'> ";
                        if($has_ref_affiliate_id){
                            $content .= "<td data-label='{$l_referralID}'> {$payout['arm_payout_id']} </td>";
                        }
                        if($has_amount){
                            $content .= "<td data-label='{$l_amount}'> {$arm_amount} </td>";
                        }
                        if($has_date){
                            $content .= "<td data-label='{$l_date}'> {$arm_date_time} </td>";
                        }
                        if($has_balance){
                            $content .= "<td data-label='{$l_balance}'> {$arm_remaining_balance} </td>";
                        }
                        $content .= "</tr>";
                    } 
                }
                else
                {
                    $content .="<tr class='arm_transaction_list_item'>";
                    $content .="<td colspan='" . $i . "' class='arm_no_referrals'>{$args['message_no_record']}</td>";
                    $content .="</tr>";
                }

                $content .= "</table>";
                $content .= "</div>";
                $content .= "</div>";
                if(count($user_payout) > 0)
                {
                    $transPaging = $arm_global_settings->arm_get_paging_links($current_page, $payout_count, $per_page);
                    $content .= "<div class='arm_payout_paging_container'>$transPaging</div>";
                }
              
                foreach (array('title', 'per_page', 'message_no_record', 'label', 'value') as $k) {
                    $content .= '<input type="hidden" class="arm_trans_field_' . $k . '" name="' . $k . '" value="' . $args[$k] . '">';
                }
                
                $content .= "</form>";
                $content .= "</div>";
                echo $content;
            }
        }
        
        function arm_payout_paging_action() {
            global $wpdb, $ARMember, $arm_global_settings, $arm_members_directory, $arm_members_class;
            if (isset($_POST['action']) && $_POST['action'] == 'arm_payout_paging_action') {
                unset($_POST['action']);
                if (!empty($_POST)) {
                    $shortcode_param = '';
                    foreach ($_POST as $k => $v) {
                        $shortcode_param .= $k . '="' . $v . '" ';
                    }
                    echo do_shortcode("[arm_user_payout_transaction $shortcode_param]");
                    exit;
                }
            }
        }

        function armaff_is_affiliate( $armaff_user_id = 0) {

            global $wpdb, $arm_affiliate;

            if ( !is_user_logged_in() && empty( $armaff_user_id ) ) {
                return false;
            }

            if ( empty( $armaff_user_id ) ) {
                $armaff_user_id = get_current_user_id();
            }

            $armaff_get_affiliate = $wpdb->get_row('SELECT arm_affiliate_id FROM '.$arm_affiliate->tbl_arm_aff_affiliates.' WHERE arm_user_id = '.$armaff_user_id);

            if($armaff_get_affiliate){
                return $armaff_get_affiliate->arm_affiliate_id;
            } else {
                return false;
            }

        }

        function armaff_get_affiliate_detail( $armaff_user_affiliate = 0 ){

            if ( !is_user_logged_in() || empty( $armaff_user_affiliate ) ) {
                return false;
                // return $this->armaff_get_affiliate_register_form();
            }

            global $wpdb, $arm_affiliate, $arm_global_settings, $arm_affiliate_settings;

            $content = '<div class="armaff_affiliate_detail_container">';
                $content .= '<div class="armaff_form_heading_container">';
                $content .= '<span class="armaff_form_field_label">'.__("Affiliate Account", 'ARM_AFFILIATE').'</span>';
                $content .= '</div>';

                $content .= '<div class="armaff_affiliate_info_wrapper">';

                    $content .= '<div class="armaff_affiliate_info_row">';
                        $content .= '<label class="armaff_affiliate_detail_label">'. __( "Affiliate ID", 'ARM_AFFILIATE') .'</label>';
                        $content .= '<div class="armaff_affiliate_detail_value">'.$armaff_user_affiliate.'</div>';
                    $content .= '</div>';

                    $content .= '<div class="armaff_affiliate_info_row">';
                        $content .= '<label class="armaff_affiliate_detail_label">'. __( "Affiliate Referral URL", 'ARM_AFFILIATE') .'</label>';
                        $content .=  '<div class="armaff_affiliate_detail_value"> {RFL_URL} </div>';
                    $content .= '</div>';

                    $content .= '{ARMAFF_WEBSITE}';
                    $content .= '{ARMAFF_WEBSITE_DESC}';

                $content .= '</div>';
            $content .= '</div>';

            $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
            $armaff_referral_var = $affiliate_options['arm_aff_referral_var'];
            $armaff_referral_url = (isset($affiliate_options['arm_aff_referral_url']) && $affiliate_options['arm_aff_referral_url'] != '') ? $affiliate_options['arm_aff_referral_url'] : get_home_url() ;
            $armaff_is_encoding = isset($affiliate_options['arm_aff_id_encoding']) ? $affiliate_options['arm_aff_id_encoding'] : 0;

            $armaff_user_affiliate = $arm_affiliate_settings->arm_affiliate_get_user_id($armaff_is_encoding, $armaff_user_affiliate);

            $armaff_is_active_fancy_url = isset($affiliate_options['arm_aff_allow_fancy_url']) ? $affiliate_options['arm_aff_allow_fancy_url'] : 0;

            if($armaff_is_active_fancy_url){
                $armaff_url= parse_url( $armaff_referral_url );
                $armaff_query_string = array_key_exists( 'query', $armaff_url ) ? '?' . $armaff_url['query'] : '';
                $armaff_url_scheme      = isset( $armaff_url['scheme'] ) ? $armaff_url['scheme'] : 'http';
                $armaff_url_host        = isset( $armaff_url['host'] ) ? $armaff_url['host'] : '';
                $armaff_constructed_url = $armaff_url_scheme . '://' . $armaff_url_host . $armaff_url['path'];
                $armaff_base_url = $armaff_constructed_url;

                $armaff_referral_url = trailingslashit( $armaff_base_url ) . trailingslashit($armaff_referral_var) . trailingslashit( $armaff_user_affiliate ) . $armaff_query_string;
            } else {
                $armaff_referral_url = $arm_global_settings->add_query_arg($armaff_referral_var, $armaff_user_affiliate, $armaff_referral_url);
            }

            $content = str_replace('{RFL_URL}', $armaff_referral_url, $content);


            $armaff_affiliate = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$arm_affiliate->tbl_arm_aff_affiliates} WHERE `arm_user_id` = '%d' LIMIT 1;", $armaff_user_affiliate ) );

            if(isset($armaff_affiliate->arm_affiliate_site) && $armaff_affiliate->arm_affiliate_site != ''){

                $affsite_content = '<div class="armaff_affiliate_info_row">';
                    $affsite_content .= '<label class="armaff_affiliate_detail_label">'. __( "Website", 'ARM_AFFILIATE') .'</label>';
                    $affsite_content .=  '<div class="armaff_affiliate_detail_value"> '.$armaff_affiliate->arm_affiliate_site.' </div>';
                $affsite_content .= '</div>';

                $content = str_replace('{ARMAFF_WEBSITE}', $affsite_content, $content);

                if($armaff_affiliate->arm_affiliate_site_desc != ''){
                    $affsitedesc_content = '<div class="armaff_affiliate_info_row">';
                        $affsitedesc_content .= '<label class="armaff_affiliate_detail_label">'. __( "About Your Website", 'ARM_AFFILIATE') .'</label>';
                        $affsitedesc_content .=  '<div class="armaff_affiliate_detail_value"> '.stripslashes($armaff_affiliate->arm_affiliate_site_desc).' </div>';
                    $affsitedesc_content .= '</div>';

                    $content = str_replace('{ARMAFF_WEBSITE_DESC}', $affsitedesc_content, $content);
                } else {
                    $content = str_replace('{ARMAFF_WEBSITE_DESC}', '', $content);
                }

            } else {
                $content = str_replace('{ARMAFF_WEBSITE}', '', $content);
                $content = str_replace('{ARMAFF_WEBSITE_DESC}', '', $content);
            }


            return $content;

        }

        function armaff_get_loggedin_user_form( $armaff_user_id = 0 ){

            if ( !is_user_logged_in() && empty( $armaff_user_id ) ) {
                return false;
            }

            if ( empty( $armaff_user_id ) ) {
                $armaff_user_id = get_current_user_id();
            }

            global $wpdb, $arm_affiliate, $arm_affiliate_version;

            $armaff_slug = 'create_user_affiliate';
            $armaff_affiliate_form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$arm_affiliate->tbl_arm_aff_forms} WHERE `arm_form_slug` = '%s' LIMIT 1;", $armaff_slug ) );

            $armaff_formid = $armaff_affiliate_form->arm_form_id;
            $armaff_formtitle = $armaff_affiliate_form->arm_form_title;
            $armaff_formstyle = ($armaff_affiliate_form->arm_form_style != '') ? $armaff_affiliate_form->arm_form_style : 'material';
            $armaff_formfields = $armaff_affiliate_form->arm_form_fields;
            $armaff_formfields = ($armaff_formfields != '') ? json_decode($armaff_formfields) : '';

            $armaff_form_classes = ' armf_alignment_left armf_button_position_center';

            if($armaff_formstyle == 'material'){
                $armaff_form_classes .= ' arm_form_layout_writer';
            } else {
                $armaff_form_classes .= ' arm_form_layout_iconic armf_layout_block';
            }

            $armaff_form_attr = ' name="arm_form" id="arm_form' . $armaff_slug . '" armaff-form-style="'.$armaff_formstyle.'"';
            $armaff_form_attr .= ' data-ng-controller="ARMCtrlaff" data-ng-cloak="" data-ng-id="' . $armaff_formid . '"';

            $armaff_form_attr .= ' data-ng-submit="armaffFormSubmit(arm_form.$valid, \'arm_form' . $armaff_slug . '\', $event);" onsubmit="return false;"';

            $content = '';

            $content .= '<div class="arm_member_form_container arm_affiliate_form_container arm_affiliate_'.$armaff_formstyle.'_form">';

            $content .= '<div class="arm_form_message_container armaff_message_container armaff_form_' . $armaff_slug . '"></div>';
            $content .= '<div class="armclear"></div>';

            $content .= '<form method="post" class="arm_form armaff_form arm_shortcode_form armaff_form_' . $armaff_slug . ' '.$armaff_form_classes.'" armaff-form-slug="'.$armaff_slug.'" enctype="multipart/form-data" novalidate '.$armaff_form_attr.'>';

            $content .= '<div class="arm_form_inner_container arm_msg_pos_bottom">';

            $content .= '<div class="arm_form_wrapper_container arm_form_wrapper_container_' . $armaff_slug . ' arm_field_position_center" data-form_id="register_affiliate">';

                $content .= '<div class="arm_form_heading_container arm_add_other_style armaligncenter">';
                $content .= '<span class="arm_form_field_label_wrapper_text">' . $armaff_formtitle . '</span>';
                $content .= '</div>';

                $content .= $this->armaff_get_register_affiliate_form_fields($armaff_slug, $armaff_formfields, $armaff_formstyle);

            $content .= '</div>';

            $content .= '</div>';

            $content .= '</form>';

            $content .= '</div>';

            return $content;
        }

        function armaff_get_affiliate_register_form(){

            global $wpdb, $arm_affiliate, $arm_affiliate_version;

            $armaff_slug = 'register_affiliate';
            $armaff_affiliate_form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$arm_affiliate->tbl_arm_aff_forms} WHERE `arm_form_slug` = '%s' LIMIT 1;", $armaff_slug ) );

            $armaff_formid = $armaff_affiliate_form->arm_form_id;
            $armaff_formtitle = $armaff_affiliate_form->arm_form_title;
            $armaff_formstyle = ($armaff_affiliate_form->arm_form_style != '') ? $armaff_affiliate_form->arm_form_style : 'material';
            $armaff_formfields = $armaff_affiliate_form->arm_form_fields;
            $armaff_formfields = ($armaff_formfields != '') ? json_decode($armaff_formfields) : '';

            $armaff_form_classes = ' armf_alignment_left armf_button_position_center';

            if($armaff_formstyle == 'material'){
                $armaff_form_classes .= ' arm_form_layout_writer';
            } else {
                $armaff_form_classes .= ' arm_form_layout_iconic armf_layout_block';
            }

            $armaff_form_attr = ' name="arm_form" id="arm_form' . $armaff_slug . '" armaff-form-style="'.$armaff_formstyle.'"';
            $armaff_form_attr .= ' data-ng-controller="ARMCtrlaff" data-ng-cloak="" data-ng-id="' . $armaff_formid . '"';

            $armaff_form_attr .= ' data-ng-submit="armaffFormSubmit(arm_form.$valid, \'arm_form' . $armaff_slug . '\', $event);" onsubmit="return false;"';

            $content = '';

            $content .= '<div class="arm_member_form_container arm_affiliate_form_container arm_affiliate_'.$armaff_formstyle.'_form">';

            $content .= '<div class="arm_form_message_container armaff_message_container armaff_form_' . $armaff_slug . '"></div>';
            $content .= '<div class="armclear"></div>';

            $content .= '<form method="post" class="arm_form armaff_form arm_shortcode_form armaff_form_' . $armaff_slug . ' '.$armaff_form_classes.'" armaff-form-slug="'.$armaff_slug.'" enctype="multipart/form-data" novalidate '.$armaff_form_attr.'>';

            $content .= '<div class="arm_form_inner_container arm_msg_pos_bottom">';

            $content .= '<div class="arm_form_wrapper_container arm_form_wrapper_container_' . $armaff_slug . ' arm_field_position_center" data-form_id="register_affiliate">';

                $content .= '<div class="arm_form_heading_container arm_add_other_style armaligncenter">';
                $content .= '<span class="arm_form_field_label_wrapper_text">' . $armaff_formtitle . '</span>';
                $content .= '</div>';

                $content .= $this->armaff_get_register_affiliate_form_fields($armaff_slug, $armaff_formfields, $armaff_formstyle);

            $content .= '</div>';

            $content .= '</div>';

            $content .= '</form>';

            $content .= '</div>';

            return $content;
        }

        function arm_affiliate_register($atts, $content, $tag){



            global $ARMember;
            $arm_check_is_gutenberg_page = $ARMember->arm_check_is_gutenberg_page();
            if($arm_check_is_gutenberg_page)
            {
                return;
            }


            $armaff_user_affiliate = 0;
            if ( is_user_logged_in() ){
                $armaff_user_id = get_current_user_id();
                $armaff_user_affiliate = $this->armaff_is_affiliate( $armaff_user_id );
            }

            if ( is_user_logged_in() && $armaff_user_affiliate > 0 ) {

                return $this->armaff_get_affiliate_detail( $armaff_user_affiliate );

            } else if ( is_user_logged_in() && (empty($armaff_user_affiliate) || $armaff_user_affiliate <= 0) ) {

                return $this->armaff_get_loggedin_user_form( $armaff_user_id );

            } else {

                return false;
                // return $this->armaff_get_affiliate_register_form();

            }

        }

        function armaff_get_register_affiliate_form_fields($armaff_slug = '', $armaff_formfields = '', $armaff_formstyle = 'material'){

            $armaff_field_content = '';

            if($armaff_formfields == ''){
                return $armaff_field_content;
            }


            $armaff_affiliate_details = array();

            if(is_user_logged_in()){

                $armaff_user_id = get_current_user_id();

                $armaff_userinfo = get_userdata( $armaff_user_id );
                $armaff_affiliate_details['affiliate_uname'] = $armaff_userinfo->user_login;
                $armaff_affiliate_details['affiliate_email'] = $armaff_userinfo->user_email;
                $armaff_affiliate_details['affiliate_website'] = $armaff_userinfo->user_url;
            }

            foreach ($armaff_formfields as $field_slug => $field_options) {

                $armaff_fieldtype = $armaff_inputtype = $field_options->type;

                $armaff_fieldrequired = $field_options->required;

                $armaff_field_content .= '<div class="arm_form_field_container arm_form_field_container_' . $armaff_fieldtype . '" id="arm_form_field_container_' . $field_slug . '">';
                $armaff_field_content .= '<div class="arm_form_label_wrapper arm_form_field_label_wrapper arm_form_member_field_' . $armaff_fieldtype . '">';

                if(!in_array($armaff_fieldtype, array('submit', 'hidden'))){

                        $armaff_field_content .= '<div class="arm_member_form_field_label">';

                            if ($armaff_fieldrequired == 1) {
                                $armaff_field_content .= '<span class="required_tag required_tag_' . $field_slug . '">*</span>';
                            }
                            $armaff_field_content .= '<div class="arm_form_field_label_text">';
                            $armaff_field_content .= $field_options->label;
                            $armaff_field_content .= '</div>';

                        $armaff_field_content .= '</div>';
                }

                $armaff_field_content .= '</div>';

                $armaff_field_content .= '<div class="arm_label_input_separator"></div>';

                $armaff_field_content .= '<div class="arm_form_input_wrapper">';
                    $armaff_field_content .= $this->armaff_get_register_affiliate_form_fields_by_type($field_slug, $field_options, $armaff_affiliate_details, $armaff_formstyle);
                    $armaff_field_content .= '<div class="armclear"></div>';
                $armaff_field_content .= '</div>';


                $armaff_field_content .= '</div>';

            }

            return $armaff_field_content;

        }

        function armaff_get_register_affiliate_form_fields_by_type($field_slug = '', $field_options = '', $armaff_affiliate_details = array(), $armaff_formstyle = 'material' ){

            global $wpdb, $arm_affiliate;

            $armaff_field_html = '';

            if($field_options == ''){
                return $armaff_field_html;
            }

            $armaff_class = 'arm_form_input_box arm_form_input_box_' . $field_slug;

            $armaff_display_label = (isset($field_options->display_label) && $field_options->display_label != '') ? $field_options->display_label : $field_options->label;

            $ng_model = 'data-ng-model="arm_form.' . esc_attr($field_options->name) . '_' . $field_slug . '"';
            $armaff_required = '';

            $armaff_field_html .= '<div class="arm_form_input_container_' . $field_options->type . ' arm_form_input_container" id="arm_form_input_container_' . $field_slug . '">';

            $armaff_input_label = '';

            if($armaff_formstyle == 'material'){

                $armaff_class .= ' arm_material_input';

                $armaff_input_label = $armaff_display_label;

            }

            $armaff_field_label = '<label class="arm_material_label"> ' . $armaff_input_label . '</label>';

            if( $field_options->required == 1) { 
                $armaff_field_label = '<label class="arm_material_label"> * ' . $armaff_input_label . '</label>';
                $armaff_required = ' required="required" ';
            }

            $armaff_input = $field_options->type;
            if( in_array( $field_options->type, array('email') )) {
                $armaff_input = 'text';
            }

            $armaff_field_attr = $ng_model . ' ' . $armaff_required;

            $armaff_validate_msgs = '';

            if( $field_options->required == 1 && !empty($field_options->invalid_message) ){
                $armaff_validate_msgs .= '<div data-ng-message="required" class="arm_error_msg"><div class="arm_error_box_arrow"></div>' . $field_options->invalid_message . '</div>';
            }

            $armaff_value = (isset($armaff_affiliate_details[$field_slug])) ? $armaff_affiliate_details[$field_slug] : '';


            switch ($field_options->type) {
                case 'text':
                case 'email':
                case 'url':
                    $armaff_field_attr .= ' data-ng-trim="false"';
                    if ($field_options->type == 'email') {
                        $armaff_field_attr .= ' data-ng-pattern="/^.+@.+\..+$/"';
                        $armaff_validate_msgs .= '<div data-ng-message-exp="[\'email\', \'pattern\']" class="arm_error_msg"><div class="arm_error_box_arrow"></div>' . $field_options->invalid_message . '</div>';
                    }

                    if ($field_options->type == 'url') {
                        $armaff_validate_msgs .= '<div data-ng-message="url" class="arm_error_msg"><div class="arm_error_box_arrow"></div>' . $field_options->invalid_message . '</div>';
                    }

                    $armaff_field_html .= '<md-input-container class="md-block" flex-gt-sm="">';
                    $armaff_field_html .= $armaff_field_label;
                    $armaff_field_html .= '<input name="' . $field_options->name . '" type="' . $armaff_input . '" value="'.$armaff_value.'" class="'.$armaff_class.'" ' . $armaff_field_attr . '>';

                    $armaff_field_html .= '<div data-ng-cloak data-ng-messages="arm_form.' . esc_attr($field_options->name) . '.$error" data-ng-show="arm_form.' . esc_attr($field_options->name) . '.$touched" class="arm_error_msg_box ng-cloak">';
                    $armaff_field_html .= $armaff_validate_msgs;
                    $armaff_field_html .= '</div>';

                    $armaff_field_html .= '</md-input-container>';
                    if ($field_options->type == 'email') {
                        $armaff_field_html .= '<input type="hidden" data-id="arm_compare_' . $field_slug . '" class="arm_compare_' . $field_slug . '" ng-model="arm_form.arm_compare_' . $field_slug . '" value="{{ arm_form.' . esc_attr($field_options->name) . '_' . $field_slug . ' }}">';
                    }

                    break;

                case 'textarea':
                    $armaffrows = '3';
                    $armaffcols = '40';
                    $armaff_field_html .= '<md-input-container class="md-block" flex-gt-sm="">';
                    $armaff_field_html .= $armaff_field_label;
                    $armaff_field_html .= '<textarea class="arm_textarea ' . $armaff_class . '" name="' . esc_attr($field_options->name) . '" rows="' . $armaffrows . '" cols="' . $armaffcols . '" ' . $armaff_field_attr . ' data-ng-init="arm_form.' . esc_attr($field_options->name) . '_' . $field_slug . '=\'' . esc_attr(addslashes($armaff_value)) . '\'">' . stripslashes($armaff_value) . '</textarea>';

                    $armaff_field_html .= '<div data-ng-cloak data-ng-messages="arm_form.' . esc_attr($field_options->name) . '.$error" data-ng-show="arm_form.' . esc_attr($field_options->name) . '.$touched" class="arm_error_msg_box ng-scope">';
                    $armaff_field_html .= $armaff_validate_msgs;
                    $armaff_field_html .= '</div>';

                    $armaff_field_html .= '</md-input-container>';

                    break;

                case 'password':
                    $armaff_field_html .= '<md-input-container class="md-block" flex-gt-sm="">';
                    $armaff_field_html .= $armaff_field_label;
                    $armaff_field_html .= '<input name="' . $field_options->name . '" type="password" autocomplete="off" value="" class="'.$armaff_class.'" ' . $armaff_field_attr . '>';

                    $armaff_field_html .= '<div data-ng-cloak data-ng-messages="arm_form.' . esc_attr($field_options->name) . '.$error" data-ng-show="arm_form.' . esc_attr($field_options->name) . '.$touched" class="arm_error_msg_box ng-scope">';
                    $armaff_field_html .= $armaff_validate_msgs;
                    $armaff_field_html .= '</div>';

                    $armaff_field_html .= '</md-input-container>';
                    if ($field_options->type == 'password') {
                        $armaff_field_html .= '<input type="hidden" data-id="arm_compare_' . $field_slug . '" class="arm_compare_' . $field_slug . '" ng-model="arm_form.arm_compare_' . $field_slug . '" value="{{ arm_form.' . esc_attr($field_options->name) . '_' . $field_slug . ' }}">';
                    }

                    break;


                case 'label':

                    $armaff_label_text = (isset($armaff_affiliate_details[$field_slug])) ? $armaff_affiliate_details[$field_slug] : '';

                    $armaff_field_html .= $armaff_field_label;
                    $armaff_field_html .= '<div class="armaff_affiliate_info_row">';
                    $armaff_field_html .=  $armaff_label_text;
                    $armaff_field_html .= '</div>';
                    $armaff_field_html .=  '<input type="hidden" name="' . $field_options->name . '" value="'.$armaff_label_text .'" />';

                    break;


                case 'submit':
                    $ngClick = 'ng-click="armaffiliateSubmitBtnClick($event)"';
                    if (current_user_can('administrator')) {
                        $ngClick = 'onclick="return false;"';
                    }
                    $submit_attr = ' type="submit"';
                    $submit_class = 'arm_btn_style_border arm_form_input_box_' . $field_slug;

                    $armaff_field_html .= '<md-button class="arm_form_field_submit_button arm_form_field_container_button ' . $submit_class . '" ' . $submit_attr . ' name="armFormSubmitBtn" ' . $ngClick . '><span class="arm_spinner">' . file_get_contents(MEMBERSHIP_IMAGES_DIR . "/loader.svg") . '</span>' . $armaff_display_label . '</md-button>';

                    break;

                default:
                    $armaff_field_html .= '';
                    break;
            }

            $armaff_field_html .= '</div>';

            return $armaff_field_html;

        }

        function armaff_get_affiliate_form_fields(){

            global $wpdb, $arm_affiliate;

            $armaff_slug = 'create_user_affiliate';
            $armaff_affiliate_form = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$arm_affiliate->tbl_arm_aff_forms} WHERE `arm_form_slug` = '%s' LIMIT 1;", $armaff_slug ) );

            $armaff_formfields = $armaff_affiliate_form->arm_form_fields;
            $armaff_formfields = ($armaff_formfields != '') ? json_decode($armaff_formfields, true) : '';

            unset($armaff_formfields['affiliate_uname']);
            unset($armaff_formfields['affiliate_email']);
            unset($armaff_formfields['submit']);

            $armaff_affiliate_custom_fields = array_keys($armaff_formfields);

            return $armaff_affiliate_custom_fields;

        }

        function armaff_get_affiliate_id_by_user($armaff_userid){

            global $wpdb, $arm_affiliate;

            $armaff_affiliate = $wpdb->get_row( $wpdb->prepare( "SELECT arm_affiliate_id FROM {$arm_affiliate->tbl_arm_aff_affiliates} WHERE `arm_user_id` = '%d' LIMIT 1;", $armaff_userid ) );

            if(isset($armaff_affiliate->arm_affiliate_id)){
                return $armaff_affiliate->arm_affiliate_id;
            }

            return 0;

        }

        function arm_if_affiliate_func($atts, $content, $tag) {

            global $ARMember;
            $arm_check_is_gutenberg_page = $ARMember->arm_check_is_gutenberg_page();
            if($arm_check_is_gutenberg_page)
            {
                return;
            }

            $armaff_content = $content;
            $armaff_no_content = NULL;

            if( $this->armaff_is_active_affiliate() ){
                return do_shortcode($armaff_content);
            }

            return do_shortcode($armaff_no_content);

        }

        function arm_if_non_affiliate_func($atts, $content, $tag) {

            global $ARMember;
            $arm_check_is_gutenberg_page = $ARMember->arm_check_is_gutenberg_page();
            if($arm_check_is_gutenberg_page)
            {
                return;
            }
            $armaff_content = $content;
            $armaff_no_content = NULL;

            if( !( $this->armaff_is_active_affiliate() ) ){
                return do_shortcode($armaff_content);
            }

            return do_shortcode($armaff_no_content);

        }

        function armaff_is_active_affiliate(){

            global $arm_affiliate, $wpdb;

            if (is_user_logged_in()) {
                $armaff_current_user_id = get_current_user_id();
                $armaff_affiliate = $wpdb->get_row( $wpdb->prepare( "SELECT arm_affiliate_id FROM {$arm_affiliate->tbl_arm_aff_affiliates} WHERE `arm_user_id` = '%d' AND `arm_status` = 1  LIMIT 1;", $armaff_current_user_id ) );
                if( isset($armaff_affiliate->arm_affiliate_id) && $armaff_affiliate->arm_affiliate_id > 0 ){
                    return true;
                } else {
                    return false;
                }
            }

            return false;
        }

        function arm_aff_visits_func($atts, $content, $tag){

            global $ARMember;
            $arm_check_is_gutenberg_page = $ARMember->arm_check_is_gutenberg_page();
            if($arm_check_is_gutenberg_page)
            {
                return;
            }

            $is_user_logged_in = is_user_logged_in();
            if ( $is_user_logged_in && current_user_can('administrator')) {
                return false;
            }

            $args = shortcode_atts(array(
                                'duration' => '',
                    ), $atts, $tag);

            if ($is_user_logged_in) {

                global $wpdb, $arm_affiliate;

                $armaff_current_user_id = get_current_user_id();
                $arm_current_affiliate_id = $this->armaff_get_affiliate_id_by_user( $armaff_current_user_id );

                if(!empty($arm_current_affiliate_id))
                {
                    $where_condition = "";
                    if (!empty($args['duration']) && $args['duration']>0) {

                        $current_time = date('Y-m-d H:i:s', current_time('timestamp'));
                        $duration_date= date('Y-m-d H:i:s', strtotime($current_time. "-".$args['duration']." month"));

                        $where_condition = " AND arm_date_time>='".$duration_date."' ";
                    }

                    $armaff_visits = $wpdb->get_row( $wpdb->prepare( "SELECT count(arm_visitor_id) AS total_visits FROM {$arm_affiliate->tbl_arm_aff_visitors} WHERE `arm_affiliate_id` = '%d' ".$where_condition." GROUP BY arm_affiliate_id;", $arm_current_affiliate_id ), ARRAY_A);

                    if( isset($armaff_visits['total_visits']) ) {
                        return $armaff_visits['total_visits'];
                    }
                }
            }

        }

        function arm_aff_statistics_func($atts, $content, $tag)
        {

            global $wpdb, $arm_aff_statistics, $arm_affiliate_settings, $arm_global_settings, $arm_payment_gateways, $arm_affiliate_version,$arm_affiliate,$ARMember;

            $arm_check_is_gutenberg_page = $ARMember->arm_check_is_gutenberg_page();
            if($arm_check_is_gutenberg_page)
            {
                return;
            }

            $is_user_logged_in = is_user_logged_in();
            if ( $is_user_logged_in && current_user_can('administrator')) {
                return false;
            }

            $global_currency = $arm_payment_gateways->arm_get_global_currency();

            if ($is_user_logged_in) {
                $armaff_current_user_id = get_current_user_id(); 

                $user_aff_id = $wpdb->get_row("SELECT arm_affiliate_id , arm_user_id FROM  `{$arm_affiliate->tbl_arm_aff_affiliates}` WHERE arm_user_id = ". $armaff_current_user_id);

                if (!empty($user_aff_id->arm_affiliate_id)) {
                	$user_aff_id = $user_aff_id->arm_affiliate_id;

                	$armaffstatistics = $arm_aff_statistics->arm_aff_get_affiliate_user_statistics($user_aff_id);

		            $args = shortcode_atts(array(
		                    'earning_back_color' => '',
		                    'paid_payment_back_color' => '',
		                    'unpaid_payment_back_color' => '',
		                    'visitor_back_color' => '',
		                    'referral_back_color' => '',
		                    'earning_title' => __("Earnings", "ARM_AFFILIATE"),
		                    'payment_paid_title' => __("Payments (Paid)", "ARM_AFFILIATE"),
		                    'payment_unpaid_title' => __("Payments (Unpaid)", "ARM_AFFILIATE"),
		                    'visitor_title' => __("Visitor", "ARM_AFFILIATE"),
		                    'referral_title' => __("Referral", "ARM_AFFILIATE"),
                            'total_text' => __("Total", "ARM_AFFILIATE"),
                            'current_month_text' => __('Current Month', "ARM_AFFILIATE"),
		                ), $atts, $tag);

                    $earning_total = $arm_payment_gateways->arm_prepare_amount($global_currency, $armaffstatistics["total_earning"]);
                    $earning_current_month = $arm_payment_gateways->arm_prepare_amount($global_currency, $armaffstatistics["month_earning"]);
                    $payment_paid_total = $arm_payment_gateways->arm_prepare_amount($global_currency, $armaffstatistics['total_paid']);
                    $payment_paid_current_month = $arm_payment_gateways->arm_prepare_amount($global_currency, $armaffstatistics['month_paid']);
                    $payment_unpaid_total = $arm_payment_gateways->arm_prepare_amount($global_currency, $armaffstatistics['total_unpaid']);
                    $payment_unpaid_current_month = $arm_payment_gateways->arm_prepare_amount($global_currency, $armaffstatistics['month_unpaid']);
                    $total_visits = $armaffstatistics['total_visits'];
                    $month_visits = $armaffstatistics['month_visits'];
                    $total_referral = $armaffstatistics['total_referral'];


		            $earning_back_color =  !empty($args['earning_back_color']) ? ' style=" background: '. $args['earning_back_color'] .' !important;"' : '';
		            $paid_payment_back_color = !empty($args['paid_payment_back_color']) ? ' style="background: '. $args['paid_payment_back_color'] .' !important;"' : '';
		            $unpaid_payment_back_color = !empty($args['unpaid_payment_back_color']) ? ' style="background: '. $args['unpaid_payment_back_color'] .' !important;"' : '';
		            $visitor_back_color = !empty($args['visitor_back_color']) ? ' style="background: '.$args['visitor_back_color'].' !important;"' : '';
		            $referral_back_color = !empty($args['referral_back_color']) ? ' style="background: '.$args['referral_back_color'].' !important;"' : '';

		            $armaff_statistics_html = '';
		            $armaff_statistics_html .= '<div class="arm_dashboard_member_summary">';

		            $armaff_statistics_html .= '<div class="arm_box_wrapper">';
		            $armaff_statistics_html .= '<div class="arm_box_title"> '. $args['earning_title'] .' </div>';
		            $armaff_statistics_html .= '<div class="arm_month_total_visitor arm_member_summary"'.$earning_back_color.'>';
		            $armaff_statistics_html .= '<div class="welcome-icon arm_member_content">';
		            $armaff_statistics_html .= '<div class="arm_member_summary_count">'. $earning_total .'</div>';
		            $armaff_statistics_html .= '<div class="arm_member_summary_label">'.$args['total_text'].'</div>';
		            $armaff_statistics_html .= '</div>';
		            $armaff_statistics_html .= '<div class="welcome-icon arm_member_content">
		                                    <div class="arm_member_summary_count">'. $earning_current_month .' </div>
		                                    <div class="arm_member_summary_label">' . $args['current_month_text']. '</div>
		                                </div>';
		            $armaff_statistics_html .= '</div>';
		            $armaff_statistics_html .= '</div>';	            
		            

		            $armaff_statistics_html .= '<div class="arm_box_wrapper">';
		            $armaff_statistics_html .= '<div class="arm_box_title"> '. $args['payment_paid_title'] .' </div>';
		            $armaff_statistics_html .= '<div class="arm_active_members arm_member_summary"'.$paid_payment_back_color.'>';
		            $armaff_statistics_html .= '<div class="welcome-icon arm_member_content">
		                                    <div class="arm_member_summary_count">'. $payment_paid_total .' </div>
		                                    <div class="arm_member_summary_label">' . $args['total_text'] .'</div>
		                                </div>';
		            $armaff_statistics_html .= '<div class="welcome-icon arm_member_content">
		                                    <div class="arm_member_summary_count">'. $payment_paid_current_month . '</div>
		                                    <div class="arm_member_summary_label">' . $args['current_month_text'] .'</div>
		                                </div>';
		            $armaff_statistics_html .= '</div>';
		            $armaff_statistics_html .= '</div>';

		            $armaff_statistics_html .= '<div class="arm_box_wrapper">';
		            $armaff_statistics_html .= '<div class="arm_box_title"> '. $args['payment_unpaid_title'] .' </div>';
		            $armaff_statistics_html .= '<div class="arm_membership_plans arm_member_summary"'.$unpaid_payment_back_color.'>';
		            $armaff_statistics_html .= '<div class="welcome-icon arm_member_content">
		                                    <div class="arm_member_summary_count">'. $payment_unpaid_total .'</div>
		                                    <div class="arm_member_summary_label">'. $args['total_text'].'</div>
		                                </div>';
		            $armaff_statistics_html .= '<div class="welcome-icon arm_member_content">
		                                    <div class="arm_member_summary_count">'. $payment_unpaid_current_month . '</div>
		                                    <div class="arm_member_summary_label">' . $args['current_month_text'] . '</div>
		                                </div>';
		            $armaff_statistics_html .= '</div>';
		            $armaff_statistics_html .= '</div>';           


		            $armaff_statistics_html .= '<div class="arm_box_wrapper">';
		            $armaff_statistics_html .= '<div class="arm_box_title"> '.$args['visitor_title'].' </div>';
		            $armaff_statistics_html .= '<div class="arm_total_visitor arm_member_summary" '. $visitor_back_color .'>';
		            $armaff_statistics_html .= '<div class="welcome-icon arm_member_content">
		                                    <div class="arm_member_summary_count">' . $total_visits .'</div>
		                                    <div class="arm_member_summary_label">' . $args['total_text']. '</div>
		                                </div>';
		            $armaff_statistics_html .= '<div class="welcome-icon arm_member_content">
		                                    <div class="arm_member_summary_count">' . $month_visits . '</div>
		                                    <div class="arm_member_summary_label">' . $args['current_month_text'] . '</div>
		                                </div>';
		            $armaff_statistics_html .= '</div>';
		            $armaff_statistics_html .= '</div>';

		            $armaff_statistics_html .= '<div class="arm_box_wrapper">';
		            $armaff_statistics_html .= '<div class="arm_box_title"> '. $args['referral_title'] .' </div>';
		            $armaff_statistics_html .= '<div class="arm_total_members arm_member_summary"'.$referral_back_color.'>';
		            $armaff_statistics_html .= '<div class="welcome-icon arm_member_content">
		                                    <div class="arm_member_summary_count">' . $armaffstatistics['total_referral'] .' </div>
		                                    <div class="arm_member_summary_label">' . $args['total_text']. '</div>
		                                </div>';
		            $armaff_statistics_html .= '<div class="welcome-icon arm_member_content">
		                                    <div class="arm_member_summary_count">' . $armaffstatistics['month_referral'] .'</div>
		                                    <div class="arm_member_summary_label">' . $args['current_month_text'].'</div>
		                                </div>';
		            $armaff_statistics_html .= '</div>';
		            $armaff_statistics_html .= '</div>';

		            $armaff_statistics_html .= '</div>';

		            return $armaff_statistics_html;
                }   

            }        
        }

        function arm_aff_earning_func($atts, $content, $tag)
        {
            global $wpdb , $arm_affiliate , $ARMember,$arm_payment_gateways; 

            $arm_check_is_gutenberg_page = $ARMember->arm_check_is_gutenberg_page();
            if($arm_check_is_gutenberg_page)
            {
                return;
            }

            $is_user_logged_in = is_user_logged_in();
            if ( $is_user_logged_in && current_user_can('administrator')) {
                return false;
            }

            $args = shortcode_atts(array(
                    'duration' => '',
                ), $atts, $tag);

            if ($is_user_logged_in) {
                $armaff_current_user_id = get_current_user_id(); 
            } else {
                $armaff_current_user_id = '';
            }

            $user_table = $wpdb->users;

            if (!empty($armaff_current_user_id)) {
            	$user_aff_id = $wpdb->get_row("SELECT arm_affiliate_id , arm_user_id FROM  `{$arm_affiliate->tbl_arm_aff_affiliates}` WHERE arm_user_id = ". $armaff_current_user_id);
            }

            if (!empty($user_aff_id->arm_affiliate_id)) {

            	$where_condition = "";

                if (!empty($args['duration']) && $args['duration']>0) {

	                //$where_condition = " YEAR(arm_date_time) = YEAR(CURRENT_DATE - INTERVAL ".$args['duration']." MONTH) AND MONTH(arm_date_time) = MONTH(CURRENT_DATE - INTERVAL ".$args['duration']." MONTH) and ";

                    $current_time = date('Y-m-d H:i:s', current_time('timestamp'));
                    $duration_date= date('Y-m-d H:i:s', strtotime($current_time. "-".$args['duration']." month"));

                    $where_condition = " AND arm_date_time>='".$duration_date."' ";

	            } 

	            $month_earning = $wpdb->get_row("SELECT sum(arm_amount) as total_earning FROM `{$arm_affiliate->tbl_arm_aff_referrals}` r LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff ON aff.arm_affiliate_id = r.arm_affiliate_id LEFT JOIN `{$user_table}` u ON u.ID = aff.arm_user_id WHERE r.arm_status = 1 AND aff.arm_affiliate_id = '". $user_aff_id->arm_affiliate_id."' ".$where_condition);

                $global_currency = $arm_payment_gateways->arm_get_global_currency();
                $earning_total = $arm_payment_gateways->arm_prepare_amount($global_currency, $month_earning->total_earning);
		        return $earning_total;

            }         

                                  
        }

        function arm_aff_payment_paid_func($atts, $content, $tag)
        {
            global $wpdb, $arm_affiliate, $ARMember, $arm_payment_gateways;

            $arm_check_is_gutenberg_page = $ARMember->arm_check_is_gutenberg_page();
            if($arm_check_is_gutenberg_page)
            {
                return;
            }

            $is_user_logged_in = is_user_logged_in();
            if ($is_user_logged_in  && current_user_can('administrator')) {
                return false;
            }

            if ($is_user_logged_in) {
                $armaff_current_user_id = get_current_user_id(); 
            } else {
                return;
            }

            $args = shortcode_atts(array(
                                    'duration' => '',
                                ), $atts, $tag);

            $user_table = $wpdb->users;
            $user_aff_id = $wpdb->get_row("SELECT arm_affiliate_id , arm_user_id FROM  `{$arm_affiliate->tbl_arm_aff_affiliates}` WHERE arm_user_id = ". $armaff_current_user_id);

            if (!empty($user_aff_id->arm_affiliate_id)) 
            {
            	$where_condition = "";
            	if (!empty($args['duration']) && $args['duration']>0) 
                {
                    $current_time = date('Y-m-d H:i:s', current_time('timestamp'));
                    $duration_date= date('Y-m-d H:i:s', strtotime($current_time. "-".$args['duration']." month"));

                    $where_condition = " AND arm_date_time>='".$duration_date."' ";
	            }

	            $month_paid = $wpdb->get_row("SELECT sum(arm_amount) as total_paid FROM `{$arm_affiliate->tbl_arm_aff_payouts}` r LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff ON aff.arm_affiliate_id = r.arm_affiliate_id LEFT JOIN `{$user_table}` u ON u.ID = aff.arm_user_id WHERE aff.arm_affiliate_id = ". $user_aff_id->arm_affiliate_id.$where_condition);

                $global_currency = $arm_payment_gateways->arm_get_global_currency();

                $arm_aff_paid_total_paid = $arm_payment_gateways->arm_prepare_amount($global_currency, $month_paid->total_paid);
                
		        return $arm_aff_paid_total_paid;
            }
        }

        function arm_aff_payment_unpaid_func($atts, $content, $tag)
        {
        	global $wpdb , $arm_affiliate , $ARMember , $arm_payment_gateways; 

            $arm_check_is_gutenberg_page = $ARMember->arm_check_is_gutenberg_page();
            if($arm_check_is_gutenberg_page)
            {
                return;
            }

            $is_user_logged_in = is_user_logged_in();
            if ($is_user_logged_in  && current_user_can('administrator')) {
                return false;
            }

            $args = shortcode_atts(array(
                    'duration' => '',
                ), $atts, $tag);

            if($is_user_logged_in) {
                $armaff_current_user_id = get_current_user_id(); 
            } else {
                return;
            }

            $user_table = $wpdb->users;

            if (!empty($armaff_current_user_id)) {
            	$user_aff_id = $wpdb->get_row("SELECT arm_affiliate_id , arm_user_id FROM  `{$arm_affiliate->tbl_arm_aff_affiliates}` WHERE arm_user_id = ". $armaff_current_user_id);
            }

            if (!empty($user_aff_id->arm_affiliate_id)) 
            {
            	$where_condition = "";
	            if (!empty($args['duration']) && $args['duration']>0) 
                {
	                $current_time = date('Y-m-d H:i:s', current_time('timestamp'));
                    $duration_date= date('Y-m-d H:i:s', strtotime($current_time. "-".$args['duration']." month"));

                    $where_condition = " AND arm_date_time>='".$duration_date."' ";
	            }
	            $month_earning = $wpdb->get_row("SELECT sum(arm_amount) as total_earning FROM `{$arm_affiliate->tbl_arm_aff_referrals}` r LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff ON aff.arm_affiliate_id = r.arm_affiliate_id LEFT JOIN `{$user_table}` u ON u.ID = aff.arm_user_id and r.arm_status = 1 WHERE aff.arm_affiliate_id = ". $user_aff_id->arm_affiliate_id.$where_condition);

            	$month_paid = $wpdb->get_row("SELECT sum(arm_amount) as total_paid FROM `{$arm_affiliate->tbl_arm_aff_payouts}` r LEFT JOIN `{$arm_affiliate->tbl_arm_aff_affiliates}` aff ON aff.arm_affiliate_id = r.arm_affiliate_id LEFT JOIN `{$user_table}` u ON u.ID = aff.arm_user_id WHERE aff.arm_affiliate_id=". $user_aff_id->arm_affiliate_id.$where_condition);

            	$month_unpaid = $month_earning->total_earning - $month_paid->total_paid;

                $global_currency = $arm_payment_gateways->arm_get_global_currency();
                
                $arm_aff_total_unpaid = $arm_payment_gateways->arm_prepare_amount($global_currency, $month_unpaid);
            	return $arm_aff_total_unpaid;
            }
        }

        function arm_aff_referral_func($atts, $content, $tag)
        {
            global $wpdb, $ARMember, $arm_affiliate; 

            $arm_check_is_gutenberg_page = $ARMember->arm_check_is_gutenberg_page();
            if($arm_check_is_gutenberg_page)
            {
                return;
            }

            $is_user_logged_in = is_user_logged_in();
            if ( $is_user_logged_in && current_user_can('administrator')) {
                return false;
            }

            $args = shortcode_atts(array(
                                    'duration' => '',
                                ), $atts, $tag);

            if ($is_user_logged_in) {
                $armaff_current_user_id = get_current_user_id(); 
            } else {
                return;
            }

            $user_table = $wpdb->users;

            
           	$user_aff_id = $wpdb->get_row("SELECT arm_affiliate_id , arm_user_id FROM  `{$arm_affiliate->tbl_arm_aff_affiliates}` WHERE arm_user_id = ". $armaff_current_user_id);

            if (!empty($user_aff_id->arm_affiliate_id)) 
            {
            	$where_condition = "";
	            if (!empty($args['duration']) && $args['duration']>0) 
                {
	                $current_time = date('Y-m-d H:i:s', current_time('timestamp'));
                    $duration_date= date('Y-m-d H:i:s', strtotime($current_time. "-".$args['duration']." month"));

                    $where_condition = " AND arm_date_time>='".$duration_date."' ";
		        }

		        $month_referral = $wpdb->get_row("SELECT count(arm_referral_id) as total_referral FROM `{$arm_affiliate->tbl_arm_aff_referrals}` WHERE arm_affiliate_id = " . $user_aff_id->arm_affiliate_id.$where_condition, ARRAY_A);

                if(isset($month_referral['total_referral']))
                {
		          return $month_referral['total_referral'];
                }
            }
        }
    }
}

global $arm_aff_layout;
$arm_aff_layout = new arm_aff_layout();
?>