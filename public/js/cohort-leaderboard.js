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
				var summaryEl = root.querySelector('.hwbl-cohort-leaderboard__you');

				if (challengeEl && challenge && challenge.reference) {
					var link = challenge.url ? '<a href="' + challenge.url + '">' + challenge.reference + '</a>' : challenge.reference;
					challengeEl.innerHTML = '<p><strong>Weekly challenge:</strong> memorize ' + link + '</p>';
				}

				if (summaryEl && board && board.your_rank) {
					var of = board.member_count ? ' of ' + board.member_count : '';
					var streak = typeof board.your_streak === 'number' ? board.your_streak : 0;
					summaryEl.innerHTML = '<p><strong>You’re #' + board.your_rank + of +
						'</strong> — ' + streak + ' day streak</p>';
				} else if (summaryEl) {
					summaryEl.innerHTML = '';
				}

				if (!list || !board || !board.leaderboard) {
					return;
				}

				if (!board.leaderboard.length) {
					list.innerHTML = '<li>No cohort members yet.</li>';
					return;
				}

				list.innerHTML = board.leaderboard.map(function (row) {
					var label = row.is_you ? ' (you)' : '';
					var rank = row.rank || '';
					return '<li><span class="hwbl-cohort-rank">' + rank + '.</span> ' +
						(row.name || 'Member') + label + ' — ' + row.streak + ' day streak</li>';
				}).join('');
			});
		});
	}

	document.addEventListener('DOMContentLoaded', load);
})();
