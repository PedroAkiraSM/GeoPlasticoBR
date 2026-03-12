<?php
/**
 * GeoPlasticoBR - Sistema de Autenticação
 * Substitui o protect.php com sistema completo de login/cadastro
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

/**
 * Verifica se o usuario esta logado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'approved';
}

/**
 * Redireciona para login se nao estiver logado
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Retorna dados do usuario atual
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;

    $pdo = getDatabaseConnection();
    if (!$pdo) return null;

    $stmt = $pdo->prepare("SELECT id, nome, email, instituicao, orcid, area_pesquisa, tipo_usuario, role, status FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Verifica se usuario e admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Verifica se usuario e cientista ou admin
 */
function isScientist() {
    return isLoggedIn() && isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['scientist', 'admin']);
}

/**
 * Faz login do usuario
 */
function loginUser($email, $password) {
    $pdo = getDatabaseConnection();
    if (!$pdo) return ['success' => false, 'error' => 'Erro de conexão com o banco de dados'];

    $stmt = $pdo->prepare("SELECT id, nome, email, password_hash, role, status FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'error' => 'Email ou senha incorretos'];
    }

    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Email ou senha incorretos'];
    }

    if ($user['status'] === 'pending') {
        return ['success' => false, 'error' => 'Seu cadastro está aguardando aprovação do administrador. Você receberá acesso em breve.'];
    }

    if ($user['status'] === 'rejected') {
        return ['success' => false, 'error' => 'Seu cadastro foi recusado. Entre em contato com o administrador.'];
    }

    // Login bem-sucedido
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nome'] = $user['nome'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_status'] = $user['status'];

    return ['success' => true];
}

/**
 * Registra novo usuario
 */
function registerUser($data) {
    $pdo = getDatabaseConnection();
    if (!$pdo) return ['success' => false, 'error' => 'Erro de conexão com o banco de dados'];

    // Validações
    $nome = trim($data['nome'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $password_confirm = $data['password_confirm'] ?? '';
    $instituicao = trim($data['instituicao'] ?? '');
    $orcid = trim($data['orcid'] ?? '');
    $area_pesquisa = trim($data['area_pesquisa'] ?? '');
    $tipo_usuario = $data['tipo_usuario'] ?? 'publico';

    if (empty($nome)) return ['success' => false, 'error' => 'Nome é obrigatório'];
    if (strlen($nome) < 3) return ['success' => false, 'error' => 'Nome deve ter pelo menos 3 caracteres'];

    if (empty($email)) return ['success' => false, 'error' => 'Email é obrigatório'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['success' => false, 'error' => 'Email inválido'];

    if (empty($password)) return ['success' => false, 'error' => 'Senha é obrigatória'];
    if (strlen($password) < 8) return ['success' => false, 'error' => 'Senha deve ter pelo menos 8 caracteres'];
    if ($password !== $password_confirm) return ['success' => false, 'error' => 'As senhas não conferem'];

    if (!in_array($tipo_usuario, ['pesquisador', 'estudante', 'publico'])) {
        $tipo_usuario = 'publico';
    }

    // Validar ORCID se preenchido
    if (!empty($orcid) && !preg_match('/^\d{4}-\d{4}-\d{4}-\d{3}[\dX]$/', $orcid)) {
        return ['success' => false, 'error' => 'Formato de ORCID inválido. Use: 0000-0000-0000-0000'];
    }

    // Verificar email duplicado
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Este email já está cadastrado'];
    }

    // Inserir
    $stmt = $pdo->prepare("INSERT INTO users (nome, email, password_hash, instituicao, orcid, area_pesquisa, tipo_usuario, role, status)
        VALUES (:nome, :email, :password_hash, :instituicao, :orcid, :area_pesquisa, :tipo_usuario, 'user', 'pending')");

    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ':instituicao' => $instituicao ?: null,
        ':orcid' => $orcid ?: null,
        ':area_pesquisa' => $area_pesquisa ?: null,
        ':tipo_usuario' => $tipo_usuario,
    ]);

    return ['success' => true];
}

/**
 * Login via Google OAuth - cria usuario se nao existir, acesso imediato
 */
function googleLogin($email, $nome, $googleId) {
    $pdo = getDatabaseConnection();
    if (!$pdo) return ['success' => false, 'error' => 'Erro de conexão com o banco de dados'];

    // Verifica se usuario ja existe
    $stmt = $pdo->prepare("SELECT id, nome, email, role, status FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        // Atualiza google_id se ainda nao tiver
        $pdo->prepare("UPDATE users SET google_id = :gid WHERE id = :id AND google_id IS NULL")
            ->execute([':gid' => $googleId, ':id' => $user['id']]);

        // Se estava pendente, aprovar automaticamente (veio pelo Google)
        if ($user['status'] !== 'approved') {
            $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = :id")
                ->execute([':id' => $user['id']]);
            $user['status'] = 'approved';
        }
    } else {
        // Criar novo usuario com acesso imediato
        $stmt = $pdo->prepare("INSERT INTO users (nome, email, password_hash, google_id, tipo_usuario, role, status)
            VALUES (:nome, :email, :password_hash, :google_id, 'publico', 'user', 'approved')");
        $stmt->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT),
            ':google_id' => $googleId,
        ]);

        $user = [
            'id' => $pdo->lastInsertId(),
            'nome' => $nome,
            'email' => $email,
            'role' => 'user',
            'status' => 'approved',
        ];
    }

    // Setar sessao
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nome'] = $user['nome'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_status'] = $user['status'];

    return ['success' => true];
}

/**
 * Gera token de reset de senha
 */
function createPasswordResetToken($email) {
    $pdo = getDatabaseConnection();
    if (!$pdo) return ['success' => false, 'error' => 'Erro de conexao'];

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    if (!$stmt->fetch()) {
        // Nao revelar que email nao existe
        return ['success' => true, 'token' => null];
    }

    $pdo->prepare("UPDATE password_resets SET used = 1 WHERE email = :email AND used = 0")
        ->execute([':email' => $email]);

    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires)");
    $stmt->execute([':email' => $email, ':token' => $token, ':expires' => $expires]);

    return ['success' => true, 'token' => $token];
}

/**
 * Valida token de reset
 */
function validateResetToken($token) {
    $pdo = getDatabaseConnection();
    if (!$pdo) return null;

    $stmt = $pdo->prepare("SELECT email, expires_at FROM password_resets WHERE token = :token AND used = 0");
    $stmt->execute([':token' => $token]);
    $reset = $stmt->fetch();

    if (!$reset) return null;
    if (strtotime($reset['expires_at']) < time()) return null;

    return $reset['email'];
}

/**
 * Reseta a senha usando o token
 */
function resetPassword($token, $newPassword) {
    $pdo = getDatabaseConnection();
    if (!$pdo) return ['success' => false, 'error' => 'Erro de conexao'];

    $email = validateResetToken($token);
    if (!$email) {
        return ['success' => false, 'error' => 'Token invalido ou expirado. Solicite um novo link.'];
    }

    if (strlen($newPassword) < 8) {
        return ['success' => false, 'error' => 'A senha deve ter pelo menos 8 caracteres.'];
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE users SET password_hash = :hash WHERE email = :email")
        ->execute([':hash' => $hash, ':email' => $email]);

    $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = :token")
        ->execute([':token' => $token]);

    return ['success' => true];
}

/**
 * Gera token CSRF
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida token CSRF
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Faz logout
 */
function logoutUser() {
    session_destroy();
    header('Location: /login.php');
    exit;
}
