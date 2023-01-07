/**
 * Created on 6/1/2016.
 */

"use strict";

if (typeof MP_Demo_MCE_Ajax != 'undefined')
	tinymce.PluginManager.add('mp_demo', function (editor, url) {

		editor.addButton('addMPDemoButton', function () {

			var mpDemoShowPopup = function (mpPopupId, shortcode, content) {
				jQuery.magnificPopup.open({
					items: {
						src: mpPopupId,
						type: 'inline'
					},
					showCloseBtn: true,
					callbacks: {
						close: function () {
						},
						open: function () {
							jQuery(mpPopupId).show();
						}
					}
				});

				jQuery(mpPopupId).find('input.button[type="button"]').click (function () {
					jQuery(mpPopupId).magnificPopup('close');
				});

				jQuery(mpPopupId).off('submit').on('submit', function (event) {
					event.preventDefault();

					var params = '';
					var formFields = jQuery(this).serializeArray();

					formFields = _.groupBy(formFields,
						function (item) {
							if (item.value) {
								return (item.name).replace('[]', '');
							}
						});

					var params = '';
					_.each(formFields, function (element, index, list) {
						if (index != 'undefined') {
							if (index != 'content') {
								params += ' '
									+ index + '="'
									+ _.pluck(element, 'value').join()
									+ '"';
							}
						}
					});

					if (content == true) {
						editor.insertContent('[' + shortcode + params + ']' + ((formFields['content'] != undefined) ? formFields['content'][0].value : '')  + '[/' + shortcode + ']');
					} else {
						editor.insertContent('[' + shortcode + params + ']');

					}

					jQuery(this).magnificPopup('close');

				});
			};

			return {
				title: MP_Demo_MCE_Ajax.mce_menu_title,
				icon: MP_Demo_MCE_Ajax.image,
				type: 'menubutton',
				menu: [
					{
						text: MP_Demo_MCE_Ajax.mce_title_try,
						onclick: function () {
							mpDemoShowPopup('#mce-mp-demo-try-demo', 'try_demo', true);
						}
					},
					{
						text: MP_Demo_MCE_Ajax.mce_title_popup,
						onclick: function () {
							mpDemoShowPopup('#mce-mp-demo-try-demo-popup', 'try_demo_popup', true);
						}
					},
					{
						text: MP_Demo_MCE_Ajax.mce_title_created,
						onclick: function () {
							mpDemoShowPopup('#mce-mp-demo-is-sandbox', 'is_sandbox', true);
						}
					},
					{
						text: MP_Demo_MCE_Ajax.mce_title_not_sandbox,
						onclick: function () {
							mpDemoShowPopup('#mce-mp-demo-is-not-sandbox', 'is_not_sandbox', true);
						}
					}
				]
			}

		});
	});

jQuery(document).ready(function () {

});

