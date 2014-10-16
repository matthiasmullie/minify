$(function() {
	var selectType = function() {
		var content = $(this ).val(),
			extension = content.split('.').pop(),
			// check if we have a valid extension (.css of .js), in which case the
			// content of the textarea is likely a link to a file, instead of code
			knownType = ['js', 'css'].indexOf(extension) > -1;

		$('#intro input[name=type]').prop('checked', false);
		if (knownType) {
			$('#intro #type_' + extension)
				.prop('checked', true)
				.trigger('change');
		}
	},
	checkType = function(e) {
		var extension = $('#intro input[name=type]:checked').val();

		if (!extension) {
			e.preventDefault();

			// Show error message when type is not selected
			$('#intro #types')
				.addClass('error')
				.append('<span class="error"><br />Select script type!</span>');
		}
	},
	discardError = function(e) {
		$('#intro #types').removeClass('error');
		$('#intro #types .error').remove();
	};

	$('#intro #source').on('change, keyup', selectType);
	$('#intro form').on('submit', checkType);
	$('#intro input[name=type]').on('change', discardError);
});
