<?php
global $wpdb, $arm_global_settings, $arm_subscription_plans, $arm_version;

/* **************./Begin Set Member Grid Fields/.************** */
$grid_columns = array(
    'User_Name' => __('Username', 'ARM_DIRECT_LOGINS'),
    'Email' => __('Email', 'ARM_DIRECT_LOGINS'),
    'Role' => __('User Role', 'ARM_DIRECT_LOGINS'),
    'Last_Logged_In' => __('Last Logged In', 'ARM_DIRECT_LOGINS'),
    'Active' => __('Active', 'ARM_DIRECT_LOGINS'),
    'Expiry' => __('Link Status', 'ARM_DIRECT_LOGINS'),
);

?>

<style type="text/css">
    .buttons-colvis{ visibility: hidden; }
</style>

<script type="text/javascript" charset="utf-8">
// <![CDATA[
    function show_grid_loader() {
        jQuery('.arm_loading_grid').show();
    }
    jQuery(document).ready(function () {
        arm_load_direct_logins_grid(false);
    });
    function arm_load_direct_logins_grid_after_filtered(msg) {
        jQuery('#example').dataTable().fnDestroy();
        arm_load_direct_logins_grid(true, msg);
    }
    function arm_load_direct_logins_grid(is_filtered, msg) {
        var search_term = jQuery("#armmanagesearch_new").val();        
        var db_search_term = (typeof search_term !== 'undefined' && search_term !== '') ? search_term : '';
        var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
        var _wpnonce = jQuery('input[name="_wpnonce"]').val();
        jQuery('#csv_sSearch').val(search_term);
        var __ARM_NO_FOUNT = '<?php echo addslashes(__('No any direct login found.','ARM_DIRECT_LOGINS')); ?>';
        var __ARM_NO_MATCHING = '<?php echo addslashes(__('No matching direct login found.','ARM_DIRECT_LOGINS')); ?>';
        var __ARMVersion = '<?php echo $arm_version;?>';
        var oTables = jQuery('#example');

        var dt_obj = {
             "oLanguage": {
                "sProcessing": show_grid_loader(),
                "sEmptyTable": __ARM_NO_FOUNT,
                "sZeroRecords": __ARM_NO_MATCHING
            },
            "sDom": '<"H"Cfr>t<"F"ipl>',
            "bProcessing": false,
            "bServerSide": true,
            "sAjaxSource": ajax_url,
            "sServerMethod": "POST",
            "fnServerParams": function (aoData) {
                aoData.push({'name': 'action', 'value': 'arm_direct_logins_list'});
                aoData.push({'name': 'sColumns', 'value':null});
                aoData.push({'name': '_wpnonce', 'value':_wpnonce});
            },
            "bRetrieve": false,
            "sPaginationType": "four_button",
            "bJQueryUI": true,
            "bPaginate": true,
            "bAutoWidth": false,
            "sScrollX": "100%",
            "bScrollCollapse": true,
            "oColVis": {
                "aiExclude": [0]
            },
            "aoColumnDefs": [
                {"sType": "html", "bVisible": false, "aTargets": []}, 
                {"sClass": "center", "aTargets": [] },
                {"sClass": "left", "aTargets": [0,1,2,3,4] },
                {"bSortable": false, "aTargets": [0,3,4]}
            ],
            "order":[[2,'desc']],
            "fixedColumns": true,
            "bStateSave": true,
            "iCookieDuration": 60 * 60,
            "sCookiePrefix": "arm_datatable_",
            "aLengthMenu": [10, 25, 50, 100, 150, 200],
            "fnStateSave": function (oSettings, oData) {
                oData.aaSorting = [];
                oData.abVisCols = [];
                oData.aoSearchCols = [];
                this.oApi._fnCreateCookie(
                    oSettings.sCookiePrefix + oSettings.sInstance,
                    this.oApi._fnJsonString(oData),
                    oSettings.iCookieDuration,
                    oSettings.sCookiePrefix,
                    oSettings.fnCookieCallback
                );
            },
            "fnStateLoadParams": function (oSettings, oData) {
                oData.iLength = 10;
                oData.iStart = 0;
                //oData.oSearch.sSearch = db_search_term;
            },
            "fnPreDrawCallback": function () {
                jQuery('.arm_loading_grid').show();
            },
            "fnCreatedRow": function( nRow, aData, iDataIndex ) {
                jQuery(nRow).find('.arm_grid_action_btn_container').each(function () {
                    jQuery(this).parent().addClass('armGridActionTD');
                    jQuery(this).parent().attr('data-key', 'armGridActionTD');
                });
            },
            "fnDrawCallback": function (oSettings) {
                jQuery('.arm_loading_grid').hide();
                jQuery("#cb-select-all-1").removeAttr("checked");
                //arm_selectbox_init();
                
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
                if(typeof msg != 'undefined'){
                    if(msg!='')
                    {
                        armToast(msg, 'success');
                        msg = '';
                    }
                }
            }
        };


        if(__ARMVersion > "4.0")
        {
            dt_obj.language = {
                "searchPlaceholder": "Search",
                "search":"",
            };

            dt_obj.buttons = [{
                "extend":"colvis",
                "columns":":not(.noVis)",
            }];

            dt_obj.sDom = '<"H"CBfr>t<"footer"ipl>';

            dt_obj.fixedColumns = false;
        }


        oTables.dataTable(dt_obj);


        var filter_box = jQuery('#arm_filter_wrapper').html();
        jQuery('div#example_filter').parent().append(filter_box);
        jQuery('div#example_filter').hide();
        jQuery('#arm_filter_wrapper').remove();
        jQuery('#armmanagesearch_new').bind('keyup', function (e) {
            e.stopPropagation();
            if (e.keyCode == 13) {
                arm_load_direct_logins_grid_after_filtered();
                return false;
            }
        });
    }
    
// ]]>
</script>

<div class="arm_members_list">
    <form method="POST" id="arm_referral_list_form" class="data_grid_list" enctype="multipart/form-data">
        <input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? $_REQUEST['page'] : '' ; ?>" />
        <input type="hidden" name="armaction" value="list" />
        <div id="armmainformnewlist" class="arm_filter_grid_list_container">
            <div class="arm_loading_grid" style="display: none;"><img src="<?php echo ARM_DIRECT_LOGINS_IMAGES_URL;?>/loader.gif" alt="Loading.."></div>
            <div class="response_messages"></div>
            <div class="armclear"></div>
            <table cellpadding="0" cellspacing="0" border="0" class="display" id="example">
                <thead>
                    <tr>
                        <?php if(!empty($grid_columns)):?>
                            <?php foreach($grid_columns as $key=>$title):?>
                                <th data-key="<?php echo $key; ?>" class="arm_grid_th_<?php echo $key; ?> " style="text-align:left;" ><?php echo $title; ?></th>
                            <?php endforeach;?>
                        <?php endif;?>
                        <th data-key="armGridActionTD" class="armGridActionTD"></th>
                    </tr>
                </thead>
            </table>
            <div class="armclear"></div>

            <input type="hidden" name="search_grid" id="search_grid" value="<?php _e('Search','ARM_DIRECT_LOGINS');?>"/>
            <input type="hidden" name="entries_grid" id="entries_grid" value="<?php _e('Direct Login','ARM_DIRECT_LOGINS');?>"/>
            <input type="hidden" name="show_grid" id="show_grid" value="<?php _e('Show','ARM_DIRECT_LOGINS');?>"/>
            <input type="hidden" name="showing_grid" id="showing_grid" value="<?php _e('Showing','ARM_DIRECT_LOGINS');?>"/>
            <input type="hidden" name="to_grid" id="to_grid" value="<?php _e('to','ARM_DIRECT_LOGINS');?>"/>
            <input type="hidden" name="of_grid" id="of_grid" value="<?php _e('of','ARM_DIRECT_LOGINS');?>"/>
            <input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php _e('No matching direct login found.','ARM_DIRECT_LOGINS');?>"/>
            <input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php _e('No any direct login found.','ARM_DIRECT_LOGINS');?>"/>
            <input type="hidden" name="filter_grid" id="filter_grid" value="<?php _e('filtered from','ARM_DIRECT_LOGINS');?>"/>
            <input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php _e('total','ARM_DIRECT_LOGINS');?>"/>
            <?php wp_nonce_field( 'arm_wp_nonce' );?>
        </div>
        <div class="footer_grid"></div>
    </form>
</div>
<div class="arm_member_view_detail_container"></div>