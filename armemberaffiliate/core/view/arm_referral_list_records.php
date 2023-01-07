<?php
global $wpdb, $arm_global_settings, $arm_subscription_plans, $arm_affiliate, $arm_version;
$date_format = $arm_global_settings->arm_get_wp_date_format();
$all_plans = $arm_subscription_plans->arm_get_all_subscription_plans();

$filter_plan_id = (!empty($_REQUEST['plan_id']) && $_REQUEST['plan_id'] != '0') ? $_REQUEST['plan_id'] : '';
$status = (isset($_REQUEST['status']) && $_REQUEST['status'] != '') ? $_REQUEST['status'] : '';
$filter_form_id = (!empty($_POST['form_id']) && $_POST['form_id'] != '0') ? $_POST['form_id'] : '0';
$filter_search = (!empty($_POST['search'])) ? $_POST['search'] : '';
$start_date = (!empty($_REQUEST['start_date']) && $_REQUEST['start_date'] != '0') ? $_REQUEST['start_date'] : '';
$end_date = (!empty($_REQUEST['end_date']) && $_REQUEST['end_date'] != '0') ? $_REQUEST['end_date'] : '';

$armaffiliate_active_woocommerce = $arm_affiliate->arm_affiliate_is_woocommerce_active();

/* **************./Begin Set Member Grid Fields/.************** */
$grid_columns = array(
    'affiliate_user' => __('Affiliate User', 'ARM_AFFILIATE'),
    'amount' => __('Commission', 'ARM_AFFILIATE'),
    'plan_name' => __('Membership Plan', 'ARM_AFFILIATE'),
    'reference_user' => __('Referred User', 'ARM_AFFILIATE'),
    'date' => __('Referral Date', 'ARM_AFFILIATE'),
    'status' => __('Status', 'ARM_AFFILIATE')
);

$exclude_sortable = ",6,7";
$left_columns = ",5,6,7";
$center_columns = "";

if($armaffiliate_active_woocommerce){
    $grid_columns = array(
        'affiliate_user' => __('Affiliate User', 'ARM_AFFILIATE'),
        'amount' => __('Commission', 'ARM_AFFILIATE'),
        'plan_name' => __('Membership Plan', 'ARM_AFFILIATE'),
        'reference_user' => __('Referred User', 'ARM_AFFILIATE'),
        'order_id' => __('Order', 'ARM_AFFILIATE'),
        'date' => __('Referral Date', 'ARM_AFFILIATE'),
        'status' => __('Status', 'ARM_AFFILIATE')
    );

    $exclude_sortable = ",7,8";
    $left_columns = ",6,7,8";
    $center_columns = ",5";
}

$default_columns = $grid_columns;
$user_meta_keys = array();

if (!empty($user_meta_keys)) {
    $exclude_keys = array('user_pass', 'repeat_pass', 'rememberme', 'remember_me', 'section', 'html');
    $exclude_keys = array_merge($exclude_keys, array_keys($grid_columns));
    foreach ($user_meta_keys as $umkey => $val) {
        if (!in_array($umkey, $exclude_keys)) {
            $grid_columns[$umkey] = $val['label'];
        }
    }
}
/* * *************./End Set Member Grid Fields/.************** */
$user_id = get_current_user_id();
$members_show_hide_column = array();
$column_hide = "";
$totalCount = count($grid_columns) + 2;
$totalDefaultCount = count($default_columns);
if (!empty($members_show_hide_column)) {
    
    $i = 1;
    foreach ($members_show_hide_column as $value) {
        if ($totalCount > $i) {
            if ($value != 1) {
                $column_hide = $column_hide . $i . ',';
            }
        }
        $i++;
    }
} else {
    $column_hide = '    ';
    $i = 2;
    foreach ($grid_columns as $value) {
        if ($totalDefaultCount < $i) {
            $column_hide = $column_hide . $i . ',';
        }
        $i++;
    }
}

$plansLists = '<li data-label="' . __('Select Plan', 'ARM_AFFILIATE') . '" data-value="">' . __('Select Plan', 'ARM_AFFILIATE') . '</li>';
if (!empty($all_plans)) {
    foreach ($all_plans as $p) {
        $p_id = $p['arm_subscription_plan_id'];
        if ($p['arm_subscription_plan_status'] == '1') {
            $plansLists .= '<li data-label="' . stripslashes(esc_attr($p['arm_subscription_plan_name'])) . '" data-value="' . $p_id . '">' . stripslashes(esc_attr($p['arm_subscription_plan_name'])) . '</li>';
        }
    }
}
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
        var start_date = jQuery("#start_date").val();
        var end_date = jQuery("#end_date").val();
        var db_filter_id = (typeof filtered_id !== 'undefined' && filtered_id !== '') ? filtered_id : '';
        var db_status_mode = (typeof status_mode_id !== 'undefined' && status_mode_id !== '') ? status_mode_id : '';
        var db_start_date = (typeof start_date !== 'undefined' && start_date !== '') ? start_date : '';
        var db_end_date = (typeof end_date !== 'undefined' && end_date !== '') ? end_date : '';
        var db_search_term = (typeof search_term !== 'undefined' && search_term !== '') ? search_term : '';
        var filtered_data = (typeof is_filtered !== 'undefined' && is_filtered !== false) ? true : false;
        var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
        var _wpnonce = jQuery('input[name="_wpnonce"]').val();
        jQuery('#csv_sSearch').val(search_term);
        jQuery('#csv_filter_plan_id').val(db_filter_id);
        jQuery('#csv_filter_status_id').val(db_status_mode);
        jQuery('#csv_start_date').val(db_start_date);
        jQuery('#csv_end_date').val(db_end_date);

        var __ARMVersion = '<?php echo $arm_version;?>';

        var oTables = jQuery('#example');
    
        var dt_obj = {
            "oLanguage": {
                "sProcessing": show_grid_loader(),
                "sEmptyTable": "No any referral found.",
                "sZeroRecords": "No matching referral found."
            },
            "bProcessing": false,
            "bServerSide": true,
            "sAjaxSource": ajax_url,
            "sServerMethod": "POST",
            "fnServerParams": function (aoData) {
                aoData.push({'name': 'action', 'value': 'arm_referrals_list'});
                aoData.push({'name': 'filter_plan_id', 'value': db_filter_id});
                aoData.push({'name': 'filter_status_id', 'value': db_status_mode});
                aoData.push({'name': 'start_date', 'value': db_start_date});
                aoData.push({'name': 'end_date', 'value': db_end_date});
                aoData.push({'name': 'sSearch', 'value': db_search_term});
                aoData.push({'name': 'sColumns', 'value':null});
                aoData.push({'name': '_wpnonce', 'value':_wpnonce});
            },
            "bRetrieve": false,
            "sDom": '<"H"Cfr>t<"F"ipl>',
            "sPaginationType": "four_button",
            "bJQueryUI": true,
            "bPaginate": true,
            "bAutoWidth": false,
            "sScrollX": "100%",
            "bScrollCollapse": true,
            "oColVis": {
                "aiExclude": [0, ]
            },
            "aoColumnDefs": [
                {"sType": "html", "bVisible": false, "aTargets": []},
                {"sClass": "center", "aTargets": [0 <?php echo ( isset($center_columns) ) ? $center_columns : ''; ?>]},
                {"sClass": "left", "aTargets": [1,2,3,4 <?php echo ( isset($left_columns) ) ? $left_columns : ''; ?>]},
                {"bSortable": false, "aTargets": [0,3,4 <?php echo ( isset($exclude_sortable) ) ? $exclude_sortable : ''; ?>]}
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

            dt_obj.buttons = [];

            dt_obj.sDom = '<"H"CBfr>t<"footer"ipl>';

            dt_obj.fixedColumns = false;
        }


        oTables.dataTable(dt_obj);


        var filter_box = jQuery('#arm_filter_wrapper_after_filter').html();
        jQuery('div#example_filter').parent().append(filter_box);
        jQuery('div#example_filter').hide();
        jQuery('#arm_filter_wrapper').remove();
        jQuery('#armmanagesearch_new').bind('keyup', function (e) {
            e.stopPropagation();
            if (e.keyCode == 13) {
                arm_load_membership_grid_after_filtered();
                return false;
            }
        });
    }
// ]]>
</script>
<div class="arm_filter_wrapper" id="arm_filter_wrapper_after_filter" style="display:none;">
    <div class="arm_datatable_filters_options">
        <div class='sltstandard'>
            <input type='hidden' id='arm_manage_bulk_action1' name="action1" value="-1" />
            <dl class="arm_selectbox">
                <dt style="width: 150px;"><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                <dd>
                    <ul data-id="arm_manage_bulk_action1">
                        <li data-label="<?php _e('Bulk Actions', 'ARM_AFFILIATE'); ?>" data-value="-1"><?php _e('Bulk Actions', 'ARM_AFFILIATE'); ?></li>
                        <li data-label="<?php _e('Delete', 'ARM_AFFILIATE'); ?>" data-value="delete_member"><?php _e('Delete', 'ARM_AFFILIATE'); ?></li>
                    </ul>
                </dd>
            </dl>
        </div>
        <input type="submit" id="doaction1" class="armbulkbtn armemailaddbtn" value="<?php _e('Go', 'ARM_AFFILIATE'); ?>"/>
    </div>
</div>
<div class="arm_members_list">
	<div class="arm_filter_wrapper" id="arm_filter_wrapper" style="display:none;">
		<div class="arm_datatable_filters_options">
			<div class='sltstandard'>
				<input type='hidden' id='arm_manage_bulk_action1' name="action1" value="-1" />
				<dl class="arm_selectbox">
					<dt style="width: 150px;"><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
					<dd>
						<ul data-id="arm_manage_bulk_action1">
							<li data-label="<?php _e('Bulk Actions','ARM_AFFILIATE'); ?>" data-value="-1"><?php _e('Bulk Actions','ARM_AFFILIATE'); ?></li>
							<li data-label="<?php _e('Delete', 'ARM_AFFILIATE');?>" data-value="delete_member"><?php _e('Delete', 'ARM_AFFILIATE');?></li>
						</ul>
					</dd>
				</dl>
			</div>
			<input type="submit" id="doaction1" class="armbulkbtn armemailaddbtn" value="<?php _e('Go','ARM_AFFILIATE');?>"/>
		</div>
	</div>
        <!--/================ Begin Export Data CSV ======================/ -->
        <form name='export_data_to_csv' id='export_data_to_csv' method="POST" enctype="multipart/form-data">
            <input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? $_REQUEST['page'] : '' ; ?>" />
            <input type="hidden" name="sSearch" id="csv_sSearch" value=""/>
            <input type="hidden" name="filter_plan_id" id="csv_filter_plan_id" value="<?php echo $filter_plan_id;?>"/>
            <input type="hidden" name="filter_status_id" id="csv_filter_status_id" value="<?php echo $status; ?>"/>
            <input type="hidden" name="start_date" id="csv_start_date" value="<?php echo $start_date; ?>"/>
            <input type="hidden" name="end_date" id="csv_end_date" value="<?php echo $end_date; ?>"/>
            <input type="hidden" name="arm_action" id="arm_action" value="referrals_export_csv"/>
        </form>
        <!--/================ End Export Data CSV ======================/ -->
	<form method="POST" id="arm_referral_list_form" class="data_grid_list" onsubmit="return arm_referral_list_form_bulk_action();" enctype="multipart/form-data">
		<input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? $_REQUEST['page'] : '' ; ?>" />
		<input type="hidden" name="armaction" value="list" />
		<div class="arm_datatable_filters">
			<div class="arm_dt_filter_block">
				<label class="arm_datatable_searchbox referral_searchbox"><input type="text" placeholder="<?php _e('Search', 'ARM_AFFILIATE');?>" id="armmanagesearch_new" value="<?php echo $filter_search;?>" tabindex="-1" onkeydown="if (event.keyCode == '13') { arm_load_membership_grid_after_filtered(false); return false; }"></label>
				<!--/====================Begin Filter By Plan Box====================/ -->
				<?php if (!empty($all_plans)):?>
				<div class="arm_datatable_filter_item">
                                        <span><label><?php _e('Membership', 'ARM_AFFILIATE')?>:</label></span><br/>
					<input type="hidden" id="arm_subs_filter" class="arm_subs_filter" value="<?php echo $filter_plan_id;?>" />
					<dl class="arm_multiple_selectbox">
						<dt style="width: 180px;"><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
						<dd>
							<ul data-id="arm_subs_filter" data-placeholder="<?php _e('Select Plans', 'ARM_AFFILIATE');?>">
								<?php foreach ($all_plans as $plan): ?>
								<li data-label="<?php echo stripslashes(esc_attr($plan['arm_subscription_plan_name'])); ?>" data-value="<?php echo $plan['arm_subscription_plan_id']; ?>"><input type="checkbox" class="arm_icheckbox" value="<?php echo $plan['arm_subscription_plan_id'];?>"/><?php echo stripslashes($plan['arm_subscription_plan_name']); ?></li>
								<?php endforeach;?>
							</ul>
						</dd>
					</dl>
				</div>
                                <div class="arm_datatable_filter_item">
                                    <span><label><?php _e('Status', 'ARM_AFFILIATE') ?>:</label></span><br/>
                                    <input type="hidden" id="arm_mode_filter" class="arm_mode_filter" value="<?php echo $status; ?>" />
                                    <dl class="arm_multiple_selectbox">
                                        <dt style="width: 120px;"><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                                        <dd>
                                            <ul data-id="arm_mode_filter" data-placeholder="<?php _e('Select Status', 'ARM_AFFILIATE'); ?>">
                                                <li data-label="<?php _e('Accepted', 'ARM_AFFILIATE'); ?>" data-value="1"><input type="checkbox" class="arm_icheckbox" value="2"/><?php _e('Accepted', 'ARM_AFFILIATE'); ?></li>
                                                <li data-label="<?php _e('Pending', 'ARM_AFFILIATE'); ?>" data-value="0"><input type="checkbox" class="arm_icheckbox" value="0"/><?php _e('Pending', 'ARM_AFFILIATE'); ?></li>
                                                <li data-label="<?php _e('Rejected', 'ARM_AFFILIATE'); ?>" data-value="3"><input type="checkbox" class="arm_icheckbox" value="3"/><?php _e('Rejected', 'ARM_AFFILIATE'); ?></li>
                                            </ul>
                                        </dd>
                                    </dl>
                                </div>
				<?php endif;?>
				<!--/====================End Filter By Plan Box====================/-->
                                
				<!--/====================Begin Filter By Date Form Box====================/-->
                                <div class="arm_datatable_filter_item arm_import_export_date_fields">
                                    <span><label><?php _e('Start Date', 'ARM_AFFILIATE') ?>:</label></span><br/>
                                    <input type="text" name="start_date" id="start_date" placeholder="<?php _e('Select Date', 'ARM_AFFILIATE');?>" data-dateformat='<?php echo arm_wp_date_format_to_bootstrap_datepicker(); ?>' class="arm_datepicker" value="<?php echo $start_date; ?>">
                                </div>
                                <div class="arm_datatable_filter_item arm_import_export_date_fields">
                                    <span><label><?php _e('End Date', 'ARM_AFFILIATE') ?>:</label></span><br/>
                                    <input type="text" name="end_date" id="end_date" placeholder="<?php _e('Select Date', 'ARM_AFFILIATE');?>" data-dateformat='<?php echo arm_wp_date_format_to_bootstrap_datepicker(); ?>' class="arm_datepicker" value="<?php echo $end_date; ?>">
                                </div>
                                <!--/====================End Filter By date Box====================/-->
				<!--/====================Begin Filter By referral Form Box====================/-->
				<input type="hidden" id="arm_form_filter" class=" arm_form_filter" value="<?php echo $filter_form_id;?>" />
				<!--/====================End Filter By referral Form Box====================/-->
                                <div class="arm_dt_filter_block arm_dt_filter_submit arm_datatable_filter_item">
                                    <input type="button" class="armemailaddbtn" id="arm_member_grid_filter_btn" onClick="arm_load_membership_grid_after_filtered();" value="<?php _e('Apply', 'ARM_AFFILIATE'); ?>"/>
                                </div>
                                <input type="button" class="armemailaddbtn arm_datatable_filter_item" id="arm_user_export_btn_csv" onClick="export_referrals_csv();" value="<?php _e('Export To CSV', 'ARM_AFFILIATE');?>"/>
			</div>
			<div class="armclear"></div>
		</div>
		<div id="armmainformnewlist" class="arm_filter_grid_list_container">
			<div class="arm_loading_grid" style="display: none;"><img src="<?php echo ARM_AFFILIATE_IMAGES_URL;?>/loader.gif" alt="Loading.."></div>
			<div class="response_messages"></div>
			<div class="armclear"></div>
			<table cellpadding="0" cellspacing="0" border="0" class="display" id="example">
				<thead>
					<tr>
						<th class="center cb-select-all-th" style="max-width:60px;text-align:center;"><input id="cb-select-all-1" type="checkbox" class="chkstanard"></th>
						<?php if(!empty($grid_columns)):?>
							<?php foreach($grid_columns as $key=>$title):?>
                                                            <th data-key="<?php echo $key; ?>" class="arm_grid_th_<?php echo $key; ?> " ><?php echo $title; ?></th>
							<?php endforeach;?>
						<?php endif;?>
                                                <th data-key="armGridActionTD" class="armGridActionTD"></th>
					</tr>
				</thead>
			</table>
			<div class="armclear"></div>
			
			<input type="hidden" name="search_grid" id="search_grid" value="<?php _e('Search','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="entries_grid" id="entries_grid" value="<?php _e('referrals','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="show_grid" id="show_grid" value="<?php _e('Show','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="showing_grid" id="showing_grid" value="<?php _e('Showing','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="to_grid" id="to_grid" value="<?php _e('to','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="of_grid" id="of_grid" value="<?php _e('of','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php _e('No matching referral found.','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php _e('No any referral found.','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="filter_grid" id="filter_grid" value="<?php _e('filtered from','ARM_AFFILIATE');?>"/>
			<input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php _e('total','ARM_AFFILIATE');?>"/>
            <?php wp_nonce_field( 'arm_wp_nonce' );?>
		</div>
		<div class="footer_grid"></div>
	</form>
</div>