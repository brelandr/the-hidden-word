(function ($) {
	'use strict';

	var cfg = window.thwExplainPreload || {};
	var pollTimer = null;

	function post(action, data) {
		data = data || {};
		data.action = action;
		data.nonce = cfg.nonce;
		return $.post(cfg.ajaxUrl, data);
	}

	function statusLabel(status) {
		var map = {
			idle: (cfg.i18n && cfg.i18n.idle) || 'Idle',
			running: (cfg.i18n && cfg.i18n.running) || 'Running',
			paused: (cfg.i18n && cfg.i18n.paused) || 'Paused',
			done: (cfg.i18n && cfg.i18n.done) || 'Done',
			error: (cfg.i18n && cfg.i18n.error) || 'Error'
		};
		return map[status] || status;
	}

	function countsText(job) {
		var s = (job && job.stats) || {};
		var text =
			(s.processed || 0) +
			' / ' +
			(s.total || 0) +
			' processed · ' +
			(s.generated || 0) +
			' generated · ' +
			(s.skipped || 0) +
			' skipped · ' +
			(s.errors || 0) +
			' errors';
		if (s.queued) {
			text += ' · ' + s.queued + ' queued for OpenAI';
		}
		return text;
	}

	function configText(job) {
		if (!job || !job.translations || !job.translations.length) {
			return 'No active job configuration.';
		}
		var mode =
			job.mode === 'openai_batch'
				? (cfg.i18n && cfg.i18n.openai_batch) || 'OpenAI Batch API'
				: (cfg.i18n && cfg.i18n.realtime) || 'Realtime API';
		var text =
			'Bibles: ' +
			job.translations.join(', ') +
			' · Traditions: ' +
			(job.traditions || []).join(', ') +
			' · Scope: ' +
			(job.scopes || []).join(', ') +
			' · ' +
			mode;
		if (job.openai && job.openai.phase) {
			text += ' · Batch phase: ' + job.openai.phase;
			if (job.openai.batch_status) {
				text += ' (' + job.openai.batch_status + ')';
			}
		}
		return text;
	}

	function pollDelay(job) {
		if (
			job &&
			job.mode === 'openai_batch' &&
			job.openai &&
			job.openai.phase === 'submitted'
		) {
			return 15000;
		}
		return 1200;
	}

	function applyJob(job) {
		if (!job) {
			return;
		}
		var status = job.status || 'idle';
		var $box = $('#thw-explain-preload-status');
		$box.attr('data-status', status);
		$box.find('.thw-preload-status-label').text(statusLabel(status));
		$box.find('.thw-preload-counts').text(countsText(job));
		$box.find('progress').val(job.percent || 0);
		$box.find('.thw-preload-percent').text(String(job.percent || 0) + '%');
		$box.find('.thw-preload-last-ref').text(job.last_reference || '—');
		$box.find('.thw-preload-config-text').text(configText(job));

		var $err = $box.find('.thw-preload-error');
		if (job.last_error) {
			$err.text(job.last_error).removeAttr('hidden');
		} else {
			$err.text('').attr('hidden', 'hidden');
		}

		var running = status === 'running';
		$('#thw-explain-preload-start').prop('disabled', running);
		$('#thw-explain-preload-pause').prop('disabled', !running);
		$('#thw-explain-preload-resume').prop('disabled', status !== 'paused');

		if (job.mode) {
			$('#thw-explain-preload-form input[name="mode"][value="' + job.mode + '"]').prop(
				'checked',
				true
			);
		}

		if (running) {
			schedulePoll(job);
		} else {
			clearPoll();
		}
	}

	function schedulePoll(job) {
		clearPoll();
		pollTimer = window.setTimeout(function () {
			post('thw_explain_preload_process', {})
				.done(function (resp) {
					if (resp && resp.success && resp.data && resp.data.job) {
						applyJob(resp.data.job);
					} else {
						schedulePoll(job);
					}
				})
				.fail(function () {
					schedulePoll(job);
				});
		}, pollDelay(job));
	}

	function clearPoll() {
		if (pollTimer) {
			window.clearTimeout(pollTimer);
			pollTimer = null;
		}
	}

	function selected(name) {
		var values = [];
		$('#thw-explain-preload-form input[name="' + name + '"]:checked').each(function () {
			values.push($(this).val());
		});
		return values;
	}

	function selectedMode() {
		return (
			$('#thw-explain-preload-form input[name="mode"]:checked').val() || 'realtime'
		);
	}

	$(function () {
		if (cfg.job) {
			applyJob(cfg.job);
		}

		$('#thw-explain-preload-start').on('click', function () {
			var translations = selected('translations[]');
			var traditions = selected('traditions[]');
			var scopes = selected('scopes[]');

			if (!translations.length) {
				window.alert((cfg.i18n && cfg.i18n.selectBible) || 'Select a Bible');
				return;
			}
			if (!traditions.length) {
				window.alert((cfg.i18n && cfg.i18n.selectTradition) || 'Select a tradition');
				return;
			}
			if (!scopes.length) {
				scopes = ['verse'];
			}

			post('thw_explain_preload_start', {
				translations: translations,
				traditions: traditions,
				scopes: scopes,
				mode: selectedMode(),
				continue_job: $('#thw-explain-preload-form input[name="continue_job"]').is(
					':checked'
				)
					? 1
					: 0
			}).done(function (resp) {
				if (resp && resp.success && resp.data && resp.data.job) {
					applyJob(resp.data.job);
				} else if (resp && resp.data && resp.data.message) {
					window.alert(resp.data.message);
				}
			});
		});

		$('#thw-explain-preload-pause').on('click', function () {
			post('thw_explain_preload_pause', {}).done(function (resp) {
				if (resp && resp.success && resp.data && resp.data.job) {
					applyJob(resp.data.job);
				}
			});
		});

		$('#thw-explain-preload-resume').on('click', function () {
			post('thw_explain_preload_resume', {
				mode: selectedMode()
			}).done(function (resp) {
				if (resp && resp.success && resp.data && resp.data.job) {
					applyJob(resp.data.job);
				} else if (resp && resp.data && resp.data.message) {
					window.alert(resp.data.message);
				}
			});
		});

		$('#thw-explain-preload-clear').on('click', function () {
			if (
				!window.confirm(
					(cfg.i18n && cfg.i18n.confirmClear) || 'Clear job status?'
				)
			) {
				return;
			}
			post('thw_explain_preload_clear', {}).done(function (resp) {
				if (resp && resp.success && resp.data && resp.data.job) {
					applyJob(resp.data.job);
				}
			});
		});
	});
})(jQuery);
