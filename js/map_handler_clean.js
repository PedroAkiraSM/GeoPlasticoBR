let map = null;
let markerCluster = null;
let microplasticsData = [];
let baseLayers = {};
let currentBaseLayer = null;

const API_URL = '/api/get_microplastics.php';

const MAP_CONFIG = {
    center: [-15.7801, -47.9292],
    zoom: 4,
    minZoom: 3,
    maxZoom: 18
};

function getMarkerColor(concentration) {
    if (concentration < 1000) return '#00CC88';
    if (concentration < 3000) return '#FFD700';
    if (concentration < 5000) return '#FFA500';
    if (concentration < 8000) return '#FF6600';
    return '#CC0000';
}

function getAmbienteBadge(tipo) {
    if (tipo === 'Doce') return '<span style="background:#0077b6;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;">Doce</span>';
    if (tipo === 'Marinho') return '<span style="background:#023e8a;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;">Marinho</span>';
    return '';
}

function createMarker(item) {
    if (!item.latitude || !item.longitude) return null;

    const color = getMarkerColor(item.concentration_value);

    const icon = L.divIcon({
        className: 'custom-marker',
        html: `<div style="background-color: ${color}; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 10px rgba(0,0,0,0.5);"></div>`,
        iconSize: [20, 20],
        iconAnchor: [10, 10]
    });

    const marker = L.marker([item.latitude, item.longitude], { icon: icon });

    const ambienteBadge = getAmbienteBadge(item.tipo_ambiente);
    const matrizTag = item.matriz ? `<span style="background:#e0f7fa;color:#006064;padding:2px 6px;border-radius:3px;font-size:11px;margin-left:4px;">${item.matriz}</span>` : '';

    const popupContent = `
        <div style="font-family: 'Outfit', sans-serif; min-width: 240px;">
            <h3 style="margin: 0 0 8px 0; color: #00838f; font-size: 16px; font-weight: 700;">${item.system || 'Sistema desconhecido'}</h3>
            <div style="margin-bottom: 10px;">${ambienteBadge} ${matrizTag}</div>
            ${item.sampling_point ? `<p style="margin: 4px 0; font-size: 13px;"><strong>Ponto:</strong> ${item.sampling_point}</p>` : ''}
            ${item.ecossistema ? `<p style="margin: 4px 0; font-size: 13px;"><strong>Ecossistema:</strong> ${item.ecossistema}</p>` : ''}
            <p style="margin: 4px 0; font-size: 13px;"><strong>Concentracao:</strong> ${item.concentration || 'N/A'} ${item.unidade ? `(${item.unidade})` : ''}</p>
            <p style="margin: 4px 0; font-size: 13px;"><strong>Coordenadas:</strong> ${item.latitude.toFixed(4)}, ${item.longitude.toFixed(4)}</p>
            ${item.author ? `<p style="margin: 8px 0 0 0; font-size: 11px; color: #666;"><strong>Autor:</strong> ${item.author}</p>` : ''}
        </div>
    `;

    marker.bindPopup(popupContent);
    marker.itemData = item;

    return marker;
}

function initMap() {
    map = L.map('map', {
        center: MAP_CONFIG.center,
        zoom: MAP_CONFIG.zoom,
        minZoom: MAP_CONFIG.minZoom,
        maxZoom: MAP_CONFIG.maxZoom,
        zoomControl: false
    });

    L.control.zoom({
        position: 'topright'
    }).addTo(map);

    baseLayers = {
        'Padrao': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19
        }),
        'Satelite': L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Esri',
            maxZoom: 19
        }),
        'Vias': L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap, HOT',
            maxZoom: 19
        }),
        'Topografico': L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            attribution: 'OpenTopoMap',
            maxZoom: 17
        }),
        'Escuro': L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CARTO',
            maxZoom: 19
        })
    };

    currentBaseLayer = baseLayers['Padrao'];
    currentBaseLayer.addTo(map);

    L.control.layers(baseLayers, null, {
        position: 'topright'
    }).addTo(map);

    markerCluster = L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true
    });

    map.addLayer(markerCluster);

    loadData();
}

async function loadData() {
    try {
        const response = await fetch(API_URL);

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const result = await response.json();

        if (result.success && result.data && result.data.length > 0) {
            microplasticsData = result.data;
            updateMapMarkers(microplasticsData);

            if (window.updateSystemFilterOptions) {
                window.updateSystemFilterOptions(microplasticsData);
            }
            if (window.updateFilterStats) {
                window.updateFilterStats();
            }
        } else {
            console.error('No data received from API');
        }
    } catch (error) {
        console.error('Error loading data:', error);
    }
}

function updateMapMarkers(data) {
    if (!markerCluster) return;

    markerCluster.clearLayers();

    data.forEach(item => {
        const marker = createMarker(item);
        if (marker) {
            markerCluster.addLayer(marker);
        }
    });
}

window.initMap = initMap;
window.updateMapMarkers = updateMapMarkers;
window.createMarker = createMarker;
window.microplasticsData = microplasticsData;
window.markerCluster = markerCluster;
