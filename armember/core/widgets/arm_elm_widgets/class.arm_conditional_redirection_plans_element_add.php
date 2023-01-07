<?php
namespace ElementorARMELEMENT\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

if(! defined('ABSPATH')) exit;

class arm_conditional_redirect_plans_shortcode extends Widget_Base
{
    public function get_name()
    {
        return 'arm-condition-redirection-plan-shortcode';
    }

    public function get_title()
    {
        return esc_html('ARMember Conditional Redirection','ARMemeber').'<style>
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
        global $ARMember,$wp,$wpdb,$armainhelper,$arm_subscription_plans;

		$all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('arm_subscription_plan_id, arm_subscription_plan_name');
		
        /**START Fetch all shortcode controls from DB */
        /*END*/
        $this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'ARMember Conditional Redirection', 'ARMember' ),
			]
		);

		$this->add_control(
			'title',
			[
				'label' => esc_html__( 'Title', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'Conditional Redirection Rules (User Roles)',
				'label_block' => true,
			]
		);
		
		$this->add_control(
			'arm_shortcode_select',
			[
				'label' => esc_html__( 'Conditions', 'ARMember'),
				'type' => Controls_Manager::SELECT,
				'default' => 'having',
				'options' => [
					'having'=>__('Having','ARMember'),
					'nothaving' => __('Not Having','ARMember')
				],
				'label_block' => true,
				
			]
		);
		$plans = array();
		foreach($all_plans as $plan) {
			$key = $plan['arm_subscription_plan_id'];
			$val=$plan['arm_subscription_plan_name'];
			$plans[$key]=$val;
	}

		$this->add_control(
            'arm_show_plans',
            [
                'label' => esc_html__( 'Select User Plans', 'ARMember' ),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $plans,	
                'label_block' => true,
            ]
        );

		$this->add_control(
			'arm_redirect_url',
			[
				'label' => esc_html__( 'Redirect URL', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
				'placeholder'=>__('Please enter URL with http:// or https://.','ARMember'),
			]
		);
		
		$this->end_controls_section();
    }

	protected function render()
	{
		
		$settings = $this->get_settings_for_display();
		$str='';
		if(!empty($settings['arm_show_plans']))
		{
			foreach($settings['arm_show_plans'] as $sk)
			{
				$str .= $sk.',';
			}
		}

		if($settings['arm_redirect_url'] !='')
		{
			echo '<h5 class="title">';
			echo $settings['title'];
			echo '</h5>';
			echo '<div class="arm_select">';					
			echo '[arm_conditional_redirection_role condition="'.$settings['arm_shortcode_select'].'" redirect_to="'.$settings['arm_redirect_url'].'" plans="'.$str.'"]';
			echo '</div>';
		}
		else
		{
			echo "<h5>Please enter redirection URL</h5>";
		}
	}
}
