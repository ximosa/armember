
function mpConfirmResetDemo() {
	var confirmReset = confirm(MP_Demo_Ajax.confirmMessage);

	if (confirmReset == true) {
		var params =  {
			action : 'route_url',
			security : MP_Demo_Ajax.security,
			mp_demo_action : 'reset',
			controller : 'sandbox'
		};

		jQuery.ajax({
			url: MP_Demo_Ajax.url,
			type: "POST",
			data: params,
			success: function(response) {
				if (response.data.status == true) {
					alert(MP_Demo_Ajax.successMessage);
					location.reload(true);
				} else {
					alert(MP_Demo_Ajax.warningMessage);
				}
			},
			error: function(data, status) {
				callbackError(data);
			}
		});

	}

}
