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

**Deactivation rule:** Types with linked records in `microplastics_sediment` can only be deactivated (not deleted). Types with zero linked records can be hard-deleted.

### Table: `measurement_units`

Accepted measurement units for concentration data.

| Column | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | — |
| name | VARCHAR(50) NOT NULL UNIQUE | e.g. "mg/kg", "itens/m²", "mg/L" |
| description | TEXT NULL | Long description |
| active | TINYINT(1) DEFAULT 1 | — |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | — |

### Table: `concentration_thresholds`

Severity ranges per measurement unit.

| Column | Type | Description |
|---|---|---|
| id | INT PK AUTO_INCREMENT | — |
| unit_id | INT NOT NULL | FK → `measurement_units.id` ON DELETE CASCADE |
| level | ENUM('baixo','medio','alto','critico') NOT NULL | Severity level |
| min_value | DECIMAL(15,4) NOT NULL | Lower bound |
| max_value | DECIMAL(15,4) NULL | Upper bound (NULL = no ceiling) |
| color | VARCHAR(7) NOT NULL | Hex color for map display, e.g. `#22c55e` |
| created_at | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | — |

**Constraint:** UNIQUE on `(unit_id, level)`.

## Admin Panel Structure

The existing `admin.php` gains 4 new tabs alongside the 2 existing ones (`usuarios`, `dados`).

### Tab: Configurações (`?tab=configuracoes`)

- Form with fields for each `site_settings` key
- Input types vary by `setting_type`: text input, email input, URL input, file upload
- Logo upload: accepts PNG/JPG/SVG, max 2MB, validates MIME type, saves to `assets/images/uploads/` with timestamp-sanitized filename
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
- Deactivate: toggle button; if no linked records, shows delete option instead
- Active count badge next to each sub-section toggle

### Tab: Unidades e Limiares (`?tab=unidades`)

- List of measurement units with name, description, status
- Click/expand to show 4 threshold rows (baixo/médio/alto/crítico)
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
| `admin.php` | Add tab navigation system; include new tab files |
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
getDataTypes($category)         → array: active types for a category
getUnitsWithThresholds()        → array: active units with nested thresholds
getThresholdColor($unit, $val)  → string: hex color for a concentration value + unit
```

All functions use `static $cache` to avoid duplicate queries within the same request.

## Security

- All new tabs require `role === 'admin'` (existing check in `admin.php`)
- CSRF token on all forms (existing pattern)
- Prepared statements for all DB operations (existing pattern)
- Logo upload: MIME type whitelist (`image/png`, `image/jpeg`, `image/svg+xml`), 2MB max, filename sanitized with `time() . '_' . basename()`
- Data type deactivation checks for linked records before allowing hard delete

## Migration Strategy

Zero-downtime migration:

1. Run `migration_cms.sql` to create tables
2. INSERT initial data extracted from current hardcoded values
3. Deploy updated PHP files
4. Site continues working identically — same appearance, data now from database
5. Admin can immediately start editing via new tabs

## Scope Boundaries

**In scope:** Items 1–5 from backlog (settings, content blocks, data types, thresholds, institutional config).

**Out of scope:** Map visualization (items 6–9), community features (items 10–13), metrics (14–15), onboarding (16–18), documentation (19–22). These will be designed and implemented in subsequent phases, building on the CMS foundation.
