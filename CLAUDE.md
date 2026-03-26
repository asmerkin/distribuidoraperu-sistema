# Distribuidora Perú — Sistema de Gestión de Inventario

## Contexto

Distribuidora Perú es una distribuidora de artículos de oficina ubicada en Mendoza, Argentina. Actualmente no tiene ningún sistema digital. Vende tanto a consumidor final (D2C/mostrador) como a empresas/clientes mayoristas (B2B). Este sistema es su primera herramienta de gestión.

## Objetivo

Construir un sistema web de gestión de inventario con módulos de: Catálogo + Stock (con conteo físico), Compras (Purchase Orders con recepción y confirmación de precios), Proveedores (con facturas y pagos), y gestión de usuarios. El sistema debe ser simple, rápido de usar, y mobile-friendly para uso en depósito.

## Stack Técnico

- **Backend:** Laravel 13 (PHP 8.5)
- **Admin UI:** Filament v5 (panel principal del sistema, no solo admin)
- **Base de datos:** SQLite (desarrollo) / MySQL 8 (producción)
- **Auth:** Laravel built-in, perfil de usuario con Filament, CRUD de usuarios
- **Queue:** Laravel Queues (para envío de emails de POs)
- **PDF generation:** Laravel DomPDF o similar (para POs) — pendiente
- **Storage:** S3 (via league/flysystem-aws-s3-v3) para archivos adjuntos
- **Deployment:** DigitalOcean App Platform
- **Idioma:** Locale `es` (español), traducciones via `lang/es/`

## Filament v5 — Cambios clave vs v3

- **Actions:** Ya no existe `Filament\Tables\Actions\Action`. Todas las actions (tabla, header, form, etc.) usan `Filament\Actions\Action`, `Filament\Actions\EditAction`, `Filament\Actions\DeleteAction`, etc. desde el namespace `Filament\Actions\`.
- **Schema:** `Filament\Forms\Form` se reemplazó por `Filament\Schemas\Schema`. El método en Resources sigue siendo `form(Schema $schema)` y `table(Schema $schema)`.
- **Imports:** Siempre verificar que los imports usen los namespaces de Filament v5, no los de v3.

## Convenciones de Código

- **IDs:** ULID (usa `HasUlids` trait)
- **Money:** almacenar en decimal(10,2), sin minor units
- **Enums:** PHP backed enums con **valores en inglés** (ej: `Draft = 'draft'`, `In = 'in'`). Labels en español via `__('enums.xxx')` con archivo `lang/es/enums.php`
- **Services:** lógica de negocio en Services, no en Controllers ni en Models. `InventoryService` es el punto central para todo movimiento de stock
- **Actions:** usar Filament Actions para operaciones (enviar PO, recibir, ajustar, transferir)
- **Tests:** Feature tests para flujos críticos con Pest
- **UI:** Todo en español. Los `modelLabel`, `navigationLabel`, notificaciones, etc. van directamente en español en el código Filament (no se usan traducciones para UI labels)
- **Código:** Todo en inglés (nombres de clases, métodos, variables, enum cases, valores de DB)

## Módulos

### 1. Catálogo de Productos (modelo Shopify)

La estructura sigue el patrón de Shopify: un Product tiene una o más Variants. El inventario se trackea a nivel de Variant + Location (InventoryLevel). Un producto sin opciones tiene igualmente una única "default" variant.

**Modelos:** `Product`, `ProductOption`, `ProductOptionValue`, `Variant`, `Location`, `InventoryLevel`, `Category`

**Tabla pivot: `product_supplier`** — Relación many-to-many entre productos y proveedores (solo asociación, sin datos extra).

**Relaciones clave:**
```
Product hasMany Variants
Product hasMany ProductOptions → each hasMany ProductOptionValues
Product belongsToMany Suppliers (via product_supplier)
Variant belongsToMany ProductOptionValues (via variant_option_values)
Variant hasMany InventoryLevels
Location hasMany InventoryLevels
InventoryLevel belongsTo Variant + Location
```

**Stock total de una variant:** `SUM(inventory_levels.quantity)` across all locations.
**Stock total de un product:** `SUM(inventory_levels.quantity)` across all its variants and locations.

### 2. Gestión de Stock

**Modelo: `StockMovement`** — Todo movimiento de stock es inmutable y pasa por `InventoryService`.

**Enums:**
- `StockMovementType`: `In`, `Out`, `Adjustment`, `Transfer`
- `StockMovementReason`: `Purchase`, `StockCount`, `Shrinkage`, `Return`, `TransferIn`, `TransferOut`

**Reglas de negocio:**
- Nunca se modifica `InventoryLevel.quantity` directamente
- `InventoryService::recordMovement()` centraliza toda la lógica
- Si no existe el `InventoryLevel` para esa variant+location, se crea automáticamente
- Historial inmutable — se crean ajustes compensatorios para corregir
- Transferencias generan 2 movimientos: salida en origin + entrada en destino

**Funcionalidades:** Conteo físico (PWA Scanner — ver sección PWA), Transferencias (StockTransferPage), Historial de movimientos (StockMovementHistory), Ajuste rápido por variante (InventoryRelationManager en ProductResource)

### 3. Compras — Purchase Orders

**Modelos:** `Supplier`, `PurchaseOrder`, `PurchaseOrderItem`, `PurchaseOrderReceipt`, `PurchaseOrderReceiptItem`

**Enum `PurchaseOrderStatus`:** `Draft`, `Sent`, `PartiallyReceived`, `Received`, `Cancelled`

**Flujo de la Purchase Order:**
```
1. CREAR (Draft) → proveedor, location destino, variants, cantidades, precios
2. ENVIAR (Sent) → marca sent_at (PDF/email pendiente de implementar)
3. RECIBIR → modal con cantidad + precio por línea
   → Crea PurchaseOrderReceipt con items (historial de recepciones)
   → Si precio difiere: actualiza unit_cost de la línea, recalcula subtotal
   → Actualiza cost_price de la variante con precio confirmado
   → Genera StockMovements de entrada
   → Status → Received o PartiallyReceived
4. ANULAR RECEPCIÓN → revierte stock (crea salidas), resta cantidades, marca receipt como voided
5. CANCELAR → solo en Draft o Sent
```

**View de PO:** Infolist read-only con detalle + tab de Recepciones (RelationManager con acciones Ver/Anular)

### 4. Facturas de Proveedores

**Modelos:** `SupplierInvoice`, `SupplierPayment`

**Enum `SupplierInvoiceStatus`:** `Unpaid`, `PartiallyPaid`, `Paid`

- Facturas vinculables a una PO (opcional)
- Pagos parciales/totales con registro de método y comprobante (FileUpload a S3)
- Filtros: por estado, por vencidas, por rango de fechas, por proveedor
- Vista del proveedor: tabs con Productos, Facturas, Órdenes de Compra, Pagos + widget de stats

### 5. Dashboard

- Widget: Órdenes Pendientes de Recepción (full width)
- Widget: Stock Bajo (full width)

### 6. Usuarios

- **Perfil propio:** Filament built-in (`->profile()`) — nombre, email, contraseña
- **CRUD de usuarios:** Resource en Configuración — crear, editar, eliminar. No se puede eliminar el usuario logueado. Password hasheado, opcional en edición.
- **Roles/permisos:** Pendiente (Filament Shield o policies)

## Configuración del Sistema

**Modelo: `Setting`** (key-value custom)

- company_name, company_address, company_phone, company_tax_id, company_email, po_reply_to_email

**Branding:** Logo de Distribuidora Perú, color primario rojo (#dc2626), favicon, Zinc como gray.

## Lo que NO incluye esta versión

- Ventas / registro de salida de mercadería (módulo futuro)
- Clientes (D2C/B2B)
- Precios de venta
- Facturación electrónica AFIP
- Cuentas corrientes de clientes
- E-commerce / tienda online
- Reportes contables
- Multi-moneda
- PDF de Purchase Orders (pendiente)
- Email de Purchase Orders (pendiente)
- Import/Export CSV (pendiente)
- Roles y permisos granulares (pendiente)
