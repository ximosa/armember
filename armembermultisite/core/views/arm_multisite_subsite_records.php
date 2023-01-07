<?php
global $wpdb, $arm_global_settings, $arm_subscription_plans, $arm_version;

$filter_status = (!empty($_REQUEST['status']) && $_REQUEST['status'] != '0') ? $_REQUEST['status'] : '';
$filter_search = (!empty($_POST['search'])) ? $_POST['search'] : '';

/* **************./Begin Set item Grid Fields/.************** */
$grid_columns = array(
    'username' => esc_html__('Site Owner (Username)', 'ARM_MULTISUBSITE'),
    'membership_plan' => esc_html__('Membership Plan', 'ARM_MULTISUBSITE'),
    'subsite' => esc_html__('Subsite', 'ARM_MULTISUBSITE'),
    'status' => esc_html__('Status', 'ARM_MULTISUBSITE')
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
    function arm_load_item_grid_after_filtered(data) {
        var tbl = jQuery('#armember_datatable').dataTable(); 
        tbl.fnDeleteRow(data);
        console.log(data);
        jQuery('#armember_datatable').dataTable().fnDestroy();
        arm_load_item_grid(true);
    }
    function arm_load_item_grid_after_filtered_bulk(data) {
        var tbl = jQuery('#armember_datatable').dataTable(); 
        for(var i=0; i<data.length; i++) {
            var checked_id = jQuery(data[i]).val();
            var row = jQuery("#arm_multisite_"+checked_id);
            tbl.fnDeleteRow(row);
        }
        
        jQuery('#armember_datatable').dataTable().fnDestroy();
        arm_load_item_grid(true);
    }
    function arm_load_item_grid(is_filtered) {
        var __ARMVersion = '<?php echo $arm_version; ?>';
        var oTables = jQuery('#armember_datatable');

        var dt_obj = {
            "sDom": '<"H"Cfr>t<"F"ipl>',
            "sPaginationType": "four_button",
            "oLanguage": {
                "sEmptyTable": "No any subsite found.",
                "sZeroRecords": "No matching subsite found."
            },
            "bJQueryUI": true,
            "bPaginate": true,
            "bAutoWidth" : false,
            "aaSorting": [],
            "aoColumnDefs": [
                { "bVisible": false, "aTargets": [] },
                { "bSortable": false, "aTargets": [] }
            ],
            "fnDrawCallback":function(){
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
        jQuery('div#armember_datatable_filter').parent().append(filter_box);
        jQuery('div#armember_datatable_filter label').addClass('arm_datatable_searchbox');
        jQuery('#arm_filter_wrapper').remove();
        
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
                        <li data-label="<?php esc_html_e('Bulk Actions', 'ARM_MULTISUBSITE'); ?>" data-value="-1"><?php esc_html_e('Bulk Actions', 'ARM_MULTISUBSITE'); ?></li>
                        <li data-label="<?php esc_html_e('Delete', 'ARM_MULTISUBSITE'); ?>" data-value="delete_item"><?php esc_html_e('Delete', 'ARM_MULTISUBSITE'); ?></li>
                    </ul>
                </dd>
            </dl>
        </div>
        <input type="submit" id="doaction1" class="armbulkbtn armemailaddbtn" value="<?php esc_html_e('Go', 'ARM_MULTISUBSITE'); ?>"/>
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
                            <li data-label="<?php esc_html_e('Bulk Actions','ARM_MULTISUBSITE'); ?>" data-value="-1"><?php esc_html_e('Bulk Actions','ARM_MULTISUBSITE'); ?></li>
                            <li data-label="<?php esc_html_e('Delete', 'ARM_MULTISUBSITE');?>" data-value="delete_item"><?php esc_html_e('Delete', 'ARM_MULTISUBSITE');?></li>
                        </ul>
                    </dd>
                </dl>
            </div>
            <input type="submit" id="doaction1" class="armbulkbtn armemailaddbtn" value="<?php esc_html_e('Go','ARM_MULTISUBSITE');?>"/>
        </div>
    </div>
    <form method="POST" id="arm_multisite_subsite_list_form" class="data_grid_list" onsubmit="return arm_multisite_subsite_list_form_bulk_action();" enctype="multipart/form-data">
        <input type="hidden" name="page" value="<?php echo isset($_REQUEST['page']) ? $_REQUEST['page'] : '' ; ?>" />
        <input type="hidden" name="armaction" value="list" />
        
        <div id="armmainformnewlist" class="arm_filter_grid_list_container">
            <div class="arm_loading_grid" style="display: none;"><img src="<?php echo MEMBERSHIP_IMAGES_URL;?>/loader.gif" alt="Loading.."></div>
            <div class="response_messages"></div>
            <div class="armclear"></div>
            <table cellpadding="0" cellspacing="0" border="0" class="display" id="armember_datatable">
                <thead>
                    <tr>
                        <th class="center cb-select-all-th" style="max-width:60px;text-align:center;"><input id="cb-select-all-1" type="checkbox" class="chkstanard"></th>
                        <?php if(!empty($grid_columns)):?>
                            <?php foreach($grid_columns as $key=>$title):?>
                                <th data-key="<?php echo $key; ?>" class="left arm_grid_th_<?php echo $key; ?> " ><?php echo $title; ?></th>
                            <?php endforeach;?>
                        <?php endif;?>
                        <th data-key="armGridActionTD" class="armGridActionTD"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        global $wpdb, $ARMember, $arm_global_settings, $arm_multisubsite, $arm_subscription_plans;

                        if( method_exists($ARMember, 'arm_check_user_cap') ){
                            $arm_multisite_subsite_capabilities = $arm_multisubsite->arm_multisubsite_page_slug();
                            $ARMember->arm_check_user_cap($arm_multisite_subsite_capabilities['0'],'0');
                        }
                        $date_format = $arm_global_settings->arm_get_wp_date_format();
                        $nowDate = current_time('mysql');
                        
                        $grid_columns = array(
                            'username' => esc_html__('Site Owner (Username)', 'ARM_MULTISUBSITE'),
                            'membership_plan' => esc_html__('Membership Plan', 'ARM_MULTISUBSITE'),
                            'subsite' => esc_html__('Subsite', 'ARM_MULTISUBSITE'),
                            'status' => esc_html__('Status', 'ARM_MULTISUBSITE')
                        );

                        $sorting_ord = isset($_REQUEST['sSortDir_0']) ? $_REQUEST['sSortDir_0'] : 'desc';
                        $sorting_col = (isset($_REQUEST['iSortCol_0']) && $_REQUEST['iSortCol_0'] > 0) ? $_REQUEST['iSortCol_0'] : 5;
                        $order_by = 'blog_id';

                        $where_condition = array();
                        
                        $sSearch = isset($_REQUEST['sSearch']) ? $_REQUEST['sSearch'] : '';
                        if($sSearch != '')
                        { $where_condition['search'] = $sSearch ; }
                        
                        $arm_status = isset($_REQUEST['filter_status_id']) ? $_REQUEST['filter_status_id'] : '';
                        $arm_status = explode(',',$arm_status);
                        if(in_array('public', $arm_status))
                        { 
                            $where_condition['public'] =   1; 
                        }
                        if(in_array('deleted', $arm_status))
                        { 
                            $where_condition['deleted'] =  1; 
                        }

                        $offset = isset($_REQUEST['iDisplayStart']) ? $_REQUEST['iDisplayStart'] : 0;
                        $number = isset($_REQUEST['iDisplayLength']) ? $_REQUEST['iDisplayLength'] : 10;
                        
                        $arm_multisite_arg = array('site__not_in' => 1, 'orderby' => $order_by , 'order' => $sorting_ord );

                        $arm_multisite_arg = array_merge($arm_multisite_arg, $where_condition);
                        $arm_multisite_tmp_query = get_sites($arm_multisite_arg);

                        $form_result = $arm_multisite_tmp_query;
                        $total_before_filter = count($form_result);
                        $arm_multisite_paging_arg = array('offset' => $offset , 'number' => $number);
                        
                        $total_after_filter = count($arm_multisite_tmp_query);
                        $form_result = $arm_multisite_tmp_query;
                        
                        $all_membership_plans = $arm_subscription_plans->arm_get_all_subscription_plans('arm_subscription_plan_id, arm_subscription_plan_name,arm_subscription_plan_options');
                        
                        $grid_data = "";
                        $ai = 0;
                        $data_count = 0;
                        foreach ($form_result as $arm_multisite_blog) {
                            
                            $userblog_id = $arm_multisite_blog->blog_id;
                            $blog_user_details = get_blog_details($userblog_id);
                            
                            $arm_multisite_blog_args = array('blog_id' => $userblog_id);
                            $arm_multisite_blog_users = get_users( $arm_multisite_blog_args );
                            
                            $arm_multisite_userid = !empty($arm_multisite_blog_users[0]->ID) ? $arm_multisite_blog_users[0]->ID : 0;
                            $blog_user_login = !empty($arm_multisite_blog_users[0]->user_login) ? $arm_multisite_blog_users[0]->user_login : '';
                            
                            $user_url = admin_url("admin.php?page=arm_manage_members&action=view_member&id=".$arm_multisite_userid);
                            $arm_multisite_username = "<a href='".$user_url."' target='_blank'>".$blog_user_login."</a>";
                            $arm_multisite_subsite_ids = get_user_meta($arm_multisite_userid,'arm_multisite_id' , TRUE );
                            $arm_multisite_subsite_id = 0;
                            $arm_multisite_user_plan_id = 0;
                            $display_in_list = 0;
                            if(!empty($arm_multisite_subsite_ids))
                            {
                                foreach ($arm_multisite_subsite_ids as $key => $arm_multisite_subsite_id_value) {

                                    $arm_multisite_subsite_id = !empty($arm_multisite_subsite_id_value['site_id']) ? $arm_multisite_subsite_id_value['site_id'] : '';
                                    if($arm_multisite_subsite_id==$userblog_id)
                                    {
                                        $arm_multisite_user_plan_id = !empty($arm_multisite_subsite_id_value['plan_id']) ? $arm_multisite_subsite_id_value['plan_id'] : '';
                                        $display_in_list = 1;
                                    }

                                }
                            }
                            if($display_in_list == 1) {
                                $user_blog_site = $blog_user_details->blogname;
                                $arm_multisite_user_plan_name = !empty($all_membership_plans[$arm_multisite_user_plan_id]['arm_subscription_plan_name']) ? $all_membership_plans[$arm_multisite_user_plan_id]['arm_subscription_plan_name'] : '';
                                $current_admin_site_url = $blog_user_details->siteurl.'/wp-admin';
                                $arm_multisite_link = "<span style='cursor:pointer'><a href='".$current_admin_site_url."'>".$user_blog_site."</a></span>";

                                $arm_multisite_subsite_public_status = $blog_user_details->public;
                                $arm_multisite_subsite_archived_status = $blog_user_details->archived;
                                $arm_multisite_subsite_mature_status = $blog_user_details->mature;
                                $arm_multisite_subsite_spam_status = $blog_user_details->spam;
                                $arm_multisite_subsite_delete_status = $blog_user_details->deleted;
                                
                                $arm_multisite_subsite_status = array();
                                if($arm_multisite_subsite_delete_status == 1)
                                {
                                    $arm_multisite_subsite_status[] = sprintf(esc_html__('%sDeactive%s', 'ARM_MULTISUBSITE'), '<span class="arm_item_status_text inactive banned">', '</span>');
                                }
                                if($arm_multisite_subsite_delete_status == 0) 
                                {
                                    $arm_multisite_subsite_status[] = sprintf(esc_html__('%sActive%s', 'ARM_MULTISUBSITE'), '<span class="arm_item_status_text active">', '</span>');
                                }
                                
                                $arm_multisite_subsite_status = implode(', ', $arm_multisite_subsite_status);
                                $arm_item_datetime = date($date_format, strtotime($arm_multisite_blog->last_updated));
                                
                                

                                $gridAction = "<div class='arm_grid_action_btn_container'>";
                                if($arm_multisite_subsite_delete_status == 1)
                                {
                                    $gridAction .= "<a href='javascript:void(0)' ><img src='" . ARM_MULTISUBSITE_IMAGES_URL  . "active.png' class='armhelptip arm_change_user_status_ok_btn' title='" . esc_html__('Activate', 'ARM_MULTISUBSITE') . "' onmouseover=\"this.src='" . ARM_MULTISUBSITE_IMAGES_URL . "active_hover.png';\" onmouseout=\"this.src='" . ARM_MULTISUBSITE_IMAGES_URL . "active.png';\" data-item_id='{$userblog_id}' data-status='public' /></a>";
                                } 
                                if( 1 == $arm_multisite_subsite_public_status ) {
                                    $gridAction .= "<a href='javascript:void(0)' ><img src='" . ARM_MULTISUBSITE_IMAGES_URL . "deactive.png' class='armhelptip arm_change_user_status_ok_btn' title='" . esc_html__('Deactivate', 'ARM_MULTISUBSITE') . "' onmouseover=\"this.src='" . ARM_MULTISUBSITE_IMAGES_URL . "deactive_hover.png';\" onmouseout=\"this.src='" . ARM_MULTISUBSITE_IMAGES_URL . "deactive.png';\" data-item_id='{$userblog_id}' data-status='deleted' /></a>";
                                }
                                $gridAction .= "<a href='".$blog_user_details->home."' target='_blank' ><img src='" . ARM_MULTISUBSITE_IMAGES_URL . "visit.png' class='armhelptip arm_subsite_visit_btn' title='" . esc_html__('Visit', 'ARM_MULTISUBSITE') . "' onmouseover=\"this.src='" . ARM_MULTISUBSITE_IMAGES_URL . "visit_hover.png';\" onmouseout=\"this.src='" . ARM_MULTISUBSITE_IMAGES_URL . "visit.png';\" /></a>";
                                    
                                $gridAction .= "<a href='javascript:void(0)' onclick='showConfirmBoxCallback({$userblog_id},{$arm_multisite_userid});'><img src='" . ARM_MULTISUBSITE_IMAGES_URL . "delete.png' class='armhelptip' title='" . esc_html__('Delete', 'ARM_MULTISUBSITE') . "' onmouseover=\"this.src='" . ARM_MULTISUBSITE_IMAGES_URL . "delete_hover.png';\" onmouseout=\"this.src='" . ARM_MULTISUBSITE_IMAGES_URL . "delete.png';\" /></a>";

                                $gridAction .= $arm_global_settings->arm_get_confirm_box($userblog_id, esc_html__("Are you sure you want to delete this subsite?", 'ARM_MULTISUBSITE'), 'arm_mutisite_subsite_delete_btn');
                                $gridAction .= "</div>";
                                $grid_data .= "<tr id='arm_multisite_{$userblog_id}'>";
                                $grid_data .= "<td class='center'><input id=\"cb-item-action-{$userblog_id}\" class=\"chkstanard\" type=\"checkbox\" value=\"{$userblog_id}\" name=\"item-action[]\"></td>";
                                $grid_data .= "<td>".$arm_multisite_username."</td>";
                                $grid_data .= "<td>".$arm_multisite_user_plan_name."</td>";
                                $grid_data .= "<td>".$arm_multisite_link."</td>";
                                $grid_data .= "<td>".$arm_multisite_subsite_status."</td>";
                                $grid_data .= "<td class='armGridActionTD'>".$gridAction."</td>";    
                                $grid_data .= "</tr>";
                                $data_count++;
                            }
                            
                            $ai++;
                        }
                        echo $grid_data;
                    ?>
                </tbody>
            </table>
            <div class="armclear"></div>
            <input type="hidden" name="search_grid" id="search_grid" value="<?php esc_html_e('Search','ARM_MULTISUBSITE');?>"/>
            <input type="hidden" name="entries_grid" id="entries_grid" value="<?php esc_html_e('item','ARM_MULTISUBSITE');?>"/>
            <input type="hidden" name="show_grid" id="show_grid" value="<?php esc_html_e('Show','ARM_MULTISUBSITE');?>"/>
            <input type="hidden" name="showing_grid" id="showing_grid" value="<?php esc_html_e('Showing','ARM_MULTISUBSITE');?>"/>
            <input type="hidden" name="to_grid" id="to_grid" value="<?php esc_html_e('to','ARM_MULTISUBSITE');?>"/>
            <input type="hidden" name="of_grid" id="of_grid" value="<?php esc_html_e('of','ARM_MULTISUBSITE');?>"/>
            <input type="hidden" name="no_match_record_grid" id="no_match_record_grid" value="<?php esc_html_e('No matching item found.','ARM_MULTISUBSITE');?>"/>
            <input type="hidden" name="no_record_grid" id="no_record_grid" value="<?php esc_html_e('No any subsite found.','ARM_MULTISUBSITE');?>"/>
            <input type="hidden" name="filter_grid" id="filter_grid" value="<?php esc_html_e('filtered from','ARM_MULTISUBSITE');?>"/>
            <input type="hidden" name="totalwd_grid" id="totalwd_grid" value="<?php esc_html_e('total','ARM_MULTISUBSITE');?>"/>
            <?php wp_nonce_field( 'arm_wp_nonce' );?>
        </div>
        <div class="footer_grid"></div>
    </form>
</div>