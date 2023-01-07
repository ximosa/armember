<?php
    global $wpdb, $arm_affiliate_settings, $arm_aff_affiliate, $arm_affiliate_commision_setup, $arm_default_user_details_text, $armaff_rate_type_arr, $arm_version;
    global $arm_subscription_plans, $arm_global_settings, $arm_payment_gateways;

    $all_affiliate_users =  $arm_aff_affiliate->arm_get_affiliate_users('add_commision');
    $all_members = $all_affiliate_users['all_members'];
    $all_affiliate_ids = $all_affiliate_users['all_affiliates'];

    $affiliates_result = $arm_affiliate_commision_setup->armaff_get_all_affiliates_commision_setup();

    if (!empty($affiliates_result)) {
        foreach($affiliates_result as $affiliateData) {

            if(in_array($affiliateData['armaff_affiliate_id'], $all_affiliate_ids )){
                unset($all_affiliate_ids[$affiliateData['armaff_user_id']]);
            }

        }
    }

    $armaffplan_grid_columns = array(
        'planid' => __('Plan ID', 'ARM_AFFILIATE'),
        'planname' => __('Plan Name', 'ARM_AFFILIATE'),
        'plantype' => __('Plan Type', 'ARM_AFFILIATE'),
        'referraltype' => __('Referral Type', 'ARM_AFFILIATE'),
        'referralrate' => __('Referral Rate', 'ARM_AFFILIATE'),
    );

    $affiliates_grid_columns = array(
        'affiliateid' => __('Affiliate ID', 'ARM_AFFILIATE'),
        'Username' => __('Username', 'ARM_AFFILIATE'),
        'userid' => __('User ID', 'ARM_AFFILIATE'),
        'referraltype' => __('Referral Type', 'ARM_AFFILIATE'),
        'referralrate' => __('Referral Rate', 'ARM_AFFILIATE'),
        'recurringreferralstatus' => __('Recurring Referral Status', 'ARM_AFFILIATE'),
    );

?>

<?php
    if(version_compare($arm_version, '4.0.1', '<'))
    {
?>
        <style type="text/css" title="currentStyle">
            @import "<?php echo MEMBERSHIP_URL;?>/datatables/media/css/demo_page.css";
            @import "<?php echo MEMBERSHIP_URL;?>/datatables/media/css/demo_table_jui.css";
            @import "<?php echo MEMBERSHIP_URL;?>/datatables/media/css/jquery-ui-1.8.4.custom.css";
            @import "<?php echo MEMBERSHIP_URL;?>/datatables/media/css/ColVis.css";
            .paginate_page a{display:none;}
            #poststuff #post-body {margin-top: 32px;}
            .ColVis_Button{display:none;}
        </style>
<?php
    }
    else
    {
?>
        <style type="text/css">
            .dataTables_filter{ padding: 1rem; }
        </style>
<?php        
    }
?>
<script type="text/javascript" charset="utf-8">
// <![CDATA[
jQuery(document).ready( function () {
    arm_load_plan_list_grid();
    arm_load_affiliates_referral_list_grid('');
    arm_icheck_init();
});

function show_affiliates_grid_loader() {
    jQuery('.armaff_affiliates_loading_grid').show();
}

function show_plans_grid_loader() {
    jQuery('.armaff_plans_loading_grid').show();
}

function arm_load_plan_list_filtered_grid()
{
    jQuery('#example_1').dataTable().fnDestroy();
    arm_load_plan_list_grid();
}

function arm_load_plan_list_grid(){

    /*var search_term = jQuery("#armmanagesearch_new").val();
    var db_search_term = (typeof search_term !== 'undefined' && search_term !== '') ? search_term : '';*/

    var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';

    var __ARMVersion = '<?php echo $arm_version;?>';

    var oTables = jQuery('#example_1');

    var dt_obj = {
        "sDom": '<"H"Cfr>t<"F"ipl>',
        "sPaginationType": "four_button",
        "oLanguage": {
            "sProcessing": show_plans_grid_loader(),
            "sEmptyTable": "No any subscription plan found.",
            "sZeroRecords": "No matching records found."
          },
        "bJQueryUI": true,
        "bPaginate": true,
        "bAutoWidth" : false,
        "aaSorting": [],
        "aoColumnDefs": [
            { "bVisible": false, "aTargets": [] },
            { "bSortable": false, "aTargets": [] },
            {"sClass": "center", "aTargets": [0,3,4] },
        ],
        "fnDrawCallback":function(){
            jQuery('.armaff_plans_loading_grid').hide();
            if (jQuery.isFunction(jQuery().tipso)) {
                jQuery('.armhelptip').each(function () {
                    jQuery(this).tipso({
                        position: 'top',
                        size: 'small',
                        background: '#939393',
                        color: '#ffffff',
                        width: false,
                        maxWidth: 400,
                        useTitle: true
                    });
                });
            }
        }
    };


    if(__ARMVersion > "4.0")
    {
        dt_obj.language = {
            "searchPlaceholder": "Search",
            "search":"",
        };

        dt_obj.buttons = [];

        dt_obj.sDom = '<"H"CBfr>t<"footer"ipl>';

        dt_obj.fixedColumns = false;
    }


    oTables.dataTable(dt_obj);

    var filter_box = jQuery('#arm_filter_wrapper').html();
    jQuery('div#example_1_filter').parent().append(filter_box);
    jQuery('#arm_filter_wrapper').remove();
}


function arm_load_affiliates_referral_filtered_grid(message = '')
{
    jQuery('#example').dataTable().fnDestroy();
    arm_load_affiliates_referral_list_grid(message);
}

function arm_load_affiliates_referral_list_grid(message = ''){
    /*var search_term = jQuery("#armmanagesearch_new").val();
    var db_search_term = (typeof search_term !== 'undefined' && search_term !== '') ? search_term : '';*/

    var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
    var _wpnonce = jQuery('input[name="_wpnonce"]').val();

    var __ARMVersion = '<?php echo $arm_version;?>';

    var oTables = jQuery('#example');

    var dt_obj = {
        "sDom": '<"H"Cfr>t<"F"ipl>',
        "sPaginationType": "four_button",
        "oLanguage": {
            "sProcessing": show_affiliates_grid_loader(),
            "sEmptyTable": "Commission setup not exist for any affiliate.",
            "sZeroRecords": "No matching affiliate found."
          },
        "bProcessing": false,
        "bServerSide": true,
        "sAjaxSource": ajax_url,
        "sServerMethod": "POST",
        "fnServerParams": function (aoData) {
            aoData.push({'name': 'action', 'value': 'armaff_commision_setup_affiliates_grid'});
            /*aoData.push({'name': 'sSearch', 'value': db_search_term});*/
            aoData.push({'name': 'sColumns', 'value':null});
            aoData.push({'name': '_wpnonce', 'value':_wpnonce});
        },
        "bRetrieve": false,
        "bJQueryUI": true,
        "bPaginate": true,
        "bAutoWidth" : false,
        "aaSorting": [],
        "aoColumnDefs": [
            {"sClass": "left", "aTargets": [1] },
            {"sClass": "center", "aTargets": [0,2,3,4,5] },
            { "bVisible": false, "aTargets": [] },
            { "bSortable": false, "aTargets": [3,5] }
        ],
        "order":[[0,'desc']],
        "aLengthMenu": [10, 25, 50, 100, 150, 200],
        "fnStateLoadParams": function (oSettings, oData) {
            oData.iLength = 10;
            oData.iStart = 0;
            //oData.oSearch.sSearch = db_search_term;
        },
        "fnPreDrawCallback": function () {
            jQuery('.armaff_affiliates_loading_grid').show();
        },
        "fnCreatedRow": function( nRow, aData, iDataIndex ) {
            jQuery(nRow).find('.arm_grid_action_btn_container').each(function () {
                jQuery(this).parent().addClass('armGridActionTD');
                jQuery(this).parent().attr('data-key', 'armGridActionTD');
            });
        },
        "fnDrawCallback":function(){
            jQuery('.armaff_affiliates_loading_grid').hide();
            if(message != '') {
                armToast(message, 'success');
                message = '';
            }
            if (jQuery.isFunction(jQuery().tipso)) {
                jQuery('.armhelptip').each(function () {
                    jQuery(this).tipso({
                        position: 'top',
                        size: 'small',
                        background: '#939393',
                        color: '#ffffff',
                        width: false,
                        maxWidth: 400,
                        useTitle: true
                    });
                });
            }
        }
    };


    if(__ARMVersion > "4.0")
    {
        dt_obj.language = {
            "searchPlaceholder": "Search",
            "search":"",
        };

        dt_obj.buttons = [];

        dt_obj.sDom = '<"H"CBfr>t<"footer"ipl>';

        dt_obj.fixedColumns = false;
    }


    oTables.dataTable(dt_obj);

}

// ]]>
</script>
<div class="wrap arm_page arm_affiliate_commision_setup_main_wrapper">
    <div class="content_wrapper arm_affiliate_commision_setup_content" id="content_wrapper">
        <div class="page_title"><?php _e('ARMember Plans Affiliates','ARM_AFFILIATE');?></div>
        <div class="arm_affiliate_commision_setup_plans">
            <div class="arm_subscription_plans_list" style="position:relative;top:10px;">
                <form method="GET" id="subscription_plans_list_form" class="data_grid_list" onsubmit="return false;">
                    <div id="armmainformnewlist">
                        <div class="arm_loading_grid armaff_plans_loading_grid" style="display: none;"><img src="<?php echo ARM_AFFILIATE_IMAGES_URL;?>/loader.gif" alt="Loading.."></div>
                        <table cellpadding="0" cellspacing="0" border="0" class="display arm_on_display" id="example_1">
                            <thead>
                                <tr>
                                <?php if(!empty($armaffplan_grid_columns)):?>
                                    <?php foreach($armaffplan_grid_columns as $key=>$title):?>
                                        <th data-key="<?php echo $key; ?>" class="arm_grid_th_<?php echo $key; ?> " ><?php echo $title; ?></th>
                                    <?php endforeach;?>
                                <?php endif;?>
                                    <th data-key="armGridActionTD" class="armGridActionTD"></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                            $form_result = $arm_subscription_plans->arm_get_all_subscription_plans();
                            if (!empty($form_result)) {

                                $armaff_currency = $arm_payment_gateways->arm_get_global_currency();

                                foreach($form_result as $planData) {
                                    $planObj = new ARM_Plan();
                                    $planObj->init((object) $planData);
                                    $planID = $planData['arm_subscription_plan_id'];

                                    $armplan_type = '';

                                    if( $planObj->is_recurring() && isset($planObj->options['payment_cycles']) && count($planObj->options['payment_cycles']) > 1 ) {
                                        $armplan_type = '<span class="arm_item_status_text active">' . __('Paid','ARM_AFFILIATE') . '</span><br/>' . __('Multiple Cycle','ARM_AFFILIATE');
                                    } else {
                                        $armplan_type = $planObj->plan_text(true);
                                    }

                                    $aff_referral_type_text = ''; $aff_referral_type_unit = '';$aff_referral_type = "";$aff_referral_rate = "";

                                    $is_referral_enable = isset($planObj->options['arm_affiliate_referral_disable']) ? $planObj->options['arm_affiliate_referral_disable'] : 0;
                                    if($is_referral_enable){
                                        $aff_referral_type = isset($planObj->options['arm_affiliate_referral_type']) ? $planObj->options['arm_affiliate_referral_type'] : '';
                                        $aff_referral_rate = isset($planObj->options['arm_affiliate_referral_rate']) ? $planObj->options['arm_affiliate_referral_rate'] : '';
                                    }

                                    if($aff_referral_type == 'percentage'){
                                        $aff_referral_type_text = __('Percentage', 'ARM_AFFILIATE');
                                        $aff_referral_type_unit = '%';
                                    } else if($aff_referral_type == 'fixed_rate'){
                                        $aff_referral_type_text = __('Fixed Rate', 'ARM_AFFILIATE');
                                        $aff_referral_type_unit = $armaff_currency;
                                    }

                                    if($aff_referral_rate != ''){
                                        $aff_referral_rate = $aff_referral_rate. ' ' . $aff_referral_type_unit;
                                    }

                                    $gridAction = "<div class='arm_grid_action_wrapper'>";
                                    $gridAction .= "<div class='arm_grid_action_btn_container'>";
                                    if (current_user_can('arm_manage_plans')) {
                                        $gridAction .= "<a href='javascript:void(0)' onclick='armaff_edit_plan_commision({$planID})' class='armhelptip' title='" . __('Edit Referral', 'ARM_AFFILIATE') . "' ><img src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_edit.png' onmouseover=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_edit_hover.png';\" onmouseout=\"this.src='" . ARM_AFFILIATE_IMAGES_URL . "/grid_edit.png';\" /></a>";
                                    }
                                    $gridAction .= "</div>";
                                    $gridAction .= "</div>";

                                    ?>
                                    <tr class="armaff_plan_row_<?php echo $planID;?>">
                                        <td class=""><?php echo $planID; ?></td>
                                        <td class=""><?php echo esc_html(stripslashes($planObj->name)); ?></td>
                                        <td><?php echo $armplan_type; ?></td>
                                        <td class="armaff_plan_type_<?php echo $planID;?>"><?php echo $aff_referral_type_text; ?></td>
                                        <td class="armaff_plan_rate_<?php echo $planID;?>"><?php echo $aff_referral_rate; ?></td>
                                        <td class="armGridActionTD"><?php echo $gridAction; ?></td>
                                    </tr>
                                <?php 
                                }//End Foreach
                            }
                            ?>
                            </tbody>
                        </table>
                        <div class="armclear"></div>
                        <input type="hidden" name="show_hide_columns" id="show_hide_columns" value="<?php _e('Show / Hide columns','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="search_grid" id="search_grid" value="<?php _e('Search','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="entries_grid" id="entries_grid" value="<?php _e('plans','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="show_grid" id="show_grid" value="<?php _e('Show','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="showing_grid" id="showing_grid" value="<?php _e('Showing','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="to_grid" id="to_grid" value="<?php _e('to','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="of_grid" id="of_grid" value="<?php _e('of','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php _e('No matching plans found','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php _e('No any subscription plan found.','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="filter_grid" id="filter_grid" value="<?php _e('filtered from','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php _e('total','ARM_AFFILIATE');?>"/>
                        <?php wp_nonce_field( 'arm_wp_nonce' );?>
                    </div>
                    <div class="footer_grid"></div>
                </form>
            </div>
            <div class="armclear"></div>
         </div>
    </div>
    <br>
    <div class="content_wrapper arm_affiliate_commision_setup_content" id="content_wrapper">
        <div class="page_title">
            <?php _e('ARMember User Commissions','ARM_AFFILIATE');?>
            <div class="arm_add_new_item_box">
                <a class="greensavebtn arm_add_affiliate_userwise_referral" href="javascript:void(0);">
                    <img align="absmiddle" src="<?php echo ARM_AFFILIATE_IMAGES_URL; ?>/add_new_icon.png" />
                    <span><?php _e('Add New User Commission', 'ARM_AFFILIATE') ?></span>
                </a>
            </div>
            <div class="armclear"></div>
            </div>
        <div class="arm_affiliate_commision_setup_plans">

            <div class="arm_affiliate_users_list" style="position:relative;top:10px;">
                <form method="GET" id="arm_affiliate_users_list_form" class="data_grid_list" onsubmit="return false;">
                    <div id="armmainformnewlist">
                        <div class="arm_loading_grid armaff_affiliates_loading_grid" style="display: none;"><img src="<?php echo ARM_AFFILIATE_IMAGES_URL;?>/loader.gif" alt="Loading.."></div>
                        <table cellpadding="0" cellspacing="0" border="0" class="display arm_on_display" id="example">
                            <thead>
                                <tr>
                                <?php if(!empty($affiliates_grid_columns)):?>
                                    <?php foreach($affiliates_grid_columns as $key=>$title):?>
                                        <th data-key="<?php echo $key; ?>" class="arm_grid_th_<?php echo $key; ?> " ><?php echo $title; ?></th>
                                    <?php endforeach;?>
                                <?php endif;?>
                                    <th data-key="armGridActionTD" class="armGridActionTD"></th>
                                </tr>
                            </thead>
                        </table>
                        <div class="armclear"></div>
                        <input type="hidden" name="search_grid" id="search_grid" value="<?php _e('Search','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="entries_grid" id="entries_grid" value="<?php _e('affiliates','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="show_grid" id="show_grid" value="<?php _e('Show','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="showing_grid" id="showing_grid" value="<?php _e('Showing','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="to_grid" id="to_grid" value="<?php _e('to','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="of_grid" id="of_grid" value="<?php _e('of','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php _e('No matching affiliate user found.','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php _e('No any affiliate user found.','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="filter_grid" id="filter_grid" value="<?php _e('filtered from','ARM_AFFILIATE');?>"/>
                        <input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php _e('total','ARM_AFFILIATE');?>"/>
                        <?php wp_nonce_field( 'arm_wp_nonce' );?>
                    </div>
                    <div class="footer_grid"></div>
                </form>
            </div>
            <div class="armclear"></div>
         </div>
    </div>
</div>
<?php $arm_affiliate_settings->arm_affiliate_get_footer(); ?>

<!--./******************** Edit Plan Commission Form ********************/.-->
<div class="arm_edit_plan_comission popup_wrapper" style="width: 850px;">
    <form method="post" action="#" id="arm_edit_plan_comission_wrapper_frm" class="arm_admin_form arm_edit_plan_comission_wrapper_frm">
        <table cellspacing="0" width="100%">
            <tr class="popup_wrapper_inner">
                <td class="arm_edit_plan_comission_close_btn arm_popup_close_btn"></td>
                <td class="popup_header"><?php _e('Edit Affiliate Settings', 'ARM_AFFILIATE');?></td>
                <input type="hidden" name="armaff_commision_plan_id" id="armaff_commision_plan_id" value="" />
                
                <td class="popup_content_text">
                    <img src="<?php echo ARM_AFFILIATE_IMAGES_URL.'/arm_loader.gif' ?>" id="armaff_edit_plan_commision_loader_img" class="armaff_edit_plan_commision_loader_img" style="margin: 20px auto; display: block;" />
                    <div class="arm_edit_plan_comission_content">
                        <?php echo $arm_affiliate_commision_setup->armaff_get_plan_commision_setup_popup_content(); ?>
                    </div>
                </td>
                
                <td class="popup_content_btn popup_footer">
                    <div class="popup_content_btn_wrapper">
                        <img src="<?php echo ARM_AFFILIATE_IMAGES_URL.'/arm_loader.gif' ?>" id="arm_loader_img" class="arm_loader_img" style="position: relative;top: 15px;display: none;float: <?php echo (is_rtl()) ? 'right' : 'left';?>;" width="20" height="20" />
                        <button class="arm_save_btn arm_edit_plan_comission_submit" type="submit" data-type="edit"><?php _e('Save', 'ARM_AFFILIATE') ?></button>
                        <button class="arm_cancel_btn arm_edit_plan_comission_close_btn" type="button"><?php _e('Cancel','ARM_AFFILIATE');?></button>
                    </div>
                    <?php wp_nonce_field( 'arm_wp_nonce' );?>
                </td>
            </tr>
        </table>
        <div class="armclear"></div>
    </form>
</div>
<!--./******************** Edit Plan Commission Form ********************/.-->

<!--./******************** Add New Affiliate User Referral Form ********************/.-->
<div class="armaff_select_affiliate_user popup_wrapper" style="width: 850px;">
    <form method="post" action="#" onsubmit="return false;" id="arm_add_user_referral_wrapper_frm" class="arm_admin_form arm_add_user_referral_wrapper_frm <?php echo is_rtl() ? 'arm_page_rtl' : ''; ?>">
        <table cellspacing="0" width="100%">
            <tr class="popup_wrapper_inner">    
                <td class="armaff_add_user_referral_close_btn arm_popup_close_btn"></td>
                <td class="popup_header"><span class="armaff_add_affiliate_title"><?php _e('Add New User Commission', 'ARM_AFFILIATE');?></span><span class="armaff_edit_affiliate_title" style="display: none;"><?php _e('Edit User Commission', 'ARM_AFFILIATE');?></span></td>
                
                <td class="popup_content_text">
                    <table class="arm_table_label_affiliate">
                        
                        <tr class="form-field exists_user_section" id="armaff_select_affiliate">
                            <th class="arm-form-table-label armaff_right_text"><?php _e('Select User To Give Commission', 'ARM_AFFILIATE'); ?></th>
                            <td class="arm-form-table-content">
                                <input type='hidden' id="arm_aff_action" class="arm_aff_action" name="arm_aff_action" value="add" />
                                <input type='hidden' id="arm_affiliate_user_id" class="arm_affiliate_user_id_change_input" name="arm_affiliate_user_id" value="" />
                                <input type='hidden' id="arm_affiliate_id" class="arm_affiliate_id_change_input" name="arm_affiliate_id" value="" />
                                <dl class="arm_selectbox arm_affiliate_commision_form" style="margin-right:0px;">
                                    <dt style="width: 210px;">
                                        <span></span><input type="text" style="display:none;" value="<?php _e('Select Affiliate User', 'ARM_AFFILIATE'); ?>" class="arm_autocomplete" />
                                        <i class="armfa armfa-caret-down armfa-lg"></i>
                                    </dt>
                                    <dd>
                                        <ul data-id="arm_affiliate_user_id">
                                            <li data-label="Select Affiliate User" data-value="" data-affiliate="" data-type="Select Affiliate User"><?php _e('Select Affiliate User', 'ARM_AFFILIATE'); ?></li>
                                            <?php if (!empty($all_members)): ?>
                                                <?php foreach ($all_members as $user):
                                                    if(isset($all_affiliate_ids[$user->ID])){
                                                ?>
                                                    <li data-label="<?php echo $user->user_login;?>" data-value="<?php echo $user->ID;?>" data-type="<?php echo $user->user_login;?>" data-affiliate="<?php echo $all_affiliate_ids[$user->ID]; ?>"><?php echo $user->user_login;?></li>
                                                <?php } endforeach; ?>
                                            <?php endif; ?>
                                        </ul>
                                    </dd>
                                </dl>
                                <span id="arm_user_ids_error" class="arm_error_msg arm_user_ids_error" style="display:none;"><?php _e('Please select affiliate user.', 'ARM_AFFILIATE');?></span>
                            </td>
                        </tr>
                        <tr class="form-field exists_user_section" id="armaff_edit_affiliate_name" style="display: none;">
                            <th class="arm-form-table-label armaff_right_text"><?php _e('Username', 'ARM_AFFILIATE'); ?></th>
                            <td class="arm-form-table-content armaff_affiliate_name"></td>
                        </tr>
                        <tr>
                            <img src="<?php echo ARM_AFFILIATE_IMAGES_URL.'/arm_loader.gif' ?>" id="armaff_get_affiliate_commision_loader_img" class="armaff_get_affiliate_commision_loader_img" style="margin: 20px auto; display: none;" />
                            <td class="armaff_add_affiliate_commision_section" colspan="2">
                                <?php echo $arm_affiliate_commision_setup->armaff_get_affiliate_commision_setup_popup_content(); ?>
                            </td>
                        </tr>
                    </table>
                    <div class="armclear"></div>
                </td>
                
                <td class="popup_content_btn popup_footer">
                    <div class="popup_content_btn_wrapper">
                        <img src="<?php echo ARM_AFFILIATE_IMAGES_URL.'/arm_loader.gif' ?>" id="arm_affiliate_loader_img" class="arm_affiliate_loader_img" style="position: relative;top: 15px;display: none;float: <?php echo (is_rtl()) ? 'right' : 'left';?>;" width="20" height="20" />
                        <button class="arm_save_btn arm_submit_affiliate_user_referral" type="button" data-type="add"><?php _e('Save', 'ARM_AFFILIATE') ?></button>
                        <button class="arm_cancel_btn armaff_add_user_referral_close_btn" type="button"><?php _e('Cancel','ARM_AFFILIATE');?></button>
                    </div>
                    <?php wp_nonce_field( 'arm_wp_nonce' );?>
                </td>
            </tr>
        </table>
        <div class="armclear"></div>
    </form>
</div>
<!--./******************** Add New Affiliate User Referral Form ********************/.-->