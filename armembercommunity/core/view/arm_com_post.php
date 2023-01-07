<?php
global $wpdb, $arm_community_setting, $arm_global_settings, $arm_version;
$comment_grid_columns = array(
    'user_avatar' => __('User Avatar', ARM_COMMUNITY_TEXTDOMAIN),
    'user_name' => __('User Name', ARM_COMMUNITY_TEXTDOMAIN),
    'post_commnet' => __('Comment', ARM_COMMUNITY_TEXTDOMAIN),
);
?>

<div class="wrap arm_page arm_manage_members_main_wrapper">
    <div class="content_wrapper" id="content_wrapper">
        <?php echo $arm_community_setting->arm_get_community_user_activity_tab('arm_com_post_selected'); ?>
        <div class="armclear"></div>
        <div class="arm_members_grid_container" id="arm_members_grid_container">
            <?php
            if (file_exists(ARM_COMMUNITY_VIEW_DIR . '/arm_com_post_list_records.php')) {
                include( ARM_COMMUNITY_VIEW_DIR . '/arm_com_post_list_records.php');
            }
            ?>
        </div>

        <?php
        /*         * *********./Begin Bulk Delete Member Popup/.********** */
        $bulk_delete_post_popup_content = '<span class="arm_confirm_text">' . __("Are you sure you want to delete this Post(s)?", ARM_COMMUNITY_TEXTDOMAIN);
        $bulk_delete_post_popup_content .= '<input type="hidden" value="false" id="bulk_delete_flag"/>';
        $bulk_delete_post_popup_arg = array(
            'id' => 'delete_bulk_form_message',
            'class' => 'delete_bulk_form_message',
            'title' => __('Delete Post(s)', ARM_COMMUNITY_TEXTDOMAIN),
            'content' => $bulk_delete_post_popup_content,
            'button_id' => 'arm_bulk_delete_post_ok_btn',
            'button_onclick' => "apply_com_post_bulk_action('bulk_delete_flag');",
        );
        echo $arm_global_settings->arm_get_bpopup_html($bulk_delete_post_popup_arg);
        /*         * *********./End Bulk Delete Member Popup/.********** */
        ?>
    </div>
</div>

<script type="text/javascript">
    function show_comment_grid_loader() {
        jQuery(".arm_view_comment_popup .arm_loading").show();
    }
    var arm_com_post_comment_id = 0;
    function arm_load_com_post_comment_grid(post_id) {
        arm_com_post_comment_id = post_id;
        jQuery('#armember_datatable_1').dataTable().fnDestroy();
        var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
        var _wpnonce = jQuery('input[name="_wpnonce"]').val();

        var __ARMVersion = '<?php echo $arm_version; ?>';

        var oTables = jQuery('#armember_datatable_1');
        var dt_obj = {
            "oLanguage": {
                "sProcessing": show_comment_grid_loader(),
                "sEmptyTable": "No any post found.",
                "sZeroRecords": "No matching post found."
            },
            "bProcessing": false,
            "bServerSide": true,
            "sAjaxSource": ajax_url,
            "sServerMethod": "POST",
            "fnServerParams": function (aoData) {
                aoData.push({'name': 'action', 'value': 'arm_com_post_comment_list'});
                aoData.push({'name': 'post_id', 'value': post_id});
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
                {"sClass": "center", "aTargets": [0]},
                {"sClass": "left", "aTargets": [0, 1, 2]},
                {"bSortable": false, "aTargets": [0, 1, 2]},
                {"sWidth": "15%", "aTargets": [0]},
                {"sWidth": "20%", "aTargets": [1]},
                {"sWidth": "65%", "aTargets": [2]}
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
                jQuery('.arm_view_comment_popup .arm_loading').show();
            },
            "fnCreatedRow": function (nRow, aData, iDataIndex) {
                jQuery(nRow).find('.arm_grid_action_btn_container').each(function () {
                    jQuery(this).parent().addClass('armGridActionTD');
                    jQuery(this).parent().attr('data-key', 'armGridActionTD');
                });
            },
            "fnDrawCallback": function (oSettings) {
                jQuery('.arm_view_comment_popup .arm_loading').hide();
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
                    armToast(msg, 'success');
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
</script>

<div class="arm_com_view_post_comment_popup popup_wrapper arm_popup_wrapper arm_popup_community_form" style="width: 850px; margin-top: 40px;">
    <div class="popup_wrapper_inner">
        <div class="popup_header">
            <span class="popup_close_btn arm_popup_close_btn"></span>
            <div class="popup_header_text arm_form_heading_container">
                <span class="arm_form_field_label_wrapper_text"></span><span class="arm_form_field_label_wrapper_text_default"><?php _e("'s Comments",ARM_COMMUNITY_TEXTDOMAIN); ?></span>
            </div>
        </div>
        <div class="popup_content_text arm_view_comment_popup">
            <div class="arm_loading"><img src="<?php echo MEMBERSHIP_IMAGES_URL . '/loader.gif' ?>" alt="Loading.."></div>

            <!-- data grid -->
            <div class="arm_members_list">
                <form method="POST" id="arm_com_post_comment_list_form" class="data_grid_list" enctype="multipart/form-data" onsubmit="" >
                    <div id="arm_com_user_post_comment_armmainformnewlist" class="arm_filter_grid_list_container">
                        <div class="armclear"></div>
                        <table cellpadding="0" cellspacing="0" border="0" class="display" id="armember_datatable_1">
                            <thead>
                                <tr>
                                    <?php
                                        if (!empty($comment_grid_columns)) {
                                            foreach ($comment_grid_columns as $key => $title) {
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

                        <input type="hidden" name="arm_com_user_post_comment_entries_grid" id="arm_com_user_post_comment_entries_grid" value="<?php _e('User Post', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
                        <input type="hidden" name="arm_com_user_post_comment_show_grid" id="arm_com_user_post_comment_show_grid" value="<?php _e('Show', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
                        <input type="hidden" name="arm_com_user_post_comment_showing_grid" id="arm_com_user_post_comment_showing_grid" value="<?php _e('Showing', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
                        <input type="hidden" name="arm_com_user_post_comment_to_grid" id="arm_com_user_post_comment_to_grid" value="<?php _e('to', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
                        <input type="hidden" name="arm_com_user_post_comment_of_grid" id="arm_com_user_post_comment_of_grid" value="<?php _e('of', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
                        <input type="hidden" name="arm_com_user_post_comment_no_match_record_grid" id="arm_com_user_post_comment_no_match_record_grid" value="<?php _e('No matching post found.', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
                        <input type="hidden" name="arm_com_user_post_comment_no_record_grid" id="arm_com_user_post_comment_no_record_grid" value="<?php _e('No any post found.', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
                        <input type="hidden" name="arm_com_user_post_comment_filter_grid" id="arm_com_user_post_comment_filter_grid" value="<?php _e('filtered from', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
                        <input type="hidden" name="arm_com_user_post_comment_totalwd_grid" id="arm_com_user_post_comment_totalwd_grid" value="<?php _e('total', ARM_COMMUNITY_TEXTDOMAIN); ?>"/>
                    </div>
                    <div class="footer_grid"></div>
                </form>
            </div>
            <!-- data grid -->
        </div>
    </div>
</div>

<?php $arm_community_setting->arm_community_get_footer(); ?>

<?php
    if(version_compare($arm_version, '4.0.1', '<'))
    {
?>
        <style type="text/css" title="currentStyle">
            @import "<?php echo ARM_COM_ARMEMBER_URL; ?>/datatables/media/css/demo_page.css";
            @import "<?php echo ARM_COM_ARMEMBER_URL; ?>/datatables/media/css/demo_table_jui.css";
            @import "<?php echo ARM_COM_ARMEMBER_URL; ?>/datatables/media/css/jquery-ui-1.8.4.custom.css";
        </style>
<?php        
    }
?>