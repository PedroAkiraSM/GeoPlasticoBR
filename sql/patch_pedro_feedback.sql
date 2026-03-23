-- Patch: Pedro feedback fixes (2026-03-22)

-- 1. Change dimensao from select to text in all categories
UPDATE category_fields SET field_type = 'text', select_options = NULL
WHERE field_name = 'dimensao';

-- 2. Add "Multicolorido" to Cor multicheck options in all categories
-- Current options: ["Azul","Vermelho","Preto","Branco","Transparente","Amarelo","Verde","Marrom","Cinza","Laranja","Rosa","Roxo"]
-- New: add "Multicolorido" at the end
UPDATE category_fields
SET select_options = '["Azul","Vermelho","Preto","Branco","Transparente","Amarelo","Verde","Marrom","Cinza","Laranja","Rosa","Roxo","Multicolorido"]'
WHERE field_name = 'cor' AND field_type = 'multicheck';
