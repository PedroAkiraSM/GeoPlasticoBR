<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $settings_error = 'Token de seguranca invalido.';
    } else {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = :val WHERE setting_key = :key");

        $textFields = ['site_name', 'site_description', 'contact_email', 'version_label',
                        'facebook_url', 'instagram_url', 'linkedin_url', 'footer_text'];

        foreach ($textFields as $field) {
            if (isset($_POST[$field])) {
                $stmt->execute([':val' => trim($_POST[$field]), ':key' => $field]);
            }
        }

        // Handle logo upload
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo_file'];
            $allowedMimes = ['image/png', 'image/jpeg'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowedMimes)) {
                $settings_error = 'Formato invalido. Use PNG ou JPG.';
            } elseif ($file['size'] > $maxSize) {
                $settings_error = 'Arquivo muito grande. Maximo 2MB.';
            } else {
                $ext = $mime === 'image/png' ? 'png' : 'jpg';
                $filename = time() . '_logo.' . $ext;
                $dest = __DIR__ . '/../assets/images/uploads/' . $filename;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $stmt->execute([':val' => 'assets/images/uploads/' . $filename, ':key' => 'logo_path']);
                } else {
                    $settings_error = 'Erro ao salvar arquivo.';
                }
            }
        }

        if (!isset($settings_error)) {
            $settings_success = 'Configuracoes salvas com sucesso!';
            getSettings(true);
        }
    }
}

$settings = getSettings();
?>

<div class="bg-white rounded-xl shadow-lg p-6 md:p-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Configuracoes Gerais</h2>

    <?php if (isset($settings_error)): ?>
        <div class="mb-4 p-4 bg-red-100 text-red-800 border border-red-200 rounded-lg"><?= htmlspecialchars($settings_error) ?></div>
    <?php endif; ?>
    <?php if (isset($settings_success)): ?>
        <div class="mb-4 p-4 bg-green-100 text-green-800 border border-green-200 rounded-lg"><?= htmlspecialchars($settings_success) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-8">
        <input type="hidden" name="action" value="save_settings">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

        <!-- Site Identity -->
        <fieldset class="border border-gray-200 rounded-lg p-6">
            <legend class="text-sm font-semibold text-gray-500 px-2">Identidade do Site</legend>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nome do Site</label>
                    <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Descricao</label>
                    <textarea name="site_description" rows="2"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Versao</label>
                    <select name="version_label" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <?php foreach (['Alpha', 'Beta', 'Estavel'] as $v): ?>
                            <option value="<?= $v ?>" <?= ($settings['version_label'] ?? '') === $v ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Logo (PNG ou JPG, max 2MB)</label>
                    <?php if (!empty($settings['logo_path'])): ?>
                        <div class="mb-2">
                            <img src="/<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo atual" class="h-12 rounded bg-gray-100 p-1">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="logo_file" accept="image/png,image/jpeg" class="text-sm text-gray-500">
                </div>
            </div>
        </fieldset>

        <!-- Contact & Social -->
        <fieldset class="border border-gray-200 rounded-lg p-6">
            <legend class="text-sm font-semibold text-gray-500 px-2">Contato e Redes Sociais</legend>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">E-mail de Contato</label>
                    <input type="email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Facebook URL</label>
                        <input type="url" name="facebook_url" value="<?= htmlspecialchars($settings['facebook_url'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="https://facebook.com/...">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Instagram URL</label>
                        <input type="url" name="instagram_url" value="<?= htmlspecialchars($settings['instagram_url'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="https://instagram.com/...">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">LinkedIn URL</label>
                        <input type="url" name="linkedin_url" value="<?= htmlspecialchars($settings['linkedin_url'] ?? '') ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="https://linkedin.com/in/...">
                    </div>
                </div>
            </div>
        </fieldset>

        <!-- Footer -->
        <fieldset class="border border-gray-200 rounded-lg p-6">
            <legend class="text-sm font-semibold text-gray-500 px-2">Rodape</legend>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Texto do Rodape</label>
                <textarea name="footer_text" rows="2"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($settings['footer_text'] ?? '') ?></textarea>
            </div>
        </fieldset>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition">
            Salvar Configuracoes
        </button>
    </form>
</div>
