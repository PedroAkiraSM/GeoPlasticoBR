<?php
$pdo = getDatabaseConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unidades_action'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $unit_error = 'Token de seguranca invalido.';
    } else {
        $action = $_POST['unidades_action'];

        if ($action === 'add_unit') {
            $name = trim($_POST['unit_name'] ?? '');
            $desc = trim($_POST['unit_description'] ?? '') ?: null;

            if ($name === '') {
                $unit_error = 'Nome e obrigatorio.';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO measurement_units (name, description) VALUES (:name, :desc)");
                    $stmt->execute([':name' => $name, ':desc' => $desc]);
                    $unitId = $pdo->lastInsertId();

                    $defaults = [
                        ['baixo', 0, 1000, '#00CC88'],
                        ['medio', 1000, 3000, '#FFD700'],
                        ['elevado', 3000, 5000, '#FFA500'],
                        ['alto', 5000, 8000, '#FF6600'],
                        ['critico', 8000, null, '#CC0000'],
                    ];
                    $ins = $pdo->prepare("INSERT INTO concentration_thresholds (unit_id, level, min_value, max_value, color) VALUES (:uid, :lvl, :min, :max, :col)");
                    foreach ($defaults as $d) {
                        $ins->execute([':uid' => $unitId, ':lvl' => $d[0], ':min' => $d[1], ':max' => $d[2], ':col' => $d[3]]);
                    }
                    $unit_success = "Unidade '$name' adicionada com limiares padrao.";
                } catch (PDOException $e) {
                    $unit_error = str_contains($e->getMessage(), 'Duplicate') ? 'Esta unidade ja existe.' : 'Erro ao salvar.';
                }
            }
        }

        if ($action === 'save_thresholds') {
            $unitId = (int) ($_POST['unit_id'] ?? 0);
            $levels = ['baixo', 'medio', 'elevado', 'alto', 'critico'];

            $stmt = $pdo->prepare("UPDATE concentration_thresholds SET min_value = :min, max_value = :max, color = :col WHERE unit_id = :uid AND level = :lvl");

            foreach ($levels as $lvl) {
                $min = $_POST["thresh_{$lvl}_min"] ?? 0;
                $max = $_POST["thresh_{$lvl}_max"] ?? null;
                $color = $_POST["thresh_{$lvl}_color"] ?? '#6b7280';

                if ($max === '' || $max === null) $max = null;

                $stmt->execute([
                    ':min' => (float) $min,
                    ':max' => $max !== null ? (float) $max : null,
                    ':col' => $color,
                    ':uid' => $unitId,
                    ':lvl' => $lvl,
                ]);
            }
            $unit_success = 'Limiares atualizados.';
        }

        if ($action === 'toggle_unit') {
            $id = (int) ($_POST['unit_id'] ?? 0);
            $newStatus = (int) ($_POST['new_status'] ?? 1);
            $pdo->prepare("UPDATE measurement_units SET active = :s WHERE id = :id")->execute([':s' => $newStatus, ':id' => $id]);
            $unit_success = $newStatus ? 'Unidade reativada.' : 'Unidade desativada.';
        }
    }
}

$units = $pdo->query("SELECT * FROM measurement_units ORDER BY active DESC, name ASC")->fetchAll();
$thresholdStmt = $pdo->prepare("SELECT * FROM concentration_thresholds WHERE unit_id = :uid ORDER BY min_value ASC");
$expandedUnit = isset($_POST['unit_id']) ? (int) $_POST['unit_id'] : null;
$levelLabels = ['baixo' => 'Baixo', 'medio' => 'Medio', 'elevado' => 'Elevado', 'alto' => 'Alto', 'critico' => 'Critico'];
?>

<div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Unidades de Medida e Limiares</h2>

    <?php if (isset($unit_error)): ?>
        <div class="mb-4 p-4 bg-red-100 text-red-800 border border-red-200 rounded-lg"><?= htmlspecialchars($unit_error) ?></div>
    <?php endif; ?>
    <?php if (isset($unit_success)): ?>
        <div class="mb-4 p-4 bg-green-100 text-green-800 border border-green-200 rounded-lg"><?= htmlspecialchars($unit_success) ?></div>
    <?php endif; ?>

    <!-- Add new unit -->
    <form method="POST" class="flex gap-3 mb-6 items-end">
        <input type="hidden" name="unidades_action" value="add_unit">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <div class="flex-1">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Nome da Unidade</label>
            <input type="text" name="unit_name" required placeholder="ex: part/Kg"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
        </div>
        <div class="flex-[2]">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Descricao (opcional)</label>
            <input type="text" name="unit_description" placeholder="Descricao..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
        </div>
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap">
            + Adicionar
        </button>
    </form>

    <!-- Units list -->
    <div class="space-y-3">
        <?php foreach ($units as $unit):
            $thresholdStmt->execute([':uid' => $unit['id']]);
            $thresholds = $thresholdStmt->fetchAll();
            $isExpanded = ($expandedUnit === (int) $unit['id']);
        ?>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <!-- Unit header -->
                <div class="flex justify-between items-center px-4 py-3 bg-gray-50 cursor-pointer hover:bg-gray-100"
                    onclick="this.nextElementSibling.classList.toggle('hidden')">
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-gray-900"><?= htmlspecialchars($unit['name']) ?></span>
                        <span class="text-sm text-gray-500"><?= htmlspecialchars($unit['description'] ?? '') ?></span>
                        <?php if (!$unit['active']): ?>
                            <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs font-semibold">Inativo</span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- Color preview -->
                        <div class="flex gap-0.5">
                            <?php foreach ($thresholds as $t): ?>
                                <div style="width: 20px; height: 12px; background: <?= htmlspecialchars($t['color']) ?>; border-radius: 2px;" title="<?= $levelLabels[$t['level']] ?? $t['level'] ?>"></div>
                            <?php endforeach; ?>
                        </div>
                        <form method="POST" class="inline" onclick="event.stopPropagation()">
                            <input type="hidden" name="unidades_action" value="toggle_unit">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="unit_id" value="<?= $unit['id'] ?>">
                            <input type="hidden" name="new_status" value="<?= $unit['active'] ? 0 : 1 ?>">
                            <button type="submit" class="bg-gray-200 hover:bg-gray-300 text-gray-600 px-2 py-1 rounded text-xs">
                                <?= $unit['active'] ? 'Desativar' : 'Reativar' ?>
                            </button>
                        </form>
                        <span class="text-gray-400 text-xs">&#9660;</span>
                    </div>
                </div>

                <!-- Thresholds (expandable) -->
                <div class="<?= $isExpanded ? '' : 'hidden' ?> p-4 border-t border-gray-200">
                    <form method="POST">
                        <input type="hidden" name="unidades_action" value="save_thresholds">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="unit_id" value="<?= $unit['id'] ?>">

                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-xs text-gray-500">
                                    <th class="text-left py-2">Nivel</th>
                                    <th class="text-left py-2">Min</th>
                                    <th class="text-left py-2">Max</th>
                                    <th class="text-left py-2">Cor</th>
                                    <th class="text-left py-2">Preview</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($thresholds as $t): ?>
                                    <tr>
                                        <td class="py-2 font-medium text-gray-700"><?= $levelLabels[$t['level']] ?? $t['level'] ?></td>
                                        <td class="py-2">
                                            <input type="number" step="0.0001" name="thresh_<?= $t['level'] ?>_min" value="<?= $t['min_value'] ?>"
                                                class="w-24 px-2 py-1.5 border border-gray-300 rounded text-sm">
                                        </td>
                                        <td class="py-2">
                                            <input type="number" step="0.0001" name="thresh_<?= $t['level'] ?>_max" value="<?= $t['max_value'] ?? '' ?>" placeholder="&#8734;"
                                                class="w-24 px-2 py-1.5 border border-gray-300 rounded text-sm">
                                        </td>
                                        <td class="py-2">
                                            <input type="color" name="thresh_<?= $t['level'] ?>_color" value="<?= htmlspecialchars($t['color']) ?>"
                                                class="w-10 h-8 p-0 border border-gray-300 rounded cursor-pointer">
                                        </td>
                                        <td class="py-2">
                                            <div style="width: 40px; height: 20px; background: <?= htmlspecialchars($t['color']) ?>; border-radius: 4px;"></div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <button type="submit" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                            Salvar Limiares
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
