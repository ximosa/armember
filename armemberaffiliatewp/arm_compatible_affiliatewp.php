<?php

class ARM_compatible_affiliatewp extends Affiliate_WP_Base {
    
    public function __construct() {
        
        $this->context = 'arm';
        
        add_action( 'arm_display_field_add_membership_plan', array( $this, 'display_field_add_membership_plan_page' ) );
        
        add_filter( 'arm_befor_save_field_membership_plan', array( $this, 'before_save_field_membership_plan' ), 10, 2 );
        
        add_filter( 'arm_add_arm_entries_value', array( $this, 'add_affiliate_in_arm_entries' ), 10, 1 );
        
        add_action( 'arm_after_add_new_user', array( $this, 'add_pending_referral' ), 10, 2 );
        
        add_filter( 'affwp_referral_reference_column', array( $this, 'reference_link' ), 10, 2 );
    }
    
    function display_field_add_membership_plan_page($plan_options){
        global $arm_payment_gateways;
        ?>
        <div class="arm_solid_divider"></div>
        <div id="arm_plan_price_box_content" class="arm_plan_price_box">
            <div class="page_sub_content">
                <div class="page_sub_title"><?php _e('AffiliateWP Settings',ARM_AFFILIATEWP_TEXTDOMAIN);?></div>
                <table class="form-table">
                    <tr class="form-field form-required">
                        <th><label><?php _e('Enable Referral Conversion' ,ARM_AFFILIATEWP_TEXTDOMAIN);?></label></th>   
                        <td>
                            <?php
                            $enable_affiliatewp_disable_referrals = (!empty($plan_options["affiliatewp_disable_referral"])) ? $plan_options["affiliatewp_disable_referral"] : 0;
                            ?>
                            <div class="armclear"></div>
                            <div class="armswitch arm_global_setting_switch" style="vertical-align: middle;">
                                <input type="checkbox" id="affiliatewp_disable_referrals" <?php checked($enable_affiliatewp_disable_referrals, 1);?> value="1" class="armswitch_input" name="arm_subscription_plan_options[affiliatewp_disable_referral]"/>
                                <label for="affiliatewp_disable_referrals" class="armswitch_label" style="min-width:40px;"></label>
                            </div>
                            &nbsp;
                            <i class="arm_helptip_icon fa fa-question-circle" title="<?php _e('Enable referrals for this membership plan.',ARM_AFFILIATEWP_TEXTDOMAIN); ?>"></i>
                            <div class="armclear"></div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }
    
    function before_save_field_membership_plan($plan_options, $posted_data)
    {
        $plan_options['affiliatewp_disable_referral'] = isset($posted_data['arm_subscription_plan_options']['affiliatewp_disable_referral']) ? $posted_data['arm_subscription_plan_options']['affiliatewp_disable_referral'] : 0;
        return $plan_options;
    }
    
    function add_affiliate_in_arm_entries($entry_post_data){
        $entry_post_data['ref_affiliate_id'] = affiliate_wp()->tracking->get_affiliate_id();
        return $entry_post_data;
    }
    
    public function get_product_rate( $product_id = 0, $args = array() ) {
            $affiliate_id = $args['affiliate_id'];
            
            $rate = isset($this->level_referrals_settings['rate']) ? $this->level_referrals_settings['rate'] : '';
            
            $product_id = 0;
            
            return apply_filters( 'affwp_get_product_rate', $rate, $product_id, $args, $affiliate_id, $this->context );
    }

    function add_pending_referral( $user_id, $posted_data ){
        
        global $wpdb, $ARMember;
        
        $ARMember->arm_write_response('reptuelog : tes99t');
        
        $plan_amount = 0;
        $affiliate_id     = affwp_get_affiliate_id( $user_id );
        $plan_id = isset($posted_data['subscription_plan']) ? $posted_data['subscription_plan'] : 0;
        if ($plan_id == 0) {
            $plan_id = isset($posted_data['_subscription_plan']) ? $posted_data['_subscription_plan'] : 0;
        }
        
        $bank_log = $ARMember->tbl_arm_bank_transfer_log;
        $orderid = $wpdb->get_row($wpdb->prepare("SELECT `arm_log_id`, `arm_amount` FROM `{$bank_log}` WHERE `arm_plan_id` = %d and `arm_user_id` = %d ", $plan_id, $user_id));
        if($wpdb->num_rows > 0)
        {
            $affiliate_id     = 'B'.$orderid->arm_log_id;
            $plan_amount = $orderid->arm_amount;
        }
        else
        {
            $payment_log = $ARMember->tbl_arm_payment_log;
            $orderid = $wpdb->get_row($wpdb->prepare("SELECT `arm_log_id`, `arm_amount` FROM `{$payment_log}` WHERE `arm_plan_id` = %d and `arm_user_id` = %d ", $plan_id, $user_id));
            if($wpdb->num_rows > 0)
            {
                $affiliate_id     = $orderid->arm_log_id;
                $plan_amount = $orderid->arm_amount;
            }
            else{
                //not affiliate id not plan amount
            }
        }
        //$affiliate_id     = affwp_get_affiliate_id( $user_id );
        
        $plan_table = $ARMember->tbl_arm_subscription_plans;
        $plan_data = $wpdb->get_row($wpdb->prepare("SELECT `arm_subscription_plan_id`, `arm_subscription_plan_name`, `arm_subscription_plan_options`, `arm_subscription_plan_amount` FROM `{$plan_table}` WHERE `arm_subscription_plan_id` = %d ", $plan_id));
        $arm_subscription_plan_options = maybe_unserialize($plan_data->arm_subscription_plan_options);
        
        //$plan_ammount = $plan_data->arm_subscription_plan_amount;
        $disable_referral = isset($arm_subscription_plan_options['affiliatewp_disable_referral']) ? $arm_subscription_plan_options['affiliatewp_disable_referral'] : 0;
        $description = $plan_data->arm_subscription_plan_name;
        $ref_affiliate_id = affiliate_wp()->tracking->get_affiliate_id();
        if(($ref_affiliate_id <= 0 || $ref_affiliate_id != '') && isset($posted_data['ref_affiliate_id']))
        {
            $ref_affiliate_id = isset($posted_data['ref_affiliate_id']) ? $posted_data['ref_affiliate_id'] : 0 ;
        }
        
        if( $disable_referral == 1 ){
            
            $referral_total = $this->calculate_referral_amount( $plan_amount, $affiliate_id, $plan_id, $ref_affiliate_id );
         
            $referral_id = $this->insert_pending_referral( $referral_total, $affiliate_id, $description, '', array( 'affiliate_id' => $ref_affiliate_id ));
            
            if($referral_id > 0){
                affiliate_wp()->referrals->update( $referral_id, array( 'custom' => $affiliate_id ), '', 'referral' );
            }
        }
    }
    
    public function reference_link( $reference = 0, $referral ) {

        if( empty( $referral->context ) || ('armember' != $referral->context && 'arm' != $referral->context) ) {

                return $reference;
        }

        global $ARMember, $wpdb;
        $qry_table = "";
        $log_id = "";
        if(strpos($reference,"B")===0) 
        {
            $log_id = str_replace("B", "", $reference);
            $qry_table = $ARMember->tbl_arm_bank_transfer_log;
        } 
        else 
        {
            $qry_table = $ARMember->tbl_arm_payment_log;
            $log_id = $reference;
        }

        $user_id = $wpdb->get_row($wpdb->prepare("SELECT arm_user_id FROM ".$qry_table." WHERE arm_log_id=%d", $log_id), ARRAY_A);
        if(!empty($user_id['arm_user_id']))
        {
            $url = admin_url( 'admin.php?page=arm_manage_members&action=view_member&id='.$user_id['arm_user_id']);
            $reference = '<a href="' . esc_url( $url ) . '">' . $reference . '</a>';
        }
        
        return $reference;
    }
}
new ARM_compatible_affiliatewp;

?>