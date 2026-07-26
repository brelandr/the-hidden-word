(function () {
	'use strict';

	function qs(sel, root) {
		return (root || document).querySelector(sel);
	}

	function escHtml(text) {
		var div = document.createElement('div');
		div.textContent = text == null ? '' : String(text);
		return div.innerHTML;
	}

	function renderTests(tests) {
		if (!tests || !tests.length) {
			return '';
		}
		var html = '<ul class="thw-bible-api-test__tests">';
		tests.forEach(function (test) {
			html += '<li class="thw-bible-api-test__test' + (test.ok ? ' is-ok' : ' is-fail') + '">';
			html += '<strong>' + escHtml(test.label) + '</strong>';
			if (test.ok) {
				html +=
					'<br><span class="description">' +
					escHtml(
						(test.verse_count ? test.verse_count + ' verse(s). ' : '') +
							(test.sample_text ? 'Sample: “' + test.sample_text + '”' : '')
					) +
					'</span>';
			} else {
				if (test.error) {
					html += '<br><span class="thw-bible-api-test__error">' + escHtml(test.error) + '</span>';
				}
				if (test.hint) {
					html += '<br><span class="description">' + escHtml(test.hint) + '</span>';
				}
				if (test.http_code) {
					html += '<br><code>HTTP ' + escHtml(String(test.http_code)) + '</code>';
				}
				if (test.url) {
					html += '<br><code>' + escHtml(test.url) + '</code>';
				}
				if (test.body_preview) {
					html += '<br><span class="description">' + escHtml(test.body_preview) + '</span>';
				}
			}
			html += '</li>';
		});
		html += '</ul>';
		return html;
	}

	function renderProviderResult(data) {
		var cls = data.ok ? 'notice-success' : 'notice-error';
		var html = '<div class="notice ' + cls + ' inline thw-bible-api-test__result"><p>';
		html += '<strong>' + escHtml(data.summary || '') + '</strong>';
		html += renderTests(data.tests);
		html += '</p></div>';
		return html;
	}

	function renderDiagnosis(data) {
		var cls = data.ok ? 'notice-success' : 'notice-error';
		var html = '<div class="notice ' + cls + ' inline thw-bible-api-test__result"><p>';
		html += '<strong>' + escHtml(data.summary || '') + '</strong>';
		if (data.steps && data.steps.length) {
			html += '<ol class="thw-bible-api-test__steps">';
			data.steps.forEach(function (step) {
				html += '<li class="thw-bible-api-test__step' + (step.ok ? ' is-ok' : ' is-fail') + '">';
				html += '<strong>' + escHtml(step.label) + '</strong> — ' + escHtml(step.detail || '');
				html += renderTests(step.tests);
				html += '</li>';
			});
			html += '</ol>';
		}
		html += '</p></div>';
		return html;
	}

	function setBusy(btn, busy) {
		if (!btn) {
			return;
		}
		btn.disabled = !!busy;
		btn.setAttribute('aria-busy', busy ? 'true' : 'false');
	}

	function postTest(payload) {
		var cfg = window.thwBibleApiTest || {};
		var body = new URLSearchParams(payload);
		body.append('action', 'thw_test_bible_api');
		body.append('nonce', cfg.nonce || '');

		return fetch(cfg.ajaxUrl || '/wp-admin/admin-ajax.php', {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
		}).then(function (res) {
			return res.json();
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		var wrap = qs('[data-thw-bible-api-test]');
		if (!wrap) {
			return;
		}

		var bibliaBtn = qs('[data-thw-test-biblia]', wrap);
		var youversionBtn = qs('[data-thw-test-youversion]', wrap);
		var apiBtn = qs('[data-thw-test-api-bible]', wrap);
		var diagBtn = qs('[data-thw-diagnose-translation]', wrap);
		var out = qs('.thw-bible-api-test__output', wrap);
		var translationSelect = qs('#thw_bible_api_test_translation', wrap);

		function showResult(html) {
			if (out) {
				out.innerHTML = html;
				out.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
			}
		}

		function getTranslation() {
			return translationSelect ? translationSelect.value : 'nlt';
		}

		function runProviderTest(provider, btn, keyInputId) {
			if (!btn) {
				return;
			}
			setBusy(btn, true);
			showResult(
				'<p class="description">' +
					escHtml((window.thwBibleApiTest && thwBibleApiTest.i18n.testing) || 'Testing…') +
					'</p>'
			);
			var keyInput = qs('#' + keyInputId);
			postTest({
				mode: 'provider',
				provider: provider,
				translation: getTranslation(),
				api_key: keyInput ? keyInput.value : '',
			})
				.then(function (json) {
					if (json.success && json.data) {
						showResult(renderProviderResult(json.data));
					} else {
						showResult(
							'<div class="notice notice-error inline"><p>' +
								escHtml(
									(json.data && json.data.message) ||
										(window.thwBibleApiTest && thwBibleApiTest.i18n.testFailed) ||
										'Test failed.'
								) +
								'</p></div>'
						);
					}
				})
				.catch(function () {
					showResult(
						'<div class="notice notice-error inline"><p>' +
							escHtml(
								(window.thwBibleApiTest && thwBibleApiTest.i18n.requestFailed) || 'Request failed.'
							) +
							'</p></div>'
					);
				})
				.finally(function () {
					setBusy(btn, false);
				});
		}

		if (bibliaBtn) {
			bibliaBtn.addEventListener('click', function () {
				runProviderTest('biblia', bibliaBtn, 'thw_biblia_api_key');
			});
		}

		if (youversionBtn) {
			youversionBtn.addEventListener('click', function () {
				runProviderTest('youversion', youversionBtn, 'thw_youversion_app_key');
			});
		}

		if (apiBtn) {
			apiBtn.addEventListener('click', function () {
				runProviderTest('api_bible', apiBtn, 'thw_api_bible_key');
			});
		}

		if (diagBtn) {
			diagBtn.addEventListener('click', function () {
				setBusy(diagBtn, true);
				showResult(
					'<p class="description">' +
						escHtml((window.thwBibleApiTest && thwBibleApiTest.i18n.diagnosing) || 'Diagnosing…') +
						'</p>'
				);
				postTest({
					mode: 'translation',
					translation: getTranslation(),
				})
					.then(function (json) {
						if (json.success && json.data) {
							showResult(renderDiagnosis(json.data));
						} else {
							showResult(
								'<div class="notice notice-error inline"><p>' +
									escHtml(
										(json.data && json.data.message) ||
											(window.thwBibleApiTest && thwBibleApiTest.i18n.diagnosisFailed) ||
											'Diagnosis failed.'
									) +
									'</p></div>'
							);
						}
					})
					.catch(function () {
						showResult(
							'<div class="notice notice-error inline"><p>' +
								escHtml(
									(window.thwBibleApiTest && thwBibleApiTest.i18n.requestFailed) ||
										'Request failed.'
								) +
								'</p></div>'
						);
					})
					.finally(function () {
						setBusy(diagBtn, false);
					});
			});
		}
	});
})();
