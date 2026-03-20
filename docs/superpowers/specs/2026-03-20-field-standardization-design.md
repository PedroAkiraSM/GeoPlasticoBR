# Field Standardization Design - GeoPlasticoBR

**Date:** 2026-03-20
**Status:** Approved
**Author:** Akira + Claude

## Summary

Standardize microplastic characteristic fields across all 11 sample categories in GeoPlasticoBR. Currently, fields are inconsistent: some categories have no fields, others use legacy individual checkboxes, and none have the full set of Cor, Forma, Polímero, and Dimensão fields required by the research methodology.

This is a **database-only migration** — no PHP, JS, or CSS changes needed for the admin panel. The dynamic field system (`category_fields` → `sample_values`) already renders admin forms and map popups automatically.

**Note:** `contribuir.php` (public contribution form) is hardcoded and does NOT use the dynamic field system. Updating it is a separate follow-up task.

## Current State

### Precondition Check
Before running the migration, verify the starting state:
```sql
SELECT id, name FROM sample_categories ORDER BY id;
```
Expected: 10 categories (Sedimento, Peixes, Água, Corais, Mamíferos, Solo, Bivalves, Plantas, Aves, Anfíbios). Répteis should NOT exist yet.

### Categories (10 exist, 1 missing)
| Category | Samples | Fields | Issues |
|----------|---------|--------|--------|
| Sedimento | 86 | Has `formas` (multicheck) | Needs rename to `forma`, missing cor/polimero/dimensao |
| Peixes | 210 | Has legacy individual checkboxes (fiber repurposed as multicheck) | Needs data migration to new `forma` field |
| Água | 75 | No fields | Needs all standard fields |
| Corais | 1 | Has wrong `matriz` field (lists organisms instead of substrate) | Inactivate `matriz`, add standard fields |
| Mamíferos | 0 | Has some fields | Needs standardization |
| Solo | 0 | No fields | Needs all standard fields |
| Bivalves | 0 | No fields | Needs all standard fields |
| Plantas | 0 | No fields | Needs all standard fields |
| Aves | 0 | No fields | Needs all standard fields |
| Anfíbios | 0 | No fields | Needs all standard fields |
| **Répteis** | — | **Does not exist** | **Must be created** |

### Data at Risk
- 372 total samples, 3222 sample_values
- Categories with data: Água (75), Sedimento (86), Peixes (210), Corais (1)
- Peixes `fiber` field (multicheck) has data that must be migrated to new `forma` field

## Design

### Field Structure

#### Block 1: Common Fields (ALL 11 categories)

| Field Name | Label | Type | Options | display_order |
|------------|-------|------|---------|---------------|
| sampling_point | Ponto de Coleta | text | — | 1 |
| tipo_ambiente | Tipo de Ambiente | select | Água doce, Água salgada, Terrestre | 2 |
| ecossistema | Ecossistema | select | Mangue, Ilha, Oceano, Estuário, Restinga, Apicum, Lago, Reservatório, Rio, Córrego, Floresta, Campo, Área urbana, Solo exposto | 3 |
| cor | Cor | multicheck | Azul, Vermelho, Preto, Branco, Transparente, Amarelo, Verde, Marrom, Cinza, Laranja, Rosa, Roxo, Multicolorido | 4 |
| forma | Forma | multicheck | Fibra, Fragmento, Filme, Pellet, Espuma, Esfera, Linha, Filamento | 5 |
| polimero | Polímero | multicheck | Polietileno (PE), Polipropileno (PP), Poliestireno (PS), PET, Nylon, PVC, Poliamida, Poliéster, Acrílico, EVA, Outro | 6 |
| dimensao | Dimensão | select | <0.1mm, 0.1-1mm, 1-5mm, >5mm | 7 |

**Known limitation:** `ecossistema` is stored as a flat select with all 14 options. Conditional filtering based on `tipo_ambiente` (e.g., showing only "Mangue, Ilha, Oceano..." when "Água salgada" is selected) will be implemented as a future JS enhancement. The database structure supports this — no schema change will be needed.

#### Block 2: Abiotic Categories (Sedimento, Água, Solo)

| Field Name | Label | Type | Options | display_order |
|------------|-------|------|---------|---------------|
| concentration_value | Concentração | decimal | — | 10 |
| unidade | Unidade | select | partículas/L, partículas/kg, partículas/m³, mg/kg, mg/L | 11 |
| depth | Profundidade | text | — | 12 |

**Note:** `concentration_value` intentionally shares the name with the legacy `samples.concentration_value` column to ease future data migration from the legacy system into EAV.

#### Block 3a: Biotic Concentration Categories (Corais, Bivalves, Plantas)

| Field Name | Label | Type | Options | display_order |
|------------|-------|------|---------|---------------|
| concentration_value | Concentração | decimal | — | 10 |
| unidade | Unidade | select | partículas/indivíduo, partículas/g, partículas/kg, mg/kg | 11 |
| familia_biologica | Família Biológica | text | — | 12 |
| especie | Espécie | text | — | 13 |

#### Block 3b: Biotic Individual Categories (Peixes, Aves, Répteis, Mamíferos, Anfíbios)

| Field Name | Label | Type | Options | display_order |
|------------|-------|------|---------|---------------|
| total_individuals | Total de Indivíduos | number | — | 10 |
| individuals_with_mp | Indivíduos Contaminados | number | — | 11 |
| familia_biologica | Família Biológica | text | — | 12 |
| especie | Espécie | text | — | 13 |

### Migration Plan

The SQL migration will execute the following steps inside a single transaction:

**Step 0: Backup** — Full database dump before execution

**Step 1: Create Répteis category** — Insert into `sample_categories` with appropriate color and display_order

**Step 2: Migrate Peixes `fiber` data** — The existing `fiber` field (multicheck) contains forma data stored as JSON arrays with English values. Migration is a **COPY** (INSERT new rows, keep originals):
1. Create new `forma` field for Peixes
2. For each sample_value referencing the old `fiber` field_id, INSERT a new row with the new `forma` field_id
3. Translate stored values from English to Portuguese:
   - `fiber` → `Fibra`
   - `fragment` → `Fragmento`
   - `film` → `Filme`
   - `pellet` → `Pellet`
   - `foam` → `Espuma`
   - `sphere` → `Esfera`
4. Original `fiber` sample_values rows are **kept** for rollback safety

**Step 3: Rename Sedimento `formas` → `forma`** — Update `category_fields` name from `formas` to `forma` for the Sedimento category (preserves existing data, update options to match standard list)

**Step 4: Inactivate Corais `matriz` field** — Set `is_active = 0` for the `matriz` field in Corais (has incorrect organism options; 1 sample may have data — preserve, don't delete)

**Step 5: Delete inactive/unused fields** — Remove fields with `is_active = 0` and no associated `sample_values` data

**Step 6: Inactivate replaced fields** — Set `is_active = 0` for legacy fields being replaced (e.g., Peixes individual checkbox fields: film, fragment, foam, pellets, sphere, fiber after data copy)

**Step 7: Insert standardized fields** — For each of the 11 categories, insert the appropriate Block 1 + Block 2/3a/3b fields that don't already exist. Use `display_order` values as specified in the field tables above. Set `is_required = 0` for all new fields (data availability varies by study).

### What This Does NOT Change

- **No PHP/JS/CSS changes for admin** — The dynamic field system renders admin forms and map popups automatically
- **`contribuir.php` remains hardcoded** — Public contribution form update is a separate follow-up task
- **No legacy table migration** — `microplastics_sediment` and `microplastics_fish` tables remain untouched (separate cleanup task)
- **No data loss** — Existing sample_values are preserved; fields are inactivated, not deleted, when they have data; fiber migration is a copy, not a move

### Rollback Strategy

- Full database backup before execution
- Transaction wrapping — any error rolls back all changes
- Inactivated fields can be reactivated if needed
- Fiber migration uses COPY — original sample_values rows with old field_id still exist and can be restored

## Success Criteria

1. All 11 categories exist in `sample_categories`
2. Each category has the correct Block 1 + Block 2/3a/3b fields with correct `display_order`
3. Field names, types, and options are consistent across categories
4. Peixes forma data migrated from legacy `fiber` field with Portuguese translations
5. No data loss — all existing sample_values preserved
6. Admin panel shows correct fields for each category
7. Map popups display multicheck values correctly
