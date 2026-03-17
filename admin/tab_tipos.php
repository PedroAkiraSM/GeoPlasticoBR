<?php
$pdo = getDatabaseConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipos_action'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $tipos_error = 'Token de seguranca invalido.';
    } else {
        $action = $_POST['tipos_action'];

        if ($action === 'add') {
            $cat = $_POST['category'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '') ?: null;

            if ($name === '') {
                $tipos_error = 'Nome e obrigatorio.';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO data_types (category, name, description) VALUES (:cat, :name, :desc)");
                    $stmt->execute([':cat' => $cat, ':name' => $name, ':desc' => $desc]);
                    $tipos_success = "Tipo '$name' adicionado.";
                } catch (PDOException $e) {
                    $tipos_error = str_contains($e->getMessage(), 'Duplicate') ? 'Este tipo ja existe nesta categoria.' : 'Erro ao salvar.';
                }
            }
        }

        if ($action === 'toggle') {
            $id = (int) ($_POST['type_id'] ?? 0);
            $newStatus = (int) ($_POST['new_status'] ?? 1);
            $stmt = $pdo->prepare("UPDATE data_types SET active = :s WHERE id = :id");
            $stmt->execute([':s' => $newStatus, ':id' => $id]);
            $tipos_success = $newStatus ? 'Tipo reativado.' : 'Tipo desativado.';
        }

        if ($action === 'delete') {
            $id = (int) ($_POST['type_id'] ?? 0);
            $type = $pdo->prepare("SELECT category, name FROM data_types WHERE id = :id");
            $type->execute([':id' => $id]);
            $typeRow = $type->fetch();

            if ($typeRow) {
                $colMap = ['ambiente' => 'tipo_ambiente', 'ecossistema' => 'ecossistema', 'matriz' => 'matriz'];
                $col = $colMap[$typeRow['category']] ?? null;

                $linkedCount = 0;
                if ($col) {
                    $check = $pdo->prepare("SELECT COUNT(*) FROM microplastics_sediment WHERE $col = :name");
                    $check->execute([':name' => $typeRow['name']]);
                    $linkedCount = (int) $check->fetchColumn();
                }

                if ($linkedCount > 0) {
                    $tipos_error = "Nao e possivel excluir: $linkedCount registro(s) vinculado(s). Use desativar.";
                } else {
                    $pdo->prepare("DELETE FROM data_types WHERE id = :id")->execute([':id' => $id]);
                    $tipos_success = 'Tipo excluido.';
                }
            }
        }

        if ($action === 'edit') {
            $id = (int) ($_POST['type_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '') ?: null;

            if ($name === '') {
                $tipos_error = 'Nome e obrigatorio.';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE data_types SET name = :name, description = :desc WHERE id = :id");
                    $stmt->execute([':name' => $name, ':desc' => $desc, ':id' => $id]);
                    $tipos_success = 'Tipo atualizado.';
                } catch (PDOException $e) {
                    $tipos_error = str_contains($e->getMessage(), 'Duplicate') ? 'Este nome ja existe nesta categoria.' : 'Erro ao salvar.';
                }
            }
        }
    }
}

$selectedCat = $_GET['cat'] ?? 'ambiente';
$categories = ['ambiente' => 'Ambientes', 'ecossistema' => 'Ecossistemas', 'matriz' => 'Matrizes'];
$types = getAllDataTypes($selectedCat);
$colMap = ['ambiente' => 'tipo_ambiente', 'ecossistema' => 'ecossistema', 'matriz' => 'matriz'];
$col = $colMap[$selectedCat] ?? 'tipo_ambiente';
?>

<div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Tipos de Dados</h2>

    <?php if (isset($tipos_error)): ?>
        <div class="mb-4 p-4 bg-red-100 text-red-800 border border-red-200 rounded-lg"><?= htmlspecialchars($tipos_error) ?></div>
    <?php endif; ?>
    <?php if (isset($tipos_success)): ?>
        <div class="mb-4 p-4 bg-green-100 text-green-800 border border-green-200 rounded-lg"><?= htmlspecialchars($tipos_success) ?></div>
    <?php endif; ?>

    <!-- Category selector -->
    <div class="flex gap-2 mb-6">
        <?php foreach ($categories as $cKey => $cLabel): ?>
            <a href="?tab=tipos&cat=<?= $cKey ?>"
                class="px-4 py-2 rounded-lg text-sm font-semibold <?= $selectedCat === $cKey ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
                <?= $cLabel ?>
                <span class="bg-gray-200 text-gray-600 px-1.5 py-0.5 rounded-full text-xs ml-1"><?= count(array_filter($types, fn($t) => $t['active'])) ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Add new -->
    <form method="POST" class="flex gap-3 mb-6 items-end">
        <input type="hidden" name="tipos_action" value="add">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="category" value="<?= $selectedCat ?>">
        <div class="flex-1">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Nome</label>
            <input type="text" name="name" required placeholder="Novo tipo..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
        </div>
        <div class="flex-[2]">
            <label class="block text-xs font-semibold text-gray-500 mb-1">Descricao (opcional)</label>
            <input type="text" name="description" placeholder="Descricao..."
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
        </div>
        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold whitespace-nowrap">
            + Adicionar
        </button>
    </form>

    <!-- Types table -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Nome</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Descricao</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600">Registros</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-600">Status</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Acoes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($types as $type):
                    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM microplastics_sediment WHERE $col = :name");
                    $countStmt->execute([':name' => $type['name']]);
                    $linkedCount = (int) $countStmt->fetchColumn();
                ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium"><?= htmlspecialchars($type['name']) ?></td>
                        <td class="px-4 py-3 text-gray-500"><?= htmlspecialchars($type['description'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-center text-gray-500"><?= $linkedCount ?></td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-1 rounded text-xs font-semibold <?= $type['active'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                                <?= $type['active'] ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex gap-1 justify-end">
                                <button type="button" onclick="document.getElementById('edit-row-<?= $type['id'] ?>').classList.toggle('hidden')" title="Editar"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-2 py-1.5 rounded text-xs">Editar</button>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="tipos_action" value="toggle">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="type_id" value="<?= $type['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $type['active'] ? 0 : 1 ?>">
                                    <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-2 py-1.5 rounded text-xs">
                                        <?= $type['active'] ? 'Desativar' : 'Reativar' ?>
                                    </button>
                                </form>
                                <?php if ($linkedCount === 0): ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('Excluir tipo <?= htmlspecialchars(addslashes($type['name'])) ?>?')">
                                        <input type="hidden" name="tipos_action" value="delete">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="type_id" value="<?= $type['id'] ?>">
                                        <button type="submit" class="bg-red-100 hover:bg-red-200 text-red-700 px-2 py-1.5 rounded text-xs">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <!-- Inline edit row -->
                    <tr id="edit-row-<?= $type['id'] ?>" class="hidden">
                        <td colspan="5" class="px-4 py-3 bg-gray-50">
                            <form method="POST" class="flex gap-3 items-end">
                                <input type="hidden" name="tipos_action" value="edit">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="type_id" value="<?= $type['id'] ?>">
                                <div class="flex-1">
                                    <label class="block text-xs text-gray-500 mb-1">Nome</label>
                                    <input type="text" name="name" value="<?= htmlspecialchars($type['name']) ?>" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <div class="flex-[2]">
                                    <label class="block text-xs text-gray-500 mb-1">Descricao</label>
                                    <input type="text" name="description" value="<?= htmlspecialchars($type['description'] ?? '') ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">Salvar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
