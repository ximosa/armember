<?php
global $armember_demo_setup;

$plugin_list = $armember_demo_setup->armember_caps_with_addons();
get_header();
?>
<div class="main_container">
		<div class="armember_logo_container">
			<img src="<?php echo ARMEMBER_TESTDRIVE_SETUP_URL.'/images/logo.png' ?>" width="" height="" />
		</div>
		<input type="hidden" id="is_ssl" value="<?php echo is_ssl(); ?>" />
		<div class="mp_demo_success">
			<div class="mp_demo_thanks">
				Thank you for submission.<br>
				<center><table style="margin-top: 20px;padding:20px;background:#fffbb7;border-radius:1px solid #eae6a3;border-radius:8px;" cellpadding="5">
					<tr>
						<th align="right">Site URL:</th>
						<td id="testdrive_created_site_url"><a target="_blank" href="#" style="text-decoration: none;color:#6755e5;"></a></td>
					</tr>
					<tr>
						<th align="right">Login:</th>
						<td id="testdrive_created_login"></td>
					</tr>
					<tr>
						<th align="right">Password:</th>
						<td id="testdrive_created_password"></td>
					</tr>
				</table></center>
			</div>

			<div class="mp_demo_desc_first">
				Please start exploring our product.
			</div>
			<div class="mp_demo_desc_second">
				(Demo will expire in 60 minutes)
			</div>
		</div>

		<input type="hidden" id="ajaxurl" value="<?php echo admin_url('admin-ajax.php'); ?>" />


		<form action="<?php echo the_permalink(); ?>" method="post" id="try-demo" onSubmit="return validate_form();" class="try-demo">
			<input name="mp_demo_create_sandbox" type="hidden" value="1">
			<input name="action" type="hidden" value="route_url">

			<h3>Please signup demo of ARForms with Addons!!</h3>
			<p class="input-wrapper">
				<label for="mp_email">Your email:</label><br>
				<input type="email" id="mp_email" name="mp_email" class="mp-demo-email" placeholder="example@mail.com" required="required">
			
			</p>
			<input name="mp_source_id" type="hidden" value="1">
			<input name="mp_demo_action" type="hidden" value="send_response">
			<input name="controller" type="hidden" value="mail">
			<input name="mp_demo_url" type="hidden" value="<?php echo site_url(); ?>">
			<input name="security" type="hidden" value="<?php echo wp_create_nonce('mp-ajax-nonce'); ?>">
			
			
			<div class="armember_addon_container">
				
				<?php 
					$output = "";
				    
				    if(!empty($plugin_list)){
				    	$i=0;
				    	foreach ($plugin_list as $plugin_dir => $plugin) {	
				    		$id = str_replace(" ", "_", strtolower($plugin['name']));   	
					    	
			    			$chkdsb="";
			    			if($plugin['name']=='ARMember'){
			    				$chkdsb = 'checked="checked" disabled';
			    				$output .= "<div class='armember_label_heading'>";
			    				$output .= "<div class='armember_test_custom_checkbox'>";
			    				$output .= "<div class='armember_custom_checkbox_wrapper'>";
			    				$output .= "<input ".$chkdsb." type='checkbox' name='armember_plugin[]' id='".$id."' value='".$plugin_dir."' />";
			    				$output .= "<svg width='17px' height='17px'>";
			    					$output .= "<g id='unchecked'><path fill='#FFFFFF' d='M17,0v17H0V0H17 M15,2H2v13h13V2L15,2z'/></g>";
	    							$output .= "<g id='checked'><path fill='#FFFFFF' d='M17,0v17H0V0H17 M15,2H2v13h13V2L15,2z'/><polygon fill='#ffffff' points='13.768,5.841 12.335,4.409 7.142,9.602 4.928,7.388 3.514,8.803 7,12.287 7.015,12.273 7.175,12.434'/></g>";
			    					$output .= "</svg>";
			    				$output .= "</div>";
		    					$output .= "<span><label for='".$id."'>ARMember Demo</label></span>";
			    				$output .= "</div></div>";
		    					$output .= "<div class='armember_addon_desc'>Please Choose Addons</div>";
			    				continue;
			    			}
			    			
			    			$output .= "<div class='armember_addon_list'>";
			    			
			    			$output .= "<div class='armember_test_custom_checkbox'>";
			    				$output .= "<div class='armember_custom_checkbox_wrapper'>";
			    					$output .= "<input type='checkbox' name='armember_plugin[]' id='{$id}' value='{$plugin_dir}' />";
			    					$output .= "<svg width='17px' height='17px'>";
			    						$output .= "<g id='unchecked'><path fill='#6755E5' d='M17,0v17H0V0H17 M15,2H2v13h13V2L15,2z'/></g>";
			    						$output .= "<g id='checked'><path fill='#6755E5' d='M17,0v17H0V0H17 M15,2H2v13h13V2L15,2z'/><polygon fill='#6755E5' points='13.768,5.841 12.335,4.409 7.142,9.602 4.928,7.388 3.514,8.803 7,12.287 7.015,12.273 7.175,12.434'/></g>";
			    					$output .= "</svg>";
			    				$output .= "</div>";
			    				$output .= "<span>";
			    					$output .= "<label for='{$id}' style='margin-left: 4px;'>{$plugin['name']}</label>";
			    				$output .= "</span>";
			    			$output .= "</div>";
			    			$output .= "</div>";
			    			$i++;
					    }
					    $output .= "<input type='hidden' value='armember/armember.php' name='armember_plugin[]' id='armember'>";
					    $output .= "<br>";
				    }
				    echo $output;
				?>
				
			</div>


			<?php 
			
				$g_recaptcha = array();
				$general_setting = get_option('mp_demo_general');
		        if(!empty($general_setting)){
		            $mp_demo_general_setting = maybe_unserialize($general_setting);
		            if(isset($mp_demo_general_setting['recaptcha']) && !empty($mp_demo_general_setting['recaptcha'])){
		                $g_recaptcha['site_key'] = $mp_demo_general_setting['recaptcha']['site_key'];
		                $g_recaptcha['secret_key'] = $mp_demo_general_setting['recaptcha']['secret_key'];
		                $g_recaptcha['lang'] = $mp_demo_general_setting['recaptcha']['lang'];
		            }
		        }
			?>

			<?php if(!empty($g_recaptcha)){ ?>
			<div class="testdrive_g_recaptch_div">
		    	<div class="g-recaptcha" data-sitekey="<?php echo (isset($g_recaptcha['site_key']))?$g_recaptcha['site_key']:''; ?>"></div>
		    </div>
		    <?php } ?>
		    				
			<div class="demo_submit_btn">
				<input type="submit" name="submit" class="mp-submit" value="START DEMO">
				<div class="armember_testdrive_loader">
					<div class="arf_imageloader"></div>
					<span>Creating your sandbox...</span>
				</div>
				<div class="armember_testdrive_response_err_div" style="color:red;">
				
				</div>
			</div>

		</form>
	</div>
<?php
get_footer();