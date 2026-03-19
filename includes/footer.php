    <!-- Footer -->
    <?php
    $_footerSiteName = getSetting('site_name', 'GeoPlasticoBR');
    $_footerDesc = getSetting('site_description', 'Plataforma de mapeamento cientifico de microplasticos nos ecossistemas aquaticos brasileiros.');
    $_footerEmail = getSetting('contact_email', 'contato@geoplasticobr.com');
    $_footerText = getSetting('footer_text', 'Dados cientificos verificados por pares | Acesso aberto');
    $_footerVersion = getSetting('version_label', 'Beta');
    $_fbUrl = getSetting('facebook_url', '');
    $_igUrl = getSetting('instagram_url', '');
    $_liUrl = getSetting('linkedin_url', '');
    ?>
    <footer class="lg-footer">
        <div class="lg-footer__inner">
            <div class="lg-footer__grid">
                <div class="lg-footer__brand">
                    <h3><?php echo htmlspecialchars($_footerSiteName); ?></h3>
                    <p><?php echo htmlspecialchars($_footerDesc); ?></p>
                    <?php if ($_fbUrl || $_igUrl || $_liUrl): ?>
                    <div class="lg-footer__social">
                        <?php if ($_fbUrl): ?><a href="<?php echo htmlspecialchars($_fbUrl); ?>" target="_blank" rel="noopener">Facebook</a><?php endif; ?>
                        <?php if ($_igUrl): ?><a href="<?php echo htmlspecialchars($_igUrl); ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
                        <?php if ($_liUrl): ?><a href="<?php echo htmlspecialchars($_liUrl); ?>" target="_blank" rel="noopener">LinkedIn</a><?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="lg-footer__links">
                    <h4>Navegacao</h4>
                    <a href="/">Inicio</a>
                    <a href="/mapa.php">Mapa Interativo</a>
                    <a href="/sobre.php">Sobre o Projeto</a>
                    <a href="/contribuir.php">Contribuir</a>
                </div>
                <div class="lg-footer__links">
                    <h4>Recursos</h4>
                    <a href="/cadastro.php">Criar Conta</a>
                    <a href="/login.php">Login</a>
                    <a href="/sobre.php#metodologia">Metodologia</a>
                </div>
                <div class="lg-footer__links">
                    <h4>Contato</h4>
                    <p class="lg-footer__contact-text">Para duvidas, sugestoes ou parcerias entre em contato pelo email:</p>
                    <a href="mailto:<?php echo htmlspecialchars($_footerEmail); ?>"><?php echo htmlspecialchars($_footerEmail); ?></a>
                </div>
            </div>
            <?php if ($_footerVersion && $_footerVersion !== 'Estavel'): ?>
            <div class="lg-footer__beta">
                <p>Este site esta em fase <strong><?php echo htmlspecialchars($_footerVersion); ?></strong> — funcionalidades e dados podem mudar sem aviso previo.</p>
            </div>
            <?php endif; ?>
            <div class="lg-footer__bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($_footerSiteName); ?>. Todos os direitos reservados.</p>
                <p class="lg-footer__credits"><?php echo htmlspecialchars($_footerText); ?></p>
            </div>
        </div>
    </footer>

    <style>
    /* ================================================================
       LIQUID GLASS FOOTER — Light Theme
       ================================================================ */
    .lg-footer {
        position: relative;
        background: linear-gradient(180deg, #f0f7fa 0%, #e2eff5 100%);
        border-top: 1px solid rgba(0, 80, 120, 0.06);
        padding: clamp(3rem, 6vw, 5rem) clamp(1.5rem, 5vw, 3rem) 2rem;
        font-family: 'Inter', 'Outfit', sans-serif;
        overflow: hidden;
    }

    .lg-footer::before {
        content: '';
        position: absolute;
        top: -1px;
        left: 0;
        right: 0;
        height: 1px;
        background: linear-gradient(90deg,
            transparent 0%,
            rgba(8, 145, 178, 0.3) 20%,
            rgba(8, 145, 178, 0.1) 50%,
            rgba(13, 148, 136, 0.3) 80%,
            transparent 100%
        );
    }

    .lg-footer__inner {
        max-width: 1200px;
        margin: 0 auto;
        position: relative;
        z-index: 2;
    }

    .lg-footer__grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1.5fr;
        gap: 3rem;
        margin-bottom: 3rem;
    }

    .lg-footer__brand h3 {
        font-size: 1.3rem;
        font-weight: 800;
        color: #0f2b3c;
        margin-bottom: 0.75rem;
        letter-spacing: -0.02em;
    }

    .lg-footer__brand p {
        font-size: 0.88rem;
        color: rgba(15, 43, 60, 0.5);
        line-height: 1.7;
        max-width: 300px;
    }

    .lg-footer__links h4 {
        font-size: 0.75rem;
        font-weight: 700;
        color: #0891b2;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        margin-bottom: 1rem;
    }

    .lg-footer__links a,
    .lg-footer__contact-text {
        display: block;
        font-size: 0.88rem;
        color: rgba(15, 43, 60, 0.45);
        text-decoration: none;
        margin-bottom: 0.6rem;
        transition: all 0.25s;
    }
    .lg-footer__contact-text { cursor: default; }

    .lg-footer__links a:hover {
        color: #0891b2;
    }

    .lg-footer__social {
        display: flex;
        gap: 12px;
        margin-top: 0.75rem;
    }
    .lg-footer__social a {
        font-size: 0.82rem;
        color: rgba(15, 43, 60, 0.45);
        text-decoration: none;
        transition: all 0.25s;
    }
    .lg-footer__social a:hover { color: #0891b2; }

    .lg-footer__beta {
        text-align: center;
        margin-bottom: 1.5rem;
        padding: 0.75rem 1rem;
        background: rgba(255, 255, 255, 0.6);
        border: 1px solid rgba(8, 145, 178, 0.12);
        border-radius: 12px;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    .lg-footer__beta p {
        font-size: 0.82rem;
        color: rgba(15, 43, 60, 0.6);
        margin: 0;
    }
    .lg-footer__beta strong { color: #0891b2; }

    .lg-footer__bottom {
        border-top: 1px solid rgba(0, 80, 120, 0.06);
        padding-top: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .lg-footer__bottom p {
        font-size: 0.78rem;
        color: rgba(15, 43, 60, 0.3);
    }
    .lg-footer__credits { font-size: 0.75rem !important; }

    @media (max-width: 768px) {
        .lg-footer__grid { grid-template-columns: 1fr 1fr; gap: 2rem; }
        .lg-footer__bottom { flex-direction: column; text-align: center; }
    }
    @media (max-width: 480px) {
        .lg-footer__grid { grid-template-columns: 1fr; }
    }
    </style>

    <script src="/assets/js/particles.js"></script>
    <script src="/assets/js/animations.js"></script>
</body>
</html>
