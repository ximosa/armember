<?php
global $wpdb, $arm_global_settings, $arm_subscription_plans, $ARMember, $arm_aff_affiliate, $arm_version;
do_action('arm_aff_visits_export', $_REQUEST);
$all_plans = $arm_subscription_plans->arm_get_all_subscription_plans();
$all_aff_users = $arm_aff_affiliate->arm_get_affiliate_users();

$filter_plan_id = (!empty($_REQUEST['plan_id']) && $_REQUEST['plan_id'] != '0') ? $_REQUEST['plan_id'] : '';
$filter_search = isset($_REQUEST['filter_search']) ? $_REQUEST['filter_search'] : '';
$start_date = isset($_REQUEST['start_date']) ? $_REQUEST['start_date'] : '';
$end_date = isset($_REQUEST['end_date']) ? $_REQUEST['end_date'] : '';

/* **************./Begin Set Member Grid Fields/.************** */
$grid_columns = array(
    'sr_no' => __('SR. No', 'ARM_AFFILIATE'),
    'date' => __('Date', 'ARM_AFFILIATE'),
    'browser' => __('Browser', 'ARM_AFFILIATE'),
    'ip' => __('IP', 'ARM_AFFILIATE'),
    'country' => __('Country', 'ARM_AFFILIATE'),
    'converted' => __('Converted', 'ARM_AFFILIATE'),
    'affiliate_user' => __('Affiliate User', 'ARM_AFFILIATE'),
    'referral_id' => __('Referred User', 'ARM_AFFILIATE'),
    'commision' => __('Commission', 'ARM_AFFILIATE'),
    'plan' => __('Membership Plan', 'ARM_AFFILIATE'),
);

?>
<script type="text/javascript" charset="utf-8">
// <![CDATA[
    function show_grid_loader() {
        jQuery('.arm_loading_grid').show();
    }
    jQuery(document).ready(function () {
        arm_load_visits_grid(false, '');
    });
    function arm_load_visits_grid_after_filtered(message = '') {
        jQuery('#example').dataTable().fnDestroy();
        arm_load_visits_grid(true, message);
    }
    function arm_load_visits_grid(is_filtered, message) {
        var filtered_id = jQuery("#arm_subs_filter").val();
        var filtered_user_id = jQuery("#arm_user_filter").val();
        var search_term = jQuery("#armmanagesearch_new").val();
        var start_date = jQuery("#start_date").val();
        var end_date = jQuery("#end_date").val();
        var db_filter_id = (typeof filtered_id !== 'undefined' && filtered_id !== '') ? filtered_id : '';
        var db_filter_user_id = (typeof filtered_user_id !== 'undefined' && filtered_user_id !== '') ? filtered_user_id : '';
        var db_start_date = (typeof start_date !== 'undefined' && start_date !== '') ? start_date : '';
        var db_end_date = (typeof end_date !== 'undefined' && end_date !== '') ? end_date : '';
        var db_search_term = (typeof search_term !== 'undefined' && search_term !== '') ? search_term : '';
        var filtered_data = (typeof is_filtered !== 'undefined' && is_filtered !== false) ? true : false;

        jQuery('#csv_sSearch').val(db_search_term);
        jQuery('#csv_filter_plan_id').val(db_filter_id);
        jQuery('#csv_filter_user_id').val(db_filter_user_id);
        jQuery('#csv_start_date').val(db_start_date);
        jQuery('#csv_end_date').val(db_end_date);

        var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
        var _wpnonce = jQuery('input[name="_wpnonce"]').val();

        var __ARMVersion = '<?php echo $arm_version;?>';

        var oTables = jQuery('#example');

        var dt_obj = {
            "oLanguage": {
                "sProcessing": show_grid_loader(),
                "sEmptyTable": "No any visits found.",
                "sZeroRecords": "No matching visits found."
            },
            "bProcessing": false,
            "bServerSide": true,
            "sAjaxSource": ajax_url,
            "sServerMethod": "POST",
            "fnServerParams": function (aoData) {
                aoData.push({'name': 'action', 'value': 'arm_visits_list'});
                aoData.push({'name': 'filter_plan_id', 'value': db_filter_id});
                aoData.push({'name': 'filter_user_id', 'value': db_filter_user_id});
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
                {"sClass": "center", "aTargets": [0] },
                {"sClass": "left", "aTargets": [1,2,3,4,5,6] },
                {"bSortable": false, "aTargets": [0,3,4,5,7,8,9]}
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
              
            },
            "fnDrawCallback": function (oSettings) {
                jQuery('.arm_loading_grid').hide();
                if(message != '') {
                    armToast(message, 'success');
                }
                jQuery("#cb-select-all-1").removeAttr("checked");
                arm_selectbox_init();
                if (filtered_data == true) {
                    /*var filter_box = jQuery('#arm_filter_wrapper_after_filter').html();
                    jQuery('div#example_filter').parent().append(filter_box);
                    jQuery('div#example_filter').hide(); */
                }
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
<div class="arm_members_list">
        <!--/================ Begin Export Data CSV ======================/ -->
        
        <form name='export_data_to_csv' id='export_data_to_csv' method="POST" enctype="multipart/form-data">
            <input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? $_REQUEST['page'] : '' ; ?>" />
            <input type="hidden" name="sSearch" id="csv_sSearch" value=""/>
            <input type="hidden" name="filter_plan_id" id="csv_filter_plan_id" value="<?php echo $filter_plan_id;?>"/>
            <input type="hidden" name="filter_user_id" id="csv_filter_user_id" value="<?php echo $filter_plan_id;?>"/>
            <input type="hidden" name="start_date" id="csv_start_date" value="<?php echo $start_date; ?>"/>
            <input type="hidden" name="end_date" id="csv_end_date" value="<?php echo $end_date; ?>"/>
            <input type="hidden" name="arm_action" id="arm_action" value="visits_export_csv"/>
        </form>
        <!--/================ End Export Data CSV ======================/ -->
        <form method="POST" id="arm_visits_list_form" class="data_grid_list" enctype="multipart/form-data">
		<input type="hidden" name="page" id="page" value="<?php echo isset($_REQUEST['page']) ? $_REQUEST['page'] : '' ; ?>" />
		<input type="hidden" name="armaction" value="list" />
		<div class="arm_datatable_filters">
			<div class="arm_dt_filter_block">
                                <?php if(!empty($all_aff_users)) : ?>
                                <div class="arm_datatable_filter_item">
                                        <span><label><?php _e('Affiliate User', 'ARM_AFFILIATE')?>:</label></span><br/>
					
                                        <select id="arm_user_filter" class="arm_chosen_selectbox arm_user_filter" name="arm_general_settings[arm_exclude_role_for_restrict_admin][]" data-placeholder="<?php _e('Select user(s)..', 'ARM_AFFILIATE');?>" multiple="multiple" style="width:500px;">
                                                    <?php
                                                        if (!empty($all_aff_users)):
                                                            foreach ($all_aff_users as $user) {
                                                                ?><option class="arm_message_selectbox_op" value="<?php echo $user->ID; ?>" ><?php echo stripslashes($user->user_login);?></option><?php
                                                            }
                                                        else:
                                                    ?>
                                                            <option value=""><?php _e('No Users Available', 'ARM_AFFILIATE');?></option>
                                                    <?php endif;?>
                                            </select>
				</div>
                                <?php endif; ?>
                            
				<!--/====================Begin Filter By Date Form Box====================/-->
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
                                <?php endif; ?>
                                <div class="arm_datatable_filter_item arm_import_export_date_fields">
                                    <span><label><?php _e('Start Date', 'ARM_AFFILIATE') ?>:</label></span><br/>
                                    <input type="text" name="start_date" id="start_date" placeholder="<?php _e('Select Date', 'ARM_AFFILIATE');?>" data-dateformat='<?php echo arm_wp_date_format_to_bootstrap_datepicker(); ?>' class="arm_datepicker" value="<?php echo $start_date; ?>">
                                </div>
                                <div class="arm_datatable_filter_item arm_import_export_date_fields">
                                    <span><label><?php _e('End Date', 'ARM_AFFILIATE') ?>:</label></span><br/>
                                    <input type="text" name="end_date" id="end_date" placeholder="<?php _e('Select Date', 'ARM_AFFILIATE');?>" data-dateformat='<?php echo arm_wp_date_format_to_bootstrap_datepicker(); ?>' class="arm_datepicker" value="<?php echo $end_date; ?>">
                                </div>
                                <!--/====================End Filter By date Box====================/-->
                                <div class="arm_dt_filter_block arm_dt_filter_submit arm_datatable_filter_item">
                                    <input type="button" class="armemailaddbtn" id="arm_member_grid_filter_btn" onClick="arm_load_visits_grid_after_filtered();" value="<?php _e('Apply', 'ARM_AFFILIATE'); ?>"/>
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
						<?php if(!empty($grid_columns)):?>
							<?php foreach($grid_columns as $key=>$title):?>
                                                            <th data-key="<?php echo $key; ?>" class="arm_grid_th_<?php echo $key; ?> " ><?php echo $title; ?></th>
							<?php endforeach;?>
						<?php endif;?>
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