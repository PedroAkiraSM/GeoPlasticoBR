<?php
/**
 * API endpoint for the new dynamic samples system.
 * Returns all approved samples with coordinates, grouped by category,
 * including category metadata and field values.
 *
 * Optional query params:
 *   category_id - filter by category
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-cache, must-revalidate');

require_once '../config/database.php';
require_once '../config/cms.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $categoryId = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int)$_GET['category_id'] : null;
    $samples = getSamplesForMap($categoryId);
    $categories = getCategories();

    // Format for map consumption
    $formattedSamples = [];
    foreach ($samples as $s) {
        $fieldData = [];
        foreach ($s['fields'] as $f) {
            $fieldData[] = [
                'name' => $f['field_name'],
                'label' => $f['field_label'],
                'type' => $f['field_type'],
                'value' => $f['value_number'] !== null ? $f['value_number'] : $f['value_text'],
            ];
        }
        $formattedSamples[] = [
            'id' => (int)$s['id'],
            'category_id' => (int)$s['category_id'],
            'category_name' => $s['category_name'],
            'category_type' => $s['category_type'],
            'icon' => $s['icon'],
            'icon_image' => $s['icon_image'] ?? null,
            'color' => $s['color'],
            'title' => $s['title'],
            'latitude' => (float)$s['latitude'],
            'longitude' => (float)$s['longitude'],
            'author' => $s['author'],
            'reference' => $s['reference_text'],
            'doi' => $s['doi'] ?? null,
            'fields' => $fieldData,
        ];
    }

    echo json_encode([
        'success' => true,
        'count' => count($formattedSamples),
        'data' => $formattedSamples,
        'categories' => array_map(function($c) {
            return [
                'id' => (int)$c['id'],
                'name' => $c['name'],
                'type' => $c['type'],
                'icon' => $c['icon'],
                'icon_image' => $c['icon_image'] ?? null,
                'color' => $c['color'],
            ];
        }, $categories),
        'thresholds' => getUnitsWithThresholds(),
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Samples API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Query failed', 'data' => []]);
}
