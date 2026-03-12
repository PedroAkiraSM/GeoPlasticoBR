<?php
require_once __DIR__ . '/auth.php';
requireLogin();

require_once 'config/database.php';

$message = '';
$messageType = '';
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_data'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Erro de validacao. Recarregue a pagina e tente novamente.';
        $messageType = 'error';
    } else {
    $pdo = getDatabaseConnection();

    try {
        $stmt = $pdo->prepare("INSERT INTO microplastics_sediment
            (tipo_ambiente, ecossistema, system, sampling_point, latitude, longitude,
             concentration_sediment, concentration_value, matriz, unidade,
             author, reference, doi, submitted_by, approved)
            VALUES (:tipo_ambiente, :ecossistema, :system, :sampling_point, :latitude, :longitude,
                    :concentration_sediment, :concentration_value, :matriz, :unidade,
                    :author, :reference, :doi, :submitted_by, 0)");

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
            ':author' => $_POST['author'] ?: null,
            ':reference' => $_POST['reference'] ?: null,
            ':doi' => $_POST['doi'] ?: null,
            ':submitted_by' => $_SESSION['user_id'],
        ]);

        $message = 'Dados enviados com sucesso! Eles serao revisados pelo administrador antes de aparecer no mapa.';
        $messageType = 'success';
    } catch (PDOException $e) {
        $message = 'Erro ao enviar dados: ' . $e->getMessage();
        $messageType = 'error';
    }
    } // end csrf check
}

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

        <?php if ($message): ?>
        <div class="contrib-msg <?php echo $messageType === 'success' ? 'contrib-msg-success' : 'contrib-msg-error'; ?>">
            <?php if ($messageType === 'success'): ?>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            <?php else: ?>
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <?php endif; ?>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" class="contrib-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <!-- Bloco: Classificacao -->
            <div class="form-block">
                <div class="form-block-header">
                    <div class="form-block-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                    </div>
                    <h3>Classificacao</h3>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Tipo de Ambiente <span class="req">*</span></label>
                        <select name="tipo_ambiente" required>
                            <option value="">Selecione...</option>
                            <option value="Doce">Agua Doce</option>
                            <option value="Marinho">Marinho</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Ecossistema</label>
                        <select name="ecossistema">
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
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Matriz</label>
                        <select name="matriz">
                            <option value="">Selecione...</option>
                            <option value="Sedimento">Sedimento</option>
                            <option value="Água">Agua</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Unidade</label>
                        <select name="unidade">
                            <option value="">Selecione...</option>
                            <option value="part/Kg">part/Kg</option>
                            <option value="part/m³">part/m3</option>
                            <option value="part/m²">part/m2</option>
                            <option value="part/L">part/L</option>
                            <option value="part/mL">part/mL</option>
                            <option value="part/cm²">part/cm2</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Bloco: Local -->
            <div class="form-block">
                <div class="form-block-header">
                    <div class="form-block-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <h3>Localizacao</h3>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Sistema Aquatico <span class="req">*</span></label>
                        <input type="text" name="system" required placeholder="Ex: Amazon River, Santos beach">
                    </div>
                    <div class="form-field">
                        <label>Ponto de Amostragem</label>
                        <input type="text" name="sampling_point" placeholder="Ex: AMZ1, P1">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Latitude</label>
                        <input type="number" step="0.000001" name="latitude" id="lat-input" placeholder="Ex: -3.146601">
                    </div>
                    <div class="form-field">
                        <label>Longitude</label>
                        <input type="number" step="0.000001" name="longitude" id="lng-input" placeholder="Ex: -59.383157">
                    </div>
                </div>
                <div class="form-field-full">
                    <label>Clique no mapa para selecionar coordenadas</label>
                    <div id="minimap"></div>
                    <p class="map-hint">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        Clique no mapa para preencher latitude e longitude automaticamente
                    </p>
                </div>
            </div>

            <!-- Bloco: Dados -->
            <div class="form-block">
                <div class="form-block-header">
                    <div class="form-block-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    </div>
                    <h3>Dados da Amostra</h3>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Concentracao (texto)</label>
                        <input type="text" name="concentration_sediment" placeholder="Ex: 2101/Kg, 55.1 ± 1.1/m3">
                    </div>
                    <div class="form-field">
                        <label>Valor Numerico</label>
                        <input type="number" step="0.01" name="concentration_value" placeholder="Ex: 2101">
                    </div>
                </div>
            </div>

            <!-- Bloco: Referencia -->
            <div class="form-block">
                <div class="form-block-header">
                    <div class="form-block-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    </div>
                    <h3>Referencia Bibliografica</h3>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label>Autor(es)</label>
                        <input type="text" name="author" placeholder="Ex: Silva, A. et al. (2023)">
                    </div>
                    <div class="form-field">
                        <label>Referencia</label>
                        <input type="text" name="reference" placeholder="Titulo do artigo cientifico">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field" style="grid-column: 1 / -1;">
                        <label>DOI</label>
                        <input type="text" name="doi" placeholder="Ex: https://doi.org/10.1016/j.scitotenv.2020.139484">
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" name="submit_data" class="contrib-submit">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                Enviar para Revisao
            </button>
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

/* ===== RESPONSIVE ===== */
@media (max-width: 640px) {
    .form-row {
        grid-template-columns: 1fr;
    }

    .form-block {
        padding: 1.25rem;
    }
}
</style>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var minimap = L.map('minimap').setView([-15.78, -47.93], 4);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; CartoDB',
        maxZoom: 18
    }).addTo(minimap);

    var marker = null;

    minimap.on('click', function(e) {
        var lat = e.latlng.lat.toFixed(6);
        var lng = e.latlng.lng.toFixed(6);
        document.getElementById('lat-input').value = lat;
        document.getElementById('lng-input').value = lng;

        if (marker) minimap.removeLayer(marker);
        marker = L.marker([lat, lng]).addTo(minimap);
    });
});
</script>

<?php include 'includes/footer.php'; ?>
