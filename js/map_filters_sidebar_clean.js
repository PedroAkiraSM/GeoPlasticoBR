let originalData = [];
let filteredData = [];

function initFiltersSidebar() {
    createSidebarElements();
    setupSidebarEventListeners();

    setTimeout(() => {
        if (typeof window.microplasticsData !== 'undefined' && window.microplasticsData.length > 0) {
            originalData = [...window.microplasticsData];
            filteredData = [...window.microplasticsData];
            updateSystemFilterOptions(originalData);
            updateFilterStats();
        }
    }, 1000);
}

function createSidebarElements() {
    if (document.getElementById('filterSidebar')) return;

    const sidebarHTML = `
        <div id="filterSidebarOverlay" class="filter-sidebar-overlay"></div>

        <button id="filterSidebarToggle" class="filter-sidebar-toggle" aria-label="Abrir filtros">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
            <span class="filter-sidebar-toggle-text">FILTROS</span>
        </button>

        <aside id="filterSidebar" class="filter-sidebar">
            <div class="filter-sidebar-header">
                <h3 class="filter-sidebar-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                    </svg>
                    Filtros
                </h3>
                <p class="filter-sidebar-subtitle">Refine os dados do mapa</p>
            </div>

            <div class="filter-sidebar-content">
                <div class="filter-group">
                    <label class="filter-label">Sistema Aquático</label>
                    <select id="systemFilter" class="filter-select">
                        <option value="">Todos os sistemas</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Concentração (partículas/Kg)</label>
                    <div style="text-align: center; margin-bottom: 12px;">
                        <span id="concentrationValue" class="filter-value">0 - 100.000</span>
                    </div>
                    <div class="range-inputs">
                        <input type="number" id="minConcentration" class="filter-input-number" placeholder="Mín" value="0" min="0">
                        <span class="range-separator">—</span>
                        <input type="number" id="maxConcentration" class="filter-input-number" placeholder="Máx" value="100000" min="0">
                    </div>
                    <div class="concentration-presets">
                        <button class="preset-btn" data-min="0" data-max="1000">Baixa</button>
                        <button class="preset-btn" data-min="1000" data-max="3000">Média</button>
                        <button class="preset-btn" data-min="3000" data-max="5000">Elevada</button>
                        <button class="preset-btn" data-min="5000" data-max="8000">Alta</button>
                        <button class="preset-btn" data-min="8000" data-max="100000">Crítica</button>
                        <button class="preset-btn" data-min="0" data-max="100000">Todas</button>
                    </div>
                </div>

                <div class="filter-group">
                    <label class="filter-label">Estatísticas</label>
                    <div class="filter-stats">
                        <div class="stat-item">
                            <span class="stat-label">Pontos visíveis:</span>
                            <span id="visiblePoints" class="stat-value">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Total de pontos:</span>
                            <span id="totalPoints" class="stat-value">0</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="filter-sidebar-footer">
                <div class="filter-actions">
                    <button id="clearFiltersBtn" class="filter-btn filter-btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="1 4 1 10 7 10"></polyline>
                            <path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"></path>
                        </svg>
                        Limpar
                    </button>
                    <button id="applyFiltersBtn" class="filter-btn filter-btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Aplicar
                    </button>
                </div>
            </div>
        </aside>
    `;

    document.body.insertAdjacentHTML('beforeend', sidebarHTML);
}

function setupSidebarEventListeners() {
    const toggle = document.getElementById('filterSidebarToggle');
    const sidebar = document.getElementById('filterSidebar');
    const overlay = document.getElementById('filterSidebarOverlay');
    const applyBtn = document.getElementById('applyFiltersBtn');
    const clearBtn = document.getElementById('clearFiltersBtn');

    toggle.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', closeSidebar);
    applyBtn.addEventListener('click', applyFilters);
    clearBtn.addEventListener('click', clearFilters);

    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const min = this.dataset.min;
            const max = this.dataset.max;
            document.getElementById('minConcentration').value = min;
            document.getElementById('maxConcentration').value = max;
            updateConcentrationValue();

            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
        });
    });

    document.getElementById('minConcentration').addEventListener('input', updateConcentrationValue);
    document.getElementById('maxConcentration').addEventListener('input', updateConcentrationValue);

    document.getElementById('minConcentration').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') applyFilters();
    });
    document.getElementById('maxConcentration').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') applyFilters();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            closeSidebar();
        }
    });
}

function toggleSidebar() {
    const sidebar = document.getElementById('filterSidebar');
    const isActive = sidebar.classList.contains('active');

    if (isActive) {
        closeSidebar();
    } else {
        openSidebar();
    }
}

function openSidebar() {
    const toggle = document.getElementById('filterSidebarToggle');
    const sidebar = document.getElementById('filterSidebar');
    const overlay = document.getElementById('filterSidebarOverlay');

    sidebar.classList.add('active');
    toggle.classList.add('active');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    const toggle = document.getElementById('filterSidebarToggle');
    const sidebar = document.getElementById('filterSidebar');
    const overlay = document.getElementById('filterSidebarOverlay');

    sidebar.classList.remove('active');
    toggle.classList.remove('active');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

function updateSystemFilterOptions(data) {
    const systemFilter = document.getElementById('systemFilter');
    if (!systemFilter || !data) return;

    const systems = [...new Set(data.map(item => item.system))].filter(Boolean).sort();

    systemFilter.innerHTML = '<option value="">Todos os sistemas</option>';

    systems.forEach(system => {
        const option = document.createElement('option');
        option.value = system;
        option.textContent = system;
        systemFilter.appendChild(option);
    });
}

function updateConcentrationValue() {
    const min = document.getElementById('minConcentration').value || 0;
    const max = document.getElementById('maxConcentration').value || 100000;
    const display = document.getElementById('concentrationValue');

    if (display) {
        display.textContent = `${parseInt(min).toLocaleString('pt-BR')} - ${parseInt(max).toLocaleString('pt-BR')}`;
    }
}

function applyFilters() {
    if (!window.microplasticsData || window.microplasticsData.length === 0) {
        alert('Dados ainda não carregados. Aguarde...');
        return;
    }

    originalData = [...window.microplasticsData];

    const systemFilter = document.getElementById('systemFilter').value;
    const minConcentration = parseFloat(document.getElementById('minConcentration').value) || 0;
    const maxConcentration = parseFloat(document.getElementById('maxConcentration').value) || Infinity;

    filteredData = originalData.filter(item => {
        if (systemFilter && item.system !== systemFilter) {
            return false;
        }

        const concentration = item.concentration_value || 0;
        if (concentration < minConcentration || concentration > maxConcentration) {
            return false;
        }

        return true;
    });

    if (window.markerCluster) {
        window.markerCluster.clearLayers();

        filteredData.forEach(item => {
            if (item.latitude && item.longitude) {
                const marker = window.createMarker(item);
                if (marker) {
                    window.markerCluster.addLayer(marker);
                }
            }
        });
    }

    updateFilterStats();

    if (window.innerWidth <= 768) {
        closeSidebar();
    }
}

function clearFilters() {
    document.getElementById('systemFilter').value = '';
    document.getElementById('minConcentration').value = 0;
    document.getElementById('maxConcentration').value = 100000;

    document.querySelectorAll('.preset-btn').forEach(btn => btn.classList.remove('active'));

    updateConcentrationValue();

    if (!window.microplasticsData) return;

    originalData = [...window.microplasticsData];
    filteredData = [...window.microplasticsData];

    if (window.markerCluster) {
        window.markerCluster.clearLayers();

        filteredData.forEach(item => {
            if (item.latitude && item.longitude) {
                const marker = window.createMarker(item);
                if (marker) {
                    window.markerCluster.addLayer(marker);
                }
            }
        });
    }

    updateFilterStats();
}

function updateFilterStats() {
    const visiblePoints = document.getElementById('visiblePoints');
    const totalPoints = document.getElementById('totalPoints');

    if (visiblePoints && totalPoints) {
        visiblePoints.textContent = filteredData.length || 0;
        totalPoints.textContent = originalData.length || 0;
    }
}

window.initFiltersSidebar = initFiltersSidebar;
window.updateSystemFilterOptions = updateSystemFilterOptions;
window.originalData = originalData;
window.filteredData = filteredData;
