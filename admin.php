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

// Importar planilha CSV
if (isset($_POST['import_csv']) && validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $importType = $_POST['import_type'] ?? '';
    $importResults = ['inserted' => 0, 'skipped' => 0, 'errors' => []];

    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $tmpFile = $_FILES['csv_file']['tmp_name'];
        $fileName = $_FILES['csv_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            $message = 'Apenas arquivos CSV sao aceitos. Salve sua planilha como CSV (separado por virgula ou ponto-e-virgula).';
            $messageType = 'error';
        } else {
            $handle = fopen($tmpFile, 'r');

            // Detect delimiter (comma or semicolon)
            $firstLine = fgets($handle);
            rewind($handle);
            $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

            // Read header row
            $header = fgetcsv($handle, 0, $delimiter);
            if ($header) {
                // Normalize headers: lowercase, trim, remove BOM
                $header = array_map(function($h) {
                    $h = trim($h);
                    $h = preg_replace('/^\x{FEFF}/u', '', $h); // Remove BOM
                    return strtolower(str_replace(' ', '_', $h));
                }, $header);
            }

            if ($importType === 'sediment') {
                // Required: system
                $requiredCols = ['system'];
                $allCols = ['system','sampling_point','latitude','longitude','concentration_sediment',
                            'concentration_value','total_concentration','concentration_variation',
                            'depth','author','reference','tipo_ambiente','ecossistema','matriz','unidade'];

                $missingRequired = array_diff($requiredCols, $header);
                if (!empty($missingRequired)) {
                    $message = 'Colunas obrigatorias ausentes: ' . implode(', ', $missingRequired) . '. Colunas encontradas: ' . implode(', ', $header);
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO microplastics_sediment
                        (system, sampling_point, latitude, longitude, concentration_sediment, concentration_value,
                         total_concentration, concentration_variation, depth, author, reference,
                         tipo_ambiente, ecossistema, matriz, unidade, approved)
                        VALUES (:system, :sampling_point, :latitude, :longitude, :concentration_sediment, :concentration_value,
                                :total_concentration, :concentration_variation, :depth, :author, :reference,
                                :tipo_ambiente, :ecossistema, :matriz, :unidade, 1)");

                    $lineNum = 1;
                    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                        $lineNum++;
                        if (count($row) < count($header)) {
                            $row = array_pad($row, count($header), '');
                        }
                        $data = array_combine($header, array_slice($row, 0, count($header)));

                        if (empty(trim($data['system'] ?? ''))) {
                            $importResults['skipped']++;
                            continue;
                        }

                        try {
                            $stmt->execute([
                                ':system' => trim($data['system'] ?? ''),
                                ':sampling_point' => trim($data['sampling_point'] ?? '') ?: null,
                                ':latitude' => !empty($data['latitude']) ? (float)str_replace(',', '.', $data['latitude']) : null,
                                ':longitude' => !empty($data['longitude']) ? (float)str_replace(',', '.', $data['longitude']) : null,
                                ':concentration_sediment' => trim($data['concentration_sediment'] ?? '') ?: null,
                                ':concentration_value' => !empty($data['concentration_value']) ? (float)str_replace(',', '.', $data['concentration_value']) : 0,
                                ':total_concentration' => trim($data['total_concentration'] ?? '') ?: null,
                                ':concentration_variation' => trim($data['concentration_variation'] ?? '') ?: null,
                                ':depth' => trim($data['depth'] ?? '') ?: null,
                                ':author' => trim($data['author'] ?? '') ?: null,
                                ':reference' => trim($data['reference'] ?? '') ?: null,
                                ':tipo_ambiente' => trim($data['tipo_ambiente'] ?? '') ?: null,
                                ':ecossistema' => trim($data['ecossistema'] ?? '') ?: null,
                                ':matriz' => trim($data['matriz'] ?? '') ?: null,
                                ':unidade' => trim($data['unidade'] ?? '') ?: null,
                            ]);
                            $importResults['inserted']++;
                        } catch (PDOException $e) {
                            $importResults['errors'][] = "Linha $lineNum: " . $e->getMessage();
                        }
                    }
                }
            } elseif ($importType === 'fish') {
                $requiredCols = ['species'];
                $missingRequired = array_diff($requiredCols, $header);
                if (!empty($missingRequired)) {
                    $message = 'Colunas obrigatorias ausentes: ' . implode(', ', $missingRequired) . '. Colunas encontradas: ' . implode(', ', $header);
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO microplastics_fish
                        (species, habit, total_individuals, individuals_with_microplastics,
                         fiber, film, fragment, foam, pellets, sphere,
                         plastic_dimension, occurrence_tissues, freshwater_system,
                         latitude, longitude, author, reference, doi)
                        VALUES (:species, :habit, :total_individuals, :individuals_with_microplastics,
                                :fiber, :film, :fragment, :foam, :pellets, :sphere,
                                :plastic_dimension, :occurrence_tissues, :freshwater_system,
                                :latitude, :longitude, :author, :reference, :doi)");

                    $lineNum = 1;
                    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                        $lineNum++;
                        if (count($row) < count($header)) {
                            $row = array_pad($row, count($header), '');
                        }
                        $data = array_combine($header, array_slice($row, 0, count($header)));

                        if (empty(trim($data['species'] ?? ''))) {
                            $importResults['skipped']++;
                            continue;
                        }

                        try {
                            $boolVal = function($key) use ($data) {
                                $v = strtolower(trim($data[$key] ?? ''));
                                return in_array($v, ['1', 'true', 'sim', 'yes', 'x']) ? 1 : 0;
                            };

                            $stmt->execute([
                                ':species' => trim($data['species']),
                                ':habit' => trim($data['habit'] ?? '') ?: null,
                                ':total_individuals' => !empty($data['total_individuals']) ? (int)$data['total_individuals'] : 0,
                                ':individuals_with_microplastics' => !empty($data['individuals_with_microplastics']) ? (int)$data['individuals_with_microplastics'] : 0,
                                ':fiber' => $boolVal('fiber'),
                                ':film' => $boolVal('film'),
                                ':fragment' => $boolVal('fragment'),
                                ':foam' => $boolVal('foam'),
                                ':pellets' => $boolVal('pellets'),
                                ':sphere' => $boolVal('sphere'),
                                ':plastic_dimension' => trim($data['plastic_dimension'] ?? '') ?: null,
                                ':occurrence_tissues' => trim($data['occurrence_tissues'] ?? '') ?: null,
                                ':freshwater_system' => trim($data['freshwater_system'] ?? '') ?: null,
                                ':latitude' => !empty($data['latitude']) ? (float)str_replace(',', '.', $data['latitude']) : null,
                                ':longitude' => !empty($data['longitude']) ? (float)str_replace(',', '.', $data['longitude']) : null,
                                ':author' => trim($data['author'] ?? '') ?: null,
                                ':reference' => trim($data['reference'] ?? '') ?: null,
                                ':doi' => trim($data['doi'] ?? '') ?: null,
                            ]);
                            $importResults['inserted']++;
                        } catch (PDOException $e) {
                            $importResults['errors'][] = "Linha $lineNum: " . $e->getMessage();
                        }
                    }
                }
            }

            fclose($handle);

            if (empty($message)) {
                $errorCount = count($importResults['errors']);
                $message = "Importacao concluida: {$importResults['inserted']} inseridos, {$importResults['skipped']} ignorados (linhas vazias)";
                if ($errorCount > 0) {
                    $message .= ", $errorCount erros";
                    $messageType = 'error';
                } else {
                    $messageType = 'success';
                }
            }
        }
    } else {
        $message = 'Nenhum arquivo enviado ou erro no upload.';
        $messageType = 'error';
    }
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
                <button onclick="showTab('importar')" id="tab-importar"
                        class="tab-btn flex-1 py-3 px-4 rounded-md text-sm font-semibold text-gray-500 hover:text-gray-700">
                    Importar CSV
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
                <p class="text-sm text-gray-500 mb-2">Preencha latitude e longitude para que apareçam no mapa. Dica: procure as coordenadas do rio/sistema no Google Maps.</p>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 text-sm text-blue-700">
                    <strong>Varias especies no mesmo local?</strong> Pode usar as mesmas coordenadas — no mapa, elas aparecem agrupadas em um cluster com numero. Ao clicar, os marcadores se abrem em formato de aranha para visualizar cada um individualmente.
                </div>

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
        <!-- ========== TAB: IMPORTAR CSV ========== -->
        <div id="form-importar" class="tab-content hidden">
            <div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Importar Planilha CSV</h2>
                <p class="text-sm text-gray-500 mb-6">Suba um arquivo CSV para importar dados em lote. Aceita separador por virgula (,) ou ponto-e-virgula (;).</p>

                <?php if (!empty($importResults['errors'])): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <h3 class="font-semibold text-red-800 mb-2">Erros na importacao (<?php echo count($importResults['errors']); ?>):</h3>
                    <div class="max-h-40 overflow-y-auto text-sm text-red-700">
                        <?php foreach (array_slice($importResults['errors'], 0, 20) as $err): ?>
                            <p><?php echo htmlspecialchars($err); ?></p>
                        <?php endforeach; ?>
                        <?php if (count($importResults['errors']) > 20): ?>
                            <p class="font-semibold mt-2">... e mais <?php echo count($importResults['errors']) - 20; ?> erros</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                    <!-- Tipo de dados -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-3">Tipo de Dados</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative cursor-pointer" id="label-sediment-import">
                                <input type="radio" name="import_type" value="sediment" class="sr-only peer" checked onchange="updateImportTemplate()">
                                <div class="border-2 border-gray-200 rounded-xl p-4 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition hover:border-gray-300">
                                    <div class="font-semibold text-gray-900 mb-1">Sedimento / Agua</div>
                                    <p class="text-xs text-gray-500">Dados de microplasticos em sedimentos, agua, areia</p>
                                </div>
                            </label>
                            <label class="relative cursor-pointer" id="label-fish-import">
                                <input type="radio" name="import_type" value="fish" class="sr-only peer" onchange="updateImportTemplate()">
                                <div class="border-2 border-gray-200 rounded-xl p-4 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition hover:border-gray-300">
                                    <div class="font-semibold text-gray-900 mb-1">Peixes</div>
                                    <p class="text-xs text-gray-500">Dados de microplasticos em especies de peixes</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Colunas esperadas -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 text-sm mb-2">Colunas esperadas no CSV:</h3>
                        <div id="template-sediment" class="text-xs text-gray-600 space-y-1">
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border text-red-600">system*</span> Nome do sistema aquatico (obrigatorio)</p>
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border">sampling_point</span> Ponto de amostragem</p>
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border">latitude</span> <span class="font-mono bg-white px-1.5 py-0.5 rounded border">longitude</span> Coordenadas decimais</p>
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border">concentration_sediment</span> Concentracao (texto, ex: "2101/Kg")</p>
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border">concentration_value</span> Valor numerico da concentracao</p>
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border">unidade</span> Unidade de medida</p>
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border">tipo_ambiente</span> <span class="font-mono bg-white px-1.5 py-0.5 rounded border">ecossistema</span> <span class="font-mono bg-white px-1.5 py-0.5 rounded border">matriz</span> Classificacao</p>
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border">total_concentration</span> <span class="font-mono bg-white px-1.5 py-0.5 rounded border">concentration_variation</span> <span class="font-mono bg-white px-1.5 py-0.5 rounded border">depth</span></p>
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border">author</span> <span class="font-mono bg-white px-1.5 py-0.5 rounded border">reference</span> Autoria e referencia</p>
                        </div>
                        <div id="template-fish" class="text-xs text-gray-600 space-y-1 hidden">
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border text-red-600">species*</span> Nome da especie (obrigatorio)</p>
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border">habit</span> Habito alimentar (Omnivore, Carnivore, Herbivore, Insectivore, Detritivore, Piscivore)</p>
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border">total_individuals</span> <span class="font-mono bg-white px-1.5 py-0.5 rounded border">individuals_with_microplastics</span> Contagens</p>
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border">fiber</span> <span class="font-mono bg-white px-1.5 py-0.5 rounded border">film</span> <span class="font-mono bg-white px-1.5 py-0.5 rounded border">fragment</span> <span class="font-mono bg-white px-1.5 py-0.5 rounded border">foam</span> <span class="font-mono bg-white px-1.5 py-0.5 rounded border">pellets</span> <span class="font-mono bg-white px-1.5 py-0.5 rounded border">sphere</span> Tipos (1/0, sim/nao, true/false, x)</p>
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border">plastic_dimension</span> <span class="font-mono bg-white px-1.5 py-0.5 rounded border">occurrence_tissues</span> Dimensao e tecidos</p>
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border">freshwater_system</span> Sistema de agua doce</p>
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border">latitude</span> <span class="font-mono bg-white px-1.5 py-0.5 rounded border">longitude</span> Coordenadas decimais</p>
                            <p><span class="font-mono bg-white px-1.5 py-0.5 rounded border">author</span> <span class="font-mono bg-white px-1.5 py-0.5 rounded border">reference</span> <span class="font-mono bg-white px-1.5 py-0.5 rounded border">doi</span></p>
                        </div>
                    </div>

                    <!-- Upload -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Arquivo CSV</label>
                        <div class="relative">
                            <input type="file" name="csv_file" accept=".csv" required id="csvFileInput"
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-3 file:px-6 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-gray-300 rounded-lg"
                                   onchange="previewCSV(this)">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Tamanho maximo: 10MB. Formato: CSV (virgula ou ponto-e-virgula)</p>
                    </div>

                    <!-- Preview -->
                    <div id="csvPreview" class="hidden">
                        <h3 class="font-semibold text-gray-800 text-sm mb-2">Pre-visualizacao (primeiras 5 linhas):</h3>
                        <div class="overflow-x-auto border rounded-lg">
                            <table class="w-full text-xs" id="csvPreviewTable">
                                <thead class="bg-gray-50" id="csvPreviewHead"></thead>
                                <tbody class="divide-y" id="csvPreviewBody"></tbody>
                            </table>
                        </div>
                        <p class="text-xs text-gray-400 mt-1" id="csvPreviewCount"></p>
                    </div>

                    <button type="submit" name="import_csv" class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-lg transition flex items-center justify-center gap-2">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                        Importar Dados
                    </button>
                </form>
            </div>

            <!-- Baixar template -->
            <div class="bg-white rounded-xl shadow-lg p-6 md:p-8 mt-6">
                <h2 class="text-lg font-bold text-gray-900 mb-3">Baixar Template</h2>
                <p class="text-sm text-gray-500 mb-4">Baixe um modelo CSV com as colunas corretas para preencher.</p>
                <div class="flex gap-4">
                    <button onclick="downloadTemplate('sediment')" class="px-5 py-2.5 bg-blue-100 text-blue-700 font-semibold rounded-lg hover:bg-blue-200 transition text-sm">
                        Template Sedimento
                    </button>
                    <button onclick="downloadTemplate('fish')" class="px-5 py-2.5 bg-teal-100 text-teal-700 font-semibold rounded-lg hover:bg-teal-200 transition text-sm">
                        Template Peixes
                    </button>
                </div>
            </div>
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

        function updateImportTemplate() {
            var type = document.querySelector('input[name="import_type"]:checked').value;
            document.getElementById('template-sediment').classList.toggle('hidden', type !== 'sediment');
            document.getElementById('template-fish').classList.toggle('hidden', type !== 'fish');
        }

        function previewCSV(input) {
            var file = input.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                var text = e.target.result;
                var lines = text.split(/\r?\n/).filter(function(l) { return l.trim() !== ''; });
                if (lines.length < 2) {
                    document.getElementById('csvPreview').classList.add('hidden');
                    return;
                }

                var delim = (lines[0].split(';').length > lines[0].split(',').length) ? ';' : ',';

                function parseCSVLine(line, d) {
                    var result = [];
                    var current = '';
                    var inQuotes = false;
                    for (var i = 0; i < line.length; i++) {
                        var ch = line[i];
                        if (inQuotes) {
                            if (ch === '"' && line[i+1] === '"') { current += '"'; i++; }
                            else if (ch === '"') { inQuotes = false; }
                            else { current += ch; }
                        } else {
                            if (ch === '"') { inQuotes = true; }
                            else if (ch === d) { result.push(current.trim()); current = ''; }
                            else { current += ch; }
                        }
                    }
                    result.push(current.trim());
                    return result;
                }

                var headers = parseCSVLine(lines[0], delim);
                var thead = '<tr>';
                headers.forEach(function(h) {
                    thead += '<th class="px-2 py-1.5 text-left font-semibold text-gray-700 whitespace-nowrap">' + h + '</th>';
                });
                thead += '</tr>';
                document.getElementById('csvPreviewHead').innerHTML = thead;

                var tbody = '';
                var previewCount = Math.min(lines.length - 1, 5);
                for (var i = 1; i <= previewCount; i++) {
                    var cols = parseCSVLine(lines[i], delim);
                    tbody += '<tr class="hover:bg-gray-50">';
                    for (var j = 0; j < headers.length; j++) {
                        var val = (cols[j] || '').substring(0, 50);
                        tbody += '<td class="px-2 py-1.5 text-gray-600 whitespace-nowrap">' + val + '</td>';
                    }
                    tbody += '</tr>';
                }
                document.getElementById('csvPreviewBody').innerHTML = tbody;
                document.getElementById('csvPreviewCount').textContent = (lines.length - 1) + ' linhas de dados encontradas';
                document.getElementById('csvPreview').classList.remove('hidden');
            };
            reader.readAsText(file, 'UTF-8');
        }

        function downloadTemplate(type) {
            var content, filename;
            if (type === 'sediment') {
                content = 'system;sampling_point;latitude;longitude;concentration_sediment;concentration_value;unidade;tipo_ambiente;ecossistema;matriz;total_concentration;concentration_variation;depth;author;reference\n';
                content += 'Amazon River;AMZ1;-3.146601;-59.383157;2101/Kg;2101;part/Kg;Doce;Rio;Sedimento;417/Kg - 2101/Kg;Space;5;Gerolin, C. et al. (2020);Microplastics in sediments from Amazon rivers\n';
                filename = 'template_sedimento.csv';
            } else {
                content = 'species;habit;total_individuals;individuals_with_microplastics;fiber;film;fragment;foam;pellets;sphere;plastic_dimension;occurrence_tissues;freshwater_system;latitude;longitude;author;reference;doi\n';
                content += 'Astyanax lacustris;Omnivore;132;38;1;0;1;0;0;1;;Stomach, Gills;Uruguay River;-30.123;-57.456;Silva, A. et al. (2023);Titulo do artigo;\n';
                filename = 'template_peixes.csv';
            }
            var BOM = '\uFEFF';
            var blob = new Blob([BOM + content], { type: 'text/csv;charset=utf-8' });
            var a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = filename;
            a.click();
        }
    </script>
</body>
</html>
