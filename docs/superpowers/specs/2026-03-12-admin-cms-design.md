# GeoPlasticoBR — Admin/CMS Design Spec

**Date:** 2026-03-12
**Status:** Approved
**Scope:** Backlog items 1–5 (Admin / CMS — configurações gerais)

## Goal

Transform the GeoPlasticoBR platform into a fully admin-configurable system where all public-facing content, data categories, measurement units, and concentration thresholds are manageable through the admin panel — without touching code.

## Decisions Made

| Question | Decision | Rationale |
|---|---|---|
| Content editing model | Structured blocks (per section, per page) | Prevents admin from breaking layout; predictable schema |
| Threshold granularity | Per measurement unit | Balances scientific precision with admin simplicity; upgradeable to per-unit+matrix later |
| Data type lifecycle | Soft delete with referential protection | Preserves scientific data integrity; types with linked records can only be deactivated |
| Logo management | Direct upload via admin panel | Full self-service; admin doesn't need server access |
| Architecture | Dedicated tables per domain | Clear schemas, simple queries, proper validation per table |
| Data type linkage | String-based matching (no FK migration) | Existing `microplastics_sediment` stores types as text; changing to FK IDs would require rewriting all queries, API, and map JS — too invasive for this phase |
| SVG uploads | Raster only (PNG/JPG) | SVG can contain embedded scripts (XSS); restricting to raster formats eliminates the risk entirely |

## Database Schema

### Table: `site_settings`

Institutional configuration as key/value pairs.

| Column | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | — |
| setting_key | VARCHAR(100) UNIQUE NOT NULL | e.g. `site_name`, `logo_path`, `contact_email` |
| setting_value | TEXT | Configuration value |
| setting_type | ENUM('text','image','url','email') | Determines validation and UI input type |
| updated_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | — |

**Initial keys:** `site_name`, `site_description`, `logo_path`, `favicon_path`, `contact_email`, `version_label`, `facebook_url`, `instagram_url`, `linkedin_url`, `footer_text`.

### Table: `site_blocks`

Editable content blocks for public pages.

| Column | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | — |
| page | VARCHAR(50) NOT NULL | e.g. `home`, `sobre`, `mapa` |
| block_key | VARCHAR(100) NOT NULL | e.g. `hero_title`, `hero_subtitle`, `hero_cta` |
| block_value | TEXT | Block content |
| block_order | INT DEFAULT 0 | Display order within page |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | — |
| updated_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | — |

**Constraint:** UNIQUE on `(page, block_key)`.

### Table: `data_types`

Dynamic categories for environment types, ecosystems, and matrices.

| Column | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | — |
| category | ENUM('ambiente','ecossistema','matriz') NOT NULL | Discriminator |
| name | VARCHAR(150) NOT NULL | e.g. "Marinho", "Sedimento" |
| description | TEXT NULL | Optional description |
| active | TINYINT(1) DEFAULT 1 | 1=active, 0=deactivated (soft delete) |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | — |

**Constraints:** UNIQUE on `(category, name)`. Index on `(category, active)`.

**Linkage strategy:** The existing `microplastics_sediment` table stores `tipo_ambiente`, `ecossistema`, and `matriz` as free-text VARCHAR columns. We do NOT migrate these to FK integers. Instead:
- `data_types.name` values must match the existing strings in `microplastics_sediment`
- Linked record detection uses `SELECT COUNT(*) FROM microplastics_sediment WHERE tipo_ambiente = :name` (and equivalent for ecossistema/matriz column depending on category)
- The `contribuir.php` form populates `<option>` values with `data_types.name` (text), so new records continue to store text values
- Migration seeds `data_types` with all distinct values already present in `microplastics_sediment`

**Deactivation rule:** Types whose `name` appears in any `microplastics_sediment` record can only be deactivated (not deleted). Types with zero matching records can be hard-deleted.

### Table: `measurement_units`

Accepted measurement units for concentration data.

| Column | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | — |
| name | VARCHAR(50) NOT NULL UNIQUE | e.g. "part/Kg", "part/m3", "mg/L" |
| description | TEXT NULL | Long description |
| active | TINYINT(1) DEFAULT 1 | — |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | — |

**Note:** Unit names must match existing `unidade` column values in `microplastics_sediment`. Migration seeds this table with `SELECT DISTINCT unidade FROM microplastics_sediment`.

### Table: `concentration_thresholds`

Severity ranges per measurement unit.

| Column | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | — |
| unit_id | INT NOT NULL | FK → `measurement_units.id` ON DELETE CASCADE |
| level | ENUM('baixo','medio','elevado','alto','critico') NOT NULL | Severity level |
| min_value | DECIMAL(15,4) NOT NULL | Lower bound |
| max_value | DECIMAL(15,4) NULL | Upper bound (NULL = no ceiling) |
| color | VARCHAR(7) NOT NULL | Hex color for map display, e.g. `#22c55e` |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | — |
| updated_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | — |

**Constraint:** UNIQUE on `(unit_id, level)`.

**5 levels** (matching current `map_v2.js` behavior): `baixo` < `medio` < `elevado` < `alto` < `critico`. Migration seeds default thresholds matching the current hardcoded JS breakpoints.

## Admin Panel Structure

The existing `admin.php` has 4 tabs: `users`, `pending_data`, `sediment`, `fish`. These are preserved as-is. 4 new tabs are added:

### Tab: Configurações (`?tab=configuracoes`)

- Form with fields for each `site_settings` key
- Input types vary by `setting_type`: text input, email input, URL input, file upload
- Logo upload: accepts PNG/JPG only (no SVG — XSS risk), max 2MB, validates MIME type (`image/png`, `image/jpeg`), saves to `assets/images/uploads/` with timestamp-sanitized filename
- Preview of current logo alongside upload field
- Single "Salvar" button batch-updates all settings

### Tab: Blocos de Conteúdo (`?tab=blocos`)

- Page selector dropdown (home, sobre, mapa)
- Lists all blocks for selected page with textarea for each
- Block order editable via numeric field
- "Salvar" updates all blocks for the selected page
- Blocks are pre-populated via migration with current hardcoded content

### Tab: Tipos de Dados (`?tab=tipos`)

- Sub-section toggle: Ambientes | Ecossistemas | Matrizes
- Table listing: name, description, status badge (ativo/inativo)
- "Adicionar" opens inline form (name + description)
- Edit: inline editing
- Deactivate: toggle button; linked record check via string match against `microplastics_sediment`
- If no linked records, shows delete option instead of deactivate
- Active count badge next to each sub-section toggle

### Tab: Unidades e Limiares (`?tab=unidades`)

- List of measurement units with name, description, status
- Click/expand to show 5 threshold rows (baixo/médio/elevado/alto/crítico)
- Each threshold: min input, max input, color picker
- Visual color scale preview beside the threshold editor
- "Adicionar unidade" creates unit + empty threshold rows
- "Salvar limiares" updates all thresholds for that unit

## File Architecture

### New Files

| File | Purpose |
|---|---|
| `config/cms.php` | Helper functions for reading CMS data |
| `admin/tab_configuracoes.php` | Settings tab logic + HTML |
| `admin/tab_blocos.php` | Content blocks tab logic + HTML |
| `admin/tab_tipos.php` | Data types tab logic + HTML |
| `admin/tab_unidades.php` | Units + thresholds tab logic + HTML |
| `sql/migration_cms.sql` | DDL + initial data inserts |
| `assets/images/uploads/` | Directory for uploaded images (logo, etc.) |

### Modified Files

| File | Change |
|---|---|
| `admin.php` | Add tab navigation for 4 new tabs; include new tab files |
| `includes/header.php` | Replace hardcoded site name, logo, nav text with `getSetting()` calls |
| `includes/footer.php` | Replace hardcoded footer text, social links, version with `getSetting()` calls |
| `index.php` | Replace hardcoded homepage text with `getBlocks('home')` |
| `sobre.php` | Replace hardcoded about text with `getBlocks('sobre')` |
| `contribuir.php` | Load select options from `getDataTypes('ambiente')`, etc. |
| `mapa.php` | Pass thresholds to JS for dynamic color coding |
| `js/map_v2.js` | Read thresholds from PHP-injected variable instead of hardcoded values |
| `api/get_microplastics.php` | Include threshold data in API response |

## Helper Functions — `config/cms.php`

```
getSetting($key)                → string: single setting value
getSettings()                   → array: all settings as key=>value
getBlocks($page)                → array: blocks for a page, ordered by block_order
getDataTypes($category)         → array: active types for a category (returns name strings)
getUnitsWithThresholds()        → array: active units with nested threshold objects
getThresholdColor($unitName, $val) → string: hex color for a concentration value + unit name
```

All functions use `static $cache` to avoid duplicate queries within the same request.

`getThresholdColor()` accepts the unit **name** (string), looks up the `measurement_units.id`, then finds the matching threshold range. Returns a default gray (`#6b7280`) if unit is unknown or value is outside all ranges.

## Data Flow: Thresholds → Map JS

`mapa.php` injects threshold data as a JSON variable:

```php
<script>
window.GEO_THRESHOLDS = <?= json_encode(getUnitsWithThresholds()) ?>;
// Structure: [{ name: "part/Kg", thresholds: [{ level: "baixo", min: 0, max: 1000, color: "#22c55e" }, ...] }, ...]
</script>
```

`map_v2.js` reads `window.GEO_THRESHOLDS` and replaces the current hardcoded `getConcentrationSeverity()` function with a dynamic lookup against this data.

## Security

- All new tabs require `role === 'admin'` (existing check in `admin.php`)
- CSRF token on ALL forms — including existing approve/reject forms that currently lack it (fix as part of this work)
- Prepared statements for all DB operations (existing pattern)
- Logo upload: MIME type whitelist (`image/png`, `image/jpeg` only — no SVG), 2MB max, filename sanitized with `time() . '_' . basename()`
- Data type deactivation checks for linked records via string match before allowing hard delete

## Migration Strategy

Zero-downtime migration:

1. Run `migration_cms.sql` to create tables
2. INSERT `site_settings` with current hardcoded values from header/footer
3. INSERT `site_blocks` with current hardcoded text from index.php and sobre.php
4. INSERT `data_types` seeded from `SELECT DISTINCT tipo_ambiente FROM microplastics_sediment` (and equivalent for ecossistema, matriz), plus any additional hardcoded options from `contribuir.php`
5. INSERT `measurement_units` seeded from `SELECT DISTINCT unidade FROM microplastics_sediment`
6. INSERT `concentration_thresholds` with current hardcoded breakpoints from `map_v2.js` (5 levels)
7. Deploy updated PHP files
8. Site continues working identically — same appearance, data now from database
9. Admin can immediately start editing via new tabs

## Scope Boundaries

**In scope:** Items 1–5 from backlog (settings, content blocks, data types, thresholds, institutional config). Also: CSRF fix on existing admin forms.

**Out of scope:** Map visualization (items 6–9), community features (items 10–13), metrics (14–15), onboarding (16–18), documentation (19–22). These will be designed and implemented in subsequent phases, building on the CMS foundation.

**Future consideration:** Migrating `microplastics_sediment` columns from free-text to FK integers referencing `data_types.id` and `measurement_units.id`. This would improve data integrity but requires rewriting queries, API responses, and map JS. Deferred to a dedicated refactoring phase.
