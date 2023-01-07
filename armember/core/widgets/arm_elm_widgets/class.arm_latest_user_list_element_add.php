<?php
namespace ElementorARMELEMENT\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

if(! defined('ABSPATH')) exit;

class arm_latest_user_list_element_shortcode extends Widget_Base
{
    public function get_name()
    {
        return 'arm-user-list-shortcode';
    }

    public function get_title()
    {
        return esc_html('ARMember latest user list','ARMemeber').'<style>
        .arm_element_icon{
			display: inline-block;
		    width: 35px;
		    height: 24px;
		    background-image: url('.MEMBERSHIP_IMAGES_URL.'/armember_icon.png);
		    background-repeat: no-repeat;
		    background-position: bottom;
		}
        </style>';
    }
    public function get_icon() {
		return 'arm_element_icon';
	}

    public function get_script_depends() {
		return [ 'elementor-arm-element' ];
	}
    protected function register_controls()
    {
        global $ARMember,$wp,$wpdb,$armainhelper,$arm_member_forms,$arm_subscription_plans;
		
        /**START Fetch all shortcode controls from DB */
        /*END*/
        $this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'ARMember Latest Users', 'ARMember' ),
			]
		);

		$this->add_control(
			'title',
			[
				'label' => esc_html__( 'Title', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'Recent Registered Users',
				'label_block' => true,
			]
		);
		$this->add_control(
			'arm_records_per_page',
			[
				'label' => esc_html__( 'Total Records To Display', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
				'default'=>'5',
			]
		);	
		$this->add_control(
			'arm_list_type_select',
			[
				'label' => esc_html__( 'Display Type', 'ARMember'),
				'type' => Controls_Manager::SELECT,
				'default' => 'list',
				'options' =>[
					'list'=> __('List','ARMember'),
					'slider'=> __('Slider','ARMember')
				],
				'label_block' => true,
				
			]
		);
		$this->add_control(
			'arm_slide_effect_select',
			[
				'label' => esc_html__( 'Display Type', 'ARMember'),
				'type' => Controls_Manager::SELECT,
				'default' => 'slide',
				'options' =>[
					'slide'=> __('Slide','ARMember'),
					'crossfade'=> __('Cross Fade','ARMember'),
					'directscroll'=> __('Direct Scroll','ARMember'),
					'cover'=> __('Cover','ARMember'),
					'uncover'=> __('Uncover','ARMember')
				],
				'label_block' => true,
			]
		);
		
		

		$this->end_controls_section();
    }

	protected function render()
	{
		global $wp, $wpdb, $current_user, $arm_errors, $ARMember, $arm_global_settings, $arm_subscription_plans, $arm_member_forms, $arm_members_activity,$is_globalcss_added,$arm_social_feature,$arm_members_directory;
		$settings = $this->get_settings_for_display();

		echo '<h5 class="title">';
		echo $settings['title'];
		echo '</h5>';
		echo '<div class="arm_select">';					
		$date_format = $arm_global_settings->arm_get_wp_date_format();
		

		$total_rec = $settings['arm_records_per_page'];
		$display_type = (!empty($settings['arm_list_type_select'])) ? $settings['arm_list_type_select'] : 'list';
		$slider_effect = (!empty($settings['arm_slide_effect_select'])) ? $settings['arm_slide_effect_select'] : 'slide';
		$arg = array(
			'orderby' => 'user_registered',
			'order' => 'DESC',
			'number' => ($total_rec + 10),
			'fields' => 'all',
							'meta_key'     => 'arm_primary_status',
							'meta_value'   => '1',
		);
		$users_admin = get_users($arg);
		$users = array();
		if (!empty($users_admin)) {
			$i = 1;
			foreach ($users_admin as $member_detail){
				$userID = $member_detail->ID;
				if ($i > $total_rec) {
					continue;
				}
				if (user_can($userID, 'administrator')) {
					continue;
				}
				$users[] = $member_detail;
				$i++;
			}
		}
		$frontfontstyle = $arm_global_settings->arm_get_front_font_style();
		$output = '';
		if(!empty($frontfontstyle['google_font_url'])){
			$output .= '<link id="google-font" rel="stylesheet" type="text/css" href="' . $frontfontstyle['google_font_url'] . '" />';
		}
		if( $display_type == 'slider' ){
			$random_wrapper_key = arm_generate_random_code(6);
			$profile_template = $arm_members_directory->arm_get_template_by_id(1);
			$profile_template_opt = $profile_template['arm_options'];
			$default_cover = $profile_template_opt['default_cover'];
			$output .= "<div class='arm_widget_slider_wrapper_container arm_slider_widget_{$random_wrapper_key}' data-effect='{$slider_effect}'>";
			$u = 1;
			$zindex = 1;
			foreach( $users as $us ){
				$random_inner_key = arm_generate_random_code(6);
				$output .= "<div class='arm_slider_widget_wrapper arm_widget_wrapper_{$random_inner_key}'>";
					$output .= "<style type='text/css' style='display:none;'>";
					$output .= ".arm_slider_widget_{$random_wrapper_key} .arm_widget_wrapper_{$random_inner_key} a:before{";
					$output .= "z-index:{$zindex} !important;";
					$output .= "}";
					$output .= ".arm_slider_widget_{$random_wrapper_key} .arm_widget_wrapper_{$random_inner_key} a span{";
					$output .= "z-index:".($zindex+1)." !important;";
					$output .= "}";
					$output .= "</style>";
					$user_id = $us->ID;
					$profile_cover = get_user_meta($user_id,'profile_cover',true);
					if( $profile_cover == '' || empty($profile_cover) ){
						$profile_cover = $default_cover;
					}
					$profile_avatar = get_avatar($user_id,95);
					$output .= "<div class='arm_slider_widget_header'>";

						$output .= "<div class='arm_slider_widget_user_cover'>";
							$output .= "<img src='{$profile_cover}' style='width:100%;height:100%;border-radius:0;-webkit-border-radius:0;-o-border-radius:0;-moz-borde-radius:0;' />";
						$output .= "</div>";

						$output .= "<div class='arm_slider_widget_avatar'>";
							$output .= $profile_avatar;
						$output .= "</div>";

					$output .= "</div>";

					$output .= "<div class='arm_slider_widget_content_wrapper'>";
						$profile_link = $arm_global_settings->arm_get_user_profile_url($user_id);
													$common_messages = $arm_global_settings->arm_get_all_common_message_settings();
													$arm_member_since_label = (isset($common_messages['arm_profile_member_since']) && $common_messages['arm_profile_member_since'] != '' ) ? $common_messages['arm_profile_member_since'] : __('Member Since', 'ARMember');
						$output .= "<a href='{$profile_link}' class='arm_slider_widget_profile_link'><span>".get_user_meta($user_id,'first_name',true).' '.get_user_meta($user_id,'last_name',true)."</span></a>";

						$output .= "<div class='arm_slider_widget_user_info'>";

							$output .= "<div class='arm_slider_widget_user_info_row'>";

								$output .= "<div class='arm_slider_widget_user_info_row_left'>";
									$output .= $arm_member_since_label;
								$output .= "</div>";

								$output .= "<div class='arm_slider_widget_user_info_row_right'>";
																	
									$output .= date_i18n($date_format,strtotime($us->user_registered));
								$output .= "</div>";

							$output .= "</div>";

						$output .= "</div>";

					$output .= "</div>";
				$output .= "</div>";
				$u++;
				$zindex++;
			}
			$output .= "</div>";
			$output .= "<script type='text/javascript'>";
			$output .= "jQuery(document).ready(function (){arm_slider_widget_init();});";
			$output .= "</script>";
		} else {
			$output .= '<style type="text/css">';
			$membersWrapperClass = ".arm_widget_members";
				$output .= "
					$membersWrapperClass .arm_member_info_right a{
						{$frontfontstyle['frontOptions']['link_font']['font']}
					}
					$membersWrapperClass .arm_time_block{
						{$frontfontstyle['frontOptions']['level_4_font']['font']}
					}
				";
			$output .= '</style>';
			$get_global_css = $ARMember->arm_set_global_css(false);
			$output .= $get_global_css;
			$output .= '<div class="arm_widget_container arm_widget_members">';
			$output .= '<div class="arm_member_listing_container">';
			$output .= '<div class="arm_member_listing_wrapper">';
			if(!empty($users)){
				foreach ($users as $us)
				{
					$output .= "<div class='arm_member_info_block'>";
					$output .= "<div class='arm_member_info_left'>";
					$output .= "<div class='arm_user_avatar'>";
					$output .= get_avatar($us->ID, 60);
					$output .= "</div>";
					$output .= "</div>";
					$output .= "<div class='arm_member_info_right'>";
					$user_name = $us->first_name . ' ' . $us->last_name;
					if (empty($us->first_name) && empty($us->last_name)) {
						$user_name = $us->user_login;
					}
					if($arm_social_feature->isSocialFeature){
						$output .= "<a href='" . $arm_global_settings->arm_get_user_profile_url($us->ID) . "'>" . $user_name . "</a>";
					}
					else{
						$output .= $user_name;
					}
					$output .= "<span class='arm_time_block'>";
					$output .= __('Joined', 'ARMember');
					$output .= " " . $arm_global_settings->arm_time_elapsed(strtotime($us->user_registered));
					$output .= "</span>";
					$output .= "</div>";
					$output .= "</div>";
				}
			}
			$output .= '</div>';
			$output .= '<div class="armclear"></div>';
			$output .= '</div>';
			$output .= '</div>';
		}
		echo $output;
		echo '</div>';
	}
}
