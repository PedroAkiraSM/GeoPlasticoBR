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
