
(function ($) {
	'use strict';

	var wpAjax = function (params, callbackSuccess, callbackError) {
		params['action'] = 'route_url';

		$.ajax({
			url: window.wp_data.ajax_url,
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


	$( document ).ready( function() {
		// Trigger upgrades on page load

		$('#mp-demo-upgrate-database').click(function(){

			var $loader = $('.spinner'),
					params = {
						mp_demo_action: 'trigger_upgrades',
						controller:  'Back_Compatibility'
					};

			$loader.addClass('is-active');

			wpAjax(params,
					function(response) {

						console.log(response);
						if( response == 'complete' ) {
						}

						$loader.removeClass('is-active');
					},
					function (response) {
						console.log(response);
						$loader.removeClass('is-active');
					}
			);
		});



	});

}(jQuery));