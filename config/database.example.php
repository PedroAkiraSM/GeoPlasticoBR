<?php
/**
 * GeoPlásticoBR - Configuração do Banco de Dados
 *
 * IMPORTANTE: Copie este arquivo para "database.php" e ajuste as credenciais
 * - Local: localhost, root, sem senha
 * - Hostinger: obter credenciais no painel de controle
 */

// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'seu_banco_de_dados');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
define('DB_CHARSET', 'utf8mb4');

/**
 * Criar conexão com o banco de dados
 * @return PDO|null
 */
function getDatabaseConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        return $pdo;

    } catch (PDOException $e) {
        error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
        return null;
    }
}

/**
 * Testar conexão com o banco de dados
 * @return bool
 */
function testDatabaseConnection() {
    $pdo = getDatabaseConnection();

    if ($pdo === null) {
        return false;
    }

    try {
        $stmt = $pdo->query("SELECT 1");
        return true;
    } catch (PDOException $e) {
        error_log("Erro ao testar conexão: " . $e->getMessage());
        return false;
    }
}
