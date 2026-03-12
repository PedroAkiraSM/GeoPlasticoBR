<?php
require_once __DIR__ . '/auth.php';
requireLogin();

// Estatisticas dinamicas do banco de dados
require_once __DIR__ . '/config/database.php';
$pdo = getDatabaseConnection();
$statsPoints = 0;
$statsRecords = 0;
$statsEcosystems = 0;
if ($pdo) {
    try {
        $statsPoints = (int)$pdo->query("SELECT COUNT(*) FROM microplastics_sediment WHERE approved = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL")->fetchColumn();
        $statsRecords = (int)$pdo->query("SELECT COUNT(*) FROM microplastics_sediment WHERE approved = 1")->fetchColumn();
        $statsEcosystems = (int)$pdo->query("SELECT COUNT(DISTINCT ecossistema) FROM microplastics_sediment WHERE approved = 1 AND ecossistema IS NOT NULL")->fetchColumn();
    } catch (Exception $e) {}
}

$pageTitle = "GeoPlasticoBR - Mapeamento de Microplasticos no Brasil";
$heroPage = true;
include 'includes/header.php';
?>

<!-- Hero com Video de Fundo -->
<section class="hero-video-section">
    <div class="hero-video-wrapper">
        <video autoplay muted loop playsinline preload="metadata" class="hero-video" id="heroVideo">
            <source src="/videos/hero-bg.mp4" type="video/mp4">
        </video>
        <div class="hero-overlay"></div>
    </div>

    <div class="hero-content">
        <h1 class="hero-title">
            <span class="hero-title-main">GEO</span>
            <span class="hero-title-main">PLASTICO</span>
            <span class="hero-title-accent">BR</span>
        </h1>
        <p class="hero-subtitle">Mapeando a poluicao invisivel nos ecossistemas aquaticos brasileiros</p>
        <div class="hero-cta">
            <a href="/mapa.php" class="hero-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                    <line x1="8" y1="2" x2="8" y2="18"></line>
                    <line x1="16" y1="6" x2="16" y2="22"></line>
                </svg>
                Explorar Mapa
            </a>
            <a href="/sobre.php" class="hero-btn-secondary">Sobre o Projeto</a>
        </div>
    </div>

    <div class="hero-scroll-indicator">
        <span>Scroll</span>
        <div class="scroll-line"></div>
    </div>
</section>

<!-- Secao: O Problema -->
<section class="content-section fade-in">
    <div class="content-container">
        <div class="section-header">
            <span class="section-tag">O Problema</span>
            <h2 class="section-title">Microplasticos</h2>
            <p class="section-desc">
                Fragmentos de plastico menores que <strong>5mm</strong> contaminam silenciosamente nossos oceanos,
                rios e lagos. Praticamente invisiveis a olho nu, mas com impacto imenso nos ecossistemas aquaticos.
            </p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $statsPoints; ?>+</div>
                <div class="stat-label">Pontos de Coleta</div>
                <div class="stat-desc">Mapeados em todo o Brasil</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $statsEcosystems; ?></div>
                <div class="stat-label">Ecossistemas</div>
                <div class="stat-desc">Tipos de ambientes monitorados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $statsRecords; ?></div>
                <div class="stat-label">Registros Cientificos</div>
                <div class="stat-desc">Dados verificados por pares</div>
            </div>
        </div>
    </div>
</section>

<!-- Secao: Ferramenta Principal -->
<section class="content-section dark-section fade-in">
    <div class="content-container">
        <div class="feature-block">
            <div class="feature-text">
                <span class="section-tag">Ferramenta Principal</span>
                <h2 class="section-title">Mapa Interativo</h2>
                <p class="section-desc">
                    Visualize em tempo real a distribuicao de microplasticos nos rios, lagos e oceanos brasileiros.
                    Navegue por centenas de pontos de coleta, alterne entre visualizacoes e acesse dados cientificos completos.
                </p>
                <ul class="feature-list">
                    <li>Multiplas visualizacoes de mapa</li>
                    <li>Dados cientificos verificados</li>
                    <li>Atualizacao continua</li>
                    <li>Filtros por ambiente e ecossistema</li>
                </ul>
                <a href="/mapa.php" class="hero-btn-primary" style="margin-top: 2rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                        <line x1="8" y1="2" x2="8" y2="18"></line>
                        <line x1="16" y1="6" x2="16" y2="22"></line>
                    </svg>
                    Acessar Mapa Interativo
                </a>
            </div>
            <div class="feature-visual">
                <div class="map-preview">
                    <div id="previewMap" style="width:100%;height:100%;"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Secao: Estatisticas -->
<section class="content-section dark-section fade-in" id="chartSection">
    <div class="content-container">
        <div class="section-header">
            <span class="section-tag">Dados</span>
            <h2 class="section-title">Estatisticas do Mapeamento</h2>
            <p class="section-desc">Distribuicao de registros por ecossistema e niveis de concentracao mapeados.</p>
        </div>
        <div class="charts-grid">
            <div class="chart-card">
                <h3 class="chart-title">Registros por Ecossistema</h3>
                <canvas id="chartEcossistema"></canvas>
            </div>
            <div class="chart-card">
                <h3 class="chart-title">Niveis de Concentracao</h3>
                <canvas id="chartConcentracao"></canvas>
            </div>
        </div>
    </div>
</section>

<!-- Secao: Missao -->
<section class="content-section fade-in">
    <div class="content-container">
        <div class="section-header">
            <span class="section-tag">Nossa Missao</span>
            <h2 class="section-title">Democratizar dados cientificos</h2>
        </div>

        <div class="mission-grid">
            <div class="mission-card">
                <div class="mission-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                </div>
                <h3>Dados Verificados</h3>
                <p>Pesquisas cientificas revisadas por pares garantindo confiabilidade e precisao.</p>
            </div>
            <div class="mission-card">
                <div class="mission-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                </div>
                <h3>Acesso Aberto</h3>
                <p>Plataforma gratuita para pesquisadores, estudantes e o publico em geral.</p>
            </div>
            <div class="mission-card">
                <div class="mission-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <line x1="9" y1="21" x2="9" y2="9"/>
                        <line x1="15" y1="21" x2="15" y2="3"/>
                    </svg>
                </div>
                <h3>Visualizacao Interativa</h3>
                <p>Mapa com multiplas camadas permitindo analise espacial detalhada.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Final -->
<section class="cta-section fade-in">
    <div class="content-container" style="text-align: center;">
        <h2 class="cta-title">Pronto para Explorar?</h2>
        <p class="cta-desc">
            Descubra a distribuicao de microplasticos nos ecossistemas aquaticos brasileiros.
        </p>
        <div class="hero-cta" style="justify-content: center;">
            <a href="/mapa.php" class="hero-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                    <line x1="8" y1="2" x2="8" y2="18"></line>
                    <line x1="16" y1="6" x2="16" y2="22"></line>
                </svg>
                Acessar Mapa Agora
            </a>
            <a href="/contribuir.php" class="hero-btn-secondary">Contribuir com Dados</a>
        </div>
    </div>
</section>

<style>
/* ===== HERO VIDEO SECTION ===== */
.hero-video-section {
    position: relative;
    width: 100%;
    height: 100vh;
    min-height: 600px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero-video-wrapper {
    position: absolute;
    inset: 0;
    z-index: 0;
}

.hero-video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(180deg,
            rgba(0, 10, 20, 0.3) 0%,
            rgba(0, 10, 20, 0.1) 40%,
            rgba(0, 10, 20, 0.4) 70%,
            rgba(0, 10, 20, 0.85) 100%
        );
}

.hero-content {
    position: relative;
    z-index: 10;
    text-align: center;
    padding: 0 2rem;
    animation: heroFadeIn 1.5s ease-out;
}

.hero-title {
    display: flex;
    flex-direction: column;
    align-items: center;
    line-height: 0.9;
    margin-bottom: 1.5rem;
}

.hero-title-main {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: clamp(4rem, 12vw, 10rem);
    color: #ffffff;
    letter-spacing: -0.03em;
    text-shadow: 0 4px 30px rgba(0, 0, 0, 0.5);
}

.hero-title-accent {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: clamp(4rem, 12vw, 10rem);
    color: #60A5FA;
    letter-spacing: -0.03em;
    text-shadow: 0 0 60px rgba(96, 165, 250, 0.4), 0 4px 30px rgba(0, 0, 0, 0.5);
}

.hero-subtitle {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: clamp(1rem, 2vw, 1.3rem);
    color: rgba(255, 255, 255, 0.7);
    font-weight: 400;
    max-width: 600px;
    margin: 0 auto 2.5rem;
    letter-spacing: 0.01em;
}

.hero-cta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
}

.hero-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: #ffffff;
    color: #0F172A;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 700;
    font-size: 0.95rem;
    border-radius: 50px;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
}

.hero-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 40px rgba(255, 255, 255, 0.2);
}

.hero-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    background: transparent;
    color: #ffffff;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 600;
    font-size: 0.95rem;
    border-radius: 50px;
    text-decoration: none;
    border: 1px solid rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
}

.hero-btn-secondary:hover {
    border-color: rgba(255, 255, 255, 0.6);
    background: rgba(255, 255, 255, 0.08);
}

.hero-scroll-indicator {
    position: absolute;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.4);
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.7rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
}

.scroll-line {
    width: 1px;
    height: 40px;
    background: linear-gradient(to bottom, rgba(255,255,255,0.4), transparent);
    animation: scrollPulse 2s ease-in-out infinite;
}

/* ===== CONTENT SECTIONS ===== */
.content-section {
    padding: clamp(5rem, 10vw, 8rem) clamp(1.5rem, 5vw, 3rem);
    position: relative;
}

.dark-section {
    background: rgba(8, 15, 30, 0.5);
}

.content-container {
    max-width: 1200px;
    margin: 0 auto;
}

.section-header {
    text-align: center;
    margin-bottom: clamp(3rem, 6vw, 5rem);
}

.section-tag {
    display: inline-block;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    color: #60A5FA;
    margin-bottom: 1rem;
    padding: 0.4rem 1.2rem;
    border: 1px solid rgba(96, 165, 250, 0.25);
    border-radius: 50px;
}

.section-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: clamp(2.2rem, 5vw, 3.5rem);
    color: #ffffff;
    margin-bottom: 1.5rem;
    letter-spacing: -0.03em;
}

.section-desc {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: clamp(1rem, 1.8vw, 1.15rem);
    color: rgba(148, 163, 184, 0.9);
    line-height: 1.8;
    max-width: 700px;
    margin: 0 auto;
}

.section-desc strong {
    color: #60A5FA;
    font-weight: 600;
}

/* ===== STATS ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.stat-card {
    text-align: center;
    padding: 2.5rem 2rem;
    background: rgba(15, 23, 42, 0.5);
    border: 1px solid rgba(148, 163, 184, 0.1);
    border-radius: 16px;
    transition: all 0.3s ease;
}

.stat-card:hover {
    border-color: rgba(96, 165, 250, 0.25);
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.stat-number {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 3.5rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.stat-label {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1rem;
    font-weight: 600;
    color: #60A5FA;
    margin-bottom: 0.3rem;
}

.stat-desc {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.85rem;
    color: rgba(148, 163, 184, 0.6);
}

/* ===== FEATURE BLOCK ===== */
.feature-block {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
}

.feature-text .section-tag {
    margin-bottom: 1.5rem;
}

.feature-text .section-title {
    text-align: left;
}

.feature-text .section-desc {
    text-align: left;
    margin: 0 0 1.5rem 0;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.feature-list li {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.95rem;
    color: rgba(148, 163, 184, 0.8);
    padding: 0.6rem 0;
    padding-left: 1.5rem;
    position: relative;
}

.feature-list li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #60A5FA;
}

.feature-visual {
    display: flex;
    justify-content: center;
}

.map-preview {
    width: 100%;
    aspect-ratio: 4/3;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid rgba(148, 163, 184, 0.12);
    background: rgba(15, 23, 42, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
}

.map-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.map-preview-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(15,23,42,0.8), rgba(8,15,30,0.9));
}

/* ===== MISSION GRID ===== */
.mission-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.mission-card {
    padding: 2.5rem;
    background: rgba(15, 23, 42, 0.4);
    border: 1px solid rgba(148, 163, 184, 0.08);
    border-radius: 16px;
    transition: all 0.3s ease;
}

.mission-card:hover {
    border-color: rgba(96, 165, 250, 0.2);
    transform: translateY(-4px);
}

.mission-icon {
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: rgba(96, 165, 250, 0.08);
    border: 1px solid rgba(96, 165, 250, 0.15);
    margin-bottom: 1.5rem;
    color: #60A5FA;
}

.mission-card h3 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.75rem;
}

.mission-card p {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.95rem;
    color: rgba(148, 163, 184, 0.7);
    line-height: 1.7;
}

/* ===== CTA SECTION ===== */
.cta-section {
    padding: clamp(5rem, 10vw, 8rem) clamp(1.5rem, 5vw, 3rem);
    background: linear-gradient(180deg, transparent, rgba(96, 165, 250, 0.03), transparent);
}

.cta-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: clamp(2rem, 4vw, 3rem);
    color: #ffffff;
    margin-bottom: 1rem;
}

.cta-desc {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.1rem;
    color: rgba(148, 163, 184, 0.7);
    margin-bottom: 2.5rem;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
}

/* ===== CHARTS ===== */
.charts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.chart-card {
    background: rgba(15, 23, 42, 0.5);
    border: 1px solid rgba(148, 163, 184, 0.1);
    border-radius: 16px;
    padding: 2rem;
}

.chart-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 1.5rem;
    text-align: center;
}

/* ===== SCROLL ANIMATIONS ===== */
.fade-in {
    opacity: 0;
    transform: translateY(40px);
    transition: opacity 0.8s ease-out, transform 0.8s ease-out;
}

.fade-in.visible {
    opacity: 1;
    transform: translateY(0);
}

/* ===== ANIMATIONS ===== */
@keyframes heroFadeIn {
    0% { opacity: 0; transform: translateY(30px); }
    100% { opacity: 1; transform: translateY(0); }
}

@keyframes scrollPulse {
    0%, 100% { opacity: 0.4; transform: scaleY(1); }
    50% { opacity: 1; transform: scaleY(1.2); }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .feature-block {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .feature-visual {
        order: -1;
    }

    .hero-title-main,
    .hero-title-accent {
        font-size: clamp(3rem, 15vw, 5rem);
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    .mission-grid {
        grid-template-columns: 1fr;
    }

    .charts-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Leaflet for preview map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== Scroll fade-in =====
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.fade-in').forEach(function(el) {
        observer.observe(el);
    });

    // ===== Preview Map =====
    var pm = document.getElementById('previewMap');
    if (pm) {
        var previewMap = L.map(pm, {
            center: [-15.78, -47.93],
            zoom: 4,
            zoomControl: false,
            attributionControl: false,
            dragging: false,
            scrollWheelZoom: false,
            doubleClickZoom: false,
            touchZoom: false
        });
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(previewMap);

        fetch('/api/get_microplastics.php?limit=200')
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success && res.data) {
                    res.data.forEach(function(d) {
                        if (d.latitude && d.longitude) {
                            var color = d.concentration_value < 1000 ? '#00CC88' : d.concentration_value < 3000 ? '#FFD700' : d.concentration_value < 5000 ? '#FFA500' : d.concentration_value < 8000 ? '#FF6600' : '#CC0000';
                            L.circleMarker([d.latitude, d.longitude], {
                                radius: 4,
                                fillColor: color,
                                color: color,
                                fillOpacity: 0.7,
                                weight: 0
                            }).addTo(previewMap);
                        }
                    });

                    // Stats already rendered server-side
                }
            });
    }

    // ===== Charts =====
    fetch('/api/get_microplastics.php')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success || !res.data) return;
            var data = res.data;

            // Ecossistema chart
            var ecoCounts = {};
            data.forEach(function(d) {
                var eco = d.ecossistema || 'Outro';
                ecoCounts[eco] = (ecoCounts[eco] || 0) + 1;
            });
            var ecoLabels = Object.keys(ecoCounts).sort(function(a, b) { return ecoCounts[b] - ecoCounts[a]; });
            var ecoValues = ecoLabels.map(function(k) { return ecoCounts[k]; });

            var ctx1 = document.getElementById('chartEcossistema');
            if (ctx1) {
                new Chart(ctx1, {
                    type: 'doughnut',
                    data: {
                        labels: ecoLabels,
                        datasets: [{
                            data: ecoValues,
                            backgroundColor: ['#60A5FA', '#3B82F6', '#2563EB', '#1D4ED8', '#93C5FD', '#BFDBFE', '#7C3AED', '#A78BFA', '#34D399', '#FCD34D', '#FB923C'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: 'rgba(148,163,184,0.8)', font: { family: 'Plus Jakarta Sans', size: 11 }, padding: 12 }
                            }
                        }
                    }
                });
            }

            // Concentration chart
            var levels = { 'Baixa (0-1k)': 0, 'Media (1k-3k)': 0, 'Elevada (3k-5k)': 0, 'Alta (5k-8k)': 0, 'Critica (8k+)': 0 };
            data.forEach(function(d) {
                var v = d.concentration_value || 0;
                if (v < 1000) levels['Baixa (0-1k)']++;
                else if (v < 3000) levels['Media (1k-3k)']++;
                else if (v < 5000) levels['Elevada (3k-5k)']++;
                else if (v < 8000) levels['Alta (5k-8k)']++;
                else levels['Critica (8k+)']++;
            });

            var ctx2 = document.getElementById('chartConcentracao');
            if (ctx2) {
                new Chart(ctx2, {
                    type: 'bar',
                    data: {
                        labels: Object.keys(levels),
                        datasets: [{
                            data: Object.values(levels),
                            backgroundColor: ['#00CC88', '#FFD700', '#FFA500', '#FF6600', '#CC0000'],
                            borderRadius: 6,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { color: 'rgba(148,163,184,0.6)', font: { family: 'Plus Jakarta Sans' } },
                                grid: { color: 'rgba(148,163,184,0.08)' }
                            },
                            x: {
                                ticks: { color: 'rgba(148,163,184,0.6)', font: { family: 'Plus Jakarta Sans', size: 10 } },
                                grid: { display: false }
                            }
                        }
                    }
                });
            }
        });
});
</script>

<?php include 'includes/footer.php'; ?>
