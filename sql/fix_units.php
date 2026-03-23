<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) { echo "FAILED: No DB\n"; exit(1); }

echo "Connected OK\n";

// Check current units in Unidades e Limiares tab
$r = $pdo->query("SELECT name FROM units WHERE is_active = 1 ORDER BY name");
$dbUnits = [];
foreach ($r as $row) $dbUnits[] = $row['name'];
echo "Units in admin tab: " . implode(', ', $dbUnits) . "\n\n";

// Build select_options from actual units
$unitsJson = json_encode($dbUnits, JSON_UNESCAPED_UNICODE);
echo "New select_options: $unitsJson\n\n";

// Update all unidade fields
$stmt = $pdo->prepare("UPDATE category_fields SET select_options = :opts WHERE field_name = 'unidade' AND is_active = 1");
$stmt->execute([':opts' => $unitsJson]);
$a = $stmt->rowCount();
echo "Updated unidade fields: affected $a rows\n\n";

// Verify
$r = $pdo->query("SELECT sc.name, cf.select_options FROM category_fields cf JOIN sample_categories sc ON cf.category_id=sc.id WHERE cf.field_name='unidade' AND cf.is_active=1 LIMIT 3");
foreach ($r as $row) echo "{$row['name']}: {$row['select_options']}\n";
echo "\nDONE\n";
