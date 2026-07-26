(function ($) {
	'use strict';

	function i18n(key, fallback) {
		return (window.thwAiAssist && thwAiAssist.i18n && thwAiAssist.i18n[key]) || fallback;
	}

	function setStatus(msg, isError) {
		$('#thw-ai-status').html('<p class="' + (isError ? 'error' : 'updated') + '">' + msg + '</p>');
	}

	$('#thw-ai-draft-context').on('click', function () {
		var postId = $(this).data('post-id');
		setStatus(i18n('draftingContext', 'Drafting context…'));

		$.post(thwAiAssist.ajaxUrl, {
			action: 'thw_ai_draft_context',
			nonce: thwAiAssist.nonce,
			post_id: postId
		}).done(function (res) {
			if (res.success && res.data.content) {
				if (typeof tinymce !== 'undefined' && tinymce.get('thw_historical_context')) {
					tinymce.get('thw_historical_context').setContent(res.data.content);
				} else {
					$('#thw_historical_context').val(res.data.content);
				}
				setStatus(i18n('contextDrafted', 'Historical context drafted. Review and edit before saving.'));
			} else {
				setStatus(res.data && res.data.message ? res.data.message : i18n('requestFailed', 'Request failed.'), true);
			}
		}).fail(function () {
			setStatus(i18n('requestFailed', 'Request failed.'), true);
		});
	});

	$('#thw-ai-draft-questions').on('click', function () {
		var postId = $(this).data('post-id');
		setStatus(i18n('generatingQs', 'Generating questions…'));

		$.post(thwAiAssist.ajaxUrl, {
			action: 'thw_ai_draft_questions',
			nonce: thwAiAssist.nonce,
			post_id: postId
		}).done(function (res) {
			if (res.success && res.data.questions) {
				var $container = $('#thw-questions-repeater');
				var removeLabel = i18n('remove', 'Remove');
				$container.empty();
				res.data.questions.forEach(function (q) {
					$container.append(
						'<div class="thw-question-row">' +
						'<input type="text" name="thw_discussion_question[]" class="widefat" value="' + $('<div>').text(q).html() + '" />' +
						'<button type="button" class="button thw-remove-question">' + $('<div>').text(removeLabel).html() + '</button>' +
						'</div>'
					);
				});
				setStatus(i18n('questionsReady', 'Discussion questions generated. Review and edit before saving.'));
			} else {
				setStatus(res.data && res.data.message ? res.data.message : i18n('requestFailed', 'Request failed.'), true);
			}
		}).fail(function () {
			setStatus(i18n('requestFailed', 'Request failed.'), true);
		});
	});

	$('#thw-ai-translate-content').on('click', function () {
		var postId = $(this).data('post-id');
		var locale = $('#thw-ai-translate-locale').val();
		setStatus(i18n('translating', 'Translating content…'));

		$.post(thwAiAssist.ajaxUrl, {
			action: 'thw_ai_translate_content',
			nonce: thwAiAssist.nonce,
			post_id: postId,
			locale: locale
		}).done(function (res) {
			if (res.success) {
				var template = i18n('translationSaved', 'Translation saved for locale: %s.');
				setStatus(template.replace('%s', locale));
			} else {
				setStatus(res.data && res.data.message ? res.data.message : i18n('requestFailed', 'Request failed.'), true);
			}
		}).fail(function () {
			setStatus(i18n('requestFailed', 'Request failed.'), true);
		});
	});
}(jQuery));
