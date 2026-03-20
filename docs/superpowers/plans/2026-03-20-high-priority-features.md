# High Priority Features Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement conditional ecossistema filter, species registry with photo, and dynamic contribution form for GeoPlasticoBR.

**Architecture:** Three features built in dependency order. Feature 1 (JS-only filter) establishes the tipo_ambiente→ecossistema mapping used by Features 2 and 3. Feature 2 creates the species table, admin CRUD tab, API, and popup image display. Feature 3 rewrites contribuir.php to use the dynamic field system (samples + sample_values) instead of the legacy microplastics_sediment table.

**Tech Stack:** PHP 8+, MySQL/MariaDB, vanilla JS, Leaflet.js, Tailwind CSS (admin), custom CSS (public pages)

---

## File Structure

### Feature 1: Conditional Ecossistema Filter
- **Modify:** `mapa.php` — Add `window.GEO_ECO_MAP` output and tipo_ambiente filter dropdown
- **Modify:** `js/map_v2.js` — Fix applyFilters() to check ecossistema/tipo_ambiente/concentration, add eco mapping logic, fix clearFilter
- **Modify:** `admin/tab_dados.php` — Add JS for tipo_ambiente→ecossistema filtering in the form
- **Modify:** `contribuir.php` — Add JS for tipo_ambiente→ecossistema filtering (temporary — replaced by Feature 3 rewrite)

### Feature 2: Species Registry with Photo
- **Create:** `sql/migration_species.sql` — Species table DDL
- **Create:** `sql/run_species_migration.php` — Migration runner
- **Create:** `admin/tab_especies.php` — Species CRUD admin tab
- **Create:** `api/get_species.php` — Species list API endpoint
- **Modify:** `admin.php:615-1069` — Add sidebar button + tab content div for especies
- **Modify:** `api/get_samples.php` — Add species image post-processing
- **Modify:** `js/map_v2.js:95-143` — Show species image in popup
- **Modify:** `admin/tab_dados.php:132-168` — Handle especie field as dynamic select
- **Modify:** `sql/migration_species.sql` — UPDATE especie field_type from text to select

### Feature 3: Dynamic Contribution Form
- **Create:** `api/get_category_fields.php` — Category fields API endpoint
- **Create:** `api/submit_sample.php` — Sample submission API endpoint
- **Rewrite:** `contribuir.php` — Progressive form with AJAX-loaded dynamic fields

---

## Feature 1: Conditional Ecossistema Filter

### Task 1: Add GEO_ECO_MAP to mapa.php

**Files:**
- Modify: `mapa.php:1-7` (add PHP mapping after requires)
- Modify: `mapa.php:62-112` (add tipo_ambiente dropdown to filter panel)

- [ ] **Step 1: Add the PHP eco mapping array and output it as window.GEO_ECO_MAP**

In `mapa.php`, after the existing PHP block at the top (line 7), before the `<!DOCTYPE>`, add the eco mapping. Then output it as a `<script>` tag in the `<head>`.

Add after line 6 (`$_thresholdUnits = getUnitsWithThresholds();`):

```php
$_ecoMap = [
    'Água doce' => ['Lago', 'Reservatório', 'Rio', 'Córrego'],
    'Água salgada' => ['Mangue', 'Ilha', 'Oceano', 'Estuário', 'Restinga', 'Apicum'],
    'Terrestre' => ['Floresta', 'Campo', 'Área urbana', 'Solo exposto'],
];
```

Then inside `<head>`, after the `map_v2.css` link (line 23), add:

```html
<script>
window.GEO_ECO_MAP = <?php echo json_encode($_ecoMap, JSON_UNESCAPED_UNICODE); ?>;
</script>
```

- [ ] **Step 2: Add tipo_ambiente dropdown to filter panel**

In `mapa.php`, insert a new filter section BEFORE the ecossistema filter (before line 74). Between the search filter section (ends ~line 73) and the ecossistema section (starts ~line 74):

```php
            <div class="filter-section">
                <label class="filter-label">Tipo de Ambiente</label>
                <select class="filter-select" id="filterTipoAmbiente">
                    <option value="">Todos</option>
                    <option value="Água doce">Água doce</option>
                    <option value="Água salgada">Água salgada</option>
                    <option value="Terrestre">Terrestre</option>
                </select>
            </div>
```

- [ ] **Step 3: Commit**

```bash
git add mapa.php
git commit -m "feat: add GEO_ECO_MAP and tipo_ambiente filter dropdown to map page"
```

### Task 2: Fix applyFilters() and wire up eco filtering in map_v2.js

**Files:**
- Modify: `js/map_v2.js:16-21` (activeFilters object)
- Modify: `js/map_v2.js:341-366` (applyFilters function)
- Modify: `js/map_v2.js:801-846` (event listeners and clearFilter)

- [ ] **Step 1: Add missing filter keys to activeFilters**

In `js/map_v2.js`, change the `activeFilters` object (lines 16-21) from:

```js
    var activeFilters = {
        search: '',
        category: 'all',
        concMin: 0,
        concMax: 999999
    };
```

to:

```js
    var activeFilters = {
        search: '',
        category: 'all',
        concMin: 0,
        concMax: 999999,
        ecossistema: '',
        tipoAmbiente: ''
    };
```

- [ ] **Step 2: Fix applyFilters() to check all filter criteria**

Replace `applyFilters()` (lines 341-366) with:

```js
    function applyFilters() {
        filteredData = allData.filter(function(item) {
            if (activeFilters.category !== 'all' && item.category_id !== parseInt(activeFilters.category)) return false;

            if (activeFilters.search) {
                var q = activeFilters.search.toLowerCase();
                var title = (item.title || '').toLowerCase();
                var author = (item.author || '').toLowerCase();
                var catName = (item.category_name || '').toLowerCase();
                var fieldMatch = false;
                if (item.fields) {
                    for (var i = 0; i < item.fields.length; i++) {
                        var val = String(item.fields[i].value || '').toLowerCase();
                        if (val.indexOf(q) !== -1) { fieldMatch = true; break; }
                    }
                }
                if (title.indexOf(q) === -1 && author.indexOf(q) === -1 && catName.indexOf(q) === -1 && !fieldMatch) return false;
            }

            // Tipo de Ambiente filter
            if (activeFilters.tipoAmbiente) {
                var itemTipo = getFieldValue(item, 'tipo_ambiente');
                if (!itemTipo) return false;
                // Handle legacy values: "Doce" → "Água doce", "Salgado" → "Água salgada"
                var normalizedTipo = itemTipo;
                if (itemTipo === 'Doce') normalizedTipo = 'Água doce';
                else if (itemTipo === 'Salgado') normalizedTipo = 'Água salgada';
                else if (itemTipo === 'Salobro') normalizedTipo = 'Água salgada';
                if (normalizedTipo !== activeFilters.tipoAmbiente) return false;
            }

            // Ecossistema filter
            if (activeFilters.ecossistema) {
                var itemEco = getFieldValue(item, 'ecossistema');
                if (!itemEco || itemEco !== activeFilters.ecossistema) return false;
            }

            // Concentration filter
            if (activeFilters.concMin > 0 || activeFilters.concMax < 999999) {
                var concVal = null;
                if (item.fields) {
                    for (var i = 0; i < item.fields.length; i++) {
                        if (item.fields[i].name === 'concentration_value') {
                            concVal = parseFloat(item.fields[i].value);
                            break;
                        }
                    }
                }
                // Also check legacy concentration_value
                if (concVal === null && item.concentration_value !== undefined) {
                    concVal = parseFloat(item.concentration_value);
                }
                if (concVal === null) return false;
                if (concVal < activeFilters.concMin || concVal > activeFilters.concMax) return false;
            }

            return true;
        });

        renderMarkers();
        updateStats();
        updateFilterBadge();
    }
```

- [ ] **Step 3: Add tipo_ambiente change listener with eco cascading**

After the existing ecossistema listener (line 807), add the tipo_ambiente listener. Also modify the eco listener to not re-set if cleared by cascade:

Find the eco filter listener block (lines 801-807) and REPLACE it plus add the tipo_ambiente listener BEFORE it:

```js
        // Tipo de Ambiente filter — cascades to ecossistema
        var tipoFilter = document.getElementById('filterTipoAmbiente');
        if (tipoFilter) {
            tipoFilter.addEventListener('change', function() {
                activeFilters.tipoAmbiente = this.value;
                // Cascade: filter ecossistema dropdown options
                var ecoSelect = document.getElementById('filterEcossistema');
                if (ecoSelect && window.GEO_ECO_MAP) {
                    var currentEco = ecoSelect.value;
                    var opts = ecoSelect.querySelectorAll('option');
                    if (this.value === '') {
                        // Show all
                        for (var i = 0; i < opts.length; i++) opts[i].style.display = '';
                    } else {
                        var allowed = window.GEO_ECO_MAP[this.value] || [];
                        for (var i = 0; i < opts.length; i++) {
                            if (opts[i].value === '') { opts[i].style.display = ''; continue; }
                            opts[i].style.display = allowed.indexOf(opts[i].value) !== -1 ? '' : 'none';
                        }
                        // If current eco selection is not in allowed, reset it
                        if (currentEco && allowed.indexOf(currentEco) === -1) {
                            ecoSelect.value = '';
                            activeFilters.ecossistema = '';
                        }
                    }
                }
                applyFilters();
            });
        }

        var ecoFilter = document.getElementById('filterEcossistema');
        if (ecoFilter) {
            ecoFilter.addEventListener('change', function() {
                activeFilters.ecossistema = this.value;
                applyFilters();
            });
        }
```

- [ ] **Step 4: Fix clearFilter to reset all filter keys and UI elements**

Replace the clear button handler (lines 835-846) with:

```js
        var clearBtn = document.getElementById('filterClear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function() {
                activeFilters = { search: '', category: 'all', concMin: 0, concMax: 999999, ecossistema: '', tipoAmbiente: '' };
                if (searchInput) searchInput.value = '';
                var tipoSel = document.getElementById('filterTipoAmbiente');
                if (tipoSel) tipoSel.value = '';
                var ecoSel = document.getElementById('filterEcossistema');
                if (ecoSel) {
                    ecoSel.value = '';
                    // Show all eco options
                    ecoSel.querySelectorAll('option').forEach(function(o) { o.style.display = ''; });
                }
                // Reset concentration chips
                document.querySelectorAll('#filterConcentration .chip').forEach(function(c, i) { c.classList.toggle('active', i === 0); });
                // Reset category chips
                document.querySelectorAll('#filterCategory .chip').forEach(function(c) { c.classList.toggle('active', c.dataset.value === 'all'); });
                var envContainer = document.getElementById('envToggle');
                if (envContainer) {
                    envContainer.querySelectorAll('.env-btn').forEach(function(b, i) { b.classList.toggle('active', i === 0); });
                }
                applyFilters();
            });
        }
```

- [ ] **Step 5: Verify manually — open mapa.php, test filters**

Open the map in browser. Test:
1. Select "Água doce" in tipo_ambiente → ecossistema should show only Lago, Reservatório, Rio, Córrego
2. Select "Água salgada" → eco shows Mangue, Ilha, Oceano, Estuário, Restinga, Apicum
3. Select "Terrestre" → eco shows Floresta, Campo, Área urbana, Solo exposto
4. Clear tipo_ambiente → all eco options visible again
5. Concentration filter chips actually filter points
6. "Limpar filtros" resets everything

- [ ] **Step 6: Commit**

```bash
git add js/map_v2.js
git commit -m "fix: wire up ecossistema, tipo_ambiente, and concentration filters in map"
```

### Task 3: Add eco cascading to admin tab_dados.php

**Files:**
- Modify: `admin/tab_dados.php:132-168` (renderFieldInput function)

- [ ] **Step 1: Add inline JS for tipo_ambiente→ecossistema cascading in admin forms**

The admin form is rendered via `renderFieldInput()`. We need to add a JS snippet that runs after the form is rendered. Add this at the very end of `admin/tab_dados.php` (after the closing `</div>` of the main container):

```php
<script>
(function() {
    var ecoMap = <?php echo json_encode([
        'Água doce' => ['Lago', 'Reservatório', 'Rio', 'Córrego'],
        'Água salgada' => ['Mangue', 'Ilha', 'Oceano', 'Estuário', 'Restinga', 'Apicum'],
        'Terrestre' => ['Floresta', 'Campo', 'Área urbana', 'Solo exposto'],
    ], JSON_UNESCAPED_UNICODE); ?>;

    // Legacy value normalization
    var legacyMap = {'Doce': 'Água doce', 'Salgado': 'Água salgada', 'Salobro': 'Água salgada'};

    function cascadeEco(tipoSelect) {
        var form = tipoSelect.closest('form');
        if (!form) return;
        // Find ecossistema select in same form — field names are field_<id>
        var ecoSelects = form.querySelectorAll('select');
        var ecoSelect = null;
        for (var i = 0; i < ecoSelects.length; i++) {
            // Check if any option matches known eco values
            var opts = ecoSelects[i].options;
            for (var j = 0; j < opts.length; j++) {
                if (['Lago', 'Mangue', 'Floresta'].indexOf(opts[j].value) !== -1) {
                    ecoSelect = ecoSelects[i];
                    break;
                }
            }
            if (ecoSelect) break;
        }
        if (!ecoSelect) return;

        var val = tipoSelect.value;
        var normalized = legacyMap[val] || val;
        var allowed = ecoMap[normalized] || [];
        var opts = ecoSelect.options;

        if (!val) {
            for (var i = 0; i < opts.length; i++) opts[i].style.display = '';
        } else {
            for (var i = 0; i < opts.length; i++) {
                if (opts[i].value === '') { opts[i].style.display = ''; continue; }
                opts[i].style.display = allowed.indexOf(opts[i].value) !== -1 ? '' : 'none';
            }
            if (allowed.indexOf(ecoSelect.value) === -1) ecoSelect.value = '';
        }
    }

    // Attach to all tipo_ambiente selects in admin forms
    document.addEventListener('change', function(e) {
        var sel = e.target;
        if (sel.tagName !== 'SELECT') return;
        var opts = sel.options;
        // Detect tipo_ambiente select by checking if options include 'Água doce' or 'Terrestre'
        var isTipo = false;
        for (var i = 0; i < opts.length; i++) {
            if (['Água doce', 'Água salgada', 'Terrestre', 'Doce', 'Salgado'].indexOf(opts[i].value) !== -1) {
                isTipo = true; break;
            }
        }
        if (isTipo) cascadeEco(sel);
    });

    // Run on page load for edit forms with pre-selected values
    document.querySelectorAll('select').forEach(function(sel) {
        var opts = sel.options;
        for (var i = 0; i < opts.length; i++) {
            if (['Água doce', 'Água salgada', 'Terrestre'].indexOf(opts[i].value) !== -1 && sel.value) {
                cascadeEco(sel);
                return;
            }
        }
    });
})();
</script>
```

- [ ] **Step 2: Verify in admin — change tipo_ambiente, check ecossistema filters**

- [ ] **Step 3: Commit**

```bash
git add admin/tab_dados.php
git commit -m "feat: add tipo_ambiente→ecossistema cascading filter in admin forms"
```

### Task 4: Add eco cascading to contribuir.php (current legacy form)

**Files:**
- Modify: `contribuir.php:460-480` (JS section at bottom)

- [ ] **Step 1: Add cascading JS to contribuir.php**

In `contribuir.php`, after the existing minimap JS (before `</script>` at line 479), add:

```js
    // Tipo ambiente → Ecossistema cascading
    var ecoMap = {
        'Água doce': ['Lago', 'Reservatório', 'Rio', 'Córrego'],
        'Água salgada': ['Mangue', 'Ilha', 'Oceano', 'Estuário', 'Restinga', 'Apicum'],
        'Terrestre': ['Floresta', 'Campo', 'Área urbana', 'Solo exposto']
    };
    var legacyMap = {'Doce': 'Água doce', 'Salgado': 'Água salgada', 'Salobro': 'Água salgada'};

    var tipoSel = document.querySelector('select[name="tipo_ambiente"]');
    var ecoSel = document.querySelector('select[name="ecossistema"]');
    if (tipoSel && ecoSel) {
        tipoSel.addEventListener('change', function() {
            var val = this.value;
            var normalized = legacyMap[val] || val;
            var allowed = ecoMap[normalized] || [];
            var opts = ecoSel.options;
            if (!val) {
                for (var i = 0; i < opts.length; i++) opts[i].style.display = '';
            } else {
                for (var i = 0; i < opts.length; i++) {
                    if (opts[i].value === '') { opts[i].style.display = ''; continue; }
                    opts[i].style.display = allowed.indexOf(opts[i].value) !== -1 ? '' : 'none';
                }
                if (allowed.indexOf(ecoSel.value) === -1) ecoSel.value = '';
            }
        });
    }
```

- [ ] **Step 2: Verify in contribuir.php — selecting ambiente filters ecossistema**

- [ ] **Step 3: Commit**

```bash
git add contribuir.php
git commit -m "feat: add tipo_ambiente→ecossistema cascading in contribution form"
```

---

## Feature 2: Species Registry with Photo

### Task 5: Create species table migration

**Files:**
- Create: `sql/migration_species.sql`
- Create: `sql/run_species_migration.php`

- [ ] **Step 1: Write the SQL migration**

Create `sql/migration_species.sql`:

```sql
-- GeoPlasticoBR - Species Table Migration
-- Creates species table and updates especie field type from text to select
-- NOTE: CREATE TABLE causes implicit commit in MySQL, so no wrapping transaction for DDL

-- Create species table
CREATE TABLE IF NOT EXISTS species (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    scientific_name VARCHAR(200),
    image_path VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES sample_categories(id) ON DELETE CASCADE
);

-- Update especie field_type from 'text' to 'select' for all biotic categories
-- The select options will NOT use select_options column — JS fetches from species API instead
UPDATE category_fields
SET field_type = 'select', select_options = NULL
WHERE field_name = 'especie' AND field_type = 'text';
```

- [ ] **Step 2: Write the migration runner**

Create `sql/run_species_migration.php` following the pattern from `sql/run_field_standardization.php`:

```php
<?php
/**
 * GeoPlasticoBR - Species Table Migration Runner
 * Run via CLI: php run_species_migration.php
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getDatabaseConnection();
if (!$pdo) {
    echo "FAILED: Could not connect to database\n";
    exit(1);
}

echo "Connected to database.\n";

$sqlFile = __DIR__ . '/migration_species.sql';
if (!file_exists($sqlFile)) {
    echo "FAILED: SQL file not found at $sqlFile\n";
    exit(1);
}

$sql = file_get_contents($sqlFile);
$sql = preg_replace('/^--.*$/m', '', $sql);

$statements = [];
$current = '';
$inString = false;
$stringChar = '';

for ($i = 0; $i < strlen($sql); $i++) {
    $char = $sql[$i];
    if ($inString) {
        $current .= $char;
        if ($char === $stringChar && ($i === 0 || $sql[$i-1] !== '\\')) {
            $inString = false;
        }
    } else {
        if ($char === "'" || $char === '"') {
            $inString = true;
            $stringChar = $char;
            $current .= $char;
        } elseif ($char === ';') {
            $trimmed = trim($current);
            if ($trimmed !== '') $statements[] = $trimmed;
            $current = '';
        } else {
            $current .= $char;
        }
    }
}
$trimmed = trim($current);
if ($trimmed !== '') $statements[] = $trimmed;

echo "Parsed " . count($statements) . " SQL statements.\n\n";

$inTransaction = false;
$success = true;

foreach ($statements as $i => $stmt) {
    $preview = substr(preg_replace('/\s+/', ' ', $stmt), 0, 80);
    echo "[$i] $preview...\n";

    try {
        if (strtoupper(trim($stmt)) === 'START TRANSACTION') {
            $pdo->beginTransaction();
            $inTransaction = true;
            echo "    -> Transaction started\n";
        } elseif (strtoupper(trim($stmt)) === 'COMMIT') {
            if ($inTransaction) {
                $pdo->commit();
                $inTransaction = false;
                echo "    -> Committed\n";
            }
        } else {
            $affected = $pdo->exec($stmt);
            echo "    -> OK (affected: $affected)\n";
        }
    } catch (PDOException $e) {
        echo "    -> ERROR: " . $e->getMessage() . "\n";
        if ($inTransaction) {
            $pdo->rollBack();
            echo "    -> Transaction rolled back!\n";
        }
        $success = false;
        break;
    }
}

echo "\n";
if ($success) {
    echo "=== MIGRATION COMPLETED SUCCESSFULLY ===\n\n";

    echo "=== VERIFICATION ===\n";
    $r = $pdo->query("DESCRIBE species");
    echo "Species table columns:\n";
    foreach ($r as $row) {
        echo "  {$row['Field']} ({$row['Type']})\n";
    }

    echo "\nEspecie field types:\n";
    $r = $pdo->query("SELECT sc.name, cf.field_type FROM category_fields cf JOIN sample_categories sc ON cf.category_id = sc.id WHERE cf.field_name = 'especie'");
    foreach ($r as $row) {
        echo "  {$row['name']}: {$row['field_type']}\n";
    }
} else {
    echo "=== MIGRATION FAILED - SEE ERROR ABOVE ===\n";
    exit(1);
}
```

- [ ] **Step 3: Commit**

```bash
git add sql/migration_species.sql sql/run_species_migration.php
git commit -m "feat: add species table migration and runner script"
```

### Task 6: Create species API endpoint

**Files:**
- Create: `api/get_species.php`

- [ ] **Step 1: Create the API endpoint**

Create `api/get_species.php`:

```php
<?php
/**
 * API endpoint to fetch species for a given category.
 * GET /api/get_species.php?category_id=X
 * Returns JSON array of active species.
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$categoryId = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int)$_GET['category_id'] : null;

if (!$categoryId) {
    echo json_encode(['success' => false, 'error' => 'category_id is required']);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("SELECT id, name, scientific_name, image_path FROM species WHERE category_id = :cid AND is_active = 1 ORDER BY name");
    $stmt->execute([':cid' => $categoryId]);
    $species = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => array_map(function($s) {
            return [
                'id' => (int)$s['id'],
                'name' => $s['name'],
                'scientific_name' => $s['scientific_name'],
                'image_path' => $s['image_path'],
            ];
        }, $species),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Species API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Query failed']);
}
```

- [ ] **Step 2: Commit**

```bash
git add api/get_species.php
git commit -m "feat: add species API endpoint"
```

### Task 7: Create admin species CRUD tab

**Files:**
- Create: `admin/tab_especies.php`
- Modify: `admin.php` (add sidebar button + tab content div)

- [ ] **Step 1: Create the species admin tab**

Create `admin/tab_especies.php`. This follows the pattern from `tab_categorias.php` for upload handling and `tab_dados.php` for CRUD:

```php
<?php
$pdo = getDatabaseConnection();
$allCats = getCategories();

// Upload helper for species images
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
    // Default to first biotic category
    foreach ($allCats as $c) {
        if ($c['type'] === 'biotico') { $speciesFilter = $c['id']; break; }
    }
    if ($speciesFilter === 0) $speciesFilter = $allCats[0]['id'];
}

// Handle actions
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
                    } catch (PDOException $e) {
                        $sp_error = 'Erro: ' . $e->getMessage();
                    }
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
                    } catch (PDOException $e) {
                        $sp_error = 'Erro: ' . $e->getMessage();
                    }
                }
            }
        }

        if ($action === 'delete') {
            $spId = (int)($_POST['sp_id'] ?? 0);
            if ($spId > 0) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM species WHERE id = :id");
                    $stmt->execute([':id' => $spId]);
                    $sp_success = "Especie removida.";
                } catch (PDOException $e) {
                    $sp_error = 'Erro: ' . $e->getMessage();
                }
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

// Fetch species for selected category
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

    <!-- Category Filter -->
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
                        <?php foreach ($allCats as $c):
                            if ($c['type'] !== 'biotico') continue;
                        ?>
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
```

- [ ] **Step 2: Add the especies tab to admin.php**

Add sidebar button in `admin.php`. Find the "Dados" button (line ~615) and add the especies button after it:

After line 619 (`<?php endif; ?>` closing the `can_manage_data` guard for Dados), add the especies button with its own permission check:

```php
            <?php if (hasPermission('can_manage_data')): ?>
            <button onclick="showTab('especies')" id="tab-especies" class="sidebar-item tab-btn <?php echo $activeTab === 'especies' ? 'active' : ''; ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span>Especies</span>
            </button>
            <?php endif; ?>
```

Add tab content div. Find the dados content div (line ~1068-1069) and add after it:

```php
        <div id="form-especies" class="tab-content <?php echo $activeTab !== 'especies' ? 'hidden' : ''; ?>">
            <?php if (file_exists(__DIR__ . '/admin/tab_especies.php')) include __DIR__ . '/admin/tab_especies.php'; ?>
        </div>
```

- [ ] **Step 3: Verify in admin — check especies tab loads, test add/toggle/delete**

- [ ] **Step 4: Commit**

```bash
git add admin/tab_especies.php admin.php
git commit -m "feat: add species CRUD admin tab with photo upload"
```

### Task 8: Add species image to map popup

**Files:**
- Modify: `api/get_samples.php` (post-processing for species images)
- Modify: `js/map_v2.js:95-143` (buildPopup to show image)

- [ ] **Step 1: Add species image post-processing in get_samples.php**

In `api/get_samples.php`, after the existing sample formatting loop (after line 57, before the `echo json_encode`), add species image lookup:

```php
    // Post-processing: resolve species images for especie fields
    $speciesImageCache = [];
    foreach ($formattedSamples as &$sample) {
        foreach ($sample['fields'] as &$field) {
            if ($field['name'] === 'especie' && !empty($field['value'])) {
                $speciesName = $field['value'];
                if (!isset($speciesImageCache[$speciesName])) {
                    $spStmt = $pdo->prepare("SELECT image_path FROM species WHERE name = :name AND is_active = 1 LIMIT 1");
                    $spStmt->execute([':name' => $speciesName]);
                    $spRow = $spStmt->fetch();
                    $speciesImageCache[$speciesName] = $spRow ? $spRow['image_path'] : null;
                }
                $field['species_image'] = $speciesImageCache[$speciesName];
            }
        }
        unset($field);
    }
    unset($sample);
```

- [ ] **Step 2: Update buildPopup() to display species image**

In `js/map_v2.js`, in the `buildPopup()` function, after building the header badges div (line 103, after `html += '</div></div>';`), add species image rendering:

Replace the line `html += '<div class="popup-body">';` (line 105) with:

```js
        // Species image (if available)
        var speciesImage = null;
        if (item.fields) {
            for (var i = 0; i < item.fields.length; i++) {
                if (item.fields[i].name === 'especie' && item.fields[i].species_image) {
                    speciesImage = item.fields[i].species_image;
                    break;
                }
            }
        }
        if (speciesImage) {
            html += '<div class="popup-species-img"><img src="/' + speciesImage + '" alt="" style="width:100%;max-height:100px;object-fit:cover;border-radius:8px;margin-bottom:8px;"></div>';
        }

        html += '<div class="popup-body">';
```

Also update `buildBioticSinglePopup()` (line 209). Replace line 222 (`html += '<div class="popup-body">';`) with:

```js
        // Species image
        var speciesImage2 = null;
        if (item.fields) {
            for (var i = 0; i < item.fields.length; i++) {
                if (item.fields[i].name === 'especie' && item.fields[i].species_image) {
                    speciesImage2 = item.fields[i].species_image;
                    break;
                }
            }
        }
        if (speciesImage2) {
            html += '<div class="popup-species-img"><img src="/' + speciesImage2 + '" alt="" style="width:100%;max-height:100px;object-fit:cover;border-radius:8px;margin-bottom:8px;"></div>';
        }

        html += '<div class="popup-body">';
```

- [ ] **Step 3: Verify — check a popup for a sample that has an especie with an image**

- [ ] **Step 4: Commit**

```bash
git add api/get_samples.php js/map_v2.js
git commit -m "feat: display species image in map popup"
```

### Task 9: Wire especie field as dynamic select in admin

**Files:**
- Modify: `admin/tab_dados.php:132-168` (renderFieldInput)

- [ ] **Step 1: Add special handling for especie select in renderFieldInput**

In `admin/tab_dados.php`, in the `renderFieldInput()` function, at the beginning of the `select` type handler (line 138), add special handling for especie before the generic select:

Replace the select handler block (lines 138-145) with:

```php
    if ($field['field_type'] === 'select') {
        // Special handling for especie — fetch from species table instead of select_options
        if ($field['field_name'] === 'especie') {
            $html = "<select name=\"$name\" $req class=\"$cls\" data-species-select=\"1\"><option value=\"\">-- Selecione especie --</option>";
            // Will be populated via JS/AJAX based on category
            $html .= '</select>';
            $html .= "<script>
(function() {
    var sel = document.querySelector('[name=\"$name\"]');
    var catId = " . ((int)($GLOBALS['selectedCatId'] ?? 0)) . ";
    if (!sel || !catId) return;
    fetch('/api/get_species.php?category_id=' + catId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            var currentVal = " . json_encode($value, JSON_UNESCAPED_UNICODE) . ";
            data.data.forEach(function(sp) {
                var opt = document.createElement('option');
                opt.value = sp.name;
                opt.textContent = sp.name + (sp.scientific_name ? ' (' + sp.scientific_name + ')' : '');
                if (sp.name === currentVal) opt.selected = true;
                sel.appendChild(opt);
            });
        });
})();
</script>";
            return $html;
        }
        $html = "<select name=\"$name\" $req class=\"$cls\"><option value=\"\">-- Selecione --</option>";
        foreach (json_decode($field['select_options'] ?? '[]', true) ?: [] as $opt) {
            $sel = $value === $opt ? 'selected' : '';
            $html .= '<option value="'.htmlspecialchars($opt)."\" $sel>".htmlspecialchars($opt).'</option>';
        }
        return $html . '</select>';
    }
```

Make `$selectedCatId` accessible inside the function. At the top of `tab_dados.php`, the variable `$selectedCatId` is already set at line 4. The function needs access to it. Add `global $selectedCatId;` inside `renderFieldInput()` OR use `$GLOBALS['selectedCatId']` (already used above).

Actually, `$selectedCatId` is not a global — it's a local variable in the included file scope. To make it work, we need to set it as a global. Add before the `renderFieldInput` function definition (line 131):

```php
$GLOBALS['selectedCatId'] = $selectedCatId;
```

- [ ] **Step 2: Verify in admin — especie field shows as dropdown with species from API**

- [ ] **Step 3: Commit**

```bash
git add admin/tab_dados.php
git commit -m "feat: wire especie field as dynamic select fetching from species API"
```

---

## Feature 3: Dynamic Contribution Form

### Task 10: Create category fields API endpoint

**Files:**
- Create: `api/get_category_fields.php`

- [ ] **Step 1: Create the endpoint**

Create `api/get_category_fields.php`:

```php
<?php
/**
 * API endpoint to fetch active fields for a given category.
 * GET /api/get_category_fields.php?category_id=X
 * Returns JSON array of fields with their configuration.
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once '../config/database.php';
require_once '../config/cms.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$categoryId = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? (int)$_GET['category_id'] : null;

if (!$categoryId) {
    echo json_encode(['success' => false, 'error' => 'category_id is required']);
    exit;
}

try {
    $fields = getCategoryFields($categoryId);

    echo json_encode([
        'success' => true,
        'data' => array_map(function($f) {
            return [
                'id' => (int)$f['id'],
                'field_name' => $f['field_name'],
                'field_label' => $f['field_label'],
                'field_type' => $f['field_type'],
                'select_options' => $f['select_options'],
                'is_required' => (int)$f['is_required'],
                'display_order' => (int)$f['display_order'],
                'placeholder' => $f['placeholder'] ?? '',
            ];
        }, $fields),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Category fields API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Query failed']);
}
```

- [ ] **Step 2: Commit**

```bash
git add api/get_category_fields.php
git commit -m "feat: add category fields API endpoint"
```

### Task 11: Create sample submission API endpoint

**Files:**
- Create: `api/submit_sample.php`

- [ ] **Step 1: Create the submission endpoint**

Create `api/submit_sample.php`:

```php
<?php
/**
 * API endpoint to submit a new sample via the contribution form.
 * POST /api/submit_sample.php
 * Requires login. Inserts into samples + sample_values.
 * New submissions have approved=0 (pending admin review).
 */
header('Content-Type: application/json; charset=utf-8');

require_once '../auth.php';
require_once '../config/database.php';
require_once '../config/cms.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Login required']);
    exit;
}

// Validate CSRF
if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$categoryId = (int)($_POST['category_id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$latitude = $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
$longitude = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
$author = trim($_POST['author'] ?? '');
$reference = trim($_POST['reference_text'] ?? '');
$doi = trim($_POST['doi'] ?? '');

// Validation
$errors = [];
if ($categoryId === 0) $errors[] = 'Categoria e obrigatoria.';
if ($title === '') $errors[] = 'Titulo e obrigatorio.';
if ($latitude === null || $latitude < -90 || $latitude > 90) $errors[] = 'Latitude invalida.';
if ($longitude === null || $longitude < -180 || $longitude > 180) $errors[] = 'Longitude invalida.';

// Validate required dynamic fields
$fields = getCategoryFields($categoryId);
foreach ($fields as $field) {
    if ($field['is_required']) {
        $val = $_POST['field_' . $field['id']] ?? '';
        if ($field['field_type'] === 'multicheck') {
            $val = $_POST['field_' . $field['id']] ?? [];
            if (empty($val)) $errors[] = $field['field_label'] . ' e obrigatorio.';
        } elseif ($val === '') {
            $errors[] = $field['field_label'] . ' e obrigatorio.';
        }
    }
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $pdo = getDatabaseConnection();
    $pdo->beginTransaction();

    // Insert sample
    $stmt = $pdo->prepare("INSERT INTO samples (category_id, title, latitude, longitude, author, reference_text, doi, approved, submitted_by)
                           VALUES (:cid, :title, :lat, :lng, :author, :ref, :doi, 0, :uid)");
    $stmt->execute([
        ':cid' => $categoryId,
        ':title' => $title,
        ':lat' => $latitude,
        ':lng' => $longitude,
        ':author' => $author ?: null,
        ':ref' => $reference ?: null,
        ':doi' => $doi ?: null,
        ':uid' => $_SESSION['user_id'],
    ]);
    $sampleId = $pdo->lastInsertId();

    // Insert field values
    $stmtVal = $pdo->prepare("INSERT INTO sample_values (sample_id, field_id, value_text, value_number) VALUES (:sid, :fid, :vt, :vn)");
    foreach ($fields as $field) {
        $rawVal = $_POST['field_' . $field['id']] ?? '';

        if ($field['field_type'] === 'checkbox') {
            $rawVal = isset($_POST['field_' . $field['id']]) ? '1' : '0';
        }
        if ($field['field_type'] === 'multicheck') {
            $arr = $_POST['field_' . $field['id']] ?? [];
            $rawVal = is_array($arr) && !empty($arr) ? json_encode($arr, JSON_UNESCAPED_UNICODE) : '';
        }

        if ($rawVal === '' && !$field['is_required']) continue;

        $vt = null;
        $vn = null;
        if (in_array($field['field_type'], ['number', 'decimal'])) {
            $vn = $rawVal !== '' ? (float)str_replace(',', '.', $rawVal) : null;
        } else {
            $vt = $rawVal;
        }

        $stmtVal->execute([':sid' => $sampleId, ':fid' => $field['id'], ':vt' => $vt, ':vn' => $vn]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Dados enviados! Serao revisados pelo administrador.', 'sample_id' => (int)$sampleId]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Sample submission error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar dados.']);
}
```

- [ ] **Step 2: Commit**

```bash
git add api/submit_sample.php
git commit -m "feat: add sample submission API endpoint with validation"
```

### Task 12: Rewrite contribuir.php with dynamic form

**Files:**
- Rewrite: `contribuir.php`

- [ ] **Step 1: Rewrite the PHP section (remove legacy INSERT, keep login/CSRF)**

Replace the PHP block at the top of `contribuir.php` (lines 1-52) with:

```php
<?php
require_once __DIR__ . '/auth.php';
requireLogin();

require_once 'config/database.php';
require_once __DIR__ . '/config/cms.php';

$user = getCurrentUser();
$allCategories = getCategories();

// Separate biotic and abiotic categories
$abioticCats = array_filter($allCategories, function($c) { return $c['type'] === 'abiotico'; });
$bioticCats = array_filter($allCategories, function($c) { return $c['type'] === 'biotico'; });

$pageTitle = "Contribuir - GeoPlasticoBR";
include 'includes/header.php';
?>
```

- [ ] **Step 2: Rewrite the HTML form with progressive flow**

Replace the entire form section (from `<link rel="stylesheet"` through the `</script>` before footer include) with the new dynamic form. This is a full replacement of lines 58-480:

```html
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<!-- Page Header -->
<section class="contrib-hero">
    <div class="content-container">
        <span class="section-tag">Contribuicao Cientifica</span>
        <h1 class="contrib-title">Adicionar Dados</h1>
        <p class="contrib-desc">
            Contribua com novos pontos de coleta de microplasticos.
            Os dados serao revisados pelo administrador antes da publicacao no mapa.
        </p>
    </div>
</section>

<!-- Form Section -->
<section class="contrib-form-section">
    <div class="contrib-container">
        <div id="contribMessage"></div>

        <form id="contribForm" class="contrib-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

            <!-- Step 1: Tipo de Matriz -->
            <div class="form-block" id="stepMatriz">
                <div class="form-block-header">
                    <div class="form-block-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                    </div>
                    <h3>Tipo de Matriz</h3>
                </div>
                <div class="form-row" style="grid-template-columns: 1fr 1fr;">
                    <button type="button" class="matriz-btn" data-type="abiotico" onclick="selectMatriz('abiotico')">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                        <span>Abiotica</span>
                        <small>Sedimento, Agua, Solo</small>
                    </button>
                    <button type="button" class="matriz-btn" data-type="biotico" onclick="selectMatriz('biotico')">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Biotica</span>
                        <small>Peixes, Corais, Aves...</small>
                    </button>
                </div>
            </div>

            <!-- Step 2: Categoria -->
            <div class="form-block hidden" id="stepCategoria">
                <div class="form-block-header">
                    <div class="form-block-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    </div>
                    <h3>Categoria</h3>
                </div>
                <div class="cat-grid" id="catGrid"></div>
                <input type="hidden" name="category_id" id="categoryIdInput" value="">
            </div>

            <!-- Step 3: Fixed + Dynamic Fields -->
            <div class="hidden" id="stepFields">
                <!-- Fixed fields -->
                <div class="form-block">
                    <div class="form-block-header">
                        <div class="form-block-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <h3>Informacoes do Ponto</h3>
                    </div>
                    <div class="form-row">
                        <div class="form-field" style="grid-column: 1 / -1;">
                            <label>Titulo / Nome do Ponto <span class="req">*</span></label>
                            <input type="text" name="title" required placeholder="Ex: Rio Amazonas - Ponto 1">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label>Latitude <span class="req">*</span></label>
                            <input type="number" step="0.000001" name="latitude" id="lat-input" required placeholder="-23.5505">
                        </div>
                        <div class="form-field">
                            <label>Longitude <span class="req">*</span></label>
                            <input type="number" step="0.000001" name="longitude" id="lng-input" required placeholder="-46.6333">
                        </div>
                    </div>
                    <div class="form-field-full">
                        <label>Clique no mapa para selecionar coordenadas</label>
                        <div id="minimap"></div>
                        <p class="map-hint">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            Clique no mapa para preencher latitude e longitude
                        </p>
                    </div>
                    <div class="form-row" style="margin-top:1rem;">
                        <div class="form-field">
                            <label>Autor(es)</label>
                            <input type="text" name="author" placeholder="Ex: Silva, A. et al. (2023)">
                        </div>
                        <div class="form-field">
                            <label>Referencia Bibliografica</label>
                            <input type="text" name="reference_text" placeholder="Titulo do artigo">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-field" style="grid-column: 1 / -1;">
                            <label>DOI</label>
                            <input type="text" name="doi" placeholder="https://doi.org/10.1016/...">
                        </div>
                    </div>
                </div>

                <!-- Dynamic fields container -->
                <div class="form-block" id="dynamicFieldsBlock">
                    <div class="form-block-header">
                        <div class="form-block-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        </div>
                        <h3 id="dynamicFieldsTitle">Campos da Categoria</h3>
                    </div>
                    <div id="dynamicFieldsContainer">
                        <p class="text-center" style="color: rgba(148,163,184,0.5);">Carregando campos...</p>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" class="contrib-submit">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                    Enviar para Revisao
                </button>
            </div>
        </form>
    </div>
</section>
```

- [ ] **Step 3: Add the dynamic form JS**

After the form HTML, add the CSS and JS. **IMPORTANT:** The original `<style>` block (lines 232-456) must be preserved — it contains `contrib-hero`, `form-block`, `form-row`, `form-field`, `#minimap`, `contrib-submit`, `contrib-msg`, `contrib-msg-success`, `contrib-msg-error`, and responsive styles that the new form still uses. Copy the entire original `<style>` block, then append these additional styles inside it:

```css
/* Matriz buttons */
.matriz-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 2rem 1rem;
    background: rgba(0, 0, 0, 0.2);
    border: 2px solid rgba(148, 163, 184, 0.1);
    border-radius: 16px;
    color: #94A3B8;
    cursor: pointer;
    transition: all 0.3s;
    font-family: 'Plus Jakarta Sans', sans-serif;
}
.matriz-btn:hover { border-color: rgba(96, 165, 250, 0.3); color: #E2E8F0; }
.matriz-btn.selected { border-color: #60A5FA; background: rgba(96, 165, 250, 0.1); color: #ffffff; }
.matriz-btn span { font-size: 1rem; font-weight: 700; }
.matriz-btn small { font-size: 0.75rem; opacity: 0.6; }

/* Category grid */
.cat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 0.75rem;
}
.cat-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.4rem;
    padding: 1rem;
    background: rgba(0, 0, 0, 0.2);
    border: 2px solid rgba(148, 163, 184, 0.1);
    border-radius: 12px;
    color: #94A3B8;
    cursor: pointer;
    transition: all 0.3s;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    text-align: center;
}
.cat-card:hover { border-color: rgba(96, 165, 250, 0.3); color: #E2E8F0; }
.cat-card.selected { border-color: #60A5FA; background: rgba(96, 165, 250, 0.1); color: #ffffff; }

/* Dynamic field styles */
.dyn-field { margin-bottom: 1rem; }
.dyn-field label { display: block; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.8rem; font-weight: 600; color: rgba(148,163,184,0.8); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.05em; }
.dyn-field .req { color: #ff6b6b; }
.dyn-field input, .dyn-field select, .dyn-field textarea {
    width: 100%; padding: 0.75rem 1rem; background: rgba(0,0,0,0.25); border: 1px solid rgba(148,163,184,0.1);
    border-radius: 10px; color: #E2E8F0; font-size: 0.9rem; font-family: 'Plus Jakarta Sans', sans-serif; transition: all 0.25s;
}
.dyn-field input:focus, .dyn-field select:focus, .dyn-field textarea:focus { outline: none; border-color: rgba(96,165,250,0.4); background: rgba(0,0,0,0.35); }
.dyn-field select option { background: #1E293B; color: #E2E8F0; }
.dyn-multicheck { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.dyn-multicheck label {
    display: flex; align-items: center; gap: 0.4rem; padding: 0.5rem 0.75rem;
    background: rgba(0,0,0,0.2); border: 1px solid rgba(148,163,184,0.1); border-radius: 8px;
    cursor: pointer; transition: all 0.2s; font-size: 0.85rem; text-transform: none; letter-spacing: 0;
}
.dyn-multicheck label:hover { border-color: rgba(96,165,250,0.3); }
.dyn-multicheck input[type="checkbox"] { width: auto; padding: 0; }
```

Replace the `<script>` block with:

```html
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var contribState = { matrizType: null, categoryId: null };

var ecoMap = {
    'Água doce': ['Lago', 'Reservatório', 'Rio', 'Córrego'],
    'Água salgada': ['Mangue', 'Ilha', 'Oceano', 'Estuário', 'Restinga', 'Apicum'],
    'Terrestre': ['Floresta', 'Campo', 'Área urbana', 'Solo exposto']
};

var categoriesByType = {
    abiotico: <?php echo json_encode(array_values(array_map(function($c) { return ['id'=>(int)$c['id'], 'name'=>$c['name'], 'color'=>$c['color']]; }, $abioticCats)), JSON_UNESCAPED_UNICODE); ?>,
    biotico: <?php echo json_encode(array_values(array_map(function($c) { return ['id'=>(int)$c['id'], 'name'=>$c['name'], 'color'=>$c['color']]; }, $bioticCats)), JSON_UNESCAPED_UNICODE); ?>
};

function selectMatriz(type) {
    contribState.matrizType = type;
    contribState.categoryId = null;
    document.getElementById('categoryIdInput').value = '';
    document.getElementById('stepFields').classList.add('hidden');

    // Highlight selected button
    document.querySelectorAll('.matriz-btn').forEach(function(b) { b.classList.toggle('selected', b.dataset.type === type); });

    // Build category grid
    var grid = document.getElementById('catGrid');
    grid.innerHTML = '';
    var cats = categoriesByType[type] || [];
    cats.forEach(function(cat) {
        var card = document.createElement('button');
        card.type = 'button';
        card.className = 'cat-card';
        card.innerHTML = '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:' + cat.color + ';"></span>' + cat.name;
        card.addEventListener('click', function() {
            grid.querySelectorAll('.cat-card').forEach(function(c) { c.classList.remove('selected'); });
            card.classList.add('selected');
            selectCategory(cat.id, cat.name);
        });
        grid.appendChild(card);
    });

    document.getElementById('stepCategoria').classList.remove('hidden');
}

function selectCategory(catId, catName) {
    contribState.categoryId = catId;
    document.getElementById('categoryIdInput').value = catId;
    document.getElementById('dynamicFieldsTitle').textContent = 'Campos: ' + catName;
    document.getElementById('stepFields').classList.remove('hidden');

    // Fetch dynamic fields
    var container = document.getElementById('dynamicFieldsContainer');
    container.innerHTML = '<p style="color:rgba(148,163,184,0.5);text-align:center;">Carregando...</p>';

    fetch('/api/get_category_fields.php?category_id=' + catId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) { container.innerHTML = '<p style="color:#FCA5A5;">Erro ao carregar campos.</p>'; return; }
            renderDynamicFields(data.data, container, catId);
        })
        .catch(function() { container.innerHTML = '<p style="color:#FCA5A5;">Erro de conexao.</p>'; });

    // Init minimap if not already
    initMinimap();
}

function renderDynamicFields(fields, container, catId) {
    container.innerHTML = '';
    var grid = document.createElement('div');
    grid.style.display = 'grid';
    grid.style.gridTemplateColumns = '1fr 1fr';
    grid.style.gap = '1rem';

    fields.forEach(function(field) {
        var div = document.createElement('div');
        div.className = 'dyn-field';
        var reqHtml = field.is_required ? ' <span class="req">*</span>' : '';
        var label = '<label>' + field.field_label + reqHtml + '</label>';
        var inputName = 'field_' + field.id;
        var reqAttr = field.is_required ? 'required' : '';
        var html = label;

        if (field.field_name === 'especie' && field.field_type === 'select') {
            // Special: fetch from species API
            html += '<select name="' + inputName + '" ' + reqAttr + ' id="speciesSelect_' + field.id + '"><option value="">-- Carregando especies... --</option></select>';
            div.innerHTML = html;
            grid.appendChild(div);
            // Fetch species
            fetch('/api/get_species.php?category_id=' + catId)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var sel = document.getElementById('speciesSelect_' + field.id);
                    if (!sel) return;
                    sel.innerHTML = '<option value="">-- Selecione especie --</option>';
                    if (data.success) {
                        data.data.forEach(function(sp) {
                            var opt = document.createElement('option');
                            opt.value = sp.name;
                            opt.textContent = sp.name + (sp.scientific_name ? ' (' + sp.scientific_name + ')' : '');
                            sel.appendChild(opt);
                        });
                    }
                });
            return;
        }

        if (field.field_type === 'select') {
            var opts = [];
            try { opts = JSON.parse(field.select_options || '[]'); } catch(e) {}
            html += '<select name="' + inputName + '" ' + reqAttr + '><option value="">-- Selecione --</option>';
            opts.forEach(function(o) { html += '<option value="' + o + '">' + o + '</option>'; });
            html += '</select>';
            // Eco cascading for tipo_ambiente
            if (field.field_name === 'tipo_ambiente') {
                div.innerHTML = html;
                grid.appendChild(div);
                var sel = div.querySelector('select');
                sel.addEventListener('change', function() {
                    cascadeEcoInForm(this.value);
                });
                return;
            }
            if (field.field_name === 'ecossistema') {
                div.id = 'ecoFieldWrapper';
            }
        } else if (field.field_type === 'multicheck') {
            var opts = [];
            try { opts = JSON.parse(field.select_options || '[]'); } catch(e) {}
            html += '<div class="dyn-multicheck">';
            opts.forEach(function(o) {
                html += '<label><input type="checkbox" name="' + inputName + '[]" value="' + o + '"> ' + o + '</label>';
            });
            html += '</div>';
            // Multicheck spans full width
            div.style.gridColumn = '1 / -1';
        } else if (field.field_type === 'textarea') {
            html += '<textarea name="' + inputName + '" rows="2" ' + reqAttr + ' placeholder="' + (field.placeholder || '') + '"></textarea>';
            div.style.gridColumn = '1 / -1';
        } else if (field.field_type === 'checkbox') {
            html += '<label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;text-transform:none;letter-spacing:0;font-size:0.9rem;"><input type="checkbox" name="' + inputName + '" value="1" style="width:auto;"> Sim</label>';
        } else {
            var inputType = (field.field_type === 'number' || field.field_type === 'decimal') ? 'number' : 'text';
            var step = field.field_type === 'decimal' ? ' step="0.01"' : '';
            html += '<input type="' + inputType + '"' + step + ' name="' + inputName + '" ' + reqAttr + ' placeholder="' + (field.placeholder || '') + '">';
        }

        div.innerHTML = html;
        grid.appendChild(div);
    });

    container.appendChild(grid);
}

function cascadeEcoInForm(tipoValue) {
    var ecoWrapper = document.getElementById('ecoFieldWrapper');
    if (!ecoWrapper) return;
    var ecoSel = ecoWrapper.querySelector('select');
    if (!ecoSel) return;
    var opts = ecoSel.options;
    if (!tipoValue) {
        for (var i = 0; i < opts.length; i++) opts[i].style.display = '';
        return;
    }
    var allowed = ecoMap[tipoValue] || [];
    for (var i = 0; i < opts.length; i++) {
        if (opts[i].value === '') { opts[i].style.display = ''; continue; }
        opts[i].style.display = allowed.indexOf(opts[i].value) !== -1 ? '' : 'none';
    }
    if (allowed.indexOf(ecoSel.value) === -1) ecoSel.value = '';
}

// Minimap
var minimapInited = false;
function initMinimap() {
    if (minimapInited) return;
    minimapInited = true;
    setTimeout(function() {
        var mapEl = document.getElementById('minimap');
        if (!mapEl) return;
        var minimap = L.map('minimap').setView([-15.78, -47.93], 4);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CartoDB', maxZoom: 18
        }).addTo(minimap);
        var marker = null;
        minimap.on('click', function(e) {
            document.getElementById('lat-input').value = e.latlng.lat.toFixed(6);
            document.getElementById('lng-input').value = e.latlng.lng.toFixed(6);
            if (marker) minimap.removeLayer(marker);
            marker = L.marker([e.latlng.lat, e.latlng.lng]).addTo(minimap);
        });
    }, 100);
}

// Form submission
document.getElementById('contribForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var msgDiv = document.getElementById('contribMessage');
    var submitBtn = form.querySelector('.contrib-submit');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Enviando...';

    var formData = new FormData(form);

    fetch('/api/submit_sample.php', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                msgDiv.innerHTML = '<div class="contrib-msg contrib-msg-success"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg><span>' + data.message + '</span></div>';
                form.reset();
                document.getElementById('stepCategoria').classList.add('hidden');
                document.getElementById('stepFields').classList.add('hidden');
                document.querySelectorAll('.matriz-btn').forEach(function(b) { b.classList.remove('selected'); });
                contribState = { matrizType: null, categoryId: null };
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                var errMsg = data.errors ? data.errors.join('<br>') : (data.error || 'Erro desconhecido.');
                msgDiv.innerHTML = '<div class="contrib-msg contrib-msg-error"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg><span>' + errMsg + '</span></div>';
            }
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Enviar para Revisao';
        })
        .catch(function() {
            msgDiv.innerHTML = '<div class="contrib-msg contrib-msg-error">Erro de conexao. Tente novamente.</div>';
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg> Enviar para Revisao';
        });
});
</script>
```

- [ ] **Step 4: Verify the full contribuir.php flow**

Test in browser:
1. Select "Abiotica" → shows Sedimento, Agua, Solo
2. Select "Sedimento" → fixed fields appear + dynamic fields load via AJAX
3. tipo_ambiente filters ecossistema options
4. Fill required fields, submit → check JSON response is success
5. Select "Biotica" → shows Peixes, Corais, etc.
6. Select "Peixes" → especie field loads as dropdown from species API
7. Submit → verify record appears in admin pending_data tab

- [ ] **Step 5: Commit**

```bash
git add contribuir.php api/get_category_fields.php api/submit_sample.php
git commit -m "feat: rewrite contribuir.php with dynamic form using samples/sample_values system"
```

### Task 13: Run species migration on production

**Files:**
- Use: `sql/migration_species.sql`, `sql/run_species_migration.php`

- [ ] **Step 1: Upload migration files to production**

```bash
scp sql/migration_species.sql sql/run_species_migration.php hostinger:domains/geoplasticobr.com/public_html/sql/
```

- [ ] **Step 2: Create database backup**

```bash
ssh hostinger "cd domains/geoplasticobr.com/public_html && php -r \"require 'config/database.php'; \\\$p=getDatabaseConnection(); echo 'Connected'; \""
```

- [ ] **Step 3: Run the migration**

```bash
ssh hostinger "cd domains/geoplasticobr.com/public_html && php sql/run_species_migration.php"
```

Expected output: species table created, especie fields updated to select type.

- [ ] **Step 4: Clean up migration files from production**

```bash
ssh hostinger "rm domains/geoplasticobr.com/public_html/sql/migration_species.sql domains/geoplasticobr.com/public_html/sql/run_species_migration.php"
```

- [ ] **Step 5: Commit migration files**

```bash
git add sql/migration_species.sql sql/run_species_migration.php
git commit -m "feat: species table migration executed on production"
```

### Task 14: Deploy all changes to production

- [ ] **Step 1: Push to GitHub**

```bash
git push origin master
```

- [ ] **Step 2: Deploy files to production**

```bash
scp mapa.php js/map_v2.js admin/tab_dados.php admin/tab_especies.php admin.php contribuir.php api/get_species.php api/get_category_fields.php api/submit_sample.php hostinger:domains/geoplasticobr.com/public_html/
```

Note: preserve directory structure — API files go into `api/` and admin files into `admin/`.

```bash
scp api/get_species.php api/get_category_fields.php api/submit_sample.php hostinger:domains/geoplasticobr.com/public_html/api/
scp admin/tab_especies.php hostinger:domains/geoplasticobr.com/public_html/admin/
scp mapa.php js/map_v2.js admin/tab_dados.php admin.php contribuir.php hostinger:domains/geoplasticobr.com/public_html/
```

- [ ] **Step 3: Verify on production**

1. Map: tipo_ambiente and ecossistema filters work
2. Map: concentration filter works
3. Admin: Especies tab appears with CRUD
4. Admin: tipo_ambiente→ecossistema cascading in data forms
5. Admin: especie shows as dropdown from species API
6. Contribuir: progressive form loads, submits to dynamic system
7. Contribuir: new submission appears in admin pending_data
