<?php
namespace ElementorARMELEMENT\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

if(! defined('ABSPATH')) exit;

class arm_user_info_element_shortcode extends Widget_Base
{
    public function get_name()
    {
        return 'arm-user-info-shortcode';
    }

    public function get_title()
    {
        return esc_html('ARMember User Info','ARMemeber').'<style>
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
		$arm_form =array();
        $arm_form['Please select a valid form']='Select Form type';
		
        /**START Fetch all shortcode controls from DB */
        /*END*/
        $this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'ARMember Membership Shortcode', 'ARMember' ),
			]
		);

		$this->add_control(
			'arm_shortcode_select',
			[
				'label' => esc_html__( 'Select Option', 'ARMember'),
				'type' => Controls_Manager::SELECT,
				'default' => 'arm_userid',
				'options' =>[
					'arm_userid'=>__('User ID','ARMember'),
					'arm_username'=>__('Username','ARMember'),
					'arm_displayname'=>__('Display Name','ARMember'),
					'arm_firstname_lastname'=>__('Firstname Lastname', 'ARMember'),
					'arm_user_plan'=>__('User Plan','ARMember'),
					'arm_avatar'=>__('Avatar','ARMember'),
					'arm_usermeta'=>__('Custom Meta','ARMember')
				],
				'label_block' => true,
				
			]
		);
		$this->add_control(
			'arm_custom_meta',
			[
				'label'=>__('Enter User Meta Name','ARMember'),
				'type'=> Controls_Manager::TEXT,
				'default'=>'',
				'label_block' => true,
				'condition'=>['arm_shortcode_select'=>'arm_usermeta']
			]
			);
		

		$this->end_controls_section();
    }

	protected function render()
	{
		$settings = $this->get_settings_for_display();
		echo '<div class="arm_select">';
			$arm_shortcode='';
			echo  do_shortcode('['.$settings['arm_shortcode_select'].']');
		echo '</div>';
	}
}
