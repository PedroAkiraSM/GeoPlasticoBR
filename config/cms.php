<?php
/**
 * CMS Helper Functions
 * Provides cached access to site settings, blocks, data types, and thresholds.
 */

require_once __DIR__ . '/database.php';

/**
 * Get a single site setting value by key.
 */
function getSetting(string $key, string $default = ''): string {
    $settings = getSettings();
    return $settings[$key] ?? $default;
}

/**
 * Get all site settings as key => value array.
 * Pass $forceReload = true after saving to clear the static cache.
 */
function getSettings(bool $forceReload = false): array {
    static $cache = null;
    if ($cache !== null && !$forceReload) return $cache;

    $pdo = getDatabaseConnection();
    if (!$pdo) return [];

    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    $cache = [];
    while ($row = $stmt->fetch()) {
        $cache[$row['setting_key']] = $row['setting_value'];
    }
    return $cache;
}

/**
 * Get content blocks for a page, ordered by block_order.
 * Returns associative array: block_key => block_value
 */
function getBlocks(string $page): array {
    static $cache = [];
    if (isset($cache[$page])) return $cache[$page];

    $pdo = getDatabaseConnection();
    if (!$pdo) return [];

    $stmt = $pdo->prepare("SELECT block_key, block_value FROM site_blocks WHERE page = :page ORDER BY block_order ASC");
    $stmt->execute([':page' => $page]);
    $cache[$page] = [];
    while ($row = $stmt->fetch()) {
        $cache[$page][$row['block_key']] = $row['block_value'];
    }
    return $cache[$page];
}

/**
 * Get active data types for a category.
 * Returns array of ['id' => int, 'name' => string, 'description' => string|null]
 */
function getDataTypes(string $category): array {
    static $cache = [];
    if (isset($cache[$category])) return $cache[$category];

    $pdo = getDatabaseConnection();
    if (!$pdo) return [];

    $stmt = $pdo->prepare("SELECT id, name, description FROM data_types WHERE category = :cat AND active = 1 ORDER BY name ASC");
    $stmt->execute([':cat' => $category]);
    $cache[$category] = $stmt->fetchAll();
    return $cache[$category];
}

/**
 * Get ALL data types for a category (including inactive). For admin panel.
 */
function getAllDataTypes(string $category): array {
    $pdo = getDatabaseConnection();
    if (!$pdo) return [];

    $stmt = $pdo->prepare("SELECT id, name, description, active FROM data_types WHERE category = :cat ORDER BY name ASC");
    $stmt->execute([':cat' => $category]);
    return $stmt->fetchAll();
}

/**
 * Get active measurement units with their nested thresholds.
 * Returns: [{ name, description, thresholds: [{ level, min_value, max_value, color }] }]
 */
function getUnitsWithThresholds(): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    $pdo = getDatabaseConnection();
    if (!$pdo) return [];

    $units = $pdo->query("SELECT id, name, description FROM measurement_units WHERE active = 1 ORDER BY name ASC")->fetchAll();

    $stmt = $pdo->prepare("SELECT level, min_value, max_value, color FROM concentration_thresholds WHERE unit_id = :uid ORDER BY min_value ASC");

    $cache = [];
    foreach ($units as $unit) {
        $stmt->execute([':uid' => $unit['id']]);
        $cache[] = [
            'name' => $unit['name'],
            'description' => $unit['description'],
            'thresholds' => $stmt->fetchAll()
        ];
    }
    return $cache;
}

// =====================================================
// Dynamic Categories & Fields System
// =====================================================

/**
 * Get all active sample categories.
 */
function getCategories(bool $includeInactive = false): array {
    static $cache = null;
    if ($cache !== null && !$includeInactive) return $cache;

    $pdo = getDatabaseConnection();
    if (!$pdo) return [];

    $sql = "SELECT * FROM sample_categories" . ($includeInactive ? '' : ' WHERE is_active = 1') . " ORDER BY display_order, name";
    $result = $pdo->query($sql)->fetchAll();
    if (!$includeInactive) $cache = $result;
    return $result;
}

/**
 * Get a single category by ID.
 */
function getCategory(int $id): ?array {
    $pdo = getDatabaseConnection();
    if (!$pdo) return null;
    $stmt = $pdo->prepare("SELECT * FROM sample_categories WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

/**
 * Get fields for a category, ordered by display_order.
 */
function getCategoryFields(int $categoryId, bool $includeInactive = false): array {
    $pdo = getDatabaseConnection();
    if (!$pdo) return [];

    $sql = "SELECT * FROM category_fields WHERE category_id = :cid"
         . ($includeInactive ? '' : ' AND is_active = 1')
         . " ORDER BY display_order, id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cid' => $categoryId]);
    return $stmt->fetchAll();
}

/**
 * Get a single sample with all its field values.
 */
function getSample(int $id): ?array {
    $pdo = getDatabaseConnection();
    if (!$pdo) return null;

    $stmt = $pdo->prepare("SELECT s.*, sc.name as category_name, sc.type as category_type, sc.icon, sc.color
                           FROM samples s
                           JOIN sample_categories sc ON s.category_id = sc.id
                           WHERE s.id = :id");
    $stmt->execute([':id' => $id]);
    $sample = $stmt->fetch();
    if (!$sample) return null;

    // Fetch field values
    $stmt2 = $pdo->prepare("SELECT cf.field_name, cf.field_label, cf.field_type, sv.value_text, sv.value_number
                            FROM sample_values sv
                            JOIN category_fields cf ON sv.field_id = cf.id
                            WHERE sv.sample_id = :sid
                            ORDER BY cf.display_order");
    $stmt2->execute([':sid' => $id]);
    $sample['fields'] = $stmt2->fetchAll();

    return $sample;
}

/**
 * Get all samples for the map (with coordinates, approved).
 * Returns flat array with category metadata and field values as JSON.
 */
function getSamplesForMap(?int $categoryId = null): array {
    $pdo = getDatabaseConnection();
    if (!$pdo) return [];

    $sql = "SELECT s.id, s.category_id, s.title, s.latitude, s.longitude, s.author, s.reference_text, s.doi,
                   sc.name as category_name, sc.type as category_type, sc.icon, sc.icon_image, sc.color
            FROM samples s
            JOIN sample_categories sc ON s.category_id = sc.id
            WHERE s.approved = 1 AND s.latitude IS NOT NULL AND s.longitude IS NOT NULL AND sc.is_active = 1";
    $params = [];
    if ($categoryId) {
        $sql .= " AND s.category_id = :cid";
        $params[':cid'] = $categoryId;
    }
    $sql .= " ORDER BY s.id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $samples = $stmt->fetchAll();

    if (empty($samples)) return [];

    // Batch load all field values for these samples
    $sampleIds = array_column($samples, 'id');
    $placeholders = implode(',', array_fill(0, count($sampleIds), '?'));
    $valStmt = $pdo->prepare("SELECT sv.sample_id, cf.field_name, cf.field_label, cf.field_type, sv.value_text, sv.value_number
                              FROM sample_values sv
                              JOIN category_fields cf ON sv.field_id = cf.id
                              WHERE sv.sample_id IN ($placeholders) AND cf.is_active = 1
                              ORDER BY cf.display_order");
    $valStmt->execute($sampleIds);
    $allValues = $valStmt->fetchAll();

    // Group values by sample_id
    $valueMap = [];
    foreach ($allValues as $v) {
        $valueMap[$v['sample_id']][] = $v;
    }

    // Attach to samples
    foreach ($samples as &$s) {
        $s['fields'] = $valueMap[$s['id']] ?? [];
    }

    return $samples;
}

/**
 * Get the hex color for a concentration value given a unit name.
 * Returns gray (#6b7280) if unit unknown or value outside all ranges.
 */
function getThresholdColor(string $unitName, float $value): string {
    $units = getUnitsWithThresholds();
    foreach ($units as $unit) {
        if ($unit['name'] !== $unitName) continue;
        foreach ($unit['thresholds'] as $t) {
            $min = (float) $t['min_value'];
            $max = $t['max_value'] !== null ? (float) $t['max_value'] : PHP_FLOAT_MAX;
            if ($value >= $min && $value < $max) {
                return $t['color'];
            }
        }
    }
    return '#6b7280';
}
