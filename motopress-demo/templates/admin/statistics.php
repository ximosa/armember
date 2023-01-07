<style type="text/css">
	.selected{
		background: #008ec2 !important;
    	border-color: #006799 !important;
    	color: white !important;
	}
	.button:hover{
		background: #008ec2 !important;
    	border-color: #006799 !important;	
    	color: white !important;
	}
</style>

<div class="wrap">
	<form id="mp_demo_admin_statistics" enctype="multipart/form-data" method="GET" name="" action="">
		<input type="hidden" name="page" value="mp-demo-statistics">

		<div class="mp-demo-panel">
			<label for="mp-demo-datepicker-start"><?php _e('From', 'mp-demo'); ?></label>
			<input type="text" id="mp-demo-datepicker-start" name="mp-demo-start"
			       value="<?php echo $total['start']; ?>">
			<label for="mp-demo-datepicker-end"><?php _e('To', 'mp-demo'); ?></label>
			<input type="text" id="mp-demo-datepicker-end" name="mp-demo-end"
			       value="<?php echo $total['end']; ?>">
			<input type="submit" id="mp-demo-update-statistics" class="button"
			       value="<?php _e('Update', 'mp-demo'); ?>">
		</div>
		<div class="mp-demo-content">
			<h3><?php _e('Sandboxes Activity for', 'mp-demo'); ?> <?php echo $total['start']; ?> &ndash; <?php echo $total['end']; ?></h3>
			<table class="wp-list-table widefat fixed striped mp-demo-table">
				<thead>
				<tr class="mp-demo-table-header">
					<th id="cb" class="manage-column column-cb check-column" scope="col"></th>
					<th id="columnname" class="manage-column column-columnname"
					    scope="col"><?php _e('Creation Date', 'mp-demo'); ?></th>
					<th id="columnname" class="manage-column column-columnname"
					    scope="col"><?php _e('Total Created', 'mp-demo'); ?></th>
					<th id="columnname" class="manage-column column-columnname"
					    scope="col"><?php _e('Total Activated', 'mp-demo'); ?></th>
				</tr>
				</thead>
				<tbody id="the-list">
				<?php
				$i = 0;
				foreach ($table as $key => $row) {
					$str = '<tr>';
					$str .= '<td>' . '</td>';
					$str .= '<td>' . $row['date'] . '</td><td>' . $row['created'] . '</td><td>' . $row['activated'] . '</td>';
					$str .= '</tr>';
					echo $str;
				}
				?>
				</tbody>
				<tfoot>
				<tr class="mp-demo-table-header">
					<th id="cb" class="manage-column column-cb check-column" scope="col"></th>
					<th id="columnname" class="manage-column column-columnname"
					    scope="col"><?php _e('Creation Date', 'mp-demo'); ?></th>
					<th id="columnname" class="manage-column column-columnname"
					    scope="col"><?php _e('Total Created', 'mp-demo'); ?></th>
					<th id="columnname" class="manage-column column-columnname"
					    scope="col"><?php _e('Total Activated', 'mp-demo'); ?></th>
				</tr>
				</tfoot>
			</table>
			<br>

			<h3><?php _e('Summary for', 'mp-demo'); ?> <?php echo $total['start']; ?> &ndash; <?php echo $total['end']; ?></h3>

			<p>
				<?php _e('Total Created:', 'mp-demo'); ?> <b><?php echo $total['created']; ?></b>
			</p>

			<p>
				<?php _e('Total Activated:', 'mp-demo'); ?> <b><?php echo $total['activated']; ?></b>
			</p>
		</div>



		<div class="mp-demo-content" style="margin-top: 3em;">
			<h3>User Session Summary</h3>
			<table class="wp-list-table widefat fixed striped mp-demo-table">
				<thead>
					<tr class="mp-demo-table-header">
						<th id="columnname" class="manage-column column-columnname" scope="col" width="5%"><?php _e('Id', 'mp-demo'); ?></th>
						<th id="columnname" class="manage-column column-columnname" scope="col" width="12%"><?php _e('User', 'mp-demo'); ?></th>
						<th id="columnname" class="manage-column column-columnname" scope="col" width="20%"><?php _e('Site', 'mp-demo'); ?></th>
					    <th id="columnname" class="manage-column column-columnname" scope="col" width="30%"><?php _e('Referrer URL', 'mp-demo'); ?></th>
						<th id="columnname" class="manage-column column-columnname" scope="col" width="10%"><?php _e('IP Address', 'mp-demo'); ?></th>
					    <th id="columnname" class="manage-column column-columnname" scope="col" width="10%"><?php _e('Browser', 'mp-demo'); ?></th>
						<th id="columnname" class="manage-column column-columnname" scope="col" width="10%"><?php _e('Country', 'mp-demo'); ?></th>
						<th id="columnname" class="manage-column column-columnname" scope="col" width="10%"><?php _e('Created Date', 'mp-demo'); ?></th>
						<th id="columnname" class="manage-column column-columnname" scope="col" width="10%"><?php _e('Active session', 'mp-demo'); ?></th>
					</tr>
				</thead>
				
				<tbody>
					<?php

					global $wpdb;

					if(!empty($statistic_data)){

					foreach( $statistic_data as $k => $data ){
						$blog_url = $wpdb->get_row( $wpdb->prepare("SELECT domain,path FROM `".$wpdb->base_prefix."blogs` WHERE blog_id = %d",$data->blog_id));

						$login_time = $data->login_time;
						$logout_time = $data->logout_time;
						
						$session_time = "";
						if(!empty($logout_time) && !empty($login_time) && $logout_time != "0000-00-00 00:00:00" && $login_time != "0000-00-00 00:00:00" ){
							$datetime1 = new DateTime($login_time);
							$datetime2 = new DateTime($logout_time);
							$interval = $datetime1->diff($datetime2);
							$session_time = $interval->format('%H:%I:%S');	
						}
						?>
						<tr>
							<td><?php echo $data->user_id; ?></td>
							<td><?php echo $data->email; ?></td>
							<td><?php echo ($blog_url) ? $blog_url->domain.$blog_url->path : ''; ?></td>
							<td><?php echo $data->referrer_url; ?></td>
							<td><?php echo $data->ip_address; ?></td>
							<td><?php echo $data->browser_name.' ('.$data->browser_version.')'; ?></td>
							<td><?php echo $data->country; ?></td>
							<td><?php echo $data->created_date; ?></td>
							<td><?php echo $session_time; ?></td>

						</tr>
						<?php
					}
					}
				?>					

				</tbody>
				<tfoot>
					<tr class="mp-demo-table-header">
						<th colspan="9"><?php echo (!empty($pagination))?$pagination:''; ?></th>	
					</tr>
				</tfoot>
			</table>
		</div>
	</form>
</div>