<?php
require_once __DIR__ . '/auth.php';
requireLogin();

// Apenas admin pode acessar
if (!isAdmin()) {
    header('Location: /');
    exit;
}

require_once 'config/database.php';
require_once __DIR__ . '/config/cms.php';
$pdo = getDatabaseConnection();
$message = '';
$messageType = '';

// ========================================
// Processar acoes POST
// ========================================

// Aprovar usuario
if (isset($_POST['approve_user'])) {
    $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = :id");
    $stmt->execute([':id' => $_POST['user_id']]);
    $message = 'Usuario aprovado com sucesso!';
    $messageType = 'success';
}

// Rejeitar usuario
if (isset($_POST['reject_user'])) {
    $stmt = $pdo->prepare("UPDATE users SET status = 'rejected' WHERE id = :id");
    $stmt->execute([':id' => $_POST['user_id']]);
    $message = 'Usuario rejeitado.';
    $messageType = 'success';
}

// Alterar role
if (isset($_POST['change_role'])) {
    $new_role = $_POST['new_role'];
    if (in_array($new_role, ['user', 'scientist', 'admin'])) {
        $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
        $stmt->execute([':role' => $new_role, ':id' => $_POST['user_id']]);
        $message = 'Permissao do usuario atualizada!';
        $messageType = 'success';
    }
}

// Aprovar dado pendente
if (isset($_POST['approve_data'])) {
    $stmt = $pdo->prepare("UPDATE microplastics_sediment SET approved = 1 WHERE id = :id");
    $stmt->execute([':id' => $_POST['data_id']]);
    $message = 'Dado aprovado e publicado no mapa!';
    $messageType = 'success';
}

// Rejeitar dado pendente
if (isset($_POST['reject_data'])) {
    $stmt = $pdo->prepare("DELETE FROM microplastics_sediment WHERE id = :id AND approved = 0");
    $stmt->execute([':id' => $_POST['data_id']]);
    $message = 'Dado rejeitado e removido.';
    $messageType = 'success';
}

// Adicionar sedimento
if (isset($_POST['add_sediment']) && validateCsrfToken($_POST['csrf_token'] ?? '')) {
    try {
        $stmt = $pdo->prepare("INSERT INTO microplastics_sediment
            (tipo_ambiente, ecossistema, system, sampling_point, latitude, longitude,
             concentration_sediment, concentration_value, matriz, unidade,
             total_concentration, concentration_variation, depth, author, reference, doi, approved)
            VALUES (:tipo_ambiente, :ecossistema, :system, :sampling_point, :latitude, :longitude,
                    :concentration_sediment, :concentration_value, :matriz, :unidade,
                    :total_concentration, :concentration_variation, :depth, :author, :reference, :doi, 1)");

        $stmt->execute([
            ':tipo_ambiente' => $_POST['tipo_ambiente'] ?: null,
            ':ecossistema' => $_POST['ecossistema'] ?: null,
            ':system' => $_POST['system'],
            ':sampling_point' => $_POST['sampling_point'] ?: null,
            ':latitude' => $_POST['latitude'] ?: null,
            ':longitude' => $_POST['longitude'] ?: null,
            ':concentration_sediment' => $_POST['concentration_sediment'] ?: null,
            ':concentration_value' => $_POST['concentration_value'] ?: 0,
            ':matriz' => $_POST['matriz'] ?: null,
            ':unidade' => $_POST['unidade'] ?: null,
            ':total_concentration' => $_POST['total_concentration'] ?: null,
            ':concentration_variation' => $_POST['concentration_variation'] ?: null,
            ':depth' => $_POST['depth'] ?: null,
            ':author' => $_POST['author'] ?: null,
            ':reference' => $_POST['reference'] ?: null,
            ':doi' => $_POST['doi'] ?: null,
        ]);

        $message = 'Dados adicionados com sucesso!';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Erro ao adicionar dados: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Adicionar peixe
if (isset($_POST['add_fish']) && validateCsrfToken($_POST['csrf_token'] ?? '')) {
    try {
        $stmt = $pdo->prepare("INSERT INTO microplastics_fish
            (species, habit, total_individuals, individuals_with_microplastics, fiber, film, fragment,
             foam, pellets, sphere, plastic_dimension, occurrence_tissues, freshwater_system, latitude, longitude, author, reference, doi)
            VALUES (:species, :habit, :total_individuals, :individuals_with_microplastics, :fiber, :film,
                    :fragment, :foam, :pellets, :sphere, :plastic_dimension, :occurrence_tissues,
                    :freshwater_system, :latitude, :longitude, :author, :reference, :doi)");

        $stmt->execute([
            ':species' => $_POST['species'],
            ':habit' => $_POST['habit'] ?: null,
            ':total_individuals' => $_POST['total_individuals'] ?: 0,
            ':individuals_with_microplastics' => $_POST['individuals_with_microplastics'] ?: 0,
            ':fiber' => isset($_POST['fiber']) ? 1 : 0,
            ':film' => isset($_POST['film']) ? 1 : 0,
            ':fragment' => isset($_POST['fragment']) ? 1 : 0,
            ':foam' => isset($_POST['foam']) ? 1 : 0,
            ':pellets' => isset($_POST['pellets']) ? 1 : 0,
            ':sphere' => isset($_POST['sphere']) ? 1 : 0,
            ':plastic_dimension' => $_POST['plastic_dimension'] ?: null,
            ':occurrence_tissues' => $_POST['occurrence_tissues'] ?: null,
            ':freshwater_system' => $_POST['freshwater_system'] ?: null,
            ':latitude' => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
            ':longitude' => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null,
            ':author' => $_POST['author'] ?: null,
            ':reference' => $_POST['reference'] ?: null,
            ':doi' => $_POST['doi'] ?: null,
        ]);

        $message = 'Dados de peixe adicionados com sucesso!';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Erro: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Atualizar coordenadas de peixe existente
if (isset($_POST['update_fish_coords']) && validateCsrfToken($_POST['csrf_token'] ?? '')) {
    try {
        $fishId = (int)$_POST['fish_id'];
        $lat = !empty($_POST['fish_lat']) ? (float)$_POST['fish_lat'] : null;
        $lng = !empty($_POST['fish_lng']) ? (float)$_POST['fish_lng'] : null;

        $stmt = $pdo->prepare("UPDATE microplastics_fish SET latitude = :lat, longitude = :lng WHERE id = :id");
        $stmt->execute([':lat' => $lat, ':lng' => $lng, ':id' => $fishId]);

        $message = 'Coordenadas atualizadas com sucesso!';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Erro: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Atualizar coordenadas em lote
if (isset($_POST['bulk_update_fish_coords']) && validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $updated = 0;
    $ids = $_POST['bulk_fish_id'] ?? [];
    $lats = $_POST['bulk_fish_lat'] ?? [];
    $lngs = $_POST['bulk_fish_lng'] ?? [];

    $stmt = $pdo->prepare("UPDATE microplastics_fish SET latitude = :lat, longitude = :lng WHERE id = :id");

    for ($i = 0; $i < count($ids); $i++) {
        $lat = !empty($lats[$i]) ? (float)$lats[$i] : null;
        $lng = !empty($lngs[$i]) ? (float)$lngs[$i] : null;
        if ($lat !== null && $lng !== null) {
            $stmt->execute([':lat' => $lat, ':lng' => $lng, ':id' => (int)$ids[$i]]);
            $updated++;
        }
    }

    $message = "$updated coordenadas atualizadas com sucesso!";
    $messageType = 'success';
}

// ========================================
// Buscar dados para exibicao
// ========================================
$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();
$approved_users = $pdo->query("SELECT * FROM users WHERE status = 'approved' ORDER BY nome")->fetchAll();
$pending_data = $pdo->query("SELECT ms.*, u.nome as submitter_name FROM microplastics_sediment ms LEFT JOIN users u ON ms.submitted_by = u.id WHERE ms.approved = 0 ORDER BY ms.created_at DESC")->fetchAll();
$fish_no_coords = $pdo->query("SELECT id, species, freshwater_system, latitude, longitude FROM microplastics_fish WHERE latitude IS NULL OR longitude IS NULL ORDER BY freshwater_system, species")->fetchAll();
$fish_with_coords = $pdo->query("SELECT id, species, freshwater_system, latitude, longitude FROM microplastics_fish WHERE latitude IS NOT NULL AND longitude IS NOT NULL ORDER BY freshwater_system, species")->fetchAll();
$fish_total = $pdo->query("SELECT COUNT(*) FROM microplastics_fish")->fetchColumn();

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - GeoPlasticoBR</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Header -->
    <div class="bg-gradient-to-r from-slate-800 to-slate-900 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-cyan-400">GeoPlasticoBR</h1>
                <p class="text-sm text-gray-400">Painel de Administracao</p>
            </div>
            <div class="flex gap-4 items-center">
                <span class="text-sm text-gray-300"><?php echo htmlspecialchars($user['nome']); ?></span>
                <a href="/mapa.php" class="text-cyan-400 hover:text-cyan-300 text-sm font-medium">Mapa</a>
                <a href="/" class="text-gray-300 hover:text-white text-sm font-medium">Inicio</a>
                <a href="/login.php?logout=1" class="text-red-400 hover:text-red-300 text-sm font-medium">Sair</a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="mb-8">
            <nav class="flex space-x-1 bg-gray-100 rounded-lg p-1">
                <button onclick="showTab('users')" id="tab-users"
                        class="tab-btn flex-1 py-3 px-4 rounded-md text-sm font-semibold bg-white shadow text-slate-800">
                    Usuarios <?php if (count($pending_users) > 0): ?><span class="bg-red-500 text-white rounded-full px-2 py-0.5 text-xs ml-1"><?php echo count($pending_users); ?></span><?php endif; ?>
                </button>
                <button onclick="showTab('pending_data')" id="tab-pending_data"
                        class="tab-btn flex-1 py-3 px-4 rounded-md text-sm font-semibold text-gray-500 hover:text-gray-700">
                    Dados Pendentes <?php if (count($pending_data) > 0): ?><span class="bg-orange-500 text-white rounded-full px-2 py-0.5 text-xs ml-1"><?php echo count($pending_data); ?></span><?php endif; ?>
                </button>
                <button onclick="showTab('sediment')" id="tab-sediment"
                        class="tab-btn flex-1 py-3 px-4 rounded-md text-sm font-semibold text-gray-500 hover:text-gray-700">
                    + Sedimento
                </button>
                <button onclick="showTab('fish')" id="tab-fish"
                        class="tab-btn flex-1 py-3 px-4 rounded-md text-sm font-semibold text-gray-500 hover:text-gray-700">
                    + Peixes
                </button>
                <button onclick="showTab('configuracoes')" id="tab-configuracoes"
                        class="tab-btn flex-1 py-3 px-4 rounded-md text-sm font-semibold text-gray-500 hover:text-gray-700">
                    Configuracoes
                </button>
                <button onclick="showTab('blocos')" id="tab-blocos"
                        class="tab-btn flex-1 py-3 px-4 rounded-md text-sm font-semibold text-gray-500 hover:text-gray-700">
                    Blocos
                </button>
                <button onclick="showTab('tipos')" id="tab-tipos"
                        class="tab-btn flex-1 py-3 px-4 rounded-md text-sm font-semibold text-gray-500 hover:text-gray-700">
                    Tipos
                </button>
                <button onclick="showTab('unidades')" id="tab-unidades"
                        class="tab-btn flex-1 py-3 px-4 rounded-md text-sm font-semibold text-gray-500 hover:text-gray-700">
                    Unidades
                </button>
            </nav>
        </div>

        <!-- ========== TAB: USUARIOS ========== -->
        <div id="form-users" class="tab-content">
            <!-- Usuarios pendentes -->
            <?php if (count($pending_users) > 0): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Usuarios Pendentes de Aprovacao</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Nome</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Email</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tipo</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Instituicao</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Data</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Acoes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($pending_users as $u): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($u['nome']); ?></td>
                                <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($u['email']); ?></td>
                                <td class="px-4 py-3"><span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs"><?php echo htmlspecialchars($u['tipo_usuario']); ?></span></td>
                                <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($u['instituicao'] ?? '-'); ?></td>
                                <td class="px-4 py-3 text-gray-500"><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button name="approve_user" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded text-xs font-semibold">Aprovar</button>
                                        </form>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button name="reject_user" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs font-semibold">Rejeitar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Usuarios aprovados -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Usuarios Aprovados</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Nome</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Email</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tipo</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Permissao</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Alterar Permissao</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($approved_users as $u): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium"><?php echo htmlspecialchars($u['nome']); ?></td>
                                <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($u['email']); ?></td>
                                <td class="px-4 py-3"><span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs"><?php echo htmlspecialchars($u['tipo_usuario']); ?></span></td>
                                <td class="px-4 py-3">
                                    <?php
                                    $roleBadge = match($u['role']) {
                                        'admin' => 'bg-purple-100 text-purple-700',
                                        'scientist' => 'bg-green-100 text-green-700',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                    ?>
                                    <span class="<?php echo $roleBadge; ?> px-2 py-1 rounded text-xs font-semibold"><?php echo htmlspecialchars($u['role']); ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <form method="POST" class="flex gap-2 items-center">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <select name="new_role" class="text-xs border rounded px-2 py-1.5">
                                            <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>Usuario</option>
                                            <option value="scientist" <?php echo $u['role'] === 'scientist' ? 'selected' : ''; ?>>Cientista</option>
                                            <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                        <button name="change_role" class="bg-slate-700 hover:bg-slate-800 text-white px-3 py-1.5 rounded text-xs font-semibold">Salvar</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ========== TAB: DADOS PENDENTES ========== -->
        <div id="form-pending_data" class="tab-content hidden">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Dados Pendentes de Aprovacao</h2>
                <?php if (count($pending_data) === 0): ?>
                <p class="text-gray-500">Nenhum dado pendente de aprovacao.</p>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($pending_data as $d): ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($d['system']); ?> - <?php echo htmlspecialchars($d['sampling_point'] ?? ''); ?></h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    <span class="bg-gray-100 px-2 py-0.5 rounded"><?php echo htmlspecialchars($d['tipo_ambiente'] ?? ''); ?></span>
                                    <span class="bg-gray-100 px-2 py-0.5 rounded ml-1"><?php echo htmlspecialchars($d['ecossistema'] ?? ''); ?></span>
                                    <span class="bg-gray-100 px-2 py-0.5 rounded ml-1"><?php echo htmlspecialchars($d['matriz'] ?? ''); ?></span>
                                </p>
                                <p class="text-sm text-gray-500 mt-1">Concentracao: <?php echo htmlspecialchars($d['concentration_sediment'] ?? '-'); ?> | Coords: <?php echo ($d['latitude'] ?? '-') . ', ' . ($d['longitude'] ?? '-'); ?></p>
                                <p class="text-sm text-gray-500">Autor: <?php echo htmlspecialchars($d['author'] ?? '-'); ?></p>
                                <?php if ($d['submitter_name']): ?>
                                <p class="text-xs text-gray-400 mt-1">Enviado por: <?php echo htmlspecialchars($d['submitter_name']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="data_id" value="<?php echo $d['id']; ?>">
                                    <button name="approve_data" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded text-sm font-semibold">Aprovar</button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                    <input type="hidden" name="data_id" value="<?php echo $d['id']; ?>">
                                    <button name="reject_data" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded text-sm font-semibold">Rejeitar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ========== TAB: SEDIMENTO ========== -->
        <div id="form-sediment" class="tab-content hidden">
            <div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Adicionar Dados de Microplasticos</h2>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Ambiente <span class="text-red-500">*</span></label>
                            <select name="tipo_ambiente" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione...</option>
                                <?php foreach (getDataTypes('ambiente') as $a): ?>
                                <option value="<?= htmlspecialchars($a['name']) ?>"><?= htmlspecialchars($a['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ecossistema</label>
                            <select name="ecossistema" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione...</option>
                                <?php foreach (getDataTypes('ecossistema') as $e): ?>
                                <option value="<?= htmlspecialchars($e['name']) ?>"><?= htmlspecialchars($e['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Matriz</label>
                            <select name="matriz" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione...</option>
                                <?php foreach (getDataTypes('matriz') as $m): ?>
                                <option value="<?= htmlspecialchars($m['name']) ?>"><?= htmlspecialchars($m['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Sistema Aquatico <span class="text-red-500">*</span></label>
                            <input type="text" name="system" required placeholder="Ex: Amazon River, Santos beach" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Ponto de Amostragem</label>
                            <input type="text" name="sampling_point" placeholder="Ex: AMZ1, P1" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Latitude</label>
                            <input type="number" step="0.000001" name="latitude" placeholder="Ex: -3.146601" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Longitude</label>
                            <input type="number" step="0.000001" name="longitude" placeholder="Ex: -59.383157" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Concentracao (texto)</label>
                            <input type="text" name="concentration_sediment" placeholder="Ex: 2101/Kg" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Valor Numerico</label>
                            <input type="number" step="0.01" name="concentration_value" placeholder="Ex: 2101" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Unidade</label>
                            <select name="unidade" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione...</option>
                                <?php foreach (getUnitsWithThresholds() as $u): ?>
                                <option value="<?= htmlspecialchars($u['name']) ?>"><?= htmlspecialchars($u['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Variacao</label>
                            <select name="concentration_variation" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione...</option>
                                <option value="Space">Espacial</option>
                                <option value="Time">Temporal</option>
                                <option value="SPACE + TIME">Espacial + Temporal</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Concentracao Total</label>
                            <input type="text" name="total_concentration" placeholder="Ex: 417/Kg - 2.101/Kg" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Profundidade (m)</label>
                            <input type="text" name="depth" placeholder="Ex: 5, 0.05" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Autor(es)</label>
                            <input type="text" name="author" placeholder="Ex: Gerolin, C. et al. (2020)" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Referencia</label>
                            <input type="text" name="reference" placeholder="Titulo do artigo" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">DOI</label>
                            <input type="text" name="doi" placeholder="Ex: https://doi.org/10.1016/j.scitotenv.2020.139484" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <button type="submit" name="add_sediment" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition">Adicionar Dados</button>
                </form>
            </div>
        </div>

        <!-- ========== TAB: PEIXES ========== -->
        <div id="form-fish" class="tab-content hidden">
            <div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Adicionar Dados de Peixes</h2>
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Especie <span class="text-red-500">*</span></label>
                            <input type="text" name="species" required placeholder="Ex: Astyanax lacustris" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Habito Alimentar</label>
                            <select name="habit" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                <option value="">Selecione...</option>
                                <option value="Omnivore">Onivoro</option>
                                <option value="Carnivore">Carnivoro</option>
                                <option value="Herbivore">Herbivoro</option>
                                <option value="Insectivore">Insetivoro</option>
                                <option value="Detritivore">Detritivoro</option>
                                <option value="Piscivore">Piscivoro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Total Individuos</label>
                            <input type="number" name="total_individuals" placeholder="132" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Individuos c/ Microplasticos</label>
                            <input type="number" name="individuals_with_microplastics" placeholder="38" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-3">Tipos de Microplasticos</label>
                            <div class="grid grid-cols-3 gap-3">
                                <label class="flex items-center gap-2"><input type="checkbox" name="fiber" class="w-4 h-4"> Fibras</label>
                                <label class="flex items-center gap-2"><input type="checkbox" name="film" class="w-4 h-4"> Filmes</label>
                                <label class="flex items-center gap-2"><input type="checkbox" name="fragment" class="w-4 h-4"> Fragmentos</label>
                                <label class="flex items-center gap-2"><input type="checkbox" name="foam" class="w-4 h-4"> Espumas</label>
                                <label class="flex items-center gap-2"><input type="checkbox" name="pellets" class="w-4 h-4"> Pellets</label>
                                <label class="flex items-center gap-2"><input type="checkbox" name="sphere" class="w-4 h-4"> Esferas</label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Dimensao Plasticos</label>
                            <input type="text" name="plastic_dimension" placeholder="<5mm, 1-5mm" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Sistema de Agua Doce</label>
                            <input type="text" name="freshwater_system" placeholder="Amazon River" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Latitude</label>
                            <input type="text" name="latitude" placeholder="Ex: -23.5505" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Longitude</label>
                            <input type="text" name="longitude" placeholder="Ex: -46.6333" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tecidos Afetados</label>
                            <input type="text" name="occurrence_tissues" placeholder="Stomach, intestine" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Autor(es)</label>
                            <input type="text" name="author" placeholder="Silva, A. et al. (2023)" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Referencia</label>
                            <textarea name="reference" rows="2" placeholder="Titulo do artigo" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">DOI</label>
                            <input type="text" name="doi" placeholder="Ex: https://doi.org/10.1016/j.scitotenv.2020.139484" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                    <button type="submit" name="add_fish" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition">Adicionar Dados de Peixe</button>
                </form>
            </div>

            <!-- Status dos peixes -->
            <div class="bg-white rounded-xl shadow-lg p-6 md:p-8 mt-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-900">Status das Coordenadas</h2>
                    <div class="flex gap-3 text-sm">
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full font-medium"><?php echo count($fish_with_coords); ?> com coordenadas</span>
                        <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full font-medium"><?php echo count($fish_no_coords); ?> sem coordenadas</span>
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full font-medium"><?php echo $fish_total; ?> total</span>
                    </div>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                    <div class="bg-green-500 h-3 rounded-full transition-all" style="width: <?php echo $fish_total > 0 ? round(count($fish_with_coords) / $fish_total * 100) : 0; ?>%"></div>
                </div>
                <p class="text-xs text-gray-500"><?php echo $fish_total > 0 ? round(count($fish_with_coords) / $fish_total * 100) : 0; ?>% dos peixes com coordenadas — apenas estes aparecem no mapa</p>
            </div>

            <?php if (count($fish_no_coords) > 0): ?>
            <!-- Peixes sem coordenadas - edição em lote -->
            <div class="bg-white rounded-xl shadow-lg p-6 md:p-8 mt-6">
                <h2 class="text-xl font-bold text-gray-900 mb-2">Peixes sem Coordenadas</h2>
                <p class="text-sm text-gray-500 mb-4">Preencha latitude e longitude para que apareçam no mapa. Dica: procure as coordenadas do rio/sistema no Google Maps.</p>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                    <!-- Filtro por sistema -->
                    <div class="mb-4">
                        <input type="text" id="fishFilterInput" placeholder="Filtrar por especie ou sistema..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" oninput="filterFishRows()">
                    </div>

                    <div class="overflow-x-auto max-h-[600px] overflow-y-auto border rounded-lg">
                        <table class="w-full text-sm" id="fishTable">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700 w-8">#</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Especie</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700">Sistema</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700 w-36">Latitude</th>
                                    <th class="px-3 py-2 text-left font-semibold text-gray-700 w-36">Longitude</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach ($fish_no_coords as $i => $fish): ?>
                                <tr class="fish-row hover:bg-blue-50 transition" data-species="<?php echo strtolower(htmlspecialchars($fish['species'])); ?>" data-system="<?php echo strtolower(htmlspecialchars($fish['freshwater_system'] ?? '')); ?>">
                                    <td class="px-3 py-2 text-gray-400"><?php echo $fish['id']; ?></td>
                                    <td class="px-3 py-2 font-medium text-gray-900 italic"><?php echo htmlspecialchars($fish['species']); ?></td>
                                    <td class="px-3 py-2 text-gray-600"><?php echo htmlspecialchars($fish['freshwater_system'] ?? '—'); ?></td>
                                    <td class="px-3 py-1">
                                        <input type="hidden" name="bulk_fish_id[]" value="<?php echo $fish['id']; ?>">
                                        <input type="text" name="bulk_fish_lat[]" placeholder="-15.78" class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </td>
                                    <td class="px-3 py-1">
                                        <input type="text" name="bulk_fish_lng[]" placeholder="-47.92" class="w-full px-2 py-1.5 border border-gray-200 rounded text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-between">
                        <p class="text-xs text-gray-400">Apenas linhas com ambas coordenadas preenchidas serao atualizadas</p>
                        <button type="submit" name="bulk_update_fish_coords" class="px-6 py-2.5 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                            Salvar Coordenadas
                        </button>
                    </div>
                </form>
            </div>

            <script>
            function filterFishRows() {
                var q = document.getElementById('fishFilterInput').value.toLowerCase();
                document.querySelectorAll('.fish-row').forEach(function(row) {
                    var species = row.getAttribute('data-species');
                    var system = row.getAttribute('data-system');
                    row.style.display = (species.indexOf(q) !== -1 || system.indexOf(q) !== -1) ? '' : 'none';
                });
            }
            </script>
            <?php endif; ?>

            <?php if (count($fish_with_coords) > 0): ?>
            <!-- Peixes COM coordenadas -->
            <div class="bg-white rounded-xl shadow-lg p-6 md:p-8 mt-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Peixes com Coordenadas <span class="text-sm font-normal text-green-600">(aparecem no mapa)</span></h2>
                <div class="overflow-x-auto max-h-[400px] overflow-y-auto border rounded-lg">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 w-8">#</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Especie</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Sistema</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Lat</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700">Lng</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($fish_with_coords as $fish): ?>
                            <tr class="hover:bg-green-50">
                                <td class="px-3 py-2 text-gray-400"><?php echo $fish['id']; ?></td>
                                <td class="px-3 py-2 font-medium text-gray-900 italic"><?php echo htmlspecialchars($fish['species']); ?></td>
                                <td class="px-3 py-2 text-gray-600"><?php echo htmlspecialchars($fish['freshwater_system'] ?? '—'); ?></td>
                                <td class="px-3 py-2 text-gray-600"><?php echo $fish['latitude']; ?></td>
                                <td class="px-3 py-2 text-gray-600"><?php echo $fish['longitude']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <!-- ========== TAB: CONFIGURACOES ========== -->
        <div id="form-configuracoes" class="tab-content hidden">
            <?php if (file_exists(__DIR__ . '/admin/tab_configuracoes.php')) include __DIR__ . '/admin/tab_configuracoes.php'; ?>
        </div>

        <!-- ========== TAB: BLOCOS ========== -->
        <div id="form-blocos" class="tab-content hidden">
            <?php if (file_exists(__DIR__ . '/admin/tab_blocos.php')) include __DIR__ . '/admin/tab_blocos.php'; ?>
        </div>

        <!-- ========== TAB: TIPOS ========== -->
        <div id="form-tipos" class="tab-content hidden">
            <?php if (file_exists(__DIR__ . '/admin/tab_tipos.php')) include __DIR__ . '/admin/tab_tipos.php'; ?>
        </div>

        <!-- ========== TAB: UNIDADES ========== -->
        <div id="form-unidades" class="tab-content hidden">
            <?php if (file_exists(__DIR__ . '/admin/tab_unidades.php')) include __DIR__ . '/admin/tab_unidades.php'; ?>
        </div>
    </div>

    <script>
        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => {
                el.classList.remove('bg-white', 'shadow', 'text-slate-800');
                el.classList.add('text-gray-500');
            });
            document.getElementById('form-' + tab).classList.remove('hidden');
            const btn = document.getElementById('tab-' + tab);
            btn.classList.add('bg-white', 'shadow', 'text-slate-800');
            btn.classList.remove('text-gray-500');
        }
    </script>
</body>
</html>
