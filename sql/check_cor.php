<?php
require_once __DIR__ . '/../config/database.php';
$pdo = getDatabaseConnection();
$r = $pdo->query("SELECT id, select_options FROM category_fields WHERE field_name='cor' AND is_active=1 LIMIT 1");
$row = $r->fetch();
echo "ID: " . $row['id'] . "\n";
echo "Options: " . $row['select_options'] . "\n";
echo "Has Multicolorido: " . (strpos($row['select_options'], 'Multicolorido') !== false ? 'YES' : 'NO') . "\n";

// If missing, add it
if (strpos($row['select_options'], 'Multicolorido') === false) {
    $opts = json_decode($row['select_options'], true);
    $opts[] = 'Multicolorido';
    $newOpts = json_encode($opts, JSON_UNESCAPED_UNICODE);
    $a = $pdo->exec("UPDATE category_fields SET select_options = '" . addslashes($newOpts) . "' WHERE field_name = 'cor' AND is_active=1");
    echo "Added Multicolorido: affected $a rows\n";
} else {
    echo "Already present, no action needed.\n";
}
