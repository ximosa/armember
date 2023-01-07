<?php
namespace ElementorARMELEMENT\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

if(! defined('ABSPATH')) exit;

class arm_my_profile_shortcode extends Widget_Base
{
    public function get_name()
    {
        return 'arm-my-profile-button-shortcode';
    }

    public function get_title()
    {
        return esc_html('ARMember My Profile ','ARMemeber').'<style>
        .arm_element_icon{
			display: inline-block;
		    width: 35px;
		    height: 24px;
		    background-image: url('.MEMBERSHIP_IMAGES_URL.'/armember_icon.png);
		    background-repeat: no-repeat;
		    background-position: bottom;
		}
        .arm_show_profiles .elementor-choices-label .elementor-screen-only{
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
        global $ARMember,$wp,$wpdb,$armainhelper,$arm_member_forms,$arm_members_directory,$arm_social_feature;
		$arm_form =array();
        $arm_form['Please select a valid form']='Select Form type';
		
        /**START Fetch all shortcode controls from DB */
        /*END*/
        $this->start_controls_section(
			'section_content',
			[
				'label' => esc_html__( 'ARMember Profile', 'ARMember' ),
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
        $profileFields= array();
		$dbProfileFields = $arm_members_directory->arm_template_profile_fields();
		foreach($dbProfileFields as $db_form)
		{
			$get_profile_key = $db_form['meta_key'];
			$get_profile_val = $db_form['label'];
			$profileFields[$get_profile_key]=$get_profile_val;
			// array_push($profileFields,$key);
		}
		$this->add_control(
			'arm_show_profiles',
			[
				'label' => esc_html__( 'Profile Fields', 'ARMember' ),
				'type' => Controls_Manager::SELECT2,
				'multiple' => true,
				'options' => $profileFields,
				'default' =>['user_login','first_name','last_name'],
				'classes'=>'arm_show_profiles',
				'label_block' => true,
			]
		);
		
		$str = '';
		if(!empty($profileFields))
		{
			foreach($profileFields as $lab => $val)
			{
				$get_val = $val;
				$this->add_control(
					'arm_value_'.$lab,
					[
						'label' => $get_val,
						'type' => Controls_Manager::TEXT,
						'label_block' => true,
						'default'=>$val,
						'condition'=>['arm_show_profiles'=>$lab]
					]
				);
			}
		}
		
		
		if($arm_social_feature->isSocialFeature)
		{
			$this->end_controls_section();

			$this->start_controls_section(
				'section_content_social',
				[
					'label' => esc_html__( 'ARMember Social Profile', 'ARMember' ),
					]
				);
				$socaialprofileFields =array();
				$socialProfileFields = $arm_member_forms->arm_social_profile_field_types();
				foreach($socialProfileFields as $key => $val)
				{
					$get_sprofile_key = $key;
					$get_sprofile_val = $val;
					$socaialprofileFields[$get_sprofile_key]=$get_sprofile_val;
					// array_push($profileFields,$key);
				}
				$this->add_control(
					'arm_show_social_profiles',
					[
						'label' => esc_html__( 'Social Profile Fields', 'ARMember' ),
						'type' => Controls_Manager::SELECT2,
						'multiple' => true,
						'options' => $socaialprofileFields,
						'classes'=>'arm_show_profiles',	
						'label_block' => true,
						]
					);
					
					
					$this->end_controls_section();
		}
	}
				
	protected function render()
	{
		global $arm_social_feature;
		
		$get_key_array=array();
		$get_value_array=array();
		$settings = $this->get_settings_for_display();

		echo '<h5 class="title">';
		echo $settings['title'];
		echo '</h5>';
		echo '<div class="arm_select">';
			$arm_shortcode='';
			$str='';
			
			$str_profile_val='';
			$str_social='';
			
			if(isset($settings['arm_show_profiles']))
			{
				foreach($settings['arm_show_profiles'] as $element)
				{
					$str .=$element.',';
					$str_profile_val .= $settings['arm_value_'.$element.''].',';
				}
			}
			if($arm_social_feature->isSocialFeature)
			{
				if(!empty($settings['arm_show_social_profiles']))
				{
					foreach($settings['arm_show_social_profiles'] as $social_element)
					  {
						$str_social .=$social_element.',';
					  }
				}
			}
			echo do_shortcode('[arm_account_detail social_fields="'.$str_social.'" label="'.$str.'" value="'.$str_profile_val.'"]');
		echo '</div>';
	}
}
