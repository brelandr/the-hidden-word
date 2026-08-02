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

		function selectedFillMode() {
			return (
				$('input[name="thw_explain_pack_fill_mode"]:checked').val() || 'realtime'
			);
		}

		function setFillSelection(translation, tradition, scope, missing, samples) {
			$('#thw-explain-pack-fill-translation').val(translation);
			$('#thw-explain-pack-fill-tradition').val(tradition);
			$('#thw-explain-pack-fill-scope').val(scope);
			$('#thw-explain-pack-fill-missing').val(String(missing || 0));
			var label =
				translation.toUpperCase() +
				' · ' +
				tradition +
				' · ' +
				scope +
				' · ' +
				String(missing || 0) +
				' missing';
			$('#thw-explain-pack-fill-selection-label').text(label);
			var $samples = $('#thw-explain-pack-fill-samples');
			if (samples && samples.length) {
				$samples
					.text(
						(
							(cfg.i18n && cfg.i18n.fillGapsSamples) ||
							'First missing: %s'
						).replace('%s', samples.join(', '))
					)
					.prop('hidden', false);
			} else {
				$samples.text('').prop('hidden', true);
			}
			$('#thw-explain-pack-fill-start').prop('disabled', !translation || !tradition);
			$('#thw-explain-pack-fill-panel').addClass('is-armed');
		}

		function startFillGaps() {
			var translation = String($('#thw-explain-pack-fill-translation').val() || '');
			var tradition = String($('#thw-explain-pack-fill-tradition').val() || '');
			var scope = String($('#thw-explain-pack-fill-scope').val() || 'verse');
			var missing = parseInt($('#thw-explain-pack-fill-missing').val(), 10) || 0;
			var mode = selectedFillMode();
			var $start = $('#thw-explain-pack-fill-start');

			if (!translation || !tradition) {
				window.alert(
					(cfg.i18n && cfg.i18n.fillGapsNeedSelection) ||
						'Select gaps on a row first'
				);
				return;
			}

			var msg = (
				(cfg.i18n && cfg.i18n.fillGapsConfirm) ||
				'Generate %1$s missing %2$s · %3$s · %4$s explains?'
			)
				.replace('%1$s', String(missing))
				.replace('%2$s', translation.toUpperCase())
				.replace('%3$s', tradition)
				.replace('%4$s', scope);
			var samplesText = $('#thw-explain-pack-fill-samples').text();
			if (samplesText) {
				msg += '\n\n' + samplesText;
			}
			if (!window.confirm(msg)) {
				return;
			}

			$start.prop('disabled', true);
			post('thw_explain_pack_fill_gaps', {
				translation: translation,
				tradition: tradition,
				scope: scope,
				mode: mode
			})
				.always(function () {
					$start.prop('disabled', false);
				})
				.done(function (resp) {
					if (resp && resp.success && resp.data) {
						var url = resp.data.preloadUrl || cfg.preloadUrl || '';
						if (url) {
							window.location.href = url;
							return;
						}
					}
					window.alert(
						(resp && resp.data && resp.data.message) ||
							((cfg.i18n && cfg.i18n.fillGapsBusy) ||
								'Could not start fill gaps')
					);
				})
				.fail(function (xhr) {
					var msgFail =
						(xhr &&
							xhr.responseJSON &&
							xhr.responseJSON.data &&
							xhr.responseJSON.data.message) ||
						((cfg.i18n && cfg.i18n.fillGapsBusy) ||
							'Could not start fill gaps');
					window.alert(msgFail);
				});
		}

		$(document).on('click', '.thw-explain-pack-inventory-row', function (e) {
			if ($(e.target).closest('.thw-explain-pack-select-gaps').length) {
				return;
			}
			var $row = $(this);
			var translation = String($row.data('translation') || '');
			var tradition = String($row.data('tradition') || '');
			var scope = String($row.data('scope') || 'verse');
			if (!translation || !tradition) {
				return;
			}
			$('.thw-explain-pack-inventory-row').removeClass('is-selected');
			$row.addClass('is-selected');
			$('#thw-explain-pack-export-translation').val(translation);
			$('#thw-explain-pack-export-tradition').val(tradition);
			$('input[name="export_scopes[]"]').prop('checked', false);
			$('input[name="export_scopes[]"][value="' + scope + '"]').prop('checked', true);
			$('#thw-explain-pack-remove-translation').val(translation);
			$('#thw-explain-pack-remove-tradition').val(tradition);
		});

		$(document).on('click', '.thw-explain-pack-select-gaps', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var $btn = $(this);
			var translation = String($btn.data('translation') || '');
			var tradition = String($btn.data('tradition') || '');
			var scope = String($btn.data('scope') || 'verse');
			var missing = parseInt($btn.data('missing'), 10) || 0;
			if (!translation || !tradition) {
				return;
			}

			$('.thw-explain-pack-inventory-row').removeClass('is-selected');
			$btn.closest('tr').addClass('is-selected');

			$btn.prop('disabled', true);
			post('thw_explain_pack_gap_preview', {
				translation: translation,
				tradition: tradition,
				scope: scope
			})
				.always(function () {
					$btn.prop('disabled', false);
				})
				.done(function (preview) {
					var samples = [];
					var count = missing;
					if (preview && preview.success && preview.data) {
						count = preview.data.missing || missing;
						samples = preview.data.samples || [];
					}
					if (!count) {
						window.alert((cfg.i18n && cfg.i18n.noGaps) || 'No gaps');
						return;
					}
					setFillSelection(translation, tradition, scope, count, samples);
					var panel = document.getElementById('thw-explain-pack-fill-panel');
					if (panel && panel.scrollIntoView) {
						panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
					}
				});
		});

		$('#thw-explain-pack-fill-start').on('click', function () {
			startFillGaps();
		});

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
