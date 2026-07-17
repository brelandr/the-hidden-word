(function () {
	'use strict';

	document.addEventListener('click', function (ev) {
		var btn = ev.target.closest('.hwbl-memorization-audio');
		if (!btn) {
			return;
		}

		var cfg = window.hwblMemorization || {};
		var bookId = btn.getAttribute('data-book-id');
		var chapter = btn.getAttribute('data-chapter');
		var translation = btn.getAttribute('data-translation') || 'kjv';
		var player = btn.parentNode.querySelector('.hwbl-memorization-audio-player');

		if (!bookId || !chapter || !cfg.restUrl) {
			return;
		}

		var url = cfg.restUrl + 'memorize/audio?book_id=' + encodeURIComponent(bookId) +
			'&chapter=' + encodeURIComponent(chapter) +
			'&translation=' + encodeURIComponent(translation);

		fetch(url, { headers: { 'X-WP-Nonce': cfg.nonce || '' } })
			.then(function (res) { return res.ok ? res.json() : null; })
			.then(function (data) {
				if (!data || !data.audio || !player) {
					return;
				}
				var sources = Object.keys(data.audio).map(function (key) {
					return data.audio[key];
				}).filter(Boolean);
				if (!sources.length) {
					return;
				}
				player.src = sources[0];
				player.hidden = false;
				player.play().catch(function () {
					// Autoplay may be blocked until user gesture — already on click.
				});
			});
	});
})();
