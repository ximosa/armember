<?php
namespace ElementorARMELEMENT\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

if(! defined('ABSPATH')) exit;

class arm_partial_content_restriction_shortcode extends Widget_Base
{
    public function get_name()
    {
        return 'arm-partial-content-restriction-shortcode';
    }

    public function get_title()
    {
        return esc_html('ARMember Content Restriction','ARMemeber').'<style>
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

		$all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('arm_subscription_plan_id, arm_subscription_plan_name');
		
        /**START Fetch all shortcode controls from DB */
        /*END*/
        $this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'ARMember Partial Content Restriction', 'ARMember' ),
			]
		);

		$this->add_control(
			'arm_shortcode_select',
			[
				'label' => esc_html__( 'Restriction Type', 'ARMember'),
				'type' => Controls_Manager::SELECT,
				'default' => 'hide',
				'options' =>[
					'hide'=>__('Hide content only for','ARMember'),
					'show'=>__('Show content only for','ARMember')
				],
				'label_block' => true,
				
			]
		);

		$plans = array();
		$plans['registered'] = __('LoggedIn Users','ARMember');
		$plans['unregistered'] = __('Non LoggedIn Users','ARMember');
		foreach($all_plans as $plan) {
			$key = $plan['arm_subscription_plan_id'];
			$val=$plan['arm_subscription_plan_name'];
			$plans[$key]=$val;
		}
		$plans['any_plan'] =__('Any Plan','ARMember');
		

		$this->add_control(
            'arm_show_plans',
            [
                'label' => esc_html__( 'Select User Roles', 'ARMember' ),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $plans,	
                'label_block' => true,
            ]
        );

		$this->add_control(
			'arm_display_textarea',
			[
				'label' => esc_html__( 'Content to display on true condition', 'ARMember'),
				'type' => Controls_Manager::TEXTAREA,
				'default' => 'Content Goes Here if condition is true',
				'label_block' => true,
				'classes'=>'',
			]
		);
		$this->add_control(
			'arm_display_textarea_else',
			[
				'label' => esc_html__( 'Content to display on false condition', 'ARMember'),
				'type' => Controls_Manager::TEXTAREA,
				'default' => 'Content Goes Here if Condition is false',
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
			$str='';
			if(!empty($settings['arm_show_plans']))
			{
				foreach($settings['arm_show_plans'] as $sk)
				{
					$str .= $sk.',';
				}
			}
			echo  do_shortcode('[arm_restrict_content plan="'.$sk.'" type="'.$settings['arm_shortcode_select'].'"]'.$settings['arm_display_textarea'].' [armelse]
			'.$settings['arm_display_textarea_else'].'
			[/arm_restrict_content]');
		echo '</div>';
	}
}
