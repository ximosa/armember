<?php
global $wpdb, $arm_global_settings, $arm_subscription_plans, $arm_dd_items, $arm_version;

$arm_dd_item_data = $arm_dd_items->arm_dd_item_all_data();

$arm_download_filter = (!empty($_POST['arm_download_filter'])) ? $_POST['arm_download_filter'] : '';
$start_date = (!empty($_POST['start_date'])) ? $_POST['start_date'] : '';
$end_date = (!empty($_POST['end_date'])) ? $_POST['end_date'] : '';
$filter_search = (!empty($_POST['search'])) ? $_POST['search'] : '';

/* **************./Begin Set Download history Grid Fields/.************** */
$grid_columns = array(
    'id' => __('ID', 'ARM_DD'),
    'item_name' => __('Item Name', 'ARM_DD'),
    'username' => __('Username', 'ARM_DD'),
    'ip_address' => __('IP Address', 'ARM_DD'),
    'browser' => __('Browser', 'ARM_DD'),
    'country' => __('Country', 'ARM_DD'),
    'datetime' => __('Date Time', 'ARM_DD')
);

?>
<script type="text/javascript" charset="utf-8">
// <![CDATA[
    function show_grid_loader() {
        jQuery('.arm_loading_grid').show();
    }
    jQuery(document).ready(function () {
        arm_load_download_grid(false);

        jQuery(document).on('click', '.arm_remove_user_multiauto_selected_itembox', function () {
            var arm_remove_download_filter_id =jQuery(this).data("download-filter-id");
           
            jQuery(this).parents('.arm_users_multiauto_itembox').remove();

            var arm_download_filter_val = jQuery('#arm_download_filter').val();
            
            var arm_download_filter_array = JSON.parse("[" + arm_download_filter_val + "]");
            
            if(jQuery.inArray(arm_remove_download_filter_id, arm_download_filter_array) !== -1){

                arm_download_filter_array = jQuery.grep(arm_download_filter_array, function(value) {
                  return value != arm_remove_download_filter_id;
                });
                var arm_download_filter_id = arm_download_filter_array.join(",");
                jQuery('#arm_download_filter').val(arm_download_filter_id);
            }
            if(jQuery('#arm_users_multiauto_items .arm_users_multiauto_itembox').length == 0) {
                jQuery('#arm_users_multiauto_items').hide();
            }
            return false;
        });

        if (jQuery.isFunction(jQuery().autocomplete))
        {
            if(jQuery("#arm_download_items_input").length > 0){
                
                jQuery('#arm_download_items_input').autocomplete({
                    minLength: 0,
                    delay: 500,
                    appendTo: ".arm_multiauto_user_field",
                    source: function (request, response) {
                        jQuery.ajax({
                            type: "POST",
                            url: ajaxurl,
                            dataType: 'json',
                            data: "action=arm_dd_item_filter_ajax_action&txt="+request.term,
                            beforeSend: function () {},
                            success: function (res) {
                                response(res.data);

                            }
                        });
                    },
                    focus: function() {return false;},
                    select: function(event, ui) {
                        
                        var itemData = ui.item;

                        jQuery("#arm_download_items_input").val('');
                        if(jQuery('.arm_users_multiauto_items .arm_users_multiauto_itembox_'+itemData.id).length > 0) {

                        } else {
                            
                            var arm_download_filter_val = jQuery('#arm_download_filter').val();
                            
                            var arm_download_filter_array = JSON.parse("[" + arm_download_filter_val + "]");
                            
                            if(jQuery.inArray(itemData.id, arm_download_filter_array) == -1){
                                   
                                arm_download_filter_array.push(itemData.id);
                                var arm_download_filter_id = arm_download_filter_array.join(",");
                                jQuery('#arm_download_filter').val(arm_download_filter_id);
                            }

                            var itemHtml = '<div class="arm_users_multiauto_itembox arm_users_multiauto_itembox_'+itemData.id+'">';
                            itemHtml += '<input type="hidden" name="arm_member_input_hidden['+itemData.id+']" value="'+itemData.id+'"/>';
                            itemHtml += '<label>'+itemData.label+'<span data-download-filter-id="'+itemData.id+'" class="arm_remove_user_multiauto_selected_itembox">x</span></label>';
                            itemHtml += '</div>';
                            jQuery("#arm_users_multiauto_items").append(itemHtml);
                        }
                        jQuery('#arm_users_multiauto_items').show();
                        
                        
                        return false;
                    },
                }).data('uiAutocomplete')._renderItem = function (ul, item) {
                    var itemClass = 'ui-menu-item';
                    if(jQuery('.arm_users_multiauto_items .arm_users_multiauto_itembox_'+item.id).length > 0) {
                        itemClass += ' ui-menu-item-selected';
                    }
                    var itemHtml = '<li class="'+itemClass+'" data-value="'+item.value+'" data-id="'+item.id+'" ><a>' + item.label + '</a></li>';
                    return jQuery(itemHtml).appendTo(ul);
                };
            }
        }
    });
    function arm_load_download_grid_after_filtered() {
        jQuery('#example').dataTable().fnDestroy();
        arm_load_download_grid(true);
    }
    function arm_load_download_grid(is_filtered) {
        var search_term = jQuery("#armmanagesearch_new").val();
        var download_id = jQuery("#arm_download_filter").val();
        var start_date = jQuery("#start_date").val();
        var end_date = jQuery("#end_date").val();
        
        var db_download_id = (typeof download_id !== 'undefined' && download_id !== '') ? download_id : '';
        var db_search_term = (typeof search_term !== 'undefined' && search_term !== '') ? search_term : '';
        var db_start_date = (typeof start_date !== 'undefined' && start_date !== '') ? start_date : '';
        var db_end_date = (typeof end_date !== 'undefined' && end_date !== '') ? end_date : '';
        var filtered_data = (typeof is_filtered !== 'undefined' && is_filtered !== false) ? true : false;
        var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
        var _wpnonce = jQuery('input[name="_wpnonce"]').val();
	var __ARMVersion = '<?php echo $arm_version;?>';
	
        jQuery('#csv_sSearch').val(search_term);
        jQuery('#csv_filter_download_id').val(db_download_id);
        jQuery('#csv_start_date').val(db_start_date);
        jQuery('#csv_end_date').val(db_end_date);


        var oTables = jQuery('#example');

        var dt_obj = {
            "oLanguage": {
                "sProcessing": show_grid_loader(),
                "sEmptyTable": "No any download history found.",
                "sZeroRecords": "No matching download history found."
            },
            "bProcessing": false,
            "bServerSide": true,
            "sAjaxSource": ajax_url,
            "sServerMethod": "POST",
            "fnServerParams": function (aoData) {
                aoData.push({'name': 'action', 'value': 'arm_dd_download_list'});
                aoData.push({'name': 'filter_download_id', 'value': db_download_id});
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
                {"sType": "html", "bVisible": false, "aTargets": [1],"bSearchable": false},
                {"sClass": "center", "aTargets": [0] },
                {"sClass": "left", "aTargets": [1,2,3,4,5] },
                {"bSortable": false, "aTargets": [0]}
            ],
            "order":[[1,'desc']],
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
                arm_load_download_grid_after_filtered();
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
                <dt style="width: 150px;">
                    <span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/>
                    <i class="armfa armfa-caret-down armfa-lg"></i>
                </dt>
                <dd>
                    <ul data-id="arm_manage_bulk_action1">
                        <li data-label="<?php _e('Bulk Actions', 'ARM_DD'); ?>" data-value="-1"><?php _e('Bulk Actions', 'ARM_DD'); ?></li>
                        <li data-label="<?php _e('Delete', 'ARM_DD'); ?>" data-value="delete_download"><?php _e('Delete', 'ARM_DD'); ?></li>
                    </ul>
                </dd>
            </dl>
        </div>
        <input type="submit" id="doaction1" class="armbulkbtn armemailaddbtn" value="<?php _e('Go', 'ARM_DD'); ?>"/>
    </div>
</div>
<div class="arm_download_list">
    <div class="arm_filter_wrapper" id="arm_filter_wrapper" style="display:none;">
        <div class="arm_datatable_filters_options">
            <div class='sltstandard'>
                <input type='hidden' id='arm_manage_bulk_action1' name="action1" value="-1" />
                <dl class="arm_selectbox">
                    <dt style="width: 150px;">
                        <span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/>
                        <i class="armfa armfa-caret-down armfa-lg"></i>
                    </dt>
                    <dd>
                        <ul data-id="arm_manage_bulk_action1">
                            <li data-label="<?php _e('Bulk Actions','ARM_DD'); ?>" data-value="-1"><?php _e('Bulk Actions','ARM_DD'); ?></li>
                            <li data-label="<?php _e('Delete', 'ARM_DD');?>" data-value="delete_download"><?php _e('Delete', 'ARM_DD');?></li>
                        </ul>
                    </dd>
                </dl>
            </div>
            <input type="submit" id="doaction1" class="armbulkbtn armemailaddbtn" value="<?php _e('Go','ARM_DD');?>"/>
        </div>
    </div>
    <!--/================ Begin Export Data CSV ======================/ -->
    <form name='export_data_to_csv' id='export_data_to_csv' method="POST" enctype="multipart/form-data">
        <input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? $_REQUEST['page'] : '' ; ?>" />
        <input type="hidden" name="sSearch" id="csv_sSearch" value=""/>
        <input type="hidden" name="filter_download_id" id="csv_filter_download_id" value="<?php echo $arm_download_filter; ?>"/>
        <input type="hidden" name="start_date" id="csv_start_date" value="<?php echo $start_date; ?>"/>
        <input type="hidden" name="end_date" id="csv_end_date" value="<?php echo $end_date; ?>"/>
        <input type="hidden" name="arm_action" id="arm_action" value="downloads_history_export_csv"/>
    </form>
    <!--/================ End Export Data CSV ======================/ -->
    <form method="POST" id="arm_download_list_form" class="data_grid_list" onsubmit="return arm_download_list_form_bulk_action();" enctype="multipart/form-data">
        <input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? $_REQUEST['page'] : '' ; ?>" />
        <input type="hidden" name="armaction" value="list" />
        <div class="arm_datatable_filters">
            <div class="arm_dt_filter_block">
                <div class="arm_datatable_filter_item">
                    <span><label><?php _e('Username', 'ARM_DD')?>:</label></span><br/>
                    <label class="arm_datatable_searchbox"><input type="text" placeholder="<?php _e('Username', 'ARM_DD');?>" id="armmanagesearch_new" value="<?php echo $filter_search;?>" class="armmanagesearch_new" tabindex="-1" onkeydown="if (event.keyCode == 13) return false;"></label>
                </div>                
                <div class="arm_datatable_filter_item arm_import_export_date_fields">
                    <span><label><?php _e('Start Date', 'ARM_DD') ?>:</label></span><br/>
                    <input type="text" name="start_date" id="start_date" placeholder="<?php _e('Select Date', 'ARM_DD');?>" data-dateformat='<?php echo arm_wp_date_format_to_bootstrap_datepicker(); ?>' class="arm_datepicker" value="<?php echo $start_date; ?>">
                </div>
                <div class="arm_datatable_filter_item arm_import_export_date_fields">
                    <span><label><?php _e('End Date', 'ARM_DD') ?>:</label></span><br/>
                    <input type="text" name="end_date" id="end_date" placeholder="<?php _e('Select Date', 'ARM_DD');?>" data-dateformat='<?php echo arm_wp_date_format_to_bootstrap_datepicker(); ?>' class="arm_datepicker" value="<?php echo $end_date; ?>">
                </div>
            </div>
            <div class="armclear"></div>
            <br>
            <div class="arm_dt_filter_block">
                    <div class="arm_datatable_filter_item arm_multiauto_user_field">
                        <input id="arm_download_items_input" type="text" value="" placeholder="<?php esc_html_e('Search By Download Items', 'ARM_DD');?>">
                        <input type="hidden" id="arm_download_filter" class="arm_download_filter" value="" />
                        <div class="arm_dt_filter_block arm_dt_filter_submit arm_datatable_filter_download">
                        <input type="button" class="armemailaddbtn" id="arm_download_grid_filter_btn" onClick="arm_load_download_grid_after_filtered();" value="<?php _e('Apply', 'ARM_DD'); ?>"/>
                        </div>
                        <input type="button" class="armemailaddbtn arm_datatable_filter_item" id="arm_user_export_btn_csv" onClick="jQuery('#export_data_to_csv').submit();" value="<?php _e('Export To CSV', 'ARM_DD');?>"/>
                        <br>
                        <div class="arm_users_multiauto_items" id="arm_users_multiauto_items" style="display: none;"></div>
                    </div>
            </div>
            <div class="armclear"></div>
        </div>
        <div id="armmainformnewlist" class="arm_filter_grid_list_container">
            <div class="arm_loading_grid" style="display: none;"><img src="<?php echo ARM_DD_IMAGES_URL;?>/loader.gif" alt="Loading.."></div>
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
            <input type="hidden" name="search_grid" id="search_grid" value="<?php _e('Search','ARM_DD');?>"/>
            <input type="hidden" name="entries_grid" id="entries_grid" value="<?php _e('download','ARM_DD');?>"/>
            <input type="hidden" name="show_grid" id="show_grid" value="<?php _e('Show','ARM_DD');?>"/>
            <input type="hidden" name="showing_grid" id="showing_grid" value="<?php _e('Showing','ARM_DD');?>"/>
            <input type="hidden" name="to_grid" id="to_grid" value="<?php _e('to','ARM_DD');?>"/>
            <input type="hidden" name="of_grid" id="of_grid" value="<?php _e('of','ARM_DD');?>"/>
            <input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php _e('No matching download hitory found.','ARM_DD');?>"/>
            <input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php _e('No any download history found.','ARM_DD');?>"/>
            <input type="hidden" name="filter_grid" id="filter_grid" value="<?php _e('filtered from','ARM_DD');?>"/>
            <input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php _e('total','ARM_DD');?>"/>
            <?php wp_nonce_field( 'arm_wp_nonce' );?>
        </div>
        <div class="footer_grid"></div>
    </form>
</div>