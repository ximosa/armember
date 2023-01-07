<?php
namespace ElementorARMELEMENT\Widgets;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

if(! defined('ABSPATH')) exit;

class arm_setup_element_shortcode extends Widget_Base
{
    public function get_name()
    {
        return 'arm-setup-element-shortcode';
    }

    public function get_title()
    {
        return esc_html('ARMember Membership Setup Form','ARMemeber').'<style>
        .arm_element_icon{
			display: inline-block;
		    width: 35px;
		    height: 24px;
		    background-image: url('.MEMBERSHIP_IMAGES_URL.'/armember_icon.png);
		    background-repeat: no-repeat;
		    background-position: bottom;
		}
        .arm_hide_setup_title .elementor-choices-label .elementor-screen-only{
			position: relative;
			top: 0;
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
		$setups = $wpdb->get_results("SELECT `arm_setup_id`, `arm_setup_name` FROM `".$ARMember->tbl_arm_membership_setup."` ");
		$arm_setups =array();
		$default = $cnt = 0;
		if(!empty($setups)){
			foreach ($setups as $ms) {
				$setup_id = $ms->arm_setup_id;
				if($cnt == 0)
				{
					$default = $setup_id;
				}
				$cnt++;
				
				$setup_name = __( $ms->arm_setup_name." (ID: ".$setup_id.")","ARMember");
				$arm_setups[$setup_id]=$setup_name;
			} 
		}
        /**START Fetch all shortcode controls from DB */
        /*END*/
        $this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'ARMember Membership Setup Form', 'ARMember' ),
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
		$this->add_control(
			'arm_select_setup',
			[
				'label' => esc_html__( 'Select Setup', 'ARMember'),
				'type' => Controls_Manager::SELECT,
				'default' => $default,
				'options' => $arm_setups,
				'label_block' => true,
				
			]
		);
        $this->add_control(
			'arm_setup_hide_title',
			[
				'label' => esc_html__('Hide Setup Title','ARMember'),
				'type' => Controls_Manager::CHOOSE,
				'default' =>'false',
				'options' => [
					'true' => [
						'title' => esc_html__( 'Yes', 'ARMember' ),
					],
					'false' => [
						'title' => esc_html__( 'No', 'ARMember' ),
					],
				],
				'classes'=>'arm_hide_setup_title',
				
			]
		);
		$this->add_control(
			'arm_stp_type',
			[
				'label' => esc_html__( 'How you want to include this form into page?', 'ARMember'),
				'type' => Controls_Manager::SELECT,
				'default' => 'arm_setup',
				'options' => [
					'arm_setup' => esc_html__( 'Internal', 'ARMember' ),
					'popup' =>esc_html__( 'Modal (popup) Window', 'ARMember' ),
				],
				'label_block' => true,
				'classes'=>'',
				
			]
		);
		$this->add_control(
			'arm_popup_label',
			[
				'label' => esc_html__( 'Link or Button Text', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
				'default' =>'Click here to open Form',
				'condition'=>['arm_stp_type' => 'popup','arm_model_trigger_type'=>['link','button']],
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
							
                        ],
				'label_block' => true,
				'condition'=>['arm_stp_type' => 'popup'],
				
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
				'condition'=>['arm_stp_type' => 'popup','arm_model_trigger_type'=>['link','button']],
				
			]
		);
		$this->add_control(
			'arm_click_back_color',
			[
				'label' => esc_html__( 'Background Color', 'ARMember' ),
				'type' => Controls_Manager::COLOR,
				'label_block' => true,
				'default' =>'#000000',
				'condition'=>['arm_stp_type' => 'popup','arm_model_trigger_type'=>['link','button']],
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
				'condition'=>['arm_stp_type' => 'popup','arm_model_trigger_type'=>['link','button']],
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
                'condition'=>['arm_stp_type' => 'popup','arm_model_trigger_type'=>['link','button']],
				
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
                'condition'=>['arm_stp_type' => 'popup','arm_model_trigger_type'=>['link','button']],
				
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
		echo '<div class="arm_select_setup">';
			/**Shotcode goes here */
            $arm_shortcode='';
			if(isset($settings['arm_stp_type']) && $settings['arm_stp_type']=="popup")
			{
				if (isset($settings['arm_model_trigger_type']) && ($settings['arm_model_trigger_type'] == 'link' || $settings['arm_model_trigger_type'] == 'button')) {
				
					echo do_shortcode('[arm_setup  id="'.$settings['arm_select_setup'].'" hide_title="'.$settings['arm_setup_hide_title'].'" popup="true" link_type="'.$settings['arm_model_trigger_type'].'" link_title="'.$settings['arm_popup_label'].'" popup_height="auto" popup_width="800" link_css="" link_hover_css="" modal_bgcolor="'.$settings['arm_click_back_color'].'" overlay="'.$settings['arm_click_back_overlay'].'"]');
				}
			}
			else
			{
				echo do_shortcode('[arm_setup  id="'.$settings['arm_select_setup'].'" hide_title="'.$settings['arm_setup_hide_title'].'"]');
			}
		echo '</div>';
	}
}
