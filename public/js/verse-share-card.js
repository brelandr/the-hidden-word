/**
 * Client-side verse share card (canvas → PNG download / Web Share).
 */
(function () {
	'use strict';

	function brandColors() {
		var cfg = window.hwblVerseShare || {};
		return {
			primary: cfg.primaryColor || '#1a365d',
			secondary: cfg.secondaryColor || '#c9a227',
			siteName: cfg.siteName || document.title || 'The Hidden Word',
		};
	}

	function wrapText(ctx, text, x, y, maxWidth, lineHeight) {
		var words = String(text || '').split(/\s+/);
		var line = '';
		var lines = [];
		for (var i = 0; i < words.length; i++) {
			var test = line ? line + ' ' + words[i] : words[i];
			if (ctx.measureText(test).width > maxWidth && line) {
				lines.push(line);
				line = words[i];
			} else {
				line = test;
			}
		}
		if (line) {
			lines.push(line);
		}
		lines.forEach(function (l, idx) {
			ctx.fillText(l, x, y + idx * lineHeight);
		});
		return lines.length;
	}

	function renderCard(verseText, reference) {
		var colors = brandColors();
		var canvas = document.createElement('canvas');
		var w = 1080;
		var h = 1350;
		canvas.width = w;
		canvas.height = h;
		var ctx = canvas.getContext('2d');

		var grad = ctx.createLinearGradient(0, 0, w, h);
		grad.addColorStop(0, colors.primary);
		grad.addColorStop(1, '#0f172a');
		ctx.fillStyle = grad;
		ctx.fillRect(0, 0, w, h);

		ctx.fillStyle = colors.secondary;
		ctx.fillRect(72, 120, 120, 8);

		ctx.fillStyle = '#ffffff';
		ctx.font = '600 42px Georgia, "Times New Roman", serif';
		ctx.fillText(colors.siteName, 72, 100);

		ctx.font = 'italic 54px Georgia, "Times New Roman", serif';
		var lineCount = wrapText(ctx, '“' + verseText + '”', 72, 280, w - 144, 72);

		ctx.font = '600 40px Georgia, "Times New Roman", serif';
		ctx.fillStyle = colors.secondary;
		ctx.fillText(reference || '', 72, 280 + lineCount * 72 + 64);

		ctx.fillStyle = 'rgba(255,255,255,0.55)';
		ctx.font = '28px system-ui, sans-serif';
		ctx.fillText('thehiddenword.org', 72, h - 80);

		return canvas;
	}

	function downloadPng(canvas, filename) {
		canvas.toBlob(function (blob) {
			if (!blob) {
				return;
			}
			var url = URL.createObjectURL(blob);
			var a = document.createElement('a');
			a.href = url;
			a.download = filename || 'verse-card.png';
			document.body.appendChild(a);
			a.click();
			a.remove();
			URL.revokeObjectURL(url);
		}, 'image/png');
	}

	function shareOrDownload(verseText, reference) {
		var canvas = renderCard(verseText, reference);
		if (navigator.share && navigator.canShare) {
			canvas.toBlob(function (blob) {
				if (!blob) {
					downloadPng(canvas, 'verse-card.png');
					return;
				}
				var file = new File([blob], 'verse-card.png', { type: 'image/png' });
				var data = { files: [file], title: reference || 'Verse', text: verseText };
				if (navigator.canShare(data)) {
					navigator.share(data).catch(function () {
						downloadPng(canvas, 'verse-card.png');
					});
				} else {
					downloadPng(canvas, 'verse-card.png');
				}
			}, 'image/png');
			return;
		}
		downloadPng(canvas, 'verse-card.png');
	}

	function onClick(e) {
		var btn = e.target.closest('[data-hwbl-share-verse]');
		if (!btn) {
			return;
		}
		e.preventDefault();
		var text = btn.getAttribute('data-verse') || '';
		var ref = btn.getAttribute('data-ref') || '';
		if (!text) {
			return;
		}
		shareOrDownload(text, ref);
	}

	document.addEventListener('click', onClick);

	window.hwblShareVerseCard = shareOrDownload;
})();
