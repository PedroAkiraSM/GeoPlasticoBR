<?php
$pdo = getDatabaseConnection();
$allCats = getCategories();

function uploadSpeciesImage(array $file): string|false {
    $allowed = ['image/png', 'image/jpeg', 'image/webp'];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) return false;
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed)) return false;
    $ext = ['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'][$mime] ?? 'png';
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/species/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $filename = 'sp_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) return false;
    return 'uploads/species/' . $filename;
}

$speciesFilter = (int)($_GET['species_cat'] ?? $_POST['species_cat'] ?? 0);
if ($speciesFilter === 0 && !empty($allCats)) {
    foreach ($allCats as $c) {
        if ($c['type'] === 'biotico') { $speciesFilter = $c['id']; break; }
    }
    if ($speciesFilter === 0) $speciesFilter = $allCats[0]['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['species_action'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $sp_error = 'Token de seguranca invalido.';
    } else {
        $action = $_POST['species_action'];

        if ($action === 'add') {
            $name = trim($_POST['sp_name'] ?? '');
            $sciName = trim($_POST['sp_scientific_name'] ?? '');
            $catId = (int)($_POST['sp_category_id'] ?? 0);
            if ($name === '' || $catId === 0) {
                $sp_error = 'Nome e categoria sao obrigatorios.';
            } else {
                $imagePath = null;
                if (!empty($_FILES['sp_image']['name'])) {
                    $imagePath = uploadSpeciesImage($_FILES['sp_image']);
                    if ($imagePath === false) { $sp_error = 'Erro no upload. Use PNG/JPG/WebP ate 2MB.'; }
                }
                if (!isset($sp_error)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO species (category_id, name, scientific_name, image_path) VALUES (:cid, :name, :sci, :img)");
                        $stmt->execute([':cid' => $catId, ':name' => $name, ':sci' => $sciName ?: null, ':img' => $imagePath]);
                        $sp_success = "Especie '$name' cadastrada!";
                    } catch (PDOException $e) { $sp_error = 'Erro: ' . $e->getMessage(); }
                }
            }
        }

        if ($action === 'edit') {
            $spId = (int)($_POST['sp_id'] ?? 0);
            $name = trim($_POST['sp_name'] ?? '');
            $sciName = trim($_POST['sp_scientific_name'] ?? '');
            $catId = (int)($_POST['sp_category_id'] ?? 0);
            if ($spId === 0 || $name === '') {
                $sp_error = 'Dados invalidos.';
            } else {
                $imagePath = null;
                if (!empty($_FILES['sp_image']['name'])) {
                    $imagePath = uploadSpeciesImage($_FILES['sp_image']);
                    if ($imagePath === false) { $sp_error = 'Erro no upload da imagem.'; }
                }
                if (!isset($sp_error)) {
                    try {
                        if ($imagePath) {
                            $stmt = $pdo->prepare("UPDATE species SET name=:name, scientific_name=:sci, category_id=:cid, image_path=:img WHERE id=:id");
                            $stmt->execute([':name'=>$name, ':sci'=>$sciName?:null, ':cid'=>$catId, ':img'=>$imagePath, ':id'=>$spId]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE species SET name=:name, scientific_name=:sci, category_id=:cid WHERE id=:id");
                            $stmt->execute([':name'=>$name, ':sci'=>$sciName?:null, ':cid'=>$catId, ':id'=>$spId]);
                        }
                        $sp_success = "Especie atualizada!";
                    } catch (PDOException $e) { $sp_error = 'Erro: ' . $e->getMessage(); }
                }
            }
        }

        if ($action === 'delete') {
            $spId = (int)($_POST['sp_id'] ?? 0);
            if ($spId > 0) {
                try {
                    $pdo->prepare("DELETE FROM species WHERE id = :id")->execute([':id' => $spId]);
                    $sp_success = "Especie removida.";
                } catch (PDOException $e) { $sp_error = 'Erro: ' . $e->getMessage(); }
            }
        }

        if ($action === 'toggle') {
            $spId = (int)($_POST['sp_id'] ?? 0);
            if ($spId > 0) {
                $pdo->prepare("UPDATE species SET is_active = NOT is_active WHERE id = :id")->execute([':id' => $spId]);
                $sp_success = "Status alterado.";
            }
        }
    }
}

$speciesList = [];
if ($speciesFilter > 0) {
    $stmt = $pdo->prepare("SELECT * FROM species WHERE category_id = :cid ORDER BY name");
    $stmt->execute([':cid' => $speciesFilter]);
    $speciesList = $stmt->fetchAll();
}
?>

<div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
    <div class="flex items-center justify-between mb-2">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Especies</h2>
            <p class="text-sm text-gray-500 mt-1">Cadastre especies por categoria com nome cientifico e foto.</p>
        </div>
    </div>

    <?php if (isset($sp_error)): ?>
        <div class="mb-4 p-4 bg-red-50 text-red-700 border border-red-200 rounded-lg"><?= htmlspecialchars($sp_error) ?></div>
    <?php endif; ?>
    <?php if (isset($sp_success)): ?>
        <div class="mb-4 p-4 bg-green-50 text-green-700 border border-green-200 rounded-lg"><?= htmlspecialchars($sp_success) ?></div>
    <?php endif; ?>

    <!-- Category Filter (biotic only) -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 pb-4">
        <?php foreach ($allCats as $cat):
            if (!$cat['is_active'] || $cat['type'] !== 'biotico') continue;
            $spCount = $pdo->prepare("SELECT COUNT(*) FROM species WHERE category_id=:cid");
            $spCount->execute([':cid'=>$cat['id']]);
            $cnt = (int)$spCount->fetchColumn();
        ?>
            <a href="?tab=especies&species_cat=<?= $cat['id'] ?>"
               class="px-4 py-2.5 rounded-lg text-sm font-semibold border-2 transition-all flex items-center gap-2 <?= $cat['id'] === $speciesFilter ? 'border-blue-500 bg-blue-50 text-blue-700 shadow-sm' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50' ?>">
                <span class="inline-block w-3 h-3 rounded-full" style="background:<?= htmlspecialchars($cat['color']) ?>"></span>
                <?= htmlspecialchars($cat['name']) ?>
                <span class="bg-gray-200 text-gray-600 rounded-full px-2 py-0.5 text-xs font-bold"><?= $cnt ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Add Form -->
    <div class="mb-6">
        <button onclick="document.getElementById('addSpeciesForm').classList.toggle('hidden')"
            class="w-full flex items-center justify-between bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl px-5 py-3 hover:from-green-100 hover:to-emerald-100 transition-colors">
            <span class="font-bold text-green-800 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nova Especie
            </span>
        </button>
        <form method="POST" enctype="multipart/form-data" id="addSpeciesForm" class="hidden mt-3 p-5 bg-gray-50 rounded-xl border border-gray-200">
            <input type="hidden" name="species_action" value="add">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="species_cat" value="<?= $speciesFilter ?>">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nome *</label>
                    <input type="text" name="sp_name" required placeholder="Ex: Tilapia" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nome Cientifico</label>
                    <input type="text" name="sp_scientific_name" placeholder="Ex: Oreochromis niloticus" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm italic">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Categoria *</label>
                    <select name="sp_category_id" required class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                        <?php foreach ($allCats as $c): if ($c['type'] !== 'biotico') continue; ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] === $speciesFilter ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Foto (PNG/JPG/WebP, max 2MB)</label>
                <input type="file" name="sp_image" accept="image/png,image/jpeg,image/webp" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2.5 rounded-lg text-sm font-semibold">Cadastrar</button>
        </form>
    </div>

    <!-- Species List -->
    <?php if (empty($speciesList)): ?>
        <p class="text-gray-400 text-center py-8">Nenhuma especie cadastrada nesta categoria.</p>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-2 text-gray-500 font-semibold">Foto</th>
                    <th class="text-left py-3 px-2 text-gray-500 font-semibold">Nome</th>
                    <th class="text-left py-3 px-2 text-gray-500 font-semibold">Nome Cientifico</th>
                    <th class="text-left py-3 px-2 text-gray-500 font-semibold">Status</th>
                    <th class="text-right py-3 px-2 text-gray-500 font-semibold">Acoes</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($speciesList as $sp): ?>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-2 px-2">
                        <?php if ($sp['image_path']): ?>
                        <img src="/<?= htmlspecialchars($sp['image_path']) ?>" alt="" class="w-16 h-12 object-cover rounded-lg border border-gray-200">
                        <?php else: ?>
                        <div class="w-16 h-12 bg-gray-100 rounded-lg flex items-center justify-center text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="py-2 px-2 font-semibold text-gray-800"><?= htmlspecialchars($sp['name']) ?></td>
                    <td class="py-2 px-2 text-gray-500 italic"><?= htmlspecialchars($sp['scientific_name'] ?? '—') ?></td>
                    <td class="py-2 px-2">
                        <form method="POST" class="inline">
                            <input type="hidden" name="species_action" value="toggle">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="sp_id" value="<?= $sp['id'] ?>">
                            <input type="hidden" name="species_cat" value="<?= $speciesFilter ?>">
                            <button type="submit" class="px-2 py-1 rounded-full text-xs font-bold <?= $sp['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                                <?= $sp['is_active'] ? 'Ativo' : 'Inativo' ?>
                            </button>
                        </form>
                    </td>
                    <td class="py-2 px-2 text-right flex gap-2 justify-end">
                        <button type="button" onclick="toggleEditSpecies(<?= $sp['id'] ?>)" class="text-blue-500 hover:text-blue-700 text-xs font-semibold">Editar</button>
                        <form method="POST" class="inline" onsubmit="return confirm('Remover esta especie?')">
                            <input type="hidden" name="species_action" value="delete">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="sp_id" value="<?= $sp['id'] ?>">
                            <input type="hidden" name="species_cat" value="<?= $speciesFilter ?>">
                            <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-semibold">Remover</button>
                        </form>
                    </td>
                </tr>
                <!-- Inline edit row -->
                <tr id="editRow_<?= $sp['id'] ?>" class="hidden bg-blue-50">
                    <td colspan="5" class="p-3">
                        <form method="POST" enctype="multipart/form-data" class="flex flex-wrap items-end gap-3">
                            <input type="hidden" name="species_action" value="edit">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="sp_id" value="<?= $sp['id'] ?>">
                            <input type="hidden" name="species_cat" value="<?= $speciesFilter ?>">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Nome</label>
                                <input type="text" name="sp_name" required value="<?= htmlspecialchars($sp['name']) ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-40">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Cientifico</label>
                                <input type="text" name="sp_scientific_name" value="<?= htmlspecialchars($sp['scientific_name'] ?? '') ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm w-48 italic">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Categoria</label>
                                <select name="sp_category_id" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <?php foreach ($allCats as $c): if ($c['type'] !== 'biotico') continue; ?>
                                    <option value="<?= $c['id'] ?>" <?= $c['id'] === (int)$sp['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Nova foto</label>
                                <input type="file" name="sp_image" accept="image/png,image/jpeg,image/webp" class="text-xs w-40">
                            </div>
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">Salvar</button>
                            <button type="button" onclick="toggleEditSpecies(<?= $sp['id'] ?>)" class="text-gray-500 text-sm">Cancelar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
<script>
function toggleEditSpecies(id) {
    var row = document.getElementById('editRow_' + id);
    if (row) row.classList.toggle('hidden');
}
</script>
