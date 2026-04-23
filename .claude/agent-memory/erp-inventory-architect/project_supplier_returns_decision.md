---
name: Supplier returns & credit notes — architecture decision
description: Decision to model supplier credit notes as first-class entities with virtual-payment integration, not PO adjustments
type: project
---

Supplier returns feature decided 2026-04-20 to be modeled as `SupplierCreditNote` + `SupplierCreditNoteItem` first-class entities, separate from PurchaseOrder.

**Why:** In Argentina a nota de crédito is a real tax document with its own CAE/number/date. Modeling it as a PO adjustment would force a re-model when Tango/AFIP integration arrives. NCs must also be issuable without a PO (defective stock from old inventory, commercial bonuses without physical return).

**How to apply:**
- NC links optionally to PO and/or SupplierInvoice (both nullable FKs).
- NC application against invoice balance uses the existing `SupplierPayment` mechanism: add `SupplierPaymentMethod::CreditNote` and create a payment row with `method=credit_note`, `supplier_credit_note_id` FK. `SupplierInvoice::recalculateFromPayments()` then works unchanged.
- A NC can be unapplied (saldo a favor) or split across multiple invoices — `applied_amount` is derived by summing linked payments.
- Stock impact: `StockMovement(type=Out, reason=Return, reference=SupplierCreditNote)`. The `Return` enum value already exists in StockMovementReason and was reserved for this.
- Location per item is mandatory — no default. Validate only against InventoryLevel.quantity, NOT against PO received quantities (stock mixes across POs in reality; NCs can exist without PO).
- Known V1 limitation: bonificaciones retroactivas de precio do NOT auto-update SupplierVariantPriceLog — requires manual cost_price adjustment.
