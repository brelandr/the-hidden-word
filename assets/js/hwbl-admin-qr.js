/**
 * Render local QR codes for companion join links (no third-party network call).
 */
(function () {
	'use strict';

	function render(el) {
		if (typeof qrcode !== 'function') {
			return;
		}
		var data = el.getAttribute('data-hwbl-qr') || '';
		if (!data) {
			return;
		}
		var size = parseInt(el.getAttribute('data-hwbl-qr-size') || '4', 10);
		if (!size || size < 1) {
			size = 4;
		}
		try {
			var qr = qrcode(0, 'M');
			qr.addData(data);
			qr.make();
			el.innerHTML = qr.createImgTag(size, 8);
			var img = el.querySelector('img');
			if (img) {
				img.alt = el.getAttribute('data-hwbl-qr-alt') || 'QR code';
				img.width = 220;
				img.height = 220;
				img.style.border = '1px solid #ccd0d4';
				img.style.background = '#fff';
				img.style.padding = '8px';
				img.style.display = 'block';
			}
		} catch (err) {
			el.textContent = data;
		}
	}

	function init() {
		var nodes = document.querySelectorAll('[data-hwbl-qr]');
		for (var i = 0; i < nodes.length; i++) {
			render(nodes[i]);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
