<?php
/**
 * Sistema de Proteção por Senha - GeoPlasticoBR
 * Alternativa ao .htaccess que SEMPRE funciona
 */

session_start();

// Configurações
$USUARIO_CORRETO = 'geoplastico';
$SENHA_CORRETA = 'GeoPlastico2026!';

// Verificar se já está autenticado
if (isset($_SESSION['geoplastico_autenticado']) && $_SESSION['geoplastico_autenticado'] === true) {
    return; // Já autenticado, continuar carregando a página
}

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if ($usuario === $USUARIO_CORRETO && $senha === $SENHA_CORRETA) {
        $_SESSION['geoplastico_autenticado'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $erro = 'Usuário ou senha incorretos!';
    }
}

// Processar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Se não está autenticado, mostrar formulário de login
if (!isset($_SESSION['geoplastico_autenticado']) || $_SESSION['geoplastico_autenticado'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>GeoPlasticoBR - Acesso Restrito</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                background: linear-gradient(135deg, #0a4d68 0%, #088395 100%);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 20px;
            }

            .login-container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                padding: 50px;
                width: 100%;
                max-width: 450px;
                animation: slideIn 0.5s ease-out;
            }

            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .logo {
                text-align: center;
                margin-bottom: 30px;
            }

            .logo h1 {
                color: #0a4d68;
                font-size: 32px;
                font-weight: 700;
                margin-bottom: 10px;
            }

            .logo p {
                color: #666;
                font-size: 14px;
            }

            .lock-icon {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #0a4d68 0%, #088395 100%);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                box-shadow: 0 10px 30px rgba(8, 131, 149, 0.3);
            }

            .lock-icon svg {
                width: 40px;
                height: 40px;
                fill: white;
            }

            .form-group {
                margin-bottom: 25px;
            }

            label {
                display: block;
                color: #333;
                font-weight: 600;
                margin-bottom: 8px;
                font-size: 14px;
            }

            input {
                width: 100%;
                padding: 15px 18px;
                border: 2px solid #e0e0e0;
                border-radius: 12px;
                font-size: 15px;
                transition: all 0.3s ease;
                background: #f8f9fa;
            }

            input:focus {
                outline: none;
                border-color: #088395;
                background: white;
                box-shadow: 0 0 0 4px rgba(8, 131, 149, 0.1);
            }

            button {
                width: 100%;
                padding: 16px;
                background: linear-gradient(135deg, #0a4d68 0%, #088395 100%);
                color: white;
                border: none;
                border-radius: 12px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(8, 131, 149, 0.4);
            }

            button:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(8, 131, 149, 0.5);
            }

            button:active {
                transform: translateY(0);
            }

            .error {
                background: #fee;
                color: #c33;
                padding: 12px 16px;
                border-radius: 10px;
                margin-bottom: 20px;
                font-size: 14px;
                border-left: 4px solid #c33;
                animation: shake 0.5s;
            }

            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-10px); }
                75% { transform: translateX(10px); }
            }

            .footer {
                text-align: center;
                margin-top: 30px;
                color: #999;
                font-size: 13px;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="logo">
                <div class="lock-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M12 1C8.676 1 6 3.676 6 7v2H5c-1.103 0-2 .897-2 2v10c0 1.103.897 2 2 2h14c1.103 0 2-.897 2-2V11c0-1.103-.897-2-2-2h-1V7c0-3.324-2.676-6-6-6zm0 2c2.206 0 4 1.794 4 4v2H8V7c0-2.206 1.794-4 4-4z"/>
                    </svg>
                </div>
                <h1>GeoPlasticoBR</h1>
                <p>Acesso Restrito - Desenvolvimento</p>
            </div>

            <?php if (isset($erro)): ?>
            <div class="error">
                <?php echo htmlspecialchars($erro); ?>
            </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="usuario">Usuário</label>
                    <input type="text" id="usuario" name="usuario" required autofocus placeholder="Digite seu usuário">
                </div>

                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required placeholder="Digite sua senha">
                </div>

                <button type="submit" name="login">Acessar Sistema</button>
            </form>

            <div class="footer">
                <p>Sistema de proteção por senha ativado</p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
