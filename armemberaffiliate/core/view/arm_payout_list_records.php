<?php 
global $wpdb, $arm_global_settings, $arm_subscription_plans, $arm_version;
$date_format = $arm_global_settings->arm_get_wp_date_format();

$filter_plan_id = (!empty($_REQUEST['plan_id']) && $_REQUEST['plan_id'] != '0') ? $_REQUEST['plan_id'] : '';
$filter_form_id = (!empty($_POST['form_id']) && $_POST['form_id'] != '0') ? $_POST['form_id'] : '0';
$filter_search = (!empty($_POST['search'])) ? $_POST['search'] : '';

/* **************./Begin Set Member Grid Fields/.************** */
$grid_columns = array(
    'affiliate_user' => __('Affiliate User', 'ARM_AFFILIATE'),
    'total' => __('Total Earning', 'ARM_AFFILIATE'),
    'paid' => __('Paid Amount', 'ARM_AFFILIATE'),
    'due' => __('Due Amount', 'ARM_AFFILIATE')
);

/* * *************./End Set Member Grid Fields/.************** */

?>
<script type="text/javascript" charset="utf-8">
// <![CDATA[
    function show_grid_loader() {
        jQuery('.arm_loading_grid').show();
    }
    jQuery(document).ready(function () {
        arm_load_membership_grid(false);
    });
    function arm_load_membership_grid_after_filtered() {
        jQuery('#example').dataTable().fnDestroy();
        arm_load_membership_grid(true);
    }
    function arm_load_membership_grid(is_filtered) {
        var search_term = jQuery("#armmanagesearch_new").val();
        var filtered_id = jQuery("#arm_subs_filter").val();
        var status_mode_id = jQuery("#arm_mode_filter").val();
        var db_filter_id = (typeof filtered_id !== 'undefined' && filtered_id !== '') ? filtered_id : '';
        var db_status_mode = (typeof status_mode_id !== 'undefined' && status_mode_id !== '') ? status_mode_id : '';
        var db_search_term = (typeof search_term !== 'undefined' && search_term !== '') ? search_term : '';
        var filtered_data = (typeof is_filtered !== 'undefined' && is_filtered !== false) ? true : false;
        var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
        var _wpnonce = jQuery('input[name="_wpnonce"]').val();

        var __ARMVersion = '<?php echo $arm_version;?>';

        var oTables = jQuery('#example');

        var dt_obj = {
            "oLanguage": {
                "sProcessing": show_grid_loader(),
                "sEmptyTable": "No any payout found.",
                "sZeroRecords": "No matching payout found."
            },
            "bProcessing": false,
            "bServerSide": true,
            "sAjaxSource": ajax_url,
            "sServerMethod": "POST",
            "fnServerParams": function (aoData) {
                aoData.push({'name': 'action', 'value': 'arm_payouts_list'});
                aoData.push({'name': 'filter_plan_id', 'value': db_filter_id});
                aoData.push({'name': 'filter_status_id', 'value': db_status_mode});
                aoData.push({'name': 'sSearch', 'value': db_search_term});
                aoData.push({'name': 'sColumns', 'value':null});
                aoData.push({'name': '_wpnonce', 'value':_wpnonce});
              
            },
            "bRetrieve": false,
            "sDom": '<"H"Cfr>t<"F"ipl>',
            "sPaginationType": "four_button",
            "bJQueryUI": true,
            "bPaginate": true,
            "bAutoWidth": true,
            "sScrollX": "100%",
            "bScrollCollapse": true,
            "aoColumnDefs": [
                {"sType": "html", "bVisible": false, "aTargets": []},
                {"sClass": "center", "aTargets": [1,2,3] },
                {"sClass": "left", "aTargets": [0] },
                {"bSortable": false, "aTargets": [2]}
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
                arm_selectbox_init();
                filtered_data = false;
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
                arm_load_affiliate_grid_after_filtered();
                return false;
            }
        });
    }
// ]]>
</script>
<style>
    .dataTables_scrollHeadInner, .dataTables_scrollHeadInner table {
        width:100% !important;
    }
    .dataTables_scrollBody, .dataTables_scrollBody table{
        width: 100% !important;
    }
</style>

<div class="arm_members_list">
    <form method="POST" id="arm_user_export_payout_hisroty" class="arm_user_export_payout_hisroty">
        <input type='hidden' id="arm_action" class="arm_action" name="arm_action" value="payouts_export_csv" />
        <input type="hidden" id="arm_affiliate_user_id" name="arm_affiliate_user_id" class="arm_affiliate_user_id" value="" />
    </form>
	<form method="GET" id="arm_member_list_form" class="data_grid_list" enctype="multipart/form-data">
		<input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? $_REQUEST['page'] : '' ; ?>" />
		<input type="hidden" name="armaction" value="list" />
                <div class="arm_datatable_filters">
                    <div class="arm_dt_filter_block">
                        <label class="arm_datatable_searchbox"><input type="text" placeholder="<?php _e('Affiliate User', 'ARM_AFFILIATE');?>" id="armmanagesearch_new" value="<?php echo $filter_search;?>" tabindex="-1" onkeydown="if (event.keyCode == 13) return false;"></label>
                        <!--/====================Begin Filter By Member Form Box====================/-->
                        <input type="hidden" id="arm_form_filter" class="arm_form_filter" value="<?php echo $filter_form_id;?>" />
                        <!--/====================End Filter By Member Form Box====================/-->
                    </div>
                    <div class="arm_dt_filter_block arm_dt_filter_submit">
                        <input type="button" class="armemailaddbtn" id="arm_member_grid_filter_btn" onClick="arm_load_membership_grid_after_filtered();" value="<?php _e('Apply', 'ARM_AFFILIATE'); ?>"/>
                    </div>

                    <button id="arm_user_export_btn_csv" class="armemailaddbtn export_popup" name="arm_action" value="payouts_export_csv" type="button" style="min-width: 120px;"><?php _e('Export To CSV', 'ARM_AFFILIATE');?></button>
                    <div class="armclear"></div>
                </div>
		<div id="armmainformnewlist" class="arm_filter_grid_list_container">
			<div class="arm_loading_grid" style="display: none;"><img src="<?php echo ARM_AFFILIATE_IMAGES_URL;?>/loader.gif" alt="Loading.."></div>
			<div class="response_messages"></div>
			<div class="armclear"></div>
			<table cellpadding="0" cellspacing="0" border="0" class="display" id="example">
				<thead>
					<tr>
						<?php if(!empty($grid_columns)):?>
							<?php foreach($grid_columns as $key=>$title):?>
                                                            <th data-key="<?php echo $key; ?>" class="arm_grid_th_<?php echo $key; ?>" ><?php echo $title; ?></th>
							<?php endforeach;?>
						<?php endif;?>
                                                <th data-key="armGridActionTD" class="armGridActionTD"></th>
					</tr>
				</thead>
			</table>
			<div class="armclear"></div>
			
			<input type="hidden" name="search_grid" id="search_grid" value="<?php _e('Search','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="entries_grid" id="entries_grid" value="<?php _e('payout','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="show_grid" id="show_grid" value="<?php _e('Show','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="showing_grid" id="showing_grid" value="<?php _e('Showing','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="to_grid" id="to_grid" value="<?php _e('to','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="of_grid" id="of_grid" value="<?php _e('of','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php _e('No matching payout found.','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php _e('No any payout found.','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="filter_grid" id="filter_grid" value="<?php _e('filtered from','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php _e('total','ARM_AFFILIATE');?>"/>
            <?php wp_nonce_field( 'arm_wp_nonce' );?>
		</div>
		<div class="footer_grid"></div>
	</form>
</div>