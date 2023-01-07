<?php
namespace ElementorARMELEMENT\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Controls_Stack;

if(! defined('ABSPATH')) exit;

class arm_payment_transaction_shortcode extends Widget_Base
{
    public function get_name()
    {
        return 'arm-payment-transaction-button-shortcode';
    }

    public function get_title()
    {
        return esc_html('ARMember Payment Transactions','ARMemeber').'<style>
        .arm_element_icon{
			display: inline-block;
		    width: 35px;
		    height: 24px;
		    background-image: url('.MEMBERSHIP_IMAGES_URL.'/armember_icon.png);
		    background-repeat: no-repeat;
		    background-position: bottom;
		}
        .arm_show_title .elementor-choices-label .elementor-screen-only{
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
				'label' => esc_html__( 'Payment Transactions List', 'ARMember' ),
			]
		);
        $this->add_control(
			'title',
			[
				'label' => esc_html__( 'Title', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'Transactions'
			]
		);
        
        $this->add_control(
            'arm_show_payment_transaction_fields',
            [
                'label' => esc_html__( 'Transaction History', 'ARMember' ),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => [
                    'transaction_id'=> __('Transaction ID','ARMember'),
                    'invoice_id'=> __('Invoice ID','ARMember'),
                    'plan'=>__('Plan','ARMember'),
                    'payment_gateway' => __('Payment Gateway','ARMember'),
                    'payment_type'=> __('Payment Type','ARMember'),
                    'transaction_status' => __('Transaction Status','ARMember'),
                    'amount' => __('Amount','ARMember'),
					'used_coupon_code'=>__('Used coupon Code','ARMember'),
					'used_coupon_discount'=>__('Used coupon Discount','ARMember'),
					'payment_date'=>__('Payment Date','ARMember'),
					'tax_percentage'=>__('TAX Percentage','ARMember'),
					'tax_amount'=>__('TAX Amount','ARMember'),
                ],
                'default'=>['transaction_id','invoice_id','plan','payment_gateway','payment_type','transaction_status','amount','used_coupon_code','used_coupon_discount','payment_date','tax_percentage','tax_amount'],
                'classes'=>'arm_show_profiles',	
                'label_block' => true,
            ]
        );
        $this->add_control(
			'arm_transaction_id',
			[
				'label' => esc_html__( 'Transaction ID', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'Transaction ID',
                'condition' => ['arm_show_payment_transaction_fields'=> 'transaction_id']

			]
		);

        $this->add_control(
			'arm_invoice_id',
			[
				'label' => esc_html__( 'Invoice ID', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'Invoice ID',
                'condition' => ['arm_show_payment_transaction_fields'=> 'invoice_id']
			]
		);

        $this->add_control(
			'arm_plan',
			[
				'label' => esc_html__( 'Plan', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'Plan',
                'condition' => ['arm_show_payment_transaction_fields'=> 'plan']
			]
		);

        $this->add_control(
			'arm_payment_gateway',
			[
				'label' => esc_html__( 'Payment Gateway', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'Payment Gateway',
                'condition' => ['arm_show_payment_transaction_fields'=> 'payment_gateway']
			]
		);

        $this->add_control(
			'arm_payment_type',
			[
				'label' => esc_html__( 'Payment Type', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'Payment Type',
                'condition' => ['arm_show_payment_transaction_fields'=> 'payment_type']
			]
		);

        $this->add_control(
			'arm_transaction_status',
			[
				'label' => esc_html__( 'Transaction Status', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'Transaction Status',
                'condition' => ['arm_show_payment_transaction_fields'=> 'transaction_status']
			]
		);

        $this->add_control(
			'arm_amount',
			[
				'label' => esc_html__( 'Amount', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'Amount',
                'condition' => ['arm_show_payment_transaction_fields'=> 'action']
			]
		);
		$this->add_control(
			'arm_used_coupon_code',
			[
				'label' => esc_html__( 'Used coupon Code', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'Used coupon Code',
                'condition' => ['arm_show_payment_transaction_fields'=> 'used_coupon_code']
			]
		);
		$this->add_control(
			'arm_used_coupon_discount',
			[
				'label' => esc_html__( 'Used coupon Discount', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'Used coupon Discount',
                'condition' => ['arm_show_payment_transaction_fields'=> 'used_coupon_discount']
			]
		);
		$this->add_control(
			'arm_payment_date',
			[
				'label' => esc_html__( 'Payment Date', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'Payment Date',
                'condition' => ['arm_show_payment_transaction_fields'=> 'payment_date']
			]
		);
		$this->add_control(
			'arm_tax_percentage',
			[
				'label' => esc_html__( 'TAX Percentage', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'TAX Percentage',
                'condition' => ['arm_show_payment_transaction_fields'=> 'tax_percentage']
			]
		);
		$this->add_control(
			'arm_tax_amount',
			[
				'label' => esc_html__( 'TAX Amount', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'TAX Amount',
                'condition' => ['arm_show_payment_transaction_fields'=> 'tax_amount']
			]
		);

        $this->add_control(
			'arm_invoice_btn',
			[
				'label' => esc_html__('Display View Invoice Button','ARMember'),
				'type' => Controls_Manager::CHOOSE,
				'default' =>'true',
				'options' => [
					'true' => [
						'title' => esc_html__( 'Yes', 'ARMember' ),
					],
					'false' => [
						'title' => esc_html__( 'No', 'ARMember' ),
					],
				],
				'classes'=>'arm_show_title',
			]
		);

        $this->add_control(
			'arm_invoice_txt',
			[
				'label' => esc_html__( 'View Invoice', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'View Invoice',
                'condition'=>['arm_invoice_btn'=>'true'],
			]
		);

        $this->add_control(
			'arm_button_css',
			[
				'label' => esc_html__( 'Button CSS', 'ARMember' ),
				'type' => Controls_Manager::TEXTAREA,
				'label_block' => true,
                'default'=>'',
                 'condition'=>['arm_invoice_btn'=>'true'],
			]
		);
        $this->add_control(
			'arm_button_hover_css',
			[
				'label' => esc_html__( 'Button Hover CSS', 'ARMember' ),
				'type' => Controls_Manager::TEXTAREA,
				'label_block' => true,
                'default'=>'',
                 'condition'=>['arm_invoice_btn'=>'true'],
			]
		);

        $this->add_control(
			'arm_records_per_page',
			[
				'label' => esc_html__( 'Records Per Page', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'5',
			]
		);
        $this->add_control(
			'arm_records_message',
			[
				'label' => esc_html__( 'No Records Message', 'ARMember' ),
				'type' => Controls_Manager::TEXT,
				'label_block' => true,
                'default'=>'There is no any Transactions found.',
			]
		);

        $this->end_controls_section();

	}
				
	protected function render()
	{
		global $arm_social_feature;
		$settings = $this->get_settings_for_display();
        $str='';
		$str_label = '';
        foreach($settings['arm_show_payment_transaction_fields'] as $sk)
        {
            $str .= $settings['arm_'.$sk].',';
			$str_label .= $sk.',';
        }
		echo '<div class="arm_select">';
			$arm_shortcode='';
            echo do_shortcode('[arm_member_transaction display_invoice_button="'.$settings['arm_invoice_btn'].'" view_invoice_text="'.$settings['arm_invoice_txt'].'" view_invoice_css="'.$settings['arm_button_css'].'" view_invoice_hover_css="'.$settings['arm_button_hover_css'].'" title="'.$settings['title'].'" per_page="'.$settings['arm_records_per_page'].'" message_no_record="'.$settings['arm_records_message'].'"  label="'.$str_label.'"  value="'.$str.'"]');
           
		echo '</div>';
	}
}
