<?php
global $wpdb, $arm_global_settings, $arm_subscription_plans,$arm_dd_items, $arm_version;

$filter_status = (!empty($_REQUEST['status']) && $_REQUEST['status'] != '0') ? $_REQUEST['status'] : '';
$filter_search = (!empty($_POST['search'])) ? $_POST['search'] : '';

/* **************./Begin Set item Grid Fields/.************** */
$grid_columns = array(
                'id'   => __('ID', 'ARM_DD'),
                'name' => __('Name', 'ARM_DD'),
                'permission_type' => __('Permission Type', 'ARM_DD'),
                'permission' => __('Permission', 'ARM_DD'),
                'download' => __('No. Of Downloads', 'ARM_DD'),
                'shortcode' => __('Shortcode', 'ARM_DD'),
                'status' => __('Access', 'ARM_DD').' <div class="dd_items_records_access_field">'.__('(Enable/Disable)', 'ARM_DD').'</div>',
                'datetime' => __('Date', 'ARM_DD')
            );

?>
<script type="text/javascript" charset="utf-8">
// <![CDATA[
    function show_grid_loader() {
        jQuery('.arm_loading_grid').show();
    }
    jQuery(document).ready(function () {
        arm_load_item_grid(false);
    });
    function arm_load_item_grid_after_filtered() {
        jQuery('#example').dataTable().fnDestroy();
        arm_load_item_grid(true);
    }
    function arm_load_item_grid(is_filtered) {
        var search_term = jQuery("#armmanagesearch_new").val();
        var status = jQuery("#arm_filter_status").val();
        
        var db_status = (typeof status !== 'undefined' && status !== '') ? status : '';
        var db_search_term = (typeof search_term !== 'undefined' && search_term !== '') ? search_term : '';
        var filtered_data = (typeof is_filtered !== 'undefined' && is_filtered !== false) ? true : false;
        var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
        var _wpnonce = jQuery('input[name="_wpnonce"]').val();
	var __ARMVersion = '<?php echo $arm_version;?>';


        var oTables = jQuery('#example');

        var dt_obj = {
            "oLanguage": {
                "sProcessing": show_grid_loader(),
                "sEmptyTable": "No any item found.",
                "sZeroRecords": "No matching item found."
            },
            "bProcessing": false,
            "bServerSide": true,
            "sAjaxSource": ajax_url,
            "sServerMethod": "POST",
            "fnServerParams": function (aoData) {
                aoData.push({'name': 'action', 'value': 'arm_dd_item_list'});
                aoData.push({'name': 'filter_status_id', 'value': db_status});
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
                {"sType": "html", "bVisible": false, "aTargets": [1]},
                {"sClass": "center", "aTargets": [5,6,7] },
                {"sClass": "left", "aTargets": [1,2,3,5] },
                {"bSortable": false, "aTargets": [0,3,4,6,7]},
                { "width": "20px", "aTargets": [5] },
                { "width": "180px", "aTargets": [6] },
                { "width": "130px", "aTargets": [8] },
            ],
            "order": [[ 1, "desc" ]],
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
                // jQuery(nRow).find('.armGridActionTD').each(function () {
                //     jQuery(this).parent().attr('style','position: absolute;');
                // });
                jQuery(nRow).find('.arm_grid_action_btn_container').each(function () {
                    jQuery(this).parent().addClass('armGridActionTD');
                    jQuery(this).parent().attr('data-key', 'armGridActionTD');
                });
            },
            "fnDrawCallback": function (oSettings) {
                jQuery('.arm_loading_grid').hide();
                jQuery("#cb-select-all-1").removeAttr("checked");
                arm_selectbox_init();
//                if (filtered_data == true) {
//                    var filter_box = jQuery('#arm_filter_wrapper_after_filter').html();
//                    jQuery('div#example_filter').parent().append(filter_box);
//                    jQuery('div#example_filter').hide();
//                }
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
                arm_load_item_grid_after_filtered();
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
                        <li data-label="<?php _e('Delete', 'ARM_DD'); ?>" data-value="delete_item"><?php _e('Delete', 'ARM_DD'); ?></li>
                    </ul>
                </dd>
            </dl>
        </div>
        <input type="submit" id="doaction1" class="armbulkbtn armemailaddbtn" value="<?php _e('Go', 'ARM_DD'); ?>"/>
    </div>
</div>
<div class="arm_item_list">
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
                            <li data-label="<?php _e('Delete', 'ARM_DD');?>" data-value="delete_item"><?php _e('Delete', 'ARM_DD');?></li>
                        </ul>
                    </dd>
                </dl>
            </div>
            <input type="submit" id="doaction1" class="armbulkbtn armemailaddbtn" value="<?php _e('Go','ARM_DD');?>"/>
        </div>
    </div>
    <form method="POST" id="arm_item_list_form" class="data_grid_list" onsubmit="return arm_item_list_form_bulk_action();" enctype="multipart/form-data">
        <input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? $_REQUEST['page'] : '' ; ?>" />
        <input type="hidden" name="armaction" value="list" />
        <div class="arm_datatable_filters">
            <div class="arm_dt_filter_block">
                <label class="arm_datatable_searchbox"><input type="text" placeholder="<?php _e('Search', 'ARM_DD');?>" id="armmanagesearch_new" value="<?php echo $filter_search;?>" tabindex="-1" onkeydown="if (event.keyCode == 13) return false;"></label>
                <!--/==================== Begin Filter By Plan Box ====================/ -->
                <div class="arm_filter_payment_node_box arm_datatable_filter_item">
                    <span><?php _e('Access (Enable/Disable)', 'ARM_DD')?> :</span>
                    <input type="hidden" id="arm_filter_status" class="arm_filter_status" value="<?php echo $filter_status; ?>" />
                    <dl class="arm_selectbox">
                        <dt style="width: 170px;"><span></span><input type="text" style="display:none;" value="" class="arm_autocomplete"/><i class="armfa armfa-caret-down armfa-lg"></i></dt>
                        <dd>
                            <ul data-id="arm_filter_status" data-placeholder="<?php _e('Select Access', 'ARM_DD'); ?>">
                                <li data-label="<?php _e('Select Access', 'ARM_DD'); ?>" data-value=""><?php _e('Select Access', 'ARM_DD'); ?></li>
                                <li data-label="<?php _e('Enable', 'ARM_DD'); ?>" data-value="1"><?php _e('Enable', 'ARM_DD'); ?></li>
                                <li data-label="<?php _e('Disable', 'ARM_DD'); ?>" data-value="0"><?php _e('Disable', 'ARM_DD'); ?></li>
                            </ul>
                        </dd>
                    </dl>
                </div>
                <!--/==================== End Filter By Plan Box ====================/-->

                <div class="arm_dt_filter_block arm_dt_filter_submit arm_datatable_filter_item">
                <input type="button" class="armemailaddbtn" id="arm_item_grid_filter_btn" onClick="arm_load_item_grid_after_filtered();" value="<?php _e('Apply', 'ARM_DD'); ?>"/>
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
            <input type="hidden" name="entries_grid" id="entries_grid" value="<?php _e('item','ARM_DD');?>"/>
            <input type="hidden" name="show_grid" id="show_grid" value="<?php _e('Show','ARM_DD');?>"/>
            <input type="hidden" name="showing_grid" id="showing_grid" value="<?php _e('Showing','ARM_DD');?>"/>
            <input type="hidden" name="to_grid" id="to_grid" value="<?php _e('to','ARM_DD');?>"/>
            <input type="hidden" name="of_grid" id="of_grid" value="<?php _e('of','ARM_DD');?>"/>
            <input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php _e('No matching item found.','ARM_DD');?>"/>
            <input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php _e('No any item found.','ARM_DD');?>"/>
            <input type="hidden" name="filter_grid" id="filter_grid" value="<?php _e('filtered from','ARM_DD');?>"/>
            <input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php _e('total','ARM_DD');?>"/>
            <?php wp_nonce_field( 'arm_wp_nonce' );?>
        </div>
        <div class="footer_grid"></div>
    </form>
</div>
<?php 
    $dd_generate_shortcode_popup_arg = array(
        'id' => 'add_dd_shortcode_wrapper',
        'class' => 'add_dd_shortcode_wrapper',
        'title' => __('Generate Shortcode', 'ARM_DD'),
    );
    echo $arm_dd_items->arm_dd_get_bpopup_html($dd_generate_shortcode_popup_arg);
 ?>