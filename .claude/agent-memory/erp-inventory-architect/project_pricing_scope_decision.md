---
name: Pricing feature scope decision (V1)
description: Minimal pricing added now is margin-analysis only — NOT the sales module; sale_price is a single reference field per variant
type: project
---

Pricing feature decided 2026-04-20 to be scoped as margin-analysis visibility only, explicitly NOT a sales module. CLAUDE.md originally excluded "Precios de venta" from scope; this V1 adds it narrowly as reference data.

**Why:** Client asked on WhatsApp for a table showing purchase cost, "cuánto te queda" (landed cost w/ IVA), and sale price with margin. Doing it as full price-lists/tiers would expand into Sales-module territory that is explicitly deferred. A single `sale_price` field gives immediate value and migrates cleanly to `price_list_items` later.

**How to apply:**
- Add `Variant.sale_price` (decimal 10,2, nullable). Single price, no list concept.
- Add `iva_rate` at Product or Category level (default 21%) — lets "landed cost" computation evolve to include freight/percepciones later.
- Accessor `Variant.landed_cost` = `cost_price * (1 + iva_rate)` for V1.
- Accessor `Variant.margin_percent` = `(sale_price - landed_cost) / sale_price`.
- Filament page/widget with a margin-analysis table (SKU, name, purchase cost, landed cost, sale price, margin %), filterable by category/supplier.
- DO NOT build in V1: multiple price lists, automatic markup %, wholesale/retail tiers, discounts. These belong to the Sales module.
- When the real Sales module arrives, `Variant.sale_price` becomes the "base price" or migrates to `price_list_items`.
- CLAUDE.md should be updated to reflect that sale_price exists as reference/analysis data, not as sales-module input.
