<?php
namespace ElementorARMELEMENT\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

if(! defined('ABSPATH')) exit;

class arm_user_plan_info_element_shortcode extends Widget_Base
{
    public function get_name()
    {
        return 'arm-user-plan-info-shortcode';
    }

    public function get_title()
    {
        return esc_html('ARMember Plan Info','ARMemeber').'<style>
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
        $all_plans = $arm_subscription_plans->arm_get_all_subscription_plans('arm_subscription_plan_id, arm_subscription_plan_name');
		
        /**START Fetch all shortcode controls from DB */
        /*END*/
        $this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'ARMember Membership Shortcode', 'ARMember' ),
			]
		);
		$this->add_control(
			'title',
			[
				'label' => esc_html__( 'Title', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'ARMember Plan Info'
			]
		);
		$plans = array();
		$cnt=0;
		foreach($all_plans as $plan) {
			if($cnt == 0)
			{
				$default = $plan['arm_subscription_plan_id'];
			}
			$key = $plan['arm_subscription_plan_id'];
			$val=$plan['arm_subscription_plan_name'];
			$plans[$key]=$val;
			$cnt++;
		}
		$this->add_control(
			'arm_show_plans',
			[
				'label' => esc_html__( 'Select User Plans', 'ARMember' ),
				'type' => Controls_Manager::SELECT,
				'multiple' => true,
				'options' => $plans,	
				'label_block' => true,
				'default' => $default
			]
		);

		$this->add_control(
			'arm_select_plan_info',
			[
				'label' => esc_html__( 'Select Plan Information', 'ARMember' ),
				'type' => Controls_Manager::SELECT,
				'label_block' => true,
				'options'=>[
					'arm_start_plan'=>__('Start Date','ARMember'),
					'arm_expire_plan'=>__('Expire Date','ARMember'),
					'arm_trial_start'=>__('Trial Start Date','ARMember'),
					'arm_trial_end'=>__('Trial Start Date','ARMember'),
					'arm_grace_period_end'=>__('Grace End Date','ARMember'),
					'arm_user_gateway'=>__('Paid By','ARMember'),
					'arm_completed_recurring'=>__('Completed Recurrence','ARMember'),
					'arm_next_due_payment' => __('Next Due Date','ARMember'),
					'arm_payment_mode' => __('Payment Mode','ARMember'),
					'arm_payment_cycle' => __('Payment Cycle','ARMember'),
				],
				'default'=>'arm_start_plan'
			]
		);
	
		$this->end_controls_section();
	}

	protected function render()
	{
		
		$settings = $this->get_settings_for_display();
			echo '<h5 class="title">';
			echo $settings['title'];
			echo '</h5>';
			echo '<div class="arm_select">';
			echo do_shortcode('[arm_user_planinfo plan_id="'.$settings['arm_show_plans'].'" plan_info="'.$settings['arm_select_plan_info'].'"]');
			echo '</div>';
		
	}
}
