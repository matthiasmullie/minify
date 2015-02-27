$(function() {
	var selectType = function() {
		var content = $(this).val(),
			extension = content.split('.').pop(),
			// check if we have a valid extension (.css of .js), in which case the
			// content of the textarea is likely a link to a file, instead of code
			knownType = ['js', 'css'].indexOf(extension) > -1;

		$('#intro input[name=type]').prop('checked', false);
		if (knownType) {
			$('#type_' + extension)
				.prop('checked', true)
				.trigger('change');
		}
	},
	checkType = function(e) {
		e.preventDefault();

		var extension = $('#intro input[name=type]:checked').val();

		if (extension) {
			minify();
		} else {
			// Show error message when type is not selected
			$('#types').addClass('error')
			showError('Please select the type (CSS or JavaScript) of your script!');
		}
	},
	minify = function() {
		var $form = $('#intro form'),
			$submitButton = $('#intro input[type=submit]'),
			$spinner = $('<button><i class="fa fa-spinner fa-spin"></i></button>');

			$submitButton.replaceWith($spinner);

		$.ajax({
			url: $form.attr('action'),
			type: $form.attr('method'),
			data: $form.serialize(),
		}).done(function(data, textStatus, jqXHR) {
			// replace textarea content with minified script
			$('#source').val(data.minified);

			// display minifier gains
			showSuccess(
				'Original script: ' + data.sizes.original + 'b, ' +
				'minified script: ' + data.sizes.minified + 'b. ' +
				'Gain: ' + (data.sizes.original - data.sizes.minified) + 'b.'
			);

			$spinner.replaceWith($submitButton);
		}).fail(function() {
			showError('Something, somewhere, somehow failed! Did you post a link to an unreachable script?');
			$spinner.replaceWith($submitButton);
		});
	},
	showError = function(message) {
		$('#error').text(message);
	},
	showSuccess = function(message) {
		$('#success').text(message);
	},
	discardErrorSuccess = function() {
		$('#types').removeClass('error');
		showError('');
		showSuccess('');
	};

	$('#source').on('change, keyup', selectType);
	$('#intro form').on('submit', checkType);
	$('#intro input[name=type]').on('change', discardErrorSuccess);
	$('#intro #source').on('keyup', discardErrorSuccess);

	$('#source').autoExpand();
});
