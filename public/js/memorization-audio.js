(function () {
	'use strict';

	function cfg() {
		return window.hwblMemorization || {};
	}

	function i18n(key, fallback) {
		var strings = cfg().i18n || {};
		return strings[key] || fallback;
	}

	function setStatus(wrap, text, isError) {
		var status = wrap ? wrap.querySelector('.hwbl-memorization-audio-status') : null;
		if (!status) {
			return;
		}
		status.hidden = !text;
		status.textContent = text || '';
		status.classList.toggle('is-error', !!isError);
	}

	function pickAudioUrl(audioMap, preferredNarrator) {
		if (!audioMap || typeof audioMap !== 'object') {
			return '';
		}
		if (preferredNarrator && audioMap[preferredNarrator]) {
			return audioMap[preferredNarrator];
		}
		var keys = Object.keys(audioMap);
		for (var i = 0; i < keys.length; i++) {
			if (audioMap[keys[i]]) {
				return audioMap[keys[i]];
			}
		}
		return '';
	}

	function stopSpeech() {
		if (typeof window.speechSynthesis !== 'undefined') {
			try {
				window.speechSynthesis.cancel();
			} catch (e) {
				// Ignore.
			}
		}
	}

	function speakVerse(text, wrap, btn) {
		if (typeof window.speechSynthesis === 'undefined') {
			return false;
		}
		var verse = String(text || '').trim();
		if (!verse) {
			return false;
		}

		stopSpeech();
		var player = wrap ? wrap.querySelector('.hwbl-memorization-audio-player') : null;
		if (player) {
			try {
				player.pause();
			} catch (e) {
				// Ignore.
			}
			player.hidden = true;
		}

		var utter = new window.SpeechSynthesisUtterance(verse);
		utter.rate = 0.95;
		utter.onend = function () {
			btn.classList.remove('is-speaking');
			btn.textContent = i18n('listenVerse', 'Listen to verse');
			setStatus(wrap, '', false);
		};
		utter.onerror = function () {
			btn.classList.remove('is-speaking');
			btn.textContent = i18n('listenVerse', 'Listen to verse');
			setStatus(wrap, i18n('audioUnavailable', 'Audio is not available right now.'), true);
		};

		btn.classList.add('is-speaking');
		btn.textContent = i18n('stopListening', 'Stop listening');
		setStatus(wrap, i18n('audioPlayingVerse', 'Playing this verse.'), false);
		window.speechSynthesis.speak(utter);
		return true;
	}

	function playChapterFallback(btn, wrap, bookId, chapter, translation) {
		var player = wrap ? wrap.querySelector('.hwbl-memorization-audio-player') : null;
		var restBase = cfg().restUrl || '';
		if (!bookId || !chapter || !restBase || !player) {
			setStatus(wrap, i18n('audioUnavailable', 'Audio is not available right now.'), true);
			return;
		}

		btn.disabled = true;
		setStatus(wrap, i18n('audioLoading', 'Loading chapter audio…'), false);

		var url =
			restBase +
			'memorize/audio?book_id=' +
			encodeURIComponent(bookId) +
			'&chapter=' +
			encodeURIComponent(chapter) +
			'&translation=' +
			encodeURIComponent(translation);

		fetch(url, { headers: { 'X-WP-Nonce': cfg().nonce || '' } })
			.then(function (res) {
				return res.ok ? res.json() : null;
			})
			.then(function (data) {
				btn.disabled = false;
				if (!data) {
					setStatus(wrap, i18n('audioUnavailable', 'Audio is not available right now.'), true);
					return;
				}

				var src = pickAudioUrl(data.audio, data.narrator || cfg().narrator || 'david');
				if (!src) {
					setStatus(
						wrap,
						data.message || i18n('audioUnavailable', 'Audio is not available right now.'),
						true
					);
					return;
				}

				player.src = src;
				player.hidden = false;
				setStatus(
					wrap,
					i18n(
						'audioPlayingChapter',
						'Verse speech unavailable — playing the full chapter instead.'
					),
					false
				);

				var playPromise = player.play();
				if (playPromise && typeof playPromise.catch === 'function') {
					playPromise.catch(function () {
						setStatus(wrap, i18n('audioBlocked', 'Tap play on the audio player to listen.'), false);
					});
				}
			})
			.catch(function () {
				btn.disabled = false;
				setStatus(wrap, i18n('audioUnavailable', 'Audio is not available right now.'), true);
			});
	}

	document.addEventListener('click', function (ev) {
		var btn = ev.target.closest('.hwbl-memorization-audio');
		if (!btn) {
			return;
		}

		ev.preventDefault();

		var wrap = btn.closest('.hwbl-memorization-audio-wrap') || btn.parentNode;
		var bookId = btn.getAttribute('data-book-id');
		var chapter = btn.getAttribute('data-chapter');
		var translation = btn.getAttribute('data-translation') || 'kjv';
		var verseText =
			btn.getAttribute('data-verse-text') ||
			(btn.closest('.hwbl-memorization') &&
				btn.closest('.hwbl-memorization').getAttribute('data-verse')) ||
			'';

		if (btn.classList.contains('is-speaking')) {
			stopSpeech();
			btn.classList.remove('is-speaking');
			btn.textContent = i18n('listenVerse', 'Listen to verse');
			setStatus(wrap, '', false);
			return;
		}

		if (btn.disabled) {
			return;
		}

		// Prefer exact verse TTS — chapter MP3s always start at verse 1.
		if (speakVerse(verseText, wrap, btn)) {
			return;
		}

		playChapterFallback(btn, wrap, bookId, chapter, translation);
	});
})();
