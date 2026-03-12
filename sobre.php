<?php
require_once __DIR__ . '/auth.php';
requireLogin();
$pageTitle = "Sobre o Projeto - GeoPlasticoBR";
$pageDescription = "Conhea o GeoPlasticoBR: plataforma de mapeamento de microplasticos nos ecossistemas aquaticos brasileiros.";
include 'includes/header.php';
?>

<!-- Hero -->
<section class="sobre-hero fade-in">
    <div class="content-container">
        <span class="section-tag">Sobre o Projeto</span>
        <h1 class="sobre-title">GeoPlasticoBR</h1>
        <p class="sobre-subtitle">
            Uma plataforma cientifica de mapeamento de microplasticos
            nos ecossistemas aquaticos brasileiros.
        </p>
    </div>
</section>

<!-- O que e -->
<section class="content-section fade-in">
    <div class="content-container">
        <div class="about-block">
            <div class="about-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
            </div>
            <h2 class="about-title">O que e o GeoPlasticoBR?</h2>
            <p class="about-text">
                O GeoPlasticoBR e uma plataforma digital que reune e visualiza dados cientificos sobre a
                presenca de microplasticos em ecossistemas aquaticos brasileiros. A partir de pesquisas
                publicadas e revisadas por pares, o projeto compila informacoes de concentracao, localizacao
                e tipo de microplasticos encontrados em rios, lagos, praias e oceanos do Brasil.
            </p>
            <p class="about-text">
                Microplasticos sao fragmentos de plastico menores que 5mm, praticamente invisiveis a olho nu,
                mas que representam uma seria ameaca aos ecossistemas aquaticos e a saude humana. Eles provem
                da degradacao de plasticos maiores, de cosmeticos, tecidos sinteticos e processos industriais.
            </p>
        </div>
    </div>
</section>

<!-- Objetivos -->
<section class="content-section dark-section fade-in">
    <div class="content-container">
        <div class="section-header">
            <span class="section-tag">Objetivos</span>
            <h2 class="section-title">Por que este projeto existe?</h2>
        </div>
        <div class="objectives-grid">
            <div class="objective-card">
                <div class="objective-number">01</div>
                <h3>Centralizar Dados</h3>
                <p>Reunir em uma unica plataforma os dados dispersos em dezenas de publicacoes cientificas sobre microplasticos no Brasil.</p>
            </div>
            <div class="objective-card">
                <div class="objective-number">02</div>
                <h3>Visualizar Espacialmente</h3>
                <p>Permitir a visualizacao geografica dos dados, revelando padroes e areas criticas de contaminacao.</p>
            </div>
            <div class="objective-card">
                <div class="objective-number">03</div>
                <h3>Democratizar o Acesso</h3>
                <p>Tornar dados cientificos acessiveis para pesquisadores, estudantes, gestores ambientais e o publico em geral.</p>
            </div>
            <div class="objective-card">
                <div class="objective-number">04</div>
                <h3>Apoiar Politicas Publicas</h3>
                <p>Fornecer evidencias para a formulacao de politicas ambientais e acoes de combate a poluicao plastica.</p>
            </div>
        </div>
    </div>
</section>

<!-- Metodologia -->
<section class="content-section fade-in">
    <div class="content-container">
        <div class="about-block">
            <div class="about-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            </div>
            <h2 class="about-title">Metodologia</h2>
            <p class="about-text">
                Os dados apresentados no GeoPlasticoBR sao extraidos de artigos cientificos publicados em
                periodicos revisados por pares. Cada registro inclui informacoes sobre o tipo de ambiente
                (agua doce ou marinho), ecossistema, ponto de amostragem, coordenadas geograficas,
                concentracao de microplasticos, matriz analisada (sedimento ou agua) e a referencia bibliografica.
            </p>
            <p class="about-text">
                Os dados passam por um processo de curadoria antes de serem publicados no mapa, garantindo
                a qualidade e confiabilidade das informacoes apresentadas.
            </p>
        </div>
    </div>
</section>

<!-- Funcionalidades -->
<section class="content-section dark-section fade-in">
    <div class="content-container">
        <div class="section-header">
            <span class="section-tag">Funcionalidades</span>
            <h2 class="section-title">O que voce pode fazer</h2>
        </div>
        <div class="features-list">
            <div class="feature-item">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#60A5FA" stroke-width="1.5"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg>
                <div>
                    <h4>Mapa Interativo</h4>
                    <p>Navegue por centenas de pontos de coleta com diferentes camadas de visualizacao, incluindo heatmap.</p>
                </div>
            </div>
            <div class="feature-item">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#60A5FA" stroke-width="1.5"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                <div>
                    <h4>Filtros Avancados</h4>
                    <p>Filtre por tipo de ambiente, ecossistema, matriz e faixa de concentracao.</p>
                </div>
            </div>
            <div class="feature-item">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#60A5FA" stroke-width="1.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <div>
                    <h4>Exportacao de Dados</h4>
                    <p>Exporte os dados filtrados em formato CSV para suas proprias analises.</p>
                </div>
            </div>
            <div class="feature-item">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#60A5FA" stroke-width="1.5"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                <div>
                    <h4>Contribuicao Colaborativa</h4>
                    <p>Pesquisadores podem submeter novos dados que serao revisados antes da publicacao.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section fade-in">
    <div class="content-container" style="text-align: center;">
        <h2 class="cta-title">Comece a Explorar</h2>
        <p class="cta-desc">Descubra a distribuicao de microplasticos nos ecossistemas aquaticos brasileiros.</p>
        <div class="hero-cta" style="justify-content: center;">
            <a href="/mapa.php" class="hero-btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg>
                Acessar Mapa
            </a>
            <a href="/contribuir.php" class="hero-btn-secondary">Contribuir com Dados</a>
        </div>
    </div>
</section>

<style>
/* ===== SOBRE HERO ===== */
.sobre-hero {
    padding: clamp(8rem, 14vw, 12rem) clamp(1.5rem, 5vw, 3rem) clamp(3rem, 6vw, 5rem);
    text-align: center;
}

.sobre-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: clamp(2.5rem, 6vw, 4rem);
    color: #ffffff;
    margin: 1rem 0;
    letter-spacing: -0.03em;
}

.sobre-subtitle {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: clamp(1rem, 1.8vw, 1.15rem);
    color: rgba(148, 163, 184, 0.7);
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.8;
}

/* ===== ABOUT BLOCKS ===== */
.about-block {
    max-width: 750px;
    margin: 0 auto;
}

.about-icon {
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    background: rgba(96, 165, 250, 0.08);
    border: 1px solid rgba(96, 165, 250, 0.15);
    color: #60A5FA;
    margin-bottom: 1.5rem;
}

.about-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: clamp(1.5rem, 3vw, 2rem);
    color: #ffffff;
    margin-bottom: 1.5rem;
}

.about-text {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1rem;
    color: rgba(148, 163, 184, 0.8);
    line-height: 1.8;
    margin-bottom: 1.2rem;
}

/* ===== OBJECTIVES ===== */
.objectives-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1.5rem;
}

.objective-card {
    padding: 2rem;
    background: rgba(15, 23, 42, 0.5);
    border: 1px solid rgba(148, 163, 184, 0.08);
    border-radius: 16px;
    transition: all 0.3s ease;
}

.objective-card:hover {
    border-color: rgba(96, 165, 250, 0.2);
    transform: translateY(-4px);
}

.objective-number {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 2rem;
    font-weight: 800;
    color: rgba(96, 165, 250, 0.2);
    margin-bottom: 0.75rem;
}

.objective-card h3 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.15rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.75rem;
}

.objective-card p {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.9rem;
    color: rgba(148, 163, 184, 0.7);
    line-height: 1.7;
}

/* ===== FEATURES LIST ===== */
.features-list {
    max-width: 700px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.feature-item {
    display: flex;
    gap: 1.25rem;
    padding: 1.5rem;
    background: rgba(15, 23, 42, 0.5);
    border: 1px solid rgba(148, 163, 184, 0.08);
    border-radius: 14px;
    transition: border-color 0.3s;
}

.feature-item:hover {
    border-color: rgba(96, 165, 250, 0.2);
}

.feature-item svg {
    flex-shrink: 0;
    margin-top: 2px;
}

.feature-item h4 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 0.4rem;
}

.feature-item p {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.9rem;
    color: rgba(148, 163, 184, 0.7);
    line-height: 1.6;
}

/* ===== SHARED ===== */
.content-section { padding: clamp(5rem, 10vw, 8rem) clamp(1.5rem, 5vw, 3rem); position: relative; }
.dark-section { background: rgba(8, 15, 30, 0.5); }
.content-container { max-width: 1200px; margin: 0 auto; }
.section-header { text-align: center; margin-bottom: clamp(3rem, 6vw, 5rem); }
.section-tag { display: inline-block; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.15em; color: #60A5FA; margin-bottom: 1rem; padding: 0.4rem 1.2rem; border: 1px solid rgba(96, 165, 250, 0.25); border-radius: 50px; }
.section-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: clamp(2.2rem, 5vw, 3.5rem); color: #ffffff; margin-bottom: 1.5rem; letter-spacing: -0.03em; }
.cta-section { padding: clamp(5rem, 10vw, 8rem) clamp(1.5rem, 5vw, 3rem); background: linear-gradient(180deg, transparent, rgba(96, 165, 250, 0.03), transparent); }
.cta-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: clamp(2rem, 4vw, 3rem); color: #ffffff; margin-bottom: 1rem; }
.cta-desc { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.1rem; color: rgba(148, 163, 184, 0.7); margin-bottom: 2.5rem; max-width: 500px; margin-left: auto; margin-right: auto; }
.hero-cta { display: flex; gap: 1rem; flex-wrap: wrap; }
.hero-btn-primary { display: inline-flex; align-items: center; gap: 0.5rem; padding: 1rem 2rem; background: #ffffff; color: #0F172A; font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; font-size: 0.95rem; border-radius: 50px; text-decoration: none; transition: all 0.3s ease; }
.hero-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 40px rgba(255, 255, 255, 0.2); }
.hero-btn-secondary { display: inline-flex; align-items: center; gap: 0.5rem; padding: 1rem 2rem; background: transparent; color: #ffffff; font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 600; font-size: 0.95rem; border-radius: 50px; text-decoration: none; border: 1px solid rgba(255, 255, 255, 0.3); transition: all 0.3s ease; }
.hero-btn-secondary:hover { border-color: rgba(255, 255, 255, 0.6); background: rgba(255, 255, 255, 0.08); }

/* Scroll animations */
.fade-in { opacity: 0; transform: translateY(40px); transition: opacity 0.8s ease-out, transform 0.8s ease-out; }
.fade-in.visible { opacity: 1; transform: translateY(0); }

@media (max-width: 768px) {
    .objectives-grid { grid-template-columns: 1fr; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) entry.target.classList.add('visible');
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.fade-in').forEach(function(el) { observer.observe(el); });
});
</script>

<?php include 'includes/footer.php'; ?>
