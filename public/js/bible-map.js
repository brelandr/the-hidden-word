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

	function placePopupHtml(place, stopIndex) {
		var parts = ['<strong>' + escapeHtml(place.name || '') + '</strong>'];
		if (typeof stopIndex === 'number' && stopIndex >= 0) {
			parts.unshift('<em>Stop ' + (stopIndex + 1) + '</em><br>');
		}
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
				'<br><em>' + escapeHtml(place.confidence) + ' confidence</em>'
			);
		}
		return parts.join('');
	}

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

	function formatPlaceLabelHtml(place) {
		var name = String((place && place.name) || '').trim();
		var modern = String((place && place.modern_name) || '').trim();
		var html =
			'<span class="hwbl-bible-map__label-biblical">' +
			escapeHtml(name) +
			'</span>';
		if (modern && modern.toLowerCase() !== name.toLowerCase()) {
			html +=
				'<span class="hwbl-bible-map__label-modern">' +
				escapeHtml(modern) +
				' (today)</span>';
		}
		return html;
	}

	function placesToGeoJSON(places, opts) {
		opts = opts || {};
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
					popup: placePopupHtml(place, opts.numbered ? index : undefined),
					stop: opts.numbered ? index + 1 : 0,
				},
			});
		});
		return {
			type: 'FeatureCollection',
			features: features,
		};
	}

	function sortPlacesForJourney(places) {
		return (places || [])
			.filter(function (p) {
				return p && p.lat != null && p.lng != null;
			})
			.slice()
			.sort(function (a, b) {
				var ka = String(a.first_key || '');
				var kb = String(b.first_key || '');
				if (ka && kb && ka !== kb) {
					return ka < kb ? -1 : 1;
				}
				if (ka && !kb) {
					return -1;
				}
				if (!ka && kb) {
					return 1;
				}
				return String(a.name || '').localeCompare(String(b.name || ''));
			});
	}

	var MAPBOX_SOURCE_ID = 'hwbl-biblical-places';
	var MAPBOX_CLUSTER_LAYER = 'hwbl-biblical-clusters';
	var MAPBOX_CLUSTER_COUNT = 'hwbl-biblical-cluster-count';
	var MAPBOX_UNCLUSTERED = 'hwbl-biblical-unclustered';
	var MAPBOX_LABEL_LAYER_ID = 'hwbl-biblical-place-labels';
	var MAPBOX_ROUTE_SOURCE = 'hwbl-biblical-route';
	var MAPBOX_ROUTE_LAYER = 'hwbl-biblical-route-line';

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
		stopJourneyPlayback(widget);
		if (widget._hwblMapMarkers && widget._hwblMapMarkers.length) {
			widget._hwblMapMarkers.forEach(function (marker) {
				if (marker && typeof marker.remove === 'function') {
					marker.remove();
				}
			});
		}
		if (widget._hwblClusterGroup && widget._hwblMap) {
			try {
				widget._hwblMap.removeLayer(widget._hwblClusterGroup);
			} catch (e) {
				// ignore
			}
		}
		if (widget._hwblMap && typeof widget._hwblMap.remove === 'function') {
			widget._hwblMap.remove();
		}
		widget._hwblMap = null;
		widget._hwblMapMarkers = null;
		widget._hwblClusterGroup = null;
		widget._hwblMapPlaces = null;
		widget._hwblJourneyStops = null;
		['.hwbl-bible-map__style-bar', '.hwbl-bible-map__journey-bar'].forEach(
			function (sel) {
				var el = widget.querySelector(sel);
				if (el && el.parentNode) {
					el.parentNode.removeChild(el);
				}
			}
		);
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
		return readStoredScope(attr === 'book' ? 'book' : 'passage');
	}

	function journeyStorageKey() {
		return 'hwbl_bible_map_journey';
	}

	function readJourneyEnabled() {
		try {
			return window.sessionStorage.getItem(journeyStorageKey()) === '1';
		} catch (e) {
			return false;
		}
	}

	function writeJourneyEnabled(on) {
		try {
			window.sessionStorage.setItem(journeyStorageKey(), on ? '1' : '0');
		} catch (e) {
			// ignore
		}
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

	function ensureJourneyBar(widget, places) {
		var canvas = widget.querySelector('.hwbl-bible-map__canvas');
		if (!canvas || !canvas.parentNode) {
			return null;
		}
		var existing = widget.querySelector('.hwbl-bible-map__journey-bar');
		if (existing) {
			existing.parentNode.removeChild(existing);
		}
		var stops = sortPlacesForJourney(places);
		if (stops.length < 2) {
			widget._hwblJourneyEnabled = false;
			return null;
		}

		var enabled = readJourneyEnabled();
		widget._hwblJourneyEnabled = enabled;

		var bar = document.createElement('div');
		bar.className = 'hwbl-bible-map__journey-bar';

		var toggleLabel = document.createElement('label');
		toggleLabel.className = 'hwbl-bible-map__journey-toggle';
		var checkbox = document.createElement('input');
		checkbox.type = 'checkbox';
		checkbox.className = 'hwbl-bible-map__journey-checkbox';
		checkbox.checked = enabled;
		toggleLabel.appendChild(checkbox);
		toggleLabel.appendChild(document.createTextNode(' Journey mode'));
		bar.appendChild(toggleLabel);

		var controls = document.createElement('div');
		controls.className = 'hwbl-bible-map__journey-controls';
		controls.hidden = !enabled;

		var prev = document.createElement('button');
		prev.type = 'button';
		prev.className = 'hwbl-btn hwbl-btn-secondary hwbl-bible-map__journey-prev';
		prev.textContent = 'Prev stop';
		var play = document.createElement('button');
		play.type = 'button';
		play.className = 'hwbl-btn hwbl-btn-secondary hwbl-bible-map__journey-play';
		play.textContent = 'Play';
		var next = document.createElement('button');
		next.type = 'button';
		next.className = 'hwbl-btn hwbl-btn-secondary hwbl-bible-map__journey-next';
		next.textContent = 'Next stop';
		var status = document.createElement('span');
		status.className = 'hwbl-bible-map__journey-status';
		controls.appendChild(prev);
		controls.appendChild(play);
		controls.appendChild(next);
		controls.appendChild(status);
		bar.appendChild(controls);

		canvas.parentNode.insertBefore(bar, canvas.nextSibling);

		checkbox.addEventListener('change', function () {
			writeJourneyEnabled(checkbox.checked);
			widget._hwblJourneyEnabled = checkbox.checked;
			if (typeof widget._hwblReloadPlaces === 'function') {
				widget._hwblReloadPlaces();
			}
		});
		prev.addEventListener('click', function () {
			stepJourney(widget, -1);
		});
		next.addEventListener('click', function () {
			stepJourney(widget, 1);
		});
		play.addEventListener('click', function () {
			toggleJourneyPlayback(widget, play);
		});

		return bar;
	}

	function stopJourneyPlayback(widget) {
		if (widget._hwblJourneyTimer) {
			window.clearInterval(widget._hwblJourneyTimer);
			widget._hwblJourneyTimer = null;
		}
		var play = widget.querySelector('.hwbl-bible-map__journey-play');
		if (play) {
			play.textContent = 'Play';
		}
	}

	function toggleJourneyPlayback(widget, playBtn) {
		if (widget._hwblJourneyTimer) {
			stopJourneyPlayback(widget);
			return;
		}
		if (playBtn) {
			playBtn.textContent = 'Pause';
		}
		widget._hwblJourneyTimer = window.setInterval(function () {
			var stops = widget._hwblJourneyStops || [];
			var idx = widget._hwblJourneyIndex || 0;
			if (idx >= stops.length - 1) {
				stopJourneyPlayback(widget);
				return;
			}
			stepJourney(widget, 1);
		}, 1800);
	}

	function updateJourneyStatus(widget) {
		var status = widget.querySelector('.hwbl-bible-map__journey-status');
		var stops = widget._hwblJourneyStops || [];
		var idx = widget._hwblJourneyIndex || 0;
		if (!status || !stops.length) {
			return;
		}
		var place = stops[idx];
		status.textContent =
			'Stop ' +
			(idx + 1) +
			' / ' +
			stops.length +
			(place && place.name ? ': ' + place.name : '');
	}

	function stepJourney(widget, delta) {
		var stops = widget._hwblJourneyStops || [];
		if (!stops.length) {
			return;
		}
		var idx = widget._hwblJourneyIndex || 0;
		idx = Math.max(0, Math.min(stops.length - 1, idx + delta));
		widget._hwblJourneyIndex = idx;
		updateJourneyStatus(widget);
		focusPlace(widget, stops[idx]);
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

	function clearMapboxLayers(map) {
		[
			MAPBOX_LABEL_LAYER_ID,
			MAPBOX_CLUSTER_COUNT,
			MAPBOX_CLUSTER_LAYER,
			MAPBOX_UNCLUSTERED,
			MAPBOX_ROUTE_LAYER,
		].forEach(function (id) {
			if (map.getLayer(id)) {
				map.removeLayer(id);
			}
		});
		[MAPBOX_SOURCE_ID, MAPBOX_ROUTE_SOURCE].forEach(function (id) {
			if (map.getSource(id)) {
				map.removeSource(id);
			}
		});
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

	function addMapboxJourney(map, places) {
		var coords = places.map(function (p) {
			return [p.lng, p.lat];
		});
		if (coords.length < 2) {
			return;
		}
		map.addSource(MAPBOX_ROUTE_SOURCE, {
			type: 'geojson',
			data: {
				type: 'Feature',
				geometry: { type: 'LineString', coordinates: coords },
			},
		});
		map.addLayer({
			id: MAPBOX_ROUTE_LAYER,
			type: 'line',
			source: MAPBOX_ROUTE_SOURCE,
			layout: { 'line-join': 'round', 'line-cap': 'round' },
			paint: {
				'line-color': '#b45309',
				'line-width': 3,
				'line-opacity': 0.85,
			},
		});
	}

	function addMapboxClustered(widget, map, places, fit, journey) {
		clearMapboxMarkers(widget);
		clearMapboxLayers(map);
		var list = journey ? sortPlacesForJourney(places) : places || [];
		var geojson = placesToGeoJSON(list, { numbered: !!journey });

		map.addSource(MAPBOX_SOURCE_ID, {
			type: 'geojson',
			data: geojson,
			cluster: !journey,
			clusterMaxZoom: 12,
			clusterRadius: 50,
		});

		if (journey) {
			addMapboxJourney(map, list);
			widget._hwblJourneyStops = list;
			widget._hwblJourneyIndex = 0;
			updateJourneyStatus(widget);
		} else {
			widget._hwblJourneyStops = null;
		}

		if (!journey) {
			map.addLayer({
				id: MAPBOX_CLUSTER_LAYER,
				type: 'circle',
				source: MAPBOX_SOURCE_ID,
				filter: ['has', 'point_count'],
				paint: {
					'circle-color': [
						'step',
						['get', 'point_count'],
						'#93c5fd',
						10,
						'#60a5fa',
						30,
						'#2563eb',
					],
					'circle-radius': [
						'step',
						['get', 'point_count'],
						16,
						10,
						22,
						30,
						28,
					],
				},
			});
			map.addLayer({
				id: MAPBOX_CLUSTER_COUNT,
				type: 'symbol',
				source: MAPBOX_SOURCE_ID,
				filter: ['has', 'point_count'],
				layout: {
					'text-field': ['get', 'point_count_abbreviated'],
					'text-size': 12,
				},
				paint: { 'text-color': '#0f172a' },
			});
		}

		map.addLayer({
			id: MAPBOX_UNCLUSTERED,
			type: 'circle',
			source: MAPBOX_SOURCE_ID,
			filter: journey ? ['all'] : ['!', ['has', 'point_count']],
			paint: {
				'circle-color': journey ? '#b45309' : '#b91c1c',
				'circle-radius': journey ? 7 : 6,
				'circle-stroke-width': 2,
				'circle-stroke-color': '#fff8f0',
			},
		});

		map.addLayer({
			id: MAPBOX_LABEL_LAYER_ID,
			type: 'symbol',
			source: MAPBOX_SOURCE_ID,
			filter: journey ? ['all'] : ['!', ['has', 'point_count']],
			layout: {
				'text-field': journey
					? ['concat', ['to-string', ['get', 'stop']], '. ', ['get', 'name']]
					: ['get', 'label'],
				'text-font': ['DIN Pro Bold', 'Arial Unicode MS Bold'],
				'text-size': 12,
				'text-variable-anchor': ['top', 'bottom', 'left', 'right'],
				'text-radial-offset': 0.9,
				'text-optional': true,
			},
			paint: {
				'text-color': '#b91c1c',
				'text-halo-color': '#fff8f0',
				'text-halo-width': 2,
			},
		});

		if (!map._hwblClusterClickBound) {
			map.on('click', MAPBOX_CLUSTER_LAYER, function (e) {
				var features = map.queryRenderedFeatures(e.point, {
					layers: [MAPBOX_CLUSTER_LAYER],
				});
				var clusterId = features[0] && features[0].properties.cluster_id;
				var source = map.getSource(MAPBOX_SOURCE_ID);
				if (!source || clusterId == null) {
					return;
				}
				source.getClusterExpansionZoom(clusterId, function (err, zoom) {
					if (err) {
						return;
					}
					map.easeTo({
						center: features[0].geometry.coordinates,
						zoom: zoom,
					});
				});
			});
			map.on('click', MAPBOX_UNCLUSTERED, function (e) {
				var feature = e.features && e.features[0];
				if (!feature) {
					return;
				}
				new window.mapboxgl.Popup({ offset: 12 })
					.setLngLat(feature.geometry.coordinates)
					.setHTML(feature.properties.popup || feature.properties.name)
					.addTo(map);
			});
			map._hwblClusterClickBound = true;
		}

		var bounds = new window.mapboxgl.LngLatBounds();
		var hasPoint = false;
		list.forEach(function (place) {
			if (place.lat == null || place.lng == null) {
				return;
			}
			hasPoint = true;
			bounds.extend([place.lng, place.lat]);
		});
		if (fit && hasPoint) {
			if (list.length === 1) {
				map.jumpTo({ center: [list[0].lng, list[0].lat], zoom: 8 });
			} else {
				map.fitBounds(bounds, { padding: 40, maxZoom: journey ? 9 : 10 });
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
		var display = widget._hwblJourneyEnabled
			? sortPlacesForJourney(places)
			: places || [];

		if (!display.length) {
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
				display.length === 1 ? '1 place' : display.length + ' places';
		}

		display.forEach(function (place, index) {
			var li = document.createElement('li');
			li.className = 'hwbl-bible-map__list-item';
			var nameEl = document.createElement('span');
			nameEl.className = 'hwbl-bible-map__list-name';
			var label = place.name || '';
			if (widget._hwblJourneyEnabled) {
				label = index + 1 + '. ' + label;
			}
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

			li.setAttribute('role', 'button');
			li.tabIndex = 0;
			li.addEventListener('click', function () {
				if (widget._hwblJourneyEnabled) {
					widget._hwblJourneyIndex = index;
					updateJourneyStatus(widget);
				}
				focusPlace(widget, place);
			});
			li.addEventListener('keydown', function (event) {
				if (event.key === 'Enter' || event.key === ' ') {
					event.preventDefault();
					if (widget._hwblJourneyEnabled) {
						widget._hwblJourneyIndex = index;
						updateJourneyStatus(widget);
					}
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

	function numberedIcon(index) {
		return window.L.divIcon({
			className: 'hwbl-bible-map__stop-icon',
			html: '<span>' + (index + 1) + '</span>',
			iconSize: [26, 26],
			iconAnchor: [13, 13],
		});
	}

	function initLeaflet(widget, canvas, places, journey) {
		if (typeof window.L === 'undefined') {
			throw new Error('Leaflet is not loaded.');
		}
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

		var list = journey ? sortPlacesForJourney(places) : places || [];
		var bounds = [];
		var permanentLabels = !journey && list.length <= 12;
		var useCluster =
			!journey &&
			list.length > 12 &&
			window.L.markerClusterGroup &&
			typeof window.L.markerClusterGroup === 'function';

		var layerTarget = map;
		if (useCluster) {
			widget._hwblClusterGroup = window.L.markerClusterGroup({
				showCoverageOnHover: false,
				maxClusterRadius: 50,
			});
			map.addLayer(widget._hwblClusterGroup);
			layerTarget = widget._hwblClusterGroup;
		}

		if (journey && list.length > 1) {
			var latlngs = list.map(function (p) {
				return [p.lat, p.lng];
			});
			window.L.polyline(latlngs, {
				color: '#b45309',
				weight: 3,
				opacity: 0.85,
			}).addTo(map);
			widget._hwblJourneyStops = list;
			widget._hwblJourneyIndex = 0;
			updateJourneyStatus(widget);
		} else {
			widget._hwblJourneyStops = null;
		}

		list.forEach(function (place, index) {
			if (place.lat == null || place.lng == null) {
				return;
			}
			var marker = journey
				? window.L.marker([place.lat, place.lng], {
						icon: numberedIcon(index),
				  })
				: window.L.marker([place.lat, place.lng]);
			marker.bindPopup(placePopupHtml(place, journey ? index : undefined));
			if (!journey) {
				marker.bindTooltip(formatPlaceLabelHtml(place), {
					permanent: permanentLabels,
					direction: 'top',
					offset: [0, -8],
					opacity: 1,
					className: 'hwbl-bible-map__label',
					sticky: !permanentLabels,
				});
			}
			layerTarget.addLayer(marker);
			bounds.push([place.lat, place.lng]);
		});

		if (bounds.length === 1) {
			map.setView(bounds[0], 8);
		} else if (bounds.length > 1) {
			map.fitBounds(bounds, { padding: [24, 24], maxZoom: journey ? 9 : 10 });
		} else {
			map.setView([31.7683, 35.2137], 6);
		}

		setTimeout(function () {
			map.invalidateSize();
		}, 50);

		return map;
	}

	function initMapbox(widget, canvas, places, journey) {
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
			addMapboxClustered(widget, map, places, true, journey);
			map.resize();
		});

		if (select) {
			select.addEventListener('change', function () {
				var nextKey = select.value;
				var nextUrl = resolveStyleUrl(nextKey, styles, conf.mapboxStyle);
				writeStoredStyleKey(nextKey);
				map.setStyle(nextUrl);
				map.once('style.load', function () {
					addMapboxClustered(
						widget,
						map,
						widget._hwblMapPlaces || places,
						true,
						!!widget._hwblJourneyEnabled
					);
					map.resize();
				});
			});
		}

		return map;
	}

	function statusForPlaces(places, scope, journey) {
		if (!places.length) {
			return scope === 'book'
				? 'No catalogued places for this book.'
				: 'No catalogued places for this passage.';
		}
		if (journey) {
			return places.length === 1
				? 'Journey: 1 stop'
				: 'Journey: ' + places.length + ' stops (verse order)';
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
		ensureJourneyBar(widget, places);
		var journey = !!widget._hwblJourneyEnabled && places.length > 1;

		if (!places.length) {
			if (status) {
				status.textContent = statusForPlaces(places, scope, false);
			}
			return;
		}

		if (status) {
			status.textContent = statusForPlaces(places, scope, journey);
		}

		var provider = cfg().provider || 'leaflet';
		try {
			widget._hwblMap =
				provider === 'mapbox'
					? initMapbox(widget, canvas, places, journey)
					: initLeaflet(widget, canvas, places, journey);
			widget._hwblMapPlaces = places;
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
					scope === 'book' ? 'Choose a book.' : 'Choose a book and chapter.';
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
				widget._hwblJourneyEnabled =
					readJourneyEnabled() && sortPlacesForJourney(places).length > 1;
				renderMap(widget, places, resolvedScope);
				renderList(widget, places);
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

	function invalidateVisibleMaps(root) {
		(root || document)
			.querySelectorAll('.hwbl-bible-map')
			.forEach(function (widget) {
				var map = widget._hwblMap;
				if (!map) {
					return;
				}
				if (typeof map.invalidateSize === 'function') {
					map.invalidateSize();
				} else if (typeof map.resize === 'function') {
					map.resize();
				}
			});
	}

	window.hwblBibleMapApi = {
		initWidget: initWidget,
		loadWidget: loadWidget,
		renderIntoPanel: renderIntoPanel,
		fetchPlaces: fetchPlaces,
		invalidateVisibleMaps: invalidateVisibleMaps,
	};

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.hwbl-bible-map').forEach(initWidget);
	});

	document.addEventListener('click', function (e) {
		var btn = e.target && e.target.closest && e.target.closest('.hwbl-tab-button');
		if (!btn) {
			return;
		}
		window.setTimeout(function () {
			var lesson = btn.closest('.hwbl-lesson');
			invalidateVisibleMaps(lesson || document);
		}, 50);
	});
})();
