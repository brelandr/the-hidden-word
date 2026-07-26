(function ($) {
	'use strict';

	var cfg = window.hwblLocalBibles || {};
	var pollTimer = null;

	function post(action, data) {
		data = data || {};
		data.action = action;
		data.nonce = cfg.nonce;
		return $.post(cfg.ajaxUrl, data);
	}

	function statusLabel(status) {
		var map = {
			not_installed: (cfg.i18n && cfg.i18n.notInstalled) || 'Not installed',
			downloading: (cfg.i18n && cfg.i18n.installing) || 'Downloading',
			importing: (cfg.i18n && cfg.i18n.installing) || 'Importing',
			ready: (cfg.i18n && cfg.i18n.ready) || 'Ready',
			error: (cfg.i18n && cfg.i18n.error) || 'Error'
		};
		return map[status] || status;
	}

	function applyRows(rows) {
		if (!rows || !rows.length) {
			return;
		}
		var busy = false;
		rows.forEach(function (row) {
			var $tr = $('tr[data-slug="' + row.slug + '"]');
			if (!$tr.length) {
				return;
			}
			var status = row.status || 'not_installed';
			var isBusy = status === 'downloading' || status === 'importing';
			var installed = !!row.installed;
			busy = busy || isBusy;

			$tr.find('.hwbl-local-status-label').text(statusLabel(status));
			$tr.find('.hwbl-local-verse-count').text(String(row.verse_count || 0));

			var $progress = $tr.find('.hwbl-local-progress');
			if (isBusy) {
				$progress.removeAttr('hidden');
				$progress.find('progress').val(row.percent || 0);
				$progress.find('.hwbl-local-percent').text(String(row.percent || 0) + '%');
			} else {
				$progress.attr('hidden', 'hidden');
			}

			var $err = $tr.find('.hwbl-local-error');
			if (row.error) {
				if (!$err.length) {
					$tr.find('.hwbl-local-status').append(
						$('<p class="hwbl-local-error"></p>').text(row.error)
					);
				} else {
					$err.text(row.error);
				}
			} else {
				$err.remove();
			}

			$tr.find('input[type="checkbox"]').prop('disabled', isBusy || installed).prop('checked', false);
			$tr.find('.hwbl-local-install-one').prop('disabled', isBusy || installed);
			$tr.find('.hwbl-local-remove').prop(
				'disabled',
				!installed && !isBusy && status !== 'error'
			);
		});

		if (busy) {
			schedulePoll();
		} else {
			clearPoll();
		}
	}

	function schedulePoll() {
		clearPoll();
		pollTimer = window.setTimeout(function () {
			post('hwbl_local_bible_process', {})
				.done(function (resp) {
					if (resp && resp.success && resp.data && resp.data.rows) {
						applyRows(resp.data.rows);
					} else {
						schedulePoll();
					}
				})
				.fail(function () {
					schedulePoll();
				});
		}, 900);
	}

	function clearPoll() {
		if (pollTimer) {
			window.clearTimeout(pollTimer);
			pollTimer = null;
		}
	}

	function installSlugs(slugs) {
		if (!slugs.length) {
			window.alert((cfg.i18n && cfg.i18n.selectOne) || 'Select at least one translation.');
			return;
		}
		post('hwbl_local_bible_install', { 'slugs[]': slugs })
			.done(function (resp) {
				if (resp && resp.success && resp.data && resp.data.rows) {
					applyRows(resp.data.rows);
				}
			});
	}

	function filterByLanguage() {
		var lang = String($('#hwbl-local-language').val() || '');
		var visible = 0;
		$('.hwbl-local-bibles-table tbody tr[data-slug]').each(function () {
			var rowLang = String($(this).attr('data-language') || '');
			var show = !lang || rowLang === lang;
			$(this).toggle(show);
			if (show) {
				visible += 1;
			} else {
				$(this).find('input[type="checkbox"]').prop('checked', false);
			}
		});
		$('.hwbl-local-filter-empty').prop('hidden', visible > 0);
	}

	$(function () {
		if (cfg.rows) {
			applyRows(cfg.rows);
		}

		filterByLanguage();
		$('#hwbl-local-language').on('change', filterByLanguage);

		$('#hwbl-local-install-selected').on('click', function () {
			var slugs = [];
			$('#hwbl-local-bibles-form input[name="slugs[]"]:checked').each(function () {
				if ($(this).closest('tr').is(':visible')) {
					slugs.push($(this).val());
				}
			});
			installSlugs(slugs);
		});

		$(document).on('click', '.hwbl-local-install-one', function () {
			installSlugs([$(this).data('slug')]);
		});

		$(document).on('click', '.hwbl-local-remove', function () {
			if (!window.confirm((cfg.i18n && cfg.i18n.confirmRemove) || 'Remove this Bible?')) {
				return;
			}
			var slug = $(this).data('slug');
			post('hwbl_local_bible_remove', { slug: slug }).done(function (resp) {
				if (resp && resp.success && resp.data && resp.data.rows) {
					applyRows(resp.data.rows);
				}
			});
		});
	});
})(jQuery);
