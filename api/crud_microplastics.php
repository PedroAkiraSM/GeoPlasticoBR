<?php
/**
 * API CRUD para microplastics_sediment
 * Apenas admin e scientist podem acessar
 */
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config/database.php';

// Verificar autenticacao
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Nao autenticado']);
    exit;
}

if (!isAdmin() && !isScientist()) {
    echo json_encode(['success' => false, 'error' => 'Sem permissao']);
    exit;
}

$pdo = getDatabaseConnection();
if (!$pdo) {
    echo json_encode(['success' => false, 'error' => 'Erro de conexao']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ===== GET: Listar com paginacao e busca =====
if ($method === 'GET') {
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    $search = trim($_GET['search'] ?? '');

    $where = '1=1';
    $params = [];

    if ($search) {
        $where .= " AND (system LIKE :search OR sampling_point LIKE :search2 OR author LIKE :search3 OR reference LIKE :search4)";
        $params[':search'] = "%$search%";
        $params[':search2'] = "%$search%";
        $params[':search3'] = "%$search%";
        $params[':search4'] = "%$search%";
    }

    if (!empty($_GET['tipo_ambiente'])) {
        $where .= " AND tipo_ambiente = :tipo";
        $params[':tipo'] = $_GET['tipo_ambiente'];
    }

    if (!empty($_GET['approved']) && $_GET['approved'] !== 'all') {
        $where .= " AND approved = :approved";
        $params[':approved'] = intval($_GET['approved']);
    }

    // Count total
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM microplastics_sediment WHERE $where");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // Fetch page
    $stmt = $pdo->prepare("SELECT id, tipo_ambiente, ecossistema, system, sampling_point, latitude, longitude,
                                   concentration_sediment, concentration_value, matriz, unidade, author, reference, doi, approved, created_at
                            FROM microplastics_sediment
                            WHERE $where
                            ORDER BY id DESC
                            LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => $rows,
        'total' => (int)$total,
        'page' => $page,
        'pages' => ceil($total / $perPage),
        'perPage' => $perPage
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ===== POST: Criar =====
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Dados invalidos']);
        exit;
    }

    $system = trim($input['system'] ?? '');
    if (empty($system)) {
        echo json_encode(['success' => false, 'error' => 'Sistema aquatico e obrigatorio']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO microplastics_sediment
            (tipo_ambiente, ecossistema, system, sampling_point, latitude, longitude,
             concentration_sediment, concentration_value, matriz, unidade, author, reference, doi, approved)
            VALUES (:tipo_ambiente, :ecossistema, :system, :sampling_point, :latitude, :longitude,
                    :concentration_sediment, :concentration_value, :matriz, :unidade, :author, :reference, :doi, :approved)");

        $stmt->execute([
            ':tipo_ambiente' => $input['tipo_ambiente'] ?: null,
            ':ecossistema' => $input['ecossistema'] ?: null,
            ':system' => $system,
            ':sampling_point' => $input['sampling_point'] ?: null,
            ':latitude' => $input['latitude'] ?: null,
            ':longitude' => $input['longitude'] ?: null,
            ':concentration_sediment' => $input['concentration_sediment'] ?: null,
            ':concentration_value' => $input['concentration_value'] ?: 0,
            ':matriz' => $input['matriz'] ?: null,
            ':unidade' => $input['unidade'] ?: null,
            ':author' => $input['author'] ?: null,
            ':reference' => $input['reference'] ?: null,
            ':doi' => $input['doi'] ?: null,
            ':approved' => isset($input['approved']) ? intval($input['approved']) : 1,
        ]);

        echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Erro ao criar: ' . $e->getMessage()]);
    }
    exit;
}

// ===== PUT: Atualizar =====
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || empty($input['id'])) {
        echo json_encode(['success' => false, 'error' => 'ID obrigatorio']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE microplastics_sediment SET
            tipo_ambiente = :tipo_ambiente, ecossistema = :ecossistema, system = :system,
            sampling_point = :sampling_point, latitude = :latitude, longitude = :longitude,
            concentration_sediment = :concentration_sediment, concentration_value = :concentration_value,
            matriz = :matriz, unidade = :unidade, author = :author, reference = :reference, doi = :doi, approved = :approved
            WHERE id = :id");

        $stmt->execute([
            ':id' => intval($input['id']),
            ':tipo_ambiente' => $input['tipo_ambiente'] ?: null,
            ':ecossistema' => $input['ecossistema'] ?: null,
            ':system' => $input['system'] ?? '',
            ':sampling_point' => $input['sampling_point'] ?: null,
            ':latitude' => $input['latitude'] ?: null,
            ':longitude' => $input['longitude'] ?: null,
            ':concentration_sediment' => $input['concentration_sediment'] ?: null,
            ':concentration_value' => $input['concentration_value'] ?: 0,
            ':matriz' => $input['matriz'] ?: null,
            ':unidade' => $input['unidade'] ?: null,
            ':author' => $input['author'] ?: null,
            ':reference' => $input['reference'] ?: null,
            ':doi' => $input['doi'] ?: null,
            ':approved' => isset($input['approved']) ? intval($input['approved']) : 1,
        ]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Erro ao atualizar: ' . $e->getMessage()]);
    }
    exit;
}

// ===== DELETE =====
if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? $_GET['id'] ?? 0);

    if (!$id) {
        echo json_encode(['success' => false, 'error' => 'ID obrigatorio']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM microplastics_sediment WHERE id = :id");
        $stmt->execute([':id' => $id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Erro ao deletar: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'error' => 'Metodo nao suportado']);
