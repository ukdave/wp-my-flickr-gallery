/*jslint sloppy: true, white: true, browser: true */
/*global jQuery: true, ajax_object: true, colorboxSelector: true */

/**
 * MFG module.
 */
var mfg = (function($) {
	var pub = {},
	    historyEdited = false;

	/**
	 *
	 */
	function aPrivateFunction() {
		// do private stuff
	}

	/**
	 * Initialise.
	 */
	pub.init = function() {
		if (window.history && history.pushState) {
			// Click listeners for Prev and Next navigation buttons
			$(document).on('click', '#mfg-pagination .btn', function(event) {
				var href = $(this).attr('href'),
				    idx = href.lastIndexOf('='),
				    page = (idx > 0 ? parseInt(href.substring(idx + 1), 10) : 1);
				event.preventDefault();
				if (!$(this).hasClass('disabled')) {
					pub.goToPage(page, true);
				} else {
					return false;
				}
			});
			// History popstate listener
			$(window).on('popstate', function(event) {
				if (historyEdited) {
					var href = location.pathname + location.search,
					    idx = href.lastIndexOf('='),
					    page = (idx > 0 ? parseInt(href.substring(idx + 1), 10) : 1);
					pub.goToPage(page, false);
				}
			});
		}
	};

	/**
	 * Navigate to gallery page using AJAX.
	 *
	 * @param page the gallery page to navigate to
	 * @param updateHistory whether to update the browser history
	 */
	pub.goToPage = function(page, updateHistory) {
		// Display a modal loader DIV over the gallery
		$('#mfg').append(
			$('<div id="mfg-loader"></div>')
			.css('position', 'absolute')
			.css('top', $('#mfg-gallery').position().top)
			.css('left', $('#mfg-gallery').position().left)
			.css('width', $('#mfg-gallery').width() + 'px')
			.css('height', $('#mfg-gallery').height() + 'px')
			.css('line-height', $('#mfg-gallery').height() + 'px')
			.hide()
		);
		$('#mfg-loader').fadeIn();
		// Disable the navifation buttons
		$('#mfg-pagination .btn').addClass('disabled');
		// Load the content
		$.get(
			ajax_object.ajax_url, 
			{
				action: 'mfg_ajax',
				mfg_post_id: $("#mfg-post-id").val(),
				mfg_page: page
			},
			function(response) {
				$('#mfg').replaceWith(response);
				// Re-initialise colorbox (if present)
				if (typeof colorboxSelector === 'function') {
					colorboxSelector();
				}
			}
		);
		// Update the browser history
		if (updateHistory) {
			history.pushState(null, null, '?mfg_page=' + page);
			historyEdited = true;
		}
	};

	return pub;
}(jQuery));


jQuery(document).ready(function() {
	mfg.init();
});
