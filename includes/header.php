<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'GeoPlasticoBR'; ?></title>
    <meta name="description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Plataforma de mapeamento de microplasticos nos ecossistemas aquaticos brasileiros. Dados cientificos verificados sobre poluicao plastica.'; ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo isset($pageTitle) ? $pageTitle : 'GeoPlasticoBR'; ?>">
    <meta property="og:description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Plataforma de mapeamento de microplasticos nos ecossistemas aquaticos brasileiros.'; ?>">
    <meta property="og:url" content="https://geoplasticobr.com<?php echo $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:image" content="https://geoplasticobr.com/assets/images/og-image.svg">
    <meta property="og:site_name" content="GeoPlasticoBR">
    <meta property="og:locale" content="pt_BR">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($pageTitle) ? $pageTitle : 'GeoPlasticoBR'; ?>">
    <meta name="twitter:description" content="<?php echo isset($pageDescription) ? $pageDescription : 'Plataforma de mapeamento de microplasticos nos ecossistemas aquaticos brasileiros.'; ?>">
    <meta name="twitter:image" content="https://geoplasticobr.com/assets/images/og-image.svg">

    <link href="/assets/css/output.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <?php if (empty($heroPage)): ?>
    <div class="light-rays"></div>
    <?php endif; ?>

    <!-- Navigation Bar -->
    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
    <nav id="mainNav" class="site-nav <?php echo !empty($heroPage) ? 'nav-transparent' : ''; ?>">
        <div class="nav-inner">
            <a href="/" class="nav-brand">GeoPlasticoBR <span class="beta-badge">Beta</span></a>
            <button class="nav-hamburger" id="navHamburger" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
            <div class="nav-links" id="navLinks">
                <a href="/">Inicio</a>
                <a href="/mapa.php">Mapa</a>
                <a href="/sobre.php">Sobre</a>
                <a href="/contribuir.php" class="nav-link-special">Contribuir</a>
                <?php if (function_exists('isAdmin') && isAdmin()): ?>
                <a href="/gerenciar.php" class="nav-link-special">Gerenciar</a>
                <a href="/admin.php" class="nav-link-admin">Admin</a>
                <?php elseif (function_exists('isScientist') && isScientist()): ?>
                <a href="/gerenciar.php" class="nav-link-special">Gerenciar</a>
                <?php endif; ?>
                <span class="nav-user"><?php echo htmlspecialchars($_SESSION['user_nome'] ?? ''); ?></span>
                <a href="/login.php?logout=1" class="nav-link-logout">Sair</a>
            </div>
        </div>
    </nav>
    <?php if (empty($heroPage)): ?>
    <div style="height:56px;"></div>
    <?php endif; ?>
    <?php endif; ?>

    <style>
    .site-nav {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 9999;
        background: rgba(8, 15, 30, 0.92);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(148, 163, 184, 0.1);
        padding: 0 30px;
        font-family: 'Plus Jakarta Sans', sans-serif;
        transition: all 0.4s ease;
    }

    .site-nav.nav-transparent {
        background: transparent;
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
        border-bottom-color: transparent;
    }

    .site-nav.nav-scrolled {
        background: rgba(8, 15, 30, 0.95);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom-color: rgba(148, 163, 184, 0.1);
    }

    .nav-inner {
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 56px;
    }

    .nav-brand {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 800;
        font-size: 1.15rem;
        color: #ffffff;
        text-decoration: none;
        letter-spacing: -0.02em;
        transition: opacity 0.3s;
    }

    .nav-brand:hover { opacity: 0.8; }

    .beta-badge {
        display: inline-block;
        font-size: 0.55rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        background: linear-gradient(135deg, #00acc1, #00CC88);
        color: #fff;
        padding: 2px 6px;
        border-radius: 4px;
        margin-left: 6px;
        vertical-align: super;
        line-height: 1;
    }

    .nav-links {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .nav-links a {
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 500;
        transition: color 0.2s;
    }

    .nav-links a:hover { color: #ffffff; }

    .nav-link-special {
        color: #60A5FA !important;
        font-weight: 600 !important;
    }

    .nav-link-admin {
        color: #FBBF24 !important;
        font-weight: 600 !important;
    }

    .nav-link-logout {
        color: rgba(255, 138, 138, 0.7) !important;
    }

    .nav-link-logout:hover {
        color: #ff8a8a !important;
    }

    .nav-user {
        color: rgba(255, 255, 255, 0.4);
        font-size: 0.8rem;
    }

    /* Hamburger button */
    .nav-hamburger {
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
    .nav-hamburger span {
        display: block;
        width: 22px;
        height: 2px;
        background: #ffffff;
        border-radius: 2px;
        transition: all 0.3s ease;
    }
    .nav-hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
    .nav-hamburger.open span:nth-child(2) { opacity: 0; }
    .nav-hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

    @media (max-width: 768px) {
        .site-nav { padding: 0 16px; }
        .nav-hamburger { display: flex; }
        .nav-links {
            display: none;
            position: fixed;
            top: 56px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(8, 15, 30, 0.98);
            backdrop-filter: blur(20px);
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 24px;
            z-index: 10000;
        }
        .nav-links.open { display: flex; }
        .nav-links a { font-size: 1.1rem; }
        .nav-user { display: block; color: rgba(255,255,255,0.4); font-size: 0.85rem; }
    }
    </style>

    <script>
    (function() {
        // Hamburger menu
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
        // Scroll effect
        var nav = document.getElementById('mainNav');
        if (nav) {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 80) {
                    nav.classList.add('nav-scrolled');
                } else {
                    nav.classList.remove('nav-scrolled');
                }
            });
        }
        <?php endif; ?>
    })();
    </script>
