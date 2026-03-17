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

require_once __DIR__ . '/config/cms.php';
$_homeBlocks = getBlocks('home');

$pageTitle = getSetting('site_name', 'GeoPlasticoBR') . " - Mapeamento de Microplasticos no Brasil";
$heroPage = true;
include 'includes/header.php';
?>

<!-- Grain Overlay -->
<div class="grain-overlay"></div>

<!-- Hero com Video de Fundo -->
<section class="hero-video-section">
    <div class="hero-video-wrapper">
        <video autoplay muted loop playsinline preload="metadata" class="hero-video" id="heroVideo">
            <source src="/videos/hero-bg.mp4" type="video/mp4">
        </video>
        <div class="hero-overlay"></div>
        <div class="hero-mesh"></div>
    </div>

    <div class="hero-content">
        <div class="hero-eyebrow" data-anim="fade" data-delay="0">
            <span class="eyebrow-line"></span>
            <span>Plataforma de Monitoramento Ambiental</span>
            <span class="eyebrow-line"></span>
        </div>
        <h1 class="hero-title" data-anim="fade" data-delay="1">
            <span class="hero-title-main">GEO</span>
            <span class="hero-title-main">PLASTICO</span>
            <span class="hero-title-accent">BR</span>
        </h1>
        <p class="hero-subtitle" data-anim="fade" data-delay="2"><?php echo htmlspecialchars($_homeBlocks['hero_subtitle'] ?? 'Mapeando a poluicao invisivel nos ecossistemas aquaticos brasileiros'); ?></p>
        <div class="hero-cta" data-anim="fade" data-delay="3">
            <a href="/mapa.php" class="hero-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon>
                    <line x1="8" y1="2" x2="8" y2="18"></line>
                    <line x1="16" y1="6" x2="16" y2="22"></line>
                </svg>
                <?php echo htmlspecialchars($_homeBlocks['hero_cta_primary'] ?? 'Explorar Mapa'); ?>
            </a>
            <a href="/sobre.php" class="hero-btn-secondary"><?php echo htmlspecialchars($_homeBlocks['hero_cta_secondary'] ?? 'Sobre o Projeto'); ?></a>
        </div>
    </div>

    <div class="hero-scroll-indicator" data-anim="fade" data-delay="4">
        <span>Scroll</span>
        <div class="scroll-line"></div>
    </div>
</section>

<!-- Secao: O Problema -->
<section class="content-section fade-in">
    <div class="section-divider">
        <span class="divider-number">01</span>
        <span class="divider-line"></span>
    </div>
    <div class="content-container">
        <div class="section-header">
            <span class="section-tag"><?php echo htmlspecialchars($_homeBlocks['problem_tag'] ?? 'O Problema'); ?></span>
            <h2 class="section-title"><?php echo htmlspecialchars($_homeBlocks['problem_title'] ?? 'Microplasticos'); ?></h2>
            <p class="section-desc">
                <?php echo htmlspecialchars($_homeBlocks['problem_description'] ?? 'Fragmentos de plastico menores que 5mm contaminam silenciosamente nossos oceanos, rios e lagos.'); ?>
            </p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-glow"></div>
                <div class="stat-number" data-count="<?php echo $statsPoints; ?>">0</div>
                <div class="stat-suffix">+</div>
                <div class="stat-label"><?php echo htmlspecialchars($_homeBlocks['stats_label_1'] ?? 'Pontos de Coleta'); ?></div>
                <div class="stat-desc"><?php echo htmlspecialchars($_homeBlocks['stats_desc_1'] ?? 'Mapeados em todo o Brasil'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-glow"></div>
                <div class="stat-number" data-count="<?php echo $statsEcosystems; ?>">0</div>
                <div class="stat-label"><?php echo htmlspecialchars($_homeBlocks['stats_label_2'] ?? 'Ecossistemas'); ?></div>
                <div class="stat-desc"><?php echo htmlspecialchars($_homeBlocks['stats_desc_2'] ?? 'Tipos de ambientes monitorados'); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-glow"></div>
                <div class="stat-number" data-count="<?php echo $statsRecords; ?>">0</div>
                <div class="stat-label"><?php echo htmlspecialchars($_homeBlocks['stats_label_3'] ?? 'Registros Cientificos'); ?></div>
                <div class="stat-desc"><?php echo htmlspecialchars($_homeBlocks['stats_desc_3'] ?? 'Dados verificados por pares'); ?></div>
            </div>
        </div>
    </div>
</section>

<!-- Secao: Ferramenta Principal -->
<section class="content-section dark-section fade-in">
    <div class="section-divider">
        <span class="divider-number">02</span>
        <span class="divider-line"></span>
    </div>
    <div class="content-container">
        <div class="feature-block">
            <div class="feature-text">
                <span class="section-tag"><?php echo htmlspecialchars($_homeBlocks['feature_tag'] ?? 'Ferramenta Principal'); ?></span>
                <h2 class="section-title"><?php echo htmlspecialchars($_homeBlocks['feature_title'] ?? 'Mapa Interativo'); ?></h2>
                <p class="section-desc">
                    Visualize em tempo real a distribuicao de microplasticos nos rios, lagos e oceanos brasileiros.
                    Navegue por centenas de pontos de coleta, alterne entre visualizacoes e acesse dados cientificos completos.
                </p>
                <ul class="feature-list">
                    <li>
                        <span class="feature-list-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 6v16l7-4 8 4 7-4V2l-7 4-8-4-7 4z"/></svg>
                        </span>
                        Multiplas visualizacoes de mapa
                    </li>
                    <li>
                        <span class="feature-list-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        </span>
                        Dados cientificos verificados
                    </li>
                    <li>
                        <span class="feature-list-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                        </span>
                        Atualizacao continua
                    </li>
                    <li>
                        <span class="feature-list-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        </span>
                        Filtros por ambiente e ecossistema
                    </li>
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
                    <div class="map-preview-chrome">
                        <span class="chrome-dot"></span>
                        <span class="chrome-dot"></span>
                        <span class="chrome-dot"></span>
                        <span class="chrome-url">geoplasticobr.com/mapa</span>
                    </div>
                    <div id="previewMap" style="width:100%;height:100%;"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Secao: Estatisticas -->
<section class="content-section dark-section fade-in" id="chartSection">
    <div class="section-divider">
        <span class="divider-number">03</span>
        <span class="divider-line"></span>
    </div>
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
    <div class="section-divider">
        <span class="divider-number">04</span>
        <span class="divider-line"></span>
    </div>
    <div class="content-container">
        <div class="section-header">
            <span class="section-tag">Nossa Missao</span>
            <h2 class="section-title"><?php echo htmlspecialchars($_homeBlocks['mission_title'] ?? 'Democratizar dados cientificos'); ?></h2>
        </div>

        <div class="mission-grid">
            <div class="mission-card">
                <div class="mission-card-number">01</div>
                <div class="mission-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <h3>Dados Verificados</h3>
                <p>Pesquisas cientificas revisadas por pares garantindo confiabilidade e precisao.</p>
            </div>
            <div class="mission-card">
                <div class="mission-card-number">02</div>
                <div class="mission-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                </div>
                <h3>Acesso Aberto</h3>
                <p>Plataforma gratuita para pesquisadores, estudantes e o publico em geral.</p>
            </div>
            <div class="mission-card">
                <div class="mission-card-number">03</div>
                <div class="mission-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
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
    <div class="cta-bg-effect"></div>
    <div class="content-container" style="text-align: center; position: relative; z-index: 2;">
        <h2 class="cta-title"><?php echo htmlspecialchars($_homeBlocks['cta_title'] ?? 'Pronto para Explorar?'); ?></h2>
        <p class="cta-desc">
            <?php echo htmlspecialchars($_homeBlocks['cta_description'] ?? 'Descubra a distribuicao de microplasticos nos ecossistemas aquaticos brasileiros.'); ?>
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
/* ===== CSS CUSTOM PROPERTIES ===== */
:root {
    --c-bg-deep: #030712;
    --c-bg-surface: rgba(8, 15, 30, 0.6);
    --c-bg-card: rgba(12, 22, 42, 0.55);
    --c-border: rgba(148, 163, 184, 0.08);
    --c-border-hover: rgba(34, 211, 238, 0.25);
    --c-accent: #22D3EE;
    --c-accent-dim: rgba(34, 211, 238, 0.12);
    --c-accent-glow: rgba(34, 211, 238, 0.3);
    --c-text: #ffffff;
    --c-text-secondary: rgba(148, 163, 184, 0.85);
    --c-text-muted: rgba(148, 163, 184, 0.5);
    --font-display: 'Syne', 'Plus Jakarta Sans', sans-serif;
    --font-body: 'Plus Jakarta Sans', sans-serif;
    --ease-out-expo: cubic-bezier(0.16, 1, 0.3, 1);
}

/* ===== GRAIN TEXTURE OVERLAY ===== */
.grain-overlay {
    position: fixed;
    inset: 0;
    z-index: 9998;
    pointer-events: none;
    opacity: 0.035;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
    background-repeat: repeat;
    background-size: 256px 256px;
}

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
    filter: saturate(0.7) brightness(0.8);
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 80% 60% at 50% 40%, transparent 0%, rgba(3, 7, 18, 0.4) 60%),
        linear-gradient(180deg,
            rgba(3, 7, 18, 0.2) 0%,
            rgba(3, 7, 18, 0.05) 30%,
            rgba(3, 7, 18, 0.3) 60%,
            rgba(3, 7, 18, 0.92) 100%
        );
}

.hero-mesh {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(circle at 20% 80%, rgba(34, 211, 238, 0.06) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(6, 182, 212, 0.04) 0%, transparent 50%);
    mix-blend-mode: screen;
}

.hero-content {
    position: relative;
    z-index: 10;
    text-align: center;
    padding: 0 2rem;
}

/* Hero Eyebrow */
.hero-eyebrow {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 2rem;
    font-family: var(--font-body);
    font-size: 0.72rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.2em;
    color: rgba(34, 211, 238, 0.7);
    opacity: 0;
    transform: translateY(20px);
}

.eyebrow-line {
    display: block;
    width: 40px;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--c-accent-glow), transparent);
}

/* Hero Title */
.hero-title {
    display: flex;
    flex-direction: column;
    align-items: center;
    line-height: 0.88;
    margin-bottom: 1.8rem;
    opacity: 0;
    transform: translateY(30px);
}

.hero-title-main {
    font-family: var(--font-display);
    font-weight: 800;
    font-size: clamp(4rem, 12vw, 10rem);
    color: #ffffff;
    letter-spacing: -0.04em;
    text-shadow: 0 4px 40px rgba(0, 0, 0, 0.5);
}

.hero-title-accent {
    font-family: var(--font-display);
    font-weight: 800;
    font-size: clamp(4rem, 12vw, 10rem);
    letter-spacing: -0.04em;
    background: linear-gradient(135deg, #22D3EE, #06B6D4, #0EA5E9);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    filter: drop-shadow(0 0 40px rgba(34, 211, 238, 0.3));
}

.hero-subtitle {
    font-family: var(--font-body);
    font-size: clamp(1rem, 2vw, 1.2rem);
    color: var(--c-text-secondary);
    font-weight: 400;
    max-width: 550px;
    margin: 0 auto 2.5rem;
    letter-spacing: 0.01em;
    line-height: 1.7;
    opacity: 0;
    transform: translateY(20px);
}

/* Hero CTAs */
.hero-cta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
    opacity: 0;
    transform: translateY(20px);
}

.hero-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.9rem 2rem;
    background: var(--c-accent);
    color: var(--c-bg-deep);
    font-family: var(--font-body);
    font-weight: 700;
    font-size: 0.9rem;
    border-radius: 50px;
    text-decoration: none;
    transition: all 0.4s var(--ease-out-expo);
    border: none;
    box-shadow: 0 0 0 0 var(--c-accent-glow);
}

.hero-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 30px var(--c-accent-glow), 0 0 60px rgba(34, 211, 238, 0.15);
}

.hero-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.9rem 2rem;
    background: transparent;
    color: var(--c-text-secondary);
    font-family: var(--font-body);
    font-weight: 500;
    font-size: 0.9rem;
    border-radius: 50px;
    text-decoration: none;
    border: 1px solid var(--c-border);
    transition: all 0.4s var(--ease-out-expo);
}

.hero-btn-secondary:hover {
    border-color: var(--c-border-hover);
    color: #ffffff;
    background: rgba(34, 211, 238, 0.05);
}

/* Hero Scroll */
.hero-scroll-indicator {
    position: absolute;
    bottom: 2.5rem;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    color: var(--c-text-muted);
    font-family: var(--font-body);
    font-size: 0.65rem;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    opacity: 0;
    transform: translate(-50%, 10px);
}

.scroll-line {
    width: 1px;
    height: 40px;
    background: linear-gradient(to bottom, var(--c-accent-glow), transparent);
    animation: scrollPulse 2.5s ease-in-out infinite;
}

/* ===== SECTION DIVIDER ===== */
.section-divider {
    display: flex;
    align-items: center;
    gap: 1rem;
    max-width: 1200px;
    margin: 0 auto 3rem;
    padding: 0 clamp(1.5rem, 5vw, 3rem);
}

.divider-number {
    font-family: var(--font-display);
    font-size: 0.7rem;
    font-weight: 700;
    color: var(--c-accent);
    letter-spacing: 0.1em;
    opacity: 0.6;
}

.divider-line {
    flex: 1;
    height: 1px;
    background: linear-gradient(90deg, var(--c-border), transparent);
}

/* ===== CONTENT SECTIONS ===== */
.content-section {
    padding: clamp(5rem, 10vw, 8rem) clamp(1.5rem, 5vw, 3rem);
    position: relative;
}

.dark-section {
    background: var(--c-bg-surface);
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
    font-family: var(--font-body);
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: var(--c-accent);
    margin-bottom: 1.2rem;
    padding: 0.35rem 1rem;
    border: 1px solid rgba(34, 211, 238, 0.2);
    border-radius: 50px;
    background: var(--c-accent-dim);
}

.section-title {
    font-family: var(--font-display);
    font-weight: 800;
    font-size: clamp(2.2rem, 5vw, 3.5rem);
    color: var(--c-text);
    margin-bottom: 1.5rem;
    letter-spacing: -0.03em;
    line-height: 1.1;
}

.section-desc {
    font-family: var(--font-body);
    font-size: clamp(0.95rem, 1.6vw, 1.1rem);
    color: var(--c-text-secondary);
    line-height: 1.8;
    max-width: 650px;
    margin: 0 auto;
}

/* ===== STATS ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

.stat-card {
    position: relative;
    text-align: center;
    padding: 2.5rem 2rem;
    background: var(--c-bg-card);
    border: 1px solid var(--c-border);
    border-radius: 20px;
    transition: all 0.5s var(--ease-out-expo);
    overflow: hidden;
}

.stat-glow {
    position: absolute;
    top: -40%;
    left: 50%;
    transform: translateX(-50%);
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, var(--c-accent-glow), transparent 70%);
    opacity: 0;
    transition: opacity 0.5s ease;
    pointer-events: none;
}

.stat-card:hover {
    border-color: var(--c-border-hover);
    transform: translateY(-6px);
}

.stat-card:hover .stat-glow {
    opacity: 0.15;
}

.stat-number {
    font-family: var(--font-display);
    font-size: clamp(2.8rem, 5vw, 3.8rem);
    font-weight: 800;
    color: var(--c-text);
    line-height: 1;
    display: inline;
}

.stat-suffix {
    font-family: var(--font-display);
    font-size: clamp(2rem, 3vw, 2.5rem);
    font-weight: 700;
    color: var(--c-accent);
    display: inline;
}

.stat-label {
    font-family: var(--font-body);
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--c-accent);
    margin-top: 0.8rem;
    margin-bottom: 0.3rem;
}

.stat-desc {
    font-family: var(--font-body);
    font-size: 0.82rem;
    color: var(--c-text-muted);
}

/* ===== FEATURE BLOCK ===== */
.feature-block {
    display: grid;
    grid-template-columns: 1fr 1.1fr;
    gap: 4rem;
    align-items: center;
}

.feature-text .section-tag { margin-bottom: 1.5rem; }
.feature-text .section-title { text-align: left; }
.feature-text .section-desc { text-align: left; margin: 0 0 1.5rem 0; }

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.feature-list li {
    font-family: var(--font-body);
    font-size: 0.9rem;
    color: var(--c-text-secondary);
    padding: 0.7rem 0;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    transition: color 0.3s;
}

.feature-list li:hover {
    color: var(--c-text);
}

.feature-list-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: var(--c-accent-dim);
    color: var(--c-accent);
    flex-shrink: 0;
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
    border: 1px solid var(--c-border);
    background: var(--c-bg-card);
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(34, 211, 238, 0.05);
    transition: box-shadow 0.4s ease;
}

.map-preview:hover {
    box-shadow: 0 25px 70px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(34, 211, 238, 0.12);
}

.map-preview-chrome {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 14px;
    background: rgba(15, 23, 42, 0.8);
    border-bottom: 1px solid var(--c-border);
}

.chrome-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(148, 163, 184, 0.2);
}

.chrome-dot:nth-child(1) { background: rgba(239, 68, 68, 0.5); }
.chrome-dot:nth-child(2) { background: rgba(234, 179, 8, 0.5); }
.chrome-dot:nth-child(3) { background: rgba(34, 197, 94, 0.5); }

.chrome-url {
    margin-left: 10px;
    font-family: var(--font-body);
    font-size: 0.7rem;
    color: var(--c-text-muted);
    background: rgba(148, 163, 184, 0.05);
    padding: 3px 10px;
    border-radius: 4px;
}

/* ===== MISSION GRID ===== */
.mission-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

.mission-card {
    position: relative;
    padding: 2.5rem;
    background: var(--c-bg-card);
    border: 1px solid var(--c-border);
    border-radius: 20px;
    transition: all 0.5s var(--ease-out-expo);
    overflow: hidden;
}

.mission-card:hover {
    border-color: var(--c-border-hover);
    transform: translateY(-4px);
}

.mission-card-number {
    position: absolute;
    top: 1.2rem;
    right: 1.5rem;
    font-family: var(--font-display);
    font-size: 0.7rem;
    font-weight: 700;
    color: rgba(34, 211, 238, 0.15);
    letter-spacing: 0.05em;
}

.mission-icon {
    width: 52px;
    height: 52px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: var(--c-accent-dim);
    border: 1px solid rgba(34, 211, 238, 0.12);
    margin-bottom: 1.5rem;
    color: var(--c-accent);
    transition: all 0.4s var(--ease-out-expo);
}

.mission-card:hover .mission-icon {
    background: rgba(34, 211, 238, 0.18);
    box-shadow: 0 0 20px rgba(34, 211, 238, 0.1);
}

.mission-card h3 {
    font-family: var(--font-display);
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--c-text);
    margin-bottom: 0.75rem;
}

.mission-card p {
    font-family: var(--font-body);
    font-size: 0.9rem;
    color: var(--c-text-muted);
    line-height: 1.7;
}

/* ===== CTA SECTION ===== */
.cta-section {
    position: relative;
    padding: clamp(6rem, 12vw, 10rem) clamp(1.5rem, 5vw, 3rem);
    overflow: hidden;
}

.cta-bg-effect {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 70% 50% at 50% 50%, rgba(34, 211, 238, 0.04) 0%, transparent 70%);
    pointer-events: none;
}

.cta-title {
    font-family: var(--font-display);
    font-weight: 800;
    font-size: clamp(2rem, 4vw, 3rem);
    color: var(--c-text);
    margin-bottom: 1rem;
    letter-spacing: -0.03em;
}

.cta-desc {
    font-family: var(--font-body);
    font-size: 1.05rem;
    color: var(--c-text-muted);
    margin-bottom: 2.5rem;
    max-width: 480px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.7;
}

/* ===== CHARTS ===== */
.charts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.chart-card {
    background: var(--c-bg-card);
    border: 1px solid var(--c-border);
    border-radius: 20px;
    padding: 2rem;
    transition: border-color 0.4s ease;
}

.chart-card:hover {
    border-color: var(--c-border-hover);
}

.chart-title {
    font-family: var(--font-display);
    font-size: 0.95rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.75);
    margin-bottom: 1.5rem;
    text-align: center;
    letter-spacing: -0.01em;
}

/* ===== SCROLL ANIMATIONS ===== */
.fade-in {
    opacity: 0;
    transform: translateY(40px);
    transition: opacity 0.9s var(--ease-out-expo), transform 0.9s var(--ease-out-expo);
}

.fade-in.visible {
    opacity: 1;
    transform: translateY(0);
}

/* ===== HERO STAGGERED ANIMATIONS ===== */
[data-anim="fade"] {
    opacity: 0;
    transform: translateY(25px);
}

.hero-anim-active [data-anim="fade"][data-delay="0"] { animation: heroReveal 1s var(--ease-out-expo) 0.2s forwards; }
.hero-anim-active [data-anim="fade"][data-delay="1"] { animation: heroReveal 1s var(--ease-out-expo) 0.4s forwards; }
.hero-anim-active [data-anim="fade"][data-delay="2"] { animation: heroReveal 1s var(--ease-out-expo) 0.7s forwards; }
.hero-anim-active [data-anim="fade"][data-delay="3"] { animation: heroReveal 1.2s var(--ease-out-expo) 1s forwards; }
.hero-anim-active [data-anim="fade"][data-delay="4"] { animation: heroReveal 1s var(--ease-out-expo) 1.5s forwards; }

@keyframes heroReveal {
    0% { opacity: 0; transform: translateY(25px); }
    100% { opacity: 1; transform: translateY(0); }
}

@keyframes scrollPulse {
    0%, 100% { opacity: 0.3; transform: scaleY(1); }
    50% { opacity: 0.8; transform: scaleY(1.15); }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 900px) {
    .feature-block {
        grid-template-columns: 1fr;
        gap: 2.5rem;
    }
    .feature-visual { order: -1; }
    .feature-text .section-title,
    .feature-text .section-desc { text-align: center; }
    .feature-text { display: flex; flex-direction: column; align-items: center; }
    .feature-list { width: 100%; max-width: 400px; }
    .stats-grid { grid-template-columns: 1fr; }
    .mission-grid { grid-template-columns: 1fr; }
    .charts-grid { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
    .hero-title-main,
    .hero-title-accent {
        font-size: clamp(3rem, 15vw, 5rem);
    }
    .hero-eyebrow { font-size: 0.6rem; gap: 0.6rem; }
    .eyebrow-line { width: 24px; }
    .section-divider { margin-bottom: 2rem; }
}

@media (min-width: 901px) and (max-width: 1100px) {
    .stats-grid { grid-template-columns: repeat(3, 1fr); gap: 1rem; }
    .mission-grid { grid-template-columns: repeat(3, 1fr); gap: 1rem; }
}
</style>

<!-- Google Fonts: Syne (display) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&display=swap" rel="stylesheet">

<!-- Leaflet for preview map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ===== Hero staggered animation trigger =====
    requestAnimationFrame(function() {
        document.querySelector('.hero-video-section').classList.add('hero-anim-active');
    });

    // ===== Scroll fade-in =====
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');

                // Animate stat counters when stats section appears
                entry.target.querySelectorAll('.stat-number[data-count]').forEach(function(el) {
                    animateCounter(el);
                });
            }
        });
    }, { threshold: 0.15 });

    document.querySelectorAll('.fade-in').forEach(function(el) {
        observer.observe(el);
    });

    // ===== Counter Animation =====
    function animateCounter(el) {
        var target = parseInt(el.getAttribute('data-count'), 10);
        if (isNaN(target) || el.dataset.animated) return;
        el.dataset.animated = '1';
        var duration = 1800;
        var start = performance.now();
        function step(now) {
            var elapsed = now - start;
            var progress = Math.min(elapsed / duration, 1);
            // ease-out-expo
            var eased = 1 - Math.pow(1 - progress, 4);
            el.textContent = Math.round(target * eased);
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = target;
        }
        requestAnimationFrame(step);
    }

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
                    var thresholds = res.thresholds && res.thresholds.length > 0 ? res.thresholds[0].thresholds : null;
                    function getPreviewColor(val) {
                        if (thresholds) {
                            for (var i = 0; i < thresholds.length; i++) {
                                var t = thresholds[i];
                                var min = parseFloat(t.min_value);
                                var max = t.max_value !== null ? parseFloat(t.max_value) : Infinity;
                                if (val >= min && val < max) return t.color;
                            }
                            return '#6b7280';
                        }
                        return val < 1000 ? '#00CC88' : val < 3000 ? '#FFD700' : val < 5000 ? '#FFA500' : val < 8000 ? '#FF6600' : '#CC0000';
                    }
                    res.data.forEach(function(d) {
                        if (d.latitude && d.longitude) {
                            var color = getPreviewColor(d.concentration_value);
                            L.circleMarker([d.latitude, d.longitude], {
                                radius: 4,
                                fillColor: color,
                                color: color,
                                fillOpacity: 0.7,
                                weight: 0
                            }).addTo(previewMap);
                        }
                    });
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
                            backgroundColor: ['#22D3EE', '#06B6D4', '#0891B2', '#0E7490', '#67E8F9', '#A5F3FC', '#7C3AED', '#A78BFA', '#34D399', '#FCD34D', '#FB923C'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: 'rgba(148,163,184,0.75)', font: { family: 'Plus Jakarta Sans', size: 11 }, padding: 14, usePointStyle: true, pointStyleWidth: 8 }
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
                            borderRadius: 8,
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
                                ticks: { color: 'rgba(148,163,184,0.5)', font: { family: 'Plus Jakarta Sans' } },
                                grid: { color: 'rgba(148,163,184,0.06)' }
                            },
                            x: {
                                ticks: { color: 'rgba(148,163,184,0.5)', font: { family: 'Plus Jakarta Sans', size: 10 } },
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
