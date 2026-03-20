# High Priority Features Design - GeoPlasticoBR

**Date:** 2026-03-20
**Status:** Approved
**Author:** Akira + Claude

## Summary

Three independent features to implement in order of complexity:
1. Conditional ecossistema filter (JS only)
2. Species registry with photo (new table + admin tab + popup image)
3. Dynamic contribution form (rewrite contribuir.php to use samples/sample_values)

## Feature 1: Conditional Ecossistema Filter

### Mapping

A single centralized mapping defines the relationship between tipo_ambiente and ecossistema:

```
Água doce    → [Lago, Reservatório, Rio, Córrego]
Água salgada → [Mangue, Ilha, Oceano, Estuário, Restinga, Apicum]
Terrestre    → [Floresta, Campo, Área urbana, Solo exposto]
```

This mapping is defined once in PHP and exposed as `window.GEO_ECO_MAP` for JS consumption, avoiding duplication.

**Legacy value handling:** Existing `sample_values` may contain old `tipo_ambiente` values (`"Doce"`, `"Salgado"`, `"Salobro"` from original seed data). The JS mapping must handle both old and new formats:
```
"Doce" OR "Água doce"       → [Lago, Reservatório, Rio, Córrego]
"Salgado" OR "Água salgada" → [Mangue, Ilha, Oceano, Estuário, Restinga, Apicum]
"Terrestre"                 → [Floresta, Campo, Área urbana, Solo exposto]
```

### Where It Applies

**1. Map (mapa.php + map_v2.js):**
- Add `tipo_ambiente` filter to the filter panel (dropdown select)
- When tipo_ambiente is selected, filter the ecossistema dropdown to show only matching options
- Fix `applyFilters()` to actually use `activeFilters.ecossistema` and new `activeFilters.tipo_ambiente` — compare against sample's dynamic fields array
- Also fix `activeFilters.concMin/concMax` — currently set by concentration chip filter but never checked in `applyFilters()` (same bug pattern)
- Currently `activeFilters.ecossistema` is set on change but never checked in the filter function (bug)
- Fix `clearFilter` handler to reset `tipo_ambiente` and `ecossistema` keys and their select elements

**2. Admin (tab_dados.php):**
- When the `tipo_ambiente` select changes, JS filters the `ecossistema` select options in the same form row

**3. Contribution form (contribuir.php):**
- Same behavior — selecting tipo_ambiente filters ecossistema options

### No Database Changes

The fields `tipo_ambiente` and `ecossistema` already exist in all 11 categories with all options. Filtering is 100% client-side JS.

## Feature 2: Species Registry with Photo

### New Table

```sql
CREATE TABLE species (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    scientific_name VARCHAR(200),
    image_path VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES sample_categories(id) ON DELETE CASCADE
);
```

### Admin — New "Espécies" Tab

- CRUD: name, scientific_name, category (select), photo (upload)
- List filtered by category (dropdown at top)
- Upload follows existing `uploadCategoryIcon()` pattern — saves to `uploads/species/`
- Limit: 2MB, formats PNG/JPG/WebP

### Field Change: especie becomes select

For all 8 biotic categories (Peixes, Corais, Bivalves, Plantas, Aves, Répteis, Mamíferos, Anfíbios):
- Field `especie` changes from `text` to `select`
- Options populated dynamically from `species` table filtered by `category_id`
- In admin (tab_dados.php) and contribution form (contribuir.php): AJAX fetches species for the selected category

Field `familia_biologica` remains as free text — families are too numerous and vary by study.

### New API Endpoint

`api/get_species.php?category_id=X` — returns species list as JSON:
```json
[{"id": 1, "name": "Tilápia", "scientific_name": "Oreochromis niloticus", "image_path": "uploads/species/sp_abc.jpg"}, ...]
```

### Map Popup Enhancement

- Species image is resolved via **post-processing** in PHP (not a JOIN in the generic EAV query): after `getSamplesForMap()` returns samples with their fields, a second pass detects `especie` fields and looks up the species image from the `species` table. This avoids modifying the generic EAV query for a single field's special behavior.
- In popup, if species has an image, display it at the top of the card (before header)
- Size: thumbnail ~120x80px, rounded corners
- Falls back to no image gracefully if species has no photo

### Migration

- Update `especie` field type from `text` to `select` in `category_fields` for all biotic categories
- The `select_options` for especie will NOT be stored in `category_fields.select_options` — instead, the admin/form JS will fetch from the `species` table via API (special handling for this field)
- **Existing data:** Currently only Peixes (210 samples) has `especie` field data (free text). Since species records don't exist yet, existing text values are preserved as-is in `sample_values.value_text`. New entries will store the species `name` (not ID) to maintain readability and backward compatibility. The popup displays the value directly — if a matching species record exists, it also shows the image.

## Feature 3: Dynamic Contribution Form

### Current State

`contribuir.php` has a fixed form structure using `getDataTypes()` for select options, inserting into legacy table `microplastics_sediment`. Must migrate to the dynamic system (`samples` + `sample_values`).

### Progressive Form Flow

```
Step 1: Tipo de matriz (Abiótica / Biótica)
    ↓
Step 2: Categoria (filtered by type — e.g., Abiótica → Sedimento, Água, Solo)
    ↓
Step 3: Fixed fields appear (coordinates, author, DOI, reference)
        + Dynamic fields loaded via AJAX from category_fields
        + Ecossistema filtered by tipo_ambiente (reuses Feature 1 mapping)
        + Espécie as select filtered by category (reuses Feature 2 API)
    ↓
Step 4: Submit → INSERT into samples + sample_values
```

### New API Endpoint

`api/get_category_fields.php?category_id=X` — returns active fields as JSON:
```json
[{"id": 58, "field_name": "cor", "field_label": "Cor", "field_type": "multicheck", "select_options": "[\"Azul\",\"Vermelho\",...]", "is_required": 0, "display_order": 4}, ...]
```

Uses existing `getCategoryFields()` from cms.php, just exposed as an endpoint.

### Form JS Rendering

When category is selected, AJAX fetches fields and renders them dynamically:
- `text` → `<input type="text">`
- `number` → `<input type="number">`
- `decimal` → `<input type="number" step="0.01">`
- `select` → `<select>` with options from `select_options` JSON
- `multicheck` → checkbox grid (same as admin tab_dados.php)
- `especie` field (select) → special handling: fetch from species API instead of select_options

### Submit Handler

PHP receives form data and:
1. INSERT into `samples` (title, latitude, longitude, author, reference_text, doi, category_id, approved=0)
2. Loop through dynamic fields, INSERT each into `sample_values` (sample_id, field_id, value_text or value_number)
3. `approved=0` by default — admin reviews before publishing

### Fixed Fields (always visible after category selection)

- Latitude (required)
- Longitude (required)
- Título/Nome do ponto (required)
- Autor(es)
- Referência bibliográfica
- DOI

### Dynamic Fields (loaded per category)

All fields from `category_fields` where `is_active=1`, ordered by `display_order`.

### Legacy Table

`microplastics_sediment` and `microplastics_fish` remain untouched. The new form inserts only into the dynamic system. Old data continues to be accessible via existing code paths.

### Validation

- Fields with `is_required=1`: validated client-side (HTML required attribute) and server-side before INSERT
- Coordinates: validated as valid lat/lng ranges
- Multicheck: stored as JSON array in `value_text`

## Implementation Notes

- **Authentication:** The contribution form requires login (`requireLogin()`), same as current. New API endpoints (`get_category_fields.php`, `get_species.php`) are public (read-only metadata). Submit endpoint requires login.
- **`approved=0` by default:** New submissions require admin approval before appearing on the map. Uses `$_SESSION['user_id']` for `submitted_by`.

## Implementation Order

1. **Feature 1** (ecossistema filter) — no DB changes, JS only, can be done first
2. **Feature 2** (species registry) — new table + admin tab + API + popup changes
3. **Feature 3** (dynamic form) — depends on Feature 1 (eco mapping) and Feature 2 (species API)

## Success Criteria

1. Selecting tipo_ambiente filters ecossistema options in map, admin, and contribuir.php
2. Map filter panel actually filters points by tipo_ambiente and ecossistema
3. Admin has species CRUD with photo upload per category
4. Map popups show species photo when available
5. contribuir.php loads fields dynamically based on selected category
6. New submissions go into samples + sample_values (not legacy tables)
7. No data loss — legacy data continues to work
