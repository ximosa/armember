<?php
if(!class_exists('ARM_affiliate_woocommerce')){
    
    class ARM_affiliate_woocommerce{

        function __construct(){

            add_action('admin_enqueue_scripts', array(&$this, 'armaffiliate_enqueue_woocommerce_stylesheet'));
            add_action('deactivated_plugin', array(&$this, 'armaffiliate_woomcommerce_deactivation'), 11, 2);

            add_action( 'woocommerce_coupon_options', array( $this, 'armaffiliate_coupon_fields' ) );
            add_action( 'woocommerce_coupon_options_save', array( $this, 'armaffiliate_save_coupon_fields' ) );

            add_filter( 'woocommerce_product_data_tabs', array( $this, 'armaffiliate_add_product_tabs' ) );
            add_action( 'woocommerce_product_data_panels', array( $this, 'armaffiliate_add_product_panels' ), 101 );
            add_action( 'woocommerce_process_product_meta', array(&$this, 'armaffiliate_save_product_meta'));


            add_action( 'woocommerce_checkout_after_customer_details' , array( $this, 'armaffiliate_add_referral_checkout_fields') );

            add_action( 'woocommerce_order_status_completed', array( $this, 'armaffiliate_accept_order_referral' ), 20 );
            add_action( 'woocommerce_order_status_processing', array( $this, 'armaffiliate_process_order_referral' ), 20 );

            add_action( 'woocommerce_order_status_completed_to_refunded', array( $this, 'armaffiliate_reject_order_referral' ), 20 );
            add_action( 'woocommerce_order_status_on-hold_to_refunded', array( $this, 'armaffiliate_reject_order_referral' ), 20 );
            add_action( 'woocommerce_order_status_processing_to_refunded', array( $this, 'armaffiliate_reject_order_referral' ), 20 );
            add_action( 'woocommerce_order_status_processing_to_cancelled', array( $this, 'armaffiliate_reject_order_referral' ), 20 );
            add_action( 'woocommerce_order_status_completed_to_cancelled', array( $this, 'armaffiliate_reject_order_referral' ), 20 );
            add_action( 'woocommerce_order_status_pending_to_cancelled', array( $this, 'armaffiliate_reject_order_referral' ), 20 );
            add_action( 'woocommerce_order_status_pending_to_failed', array( $this, 'armaffiliate_reject_order_referral' ), 20 );

            add_action( 'wc-on-hold_to_trash', array( $this, 'armaffiliate_reject_order_referral' ), 20 );
            add_action( 'wc-processing_to_trash', array( $this, 'armaffiliate_reject_order_referral' ), 20 );
            add_action( 'wc-completed_to_trash', array( $this, 'armaffiliate_reject_order_referral' ), 20 );

            add_action( 'woocommerce_checkout_order_processed', array( $this, 'armaffiliate_add_referral_order' ), 100, 1 );

        }


        function armaffiliate_enqueue_woocommerce_stylesheet()
        {
            global $post_type, $arm_affiliate_version;
            if((isset($_GET['post'])) || (isset($_GET['post_type']) && $_GET['post_type'] == 'product' ))
            {
                wp_enqueue_style('armaffiliate_woocommerce_css', ARM_AFFILIATE_URL . '/css/arm_aff_woocommerce.css', array(), $arm_affiliate_version);
            }
        }

        function armaffiliate_woomcommerce_deactivation($plugin, $network_activation){
            if ($plugin == "woocommerce/woocommerce.php") {
                global $arm_affiliate_settings;
                $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
                if( isset($affiliate_options['armaffiliate_woo_options']) ) {
                    unset( $affiliate_options['armaffiliate_woo_options'] );
                    update_option( 'arm_affiliate_setting', $affiliate_options );
                }
            }
        }

        function armaffiliate_coupon_fields(){

            global $post, $arm_aff_affiliate;

            $armaffiliate_sel_id = get_post_meta( $post->ID, 'armaffiliate_coupon_affiliate', true );

            $all_armaffiliates = $arm_aff_affiliate->arm_get_all_active_affiliates();

            $armaffiliates_options = $all_armaffiliates_users = array();

            $armaffiliates_options[0] = __("Select Affiliate", 'ARM_AFFILIATE');

            if(!empty($all_armaffiliates)) {
                
                $all_armaffiliates_user_ids = array_column($all_armaffiliates, 'arm_user_id');

                $armaff_admin = array(
                    'include' => implode(',', $all_armaffiliates_user_ids),
                    'fields'   => array('ID', 'user_login', 'user_nicename', 'user_email', 'display_name')
                );
                $super_admin_ids = get_users($armaff_admin);

                foreach ($super_admin_ids as $key => $affiliate_user) {
                    $all_armaffiliates_users[ $affiliate_user->ID ] = $affiliate_user;
                }

                foreach ($all_armaffiliates as $key => $armaffiliate) {

                    if(isset($all_armaffiliates_users[$armaffiliate['arm_user_id']])){
                        $armaff_user = $all_armaffiliates_users[$armaffiliate['arm_user_id']];
                        $armaff_label = isset($armaff_user->user_login) ? $armaff_user->user_login : $armaff_user->user_nicename;
                    } else {
                        continue;
                    }

                    $armaffiliates_options[ $armaffiliate['arm_affiliate_id'] ] = $armaff_label;

                }

            }

            ?>
            <p class="form-field armaffiliate_woo_coupon_fields">
                <?php
                    woocommerce_wp_select(
                        array(
                            'id' => 'armaffiliate_coupon_affiliate',
                            'label' => __('ARMember Affiliate', 'ARM_AFFILIATE'),
                            'options' => $armaffiliates_options,
                            'value' => $armaffiliate_sel_id,
                        )
                    );
                ?>
            </p>
            <?php
        }

        function armaffiliate_save_coupon_fields( $coupon_id = 0 ){

            if(isset($_POST['armaffiliate_coupon_affiliate'])) {
                update_post_meta( $coupon_id, 'armaffiliate_coupon_affiliate', $_POST['armaffiliate_coupon_affiliate'] );
            } else {
                delete_post_meta( $coupon_id, 'armaffiliate_coupon_affiliate' );
            }

        }

        function armaffiliate_add_product_tabs( $woo_tabs ){

            $woo_tabs['armember_affiliate'] = array(
                'label'  => __( 'ARMember Affiliate', 'ARM_AFFILIATE' ),
                'target' => 'armaffiliate_product_panel',
                'class'  => array('armaffiliate_mapping_tab'),
            );

            return $woo_tabs;

        }

        function armaffiliate_add_product_panels(){

            global $post;

            ?>

            <div id="armaffiliate_product_panel" class="panel woocommerce_options_panel">

                <div class="options_group armaffiliate_wc_product_options">
                    <p class="armaffiliate_wc_products_title"><?php _e( 'ARMember Affiliates commission rate on each sale of this product', 'ARM_AFFILIATE' ); ?></p>

                    <?php

                    woocommerce_wp_text_input( array(
                        'id'          => '_armaffiliate_product_rate',
                        'label'       => __( 'ARMember Affiliate Rate', 'ARM_AFFILIATE' ),
                        'description' => __( '( Blank is considered to use default rates. )', 'ARM_AFFILIATE' ),
                        'class'       => 'armaffiliate_wc_products_input'
                    ) );

                    woocommerce_wp_checkbox( array(
                        'id'          => '_armaffiliate_referrals_disabled',
                        'label'       => '',
                        'description' => __( 'Disable affiliate commission on sale of this product.', 'ARM_AFFILIATE' ),
                        'cbvalue'     => 1,
                        'class'       => 'armaffiliate_wc_products_checkbox'
                    ) );

                    ?>

                </div>
            </div>

            <?php

        }

        function armaffiliate_save_product_meta($product_id){

            if(isset($_POST['_armaffiliate_product_rate'])){
                update_post_meta($product_id, '_armaffiliate_product_rate', esc_attr($_POST['_armaffiliate_product_rate']));
            } else {
                delete_post_meta($product_id, '_armaffiliate_product_rate');
            }

            if(isset($_POST['_armaffiliate_referrals_disabled'])){
                update_post_meta($product_id, '_armaffiliate_referrals_disabled', esc_attr($_POST['_armaffiliate_referrals_disabled']));
            } else {
                delete_post_meta($product_id, '_armaffiliate_referrals_disabled');
            }

        }

        function armaffiliate_add_referral_order( $order_id = 0 ) {

            if(!class_exists('WC_Order')){
                return false;
            }

            $armaffiliate_order = new WC_Order( $order_id );

            $armaff_coupon_affiliate = $this->armaffiliate_get_coupon_affiliate( $armaffiliate_order );
            $arm_referred_affiliate = $this->is_armaffiliate_referred();
            /*$is_allowed_referral = $arm_aff_referrals->arm_check_is_allowed_affiliate();*/

            if( $armaff_coupon_affiliate || $arm_referred_affiliate > 0 ) {

                global $wpdb, $arm_affiliate, $arm_affiliate_settings;

                $armaff_id = $arm_referred_affiliate;
                if($armaff_coupon_affiliate){
                    $armaff_id = $armaff_coupon_affiliate;
                }

                $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();

                $armaff_get_affiliate = $wpdb->get_row('SELECT * FROM ' . $arm_affiliate->tbl_arm_aff_affiliates . ' WHERE arm_affiliate_id = '.$armaff_id.' LIMIT 1');

                if(!$armaff_get_affiliate){
                    return false;
                }

                if ( $this->armaffiliate_identify( $armaffiliate_order->billing_email, $armaff_get_affiliate ) ) {
                    return false;
                }

                $armaff_get_referral = $wpdb->get_row( $wpdb->prepare(  "SELECT `arm_referral_id`, `arm_status` FROM $arm_affiliate->tbl_arm_aff_referrals WHERE `arm_woo_order` = '%s' LIMIT 1;", $order_id ) );

                if ( $armaff_get_referral && ( $armaff_get_referral->arm_status == 1 || $armaff_get_referral->arm_status == 2 ) ) {
                    return false;
                }

                $armaffiliate_shipping_items = $armaffiliate_order->get_total_shipping();

                $armaff_items = $armaffiliate_order->get_items();

                $armaff_amount = 0.00;

                foreach ( $armaff_items as $armaff_product ) {

                    if ( get_post_meta( $armaff_product['product_id'], '_armaffiliate_referrals_disabled', true ) ) {
                        continue;
                    }

                    if( !empty( $armaff_product['variation_id'] ) && get_post_meta( $armaff_product['variation_id'], '_armaffiliate_referrals_disabled', true ) ) {
                        continue;
                    }

                    $armaff_total_products_amt = $armaff_product['line_total'];
                    $armaff_shipping = 0;

                    $armaff_product_id = $armaff_product['product_id'];

                    $armaff_amount += $this->get_referral_amount_calculated( $armaff_total_products_amt, $order_id, $armaff_product_id, $armaff_id, $affiliate_options );

                }


                $armaffiliate_allow_zero_commision = isset($affiliate_options['arm_aff_not_allow_zero_commision']) ? $affiliate_options['arm_aff_not_allow_zero_commision'] : 0 ;

                if ( $armaff_amount == 0 && !$armaffiliate_allow_zero_commision ) {
                    return false;
                }


                if($armaff_get_referral){

                    $wpdb->update(
                        $arm_affiliate->tbl_arm_aff_referrals, 
                        array( 'arm_amount' => $armaff_amount, 'arm_woo_order' => $order_id ), 
                        array( 'arm_referral_id' => $armaff_get_referral->arm_referral_id ),
                        array( '%s', '%s' ),
                        array( '%d' )
                    );

                } else {

                    global $arm_payment_gateways;

                    $armaff_default_refferal_status = isset($affiliate_options['arm_aff_referral_status']) ? $affiliate_options['arm_aff_referral_status'] :  0 ;
                    $armaff_currency = $arm_payment_gateways->arm_get_global_currency();

                    $armaff_referrals_values = array(
                        'arm_affiliate_id' => $armaff_id,
                        'arm_plan_id' => 0,
                        'arm_ref_affiliate_id' => $armaff_get_affiliate->arm_user_id,
                        'arm_status' => $armaff_default_refferal_status,
                        'arm_amount' => $armaff_amount,
                        'arm_currency' => $armaff_currency,
                        'arm_woo_order' => $order_id,
                        'arm_date_time' => current_time('mysql')
                    );

                    $armaff_referral_id = $wpdb->insert($arm_affiliate->tbl_arm_aff_referrals, $armaff_referrals_values);

                    if( $armaff_referral_id ){
                        /*$armaffiliate_order->add_order_note( sprintf( __( '%s got referral added for %s amount.', 'ARM_AFFILIATE' ), $armaff_amount, $armaff_name ) );*/
                    }

                }

            }

        }

        function armaffiliate_get_coupon_affiliate( $armaffiliate_order ) {

            $armaffiliate_woo_coupons = $armaffiliate_order->get_used_coupons();

            if ( empty( $armaffiliate_woo_coupons ) ) {
                return false;
            }

            foreach ( $armaffiliate_woo_coupons as $armaffiliate_woo_code ) {

                $armaffiliate_woo_coupon = new WC_Coupon( $armaffiliate_woo_code );
                $armaffiliate_id = get_post_meta( $armaffiliate_woo_coupon->id, 'armaffiliate_coupon_affiliate', true );

                if ( $armaffiliate_id ) {

                    if ( !$this->armaffiliate_validate_affiliate( $armaffiliate_id ) ) {
                        continue;
                    }

                    return $armaffiliate_id;

                }

            }

            return false;
        }

        function armaffiliate_validate_affiliate( $armaffiliate_id = 0 ){

            if(empty($armaffiliate_id)){
                return false;
            }

            global $wpdb, $arm_affiliate;


            $armaff_get_affiliate = $wpdb->get_row('SELECT * FROM '.$arm_affiliate->tbl_arm_aff_affiliates.' WHERE `arm_affiliate_id` = '.$armaffiliate_id.' LIMIT 1');

            if( $armaff_get_affiliate ) {

                if( $armaff_get_affiliate->arm_status != 1 ){
                    return false;
                }

                if( is_user_logged_in() && get_current_user_id() == $armaff_get_affiliate->arm_user_id ) {
                    return false;
                }

            } else {
                return false;
            }


            return true;

        }

        function is_armaffiliate_referred() {

            global $wp_query, $arm_affiliate_settings;

            $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
            $referral_var = $affiliate_options['arm_aff_referral_var'];

            $armaffReqAffiliate = isset($_REQUEST[$referral_var]) ? $_REQUEST[$referral_var] : '';
            if(empty($armaffReqAffiliate))
            {
                $armaffReqAffiliate = isset($_COOKIE['arm_aff_ref_cookie']) ? $_COOKIE['arm_aff_ref_cookie'] : '';
            }

            if($armaffReqAffiliate == ''){
                $armaffReqAffiliate = $wp_query->get($referral_var);
            }

            if($armaffReqAffiliate != '')
            {
                return $armaffReqAffiliate;
            }

            return false;

        }

        function armaffiliate_identify($armaff_email, $armaff_get_affiliate){

            if($armaff_get_affiliate){
                $armaff_info = get_userdata($armaff_get_affiliate->arm_user_id);
                if( $armaff_info->user_email == $armaff_email ) {
                    return true;
                }
            }

            return false;

        }

        function get_referral_amount_calculated( $base_amount = '', $reference = '', $product_id = 0, $affiliate_id = 0, $affiliate_options = array() ) {

            $rate = '';
            if ( !empty( $product_id ) ) {
                $rate = get_post_meta( $product_id, '_armaffiliate_product_rate', true );
            }

            $armaff_global_rate = isset($affiliate_options['arm_aff_referral_default_rate']) ? $affiliate_options['arm_aff_referral_default_rate'] : 20 ;
            $rate = ($rate != '') ? $rate : $armaff_global_rate;

            $armaff_referral_amount = $rate;

            if ( $armaff_referral_amount < 0 ) {
                $armaff_referral_amount = 0;
            }

            return $armaff_referral_amount;

        }

        function armaffiliate_add_referral_checkout_fields(){

            global $wp_query, $arm_affiliate_settings;

            $affiliate_options = $arm_affiliate_settings->get_arm_affiliate_settings();
            $referral_var = $affiliate_options['arm_aff_referral_var'];

            $armaffReqAffiliate = isset($_REQUEST[$referral_var]) ? $_REQUEST[$referral_var] : '';

            if($armaffReqAffiliate == ''){
                $armaffReqAffiliate = $wp_query->get($referral_var);
            }

            ?>
            
            <input type="hidden" name="<?php echo $referral_var; ?>" value="<?php echo $armaffReqAffiliate; ?>" />
            <?php

        }

        function armaffiliate_accept_order_referral( $order_id = 0 ){

            if(empty($order_id)){
                return false;
            }

            global $wpdb, $arm_affiliate;

            $armaff_get_referral = $wpdb->get_row( $wpdb->prepare(  "SELECT `arm_referral_id`, `arm_status` FROM $arm_affiliate->tbl_arm_aff_referrals WHERE `arm_woo_order` = '%s' LIMIT 1;", $order_id ) );
            if( (!$armaff_get_referral) || ($armaff_get_referral && $armaff_get_referral->arm_status > 0) ){
                return false;
            }

            $armaff_update = $wpdb->update(
                    $arm_affiliate->tbl_arm_aff_referrals, 
                    array( 'arm_status' => 1),
                    array( 'arm_referral_id' => $armaff_get_referral->arm_referral_id ), 
                    array( '%d' ),
                    array( '%d' )
                );

            if($armaff_update){
                return true;
            }

            return false;

        }

        function armaffiliate_process_order_referral( $order_id = 0 ){

            $armaff_wc_method = get_post_meta( $order_id, '_payment_method', true );

            if( $armaff_wc_method !== 'cod' ) {
                $this->armaffiliate_accept_order_referral();
            }

        }

        function armaffiliate_reject_order_referral( $order_id = 0 ){

            if ( is_a( $order_id, 'WP_Post' ) ) {

                $order_id = $order_id->ID;

                if(empty($order_id)){
                    return false;
                }

                $armaffiliate_order_post = get_post_type( $order_id );
                if($armaffiliate_order_post != 'shop_order'){
                    return;
                }

            }

            global $wpdb, $arm_affiliate;

            $armaff_get_referral = $wpdb->get_row( $wpdb->prepare(  "SELECT `arm_referral_id`, `arm_status` FROM $arm_affiliate->tbl_arm_aff_referrals WHERE `arm_woo_order` = '%s' LIMIT 1;", $order_id ) );
            if( (!$armaff_get_referral) || ($armaff_get_referral && $armaff_get_referral->arm_status == 2) ){
                return false;
            }

            $armaff_update = $wpdb->update(
                    $arm_affiliate->tbl_arm_aff_referrals, 
                    array( 'arm_status' => 3),
                    array( 'arm_referral_id' => $armaff_get_referral->arm_referral_id ), 
                    array( '%d' ),
                    array( '%d' )
                );

            if($armaff_update){
                return true;
            }

            return false;

        }


    }
}

global $armaffiliate_woocommerce;
$armaffiliate_woocommerce = new ARM_affiliate_woocommerce();
?>