<?php
/**
 * API endpoint to fetch species for a given category.
 * GET /api/get_species.php?category_id=X
 * Returns JSON array of active species.
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$categoryId = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int)$_GET['category_id'] : null;

if (!$categoryId) {
    echo json_encode(['success' => false, 'error' => 'category_id is required']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("SELECT id, name, scientific_name, image_path FROM species WHERE category_id = :cid AND is_active = 1 ORDER BY name");
    $stmt->execute([':cid' => $categoryId]);
    $species = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => array_map(function($s) {
            return [
                'id' => (int)$s['id'],
                'name' => $s['name'],
                'scientific_name' => $s['scientific_name'],
                'image_path' => $s['image_path'],
            ];
        }, $species),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Species API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Query failed']);
}
