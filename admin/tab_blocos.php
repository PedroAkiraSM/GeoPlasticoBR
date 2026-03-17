<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_blocks') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $blocks_error = 'Token de seguranca invalido.';
    } else {
        $pdo = getDatabaseConnection();
        $page = $_POST['block_page'] ?? '';
        $allowedPages = ['home', 'sobre', 'mapa'];

        if (!in_array($page, $allowedPages)) {
            $blocks_error = 'Pagina invalida.';
        } else {
            $stmt = $pdo->prepare("UPDATE site_blocks SET block_value = :val, block_order = :ord WHERE page = :page AND block_key = :key");

            foreach ($_POST as $key => $value) {
                if (strpos($key, 'block_') === 0 && $key !== 'block_page' && strpos($key, 'order_') !== 6) {
                    $blockKey = substr($key, 6); // remove 'block_' prefix
                    $order = (int) ($_POST['block_order_' . $blockKey] ?? 0);
                    $stmt->execute([':val' => trim($value), ':ord' => $order, ':page' => $page, ':key' => $blockKey]);
                }
            }
            $blocks_success = 'Blocos salvos com sucesso!';
        }
    }
}

$selectedPage = $_GET['block_page'] ?? $_POST['block_page'] ?? 'home';
$pdo = getDatabaseConnection();
$bStmt = $pdo->prepare("SELECT block_key, block_value, block_order FROM site_blocks WHERE page = :page ORDER BY block_order ASC");
$bStmt->execute([':page' => $selectedPage]);
$blocksWithOrder = $bStmt->fetchAll();
$pageLabels = ['home' => 'Pagina Inicial', 'sobre' => 'Sobre', 'mapa' => 'Mapa'];
?>

<div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Blocos de Conteudo</h2>

    <?php if (isset($blocks_error)): ?>
        <div class="mb-4 p-4 bg-red-100 text-red-800 border border-red-200 rounded-lg"><?= htmlspecialchars($blocks_error) ?></div>
    <?php endif; ?>
    <?php if (isset($blocks_success)): ?>
        <div class="mb-4 p-4 bg-green-100 text-green-800 border border-green-200 rounded-lg"><?= htmlspecialchars($blocks_success) ?></div>
    <?php endif; ?>

    <!-- Page selector -->
    <div class="flex gap-2 mb-6">
        <?php foreach ($pageLabels as $pKey => $pLabel): ?>
            <a href="?tab=blocos&block_page=<?= $pKey ?>"
                class="px-4 py-2 rounded-lg text-sm font-semibold <?= $selectedPage === $pKey ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' ?>">
                <?= $pLabel ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($blocksWithOrder)): ?>
        <p class="text-gray-500">Nenhum bloco cadastrado para esta pagina.</p>
    <?php else: ?>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="save_blocks">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="block_page" value="<?= htmlspecialchars($selectedPage) ?>">

            <?php foreach ($blocksWithOrder as $block):
                $key = $block['block_key'];
                $value = $block['block_value'];
                $order = $block['block_order'];
            ?>
                <div class="flex gap-3 items-start border border-gray-100 rounded-lg p-4">
                    <div class="flex-1">
                        <label class="block text-xs font-semibold text-gray-400 mb-1"><?= htmlspecialchars($key) ?></label>
                        <?php if (strlen($value) > 100): ?>
                            <textarea name="block_<?= htmlspecialchars($key) ?>" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm"><?= htmlspecialchars($value) ?></textarea>
                        <?php else: ?>
                            <input type="text" name="block_<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                        <?php endif; ?>
                    </div>
                    <div class="w-16">
                        <label class="block text-xs font-semibold text-gray-400 mb-1">Ordem</label>
                        <input type="number" name="block_order_<?= htmlspecialchars($key) ?>" value="<?= $order ?>"
                            class="w-full px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm text-center">
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition">
                Salvar Blocos
            </button>
        </form>
    <?php endif; ?>
</div>
