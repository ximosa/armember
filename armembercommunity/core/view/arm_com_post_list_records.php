<?php
global $wpdb, $arm_global_settings, $arm_community_setting, $arm_version;
$grid_columns = array(
    'post_title' => __('Post Title', ARM_COMMUNITY_TEXTDOMAIN),
    'post_content' => __('Post Content', ARM_COMMUNITY_TEXTDOMAIN),
    'post_by' => __('Post By', ARM_COMMUNITY_TEXTDOMAIN),
    'date' => __('Date', ARM_COMMUNITY_TEXTDOMAIN)
);
$filter_search = (!empty($_REQUEST['search'])) ? $_REQUEST['search'] : '';
?>
<script type="text/javascript" charset="utf-8">
// <![CDATA[
    function show_grid_loader() {
        jQuery('.arm_loading_grid').show();
    }
    jQuery(document).ready(function () {
        arm_load_com_post_grid(false);
    });
    function arm_load_com_post_grid_after_filtered(msg) {
        jQuery('#armember_datatable').dataTable().fnDestroy();
        arm_load_com_post_grid(true, msg);
    }

    function arm_load_com_post_grid(is_filtered, msg) {
        var search_term = jQuery("#arm_com_post_search").val().trim();
        var com_post_search_term = (typeof search_term !== 'undefined' && search_term !== '') ? search_term : '';

        var start_data = jQuery("#arm_filter_pstart_date").val().trim();
        var com_post_search_start_data = (typeof start_data !== 'undefined' && start_data !== '') ? start_data : '';

        var end_data = jQuery("#arm_filter_pend_date").val().trim();
        var com_post_search_end_data = (typeof end_data !== 'undefined' && end_data !== '') ? end_data : '';

        var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
        var _wpnonce = jQuery('input[name="_wpnonce"]').val();
        var __ARMVersion = '<?php echo $arm_version;?>';
        
        var oTables = jQuery('#armember_datatable');
        var dt_obj = {
            "oLanguage": {
                "sProcessing": show_grid_loader(),
                "sEmptyTable": "No any post found.",
                "sZeroRecords": "No matching post found."
            },
            "bProcessing": false,
            "bServerSide": true,
            "sAjaxSource": ajax_url,
            "sServerMethod": "POST",
            "fnServerParams": function (aoData) {
                aoData.push({'name': 'action', 'value': 'arm_com_post_list'});
                aoData.push({'name': 'com_post_search_term', 'value': com_post_search_term});
                aoData.push({'name': 'com_post_search_start_data', 'value': com_post_search_start_data});
                aoData.push({'name': 'com_post_search_end_data', 'value': com_post_search_end_data});
                aoData.push({'name': '_wpnonce', 'value': _wpnonce});
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
                "aiExclude": [0]
            },
            "aoColumnDefs": [
                {"sType": "html", "bVisible": false, "aTargets": []},
                {"sClass": "center", "aTargets": [0]},
                {"sClass": "left", "aTargets": [0, 1, 2]},
                {"bSortable": false, "aTargets": [0, 2]},
                {"sWidth": "15%", "aTargets": [1]},
                {"sWidth": "55%", "aTargets": [2]},
                {"sWidth": "12%", "aTargets": [3]},
                {"sWidth": "18%", "aTargets": [4]},
            ],
            "order": [[2, 'desc']],
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
            },
            "fnPreDrawCallback": function () {
                jQuery('.arm_loading_grid').show();
            },
            "fnCreatedRow": function (nRow, aData, iDataIndex) {
                jQuery(nRow).find('.arm_grid_action_btn_container').each(function () {
                    jQuery(this).parent().addClass('armGridActionTD');
                    jQuery(this).parent().attr('data-key', 'armGridActionTD');
                });
            },
            "fnDrawCallback": function (oSettings) {
                jQuery('.arm_loading_grid').hide();
                jQuery("#cb-select-all-1").removeAttr("checked");
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
                if (typeof msg != 'undefined') {
                    if(msg != '')
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

            dt_obj.buttons = [];

            dt_obj.sDom = '<"H"CBfr>t<"footer"ipl>';

            dt_obj.fixedColumns = false;
        }

        oTables.dataTable(dt_obj);

        var filter_box = jQuery('#arm_filter_wrapper').html();
        jQuery('div#armember_datatable_filter').parent().append(filter_box);
        jQuery('div#armember_datatable_filter').hide();
        jQuery('#arm_com_post_search').bind('keydown', function (e) {
            e.stopPropagation();
            if (e.keyCode == 13) {
                arm_load_com_post_grid_after_filtered();
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
                        <li data-label="<?php _e('Bulk Actions', ARM_COMMUNITY_TEXTDOMAIN); ?>" data-value="-1"><?php _e('Bulk Actions', ARM_COMMUNITY_TEXTDOMAIN); ?></li>
                        <li data-label="<?php _e('Delete', ARM_COMMUNITY_TEXTDOMAIN); ?>" data-value="delete_member"><?php _e('Delete', ARM_COMMUNITY_TEXTDOMAIN); ?></li>
                    </ul>
                </dd>
            </dl>
        </div>
        <input type="submit" id="doaction1" class="armbulkbtn armemailaddbtn" value="<?php _e('Go', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
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
                            <li data-label="<?php _e('Bulk Actions', ARM_COMMUNITY_TEXTDOMAIN); ?>" data-value="-1"><?php _e('Bulk Actions', ARM_COMMUNITY_TEXTDOMAIN); ?></li>
                            <li data-label="<?php _e('Delete', ARM_COMMUNITY_TEXTDOMAIN); ?>" data-value="delete_member"><?php _e('Delete', ARM_COMMUNITY_TEXTDOMAIN); ?></li>
                        </ul>
                    </dd>
                </dl>
            </div>
            <input type="submit" id="doaction1" class="armbulkbtn armemailaddbtn" value="<?php _e('Go', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
        </div>
    </div>
    <form method="POST" id="arm_com_post_list_form" class="data_grid_list" enctype="multipart/form-data" onsubmit="return arm_com_post_list_form_bulk_action();" >
        <input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? $_REQUEST['page'] : ''; ?>" />
        <input type="hidden" name="armaction" value="list" />

        <div class="arm_datatable_filters arm_com_post_filter">
            <div class="arm_dt_filter_block arm_datatable_searchbox">
                <label><input type="text" placeholder="<?php _e('Search', ARM_COMMUNITY_TEXTDOMAIN); ?>" id="arm_com_post_search" value="<?php echo $filter_search; ?>" tabindex="-1"></label>
                <div class="arm_datatable_filter_item arm_filter_pstatus_label">
                    <input type="text" id="arm_filter_pstart_date" placeholder="start date" />
                </div>
                <div class="arm_datatable_filter_item arm_filter_pstatus_label">
                    <input type="text" id="arm_filter_pend_date" placeholder="end date" />
                </div>
            </div>
            <div class="arm_dt_filter_block arm_dt_filter_submit arm_payment_history_filter_submit">
                <input type="button" class="armemailaddbtn" id="arm_payment_grid_filter_btn" value="<?php _e('Filter', ARM_COMMUNITY_TEXTDOMAIN); ?>" onClick="arm_load_com_post_grid_after_filtered()"/>
            </div>
            <div class="armclear"></div>
        </div>

        <div id="armmainformnewlist" class="arm_filter_grid_list_container">
            <div class="arm_loading_grid" style="display: none;"><img src="<?php echo ARM_COMMUNITY_IMAGES_URL; ?>/loader.gif" alt="Loading.."></div>
            <div class="response_messages"></div>
            <div class="armclear"></div>
            <table cellpadding="0" cellspacing="0" border="0" class="display" id="armember_datatable">
                <thead>
                    <tr>
                        <th class="center cb-select-all-th" style="max-width:60px;text-align:center;"><input id="cb-select-all-1" type="checkbox" class="chkstanard"></th>
                        <?php
                            if (!empty($grid_columns)) {
                                foreach ($grid_columns as $key => $title) {
                        ?>
                                    <th data-key="<?php echo $key; ?>" class="arm_grid_th_<?php echo $key; ?> " style="text-align:left;" ><?php echo $title; ?></th>
                        <?php
                                }
                            }
                        ?>
                        <th data-key="armGridActionTD" class="armGridActionTD"></th>
                    </tr>
                </thead>
            </table>
            <div class="armclear"></div>

            <input type="hidden" name="search_grid" id="search_grid" value="<?php _e('Search', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
            <input type="hidden" name="entries_grid" id="entries_grid" value="<?php _e('User Post', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
            <input type="hidden" name="show_grid" id="show_grid" value="<?php _e('Show', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
            <input type="hidden" name="showing_grid" id="showing_grid" value="<?php _e('Showing', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
            <input type="hidden" name="to_grid" id="to_grid" value="<?php _e('to', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
            <input type="hidden" name="of_grid" id="of_grid" value="<?php _e('of', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
            <input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php _e('No matching post found.', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
            <input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php _e('No any post found.', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
            <input type="hidden" name="filter_grid" id="filter_grid" value="<?php _e('filtered from', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
            <input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php _e('total', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
            <?php wp_nonce_field( 'arm_wp_nonce' );?>
        </div>

        <div class="footer_grid"></div>
    </form>
</div>