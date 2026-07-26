(function () {
	'use strict';

	var select = document.getElementById('thw_ai_explain_rules_preset');
	var textarea = document.getElementById('thw_ai_explain_rules');

	if (!select || !textarea) {
		return;
	}

	select.addEventListener('change', function () {
		var option = select.options[select.selectedIndex];
		if (!option || option.value === 'custom') {
			return;
		}

		var rules = option.getAttribute('data-rules');
		if (rules) {
			textarea.value = rules;
		}
	});
})();
