<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
if (!$pdo) { echo "FAILED: No DB connection\n"; exit(1); }

// 1. dimensao: select -> text
$a = $pdo->exec("UPDATE category_fields SET field_type = 'text', select_options = NULL WHERE field_name = 'dimensao'");
echo "dimensao -> text: affected $a rows\n";

// 2. Add Multicolorido to Cor
$a = $pdo->exec("UPDATE category_fields SET select_options = '[\"Azul\",\"Vermelho\",\"Preto\",\"Branco\",\"Transparente\",\"Amarelo\",\"Verde\",\"Marrom\",\"Cinza\",\"Laranja\",\"Rosa\",\"Roxo\",\"Multicolorido\"]' WHERE field_name = 'cor' AND field_type = 'multicheck'");
echo "cor + Multicolorido: affected $a rows\n";

// Verify
echo "\n=== Verification ===\n";
$r = $pdo->query("SELECT sc.name, cf.field_name, cf.field_type, cf.select_options FROM category_fields cf JOIN sample_categories sc ON cf.category_id=sc.id WHERE cf.field_name IN ('dimensao','cor') AND cf.is_active=1 ORDER BY sc.id, cf.field_name");
foreach ($r as $row) {
    $opts = $row['select_options'] ? substr($row['select_options'], 0, 80) : 'null';
    echo "{$row['name']} | {$row['field_name']}: type={$row['field_type']} opts={$opts}\n";
}
echo "\nDONE\n";
