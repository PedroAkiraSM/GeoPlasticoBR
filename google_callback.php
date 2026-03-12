<?php
/**
 * Google OAuth 2.0 Callback
 * Recebe o code do Google, troca por token, busca perfil e faz login
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config/google.php';

// Se ja esta logado, redirecionar
if (isLoggedIn()) {
    header('Location: /');
    exit;
}

// Verificar se recebeu o code
if (!isset($_GET['code'])) {
    header('Location: /login.php?error=google_failed');
    exit;
}

$code = $_GET['code'];

// 1. Trocar code por access_token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code',
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$tokenResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    header('Location: /login.php?error=google_token');
    exit;
}

$tokenResult = json_decode($tokenResponse, true);
if (!isset($tokenResult['access_token'])) {
    header('Location: /login.php?error=google_token');
    exit;
}

$accessToken = $tokenResult['access_token'];

// 2. Buscar perfil do usuario
$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init($userInfoUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
$userResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    header('Location: /login.php?error=google_profile');
    exit;
}

$userInfo = json_decode($userResponse, true);
if (!isset($userInfo['email'])) {
    header('Location: /login.php?error=google_email');
    exit;
}

$email = $userInfo['email'];
$nome = $userInfo['name'] ?? $userInfo['email'];
$googleId = $userInfo['id'] ?? '';

// 3. Fazer login/criar conta
$result = googleLogin($email, $nome, $googleId);

if ($result['success']) {
    header('Location: /');
    exit;
} else {
    header('Location: /login.php?error=google_login');
    exit;
}
