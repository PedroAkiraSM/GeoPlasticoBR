<?php
require_once __DIR__ . '/../config/cms.php';
$_siteName = getSetting('site_name', 'GeoPlasticoBR');
$_siteDesc = getSetting('site_description', 'Plataforma de mapeamento de microplasticos nos ecossistemas aquaticos brasileiros. Dados cientificos verificados sobre poluicao plastica.');
$_logoPath = getSetting('logo_path', '');
$_versionLabel = getSetting('version_label', 'Beta');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : $_siteName; ?></title>
    <meta name="description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : htmlspecialchars($_siteDesc); ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo isset($pageTitle) ? $pageTitle : htmlspecialchars($_siteName); ?>">
    <meta property="og:description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : htmlspecialchars($_siteDesc); ?>">
    <meta property="og:url" content="https://geoplasticobr.com<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:image" content="https://geoplasticobr.com/assets/images/og-image.svg">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($_siteName); ?>">
    <meta property="og:locale" content="pt_BR">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($pageTitle) ? $pageTitle : htmlspecialchars($_siteName); ?>">
    <meta name="twitter:description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : htmlspecialchars($_siteDesc); ?>">
    <meta name="twitter:image" content="https://geoplasticobr.com/assets/images/og-image.svg">

    <link href="/assets/css/output.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- GSAP -->
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
</head>
<body>

    <!-- SVG Filters for Liquid Glass -->
    <svg width="0" height="0" style="position:absolute">
        <defs>
            <filter id="liquid-glass" x="-20%" y="-20%" width="140%" height="140%" color-interpolation-filters="sRGB">
                <feTurbulence type="fractalNoise" baseFrequency="0.015 0.015" numOctaves="3" seed="2" result="noise"/>
                <feDisplacementMap in="SourceGraphic" in2="noise" scale="18" xChannelSelector="R" yChannelSelector="G" result="displaced"/>
                <feGaussianBlur in="displaced" stdDeviation="0.5" result="blurred"/>
                <feSpecularLighting in="noise" surfaceScale="2" specularConstant="0.8" specularExponent="25" result="specular">
                    <fePointLight x="200" y="100" z="300" />
                </feSpecularLighting>
                <feComposite in="specular" in2="SourceGraphic" operator="in" result="specMask"/>
                <feMerge>
                    <feMergeNode in="blurred"/>
                    <feMergeNode in="specMask"/>
                </feMerge>
            </filter>
            <filter id="liquid-glass-subtle" x="-10%" y="-10%" width="120%" height="120%" color-interpolation-filters="sRGB">
                <feTurbulence type="fractalNoise" baseFrequency="0.02 0.02" numOctaves="2" seed="5" result="noise"/>
                <feDisplacementMap in="SourceGraphic" in2="noise" scale="6" xChannelSelector="R" yChannelSelector="G" result="displaced"/>
                <feGaussianBlur in="displaced" stdDeviation="0.3" result="blurred"/>
                <feSpecularLighting in="noise" surfaceScale="1" specularConstant="0.5" specularExponent="30" result="specular">
                    <fePointLight x="400" y="50" z="200" />
                </feSpecularLighting>
                <feComposite in="specular" in2="SourceGraphic" operator="in" result="specMask"/>
                <feMerge>
                    <feMergeNode in="blurred"/>
                    <feMergeNode in="specMask"/>
                </feMerge>
            </filter>
        </defs>
    </svg>

    <?php if (empty($heroPage)): ?>
    <div class="light-rays"></div>
    <?php endif; ?>

    <!-- Navigation Bar -->
    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
    <nav id="mainNav" class="lg-nav <?php echo !empty($heroPage) ? 'lg-nav--hero' : ''; ?>">
        <div class="lg-nav__inner">
            <a href="/" class="lg-nav__brand"><?php if ($_logoPath): ?><img src="/<?php echo htmlspecialchars($_logoPath); ?>" alt="<?php echo htmlspecialchars($_siteName); ?>" class="lg-nav__logo"><?php else: ?><?php echo htmlspecialchars($_siteName); ?><?php endif; ?> <?php if ($_versionLabel && $_versionLabel !== 'Estavel'): ?><span class="lg-nav__badge"><?php echo htmlspecialchars($_versionLabel); ?></span><?php endif; ?></a>
            <button class="lg-nav__hamburger" id="navHamburger" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
            <div class="lg-nav__links" id="navLinks">
                <a href="/">Inicio</a>
                <a href="/mapa.php">Mapa</a>
                <a href="/sobre.php">Sobre</a>
                <a href="/contribuir.php" class="lg-nav__link--accent">Contribuir</a>
                <?php if (function_exists('isAdmin') && isAdmin()): ?>
                <a href="/gerenciar.php" class="lg-nav__link--accent">Gerenciar</a>
                <a href="/admin.php" class="lg-nav__link--admin">Admin</a>
                <?php elseif (function_exists('isScientist') && isScientist()): ?>
                <a href="/gerenciar.php" class="lg-nav__link--accent">Gerenciar</a>
                <?php endif; ?>
                <span class="lg-nav__user"><?php echo htmlspecialchars($_SESSION['user_nome'] ?? ''); ?></span>
                <a href="/login.php?logout=1" class="lg-nav__link--logout">Sair</a>
            </div>
        </div>
    </nav>
    <?php if (empty($heroPage)): ?>
    <div style="height:64px;"></div>
    <?php endif; ?>
    <?php endif; ?>

    <style>
    /* ================================================================
       LIQUID GLASS NAVBAR — Light Theme
       ================================================================ */
    .lg-nav {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 9999;
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(30px) saturate(180%);
        -webkit-backdrop-filter: blur(30px) saturate(180%);
        border-bottom: 1px solid rgba(0, 80, 120, 0.08);
        padding: 0 32px;
        font-family: 'Inter', 'Outfit', sans-serif;
        transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .lg-nav--hero {
        background: transparent;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
        border-bottom-color: transparent;
    }

    /* After scrolling on hero pages */
    .lg-nav--scrolled {
        background: rgba(255, 255, 255, 0.75) !important;
        backdrop-filter: blur(40px) saturate(200%) !important;
        -webkit-backdrop-filter: blur(40px) saturate(200%) !important;
        border-bottom-color: rgba(0, 80, 120, 0.1) !important;
        box-shadow: 0 1px 20px rgba(0, 0, 0, 0.06);
    }

    .lg-nav__inner {
        max-width: 1300px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 64px;
    }

    .lg-nav__brand {
        font-family: 'Inter', sans-serif;
        font-weight: 800;
        font-size: 1.2rem;
        color: #0f2b3c;
        text-decoration: none;
        letter-spacing: -0.03em;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .lg-nav--hero .lg-nav__brand { color: #ffffff; }
    .lg-nav--scrolled .lg-nav__brand { color: #0f2b3c !important; }
    .lg-nav__brand:hover { opacity: 0.75; }

    .lg-nav__logo { height: 30px; vertical-align: middle; margin-right: 6px; }

    .lg-nav__badge {
        display: inline-block;
        font-size: 0.5rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        background: linear-gradient(135deg, #0891b2, #0d9488);
        color: #fff;
        padding: 3px 8px;
        border-radius: 6px;
        margin-left: 8px;
        vertical-align: super;
        line-height: 1;
    }

    .lg-nav__links {
        display: flex;
        align-items: center;
        gap: 24px;
    }

    .lg-nav__links a {
        color: rgba(15, 43, 60, 0.55);
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 500;
        transition: color 0.25s;
        position: relative;
    }
    .lg-nav--hero .lg-nav__links a { color: rgba(255,255,255,0.7); }
    .lg-nav--scrolled .lg-nav__links a { color: rgba(15, 43, 60, 0.55) !important; }

    .lg-nav__links a:hover { color: #0f2b3c; }
    .lg-nav--hero .lg-nav__links a:hover { color: #ffffff; }
    .lg-nav--scrolled .lg-nav__links a:hover { color: #0f2b3c !important; }

    .lg-nav__links a::after {
        content: '';
        position: absolute;
        bottom: -4px;
        left: 50%;
        width: 0;
        height: 1.5px;
        background: #0891b2;
        transition: all 0.3s;
        transform: translateX(-50%);
    }
    .lg-nav__links a:hover::after { width: 100%; }

    .lg-nav__link--accent {
        color: #0891b2 !important;
        font-weight: 600 !important;
    }
    .lg-nav--hero .lg-nav__link--accent { color: #5eead4 !important; }
    .lg-nav--scrolled .lg-nav__link--accent { color: #0891b2 !important; }

    .lg-nav__link--admin {
        color: #b45309 !important;
        font-weight: 600 !important;
    }
    .lg-nav--hero .lg-nav__link--admin { color: #fbbf24 !important; }
    .lg-nav--scrolled .lg-nav__link--admin { color: #b45309 !important; }

    .lg-nav__link--logout { color: rgba(220, 60, 60, 0.5) !important; }
    .lg-nav__link--logout:hover { color: #dc3c3c !important; }

    .lg-nav__user {
        color: rgba(15, 43, 60, 0.3);
        font-size: 0.8rem;
    }
    .lg-nav--hero .lg-nav__user { color: rgba(255,255,255,0.35); }
    .lg-nav--scrolled .lg-nav__user { color: rgba(15, 43, 60, 0.3) !important; }

    /* Hamburger */
    .lg-nav__hamburger {
        display: none;
        flex-direction: column;
        justify-content: center;
        gap: 5px;
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        z-index: 10001;
    }
    .lg-nav__hamburger span {
        display: block;
        width: 22px;
        height: 2px;
        background: #0f2b3c;
        border-radius: 2px;
        transition: all 0.3s ease;
    }
    .lg-nav--hero .lg-nav__hamburger span { background: #ffffff; }
    .lg-nav--scrolled .lg-nav__hamburger span { background: #0f2b3c !important; }

    .lg-nav__hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
    .lg-nav__hamburger.open span:nth-child(2) { opacity: 0; }
    .lg-nav__hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

    @media (max-width: 768px) {
        .lg-nav { padding: 0 16px; }
        .lg-nav__hamburger { display: flex; }
        .lg-nav__links {
            display: none;
            position: fixed;
            top: 64px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 28px;
            z-index: 10000;
        }
        .lg-nav__links.open { display: flex; }
        .lg-nav__links a { font-size: 1.1rem; color: #0f2b3c !important; }
        .lg-nav__user { display: block; font-size: 0.85rem; color: rgba(15,43,60,0.4) !important; }
    }
    </style>

    <script>
    (function() {
        var btn = document.getElementById('navHamburger');
        var links = document.getElementById('navLinks');
        if (btn && links) {
            btn.addEventListener('click', function() {
                btn.classList.toggle('open');
                links.classList.toggle('open');
            });
            links.querySelectorAll('a').forEach(function(a) {
                a.addEventListener('click', function() {
                    btn.classList.remove('open');
                    links.classList.remove('open');
                });
            });
        }

        <?php if (!empty($heroPage)): ?>
        var nav = document.getElementById('mainNav');
        if (nav) {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 80) {
                    nav.classList.add('lg-nav--scrolled');
                } else {
                    nav.classList.remove('lg-nav--scrolled');
                }
            });
        }
        <?php endif; ?>
    })();
    </script>
