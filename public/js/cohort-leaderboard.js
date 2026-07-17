(function () {
	'use strict';

	var cfg = window.hwblCohortLeaderboard || {};

	function load() {
		if (!cfg.restUrl || !cfg.nonce) {
			return;
		}

		var headers = { 'X-WP-Nonce': cfg.nonce };

		Promise.all([
			fetch(cfg.restUrl + 'cohort/leaderboard', { headers: headers }).then(function (r) { return r.json(); }),
			fetch(cfg.restUrl + 'cohort/weekly-challenge', { headers: headers }).then(function (r) { return r.json(); })
		]).then(function (results) {
			var board = results[0];
			var challenge = results[1];
			document.querySelectorAll('[data-hwbl-cohort-leaderboard]').forEach(function (root) {
				var list = root.querySelector('.hwbl-cohort-leaderboard__list');
				var challengeEl = root.querySelector('.hwbl-cohort-weekly-challenge');

				if (challengeEl && challenge && challenge.reference) {
					var link = challenge.url ? '<a href="' + challenge.url + '">' + challenge.reference + '</a>' : challenge.reference;
					challengeEl.innerHTML = '<p><strong>Weekly challenge:</strong> memorize ' + link + '</p>';
				}

				if (!list || !board || !board.leaderboard) {
					return;
				}

				if (!board.leaderboard.length) {
					list.innerHTML = '<li>No cohort members yet.</li>';
					return;
				}

				list.innerHTML = board.leaderboard.map(function (row, index) {
					var label = row.is_you ? ' (you)' : '';
					return '<li><span class="hwbl-cohort-rank">' + (index + 1) + '.</span> ' +
						(row.name || 'Member') + label + ' — ' + row.streak + ' day streak</li>';
				}).join('');
			});
		});
	}

	document.addEventListener('DOMContentLoaded', load);
})();
