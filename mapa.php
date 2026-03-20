<?php
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/config/cms.php';
$_mapaBlocks = getBlocks('mapa');
$_thresholdUnits = getUnitsWithThresholds();
$_ecoMap = [
    'Água doce' => ['Lago', 'Reservatório', 'Rio', 'Córrego'],
    'Água salgada' => ['Mangue', 'Ilha', 'Oceano', 'Estuário', 'Restinga', 'Apicum'],
    'Terrestre' => ['Floresta', 'Campo', 'Área urbana', 'Solo exposto'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa Interativo - GeoPlasticoBR</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
    <link rel="stylesheet" href="/css/map_v2.css?v=<?php echo time(); ?>">
    <script>
window.GEO_ECO_MAP = <?php echo json_encode($_ecoMap, JSON_UNESCAPED_UNICODE); ?>;
</script>
</head>
<body>
    <!-- Top Bar -->
    <header class="map-topbar">
        <div class="topbar-left">
            <a href="/" class="topbar-brand"><?php echo htmlspecialchars($_mapaBlocks['brand'] ?? getSetting('site_name', 'GeoPlasticoBR')); ?></a>
            <span class="topbar-sep"></span>
            <span class="topbar-page"><?php echo htmlspecialchars($_mapaBlocks['page_label'] ?? 'Mapa Interativo'); ?></span>
        </div>
        <div class="topbar-stats">
            <div class="topbar-stat">
                <span class="topbar-stat-value" id="statVisible">0</span>
                <span class="topbar-stat-label">visiveis</span>
            </div>
            <div class="topbar-stat">
                <span class="topbar-stat-value" id="statTotal">0</span>
                <span class="topbar-stat-label">total</span>
            </div>
        </div>
        <div class="topbar-right">
            <a href="/contribuir.php" class="topbar-link">Contribuir</a>
            <a href="/" class="topbar-btn">Inicio</a>
        </div>
    </header>

    <!-- Toggle Doce/Marinho -->
    <div class="env-toggle" id="envToggle">
        <button class="env-btn active" data-env="all">Todos</button>
        <button class="env-btn" data-env="Doce">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
            Doce
        </button>
        <button class="env-btn" data-env="Marinho">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s2-4 5-4 5 4 5 4 2-4 5-4 5 4 5 4"/><path d="M2 18s2-4 5-4 5 4 5 4 2-4 5-4 5 4 5 4"/></svg>
            Marinho
        </button>
    </div>

    <!-- Filter Panel -->
    <div class="filter-panel" id="filterPanel">
        <button class="filter-toggle" id="filterToggle">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
            <span>Filtros</span>
            <span class="filter-badge" id="filterBadge" style="display:none;">0</span>
        </button>
        <div class="filter-dropdown" id="filterDropdown">
            <div class="filter-section">
                <label class="filter-label">Buscar sistema</label>
                <input type="text" class="filter-input" id="filterSearch" placeholder="Ex: Amazon, Santos..." autocomplete="off">
            </div>
            <div class="filter-section">
                <label class="filter-label">Tipo de Ambiente</label>
                <select class="filter-select" id="filterTipoAmbiente">
                    <option value="">Todos</option>
                    <option value="Água doce">Água doce</option>
                    <option value="Água salgada">Água salgada</option>
                    <option value="Terrestre">Terrestre</option>
                </select>
            </div>
            <div class="filter-section">
                <label class="filter-label">Ecossistema</label>
                <select class="filter-select" id="filterEcossistema">
                    <option value="">Todos</option>
                    <?php foreach (getDataTypes('ecossistema') as $dt): ?>
                    <option value="<?php echo htmlspecialchars($dt['name']); ?>"><?php echo htmlspecialchars($dt['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-section">
                <label class="filter-label">Categoria</label>
                <div class="filter-chips" id="filterCategory">
                    <button class="chip active" data-value="all">Todas</button>
                    <?php foreach (getCategories() as $cat): ?>
                    <button class="chip" data-value="<?php echo $cat['id']; ?>">
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?php echo htmlspecialchars($cat['color']); ?>;margin-right:4px;vertical-align:middle;"></span><?php echo htmlspecialchars($cat['name']); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="filter-section">
                <label class="filter-label">Concentracao</label>
                <div class="filter-chips" id="filterConcentration">
                    <button class="chip active" data-min="0" data-max="999999">Todas</button>
                    <?php
                    $concLabels = ['baixo' => 'Baixa', 'medio' => 'Media', 'elevado' => 'Elevada', 'alto' => 'Alta', 'critico' => 'Critica'];
                    if (!empty($_thresholdUnits) && !empty($_thresholdUnits[0]['thresholds'])):
                        foreach ($_thresholdUnits[0]['thresholds'] as $t):
                            $cMin = (int)$t['min_value'];
                            $cMax = $t['max_value'] !== null ? (int)$t['max_value'] : 999999;
                            $cLabel = $concLabels[$t['level']] ?? $t['level'];
                    ?>
                    <button class="chip" data-min="<?php echo $cMin; ?>" data-max="<?php echo $cMax; ?>"><?php echo htmlspecialchars($cLabel); ?></button>
                    <?php endforeach; endif; ?>
                </div>
            </div>
            <button class="filter-clear" id="filterClear">Limpar filtros</button>
        </div>
    </div>

    <!-- Map Tools (right side) -->
    <div class="map-tools">
        <button class="tool-btn" id="toggleHeatmap" title="Alternar Heatmap">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
        </button>
        <button class="tool-btn" id="toggleFullscreen" title="Tela cheia">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
        </button>
        <button class="tool-btn" id="exportCSV" title="Exportar CSV">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        </button>
    </div>

    <!-- Legend -->
    <div class="map-legend" id="mapLegend">
        <button class="legend-toggle" id="legendToggle">
            <span>Legenda</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div class="legend-content" id="legendContent">
            <?php
            $levelLabels = ['baixo' => 'Baixa', 'medio' => 'Media', 'elevado' => 'Elevada', 'alto' => 'Alta', 'critico' => 'Critica'];
            if (!empty($_thresholdUnits)):
                foreach ($_thresholdUnits[0]['thresholds'] as $t):
                    $min = number_format((float)$t['min_value'], 0, ',', '.');
                    $max = $t['max_value'] !== null ? number_format((float)$t['max_value'], 0, ',', '.') : null;
                    $label = $levelLabels[$t['level']] ?? $t['level'];
            ?>
            <div class="legend-item"><span class="legend-dot" style="background:<?php echo htmlspecialchars($t['color']); ?>;"></span><span><?php echo $max !== null ? "$min - $max ($label)" : "&gt; $min ($label)"; ?></span></div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- Geocoding Search (expandable) -->
    <div class="geocoding-bar" id="geocodingBar">
        <button type="button" class="geo-icon-btn" id="geoToggleBtn" title="Buscar local">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </button>
        <input type="text" id="geocodeInput" placeholder="Buscar local..." autocomplete="off">
        <div class="geocode-results" id="geocodeResults"></div>
    </div>

    <!-- Loading Overlay -->
    <div class="map-loading" id="mapLoading">
        <div class="loading-spinner"></div>
        <span>Carregando dados...</span>
    </div>

    <!-- Map Container -->
    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
    <script>
    window.GEO_THRESHOLDS = <?php echo json_encode($_thresholdUnits, JSON_UNESCAPED_UNICODE); ?>;
    </script>
    <script src="/js/map_v2.js?v=<?php echo time(); ?>"></script>
</body>
</html>
