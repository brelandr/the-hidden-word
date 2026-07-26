(function () {
	'use strict';

	var root = document.getElementById('thw-church-subject-rules');
	if (!root) {
		return;
	}

	var body = root.querySelector('.thw-church-subject-rules__body');
	var addBtn = root.querySelector('.thw-church-subject-rules__add');
	var template = document.getElementById('thw-church-subject-rule-template');

	function nextIndex() {
		return body ? body.querySelectorAll('.thw-church-subject-rules__row').length : 0;
	}

	function reindexRows() {
		if (!body) {
			return;
		}
		body.querySelectorAll('.thw-church-subject-rules__row').forEach(function (row, index) {
			row.querySelectorAll('[name]').forEach(function (el) {
				el.name = el.name.replace(/thw_ai_church_subject_rules\[[^\]]+\]/, 'thw_ai_church_subject_rules[' + index + ']');
			});
		});
	}

	if (addBtn && template && body) {
		addBtn.addEventListener('click', function () {
			var html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex()));
			var wrap = document.createElement('tbody');
			wrap.innerHTML = html.trim();
			var row = wrap.firstElementChild;
			if (row) {
				body.appendChild(row);
			}
		});
	}

	root.addEventListener('click', function (event) {
		var btn = event.target.closest('.thw-church-subject-rules__remove');
		if (!btn) {
			return;
		}
		var row = btn.closest('.thw-church-subject-rules__row');
		if (row && body) {
			row.remove();
			reindexRows();
			if (!body.querySelector('.thw-church-subject-rules__row') && addBtn) {
				addBtn.click();
			}
		}
	});
})();
