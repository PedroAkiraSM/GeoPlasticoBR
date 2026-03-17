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
    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-grid">
                <div class="footer-brand">
                    <h3><?php echo htmlspecialchars($_footerSiteName); ?></h3>
                    <p><?php echo htmlspecialchars($_footerDesc); ?></p>
                    <?php if ($_fbUrl || $_igUrl || $_liUrl): ?>
                    <div class="footer-social">
                        <?php if ($_fbUrl): ?><a href="<?php echo htmlspecialchars($_fbUrl); ?>" target="_blank" rel="noopener">Facebook</a><?php endif; ?>
                        <?php if ($_igUrl): ?><a href="<?php echo htmlspecialchars($_igUrl); ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
                        <?php if ($_liUrl): ?><a href="<?php echo htmlspecialchars($_liUrl); ?>" target="_blank" rel="noopener">LinkedIn</a><?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="footer-links">
                    <h4>Navegacao</h4>
                    <a href="/">Inicio</a>
                    <a href="/mapa.php">Mapa Interativo</a>
                    <a href="/sobre.php">Sobre o Projeto</a>
                    <a href="/contribuir.php">Contribuir</a>
                </div>
                <div class="footer-links">
                    <h4>Recursos</h4>
                    <a href="/cadastro.php">Criar Conta</a>
                    <a href="/login.php">Login</a>
                    <a href="/sobre.php#metodologia">Metodologia</a>
                </div>
                <div class="footer-links">
                    <h4>Contato</h4>
                    <p class="footer-contact-text">Para duvidas, sugestoes ou parcerias entre em contato pelo email:</p>
                    <a href="mailto:<?php echo htmlspecialchars($_footerEmail); ?>"><?php echo htmlspecialchars($_footerEmail); ?></a>
                </div>
            </div>
            <?php if ($_footerVersion && $_footerVersion !== 'Estavel'): ?>
            <div class="footer-beta-notice">
                <p>🚧 Este site esta em fase <strong><?php echo htmlspecialchars($_footerVersion); ?></strong> — funcionalidades e dados podem mudar sem aviso previo.</p>
            </div>
            <?php endif; ?>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($_footerSiteName); ?>. Todos os direitos reservados.</p>
                <p class="footer-credits"><?php echo htmlspecialchars($_footerText); ?></p>
            </div>
        </div>
    </footer>

    <style>
    .site-footer {
        background: rgba(8, 12, 24, 0.95);
        border-top: 1px solid rgba(148, 163, 184, 0.08);
        padding: clamp(3rem, 6vw, 5rem) clamp(1.5rem, 5vw, 3rem) 2rem;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }
    .footer-inner { max-width: 1200px; margin: 0 auto; }
    .footer-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1.5fr;
        gap: 3rem;
        margin-bottom: 3rem;
    }
    .footer-brand h3 {
        font-size: 1.3rem; font-weight: 800; color: #fff;
        margin-bottom: 0.75rem; letter-spacing: -0.02em;
    }
    .footer-brand p {
        font-size: 0.88rem; color: rgba(148, 163, 184, 0.6);
        line-height: 1.7; max-width: 300px;
    }
    .footer-links h4 {
        font-size: 0.8rem; font-weight: 700; color: rgba(148, 163, 184, 0.8);
        text-transform: uppercase; letter-spacing: 0.08em;
        margin-bottom: 1rem;
    }
    .footer-links a, .footer-contact-text {
        display: block; font-size: 0.88rem;
        color: rgba(148, 163, 184, 0.5);
        text-decoration: none; margin-bottom: 0.6rem;
        transition: color 0.2s;
    }
    .footer-contact-text { cursor: default; }
    .footer-social { display: flex; gap: 12px; margin-top: 0.75rem; }
    .footer-social a {
        font-size: 0.82rem; color: rgba(148, 163, 184, 0.5);
        text-decoration: none; transition: color 0.2s;
    }
    .footer-social a:hover { color: #60A5FA; }
    .footer-links a:hover { color: #60A5FA; }
    .footer-bottom {
        border-top: 1px solid rgba(148, 163, 184, 0.06);
        padding-top: 1.5rem;
        display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: 0.5rem;
    }
    .footer-bottom p {
        font-size: 0.78rem; color: rgba(148, 163, 184, 0.35);
    }
    .footer-credits { font-size: 0.75rem !important; }
    .footer-beta-notice {
        text-align: center;
        margin-bottom: 1.5rem;
        padding: 0.75rem 1rem;
        background: rgba(0, 172, 193, 0.08);
        border: 1px solid rgba(0, 172, 193, 0.2);
        border-radius: 8px;
    }
    .footer-beta-notice p {
        font-size: 0.82rem;
        color: rgba(0, 204, 136, 0.8);
        margin: 0;
    }
    .footer-beta-notice strong {
        color: #00CC88;
    }

    @media (max-width: 768px) {
        .footer-grid { grid-template-columns: 1fr 1fr; gap: 2rem; }
        .footer-bottom { flex-direction: column; text-align: center; }
    }
    @media (max-width: 480px) {
        .footer-grid { grid-template-columns: 1fr; }
    }
    </style>

    <!-- JavaScript -->
    <script src="/assets/js/particles.js"></script>
    <script src="/assets/js/animations.js"></script>
</body>
</html>
