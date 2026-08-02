/**
 * Bible place maps (Leaflet or Mapbox GL) powered by OpenBible data.
 */
(function () {
	'use strict';

	function cfg() {
		return window.hwblBibleMap || {};
	}

	function escapeHtml(value) {
		return String(value == null ? '' : value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function formatVersesLabel(place, maxRefs) {
		var verses = place && Array.isArray(place.verses) ? place.verses : [];
		if (!verses.length) {
			return '';
		}
		var limit = typeof maxRefs === 'number' ? maxRefs : 8;
		if (verses.length <= limit) {
			return verses.join('; ');
		}
		var shown = verses.slice(0, limit).join('; ');
		return shown + '; +' + (verses.length - limit) + ' more';
	}

	function placePopupHtml(place) {
		var parts = ['<strong>' + escapeHtml(place.name || '') + '</strong>'];
		if (place.modern_name) {
			parts.push('<br>' + escapeHtml(place.modern_name));
		}
		var verseLabel = formatVersesLabel(place, 10);
		if (verseLabel) {
			parts.push(
				'<br><span class="hwbl-bible-map__popup-verses">' +
					escapeHtml(verseLabel) +
					'</span>'
			);
		}
		if (place.country) {
			parts.push('<br>' + escapeHtml(place.country));
		}
		if (place.confidence) {
			parts.push(
				'<br><em>' +
					escapeHtml(place.confidence) +
					' confidence</em>'
			);
		}
		return parts.join('');
	}

	/**
	 * Two-line label: biblical name + modern location (when different).
	 *
	 * @param {object} place
	 * @returns {string}
	 */
	function formatPlaceLabel(place) {
		var name = String((place && place.name) || '').trim();
		var modern = String((place && place.modern_name) || '').trim();
		if (!name) {
			return modern;
		}
		if (!modern || modern.toLowerCase() === name.toLowerCase()) {
			return name;
		}
		return name + '\n' + modern + ' (today)';
	}

	/**
	 * HTML for Leaflet tooltip / DivIcon labels.
	 *
	 * @param {object} place
	 * @returns {string}
	 */
	function formatPlaceLabelHtml(place) {
		var name = String((place && place.name) || '').trim();
		var modern = String((place && place.modern_name) || '').trim();
		var html = '<span class="hwbl-bible-map__label-biblical">' + escapeHtml(name) + '</span>';
		if (modern && modern.toLowerCase() !== name.toLowerCase()) {
			html +=
				'<span class="hwbl-bible-map__label-modern">' +
				escapeHtml(modern) +
				' (today)</span>';
		}
		return html;
	}

	/**
	 * Build a GeoJSON FeatureCollection from place hits.
	 *
	 * @param {Array<object>} places
	 * @returns {object}
	 */
	function placesToGeoJSON(places) {
		var features = [];
		(places || []).forEach(function (place, index) {
			if (place.lat == null || place.lng == null) {
				return;
			}
			features.push({
				type: 'Feature',
				id: place.id || index,
				geometry: {
					type: 'Point',
					coordinates: [place.lng, place.lat],
				},
				properties: {
					name: place.name || '',
					modern_name: place.modern_name || '',
					label: formatPlaceLabel(place),
					confidence: place.confidence || '',
				},
			});
		});
		return {
			type: 'FeatureCollection',
			features: features,
		};
	}

	var MAPBOX_SOURCE_ID = 'hwbl-biblical-places';
	var MAPBOX_LABEL_LAYER_ID = 'hwbl-biblical-place-labels';

	function fetchPlaces(bookId, chapter, verse, scope) {
		var conf = cfg();
		var base = (conf.restUrl || '').replace(/\?$/, '');
		var mapScope = scope === 'book' ? 'book' : 'passage';
		var url =
			base +
			(base.indexOf('?') === -1 ? '?' : '&') +
			'book_id=' +
			encodeURIComponent(bookId) +
			'&chapter=' +
			encodeURIComponent(chapter || 0) +
			'&verse=' +
			encodeURIComponent(verse || 0) +
			'&scope=' +
			encodeURIComponent(mapScope);

		return fetch(url, {
			headers: conf.nonce ? { 'X-WP-Nonce': conf.nonce } : {},
		}).then(function (res) {
			return res.json().then(function (body) {
				if (!res.ok) {
					var err = new Error(
						(body && body.message) || 'Could not load places.'
					);
					err.code = body && body.code;
					throw err;
				}
				return body;
			});
		});
	}

	function destroyMap(widget) {
		if (widget._hwblMapMarkers && widget._hwblMapMarkers.length) {
			widget._hwblMapMarkers.forEach(function (marker) {
				if (marker && typeof marker.remove === 'function') {
					marker.remove();
				}
			});
		}
		if (widget._hwblMap && typeof widget._hwblMap.remove === 'function') {
			widget._hwblMap.remove();
		}
		widget._hwblMap = null;
		widget._hwblMapMarkers = null;
		widget._hwblMapPlaces = null;
		var styleWrap = widget.querySelector('.hwbl-bible-map__style-bar');
		if (styleWrap) {
			styleWrap.parentNode.removeChild(styleWrap);
		}
	}

	function scopeStorageKey() {
		return 'hwbl_bible_map_scope';
	}

	function normalizeScope(value) {
		return value === 'book' ? 'book' : 'passage';
	}

	function readStoredScope(fallback) {
		try {
			var stored = window.sessionStorage.getItem(scopeStorageKey());
			if (stored === 'book' || stored === 'passage') {
				return stored;
			}
		} catch (e) {
			// Ignore quota / private mode.
		}
		return normalizeScope(fallback);
	}

	function writeStoredScope(scope) {
		try {
			window.sessionStorage.setItem(scopeStorageKey(), normalizeScope(scope));
		} catch (e) {
			// Ignore quota / private mode.
		}
	}

	function resolveWidgetScope(widget) {
		var attr = widget.getAttribute('data-scope') || 'passage';
		// Prefer session choice so reader chapter changes keep Whole book.
		return readStoredScope(attr === 'book' ? 'book' : 'passage');
	}

	function ensureScopeBar(widget) {
		var existing = widget.querySelector('.hwbl-bible-map__scope-bar');
		if (existing) {
			return existing.querySelector('.hwbl-bible-map__scope-select');
		}
		var canvas = widget.querySelector('.hwbl-bible-map__canvas');
		if (!canvas || !canvas.parentNode) {
			return null;
		}
		var selected = resolveWidgetScope(widget);
		widget.setAttribute('data-scope', selected);

		var bar = document.createElement('div');
		bar.className = 'hwbl-bible-map__scope-bar';
		var label = document.createElement('label');
		label.className = 'hwbl-bible-map__scope-label';
		label.textContent = 'Show places';
		var select = document.createElement('select');
		select.className = 'hwbl-bible-map__scope-select';
		select.setAttribute('aria-label', 'Show places for');
		;[
			{ value: 'passage', label: 'This passage' },
			{ value: 'book', label: 'Whole book' },
		].forEach(function (item) {
			var opt = document.createElement('option');
			opt.value = item.value;
			opt.textContent = item.label;
			if (item.value === selected) {
				opt.selected = true;
			}
			select.appendChild(opt);
		});
		label.appendChild(select);
		bar.appendChild(label);
		canvas.parentNode.insertBefore(bar, canvas);

		select.addEventListener('change', function () {
			var next = normalizeScope(select.value);
			widget.setAttribute('data-scope', next);
			writeStoredScope(next);
			if (typeof widget._hwblReloadPlaces === 'function') {
				widget._hwblReloadPlaces();
			} else {
				loadWidget(widget);
			}
		});

		return select;
	}

	function styleStorageKey() {
		return 'hwbl_bible_map_style_key';
	}

	function readStoredStyleKey(fallback) {
		try {
			var stored = window.sessionStorage.getItem(styleStorageKey());
			return stored || fallback || 'outdoors';
		} catch (e) {
			return fallback || 'outdoors';
		}
	}

	function writeStoredStyleKey(key) {
		try {
			window.sessionStorage.setItem(styleStorageKey(), key);
		} catch (e) {
			// Ignore quota / private mode.
		}
	}

	function resolveStyleUrl(key, styles, fallbackUrl) {
		var list = styles || [];
		for (var i = 0; i < list.length; i++) {
			if (list[i].key === key && list[i].url) {
				return list[i].url;
			}
		}
		return fallbackUrl || 'mapbox://styles/mapbox/outdoors-v12';
	}

	function ensureStyleBar(widget, styles, selectedKey) {
		var existing = widget.querySelector('.hwbl-bible-map__style-bar');
		if (existing) {
			return existing.querySelector('.hwbl-bible-map__style-select');
		}
		if (!styles || !styles.length) {
			return null;
		}
		var canvas = widget.querySelector('.hwbl-bible-map__canvas');
		if (!canvas || !canvas.parentNode) {
			return null;
		}
		var bar = document.createElement('div');
		bar.className = 'hwbl-bible-map__style-bar';
		var label = document.createElement('label');
		label.className = 'hwbl-bible-map__style-label';
		label.textContent = 'Map style';
		var select = document.createElement('select');
		select.className = 'hwbl-bible-map__style-select';
		select.setAttribute('aria-label', 'Map style');
		styles.forEach(function (item) {
			var opt = document.createElement('option');
			opt.value = item.key;
			opt.textContent = item.label;
			if (item.key === selectedKey) {
				opt.selected = true;
			}
			select.appendChild(opt);
		});
		label.appendChild(select);
		bar.appendChild(label);
		canvas.parentNode.insertBefore(bar, canvas);
		return select;
	}

	function clearMapboxMarkers(widget) {
		if (!widget._hwblMapMarkers) {
			return;
		}
		widget._hwblMapMarkers.forEach(function (marker) {
			if (marker && typeof marker.remove === 'function') {
				marker.remove();
			}
		});
		widget._hwblMapMarkers = [];
	}

	function upsertMapboxLabelLayer(map, places) {
		var geojson = placesToGeoJSON(places);
		if (map.getSource(MAPBOX_SOURCE_ID)) {
			map.getSource(MAPBOX_SOURCE_ID).setData(geojson);
		} else {
			map.addSource(MAPBOX_SOURCE_ID, {
				type: 'geojson',
				data: geojson,
			});
		}

		if (map.getLayer(MAPBOX_LABEL_LAYER_ID)) {
			return;
		}

		map.addLayer({
			id: MAPBOX_LABEL_LAYER_ID,
			type: 'symbol',
			source: MAPBOX_SOURCE_ID,
			layout: {
				'text-field': ['get', 'label'],
				'text-font': ['DIN Pro Bold', 'Arial Unicode MS Bold'],
				'text-size': [
					'interpolate',
					['linear'],
					['zoom'],
					4,
					12,
					10,
					17,
				],
				'text-variable-anchor': ['top', 'bottom', 'left', 'right'],
				'text-radial-offset': 0.9,
				'text-justify': 'auto',
				'text-line-height': 1.15,
				'text-max-width': 10,
				'text-allow-overlap': false,
				'text-ignore-placement': false,
				'text-optional': true,
			},
			paint: {
				'text-color': '#b91c1c',
				'text-halo-color': '#fff8f0',
				'text-halo-width': 2,
			},
		});
	}

	function addMapboxMarkers(widget, map, places, fit) {
		clearMapboxMarkers(widget);
		widget._hwblMapMarkers = [];
		var bounds = new window.mapboxgl.LngLatBounds();
		var hasPoint = false;
		(places || []).forEach(function (place) {
			if (place.lat == null || place.lng == null) {
				return;
			}
			hasPoint = true;
			var popup = new window.mapboxgl.Popup({ offset: 16 }).setHTML(
				placePopupHtml(place)
			);
			var marker = new window.mapboxgl.Marker()
				.setLngLat([place.lng, place.lat])
				.setPopup(popup)
				.addTo(map);
			widget._hwblMapMarkers.push(marker);
			bounds.extend([place.lng, place.lat]);
		});

		try {
			upsertMapboxLabelLayer(map, places);
		} catch (err) {
			// Style may still be loading; caller retries on style.load.
		}

		if (fit && hasPoint) {
			if (places.length === 1) {
				map.jumpTo({ center: [places[0].lng, places[0].lat], zoom: 8 });
			} else {
				map.fitBounds(bounds, { padding: 40, maxZoom: 10 });
			}
		}
		return hasPoint;
	}

	function ensurePlacesDropdown(widget) {
		var list = widget.querySelector('.hwbl-bible-map__list');
		if (!list) {
			return null;
		}
		var wrap = list.closest('.hwbl-bible-map__places');
		if (wrap) {
			return wrap;
		}
		wrap = document.createElement('details');
		wrap.className = 'hwbl-bible-map__places';
		var summary = document.createElement('summary');
		summary.className = 'hwbl-bible-map__places-summary';
		summary.textContent = 'Places list';
		list.parentNode.insertBefore(wrap, list);
		wrap.appendChild(summary);
		wrap.appendChild(list);
		return wrap;
	}

	function renderList(widget, places) {
		var wrap = ensurePlacesDropdown(widget);
		var list = widget.querySelector('.hwbl-bible-map__list');
		if (!list) {
			return;
		}
		var summary = wrap
			? wrap.querySelector('.hwbl-bible-map__places-summary')
			: null;
		list.innerHTML = '';

		if (!places.length) {
			if (wrap) {
				wrap.hidden = true;
			}
			if (summary) {
				summary.textContent = 'Places list';
			}
			return;
		}

		if (wrap) {
			wrap.hidden = false;
		}
		if (summary) {
			summary.textContent =
				places.length === 1
					? '1 place'
					: places.length + ' places';
		}

		places.forEach(function (place) {
			var li = document.createElement('li');
			li.className = 'hwbl-bible-map__list-item';
			var nameEl = document.createElement('span');
			nameEl.className = 'hwbl-bible-map__list-name';
			var label = place.name || '';
			if (place.modern_name && place.modern_name !== place.name) {
				label += ' — ' + place.modern_name;
			}
			nameEl.textContent = label;
			li.appendChild(nameEl);

			var verseLabel = formatVersesLabel(place, 6);
			if (verseLabel) {
				var verseEl = document.createElement('span');
				verseEl.className = 'hwbl-bible-map__list-verses';
				verseEl.textContent = verseLabel;
				li.appendChild(verseEl);
			}

			var titleBits = [];
			if (place.verses && place.verses.length) {
				titleBits.push(place.verses.join('; '));
			}
			if (place.confidence) {
				titleBits.push(place.confidence + ' confidence');
			}
			li.title = titleBits.join(' · ');
			li.setAttribute('role', 'button');
			li.tabIndex = 0;
			li.addEventListener('click', function () {
				focusPlace(widget, place);
			});
			li.addEventListener('keydown', function (event) {
				if (event.key === 'Enter' || event.key === ' ') {
					event.preventDefault();
					focusPlace(widget, place);
				}
			});
			list.appendChild(li);
		});
	}

	function focusPlace(widget, place) {
		var map = widget._hwblMap;
		if (!map || place.lat == null || place.lng == null) {
			return;
		}
		var provider = cfg().provider || 'leaflet';
		if (provider === 'mapbox' && map.flyTo) {
			map.flyTo({ center: [place.lng, place.lat], zoom: 9 });
			return;
		}
		if (map.setView) {
			map.setView([place.lat, place.lng], 9);
		}
	}

	function initLeaflet(canvas, places) {
		if (typeof window.L === 'undefined') {
			throw new Error('Leaflet is not loaded.');
		}
		// Marker images live beside leaflet.css (vendored).
		if (window.L.Icon && window.L.Icon.Default) {
			var leafletCss = document.querySelector('link[href*="leaflet.css"]');
			var iconBase = leafletCss
				? leafletCss.href.replace(/leaflet\.css.*$/, '')
				: '';
			if (iconBase) {
				window.L.Icon.Default.mergeOptions({
					iconUrl: iconBase + 'marker-icon.png',
					iconRetinaUrl: iconBase + 'marker-icon-2x.png',
					shadowUrl: iconBase + 'marker-shadow.png',
				});
			}
		}
		var map = window.L.map(canvas, { scrollWheelZoom: false });
		window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 18,
			attribution:
				'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
		}).addTo(map);

		var bounds = [];
		var permanentLabels = (places || []).length <= 12;
		places.forEach(function (place) {
			if (place.lat == null || place.lng == null) {
				return;
			}
			var marker = window.L.marker([place.lat, place.lng]).addTo(map);
			marker.bindPopup(placePopupHtml(place));
			marker.bindTooltip(formatPlaceLabelHtml(place), {
				permanent: permanentLabels,
				direction: 'top',
				offset: [0, -8],
				opacity: 1,
				className: 'hwbl-bible-map__label',
				sticky: !permanentLabels,
			});
			bounds.push([place.lat, place.lng]);
		});

		if (bounds.length === 1) {
			map.setView(bounds[0], 8);
		} else if (bounds.length > 1) {
			map.fitBounds(bounds, { padding: [24, 24], maxZoom: 10 });
		} else {
			map.setView([31.7683, 35.2137], 6);
		}

		setTimeout(function () {
			map.invalidateSize();
		}, 50);

		return map;
	}

	function initMapbox(widget, canvas, places) {
		if (typeof window.mapboxgl === 'undefined') {
			throw new Error('Mapbox GL is not loaded.');
		}
		var conf = cfg();
		if (!conf.mapboxToken) {
			throw new Error('Mapbox access token is missing.');
		}
		window.mapboxgl.accessToken = conf.mapboxToken;

		var styles = conf.mapboxStyles || [];
		var selectedKey = readStoredStyleKey(conf.mapboxStyleKey || 'outdoors');
		var styleUrl = resolveStyleUrl(selectedKey, styles, conf.mapboxStyle);
		// If stored key is no longer available (e.g. custom removed), fall back.
		if (!styles.some(function (s) { return s.key === selectedKey; }) && styles.length) {
			selectedKey = conf.mapboxStyleKey || styles[0].key;
			styleUrl = resolveStyleUrl(selectedKey, styles, conf.mapboxStyle);
		}

		var select = ensureStyleBar(widget, styles, selectedKey);
		widget._hwblMapPlaces = places;

		var map = new window.mapboxgl.Map({
			container: canvas,
			style: styleUrl,
			center: [35.2137, 31.7683],
			zoom: 6,
			attributionControl: true,
		});
		map.addControl(new window.mapboxgl.NavigationControl(), 'top-right');

		map.on('load', function () {
			addMapboxMarkers(widget, map, places, true);
			map.resize();
		});

		if (select) {
			select.addEventListener('change', function () {
				var nextKey = select.value;
				var nextUrl = resolveStyleUrl(nextKey, styles, conf.mapboxStyle);
				writeStoredStyleKey(nextKey);
				map.setStyle(nextUrl);
				map.once('style.load', function () {
					addMapboxMarkers(widget, map, widget._hwblMapPlaces || places, true);
					map.resize();
				});
			});
		}

		return map;
	}

	function statusForPlaces(places, scope) {
		if (!places.length) {
			return scope === 'book'
				? 'No catalogued places for this book.'
				: 'No catalogued places for this passage.';
		}
		if (scope === 'book') {
			return places.length === 1
				? '1 place in this book'
				: places.length + ' places in this book';
		}
		return places.length === 1 ? '1 place' : places.length + ' places';
	}

	function renderMap(widget, places, scope) {
		var canvas = widget.querySelector('.hwbl-bible-map__canvas');
		var status = widget.querySelector('.hwbl-bible-map__status');
		if (!canvas) {
			return;
		}

		destroyMap(widget);
		canvas.innerHTML = '';
		ensureScopeBar(widget);

		if (!places.length) {
			if (status) {
				status.textContent = statusForPlaces(places, scope);
			}
			return;
		}

		if (status) {
			status.textContent = statusForPlaces(places, scope);
		}

		var provider = cfg().provider || 'leaflet';
		try {
			widget._hwblMap =
				provider === 'mapbox'
					? initMapbox(widget, canvas, places)
					: initLeaflet(canvas, places);
		} catch (err) {
			if (status) {
				status.textContent =
					(err && err.message) || 'Could not initialize the map.';
			}
		}
	}

	function loadWidget(widget) {
		var bookId = parseInt(widget.getAttribute('data-book') || '0', 10);
		var chapter = parseInt(widget.getAttribute('data-chapter') || '0', 10);
		var verse = parseInt(widget.getAttribute('data-verse') || '0', 10);
		var scope = resolveWidgetScope(widget);
		var status = widget.querySelector('.hwbl-bible-map__status');
		var attribution = widget.querySelector('.hwbl-bible-map__attribution');

		widget.setAttribute('data-scope', scope);
		ensureScopeBar(widget);
		var scopeSelect = widget.querySelector('.hwbl-bible-map__scope-select');
		if (scopeSelect && scopeSelect.value !== scope) {
			scopeSelect.value = scope;
		}

		if (!bookId || (scope !== 'book' && !chapter)) {
			if (status) {
				status.textContent =
					scope === 'book'
						? 'Choose a book.'
						: 'Choose a book and chapter.';
			}
			return Promise.resolve();
		}

		if (status) {
			status.textContent = 'Loading places…';
		}

		return fetchPlaces(bookId, chapter, verse, scope)
			.then(function (data) {
				if (attribution && data.attribution) {
					attribution.textContent = data.attribution;
				}
				var places = data.places || [];
				var resolvedScope = normalizeScope(data.scope || scope);
				renderList(widget, places);
				renderMap(widget, places, resolvedScope);
				return data;
			})
			.catch(function (err) {
				if (status) {
					status.textContent =
						(err && err.message) || 'Could not load places.';
				}
			});
	}

	function initWidget(widget) {
		if (!widget || widget._hwblBibleMapInit) {
			return;
		}
		widget._hwblBibleMapInit = true;
		widget._hwblReloadPlaces = function () {
			return loadWidget(widget);
		};
		loadWidget(widget);
	}

	/**
	 * Render places into a reader map panel (canvas + list + status).
	 *
	 * @param {HTMLElement} panel Panel root with map child nodes.
	 * @param {number} bookId
	 * @param {number} chapter
	 * @param {number} verse 0 = chapter.
	 * @returns {Promise}
	 */
	function renderIntoPanel(panel, bookId, chapter, verse) {
		if (!panel) {
			return Promise.resolve();
		}
		panel.setAttribute('data-book', String(bookId));
		panel.setAttribute('data-chapter', String(chapter));
		panel.setAttribute('data-verse', String(verse || 0));
		panel.classList.add('hwbl-bible-map');
		return loadWidget(panel);
	}

	window.hwblBibleMapApi = {
		initWidget: initWidget,
		loadWidget: loadWidget,
		renderIntoPanel: renderIntoPanel,
		fetchPlaces: fetchPlaces,
	};

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.hwbl-bible-map').forEach(initWidget);
	});
})();
