/**
 * GeoPlasticoBR - Map v2
 * Full rewrite with heatmap, filters, export, fullscreen, geocoding, loading
 */

(function() {
    'use strict';

    var map, markerCluster, heatLayer;
    var allData = [];
    var filteredData = [];
    var heatmapActive = false;

    var activeFilters = {
        env: 'all',
        search: '',
        ecossistema: '',
        matriz: '',
        concMin: 0,
        concMax: 999999
    };

    var API_URL = '/api/get_microplastics.php';

    // ===== COLORS (dynamic from CMS thresholds) =====
    var _thresholds = (window.GEO_THRESHOLDS && window.GEO_THRESHOLDS.length > 0)
        ? window.GEO_THRESHOLDS[0].thresholds : null;

    var _levelLabels = { baixo: 'Baixa', medio: 'Media', elevado: 'Elevada', alto: 'Alta', critico: 'Critica' };

    function getColor(val) {
        if (_thresholds) {
            for (var i = 0; i < _thresholds.length; i++) {
                var t = _thresholds[i];
                var min = parseFloat(t.min_value);
                var max = t.max_value !== null ? parseFloat(t.max_value) : Infinity;
                if (val >= min && val < max) return t.color;
            }
            return '#6b7280';
        }
        // Fallback
        if (val < 1000) return '#00CC88';
        if (val < 3000) return '#FFD700';
        if (val < 5000) return '#FFA500';
        if (val < 8000) return '#FF6600';
        return '#CC0000';
    }

    function getLevel(val) {
        if (_thresholds) {
            for (var i = 0; i < _thresholds.length; i++) {
                var t = _thresholds[i];
                var min = parseFloat(t.min_value);
                var max = t.max_value !== null ? parseFloat(t.max_value) : Infinity;
                if (val >= min && val < max) return _levelLabels[t.level] || t.level;
            }
            return 'Desconhecido';
        }
        if (val < 1000) return 'Baixa';
        if (val < 3000) return 'Media';
        if (val < 5000) return 'Elevada';
        if (val < 8000) return 'Alta';
        return 'Critica';
    }

    // ===== POPUP =====
    function buildPopup(item) {
        var color = getColor(item.concentration_value);
        var level = getLevel(item.concentration_value);

        var envColor = item.tipo_ambiente === 'Doce' ? '#0077b6' : '#023e8a';
        var envLabel = item.tipo_ambiente || '—';

        var html = '<div class="popup-card">';
        html += '<div class="popup-header">';
        html += '<h3 class="popup-title">' + (item.system || 'Sistema desconhecido') + '</h3>';
        html += '<div class="popup-badges">';
        if (item.tipo_ambiente) html += '<span class="popup-badge" style="background:' + envColor + ';">' + envLabel + '</span>';
        if (item.matriz) html += '<span class="popup-badge popup-badge-outline">' + item.matriz + '</span>';
        html += '</div></div>';

        html += '<div class="popup-body">';

        if (item.sampling_point) {
            html += '<div class="popup-row"><span class="popup-key">Ponto</span><span class="popup-val">' + item.sampling_point + '</span></div>';
        }
        if (item.ecossistema) {
            html += '<div class="popup-row"><span class="popup-key">Ecossistema</span><span class="popup-val">' + item.ecossistema + '</span></div>';
        }

        html += '<div class="popup-concentration">';
        html += '<div class="popup-conc-bar"><div class="popup-conc-fill" style="width:' + Math.min((item.concentration_value / 10000) * 100, 100) + '%;background:' + color + ';"></div></div>';
        html += '<div class="popup-conc-info">';
        html += '<span class="popup-conc-value">' + (item.concentration || 'N/A') + '</span>';
        if (item.unidade) html += '<span class="popup-conc-unit">' + item.unidade + '</span>';
        html += '</div>';
        html += '<span class="popup-conc-level" style="color:' + color + ';">' + level + '</span>';
        html += '</div>';

        html += '<div class="popup-row"><span class="popup-key">Coordenadas</span><span class="popup-val">' + item.latitude.toFixed(4) + ', ' + item.longitude.toFixed(4) + '</span></div>';

        if (item.author) {
            html += '<div class="popup-author">' + item.author + '</div>';
        }
        if (item.reference) {
            html += '<div class="popup-ref">' + item.reference + '</div>';
        }

        html += '</div></div>';
        return html;
    }

    // ===== MARKER =====
    function createMarker(item) {
        if (!item.latitude || !item.longitude) return null;

        var color = getColor(item.concentration_value);
        var size = 12;
        if (item.concentration_value >= 5000) size = 16;
        if (item.concentration_value >= 8000) size = 20;

        var icon = L.divIcon({
            className: 'geo-marker',
            html: '<div style="width:' + size + 'px;height:' + size + 'px;background:' + color + ';border-radius:50%;border:2px solid rgba(255,255,255,0.9);box-shadow:0 2px 8px rgba(0,0,0,0.4),0 0 12px ' + color + '40;"></div>',
            iconSize: [size, size],
            iconAnchor: [size / 2, size / 2]
        });

        var marker = L.marker([item.latitude, item.longitude], { icon: icon });
        marker.bindPopup(buildPopup(item), {
            maxWidth: 320,
            minWidth: 280,
            className: 'geo-popup'
        });
        marker.itemData = item;
        return marker;
    }

    // ===== FILTER =====
    function applyFilters() {
        filteredData = allData.filter(function(item) {
            if (activeFilters.env !== 'all' && item.tipo_ambiente !== activeFilters.env) return false;

            if (activeFilters.search) {
                var q = activeFilters.search.toLowerCase();
                var sys = (item.system || '').toLowerCase();
                var sp = (item.sampling_point || '').toLowerCase();
                if (sys.indexOf(q) === -1 && sp.indexOf(q) === -1) return false;
            }

            if (activeFilters.ecossistema && item.ecossistema !== activeFilters.ecossistema) return false;
            if (activeFilters.matriz && item.matriz !== activeFilters.matriz) return false;

            var val = item.concentration_value || 0;
            if (val < activeFilters.concMin || val > activeFilters.concMax) return false;

            return true;
        });

        renderMarkers();
        updateStats();
        updateFilterBadge();
    }

    function renderMarkers() {
        if (markerCluster) {
            markerCluster.clearLayers();
        }

        if (heatLayer) {
            map.removeLayer(heatLayer);
            heatLayer = null;
        }

        if (heatmapActive) {
            var heatData = [];
            filteredData.forEach(function(item) {
                if (item.latitude && item.longitude) {
                    heatData.push([item.latitude, item.longitude, Math.min(item.concentration_value / 5000, 1)]);
                }
            });
            heatLayer = L.heatLayer(heatData, {
                radius: 30,
                blur: 20,
                maxZoom: 10,
                max: 1,
                gradient: _thresholds ? (function() {
                    var g = {};
                    for (var i = 0; i < _thresholds.length; i++) {
                        g[((i + 1) / _thresholds.length).toFixed(1)] = _thresholds[i].color;
                    }
                    return g;
                })() : {
                    0.2: '#00CC88',
                    0.4: '#FFD700',
                    0.6: '#FFA500',
                    0.8: '#FF6600',
                    1.0: '#CC0000'
                }
            }).addTo(map);
        } else {
            filteredData.forEach(function(item) {
                var marker = createMarker(item);
                if (marker) markerCluster.addLayer(marker);
            });
        }
    }

    function updateStats() {
        var el1 = document.getElementById('statVisible');
        var el2 = document.getElementById('statTotal');
        if (el1) el1.textContent = filteredData.length;
        if (el2) el2.textContent = allData.length;
    }

    function updateFilterBadge() {
        var count = 0;
        if (activeFilters.env !== 'all') count++;
        if (activeFilters.search) count++;
        if (activeFilters.ecossistema) count++;
        if (activeFilters.matriz) count++;
        if (activeFilters.concMin > 0 || activeFilters.concMax < 999999) count++;

        var badge = document.getElementById('filterBadge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = '';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    // ===== EXPORT CSV =====
    function exportCSV() {
        if (filteredData.length === 0) return;

        var headers = ['Sistema', 'Ponto', 'Tipo Ambiente', 'Ecossistema', 'Matriz', 'Unidade', 'Concentracao', 'Valor', 'Latitude', 'Longitude', 'Autor', 'Referencia'];
        var rows = [headers.join(',')];

        filteredData.forEach(function(item) {
            var row = [
                '"' + (item.system || '').replace(/"/g, '""') + '"',
                '"' + (item.sampling_point || '').replace(/"/g, '""') + '"',
                '"' + (item.tipo_ambiente || '') + '"',
                '"' + (item.ecossistema || '') + '"',
                '"' + (item.matriz || '') + '"',
                '"' + (item.unidade || '') + '"',
                '"' + (item.concentration || '') + '"',
                item.concentration_value || 0,
                item.latitude || '',
                item.longitude || '',
                '"' + (item.author || '').replace(/"/g, '""') + '"',
                '"' + (item.reference || '').replace(/"/g, '""') + '"'
            ];
            rows.push(row.join(','));
        });

        var csv = rows.join('\n');
        var blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'geoplasticobr_dados_' + new Date().toISOString().slice(0, 10) + '.csv';
        a.click();
        URL.revokeObjectURL(url);
    }

    // ===== LOADING =====
    function hideLoading() {
        var el = document.getElementById('mapLoading');
        if (el) el.classList.add('hidden');
    }

    // ===== INIT MAP =====
    function initMap() {
        map = L.map('map', {
            center: [-15.78, -47.93],
            zoom: 4,
            minZoom: 3,
            maxZoom: 18,
            zoomControl: false,
            maxBounds: [[-85, -200], [85, 200]],
            maxBoundsViscosity: 1.0,
            worldCopyJump: true
        });

        L.control.zoom({ position: 'topright' }).addTo(map);

        var baseLayers = {
            'Padrao': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap', maxZoom: 19
            }),
            'Satelite': L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Esri', maxZoom: 19
            }),
            'Vias': L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap HOT', maxZoom: 19
            }),
            'Topografico': L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
                attribution: 'OpenTopoMap', maxZoom: 17
            }),
            'Escuro': L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; CARTO', maxZoom: 19
            })
        };

        baseLayers['Padrao'].addTo(map);

        L.control.layers(baseLayers, null, { position: 'topright' }).addTo(map);

        markerCluster = L.markerClusterGroup({
            maxClusterRadius: 50,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true,
            iconCreateFunction: function(cluster) {
                var count = cluster.getChildCount();
                var size = count < 10 ? 36 : count < 50 ? 44 : 52;
                var cls = count < 10 ? 'cluster-sm' : count < 50 ? 'cluster-md' : 'cluster-lg';
                return L.divIcon({
                    html: '<div class="cluster-icon ' + cls + '">' + count + '</div>',
                    className: 'geo-cluster',
                    iconSize: [size, size]
                });
            }
        });
        map.addLayer(markerCluster);

        loadData();
        setupUI();
        setupGeocoding();
    }

    // ===== LOAD DATA =====
    function loadData() {
        fetch(API_URL)
            .then(function(res) { return res.json(); })
            .then(function(result) {
                if (result.success && result.data) {
                    allData = result.data;
                    filteredData = allData.slice();
                    renderMarkers();
                    updateStats();
                }
                hideLoading();
            })
            .catch(function(err) {
                console.error('Error loading data:', err);
                hideLoading();
            });
    }

    // ===== GEOCODING =====
    function setupGeocoding() {
        var input = document.getElementById('geocodeInput');
        var results = document.getElementById('geocodeResults');
        if (!input || !results) return;

        var geoTimer;
        var geoMarker = null;

        input.addEventListener('input', function() {
            clearTimeout(geoTimer);
            var q = input.value.trim();
            if (q.length < 3) {
                results.classList.remove('open');
                return;
            }
            geoTimer = setTimeout(function() {
                fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q) + '&countrycodes=br&limit=5')
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        results.innerHTML = '';
                        if (data.length === 0) {
                            results.classList.remove('open');
                            return;
                        }
                        data.forEach(function(place) {
                            var div = document.createElement('div');
                            div.className = 'geocode-item';
                            div.textContent = place.display_name;
                            div.addEventListener('click', function() {
                                var lat = parseFloat(place.lat);
                                var lon = parseFloat(place.lon);
                                map.setView([lat, lon], 10);

                                if (geoMarker) map.removeLayer(geoMarker);
                                geoMarker = L.marker([lat, lon]).addTo(map);
                                setTimeout(function() {
                                    if (geoMarker) { map.removeLayer(geoMarker); geoMarker = null; }
                                }, 5000);

                                results.classList.remove('open');
                                input.value = place.display_name.split(',')[0];
                            });
                            results.appendChild(div);
                        });
                        results.classList.add('open');
                    });
            }, 400);
        });

        document.addEventListener('click', function(e) {
            var bar = document.getElementById('geocodingBar');
            if (bar && !bar.contains(e.target)) {
                results.classList.remove('open');
            }
        });
    }

    // ===== UI SETUP =====
    function setupUI() {
        // Environment toggle
        var envBtns = document.querySelectorAll('#envToggle .env-btn');
        envBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                envBtns.forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                activeFilters.env = btn.dataset.env;
                applyFilters();
            });
        });

        // Filter panel toggle
        var filterToggle = document.getElementById('filterToggle');
        var filterDropdown = document.getElementById('filterDropdown');
        filterToggle.addEventListener('click', function() {
            filterDropdown.classList.toggle('open');
            filterToggle.classList.toggle('open');
        });

        // Close filter panel on outside click
        document.addEventListener('click', function(e) {
            var panel = document.getElementById('filterPanel');
            if (!panel.contains(e.target)) {
                filterDropdown.classList.remove('open');
                filterToggle.classList.remove('open');
            }
        });

        // Search
        var searchInput = document.getElementById('filterSearch');
        var searchTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                activeFilters.search = searchInput.value.trim();
                applyFilters();
            }, 300);
        });

        // Ecossistema
        document.getElementById('filterEcossistema').addEventListener('change', function() {
            activeFilters.ecossistema = this.value;
            applyFilters();
        });

        // Matriz chips
        document.querySelectorAll('#filterMatriz .chip').forEach(function(chip) {
            chip.addEventListener('click', function() {
                document.querySelectorAll('#filterMatriz .chip').forEach(function(c) { c.classList.remove('active'); });
                chip.classList.add('active');
                activeFilters.matriz = chip.dataset.value;
                applyFilters();
            });
        });

        // Concentration chips
        document.querySelectorAll('#filterConcentration .chip').forEach(function(chip) {
            chip.addEventListener('click', function() {
                document.querySelectorAll('#filterConcentration .chip').forEach(function(c) { c.classList.remove('active'); });
                chip.classList.add('active');
                activeFilters.concMin = parseFloat(chip.dataset.min) || 0;
                activeFilters.concMax = parseFloat(chip.dataset.max) || 999999;
                applyFilters();
            });
        });

        // Clear filters
        document.getElementById('filterClear').addEventListener('click', function() {
            activeFilters = { env: 'all', search: '', ecossistema: '', matriz: '', concMin: 0, concMax: 999999 };
            searchInput.value = '';
            document.getElementById('filterEcossistema').value = '';
            document.querySelectorAll('#filterMatriz .chip').forEach(function(c, i) { c.classList.toggle('active', i === 0); });
            document.querySelectorAll('#filterConcentration .chip').forEach(function(c, i) { c.classList.toggle('active', i === 0); });
            document.querySelectorAll('#envToggle .env-btn').forEach(function(b, i) { b.classList.toggle('active', i === 0); });
            applyFilters();
        });

        // Heatmap toggle
        document.getElementById('toggleHeatmap').addEventListener('click', function() {
            heatmapActive = !heatmapActive;
            this.classList.toggle('active', heatmapActive);
            renderMarkers();
        });

        // Fullscreen
        document.getElementById('toggleFullscreen').addEventListener('click', function() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        });

        // Export CSV
        document.getElementById('exportCSV').addEventListener('click', exportCSV);

        // Legend toggle
        var legendContent = document.getElementById('legendContent');
        document.getElementById('legendToggle').addEventListener('click', function() {
            legendContent.classList.toggle('collapsed');
            this.classList.toggle('collapsed');
        });
    }

    // ===== START =====
    document.addEventListener('DOMContentLoaded', initMap);

})();
