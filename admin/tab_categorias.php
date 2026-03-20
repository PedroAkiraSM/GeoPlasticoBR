<?php
$pdo = getDatabaseConnection();

// Upload helper for category icons
function uploadCategoryIcon(array $file): string|false {
    $allowed = ['image/png', 'image/svg+xml', 'image/jpeg', 'image/webp'];
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) return false;
    $mime = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed)) return false;
    $ext = ['image/png'=>'png','image/svg+xml'=>'svg','image/jpeg'=>'jpg','image/webp'=>'webp'][$mime] ?? 'png';
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/categories/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $filename = 'cat_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) return false;
    return 'uploads/categories/' . $filename;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cat_action'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $cat_error = 'Token de seguranca invalido.';
    } else {
        $action = $_POST['cat_action'];

        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $type = $_POST['type'] ?? 'abiotico';
            $icon = $_POST['icon'] ?? 'circle';
            $color = $_POST['color'] ?? '#0ea5e9';

            if ($name === '') {
                $cat_error = 'Nome e obrigatorio.';
            } else {
                try {
                    $iconImage = null;
                    if (!empty($_FILES['icon_image']['name'])) {
                        $iconImage = uploadCategoryIcon($_FILES['icon_image']);
                        if ($iconImage === false) { $cat_error = 'Erro no upload da imagem. Use PNG ou SVG ate 2MB.'; }
                    }
                    if (!isset($cat_error)) {
                        $maxOrder = $pdo->query("SELECT COALESCE(MAX(display_order),0)+1 FROM sample_categories")->fetchColumn();
                        $stmt = $pdo->prepare("INSERT INTO sample_categories (name, type, icon, icon_image, color, display_order) VALUES (:name, :type, :icon, :img, :color, :ord)");
                        $stmt->execute([':name' => $name, ':type' => $type, ':icon' => $icon, ':img' => $iconImage, ':color' => $color, ':ord' => $maxOrder]);
                        $cat_success = "Categoria '$name' criada!";
                    }
                } catch (PDOException $e) {
                    $cat_error = 'Erro: ' . $e->getMessage();
                }
            }
        }

        if ($action === 'edit') {
            $id = (int)$_POST['cat_id'];
            $name = trim($_POST['name'] ?? '');
            $type = $_POST['type'] ?? 'abiotico';
            $icon = $_POST['icon'] ?? 'circle';
            $color = $_POST['color'] ?? '#0ea5e9';

            if ($name === '') {
                $cat_error = 'Nome e obrigatorio.';
            } else {
                $iconImage = null;
                $hasNewImage = !empty($_FILES['icon_image']['name']);
                $removeImage = isset($_POST['remove_icon_image']);
                if ($hasNewImage) {
                    $iconImage = uploadCategoryIcon($_FILES['icon_image']);
                    if ($iconImage === false) { $cat_error = 'Erro no upload da imagem. Use PNG ou SVG ate 2MB.'; }
                }
                if (!isset($cat_error)) {
                    if ($hasNewImage && $iconImage) {
                        $stmt = $pdo->prepare("UPDATE sample_categories SET name=:name, type=:type, icon=:icon, icon_image=:img, color=:color WHERE id=:id");
                        $stmt->execute([':name'=>$name, ':type'=>$type, ':icon'=>$icon, ':img'=>$iconImage, ':color'=>$color, ':id'=>$id]);
                    } elseif ($removeImage) {
                        $stmt = $pdo->prepare("UPDATE sample_categories SET name=:name, type=:type, icon=:icon, icon_image=NULL, color=:color WHERE id=:id");
                        $stmt->execute([':name'=>$name, ':type'=>$type, ':icon'=>$icon, ':color'=>$color, ':id'=>$id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE sample_categories SET name=:name, type=:type, icon=:icon, color=:color WHERE id=:id");
                        $stmt->execute([':name'=>$name, ':type'=>$type, ':icon'=>$icon, ':color'=>$color, ':id'=>$id]);
                    }
                    $cat_success = 'Categoria atualizada!';
                }
            }
        }

        if ($action === 'toggle') {
            $id = (int)$_POST['cat_id'];
            $new = (int)$_POST['new_status'];
            $pdo->prepare("UPDATE sample_categories SET is_active=:s WHERE id=:id")->execute([':s'=>$new, ':id'=>$id]);
            $cat_success = $new ? 'Categoria reativada.' : 'Categoria desativada.';
        }

        if ($action === 'delete') {
            $id = (int)$_POST['cat_id'];
            $count = $pdo->prepare("SELECT COUNT(*) FROM samples WHERE category_id=:id");
            $count->execute([':id'=>$id]);
            if ((int)$count->fetchColumn() > 0) {
                $cat_error = 'Nao pode excluir: existem amostras vinculadas. Desative.';
            } else {
                $pdo->prepare("DELETE FROM sample_categories WHERE id=:id")->execute([':id'=>$id]);
                $cat_success = 'Categoria excluida.';
            }
        }
    }
}

$allCats = getCategories(true);
$icons = ['circle'=>'Circulo','diamond'=>'Losango','square'=>'Quadrado','triangle'=>'Triangulo','star'=>'Estrela'];
$iconSvg = [
    'circle' => '<svg viewBox="0 0 24 24" class="w-5 h-5"><circle cx="12" cy="12" r="8" fill="currentColor"/></svg>',
    'diamond' => '<svg viewBox="0 0 24 24" class="w-5 h-5"><rect x="6" y="6" width="12" height="12" rx="2" transform="rotate(45 12 12)" fill="currentColor"/></svg>',
    'square' => '<svg viewBox="0 0 24 24" class="w-5 h-5"><rect x="4" y="4" width="16" height="16" rx="3" fill="currentColor"/></svg>',
    'triangle' => '<svg viewBox="0 0 24 24" class="w-5 h-5"><polygon points="12,3 22,21 2,21" fill="currentColor"/></svg>',
    'star' => '<svg viewBox="0 0 24 24" class="w-5 h-5"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26" fill="currentColor"/></svg>',
];
?>

<div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Categorias de Amostras</h2>
            <p class="text-sm text-gray-500 mt-1">Cada categoria agrupa amostras do mesmo tipo e define como aparecem no mapa.</p>
        </div>
        <button onclick="document.getElementById('addCatForm').classList.toggle('hidden')" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold flex items-center gap-2 transition-colors shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nova Categoria
        </button>
    </div>

    <?php if (isset($cat_error)): ?>
        <div class="mb-4 p-4 bg-red-50 text-red-700 border border-red-200 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <?= htmlspecialchars($cat_error) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($cat_success)): ?>
        <div class="mb-4 p-4 bg-green-50 text-green-700 border border-green-200 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <?= htmlspecialchars($cat_success) ?>
        </div>
    <?php endif; ?>

    <!-- Add form (hidden by default) -->
    <form method="POST" id="addCatForm" class="hidden mb-6 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-5" enctype="multipart/form-data">
        <input type="hidden" name="cat_action" value="add">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <h3 class="text-sm font-bold text-green-800 mb-4 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Criar Nova Categoria
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Nome da Categoria *</label>
                <input type="text" name="name" required placeholder="Ex: Planta, Solo, Invertebrado..."
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm transition-shadow">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Classificacao</label>
                <select name="type" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500">
                    <option value="abiotico">Abiotico (nao-vivo)</option>
                    <option value="biotico">Biotico (vivo)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Forma no Mapa</label>
                <div class="flex gap-1.5">
                    <?php foreach ($icons as $k=>$v): ?>
                    <label class="flex-1 cursor-pointer">
                        <input type="radio" name="icon" value="<?=$k?>" <?=$k==='circle'?'checked':''?> class="sr-only peer">
                        <div class="flex items-center justify-center p-2 rounded-lg border-2 border-gray-200 peer-checked:border-green-500 peer-checked:bg-green-50 hover:bg-gray-50 transition-all text-gray-400 peer-checked:text-green-600" title="<?=$v?>">
                            <?= $iconSvg[$k] ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Cor</label>
                    <input type="color" name="color" value="#0ea5e9" class="w-full h-[42px] rounded-lg border border-gray-300 cursor-pointer">
                </div>
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg text-sm font-semibold transition-colors whitespace-nowrap">
                    Criar
                </button>
            </div>
        </div>
        <div class="mt-4">
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Icone Personalizado (PNG/SVG, opcional)</label>
            <input type="file" name="icon_image" accept="image/png,image/svg+xml,image/jpeg,image/webp"
                class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
            <p class="text-xs text-gray-400 mt-1">Ate 2MB. Sera usado no lugar da forma geometrica no mapa.</p>
        </div>
    </form>

    <!-- Category Cards -->
    <?php if (empty($allCats)): ?>
        <div class="text-center py-16 text-gray-400">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            <p class="text-lg font-semibold mb-1">Nenhuma categoria criada</p>
            <p class="text-sm">Clique em "Nova Categoria" para comecar.</p>
        </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($allCats as $cat):
            $fieldCount = $pdo->prepare("SELECT COUNT(*) FROM category_fields WHERE category_id=:id");
            $fieldCount->execute([':id'=>$cat['id']]);
            $fCount = (int)$fieldCount->fetchColumn();

            $sampleCount = $pdo->prepare("SELECT COUNT(*) FROM samples WHERE category_id=:id");
            $sampleCount->execute([':id'=>$cat['id']]);
            $sCount = (int)$sampleCount->fetchColumn();
        ?>
        <div class="relative group rounded-xl border-2 <?= $cat['is_active'] ? 'border-gray-200 hover:border-gray-300' : 'border-dashed border-gray-200 opacity-60' ?> bg-white transition-all hover:shadow-md overflow-hidden">
            <!-- Color bar -->
            <div class="h-1.5" style="background:<?= htmlspecialchars($cat['color']) ?>"></div>

            <div class="p-5">
                <!-- Header -->
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:<?= htmlspecialchars($cat['color']) ?>20; color:<?= htmlspecialchars($cat['color']) ?>">
                            <?php if (!empty($cat['icon_image'])): ?>
                                <img src="/<?= htmlspecialchars($cat['icon_image']) ?>" alt="" class="w-6 h-6 object-contain">
                            <?php else: ?>
                                <?= $iconSvg[$cat['icon']] ?? $iconSvg['circle'] ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-lg leading-tight"><?= htmlspecialchars($cat['name']) ?></h3>
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold mt-0.5 <?= $cat['type']==='biotico' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' ?>">
                                <?= $cat['type']==='biotico' ? 'Biotico' : 'Abiotico' ?>
                            </span>
                        </div>
                    </div>
                    <?php if (!$cat['is_active']): ?>
                        <span class="px-2 py-1 rounded text-xs font-bold bg-red-100 text-red-600">Inativo</span>
                    <?php endif; ?>
                </div>

                <!-- Stats -->
                <div class="flex gap-4 mb-4">
                    <div class="flex-1 bg-gray-50 rounded-lg p-3 text-center">
                        <div class="text-xl font-bold text-gray-800"><?= $fCount ?></div>
                        <div class="text-xs text-gray-500 mt-0.5">Campos</div>
                    </div>
                    <div class="flex-1 bg-gray-50 rounded-lg p-3 text-center">
                        <div class="text-xl font-bold text-gray-800"><?= $sCount ?></div>
                        <div class="text-xs text-gray-500 mt-0.5">Amostras</div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <a href="?tab=campos&cat_id=<?=$cat['id']?>" class="flex-1 text-center bg-blue-50 hover:bg-blue-100 text-blue-700 px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                        Campos
                    </a>
                    <a href="?tab=dados&cat_id=<?=$cat['id']?>" class="flex-1 text-center bg-gray-50 hover:bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                        Dados
                    </a>
                    <button onclick="document.getElementById('editcat-<?=$cat['id']?>').classList.toggle('hidden')"
                        class="bg-gray-50 hover:bg-gray-100 text-gray-600 px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    <form method="POST" class="inline">
                        <input type="hidden" name="cat_action" value="toggle">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="cat_id" value="<?=$cat['id']?>">
                        <input type="hidden" name="new_status" value="<?=$cat['is_active']?0:1?>">
                        <button type="submit" class="bg-gray-50 hover:bg-gray-100 text-gray-600 px-3 py-2 rounded-lg text-xs font-semibold transition-colors" title="<?=$cat['is_active']?'Desativar':'Reativar'?>">
                            <?php if($cat['is_active']): ?>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878l4.242 4.242M21 21l-4.878-4.878"/></svg>
                            <?php else: ?>
                                <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <?php endif; ?>
                        </button>
                    </form>
                    <?php if ($sCount === 0): ?>
                    <form method="POST" class="inline" onsubmit="return confirm('Excluir categoria <?=htmlspecialchars(addslashes($cat['name']))?>?')">
                        <input type="hidden" name="cat_action" value="delete">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="cat_id" value="<?=$cat['id']?>">
                        <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 px-3 py-2 rounded-lg text-xs font-semibold transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Edit panel (hidden) -->
            <div id="editcat-<?=$cat['id']?>" class="hidden border-t border-gray-200 bg-amber-50 p-5">
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
                    <input type="hidden" name="cat_action" value="edit">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="cat_id" value="<?=$cat['id']?>">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Nome</label>
                        <input type="text" name="name" value="<?=htmlspecialchars($cat['name'])?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo</label>
                        <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="abiotico" <?=$cat['type']==='abiotico'?'selected':''?>>Abiotico</option>
                            <option value="biotico" <?=$cat['type']==='biotico'?'selected':''?>>Biotico</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Icone / Cor</label>
                        <div class="flex gap-2">
                            <select name="icon" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <?php foreach($icons as $k=>$v): ?>
                                <option value="<?=$k?>" <?=$cat['icon']===$k?'selected':''?>><?=$v?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="color" name="color" value="<?=htmlspecialchars($cat['color'])?>" class="w-10 h-[38px] rounded-lg border border-gray-300 cursor-pointer">
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors">Salvar</button>
                    </div>
                    </div>
                    <div class="mt-3 flex items-center gap-3">
                        <?php if (!empty($cat['icon_image'])): ?>
                            <img src="/<?= htmlspecialchars($cat['icon_image']) ?>" alt="Icone" class="w-8 h-8 object-contain rounded">
                            <label class="flex items-center gap-1 text-xs text-red-500 cursor-pointer"><input type="checkbox" name="remove_icon_image" class="rounded"> Remover</label>
                        <?php endif; ?>
                        <div class="flex-1">
                            <input type="file" name="icon_image" accept="image/png,image/svg+xml,image/jpeg,image/webp"
                                class="w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
