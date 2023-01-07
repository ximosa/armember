(function ($) {
	'use strict';

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

	$.fn.mpDemoImageUpload = function () {
		return this.each(function (i, el) {
			var file_frame,
				$parent = $(this).closest('td');

			$(el).bind('click', function (event) {
				event.preventDefault();

				// If the media frame already exists, reopen it.
				if (file_frame) {
					file_frame.open();
					return;
				}

				file_frame = wp.media.frames.file_frame = wp.media({
					title: $(this).data('uploader_title'),
					button: {
						text: $(this).data('uploader_button_text')
					},
					library: {type: 'image'},
					multiple: false  // Set to true to allow multiple files to be selected
				});

				file_frame.on('select', function () {
					var attachment = file_frame.state().get('selection').first().toJSON(),
						$preview = $($parent.find('.upload_image_preview'));
					attachment = attachment.url;

					$parent.find('.mp_logo_url').val(attachment);
					$preview.show();
					$preview.find('img').attr('src', attachment);
				});

				file_frame.open();

			});

		});
	};

	$.fn.mpDemoTabNav = function() {

		return this.each(function (i, elem) {

			$(elem).on('click',function(e){
				e.preventDefault();
				e.stopPropagation();

				var target = $(e.target),
					targetBlockId = $(target).attr('href');

				//$(targetBlockId).removeClass();
				$('.mp-demo-nav-tabs a').removeClass('nav-tab-active');
				$(target).addClass('nav-tab-active');
				$("div[id^='mp-demo-nav-tab-']").css('display', 'none');
				$(targetBlockId).fadeIn();

			});


		});
	};

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

	var cleanForm = function ($params) {
		for (var key in $params) {
			$($params[key]).val('');
		}

		$('#mp-source-select-items .upload_image_preview').hide();
	};

	var getParams = function ($params) {
		var res = {};

		for (var key in $params) {
			res[key] = $params[key].val();
		}

		cleanForm($params);

		return res;
	};

	var setParams = function ($params, vals) {
		for (var key in $params) {
			$($params[key]).val(vals[key]);
		}
	};

	var getEditableParams = function ($params, index) {
		var res = {};

		for (var key in $params) {
			res[key] = $("input[name='settings[select][" + index + "][" + key + "]']").val();
		}

		cleanForm($params);

		return res;
	};

	var setEditableParams = function ($params, vals, index) {
		var new_ind = vals['link_id'],
			$tableList = $('#mp-source-select-items'),
			tr = $tableList.find("tr[data-id='" + index + "']");

		for (var key in $params) {
			var input = $tableList.find("input[name='settings[select][" + index + "][" + key + "]']");
			input.val(vals[key]);
			input.attr('name', "settings[select][" + new_ind + "][" + key + "]");

			if (key === 'img') {
				tr.find("td.select-" + key + ' img').attr('src', vals[key]);
			} else {
				tr.find("td.select-" + key).text(vals[key]);
			}
		}

		tr.find('.delete-event-button').attr('data-id', new_ind);
		tr.find('.edit-event-button').attr('data-id', new_ind);
		tr.attr('data-id', new_ind);
	};

	function mpDemoGetVar() {
		var vars = {};
		var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function (m, key, value) {
			vars[key] = value;
		});
		return vars;
	}

	/**
	 * Tab = mail
	 */
	function mpDemoWatchTestEmail() {

		$('#mp-demo-test-email').click(function () {

			var $input = $('input[name="test-email-receiver"]');
			if ($input.val() == '')
				return;

			var $form = $('.mp-admin-settings-email-form'),
				$dialog = $('.mp-message', $form),
				$loader = $('.spinner', $form),
				jsondata = $form.serializeObject(),
				temp = {};

			temp['security'] = MP_Demo_Ajax.security;
			temp['mp_demo_action'] = 'send_response';
			temp['controller'] = 'mail';
			temp['mp_demo_url'] = window.location.href;
			temp['mp_subtab'] = mpDemoGetVar()['subtab'];
			temp['mp_subtab'] = temp['mp_subtab'] !== "undefined" ? temp['mp_subtab'] : 'to-customer';
			$loader.addClass('is-active');
			jsondata = $.extend(true, jsondata, temp);
			delete jsondata['mp_demo_save'];
			delete jsondata['action'];

			wpAjax(jsondata,
				function (response) {

					if (response) {
						if (response.success === true) {
							$('.mp-demo-success', $dialog).fadeIn();
							setTimeout(function () {
								$('.mp-demo-success', $dialog).fadeOut();
							}, 5000);

						} else {
							$('.mp-demo-fail', $dialog).fadeIn();
							if (response.errors !== "undefined") {
								$('.mp-demo-fail', $dialog).find('.mp-errors').text(response.errors);
							}
							setTimeout(function () {
								$('.mp-demo-fail', $dialog).fadeOut();
							}, 5000);
						}
						$dialog.fadeIn();
					}

					$loader.removeClass('is-active');
					$input.val('');
				},
				function (data) {
					console.log('Error ', data);
					$loader.removeClass('is-active');
				}
			);
		});
	}

	/**
	 * Tab = toolbar
	 */
	function mpDemoWatchLogoUpload() {
		jQuery('.upload_image_button').mpDemoImageUpload();

		return;
	}

	/**
	 * Tab = toolbar
	 */
	function mpDemoWatchSelectTable() {

		var $addBtn = $('#mp_add_table_item'),
				$cancel_btn = $('#mp_cancel_table_editing'),
				$params = {
					'link_id': $('#select-link_id'),
					'text': $('#select-text'),
					'link': $('#select-link'),
					'btn_text': $('#select-btn_text'),
					'btn_url': $('#select-btn_url'),
					'btn_class': $('#select-btn_class'),
					'img': $('#select-img')
				},
				$tableList = $('#mp-source-select-items').find('tbody');

		// Make table sortable
		if($.fn.sortable) {
			$tableList.sortable({
				items: 'tr',
				cursor: "move",
				//cursor: "grabbing",
				opacity: 0.95
			});
		}


		/**
		 * (Add new || Edit)  row in table
		 */
		$addBtn.click(function () {

			var data = getParams($params),
					spinner = $(this).parent().find('.spinner'),
					action = $(this).attr('data-action');
			$(spinner).addClass('is-active');

			if (action === 'edit') {
				var index = $(this).attr('data-id');

				setEditableParams($params, data, index);

				$addBtn.val(MP_Demo_Ajax.add_text);
				$addBtn.attr('data-action', 'add');
				$addBtn.attr('data-id', -1);
				$(spinner).removeClass('is-active');

			} else {
				var params = {
					mp_demo_action: 'add_row',
					controller: 'toolbar',
					data: data
				};

				wpAjax(params,
						function (data) {
							$('.mp-demo-no-rows-message').hide();
							$tableList.append(data.data);
							$(spinner).removeClass('is-active');
							$('tr', $tableList).removeClass('active');
						},
						function (data) {
							console.log('Error ', data);
							$(spinner).removeClass('is-active');
							$('tr', $tableList).removeClass('active');
						});
			}

		});


		/**
		 * Cancel event
		 */
		$cancel_btn.click(function () {
			var spinner = $(this).parent().find('.spinner');
			cleanForm($params);
			$('tr', $tableList).removeClass('active');
			$addBtn.attr('data-action', 'add');
			$addBtn.attr('data-id', -1);
			$addBtn.val(MP_Demo_Ajax.add_text);
			$(spinner).removeClass('is-active');
		});

		/**
		 * Set form with editable row
		 */
		$tableList.delegate(".edit-event-button", "click", function (e) {
			var ind = $(this).attr('data-id'),
					view_btn = $(this).parent().find('.view-event-button'),
					data = getEditableParams($params, ind);

			$('tr', $tableList).removeClass('active');
			$('tr[data-id="' + ind + '"]', $tableList).addClass('active');

			setParams($params, data);

			$addBtn.val(MP_Demo_Ajax.update_text);
			$addBtn.attr('data-action', 'edit');
			$addBtn.attr('data-id', ind);
			view_btn.remove();
			e.preventDefault();
			return false;
		});

		/**
		 * Remove row
		 */
		$tableList.delegate(".delete-event-button", "click", function (e) {
			var ind = $(this).attr('data-id'),
					$row = $tableList.find("tr[data-id='" + ind + "']");

			$row.after('<tr></tr>').hide();
			$row.empty();

			$('tr', $tableList).removeClass('active');

			e.preventDefault();
			return false;
		});


	}

	/**
	 * Tab = export
	 */
	function mpDemoWatchReplaceTable() {
		var $exportForm = $('#mp-demo-export-sandbox'),
			$addBtn = $('#mp-demo-add-replacement'),
			$exportInfoWrap = $('#mp-demo-export-info-wrap'),
			$tableList = $('#mp-replacements-table').find('tbody');

		// Make table sortable
		if ($.fn.sortable) {
			$tableList.sortable({
				items: 'tr',
				cursor: "move",
				opacity: 0.95
			});
		}

		/**
		 * (Add new)  row in table
		 */
		$addBtn.click(function () {
			var $spinner = $(this).parent().find('.spinner');

			$spinner = $($spinner);

			$spinner.addClass('is-active');

			var params = {
				mp_demo_action: 'add_row',
				controller: 'sandbox'
			};

			wpAjax(params,
				function (data) {
					$('.mp-demo-no-rows-message').hide();

					$tableList.append(data.data);
					$spinner.removeClass('is-active');
					$('tr', $tableList).removeClass('active');
				},
				function (data) {
					console.log('Error ', data);
					$spinner.removeClass('is-active');
					$('tr', $tableList).removeClass('active');
				}
			);

		});

		/**
		 * Remove row
		 */
		$tableList.delegate(".mp-demo-symbol-delete", "click", function (e) {
			var $row = $(this).parent().parent();
			$row.empty();

			return false;
		});

		$("#mp-demo-export-info-wrap").delegate("#mp-demo-remove-export-files", "click", function (e) {
			
			e.preventDefault();

			var $spinner = $(this).parent().find('.spinner'),
				formData = {};

			formData['security'] = MP_Demo_Ajax.security;
			formData['controller'] = 'sandbox';
			formData['mp_demo_action'] = 'remove_export';
			formData['source_path'] = $(this).attr('data-export');

			$spinner = $($spinner);

			$spinner.addClass('is-active');

			wpAjax(formData,
					function (response) {
						$exportInfoWrap.append(response.data.html);
						$spinner.removeClass('is-active');
					},
					function (data) {
						console.log('Error ', data);
						$spinner.removeClass('is-active');
					}
			);

		});


		// Disable empty form
		$(':input[type!="submit"]', $exportForm.get(0)).live ('change', function (e) {
			if ($('input[type="checkbox"]:checked', $exportForm).length == 0) {
				$exportForm.find(':input[type="submit"]').attr('disabled', 'disabled');
			} else {
				$exportForm.find (':input[type="submit"]').removeAttr('disabled');
			}
		});


		/**
		 * Submit export
		 */
		$exportForm.submit(function(e) {
			e.preventDefault();

			var formData = $(this).serializeObject(),
				$loader = $('.spinner', '.submit');

			$loader.addClass('is-active');

			formData['security'] = MP_Demo_Ajax.security;
			formData['controller'] = 'sandbox';

			// Block form
			$('input', $exportForm).attr("disabled", true);

			// Send ajax
			// 1. export_tables
			formData['mp_demo_action'] = 'export_tables';

			wpAjax(formData,
				function (response) {

					$exportInfoWrap.append(response.data.html);

					formData['mp_demo_action'] = 'apply_replacements';
					formData['sql_file'] = response.data.sql_file;
					formData['blog_export_folder'] = response.data.blog_export_folder;
					formData['tables_count'] = response.data.tables_count;

					// 2. replace substrings in export_tables
					wpAjax(formData,
							function (response) {

								$exportInfoWrap.append(response.data.html);

								// 3.
								formData['mp_demo_action'] = 'export_uploads';

								wpAjax(formData,
										function (response) {

											$exportInfoWrap.append(response.data.html);

											formData['mp_demo_action'] = 'create_zip';

											wpAjax(formData,
													function (response) {

														$exportInfoWrap.append(response.data.html);
														$loader.removeClass('is-active');
													},
													function (data) {
														console.log('Error ', data);
														$loader.removeClass('is-active');
													}
											);

										},
										function (data) {
											console.log('Error ', data);
											$loader.removeClass('is-active');
										}
								);
							},
							function (data) {
								console.log('Error ', data);
								$loader.removeClass('is-active');
							}
					);
				},
				function (data) {
					console.log('Error ', data);
					$loader.removeClass('is-active');
				}
			);

			return false;
		});

	}

	/**
	 * Toggle select all restrictions
	 */
	function mpDemoWatchToggleRestrictions() {

		$('.mp-admin-restrict').delegate('.mp-demo-parent', 'change', function () {
			var items = $(this).closest('.mp-parent-div').find('input[type="checkbox"]');

			if (this.checked) {
				items.attr('checked', 'checked');
			} else {
				items.removeAttr('checked');
			}
		});
	}

	function mpDemoLifetimeWatch() {

		var $wrap = $('.mp-demo-lifetime-wrap'),
			val = $('input[name="settings[is_lifetime]"]:checked', $wrap).val();

		mpDemoSetExpirationSettings($wrap, val);

		$('input[name="settings[is_lifetime]"]', $wrap).on('click', function() {
			mpDemoSetExpirationSettings($wrap, $(this).val());
		});
	}

	function mpDemoSetExpirationSettings($wrap, val) {

		if (val == 1) {
			$('input[name="settings[expiration_duration]"]', $wrap).attr('disabled', 'disabled');
			$('select[name="settings[expiration_measure]"]', $wrap).attr('disabled', 'disabled');
		}
		else {
			$('input[name="settings[expiration_duration]"]', $wrap).removeAttr('disabled');
			$('select[name="settings[expiration_measure]"]', $wrap).removeAttr('disabled');
		}
	}

	function mpDemoStatistcs() {

		if (typeof $().datepicker !== 'function') {
			return;
		}

		var minDate = new Date(2000, 10 - 1, 25);
		var startDate = minDate;

		$('#mp-demo-datepicker-start').datepicker({
			minDate: minDate,
			dateFormat: "yy-mm-dd",
			onSelect: function (dateText, inst) {

				startDate = dateText;
			}
		});

		$('#mp-demo-datepicker-end').datepicker({
			dateFormat: "yy-mm-dd",
			beforeShowDay: function (date) {
				var end = $.datepicker.formatDate('yy-mm-dd', date);

				return [$('#mp-demo-datepicker-start').val() <= end];
			}
		});
	}

	$(document).ready(function () {

		//tab=toolbar
		if (mpDemoGetVar()['tab'] === 'toolbar') {
			$('.mp-demo-colorpicker').wpColorPicker({defaultColor: false});
			mpDemoWatchSelectTable();
		}

		$('.mp-demo-nav-tabs a').mpDemoTabNav();

		mpDemoWatchTestEmail();

		mpDemoWatchLogoUpload();

		mpDemoWatchToggleRestrictions();

		mpDemoLifetimeWatch();

		mpDemoStatistcs();

		mpDemoWatchReplaceTable();

	});

}(jQuery));
