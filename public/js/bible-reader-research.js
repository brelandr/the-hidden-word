(function () {
	'use strict';

	function qs(root, sel) {
		return root.querySelector(sel);
	}

	function fetchJson(url, options) {
		var cfg = window.hwblBibleReader || {};
		var headers = { Accept: 'application/json' };
		if (cfg.nonce) {
			headers['X-WP-Nonce'] = cfg.nonce;
		}
		var opts = options || {};
		if (opts.headers) {
			headers = Object.assign({}, headers, opts.headers);
			opts = Object.assign({}, opts, { headers: headers });
		}
		return fetch(url, Object.assign({ credentials: 'same-origin', headers: headers }, opts)).then(function (res) {
			return res.json().then(function (payload) {
				if (!res.ok) {
					throw payload;
				}
				return payload;
			});
		});
	}

	function getExplainConfig() {
		return window.thwAiExplain || null;
	}

	function getTradition(root) {
		var select = qs(root, '.hwbl-bible-reader__research-tradition');
		if (select && select.value) {
			return select.value;
		}
		var explainCfg = getExplainConfig();
		if (explainCfg && explainCfg.userTradition) {
			try {
				return window.localStorage.getItem(explainCfg.traditionStorageKey || 'thw_ai_tradition_preset') || '';
			} catch (e) {
				return '';
			}
		}
		return '';
	}

	function initResearch(root) {
		if (!root || root.dataset.hwblResearchInit === '1') {
			return;
		}
		root.dataset.hwblResearchInit = '1';

		var cfg = window.hwblBibleReader || {};
		var i18n = cfg.i18n || {};
		var explainCfg = getExplainConfig();
		var selectedVerse = parseInt(root.dataset.verse || '0', 10);

		var elScope = qs(root, '.hwbl-bible-reader__research-scope');
		var elBtn = qs(root, '.hwbl-bible-reader__research-btn');
		var elLesson = qs(root, '.hwbl-bible-reader__research-lesson');
		var elPanel = qs(root, '.hwbl-bible-reader__research-panel');
		var elTitle = qs(root, '.hwbl-bible-reader__research-title');
		var elOutput = qs(root, '.hwbl-bible-reader__research-output');
		var elContent = qs(root, '.hwbl-bible-reader__content');

		function updateResearchMeta() {
			if (!cfg.restUrl) {
				return;
			}
			var bookId = parseInt(root.dataset.book || '0', 10);
			var chapter = parseInt(root.dataset.chapter || '0', 10);
			var verse = selectedVerse || parseInt(root.dataset.verse || '0', 10);
			var url =
				cfg.restUrl +
				'bible/research?book_id=' +
				encodeURIComponent(String(bookId)) +
				'&chapter=' +
				encodeURIComponent(String(chapter)) +
				'&verse=' +
				encodeURIComponent(String(verse));

			fetchJson(url)
				.then(function (data) {
					if (elLesson && data.lesson_url) {
						elLesson.href = data.lesson_url;
						elLesson.hidden = false;
					} else if (elLesson) {
						elLesson.hidden = true;
					}
				})
				.catch(function () {
					if (elLesson) {
						elLesson.hidden = true;
					}
				});
		}

		function showError(err) {
			var msg = i18n.researchError || 'Could not generate an explanation.';
			if (err && err.code === 'thw_ai_rate_limit') {
				msg = (explainCfg && explainCfg.rateLimit) || msg;
			} else if (err && err.message) {
				msg = err.message;
			} else if (err && err.data && err.data.message) {
				msg = err.data.message;
			}
			if (elOutput) {
				elOutput.innerHTML = '<p class="hwbl-bible-reader__research-error">' + msg + '</p>';
			}
		}

		function runExplain() {
			if (!cfg.explainRestUrl) {
				if (!cfg.loggedIn) {
					showError({ message: i18n.researchLogin || 'Log in to generate an AI explanation.' });
				} else {
					showError({ message: i18n.researchError });
				}
				if (elPanel) {
					elPanel.hidden = false;
				}
				return;
			}

			if (!cfg.loggedIn) {
				if (elPanel) {
					elPanel.hidden = false;
				}
				showError({ message: i18n.researchLogin || 'Log in to generate an AI explanation.' });
				return;
			}

			var scope = elScope ? elScope.value : 'verse';
			var verse = scope === 'verse' ? selectedVerse || parseInt(root.dataset.verse || '0', 10) : 0;
			if (scope === 'verse' && !verse) {
				showError({ message: i18n.researchHint || 'Click a verse first.' });
				if (elPanel) {
					elPanel.hidden = false;
				}
				return;
			}

			if (elPanel) {
				elPanel.hidden = false;
			}
			if (elTitle) {
				elTitle.textContent = scope === 'chapter' ? i18n.researchChapter || 'This chapter' : i18n.researchVerse || 'This verse';
			}
			if (elOutput) {
				elOutput.innerHTML = '<p class="hwbl-bible-reader__research-loading">' + (i18n.researchLoading || 'Generating explanation…') + '</p>';
			}

			fetchJson(cfg.explainRestUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify({
					book_id: parseInt(root.dataset.book || '0', 10),
					chapter: parseInt(root.dataset.chapter || '0', 10),
					verse: verse,
					translation: root.dataset.translation || cfg.translation,
					scope: scope,
					tradition: getTradition(root),
				}),
			})
				.then(function (payload) {
					if (elTitle && payload.reference) {
						elTitle.textContent = payload.reference;
					}
					if (elOutput) {
						var html = payload.content || '';
						if (!html) {
							showError({ message: i18n.researchError || 'Could not generate an explanation.' });
							return;
						}
						if (payload.complianceFlagged && explainCfg && explainCfg.complianceFlagged) {
							html = '<p class="hwbl-bible-reader__research-flag">' + explainCfg.complianceFlagged + '</p>' + html;
						}
						elOutput.innerHTML = html;
					}
					if (elLesson && payload.lessonUrl) {
						elLesson.href = payload.lessonUrl;
						elLesson.hidden = false;
					}
				})
				.catch(showError);
		}

		if (elContent) {
			elContent.addEventListener('click', function (event) {
				var verseNode = event.target.closest('.hwbl-bible-reader__verse');
				if (!verseNode) {
					return;
				}
				selectedVerse = parseInt(verseNode.getAttribute('data-verse') || '0', 10);
				if (elScope) {
					elScope.value = 'verse';
				}
				updateResearchMeta();
			});
		}

		if (elBtn) {
			elBtn.addEventListener('click', runExplain);
		}

		if (elScope) {
			elScope.addEventListener('change', function () {
				if (elPanel && elScope.value === 'chapter') {
					elPanel.hidden = true;
				}
			});
		}

		var observer = new MutationObserver(function () {
			selectedVerse = parseInt(root.dataset.verse || '0', 10);
			updateResearchMeta();
		});
		observer.observe(root, { attributes: true, attributeFilter: ['data-verse', 'data-book', 'data-chapter'] });

		updateResearchMeta();
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.hwbl-bible-reader').forEach(initResearch);
	});
})();
