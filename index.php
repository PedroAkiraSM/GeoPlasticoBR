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
        $statsPoints = (int)$pdo->query("SELECT COUNT(*) FROM samples WHERE approved = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL")->fetchColumn();
        $statsRecords = (int)$pdo->query("SELECT COUNT(*) FROM samples WHERE approved = 1")->fetchColumn();
        $statsEcosystems = (int)$pdo->query("SELECT COUNT(*) FROM sample_categories WHERE is_active = 1")->fetchColumn();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/config/cms.php';
$_homeBlocks = getBlocks('home');

$pageTitle = getSetting('site_name', 'GeoPlasticoBR') . " - Mapeamento de Microplasticos no Brasil";
$heroPage = true;
include 'includes/header.php';
?>

<!-- ============ FLOATING ORBS (Background Light Sources) ============ -->
<div class="lg-orbs" aria-hidden="true">
    <div class="lg-orb lg-orb--1"></div>
    <div class="lg-orb lg-orb--2"></div>
    <div class="lg-orb lg-orb--3"></div>
    <div class="lg-orb lg-orb--4"></div>
</div>

<!-- ============ HERO SECTION ============ -->
<section class="lg-hero">
    <div class="lg-hero__bg">
        <video autoplay muted loop playsinline preload="metadata" class="lg-hero__video" id="heroVideo">
            <source src="/videos/hero-bg.mp4" type="video/mp4">
        </video>
        <div class="lg-hero__overlay"></div>
    </div>

    <div class="lg-hero__inner">
        <div class="lg-hero__content" data-anim="stagger">
            <div class="lg-hero__badge">
                <span class="lg-hero__badge-dot"></span>
                Monitoramento Ambiental
            </div>
            <h1 class="lg-hero__title">
                Mapeando a<br>
                <span class="lg-hero__title--glow">poluicao invisivel</span>
            </h1>
            <p class="lg-hero__desc"><?php echo htmlspecialchars($_homeBlocks['hero_subtitle'] ?? 'Plataforma cientifica de mapeamento de microplasticos nos ecossistemas aquaticos brasileiros.'); ?></p>
            <div class="lg-hero__actions">
                <a href="/mapa.php" class="lg-btn lg-btn--primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                    <span><?php echo htmlspecialchars($_homeBlocks['hero_cta_primary'] ?? 'Explorar Mapa'); ?></span>
                </a>
                <a href="/sobre.php" class="lg-btn lg-btn--glass"><?php echo htmlspecialchars($_homeBlocks['hero_cta_secondary'] ?? 'Sobre o Projeto'); ?></a>
            </div>
        </div>

        <!-- Liquid Glass Stats Card -->
        <div class="lg-glass-card lg-glass-card--hero" id="heroGlassCard">
            <div class="lg-glass-card__shine"></div>
            <div class="lg-glass-card__header">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                Dados em Tempo Real
            </div>
            <div class="lg-glass-card__stats">
                <div class="lg-glass-card__stat">
                    <span class="lg-glass-card__num" data-count="<?php echo $statsPoints; ?>">0</span>
                    <span class="lg-glass-card__label"><?php echo htmlspecialchars($_homeBlocks['stats_label_1'] ?? 'Pontos'); ?></span>
                </div>
                <div class="lg-glass-card__divider"></div>
                <div class="lg-glass-card__stat">
                    <span class="lg-glass-card__num" data-count="<?php echo $statsEcosystems; ?>">0</span>
                    <span class="lg-glass-card__label"><?php echo htmlspecialchars($_homeBlocks['stats_label_2'] ?? 'Categorias'); ?></span>
                </div>
                <div class="lg-glass-card__divider"></div>
                <div class="lg-glass-card__stat">
                    <span class="lg-glass-card__num" data-count="<?php echo $statsRecords; ?>">0</span>
                    <span class="lg-glass-card__label"><?php echo htmlspecialchars($_homeBlocks['stats_label_3'] ?? 'Registros'); ?></span>
                </div>
            </div>
            <a href="/mapa.php" class="lg-glass-card__cta">
                Abrir Mapa Interativo
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
            </a>
        </div>
    </div>

    <div class="lg-hero__scroll">
        <div class="lg-hero__scroll-line"></div>
    </div>
</section>

<!-- ============ PROBLEM / CONTEXT ============ -->
<section class="lg-section" id="problema">
    <div class="lg-container">
        <div class="lg-eyebrow">O Problema</div>
        <div class="lg-problem">
            <div class="lg-problem__text">
                <h2 class="lg-h2"><?php echo htmlspecialchars($_homeBlocks['problem_title'] ?? 'Microplasticos estao em toda parte'); ?></h2>
                <p class="lg-p">
                    <?php echo htmlspecialchars($_homeBlocks['problem_description'] ?? 'Fragmentos de plastico menores que 5mm contaminam silenciosamente nossos oceanos, rios e lagos. Uma crise ambiental invisivel que afeta toda a cadeia alimentar.'); ?>
                </p>
            </div>
            <div class="lg-problem__stats">
                <div class="lg-glass-card lg-glass-card--stat">
                    <span class="lg-glass-card__num" data-count="<?php echo $statsPoints; ?>">0</span>
                    <span class="lg-glass-card__stat-label"><?php echo htmlspecialchars($_homeBlocks['stats_label_1'] ?? 'Pontos de Coleta'); ?></span>
                    <span class="lg-glass-card__stat-sub"><?php echo htmlspecialchars($_homeBlocks['stats_desc_1'] ?? 'Mapeados em todo o Brasil'); ?></span>
                </div>
                <div class="lg-glass-card lg-glass-card--stat">
                    <span class="lg-glass-card__num" data-count="<?php echo $statsEcosystems; ?>">0</span>
                    <span class="lg-glass-card__stat-label"><?php echo htmlspecialchars($_homeBlocks['stats_label_2'] ?? 'Categorias'); ?></span>
                    <span class="lg-glass-card__stat-sub"><?php echo htmlspecialchars($_homeBlocks['stats_desc_2'] ?? 'Tipos de amostras monitoradas'); ?></span>
                </div>
                <div class="lg-glass-card lg-glass-card--stat">
                    <span class="lg-glass-card__num" data-count="<?php echo $statsRecords; ?>">0</span>
                    <span class="lg-glass-card__stat-label"><?php echo htmlspecialchars($_homeBlocks['stats_label_3'] ?? 'Registros Cientificos'); ?></span>
                    <span class="lg-glass-card__stat-sub"><?php echo htmlspecialchars($_homeBlocks['stats_desc_3'] ?? 'Dados verificados por pares'); ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ MAP FEATURE ============ -->
<section class="lg-section">
    <div class="lg-container">
        <div class="lg-eyebrow">Ferramenta Principal</div>
        <div class="lg-map-feature">
            <div class="lg-map-feature__info">
                <h2 class="lg-h2"><?php echo htmlspecialchars($_homeBlocks['feature_title'] ?? 'Mapa Interativo'); ?></h2>
                <p class="lg-p">Visualize em tempo real a distribuicao de microplasticos nos rios, lagos e oceanos brasileiros.</p>
                <div class="lg-checklist">
                    <div class="lg-checklist__item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>Multiplas visualizacoes: markers, clusters e heatmap</span>
                    </div>
                    <div class="lg-checklist__item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>Dados cientificos revisados por pares</span>
                    </div>
                    <div class="lg-checklist__item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <span>Filtros por ambiente, ecossistema e concentracao</span>
                    </div>
                </div>
                <a href="/mapa.php" class="lg-btn lg-btn--primary" style="margin-top: 2rem;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                    <span>Acessar Mapa</span>
                </a>
            </div>
            <div class="lg-map-feature__preview">
                <div class="lg-map-browser">
                    <div class="lg-map-browser__bar">
                        <div class="lg-map-browser__dots"><span></span><span></span><span></span></div>
                        <div class="lg-map-browser__url">geoplasticobr.com/mapa</div>
                    </div>
                    <div class="lg-map-browser__content">
                        <div id="previewMap" style="width:100%;height:100%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ CHARTS ============ -->
<section class="lg-section" id="chartSection">
    <div class="lg-container">
        <div class="lg-eyebrow">Dados</div>
        <h2 class="lg-h2" style="text-align:center; margin-bottom: 0.75rem;">Estatisticas do Mapeamento</h2>
        <p class="lg-p" style="text-align:center; margin: 0 auto 3rem; max-width: 480px;">Distribuicao de registros por ecossistema e niveis de concentracao.</p>
        <div class="lg-charts">
            <div class="lg-glass-card lg-glass-card--chart">
                <h3 class="lg-chart-title">Registros por Ecossistema</h3>
                <canvas id="chartEcossistema"></canvas>
            </div>
            <div class="lg-glass-card lg-glass-card--chart">
                <h3 class="lg-chart-title">Niveis de Concentracao</h3>
                <canvas id="chartConcentracao"></canvas>
            </div>
        </div>
    </div>
</section>

<!-- ============ MISSION ============ -->
<section class="lg-section">
    <div class="lg-container">
        <div class="lg-eyebrow">Nossa Missao</div>
        <h2 class="lg-h2" style="text-align:center; margin-bottom: 3rem;"><?php echo htmlspecialchars($_homeBlocks['mission_title'] ?? 'Democratizar dados cientificos'); ?></h2>
        <div class="lg-mission-grid">
            <div class="lg-glass-card lg-glass-card--mission">
                <div class="lg-mission-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <h3>Dados Verificados</h3>
                <p>Pesquisas cientificas revisadas por pares garantindo confiabilidade e precisao.</p>
            </div>
            <div class="lg-glass-card lg-glass-card--mission">
                <div class="lg-mission-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </div>
                <h3>Acesso Aberto</h3>
                <p>Plataforma gratuita para pesquisadores, estudantes e o publico em geral.</p>
            </div>
            <div class="lg-glass-card lg-glass-card--mission">
                <div class="lg-mission-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                </div>
                <h3>Visualizacao Interativa</h3>
                <p>Mapa com multiplas camadas permitindo analise espacial detalhada.</p>
            </div>
        </div>
    </div>
</section>

<!-- ============ CTA FINAL ============ -->
<section class="lg-cta">
    <div class="lg-container lg-cta__inner">
        <h2 class="lg-cta__title"><?php echo htmlspecialchars($_homeBlocks['cta_title'] ?? 'Pronto para Explorar?'); ?></h2>
        <p class="lg-cta__desc">
            <?php echo htmlspecialchars($_homeBlocks['cta_description'] ?? 'Descubra a distribuicao de microplasticos nos ecossistemas aquaticos brasileiros.'); ?>
        </p>
        <div class="lg-cta__actions">
            <a href="/mapa.php" class="lg-btn lg-btn--primary lg-btn--lg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                <span>Acessar Mapa Agora</span>
            </a>
            <a href="/contribuir.php" class="lg-btn lg-btn--glass">Contribuir com Dados</a>
        </div>
    </div>
</section>

<!-- ============ STYLES ============ -->
<style>
/* ================================================================
   DESIGN TOKENS — DARK OCEAN + LIQUID GLASS
   ================================================================ */
:root {
    --lg-bg: #020a18;
    --lg-bg-surface: rgba(0, 20, 40, 0.4);
    --lg-glass-bg: rgba(255, 255, 255, 0.04);
    --lg-glass-bg-hover: rgba(255, 255, 255, 0.07);
    --lg-glass-border: rgba(0, 212, 255, 0.12);
    --lg-glass-border-hover: rgba(0, 212, 255, 0.25);
    --lg-glass-blur: 25px;
    --lg-accent: #00d4ff;
    --lg-accent-teal: #0d9488;
    --lg-accent-glow: rgba(0, 212, 255, 0.3);
    --lg-text: #e8f4f8;
    --lg-text-secondary: rgba(200, 220, 230, 0.6);
    --lg-text-muted: rgba(200, 220, 230, 0.35);
    --lg-font: 'Inter', 'Outfit', sans-serif;
    --lg-font-display: 'Inter', 'Outfit', sans-serif;
    --lg-ease: cubic-bezier(0.16, 1, 0.3, 1);
    --lg-radius: 24px;
}

/* === RESET === */
body {
    background: var(--lg-bg) !important;
    color: var(--lg-text) !important;
    background-image: none !important;
    overflow-x: hidden;
}
.light-rays { display: none !important; }

/* ================================================================
   FLOATING ORBS — Bioluminescent Background
   ================================================================ */
.lg-orbs {
    position: fixed;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    overflow: hidden;
}

.lg-orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.4;
}

.lg-orb--1 {
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(0, 212, 255, 0.15) 0%, transparent 70%);
    top: 10%;
    left: -10%;
}
.lg-orb--2 {
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(13, 148, 136, 0.12) 0%, transparent 70%);
    top: 40%;
    right: -5%;
}
.lg-orb--3 {
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(0, 212, 255, 0.1) 0%, transparent 70%);
    bottom: 20%;
    left: 20%;
}
.lg-orb--4 {
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(13, 148, 136, 0.1) 0%, transparent 70%);
    top: 70%;
    right: 30%;
}

/* ================================================================
   HERO SECTION
   ================================================================ */
.lg-hero {
    position: relative;
    width: 100%;
    min-height: 100vh;
    display: flex;
    align-items: stretch;
    overflow: hidden;
    z-index: 1;
}

.lg-hero__bg {
    position: absolute;
    inset: 0;
}

.lg-hero__video {
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: brightness(0.3) saturate(0.6) hue-rotate(-10deg);
}

.lg-hero__overlay {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(180deg, rgba(2, 10, 24, 0.4) 0%, rgba(2, 10, 24, 0.2) 40%, rgba(2, 10, 24, 0.8) 100%),
        radial-gradient(ellipse 80% 50% at 30% 50%, rgba(0, 212, 255, 0.06) 0%, transparent 60%);
}

.lg-hero__inner {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 1300px;
    margin: 0 auto;
    padding: 140px 48px 80px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 60px;
    min-height: 100vh;
}

/* Hero content */
.lg-hero__content {
    flex: 1;
    max-width: 620px;
}

.lg-hero__badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 18px;
    background: rgba(0, 212, 255, 0.06);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(0, 212, 255, 0.15);
    border-radius: 50px;
    font-family: var(--lg-font);
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--lg-accent);
    text-transform: uppercase;
    letter-spacing: 0.14em;
    margin-bottom: 32px;
    opacity: 0;
    transform: translateY(20px);
}

.lg-hero__badge-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--lg-accent);
    box-shadow: 0 0 8px var(--lg-accent-glow);
    animation: lgDotPulse 2s ease-in-out infinite;
}

.lg-hero__title {
    font-family: var(--lg-font-display);
    font-weight: 800;
    font-size: clamp(3rem, 6vw, 5rem);
    line-height: 1.05;
    color: var(--lg-text);
    margin-bottom: 24px;
    letter-spacing: -0.04em;
    opacity: 0;
    transform: translateY(30px);
}

.lg-hero__title--glow {
    background: linear-gradient(135deg, #00d4ff 0%, #0d9488 50%, #00d4ff 100%);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.lg-hero__desc {
    font-family: var(--lg-font);
    font-size: 1.05rem;
    color: var(--lg-text-secondary);
    line-height: 1.75;
    max-width: 460px;
    margin-bottom: 32px;
    opacity: 0;
    transform: translateY(20px);
}

.lg-hero__actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    opacity: 0;
    transform: translateY(20px);
}

/* ================================================================
   BUTTONS — Liquid Glass Style
   ================================================================ */
.lg-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 13px 28px;
    font-family: var(--lg-font);
    font-weight: 600;
    font-size: 0.88rem;
    text-decoration: none;
    border-radius: 14px;
    transition: all 0.4s var(--lg-ease);
    cursor: pointer;
    border: none;
    position: relative;
    overflow: hidden;
}

.lg-btn--primary {
    background: linear-gradient(135deg, var(--lg-accent), var(--lg-accent-teal));
    color: #020a18;
    box-shadow: 0 4px 20px rgba(0, 212, 255, 0.25), 0 0 40px rgba(0, 212, 255, 0.1);
}
.lg-btn--primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(0, 212, 255, 0.4), 0 0 60px rgba(0, 212, 255, 0.15);
}

.lg-btn--glass {
    background: rgba(255, 255, 255, 0.06);
    color: var(--lg-text);
    border: 1px solid rgba(0, 212, 255, 0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}
.lg-btn--glass:hover {
    background: rgba(0, 212, 255, 0.08);
    border-color: rgba(0, 212, 255, 0.3);
    color: #ffffff;
    box-shadow: 0 0 30px rgba(0, 212, 255, 0.1);
}

.lg-btn--lg { padding: 16px 36px; font-size: 0.95rem; }

/* ================================================================
   GLASS CARD — Universal Liquid Glass Component
   ================================================================ */
.lg-glass-card {
    background: var(--lg-glass-bg);
    border: 1px solid var(--lg-glass-border);
    border-radius: var(--lg-radius);
    padding: 28px;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(var(--lg-glass-blur)) saturate(150%);
    -webkit-backdrop-filter: blur(var(--lg-glass-blur)) saturate(150%);
    transition: all 0.5s var(--lg-ease);
    box-shadow:
        0 24px 80px rgba(0, 0, 0, 0.3),
        0 8px 24px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.06),
        inset 0 -1px 0 rgba(255, 255, 255, 0.02);
}

.lg-glass-card:hover {
    background: var(--lg-glass-bg-hover);
    border-color: var(--lg-glass-border-hover);
    transform: translateY(-4px);
    box-shadow:
        0 32px 100px rgba(0, 0, 0, 0.4),
        0 12px 32px rgba(0, 0, 0, 0.25),
        0 0 60px rgba(0, 212, 255, 0.05),
        inset 0 1px 0 rgba(255, 255, 255, 0.08);
}

/* Glass shine effect */
.lg-glass-card__shine,
.lg-glass-card::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: inherit;
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.08) 0%,
        rgba(255, 255, 255, 0) 40%,
        rgba(255, 255, 255, 0.03) 100%
    );
    pointer-events: none;
    z-index: 1;
}

.lg-glass-card > *:not(.lg-glass-card__shine) {
    position: relative;
    z-index: 2;
}

/* Hero glass card */
.lg-glass-card--hero {
    width: 360px;
    flex-shrink: 0;
    opacity: 0;
    transform: translateY(30px);
}

.lg-glass-card__header {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--lg-accent);
    font-family: var(--lg-font);
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(0, 212, 255, 0.08);
    margin-bottom: 20px;
}

.lg-glass-card__stats {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.lg-glass-card__stat { flex: 1; text-align: center; }

.lg-glass-card__num {
    display: block;
    font-family: var(--lg-font-display);
    font-weight: 800;
    font-size: 2.2rem;
    color: var(--lg-text);
    line-height: 1;
    letter-spacing: -0.04em;
}

.lg-glass-card__label {
    display: block;
    font-family: var(--lg-font);
    font-size: 0.65rem;
    color: var(--lg-text-muted);
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.lg-glass-card__divider {
    width: 1px;
    height: 36px;
    background: rgba(0, 212, 255, 0.1);
}

.lg-glass-card__cta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    width: 100%;
    padding: 12px;
    background: rgba(0, 212, 255, 0.06);
    border: 1px solid rgba(0, 212, 255, 0.12);
    border-radius: 12px;
    color: var(--lg-accent);
    font-family: var(--lg-font);
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.35s var(--lg-ease);
}
.lg-glass-card__cta:hover {
    background: rgba(0, 212, 255, 0.12);
    border-color: rgba(0, 212, 255, 0.25);
    box-shadow: 0 0 24px rgba(0, 212, 255, 0.1);
}

/* Stat card variant */
.lg-glass-card--stat {
    padding: 24px 28px;
}
.lg-glass-card__stat-label {
    display: block;
    font-family: var(--lg-font);
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--lg-text);
    margin-top: 6px;
}
.lg-glass-card__stat-sub {
    display: block;
    font-family: var(--lg-font);
    font-size: 0.78rem;
    color: var(--lg-text-muted);
    margin-top: 2px;
}

/* Scroll indicator */
.lg-hero__scroll {
    position: absolute;
    bottom: 32px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
}

.lg-hero__scroll-line {
    width: 1px;
    height: 48px;
    background: linear-gradient(to bottom, var(--lg-accent), transparent);
    animation: lgScrollPulse 2s ease-in-out infinite;
}

/* ================================================================
   SECTIONS
   ================================================================ */
.lg-section {
    position: relative;
    padding: clamp(80px, 10vw, 120px) 0;
    z-index: 1;
}

.lg-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 48px;
}

.lg-eyebrow {
    display: inline-block;
    font-family: var(--lg-font);
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.18em;
    color: var(--lg-accent);
    margin-bottom: 16px;
    padding: 5px 14px;
    background: rgba(0, 212, 255, 0.06);
    border: 1px solid rgba(0, 212, 255, 0.1);
    border-radius: 8px;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}

.lg-h2 {
    font-family: var(--lg-font-display);
    font-weight: 800;
    font-size: clamp(2rem, 3.5vw, 2.8rem);
    color: var(--lg-text);
    letter-spacing: -0.035em;
    margin-bottom: 16px;
    line-height: 1.1;
}

.lg-p {
    font-family: var(--lg-font);
    font-size: 1rem;
    color: var(--lg-text-secondary);
    line-height: 1.8;
    max-width: 540px;
}

/* ================================================================
   PROBLEM SECTION
   ================================================================ */
.lg-problem {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 64px;
    align-items: start;
    margin-top: 8px;
}

.lg-problem__stats {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* ================================================================
   MAP FEATURE
   ================================================================ */
.lg-map-feature {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 56px;
    align-items: center;
    margin-top: 8px;
}

.lg-checklist {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-top: 24px;
}

.lg-checklist__item {
    display: flex;
    align-items: center;
    gap: 12px;
    color: var(--lg-accent);
}
.lg-checklist__item span {
    font-family: var(--lg-font);
    font-size: 0.9rem;
    color: var(--lg-text-secondary);
}

/* Map browser mockup */
.lg-map-browser {
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid var(--lg-glass-border);
    background: var(--lg-glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    box-shadow:
        0 24px 80px rgba(0, 0, 0, 0.4),
        0 0 40px rgba(0, 212, 255, 0.03);
    transition: all 0.5s var(--lg-ease);
}
.lg-map-browser:hover {
    transform: translateY(-4px);
    border-color: var(--lg-glass-border-hover);
    box-shadow:
        0 32px 100px rgba(0, 0, 0, 0.5),
        0 0 60px rgba(0, 212, 255, 0.06);
}

.lg-map-browser__bar {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: rgba(0, 20, 40, 0.6);
    border-bottom: 1px solid rgba(0, 212, 255, 0.06);
}

.lg-map-browser__dots { display: flex; gap: 6px; }
.lg-map-browser__dots span {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    opacity: 0.7;
}
.lg-map-browser__dots span:nth-child(1) { background: #FF5F57; }
.lg-map-browser__dots span:nth-child(2) { background: #FFBD2E; }
.lg-map-browser__dots span:nth-child(3) { background: #28CA41; }

.lg-map-browser__url {
    flex: 1;
    text-align: center;
    font-family: var(--lg-font);
    font-size: 0.72rem;
    color: var(--lg-text-muted);
    background: rgba(0, 212, 255, 0.04);
    border: 1px solid rgba(0, 212, 255, 0.06);
    padding: 5px 16px;
    border-radius: 8px;
}

.lg-map-browser__content { aspect-ratio: 16/10; }

/* ================================================================
   CHARTS
   ================================================================ */
.lg-charts {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.lg-glass-card--chart { padding: 28px; }

.lg-chart-title {
    font-family: var(--lg-font);
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--lg-text);
    margin-bottom: 20px;
    text-align: center;
}

/* ================================================================
   MISSION
   ================================================================ */
.lg-mission-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.lg-glass-card--mission { padding: 32px; }

.lg-mission-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: rgba(0, 212, 255, 0.08);
    border: 1px solid rgba(0, 212, 255, 0.1);
    color: var(--lg-accent);
    margin-bottom: 20px;
}

.lg-glass-card--mission h3 {
    font-family: var(--lg-font);
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--lg-text);
    margin-bottom: 8px;
}

.lg-glass-card--mission p {
    font-family: var(--lg-font);
    font-size: 0.88rem;
    color: var(--lg-text-muted);
    line-height: 1.7;
}

/* ================================================================
   CTA
   ================================================================ */
.lg-cta {
    position: relative;
    padding: clamp(80px, 10vw, 120px) 0;
    z-index: 1;
    overflow: hidden;
}

.lg-cta::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 60% 50% at 30% 60%, rgba(0, 212, 255, 0.08) 0%, transparent 60%),
        radial-gradient(ellipse 40% 50% at 70% 40%, rgba(13, 148, 136, 0.06) 0%, transparent 60%);
    pointer-events: none;
}

.lg-cta__inner { text-align: center; position: relative; z-index: 2; }

.lg-cta__title {
    font-family: var(--lg-font-display);
    font-weight: 800;
    font-size: clamp(2.2rem, 4vw, 3.2rem);
    color: var(--lg-text);
    margin-bottom: 16px;
    letter-spacing: -0.035em;
}

.lg-cta__desc {
    font-family: var(--lg-font);
    font-size: 1.05rem;
    color: var(--lg-text-secondary);
    margin: 0 auto 32px;
    max-width: 440px;
    line-height: 1.7;
}

.lg-cta__actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}

/* ================================================================
   ANIMATIONS
   ================================================================ */
.lg-fade-in {
    opacity: 0;
    transform: translateY(40px);
}

@keyframes lgDotPulse {
    0%, 100% { opacity: 1; transform: scale(1); box-shadow: 0 0 8px var(--lg-accent-glow); }
    50% { opacity: 0.4; transform: scale(0.6); box-shadow: 0 0 4px var(--lg-accent-glow); }
}

@keyframes lgScrollPulse {
    0%, 100% { opacity: 0.3; }
    50% { opacity: 0.8; }
}

/* ================================================================
   RESPONSIVE
   ================================================================ */
@media (max-width: 1024px) {
    .lg-hero__inner {
        flex-direction: column;
        justify-content: center;
        padding: 120px 32px 60px;
        gap: 40px;
    }
    .lg-hero__content { max-width: 100%; text-align: center; }
    .lg-hero__desc { margin-left: auto; margin-right: auto; }
    .lg-hero__actions { justify-content: center; }
    .lg-glass-card--hero { width: 100%; max-width: 400px; align-self: center; }
    .lg-problem { grid-template-columns: 1fr; gap: 40px; }
    .lg-map-feature { grid-template-columns: 1fr; gap: 40px; }
    .lg-map-feature__preview { order: -1; }
}

@media (max-width: 768px) {
    .lg-hero__inner { padding: 100px 20px 50px; }
    .lg-hero__title { font-size: clamp(2.2rem, 9vw, 3.2rem); }
    .lg-container { padding: 0 20px; }
    .lg-charts { grid-template-columns: 1fr; }
    .lg-mission-grid { grid-template-columns: 1fr; }
    .lg-glass-card--hero { max-width: 100%; width: 100%; }
    .lg-orb { display: none; }
}

/* === REDUCED MOTION === */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
</style>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // ================================================================
    // GSAP ANIMATIONS
    // ================================================================
    gsap.registerPlugin(ScrollTrigger);

    // Hero entrance animations
    var heroTl = gsap.timeline({ delay: 0.2 });
    heroTl
        .to('.lg-hero__badge', { opacity: 1, y: 0, duration: 0.7, ease: 'power3.out' })
        .to('.lg-hero__title', { opacity: 1, y: 0, duration: 0.8, ease: 'power3.out' }, '-=0.4')
        .to('.lg-hero__desc', { opacity: 1, y: 0, duration: 0.8, ease: 'power3.out' }, '-=0.5')
        .to('.lg-hero__actions', { opacity: 1, y: 0, duration: 0.8, ease: 'power3.out' }, '-=0.5')
        .to('.lg-glass-card--hero', { opacity: 1, y: 0, duration: 1, ease: 'power3.out' }, '-=0.6');

    // Title gradient animation
    gsap.to('.lg-hero__title--glow', {
        backgroundPosition: '200% center',
        duration: 4,
        ease: 'none',
        repeat: -1
    });

    // Floating orbs animation
    gsap.utils.toArray('.lg-orb').forEach(function(orb, i) {
        var duration = 15 + i * 5;
        gsap.to(orb, {
            x: 'random(-100, 100)',
            y: 'random(-80, 80)',
            duration: duration,
            ease: 'sine.inOut',
            repeat: -1,
            yoyo: true,
            delay: i * 2
        });
        gsap.to(orb, {
            opacity: 0.2 + Math.random() * 0.3,
            duration: duration * 0.6,
            ease: 'sine.inOut',
            repeat: -1,
            yoyo: true,
            delay: i
        });
    });

    // Mouse tracking on hero glass card
    var heroCard = document.getElementById('heroGlassCard');
    if (heroCard) {
        document.addEventListener('mousemove', function(e) {
            var rect = heroCard.getBoundingClientRect();
            var centerX = rect.left + rect.width / 2;
            var centerY = rect.top + rect.height / 2;
            var deltaX = (e.clientX - centerX) / window.innerWidth;
            var deltaY = (e.clientY - centerY) / window.innerHeight;

            gsap.to(heroCard, {
                rotateY: deltaX * 8,
                rotateX: -deltaY * 6,
                duration: 0.8,
                ease: 'power2.out',
                transformPerspective: 1000
            });

            // Move shine highlight
            var shine = heroCard.querySelector('.lg-glass-card__shine');
            if (shine) {
                gsap.to(shine, {
                    background: 'linear-gradient(' +
                        (135 + deltaX * 30) + 'deg, ' +
                        'rgba(255,255,255,' + (0.08 + Math.abs(deltaX) * 0.06) + ') 0%, ' +
                        'rgba(255,255,255,0) 40%, ' +
                        'rgba(0,212,255,' + (0.03 + Math.abs(deltaY) * 0.04) + ') 100%)',
                    duration: 0.5,
                    ease: 'power2.out'
                });
            }
        });

        // Reset on mouse leave
        document.addEventListener('mouseleave', function() {
            gsap.to(heroCard, {
                rotateY: 0,
                rotateX: 0,
                duration: 1,
                ease: 'power3.out'
            });
        });
    }

    // ScrollTrigger for sections
    gsap.utils.toArray('.lg-section, .lg-cta').forEach(function(section) {
        var elements = section.querySelectorAll('.lg-glass-card, .lg-h2, .lg-p, .lg-eyebrow, .lg-checklist__item, .lg-map-browser, .lg-chart-title');
        gsap.from(elements, {
            scrollTrigger: {
                trigger: section,
                start: 'top 80%',
                toggleActions: 'play none none none'
            },
            opacity: 0,
            y: 40,
            duration: 0.8,
            stagger: 0.1,
            ease: 'power3.out'
        });
    });

    // CTA section
    gsap.from('.lg-cta__title, .lg-cta__desc, .lg-cta__actions', {
        scrollTrigger: {
            trigger: '.lg-cta',
            start: 'top 80%'
        },
        opacity: 0,
        y: 40,
        duration: 0.8,
        stagger: 0.15,
        ease: 'power3.out'
    });

    // Glass card hover glow with GSAP
    document.querySelectorAll('.lg-glass-card').forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            gsap.to(card, {
                boxShadow: '0 32px 100px rgba(0,0,0,0.4), 0 0 80px rgba(0,212,255,0.08), inset 0 1px 0 rgba(255,255,255,0.1)',
                duration: 0.4,
                ease: 'power2.out'
            });
        });
        card.addEventListener('mouseleave', function() {
            gsap.to(card, {
                boxShadow: '0 24px 80px rgba(0,0,0,0.3), 0 8px 24px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.06), inset 0 -1px 0 rgba(255,255,255,0.02)',
                duration: 0.5,
                ease: 'power2.out'
            });
        });
    });

    // ================================================================
    // COUNTER ANIMATIONS (GSAP)
    // ================================================================
    function animateCounters(container) {
        container.querySelectorAll('[data-count]').forEach(function(el) {
            var target = parseInt(el.getAttribute('data-count'), 10);
            if (isNaN(target) || el.dataset.animated) return;
            el.dataset.animated = '1';
            gsap.fromTo(el, { innerText: 0 }, {
                innerText: target,
                duration: 1.8,
                ease: 'power2.out',
                snap: { innerText: 1 },
                scrollTrigger: {
                    trigger: el,
                    start: 'top 90%'
                }
            });
        });
    }

    // Hero counters (immediate after animation)
    setTimeout(function() {
        var heroCard = document.querySelector('.lg-glass-card--hero');
        if (heroCard) {
            heroCard.querySelectorAll('[data-count]').forEach(function(el) {
                var target = parseInt(el.getAttribute('data-count'), 10);
                if (isNaN(target) || el.dataset.animated) return;
                el.dataset.animated = '1';
                gsap.fromTo(el, { innerText: 0 }, {
                    innerText: target,
                    duration: 1.8,
                    ease: 'power2.out',
                    snap: { innerText: 1 }
                });
            });
        }
    }, 1200);

    // Section counters with ScrollTrigger
    document.querySelectorAll('.lg-section').forEach(function(section) {
        ScrollTrigger.create({
            trigger: section,
            start: 'top 80%',
            onEnter: function() { animateCounters(section); }
        });
    });

    // ================================================================
    // PREVIEW MAP
    // ================================================================
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
                    function getColor(val) {
                        if (thresholds) {
                            for (var i = 0; i < thresholds.length; i++) {
                                var t = thresholds[i];
                                var min = parseFloat(t.min_value);
                                var max = t.max_value !== null ? parseFloat(t.max_value) : Infinity;
                                if (val >= min && val < max) return t.color;
                            }
                            return '#6b7280';
                        }
                        return val < 1000 ? '#0d9488' : val < 3000 ? '#f59e0b' : val < 5000 ? '#f97316' : val < 8000 ? '#ef4444' : '#991b1b';
                    }
                    res.data.forEach(function(d) {
                        if (d.latitude && d.longitude) {
                            L.circleMarker([d.latitude, d.longitude], {
                                radius: 5,
                                fillColor: getColor(d.concentration_value),
                                color: getColor(d.concentration_value),
                                fillOpacity: 0.8,
                                weight: 0
                            }).addTo(previewMap);
                        }
                    });
                }
            });
    }

    // ================================================================
    // CHARTS (Dark Theme)
    // ================================================================
    var chartTextColor = 'rgba(200, 220, 230, 0.5)';
    var chartGridColor = 'rgba(0, 212, 255, 0.04)';
    fetch('/api/get_microplastics.php')
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success || !res.data) return;
            var data = res.data;

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
                            backgroundColor: ['#00d4ff','#0d9488','#0891b2','#059669','#6366f1','#8b5cf6','#f59e0b','#ef4444','#ec4899','#64748b','#a3a3a3'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: chartTextColor, font: { family: 'Inter, Outfit, sans-serif', size: 11 }, padding: 14, usePointStyle: true, pointStyleWidth: 8 }
                            }
                        }
                    }
                });
            }

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
                            backgroundColor: ['#0d9488', '#f59e0b', '#f97316', '#ef4444', '#991b1b'],
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
                                ticks: { color: chartTextColor, font: { family: 'Inter, Outfit' } },
                                grid: { color: chartGridColor }
                            },
                            x: {
                                ticks: { color: chartTextColor, font: { family: 'Inter, Outfit', size: 11 } },
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
