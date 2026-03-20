<?php
$pdo = getDatabaseConnection();
$allCats = getCategories(true);
$selectedCatId = (int)($_GET['cat_id'] ?? $_POST['cat_id'] ?? 0);
if ($selectedCatId === 0 && !empty($allCats)) $selectedCatId = $allCats[0]['id'];
$selectedCat = null;
foreach ($allCats as $c) { if ($c['id'] === $selectedCatId) { $selectedCat = $c; break; } }

$fieldTypes = [
    'text' => ['label' => 'Texto', 'icon' => 'T', 'color' => 'blue'],
    'number' => ['label' => 'Numero Inteiro', 'icon' => '#', 'color' => 'purple'],
    'decimal' => ['label' => 'Decimal', 'icon' => '.0', 'color' => 'indigo'],
    'checkbox' => ['label' => 'Checkbox', 'icon' => '&check;', 'color' => 'green'],
    'multicheck' => ['label' => 'Multi-Checkbox', 'icon' => '&#9745;', 'color' => 'emerald'],
    'select' => ['label' => 'Lista (Select)', 'icon' => '&darr;', 'color' => 'orange'],
    'textarea' => ['label' => 'Texto Longo', 'icon' => '&para;', 'color' => 'teal'],
];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['campos_action'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $campos_error = 'Token de seguranca invalido.';
    } else {
        $action = $_POST['campos_action'];

        if ($action === 'add' && $selectedCatId > 0) {
            $fieldName = trim(preg_replace('/[^a-z0-9_]/', '_', strtolower($_POST['field_name'] ?? '')));
            $fieldLabel = trim($_POST['field_label'] ?? '');
            $fieldType = $_POST['field_type'] ?? 'text';
            $placeholder = trim($_POST['placeholder'] ?? '');
            $isRequired = isset($_POST['is_required']) ? 1 : 0;
            $selectOptions = trim($_POST['select_options'] ?? '');

            if ($fieldName === '' || $fieldLabel === '') {
                $campos_error = 'Nome interno e label sao obrigatorios.';
            } else {
                if (in_array($fieldType, ['select', 'multicheck']) && $selectOptions !== '') {
                    $decoded = json_decode($selectOptions);
                    if (!is_array($decoded)) {
                        $campos_error = 'Opcoes devem ser JSON array. Ex: ["Op1","Op2","Op3"]';
                    }
                }
                if (!isset($campos_error)) {
                    try {
                        $maxOrder = $pdo->prepare("SELECT COALESCE(MAX(display_order),0)+1 FROM category_fields WHERE category_id=:cid");
                        $maxOrder->execute([':cid' => $selectedCatId]);
                        $nextOrder = (int)$maxOrder->fetchColumn();

                        $stmt = $pdo->prepare("INSERT INTO category_fields (category_id, field_name, field_label, field_type, select_options, is_required, display_order, placeholder) VALUES (:cid, :fn, :fl, :ft, :so, :req, :ord, :ph)");
                        $stmt->execute([
                            ':cid' => $selectedCatId, ':fn' => $fieldName, ':fl' => $fieldLabel, ':ft' => $fieldType,
                            ':so' => $selectOptions ?: null, ':req' => $isRequired, ':ord' => $nextOrder, ':ph' => $placeholder ?: null,
                        ]);
                        $campos_success = "Campo '$fieldLabel' criado!";
                    } catch (PDOException $e) {
                        if (str_contains($e->getMessage(), 'Duplicate entry')) {
                            $campos_error = "Ja existe um campo '$fieldName' nesta categoria.";
                        } else {
                            $campos_error = 'Erro: ' . $e->getMessage();
                        }
                    }
                }
            }
        }

        if ($action === 'edit') {
            $id = (int)$_POST['field_id'];
            $fieldLabel = trim($_POST['field_label'] ?? '');
            $fieldType = $_POST['field_type'] ?? 'text';
            $placeholder = trim($_POST['placeholder'] ?? '');
            $isRequired = isset($_POST['is_required']) ? 1 : 0;
            $selectOptions = trim($_POST['select_options'] ?? '');

            if ($fieldLabel === '') {
                $campos_error = 'Label e obrigatorio.';
            } else {
                if (in_array($fieldType, ['select', 'multicheck']) && $selectOptions !== '') {
                    $decoded = json_decode($selectOptions);
                    if (!is_array($decoded)) { $campos_error = 'Opcoes devem ser JSON array.'; }
                }
                if (!isset($campos_error)) {
                    $stmt = $pdo->prepare("UPDATE category_fields SET field_label=:fl, field_type=:ft, select_options=:so, is_required=:req, placeholder=:ph WHERE id=:id");
                    $stmt->execute([':fl'=>$fieldLabel, ':ft'=>$fieldType, ':so'=>$selectOptions?:null, ':req'=>$isRequired, ':ph'=>$placeholder?:null, ':id'=>$id]);
                    $campos_success = 'Campo atualizado!';
                }
            }
        }

        if ($action === 'toggle') {
            $id = (int)$_POST['field_id'];
            $new = (int)$_POST['new_status'];
            $pdo->prepare("UPDATE category_fields SET is_active=:s WHERE id=:id")->execute([':s'=>$new, ':id'=>$id]);
            $campos_success = $new ? 'Campo reativado.' : 'Campo desativado.';
        }

        if ($action === 'delete') {
            $id = (int)$_POST['field_id'];
            $count = $pdo->prepare("SELECT COUNT(*) FROM sample_values WHERE field_id=:id");
            $count->execute([':id'=>$id]);
            if ((int)$count->fetchColumn() > 0) {
                $campos_error = 'Nao pode excluir: existem valores vinculados. Desative o campo.';
            } else {
                $pdo->prepare("DELETE FROM category_fields WHERE id=:id")->execute([':id'=>$id]);
                $campos_success = 'Campo excluido.';
            }
        }

        if ($action === 'reorder') {
            $order = json_decode($_POST['field_order'] ?? '[]', true);
            if (is_array($order)) {
                $stmt = $pdo->prepare("UPDATE category_fields SET display_order=:ord WHERE id=:id AND category_id=:cid");
                foreach ($order as $i => $fieldId) {
                    $stmt->execute([':ord' => $i + 1, ':id' => (int)$fieldId, ':cid' => $selectedCatId]);
                }
                $campos_success = 'Ordem atualizada!';
            }
        }
    }
}

$fields = $selectedCatId > 0 ? getCategoryFields($selectedCatId, true) : [];
?>

<div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-2">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Campos Personalizados</h2>
            <p class="text-sm text-gray-500 mt-1">Defina quais informacoes serao coletadas em cada categoria.</p>
        </div>
    </div>

    <?php if (isset($campos_error)): ?>
        <div class="mb-4 p-4 bg-red-50 text-red-700 border border-red-200 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <?= htmlspecialchars($campos_error) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($campos_success)): ?>
        <div class="mb-4 p-4 bg-green-50 text-green-700 border border-green-200 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <?= htmlspecialchars($campos_success) ?>
        </div>
    <?php endif; ?>

    <!-- Category Selector Tabs -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
        <?php foreach ($allCats as $cat):
            $catFieldCount = $pdo->prepare("SELECT COUNT(*) FROM category_fields WHERE category_id=:cid");
            $catFieldCount->execute([':cid' => $cat['id']]);
            $cfCount = (int)$catFieldCount->fetchColumn();
        ?>
            <a href="?tab=campos&cat_id=<?= $cat['id'] ?>"
               class="px-4 py-2.5 rounded-lg text-sm font-semibold border-2 transition-all flex items-center gap-2 <?= $cat['id'] === $selectedCatId ? 'border-blue-500 bg-blue-50 text-blue-700 shadow-sm' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700' ?> <?= !$cat['is_active'] ? 'opacity-50' : '' ?>">
                <span class="inline-block w-3 h-3 rounded-full shrink-0" style="background:<?= htmlspecialchars($cat['color']) ?>"></span>
                <?= htmlspecialchars($cat['name']) ?>
                <span class="bg-gray-200 text-gray-600 rounded-full px-2 py-0.5 text-xs font-bold"><?= $cfCount ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($selectedCat): ?>
    <!-- Add new field -->
    <div class="mb-6">
        <button onclick="document.getElementById('addFieldForm').classList.toggle('hidden')" class="text-sm font-semibold text-green-700 hover:text-green-800 flex items-center gap-1.5 mb-3">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Adicionar campo a "<?= htmlspecialchars($selectedCat['name']) ?>"
        </button>
        <form method="POST" id="addFieldForm" class="hidden bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-5">
            <input type="hidden" name="campos_action" value="add">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="cat_id" value="<?= $selectedCatId ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Label (o que o usuario ve) *</label>
                    <input type="text" name="field_label" required placeholder="Ex: Concentracao Total" id="addFieldLabel"
                        oninput="autoSlug(this.value)"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nome interno (auto-gerado) *</label>
                    <input type="text" name="field_name" required id="addFieldName"
                        pattern="[a-z0-9_]+" title="Apenas letras minusculas, numeros e _"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-mono bg-gray-50 text-gray-600">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Tipo do Campo</label>
                    <select name="field_type" id="addFieldType" onchange="toggleSelectOpts(this)"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                        <?php foreach ($fieldTypes as $k => $v): ?>
                        <option value="<?= $k ?>"><?= $v['icon'] ?> <?= $v['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Placeholder</label>
                    <input type="text" name="placeholder" placeholder="Texto de ajuda..."
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm">
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-2 cursor-pointer bg-white border border-gray-300 rounded-lg px-4 py-2.5 w-full">
                        <input type="checkbox" name="is_required" class="rounded text-green-600 focus:ring-green-500">
                        <span class="text-sm text-gray-700">Obrigatorio</span>
                    </label>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">
                        Criar Campo
                    </button>
                </div>
            </div>

            <div class="hidden" id="selectOptsAdd">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Opcoes do Select (JSON array)</label>
                <input type="text" name="select_options" placeholder='["Opcao 1","Opcao 2","Opcao 3"]'
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm font-mono">
                <p class="text-xs text-gray-400 mt-1">Formato: ["valor1","valor2","valor3"]</p>
            </div>
        </form>
    </div>

    <!-- Fields List -->
    <?php if (empty($fields)): ?>
        <div class="text-center py-12 text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <p class="font-semibold mb-1">Nenhum campo definido</p>
            <p class="text-sm">Clique em "+ Adicionar campo" acima.</p>
        </div>
    <?php else: ?>
    <div class="space-y-2" id="fieldsBody">
        <?php foreach ($fields as $i => $field):
            $valCount = $pdo->prepare("SELECT COUNT(*) FROM sample_values WHERE field_id=:id");
            $valCount->execute([':id' => $field['id']]);
            $vCount = (int)$valCount->fetchColumn();
            $ft = $fieldTypes[$field['field_type']] ?? ['label'=>$field['field_type'],'icon'=>'?','color'=>'gray'];
        ?>
        <div class="border border-gray-200 rounded-lg overflow-hidden <?= !$field['is_active'] ? 'opacity-50' : '' ?>" data-field-id="<?= $field['id'] ?>" draggable="true">
            <!-- Field row -->
            <div class="flex items-center gap-3 px-4 py-3 bg-white hover:bg-gray-50 transition-colors">
                <!-- Drag handle -->
                <div class="text-gray-300 cursor-move shrink-0" title="Arraste para reordenar">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
                </div>
                <!-- Order number -->
                <span class="text-xs font-mono text-gray-400 w-5 text-center shrink-0"><?= $field['display_order'] ?></span>
                <!-- Type badge -->
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-bold shrink-0 bg-<?= $ft['color'] ?>-100 text-<?= $ft['color'] ?>-700">
                    <?= $ft['icon'] ?>
                </span>
                <!-- Label + name -->
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($field['field_label']) ?></div>
                    <div class="text-xs font-mono text-gray-400"><?= htmlspecialchars($field['field_name']) ?></div>
                </div>
                <!-- Required badge -->
                <?php if ($field['is_required']): ?>
                    <span class="text-xs font-bold text-red-500 bg-red-50 px-2 py-0.5 rounded">Obrig.</span>
                <?php endif; ?>
                <!-- Values count -->
                <span class="text-xs text-gray-400" title="Valores preenchidos"><?= $vCount ?> val.</span>
                <!-- Status -->
                <?php if (!$field['is_active']): ?>
                    <span class="text-xs font-bold text-red-500 bg-red-50 px-2 py-0.5 rounded">Inativo</span>
                <?php endif; ?>
                <!-- Actions -->
                <div class="flex gap-1 shrink-0">
                    <button onclick="this.closest('[data-field-id]').querySelector('.edit-panel').classList.toggle('hidden')"
                        class="p-1.5 rounded hover:bg-gray-200 text-gray-500 transition-colors" title="Editar">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    <form method="POST" class="inline">
                        <input type="hidden" name="campos_action" value="toggle">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="cat_id" value="<?= $selectedCatId ?>">
                        <input type="hidden" name="field_id" value="<?= $field['id'] ?>">
                        <input type="hidden" name="new_status" value="<?= $field['is_active'] ? 0 : 1 ?>">
                        <button type="submit" class="p-1.5 rounded hover:bg-gray-200 text-gray-500 transition-colors" title="<?=$field['is_active']?'Desativar':'Reativar'?>">
                            <?php if($field['is_active']): ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878l4.242 4.242M21 21l-4.878-4.878"/></svg>
                            <?php else: ?>
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <?php endif; ?>
                        </button>
                    </form>
                    <?php if ($vCount === 0): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Excluir campo <?=htmlspecialchars(addslashes($field['field_label']))?>?')">
                        <input type="hidden" name="campos_action" value="delete">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="cat_id" value="<?= $selectedCatId ?>">
                        <input type="hidden" name="field_id" value="<?= $field['id'] ?>">
                        <button type="submit" class="p-1.5 rounded hover:bg-red-100 text-gray-400 hover:text-red-600 transition-colors" title="Excluir">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Edit panel (hidden) -->
            <div class="edit-panel hidden border-t border-gray-200 bg-amber-50 p-4">
                <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                    <input type="hidden" name="campos_action" value="edit">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="cat_id" value="<?= $selectedCatId ?>">
                    <input type="hidden" name="field_id" value="<?= $field['id'] ?>">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Label</label>
                        <input type="text" name="field_label" value="<?=htmlspecialchars($field['field_label'])?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo</label>
                        <select name="field_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <?php foreach($fieldTypes as $k=>$v): ?>
                            <option value="<?=$k?>" <?=$field['field_type']===$k?'selected':''?>><?= $v['icon'] ?> <?= $v['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Placeholder / Opcoes</label>
                        <input type="text" name="placeholder" value="<?=htmlspecialchars($field['placeholder'] ?? '')?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-1">
                        <input type="text" name="select_options" value="<?=htmlspecialchars($field['select_options'] ?? '')?>"
                            placeholder='["Op1","Op2"]' class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono text-xs">
                    </div>
                    <div class="flex items-end gap-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_required" <?=$field['is_required']?'checked':''?> class="rounded text-amber-600">
                            <span class="text-xs font-semibold">Obrig.</span>
                        </label>
                        <button type="submit" class="flex-1 bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Reorder -->
    <?php if (count($fields) > 1): ?>
    <div class="mt-4 flex items-center gap-3 pt-4 border-t border-gray-100">
        <p class="text-xs text-gray-400 flex items-center gap-1">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/></svg>
            Arraste para reordenar, depois:
        </p>
        <form method="POST" id="reorderForm">
            <input type="hidden" name="campos_action" value="reorder">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="cat_id" value="<?= $selectedCatId ?>">
            <input type="hidden" name="field_order" id="fieldOrderInput" value="">
            <button type="submit" onclick="return saveFieldOrder()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-1.5 rounded-lg text-xs font-semibold transition-colors">
                Salvar Ordem
            </button>
        </form>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php else: ?>
        <div class="text-center py-16 text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            <p class="text-lg font-semibold mb-1">Nenhuma categoria encontrada</p>
            <p class="text-sm">Crie categorias na aba "Categorias" primeiro.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function autoSlug(label) {
    const slug = label.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
    document.getElementById('addFieldName').value = slug;
}

function toggleSelectOpts(sel) {
    const div = document.getElementById('selectOptsAdd');
    if (div) div.classList.toggle('hidden', sel.value !== 'select' && sel.value !== 'multicheck');
}

// Drag-and-drop reorder
(function() {
    const container = document.getElementById('fieldsBody');
    if (!container) return;
    let dragging = null;

    container.querySelectorAll('[data-field-id]').forEach(el => {
        el.addEventListener('dragstart', e => { dragging = el; el.style.opacity = '0.4'; });
        el.addEventListener('dragend', () => { dragging = null; el.style.opacity = '1'; });
        el.addEventListener('dragover', e => {
            e.preventDefault();
            if (dragging && dragging !== el && el.dataset.fieldId) {
                const rect = el.getBoundingClientRect();
                const mid = rect.top + rect.height / 2;
                if (e.clientY < mid) container.insertBefore(dragging, el);
                else container.insertBefore(dragging, el.nextElementSibling);
            }
        });
    });
})();

function saveFieldOrder() {
    const els = document.querySelectorAll('#fieldsBody [data-field-id]');
    const order = Array.from(els).map(el => el.dataset.fieldId);
    document.getElementById('fieldOrderInput').value = JSON.stringify(order);
    return true;
}
</script>
