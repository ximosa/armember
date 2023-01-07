<?php
namespace ElementorARMELEMENT\Widgets;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

if(! defined('ABSPATH')) exit;

class arm_close_account_shortcode extends Widget_Base
{
    public function get_name()
    {
        return 'arm-close-account-shortcode';
    }

    public function get_title()
    {
        return esc_html('ARMember Close Account','ARMemeber').'<style>
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
				'label' => esc_html__( 'ARMember Close Account', 'ARMember' ),
			]
		);

        $this->add_control(
			'title',
			[
				'label' => esc_html__( 'Title', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default' => __('Close Account','ARMember'),
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
			echo do_shortcode('[arm_close_account set_id="'.$settings['arm_shortcode_select'].'"]');
		echo '</div>';
	}
}
