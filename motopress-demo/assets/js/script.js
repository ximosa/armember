
(function ($) {
	'use strict';

	var wpAjax = function (params, callbackSuccess, callbackError) {
		params['action'] = 'route_url';

		$.ajax({
			url: MP_Demo_Ajax.url,
			type: "POST",
			data: params,
			success: function(data) {
				callbackSuccess(data);
			},
			error: function(data, status) {
				callbackError(data);
			}
		});
	};

	var mpDemoShowPopup = function (mpPopupId, hashValue, elem) {
		if (typeof elem !== "undefined") {
			elem.magnificPopup({
				items: {
					src: mpPopupId,
					type: 'inline'
				},
				showCloseBtn: true,
				callbacks: {
					close: function () {
						$('input[type=text]', mpPopupId).val('');
						// remove hash
						history.pushState("", document.title, window.location.pathname + window.location.search);
						$('.mp-message').fadeOut();
					},
					open: function () {
						window.location.hash = hashValue;
						$(mpPopupId).show();
						$('>:not(.mp-message,.mfp-close)', mpPopupId).slideDown();
						$('.mp-message', mpPopupId).hide();
					}
				}
			});
		} else {
			$.magnificPopup.open({
				items: {
					src: mpPopupId,
					type: 'inline'
				},
				showCloseBtn: true,
				callbacks: {
					close: function () {
						history.pushState("", document.title, window.location.pathname + window.location.search);
						$('.mp-message').fadeOut();
					},
					open: function () {
						$(mpPopupId).show();
						$('>:not(.mp-message,.mfp-close)', mpPopupId).slideDown();
						$('.mp-message', mpPopupId).hide();
					}
				}
			});
		}
	};

	$.fn.serializeObject = function () {
		var o = {};
		var a = this.serializeArray();
		$.each(a, function () {
			if (o[this.name]) {
				if (!o[this.name].push) {
					o[this.name] = [o[this.name]];
				}
				o[this.name].push(this.value || '');
			} else {
				o[this.name] = this.value || '';
			}
		});
		return o;
	};

	$.fn.googleRecaptcha = function () {
		var _this = this;

		return this.each(function (i, el) {

			grecaptcha.render(el, {
				'sitekey': $(el).data('sitekey')
			});
		});
	};

	window.mpDemoGCaptchaOnLoad = function () {

		$('.mp-recaptcha').googleRecaptcha();

	};


	$(document).ready(function () {

		var mpPopupId = '#try-demo-popup';

		//$(window).on('hashchange', function () {
		//	var hash = location.hash;
		//	if (mpPopupId == hash) {
		//		mpDemoShowPopup(mpPopupId);
		//	}
		//});

		if (window.location.hash == mpPopupId) {
			mpDemoShowPopup(mpPopupId);
		}


		$('.mp-demo-popup-link-popup').each(function (i, elem) {
			var elem = $(elem);
			var popup = elem.next();
			mpDemoShowPopup(popup, mpPopupId, elem);
		});


		//Popup form
		var $form_popup = $('.try-demo-popup');


		$form_popup.submit(function (e) {
			e.preventDefault();
			var $form_popup = $(e.currentTarget),
				$dialog = $('.mp-message', $form_popup),
				loader = $('.mp-loader', $form_popup),
				temp = {};
			loader.fadeIn();
			temp['mp_demo_action'] = 'send_response';
			temp['controller'] = 'mail';
			temp['security'] = MP_Demo_Ajax.security;
			temp['mp_demo_url'] = window.location.href;

			var jsondata = $form_popup.serializeObject();
			jsondata = $.extend(true, jsondata, temp);

			wpAjax(jsondata,
					function (response) {
						$('.mp-demo-success', $dialog).hide();
						$('.mp-demo-fail', $dialog).hide();

						if (response && response.success === true) {
							$('.mp-demo-success', $dialog).fadeIn();
							$('>:not(.mp-message *,.mfp-close)', $form_popup).slideUp();
						} else {
							$('.mp-demo-fail', $dialog).fadeIn();
							if (response.errors !== "undefined") {
								$('.mp-demo-fail', $dialog).find('.mp-errors').text(response.errors);
							}
						}
						$dialog.fadeIn();

						loader.fadeOut();
					},
					function (data) {
						loader.fadeOut();
						$('.mp-demo-fail', $dialog).fadeIn();
					}
			);

			$form_popup[0].reset();

			return false;
		});


		//Normal Form
		var $form = $('.try-demo');

		$form.submit(function (e) {
			e.preventDefault();
			var $form = $(e.currentTarget),
				$dialog = $('.mp-message', $form),
				loader = $('.mp-loader', $form),
				temp = {};
			loader.fadeIn();

			temp['action'] = 'route_url';
			temp['mp_demo_action'] = 'send_response';
			temp['controller'] = 'mail';
			temp['security'] = MP_Demo_Ajax.security;
			temp['mp_demo_url'] = window.location.href;

			var jsondata = $form.serializeObject();
			jsondata = $.extend(true, jsondata, temp);

			wpAjax(jsondata,
					function (response) {
						$('.mp-demo-success', $dialog).hide();
						$('.mp-demo-fail', $dialog).hide();

						if (response.success === true) {
							$('.mp-demo-success', $dialog).fadeIn();

						} else {
							$('.mp-demo-fail', $dialog).fadeIn();

							if (response.errors !== "undefined") {
								$('.mp-demo-fail', $dialog).find('.mp-errors').text(response.errors);
							}
						}
						$dialog.fadeIn();
						loader.fadeOut();
					},
					function (data) {
						loader.fadeOut();
						$('.mp-demo-fail', $dialog).fadeIn();
					}
			);

			$form[0].reset();

			return false;
		});

	});

}(jQuery));