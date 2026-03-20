<?php
/**
 * GeoPlasticoBR - Species Table Migration Runner
 * Run via CLI: php run_species_migration.php
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "FAILED: Could not connect to database\n";
    exit(1);
}

echo "Connected to database.\n";

$sqlFile = __DIR__ . '/migration_species.sql';
if (!file_exists($sqlFile)) {
    echo "FAILED: SQL file not found at $sqlFile\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);
$sql = preg_replace('/^--.*$/m', '', $sql);

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
            if ($trimmed !== '') $statements[] = $trimmed;
            $current = '';
        } else {
            $current .= $char;
        }
    }
}
$trimmed = trim($current);
if ($trimmed !== '') $statements[] = $trimmed;

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

    echo "=== VERIFICATION ===\n";
    $r = $pdo->query("DESCRIBE species");
    echo "Species table columns:\n";
    foreach ($r as $row) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }

    echo "\nEspecie field types:\n";
    $r = $pdo->query("SELECT sc.name, cf.field_type FROM category_fields cf JOIN sample_categories sc ON cf.category_id = sc.id WHERE cf.field_name = 'especie'");
    foreach ($r as $row) {
        echo "  {$row['name']}: {$row['field_type']}\n";
    }
} else {
    echo "=== MIGRATION FAILED - SEE ERROR ABOVE ===\n";
    exit(1);
}
