<?php
namespace ElementorARMELEMENT\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

if(! defined('ABSPATH')) exit;

class arm_membership_login_element_shortcode extends Widget_Base
{
    public function get_name()
    {
        return 'arm-login-element-shortcode';
    }

    public function get_title()
    {
        return esc_html('ARMember Login Form','ARMemeber').'<style>
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
        /**START Fetch all shortcode controls from DB */
        /*END*/
        $this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'ARMember Login', 'ARMember' ),
			]
		);
        $this->add_control(
			'title',
			[
				'label' => esc_html__( 'Title', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
			]
		);
		$forms = $wpdb->get_results("SELECT * FROM `" . $ARMember->tbl_arm_forms . "` WHERE `arm_form_type`='login' ORDER BY `arm_form_id` ASC", ARRAY_A);
		$default = $cnt =0;
		if(!empty($forms)){
			foreach ($forms as $form) {
				$form_id = $form['arm_form_id'];
				if($cnt == 0)
				{
					$default = $form_id;
				}
				$cnt++;
				$form_slug = $form['arm_form_slug'];
				$form_shortcodes['forms'][$form_id] = array(
					'id' => $form['arm_form_id'],
					'slug' => $form['arm_form_slug'],
					'name' => __( $form['arm_form_label']." (ID: ".$form['arm_form_id'].")","ARMember"),
				);
				$arm_form[$form_id]=$form_shortcodes['forms'][$form_id]['name'];
			} 
		}

		$this->add_control(
			'arm_shortcode_select',
			[
				'label' => esc_html__( 'Select Forms', 'ARMember'),
				'type' => Controls_Manager::SELECT,
				'default' => $default,
				'options' => $arm_form,
				'label_block' => true,
				
			]
		);
		$this->add_control(
			'arm_frm_type',
			[
				'label' => esc_html__( 'Form Type', 'ARMember'),
				'type' => Controls_Manager::SELECT,
				'default' => 'arm_form',
				'options' => [
					'arm_form' => esc_html__( 'Internal', 'ARMember' ),
					'popup' =>esc_html__( 'Modal (popup) Window', 'ARMember' ),
				],
				'label_block' => true,
				'classes'=>'',
				
			]
		);
		$this->add_control(
			'arm_popup_label',
			[
				'label' => esc_html__( 'Label', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
				'default' =>'Click here to open Form',
				'condition'=>['arm_frm_type' => 'popup','arm_model_trigger_type'=>['onload','link','button']],
			]
		);
		$this->add_control(
			'arm_model_trigger_type',
			[
				'label' => esc_html__( 'Modal Trigger Type','ARMember'),
				'type' => Controls_Manager::SELECT,
				'default' => 'link',
				'options' => [
							'link' => esc_html__( 'Link', 'ARMember' ),
							'button' =>esc_html__( 'Button', 'ARMember' ),
                            "onload"  =>"On Page Load"
							
                        ],
				'label_block' => true,
				'condition'=>['arm_frm_type' => 'popup'],
				
			]
		);

		$this->add_control(
			'arm_click_back_overlay',
			[
				'label' => esc_html__( 'Background Overlay','ARMember'),
				'type' => Controls_Manager::SELECT,
				'default' => '0.6',
				'options' => [
							"0"		=>"0 (None)",
                            "0.1" 	=>"10%",
                            "0.2"  	=>"20%",
                            "0.3"   =>"30%",
                            "0.4" 	=>"40%",
                            "0.5" 	=>"50%",
                            "0.6" 	=>"60%",
                            "0.7" 	=>"70%",
                            "0.8" 	=>"80%",
                            "0.9" 	=>"90%",
                            "1" 	=>"100%",

                        ],
				'label_block' => true,
				'condition'=>['arm_frm_type' => 'popup','arm_model_trigger_type'=>['onload','link','button']],
				
			]
		);
		$this->add_control(
			'arm_click_back_color',
			[
				'label' => esc_html__( 'Background Color', 'ARMember' ),
				'type' => Controls_Manager::COLOR,
				'label_block' => true,
				'default' =>'#000000',
				'condition'=>['arm_frm_type' => 'popup','arm_model_trigger_type'=>['onload','link','button']],
				// 'classes'=>'arf_back_color_style',
			]
		);
		$this->add_control(
			'arm_loggedin_message',
			[
				'label' => esc_html__( 'Label', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
				'default' =>'You are already logged in.',
			]
		);
		$this->add_control(
			'arm_frm_position',
			[
				'label' => esc_html__( 'Form Position', 'ARMember'),
				'type' => Controls_Manager::SELECT,
				'default' => 'center',
				'options' => [
					'left' => esc_html__( 'Left', 'ARMember' ),
					'center' =>esc_html__( 'Center', 'ARMember' ),
					'right' =>esc_html__( 'Right', 'ARMember' )
				],
				'label_block' => true,
				'classes'=>'',
				
			]
		);
		$this->add_control(
			'arm_link_css',
			[
				'label' => esc_html__( 'Link CSS', 'ARMember'),
				'type' => Controls_Manager::TEXTAREA,
				'default' => '',
				'label_block' => true,
				'classes'=>'',
				'condition'=>['arm_frm_type' => 'popup'],
			]
		);
		$this->add_control(
			'arm_link_hover_css',
			[
				'label' => esc_html__( 'Link Hover CSS', 'ARMember'),
				'type' => Controls_Manager::TEXTAREA,
				'default' => '',
				'label_block' => true,
				'classes'=>'',
				'condition'=>['arm_frm_type' => 'popup'],
				
			]
		);
		$all_active_plans = $arm_subscription_plans->arm_get_all_active_subscription_plans();
		$arm_plans = array();
		$this->add_control(
			'arm_plan_select',
			[
				'label' => esc_html__( 'Assign Default Plan', 'ARMember'),
				'type' => Controls_Manager::SELECT,
				'default' => '0',
				'options' => [
					'0' => __('Free Membership','ARMember'),
				],
				'label_block' => true,
				
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
			$arm_shortcode='';
				if(isset($settings['arm_frm_type']) && $settings['arm_frm_type']=="popup")
				{
					if (isset($settings['arm_model_trigger_type']) && ($settings['arm_model_trigger_type'] == 'link' || $settings['arm_model_trigger_type'] == 'button' || $settings['arm_model_trigger_type'] == 'onload')) {
						
						echo do_shortcode('[arm_form id="'.$settings['arm_shortcode_select'].'" assign_default_plan="'.$settings['arm_plan_select'].'" form_position="'.$settings['arm_frm_position'].'" logged_in_message="'.$settings['arm_loggedin_message'].'" popup="true" link_type="'.$settings['arm_model_trigger_type'].'" link_title="'.$settings['arm_popup_label'].'" popup_height="auto" popup_width="700" link_css="" link_hover_css="" modal_bgcolor="'.$settings['arm_click_back_color'].'" overlay="'.$settings['arm_click_back_overlay'].'"]');
					}
				}
				else
				{
					echo  do_shortcode('[arm_form id="'.$settings['arm_shortcode_select'].'" assign_default_plan="'.$settings['arm_plan_select'].'" form_position="'.$settings['arm_frm_position'].'" logged_in_message="'.$settings['arm_loggedin_message'].'"]');
				}
		echo '</div>';
	}
}
