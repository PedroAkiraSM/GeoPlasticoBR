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
