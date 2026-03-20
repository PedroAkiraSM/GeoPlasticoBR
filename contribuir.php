<?php
require_once __DIR__ . '/auth.php';
requireLogin();

require_once 'config/database.php';
require_once __DIR__ . '/config/cms.php';

$user = getCurrentUser();
$allCategories = getCategories();

$abioticCats = array_filter($allCategories, function($c) { return $c['type'] === 'abiotico'; });
$bioticCats = array_filter($allCategories, function($c) { return $c['type'] === 'biotico'; });

$pageTitle = "Contribuir - GeoPlasticoBR";
include 'includes/header.php';
?>

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

<style>
/* ===== CONTRIB HERO ===== */
.contrib-hero {
    padding: clamp(7rem, 12vw, 10rem) clamp(1.5rem, 5vw, 3rem) clamp(3rem, 6vw, 5rem);
    text-align: center;
}

.contrib-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-weight: 800;
    font-size: clamp(2.5rem, 6vw, 4rem);
    color: #ffffff;
    margin: 1rem 0;
    letter-spacing: -0.03em;
}

.contrib-desc {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: clamp(1rem, 1.8vw, 1.1rem);
    color: rgba(148, 163, 184, 0.7);
    max-width: 550px;
    margin: 0 auto;
    line-height: 1.7;
}

/* ===== FORM SECTION ===== */
.contrib-form-section {
    padding: 0 clamp(1rem, 4vw, 3rem) clamp(4rem, 8vw, 6rem);
}

.contrib-container {
    max-width: 800px;
    margin: 0 auto;
}

/* ===== MESSAGES ===== */
.contrib-msg {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.25rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.9rem;
}

.contrib-msg-success {
    background: rgba(52, 211, 153, 0.08);
    border: 1px solid rgba(52, 211, 153, 0.2);
    color: #6EE7B7;
}

.contrib-msg-error {
    background: rgba(255, 50, 50, 0.08);
    border: 1px solid rgba(255, 80, 80, 0.2);
    color: #FCA5A5;
}

/* ===== FORM BLOCKS ===== */
.form-block {
    background: rgba(15, 23, 42, 0.5);
    border: 1px solid rgba(148, 163, 184, 0.08);
    border-radius: 16px;
    padding: clamp(1.5rem, 3vw, 2rem);
    margin-bottom: 1.5rem;
    transition: border-color 0.3s;
}

.form-block:hover {
    border-color: rgba(96, 165, 250, 0.15);
}

.form-block-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.form-block-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: rgba(96, 165, 250, 0.08);
    border: 1px solid rgba(96, 165, 250, 0.12);
    color: #60A5FA;
    flex-shrink: 0;
}

.form-block-header h3 {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: #ffffff;
    margin: 0;
}

/* ===== FORM FIELDS ===== */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-row:last-child {
    margin-bottom: 0;
}

.form-field label,
.form-field-full label {
    display: block;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.8rem;
    font-weight: 600;
    color: rgba(148, 163, 184, 0.8);
    margin-bottom: 0.4rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.form-field label .req {
    color: #ff6b6b;
}

.form-field input,
.form-field select,
.form-field textarea,
.form-field-full input,
.form-field-full select {
    width: 100%;
    padding: 0.75rem 1rem;
    background: rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(148, 163, 184, 0.1);
    border-radius: 10px;
    color: #E2E8F0;
    font-size: 0.9rem;
    font-family: 'Plus Jakarta Sans', sans-serif;
    transition: all 0.25s ease;
}

.form-field input:focus,
.form-field select:focus,
.form-field textarea:focus,
.form-field-full input:focus,
.form-field-full select:focus {
    outline: none;
    border-color: rgba(96, 165, 250, 0.4);
    background: rgba(0, 0, 0, 0.35);
    box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.08);
}

.form-field select option {
    background: #1E293B;
    color: #E2E8F0;
}

.form-field input::placeholder,
.form-field textarea::placeholder,
.form-field-full input::placeholder {
    color: rgba(148, 163, 184, 0.3);
}

.form-field-full {
    margin-top: 1rem;
}

/* ===== MAP ===== */
#minimap {
    height: 280px;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    margin-top: 0.5rem;
    overflow: hidden;
}

.map-hint {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    color: rgba(148, 163, 184, 0.4);
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.75rem;
    margin-top: 0.5rem;
}

/* ===== SUBMIT ===== */
.contrib-submit {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    width: 100%;
    padding: 1rem;
    background: #ffffff;
    color: #0F172A;
    border: none;
    border-radius: 50px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 0.5rem;
}

.contrib-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 40px rgba(255, 255, 255, 0.15);
}

/* ===== MATRIZ BUTTONS ===== */
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

/* ===== CATEGORY GRID ===== */
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

/* ===== DYNAMIC FIELDS ===== */
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

/* ===== RESPONSIVE ===== */
@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }

    .form-block {
        padding: 1.25rem;
    }
}

.hidden { display: none !important; }
</style>

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

    document.querySelectorAll('.matriz-btn').forEach(function(b) { b.classList.toggle('selected', b.dataset.type === type); });

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

    var container = document.getElementById('dynamicFieldsContainer');
    container.innerHTML = '<p style="color:rgba(148,163,184,0.5);text-align:center;">Carregando...</p>';

    fetch('/api/get_category_fields.php?category_id=' + catId)
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) { container.innerHTML = '<p style="color:#FCA5A5;">Erro ao carregar campos.</p>'; return; }
            renderDynamicFields(data.data, container, catId);
        })
        .catch(function() { container.innerHTML = '<p style="color:#FCA5A5;">Erro de conexao.</p>'; });

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
        var label = '<label>' + escapeHtml(field.field_label) + reqHtml + '</label>';
        var inputName = 'field_' + field.id;
        var reqAttr = field.is_required ? 'required' : '';
        var html = label;

        if (field.field_name === 'especie' && field.field_type === 'select') {
            html += '<select name="' + inputName + '" ' + reqAttr + ' id="speciesSelect_' + field.id + '"><option value="">-- Carregando especies... --</option></select>';
            div.innerHTML = html;
            grid.appendChild(div);
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
            opts.forEach(function(o) { html += '<option value="' + escapeHtml(o) + '">' + escapeHtml(o) + '</option>'; });
            html += '</select>';
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
                html += '<label><input type="checkbox" name="' + inputName + '[]" value="' + escapeHtml(o) + '"> ' + escapeHtml(o) + '</label>';
            });
            html += '</div>';
            div.style.gridColumn = '1 / -1';
        } else if (field.field_type === 'textarea') {
            html += '<textarea name="' + inputName + '" rows="2" ' + reqAttr + ' placeholder="' + escapeHtml(field.placeholder || '') + '"></textarea>';
            div.style.gridColumn = '1 / -1';
        } else if (field.field_type === 'checkbox') {
            html += '<label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;text-transform:none;letter-spacing:0;font-size:0.9rem;"><input type="checkbox" name="' + inputName + '" value="1" style="width:auto;"> Sim</label>';
        } else {
            var inputType = (field.field_type === 'number' || field.field_type === 'decimal') ? 'number' : 'text';
            var step = field.field_type === 'decimal' ? ' step="0.01"' : '';
            html += '<input type="' + inputType + '"' + step + ' name="' + inputName + '" ' + reqAttr + ' placeholder="' + escapeHtml(field.placeholder || '') + '">';
        }

        div.innerHTML = html;
        grid.appendChild(div);
    });

    container.appendChild(grid);
}

function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
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

<?php include 'includes/footer.php'; ?>
