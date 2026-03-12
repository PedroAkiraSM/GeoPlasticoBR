<?php
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$token = $_GET['token'] ?? '';
$message = '';
$messageType = '';
$validToken = false;

if (empty($token)) {
    $message = 'Link invalido. Solicite um novo link de redefinicao.';
    $messageType = 'error';
} else {
    $email = validateResetToken($token);
    if ($email) {
        $validToken = true;
    } else {
        $message = 'Link invalido ou expirado. Solicite um novo link de redefinicao.';
        $messageType = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($password !== $passwordConfirm) {
        $message = 'As senhas nao conferem.';
        $messageType = 'error';
    } else {
        $result = resetPassword($token, $password);
        if ($result['success']) {
            $message = 'Senha redefinida com sucesso! Voce ja pode fazer login.';
            $messageType = 'success';
            $validToken = false; // Esconder o formulario
        } else {
            $message = $result['error'];
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - GeoPlasticoBR</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
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
        .card {
            background: rgba(255, 255, 255, 0.04);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(148, 163, 184, 0.12);
            border-radius: 20px;
            padding: 50px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
        }
        .logo { text-align: center; margin-bottom: 32px; }
        .logo h1 { font-weight: 800; font-size: 1.8rem; color: #fff; letter-spacing: -0.02em; margin-bottom: 8px; }
        .logo p { color: #94A3B8; font-size: 0.9rem; }
        .form-group { margin-bottom: 24px; }
        .form-group label { display: block; color: #94A3B8; font-weight: 600; margin-bottom: 8px; font-size: 0.875rem; }
        .form-group input {
            width: 100%; padding: 14px 18px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(148, 163, 184, 0.15);
            border-radius: 10px; color: #E2E8F0;
            font-size: 1rem; font-family: 'Plus Jakarta Sans', sans-serif;
            transition: all 0.3s;
        }
        .form-group input:focus { outline: none; border-color: #60A5FA; box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.15); }
        .form-group input::placeholder { color: rgba(148, 163, 184, 0.4); }
        .form-group small { display: block; color: rgba(148, 163, 184, 0.4); font-size: 0.78rem; margin-top: 6px; }
        .btn-submit {
            width: 100%; padding: 16px;
            background: #fff; color: #0F172A;
            border: none; border-radius: 10px;
            font-size: 1rem; font-weight: 700;
            font-family: 'Plus Jakarta Sans', sans-serif;
            cursor: pointer; transition: all 0.3s;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(255, 255, 255, 0.15); }
        .msg {
            padding: 12px 16px; border-radius: 10px;
            margin-bottom: 20px; font-size: 0.875rem; line-height: 1.5;
        }
        .msg-success { background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.2); color: #6EE7B7; }
        .msg-error { background: rgba(255, 50, 50, 0.1); border: 1px solid rgba(255, 80, 80, 0.2); color: #FCA5A5; }
        .back-link { text-align: center; margin-top: 24px; }
        .back-link a { color: #60A5FA; text-decoration: none; font-weight: 600; font-size: 0.875rem; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <h1>GeoPlasticoBR</h1>
            <p>Crie uma nova senha para sua conta.</p>
        </div>

        <?php if ($message): ?>
        <div class="msg <?php echo $messageType === 'success' ? 'msg-success' : 'msg-error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if ($validToken): ?>
        <form method="POST">
            <div class="form-group">
                <label for="password">Nova Senha</label>
                <input type="password" id="password" name="password" required placeholder="Digite a nova senha">
                <small>Minimo 8 caracteres</small>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirmar Nova Senha</label>
                <input type="password" id="password_confirm" name="password_confirm" required placeholder="Confirme a nova senha">
            </div>
            <button type="submit" class="btn-submit">Redefinir Senha</button>
        </form>
        <?php endif; ?>

        <div class="back-link">
            <?php if ($messageType === 'success'): ?>
            <a href="/login.php">Ir para o Login</a>
            <?php else: ?>
            <a href="/forgot_password.php">Solicitar novo link</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
