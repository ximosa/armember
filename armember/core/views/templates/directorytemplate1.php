<?php
global $arm_member_forms, $arm_members_directory;
if (isset($user) && !empty($user))
{
    $tempopt = $templateOpt['arm_options'];
    $separator_class = ($n % 3 == 0) ? 'arm_directorytemplate1_last_field' : '';
    $first_class = ($f == 0 || ($f % 3 == 0) ) ? 'arm_first_user_block' : '';
	$fileContent .= '<div class="arm_user_block '.$separator_class.' '.$first_class.'" >';
		$fileContent .= '<a href="' . $user['user_link'] . '" class="arm_dp_user_link"><div class="arm_user_avatar">' . $user['profile_picture'] . '</div></a>';
		$fileContent .= '<div class="armclear"></div>';
		$fileContent .= '<a class="arm_user_link" href="' . $user['user_link'] . '">' . $user['full_name'];
		$fileContent .= '</a>';

        $member_field_detail_content = $arm_members_directory->arm_template_display_member_details($tempopt,$user,1);

        $fileContent .= $member_field_detail_content['member_joining_date_content'];

        if(!empty($user['arm_badges_detail'])){
		$fileContent .= "<div class='arm_badges_detail'>";
		$fileContent .= $user['arm_badges_detail'];
		$fileContent .= "</div>";
    }
        
        $fileContent .= $member_field_detail_content['member_detail_content'];
                
        $fileContent .= '<div class="armclear"></div>';
		$fileContent .= '<div class="arm_view_profile_btn_wrapper"><a href="' . $user['user_link'] . '" class="arm_view_profile_user_link">' . $arm_view_profile_label . '</a></div>';
		$fileContent .= '<div class="armclear"></div>';
		
        $slected_social_fields = isset($tempopt['arm_social_fields']) ? $tempopt['arm_social_fields'] : array();
        $slected_social_fields_content = "";
        if (!empty($slected_social_fields)) {
            foreach ($slected_social_fields as $skey) {
                if (isset($args['is_preview']) && $args['is_preview'] == 1) {
                    $slected_social_fields_content .= "<div class='arm_social_prof_div arm_user_social_fields arm_social_field_{$skey}'><a target='_blank' href='#'></a></div>";
                } else {
                    $spfMetaKey = 'arm_social_field_'.$skey;
                    if (in_array($skey, $slected_social_fields)) {
                        $skey_field = get_user_meta($user['ID'],$spfMetaKey,true);
                        if( isset($skey_field) && !empty($skey_field) ) {
                            $slected_social_fields_content .= "<div class='arm_social_prof_div arm_user_social_fields arm_social_field_{$skey}'><a target='_blank' href='{$skey_field}'></a></div>";
                        }
                    }
                }
            }
        }

        $arm_user_social_blocks_style = "style='display:none;'";
        if(!empty($slected_social_fields_content)) {
            $arm_user_social_blocks_style = "style='display:block;'";
        }
        $fileContent .= "<div class='arm_user_social_blocks' ".$arm_user_social_blocks_style.">";
        $fileContent .= $slected_social_fields_content;
		$fileContent .= '</div>';
	$fileContent .= '</div>';
}