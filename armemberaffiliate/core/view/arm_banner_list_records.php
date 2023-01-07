<?php
global $wpdb, $arm_global_settings, $arm_subscription_plans, $ARMember, $arm_version;

/* **************./Begin Set Member Grid Fields/.************** */
$grid_columns = array(
    'title' => __('Title', 'ARM_AFFILIATE'),
    'thumbnail' => __('Banner', 'ARM_AFFILIATE'),
    'shortcode' => __('Shortcode', 'ARM_AFFILIATE'),
    'status' => __('Status', 'ARM_AFFILIATE'),
    'desc' => __('Link', 'ARM_AFFILIATE'),
);

?>
<script type="text/javascript" charset="utf-8">
// <![CDATA[
    function show_grid_loader() {
        jQuery('.arm_loading_grid').show();
    }
    jQuery(document).ready(function () {
        arm_load_membership_grid(false, '');
    });
    function arm_load_affiliate_grid_after_filtered(message = '') {
        jQuery('#example').dataTable().fnDestroy();
        arm_load_membership_grid(true, message);
    }
    function arm_load_membership_grid(is_filtered, message) {
        
        var filtered_data = (typeof is_filtered !== 'undefined' && is_filtered !== false) ? true : false;
        var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
        var _wpnonce = jQuery('input[name="_wpnonce"]').val();

        var __ARMVersion = '<?php echo $arm_version;?>';

        var oTables = jQuery('#example');

        var dt_obj = {
            "oLanguage": {
                "sProcessing": show_grid_loader(),
                "sEmptyTable": "No any banner found.",
                "sZeroRecords": "No matching banner found."
            },
            "bProcessing": false,
            "bServerSide": true,
            "sAjaxSource": ajax_url,
            "sServerMethod": "POST",
            "fnServerParams": function (aoData) {
                aoData.push({'name': 'action', 'value': 'arm_banner_list'});
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
                {"sClass": "center", "aTargets": [0,1,2] },
                {"bSortable": false, "aTargets": [0,1,2,3,4,5]},
                { "width": "15%", "targets": [2] }
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
                if(message != '') {
                    armToast(message, 'success');
                }
                jQuery("#cb-select-all-1").removeAttr("checked");
                arm_selectbox_init();
                if (filtered_data == true) {
                    var filter_box = jQuery('#arm_filter_wrapper_after_filter').html();
                    jQuery('div#example_filter').parent().append(filter_box);
                    jQuery('div#example_filter').hide();
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

            dt_obj.buttons = [];

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
        <form method="POST" id="arm_banner_list_form" class="data_grid_list" onsubmit="return arm_banner_list_form_bulk_action();" enctype="multipart/form-data">
		<input type="hidden" name="page" id="page" value="<?php echo isset($_REQUEST['page']) ? $_REQUEST['page'] : '' ; ?>" />
		<input type="hidden" name="armaction" value="list" />
		
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