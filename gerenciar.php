<?php
require_once __DIR__ . '/auth.php';
requireLogin();

// Apenas admin e scientist podem acessar
if (!isAdmin() && !isScientist()) {
    header('Location: /');
    exit;
}

$pageTitle = "Gerenciar Dados - GeoPlasticoBR";
$user = getCurrentUser();
include 'includes/header.php';
?>

<section class="crud-hero">
    <div class="content-container">
        <span class="section-tag">Gerenciamento</span>
        <h1 class="crud-title">Dados do Mapa</h1>
        <p class="crud-desc">Visualize, adicione, edite e remova registros de microplasticos.</p>
    </div>
</section>

<section class="crud-section">
    <div class="crud-container">

        <!-- Toolbar -->
        <div class="crud-toolbar">
            <div class="crud-search-wrap">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="crudSearch" placeholder="Buscar sistema, autor, ponto...">
            </div>
            <div class="crud-filters">
                <select id="crudFilterEnv">
                    <option value="">Ambiente: Todos</option>
                    <option value="Doce">Agua Doce</option>
                    <option value="Marinho">Marinho</option>
                </select>
                <select id="crudFilterApproved">
                    <option value="all">Status: Todos</option>
                    <option value="1">Aprovados</option>
                    <option value="0">Pendentes</option>
                </select>
            </div>
            <button class="crud-btn-add" id="btnAdd">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Novo Registro
            </button>
        </div>

        <!-- Stats bar -->
        <div class="crud-stats">
            <span id="crudStats">Carregando...</span>
        </div>

        <!-- Table -->
        <div class="crud-table-wrap">
            <table class="crud-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Sistema</th>
                        <th>Ambiente</th>
                        <th>Ecossistema</th>
                        <th>Concentracao</th>
                        <th>Coords</th>
                        <th>Autor</th>
                        <th>Referencia</th>
                        <th>DOI</th>
                        <th>Status</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody id="crudBody">
                    <tr><td colspan="11" style="text-align:center;padding:3rem;color:rgba(148,163,184,0.5);">Carregando dados...</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="crud-pagination" id="crudPagination"></div>

    </div>
</section>

<!-- Modal -->
<div class="crud-modal-overlay" id="modalOverlay">
    <div class="crud-modal">
        <div class="crud-modal-header">
            <h3 id="modalTitle">Novo Registro</h3>
            <button class="crud-modal-close" id="modalClose">&times;</button>
        </div>
        <form id="crudForm" class="crud-modal-body">
            <input type="hidden" name="id" id="formId">
            <div class="modal-grid">
                <div class="modal-field">
                    <label>Sistema Aquatico *</label>
                    <input type="text" name="system" id="formSystem" required placeholder="Ex: Amazon River">
                </div>
                <div class="modal-field">
                    <label>Ponto de Amostragem</label>
                    <input type="text" name="sampling_point" id="formSamplingPoint" placeholder="Ex: AMZ1">
                </div>
                <div class="modal-field">
                    <label>Tipo de Ambiente</label>
                    <select name="tipo_ambiente" id="formTipoAmbiente">
                        <option value="">Selecione...</option>
                        <option value="Doce">Doce</option>
                        <option value="Marinho">Marinho</option>
                    </select>
                </div>
                <div class="modal-field">
                    <label>Ecossistema</label>
                    <select name="ecossistema" id="formEcossistema">
                        <option value="">Selecione...</option>
                        <option value="Rio">Rio</option>
                        <option value="Lago">Lago</option>
                        <option value="Bacia">Bacia</option>
                        <option value="Córrego">Corrego</option>
                        <option value="Praia">Praia</option>
                        <option value="Estuário">Estuario</option>
                        <option value="Ilha">Ilha</option>
                        <option value="Região costeira">Regiao costeira</option>
                        <option value="Plataforma">Plataforma</option>
                        <option value="Oceano aberto">Oceano aberto</option>
                        <option value="Laguna">Laguna</option>
                    </select>
                </div>
                <div class="modal-field">
                    <label>Latitude</label>
                    <input type="number" step="0.000001" name="latitude" id="formLat" placeholder="-3.146601">
                </div>
                <div class="modal-field">
                    <label>Longitude</label>
                    <input type="number" step="0.000001" name="longitude" id="formLng" placeholder="-59.383157">
                </div>
                <div class="modal-field">
                    <label>Concentracao (texto)</label>
                    <input type="text" name="concentration_sediment" id="formConcText" placeholder="Ex: 2101/Kg">
                </div>
                <div class="modal-field">
                    <label>Valor Numerico</label>
                    <input type="number" step="0.01" name="concentration_value" id="formConcValue" placeholder="2101">
                </div>
                <div class="modal-field">
                    <label>Matriz</label>
                    <select name="matriz" id="formMatriz">
                        <option value="">Selecione...</option>
                        <option value="Sedimento">Sedimento</option>
                        <option value="Água">Agua</option>
                    </select>
                </div>
                <div class="modal-field">
                    <label>Unidade</label>
                    <select name="unidade" id="formUnidade">
                        <option value="">Selecione...</option>
                        <option value="part/Kg">part/Kg</option>
                        <option value="part/m³">part/m3</option>
                        <option value="part/m²">part/m2</option>
                        <option value="part/L">part/L</option>
                    </select>
                </div>
                <div class="modal-field full">
                    <label>Autor(es)</label>
                    <input type="text" name="author" id="formAuthor" placeholder="Silva, A. et al. (2023)">
                </div>
                <div class="modal-field full">
                    <label>Referencia</label>
                    <input type="text" name="reference" id="formReference" placeholder="Titulo do artigo">
                </div>
                <div class="modal-field full">
                    <label>DOI</label>
                    <input type="text" name="doi" id="formDoi" placeholder="Ex: https://doi.org/10.1016/j.scitotenv.2020.139484">
                </div>
                <div class="modal-field">
                    <label>Status</label>
                    <select name="approved" id="formApproved">
                        <option value="1">Aprovado</option>
                        <option value="0">Pendente</option>
                    </select>
                </div>
            </div>
            <div class="crud-modal-footer">
                <button type="button" class="btn-cancel" id="btnCancel">Cancelar</button>
                <button type="submit" class="btn-save" id="btnSave">Salvar</button>
            </div>
        </form>
    </div>
</div>

<style>
/* ===== HERO ===== */
.crud-hero {
    padding: clamp(7rem, 12vw, 10rem) clamp(1.5rem, 5vw, 3rem) clamp(2rem, 4vw, 3rem);
    text-align: center;
}
.crud-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: clamp(2rem, 5vw, 3.5rem); color: #fff; margin: 1rem 0 0.5rem; letter-spacing: -0.03em; }
.crud-desc { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1rem; color: rgba(148,163,184,0.7); }
.section-tag { display: inline-block; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.15em; color: #60A5FA; padding: 0.4rem 1.2rem; border: 1px solid rgba(96,165,250,0.25); border-radius: 50px; }

/* ===== SECTION ===== */
.crud-section { padding: 0 clamp(1rem, 4vw, 3rem) clamp(4rem, 8vw, 6rem); }
.crud-container { max-width: 1300px; margin: 0 auto; }

/* ===== TOOLBAR ===== */
.crud-toolbar {
    display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
    padding: 16px 20px;
    background: rgba(15,23,42,0.5);
    border: 1px solid rgba(148,163,184,0.08);
    border-radius: 14px;
    margin-bottom: 12px;
}
.crud-search-wrap {
    display: flex; align-items: center; gap: 8px;
    background: rgba(0,0,0,0.25); border: 1px solid rgba(148,163,184,0.1);
    border-radius: 8px; padding: 8px 12px; flex: 1; min-width: 200px;
}
.crud-search-wrap svg { color: rgba(148,163,184,0.4); flex-shrink: 0; }
.crud-search-wrap input { flex: 1; background: none; border: none; outline: none; color: #E2E8F0; font-size: 0.85rem; font-family: 'Plus Jakarta Sans', sans-serif; }
.crud-search-wrap input::placeholder { color: rgba(148,163,184,0.3); }
.crud-filters { display: flex; gap: 8px; }
.crud-filters select {
    background: rgba(0,0,0,0.25); border: 1px solid rgba(148,163,184,0.1);
    border-radius: 8px; padding: 8px 12px; color: #E2E8F0;
    font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.82rem;
}
.crud-filters select option { background: #1E293B; color: #E2E8F0; }
.crud-btn-add {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 18px; background: #60A5FA; color: #0F172A;
    border: none; border-radius: 8px; font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.85rem; font-weight: 700; cursor: pointer; transition: all 0.2s;
    white-space: nowrap;
}
.crud-btn-add:hover { background: #93C5FD; transform: translateY(-1px); }

/* ===== STATS ===== */
.crud-stats {
    padding: 8px 0; font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.8rem; color: rgba(148,163,184,0.5);
}

/* ===== TABLE ===== */
.crud-table-wrap {
    overflow-x: auto; border-radius: 14px;
    border: 1px solid rgba(148,163,184,0.08);
    background: rgba(15,23,42,0.4);
}
.crud-table {
    width: 100%; border-collapse: collapse;
    font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.82rem;
}
.crud-table thead { background: rgba(0,0,0,0.2); }
.crud-table th {
    padding: 12px 14px; text-align: left; font-weight: 600;
    color: rgba(148,163,184,0.7); font-size: 0.75rem;
    text-transform: uppercase; letter-spacing: 0.05em;
    white-space: nowrap; border-bottom: 1px solid rgba(148,163,184,0.08);
}
.crud-table td {
    padding: 10px 14px; color: rgba(226,232,240,0.85);
    border-bottom: 1px solid rgba(148,163,184,0.04);
    max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.crud-table tr:hover td { background: rgba(96,165,250,0.03); }
.badge-approved { background: rgba(52,211,153,0.15); color: #6EE7B7; padding: 3px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 600; }
.badge-pending { background: rgba(251,191,36,0.15); color: #FCD34D; padding: 3px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 600; }
.crud-actions { display: flex; gap: 6px; }
.crud-actions button {
    padding: 5px 10px; border: none; border-radius: 6px;
    font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.72rem;
    font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.btn-edit { background: rgba(96,165,250,0.15); color: #60A5FA; }
.btn-edit:hover { background: rgba(96,165,250,0.25); }
.btn-delete { background: rgba(239,68,68,0.12); color: #FCA5A5; }
.btn-delete:hover { background: rgba(239,68,68,0.22); }

/* ===== PAGINATION ===== */
.crud-pagination {
    display: flex; justify-content: center; gap: 6px; padding: 20px 0;
}
.crud-pagination button {
    padding: 6px 12px; background: rgba(15,23,42,0.5);
    border: 1px solid rgba(148,163,184,0.1); border-radius: 8px;
    color: rgba(148,163,184,0.7); font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.8rem; cursor: pointer; transition: all 0.2s;
}
.crud-pagination button:hover { border-color: rgba(96,165,250,0.3); color: #E2E8F0; }
.crud-pagination button.active { background: #60A5FA; color: #0F172A; border-color: #60A5FA; font-weight: 700; }

/* ===== MODAL ===== */
.crud-modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 10000;
    background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);
    align-items: center; justify-content: center; padding: 20px;
}
.crud-modal-overlay.open { display: flex; }
.crud-modal {
    width: 100%; max-width: 680px; max-height: 90vh; overflow-y: auto;
    background: #1E293B; border: 1px solid rgba(148,163,184,0.12);
    border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}
.crud-modal-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 20px 24px; border-bottom: 1px solid rgba(148,163,184,0.08);
}
.crud-modal-header h3 { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 700; font-size: 1.1rem; color: #fff; }
.crud-modal-close { background: none; border: none; color: rgba(148,163,184,0.5); font-size: 1.5rem; cursor: pointer; }
.crud-modal-close:hover { color: #fff; }
.crud-modal-body { padding: 24px; }
.modal-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.modal-field label {
    display: block; font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.75rem; font-weight: 600; color: rgba(148,163,184,0.7);
    margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.04em;
}
.modal-field input, .modal-field select {
    width: 100%; padding: 9px 12px; background: rgba(0,0,0,0.25);
    border: 1px solid rgba(148,163,184,0.1); border-radius: 8px;
    color: #E2E8F0; font-size: 0.85rem; font-family: 'Plus Jakarta Sans', sans-serif;
}
.modal-field input:focus, .modal-field select:focus { outline: none; border-color: rgba(96,165,250,0.4); }
.modal-field select option { background: #1E293B; color: #E2E8F0; }
.modal-field input::placeholder { color: rgba(148,163,184,0.3); }
.modal-field.full { grid-column: 1 / -1; }
.crud-modal-footer { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
.btn-cancel {
    padding: 9px 20px; background: rgba(148,163,184,0.1);
    border: 1px solid rgba(148,163,184,0.15); border-radius: 8px;
    color: rgba(148,163,184,0.8); font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.85rem; font-weight: 600; cursor: pointer;
}
.btn-save {
    padding: 9px 24px; background: #60A5FA; color: #0F172A;
    border: none; border-radius: 8px; font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.85rem; font-weight: 700; cursor: pointer;
}
.btn-save:hover { background: #93C5FD; }

@media (max-width: 768px) {
    .crud-toolbar { flex-direction: column; }
    .crud-filters { width: 100%; }
    .crud-filters select { flex: 1; }
    .crud-btn-add { width: 100%; justify-content: center; }
    .modal-grid { grid-template-columns: 1fr; }
}
</style>

<script>
(function() {
    var API = '/api/crud_microplastics.php';
    var currentPage = 1;
    var searchTimer;

    function loadData() {
        var search = document.getElementById('crudSearch').value.trim();
        var env = document.getElementById('crudFilterEnv').value;
        var approved = document.getElementById('crudFilterApproved').value;

        var url = API + '?page=' + currentPage;
        if (search) url += '&search=' + encodeURIComponent(search);
        if (env) url += '&tipo_ambiente=' + encodeURIComponent(env);
        if (approved !== 'all') url += '&approved=' + approved;

        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res.success) return;
                renderTable(res.data);
                renderPagination(res.page, res.pages);
                document.getElementById('crudStats').textContent =
                    'Mostrando ' + res.data.length + ' de ' + res.total + ' registros (pagina ' + res.page + ' de ' + res.pages + ')';
            });
    }

    function renderTable(data) {
        var tbody = document.getElementById('crudBody');
        if (data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:3rem;color:rgba(148,163,184,0.4);">Nenhum registro encontrado</td></tr>';
            return;
        }

        var html = '';
        data.forEach(function(row) {
            var statusBadge = row.approved == 1
                ? '<span class="badge-approved">Aprovado</span>'
                : '<span class="badge-pending">Pendente</span>';
            var coords = (row.latitude && row.longitude)
                ? parseFloat(row.latitude).toFixed(2) + ', ' + parseFloat(row.longitude).toFixed(2)
                : '-';

            html += '<tr>';
            html += '<td>' + row.id + '</td>';
            html += '<td title="' + escHtml(row.system || '') + '">' + escHtml(row.system || '-') + '</td>';
            html += '<td>' + escHtml(row.tipo_ambiente || '-') + '</td>';
            html += '<td>' + escHtml(row.ecossistema || '-') + '</td>';
            html += '<td>' + escHtml(row.concentration_sediment || (row.concentration_value || '-')) + '</td>';
            html += '<td>' + coords + '</td>';
            html += '<td title="' + escHtml(row.author || '') + '">' + escHtml(row.author || '-') + '</td>';
            html += '<td title="' + escHtml(row.reference || '') + '">' + escHtml(row.reference || '-') + '</td>';
            if (row.doi) {
                html += '<td><a href="' + escHtml(row.doi) + '" target="_blank" style="color:#60A5FA;text-decoration:underline;" title="' + escHtml(row.doi) + '">Link</a></td>';
            } else {
                html += '<td>-</td>';
            }
            html += '<td>' + statusBadge + '</td>';
            html += '<td><div class="crud-actions">';
            html += '<button class="btn-edit" onclick="editRow(' + row.id + ')">Editar</button>';
            html += '<button class="btn-delete" onclick="deleteRow(' + row.id + ')">Excluir</button>';
            html += '</div></td>';
            html += '</tr>';
        });
        tbody.innerHTML = html;
    }

    function renderPagination(page, pages) {
        var el = document.getElementById('crudPagination');
        if (pages <= 1) { el.innerHTML = ''; return; }

        var html = '';
        if (page > 1) html += '<button onclick="goPage(' + (page - 1) + ')">Anterior</button>';

        var start = Math.max(1, page - 2);
        var end = Math.min(pages, page + 2);
        for (var i = start; i <= end; i++) {
            html += '<button class="' + (i === page ? 'active' : '') + '" onclick="goPage(' + i + ')">' + i + '</button>';
        }

        if (page < pages) html += '<button onclick="goPage(' + (page + 1) + ')">Proximo</button>';
        el.innerHTML = html;
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ===== MODAL =====
    function openModal(title, data) {
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('formId').value = data ? data.id : '';
        document.getElementById('formSystem').value = data ? data.system || '' : '';
        document.getElementById('formSamplingPoint').value = data ? data.sampling_point || '' : '';
        document.getElementById('formTipoAmbiente').value = data ? data.tipo_ambiente || '' : '';
        document.getElementById('formEcossistema').value = data ? data.ecossistema || '' : '';
        document.getElementById('formLat').value = data ? data.latitude || '' : '';
        document.getElementById('formLng').value = data ? data.longitude || '' : '';
        document.getElementById('formConcText').value = data ? data.concentration_sediment || '' : '';
        document.getElementById('formConcValue').value = data ? data.concentration_value || '' : '';
        document.getElementById('formMatriz').value = data ? data.matriz || '' : '';
        document.getElementById('formUnidade').value = data ? data.unidade || '' : '';
        document.getElementById('formAuthor').value = data ? data.author || '' : '';
        document.getElementById('formReference').value = data ? data.reference || '' : '';
        document.getElementById('formDoi').value = data ? data.doi || '' : '';
        document.getElementById('formApproved').value = data ? (data.approved || '0') : '1';
        document.getElementById('modalOverlay').classList.add('open');
    }

    function closeModal() {
        document.getElementById('modalOverlay').classList.remove('open');
    }

    // ===== GLOBAL FUNCTIONS =====
    window.goPage = function(p) { currentPage = p; loadData(); };

    window.editRow = function(id) {
        fetch(API + '?page=1&search=' + id)
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success && res.data.length > 0) {
                    var found = res.data.find(function(d) { return d.id == id; });
                    if (found) openModal('Editar Registro #' + id, found);
                }
            });
    };

    window.deleteRow = function(id) {
        if (!confirm('Tem certeza que deseja excluir o registro #' + id + '?')) return;
        fetch(API, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) loadData();
            else alert('Erro: ' + res.error);
        });
    };

    // ===== EVENTS =====
    document.addEventListener('DOMContentLoaded', function() {
        loadData();

        document.getElementById('crudSearch').addEventListener('input', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() { currentPage = 1; loadData(); }, 400);
        });

        document.getElementById('crudFilterEnv').addEventListener('change', function() { currentPage = 1; loadData(); });
        document.getElementById('crudFilterApproved').addEventListener('change', function() { currentPage = 1; loadData(); });

        document.getElementById('btnAdd').addEventListener('click', function() {
            openModal('Novo Registro', null);
        });

        document.getElementById('modalClose').addEventListener('click', closeModal);
        document.getElementById('btnCancel').addEventListener('click', closeModal);
        document.getElementById('modalOverlay').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        document.getElementById('crudForm').addEventListener('submit', function(e) {
            e.preventDefault();
            var id = document.getElementById('formId').value;
            var payload = {
                system: document.getElementById('formSystem').value,
                sampling_point: document.getElementById('formSamplingPoint').value,
                tipo_ambiente: document.getElementById('formTipoAmbiente').value,
                ecossistema: document.getElementById('formEcossistema').value,
                latitude: document.getElementById('formLat').value,
                longitude: document.getElementById('formLng').value,
                concentration_sediment: document.getElementById('formConcText').value,
                concentration_value: document.getElementById('formConcValue').value,
                matriz: document.getElementById('formMatriz').value,
                unidade: document.getElementById('formUnidade').value,
                author: document.getElementById('formAuthor').value,
                reference: document.getElementById('formReference').value,
                doi: document.getElementById('formDoi').value,
                approved: document.getElementById('formApproved').value
            };

            var method = 'POST';
            if (id) {
                method = 'PUT';
                payload.id = parseInt(id);
            }

            fetch(API, {
                method: method,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.success) {
                    closeModal();
                    loadData();
                } else {
                    alert('Erro: ' + res.error);
                }
            });
        });
    });
})();
</script>

<?php include 'includes/footer.php'; ?>
