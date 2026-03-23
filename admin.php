<?php
require_once __DIR__ . '/auth.php';
requireLogin();

// Verificar acesso ao painel
if (!canAccessAdmin()) {
    header('Location: /');
    exit;
}

require_once 'config/database.php';
require_once __DIR__ . '/config/cms.php';
$pdo = getDatabaseConnection();
$message = '';
$messageType = '';
$activeTab = $_POST['_tab'] ?? $_GET['tab'] ?? 'users';

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
    $targetId = (int)$_POST['user_id'];

    // Validate role exists
    $roleCheck = $pdo->prepare("SELECT level FROM roles WHERE name = :name");
    $roleCheck->execute([':name' => $new_role]);
    $newRoleData = $roleCheck->fetch();

    if (!$newRoleData) {
        $message = 'Cargo invalido.';
        $messageType = 'error';
    } else {
        $newRoleLevel = (int)$newRoleData['level'];
        $myLevel = getRoleLevel();

        // Check target user's current role level
        $target = $pdo->prepare("SELECT u.role, COALESCE(r.level, 0) as role_level FROM users u LEFT JOIN roles r ON u.role = r.name WHERE u.id = :id");
        $target->execute([':id' => $targetId]);
        $targetData = $target->fetch();
        $targetLevel = (int)($targetData['role_level'] ?? 0);

        if ($targetLevel >= $myLevel && !isOwner()) {
            $message = 'Voce so pode alterar cargos de usuarios com nivel inferior ao seu.';
            $messageType = 'error';
        } elseif ($newRoleLevel > $myLevel && !isOwner()) {
            $message = 'Voce nao pode atribuir um cargo com nivel superior ao seu.';
            $messageType = 'error';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
            $stmt->execute([':role' => $new_role, ':id' => $targetId]);
            $message = 'Cargo do usuario atualizado!';
            $messageType = 'success';
        }
    }
}

// Bloquear usuario
if (isset($_POST['block_user']) && validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $targetId = (int)$_POST['user_id'];
    $target = $pdo->prepare("SELECT role FROM users WHERE id = :id");
    $target->execute([':id' => $targetId]);
    $targetRole = $target->fetchColumn();
    if ($targetRole === 'owner') {
        $message = 'Nao e possivel bloquear um dono.';
        $messageType = 'error';
    } else {
        $pdo->prepare("UPDATE users SET status = 'blocked' WHERE id = :id")->execute([':id' => $targetId]);
        $message = 'Usuario bloqueado.';
        $messageType = 'success';
    }
}

// Desbloquear usuario
if (isset($_POST['unblock_user']) && validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = :id")->execute([':id' => (int)$_POST['user_id']]);
    $message = 'Usuario desbloqueado.';
    $messageType = 'success';
}

// Excluir usuario (apenas owners)
if (isset($_POST['delete_user']) && validateCsrfToken($_POST['csrf_token'] ?? '') && isOwner()) {
    $targetId = (int)$_POST['user_id'];
    $target = $pdo->prepare("SELECT role FROM users WHERE id = :id");
    $target->execute([':id' => $targetId]);
    $targetRole = $target->fetchColumn();
    if ($targetRole === 'owner') {
        $message = 'Nao e possivel excluir um dono.';
        $messageType = 'error';
    } elseif ($targetId === (int)$_SESSION['user_id']) {
        $message = 'Nao e possivel excluir a si mesmo.';
        $messageType = 'error';
    } else {
        $pdo->prepare("DELETE FROM users WHERE id = :id")->execute([':id' => $targetId]);
        $message = 'Usuario excluido permanentemente.';
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

// Criar cargo (apenas owners)
if (isset($_POST['create_role']) && validateCsrfToken($_POST['csrf_token'] ?? '') && isOwner()) {
    $newLabel = trim($_POST['new_label'] ?? '');
    $newLevel = (int)($_POST['new_level'] ?? 0);
    $newColor = $_POST['new_color'] ?? '#6b7280';
    $newPerms = $_POST['new_perms'] ?? [];

    if (empty($newLabel)) {
        $message = 'Nome do cargo e obrigatorio.';
        $messageType = 'error';
    } elseif ($newLevel < 1 || $newLevel > 99) {
        $message = 'Nivel deve ser entre 1 e 99.';
        $messageType = 'error';
    } else {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '_', iconv('UTF-8', 'ASCII//TRANSLIT', $newLabel)));
        $slug = trim($slug, '_');

        $check = $pdo->prepare("SELECT id FROM roles WHERE name = :name");
        $check->execute([':name' => $slug]);
        if ($check->fetch()) {
            $message = 'Ja existe um cargo com esse nome interno (' . $slug . ').';
            $messageType = 'error';
        } else {
            $stmt = $pdo->prepare("INSERT INTO roles (name, label, level, color, can_access_admin, can_manage_users, can_manage_data, can_manage_categories, can_import_csv, can_manage_site, is_system)
                VALUES (:name, :label, :level, :color, :p1, :p2, :p3, :p4, :p5, :p6, 0)");
            $stmt->execute([
                ':name' => $slug, ':label' => $newLabel, ':level' => $newLevel, ':color' => $newColor,
                ':p1' => isset($newPerms['can_access_admin']) ? 1 : 0,
                ':p2' => isset($newPerms['can_manage_users']) ? 1 : 0,
                ':p3' => isset($newPerms['can_manage_data']) ? 1 : 0,
                ':p4' => isset($newPerms['can_manage_categories']) ? 1 : 0,
                ':p5' => isset($newPerms['can_import_csv']) ? 1 : 0,
                ':p6' => isset($newPerms['can_manage_site']) ? 1 : 0,
            ]);
            $message = 'Cargo "' . htmlspecialchars($newLabel) . '" criado com sucesso!';
            $messageType = 'success';
        }
    }
    $activeTab = 'cargos';
}

// Atualizar cargo (apenas owners, nao pode alterar cargos de sistema)
if (isset($_POST['update_role']) && validateCsrfToken($_POST['csrf_token'] ?? '') && isOwner()) {
    $roleId = (int)$_POST['role_id'];
    $check = $pdo->prepare("SELECT is_system FROM roles WHERE id = :id");
    $check->execute([':id' => $roleId]);
    $roleData = $check->fetch();

    if ($roleData && $roleData['is_system']) {
        $message = 'Cargos de sistema nao podem ser alterados.';
        $messageType = 'error';
    } elseif ($roleData) {
        $perms = $_POST['perms'] ?? [];
        $stmt = $pdo->prepare("UPDATE roles SET label = :label, level = :level, color = :color,
            can_access_admin = :p1, can_manage_users = :p2, can_manage_data = :p3,
            can_manage_categories = :p4, can_import_csv = :p5, can_manage_site = :p6
            WHERE id = :id AND is_system = 0");
        $stmt->execute([
            ':label' => trim($_POST['label'] ?? ''), ':level' => (int)($_POST['level'] ?? 0),
            ':color' => $_POST['color'] ?? '#6b7280', ':id' => $roleId,
            ':p1' => isset($perms['can_access_admin']) ? 1 : 0,
            ':p2' => isset($perms['can_manage_users']) ? 1 : 0,
            ':p3' => isset($perms['can_manage_data']) ? 1 : 0,
            ':p4' => isset($perms['can_manage_categories']) ? 1 : 0,
            ':p5' => isset($perms['can_import_csv']) ? 1 : 0,
            ':p6' => isset($perms['can_manage_site']) ? 1 : 0,
        ]);
        $message = 'Cargo atualizado com sucesso!';
        $messageType = 'success';
    }
    $activeTab = 'cargos';
}

// Excluir cargo (apenas owners, nao pode excluir cargos de sistema)
if (isset($_POST['delete_role']) && validateCsrfToken($_POST['csrf_token'] ?? '') && isOwner()) {
    $roleId = (int)$_POST['role_id'];
    $check = $pdo->prepare("SELECT name, is_system FROM roles WHERE id = :id");
    $check->execute([':id' => $roleId]);
    $roleData = $check->fetch();

    if ($roleData && $roleData['is_system']) {
        $message = 'Cargos de sistema nao podem ser excluidos.';
        $messageType = 'error';
    } elseif ($roleData) {
        // Mover usuarios desse cargo para 'user'
        $pdo->prepare("UPDATE users SET role = 'user' WHERE role = :role")->execute([':role' => $roleData['name']]);
        $pdo->prepare("DELETE FROM roles WHERE id = :id AND is_system = 0")->execute([':id' => $roleId]);
        $message = 'Cargo excluido. Usuarios foram movidos para "Usuario".';
        $messageType = 'success';
    }
    $activeTab = 'cargos';
}

// ========================================
// Buscar dados para exibicao
// ========================================
$pending_users = $pdo->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC")->fetchAll();
$approved_users = $pdo->query("SELECT * FROM users WHERE status = 'approved' ORDER BY FIELD(role, 'owner', 'admin', 'scientist', 'user'), nome")->fetchAll();
$blocked_users = $pdo->query("SELECT * FROM users WHERE status = 'blocked' ORDER BY nome")->fetchAll();
$userIsOwner = isOwner();
$all_roles = [];
try { $all_roles = $pdo->query("SELECT * FROM roles ORDER BY level DESC")->fetchAll(); } catch (Exception $e) {}
$pending_data = $pdo->query("SELECT ms.*, u.nome as submitter_name FROM microplastics_sediment ms LEFT JOIN users u ON ms.submitted_by = u.id WHERE ms.approved = 0 ORDER BY ms.created_at DESC")->fetchAll();

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
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        /* Sidebar */
        .admin-sidebar {
            width: 260px;
            height: 100dvh;
            height: 100vh; /* fallback for older browsers */
            background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 40;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(148,163,184,0.08);
            transition: transform 0.3s ease;
        }
        @supports (height: 100dvh) {
            .admin-sidebar { height: 100dvh; }
        }
        .admin-main {
            margin-left: 260px;
            min-height: 100vh;
        }
        .sidebar-section-label {
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(148,163,184,0.45);
            padding: 20px 20px 6px;
        }
        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 20px;
            font-size: 0.82rem;
            font-weight: 500;
            color: rgba(148,163,184,0.75);
            cursor: pointer;
            transition: all 0.15s;
            border-left: 3px solid transparent;
            text-decoration: none;
        }
        .sidebar-item:hover {
            color: #e2e8f0;
            background: rgba(148,163,184,0.06);
        }
        .sidebar-item.active {
            color: #22d3ee;
            background: rgba(34,211,238,0.06);
            border-left-color: #22d3ee;
        }
        .sidebar-item svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            opacity: 0.65;
        }
        .sidebar-item.active svg { opacity: 1; }
        .sidebar-badge {
            margin-left: auto;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 50px;
            line-height: 1.4;
        }
        /* Bell animation */
        @keyframes bellShake {
            0%, 100% { transform: rotate(0); }
            15% { transform: rotate(12deg); }
            30% { transform: rotate(-10deg); }
            45% { transform: rotate(6deg); }
            60% { transform: rotate(-4deg); }
            75% { transform: rotate(2deg); }
        }
        .notif-bell-animate {
            animation: bellShake 0.8s ease-in-out;
            animation-delay: 1s;
            transform-origin: top center;
        }
        /* Mobile overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 35;
        }
        .mobile-header {
            display: none;
        }
        @media (max-width: 1024px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-sidebar.open { transform: translateX(0); }
            .sidebar-overlay.open { display: block; }
            .admin-main { margin-left: 0; }
            .mobile-header { display: flex; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Mobile Header -->
    <div class="mobile-header bg-slate-900 px-4 py-3 items-center justify-between sticky top-0 z-30">
        <button onclick="toggleSidebar()" class="text-gray-300 hover:text-white">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <span class="text-cyan-400 font-bold text-lg">GeoPlasticoBR</span>
        <a href="/login.php?logout=1" class="text-red-400 text-sm font-medium">Sair</a>
    </div>

    <!-- Sidebar Overlay (mobile) -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <!-- Logo + Notification Bell -->
        <div class="px-5 py-5 border-b border-white/5 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-extrabold text-cyan-400 tracking-tight">GeoPlasticoBR</h1>
                <p class="text-[0.68rem] text-slate-500 mt-0.5">Painel de Administracao</p>
            </div>
            <?php
                $notifs = [];
                if (count($pending_users) > 0) $notifs[] = ['count' => count($pending_users), 'label' => count($pending_users) === 1 ? 'usuario pendente' : 'usuarios pendentes', 'tab' => 'users', 'color' => '#ef4444'];
                if (count($pending_data) > 0) $notifs[] = ['count' => count($pending_data), 'label' => count($pending_data) === 1 ? 'dado pendente' : 'dados pendentes', 'tab' => 'pending_data', 'color' => '#f59e0b'];
                $totalNotifs = array_sum(array_column($notifs, 'count'));
            ?>
            <div class="relative" id="notifWrapper">
                <button onclick="toggleNotifs()" class="relative p-2 rounded-lg hover:bg-white/8 transition group" title="Notificacoes">
                    <svg class="w-5 h-5 text-slate-400 group-hover:text-cyan-400 transition <?php echo $totalNotifs > 0 ? 'notif-bell-animate' : ''; ?>" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 01-3.46 0"/>
                    </svg>
                    <?php if ($totalNotifs > 0): ?>
                    <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] flex items-center justify-center bg-red-500 text-white text-[0.6rem] font-bold rounded-full px-1 shadow-lg shadow-red-500/30"><?php echo $totalNotifs; ?></span>
                    <?php endif; ?>
                </button>
                <!-- Dropdown -->
                <div id="notifDropdown" class="hidden absolute right-0 top-full mt-2 w-72 bg-slate-800 border border-white/10 rounded-xl shadow-2xl shadow-black/40 z-50 overflow-hidden">
                    <div class="px-4 py-3 border-b border-white/5 flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">Notificacoes</span>
                        <?php if ($totalNotifs > 0): ?>
                        <span class="text-[0.6rem] font-bold text-cyan-400 bg-cyan-400/10 px-2 py-0.5 rounded-full"><?php echo $totalNotifs; ?> nova<?php echo $totalNotifs > 1 ? 's' : ''; ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($totalNotifs === 0): ?>
                    <div class="px-4 py-8 text-center">
                        <svg class="w-8 h-8 text-slate-600 mx-auto mb-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
                        <p class="text-xs text-slate-500">Nenhuma notificacao</p>
                    </div>
                    <?php else: ?>
                    <div class="max-h-64 overflow-y-auto" style="scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.08) transparent;">
                        <?php foreach ($notifs as $n): ?>
                        <button onclick="showTab('<?php echo $n['tab']; ?>'); toggleNotifs();" class="w-full px-4 py-3 flex items-center gap-3 hover:bg-white/5 transition text-left">
                            <div class="w-9 h-9 rounded-full flex items-center justify-center flex-shrink-0" style="background: <?php echo $n['color']; ?>15;">
                                <span class="text-sm font-bold" style="color: <?php echo $n['color']; ?>;"><?php echo $n['count']; ?></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm text-slate-200 font-medium"><?php echo $n['count']; ?> <?php echo $n['label']; ?></div>
                                <div class="text-[0.65rem] text-slate-500">Clique para revisar</div>
                            </div>
                            <svg class="w-4 h-4 text-slate-500 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto py-2" style="scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.08) transparent;">

            <?php if (hasPermission('can_manage_users')): ?>
            <div class="sidebar-section-label">Moderacao</div>
            <button onclick="showTab('users')" id="tab-users" class="sidebar-item tab-btn <?php echo $activeTab === 'users' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
                <span>Usuarios</span>
                <?php if (count($pending_users) > 0): ?>
                <span class="sidebar-badge bg-red-500 text-white"><?php echo count($pending_users); ?></span>
                <?php endif; ?>
            </button>
            <button onclick="showTab('pending_data')" id="tab-pending_data" class="sidebar-item tab-btn <?php echo $activeTab === 'pending_data' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span>Dados Pendentes</span>
                <?php if (count($pending_data) > 0): ?>
                <span class="sidebar-badge bg-amber-500 text-white"><?php echo count($pending_data); ?></span>
                <?php endif; ?>
            </button>
            <?php endif; ?>

            <?php if (hasPermission('can_manage_data') || hasPermission('can_manage_categories') || hasPermission('can_import_csv')): ?>
            <div class="sidebar-section-label">Banco de Dados</div>
            <?php if (hasPermission('can_manage_data')): ?>
            <button onclick="showTab('dados')" id="tab-dados" class="sidebar-item tab-btn <?php echo $activeTab === 'dados' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                <span>Dados / Amostras</span>
            </button>
            <?php endif; ?>
            <?php if (hasPermission('can_manage_data')): ?>
            <button onclick="showTab('especies')" id="tab-especies" class="sidebar-item tab-btn <?php echo $activeTab === 'especies' ? 'active' : ''; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Especies</span>
            </button>
            <?php endif; ?>
            <?php if (hasPermission('can_manage_categories')): ?>
            <button onclick="showTab('categorias')" id="tab-categorias" class="sidebar-item tab-btn <?php echo $activeTab === 'categorias' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16a2 2 0 002-2V8a2 2 0 00-2-2h-7.93a2 2 0 01-1.66-.9l-.82-1.2A2 2 0 007.93 3H4a2 2 0 00-2 2v13c0 1.1.9 2 2 2z"/></svg>
                <span>Categorias</span>
            </button>
            <button onclick="showTab('campos')" id="tab-campos" class="sidebar-item tab-btn <?php echo $activeTab === 'campos' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span>Campos</span>
            </button>
            <?php endif; ?>
            <?php if (hasPermission('can_import_csv')): ?>
            <button onclick="showTab('importar')" id="tab-importar" class="sidebar-item tab-btn <?php echo $activeTab === 'importar' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span>Importar CSV</span>
            </button>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($userIsOwner): ?>
            <div class="sidebar-section-label">Dono</div>
            <button onclick="showTab('cargos')" id="tab-cargos" class="sidebar-item tab-btn <?php echo $activeTab === 'cargos' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15l-2 5l9-11h-5l2-5l-9 11h5z"/></svg>
                <span>Cargos</span>
            </button>
            <button onclick="showTab('configuracoes')" id="tab-configuracoes" class="sidebar-item tab-btn <?php echo $activeTab === 'configuracoes' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                <span>Configuracoes</span>
            </button>
            <button onclick="showTab('blocos')" id="tab-blocos" class="sidebar-item tab-btn <?php echo $activeTab === 'blocos' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                <span>Blocos de Conteudo</span>
            </button>
            <button onclick="showTab('tipos')" id="tab-tipos" class="sidebar-item tab-btn <?php echo $activeTab === 'tipos' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                <span>Tipos de Dados</span>
            </button>
            <button onclick="showTab('unidades')" id="tab-unidades" class="sidebar-item tab-btn <?php echo $activeTab === 'unidades' ? 'active' : ''; ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                <span>Unidades</span>
            </button>
            <?php endif; ?>
        </nav>

        <!-- User footer -->
        <div class="px-5 py-4 border-t border-white/5" style="flex-shrink:0;">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-cyan-500/15 flex items-center justify-center text-cyan-400 text-xs font-bold">
                    <?php echo strtoupper(substr($user['nome'], 0, 1)); ?>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold text-gray-300 truncate"><?php echo htmlspecialchars($user['nome']); ?></div>
                    <div class="text-[0.65rem] text-slate-500"><?php echo htmlspecialchars(getRoleLabel()); ?></div>
                </div>
            </div>
            <div class="flex gap-2 mt-3">
                <a href="/mapa.php" class="flex-1 text-center text-xs py-1.5 rounded-md bg-white/5 text-slate-400 hover:text-cyan-400 hover:bg-white/8 transition font-medium">Mapa</a>
                <a href="/" class="flex-1 text-center text-xs py-1.5 rounded-md bg-white/5 text-slate-400 hover:text-white hover:bg-white/8 transition font-medium">Inicio</a>
                <a href="/login.php?logout=1" class="flex-1 text-center text-xs py-1.5 rounded-md bg-white/5 text-red-400 hover:bg-red-500/10 transition font-medium">Sair</a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
        <div class="max-w-6xl mx-auto px-6 lg:px-10 py-8">

        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- ========== TAB: USUARIOS ========== -->
        <div id="form-users" class="tab-content <?php echo $activeTab !== 'users' ? 'hidden' : ''; ?>">
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
                <h2 class="text-xl font-bold text-gray-900 mb-4">Usuarios Ativos</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Nome</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Email</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tipo</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Cargo</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Alterar Cargo</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-600">Acoes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                                // Build role lookup from $all_roles
                                $roleLookup = [];
                                foreach ($all_roles as $rl) { $roleLookup[$rl['name']] = $rl; }
                            ?>
                            <?php foreach ($approved_users as $u):
                                $rd = $roleLookup[$u['role']] ?? null;
                                $roleColor = $rd ? $rd['color'] : '#6b7280';
                                $roleLabel = $rd ? $rd['label'] : ucfirst($u['role']);
                                $isSelf = (int)$u['id'] === (int)$_SESSION['user_id'];
                                $isTargetOwner = $u['role'] === 'owner';
                                $canEdit = $userIsOwner || (!$isTargetOwner);
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">
                                    <?php echo htmlspecialchars($u['nome']); ?>
                                    <?php if ($isSelf): ?><span class="text-xs text-gray-400 ml-1">(voce)</span><?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-gray-600 text-xs"><?php echo htmlspecialchars($u['email']); ?></td>
                                <td class="px-4 py-3"><span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs"><?php echo htmlspecialchars($u['tipo_usuario']); ?></span></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded text-xs font-semibold" style="background: <?php echo htmlspecialchars($roleColor); ?>15; color: <?php echo htmlspecialchars($roleColor); ?>; border: 1px solid <?php echo htmlspecialchars($roleColor); ?>30;"><?php echo htmlspecialchars($roleLabel); ?></span>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ($canEdit && !$isSelf): ?>
                                    <form method="POST" class="flex gap-2 items-center">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <select name="new_role" class="text-xs border rounded px-2 py-1.5">
                                            <?php foreach ($all_roles as $r):
                                                // Non-owners can't assign owner role
                                                if ($r['level'] >= 100 && !$userIsOwner) continue;
                                                // Can only assign roles at or below your level
                                                if ($r['level'] > getRoleLevel() && !$userIsOwner) continue;
                                            ?>
                                            <option value="<?php echo htmlspecialchars($r['name']); ?>" <?php echo $u['role'] === $r['name'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($r['label']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button name="change_role" class="bg-slate-700 hover:bg-slate-800 text-white px-3 py-1.5 rounded text-xs font-semibold">Salvar</button>
                                    </form>
                                    <?php else: ?>
                                    <span class="text-xs text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if (!$isSelf && !$isTargetOwner): ?>
                                    <div class="flex gap-1 justify-center">
                                        <form method="POST" class="inline" onsubmit="return confirm('Bloquear <?php echo htmlspecialchars(addslashes($u['nome'])); ?>?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button name="block_user" class="px-2 py-1 bg-orange-100 text-orange-700 rounded text-xs font-medium hover:bg-orange-200 transition" title="Bloquear">
                                                <svg class="inline w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
                                            </button>
                                        </form>
                                        <?php if ($userIsOwner): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('EXCLUIR PERMANENTEMENTE <?php echo htmlspecialchars(addslashes($u['nome'])); ?>? Esta acao nao pode ser desfeita!');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button name="delete_user" class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-medium hover:bg-red-200 transition" title="Excluir">
                                                <svg class="inline w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-xs text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Usuarios bloqueados -->
            <?php if (count($blocked_users) > 0): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 mt-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Usuarios Bloqueados</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-red-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Nome</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Email</th>
                                <th class="px-4 py-3 text-center font-semibold text-gray-600">Acoes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($blocked_users as $u): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-500"><?php echo htmlspecialchars($u['nome']); ?></td>
                                <td class="px-4 py-3 text-gray-400 text-xs"><?php echo htmlspecialchars($u['email']); ?></td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex gap-1 justify-center">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button name="unblock_user" class="px-3 py-1 bg-green-100 text-green-700 rounded text-xs font-medium hover:bg-green-200 transition">Desbloquear</button>
                                        </form>
                                        <?php if ($userIsOwner): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('EXCLUIR PERMANENTEMENTE <?php echo htmlspecialchars(addslashes($u['nome'])); ?>?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <button name="delete_user" class="px-3 py-1 bg-red-100 text-red-700 rounded text-xs font-medium hover:bg-red-200 transition">Excluir</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ========== TAB: DADOS PENDENTES ========== -->
        <div id="form-pending_data" class="tab-content <?php echo $activeTab !== 'pending_data' ? 'hidden' : ''; ?>">
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

        <!-- ========== TAB: IMPORTAR CSV ========== -->
        <div id="form-importar" class="tab-content <?php echo $activeTab !== 'importar' ? 'hidden' : ''; ?>">
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

        <!-- ========== TAB: CARGOS (owner only) ========== -->
        <div id="form-cargos" class="tab-content <?php echo $activeTab !== 'cargos' ? 'hidden' : ''; ?>">
            <?php if ($userIsOwner && file_exists(__DIR__ . '/admin/tab_cargos.php')) include __DIR__ . '/admin/tab_cargos.php'; ?>
        </div>

        <!-- ========== TAB: CONFIGURACOES ========== -->
        <div id="form-configuracoes" class="tab-content <?php echo $activeTab !== 'configuracoes' ? 'hidden' : ''; ?>">
            <?php if (file_exists(__DIR__ . '/admin/tab_configuracoes.php')) include __DIR__ . '/admin/tab_configuracoes.php'; ?>
        </div>

        <!-- ========== TAB: BLOCOS ========== -->
        <div id="form-blocos" class="tab-content <?php echo $activeTab !== 'blocos' ? 'hidden' : ''; ?>">
            <?php if (file_exists(__DIR__ . '/admin/tab_blocos.php')) include __DIR__ . '/admin/tab_blocos.php'; ?>
        </div>

        <!-- ========== TAB: TIPOS ========== -->
        <div id="form-tipos" class="tab-content <?php echo $activeTab !== 'tipos' ? 'hidden' : ''; ?>">
            <?php if (file_exists(__DIR__ . '/admin/tab_tipos.php')) include __DIR__ . '/admin/tab_tipos.php'; ?>
        </div>

        <!-- ========== TAB: UNIDADES ========== -->
        <div id="form-unidades" class="tab-content <?php echo $activeTab !== 'unidades' ? 'hidden' : ''; ?>">
            <?php if (file_exists(__DIR__ . '/admin/tab_unidades.php')) include __DIR__ . '/admin/tab_unidades.php'; ?>
        </div>

        <!-- ========== TAB: CATEGORIAS ========== -->
        <div id="form-categorias" class="tab-content <?php echo $activeTab !== 'categorias' ? 'hidden' : ''; ?>">
            <?php if (file_exists(__DIR__ . '/admin/tab_categorias.php')) include __DIR__ . '/admin/tab_categorias.php'; ?>
        </div>

        <!-- ========== TAB: CAMPOS ========== -->
        <div id="form-campos" class="tab-content <?php echo $activeTab !== 'campos' ? 'hidden' : ''; ?>">
            <?php if (file_exists(__DIR__ . '/admin/tab_campos.php')) include __DIR__ . '/admin/tab_campos.php'; ?>
        </div>

        <!-- ========== TAB: DADOS ========== -->
        <div id="form-dados" class="tab-content <?php echo $activeTab !== 'dados' ? 'hidden' : ''; ?>">
            <?php if (file_exists(__DIR__ . '/admin/tab_dados.php')) include __DIR__ . '/admin/tab_dados.php'; ?>
        </div>

        <div id="form-especies" class="tab-content <?php echo $activeTab !== 'especies' ? 'hidden' : ''; ?>">
            <?php if (file_exists(__DIR__ . '/admin/tab_especies.php')) include __DIR__ . '/admin/tab_especies.php'; ?>
        </div>

        </div>
    </div>

    <script>
        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            document.getElementById('form-' + tab).classList.remove('hidden');
            const btn = document.getElementById('tab-' + tab);
            if (btn) btn.classList.add('active');
            history.replaceState(null, '', 'admin.php?tab=' + tab + window.location.hash);
            // Close sidebar on mobile after selecting
            if (window.innerWidth <= 1024) {
                document.getElementById('adminSidebar').classList.remove('open');
                document.getElementById('sidebarOverlay').classList.remove('open');
            }
        }

        function toggleNotifs() {
            var dd = document.getElementById('notifDropdown');
            dd.classList.toggle('hidden');
        }
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            var w = document.getElementById('notifWrapper');
            if (w && !w.contains(e.target)) {
                document.getElementById('notifDropdown').classList.add('hidden');
            }
        });

        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('open');
        }

        // Ao submeter qualquer form, injetar _tab para voltar na mesma aba
        document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(function(form) {
            form.addEventListener('submit', function() {
                if (!form.querySelector('input[name="_tab"]')) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = '_tab';
                    // Detectar qual tab contem este form
                    var parent = form.closest('.tab-content');
                    if (parent && parent.id) {
                        input.value = parent.id.replace('form-', '');
                    } else {
                        var params = new URLSearchParams(window.location.search);
                        input.value = params.get('tab') || 'users';
                    }
                    form.appendChild(input);
                }
            });
        });

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
