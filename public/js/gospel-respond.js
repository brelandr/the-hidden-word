/**
 * Gospel page response form + QR render.
 */
(function () {
	'use strict';

	var cfg = window.hwblGospelRespond || {};

	function drawQr(canvas, text) {
		if (!canvas || typeof qrcode !== 'function') {
			return;
		}
		try {
			var qr = qrcode(0, 'M');
			qr.addData(String(text || ''));
			qr.make();
			var count = qr.getModuleCount();
			var size = canvas.width;
			var cell = Math.floor(size / count);
			var offset = Math.floor((size - cell * count) / 2);
			var ctx = canvas.getContext('2d');
			ctx.fillStyle = '#ffffff';
			ctx.fillRect(0, 0, size, size);
			ctx.fillStyle = '#111111';
			for (var r = 0; r < count; r++) {
				for (var c = 0; c < count; c++) {
					if (qr.isDark(r, c)) {
						ctx.fillRect(offset + c * cell, offset + r * cell, cell, cell);
					}
				}
			}
		} catch (e) {
			/* ignore QR failures */
		}
	}

	function churchesHtml(list) {
		if (!list || !list.length) {
			return '';
		}
		var html = '<h3>Nearby churches</h3>';
		list.forEach(function (ch) {
			var name = ch.name || 'Church';
			var place = [ch.city, ch.state].filter(Boolean).join(', ');
			var site = ch.siteUrl || ch.site_url || '';
			html += '<div class="church"><strong>' + escapeHtml(name) + '</strong>';
			if (place) {
				html += '<br/>' + escapeHtml(place);
			}
			if (site) {
				html += '<br/><a href="' + escapeAttr(site) + '" target="_blank" rel="noopener noreferrer">Connect</a>';
			}
			html += '</div>';
		});
		return html;
	}

	function escapeHtml(s) {
		return String(s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function escapeAttr(s) {
		return escapeHtml(s).replace(/'/g, '&#39;');
	}

	function hasConsent() {
		var el = document.getElementById('hwbl-gospel-consent');
		return !!(el && el.checked);
	}

	function submitChoice(choice) {
		var status = document.getElementById('hwbl-gospel-status');
		var thanks = document.getElementById('hwbl-gospel-thanks');
		var emailEl = document.getElementById('hwbl-gospel-email');
		var locEl = document.getElementById('hwbl-gospel-location');
		if (!hasConsent()) {
			if (status) {
				status.textContent =
					cfg.consentRequired || 'Please check the consent box before submitting.';
			}
			return;
		}
		if (status) {
			status.textContent = 'Sending…';
		}
		fetch(cfg.restUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({
				choice: choice,
				email: emailEl ? emailEl.value : '',
				location: locEl ? locEl.value : '',
				consent: true,
			}),
		})
			.then(function (res) {
				return res.json().then(function (data) {
					return { ok: res.ok, data: data };
				});
			})
			.then(function (result) {
				if (!result.ok) {
					throw new Error((result.data && result.data.message) || 'Request failed');
				}
				var data = result.data || {};
				if (status) {
					status.textContent = '';
				}
				if (thanks) {
					var msg = data.message || 'Thank you.';
					var extra = data.email
						? '<p>We will email next steps to the address you provided.</p>'
						: '';
					thanks.innerHTML =
						'<h3>' +
						escapeHtml(msg) +
						'</h3>' +
						extra +
						churchesHtml(data.churches || []);
					thanks.style.display = 'block';
				}
				var form = document.getElementById('hwbl-gospel-response');
				if (form) {
					var choices = form.querySelector('.choices');
					if (choices) {
						choices.style.display = 'none';
					}
				}
			})
			.catch(function (err) {
				if (status) {
					status.textContent = err.message || 'Something went wrong. Please try again.';
				}
			});
	}

	document.addEventListener('DOMContentLoaded', function () {
		drawQr(document.getElementById('hwbl-qr'), cfg.shareUrl || window.location.href);
		document.querySelectorAll('.choice[data-choice]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				submitChoice(btn.getAttribute('data-choice'));
			});
		});
	});
})();
