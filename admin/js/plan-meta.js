/**
 * Admin reading-plan day editor.
 */
(function ($) {
	'use strict';

	function nextIndex() {
		var max = -1;
		$('#hwbl-plan-days .hwbl-plan-day-row').each(function () {
			$(this)
				.find('[name^="hwbl_plan_days["]')
				.each(function () {
					var m = (this.name || '').match(/hwbl_plan_days\[(\d+)\]/);
					if (m) {
						max = Math.max(max, parseInt(m[1], 10));
					}
				});
		});
		return max + 1;
	}

	function renumberDays() {
		$('#hwbl-plan-days .hwbl-plan-day-row').each(function (i) {
			$(this)
				.find('.hwbl-plan-day-num')
				.val(i + 1);
		});
	}

	$(function () {
		$('#hwbl-plan-add-day').on('click', function (e) {
			e.preventDefault();
			var tpl = $('#hwbl-plan-day-template').html();
			if (!tpl) {
				return;
			}
			var idx = nextIndex();
			var html = tpl.replace(/__INDEX__/g, String(idx));
			$('#hwbl-plan-days').append(html);
			renumberDays();
		});

		$('#hwbl-plan-days').on('click', '.hwbl-plan-remove-day', function (e) {
			e.preventDefault();
			var $rows = $('#hwbl-plan-days .hwbl-plan-day-row');
			if ($rows.length <= 1) {
				return;
			}
			$(this).closest('.hwbl-plan-day-row').remove();
			renumberDays();
		});
	});
})(jQuery);
