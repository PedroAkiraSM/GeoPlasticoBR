<?php
/**
 * API endpoint to submit a new sample via the contribution form.
 * POST /api/submit_sample.php
 * Requires login. Inserts into samples + sample_values.
 * New submissions have approved=0 (pending admin review).
 */
header('Content-Type: application/json; charset=utf-8');

require_once '../auth.php';
require_once '../config/database.php';
require_once '../config/cms.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Login required']);
    exit;
}

// Validate CSRF
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$categoryId = (int)($_POST['category_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$latitude = $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
$longitude = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
$author = trim($_POST['author'] ?? '');
$reference = trim($_POST['reference_text'] ?? '');
$doi = trim($_POST['doi'] ?? '');

// Validation
$errors = [];
if ($categoryId === 0) $errors[] = 'Categoria e obrigatoria.';
if ($title === '') $errors[] = 'Titulo e obrigatorio.';
if ($latitude === null || $latitude < -90 || $latitude > 90) $errors[] = 'Latitude invalida.';
if ($longitude === null || $longitude < -180 || $longitude > 180) $errors[] = 'Longitude invalida.';

// Validate required dynamic fields
$fields = getCategoryFields($categoryId);
foreach ($fields as $field) {
    if ($field['is_required']) {
        $val = $_POST['field_' . $field['id']] ?? '';
        if ($field['field_type'] === 'multicheck') {
            $val = $_POST['field_' . $field['id']] ?? [];
            if (empty($val)) $errors[] = $field['field_label'] . ' e obrigatorio.';
        } elseif ($val === '') {
            $errors[] = $field['field_label'] . ' e obrigatorio.';
        }
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    $pdo->beginTransaction();

    // Insert sample
    $stmt = $pdo->prepare("INSERT INTO samples (category_id, title, latitude, longitude, author, reference_text, doi, approved, submitted_by)
                           VALUES (:cid, :title, :lat, :lng, :author, :ref, :doi, 0, :uid)");
    $stmt->execute([
        ':cid' => $categoryId,
        ':title' => $title,
        ':lat' => $latitude,
        ':lng' => $longitude,
        ':author' => $author ?: null,
        ':ref' => $reference ?: null,
        ':doi' => $doi ?: null,
        ':uid' => $_SESSION['user_id'],
    ]);
    $sampleId = $pdo->lastInsertId();

    // Insert field values
    $stmtVal = $pdo->prepare("INSERT INTO sample_values (sample_id, field_id, value_text, value_number) VALUES (:sid, :fid, :vt, :vn)");
    foreach ($fields as $field) {
        $rawVal = $_POST['field_' . $field['id']] ?? '';

        if ($field['field_type'] === 'checkbox') {
            $rawVal = isset($_POST['field_' . $field['id']]) ? '1' : '0';
        }
        if ($field['field_type'] === 'multicheck') {
            $arr = $_POST['field_' . $field['id']] ?? [];
            $rawVal = is_array($arr) && !empty($arr) ? json_encode($arr, JSON_UNESCAPED_UNICODE) : '';
        }

        if ($rawVal === '' && !$field['is_required']) continue;

        $vt = null;
        $vn = null;
        if (in_array($field['field_type'], ['number', 'decimal'])) {
            $vn = $rawVal !== '' ? (float)str_replace(',', '.', $rawVal) : null;
        } else {
            $vt = $rawVal;
        }

        $stmtVal->execute([':sid' => $sampleId, ':fid' => $field['id'], ':vt' => $vt, ':vn' => $vn]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Dados enviados! Serao revisados pelo administrador.', 'sample_id' => (int)$sampleId]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Sample submission error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar dados.']);
}
