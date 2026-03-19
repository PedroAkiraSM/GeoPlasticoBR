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

<!-- ============ FLOATING ORBS (Soft Background Accents) ============ -->
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
        <div class="lg-hero__content">
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
                <a href="/sobre.php" class="lg-btn lg-btn--glass-hero"><?php echo htmlspecialchars($_homeBlocks['hero_cta_secondary'] ?? 'Sobre o Projeto'); ?></a>
            </div>
        </div>

        <!-- Liquid Glass Stats Card -->
        <div class="lg-glass lg-glass--hero" id="heroGlassCard">
            <div class="lg-glass__shine"></div>
            <div class="lg-glass__header">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                Dados em Tempo Real
            </div>
            <div class="lg-glass__stats">
                <div class="lg-glass__stat-item">
                    <span class="lg-glass__num" data-count="<?php echo $statsPoints; ?>">0</span>
                    <span class="lg-glass__label"><?php echo htmlspecialchars($_homeBlocks['stats_label_1'] ?? 'Pontos'); ?></span>
                </div>
                <div class="lg-glass__divider"></div>
                <div class="lg-glass__stat-item">
                    <span class="lg-glass__num" data-count="<?php echo $statsEcosystems; ?>">0</span>
                    <span class="lg-glass__label"><?php echo htmlspecialchars($_homeBlocks['stats_label_2'] ?? 'Categorias'); ?></span>
                </div>
                <div class="lg-glass__divider"></div>
                <div class="lg-glass__stat-item">
                    <span class="lg-glass__num" data-count="<?php echo $statsRecords; ?>">0</span>
                    <span class="lg-glass__label"><?php echo htmlspecialchars($_homeBlocks['stats_label_3'] ?? 'Registros'); ?></span>
                </div>
            </div>
            <a href="/mapa.php" class="lg-glass__cta">
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
                <p class="lg-p"><?php echo htmlspecialchars($_homeBlocks['problem_description'] ?? 'Fragmentos de plastico menores que 5mm contaminam silenciosamente nossos oceanos, rios e lagos. Uma crise ambiental invisivel que afeta toda a cadeia alimentar.'); ?></p>
            </div>
            <div class="lg-problem__stats">
                <div class="lg-glass lg-glass--stat">
                    <span class="lg-glass__num lg-glass__num--dark" data-count="<?php echo $statsPoints; ?>">0</span>
                    <span class="lg-glass__stat-label"><?php echo htmlspecialchars($_homeBlocks['stats_label_1'] ?? 'Pontos de Coleta'); ?></span>
                    <span class="lg-glass__stat-sub"><?php echo htmlspecialchars($_homeBlocks['stats_desc_1'] ?? 'Mapeados em todo o Brasil'); ?></span>
                </div>
                <div class="lg-glass lg-glass--stat">
                    <span class="lg-glass__num lg-glass__num--dark" data-count="<?php echo $statsEcosystems; ?>">0</span>
                    <span class="lg-glass__stat-label"><?php echo htmlspecialchars($_homeBlocks['stats_label_2'] ?? 'Categorias'); ?></span>
                    <span class="lg-glass__stat-sub"><?php echo htmlspecialchars($_homeBlocks['stats_desc_2'] ?? 'Tipos de amostras monitoradas'); ?></span>
                </div>
                <div class="lg-glass lg-glass--stat">
                    <span class="lg-glass__num lg-glass__num--dark" data-count="<?php echo $statsRecords; ?>">0</span>
                    <span class="lg-glass__stat-label"><?php echo htmlspecialchars($_homeBlocks['stats_label_3'] ?? 'Registros Cientificos'); ?></span>
                    <span class="lg-glass__stat-sub"><?php echo htmlspecialchars($_homeBlocks['stats_desc_3'] ?? 'Dados verificados por pares'); ?></span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ MAP FEATURE ============ -->
<section class="lg-section lg-section--tinted">
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
            <div class="lg-glass lg-glass--chart">
                <h3 class="lg-chart-title">Registros por Ecossistema</h3>
                <canvas id="chartEcossistema"></canvas>
            </div>
            <div class="lg-glass lg-glass--chart">
                <h3 class="lg-chart-title">Niveis de Concentracao</h3>
                <canvas id="chartConcentracao"></canvas>
            </div>
        </div>
    </div>
</section>

<!-- ============ MISSION ============ -->
<section class="lg-section lg-section--tinted">
    <div class="lg-container">
        <div class="lg-eyebrow">Nossa Missao</div>
        <h2 class="lg-h2" style="text-align:center; margin-bottom: 3rem;"><?php echo htmlspecialchars($_homeBlocks['mission_title'] ?? 'Democratizar dados cientificos'); ?></h2>
        <div class="lg-mission-grid">
            <div class="lg-glass lg-glass--mission">
                <div class="lg-mission-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <h3>Dados Verificados</h3>
                <p>Pesquisas cientificas revisadas por pares garantindo confiabilidade e precisao.</p>
            </div>
            <div class="lg-glass lg-glass--mission">
                <div class="lg-mission-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                </div>
                <h3>Acesso Aberto</h3>
                <p>Plataforma gratuita para pesquisadores, estudantes e o publico em geral.</p>
            </div>
            <div class="lg-glass lg-glass--mission">
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
        <p class="lg-cta__desc"><?php echo htmlspecialchars($_homeBlocks['cta_description'] ?? 'Descubra a distribuicao de microplasticos nos ecossistemas aquaticos brasileiros.'); ?></p>
        <div class="lg-cta__actions">
            <a href="/mapa.php" class="lg-btn lg-btn--primary lg-btn--lg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>
                <span>Acessar Mapa Agora</span>
            </a>
            <a href="/contribuir.php" class="lg-btn lg-btn--glass-hero">Contribuir com Dados</a>
        </div>
    </div>
</section>

<!-- ============ STYLES ============ -->
<style>
/* ================================================================
   DESIGN TOKENS — LIGHT OCEAN + LIQUID GLASS
   ================================================================ */
:root {
    --lg-bg: #f4f9fb;
    --lg-bg-tinted: #eaf3f7;
    --lg-glass-bg: rgba(255, 255, 255, 0.65);
    --lg-glass-bg-hover: rgba(255, 255, 255, 0.8);
    --lg-glass-border: rgba(0, 80, 120, 0.1);
    --lg-glass-border-hover: rgba(8, 145, 178, 0.25);
    --lg-glass-blur: 25px;
    --lg-accent: #0891b2;
    --lg-accent-teal: #0d9488;
    --lg-accent-light: #e6f7fa;
    --lg-text: #0f2b3c;
    --lg-text-secondary: #5a7a8a;
    --lg-text-muted: #8aa0ab;
    --lg-font: 'Inter', 'Outfit', sans-serif;
    --lg-ease: cubic-bezier(0.16, 1, 0.3, 1);
    --lg-radius: 24px;
}

/* === Typography overrides for Liquid Glass sections === */
.lg-hero h1, .lg-hero h2, .lg-hero h3,
.lg-section h1, .lg-section h2, .lg-section h3,
.lg-cta h1, .lg-cta h2, .lg-cta h3,
.lg-h2 {
    color: var(--lg-text);
    font-family: var(--lg-font);
}
.lg-hero h1, .lg-hero .lg-h2 { color: #ffffff; }
.lg-cta h1, .lg-cta .lg-cta__title { color: #ffffff; }

.lg-section p, .lg-p {
    color: var(--lg-text-secondary);
    font-family: var(--lg-font);
}
.lg-hero p, .lg-hero .lg-hero__desc { color: rgba(255, 255, 255, 0.65); }
.lg-cta p, .lg-cta .lg-cta__desc { color: rgba(255, 255, 255, 0.6); }

.lg-glass--mission h3 {
    color: var(--lg-text);
    font-size: 1.05rem;
    font-weight: 700;
}
.lg-glass--mission p {
    color: var(--lg-text-muted);
    font-size: 0.88rem;
}

/* ================================================================
   FLOATING ORBS — Soft light accents behind glass
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
    filter: blur(100px);
}

.lg-orb--1 {
    width: 700px; height: 700px;
    background: radial-gradient(circle, rgba(8, 145, 178, 0.35) 0%, rgba(56, 189, 248, 0.15) 40%, transparent 70%);
    top: 10%; left: -10%;
}
.lg-orb--2 {
    width: 600px; height: 600px;
    background: radial-gradient(circle, rgba(13, 148, 136, 0.30) 0%, rgba(45, 212, 191, 0.12) 40%, transparent 70%);
    top: 40%; right: -8%;
}
.lg-orb--3 {
    width: 550px; height: 550px;
    background: radial-gradient(circle, rgba(8, 145, 178, 0.25) 0%, rgba(14, 165, 233, 0.10) 40%, transparent 70%);
    bottom: 10%; left: 20%;
}
.lg-orb--4 {
    width: 500px; height: 500px;
    background: radial-gradient(circle, rgba(99, 102, 241, 0.20) 0%, rgba(139, 92, 246, 0.08) 40%, transparent 70%);
    top: 65%; right: 20%;
}

/* ================================================================
   HERO SECTION (Dark — contrasts with light body)
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

.lg-hero__bg { position: absolute; inset: 0; }

.lg-hero__video {
    width: 100%; height: 100%;
    object-fit: cover;
    filter: brightness(0.35) saturate(0.7);
}

.lg-hero__overlay {
    position: absolute; inset: 0;
    background:
        linear-gradient(180deg, rgba(0, 20, 40, 0.5) 0%, rgba(0, 20, 40, 0.3) 50%, rgba(0, 20, 40, 0.7) 100%),
        radial-gradient(ellipse 60% 50% at 30% 50%, rgba(8, 145, 178, 0.08) 0%, transparent 60%);
}

.lg-hero__inner {
    position: relative; z-index: 10;
    width: 100%; max-width: 1300px;
    margin: 0 auto;
    padding: 140px 48px 80px;
    display: flex; align-items: center;
    justify-content: space-between;
    gap: 60px; min-height: 100vh;
}

.lg-hero__content { flex: 1; max-width: 620px; }

.lg-hero__badge {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 6px 18px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 50px;
    font-size: 0.7rem; font-weight: 600;
    color: rgba(255, 255, 255, 0.85);
    text-transform: uppercase; letter-spacing: 0.14em;
    margin-bottom: 32px;
    opacity: 0; transform: translateY(20px);
}

.lg-hero__badge-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: #5eead4;
    box-shadow: 0 0 8px rgba(94, 234, 212, 0.5);
    animation: lgDotPulse 2s ease-in-out infinite;
}

.lg-hero__title {
    font-weight: 800;
    font-size: clamp(3rem, 6vw, 5rem);
    line-height: 1.05; color: #ffffff;
    margin-bottom: 24px; letter-spacing: -0.04em;
    opacity: 0; transform: translateY(30px);
}

.lg-hero__title--glow {
    background: linear-gradient(135deg, #5eead4 0%, #0891b2 50%, #5eead4 100%);
    background-size: 200% auto;
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
}

.lg-hero__desc {
    font-size: 1.05rem; color: rgba(255, 255, 255, 0.65);
    line-height: 1.75; max-width: 460px;
    margin-bottom: 32px;
    opacity: 0; transform: translateY(20px);
}

.lg-hero__actions {
    display: flex; gap: 12px; flex-wrap: wrap;
    opacity: 0; transform: translateY(20px);
}

/* ================================================================
   BUTTONS
   ================================================================ */
.lg-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 13px 28px;
    font-family: var(--lg-font); font-weight: 600; font-size: 0.88rem;
    text-decoration: none; border-radius: 14px;
    transition: all 0.4s var(--lg-ease);
    cursor: pointer; border: none;
}

.lg-btn--primary {
    background: linear-gradient(135deg, var(--lg-accent), var(--lg-accent-teal));
    color: #ffffff;
    box-shadow: 0 4px 20px rgba(8, 145, 178, 0.3);
}
.lg-btn--primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(8, 145, 178, 0.4);
}

.lg-btn--glass-hero {
    background: rgba(255, 255, 255, 0.12);
    color: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
}
.lg-btn--glass-hero:hover {
    background: rgba(255, 255, 255, 0.2);
    border-color: rgba(255, 255, 255, 0.4);
    color: #ffffff;
}

.lg-btn--glass {
    background: var(--lg-glass-bg);
    color: var(--lg-text);
    border: 1px solid var(--lg-glass-border);
    backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
}
.lg-btn--glass:hover {
    background: var(--lg-glass-bg-hover);
    border-color: var(--lg-glass-border-hover);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

.lg-btn--lg { padding: 16px 36px; font-size: 0.95rem; }

/* ================================================================
   GLASS CARD — Liquid Glass with SVG Displacement
   ================================================================ */
.lg-glass {
    position: relative;
    border-radius: var(--lg-radius);
    padding: 28px;
    background: transparent;
    transition: all 0.5s var(--lg-ease);
    isolation: isolate;
    overflow: hidden;
}

/* Inner glow border + glass tint */
.lg-glass::before {
    content: '';
    position: absolute;
    inset: 0;
    z-index: 1;
    border-radius: var(--lg-radius);
    box-shadow:
        inset 2px 2px 0px -1px rgba(255, 255, 255, 0.7),
        inset 0 0 3px 1px rgba(255, 255, 255, 0.6),
        0 8px 32px rgba(0, 0, 0, 0.06),
        0 2px 8px rgba(0, 0, 0, 0.03);
    background: rgba(255, 255, 255, 0.12);
    pointer-events: none;
}

/* SVG displacement distortion layer */
.lg-glass::after {
    content: '';
    position: absolute;
    z-index: -1;
    inset: 0;
    border-radius: var(--lg-radius);
    backdrop-filter: blur(0px);
    -webkit-backdrop-filter: blur(0px);
    filter: url(#liquid-glass-subtle);
    -webkit-filter: url(#liquid-glass-subtle);
    overflow: hidden;
    isolation: isolate;
}

.lg-glass:hover::before {
    box-shadow:
        inset 2px 2px 0px -1px rgba(255, 255, 255, 0.9),
        inset 0 0 4px 2px rgba(255, 255, 255, 0.7),
        0 16px 48px rgba(0, 0, 0, 0.08),
        0 4px 12px rgba(0, 0, 0, 0.04);
    background: rgba(255, 255, 255, 0.18);
}

.lg-glass:hover {
    transform: translateY(-4px);
}

.lg-glass > *:not(.lg-glass__shine) { position: relative; z-index: 2; }

/* Colored blob behind each glass card for refraction */
.lg-glass--stat { overflow: visible; }
.lg-glass--stat::before { overflow: hidden; }

/* Section-level colored blobs for glass refraction */
.lg-section::before {
    content: '';
    position: absolute;
    inset: 0;
    z-index: 0;
    pointer-events: none;
    background:
        radial-gradient(circle 250px at 70% 30%, rgba(8, 145, 178, 0.25) 0%, transparent 70%),
        radial-gradient(circle 200px at 30% 70%, rgba(13, 148, 136, 0.20) 0%, transparent 70%),
        radial-gradient(circle 180px at 85% 80%, rgba(99, 102, 241, 0.12) 0%, transparent 70%),
        radial-gradient(circle 220px at 15% 20%, rgba(56, 189, 248, 0.15) 0%, transparent 70%);
}
.lg-section--tinted::before {
    background:
        radial-gradient(circle 280px at 25% 40%, rgba(8, 145, 178, 0.28) 0%, transparent 65%),
        radial-gradient(circle 230px at 80% 60%, rgba(13, 148, 136, 0.22) 0%, transparent 65%),
        radial-gradient(circle 200px at 60% 15%, rgba(139, 92, 246, 0.10) 0%, transparent 70%),
        radial-gradient(circle 250px at 45% 85%, rgba(56, 189, 248, 0.18) 0%, transparent 65%);
}

/* Hero glass card (dark context — opacity controlled by GSAP) */
.lg-glass--hero {
    width: 360px;
    flex-shrink: 0;
    background: rgba(255, 255, 255, 0.12);
    border-color: rgba(255, 255, 255, 0.2);
    box-shadow:
        0 24px 80px rgba(0, 0, 0, 0.3),
        0 8px 24px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.25);
    opacity: 0;
    transform: translateY(30px);
}
.lg-glass--hero::before {
    background: linear-gradient(135deg,
        rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 40%,
        rgba(255,255,255,0.05) 100%);
}

.lg-glass__header {
    display: flex; align-items: center; gap: 8px;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.72rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.1em;
    padding-bottom: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.12);
    margin-bottom: 20px;
}

.lg-glass__stats { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
.lg-glass__stat-item { flex: 1; text-align: center; }

.lg-glass__num {
    display: block; font-weight: 800;
    font-size: 2.2rem; color: #ffffff;
    line-height: 1; letter-spacing: -0.04em;
}
.lg-glass__num--dark { color: var(--lg-accent); }

.lg-glass__label {
    display: block; font-size: 0.65rem;
    color: rgba(255, 255, 255, 0.5);
    margin-top: 4px; text-transform: uppercase; letter-spacing: 0.08em;
}

.lg-glass__divider { width: 1px; height: 36px; background: rgba(255, 255, 255, 0.12); }

.lg-glass__cta {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 12px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.8rem; font-weight: 600;
    text-decoration: none;
    transition: all 0.35s var(--lg-ease);
}
.lg-glass__cta:hover {
    background: rgba(255, 255, 255, 0.18);
    color: #ffffff;
}

/* Stat card (light context) */
.lg-glass--stat { padding: 24px 28px; }
.lg-glass__stat-label {
    display: block; font-size: 0.9rem; font-weight: 600;
    color: var(--lg-text); margin-top: 6px;
}
.lg-glass__stat-sub {
    display: block; font-size: 0.78rem;
    color: var(--lg-text-muted); margin-top: 2px;
}

/* Scroll indicator */
.lg-hero__scroll { position: absolute; bottom: 32px; left: 50%; transform: translateX(-50%); z-index: 10; }
.lg-hero__scroll-line {
    width: 1px; height: 48px;
    background: linear-gradient(to bottom, rgba(255,255,255,0.4), transparent);
    animation: lgScrollPulse 2s ease-in-out infinite;
}

/* ================================================================
   SECTIONS — mesh gradient backgrounds for glass refraction
   ================================================================ */
.lg-section {
    position: relative;
    padding: clamp(80px, 10vw, 120px) 0;
    z-index: 1;
    background:
        radial-gradient(ellipse 60% 50% at 10% 80%, rgba(8, 145, 178, 0.18) 0%, transparent 60%),
        radial-gradient(ellipse 50% 60% at 90% 20%, rgba(13, 148, 136, 0.15) 0%, transparent 55%),
        radial-gradient(circle at 50% 50%, rgba(56, 189, 248, 0.08) 0%, transparent 45%),
        linear-gradient(180deg, #eaf4f8 0%, #ddeaf1 100%);
}
.lg-section--tinted {
    background:
        radial-gradient(ellipse 55% 50% at 75% 70%, rgba(8, 145, 178, 0.22) 0%, transparent 55%),
        radial-gradient(ellipse 45% 55% at 15% 30%, rgba(13, 148, 136, 0.18) 0%, transparent 50%),
        radial-gradient(circle at 40% 10%, rgba(56, 189, 248, 0.10) 0%, transparent 40%),
        linear-gradient(180deg, #dde9f1 0%, #d0e2ec 100%);
}

.lg-container { max-width: 1200px; margin: 0 auto; padding: 0 48px; }

.lg-eyebrow {
    display: inline-block;
    font-size: 0.7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.18em;
    color: var(--lg-accent);
    margin-bottom: 16px; padding: 5px 14px;
    background: var(--lg-accent-light);
    border: 1px solid rgba(8, 145, 178, 0.12);
    border-radius: 8px;
}

.lg-h2 {
    font-weight: 800;
    font-size: clamp(2rem, 3.5vw, 2.8rem);
    color: var(--lg-text);
    letter-spacing: -0.035em;
    margin-bottom: 16px; line-height: 1.1;
}

.lg-p {
    font-size: 1rem; color: var(--lg-text-secondary);
    line-height: 1.8; max-width: 540px;
}

/* Problem */
.lg-problem { display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: start; margin-top: 8px; }
.lg-problem__stats { display: flex; flex-direction: column; gap: 16px; }

/* Map Feature */
.lg-map-feature { display: grid; grid-template-columns: 1fr 1.2fr; gap: 56px; align-items: center; margin-top: 8px; }
.lg-checklist { display: flex; flex-direction: column; gap: 12px; margin-top: 24px; }
.lg-checklist__item { display: flex; align-items: center; gap: 12px; color: var(--lg-accent-teal); }
.lg-checklist__item span { font-size: 0.9rem; color: var(--lg-text-secondary); }

/* Map browser */
.lg-map-browser {
    border-radius: 20px; overflow: hidden;
    border: 1px solid var(--lg-glass-border);
    background: var(--lg-glass-bg);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0,0,0,0.04);
    transition: all 0.5s var(--lg-ease);
}
.lg-map-browser:hover {
    transform: translateY(-4px);
    box-shadow: 0 28px 80px rgba(0, 0, 0, 0.12);
}
.lg-map-browser__bar {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px;
    background: rgba(245, 247, 249, 0.8);
    border-bottom: 1px solid var(--lg-glass-border);
}
.lg-map-browser__dots { display: flex; gap: 6px; }
.lg-map-browser__dots span { width: 10px; height: 10px; border-radius: 50%; opacity: 0.8; }
.lg-map-browser__dots span:nth-child(1) { background: #FF5F57; }
.lg-map-browser__dots span:nth-child(2) { background: #FFBD2E; }
.lg-map-browser__dots span:nth-child(3) { background: #28CA41; }
.lg-map-browser__url {
    flex: 1; text-align: center; font-size: 0.72rem;
    color: var(--lg-text-muted); background: #eef1f4;
    padding: 5px 16px; border-radius: 6px;
}
.lg-map-browser__content { aspect-ratio: 16/10; }

/* Charts */
.lg-charts { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.lg-glass--chart { padding: 28px; }
.lg-chart-title {
    font-size: 0.88rem; font-weight: 700;
    color: var(--lg-text); margin-bottom: 20px; text-align: center;
}

/* Mission */
.lg-mission-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.lg-glass--mission { padding: 32px; }
.lg-mission-icon {
    width: 48px; height: 48px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 14px;
    background: var(--lg-accent-light);
    border: 1px solid rgba(8, 145, 178, 0.1);
    color: var(--lg-accent);
    margin-bottom: 20px;
}
.lg-glass--mission h3 { font-size: 1.05rem; font-weight: 700; color: var(--lg-text); margin-bottom: 8px; }
.lg-glass--mission p { font-size: 0.88rem; color: var(--lg-text-muted); line-height: 1.7; }

/* CTA */
.lg-cta {
    position: relative;
    padding: clamp(80px, 10vw, 120px) 0;
    z-index: 1; overflow: hidden;
    background: linear-gradient(135deg, #0a3d5c 0%, #0d5e6e 40%, #0a4a5c 100%);
}
.lg-cta::before {
    content: ''; position: absolute; inset: 0;
    background:
        radial-gradient(ellipse 60% 50% at 20% 80%, rgba(94, 234, 212, 0.1) 0%, transparent 60%),
        radial-gradient(ellipse 40% 50% at 80% 20%, rgba(8, 145, 178, 0.15) 0%, transparent 60%);
    pointer-events: none;
}
.lg-cta__inner { text-align: center; position: relative; z-index: 2; }
.lg-cta__title {
    font-weight: 800;
    font-size: clamp(2.2rem, 4vw, 3.2rem);
    color: #ffffff; margin-bottom: 16px; letter-spacing: -0.035em;
}
.lg-cta__desc {
    font-size: 1.05rem; color: rgba(255, 255, 255, 0.6);
    margin: 0 auto 32px; max-width: 440px; line-height: 1.7;
}
.lg-cta__actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

/* ================================================================
   ANIMATIONS
   ================================================================ */
@keyframes lgDotPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.4; transform: scale(0.6); }
}
@keyframes lgScrollPulse {
    0%, 100% { opacity: 0.3; }
    50% { opacity: 0.8; }
}

/* ================================================================
   RESPONSIVE
   ================================================================ */
@media (max-width: 1024px) {
    .lg-hero__inner { flex-direction: column; justify-content: center; padding: 120px 32px 60px; gap: 40px; }
    .lg-hero__content { max-width: 100%; text-align: center; }
    .lg-hero__desc { margin-left: auto; margin-right: auto; }
    .lg-hero__actions { justify-content: center; }
    .lg-glass--hero { width: 100%; max-width: 400px; align-self: center; }
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
    .lg-glass--hero { max-width: 100%; width: 100%; }
}

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

    // Hero entrance
    var heroTl = gsap.timeline({ delay: 0.2 });
    heroTl
        .to('.lg-hero__badge', { opacity: 1, y: 0, duration: 0.7, ease: 'power3.out' })
        .to('.lg-hero__title', { opacity: 1, y: 0, duration: 0.8, ease: 'power3.out' }, '-=0.4')
        .to('.lg-hero__desc', { opacity: 1, y: 0, duration: 0.8, ease: 'power3.out' }, '-=0.5')
        .to('.lg-hero__actions', { opacity: 1, y: 0, duration: 0.8, ease: 'power3.out' }, '-=0.5')
        .to('.lg-glass--hero', { opacity: 1, y: 0, duration: 1, ease: 'power3.out' }, '-=0.6');

    // Title gradient animation
    gsap.to('.lg-hero__title--glow', {
        backgroundPosition: '200% center',
        duration: 4, ease: 'none', repeat: -1
    });

    // Floating orbs
    gsap.utils.toArray('.lg-orb').forEach(function(orb, i) {
        gsap.to(orb, {
            x: 'random(-80, 80)', y: 'random(-60, 60)',
            duration: 18 + i * 4, ease: 'sine.inOut',
            repeat: -1, yoyo: true, delay: i * 2
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
                rotateY: deltaX * 8, rotateX: -deltaY * 6,
                duration: 0.8, ease: 'power2.out',
                transformPerspective: 1000
            });

            var shine = heroCard.querySelector('.lg-glass__shine');
            if (shine) {
                gsap.to(shine, {
                    background: 'linear-gradient(' +
                        (135 + deltaX * 30) + 'deg, ' +
                        'rgba(255,255,255,' + (0.15 + Math.abs(deltaX) * 0.1) + ') 0%, ' +
                        'rgba(255,255,255,0) 40%, ' +
                        'rgba(255,255,255,' + (0.05 + Math.abs(deltaY) * 0.08) + ') 100%)',
                    duration: 0.5, ease: 'power2.out'
                });
            }
        });

        document.addEventListener('mouseleave', function() {
            gsap.to(heroCard, { rotateY: 0, rotateX: 0, duration: 1, ease: 'power3.out' });
        });
    }

    // ScrollTrigger for sections — use set + to instead of from to avoid flash
    gsap.utils.toArray('.lg-section, .lg-cta').forEach(function(section) {
        var elements = section.querySelectorAll('.lg-glass, .lg-h2, .lg-p, .lg-eyebrow, .lg-checklist__item, .lg-map-browser, .lg-chart-title');
        gsap.set(elements, { opacity: 0, y: 40 });
        ScrollTrigger.create({
            trigger: section,
            start: 'top 85%',
            once: true,
            onEnter: function() {
                gsap.to(elements, { opacity: 1, y: 0, duration: 0.8, stagger: 0.1, ease: 'power3.out' });
            }
        });
    });

    var ctaEls = document.querySelectorAll('.lg-cta__title, .lg-cta__desc, .lg-cta__actions');
    if (ctaEls.length) {
        gsap.set(ctaEls, { opacity: 0, y: 40 });
        ScrollTrigger.create({
            trigger: '.lg-cta',
            start: 'top 85%',
            once: true,
            onEnter: function() {
                gsap.to(ctaEls, { opacity: 1, y: 0, duration: 0.8, stagger: 0.15, ease: 'power3.out' });
            }
        });
    }

    // Glass card hover glow with GSAP
    document.querySelectorAll('.lg-glass:not(.lg-glass--hero)').forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            gsap.to(card, {
                boxShadow: '0 20px 60px rgba(0,0,0,0.1), 0 0 0 1px rgba(8,145,178,0.15), inset 0 1px 0 rgba(255,255,255,0.9)',
                duration: 0.4, ease: 'power2.out'
            });
        });
        card.addEventListener('mouseleave', function() {
            gsap.to(card, {
                boxShadow: '0 8px 32px rgba(0,0,0,0.06), 0 1px 3px rgba(0,0,0,0.04), inset 0 1px 0 rgba(255,255,255,0.8)',
                duration: 0.5, ease: 'power2.out'
            });
        });
    });

    // ================================================================
    // COUNTER ANIMATIONS
    // ================================================================
    function animateCounters(container) {
        container.querySelectorAll('[data-count]').forEach(function(el) {
            var target = parseInt(el.getAttribute('data-count'), 10);
            if (isNaN(target) || el.dataset.animated) return;
            el.dataset.animated = '1';
            gsap.fromTo(el, { innerText: 0 }, {
                innerText: target, duration: 1.8, ease: 'power2.out',
                snap: { innerText: 1 },
                scrollTrigger: { trigger: el, start: 'top 90%' }
            });
        });
    }

    setTimeout(function() {
        var hc = document.querySelector('.lg-glass--hero');
        if (hc) {
            hc.querySelectorAll('[data-count]').forEach(function(el) {
                var target = parseInt(el.getAttribute('data-count'), 10);
                if (isNaN(target) || el.dataset.animated) return;
                el.dataset.animated = '1';
                gsap.fromTo(el, { innerText: 0 }, {
                    innerText: target, duration: 1.8, ease: 'power2.out', snap: { innerText: 1 }
                });
            });
        }
    }, 1200);

    document.querySelectorAll('.lg-section').forEach(function(section) {
        ScrollTrigger.create({
            trigger: section, start: 'top 80%',
            onEnter: function() { animateCounters(section); }
        });
    });

    // ================================================================
    // PREVIEW MAP
    // ================================================================
    var pm = document.getElementById('previewMap');
    if (pm) {
        var previewMap = L.map(pm, {
            center: [-15.78, -47.93], zoom: 4,
            zoomControl: false, attributionControl: false,
            dragging: false, scrollWheelZoom: false,
            doubleClickZoom: false, touchZoom: false
        });
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }).addTo(previewMap);

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
                                radius: 5, fillColor: getColor(d.concentration_value),
                                color: getColor(d.concentration_value), fillOpacity: 0.7, weight: 0
                            }).addTo(previewMap);
                        }
                    });
                }
            });
    }

    // ================================================================
    // CHARTS
    // ================================================================
    var chartTextColor = '#5a7a8a';
    var chartGridColor = 'rgba(0,0,0,0.04)';
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
                        datasets: [{ data: ecoValues,
                            backgroundColor: ['#0891b2','#0d9488','#0284c7','#059669','#6366f1','#8b5cf6','#f59e0b','#ef4444','#ec4899','#64748b','#a3a3a3'],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true, cutout: '65%',
                        plugins: { legend: { position: 'bottom',
                            labels: { color: chartTextColor, font: { family: 'Inter, Outfit, sans-serif', size: 11 }, padding: 14, usePointStyle: true, pointStyleWidth: 8 }
                        }}
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
                        datasets: [{ data: Object.values(levels),
                            backgroundColor: ['#0d9488', '#f59e0b', '#f97316', '#ef4444', '#991b1b'],
                            borderRadius: 8, borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { color: chartTextColor, font: { family: 'Inter, Outfit' } }, grid: { color: chartGridColor } },
                            x: { ticks: { color: chartTextColor, font: { family: 'Inter, Outfit', size: 11 } }, grid: { display: false } }
                        }
                    }
                });
            }
        });
});
</script>

<?php include 'includes/footer.php'; ?>
