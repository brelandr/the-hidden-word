(function () {
	'use strict';

	var cfg = window.hwblAiAssistant || {};
	var mode = 'study';

	function thread(root) {
		return root.querySelector('.hwbl-ai-assistant__thread');
	}

	function appendMessage(root, role, html) {
		var box = thread(root);
		if (!box) {
			return;
		}
		var item = document.createElement('div');
		item.className = 'hwbl-ai-assistant__message hwbl-ai-assistant__message--' + role;
		item.innerHTML = html;
		box.appendChild(item);
		box.scrollTop = box.scrollHeight;
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-hwbl-ai-assistant]').forEach(function (root) {
			root.querySelectorAll('.hwbl-ai-assistant__modes [data-mode]').forEach(function (btn) {
				btn.addEventListener('click', function () {
					mode = btn.getAttribute('data-mode') || 'study';
					root.querySelectorAll('.hwbl-ai-assistant__modes [data-mode]').forEach(function (other) {
						var active = other === btn;
						other.classList.toggle('is-active', active);
						other.setAttribute('aria-selected', active ? 'true' : 'false');
					});
				});
			});

			var form = root.querySelector('.hwbl-ai-assistant__form');
			if (!form) {
				return;
			}

			form.addEventListener('submit', function (ev) {
				ev.preventDefault();
				var input = root.querySelector('.hwbl-ai-assistant__input');
				if (!input || !input.value.trim() || !cfg.restUrl) {
					return;
				}

				var message = input.value.trim();
				appendMessage(root, 'user', '<p>' + message.replace(/</g, '&lt;') + '</p>');
				input.value = '';
				appendMessage(root, 'assistant', '<p>' + (cfg.i18n && cfg.i18n.thinking ? cfg.i18n.thinking : 'Thinking…') + '</p>');

				fetch(cfg.restUrl + 'ai/assistant', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': cfg.nonce || ''
					},
					body: JSON.stringify({ message: message, mode: mode })
				}).then(function (res) {
					return res.ok ? res.json() : null;
				}).then(function (data) {
					var box = thread(root);
					if (!box || !box.lastChild) {
						return;
					}
					box.removeChild(box.lastChild);
					var html = (data && data.response) ? data.response : '<p>No response.</p>';
					appendMessage(root, 'assistant', html);
				});
			});
		});
	});
})();
