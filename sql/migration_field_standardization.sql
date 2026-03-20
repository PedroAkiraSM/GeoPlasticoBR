-- =============================================================================
-- GeoPlasticoBR - Field Standardization Migration
-- Date: 2026-03-20
-- Spec: docs/superpowers/specs/2026-03-20-field-standardization-design.md
--
-- IMPORTANT: Take a full database backup before running this migration!
-- =============================================================================

START TRANSACTION;

-- =============================================================================
-- STEP 0.5: Add 'multicheck' to field_type ENUM
-- =============================================================================
ALTER TABLE category_fields
  MODIFY COLUMN field_type ENUM('text','number','decimal','checkbox','multicheck','select','textarea')
  NOT NULL DEFAULT 'text';

-- =============================================================================
-- STEP 1: Create Répteis category
-- =============================================================================
INSERT INTO sample_categories (name, type, icon, color, is_active, display_order)
VALUES ('Répteis', 'biotico', 'diamond', '#8b5cf6', 1, 11);

SET @repteis_id = LAST_INSERT_ID();

-- =============================================================================
-- STEP 2: Update existing fields for categories WITH DATA
-- =============================================================================

-- --- SEDIMENTO (id=1) ---
-- Update tipo_ambiente options (id=2)
UPDATE category_fields SET select_options = '["Água doce","Água salgada","Terrestre"]'
WHERE id = 2;

-- Update ecossistema options (id=3)
UPDATE category_fields SET select_options = '["Mangue","Ilha","Oceano","Estuário","Restinga","Apicum","Lago","Reservatório","Rio","Córrego","Floresta","Campo","Área urbana","Solo exposto"]'
WHERE id = 3;

-- Update unidade options (id=7)
UPDATE category_fields SET select_options = '["partículas/L","partículas/kg","partículas/m³","mg/kg","mg/L"]'
WHERE id = 7;

-- Fix formas → forma: rename, set type to multicheck, update options (id=40)
UPDATE category_fields SET
  field_name = 'forma',
  field_label = 'Forma',
  field_type = 'multicheck',
  select_options = '["Fibra","Fragmento","Filme","Pellet","Espuma","Esfera","Linha","Filamento"]'
WHERE id = 40;

-- Inactivate Sedimento fields not in spec
UPDATE category_fields SET is_active = 0 WHERE id IN (
  4,  -- matriz
  5,  -- concentration_sediment
  8,  -- total_concentration
  9   -- concentration_variation (already inactive)
);

-- --- PEIXES (id=2) ---
-- Rename familia → familia_biologica (id=28)
UPDATE category_fields SET field_name = 'familia_biologica', field_label = 'Família Biológica'
WHERE id = 28;

-- Rename ecosssistema → ecossistema, update options (id=41)
UPDATE category_fields SET
  field_name = 'ecossistema',
  field_label = 'Ecossistema',
  select_options = '["Mangue","Ilha","Oceano","Estuário","Restinga","Apicum","Lago","Reservatório","Rio","Córrego","Floresta","Campo","Área urbana","Solo exposto"]'
WHERE id = 41;

-- Rename tipo_de_ambiente → tipo_ambiente, update options (id=42)
UPDATE category_fields SET
  field_name = 'tipo_ambiente',
  field_label = 'Tipo de Ambiente',
  select_options = '["Água doce","Água salgada","Terrestre"]'
WHERE id = 42;

-- Inactivate Peixes fields not in spec
UPDATE category_fields SET is_active = 0 WHERE id IN (
  11, -- habit
  14, -- fiber (checkbox, only stores 1/0)
  15, -- film (already inactive)
  16, -- fragment (already inactive)
  17, -- foam (already inactive)
  18, -- pellets (already inactive)
  19, -- sphere (already inactive)
  20, -- plastic_dimension
  21, -- occurrence_tissues
  22  -- freshwater_system
);

-- Update display_order for kept Peixes fields
UPDATE category_fields SET display_order = 1 WHERE id = 42;  -- tipo_ambiente
UPDATE category_fields SET display_order = 2 WHERE id = 41;  -- ecossistema
UPDATE category_fields SET display_order = 10 WHERE id = 12; -- total_individuals
UPDATE category_fields SET display_order = 11 WHERE id = 13; -- individuals_with_mp
UPDATE category_fields SET display_order = 12 WHERE id = 28; -- familia_biologica
UPDATE category_fields SET display_order = 13 WHERE id = 47; -- especie

-- --- ÁGUA (id=3) ---
-- Update unidade options (id=25)
UPDATE category_fields SET select_options = '["partículas/L","partículas/kg","partículas/m³","mg/kg","mg/L"]'
WHERE id = 25;

-- Update display_order for kept Água fields
UPDATE category_fields SET display_order = 1 WHERE id = 23;  -- sampling_point
UPDATE category_fields SET display_order = 10 WHERE id = 24; -- concentration_value
UPDATE category_fields SET display_order = 11 WHERE id = 25; -- unidade
UPDATE category_fields SET display_order = 12 WHERE id = 26; -- depth

-- Inactivate volume_filtered (not in spec, keep data)
UPDATE category_fields SET is_active = 0, display_order = 99 WHERE id = 27;

-- --- CORAIS (id=7) ---
-- Rename ponto_de_amostragem → sampling_point (id=53)
UPDATE category_fields SET field_name = 'sampling_point', field_label = 'Ponto de Coleta'
WHERE id = 53;

-- Rename tipo_de_ambiente → tipo_ambiente, update options (id=54)
UPDATE category_fields SET
  field_name = 'tipo_ambiente',
  field_label = 'Tipo de Ambiente',
  select_options = '["Água doce","Água salgada","Terrestre"]'
WHERE id = 54;

-- Update ecossistema options (id=55)
UPDATE category_fields SET select_options = '["Mangue","Ilha","Oceano","Estuário","Restinga","Apicum","Lago","Reservatório","Rio","Córrego","Floresta","Campo","Área urbana","Solo exposto"]'
WHERE id = 55;

-- Rename valor_numerico → concentration_value (id=51)
UPDATE category_fields SET
  field_name = 'concentration_value',
  field_label = 'Concentração',
  field_type = 'decimal'
WHERE id = 51;

-- Update unidade options (id=52)
UPDATE category_fields SET select_options = '["partículas/indivíduo","partículas/g","partículas/kg","mg/kg"]'
WHERE id = 52;

-- Inactivate Corais fields not in spec
UPDATE category_fields SET is_active = 0 WHERE id IN (
  39, -- concentracao (text, replaced by valor_numerico→concentration_value)
  56  -- matriz (wrong options)
);

-- Update display_order for kept Corais fields
UPDATE category_fields SET display_order = 1 WHERE id = 53;  -- sampling_point
UPDATE category_fields SET display_order = 2 WHERE id = 54;  -- tipo_ambiente
UPDATE category_fields SET display_order = 3 WHERE id = 55;  -- ecossistema
UPDATE category_fields SET display_order = 10 WHERE id = 51; -- concentration_value
UPDATE category_fields SET display_order = 11 WHERE id = 52; -- unidade
UPDATE category_fields SET display_order = 12 WHERE id = 50; -- familia_biologica

-- --- MAMÍFEROS (id=8, 0 samples) ---
-- Rename n_total → total_individuals (id=45)
UPDATE category_fields SET field_name = 'total_individuals', field_label = 'Total de Indivíduos'
WHERE id = 45;

-- Rename n_com_microplasticos → individuals_with_mp (id=46)
UPDATE category_fields SET field_name = 'individuals_with_mp', field_label = 'Indivíduos Contaminados'
WHERE id = 46;

-- Inactivate Mamíferos fields not in spec
UPDATE category_fields SET is_active = 0 WHERE id IN (
  48, -- bioma
  49  -- orgaos_analisados
);

-- Update display_order for kept Mamíferos fields
UPDATE category_fields SET display_order = 10 WHERE id = 45; -- total_individuals
UPDATE category_fields SET display_order = 11 WHERE id = 46; -- individuals_with_mp
UPDATE category_fields SET display_order = 12 WHERE id = 44; -- familia_biologica

-- =============================================================================
-- STEP 3: Delete fields that are inactive AND have no sample data
-- =============================================================================
DELETE cf FROM category_fields cf
LEFT JOIN sample_values sv ON sv.field_id = cf.id
WHERE cf.is_active = 0 AND sv.id IS NULL;

-- =============================================================================
-- STEP 4: Insert standardized fields for ALL categories
-- Standard options used across all inserts:
--   tipo_ambiente: ["Água doce","Água salgada","Terrestre"]
--   ecossistema: ["Mangue","Ilha","Oceano","Estuário","Restinga","Apicum","Lago","Reservatório","Rio","Córrego","Floresta","Campo","Área urbana","Solo exposto"]
--   cor: ["Azul","Vermelho","Preto","Branco","Transparente","Amarelo","Verde","Marrom","Cinza","Laranja","Rosa","Roxo","Multicolorido"]
--   forma: ["Fibra","Fragmento","Filme","Pellet","Espuma","Esfera","Linha","Filamento"]
--   polimero: ["Polietileno (PE)","Polipropileno (PP)","Poliestireno (PS)","PET","Nylon","PVC","Poliamida","Poliéster","Acrílico","EVA","Outro"]
--   dimensao: ["<0.1mm","0.1-1mm","1-5mm",">5mm"]
-- =============================================================================

-- Helper: we'll insert only fields that don't already exist per category
-- Using INSERT IGNORE with a unique check isn't possible, so we use conditional inserts

-- ---- SEDIMENTO (id=1): needs cor, polimero, dimensao ----
INSERT INTO category_fields (category_id, field_name, field_label, field_type, select_options, is_required, display_order, is_active)
VALUES
(1, 'cor', 'Cor', 'multicheck', '["Azul","Vermelho","Preto","Branco","Transparente","Amarelo","Verde","Marrom","Cinza","Laranja","Rosa","Roxo","Multicolorido"]', 0, 4, 1),
(1, 'polimero', 'Polímero', 'multicheck', '["Polietileno (PE)","Polipropileno (PP)","Poliestireno (PS)","PET","Nylon","PVC","Poliamida","Poliéster","Acrílico","EVA","Outro"]', 0, 6, 1),
(1, 'dimensao', 'Dimensão', 'select', '["<0.1mm","0.1-1mm","1-5mm",">5mm"]', 0, 7, 1);

-- Update forma display_order (already renamed from formas)
UPDATE category_fields SET display_order = 5 WHERE id = 40;

-- ---- PEIXES (id=2): needs sampling_point, cor, forma, polimero, dimensao ----
INSERT INTO category_fields (category_id, field_name, field_label, field_type, select_options, is_required, display_order, is_active)
VALUES
(2, 'sampling_point', 'Ponto de Coleta', 'text', NULL, 0, 3, 1),
(2, 'cor', 'Cor', 'multicheck', '["Azul","Vermelho","Preto","Branco","Transparente","Amarelo","Verde","Marrom","Cinza","Laranja","Rosa","Roxo","Multicolorido"]', 0, 4, 1),
(2, 'forma', 'Forma', 'multicheck', '["Fibra","Fragmento","Filme","Pellet","Espuma","Esfera","Linha","Filamento"]', 0, 5, 1),
(2, 'polimero', 'Polímero', 'multicheck', '["Polietileno (PE)","Polipropileno (PP)","Poliestireno (PS)","PET","Nylon","PVC","Poliamida","Poliéster","Acrílico","EVA","Outro"]', 0, 6, 1),
(2, 'dimensao', 'Dimensão', 'select', '["<0.1mm","0.1-1mm","1-5mm",">5mm"]', 0, 7, 1);

-- ---- ÁGUA (id=3): needs tipo_ambiente, ecossistema, cor, forma, polimero, dimensao ----
INSERT INTO category_fields (category_id, field_name, field_label, field_type, select_options, is_required, display_order, is_active)
VALUES
(3, 'tipo_ambiente', 'Tipo de Ambiente', 'select', '["Água doce","Água salgada","Terrestre"]', 0, 2, 1),
(3, 'ecossistema', 'Ecossistema', 'select', '["Mangue","Ilha","Oceano","Estuário","Restinga","Apicum","Lago","Reservatório","Rio","Córrego","Floresta","Campo","Área urbana","Solo exposto"]', 0, 3, 1),
(3, 'cor', 'Cor', 'multicheck', '["Azul","Vermelho","Preto","Branco","Transparente","Amarelo","Verde","Marrom","Cinza","Laranja","Rosa","Roxo","Multicolorido"]', 0, 4, 1),
(3, 'forma', 'Forma', 'multicheck', '["Fibra","Fragmento","Filme","Pellet","Espuma","Esfera","Linha","Filamento"]', 0, 5, 1),
(3, 'polimero', 'Polímero', 'multicheck', '["Polietileno (PE)","Polipropileno (PP)","Poliestireno (PS)","PET","Nylon","PVC","Poliamida","Poliéster","Acrílico","EVA","Outro"]', 0, 6, 1),
(3, 'dimensao', 'Dimensão', 'select', '["<0.1mm","0.1-1mm","1-5mm",">5mm"]', 0, 7, 1);

-- ---- BIVALVES (id=4): needs ALL fields (Block 1 + Block 3a) ----
INSERT INTO category_fields (category_id, field_name, field_label, field_type, select_options, is_required, display_order, is_active)
VALUES
(4, 'sampling_point', 'Ponto de Coleta', 'text', NULL, 0, 1, 1),
(4, 'tipo_ambiente', 'Tipo de Ambiente', 'select', '["Água doce","Água salgada","Terrestre"]', 0, 2, 1),
(4, 'ecossistema', 'Ecossistema', 'select', '["Mangue","Ilha","Oceano","Estuário","Restinga","Apicum","Lago","Reservatório","Rio","Córrego","Floresta","Campo","Área urbana","Solo exposto"]', 0, 3, 1),
(4, 'cor', 'Cor', 'multicheck', '["Azul","Vermelho","Preto","Branco","Transparente","Amarelo","Verde","Marrom","Cinza","Laranja","Rosa","Roxo","Multicolorido"]', 0, 4, 1),
(4, 'forma', 'Forma', 'multicheck', '["Fibra","Fragmento","Filme","Pellet","Espuma","Esfera","Linha","Filamento"]', 0, 5, 1),
(4, 'polimero', 'Polímero', 'multicheck', '["Polietileno (PE)","Polipropileno (PP)","Poliestireno (PS)","PET","Nylon","PVC","Poliamida","Poliéster","Acrílico","EVA","Outro"]', 0, 6, 1),
(4, 'dimensao', 'Dimensão', 'select', '["<0.1mm","0.1-1mm","1-5mm",">5mm"]', 0, 7, 1),
(4, 'concentration_value', 'Concentração', 'decimal', NULL, 0, 10, 1),
(4, 'unidade', 'Unidade', 'select', '["partículas/indivíduo","partículas/g","partículas/kg","mg/kg"]', 0, 11, 1),
(4, 'familia_biologica', 'Família Biológica', 'text', NULL, 0, 12, 1),
(4, 'especie', 'Espécie', 'text', NULL, 0, 13, 1);

-- ---- SOLO (id=5): needs ALL fields (Block 1 + Block 2) ----
INSERT INTO category_fields (category_id, field_name, field_label, field_type, select_options, is_required, display_order, is_active)
VALUES
(5, 'sampling_point', 'Ponto de Coleta', 'text', NULL, 0, 1, 1),
(5, 'tipo_ambiente', 'Tipo de Ambiente', 'select', '["Água doce","Água salgada","Terrestre"]', 0, 2, 1),
(5, 'ecossistema', 'Ecossistema', 'select', '["Mangue","Ilha","Oceano","Estuário","Restinga","Apicum","Lago","Reservatório","Rio","Córrego","Floresta","Campo","Área urbana","Solo exposto"]', 0, 3, 1),
(5, 'cor', 'Cor', 'multicheck', '["Azul","Vermelho","Preto","Branco","Transparente","Amarelo","Verde","Marrom","Cinza","Laranja","Rosa","Roxo","Multicolorido"]', 0, 4, 1),
(5, 'forma', 'Forma', 'multicheck', '["Fibra","Fragmento","Filme","Pellet","Espuma","Esfera","Linha","Filamento"]', 0, 5, 1),
(5, 'polimero', 'Polímero', 'multicheck', '["Polietileno (PE)","Polipropileno (PP)","Poliestireno (PS)","PET","Nylon","PVC","Poliamida","Poliéster","Acrílico","EVA","Outro"]', 0, 6, 1),
(5, 'dimensao', 'Dimensão', 'select', '["<0.1mm","0.1-1mm","1-5mm",">5mm"]', 0, 7, 1),
(5, 'concentration_value', 'Concentração', 'decimal', NULL, 0, 10, 1),
(5, 'unidade', 'Unidade', 'select', '["partículas/L","partículas/kg","partículas/m³","mg/kg","mg/L"]', 0, 11, 1),
(5, 'depth', 'Profundidade', 'text', NULL, 0, 12, 1);

-- ---- PLANTAS (id=6): needs ALL fields (Block 1 + Block 3a) ----
INSERT INTO category_fields (category_id, field_name, field_label, field_type, select_options, is_required, display_order, is_active)
VALUES
(6, 'sampling_point', 'Ponto de Coleta', 'text', NULL, 0, 1, 1),
(6, 'tipo_ambiente', 'Tipo de Ambiente', 'select', '["Água doce","Água salgada","Terrestre"]', 0, 2, 1),
(6, 'ecossistema', 'Ecossistema', 'select', '["Mangue","Ilha","Oceano","Estuário","Restinga","Apicum","Lago","Reservatório","Rio","Córrego","Floresta","Campo","Área urbana","Solo exposto"]', 0, 3, 1),
(6, 'cor', 'Cor', 'multicheck', '["Azul","Vermelho","Preto","Branco","Transparente","Amarelo","Verde","Marrom","Cinza","Laranja","Rosa","Roxo","Multicolorido"]', 0, 4, 1),
(6, 'forma', 'Forma', 'multicheck', '["Fibra","Fragmento","Filme","Pellet","Espuma","Esfera","Linha","Filamento"]', 0, 5, 1),
(6, 'polimero', 'Polímero', 'multicheck', '["Polietileno (PE)","Polipropileno (PP)","Poliestireno (PS)","PET","Nylon","PVC","Poliamida","Poliéster","Acrílico","EVA","Outro"]', 0, 6, 1),
(6, 'dimensao', 'Dimensão', 'select', '["<0.1mm","0.1-1mm","1-5mm",">5mm"]', 0, 7, 1),
(6, 'concentration_value', 'Concentração', 'decimal', NULL, 0, 10, 1),
(6, 'unidade', 'Unidade', 'select', '["partículas/indivíduo","partículas/g","partículas/kg","mg/kg"]', 0, 11, 1),
(6, 'familia_biologica', 'Família Biológica', 'text', NULL, 0, 12, 1),
(6, 'especie', 'Espécie', 'text', NULL, 0, 13, 1);

-- ---- CORAIS (id=7): needs cor, forma, polimero, dimensao, especie ----
INSERT INTO category_fields (category_id, field_name, field_label, field_type, select_options, is_required, display_order, is_active)
VALUES
(7, 'cor', 'Cor', 'multicheck', '["Azul","Vermelho","Preto","Branco","Transparente","Amarelo","Verde","Marrom","Cinza","Laranja","Rosa","Roxo","Multicolorido"]', 0, 4, 1),
(7, 'forma', 'Forma', 'multicheck', '["Fibra","Fragmento","Filme","Pellet","Espuma","Esfera","Linha","Filamento"]', 0, 5, 1),
(7, 'polimero', 'Polímero', 'multicheck', '["Polietileno (PE)","Polipropileno (PP)","Poliestireno (PS)","PET","Nylon","PVC","Poliamida","Poliéster","Acrílico","EVA","Outro"]', 0, 6, 1),
(7, 'dimensao', 'Dimensão', 'select', '["<0.1mm","0.1-1mm","1-5mm",">5mm"]', 0, 7, 1),
(7, 'especie', 'Espécie', 'text', NULL, 0, 13, 1);

-- ---- MAMÍFEROS (id=8): needs sampling_point, tipo_ambiente, ecossistema, cor, forma, polimero, dimensao, especie ----
INSERT INTO category_fields (category_id, field_name, field_label, field_type, select_options, is_required, display_order, is_active)
VALUES
(8, 'sampling_point', 'Ponto de Coleta', 'text', NULL, 0, 1, 1),
(8, 'tipo_ambiente', 'Tipo de Ambiente', 'select', '["Água doce","Água salgada","Terrestre"]', 0, 2, 1),
(8, 'ecossistema', 'Ecossistema', 'select', '["Mangue","Ilha","Oceano","Estuário","Restinga","Apicum","Lago","Reservatório","Rio","Córrego","Floresta","Campo","Área urbana","Solo exposto"]', 0, 3, 1),
(8, 'cor', 'Cor', 'multicheck', '["Azul","Vermelho","Preto","Branco","Transparente","Amarelo","Verde","Marrom","Cinza","Laranja","Rosa","Roxo","Multicolorido"]', 0, 4, 1),
(8, 'forma', 'Forma', 'multicheck', '["Fibra","Fragmento","Filme","Pellet","Espuma","Esfera","Linha","Filamento"]', 0, 5, 1),
(8, 'polimero', 'Polímero', 'multicheck', '["Polietileno (PE)","Polipropileno (PP)","Poliestireno (PS)","PET","Nylon","PVC","Poliamida","Poliéster","Acrílico","EVA","Outro"]', 0, 6, 1),
(8, 'dimensao', 'Dimensão', 'select', '["<0.1mm","0.1-1mm","1-5mm",">5mm"]', 0, 7, 1),
(8, 'especie', 'Espécie', 'text', NULL, 0, 13, 1);

-- ---- AVES (id=9): needs ALL fields (Block 1 + Block 3b) ----
INSERT INTO category_fields (category_id, field_name, field_label, field_type, select_options, is_required, display_order, is_active)
VALUES
(9, 'sampling_point', 'Ponto de Coleta', 'text', NULL, 0, 1, 1),
(9, 'tipo_ambiente', 'Tipo de Ambiente', 'select', '["Água doce","Água salgada","Terrestre"]', 0, 2, 1),
(9, 'ecossistema', 'Ecossistema', 'select', '["Mangue","Ilha","Oceano","Estuário","Restinga","Apicum","Lago","Reservatório","Rio","Córrego","Floresta","Campo","Área urbana","Solo exposto"]', 0, 3, 1),
(9, 'cor', 'Cor', 'multicheck', '["Azul","Vermelho","Preto","Branco","Transparente","Amarelo","Verde","Marrom","Cinza","Laranja","Rosa","Roxo","Multicolorido"]', 0, 4, 1),
(9, 'forma', 'Forma', 'multicheck', '["Fibra","Fragmento","Filme","Pellet","Espuma","Esfera","Linha","Filamento"]', 0, 5, 1),
(9, 'polimero', 'Polímero', 'multicheck', '["Polietileno (PE)","Polipropileno (PP)","Poliestireno (PS)","PET","Nylon","PVC","Poliamida","Poliéster","Acrílico","EVA","Outro"]', 0, 6, 1),
(9, 'dimensao', 'Dimensão', 'select', '["<0.1mm","0.1-1mm","1-5mm",">5mm"]', 0, 7, 1),
(9, 'total_individuals', 'Total de Indivíduos', 'number', NULL, 0, 10, 1),
(9, 'individuals_with_mp', 'Indivíduos Contaminados', 'number', NULL, 0, 11, 1),
(9, 'familia_biologica', 'Família Biológica', 'text', NULL, 0, 12, 1),
(9, 'especie', 'Espécie', 'text', NULL, 0, 13, 1);

-- ---- ANFÍBIOS (id=10): needs ALL except familia_biologica (Block 1 + Block 3b) ----
INSERT INTO category_fields (category_id, field_name, field_label, field_type, select_options, is_required, display_order, is_active)
VALUES
(10, 'sampling_point', 'Ponto de Coleta', 'text', NULL, 0, 1, 1),
(10, 'tipo_ambiente', 'Tipo de Ambiente', 'select', '["Água doce","Água salgada","Terrestre"]', 0, 2, 1),
(10, 'ecossistema', 'Ecossistema', 'select', '["Mangue","Ilha","Oceano","Estuário","Restinga","Apicum","Lago","Reservatório","Rio","Córrego","Floresta","Campo","Área urbana","Solo exposto"]', 0, 3, 1),
(10, 'cor', 'Cor', 'multicheck', '["Azul","Vermelho","Preto","Branco","Transparente","Amarelo","Verde","Marrom","Cinza","Laranja","Rosa","Roxo","Multicolorido"]', 0, 4, 1),
(10, 'forma', 'Forma', 'multicheck', '["Fibra","Fragmento","Filme","Pellet","Espuma","Esfera","Linha","Filamento"]', 0, 5, 1),
(10, 'polimero', 'Polímero', 'multicheck', '["Polietileno (PE)","Polipropileno (PP)","Poliestireno (PS)","PET","Nylon","PVC","Poliamida","Poliéster","Acrílico","EVA","Outro"]', 0, 6, 1),
(10, 'dimensao', 'Dimensão', 'select', '["<0.1mm","0.1-1mm","1-5mm",">5mm"]', 0, 7, 1),
(10, 'total_individuals', 'Total de Indivíduos', 'number', NULL, 0, 10, 1),
(10, 'individuals_with_mp', 'Indivíduos Contaminados', 'number', NULL, 0, 11, 1),
(10, 'especie', 'Espécie', 'text', NULL, 0, 13, 1);

-- ---- RÉPTEIS (@repteis_id): ALL fields (Block 1 + Block 3b) ----
INSERT INTO category_fields (category_id, field_name, field_label, field_type, select_options, is_required, display_order, is_active)
VALUES
(@repteis_id, 'sampling_point', 'Ponto de Coleta', 'text', NULL, 0, 1, 1),
(@repteis_id, 'tipo_ambiente', 'Tipo de Ambiente', 'select', '["Água doce","Água salgada","Terrestre"]', 0, 2, 1),
(@repteis_id, 'ecossistema', 'Ecossistema', 'select', '["Mangue","Ilha","Oceano","Estuário","Restinga","Apicum","Lago","Reservatório","Rio","Córrego","Floresta","Campo","Área urbana","Solo exposto"]', 0, 3, 1),
(@repteis_id, 'cor', 'Cor', 'multicheck', '["Azul","Vermelho","Preto","Branco","Transparente","Amarelo","Verde","Marrom","Cinza","Laranja","Rosa","Roxo","Multicolorido"]', 0, 4, 1),
(@repteis_id, 'forma', 'Forma', 'multicheck', '["Fibra","Fragmento","Filme","Pellet","Espuma","Esfera","Linha","Filamento"]', 0, 5, 1),
(@repteis_id, 'polimero', 'Polímero', 'multicheck', '["Polietileno (PE)","Polipropileno (PP)","Poliestireno (PS)","PET","Nylon","PVC","Poliamida","Poliéster","Acrílico","EVA","Outro"]', 0, 6, 1),
(@repteis_id, 'dimensao', 'Dimensão', 'select', '["<0.1mm","0.1-1mm","1-5mm",">5mm"]', 0, 7, 1),
(@repteis_id, 'total_individuals', 'Total de Indivíduos', 'number', NULL, 0, 10, 1),
(@repteis_id, 'individuals_with_mp', 'Indivíduos Contaminados', 'number', NULL, 0, 11, 1),
(@repteis_id, 'familia_biologica', 'Família Biológica', 'text', NULL, 0, 12, 1),
(@repteis_id, 'especie', 'Espécie', 'text', NULL, 0, 13, 1);

COMMIT;

-- =============================================================================
-- VERIFICATION QUERIES (run after migration)
-- =============================================================================
-- SELECT sc.name, COUNT(cf.id) as field_count
-- FROM sample_categories sc
-- LEFT JOIN category_fields cf ON cf.category_id = sc.id AND cf.is_active = 1
-- GROUP BY sc.id ORDER BY sc.id;
--
-- Expected: each category should have 10-11 active fields
--
-- SELECT sc.name, cf.field_name, cf.field_type, cf.display_order
-- FROM category_fields cf
-- JOIN sample_categories sc ON cf.category_id = sc.id
-- WHERE cf.is_active = 1
-- ORDER BY sc.id, cf.display_order;
