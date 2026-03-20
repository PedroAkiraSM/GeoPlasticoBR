<?php
/**
 * API endpoint to fetch active fields for a given category.
 * GET /api/get_category_fields.php?category_id=X
 * Returns JSON array of fields with their configuration.
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once '../config/database.php';
require_once '../config/cms.php';

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
    $fields = getCategoryFields($categoryId);

    echo json_encode([
        'success' => true,
        'data' => array_map(function($f) {
            return [
                'id' => (int)$f['id'],
                'field_name' => $f['field_name'],
                'field_label' => $f['field_label'],
                'field_type' => $f['field_type'],
                'select_options' => $f['select_options'],
                'is_required' => (int)$f['is_required'],
                'display_order' => (int)$f['display_order'],
                'placeholder' => $f['placeholder'] ?? '',
            ];
        }, $fields),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Category fields API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Query failed']);
}
