<?php

namespace motopress_demo\classes\shortcodes;

use motopress_demo\classes\Core;
use motopress_demo\classes\models\General_Settings;
use motopress_demo\classes\models\Sandbox;
use motopress_demo\classes\Shortcodes;
use motopress_demo\classes\View;

class Shortcode_Try_Demo_Popup extends Shortcodes {

	protected static $instance;

	public static function get_instance() {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function shortcode_atts($attrs = array(), $content = null) {
		$defaultAttrs = array(
			'source_id' => '1',
			'title' => '',
			'label' => '',
			'select_label' => '',
			'placeholder' => '',
			'content' => $content,
			'captcha' => false,
			'launch_btn' => '',
			'wrapper_class' => '',
			'submit_btn' => '',
			'success' => '',
			'fail' => '',
			'loader_url' => \Motopress_Demo::get_plugin_url('assets/images/loader.gif'),
		);

		return shortcode_atts($defaultAttrs, $attrs);
	}

	/**
	 * Enqueue scripts here
	 */
	public function enqueue_scripts() {
		wp_enqueue_script('google-recaptcha');
		wp_enqueue_script('magnific-popup');
		wp_enqueue_script('mp-demo-script');
		wp_enqueue_style('magnific-popup-style');
		wp_enqueue_style('mp-demo-style');
	}

	public function hide_shortcode() {
		if (Sandbox::get_instance()->is_sandbox()
			|| General_Settings::get_instance()->get_option('prevent_clones')
		) {

			return true;
		}

		return false;
	}

	/**
	 * Main function for rendering
	 *
	 * @return \motopress_demo\classes\type|string
	 */
	public function render_shortcode($attrs, $content = null) {

		if ($this->hide_shortcode()) {
			return '';
		}

		$this->enqueue_scripts();

		$attrs = $this->shortcode_atts($attrs, $content);
		$attrs['captcha_options'] = General_Settings::get_instance()->get_option('recaptcha');

		return View::get_instance()->get_template_html("shortcodes/try-demo-popup", $attrs);
	}

	public function get_options() {
		$blogs = array();
		$sites = Core::get_sites(array('public' => 1));

		foreach ($sites as $site) {
			$blogs[] = array(
					'value' => $site['blog_id'],
					'text' => get_blog_option($site['blog_id'], 'blogname' )
			);
		}

		$params = array(
			'form_id' => 'mce-mp-demo-try-demo-popup',
			'popup_title' => __('Try Demo Popup', 'mp-demo'),
			'options' => array(

				0 => array(
					'type' => 'input',
					'name' => 'title',
					'label' => __('Form title', 'mp-demo'),
					'value' => __('To create your demo website provide the following data', 'mp-demo')
				),
				1 => array(
					'type' => 'input',
					'name' => 'label',
					'label' => __('Label for email', 'mp-demo'),
					'value' => __('Your email:', 'mp-demo')
				),
				2 => array(
					'type' => 'input',
					'name' => 'placeholder',
					'label' => __('Email placeholder', 'mp-demo'),
					'value' => 'example@mail.com'
				),
				3 => array(
					'type' => 'textarea',
					'name' => 'content',
					'label' => __('Description under the email field', 'mp-demo'),
					'value' => __('An activation email will be sent to this email address. After the confirmation you will be redirected to WordPress Dashboard.', 'mp-demo')
				),
				4 => array(
					'type' => 'input',
					'name' => 'launch_btn',
					'label' => __('Open popup button label', 'mp-demo'),
					'value' => __('Launch demo', 'mp-demo')
				),
				5 => array(
					'type' => 'input',
					'name' => 'submit_btn',
					'label' => __('Submit button', 'mp-demo'),
					'value' => __('Submit', 'mp-demo')
				),
				6 => array(
					'type' => 'textarea',
					'name' => 'success',
					'label' => __('Success message', 'mp-demo'),
					'value' => __('An activation email was sent to your email address.', 'mp-demo')
				),
				7 => array(
					'type' => 'textarea',
					'name' => 'fail',
					'label' => __('Fail message', 'mp-demo'),
					'value' => __('An error has occurred. Please notify the website Administrator.', 'mp-demo')
				),
				8 => array(
					'type' => 'input',
					'name' => 'wrapper_class',
					'label' => __('CSS class', 'mp-demo'),
					'value' => ''
				),
				9 => array(
						'type' => 'checkbox',
						'name' => 'captcha',
						'label' => __('Use reCAPTCHA', 'mp-demo'),
						'value' => 1,
						'checked' => false
				),
				10 => array(
						'type' => 'input',
						'name' => 'select_label',
						'label' => __('Write a label for drop-down list of the items available for creating sandboxes', 'mp-demo'),
						'value' => ''
				),
				11 => array(
						'type' => 'select',
						'name' => 'source_id',
						'multiple' => 'multiple',
						'selected' => array(1),
						'label' => __('Blog ID to create Demo from, default is 1', 'mp-demo'),
						'value' => $blogs
				),
			)
		);

		return $params;
	}

}
