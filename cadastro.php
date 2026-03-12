<?php
require_once __DIR__ . '/auth.php';

// Se já está logado, redirecionar
if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$error = '';
$success = false;

// Processar cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = registerUser($_POST);
    if ($result['success']) {
        $success = true;
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
    <title>Cadastro - GeoPlasticoBR</title>
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
        .register-card {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(148, 163, 184, 0.12);
            border-radius: 20px;
            padding: 40px 50px;
            width: 100%;
            max-width: 580px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800;
            font-size: 1.8rem;
            color: #ffffff;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }
        .logo p { color: #94A3B8; font-size: 0.9rem; }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-grid .full-width { grid-column: 1 / -1; }
        .form-group { margin-bottom: 4px; }
        .form-group label {
            display: block;
            color: #94A3B8;
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 0.8rem;
        }
        .form-group label .required { color: #F87171; }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 14px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(148, 163, 184, 0.15);
            border-radius: 8px;
            color: #E2E8F0;
            font-size: 0.9rem;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.3s;
        }
        .form-group select option { background: #1E293B; color: #E2E8F0; }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #60A5FA;
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.15);
        }
        .form-group input::placeholder { color: rgba(148, 163, 184, 0.4); }
        .form-group .hint { color: rgba(148, 163, 184, 0.5); font-size: 0.75rem; margin-top: 4px; }
        .btn-register {
            width: 100%;
            padding: 14px;
            background: #ffffff;
            color: #0F172A;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 16px;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.15);
        }
        .error-msg {
            background: rgba(255, 50, 50, 0.1);
            border: 1px solid rgba(255, 80, 80, 0.2);
            color: #FCA5A5;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 0.875rem;
        }
        .success-msg {
            background: rgba(52, 211, 153, 0.1);
            border: 1px solid rgba(52, 211, 153, 0.2);
            color: #6EE7B7;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            line-height: 1.6;
        }
        .success-msg h3 { font-size: 1.2rem; margin-bottom: 8px; }
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #94A3B8;
            font-size: 0.875rem;
        }
        .login-link a { color: #60A5FA; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .register-card { padding: 30px 24px; }
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="logo">
            <h1>GeoPlasticoBR</h1>
            <p>Criar Conta</p>
        </div>

        <?php if ($success): ?>
        <div class="success-msg">
            <h3>Cadastro realizado com sucesso!</h3>
            <p>Seu cadastro foi enviado para aprovacao do administrador.<br>
            Voce recebera acesso assim que sua conta for aprovada.</p>
        </div>
        <div class="login-link" style="margin-top: 24px;">
            <a href="/login.php">Voltar para o Login</a>
        </div>

        <?php else: ?>

        <?php if ($error): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-grid">
                <div class="form-group full-width">
                    <label>Nome Completo <span class="required">*</span></label>
                    <input type="text" name="nome" required minlength="3"
                           placeholder="Seu nome completo"
                           value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>">
                </div>

                <div class="form-group full-width">
                    <label>Email <span class="required">*</span></label>
                    <input type="email" name="email" required
                           placeholder="seu@email.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Senha <span class="required">*</span></label>
                    <input type="password" name="password" required minlength="8"
                           placeholder="Minimo 8 caracteres">
                </div>

                <div class="form-group">
                    <label>Confirmar Senha <span class="required">*</span></label>
                    <input type="password" name="password_confirm" required minlength="8"
                           placeholder="Repita a senha">
                </div>

                <div class="form-group full-width">
                    <label>Instituicao</label>
                    <input type="text" name="instituicao"
                           placeholder="Universidade ou instituicao de pesquisa"
                           value="<?php echo htmlspecialchars($_POST['instituicao'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>ORCID</label>
                    <input type="text" name="orcid"
                           placeholder="0000-0000-0000-0000"
                           value="<?php echo htmlspecialchars($_POST['orcid'] ?? ''); ?>">
                    <div class="hint">Formato: 0000-0000-0000-0000</div>
                </div>

                <div class="form-group">
                    <label>Tipo de Usuario</label>
                    <select name="tipo_usuario">
                        <option value="pesquisador" <?php echo ($_POST['tipo_usuario'] ?? '') === 'pesquisador' ? 'selected' : ''; ?>>Pesquisador</option>
                        <option value="estudante" <?php echo ($_POST['tipo_usuario'] ?? '') === 'estudante' ? 'selected' : ''; ?>>Estudante</option>
                        <option value="publico" <?php echo ($_POST['tipo_usuario'] ?? 'publico') === 'publico' ? 'selected' : ''; ?>>Publico Geral</option>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label>Area de Pesquisa</label>
                    <input type="text" name="area_pesquisa"
                           placeholder="Ex: Ecologia aquatica, Microplasticos, Biologia marinha"
                           value="<?php echo htmlspecialchars($_POST['area_pesquisa'] ?? ''); ?>">
                </div>
            </div>

            <button type="submit" class="btn-register">Criar Conta</button>
        </form>

        <div class="login-link">
            Ja tem conta? <a href="/login.php">Faca login</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
