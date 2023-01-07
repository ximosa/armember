<?php
namespace ElementorARMELEMENT\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

if(! defined('ABSPATH')) exit;

class arm_user_badge_element_shortcode extends Widget_Base
{
    public function get_name()
    {
        return 'arm-user-badge-shortcode';
    }

    public function get_title()
    {
        return esc_html('ARMember User badge','ARMemeber').'<style>
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
				'label' => esc_html__( 'ARMember User Badge', 'ARMember' ),
			]
		);

		$this->add_control(
			'title',
			[
				'label' => esc_html__( 'Title', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'User Badges',
				'label_block' => true,
			]
		);
		$this->add_control(
			'arm_user_id',
			[
				'label' => esc_html__( 'User Id', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
				'default'=>'',
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
		echo do_shortcode('[arm_user_badge user_id="'.$settings['arm_user_id'].'"]');
		echo '</div>';
	}
}
