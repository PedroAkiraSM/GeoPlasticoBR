# Field Standardization Design - GeoPlasticoBR

**Date:** 2026-03-20
**Status:** Approved
**Author:** Akira + Claude

## Summary

Standardize microplastic characteristic fields across all 11 sample categories in GeoPlasticoBR. Currently, fields are inconsistent: some categories have no fields, others use legacy individual checkboxes, and none have the full set of Cor, Forma, Polímero, and Dimensão fields required by the research methodology.

This is a **database-only migration** — no PHP, JS, or CSS changes needed. The dynamic field system (`category_fields` → `sample_values`) already renders everything automatically.

## Current State

### Categories (10 exist, 1 missing)
| Category | Samples | Fields | Issues |
|----------|---------|--------|--------|
| Sedimento | 86 | Has `formas` (multicheck) | Needs rename to `forma`, missing cor/polimero/dimensao |
| Peixes | 210 | Has legacy individual checkboxes (fiber repurposed as multicheck) | Needs data migration to new `forma` field |
| Água | 75 | No fields | Needs all standard fields |
| Corais | 1 | Has wrong `matriz` options | Needs correction + standard fields |
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

| Field Name | Label | Type | Options |
|------------|-------|------|---------|
| sampling_point | Ponto de Coleta | text | — |
| tipo_ambiente | Tipo de Ambiente | select | Água doce, Água salgada, Terrestre |
| ecossistema | Ecossistema | select | Mangue, Ilha, Oceano, Estuário, Restinga, Apicum, Lago, Reservatório, Rio, Córrego, Floresta, Campo, Área urbana, Solo exposto |
| cor | Cor | multicheck | Azul, Vermelho, Preto, Branco, Transparente, Amarelo, Verde, Marrom, Cinza, Laranja, Rosa, Roxo, Multicolorido |
| polimero | Polímero | multicheck | Polietileno (PE), Polipropileno (PP), Poliestireno (PS), PET, Nylon, PVC, Poliamida, Poliéster, Acrílico, EVA, Outro |
| forma | Forma | multicheck | Fibra, Fragmento, Filme, Pellet, Espuma, Esfera, Linha, Filamento |
| dimensao | Dimensão | select | <0.1mm, 0.1-1mm, 1-5mm, >5mm |

#### Block 2: Abiotic Categories (Sedimento, Água, Solo)

| Field Name | Label | Type | Options |
|------------|-------|------|---------|
| concentration_value | Concentração | decimal | — |
| unidade | Unidade | select | partículas/L, partículas/kg, partículas/m³, mg/kg, mg/L |
| depth | Profundidade | text | — |

#### Block 3a: Biotic Concentration Categories (Corais, Bivalves, Plantas)

| Field Name | Label | Type | Options |
|------------|-------|------|---------|
| concentration_value | Concentração | decimal | — |
| unidade | Unidade | select | partículas/indivíduo, partículas/g, partículas/kg, mg/kg |
| familia_biologica | Família Biológica | text | — |
| especie | Espécie | text | — |

#### Block 3b: Biotic Individual Categories (Peixes, Aves, Répteis, Mamíferos, Anfíbios)

| Field Name | Label | Type | Options |
|------------|-------|------|---------|
| total_individuals | Total de Indivíduos | number | — |
| individuals_with_mp | Indivíduos Contaminados | number | — |
| familia_biologica | Família Biológica | text | — |
| especie | Espécie | text | — |

### Migration Plan

The SQL migration will execute the following steps inside a single transaction:

1. **Create Répteis category** — Insert into `sample_categories` with appropriate color and sort_order

2. **Migrate Peixes `fiber` data** — The existing `fiber` field (multicheck) contains forma data. Copy all `sample_values` from the old `fiber` field_id to the new `forma` field_id for Peixes samples

3. **Rename Sedimento `formas` → `forma`** — Update `category_fields` name from `formas` to `forma` for the Sedimento category (preserves existing data)

4. **Delete inactive/unused fields** — Remove fields with `is_active = 0` and no associated `sample_values` data

5. **Inactivate replaced fields** — Set `is_active = 0` for legacy fields being replaced (e.g., Peixes individual checkbox fields: film, fragment, foam, pellets, sphere, fiber after data migration)

6. **Insert standardized fields** — For each category, insert the appropriate Block 1 + Block 2/3a/3b fields that don't already exist. Use `sort_order` to maintain logical field ordering. Set `is_required = 0` for all new fields (data availability varies by study).

### What This Does NOT Change

- **No PHP/JS/CSS changes** — The dynamic field system renders everything automatically
- **No legacy table migration** — `microplastics_sediment` and `microplastics_fish` tables remain untouched (separate cleanup task)
- **No data loss** — Existing sample_values are preserved; fields are inactivated, not deleted, when they have data
- **No frontend form changes** — `contribuir.php` and admin forms dynamically render from `category_fields`

### Rollback Strategy

- Full database backup before execution
- Transaction wrapping — any error rolls back all changes
- Inactivated fields can be reactivated if needed
- Migrated data retains original field reference in sample_values

## Success Criteria

1. All 11 categories exist in `sample_categories`
2. Each category has the correct Block 1 + Block 2/3a/3b fields
3. Field names, types, and options are consistent across categories
4. Peixes forma data migrated from legacy `fiber` field
5. No data loss — all existing sample_values preserved
6. Admin panel shows correct fields for each category
7. Map popups display multicheck values correctly
8. Contribution form renders all fields dynamically
