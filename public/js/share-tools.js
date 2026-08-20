/**
 * Draw QR codes for [hwbl_share_tools] canvases.
 */
(function () {
	'use strict';

	function drawQr(canvas, text) {
		if (!canvas || typeof qrcode !== 'function') {
			return;
		}
		try {
			var qr = qrcode(0, 'M');
			qr.addData(String(text || ''));
			qr.make();
			var count = qr.getModuleCount();
			var size = canvas.width || 160;
			canvas.width = size;
			canvas.height = size;
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
			/* ignore */
		}
	}

	function init() {
		document.querySelectorAll('canvas[data-hwbl-qr]').forEach(function (el) {
			drawQr(el, el.getAttribute('data-hwbl-qr'));
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}

	window.hwblDrawQr = drawQr;
})();
