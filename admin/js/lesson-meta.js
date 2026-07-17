(function ($) {
	'use strict';

	function addEchoRow() {
		var $container = $('#hwbl-echo-repeater');
		var $first = $container.find('.hwbl-echo-row').first();
		var $clone = $first.clone();
		$clone.find('select, input').val('');
		$container.append($clone);
	}

	function addQuestionRow() {
		var $container = $('#hwbl-questions-repeater');
		var $row = $('<div class="hwbl-question-row">' +
			'<input type="text" name="hwbl_discussion_question[]" class="widefat" placeholder="Reflection question" />' +
			'<button type="button" class="button hwbl-remove-question">Remove</button>' +
			'</div>');
		$container.append($row);
	}

	$(document).on('click', '#hwbl-add-echo', addEchoRow);
	$(document).on('click', '#hwbl-add-question', addQuestionRow);
	$(document).on('click', '.hwbl-remove-echo', function () {
		var $rows = $('#hwbl-echo-repeater .hwbl-echo-row');
		if ($rows.length > 1) {
			$(this).closest('.hwbl-echo-row').remove();
		}
	});
	$(document).on('click', '.hwbl-remove-question', function () {
		var $rows = $('#hwbl-questions-repeater .hwbl-question-row');
		if ($rows.length > 1) {
			$(this).closest('.hwbl-question-row').remove();
		}
	});
}(jQuery));
