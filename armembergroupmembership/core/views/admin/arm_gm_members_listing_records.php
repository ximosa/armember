<?php
	$arm_gm_grid_columns = array(
		'arm_gm_user_id' => __('User ID', 'ARMGroupMembership'),
		'arm_gm_user_login' => __('Parent Username', 'ARMGroupMembership'),
		'arm_gm_user_email' => __('Parent Email', 'ARMGroupMembership'),
		'arm_gm_child_users' => __('Total Child Users', 'ARMGroupMembership'),
		'arm_gm_total_coupons' => __('Total Invite Codes', 'ARMGroupMembership'),
		'arm_gm_remain_coupons' => __('Remaining Invite Codes', 'ARMGroupMembership'),		
	);
?>

<style type="text/css">
	.buttons-colvis{ display: none !important;}
</style>

<div class="arm_members_list">
	<div id="armmainformnewlist" class="arm_filter_grid_list_container">
    <div class="arm_loading_grid" style="display: none;"><img src="<?php echo MEMBERSHIP_IMAGES_URL; ?>/loader.gif" alt="Loading.."></div>
    <div class="response_messages"></div>
    <div class="armclear"></div>
    <table cellpadding="0" cellspacing="0" border="0" class="display" id="armember_datatable">
        <thead>
            <tr>
            	<?php
            		foreach($arm_gm_grid_columns as $arm_gm_key => $arm_gm_column_val)
            		{
            	?>
		            	<th data-key="<?php echo $arm_gm_key; ?>" class="arm_grid_th_<?php echo $arm_gm_key; ?>" >
		            		<?php echo $arm_gm_column_val; ?>
		        		</th>
		        <?php
            		}
            	?>
            	<th class="armGridActionTD"></th>
        	</tr>
        </thead>
    </table>
    <div class="armclear"></div>
</div>

<div style="margin-top: 25px; float: right;">
	<a href="<?php echo ARM_GROUP_MEMBERSHIP_URL."/documentation/"; ?>" target="_blank">Documentation</a>
</div>



<script type="text/javascript" charset="utf-8">
	function show_grid_loader() {
	    jQuery('.arm_loading_grid').show();
	}


	function arm_load_gm_list_grid(is_filtered)
	{
		var __ARM_Showing = '<?php echo addslashes(esc_html__('Showing','ARMGroupMembership')); ?>';
		var __ARM_Showing_empty = '<?php echo addslashes(esc_html__('Showing 0 to 0 of 0 entries','ARMGroupMembership')); ?>';
		var __ARM_to = '<?php echo addslashes(esc_html__('to','ARMGroupMembership')); ?>';
	    var __ARM_of = '<?php echo addslashes(esc_html__('of','ARMGroupMembership')); ?>';
	    var __ARM_Entries = ' <?php _e('entries','ARMGroupMembership'); ?>';
	    var __ARM_Show = '<?php echo addslashes(esc_html__('Show','ARMGroupMembership')); ?> ';
	    var __ARM_NO_FOUND = '<?php echo addslashes(esc_html__('No members have group membership.','ARMGroupMembership')); ?>';
	    var __ARM_NO_MATCHING = '<?php echo addslashes(esc_html__('No matching records found.','ARMGroupMembership')); ?>';

	    var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
	    var table = jQuery('#armember_datatable').dataTable({
	    	"sDom": '<"H"CBfr>t<"footer"ipl>',
			"sPaginationType": "four_button",
			"sProcessing": show_grid_loader(),
	        "oLanguage": {
	            "sProcessing": show_grid_loader(),
	            "sInfo": __ARM_Showing + " _START_ " + __ARM_to + " _END_ " + __ARM_of + " _TOTAL_ " + __ARM_Entries,
	            "sInfoEmpty": __ARM_Showing_empty,
	           
	            "sLengthMenu": __ARM_Show + "_MENU_" + __ARM_Entries,
	            "sEmptyTable": __ARM_NO_FOUND,
	            "sZeroRecords": __ARM_NO_MATCHING,
	        },
	        "language": {
	        	"searchPlaceholder": "Search",
                "search":"",
	        },
	        "buttons": [{
                "extend":"colvis",
                "columns":":not(.noVis)",
            }],
	        "bProcessing": false,
	        "bServerSide": true,
	        "sAjaxSource": ajax_url,
			"bJQueryUI": true,
			"bPaginate": true,
			"sServerMethod": "POST",
			"bAutoWidth" : false,
			"aaSorting": [],
			"ordering": false,
			"fixedColumns": false,
			"aoColumnDefs": [
				{ "bVisible": false, "aTargets": [] },
				{ "sClass": 'center', "aTargets": [] },
				{ "bSortable": false, "aTargets": [] }
			],
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
			"fnPreDrawCallback": function () {
	            jQuery('.arm_loading_grid').show();
	        },
	        "fnCreatedRow": function (nRow, aData, iDataIndex) {
	            jQuery(nRow).find('.arm_grid_action_wrapper').each(function () {
	                jQuery(this).parent().addClass('armGridActionTD');
	                jQuery(this).parent().attr('data-key', 'armGridActionTD');
	            });
	        },
	        "fnStateLoadParams": function (oSettings, oData) {
	            oData.iLength = 10;
	            oData.iStart = 0;
	        },
			"fnServerParams": function (aoData) {
	            aoData.push({'name': 'action', 'value': 'get_arm_gm_admin_data'});
	        },
			"fnDrawCallback":function(){
				jQuery('.arm_loading_grid').hide();
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
		});
	}

	jQuery(document).ready( function () {
	    arm_load_gm_list_grid(false);
	});
</script>