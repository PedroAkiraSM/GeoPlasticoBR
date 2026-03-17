# Admin/CMS Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make all public-facing content, data categories, measurement units, and concentration thresholds editable through the admin panel — no code changes needed.

**Architecture:** 5 new MySQL tables, 1 helper library (`config/cms.php`), 4 new admin tab files, 1 SQL migration. Existing pages modified to read from DB instead of hardcoded values. String-based linkage (no FK migration).

**Tech Stack:** PHP 8.3, MySQL (PDO), vanilla JS, existing CSS patterns from admin.php.

**Testing approach:** No test framework — verify via SQL queries, curl, and browser checks at each step.

---

## Chunk 1: Database & Helpers

### Task 1: Create migration SQL

**Files:**
- Create: `sql/migration_cms.sql`

- [ ] **Step 1: Write the DDL for all 5 tables**

```sql
-- =============================================
-- GeoPlasticoBR CMS Migration
-- Run once against u129322308_geoPlasticoBr
-- =============================================

-- 1. Site Settings
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('text','image','url','email') NOT NULL DEFAULT 'text',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Site Blocks
CREATE TABLE IF NOT EXISTS site_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page VARCHAR(50) NOT NULL,
    block_key VARCHAR(100) NOT NULL,
    block_value TEXT,
    block_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_page_block (page, block_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Data Types
CREATE TABLE IF NOT EXISTS data_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('ambiente','ecossistema','matriz') NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_category_name (category, name),
    INDEX idx_category_active (category, active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Measurement Units
CREATE TABLE IF NOT EXISTS measurement_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Concentration Thresholds
CREATE TABLE IF NOT EXISTS concentration_thresholds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    level ENUM('baixo','medio','elevado','alto','critico') NOT NULL,
    min_value DECIMAL(15,4) NOT NULL,
    max_value DECIMAL(15,4) NULL,
    color VARCHAR(7) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_unit_level (unit_id, level),
    FOREIGN KEY (unit_id) REFERENCES measurement_units(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

- [ ] **Step 2: Write seed data for site_settings**

Append to `sql/migration_cms.sql`:

```sql
-- =============================================
-- SEED DATA
-- =============================================

-- Site Settings (extracted from header.php, footer.php)
INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_type) VALUES
('site_name', 'GeoPlasticoBR', 'text'),
('site_description', 'Plataforma de mapeamento cientifico de microplasticos nos ecossistemas aquaticos brasileiros.', 'text'),
('logo_path', 'assets/images/logo.png', 'image'),
('favicon_path', 'favicon.svg', 'image'),
('contact_email', 'contato@geoplasticobr.com', 'email'),
('version_label', 'Beta', 'text'),
('facebook_url', '', 'url'),
('instagram_url', '', 'url'),
('linkedin_url', '', 'url'),
('footer_text', 'Dados cientificos verificados por pares | Acesso aberto', 'text');
```

- [ ] **Step 3: Write seed data for site_blocks (home page)**

Append to `sql/migration_cms.sql`:

```sql
-- Site Blocks - Home page (extracted from index.php)
INSERT IGNORE INTO site_blocks (page, block_key, block_value, block_order) VALUES
('home', 'hero_title', 'GEO PLASTICO BR', 1),
('home', 'hero_subtitle', 'Mapeando a poluicao invisivel nos ecossistemas aquaticos brasileiros', 2),
('home', 'hero_cta_primary', 'Explorar Mapa', 3),
('home', 'hero_cta_secondary', 'Sobre o Projeto', 4),
('home', 'problem_tag', 'O Problema', 10),
('home', 'problem_title', 'Microplasticos', 11),
('home', 'problem_description', 'Fragmentos plasticos menores que 5mm que contaminam oceanos, rios e lagos. Invisiveis a olho nu, representam uma das maiores ameacas ambientais da atualidade.', 12),
('home', 'stats_label_1', 'Pontos de Coleta', 13),
('home', 'stats_desc_1', 'Mapeados em todo o Brasil', 14),
('home', 'stats_label_2', 'Ecossistemas', 15),
('home', 'stats_desc_2', 'Tipos de ambientes monitorados', 16),
('home', 'stats_label_3', 'Registros Cientificos', 17),
('home', 'stats_desc_3', 'Dados verificados por pares', 18),
('home', 'feature_tag', 'Ferramenta Principal', 20),
('home', 'feature_title', 'Mapa Interativo', 21),
('home', 'mission_title', 'Democratizar dados cientificos', 30),
('home', 'cta_title', 'Pronto para Explorar?', 40),
('home', 'cta_description', 'Descubra a distribuicao de microplasticos nos ecossistemas aquaticos brasileiros.', 41);
```

- [ ] **Step 4: Write seed data for site_blocks (sobre page)**

Append to `sql/migration_cms.sql`:

```sql
-- Site Blocks - Sobre page (extracted from sobre.php)
INSERT IGNORE INTO site_blocks (page, block_key, block_value, block_order) VALUES
('sobre', 'hero_tag', 'Sobre o Projeto', 1),
('sobre', 'hero_title', 'GeoPlasticoBR', 2),
('sobre', 'hero_subtitle', 'Uma plataforma cientifica de mapeamento de microplasticos nos ecossistemas aquaticos brasileiros.', 3),
('sobre', 'what_title', 'O que e o GeoPlasticoBR?', 10),
('sobre', 'what_description', 'O GeoPlasticoBR e uma plataforma cientifica colaborativa dedicada ao mapeamento e visualizacao de dados sobre microplasticos nos ecossistemas aquaticos brasileiros.', 11),
('sobre', 'obj1_title', 'Centralizar Dados', 20),
('sobre', 'obj1_description', 'Reunir dados dispersos sobre microplasticos em uma unica plataforma acessivel.', 21),
('sobre', 'obj2_title', 'Visualizar Espacialmente', 22),
('sobre', 'obj2_description', 'Permitir a visualizacao geografica dos dados atraves de mapas interativos.', 23),
('sobre', 'obj3_title', 'Democratizar o Acesso', 24),
('sobre', 'obj3_description', 'Tornar dados cientificos acessiveis a pesquisadores, gestores e publico geral.', 25),
('sobre', 'obj4_title', 'Apoiar Politicas Publicas', 26),
('sobre', 'obj4_description', 'Fornecer subsidios para a formulacao de politicas ambientais baseadas em evidencias.', 27),
('sobre', 'methodology_title', 'Metodologia', 30),
('sobre', 'methodology_description', 'Todos os dados sao submetidos a revisao por pares antes de serem publicados na plataforma.', 31);

-- Site Blocks - Mapa page (extracted from mapa.php)
INSERT IGNORE INTO site_blocks (page, block_key, block_value, block_order) VALUES
('mapa', 'brand', 'GeoPlasticoBR', 1),
('mapa', 'page_label', 'Mapa Interativo', 2);
```

- [ ] **Step 5: Write seed data for data_types**

Append to `sql/migration_cms.sql`:

```sql
-- Data Types - Ambiente
INSERT IGNORE INTO data_types (category, name, description) VALUES
('ambiente', 'Doce', 'Ambiente de agua doce'),
('ambiente', 'Marinho', 'Ambiente marinho');

-- Data Types - Ecossistema
INSERT IGNORE INTO data_types (category, name, description) VALUES
('ecossistema', 'Rio', NULL),
('ecossistema', 'Lago', NULL),
('ecossistema', 'Bacia', NULL),
('ecossistema', 'Córrego', NULL),
('ecossistema', 'Praia', NULL),
('ecossistema', 'Estuário', NULL),
('ecossistema', 'Ilha', NULL),
('ecossistema', 'Região costeira', NULL),
('ecossistema', 'Plataforma', NULL),
('ecossistema', 'Oceano aberto', NULL),
('ecossistema', 'Laguna', NULL);

-- Data Types - Matriz
INSERT IGNORE INTO data_types (category, name, description) VALUES
('matriz', 'Sedimento', NULL),
('matriz', 'Água', NULL);
```

- [ ] **Step 6: Write seed data for measurement_units and thresholds**

Append to `sql/migration_cms.sql`:

```sql
-- Measurement Units (matching existing unidade column values)
INSERT IGNORE INTO measurement_units (name, description) VALUES
('part/Kg', 'Particulas por quilograma'),
('part/m³', 'Particulas por metro cubico'),
('part/m²', 'Particulas por metro quadrado'),
('part/L', 'Particulas por litro'),
('part/mL', 'Particulas por mililitro'),
('part/cm²', 'Particulas por centimetro quadrado');

-- Concentration Thresholds for part/Kg (matching current map_v2.js breakpoints)
INSERT IGNORE INTO concentration_thresholds (unit_id, level, min_value, max_value, color) VALUES
((SELECT id FROM measurement_units WHERE name = 'part/Kg'), 'baixo', 0, 1000, '#00CC88'),
((SELECT id FROM measurement_units WHERE name = 'part/Kg'), 'medio', 1000, 3000, '#FFD700'),
((SELECT id FROM measurement_units WHERE name = 'part/Kg'), 'elevado', 3000, 5000, '#FFA500'),
((SELECT id FROM measurement_units WHERE name = 'part/Kg'), 'alto', 5000, 8000, '#FF6600'),
((SELECT id FROM measurement_units WHERE name = 'part/Kg'), 'critico', 8000, NULL, '#CC0000');
```

- [ ] **Step 7: Verify by running migration on server**

```bash
ssh hostinger "cd ~/domains/geoplasticobr.com/public_html && mysql -u u129322308_Pedro_Akira -p u129322308_geoPlasticoBr < sql/migration_cms.sql"
```

Verify:
```bash
ssh hostinger "cd ~/domains/geoplasticobr.com/public_html && mysql -u u129322308_Pedro_Akira -p u129322308_geoPlasticoBr -e 'SHOW TABLES LIKE \"%site%\"; SHOW TABLES LIKE \"%data_types%\"; SHOW TABLES LIKE \"%measurement%\"; SHOW TABLES LIKE \"%concentration%\";'"
```

Expected: 5 new tables listed.

- [ ] **Step 8: Commit**

```bash
git add sql/migration_cms.sql
git commit -m "feat(cms): add migration SQL with DDL and seed data for 5 CMS tables"
```

---

### Task 2: Create CMS helper library

**Files:**
- Create: `config/cms.php`

- [ ] **Step 1: Write getSetting() and getSettings()**

```php
<?php
/**
 * CMS Helper Functions
 * Provides cached access to site settings, blocks, data types, and thresholds.
 */

require_once __DIR__ . '/database.php';

/**
 * Get a single site setting value by key.
 */
function getSetting(string $key, string $default = ''): string {
    $settings = getSettings();
    return $settings[$key] ?? $default;
}

/**
 * Get all site settings as key => value array.
 * Pass $forceReload = true after saving to clear the static cache.
 */
function getSettings(bool $forceReload = false): array {
    static $cache = null;
    if ($cache !== null && !$forceReload) return $cache;

    $pdo = getDatabaseConnection();
    if (!$pdo) return [];

    $stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
    $cache = [];
    while ($row = $stmt->fetch()) {
        $cache[$row['setting_key']] = $row['setting_value'];
    }
    return $cache;
}
```

- [ ] **Step 2: Write getBlocks()**

Append to `config/cms.php`:

```php
/**
 * Get content blocks for a page, ordered by block_order.
 * Returns associative array: block_key => block_value
 */
function getBlocks(string $page): array {
    static $cache = [];
    if (isset($cache[$page])) return $cache[$page];

    $pdo = getDatabaseConnection();
    if (!$pdo) return [];

    $stmt = $pdo->prepare("SELECT block_key, block_value FROM site_blocks WHERE page = :page ORDER BY block_order ASC");
    $stmt->execute([':page' => $page]);
    $cache[$page] = [];
    while ($row = $stmt->fetch()) {
        $cache[$page][$row['block_key']] = $row['block_value'];
    }
    return $cache[$page];
}
```

- [ ] **Step 3: Write getDataTypes()**

Append to `config/cms.php`:

```php
/**
 * Get active data types for a category.
 * Returns array of ['id' => int, 'name' => string, 'description' => string|null]
 */
function getDataTypes(string $category): array {
    static $cache = [];
    if (isset($cache[$category])) return $cache[$category];

    $pdo = getDatabaseConnection();
    if (!$pdo) return [];

    $stmt = $pdo->prepare("SELECT id, name, description FROM data_types WHERE category = :cat AND active = 1 ORDER BY name ASC");
    $stmt->execute([':cat' => $category]);
    $cache[$category] = $stmt->fetchAll();
    return $cache[$category];
}

/**
 * Get ALL data types for a category (including inactive). For admin panel.
 */
function getAllDataTypes(string $category): array {
    $pdo = getDatabaseConnection();
    if (!$pdo) return [];

    $stmt = $pdo->prepare("SELECT id, name, description, active FROM data_types WHERE category = :cat ORDER BY name ASC");
    $stmt->execute([':cat' => $category]);
    return $stmt->fetchAll();
}
```

- [ ] **Step 4: Write getUnitsWithThresholds() and getThresholdColor()**

Append to `config/cms.php`:

```php
/**
 * Get active measurement units with their nested thresholds.
 * Returns: [{ name, description, thresholds: [{ level, min_value, max_value, color }] }]
 */
function getUnitsWithThresholds(): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    $pdo = getDatabaseConnection();
    if (!$pdo) return [];

    $units = $pdo->query("SELECT id, name, description FROM measurement_units WHERE active = 1 ORDER BY name ASC")->fetchAll();

    $stmt = $pdo->prepare("SELECT level, min_value, max_value, color FROM concentration_thresholds WHERE unit_id = :uid ORDER BY min_value ASC");

    $cache = [];
    foreach ($units as $unit) {
        $stmt->execute([':uid' => $unit['id']]);
        $cache[] = [
            'name' => $unit['name'],
            'description' => $unit['description'],
            'thresholds' => $stmt->fetchAll()
        ];
    }
    return $cache;
}

/**
 * Get the hex color for a concentration value given a unit name.
 * Returns gray (#6b7280) if unit unknown or value outside all ranges.
 */
function getThresholdColor(string $unitName, float $value): string {
    $units = getUnitsWithThresholds();
    foreach ($units as $unit) {
        if ($unit['name'] !== $unitName) continue;
        foreach ($unit['thresholds'] as $t) {
            $min = (float) $t['min_value'];
            $max = $t['max_value'] !== null ? (float) $t['max_value'] : PHP_FLOAT_MAX;
            if ($value >= $min && $value < $max) {
                return $t['color'];
            }
        }
    }
    return '#6b7280';
}
```

- [ ] **Step 5: Verify helper functions load without errors**

Upload `config/cms.php` to server and test:

```bash
ssh hostinger "cd ~/domains/geoplasticobr.com/public_html && php -r \"require 'config/cms.php'; echo getSetting('site_name') . PHP_EOL; print_r(getDataTypes('ambiente')); print_r(getUnitsWithThresholds());\""
```

Expected: Outputs "GeoPlasticoBR", array of 2 ambientes, array of units with thresholds.

- [ ] **Step 6: Commit**

```bash
git add config/cms.php
git commit -m "feat(cms): add CMS helper library with cached getters"
```

---

## Chunk 2: Admin Panel Tabs

### Task 3: Add tab navigation to admin.php

**Files:**
- Modify: `admin.php` (lines 184-204 — tab buttons area)

- [ ] **Step 1: Add 4 new tab buttons after existing ones**

In `admin.php`, after the existing tab button for `fish` (around line 202), add:

```html
<button onclick="showTab('configuracoes')" class="tab-btn" id="tab-configuracoes">
    <span>⚙️</span> Configurações
</button>
<button onclick="showTab('blocos')" class="tab-btn" id="tab-blocos">
    <span>📝</span> Blocos de Conteúdo
</button>
<button onclick="showTab('tipos')" class="tab-btn" id="tab-tipos">
    <span>📋</span> Tipos de Dados
</button>
<button onclick="showTab('unidades')" class="tab-btn" id="tab-unidades">
    <span>📏</span> Unidades e Limiares
</button>
```

- [ ] **Step 2: Add PHP includes for new tab content**

At the top of `admin.php`, after the existing `require_once` statements, add:

```php
require_once __DIR__ . '/config/cms.php';
```

After the closing `</div>` of `form-fish` (around line 526), add:

```php
<div id="form-configuracoes" class="tab-content hidden">
    <?php if (file_exists(__DIR__ . '/admin/tab_configuracoes.php')) include __DIR__ . '/admin/tab_configuracoes.php'; ?>
</div>
<div id="form-blocos" class="tab-content hidden">
    <?php if (file_exists(__DIR__ . '/admin/tab_blocos.php')) include __DIR__ . '/admin/tab_blocos.php'; ?>
</div>
<div id="form-tipos" class="tab-content hidden">
    <?php if (file_exists(__DIR__ . '/admin/tab_tipos.php')) include __DIR__ . '/admin/tab_tipos.php'; ?>
</div>
<div id="form-unidades" class="tab-content hidden">
    <?php if (file_exists(__DIR__ . '/admin/tab_unidades.php')) include __DIR__ . '/admin/tab_unidades.php'; ?>
</div>
```

- [ ] **Step 3: Fix CSRF on existing approve/reject forms**

In `admin.php`, find the 4 approve/reject forms (around lines 234, 238, 326, 330) that lack CSRF tokens. Add inside each `<form>`:

```php
<input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
```

And in the PHP handlers at the top (where POST actions are processed), add CSRF validation:

```php
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    $error = 'Token de segurança inválido. Recarregue a página.';
}
```

- [ ] **Step 4: Verify tabs render without errors**

Upload modified `admin.php` to server. Open in browser, click each new tab — should show empty content areas (tab files not yet created). Existing tabs should still work.

- [ ] **Step 5: Commit**

```bash
git add admin.php
git commit -m "feat(cms): add CMS tab navigation and CSRF fix to admin panel"
```

---

### Task 4: Build Settings tab (tab_configuracoes.php)

**Files:**
- Create: `admin/tab_configuracoes.php`
- Create: `assets/images/uploads/` directory (with `.gitkeep`)

- [ ] **Step 1: Create required directories**

```bash
mkdir -p admin
mkdir -p assets/images/uploads
touch assets/images/uploads/.gitkeep
```

- [ ] **Step 2: Write the settings form**

Create `admin/tab_configuracoes.php`:

```php
<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $settings_error = 'Token de segurança inválido.';
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
                $settings_error = 'Formato inválido. Use PNG ou JPG.';
            } elseif ($file['size'] > $maxSize) {
                $settings_error = 'Arquivo muito grande. Máximo 2MB.';
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
            $settings_success = 'Configurações salvas com sucesso!';
            getSettings(true); // Force cache reload
        }
    }
}

$settings = getSettings();
?>

<div class="admin-section">
    <h2 style="color: #00acc1; margin-bottom: 1.5rem;">⚙️ Configurações Gerais</h2>

    <?php if (isset($settings_error)): ?>
        <div style="background: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            <?= htmlspecialchars($settings_error) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($settings_success)): ?>
        <div style="background: #d1fae5; color: #059669; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            <?= htmlspecialchars($settings_success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" style="display: grid; gap: 1.5rem;">
        <input type="hidden" name="action" value="save_settings">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

        <!-- Site Identity -->
        <fieldset style="border: 1px solid #374151; border-radius: 0.5rem; padding: 1.5rem;">
            <legend style="color: #9ca3af; padding: 0 0.5rem;">Identidade do Site</legend>

            <div style="display: grid; gap: 1rem;">
                <label style="color: #d1d5db;">
                    Nome do Site
                    <input type="text" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>"
                        style="width: 100%; padding: 0.5rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.375rem; color: #f3f4f6; margin-top: 0.25rem;">
                </label>

                <label style="color: #d1d5db;">
                    Descrição
                    <textarea name="site_description" rows="2"
                        style="width: 100%; padding: 0.5rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.375rem; color: #f3f4f6; margin-top: 0.25rem;"><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                </label>

                <label style="color: #d1d5db;">
                    Versão
                    <select name="version_label" style="width: 100%; padding: 0.5rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.375rem; color: #f3f4f6; margin-top: 0.25rem;">
                        <?php foreach (['Alpha', 'Beta', 'Estável'] as $v): ?>
                            <option value="<?= $v ?>" <?= ($settings['version_label'] ?? '') === $v ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div style="color: #d1d5db;">
                    Logo (PNG ou JPG, máx 2MB)
                    <?php if (!empty($settings['logo_path'])): ?>
                        <div style="margin: 0.5rem 0;">
                            <img src="/<?= htmlspecialchars($settings['logo_path']) ?>" alt="Logo atual" style="max-height: 60px; border-radius: 0.375rem; background: #374151; padding: 0.25rem;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="logo_file" accept="image/png,image/jpeg"
                        style="margin-top: 0.25rem; color: #9ca3af;">
                </div>
            </div>
        </fieldset>

        <!-- Contact & Social -->
        <fieldset style="border: 1px solid #374151; border-radius: 0.5rem; padding: 1.5rem;">
            <legend style="color: #9ca3af; padding: 0 0.5rem;">Contato e Redes Sociais</legend>

            <div style="display: grid; gap: 1rem;">
                <label style="color: #d1d5db;">
                    E-mail de Contato
                    <input type="email" name="contact_email" value="<?= htmlspecialchars($settings['contact_email'] ?? '') ?>"
                        style="width: 100%; padding: 0.5rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.375rem; color: #f3f4f6; margin-top: 0.25rem;">
                </label>

                <label style="color: #d1d5db;">
                    Facebook URL
                    <input type="url" name="facebook_url" value="<?= htmlspecialchars($settings['facebook_url'] ?? '') ?>"
                        style="width: 100%; padding: 0.5rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.375rem; color: #f3f4f6; margin-top: 0.25rem;" placeholder="https://facebook.com/...">
                </label>

                <label style="color: #d1d5db;">
                    Instagram URL
                    <input type="url" name="instagram_url" value="<?= htmlspecialchars($settings['instagram_url'] ?? '') ?>"
                        style="width: 100%; padding: 0.5rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.375rem; color: #f3f4f6; margin-top: 0.25rem;" placeholder="https://instagram.com/...">
                </label>

                <label style="color: #d1d5db;">
                    LinkedIn URL
                    <input type="url" name="linkedin_url" value="<?= htmlspecialchars($settings['linkedin_url'] ?? '') ?>"
                        style="width: 100%; padding: 0.5rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.375rem; color: #f3f4f6; margin-top: 0.25rem;" placeholder="https://linkedin.com/in/...">
                </label>
            </div>
        </fieldset>

        <!-- Footer -->
        <fieldset style="border: 1px solid #374151; border-radius: 0.5rem; padding: 1.5rem;">
            <legend style="color: #9ca3af; padding: 0 0.5rem;">Rodapé</legend>

            <label style="color: #d1d5db;">
                Texto do Rodapé
                <textarea name="footer_text" rows="2"
                    style="width: 100%; padding: 0.5rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.375rem; color: #f3f4f6; margin-top: 0.25rem;"><?= htmlspecialchars($settings['footer_text'] ?? '') ?></textarea>
            </label>
        </fieldset>

        <button type="submit" style="padding: 0.75rem 2rem; background: #00acc1; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600; justify-self: start;">
            Salvar Configurações
        </button>
    </form>
</div>
```

- [ ] **Step 3: Verify — upload, open Configurações tab, fill a field, save**

Expected: Form renders with seeded values, save updates DB, page reloads with new values.

- [ ] **Step 4: Commit**

```bash
git add admin/tab_configuracoes.php assets/images/uploads/.gitkeep
git commit -m "feat(cms): add Settings admin tab with logo upload"
```

---

### Task 5: Build Content Blocks tab (tab_blocos.php)

**Files:**
- Create: `admin/tab_blocos.php`

- [ ] **Step 1: Write the blocks editor**

Create `admin/tab_blocos.php`:

```php
<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_blocks') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $blocks_error = 'Token de segurança inválido.';
    } else {
        $pdo = getDatabaseConnection();
        $page = $_POST['block_page'] ?? '';
        $allowedPages = ['home', 'sobre', 'mapa'];

        if (!in_array($page, $allowedPages)) {
            $blocks_error = 'Página inválida.';
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
// Load blocks with order for admin editing
$pdo = getDatabaseConnection();
$bStmt = $pdo->prepare("SELECT block_key, block_value, block_order FROM site_blocks WHERE page = :page ORDER BY block_order ASC");
$bStmt->execute([':page' => $selectedPage]);
$blocksWithOrder = $bStmt->fetchAll();
$pageLabels = ['home' => 'Página Inicial', 'sobre' => 'Sobre', 'mapa' => 'Mapa'];
?>

<div class="admin-section">
    <h2 style="color: #00acc1; margin-bottom: 1.5rem;">📝 Blocos de Conteúdo</h2>

    <?php if (isset($blocks_error)): ?>
        <div style="background: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            <?= htmlspecialchars($blocks_error) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($blocks_success)): ?>
        <div style="background: #d1fae5; color: #059669; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            <?= htmlspecialchars($blocks_success) ?>
        </div>
    <?php endif; ?>

    <!-- Page selector -->
    <div style="margin-bottom: 1.5rem; display: flex; gap: 0.5rem;">
        <?php foreach ($pageLabels as $pKey => $pLabel): ?>
            <a href="?tab=blocos&block_page=<?= $pKey ?>"
                style="padding: 0.5rem 1rem; border-radius: 0.375rem; text-decoration: none;
                <?= $selectedPage === $pKey ? 'background: #00acc1; color: white;' : 'background: #1f2937; color: #9ca3af; border: 1px solid #374151;' ?>">
                <?= $pLabel ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($blocksWithOrder)): ?>
        <p style="color: #9ca3af;">Nenhum bloco cadastrado para esta página.</p>
    <?php else: ?>
        <form method="POST" style="display: grid; gap: 1rem;">
            <input type="hidden" name="action" value="save_blocks">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <input type="hidden" name="block_page" value="<?= htmlspecialchars($selectedPage) ?>">

            <?php foreach ($blocksWithOrder as $block):
                $key = $block['block_key'];
                $value = $block['block_value'];
                $order = $block['block_order'];
            ?>
                <div style="display: flex; gap: 0.5rem; align-items: start;">
                    <div style="flex: 1;">
                        <label style="color: #d1d5db;">
                            <span style="font-size: 0.875rem; color: #6b7280;"><?= htmlspecialchars($key) ?></span>
                            <?php if (strlen($value) > 100): ?>
                                <textarea name="block_<?= htmlspecialchars($key) ?>" rows="3"
                                    style="width: 100%; padding: 0.5rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.375rem; color: #f3f4f6; margin-top: 0.25rem;"><?= htmlspecialchars($value) ?></textarea>
                            <?php else: ?>
                                <input type="text" name="block_<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>"
                                    style="width: 100%; padding: 0.5rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.375rem; color: #f3f4f6; margin-top: 0.25rem;">
                            <?php endif; ?>
                        </label>
                    </div>
                    <div style="width: 60px;">
                        <label style="color: #6b7280; font-size: 0.75rem;">Ordem</label>
                        <input type="number" name="block_order_<?= htmlspecialchars($key) ?>" value="<?= $order ?>"
                            style="width: 100%; padding: 0.5rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.375rem; color: #f3f4f6;">
                    </div>
                </div>
            <?php endforeach; ?>

            <button type="submit" style="padding: 0.75rem 2rem; background: #00acc1; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 600; justify-self: start;">
                Salvar Blocos
            </button>
        </form>
    <?php endif; ?>
</div>
```

- [ ] **Step 2: Verify — open Blocos tab, switch pages, edit a block, save**

Expected: Blocks for `home` show by default with seeded values. Switching to `sobre` shows those blocks. Editing and saving persists.

- [ ] **Step 3: Commit**

```bash
git add admin/tab_blocos.php
git commit -m "feat(cms): add Content Blocks admin tab"
```

---

### Task 6: Build Data Types tab (tab_tipos.php)

**Files:**
- Create: `admin/tab_tipos.php`

- [ ] **Step 1: Write the data types CRUD**

Create `admin/tab_tipos.php`:

```php
<?php
$pdo = getDatabaseConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipos_action'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $tipos_error = 'Token de segurança inválido.';
    } else {
        $action = $_POST['tipos_action'];

        if ($action === 'add') {
            $cat = $_POST['category'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '') ?: null;

            if ($name === '') {
                $tipos_error = 'Nome é obrigatório.';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO data_types (category, name, description) VALUES (:cat, :name, :desc)");
                    $stmt->execute([':cat' => $cat, ':name' => $name, ':desc' => $desc]);
                    $tipos_success = "Tipo '$name' adicionado.";
                } catch (PDOException $e) {
                    $tipos_error = str_contains($e->getMessage(), 'Duplicate') ? 'Este tipo já existe nesta categoria.' : 'Erro ao salvar.';
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
            // Check linked records
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
                    $tipos_error = "Não é possível excluir: $linkedCount registro(s) vinculado(s). Use desativar.";
                } else {
                    $pdo->prepare("DELETE FROM data_types WHERE id = :id")->execute([':id' => $id]);
                    $tipos_success = 'Tipo excluído.';
                }
            }
        }

        if ($action === 'edit') {
            $id = (int) ($_POST['type_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '') ?: null;

            if ($name === '') {
                $tipos_error = 'Nome é obrigatório.';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE data_types SET name = :name, description = :desc WHERE id = :id");
                    $stmt->execute([':name' => $name, ':desc' => $desc, ':id' => $id]);
                    $tipos_success = 'Tipo atualizado.';
                } catch (PDOException $e) {
                    $tipos_error = str_contains($e->getMessage(), 'Duplicate') ? 'Este nome já existe nesta categoria.' : 'Erro ao salvar.';
                }
            }
        }
    }
}

$selectedCat = $_GET['cat'] ?? 'ambiente';
$categories = [
    'ambiente' => 'Ambientes',
    'ecossistema' => 'Ecossistemas',
    'matriz' => 'Matrizes'
];
$types = getAllDataTypes($selectedCat);

// Count linked records for each type
$colMap = ['ambiente' => 'tipo_ambiente', 'ecossistema' => 'ecossistema', 'matriz' => 'matriz'];
$col = $colMap[$selectedCat] ?? 'tipo_ambiente';
?>

<div class="admin-section">
    <h2 style="color: #00acc1; margin-bottom: 1.5rem;">📋 Tipos de Dados</h2>

    <?php if (isset($tipos_error)): ?>
        <div style="background: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            <?= htmlspecialchars($tipos_error) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($tipos_success)): ?>
        <div style="background: #d1fae5; color: #059669; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            <?= htmlspecialchars($tipos_success) ?>
        </div>
    <?php endif; ?>

    <!-- Category selector -->
    <div style="margin-bottom: 1.5rem; display: flex; gap: 0.5rem;">
        <?php foreach ($categories as $cKey => $cLabel): ?>
            <a href="?tab=tipos&cat=<?= $cKey ?>"
                style="padding: 0.5rem 1rem; border-radius: 0.375rem; text-decoration: none;
                <?= $selectedCat === $cKey ? 'background: #00acc1; color: white;' : 'background: #1f2937; color: #9ca3af; border: 1px solid #374151;' ?>">
                <?= $cLabel ?>
                <span style="background: #374151; padding: 0.125rem 0.5rem; border-radius: 999px; font-size: 0.75rem; margin-left: 0.25rem;">
                    <?= count(array_filter($types, fn($t) => $t['active'])) ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Add new -->
    <form method="POST" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; align-items: end;">
        <input type="hidden" name="tipos_action" value="add">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="category" value="<?= $selectedCat ?>">

        <div style="flex: 1;">
            <label style="color: #6b7280; font-size: 0.75rem;">Nome</label>
            <input type="text" name="name" required placeholder="Novo tipo..."
                style="width: 100%; padding: 0.5rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.375rem; color: #f3f4f6;">
        </div>
        <div style="flex: 2;">
            <label style="color: #6b7280; font-size: 0.75rem;">Descrição (opcional)</label>
            <input type="text" name="description" placeholder="Descrição..."
                style="width: 100%; padding: 0.5rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.375rem; color: #f3f4f6;">
        </div>
        <button type="submit" style="padding: 0.5rem 1rem; background: #059669; color: white; border: none; border-radius: 0.375rem; cursor: pointer; white-space: nowrap;">
            + Adicionar
        </button>
    </form>

    <!-- Types table -->
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 1px solid #374151; color: #9ca3af; font-size: 0.875rem;">
                <th style="text-align: left; padding: 0.75rem;">Nome</th>
                <th style="text-align: left; padding: 0.75rem;">Descrição</th>
                <th style="text-align: center; padding: 0.75rem;">Registros</th>
                <th style="text-align: center; padding: 0.75rem;">Status</th>
                <th style="text-align: right; padding: 0.75rem;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($types as $type):
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM microplastics_sediment WHERE $col = :name");
                $countStmt->execute([':name' => $type['name']]);
                $linkedCount = (int) $countStmt->fetchColumn();
            ?>
                <tr style="border-bottom: 1px solid #1f2937;">
                    <td style="padding: 0.75rem; color: #f3f4f6;"><?= htmlspecialchars($type['name']) ?></td>
                    <td style="padding: 0.75rem; color: #9ca3af;"><?= htmlspecialchars($type['description'] ?? '—') ?></td>
                    <td style="padding: 0.75rem; text-align: center; color: #6b7280;"><?= $linkedCount ?></td>
                    <td style="padding: 0.75rem; text-align: center;">
                        <span style="padding: 0.125rem 0.5rem; border-radius: 999px; font-size: 0.75rem;
                            <?= $type['active'] ? 'background: #064e3b; color: #34d399;' : 'background: #7f1d1d; color: #fca5a5;' ?>">
                            <?= $type['active'] ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </td>
                    <td style="padding: 0.75rem; text-align: right;">
                        <div style="display: flex; gap: 0.25rem; justify-content: flex-end;">
                            <!-- Edit (inline toggle) -->
                            <button type="button" onclick="document.getElementById('edit-row-<?= $type['id'] ?>').classList.toggle('hidden')" title="Editar"
                                style="padding: 0.375rem 0.5rem; background: #374151; color: #d1d5db; border: none; border-radius: 0.25rem; cursor: pointer; font-size: 0.75rem;">
                                ✏️
                            </button>

                            <!-- Toggle active -->
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="tipos_action" value="toggle">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                <input type="hidden" name="type_id" value="<?= $type['id'] ?>">
                                <input type="hidden" name="new_status" value="<?= $type['active'] ? 0 : 1 ?>">
                                <button type="submit" title="<?= $type['active'] ? 'Desativar' : 'Reativar' ?>"
                                    style="padding: 0.375rem 0.5rem; background: #374151; color: #d1d5db; border: none; border-radius: 0.25rem; cursor: pointer; font-size: 0.75rem;">
                                    <?= $type['active'] ? '⏸' : '▶' ?>
                                </button>
                            </form>

                            <?php if ($linkedCount === 0): ?>
                                <!-- Delete (only if no linked records) -->
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Excluir tipo <?= htmlspecialchars(addslashes($type['name'])) ?>?')">
                                    <input type="hidden" name="tipos_action" value="delete">
                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                    <input type="hidden" name="type_id" value="<?= $type['id'] ?>">
                                    <button type="submit" title="Excluir"
                                        style="padding: 0.375rem 0.5rem; background: #7f1d1d; color: #fca5a5; border: none; border-radius: 0.25rem; cursor: pointer; font-size: 0.75rem;">
                                        🗑
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <!-- Inline edit row (hidden by default) -->
                <tr id="edit-row-<?= $type['id'] ?>" class="hidden">
                    <td colspan="5" style="padding: 0.75rem; background: #111827;">
                        <form method="POST" style="display: flex; gap: 0.5rem; align-items: end;">
                            <input type="hidden" name="tipos_action" value="edit">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="hidden" name="type_id" value="<?= $type['id'] ?>">
                            <div style="flex: 1;">
                                <label style="color: #6b7280; font-size: 0.75rem;">Nome</label>
                                <input type="text" name="name" value="<?= htmlspecialchars($type['name']) ?>" required
                                    style="width: 100%; padding: 0.375rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.25rem; color: #f3f4f6;">
                            </div>
                            <div style="flex: 2;">
                                <label style="color: #6b7280; font-size: 0.75rem;">Descrição</label>
                                <input type="text" name="description" value="<?= htmlspecialchars($type['description'] ?? '') ?>"
                                    style="width: 100%; padding: 0.375rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.25rem; color: #f3f4f6;">
                            </div>
                            <button type="submit" style="padding: 0.375rem 0.75rem; background: #00acc1; color: white; border: none; border-radius: 0.25rem; cursor: pointer;">Salvar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

- [ ] **Step 2: Verify — open Tipos tab, switch categories, add/deactivate/delete types**

Expected: Seeded types appear. Types with linked records show count and only allow deactivate. Types with 0 records allow delete.

- [ ] **Step 3: Commit**

```bash
git add admin/tab_tipos.php
git commit -m "feat(cms): add Data Types admin tab with soft delete"
```

---

### Task 7: Build Units & Thresholds tab (tab_unidades.php)

**Files:**
- Create: `admin/tab_unidades.php`

- [ ] **Step 1: Write the units + thresholds editor**

Create `admin/tab_unidades.php`:

```php
<?php
$pdo = getDatabaseConnection();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unidades_action'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $unit_error = 'Token de segurança inválido.';
    } else {
        $action = $_POST['unidades_action'];

        if ($action === 'add_unit') {
            $name = trim($_POST['unit_name'] ?? '');
            $desc = trim($_POST['unit_description'] ?? '') ?: null;

            if ($name === '') {
                $unit_error = 'Nome é obrigatório.';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO measurement_units (name, description) VALUES (:name, :desc)");
                    $stmt->execute([':name' => $name, ':desc' => $desc]);
                    $unitId = $pdo->lastInsertId();

                    // Create empty threshold rows
                    $levels = ['baixo', 'medio', 'elevado', 'alto', 'critico'];
                    $defaults = [
                        ['baixo', 0, 1000, '#00CC88'],
                        ['medio', 1000, 3000, '#FFD700'],
                        ['elevado', 3000, 5000, '#FFA500'],
                        ['alto', 5000, 8000, '#FF6600'],
                        ['critico', 8000, null, '#CC0000'],
                    ];
                    $ins = $pdo->prepare("INSERT IGNORE INTO concentration_thresholds (unit_id, level, min_value, max_value, color) VALUES (:uid, :lvl, :min, :max, :col)");
                    foreach ($defaults as $d) {
                        $ins->execute([':uid' => $unitId, ':lvl' => $d[0], ':min' => $d[1], ':max' => $d[2], ':col' => $d[3]]);
                    }
                    $unit_success = "Unidade '$name' adicionada com limiares padrão.";
                } catch (PDOException $e) {
                    $unit_error = str_contains($e->getMessage(), 'Duplicate') ? 'Esta unidade já existe.' : 'Erro ao salvar.';
                }
            }
        }

        if ($action === 'save_thresholds') {
            $unitId = (int) ($_POST['unit_id'] ?? 0);
            $levels = ['baixo', 'medio', 'elevado', 'alto', 'critico'];

            $stmt = $pdo->prepare("UPDATE concentration_thresholds SET min_value = :min, max_value = :max, color = :col WHERE unit_id = :uid AND level = :lvl");

            foreach ($levels as $lvl) {
                $min = $_POST["thresh_{$lvl}_min"] ?? 0;
                $max = $_POST["thresh_{$lvl}_max"] ?? null;
                $color = $_POST["thresh_{$lvl}_color"] ?? '#6b7280';

                if ($max === '' || $max === null) $max = null;

                $stmt->execute([
                    ':min' => (float) $min,
                    ':max' => $max !== null ? (float) $max : null,
                    ':col' => $color,
                    ':uid' => $unitId,
                    ':lvl' => $lvl,
                ]);
            }
            $unit_success = 'Limiares atualizados.';
        }

        if ($action === 'toggle_unit') {
            $id = (int) ($_POST['unit_id'] ?? 0);
            $newStatus = (int) ($_POST['new_status'] ?? 1);
            $pdo->prepare("UPDATE measurement_units SET active = :s WHERE id = :id")->execute([':s' => $newStatus, ':id' => $id]);
            $unit_success = $newStatus ? 'Unidade reativada.' : 'Unidade desativada.';
        }
    }
}

// Load all units (including inactive) for admin
$units = $pdo->query("SELECT * FROM measurement_units ORDER BY active DESC, name ASC")->fetchAll();
$thresholdStmt = $pdo->prepare("SELECT * FROM concentration_thresholds WHERE unit_id = :uid ORDER BY min_value ASC");

$expandedUnit = isset($_POST['unit_id']) ? (int) $_POST['unit_id'] : null;
$levelLabels = ['baixo' => 'Baixo', 'medio' => 'Médio', 'elevado' => 'Elevado', 'alto' => 'Alto', 'critico' => 'Crítico'];
?>

<div class="admin-section">
    <h2 style="color: #00acc1; margin-bottom: 1.5rem;">📏 Unidades de Medida e Limiares</h2>

    <?php if (isset($unit_error)): ?>
        <div style="background: #fee2e2; color: #dc2626; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            <?= htmlspecialchars($unit_error) ?>
        </div>
    <?php endif; ?>
    <?php if (isset($unit_success)): ?>
        <div style="background: #d1fae5; color: #059669; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
            <?= htmlspecialchars($unit_success) ?>
        </div>
    <?php endif; ?>

    <!-- Add new unit -->
    <form method="POST" style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; align-items: end;">
        <input type="hidden" name="unidades_action" value="add_unit">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

        <div style="flex: 1;">
            <label style="color: #6b7280; font-size: 0.75rem;">Nome da Unidade</label>
            <input type="text" name="unit_name" required placeholder="ex: part/Kg"
                style="width: 100%; padding: 0.5rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.375rem; color: #f3f4f6;">
        </div>
        <div style="flex: 2;">
            <label style="color: #6b7280; font-size: 0.75rem;">Descrição (opcional)</label>
            <input type="text" name="unit_description" placeholder="Descrição..."
                style="width: 100%; padding: 0.5rem; background: #1f2937; border: 1px solid #374151; border-radius: 0.375rem; color: #f3f4f6;">
        </div>
        <button type="submit" style="padding: 0.5rem 1rem; background: #059669; color: white; border: none; border-radius: 0.375rem; cursor: pointer; white-space: nowrap;">
            + Adicionar
        </button>
    </form>

    <!-- Units list -->
    <?php foreach ($units as $unit):
        $thresholdStmt->execute([':uid' => $unit['id']]);
        $thresholds = $thresholdStmt->fetchAll();
        $isExpanded = ($expandedUnit === (int) $unit['id']);
    ?>
        <div style="border: 1px solid #374151; border-radius: 0.5rem; margin-bottom: 0.75rem; overflow: hidden;">
            <!-- Unit header -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: #1f2937; cursor: pointer;"
                onclick="this.nextElementSibling.classList.toggle('hidden')">
                <div>
                    <span style="color: #f3f4f6; font-weight: 600;"><?= htmlspecialchars($unit['name']) ?></span>
                    <span style="color: #6b7280; margin-left: 0.5rem;"><?= htmlspecialchars($unit['description'] ?? '') ?></span>
                    <?php if (!$unit['active']): ?>
                        <span style="padding: 0.125rem 0.5rem; border-radius: 999px; font-size: 0.75rem; background: #7f1d1d; color: #fca5a5; margin-left: 0.5rem;">Inativo</span>
                    <?php endif; ?>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <!-- Color preview -->
                    <div style="display: flex; gap: 2px;">
                        <?php foreach ($thresholds as $t): ?>
                            <div style="width: 20px; height: 12px; background: <?= htmlspecialchars($t['color']) ?>; border-radius: 2px;" title="<?= $levelLabels[$t['level']] ?? $t['level'] ?>"></div>
                        <?php endforeach; ?>
                    </div>
                    <!-- Toggle -->
                    <form method="POST" style="display: inline;" onclick="event.stopPropagation()">
                        <input type="hidden" name="unidades_action" value="toggle_unit">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <input type="hidden" name="unit_id" value="<?= $unit['id'] ?>">
                        <input type="hidden" name="new_status" value="<?= $unit['active'] ? 0 : 1 ?>">
                        <button type="submit" title="<?= $unit['active'] ? 'Desativar' : 'Reativar' ?>"
                            style="padding: 0.25rem 0.5rem; background: #374151; color: #d1d5db; border: none; border-radius: 0.25rem; cursor: pointer; font-size: 0.75rem;">
                            <?= $unit['active'] ? '⏸' : '▶' ?>
                        </button>
                    </form>
                    <span style="color: #6b7280;">▼</span>
                </div>
            </div>

            <!-- Thresholds (expandable) -->
            <div class="<?= $isExpanded ? '' : 'hidden' ?>" style="padding: 1rem; border-top: 1px solid #374151;">
                <form method="POST">
                    <input type="hidden" name="unidades_action" value="save_thresholds">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="unit_id" value="<?= $unit['id'] ?>">

                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="color: #9ca3af; font-size: 0.75rem;">
                                <th style="text-align: left; padding: 0.5rem;">Nível</th>
                                <th style="text-align: left; padding: 0.5rem;">Mín</th>
                                <th style="text-align: left; padding: 0.5rem;">Máx</th>
                                <th style="text-align: left; padding: 0.5rem;">Cor</th>
                                <th style="text-align: left; padding: 0.5rem;">Preview</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($thresholds as $t): ?>
                                <tr>
                                    <td style="padding: 0.5rem; color: #d1d5db;"><?= $levelLabels[$t['level']] ?? $t['level'] ?></td>
                                    <td style="padding: 0.5rem;">
                                        <input type="number" step="0.0001" name="thresh_<?= $t['level'] ?>_min" value="<?= $t['min_value'] ?>"
                                            style="width: 100px; padding: 0.375rem; background: #111827; border: 1px solid #374151; border-radius: 0.25rem; color: #f3f4f6;">
                                    </td>
                                    <td style="padding: 0.5rem;">
                                        <input type="number" step="0.0001" name="thresh_<?= $t['level'] ?>_max" value="<?= $t['max_value'] ?? '' ?>" placeholder="∞"
                                            style="width: 100px; padding: 0.375rem; background: #111827; border: 1px solid #374151; border-radius: 0.25rem; color: #f3f4f6;">
                                    </td>
                                    <td style="padding: 0.5rem;">
                                        <input type="color" name="thresh_<?= $t['level'] ?>_color" value="<?= htmlspecialchars($t['color']) ?>"
                                            style="width: 40px; height: 32px; padding: 0; border: 1px solid #374151; border-radius: 0.25rem; cursor: pointer;">
                                    </td>
                                    <td style="padding: 0.5rem;">
                                        <div style="width: 40px; height: 20px; background: <?= htmlspecialchars($t['color']) ?>; border-radius: 4px;"></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <button type="submit" style="margin-top: 1rem; padding: 0.5rem 1rem; background: #00acc1; color: white; border: none; border-radius: 0.375rem; cursor: pointer;">
                        Salvar Limiares
                    </button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
</div>
```

- [ ] **Step 2: Verify — open Unidades tab, expand a unit, change colors/values, save**

Expected: 6 seeded units with part/Kg having 5 thresholds. Expand shows threshold editor. Color picker works. Save persists.

- [ ] **Step 3: Commit**

```bash
git add admin/tab_unidades.php
git commit -m "feat(cms): add Units & Thresholds admin tab with color picker"
```

---

## Chunk 3: Public Pages Integration

### Task 8: Integrate CMS into header and footer

**Files:**
- Modify: `includes/header.php`
- Modify: `includes/footer.php`

- [ ] **Step 1: Add CMS require to header.php**

At the top of `includes/header.php`, after any existing `require_once` statements, add:

```php
<?php
require_once __DIR__ . '/../config/cms.php';
$_siteSettings = getSettings();
?>
```

- [ ] **Step 2: Replace hardcoded values in header.php**

Replace these hardcoded values (preserving surrounding HTML):

| Line | Old | New |
|------|-----|-----|
| ~6 | `"GeoPlasticoBR"` (page title fallback) | `<?= htmlspecialchars($_siteSettings['site_name'] ?? 'GeoPlasticoBR') ?>` |
| ~7 | hardcoded meta description | `<?= htmlspecialchars($_siteSettings['site_description'] ?? '') ?>` |
| ~41 | `"GeoPlasticoBR"` (brand) | `<?= htmlspecialchars($_siteSettings['site_name'] ?? 'GeoPlasticoBR') ?>` |

**Note:** The current header has no `<img>` logo tag — only the text brand name. If `logo_path` is set, optionally add an `<img>` before the brand text:
```php
<?php if (!empty($_siteSettings['logo_path'])): ?>
    <img src="/<?= htmlspecialchars($_siteSettings['logo_path']) ?>" alt="Logo" style="height: 32px; margin-right: 0.5rem;">
<?php endif; ?>
```

- [ ] **Step 3: Replace hardcoded values in footer.php**

In `includes/footer.php`, add at top:

```php
<?php
if (!function_exists('getSetting')) {
    require_once __DIR__ . '/../config/cms.php';
}
$_siteSettings = $_siteSettings ?? getSettings();
?>
```

Replace:

| Line | Old | New |
|------|-----|-----|
| ~6 | `"GeoPlasticoBR"` (brand) | `<?= htmlspecialchars($_siteSettings['site_name'] ?? 'GeoPlasticoBR') ?>` |
| ~7 | hardcoded description | `<?= htmlspecialchars($_siteSettings['site_description'] ?? '') ?>` |
| ~25 | `contato@geoplasticobr.com` | `<?= htmlspecialchars($_siteSettings['contact_email'] ?? '') ?>` |
| ~29 | `"GeoPlasticoBR"` in copyright | `<?= htmlspecialchars($_siteSettings['site_name'] ?? 'GeoPlasticoBR') ?>` |
| ~30 | hardcoded credits text | `<?= htmlspecialchars($_siteSettings['footer_text'] ?? '') ?>` |

- [ ] **Step 4: Verify — load homepage, check header brand and footer text**

Expected: Same appearance. Change a setting in admin → reload → updated.

- [ ] **Step 5: Commit**

```bash
git add includes/header.php includes/footer.php
git commit -m "feat(cms): integrate site settings into header and footer"
```

---

### Task 9: Integrate CMS blocks into index.php

**Files:**
- Modify: `index.php`

- [ ] **Step 1: Load blocks at top of index.php**

After the existing PHP block at top of `index.php` (after the DB queries for stats), add:

```php
require_once __DIR__ . '/config/cms.php';
$blocks = getBlocks('home');
```

- [ ] **Step 2: Replace hardcoded text with block lookups**

Replace each hardcoded text using the pattern `<?= htmlspecialchars($blocks['key'] ?? 'fallback') ?>`:

| Line | Block Key | Fallback |
|------|-----------|----------|
| ~35-37 | `hero_title` | `GEO PLASTICO BR` |
| ~39 | `hero_subtitle` | current text |
| ~47 | `hero_cta_primary` | `Explorar Mapa` |
| ~49 | `hero_cta_secondary` | `Sobre o Projeto` |
| ~63 | `problem_tag` | `O Problema` |
| ~64 | `problem_title` | `Microplasticos` |
| ~65-68 | `problem_description` | current text |
| ~74 | `stats_label_1` | `Pontos de Coleta` |
| ~96 | `feature_tag` | `Ferramenta Principal` |
| ~97 | `feature_title` | `Mapa Interativo` |
| ~151 | `mission_title` | `Democratizar dados cientificos` |
| ~192 | `cta_title` | `Pronto para Explorar?` |
| ~193 | `cta_description` | current text |

- [ ] **Step 3: Verify — load homepage, compare visually to before**

Expected: Identical appearance. Edit a block in admin → reload → text updated.

- [ ] **Step 4: Commit**

```bash
git add index.php
git commit -m "feat(cms): integrate content blocks into homepage"
```

---

### Task 10: Integrate CMS blocks into sobre.php

**Files:**
- Modify: `sobre.php`

- [ ] **Step 1: Load blocks at top**

After session_start and any existing PHP:

```php
require_once __DIR__ . '/config/cms.php';
$blocks = getBlocks('sobre');
```

- [ ] **Step 2: Replace hardcoded text with block lookups**

Same pattern as index.php:

| Line | Block Key | Fallback |
|------|-----------|----------|
| ~12 | `hero_tag` | `Sobre o Projeto` |
| ~13 | `hero_title` | `GeoPlasticoBR` |
| ~14 | `hero_subtitle` | current text |
| ~29 | `what_title` | current text |
| ~30-40 | `what_description` | current text |
| ~54 | `obj1_title` | `Centralizar Dados` |
| etc. for obj2, obj3, obj4 | |
| ~83 | `methodology_title` | `Metodologia` |
| ~84-93 | `methodology_description` | current text |

- [ ] **Step 3: Verify**

Expected: Identical appearance. Edit in admin → text updated on sobre page.

- [ ] **Step 4: Commit**

```bash
git add sobre.php
git commit -m "feat(cms): integrate content blocks into about page"
```

---

### Task 11: Integrate dynamic data types into contribuir.php

**Files:**
- Modify: `contribuir.php`

- [ ] **Step 1: Load data types at top**

After existing PHP:

```php
require_once __DIR__ . '/config/cms.php';
$ambientes = getDataTypes('ambiente');
$ecossistemas = getDataTypes('ecossistema');
$matrizes = getDataTypes('matriz');
$unidades = getUnitsWithThresholds(); // For unit select options
```

- [ ] **Step 2: Replace hardcoded select options**

Replace the hardcoded `<option>` tags for tipo_ambiente (lines ~99-103):

```php
<?php foreach ($ambientes as $a): ?>
    <option value="<?= htmlspecialchars($a['name']) ?>"><?= htmlspecialchars($a['name']) ?></option>
<?php endforeach; ?>
```

Same for ecossistema (lines ~107-120):
```php
<?php foreach ($ecossistemas as $e): ?>
    <option value="<?= htmlspecialchars($e['name']) ?>"><?= htmlspecialchars($e['name']) ?></option>
<?php endforeach; ?>
```

Same for matriz (lines ~126-130):
```php
<?php foreach ($matrizes as $m): ?>
    <option value="<?= htmlspecialchars($m['name']) ?>"><?= htmlspecialchars($m['name']) ?></option>
<?php endforeach; ?>
```

Same for unidade (lines ~134-142):
```php
<?php foreach ($unidades as $u): ?>
    <option value="<?= htmlspecialchars($u['name']) ?>"><?= htmlspecialchars($u['name']) ?></option>
<?php endforeach; ?>
```

- [ ] **Step 3: Also update admin.php sediment form selects**

In `admin.php`, replace the hardcoded `<option>` tags in the sediment form (lines ~354-373 for ecossistema, ~412-419 for unidade) with the same dynamic pattern. Add at top of admin.php (if not already added):

```php
require_once __DIR__ . '/config/cms.php';
```

- [ ] **Step 4: Verify — open contribuir form, check selects have correct options**

Expected: Same options as before. Add a new type in admin → new option appears in form.

- [ ] **Step 5: Commit**

```bash
git add contribuir.php admin.php
git commit -m "feat(cms): integrate dynamic data types into contribution and admin forms"
```

---

### Task 12: Integrate dynamic thresholds into mapa.php and map_v2.js

**Files:**
- Modify: `mapa.php`
- Modify: `js/map_v2.js`

- [ ] **Step 1: Inject thresholds JSON in mapa.php**

At top of `mapa.php`, after existing PHP:

```php
require_once __DIR__ . '/config/cms.php';
$thresholdsData = getUnitsWithThresholds();
```

Before the `<script src="js/map_v2.js">` tag, add:

```html
<script>
window.GEO_THRESHOLDS = <?= json_encode($thresholdsData, JSON_UNESCAPED_UNICODE) ?>;
</script>
```

- [ ] **Step 2: Replace hardcoded getColor() and getLevel() in map_v2.js**

Replace the existing `getColor()` and `getLevel()` functions (around lines 26-40) with:

```javascript
function getThresholdsForUnit(unitName) {
    if (!window.GEO_THRESHOLDS) return null;
    const unit = window.GEO_THRESHOLDS.find(u => u.name === unitName);
    return unit ? unit.thresholds : null;
}

function getColor(value, unitName) {
    const thresholds = getThresholdsForUnit(unitName || 'part/Kg');
    if (!thresholds) return '#6b7280';

    for (const t of thresholds) {
        const min = parseFloat(t.min_value);
        const max = t.max_value !== null ? parseFloat(t.max_value) : Infinity;
        if (value >= min && value < max) return t.color;
    }
    return '#6b7280';
}

function getLevel(value, unitName) {
    const levelNames = { baixo: 'Baixa', medio: 'Media', elevado: 'Elevada', alto: 'Alta', critico: 'Critica' };
    const thresholds = getThresholdsForUnit(unitName || 'part/Kg');
    if (!thresholds) return 'N/A';

    for (const t of thresholds) {
        const min = parseFloat(t.min_value);
        const max = t.max_value !== null ? parseFloat(t.max_value) : Infinity;
        if (value >= min && value < max) return levelNames[t.level] || t.level;
    }
    return 'N/A';
}
```

- [ ] **Step 3: Update calls to getColor() and getLevel() to pass unit name**

In `map_v2.js`, find every call to `getColor(value)` and `getLevel(value)` and add the unit parameter from the data point. Typically in the popup builder (around line 43-87):

```javascript
// Change from:
getColor(point.concentration_value)
// To:
getColor(point.concentration_value, point.unidade)
```

Same for `getLevel()` calls.

- [ ] **Step 4: Update heatmap gradient to use thresholds**

Replace the hardcoded heatmap gradient (lines ~163-169) to build dynamically from `window.GEO_THRESHOLDS`:

```javascript
function buildHeatmapGradient() {
    const thresholds = getThresholdsForUnit('part/Kg');
    if (!thresholds || thresholds.length === 0) {
        return { 0.2: '#00CC88', 0.4: '#FFD700', 0.6: '#FFA500', 0.8: '#FF6600', 1.0: '#CC0000' };
    }
    const gradient = {};
    const step = 1 / thresholds.length;
    thresholds.forEach((t, i) => {
        gradient[((i + 1) * step).toFixed(1)] = t.color;
    });
    return gradient;
}
```

- [ ] **Step 5: Also update hardcoded legend in mapa.php**

Replace the hardcoded legend (lines ~131-135) with dynamic PHP:

```php
<?php
$defaultUnit = null;
foreach ($thresholdsData as $u) {
    if ($u['name'] === 'part/Kg') { $defaultUnit = $u; break; }
}
if ($defaultUnit):
    $levelLabels = ['baixo' => 'Baixa', 'medio' => 'Média', 'elevado' => 'Elevada', 'alto' => 'Alta', 'critico' => 'Crítica'];
    foreach ($defaultUnit['thresholds'] as $t):
?>
    <div style="display: flex; align-items: center; gap: 0.5rem;">
        <div style="width: 12px; height: 12px; border-radius: 50%; background: <?= htmlspecialchars($t['color']) ?>;"></div>
        <span><?= $levelLabels[$t['level']] ?? $t['level'] ?>: <?= number_format($t['min_value'], 0, ',', '.') ?><?= $t['max_value'] ? ' - ' . number_format($t['max_value'], 0, ',', '.') : '+' ?></span>
    </div>
<?php
    endforeach;
endif;
?>
```

- [ ] **Step 6: Also update hardcoded concentration filter chips in mapa.php**

Replace the hardcoded filter chips (lines ~98-105) with dynamic values from thresholds.

- [ ] **Step 7: Verify — load map, check colors match, change threshold in admin → map updates**

Expected: Map renders identically. Admin changes threshold color → map uses new color on reload.

- [ ] **Step 8: Commit**

```bash
git add mapa.php js/map_v2.js
git commit -m "feat(cms): integrate dynamic thresholds into map and legend"
```

---

### Task 13: Update API response with threshold data

**Files:**
- Modify: `api/get_microplastics.php`

- [ ] **Step 1: Add threshold data to API response**

At top of `api/get_microplastics.php`:

```php
require_once __DIR__ . '/../config/cms.php';
```

In the JSON response (around line 90), add thresholds:

```php
echo json_encode([
    'success' => true,
    'count' => count($data),
    'data' => $data,
    'thresholds' => getUnitsWithThresholds()  // NEW
]);
```

- [ ] **Step 2: Verify — curl the API and check thresholds field**

```bash
curl -s "https://geoplasticobr.com/api/get_microplastics.php?limit=1" | python3 -m json.tool | head -30
```

Expected: Response includes `thresholds` array with units and levels.

- [ ] **Step 3: Commit**

```bash
git add api/get_microplastics.php
git commit -m "feat(cms): include threshold data in API response"
```

---

### Task 14: Deploy and final verification

- [ ] **Step 1: Upload all new/modified files to server via SCP (sequentially!)**

```bash
# New files first
scp sql/migration_cms.sql hostinger:~/domains/geoplasticobr.com/public_html/sql/
scp config/cms.php hostinger:~/domains/geoplasticobr.com/public_html/config/
scp -r admin/ hostinger:~/domains/geoplasticobr.com/public_html/admin/
# Modified files
scp admin.php hostinger:~/domains/geoplasticobr.com/public_html/
scp includes/header.php hostinger:~/domains/geoplasticobr.com/public_html/includes/
scp includes/footer.php hostinger:~/domains/geoplasticobr.com/public_html/includes/
scp index.php hostinger:~/domains/geoplasticobr.com/public_html/
scp sobre.php hostinger:~/domains/geoplasticobr.com/public_html/
scp contribuir.php hostinger:~/domains/geoplasticobr.com/public_html/
scp mapa.php hostinger:~/domains/geoplasticobr.com/public_html/
scp js/map_v2.js hostinger:~/domains/geoplasticobr.com/public_html/js/
scp api/get_microplastics.php hostinger:~/domains/geoplasticobr.com/public_html/api/
```

**IMPORTANT: Run each SCP sequentially, NOT in parallel!**

- [ ] **Step 2: Set permissions on new directories**

```bash
ssh hostinger "chmod 755 ~/domains/geoplasticobr.com/public_html/admin"
ssh hostinger "chmod 755 ~/domains/geoplasticobr.com/public_html/assets/images/uploads"
ssh hostinger "chmod 755 ~/domains/geoplasticobr.com/public_html/sql"
```

- [ ] **Step 3: Run migration**

```bash
ssh hostinger "cd ~/domains/geoplasticobr.com/public_html && mysql -u u129322308_Pedro_Akira -p u129322308_geoPlasticoBr < sql/migration_cms.sql"
```

- [ ] **Step 4: Verify all pages load without errors**

Open in browser:
- Homepage: site name from DB, hero text from blocks
- Sobre: text from blocks
- Mapa: legend colors from thresholds
- Contribuir: select options from data_types
- Admin → each new tab works
- API: `/api/get_microplastics.php?limit=1` includes thresholds

- [ ] **Step 5: Final commit**

```bash
git add -A
git commit -m "feat(cms): complete Admin/CMS integration — all pages use dynamic content"
git push
```
