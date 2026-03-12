<?php
require_once __DIR__ . '/auth.php';

// Processar logout (antes do check de login)
if (isset($_GET['logout'])) {
    logoutUser();
}

// Se já está logado, redirecionar
if (isLoggedIn()) {
    header('Location: /');
    exit;
}

require_once __DIR__ . '/config/google.php';

$error = '';

// Erros do Google OAuth
if (isset($_GET['error'])) {
    $googleErrors = [
        'google_failed' => 'Falha ao conectar com o Google. Tente novamente.',
        'google_token' => 'Erro ao obter token do Google. Tente novamente.',
        'google_profile' => 'Erro ao obter perfil do Google. Tente novamente.',
        'google_email' => 'Nao foi possivel obter seu email do Google.',
        'google_login' => 'Erro ao fazer login com Google. Tente novamente.',
    ];
    $error = $googleErrors[$_GET['error']] ?? '';
}

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = loginUser($email, $password);
    if ($result['success']) {
        header('Location: /');
        exit;
    } else {
        $error = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GeoPlasticoBR</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link href="/assets/css/output.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 50%, #0F172A 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(148, 163, 184, 0.12);
            border-radius: 20px;
            padding: 50px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }
        .logo { text-align: center; margin-bottom: 40px; }
        .logo h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 1.8rem;
            color: #ffffff;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }
        .logo p { color: #94A3B8; font-size: 0.9rem; }
        .form-group { margin-bottom: 24px; }
        .form-group label {
            display: block;
            color: #94A3B8;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.875rem;
        }
        .form-group input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(148, 163, 184, 0.15);
            border-radius: 10px;
            color: #E2E8F0;
            font-size: 1rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #60A5FA;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.15);
        }
        .form-group input::placeholder { color: rgba(148, 163, 184, 0.4); }
        .btn-login {
            width: 100%;
            padding: 16px;
            background: #ffffff;
            color: #0F172A;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.15);
        }
        .error-msg {
            background: rgba(255, 50, 50, 0.1);
            border: 1px solid rgba(255, 80, 80, 0.2);
            color: #FCA5A5;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.875rem;
        }
        .register-link {
            text-align: center;
            margin-top: 24px;
            color: #94A3B8;
            font-size: 0.875rem;
        }
        .register-link a {
            color: #60A5FA;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link a:hover { text-decoration: underline; }
        .btn-google {
            width: 100%;
            padding: 14px 16px;
            background: #fff;
            color: #3c4043;
            border: 1px solid #dadce0;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none;
        }
        .btn-google:hover {
            background: #f8f9fa;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateY(-1px);
        }
        .btn-google svg { flex-shrink: 0; }
        .divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 24px 0;
            color: rgba(148, 163, 184, 0.4);
            font-size: 0.8rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(148, 163, 184, 0.15);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo">
            <h1>GeoPlasticoBR</h1>
            <p>Mapeamento de Microplasticos no Brasil</p>
        </div>

        <?php if ($error): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Botao Google -->
        <a href="<?php echo htmlspecialchars(getGoogleAuthUrl()); ?>" class="btn-google">
            <svg width="20" height="20" viewBox="0 0 48 48">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            Entrar com Google
        </a>

        <div class="divider">ou</div>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus
                       placeholder="seu@email.com"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" required
                       placeholder="Digite sua senha">
            </div>

            <button type="submit" class="btn-login">Entrar</button>
        </form>

        <div class="register-link" style="margin-top: 16px;">
            <a href="/forgot_password.php" style="color: rgba(148,163,184,0.6); font-size: 0.82rem;">Esqueci minha senha</a>
        </div>

        <div class="register-link" style="margin-top: 12px;">
            Nao tem conta? <a href="/cadastro.php">Cadastre-se aqui</a>
        </div>
    </div>
</body>
</html>
