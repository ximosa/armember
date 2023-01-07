<?php
namespace ElementorARMELEMENT\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

if(! defined('ABSPATH')) exit;

class arm_check_user_is_in_trial_shortcode extends Widget_Base
{
    public function get_name()
    {
        return 'arm-user-is-in-trial-shortcode';
    }

    public function get_title()
    {
        return esc_html('ARMember User Trial Contents','ARMemeber').'<style>
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
				'label' => esc_html__( 'ARMember Is User In Trial Period', 'ARMember' ),
			]
		);

		$this->add_control(
			'arm_shortcode_select',
			[
				'label' => esc_html__( 'Display Content Based On', 'ARMember'),
				'type' => Controls_Manager::SELECT,
				'default' => '',
				'options' =>[
					'arm_if_user_in_trial'=>__('If User In Trial Period','ARMember'),
					'arm_not_if_user_in_trial'=>__('If User Not In Trial','ARMember')
				],
				'label_block' => true,
				
			]
		);
		$this->add_control(
			'arm_display_textarea',
			[
				'label' => esc_html__( 'Content to display', 'ARMember'),
				'type' => Controls_Manager::TEXTAREA,
				'default' => '',
				'label_block' => true,
				'classes'=>'',
			]
		);

		$this->end_controls_section();
    }

	protected function render()
	{
		$settings = $this->get_settings_for_display();
		echo '<div class="arm_select">';
			$arm_shortcode='';
			echo do_shortcode('['.$settings['arm_shortcode_select'].']'.$settings['arm_display_textarea'].'[/'.$settings['arm_shortcode_select'].']');
		echo '</div>';
	}
}
