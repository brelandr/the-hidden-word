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
		var prefs = window.hwblUserPreferences;
		if (prefs && prefs.userTraditionAllowed && prefs.userTraditionAllowed()) {
			return prefs.readStoredTradition() || '';
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

	function mountResearchJournal(root, elTitle, elOutput) {
		if (!window.HWBLJournalExport || !root || !elOutput) {
			return;
		}
		var panel = qs(root, '.hwbl-bible-reader__research-panel');
		if (!panel) {
			return;
		}
		var title =
			(elTitle && String(elTitle.textContent || '').trim()) ||
			'Bible explanation';
		var statusEl = panel.querySelector('.hwbl-journal-export-status');
		window.HWBLJournalExport.ensureExplainActions(
			panel,
			function () {
				var parts = [];
				if (title) {
					parts.push(title);
				}
				var explain = window.HWBLJournalExport.plainTextFromEl(elOutput);
				if (explain) {
					parts.push(explain);
				}
				return parts.join('\n\n');
			},
			title,
			root,
			function (msg) {
				if (!statusEl) {
					statusEl = document.createElement('p');
					statusEl.className =
						'hwbl-journal-export-status hwbl-bible-reader__research-status description';
					statusEl.setAttribute('role', 'status');
					panel.appendChild(statusEl);
				}
				statusEl.textContent = msg || '';
			}
		);
	}

	function setSavedLink(elSaved, url, label) {
		if (!elSaved) {
			return;
		}
		if (url) {
			elSaved.href = url;
			if (label) {
				elSaved.textContent = label;
			}
			elSaved.hidden = false;
		} else {
			elSaved.hidden = true;
			elSaved.removeAttribute('href');
		}
	}

	function setPostLink(elWrap, elPost, url, label) {
		if (!elWrap || !elPost) {
			return;
		}
		if (url) {
			elPost.href = url;
			elPost.textContent = label || 'Read saved explanation';
			elWrap.hidden = false;
		} else {
			elWrap.hidden = true;
			elPost.removeAttribute('href');
			elPost.textContent = '';
		}
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
		var elSaved = qs(root, '.hwbl-bible-reader__research-saved');
		var elLesson = qs(root, '.hwbl-bible-reader__research-lesson');
		var elPanel = qs(root, '.hwbl-bible-reader__research-panel');
		var elTitle = qs(root, '.hwbl-bible-reader__research-title');
		var elOutput = qs(root, '.hwbl-bible-reader__research-output');
		var elPostWrap = qs(root, '.hwbl-bible-reader__research-post-wrap');
		var elPost = qs(root, '.hwbl-bible-reader__research-post');
		var elContent = qs(root, '.hwbl-bible-reader__content');
		var elTradition = qs(root, '.hwbl-bible-reader__research-tradition');

		(function syncTraditionSelect() {
			if (!elTradition) {
				return;
			}
			var prefs = window.hwblUserPreferences;
			var stored = '';
			if (prefs && prefs.userTraditionAllowed && prefs.userTraditionAllowed()) {
				stored = prefs.readStoredTradition() || '';
			}
			if (
				stored &&
				[].some.call(elTradition.options || [], function (opt) {
					return opt.value === stored;
				})
			) {
				elTradition.value = stored;
			}
		})();

		function updateResearchMeta() {
			if (!cfg.restUrl) {
				return;
			}
			var bookId = parseInt(root.dataset.book || '0', 10);
			var chapter = parseInt(root.dataset.chapter || '0', 10);
			var verse = selectedVerse || parseInt(root.dataset.verse || '0', 10);
			var scope = elScope ? elScope.value : 'verse';
			var url =
				cfg.restUrl +
				'bible/research?book_id=' +
				encodeURIComponent(String(bookId)) +
				'&chapter=' +
				encodeURIComponent(String(chapter)) +
				'&verse=' +
				encodeURIComponent(String(verse)) +
				'&translation=' +
				encodeURIComponent(String(root.dataset.translation || cfg.translation || '')) +
				'&scope=' +
				encodeURIComponent(String(scope)) +
				'&tradition=' +
				encodeURIComponent(String(getTradition(root)));

			fetchJson(url)
				.then(function (data) {
					if (elLesson && data.lesson_url) {
						elLesson.href = data.lesson_url;
						elLesson.hidden = false;
					} else if (elLesson) {
						elLesson.hidden = true;
					}
					setSavedLink(elSaved, data.explain_url || '', i18n.researchSaved || 'Read saved explanation');
				})
				.catch(function () {
					if (elLesson) {
						elLesson.hidden = true;
					}
					setSavedLink(elSaved, '', '');
				});
		}

		function showError(err) {
			var msg = i18n.researchError || 'Could not generate an explanation.';
			if (err && err.code === 'thw_ai_rate_limit') {
				msg = (explainCfg && explainCfg.rateLimit) || msg;
			} else if (err && err.code === 'thw_ai_login') {
				msg = i18n.researchLogin || msg;
			} else if (err && err.message) {
				msg = err.message;
			} else if (err && err.data && err.data.message) {
				msg = err.data.message;
			}
			if (elOutput) {
				elOutput.innerHTML = '<p class="hwbl-bible-reader__research-error">' + msg + '</p>';
			}
			setPostLink(elPostWrap, elPost, '', '');
		}

		function runExplain() {
			if (!cfg.explainRestUrl) {
				showError({ message: i18n.researchError });
				if (elPanel) {
					elPanel.hidden = false;
				}
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
				elOutput.innerHTML =
					'<p class="hwbl-bible-reader__research-loading">' +
					(i18n.researchLoading || 'Generating explanation…') +
					'</p>';
			}
			setPostLink(elPostWrap, elPost, '', '');

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
						if (payload.hasTraditionDiff) {
							var label =
								payload.traditionLabel ||
								payload.tradition ||
								(i18n.researchTraditionDiff || 'Tradition variation');
							html =
								'<p class="hwbl-bible-reader__research-tradition-badge">' +
								label +
								'</p>' +
								html;
						}
						if (payload.complianceFlagged && explainCfg && explainCfg.complianceFlagged) {
							html = '<p class="hwbl-bible-reader__research-flag">' + explainCfg.complianceFlagged + '</p>' + html;
						}
						elOutput.innerHTML = html;
						mountResearchJournal(root, elTitle, elOutput);
					}
					if (elLesson && payload.lessonUrl) {
						elLesson.href = payload.lessonUrl;
						elLesson.hidden = false;
					}
					if (payload.postUrl) {
						setSavedLink(elSaved, payload.postUrl, i18n.researchSaved || 'Read saved explanation');
						setPostLink(
							elPostWrap,
							elPost,
							payload.postUrl,
							i18n.researchSaved || 'Read saved explanation'
						);
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

		var elStudyBtn = qs(root, '.hwbl-bible-reader__study-btn');
		var elStudyPanel = qs(root, '.hwbl-bible-reader__study-panel');
		var elStudyStatus = qs(root, '.hwbl-bible-reader__study-status');
		var elStudyOutput = qs(root, '.hwbl-bible-reader__study-output');

		function runStudyCard() {
			var verse = selectedVerse || parseInt(root.dataset.verse || '0', 10);
			if (!verse) {
				if (elStudyPanel) {
					elStudyPanel.hidden = false;
				}
				if (elStudyStatus) {
					elStudyStatus.textContent = i18n.studyHint || 'Click a verse first.';
				}
				return;
			}
			if (!cfg.studyCardRestUrl) {
				if (elStudyStatus) {
					elStudyStatus.textContent = i18n.studyError || 'Study card unavailable.';
				}
				return;
			}
			if (!cfg.loggedIn) {
				if (elStudyPanel) {
					elStudyPanel.hidden = false;
				}
				if (elStudyStatus) {
					elStudyStatus.textContent = i18n.studyLogin || 'Sign in to generate a study card.';
				}
				return;
			}

			if (elStudyPanel) {
				elStudyPanel.hidden = false;
			}
			if (elStudyStatus) {
				elStudyStatus.textContent = i18n.studyLoading || 'Building your Verse Study Card…';
			}
			if (elStudyOutput) {
				elStudyOutput.innerHTML = '';
			}

			fetchJson(cfg.studyCardRestUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					book_id: parseInt(root.dataset.book || '0', 10),
					chapter: parseInt(root.dataset.chapter || '0', 10),
					verse: verse,
					translation: root.dataset.translation || cfg.translation,
					tradition: getTradition(root),
				}),
			})
				.then(function (payload) {
					if (elStudyStatus) {
						elStudyStatus.textContent = '';
					}
					if (elStudyOutput) {
						if (window.hwblVerseStudyApi && window.hwblVerseStudyApi.renderCardHtml) {
							elStudyOutput.innerHTML = window.hwblVerseStudyApi.renderCardHtml(payload);
						} else {
							elStudyOutput.innerHTML =
								'<p><strong>' +
								(payload.reference || '') +
								'</strong></p><p>' +
								(payload.plainWords || '') +
								'</p>';
						}
					}
				})
				.catch(function (err) {
					var msg = i18n.studyError || 'Could not build a study card.';
					if (err && err.code === 'thw_ai_login') {
						msg = i18n.studyLogin || msg;
					} else if (err && err.message) {
						msg = err.message;
					}
					if (elStudyStatus) {
						elStudyStatus.textContent = msg;
					}
				});
		}

		if (elStudyBtn) {
			elStudyBtn.addEventListener('click', runStudyCard);
		}

		var elMapBtn = qs(root, '.hwbl-bible-reader__map-btn');
		var elMapPanel = qs(root, '.hwbl-bible-reader__map-panel');

		function runMapPlaces() {
			var mapsCfg = cfg.maps || window.hwblBibleMap || {};
			if (!mapsCfg.enabled) {
				return;
			}
			var scope = elScope ? elScope.value : 'verse';
			var verse =
				scope === 'chapter'
					? 0
					: selectedVerse || parseInt(root.dataset.verse || '0', 10);
			if (scope !== 'chapter' && !verse) {
				if (elMapPanel) {
					elMapPanel.hidden = false;
				}
				var statusEarly = elMapPanel
					? elMapPanel.querySelector('.hwbl-bible-map__status')
					: null;
				if (statusEarly) {
					statusEarly.textContent =
						i18n.mapHint || 'Click a verse, then map its places.';
				}
				return;
			}

			if (elMapPanel) {
				elMapPanel.hidden = false;
			}
			if (elStudyPanel) {
				elStudyPanel.hidden = true;
			}
			if (elPanel) {
				elPanel.hidden = true;
			}

			var api = window.hwblBibleMapApi;
			if (!api || !api.renderIntoPanel) {
				var statusMissing = elMapPanel
					? elMapPanel.querySelector('.hwbl-bible-map__status')
					: null;
				if (statusMissing) {
					statusMissing.textContent =
						i18n.mapError || 'Could not load places for this passage.';
				}
				return;
			}

			api
				.renderIntoPanel(
					elMapPanel,
					parseInt(root.dataset.book || '0', 10),
					parseInt(root.dataset.chapter || '0', 10),
					verse
				)
				.catch(function () {
					var statusErr = elMapPanel
						? elMapPanel.querySelector('.hwbl-bible-map__status')
						: null;
					if (statusErr) {
						statusErr.textContent =
							i18n.mapError || 'Could not load places for this passage.';
					}
				});
		}

		if (elMapBtn) {
			elMapBtn.addEventListener('click', runMapPlaces);
		}

		if (elScope) {
			elScope.addEventListener('change', function () {
				if (elPanel && elScope.value === 'chapter') {
					elPanel.hidden = true;
				}
				updateResearchMeta();
			});
		}

		if (elTradition) {
			elTradition.addEventListener('change', function () {
				var prefs = window.hwblUserPreferences;
				if (prefs && prefs.saveTradition && elTradition.value) {
					prefs.saveTradition(elTradition.value);
				}
				updateResearchMeta();
			});
		}

		var observer = new MutationObserver(function () {
			selectedVerse = parseInt(root.dataset.verse || '0', 10);
			updateResearchMeta();
		});
		observer.observe(root, {
			attributes: true,
			attributeFilter: ['data-verse', 'data-book', 'data-chapter', 'data-translation'],
		});

		updateResearchMeta();
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.hwbl-bible-reader').forEach(initResearch);
	});
})();
