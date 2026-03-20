/**
 * GeoPlasticoBR - Map v2 (Dynamic Categories)
 * Supports dynamic sample categories with custom icons, colors, and fields.
 * Biotic categories use grouped popups when multiple species share coordinates.
 */

(function() {
    'use strict';

    var map, markerCluster, heatLayer;
    var allData = [];
    var filteredData = [];
    var heatmapActive = false;
    var categories = [];

    var activeFilters = {
        search: '',
        category: 'all',
        concMin: 0,
        concMax: 999999
    };

    var API_URL = '/api/get_samples.php';
    var API_URL_LEGACY = '/api/get_microplastics.php';

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

    // ===== ICON SHAPES =====
    var iconShapes = {
        circle: function(color, size) {
            return '<div style="width:' + size + 'px;height:' + size + 'px;background:' + color + ';border-radius:50%;border:2px solid rgba(255,255,255,0.9);box-shadow:0 2px 8px rgba(0,0,0,0.4),0 0 12px ' + color + '40;"></div>';
        },
        diamond: function(color, size) {
            return '<div style="width:' + size + 'px;height:' + size + 'px;background:' + color + ';border-radius:3px;transform:rotate(45deg);border:2px solid rgba(255,255,255,0.9);box-shadow:0 2px 8px rgba(0,0,0,0.4),0 0 12px ' + color + '40;"></div>';
        },
        square: function(color, size) {
            return '<div style="width:' + size + 'px;height:' + size + 'px;background:' + color + ';border-radius:3px;border:2px solid rgba(255,255,255,0.9);box-shadow:0 2px 8px rgba(0,0,0,0.4),0 0 12px ' + color + '40;"></div>';
        },
        triangle: function(color, size) {
            return '<div style="width:0;height:0;border-left:' + (size/2) + 'px solid transparent;border-right:' + (size/2) + 'px solid transparent;border-bottom:' + size + 'px solid ' + color + ';filter:drop-shadow(0 2px 4px rgba(0,0,0,0.4));"></div>';
        },
        star: function(color, size) {
            return '<div style="width:' + size + 'px;height:' + size + 'px;background:' + color + ';clip-path:polygon(50% 0%,61% 35%,98% 35%,68% 57%,79% 91%,50% 70%,21% 91%,32% 57%,2% 35%,39% 35%);border:none;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.4));"></div>';
        }
    };

    // ===== Helper: get field value by name =====
    function getFieldValue(item, fieldName) {
        if (!item.fields) return null;
        for (var i = 0; i < item.fields.length; i++) {
            if (item.fields[i].name === fieldName) return item.fields[i].value;
        }
        return null;
    }

    // ===== POPUP: Single item (abiotic) =====
    function buildPopup(item) {
        var color = item.color || '#6b7280';
        var html = '<div class="popup-card">';
        html += '<div class="popup-header">';
        html += '<h3 class="popup-title">' + (item.title || 'Sem titulo') + '</h3>';
        html += '<div class="popup-badges">';
        html += '<span class="popup-badge" style="background:' + color + ';">' + (item.category_name || '') + '</span>';
        html += '<span class="popup-badge popup-badge-outline">' + (item.category_type === 'biotico' ? 'Biotico' : 'Abiotico') + '</span>';
        html += '</div></div>';

        html += '<div class="popup-body">';

        if (item.fields && item.fields.length > 0) {
            for (var i = 0; i < item.fields.length; i++) {
                var f = item.fields[i];
                if (f.value === null || f.value === '' || f.value === '0') continue;

                if (f.name === 'concentration_value' && f.type === 'decimal') {
                    var val = parseFloat(f.value) || 0;
                    var thColor = getColor(val);
                    var level = getLevel(val);
                    html += '<div class="popup-concentration">';
                    html += '<div class="popup-conc-bar"><div class="popup-conc-fill" style="width:' + Math.min((val / 10000) * 100, 100) + '%;background:' + thColor + ';"></div></div>';
                    html += '<div class="popup-conc-info"><span class="popup-conc-value">' + val + '</span></div>';
                    html += '<span class="popup-conc-level" style="color:' + thColor + ';">' + level + '</span>';
                    html += '</div>';
                } else if (f.type === 'checkbox') {
                    html += '<div class="popup-row"><span class="popup-key">' + f.label + '</span><span class="popup-val">' + (f.value === '1' || f.value === 1 ? 'Sim' : 'Nao') + '</span></div>';
                } else if (f.type === 'multicheck') {
                    var items = [];
                    try { items = JSON.parse(f.value); } catch(e) { items = [f.value]; }
                    if (items && items.length > 0) {
                        html += '<div class="popup-row" style="flex-direction:column;align-items:flex-start;gap:4px;"><span class="popup-key">' + f.label + '</span><span class="popup-val" style="text-align:left;">' + items.join(', ') + '</span></div>';
                    }
                } else {
                    html += '<div class="popup-row"><span class="popup-key">' + f.label + '</span><span class="popup-val">' + f.value + '</span></div>';
                }
            }
        }

        html += '<div class="popup-row"><span class="popup-key">Coordenadas</span><span class="popup-val">' + item.latitude.toFixed(4) + ', ' + item.longitude.toFixed(4) + '</span></div>';

        if (item.author) html += '<div class="popup-author">' + item.author + '</div>';
        if (item.reference) html += '<div class="popup-ref">' + item.reference + '</div>';
        if (item.doi) html += '<div class="popup-doi"><a href="' + (item.doi.indexOf('http') === 0 ? item.doi : 'https://doi.org/' + item.doi) + '" target="_blank" rel="noopener">DOI: ' + item.doi + '</a></div>';

        html += '</div></div>';
        return html;
    }

    // ===== POPUP: Grouped biotic panel =====
    function buildGroupedPopup(items) {
        if (items.length === 1) return buildBioticSinglePopup(items[0]);

        var first = items[0];
        var color = first.color || '#0e7490';
        var catName = first.category_name || 'Biotico';

        var html = '<div class="popup-grouped">';
        // Header
        html += '<div class="popup-grouped-header" style="border-color:' + color + ';">';
        html += '<div class="popup-grouped-title">';
        html += '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="' + color + '" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
        html += '<span>' + catName + ' &mdash; ' + first.latitude.toFixed(4) + ', ' + first.longitude.toFixed(4) + '</span>';
        html += '</div>';
        html += '<div class="popup-grouped-count">' + items.length + ' especies</div>';
        html += '</div>';

        // Species list
        html += '<div class="popup-species-list">';
        for (var i = 0; i < items.length; i++) {
            var item = items[i];
            var familia = getFieldValue(item, 'familia') || '';
            var uid = 'sp_' + item.id;

            html += '<div class="popup-species-item">';
            html += '<div class="popup-species-row" onclick="document.getElementById(\'' + uid + '\').classList.toggle(\'open\')">';
            html += '<div class="popup-species-info">';
            html += '<span class="popup-species-name">' + (item.title || 'Sem nome') + '</span>';
            if (familia) html += '<span class="popup-species-family">' + familia + '</span>';
            html += '</div>';
            html += '<svg class="popup-species-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>';
            html += '</div>';

            // Expandable detail
            html += '<div class="popup-species-detail" id="' + uid + '">';
            if (item.fields && item.fields.length > 0) {
                for (var j = 0; j < item.fields.length; j++) {
                    var f = item.fields[j];
                    if (f.value === null || f.value === '' || f.value === '0') continue;
                    if (f.name === 'familia') continue; // Already shown in header
                    if (f.type === 'checkbox') {
                        html += '<div class="popup-row"><span class="popup-key">' + f.label + '</span><span class="popup-val">' + (f.value === '1' || f.value === 1 ? 'Sim' : 'Nao') + '</span></div>';
                    } else if (f.type === 'multicheck') {
                        var mcItems = []; try { mcItems = JSON.parse(f.value); } catch(e) { mcItems = [f.value]; }
                        if (mcItems && mcItems.length > 0) html += '<div class="popup-row" style="flex-direction:column;align-items:flex-start;gap:4px;"><span class="popup-key">' + f.label + '</span><span class="popup-val" style="text-align:left;">' + mcItems.join(', ') + '</span></div>';
                    } else {
                        html += '<div class="popup-row"><span class="popup-key">' + f.label + '</span><span class="popup-val">' + f.value + '</span></div>';
                    }
                }
            }
            if (item.author) html += '<div class="popup-author">' + item.author + '</div>';
            if (item.reference) html += '<div class="popup-ref">' + item.reference + '</div>';
            if (item.doi) html += '<div class="popup-doi"><a href="' + (item.doi.indexOf('http') === 0 ? item.doi : 'https://doi.org/' + item.doi) + '" target="_blank" rel="noopener">DOI: ' + item.doi + '</a></div>';
            html += '</div>'; // detail
            html += '</div>'; // species-item
        }
        html += '</div>'; // species-list

        html += '</div>'; // popup-grouped
        return html;
    }

    // Single biotic item popup (with DOI + familia)
    function buildBioticSinglePopup(item) {
        var color = item.color || '#0e7490';
        var familia = getFieldValue(item, 'familia') || '';

        var html = '<div class="popup-card">';
        html += '<div class="popup-header">';
        html += '<h3 class="popup-title">' + (item.title || 'Sem titulo') + '</h3>';
        if (familia) html += '<div class="popup-subtitle">' + familia + '</div>';
        html += '<div class="popup-badges">';
        html += '<span class="popup-badge" style="background:' + color + ';">' + (item.category_name || '') + '</span>';
        html += '<span class="popup-badge popup-badge-outline">Biotico</span>';
        html += '</div></div>';

        html += '<div class="popup-body">';

        if (item.fields && item.fields.length > 0) {
            for (var i = 0; i < item.fields.length; i++) {
                var f = item.fields[i];
                if (f.value === null || f.value === '' || f.value === '0') continue;
                if (f.name === 'familia') continue;
                if (f.type === 'checkbox') {
                    html += '<div class="popup-row"><span class="popup-key">' + f.label + '</span><span class="popup-val">' + (f.value === '1' || f.value === 1 ? 'Sim' : 'Nao') + '</span></div>';
                } else if (f.type === 'multicheck') {
                    var mcItems2 = []; try { mcItems2 = JSON.parse(f.value); } catch(e) { mcItems2 = [f.value]; }
                    if (mcItems2 && mcItems2.length > 0) html += '<div class="popup-row" style="flex-direction:column;align-items:flex-start;gap:4px;"><span class="popup-key">' + f.label + '</span><span class="popup-val" style="text-align:left;">' + mcItems2.join(', ') + '</span></div>';
                } else {
                    html += '<div class="popup-row"><span class="popup-key">' + f.label + '</span><span class="popup-val">' + f.value + '</span></div>';
                }
            }
        }

        html += '<div class="popup-row"><span class="popup-key">Coordenadas</span><span class="popup-val">' + item.latitude.toFixed(4) + ', ' + item.longitude.toFixed(4) + '</span></div>';

        if (item.author) html += '<div class="popup-author">' + item.author + '</div>';
        if (item.reference) html += '<div class="popup-ref">' + item.reference + '</div>';
        if (item.doi) html += '<div class="popup-doi"><a href="' + (item.doi.indexOf('http') === 0 ? item.doi : 'https://doi.org/' + item.doi) + '" target="_blank" rel="noopener">DOI: ' + item.doi + '</a></div>';

        html += '</div></div>';
        return html;
    }

    // ===== Group biotic items by coordinate key =====
    function groupBioticByCoords(items) {
        var groups = {};
        items.forEach(function(item) {
            if (item.category_type !== 'biotico') return;
            var key = item.latitude.toFixed(6) + ',' + item.longitude.toFixed(6);
            if (!groups[key]) groups[key] = [];
            groups[key].push(item);
        });
        return groups;
    }

    // ===== MARKER =====
    function createMarker(item) {
        if (!item.latitude || !item.longitude) return null;

        var color = item.color || '#6b7280';
        var iconType = item.icon || 'circle';
        var size = 14;
        var icon;

        if (item.icon_image) {
            icon = L.icon({
                iconUrl: '/' + item.icon_image,
                iconSize: [24, 24],
                iconAnchor: [12, 12],
                popupAnchor: [0, -12],
                className: 'geo-marker-img'
            });
        } else {
            var shapeFn = iconShapes[iconType] || iconShapes.circle;
            var iconHtml = shapeFn(color, size);
            icon = L.divIcon({
                className: 'geo-marker',
                html: iconHtml,
                iconSize: [size, size],
                iconAnchor: [size / 2, size / 2]
            });
        }

        var marker = L.marker([item.latitude, item.longitude], { icon: icon });
        marker.bindPopup(buildPopup(item), {
            maxWidth: 320,
            minWidth: 280,
            className: 'geo-popup'
        });
        marker.itemData = item;
        return marker;
    }

    // Create grouped marker for biotic species at same location
    function createGroupedMarker(items) {
        var first = items[0];
        if (!first.latitude || !first.longitude) return null;

        var color = first.color || '#0e7490';
        var iconType = first.icon || 'diamond';
        var size = items.length > 1 ? 18 : 14;

        // For grouped markers, show a numbered badge
        var iconHtml;
        if (items.length > 1) {
            iconHtml = '<div style="position:relative;">';
            iconHtml += '<div style="width:' + size + 'px;height:' + size + 'px;background:' + color + ';border-radius:50%;border:2px solid rgba(255,255,255,0.9);box-shadow:0 2px 8px rgba(0,0,0,0.4),0 0 12px ' + color + '40;"></div>';
            iconHtml += '<div style="position:absolute;top:-8px;right:-8px;background:#fff;color:' + color + ';font-size:9px;font-weight:700;min-width:16px;height:16px;border-radius:8px;display:flex;align-items:center;justify-content:center;box-shadow:0 1px 4px rgba(0,0,0,0.3);border:1px solid ' + color + ';">' + items.length + '</div>';
            iconHtml += '</div>';
        } else {
            var shapeFn = iconShapes[iconType] || iconShapes.circle;
            iconHtml = shapeFn(color, size);
        }

        var icon = L.divIcon({
            className: 'geo-marker',
            html: iconHtml,
            iconSize: [size + 8, size + 8],
            iconAnchor: [(size + 8) / 2, (size + 8) / 2]
        });

        var marker = L.marker([first.latitude, first.longitude], { icon: icon });
        marker.bindPopup(buildGroupedPopup(items), {
            maxWidth: 360,
            minWidth: 300,
            maxHeight: 400,
            className: 'geo-popup geo-popup-grouped'
        });
        marker.itemData = first;
        marker._groupedItems = items;
        return marker;
    }

    // ===== FILTER =====
    function applyFilters() {
        filteredData = allData.filter(function(item) {
            if (activeFilters.category !== 'all' && item.category_id !== parseInt(activeFilters.category)) return false;

            if (activeFilters.search) {
                var q = activeFilters.search.toLowerCase();
                var title = (item.title || '').toLowerCase();
                var author = (item.author || '').toLowerCase();
                var catName = (item.category_name || '').toLowerCase();
                var fieldMatch = false;
                if (item.fields) {
                    for (var i = 0; i < item.fields.length; i++) {
                        var val = String(item.fields[i].value || '').toLowerCase();
                        if (val.indexOf(q) !== -1) { fieldMatch = true; break; }
                    }
                }
                if (title.indexOf(q) === -1 && author.indexOf(q) === -1 && catName.indexOf(q) === -1 && !fieldMatch) return false;
            }

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
                    heatData.push([item.latitude, item.longitude, 0.5]);
                }
            });
            heatLayer = L.heatLayer(heatData, {
                radius: 30, blur: 20, maxZoom: 10, max: 1,
                gradient: { 0.2: '#00CC88', 0.4: '#FFD700', 0.6: '#FFA500', 0.8: '#FF6600', 1.0: '#CC0000' }
            }).addTo(map);
            return;
        }

        // Group biotic items by coordinates
        var bioticGroups = groupBioticByCoords(filteredData);
        var bioticHandled = {}; // track which items were handled as groups

        // Add grouped biotic markers
        Object.keys(bioticGroups).forEach(function(key) {
            var group = bioticGroups[key];
            var marker = createGroupedMarker(group);
            if (marker) markerCluster.addLayer(marker);
            group.forEach(function(item) { bioticHandled[item.id] = true; });
        });

        // Add abiotic markers individually
        filteredData.forEach(function(item) {
            if (bioticHandled[item.id]) return;
            var marker = createMarker(item);
            if (marker) markerCluster.addLayer(marker);
        });
    }

    function updateStats() {
        var el1 = document.getElementById('statVisible');
        var el2 = document.getElementById('statTotal');
        if (el1) el1.textContent = filteredData.length;
        if (el2) el2.textContent = allData.length;
    }

    function updateFilterBadge() {
        var count = 0;
        if (activeFilters.category !== 'all') count++;
        if (activeFilters.search) count++;

        var badge = document.getElementById('filterBadge');
        if (badge) {
            if (count > 0) { badge.textContent = count; badge.style.display = ''; }
            else { badge.style.display = 'none'; }
        }
    }

    // ===== SYNC SIDEBAR CATEGORY FILTER =====
    function syncSidebarCategory(value) {
        document.querySelectorAll('#filterCategory .chip').forEach(function(c) {
            c.classList.toggle('active', c.dataset.value === value);
        });
    }

    // ===== BUILD CATEGORY FILTER BUTTONS =====
    function buildCategoryFilters() {
        var container = document.getElementById('envToggle');
        if (!container || categories.length === 0) return;

        container.innerHTML = '';

        var allBtn = document.createElement('button');
        allBtn.className = 'env-btn active';
        allBtn.dataset.env = 'all';
        allBtn.textContent = 'Todos';
        allBtn.addEventListener('click', function() {
            container.querySelectorAll('.env-btn').forEach(function(b) { b.classList.remove('active'); });
            allBtn.classList.add('active');
            activeFilters.category = 'all';
            syncSidebarCategory('all');
            applyFilters();
        });
        container.appendChild(allBtn);

        categories.forEach(function(cat) {
            var btn = document.createElement('button');
            btn.className = 'env-btn';
            btn.dataset.env = String(cat.id);
            btn.innerHTML = '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' + cat.color + ';margin-right:4px;"></span>' + cat.name;
            btn.addEventListener('click', function() {
                container.querySelectorAll('.env-btn').forEach(function(b) { b.classList.remove('active'); });
                btn.classList.add('active');
                activeFilters.category = String(cat.id);
                syncSidebarCategory(String(cat.id));
                applyFilters();
            });
            container.appendChild(btn);
        });
    }

    // ===== EXPORT CSV =====
    function exportCSV() {
        if (filteredData.length === 0) { alert('Nenhum dado para exportar.'); return; }
        if (activeFilters.category === 'all' && categories.length > 1) {
            if (!confirm('Exportar TODOS os dados (' + filteredData.length + ' registros)?\n\nDica: filtre por categoria primeiro para exportar dados separados.')) return;
        }

        var headers = ['ID', 'Categoria', 'Tipo', 'Titulo', 'Latitude', 'Longitude', 'Autor', 'Referencia', 'DOI'];
        var fieldNames = [];
        var fieldLabels = {};
        filteredData.forEach(function(item) {
            if (item.fields) {
                item.fields.forEach(function(f) {
                    if (fieldNames.indexOf(f.name) === -1) {
                        fieldNames.push(f.name);
                        fieldLabels[f.name] = f.label;
                    }
                });
            }
        });
        fieldNames.forEach(function(fn) { headers.push(fieldLabels[fn]); });

        var rows = [headers.join(';')];
        filteredData.forEach(function(item) {
            var fieldMap = {};
            if (item.fields) {
                item.fields.forEach(function(f) { fieldMap[f.name] = f.value; });
            }
            var row = [
                item.id,
                '"' + (item.category_name || '').replace(/"/g, '""') + '"',
                '"' + (item.category_type || '') + '"',
                '"' + (item.title || '').replace(/"/g, '""') + '"',
                item.latitude || '',
                item.longitude || '',
                '"' + (item.author || '').replace(/"/g, '""') + '"',
                '"' + (item.reference || '').replace(/"/g, '""') + '"',
                '"' + (item.doi || '').replace(/"/g, '""') + '"'
            ];
            fieldNames.forEach(function(fn) {
                var val = fieldMap[fn] || '';
                // Parse multicheck JSON arrays for CSV
                if (typeof val === 'string' && val.charAt(0) === '[') {
                    try { var arr = JSON.parse(val); val = arr.join(', '); } catch(e) {}
                }
                row.push('"' + String(val).replace(/"/g, '""') + '"');
            });
            rows.push(row.join(';'));
        });

        var csv = rows.join('\n');
        var blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        var catSuffix = '';
        if (activeFilters.category !== 'all') {
            for (var ci = 0; ci < categories.length; ci++) {
                if (String(categories[ci].id) === activeFilters.category) { catSuffix = '_' + categories[ci].name.toLowerCase().replace(/\s+/g, '_'); break; }
            }
        }
        a.download = 'geoplasticobr_dados' + catSuffix + '_' + new Date().toISOString().slice(0, 10) + '.csv';
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
            spiderfyDistanceMultiplier: 2,
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
                    categories = result.categories || [];
                    if (result.thresholds && result.thresholds.length > 0) {
                        _thresholds = result.thresholds[0].thresholds;
                    }
                    filteredData = allData.slice();
                    buildCategoryFilters();
                    renderMarkers();
                    updateStats();
                }
                hideLoading();
            })
            .catch(function(err) {
                console.error('Error loading new API, trying legacy:', err);
                loadLegacyData();
            });
    }

    function loadLegacyData() {
        fetch(API_URL_LEGACY)
            .then(function(res) { return res.json(); })
            .then(function(result) {
                if (result.success && result.data) {
                    allData = result.data.map(function(item) {
                        return {
                            id: item.id,
                            category_id: 1,
                            category_name: 'Sedimento',
                            category_type: 'abiotico',
                            icon: 'circle',
                            color: '#3b82f6',
                            title: item.system || 'Sem titulo',
                            latitude: item.latitude,
                            longitude: item.longitude,
                            author: item.author,
                            reference: item.reference,
                            doi: null,
                            fields: [
                                { name: 'tipo_ambiente', label: 'Tipo Ambiente', type: 'text', value: item.tipo_ambiente },
                                { name: 'ecossistema', label: 'Ecossistema', type: 'text', value: item.ecossistema },
                                { name: 'concentration_value', label: 'Concentracao', type: 'decimal', value: item.concentration_value },
                                { name: 'unidade', label: 'Unidade', type: 'text', value: item.unidade },
                                { name: 'matriz', label: 'Matriz', type: 'text', value: item.matriz },
                                { name: 'sampling_point', label: 'Ponto', type: 'text', value: item.sampling_point }
                            ].filter(function(f) { return f.value; })
                        };
                    });
                    if (result.fish && result.fish.data) {
                        var fishItems = result.fish.data.map(function(item) {
                            return {
                                id: item.id,
                                category_id: 2,
                                category_name: 'Peixe',
                                category_type: 'biotico',
                                icon: 'diamond',
                                color: '#0e7490',
                                title: item.species,
                                latitude: item.latitude,
                                longitude: item.longitude,
                                author: item.author,
                                reference: item.reference,
                                doi: null,
                                fields: [
                                    { name: 'habit', label: 'Habito', type: 'text', value: item.habit },
                                    { name: 'total_individuals', label: 'Total Individuos', type: 'number', value: item.total_individuals },
                                    { name: 'individuals_with_mp', label: 'C/ Microplasticos', type: 'number', value: item.individuals_with_microplastics },
                                    { name: 'freshwater_system', label: 'Sistema', type: 'text', value: item.freshwater_system }
                                ].filter(function(f) { return f.value; })
                            };
                        });
                        allData = allData.concat(fishItems);
                    }
                    categories = [
                        { id: 1, name: 'Sedimento', type: 'abiotico', icon: 'circle', color: '#3b82f6' },
                        { id: 2, name: 'Peixe', type: 'biotico', icon: 'diamond', color: '#0e7490' }
                    ];
                    if (result.thresholds && result.thresholds.length > 0) {
                        _thresholds = result.thresholds[0].thresholds;
                    }
                    filteredData = allData.slice();
                    buildCategoryFilters();
                    renderMarkers();
                    updateStats();
                }
                hideLoading();
            })
            .catch(function(err) {
                console.error('Error loading legacy data:', err);
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
            if (q.length < 3) { results.classList.remove('open'); return; }
            geoTimer = setTimeout(function() {
                fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q) + '&countrycodes=br&limit=5')
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        results.innerHTML = '';
                        if (data.length === 0) { results.classList.remove('open'); return; }
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
                                setTimeout(function() { if (geoMarker) { map.removeLayer(geoMarker); geoMarker = null; } }, 5000);
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
            if (bar && !bar.contains(e.target)) results.classList.remove('open');
        });
    }

    // ===== UI SETUP =====
    function setupUI() {
        var filterToggle = document.getElementById('filterToggle');
        var filterDropdown = document.getElementById('filterDropdown');
        if (filterToggle && filterDropdown) {
            filterToggle.addEventListener('click', function() {
                filterDropdown.classList.toggle('open');
                filterToggle.classList.toggle('open');
            });
            document.addEventListener('click', function(e) {
                var panel = document.getElementById('filterPanel');
                if (panel && !panel.contains(e.target)) {
                    filterDropdown.classList.remove('open');
                    filterToggle.classList.remove('open');
                }
            });
        }

        var searchInput = document.getElementById('filterSearch');
        if (searchInput) {
            var searchTimer;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function() {
                    activeFilters.search = searchInput.value.trim();
                    applyFilters();
                }, 300);
            });
        }

        var ecoFilter = document.getElementById('filterEcossistema');
        if (ecoFilter) {
            ecoFilter.addEventListener('change', function() {
                activeFilters.ecossistema = this.value;
                applyFilters();
            });
        }

        document.querySelectorAll('#filterCategory .chip').forEach(function(chip) {
            chip.addEventListener('click', function() {
                document.querySelectorAll('#filterCategory .chip').forEach(function(c) { c.classList.remove('active'); });
                chip.classList.add('active');
                activeFilters.category = chip.dataset.value;
                // Sync with envToggle buttons
                var envContainer = document.getElementById('envToggle');
                if (envContainer) {
                    envContainer.querySelectorAll('.env-btn').forEach(function(b) {
                        b.classList.toggle('active', b.dataset.env === chip.dataset.value);
                    });
                }
                applyFilters();
            });
        });

        document.querySelectorAll('#filterConcentration .chip').forEach(function(chip) {
            chip.addEventListener('click', function() {
                document.querySelectorAll('#filterConcentration .chip').forEach(function(c) { c.classList.remove('active'); });
                chip.classList.add('active');
                activeFilters.concMin = parseFloat(chip.dataset.min) || 0;
                activeFilters.concMax = parseFloat(chip.dataset.max) || 999999;
                applyFilters();
            });
        });

        var clearBtn = document.getElementById('filterClear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                activeFilters = { search: '', category: 'all', concMin: 0, concMax: 999999 };
                if (searchInput) searchInput.value = '';
                var envContainer = document.getElementById('envToggle');
                if (envContainer) {
                    envContainer.querySelectorAll('.env-btn').forEach(function(b, i) { b.classList.toggle('active', i === 0); });
                }
                applyFilters();
            });
        }

        var heatBtn = document.getElementById('toggleHeatmap');
        if (heatBtn) {
            heatBtn.addEventListener('click', function() {
                heatmapActive = !heatmapActive;
                this.classList.toggle('active', heatmapActive);
                renderMarkers();
            });
        }

        var fsBtn = document.getElementById('toggleFullscreen');
        if (fsBtn) {
            fsBtn.addEventListener('click', function() {
                if (!document.fullscreenElement) document.documentElement.requestFullscreen();
                else document.exitFullscreen();
            });
        }

        var exportBtn = document.getElementById('exportCSV');
        if (exportBtn) exportBtn.addEventListener('click', exportCSV);

        var legendContent = document.getElementById('legendContent');
        var legendToggle = document.getElementById('legendToggle');
        if (legendContent && legendToggle) {
            legendToggle.addEventListener('click', function() {
                legendContent.classList.toggle('collapsed');
                this.classList.toggle('collapsed');
            });
        }
    }

    // ===== START =====
    document.addEventListener('DOMContentLoaded', initMap);

})();
