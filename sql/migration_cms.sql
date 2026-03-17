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

-- Measurement Units (matching existing unidade column values)
INSERT IGNORE INTO measurement_units (name, description) VALUES
('part/Kg', 'Particulas por quilograma'),
('part/m³', 'Particulas por metro cubico'),
('part/m²', 'Particulas por metro quadrado'),
('part/L', 'Particulas por litro'),
('part/mL', 'Particulas por mililitro'),
('part/cm²', 'Particulas por centimetro quadrado');

-- Concentration Thresholds for all units
-- part/Kg
INSERT IGNORE INTO concentration_thresholds (unit_id, level, min_value, max_value, color) VALUES
((SELECT id FROM measurement_units WHERE name = 'part/Kg'), 'baixo', 0, 1000, '#00CC88'),
((SELECT id FROM measurement_units WHERE name = 'part/Kg'), 'medio', 1000, 3000, '#FFD700'),
((SELECT id FROM measurement_units WHERE name = 'part/Kg'), 'elevado', 3000, 5000, '#FFA500'),
((SELECT id FROM measurement_units WHERE name = 'part/Kg'), 'alto', 5000, 8000, '#FF6600'),
((SELECT id FROM measurement_units WHERE name = 'part/Kg'), 'critico', 8000, NULL, '#CC0000');

-- part/m³
INSERT IGNORE INTO concentration_thresholds (unit_id, level, min_value, max_value, color) VALUES
((SELECT id FROM measurement_units WHERE name = 'part/m³'), 'baixo', 0, 100, '#00CC88'),
((SELECT id FROM measurement_units WHERE name = 'part/m³'), 'medio', 100, 500, '#FFD700'),
((SELECT id FROM measurement_units WHERE name = 'part/m³'), 'elevado', 500, 1000, '#FFA500'),
((SELECT id FROM measurement_units WHERE name = 'part/m³'), 'alto', 1000, 5000, '#FF6600'),
((SELECT id FROM measurement_units WHERE name = 'part/m³'), 'critico', 5000, NULL, '#CC0000');

-- part/m²
INSERT IGNORE INTO concentration_thresholds (unit_id, level, min_value, max_value, color) VALUES
((SELECT id FROM measurement_units WHERE name = 'part/m²'), 'baixo', 0, 500, '#00CC88'),
((SELECT id FROM measurement_units WHERE name = 'part/m²'), 'medio', 500, 2000, '#FFD700'),
((SELECT id FROM measurement_units WHERE name = 'part/m²'), 'elevado', 2000, 5000, '#FFA500'),
((SELECT id FROM measurement_units WHERE name = 'part/m²'), 'alto', 5000, 10000, '#FF6600'),
((SELECT id FROM measurement_units WHERE name = 'part/m²'), 'critico', 10000, NULL, '#CC0000');

-- part/L
INSERT IGNORE INTO concentration_thresholds (unit_id, level, min_value, max_value, color) VALUES
((SELECT id FROM measurement_units WHERE name = 'part/L'), 'baixo', 0, 10, '#00CC88'),
((SELECT id FROM measurement_units WHERE name = 'part/L'), 'medio', 10, 50, '#FFD700'),
((SELECT id FROM measurement_units WHERE name = 'part/L'), 'elevado', 50, 100, '#FFA500'),
((SELECT id FROM measurement_units WHERE name = 'part/L'), 'alto', 100, 500, '#FF6600'),
((SELECT id FROM measurement_units WHERE name = 'part/L'), 'critico', 500, NULL, '#CC0000');

-- part/mL
INSERT IGNORE INTO concentration_thresholds (unit_id, level, min_value, max_value, color) VALUES
((SELECT id FROM measurement_units WHERE name = 'part/mL'), 'baixo', 0, 1, '#00CC88'),
((SELECT id FROM measurement_units WHERE name = 'part/mL'), 'medio', 1, 5, '#FFD700'),
((SELECT id FROM measurement_units WHERE name = 'part/mL'), 'elevado', 5, 10, '#FFA500'),
((SELECT id FROM measurement_units WHERE name = 'part/mL'), 'alto', 10, 50, '#FF6600'),
((SELECT id FROM measurement_units WHERE name = 'part/mL'), 'critico', 50, NULL, '#CC0000');

-- part/cm²
INSERT IGNORE INTO concentration_thresholds (unit_id, level, min_value, max_value, color) VALUES
((SELECT id FROM measurement_units WHERE name = 'part/cm²'), 'baixo', 0, 100, '#00CC88'),
((SELECT id FROM measurement_units WHERE name = 'part/cm²'), 'medio', 100, 500, '#FFD700'),
((SELECT id FROM measurement_units WHERE name = 'part/cm²'), 'elevado', 500, 1000, '#FFA500'),
((SELECT id FROM measurement_units WHERE name = 'part/cm²'), 'alto', 1000, 5000, '#FF6600'),
((SELECT id FROM measurement_units WHERE name = 'part/cm²'), 'critico', 5000, NULL, '#CC0000');
