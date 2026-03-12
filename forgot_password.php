<?php
require_once __DIR__ . '/auth.php';

if (isLoggedIn()) {
    header('Location: /');
    exit;
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Informe um email valido.';
        $messageType = 'error';
    } else {
        $result = createPasswordResetToken($email);

        if ($result['success'] && $result['token']) {
            $resetLink = 'https://geoplasticobr.com/reset_password.php?token=' . $result['token'];

            // Enviar email
            $subject = 'GeoPlasticoBR - Redefinir Senha';
            $body = "Ola,\n\n";
            $body .= "Voce solicitou a redefinicao de senha da sua conta no GeoPlasticoBR.\n\n";
            $body .= "Clique no link abaixo para criar uma nova senha:\n";
            $body .= $resetLink . "\n\n";
            $body .= "Este link expira em 1 hora.\n\n";
            $body .= "Se voce nao solicitou esta alteracao, ignore este email.\n\n";
            $body .= "Atenciosamente,\nEquipe GeoPlasticoBR";

            $headers = "From: noreply@geoplasticobr.com\r\n";
            $headers .= "Reply-To: contato@geoplasticobr.com\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

            @mail($email, $subject, $body, $headers);
        }

        // Sempre mostrar mensagem de sucesso (seguranca - nao revelar se email existe)
        $message = 'Se o email estiver cadastrado, voce recebera um link para redefinir sua senha.';
        $messageType = 'success';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Esqueci minha senha - GeoPlasticoBR</title>
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
        .logo p { color: #94A3B8; font-size: 0.9rem; line-height: 1.6; }
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
            <p>Informe seu email para receber um link de redefinicao de senha.</p>
        </div>

        <?php if ($message): ?>
        <div class="msg <?php echo $messageType === 'success' ? 'msg-success' : 'msg-error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus placeholder="seu@email.com">
            </div>
            <button type="submit" class="btn-submit">Enviar Link de Redefinicao</button>
        </form>

        <div class="back-link">
            <a href="/login.php">Voltar para o Login</a>
        </div>
    </div>
</body>
</html>
