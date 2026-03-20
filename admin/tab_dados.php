<?php
$pdo = getDatabaseConnection();
$allCats = getCategories(true);
$selectedCatId = (int)($_GET['cat_id'] ?? $_POST['cat_id'] ?? 0);
if ($selectedCatId === 0 && !empty($allCats)) $selectedCatId = $allCats[0]['id'];
$selectedCat = null;
foreach ($allCats as $c) { if ($c['id'] === $selectedCatId) { $selectedCat = $c; break; } }

$editingSampleId = (int)($_GET['edit_sample_id'] ?? 0);
$editingSample = $editingSampleId > 0 ? getSample($editingSampleId) : null;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dados_action'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $dados_error = 'Token de seguranca invalido.';
    } else {
        $action = $_POST['dados_action'];

        if ($action === 'add' && $selectedCatId > 0) {
            $title = trim($_POST['title'] ?? '');
            $lat = $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
            $lng = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
            $author = trim($_POST['author'] ?? '');
            $reference = trim($_POST['reference_text'] ?? '');
            $doi = trim($_POST['doi'] ?? '');

            if ($title === '') {
                $dados_error = 'Titulo e obrigatorio.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("INSERT INTO samples (category_id, title, latitude, longitude, author, reference_text, doi, approved) VALUES (:cid, :title, :lat, :lng, :author, :ref, :doi, 1)");
                    $stmt->execute([':cid'=>$selectedCatId, ':title'=>$title, ':lat'=>$lat, ':lng'=>$lng, ':author'=>$author, ':ref'=>$reference?:null, ':doi'=>$doi?:null]);
                    $sampleId = $pdo->lastInsertId();

                    $fields = getCategoryFields($selectedCatId);
                    $stmtVal = $pdo->prepare("INSERT INTO sample_values (sample_id, field_id, value_text, value_number) VALUES (:sid, :fid, :vt, :vn)");
                    foreach ($fields as $field) {
                        $rawVal = $_POST['field_' . $field['id']] ?? '';
                        if ($field['field_type'] === 'checkbox') $rawVal = isset($_POST['field_' . $field['id']]) ? '1' : '0';
                        if ($field['field_type'] === 'multicheck') {
                            $arr = $_POST['field_' . $field['id']] ?? [];
                            $rawVal = is_array($arr) && !empty($arr) ? json_encode($arr, JSON_UNESCAPED_UNICODE) : '';
                        }
                        if ($rawVal === '' && !$field['is_required']) continue;
                        $vt = null; $vn = null;
                        if (in_array($field['field_type'], ['number', 'decimal'])) { $vn = $rawVal !== '' ? (float)str_replace(',', '.', $rawVal) : null; }
                        else { $vt = $rawVal; }
                        $stmtVal->execute([':sid'=>$sampleId, ':fid'=>$field['id'], ':vt'=>$vt, ':vn'=>$vn]);
                    }
                    $pdo->commit();
                    $dados_success = "Amostra '$title' inserida com sucesso!";
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $dados_error = 'Erro: ' . $e->getMessage();
                }
            }
        }

        if ($action === 'edit') {
            $sampleId = (int)$_POST['sample_id'];
            $title = trim($_POST['title'] ?? '');
            $lat = $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
            $lng = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
            $author = trim($_POST['author'] ?? '');
            $reference = trim($_POST['reference_text'] ?? '');
            $doi = trim($_POST['doi'] ?? '');

            if ($title === '') { $dados_error = 'Titulo e obrigatorio.'; }
            else {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("UPDATE samples SET title=:title, latitude=:lat, longitude=:lng, author=:author, reference_text=:ref, doi=:doi WHERE id=:id");
                    $stmt->execute([':title'=>$title, ':lat'=>$lat, ':lng'=>$lng, ':author'=>$author, ':ref'=>$reference?:null, ':doi'=>$doi?:null, ':id'=>$sampleId]);

                    $fields = getCategoryFields($selectedCatId);
                    foreach ($fields as $field) {
                        $rawVal = $_POST['field_' . $field['id']] ?? '';
                        if ($field['field_type'] === 'checkbox') $rawVal = isset($_POST['field_' . $field['id']]) ? '1' : '0';
                        if ($field['field_type'] === 'multicheck') {
                            $arr = $_POST['field_' . $field['id']] ?? [];
                            $rawVal = is_array($arr) && !empty($arr) ? json_encode($arr, JSON_UNESCAPED_UNICODE) : '';
                        }
                        $vt = null; $vn = null;
                        if (in_array($field['field_type'], ['number','decimal'])) { $vn = $rawVal !== '' ? (float)str_replace(',','.',$rawVal) : null; }
                        else { $vt = $rawVal; }

                        $existing = $pdo->prepare("SELECT id FROM sample_values WHERE sample_id=:sid AND field_id=:fid");
                        $existing->execute([':sid'=>$sampleId, ':fid'=>$field['id']]);
                        if ($existing->fetchColumn()) {
                            $pdo->prepare("UPDATE sample_values SET value_text=:vt, value_number=:vn WHERE sample_id=:sid AND field_id=:fid")
                                ->execute([':vt'=>$vt, ':vn'=>$vn, ':sid'=>$sampleId, ':fid'=>$field['id']]);
                        } elseif ($rawVal !== '') {
                            $pdo->prepare("INSERT INTO sample_values (sample_id, field_id, value_text, value_number) VALUES (:sid, :fid, :vt, :vn)")
                                ->execute([':sid'=>$sampleId, ':fid'=>$field['id'], ':vt'=>$vt, ':vn'=>$vn]);
                        }
                    }
                    $pdo->commit();
                    $dados_success = 'Amostra atualizada!';
                    $editingSampleId = 0; $editingSample = null;
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    $dados_error = 'Erro: ' . $e->getMessage();
                }
            }
        }

        if ($action === 'delete') {
            $sampleId = (int)$_POST['sample_id'];
            $pdo->prepare("DELETE FROM samples WHERE id=:id")->execute([':id'=>$sampleId]);
            $dados_success = 'Amostra excluida.';
        }

        if ($action === 'toggle_approve') {
            $sampleId = (int)$_POST['sample_id'];
            $newStatus = (int)$_POST['new_status'];
            $pdo->prepare("UPDATE samples SET approved=:s WHERE id=:id")->execute([':s'=>$newStatus, ':id'=>$sampleId]);
            $dados_success = $newStatus ? 'Amostra aprovada.' : 'Amostra desaprovada.';
        }
    }
}

$catFields = $selectedCatId > 0 ? getCategoryFields($selectedCatId) : [];
$samples = [];
if ($selectedCatId > 0) {
    $stmt = $pdo->prepare("SELECT s.* FROM samples s WHERE s.category_id=:cid ORDER BY s.id DESC LIMIT 200");
    $stmt->execute([':cid'=>$selectedCatId]);
    $samples = $stmt->fetchAll();
}

// Helper to render a field input
function renderFieldInput($field, $value = '', $prefix = 'field_') {
    $name = $prefix . $field['id'];
    $req = $field['is_required'] ? 'required' : '';
    $ph = htmlspecialchars($field['placeholder'] ?? '');
    $cls = 'w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-shadow';

    if ($field['field_type'] === 'select') {
        $html = "<select name=\"$name\" $req class=\"$cls\"><option value=\"\">-- Selecione --</option>";
        foreach (json_decode($field['select_options'] ?? '[]', true) ?: [] as $opt) {
            $sel = $value === $opt ? 'selected' : '';
            $html .= '<option value="'.htmlspecialchars($opt)."\" $sel>".htmlspecialchars($opt).'</option>';
        }
        return $html . '</select>';
    }
    if ($field['field_type'] === 'textarea') {
        return "<textarea name=\"$name\" rows=\"2\" $req placeholder=\"$ph\" class=\"$cls\">".htmlspecialchars($value)."</textarea>";
    }
    if ($field['field_type'] === 'checkbox') {
        $chk = $value === '1' ? 'checked' : '';
        return "<label class=\"flex items-center gap-2 cursor-pointer h-[42px]\"><input type=\"checkbox\" name=\"$name\" value=\"1\" $chk class=\"rounded w-5 h-5 text-blue-600 focus:ring-blue-500\"><span class=\"text-sm text-gray-700\">Sim</span></label>";
    }
    if ($field['field_type'] === 'multicheck') {
        $options = json_decode($field['select_options'] ?? '[]', true) ?: [];
        $selected = $value !== '' ? json_decode($value, true) ?: [] : [];
        $html = '<div class="flex flex-wrap gap-2">';
        foreach ($options as $opt) {
            $chk = in_array($opt, $selected) ? 'checked' : '';
            $optEsc = htmlspecialchars($opt);
            $html .= "<label class=\"flex items-center gap-1.5 cursor-pointer bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 hover:bg-blue-50 hover:border-blue-300 transition-colors\"><input type=\"checkbox\" name=\"{$name}[]\" value=\"$optEsc\" $chk class=\"rounded w-4 h-4 text-blue-600 focus:ring-blue-500\"><span class=\"text-sm text-gray-700\">$optEsc</span></label>";
        }
        $html .= '</div>';
        return $html;
    }
    $type = $field['field_type'] === 'number' ? 'number' : 'text';
    $im = $field['field_type'] === 'decimal' ? 'inputmode="decimal"' : '';
    return "<input type=\"$type\" name=\"$name\" value=\"".htmlspecialchars($value)."\" $req $im placeholder=\"$ph\" class=\"$cls\">";
}
?>

<div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-2">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Dados / Amostras</h2>
            <p class="text-sm text-gray-500 mt-1">Insira e gerencie amostras. O formulario e gerado automaticamente.</p>
        </div>
    </div>

    <?php if (isset($dados_error)): ?>
        <div class="mb-4 p-4 bg-red-50 text-red-700 border border-red-200 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <?= htmlspecialchars($dados_error) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($dados_success)): ?>
        <div class="mb-4 p-4 bg-green-50 text-green-700 border border-green-200 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <?= htmlspecialchars($dados_success) ?>
        </div>
    <?php endif; ?>

    <!-- Category Selector -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
        <?php foreach ($allCats as $cat):
            if (!$cat['is_active']) continue;
            $catSampleCount = $pdo->prepare("SELECT COUNT(*) FROM samples WHERE category_id=:cid");
            $catSampleCount->execute([':cid'=>$cat['id']]);
            $csCount = (int)$catSampleCount->fetchColumn();
        ?>
            <a href="?tab=dados&cat_id=<?= $cat['id'] ?>"
               class="px-4 py-2.5 rounded-lg text-sm font-semibold border-2 transition-all flex items-center gap-2 <?= $cat['id'] === $selectedCatId ? 'border-blue-500 bg-blue-50 text-blue-700 shadow-sm' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50 hover:text-gray-700' ?>">
                <span class="inline-block w-3 h-3 rounded-full shrink-0" style="background:<?= htmlspecialchars($cat['color']) ?>"></span>
                <?= htmlspecialchars($cat['name']) ?>
                <span class="bg-gray-200 text-gray-600 rounded-full px-2 py-0.5 text-xs font-bold"><?= $csCount ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($selectedCat && !empty($catFields)): ?>

    <!-- ===== EDIT MODE ===== -->
    <?php if ($editingSample): ?>
    <div class="mb-6 border-2 border-amber-300 rounded-xl overflow-hidden">
        <div class="bg-amber-50 px-5 py-3 border-b border-amber-200 flex items-center justify-between">
            <h3 class="font-bold text-amber-800 flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                Editando Amostra #<?= $editingSample['id'] ?>
            </h3>
            <a href="?tab=dados&cat_id=<?= $selectedCatId ?>" class="text-sm text-amber-600 hover:text-amber-800 font-semibold">&times; Cancelar</a>
        </div>
        <form method="POST" class="p-5">
            <input type="hidden" name="dados_action" value="edit">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="cat_id" value="<?= $selectedCatId ?>">
            <input type="hidden" name="sample_id" value="<?= $editingSample['id'] ?>">

            <!-- Section: Basic Info -->
            <div class="mb-5">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Informacoes Basicas
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Titulo / Nome *</label>
                        <input type="text" name="title" required value="<?= htmlspecialchars($editingSample['title']) ?>"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Autor(es)</label>
                        <input type="text" name="author" value="<?= htmlspecialchars($editingSample['author'] ?? '') ?>"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>

            <!-- Section: Location -->
            <div class="mb-5">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Localizacao & Referencia
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Latitude</label>
                        <input type="text" name="latitude" value="<?= $editingSample['latitude'] ?? '' ?>" inputmode="decimal"
                            placeholder="-23.5505" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Longitude</label>
                        <input type="text" name="longitude" value="<?= $editingSample['longitude'] ?? '' ?>" inputmode="decimal"
                            placeholder="-46.6333" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Referencia</label>
                        <input type="text" name="reference_text" value="<?= htmlspecialchars($editingSample['reference_text'] ?? '') ?>"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">DOI</label>
                        <input type="text" name="doi" value="<?= htmlspecialchars($editingSample['doi'] ?? '') ?>"
                            placeholder="10.xxxx/xxxxx" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>

            <!-- Section: Custom Fields -->
            <div class="mb-5">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Campos de <?= htmlspecialchars($selectedCat['name']) ?>
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php
                    $valueMap = [];
                    if (!empty($editingSample['fields'])) { foreach ($editingSample['fields'] as $fv) { $valueMap[$fv['field_name']] = $fv; } }
                    foreach ($catFields as $field):
                        $fVal = $valueMap[$field['field_name']] ?? null;
                        $currentVal = '';
                        if ($fVal) { $currentVal = in_array($field['field_type'], ['number','decimal']) ? ($fVal['value_number'] ?? '') : ($fVal['value_text'] ?? ''); }
                    ?>
                    <div <?= $field['field_type'] === 'textarea' ? 'class="md:col-span-3"' : '' ?>>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                            <?= htmlspecialchars($field['field_label']) ?>
                            <?= $field['is_required'] ? '<span class="text-red-500">*</span>' : '' ?>
                        </label>
                        <?= renderFieldInput($field, $currentVal) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex gap-3 pt-3 border-t border-gray-200">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-lg text-sm font-semibold transition-colors shadow-sm">Salvar Alteracoes</button>
                <a href="?tab=dados&cat_id=<?= $selectedCatId ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg text-sm font-semibold transition-colors">Cancelar</a>
            </div>
        </form>
    </div>

    <?php else: ?>
    <!-- ===== INSERT FORM ===== -->
    <div class="mb-6">
        <button onclick="document.getElementById('insertForm').classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-180')"
            class="w-full flex items-center justify-between bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl px-5 py-3 hover:from-green-100 hover:to-emerald-100 transition-colors group">
            <span class="font-bold text-green-800 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nova Amostra: <?= htmlspecialchars($selectedCat['name']) ?>
            </span>
            <svg class="w-5 h-5 text-green-600 transition-transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <form method="POST" id="insertForm" class="border border-t-0 border-green-200 rounded-b-xl p-5 bg-white">
            <input type="hidden" name="dados_action" value="add">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="cat_id" value="<?= $selectedCatId ?>">

            <!-- Section: Basic Info -->
            <div class="mb-5">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Informacoes Basicas
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Titulo / Nome *</label>
                        <input type="text" name="title" required placeholder="Nome da amostra, especie ou local"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Autor(es)</label>
                        <input type="text" name="author" placeholder="Silva et al. (2024)"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>

            <!-- Section: Location -->
            <div class="mb-5">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Localizacao & Referencia
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Latitude</label>
                        <input type="text" name="latitude" inputmode="decimal" placeholder="-23.5505"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Longitude</label>
                        <input type="text" name="longitude" inputmode="decimal" placeholder="-46.6333"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Referencia</label>
                        <input type="text" name="reference_text" placeholder="Artigo, livro..."
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">DOI</label>
                        <input type="text" name="doi" placeholder="10.xxxx/xxxxx"
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>

            <!-- Section: Custom Fields -->
            <div class="mb-5">
                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Campos de <?= htmlspecialchars($selectedCat['name']) ?>
                    <span class="text-gray-300 font-normal normal-case">(<?= count($catFields) ?> campos)</span>
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php foreach ($catFields as $field): ?>
                    <div <?= $field['field_type'] === 'textarea' ? 'class="md:col-span-3"' : '' ?>>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                            <?= htmlspecialchars($field['field_label']) ?>
                            <?= $field['is_required'] ? '<span class="text-red-500">*</span>' : '' ?>
                        </label>
                        <?= renderFieldInput($field) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-8 py-2.5 rounded-lg text-sm font-semibold transition-colors shadow-sm flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Inserir Amostra
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- ===== LISTING ===== -->
    <div>
        <!-- Search bar -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-800 flex items-center gap-2">
                Amostras
                <span class="bg-gray-200 text-gray-600 rounded-full px-2.5 py-0.5 text-xs font-bold"><?= count($samples) ?></span>
            </h3>
            <div class="relative">
                <input type="text" id="sampleSearch" placeholder="Buscar titulo, autor..."
                    class="pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm w-64 focus:ring-2 focus:ring-blue-500" oninput="filterSamples(this.value)">
                <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider">Titulo</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600 text-xs uppercase tracking-wider w-20">Mapa</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase tracking-wider">Autor</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600 text-xs uppercase tracking-wider w-24">Status</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-600 text-xs uppercase tracking-wider w-24">Data</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-600 text-xs uppercase tracking-wider w-32">Acoes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100" id="samplesBody">
                    <?php foreach ($samples as $sample): ?>
                    <tr class="hover:bg-blue-50/40 transition-colors sample-row" data-search="<?= strtolower(htmlspecialchars($sample['title'] . ' ' . ($sample['author'] ?? ''))) ?>">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-900"><?= htmlspecialchars($sample['title']) ?></div>
                            <div class="text-xs text-gray-400 font-mono">#<?= $sample['id'] ?></div>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($sample['latitude'] && $sample['longitude']): ?>
                                <span class="inline-flex items-center gap-1 text-green-600 text-xs font-semibold" title="<?= $sample['latitude'] ?>, <?= $sample['longitude'] ?>">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/></svg>
                                    OK
                                </span>
                            <?php else: ?>
                                <span class="text-gray-300 text-xs">Sem coords</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs max-w-[180px] truncate"><?= htmlspecialchars($sample['author'] ?? '-') ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($sample['approved']): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Aprovado
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span> Pendente
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center text-xs text-gray-400"><?= date('d/m/Y', strtotime($sample['created_at'])) ?></td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex gap-1 justify-end">
                                <a href="?tab=dados&cat_id=<?= $selectedCatId ?>&edit_sample_id=<?= $sample['id'] ?>"
                                    class="p-1.5 rounded hover:bg-blue-100 text-gray-500 hover:text-blue-600 transition-colors" title="Editar">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                </a>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="dados_action" value="toggle_approve">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="cat_id" value="<?= $selectedCatId ?>">
                                    <input type="hidden" name="sample_id" value="<?= $sample['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $sample['approved'] ? 0 : 1 ?>">
                                    <button type="submit" class="p-1.5 rounded hover:bg-gray-200 text-gray-500 transition-colors" title="<?= $sample['approved'] ? 'Desaprovar' : 'Aprovar' ?>">
                                        <?php if($sample['approved']): ?>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878l4.242 4.242M21 21l-4.878-4.878"/></svg>
                                        <?php else: ?>
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <?php endif; ?>
                                    </button>
                                </form>
                                <form method="POST" class="inline" onsubmit="return confirm('Excluir amostra #<?= $sample['id'] ?>?')">
                                    <input type="hidden" name="dados_action" value="delete">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="cat_id" value="<?= $selectedCatId ?>">
                                    <input type="hidden" name="sample_id" value="<?= $sample['id'] ?>">
                                    <button type="submit" class="p-1.5 rounded hover:bg-red-100 text-gray-400 hover:text-red-600 transition-colors" title="Excluir">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (empty($samples)): ?>
                <div class="text-center py-12 text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    <p class="font-semibold mb-1">Nenhuma amostra</p>
                    <p class="text-sm">Use o formulario acima para inserir dados.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php elseif ($selectedCat && empty($catFields)): ?>
        <div class="text-center py-16 text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <p class="text-lg font-semibold mb-2">Categoria sem campos</p>
            <p class="text-sm mb-4">Defina os campos antes de inserir dados.</p>
            <a href="?tab=campos&cat_id=<?= $selectedCatId ?>" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Definir Campos
            </a>
        </div>
    <?php else: ?>
        <div class="text-center py-16 text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            <p class="text-lg font-semibold mb-1">Nenhuma categoria ativa</p>
            <p class="text-sm">Crie categorias na aba "Categorias".</p>
        </div>
    <?php endif; ?>
</div>

<script>
function filterSamples(query) {
    const q = query.toLowerCase().trim();
    document.querySelectorAll('.sample-row').forEach(row => {
        const text = row.dataset.search || '';
        row.style.display = !q || text.includes(q) ? '' : 'none';
    });
}
</script>
