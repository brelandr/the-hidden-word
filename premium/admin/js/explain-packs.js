(function ($) {
	'use strict';

	var cfg = window.thwExplainPacks || {};
	var exportTimer = null;
	var importTimer = null;
	var removeTimer = null;

	function post(action, data) {
		data = data || {};
		data.action = action;
		data.nonce = cfg.nonce;
		return $.post(cfg.ajaxUrl, data);
	}

	function postFile(action, formData) {
		formData.append('action', action);
		formData.append('nonce', cfg.nonce);
		return $.ajax({
			url: cfg.ajaxUrl,
			method: 'POST',
			data: formData,
			processData: false,
			contentType: false
		});
	}

	function clearTimer(which) {
		if (which === 'export' && exportTimer) {
			window.clearTimeout(exportTimer);
			exportTimer = null;
		}
		if (which === 'import' && importTimer) {
			window.clearTimeout(importTimer);
			importTimer = null;
		}
		if (which === 'remove' && removeTimer) {
			window.clearTimeout(removeTimer);
			removeTimer = null;
		}
	}

	function applyExport(job, snippet) {
		job = job || { status: 'idle' };
		var $box = $('#thw-explain-pack-export-status');
		$box.attr('data-status', job.status || 'idle');
		$box.find('.thw-pack-export-label').text(job.status || 'idle');
		$box.find('.thw-pack-export-counts').text(
			(job.written || 0) + ' / ' + (job.total || 0) + ' written' +
			(job.deleted ? ' · ' + job.deleted + ' deleted' : '')
		);
		$box.find('progress').val(job.percent || 0);
		$box.find('.thw-pack-export-percent').text(String(job.percent || 0) + '%');

		var $dl = $box.find('.thw-pack-export-download');
		var $snipWrap = $box.find('.thw-pack-catalog-snippet-wrap');
		if (job.download_url) {
			$('#thw-pack-download-link').attr('href', job.download_url);
			$dl.removeAttr('hidden');
			$snipWrap.removeAttr('hidden');
			if (snippet) {
				$('#thw-pack-catalog-snippet').val(JSON.stringify(snippet, null, 2));
			}
		} else {
			$dl.attr('hidden', 'hidden');
			$snipWrap.attr('hidden', 'hidden');
		}

		var $err = $box.find('.thw-pack-export-error');
		if (job.error) {
			$err.text(job.error).removeAttr('hidden');
		} else {
			$err.text('').attr('hidden', 'hidden');
		}

		if (job.status === 'building' || job.status === 'removing') {
			scheduleExportPoll();
		} else {
			clearTimer('export');
		}
	}

	function applyImport(job) {
		job = job || { status: 'idle' };
		var $box = $('#thw-explain-pack-import-status');
		$box.attr('data-status', job.status || 'idle');
		$box.find('.thw-pack-import-label').text(job.status || 'idle');
		$box.find('.thw-pack-import-counts').text(
			(job.imported || 0) + ' imported · ' +
			(job.skipped || 0) + ' skipped · ' +
			(job.errors || 0) + ' errors · ' +
			(job.total || 0) + ' total'
		);
		$box.find('progress').val(job.percent || 0);
		$box.find('.thw-pack-import-percent').text(String(job.percent || 0) + '%');

		var $err = $box.find('.thw-pack-import-error');
		if (job.error) {
			$err.text(job.error).removeAttr('hidden');
		} else {
			$err.text('').attr('hidden', 'hidden');
		}

		if (['downloading', 'extracting', 'importing'].indexOf(job.status) !== -1) {
			scheduleImportPoll();
		} else {
			clearTimer('import');
		}
	}

	function scheduleExportPoll() {
		clearTimer('export');
		exportTimer = window.setTimeout(function () {
			post('thw_explain_pack_export_process', {})
				.done(function (resp) {
					if (resp && resp.success && resp.data) {
						applyExport(resp.data.job, resp.data.snippet);
					} else {
						scheduleExportPoll();
					}
				})
				.fail(function () {
					scheduleExportPoll();
				});
		}, 1000);
	}

	function scheduleImportPoll() {
		clearTimer('import');
		importTimer = window.setTimeout(function () {
			post('thw_explain_pack_import_process', {})
				.done(function (resp) {
					if (resp && resp.success && resp.data) {
						applyImport(resp.data.job);
					} else {
						scheduleImportPoll();
					}
				})
				.fail(function () {
					scheduleImportPoll();
				});
		}, 1000);
	}

	function selectedScopes(name) {
		var values = [];
		$('input[name="' + name + '"]:checked').each(function () {
			values.push($(this).val());
		});
		return values.length ? values : ['verse'];
	}

	function startImport(url, packId) {
		if (!url) {
			window.alert((cfg.i18n && cfg.i18n.needUrl) || 'Need URL');
			return;
		}
		post('thw_explain_pack_import_start', { url: url, pack_id: packId || '' }).done(function (resp) {
			if (resp && resp.success && resp.data) {
				applyImport(resp.data.job);
			} else if (resp && resp.data && resp.data.message) {
				window.alert(resp.data.message);
			}
		});
	}

	function removeLoop(data) {
		post('thw_explain_pack_remove', data).done(function (resp) {
			if (!(resp && resp.success && resp.data && resp.data.result)) {
				if (resp && resp.data && resp.data.message) {
					window.alert(resp.data.message);
				}
				return;
			}
			if (resp.data.result.continue) {
				clearTimer('remove');
				removeTimer = window.setTimeout(function () {
					removeLoop(data);
				}, 400);
			}
		});
	}

	$(function () {
		applyExport(cfg.exportJob);
		applyImport(cfg.importJob);

		$('#thw-explain-pack-export-start').on('click', function () {
			var translation = $('#thw-explain-pack-export-translation').val();
			var tradition = $('#thw-explain-pack-export-tradition').val();
			if (!translation || !tradition) {
				window.alert((cfg.i18n && cfg.i18n.needKeys) || 'Choose Bible and tradition');
				return;
			}
			post('thw_explain_pack_export_start', {
				translation: translation,
				tradition: tradition,
				scopes: selectedScopes('export_scopes[]'),
				version: $('#thw-explain-pack-export-version').val() || '1.0.0',
				remove_after: $('#thw-explain-pack-remove-after').is(':checked') ? 1 : 0
			}).done(function (resp) {
				if (resp && resp.success && resp.data) {
					applyExport(resp.data.job, resp.data.snippet);
				} else if (resp && resp.data && resp.data.message) {
					window.alert(resp.data.message);
				}
			});
		});

		$('#thw-explain-pack-export-clear').on('click', function () {
			post('thw_explain_pack_export_clear', {}).done(function (resp) {
				if (resp && resp.success) {
					applyExport(resp.data.job);
				}
			});
		});

		$(document).on('click', '.thw-explain-pack-install', function () {
			startImport($(this).data('url'), $(this).data('pack-id'));
		});

		$('#thw-explain-pack-import-url').on('click', function () {
			startImport($('#thw-explain-pack-url').val(), '');
		});

		$('#thw-explain-pack-import-file').on('click', function () {
			var input = document.getElementById('thw-explain-pack-file');
			if (!input || !input.files || !input.files[0]) {
				window.alert((cfg.i18n && cfg.i18n.needUrl) || 'Choose a ZIP');
				return;
			}
			var fd = new FormData();
			fd.append('pack', input.files[0]);
			postFile('thw_explain_pack_import_start', fd).done(function (resp) {
				if (resp && resp.success && resp.data) {
					applyImport(resp.data.job);
				} else if (resp && resp.data && resp.data.message) {
					window.alert(resp.data.message);
				}
			});
		});

		$(document).on('click', '.thw-explain-pack-remove', function () {
			if (!window.confirm((cfg.i18n && cfg.i18n.confirmRemove) || 'Remove?')) {
				return;
			}
			removeLoop({ pack_id: $(this).data('pack-id') });
		});

		$('#thw-explain-pack-remove-keys').on('click', function () {
			var translation = $('#thw-explain-pack-remove-translation').val();
			var tradition = $('#thw-explain-pack-remove-tradition').val();
			if (!translation || !tradition) {
				window.alert((cfg.i18n && cfg.i18n.needKeys) || 'Choose Bible and tradition');
				return;
			}
			if (!window.confirm((cfg.i18n && cfg.i18n.confirmRemove) || 'Remove?')) {
				return;
			}
			removeLoop({
				translation: translation,
				tradition: tradition,
				scopes: ['verse', 'chapter']
			});
		});

		$('#thw-explain-pack-refresh-catalog').on('click', function () {
			post('thw_explain_pack_refresh_catalog', {}).done(function (resp) {
				if (resp && resp.success) {
					window.location.reload();
				}
			});
		});

		function setTokenStatus(hasToken, cleared) {
			var text = hasToken
				? 'Token on file.'
				: cleared
					? ((cfg.i18n && cfg.i18n.tokenCleared) || 'GitHub token cleared.')
					: 'No token saved yet.';
			$('#thw-explain-pack-token-status').text(text);
			$('#thw-explain-pack-publish').prop('disabled', !hasToken);
			cfg.hasToken = !!hasToken;
		}

		$('#thw-explain-pack-save-token').on('click', function () {
			var token = $('#thw-explain-pack-github-token').val();
			if (!token) {
				return;
			}
			post('thw_explain_pack_save_token', { token: token }).done(function (resp) {
				if (resp && resp.success) {
					$('#thw-explain-pack-github-token').val('');
					setTokenStatus(!!resp.data.hasToken, false);
					window.alert((cfg.i18n && cfg.i18n.tokenSaved) || 'Saved');
				} else if (resp && resp.data && resp.data.message) {
					window.alert(resp.data.message);
				}
			});
		});

		$('#thw-explain-pack-clear-token').on('click', function () {
			post('thw_explain_pack_save_token', { clear: 1 }).done(function (resp) {
				if (resp && resp.success) {
					$('#thw-explain-pack-github-token').val('');
					setTokenStatus(false, true);
				}
			});
		});

		$('#thw-explain-pack-publish').on('click', function () {
			var $btn = $(this);
			var $out = $('#thw-pack-publish-result');
			$btn.prop('disabled', true);
			$out.attr('hidden', 'hidden').removeClass('is-error is-ok');
			post('thw_explain_pack_publish', {})
				.done(function (resp) {
					if (resp && resp.success && resp.data) {
						var msg =
							((cfg.i18n && cfg.i18n.publishOk) || 'Published.') +
							(resp.data.download_url ? ' ' + resp.data.download_url : '');
						$out.text(msg).addClass('is-ok').removeAttr('hidden');
					} else if (resp && resp.data && resp.data.message) {
						$out.text(resp.data.message).addClass('is-error').removeAttr('hidden');
					} else {
						$out
							.text((cfg.i18n && cfg.i18n.publishNeed) || 'Export not ready')
							.addClass('is-error')
							.removeAttr('hidden');
					}
				})
				.fail(function (xhr) {
					var msg =
						xhr &&
						xhr.responseJSON &&
						xhr.responseJSON.data &&
						xhr.responseJSON.data.message
							? xhr.responseJSON.data.message
							: 'Publish failed';
					$out.text(msg).addClass('is-error').removeAttr('hidden');
				})
				.always(function () {
					$btn.prop('disabled', !cfg.hasToken);
				});
		});
	});
})(jQuery);
