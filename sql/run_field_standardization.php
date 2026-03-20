<?php
/**
 * GeoPlasticoBR - Field Standardization Migration Runner
 * Run via CLI: php run_field_standardization.php
 *
 * IMPORTANT: Take a full database backup before running!
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "FAILED: Could not connect to database\n";
    exit(1);
}

echo "Connected to database.\n";

// Read and execute the SQL file
$sqlFile = __DIR__ . '/migration_field_standardization.sql';
if (!file_exists($sqlFile)) {
    echo "FAILED: SQL file not found at $sqlFile\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);

// Remove comment lines and verification queries
$sql = preg_replace('/^--.*$/m', '', $sql);

// Split by semicolons (respecting quoted strings)
$statements = [];
$current = '';
$inString = false;
$stringChar = '';

for ($i = 0; $i < strlen($sql); $i++) {
    $char = $sql[$i];

    if ($inString) {
        $current .= $char;
        if ($char === $stringChar && ($i === 0 || $sql[$i-1] !== '\\')) {
            $inString = false;
        }
    } else {
        if ($char === "'" || $char === '"') {
            $inString = true;
            $stringChar = $char;
            $current .= $char;
        } elseif ($char === ';') {
            $trimmed = trim($current);
            if ($trimmed !== '') {
                $statements[] = $trimmed;
            }
            $current = '';
        } else {
            $current .= $char;
        }
    }
}

$trimmed = trim($current);
if ($trimmed !== '') {
    $statements[] = $trimmed;
}

echo "Parsed " . count($statements) . " SQL statements.\n\n";

$inTransaction = false;
$success = true;

foreach ($statements as $i => $stmt) {
    $preview = substr(preg_replace('/\s+/', ' ', $stmt), 0, 80);
    echo "[$i] $preview...\n";

    try {
        if (strtoupper(trim($stmt)) === 'START TRANSACTION') {
            $pdo->beginTransaction();
            $inTransaction = true;
            echo "    -> Transaction started\n";
        } elseif (strtoupper(trim($stmt)) === 'COMMIT') {
            if ($inTransaction) {
                $pdo->commit();
                $inTransaction = false;
                echo "    -> Committed\n";
            }
        } else {
            $affected = $pdo->exec($stmt);
            echo "    -> OK (affected: $affected)\n";
        }
    } catch (PDOException $e) {
        echo "    -> ERROR: " . $e->getMessage() . "\n";
        if ($inTransaction) {
            $pdo->rollBack();
            echo "    -> Transaction rolled back!\n";
        }
        $success = false;
        break;
    }
}

echo "\n";

if ($success) {
    echo "=== MIGRATION COMPLETED SUCCESSFULLY ===\n\n";

    // Run verification
    echo "=== VERIFICATION ===\n";
    $r = $pdo->query("SELECT sc.name, COUNT(cf.id) as field_count FROM sample_categories sc LEFT JOIN category_fields cf ON cf.category_id = sc.id AND cf.is_active = 1 GROUP BY sc.id ORDER BY sc.id");
    foreach ($r as $row) {
        echo $row['name'] . ': ' . $row['field_count'] . " active fields\n";
    }

    echo "\n=== FIELD DETAILS ===\n";
    $r = $pdo->query("SELECT sc.name as cat, cf.field_name, cf.field_type, cf.display_order FROM category_fields cf JOIN sample_categories sc ON cf.category_id = sc.id WHERE cf.is_active = 1 ORDER BY sc.id, cf.display_order");
    $lastCat = '';
    foreach ($r as $row) {
        if ($row['cat'] !== $lastCat) {
            echo "\n--- {$row['cat']} ---\n";
            $lastCat = $row['cat'];
        }
        echo "  {$row['display_order']}. {$row['field_name']} ({$row['field_type']})\n";
    }

    echo "\n=== SAMPLE COUNTS (unchanged) ===\n";
    $r = $pdo->query("SELECT sc.name, COUNT(s.id) as cnt FROM sample_categories sc LEFT JOIN samples s ON s.category_id=sc.id GROUP BY sc.id ORDER BY sc.id");
    foreach ($r as $row) {
        echo $row['name'] . ': ' . $row['cnt'] . "\n";
    }
} else {
    echo "=== MIGRATION FAILED - SEE ERROR ABOVE ===\n";
    exit(1);
}
