(function ($) {
	'use strict';

	function addEchoRow() {
		var $container = $('#thw-echo-repeater');
		var $first = $container.find('.thw-echo-row').first();
		var $clone = $first.clone();
		$clone.find('select, input').val('');
		$container.append($clone);
	}

	function addQuestionRow() {
		var $container = $('#thw-questions-repeater');
		var $row = $('<div class="thw-question-row">' +
			'<input type="text" name="thw_discussion_question[]" class="widefat" placeholder="Reflection question" />' +
			'<button type="button" class="button thw-remove-question">Remove</button>' +
			'</div>');
		$container.append($row);
	}

	$(document).on('click', '#thw-add-echo', addEchoRow);
	$(document).on('click', '#thw-add-question', addQuestionRow);
	$(document).on('click', '.thw-remove-echo', function () {
		var $rows = $('#thw-echo-repeater .thw-echo-row');
		if ($rows.length > 1) {
			$(this).closest('.thw-echo-row').remove();
		}
	});
	$(document).on('click', '.thw-remove-question', function () {
		var $rows = $('#thw-questions-repeater .thw-question-row');
		if ($rows.length > 1) {
			$(this).closest('.thw-question-row').remove();
		}
	});
}(jQuery));
