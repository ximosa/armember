(function (window, document) {
	'use strict';

	function addClass(o, c) {
		var re = new RegExp("(^|\\s)" + c + "(\\s|$)", "g");
		if (re.test(o.className)) return
		o.className = (o.className + " " + c).replace(/\s+/g, " ").replace(/(^ | $)/g, "");
	}

	function removeClass(o, c) {
		var re = new RegExp("(^|\\s)" + c + "(\\s|$)", "g");
		o.className = o.className.replace(re, "$1").replace(/\s+/g, " ").replace(/(^ | $)/g, "");
	}

	function bindReady(handler) {

		var called = false;

		function ready() {
			if (called) return;
			called = true;
			handler()
		}

		if (document.addEventListener) {
			document.addEventListener("DOMContentLoaded", function () {
				ready()
			}, false)
		} else if (document.attachEvent) {

			if (document.documentElement.doScroll && window == window.top) {
				var tryScroll = function () {
					if (called) return;
					if (!document.body) return;
					try {
						document.documentElement.doScroll("left");
						ready()
					} catch (e) {
						setTimeout(tryScroll, 0)
					}
				};

				tryScroll()
			}

			document.attachEvent("onreadystatechange", function () {

				if (document.readyState === "complete") {
					ready()
				}
			})
		}

		if (window.addEventListener)
			window.addEventListener('load', ready, false);
		else if (window.attachEvent)
			window.attachEvent('onload', ready);

	}


	function show_toolbar(toolbar, iframe_id) {

		var bodyObj = document.getElementsByTagName("body")[0],
			htmlObj = document.getElementsByTagName("html")[0];

		addClass(bodyObj, 'mp-hide-scroll');
		addClass(htmlObj, 'mp-hide-scroll');

		var responsive = document.getElementsByClassName("responsive")[0],
			select_wrap = document.getElementsByClassName("select-wrap")[0];

		responsive.onclick = function (event) {
			var target = event.target;

			while (target != this) {
				if (target.tagName == 'A') {
					event = event || window.event;
					var w = target.getAttribute('data-width'),
						links = responsive.getElementsByTagName('A');

					document.getElementById(iframe_id).setAttribute("width", w);
					document.getElementById(iframe_id).style.width = w;

					for (var i = 0; i < links.length; i++) {
						removeClass(links[i], 'active');
					}

					addClass(target, 'active');

					if (event.preventDefault) {
						event.preventDefault();
					} else { // IE8-:
						event.returnValue = false;
					}

					return false;
				}
				target = target.parentNode;
			}
		};

		if (typeof(select_wrap) != 'undefined' && select_wrap != null) {

			var select_ul = select_wrap.getElementsByTagName('UL')[0];

			select_wrap.onclick = function (event) {
				if (select_ul.style.display == 'block') {
					select_ul.style.display = 'none';
				} else {
					select_ul.style.display = 'block';
				}
			};
		}

	}

	// document ready
	bindReady(function () {
		var iframe_id = 'mp-iframe',
			toolbar = 'mp-toolbar';
		show_toolbar(toolbar, iframe_id);

	});

}(window, document));

//(function ($, window, document) {
//	'use strict';
//
//	function show_toolbar($toolbar, $iframe_id) {
//
//		$('html, body').addClass('mp-hide-scroll');
//
//		$toolbar.fadeIn();
//
//		// Responsive
//		$('.responsive', $toolbar).delegate("a", "click", function (e) {
//			$iframe_id.attr('width', ($(this).data('width')));
//			$iframe_id.css('width', ($(this).data('width')));
//			$('.responsive a', $toolbar).removeClass('active');
//			$(this).addClass('active');
//
//			e.preventDefault();
//			return false;
//		});
//	}
//
//	$(document).ready(function () {
//
//		var iframe_id = $('#mp-iframe'),
//			toolbar = $('#mp-toolbar');
//		show_toolbar(toolbar, iframe_id);
//
//	});
//
//}(jQuery, window, document));