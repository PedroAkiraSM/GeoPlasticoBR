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

<!-- ============ HERO SECTION ============ -->
<section class="hp-hero">
    <div class="hp-hero__video-wrap">
        <video autoplay muted loop playsinline preload="metadata" class="hp-hero__video" id="heroVideo">
            <source src="/videos/hero-bg.mp4" type="video/mp4">
        </video>
        <div class="hp-hero__overlay"></div>
        <div class="hp-hero__color-wash"></div>
        <div class="hp-hero__vignette"></div>
    </div>

    <!-- Decorative grid lines -->
    <div class="hp-hero__grid-lines">
        <span></span><span></span><span></span><span></span><span></span>
    </div>

    <!-- Main hero content - left aligned like reference -->
    <div class="hp-hero__inner">
        <div class="hp-hero__content" data-anim="stagger">
            <div class="hp-hero__badge">
                <span class="hp-hero__badge-dot"></span>
                Monitoramento Ambiental em Tempo Real
            </div>
            <h1 class="hp-hero__title">
                <span class="hp-hero__title-line">MAPEANDO A</span>
                <span class="hp-hero__title-line hp-hero__title-line--accent">POLUICAO</span>
                <span class="hp-hero__title-line">INVISIVEL</span>
            </h1>
            <p class="hp-hero__desc"><?php echo htmlspecialchars($_homeBlocks['hero_subtitle'] ?? 'Mapeando a poluicao invisivel nos ecossistemas aquaticos brasileiros'); ?></p>
            <div class="hp-hero__actions">
                <a href="/mapa.php" class="hp-btn hp-btn--primary">
                    <span><?php echo htmlspecialchars($_homeBlocks['hero_cta_primary'] ?? 'Explorar Mapa'); ?></span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
                <a href="/sobre.php" class="hp-btn hp-btn--ghost"><?php echo htmlspecialchars($_homeBlocks['hero_cta_secondary'] ?? 'Sobre o Projeto'); ?></a>
            </div>
        </div>

        <!-- Floating card overlay - like Targo's consultation card -->
        <div class="hp-hero__float-card" data-anim="float">
            <div class="hp-hero__float-card-header">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                <span>Dados ao Vivo</span>
            </div>
            <div class="hp-hero__float-stats">
                <div class="hp-hero__float-stat">
                    <span class="hp-hero__float-stat-num" data-count="<?php echo $statsPoints; ?>">0</span>
                    <span class="hp-hero__float-stat-label"><?php echo htmlspecialchars($_homeBlocks['stats_label_1'] ?? 'Pontos Mapeados'); ?></span>
                </div>
                <div class="hp-hero__float-divider"></div>
                <div class="hp-hero__float-stat">
                    <span class="hp-hero__float-stat-num" data-count="<?php echo $statsEcosystems; ?>">0</span>
                    <span class="hp-hero__float-stat-label"><?php echo htmlspecialchars($_homeBlocks['stats_label_2'] ?? 'Ecossistemas'); ?></span>
                </div>
                <div class="hp-hero__float-divider"></div>
                <div class="hp-hero__float-stat">
                    <span class="hp-hero__float-stat-num" data-count="<?php echo $statsRecords; ?>">0</span>
                    <span class="hp-hero__float-stat-label"><?php echo htmlspecialchars($_homeBlocks['stats_label_3'] ?? 'Registros'); ?></span>
                </div>
            </div>
            <a href="/mapa.php" class="hp-hero__float-cta">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                Abrir Mapa Interativo
            </a>
        </div>
    </div>

    <!-- Bottom scroll hint -->
    <div class="hp-hero__scroll">
        <div class="hp-hero__scroll-line"></div>
        <span>SCROLL</span>
    </div>
</section>

<!-- ============ PROBLEM SECTION ============ -->
<div class="hp-divider">
    <div class="hp-divider__inner">
        <span class="hp-divider__num">01</span>
        <span class="hp-divider__line"></span>
        <span class="hp-divider__label">O Problema</span>
        <span class="hp-divider__line"></span>
    </div>
</div>
<section class="hp-section hp-section--problem fade-in">
    <div class="hp-container">
        <div class="hp-problem">
            <div class="hp-problem__text">
                <span class="hp-tag"><?php echo htmlspecialchars($_homeBlocks['problem_tag'] ?? 'O Problema'); ?></span>
                <h2 class="hp-heading"><?php echo htmlspecialchars($_homeBlocks['problem_title'] ?? 'Microplasticos estao em toda parte'); ?></h2>
                <p class="hp-text">
                    <?php echo htmlspecialchars($_homeBlocks['problem_description'] ?? 'Fragmentos de plastico menores que 5mm contaminam silenciosamente nossos oceanos, rios e lagos. Uma crise ambiental invisivel que afeta toda a cadeia alimentar.'); ?>
                </p>
            </div>
            <div class="hp-problem__stats">
                <div class="hp-stat-block">
                    <div class="hp-stat-block__number" data-count="<?php echo $statsPoints; ?>">0</div>
                    <div class="hp-stat-block__plus">+</div>
                    <div class="hp-stat-block__label"><?php echo htmlspecialchars($_homeBlocks['stats_label_1'] ?? 'Pontos de Coleta'); ?></div>
                    <div class="hp-stat-block__sub"><?php echo htmlspecialchars($_homeBlocks['stats_desc_1'] ?? 'Mapeados em todo o Brasil'); ?></div>
                    <div class="hp-stat-block__bar"></div>
                </div>
                <div class="hp-stat-block">
                    <div class="hp-stat-block__number" data-count="<?php echo $statsEcosystems; ?>">0</div>
                    <div class="hp-stat-block__label"><?php echo htmlspecialchars($_homeBlocks['stats_label_2'] ?? 'Ecossistemas'); ?></div>
                    <div class="hp-stat-block__sub"><?php echo htmlspecialchars($_homeBlocks['stats_desc_2'] ?? 'Tipos de ambientes monitorados'); ?></div>
                    <div class="hp-stat-block__bar"></div>
                </div>
                <div class="hp-stat-block">
                    <div class="hp-stat-block__number" data-count="<?php echo $statsRecords; ?>">0</div>
                    <div class="hp-stat-block__label"><?php echo htmlspecialchars($_homeBlocks['stats_label_3'] ?? 'Registros Cientificos'); ?></div>
                    <div class="hp-stat-block__sub"><?php echo htmlspecialchars($_homeBlocks['stats_desc_3'] ?? 'Dados verificados por pares'); ?></div>
                    <div class="hp-stat-block__bar"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ MAP FEATURE SECTION ============ -->
<div class="hp-divider">
    <div class="hp-divider__inner">
        <span class="hp-divider__num">02</span>
        <span class="hp-divider__line"></span>
        <span class="hp-divider__label">Ferramenta</span>
        <span class="hp-divider__line"></span>
    </div>
</div>
<section class="hp-section hp-section--map fade-in">
    <div class="hp-section__bg-glow"></div>
    <div class="hp-container">
        <div class="hp-map-feature">
            <div class="hp-map-feature__info">
                <span class="hp-tag"><?php echo htmlspecialchars($_homeBlocks['feature_tag'] ?? 'Ferramenta Principal'); ?></span>
                <h2 class="hp-heading"><?php echo htmlspecialchars($_homeBlocks['feature_title'] ?? 'Mapa Interativo'); ?></h2>
                <p class="hp-text">
                    Visualize em tempo real a distribuicao de microplasticos nos rios, lagos e oceanos brasileiros.
                </p>
                <div class="hp-features-list">
                    <div class="hp-features-list__item">
                        <div class="hp-features-list__icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 6v16l7-4 8 4 7-4V2l-7 4-8-4-7 4z"/></svg>
                        </div>
                        <div>
                            <strong>Multiplas Visualizacoes</strong>
                            <span>Markers, clusters e heatmap</span>
                        </div>
                    </div>
                    <div class="hp-features-list__item">
                        <div class="hp-features-list__icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        </div>
                        <div>
                            <strong>Dados Verificados</strong>
                            <span>Revisados por pares</span>
                        </div>
                    </div>
                    <div class="hp-features-list__item">
                        <div class="hp-features-list__icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        </div>
                        <div>
                            <strong>Filtros Avancados</strong>
                            <span>Ambiente, ecossistema, concentracao</span>
                        </div>
                    </div>
                </div>
                <a href="/mapa.php" class="hp-btn hp-btn--primary" style="margin-top: 1.5rem;">
                    <span>Acessar Mapa</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
            <div class="hp-map-feature__preview">
                <div class="hp-map-browser">
                    <div class="hp-map-browser__bar">
                        <div class="hp-map-browser__dots">
                            <span style="background:#FF5F57"></span>
                            <span style="background:#FFBD2E"></span>
                            <span style="background:#28CA41"></span>
                        </div>
                        <div class="hp-map-browser__url">geoplasticobr.com/mapa</div>
                    </div>
                    <div class="hp-map-browser__content">
                        <div id="previewMap" style="width:100%;height:100%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ CHARTS SECTION ============ -->
<div class="hp-divider">
    <div class="hp-divider__inner">
        <span class="hp-divider__num">03</span>
        <span class="hp-divider__line"></span>
        <span class="hp-divider__label">Dados</span>
        <span class="hp-divider__line"></span>
    </div>
</div>
<section class="hp-section hp-section--charts fade-in" id="chartSection">
    <div class="hp-container">
        <div class="hp-section__header">
            <span class="hp-tag">Dados</span>
            <h2 class="hp-heading">Estatisticas do Mapeamento</h2>
            <p class="hp-text" style="text-align:center;">Distribuicao de registros por ecossistema e niveis de concentracao.</p>
        </div>
        <div class="hp-charts">
            <div class="hp-chart-card">
                <h3 class="hp-chart-card__title">Registros por Ecossistema</h3>
                <canvas id="chartEcossistema"></canvas>
            </div>
            <div class="hp-chart-card">
                <h3 class="hp-chart-card__title">Niveis de Concentracao</h3>
                <canvas id="chartConcentracao"></canvas>
            </div>
        </div>
    </div>
</section>

<!-- ============ MISSION SECTION ============ -->
<div class="hp-divider">
    <div class="hp-divider__inner">
        <span class="hp-divider__num">04</span>
        <span class="hp-divider__line"></span>
        <span class="hp-divider__label">Missao</span>
        <span class="hp-divider__line"></span>
    </div>
</div>
<section class="hp-section hp-section--mission fade-in">
    <div class="hp-container">
        <div class="hp-section__header">
            <span class="hp-tag">Nossa Missao</span>
            <h2 class="hp-heading"><?php echo htmlspecialchars($_homeBlocks['mission_title'] ?? 'Democratizar dados cientificos'); ?></h2>
        </div>
        <div class="hp-mission-cards">
            <div class="hp-mission-card">
                <div class="hp-mission-card__index">01</div>
                <div class="hp-mission-card__icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                </div>
                <h3>Dados Verificados</h3>
                <p>Pesquisas cientificas revisadas por pares garantindo confiabilidade e precisao.</p>
            </div>
            <div class="hp-mission-card">
                <div class="hp-mission-card__index">02</div>
                <div class="hp-mission-card__icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                </div>
                <h3>Acesso Aberto</h3>
                <p>Plataforma gratuita para pesquisadores, estudantes e o publico em geral.</p>
            </div>
            <div class="hp-mission-card">
                <div class="hp-mission-card__index">03</div>
                <div class="hp-mission-card__icon">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
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

<!-- ============ CTA FINAL ============ -->
<section class="hp-cta fade-in">
    <div class="hp-cta__glow"></div>
    <div class="hp-container hp-cta__inner">
        <h2 class="hp-cta__title"><?php echo htmlspecialchars($_homeBlocks['cta_title'] ?? 'Pronto para Explorar?'); ?></h2>
        <p class="hp-cta__desc">
            <?php echo htmlspecialchars($_homeBlocks['cta_description'] ?? 'Descubra a distribuicao de microplasticos nos ecossistemas aquaticos brasileiros.'); ?>
        </p>
        <div class="hp-cta__actions">
            <a href="/mapa.php" class="hp-btn hp-btn--primary hp-btn--lg">
                <span>Acessar Mapa Agora</span>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
            <a href="/contribuir.php" class="hp-btn hp-btn--ghost">Contribuir com Dados</a>
        </div>
    </div>
</section>

<!-- ============ STYLES ============ -->
<style>
/* === VARIABLES === */
:root {
    --hp-bg: #050b14;
    --hp-surface: rgba(5, 11, 20, 0.95);
    --hp-card: rgba(8, 16, 30, 0.8);
    --hp-border: rgba(34, 211, 238, 0.08);
    --hp-border-strong: rgba(34, 211, 238, 0.2);
    --hp-cyan: #22D3EE;
    --hp-cyan-bright: #67E8F9;
    --hp-cyan-dim: rgba(34, 211, 238, 0.1);
    --hp-cyan-glow: rgba(34, 211, 238, 0.35);
    --hp-teal: #14B8A6;
    --hp-white: #f0f9ff;
    --hp-gray: rgba(148, 163, 184, 0.75);
    --hp-muted: rgba(148, 163, 184, 0.45);
    --hp-font: 'Instrument Sans', 'Plus Jakarta Sans', sans-serif;
    --hp-font-display: 'Bebas Neue', 'Impact', sans-serif;
    --hp-ease: cubic-bezier(0.16, 1, 0.3, 1);
    --hp-radius: 16px;
}

/* === OVERRIDE BODY GRADIENT === */
body {
    background: #050b14 !important;
    background-image: none !important;
}
.light-rays { display: none !important; }

/* === HERO === */
.hp-hero {
    position: relative;
    width: 100%;
    min-height: 100vh;
    display: flex;
    align-items: stretch;
    overflow: hidden;
    background: var(--hp-bg);
}

.hp-hero__video-wrap {
    position: absolute;
    inset: 0;
}

.hp-hero__video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: brightness(0.55) saturate(0.8) contrast(1.1);
}

.hp-hero__overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg,
        rgba(3, 10, 18, 0.85) 0%,
        rgba(3, 10, 18, 0.4) 40%,
        rgba(3, 10, 18, 0.2) 60%,
        rgba(3, 10, 18, 0.7) 100%
    );
}

.hp-hero__color-wash {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 40% 60% at 0% 100%, rgba(34, 211, 238, 0.06) 0%, transparent 50%),
        radial-gradient(ellipse 30% 40% at 85% 15%, rgba(20, 184, 166, 0.04) 0%, transparent 50%);
    mix-blend-mode: screen;
}

.hp-hero__vignette {
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, transparent 60%, #050b14 100%);
}

/* Decorative grid */
.hp-hero__grid-lines {
    position: absolute;
    inset: 0;
    display: flex;
    justify-content: space-evenly;
    pointer-events: none;
    z-index: 1;
}
.hp-hero__grid-lines span {
    width: 1px;
    height: 100%;
    background: linear-gradient(to bottom, transparent, rgba(255, 255, 255, 0.03) 30%, rgba(255, 255, 255, 0.03) 70%, transparent);
}

/* Hero inner layout */
.hp-hero__inner {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 1400px;
    margin: 0 auto;
    padding: 140px 48px 80px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 60px;
    min-height: 100vh;
}

/* Hero content - LEFT side */
.hp-hero__content {
    flex: 1;
    max-width: 680px;
}

.hp-hero__badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    background: var(--hp-cyan-dim);
    border: 1px solid var(--hp-border-strong);
    border-radius: 50px;
    font-family: var(--hp-font);
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--hp-cyan);
    text-transform: uppercase;
    letter-spacing: 0.12em;
    margin-bottom: 28px;
    opacity: 0;
    transform: translateY(20px);
}

.hp-hero__badge-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--hp-cyan);
    animation: dotPulse 2s ease-in-out infinite;
}

.hp-hero__title {
    margin-bottom: 24px;
}

.hp-hero__title-line {
    display: block;
    font-family: var(--hp-font-display);
    font-weight: 400;
    font-size: clamp(3.5rem, 8vw, 7rem);
    line-height: 0.95;
    letter-spacing: 0.02em;
    color: var(--hp-white);
    opacity: 0;
    transform: translateY(40px);
}

.hp-hero__title-line--accent {
    background: linear-gradient(90deg, var(--hp-cyan), var(--hp-cyan-bright), var(--hp-teal));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    filter: drop-shadow(0 0 30px rgba(34, 211, 238, 0.25));
}

.hp-hero__desc {
    font-family: var(--hp-font);
    font-size: 1.05rem;
    color: var(--hp-gray);
    line-height: 1.7;
    max-width: 480px;
    margin-bottom: 32px;
    opacity: 0;
    transform: translateY(20px);
}

.hp-hero__actions {
    display: flex;
    gap: 14px;
    flex-wrap: wrap;
    opacity: 0;
    transform: translateY(20px);
}

/* Buttons */
.hp-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 28px;
    font-family: var(--hp-font);
    font-weight: 700;
    font-size: 0.88rem;
    text-decoration: none;
    border-radius: 50px;
    transition: all 0.4s var(--hp-ease);
    cursor: pointer;
    border: none;
}

.hp-btn--primary {
    background: var(--hp-cyan);
    color: var(--hp-bg);
}
.hp-btn--primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 25px var(--hp-cyan-glow), 0 0 80px rgba(34, 211, 238, 0.12);
}

.hp-btn--lg { padding: 15px 36px; font-size: 0.95rem; }

.hp-btn--ghost {
    background: transparent;
    color: var(--hp-gray);
    border: 1px solid var(--hp-border);
}
.hp-btn--ghost:hover {
    border-color: var(--hp-border-strong);
    color: var(--hp-white);
    background: var(--hp-cyan-dim);
}

/* Floating card - RIGHT side */
.hp-hero__float-card {
    width: 340px;
    flex-shrink: 0;
    background: rgba(6, 18, 34, 0.7);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid var(--hp-border-strong);
    border-radius: 20px;
    padding: 24px;
    opacity: 0;
    transform: translateY(30px) translateX(10px);
    box-shadow: 0 24px 80px rgba(0, 0, 0, 0.5), 0 0 1px rgba(34, 211, 238, 0.2);
}

.hp-hero__float-card-header {
    display: flex;
    align-items: center;
    gap: 10px;
    color: var(--hp-cyan);
    font-family: var(--hp-font);
    font-size: 0.82rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding-bottom: 18px;
    border-bottom: 1px solid var(--hp-border);
    margin-bottom: 20px;
}

.hp-hero__float-stats {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
}

.hp-hero__float-stat {
    flex: 1;
    text-align: center;
}

.hp-hero__float-stat-num {
    display: block;
    font-family: var(--hp-font-display);
    font-size: 2.2rem;
    color: var(--hp-white);
    line-height: 1;
    letter-spacing: 0.02em;
}

.hp-hero__float-stat-label {
    display: block;
    font-family: var(--hp-font);
    font-size: 0.68rem;
    color: var(--hp-muted);
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.hp-hero__float-divider {
    width: 1px;
    height: 40px;
    background: var(--hp-border);
}

.hp-hero__float-cta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 12px;
    background: var(--hp-cyan-dim);
    border: 1px solid var(--hp-border-strong);
    border-radius: 12px;
    color: var(--hp-cyan);
    font-family: var(--hp-font);
    font-size: 0.82rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s var(--hp-ease);
}
.hp-hero__float-cta:hover {
    background: rgba(34, 211, 238, 0.18);
    transform: translateY(-1px);
}

/* Hero scroll */
.hp-hero__scroll {
    position: absolute;
    bottom: 32px;
    left: 48px;
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 12px;
    font-family: var(--hp-font);
    font-size: 0.6rem;
    font-weight: 600;
    letter-spacing: 0.25em;
    color: var(--hp-muted);
    text-transform: uppercase;
}

.hp-hero__scroll-line {
    width: 48px;
    height: 1px;
    background: linear-gradient(90deg, var(--hp-cyan-glow), transparent);
    animation: scrollLineAnim 2s ease-in-out infinite;
}

/* === SECTION DIVIDERS === */
.hp-divider {
    padding: 0 48px;
    max-width: 1280px;
    margin: 0 auto;
}

.hp-divider__inner {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 0;
}

.hp-divider__num {
    font-family: var(--hp-font-display);
    font-size: 1.1rem;
    color: var(--hp-cyan);
    letter-spacing: 0.05em;
    flex-shrink: 0;
    opacity: 0.7;
}

.hp-divider__line {
    flex: 1;
    height: 1px;
    background: rgba(148, 163, 184, 0.1);
}

.hp-divider__label {
    font-family: var(--hp-font);
    font-size: 0.65rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.2em;
    color: var(--hp-muted);
    flex-shrink: 0;
}

/* === GLOBAL SECTION STYLES === */
.hp-section {
    position: relative;
    padding: clamp(60px, 8vw, 100px) 0;
}

.hp-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 48px;
}

.hp-section__header {
    text-align: center;
    margin-bottom: clamp(48px, 6vw, 72px);
}

.hp-section__bg-glow {
    display: none;
}

.hp-section--charts {
    background: rgba(8, 14, 24, 0.95);
    border-top: 1px solid rgba(148, 163, 184, 0.06);
    border-bottom: 1px solid rgba(148, 163, 184, 0.06);
}

.hp-tag {
    display: inline-block;
    padding: 5px 14px;
    background: var(--hp-cyan-dim);
    border: 1px solid var(--hp-border-strong);
    border-radius: 50px;
    font-family: var(--hp-font);
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    color: var(--hp-cyan);
    margin-bottom: 20px;
}

.hp-heading {
    font-family: var(--hp-font-display);
    font-weight: 400;
    font-size: clamp(2rem, 4vw, 3.2rem);
    color: var(--hp-white);
    letter-spacing: 0.02em;
    margin-bottom: 20px;
    line-height: 1.1;
}

.hp-text {
    font-family: var(--hp-font);
    font-size: 1rem;
    color: var(--hp-gray);
    line-height: 1.8;
    max-width: 560px;
}

/* === PROBLEM SECTION === */
.hp-problem {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 64px;
    align-items: start;
}

.hp-problem__text { padding-top: 16px; }

.hp-problem__stats {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.hp-stat-block {
    position: relative;
    padding: 28px 32px;
    background: var(--hp-card);
    border: 1px solid var(--hp-border);
    border-radius: var(--hp-radius);
    transition: all 0.4s var(--hp-ease);
    overflow: hidden;
}

.hp-stat-block:hover {
    border-color: var(--hp-border-strong);
    transform: translateX(6px);
}

.hp-stat-block__bar {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(to bottom, var(--hp-cyan), transparent);
    opacity: 0;
    transition: opacity 0.3s;
}
.hp-stat-block:hover .hp-stat-block__bar { opacity: 1; }

.hp-stat-block__number {
    font-family: var(--hp-font-display);
    font-size: 2.8rem;
    color: var(--hp-white);
    line-height: 1;
    display: inline;
    letter-spacing: 0.02em;
}

.hp-stat-block__plus {
    font-family: var(--hp-font-display);
    font-size: 2rem;
    color: var(--hp-cyan);
    display: inline;
    margin-left: 2px;
}

.hp-stat-block__label {
    font-family: var(--hp-font);
    font-size: 0.88rem;
    font-weight: 600;
    color: var(--hp-cyan);
    margin-top: 6px;
}

.hp-stat-block__sub {
    font-family: var(--hp-font);
    font-size: 0.78rem;
    color: var(--hp-muted);
    margin-top: 2px;
}

/* === MAP FEATURE === */
.hp-map-feature {
    display: grid;
    grid-template-columns: 1fr 1.15fr;
    gap: 56px;
    align-items: center;
}

.hp-features-list {
    display: flex;
    flex-direction: column;
    gap: 14px;
    margin-top: 24px;
}

.hp-features-list__item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    background: var(--hp-card);
    border: 1px solid var(--hp-border);
    border-radius: 12px;
    transition: all 0.3s var(--hp-ease);
}
.hp-features-list__item:hover {
    border-color: var(--hp-border-strong);
    transform: translateX(4px);
}

.hp-features-list__icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: var(--hp-cyan-dim);
    color: var(--hp-cyan);
    flex-shrink: 0;
}

.hp-features-list__item strong {
    display: block;
    font-family: var(--hp-font);
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--hp-white);
}
.hp-features-list__item span {
    font-family: var(--hp-font);
    font-size: 0.75rem;
    color: var(--hp-muted);
}

/* Map browser preview */
.hp-map-browser {
    border-radius: 18px;
    overflow: hidden;
    border: 1px solid var(--hp-border);
    background: var(--hp-card);
    box-shadow:
        0 30px 80px rgba(0, 0, 0, 0.5),
        0 0 0 1px rgba(34, 211, 238, 0.06),
        inset 0 1px 0 rgba(255, 255, 255, 0.03);
    transition: all 0.5s var(--hp-ease);
}
.hp-map-browser:hover {
    transform: translateY(-4px);
    box-shadow:
        0 40px 100px rgba(0, 0, 0, 0.6),
        0 0 0 1px rgba(34, 211, 238, 0.12),
        0 0 60px rgba(34, 211, 238, 0.06);
}

.hp-map-browser__bar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: rgba(8, 20, 38, 0.9);
    border-bottom: 1px solid var(--hp-border);
}

.hp-map-browser__dots {
    display: flex;
    gap: 6px;
}
.hp-map-browser__dots span {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    opacity: 0.8;
}

.hp-map-browser__url {
    flex: 1;
    text-align: center;
    font-family: var(--hp-font);
    font-size: 0.72rem;
    color: var(--hp-muted);
    background: rgba(148, 163, 184, 0.06);
    padding: 5px 16px;
    border-radius: 6px;
}

.hp-map-browser__content {
    aspect-ratio: 16/10;
}

/* === CHARTS === */
.hp-charts {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.hp-chart-card {
    background: var(--hp-card);
    border: 1px solid var(--hp-border);
    border-radius: var(--hp-radius);
    padding: 28px;
    transition: border-color 0.4s;
}
.hp-chart-card:hover { border-color: var(--hp-border-strong); }

.hp-chart-card__title {
    font-family: var(--hp-font);
    font-size: 0.9rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 20px;
    text-align: center;
}

/* === MISSION CARDS === */
.hp-mission-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.hp-mission-card {
    position: relative;
    padding: 32px;
    background: var(--hp-card);
    border: 1px solid var(--hp-border);
    border-radius: var(--hp-radius);
    transition: all 0.5s var(--hp-ease);
    overflow: hidden;
}
.hp-mission-card:hover {
    border-color: var(--hp-border-strong);
    transform: translateY(-6px);
}

.hp-mission-card__index {
    position: absolute;
    top: 20px;
    right: 24px;
    font-family: var(--hp-font-display);
    font-size: 2.5rem;
    color: rgba(34, 211, 238, 0.06);
    line-height: 1;
    letter-spacing: 0.02em;
}

.hp-mission-card__icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: var(--hp-cyan-dim);
    border: 1px solid rgba(34, 211, 238, 0.1);
    color: var(--hp-cyan);
    margin-bottom: 20px;
    transition: all 0.4s var(--hp-ease);
}
.hp-mission-card:hover .hp-mission-card__icon {
    background: rgba(34, 211, 238, 0.18);
    box-shadow: 0 0 24px rgba(34, 211, 238, 0.12);
}

.hp-mission-card h3 {
    font-family: var(--hp-font);
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--hp-white);
    margin-bottom: 10px;
}

.hp-mission-card p {
    font-family: var(--hp-font);
    font-size: 0.88rem;
    color: var(--hp-muted);
    line-height: 1.7;
}

/* === CTA === */
.hp-cta {
    position: relative;
    padding: clamp(80px, 12vw, 140px) 0;
    overflow: hidden;
}

.hp-cta__glow {
    display: none;
}

.hp-cta__inner { text-align: center; position: relative; z-index: 2; }

.hp-cta__title {
    font-family: var(--hp-font-display);
    font-size: clamp(2.2rem, 5vw, 3.8rem);
    color: var(--hp-white);
    margin-bottom: 16px;
    letter-spacing: 0.02em;
}

.hp-cta__desc {
    font-family: var(--hp-font);
    font-size: 1.05rem;
    color: var(--hp-muted);
    margin: 0 auto 36px;
    max-width: 460px;
    line-height: 1.7;
}

.hp-cta__actions {
    display: flex;
    gap: 14px;
    justify-content: center;
    flex-wrap: wrap;
}

/* === ANIMATIONS === */
.fade-in {
    opacity: 0;
    transform: translateY(50px);
    transition: opacity 1s var(--hp-ease), transform 1s var(--hp-ease);
}
.fade-in.visible {
    opacity: 1;
    transform: translateY(0);
}

/* Hero stagger */
.hp-hero.anim-ready .hp-hero__badge {
    animation: heroSlideUp 0.8s var(--hp-ease) 0.1s forwards;
}
.hp-hero.anim-ready .hp-hero__title-line:nth-child(1) {
    animation: heroSlideUp 0.9s var(--hp-ease) 0.25s forwards;
}
.hp-hero.anim-ready .hp-hero__title-line:nth-child(2) {
    animation: heroSlideUp 0.9s var(--hp-ease) 0.4s forwards;
}
.hp-hero.anim-ready .hp-hero__title-line:nth-child(3) {
    animation: heroSlideUp 0.9s var(--hp-ease) 0.55s forwards;
}
.hp-hero.anim-ready .hp-hero__desc {
    animation: heroSlideUp 0.9s var(--hp-ease) 0.7s forwards;
}
.hp-hero.anim-ready .hp-hero__actions {
    animation: heroSlideUp 1s var(--hp-ease) 0.9s forwards;
}
.hp-hero.anim-ready .hp-hero__float-card {
    animation: heroFloatIn 1.2s var(--hp-ease) 0.6s forwards;
}

@keyframes heroSlideUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes heroFloatIn {
    from { opacity: 0; transform: translateY(30px) translateX(10px); }
    to { opacity: 1; transform: translateY(0) translateX(0); }
}

@keyframes dotPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.7); }
}

@keyframes scrollLineAnim {
    0%, 100% { opacity: 0.3; transform: scaleX(1); }
    50% { opacity: 0.8; transform: scaleX(1.15); }
}

/* === RESPONSIVE === */
@media (max-width: 1024px) {
    .hp-hero__inner {
        flex-direction: column;
        justify-content: center;
        padding: 120px 32px 60px;
        gap: 40px;
    }
    .hp-hero__content { max-width: 100%; text-align: center; }
    .hp-hero__desc { margin-left: auto; margin-right: auto; }
    .hp-hero__actions { justify-content: center; }
    .hp-hero__title-line { text-align: center; }
    .hp-hero__float-card { width: 100%; max-width: 400px; align-self: center; }
    .hp-hero__scroll { left: 32px; }
    .hp-problem { grid-template-columns: 1fr; gap: 40px; }
    .hp-map-feature { grid-template-columns: 1fr; gap: 40px; }
    .hp-map-feature__preview { order: -1; }
}

@media (max-width: 768px) {
    .hp-hero__inner { padding: 100px 20px 50px; }
    .hp-hero__title-line { font-size: clamp(2.5rem, 12vw, 4rem); }
    .hp-container { padding: 0 20px; }
    .hp-divider { padding: 0 20px; }
    .hp-hero__scroll { left: 20px; }
    .hp-charts { grid-template-columns: 1fr; }
    .hp-mission-cards { grid-template-columns: 1fr; }
    .hp-hero__float-card { max-width: 100%; }
    .hp-hero__grid-lines { display: none; }
    .hp-divider__label { display: none; }
}
</style>

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // === Hero stagger animation ===
    requestAnimationFrame(function() {
        document.querySelector('.hp-hero').classList.add('anim-ready');
    });

    // === Scroll fade-in + counter animation ===
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                entry.target.querySelectorAll('[data-count]').forEach(animateCounter);
            }
        });
    }, { threshold: 0.12 });

    document.querySelectorAll('.fade-in').forEach(function(el) { observer.observe(el); });

    // Also animate hero float card counters
    setTimeout(function() {
        document.querySelectorAll('.hp-hero__float-stat-num[data-count]').forEach(animateCounter);
    }, 1200);

    function animateCounter(el) {
        var target = parseInt(el.getAttribute('data-count'), 10);
        if (isNaN(target) || el.dataset.animated) return;
        el.dataset.animated = '1';
        var duration = 1600;
        var start = performance.now();
        function step(now) {
            var p = Math.min((now - start) / duration, 1);
            var eased = 1 - Math.pow(1 - p, 4);
            el.textContent = Math.round(target * eased);
            if (p < 1) requestAnimationFrame(step);
            else el.textContent = target;
        }
        requestAnimationFrame(step);
    }

    // === Preview Map ===
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
                                radius: 5,
                                fillColor: color,
                                color: color,
                                fillOpacity: 0.75,
                                weight: 0
                            }).addTo(previewMap);
                        }
                    });
                }
            });
    }

    // === Charts ===
    fetch('/api/get_microplastics.php')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success || !res.data) return;
            var data = res.data;

            // Ecossistema
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
                                labels: { color: 'rgba(148,163,184,0.7)', font: { family: 'Instrument Sans, Plus Jakarta Sans, sans-serif', size: 11 }, padding: 14, usePointStyle: true, pointStyleWidth: 8 }
                            }
                        }
                    }
                });
            }

            // Concentracao
            var levels = { 'Baixa': 0, 'Media': 0, 'Elevada': 0, 'Alta': 0, 'Critica': 0 };
            data.forEach(function(d) {
                var v = d.concentration_value || 0;
                if (v < 1000) levels['Baixa']++;
                else if (v < 3000) levels['Media']++;
                else if (v < 5000) levels['Elevada']++;
                else if (v < 8000) levels['Alta']++;
                else levels['Critica']++;
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
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { color: 'rgba(148,163,184,0.4)', font: { family: 'Instrument Sans, Plus Jakarta Sans' } },
                                grid: { color: 'rgba(148,163,184,0.05)' }
                            },
                            x: {
                                ticks: { color: 'rgba(148,163,184,0.5)', font: { family: 'Instrument Sans, Plus Jakarta Sans', size: 11 } },
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
