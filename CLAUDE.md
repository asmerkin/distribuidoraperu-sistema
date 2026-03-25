# Distribuidora Perú — Sistema de Gestión de Inventario

## Contexto

Distribuidora Perú es una distribuidora de artículos de oficina ubicada en Mendoza, Argentina. Actualmente no tiene ningún sistema digital. Vende tanto a consumidor final (D2C/mostrador) como a empresas/clientes mayoristas (B2B). Este sistema es su primera herramienta de gestión.

## Objetivo

Construir un sistema web de gestión de inventario con dos módulos core: Catálogo + Stock (con conteo físico) y Compras (entrada de stock via Purchase Orders), más un CRUD de proveedores. El sistema debe ser simple, rápido de usar, y mobile-friendly para uso en depósito.

## Stack Técnico

- **Backend:** Laravel 13 (PHP 8.5)
- **Admin UI:** Filament v5 (última versión, panel principal del sistema, no solo admin)
- **Base de datos:** SQLite (desarrollo) / MySQL 8 (producción)
- **Auth:** Laravel built-in con Filament Shield para roles/permisos
- **Queue:** Laravel Queues (para envío de emails de POs)
- **PDF generation:** Laravel DomPDF o similar (para POs)
- **Deployment:** A definir (inicialmente puede correr en local o VPS básico)

## Módulos

### 1. Catálogo de Productos (modelo Shopify)

La estructura sigue el patrón de Shopify: un Product tiene una o más Variants. El inventario se trackea a nivel de Variant + Location (InventoryLevel). Un producto sin opciones tiene igualmente una única "default" variant.

**Modelo: `Product`**

| Campo | Tipo | Notas |
|-------|------|-------|
| id | ulid | PK |
| name | string | Nombre del producto. Ej: "Resma A4" |
| description | text, nullable | Descripción opcional |
| category_id | FK | Relación con Category |
| unit_of_measure | enum | unidad, caja, resma, pack, rollo, metro, kg |
| is_active | boolean, default true | Soft-disable |
| timestamps | | |

**Modelo: `ProductOption`** (opcional, define los ejes de variación)

| Campo | Tipo | Notas |
|-------|------|-------|
| id | ulid | PK |
| product_id | FK | |
| name | string | Ej: "Gramaje", "Color", "Tamaño" |
| position | integer | Orden de visualización |
| timestamps | | |

**Modelo: `ProductOptionValue`**

| Campo | Tipo | Notas |
|-------|------|-------|
| id | ulid | PK |
| product_option_id | FK | |
| value | string | Ej: "75g", "80g", "Blanco", "Amarillo" |
| position | integer | Orden |
| timestamps | | |

**Modelo: `Variant`**

| Campo | Tipo | Notas |
|-------|------|-------|
| id | ulid | PK |
| product_id | FK | |
| sku | string | Único, autogenerable |
| barcode | string, nullable | Código de barras (EAN/UPC) por variante |
| name | string | Autogenerado desde option values. Ej: "75g / Blanco". Para producto sin opciones: "Default" |
| cost_price | decimal(10,2) | Último precio de compra |
| is_active | boolean, default true | |
| timestamps | | |

**Tabla pivot: `variant_option_values`** (qué option values componen cada variant)

| Campo | Tipo | Notas |
|-------|------|-------|
| variant_id | FK | |
| product_option_value_id | FK | |

**Modelo: `Location`**

| Campo | Tipo | Notas |
|-------|------|-------|
| id | ulid | PK |
| name | string | Ej: "Depósito Principal", "Local Mostrador", "Depósito 2" |
| address | text, nullable | |
| is_active | boolean, default true | |
| timestamps | | |

**Modelo: `InventoryLevel`** (stock de una variant en una location)

| Campo | Tipo | Notas |
|-------|------|-------|
| id | ulid | PK |
| variant_id | FK | |
| location_id | FK | |
| quantity | integer | Stock actual (denormalizado, calculado desde movimientos) |
| min_stock | integer, default 0 | Alerta de stock mínimo para esta variant en esta location |
| timestamps | | |
| | | Unique constraint: (variant_id, location_id) |

**Modelo: `Category`**

| Campo | Tipo | Notas |
|-------|------|-------|
| id | ulid | PK |
| name | string | Ej: "Papelería", "Escritura", "Tecnología" |
| parent_id | FK, nullable | Categorías anidadas (1 nivel) |
| timestamps | | |

**Relaciones clave:**
```
Product hasMany Variants
Product hasMany ProductOptions → each hasMany ProductOptionValues
Variant belongsToMany ProductOptionValues (via variant_option_values)
Variant hasMany InventoryLevels
Location hasMany InventoryLevels
InventoryLevel belongsTo Variant + Location
```

**Producto sin opciones:** Se crea automáticamente una sola Variant con name "Default", sin ProductOptions. La UI lo presenta como producto simple (sin selector de variante).

**Producto con opciones:** Ejemplo: "Resma A4" con opción "Gramaje" (75g, 80g) y opción "Color" (Blanco, Amarillo) genera 4 variants: "75g / Blanco", "75g / Amarillo", "80g / Blanco", "80g / Amarillo". Cada una con su propio SKU, barcode, y precios.

**Stock total de una variant:** `SUM(inventory_levels.quantity)` across all locations.
**Stock total de un product:** `SUM(inventory_levels.quantity)` across all its variants and locations.

**Funcionalidades Filament:**
- CRUD de productos con inline management de variants (o generación automática desde opciones)
- CRUD de locations
- Vista de inventory levels por variant (expandible por location)
- Import CSV para carga masiva inicial (con columnas: product_name, variant_name, sku, barcode, location, quantity, cost_price)
- Export CSV/Excel
- Vista de "stock bajo" (inventory_level.quantity <= min_stock) como widget en dashboard
- Búsqueda por nombre de producto, nombre de variant, SKU, o código de barras

### 2. Gestión de Stock

**Modelo: `StockMovement`**

| Campo | Tipo | Notas |
|-------|------|-------|
| id | ulid | PK |
| variant_id | FK | La variant que se mueve |
| location_id | FK | La location donde ocurre el movimiento |
| type | enum | entrada, salida, ajuste, transferencia |
| reason | enum | compra, ajuste_conteo, merma, devolucion, transferencia_entrada, transferencia_salida |
| quantity | integer | Positivo siempre. El type define si suma o resta |
| reference_type | string, nullable | Morph (PurchaseOrder, Transfer, etc.) |
| reference_id | ulid, nullable | Morph |
| notes | text, nullable | Notas libres |
| user_id | FK | Quién registró el movimiento |
| timestamps | | |

**Reglas de negocio:**
- Todo movimiento de stock pasa por `StockMovement`. Nunca se modifica `InventoryLevel.quantity` directamente.
- Un Service (`InventoryService`) centraliza la lógica: `recordMovement(variant, location, type, reason, qty, reference?, notes?)` que crea el movimiento y actualiza el `InventoryLevel` correspondiente.
- Si no existe el `InventoryLevel` para esa variant+location, se crea automáticamente con quantity 0 antes de aplicar el movimiento.
- El historial de movimientos es inmutable (no se editan ni borran, se crean ajustes compensatorios).
- **Transferencias entre locations:** generan 2 movimientos: salida en origin + entrada en destino, vinculados por el mismo reference (Transfer).

**Funcionalidades Filament:**
- Historial de movimientos por variant (timeline), filtrable por location
- Ajuste manual de stock (para conteo físico): formulario que compara stock del sistema vs conteo real y genera movimiento de ajuste
- Transferencia de stock entre locations (formulario simple: variant, origin, destino, cantidad)
- Dashboard widget: variants con stock bajo (por location)

### 3. Compras — Purchase Orders

**Modelo: `Supplier`**

| Campo | Tipo | Notas |
|-------|------|-------|
| id | ulid | PK |
| name | string | Razón social |
| tax_id | string, nullable | CUIT del proveedor |
| contact_name | string, nullable | Persona de contacto |
| email | string, nullable | Para envío de POs |
| phone | string, nullable | |
| address | text, nullable | |
| payment_terms | text, nullable | Condiciones de pago (ej: "30 días fecha factura", "contado contra entrega") |
| notes | text, nullable | Notas generales libres |
| timestamps | | |

**Modelo: `PurchaseOrder`**

| Campo | Tipo | Notas |
|-------|------|-------|
| id | ulid | PK |
| po_number | string | Autogenerado secuencial (PO-00001) |
| supplier_id | FK | |
| location_id | FK | A qué location entra la mercadería recibida |
| status | enum | borrador, enviada, recibida_parcial, recibida, cancelada |
| order_date | date | Fecha de la orden |
| expected_date | date, nullable | Fecha esperada de entrega |
| total | decimal(10,2) | Calculado |
| notes | text, nullable | Notas internas |
| notes_for_supplier | text, nullable | Notas que aparecen en el PDF/email |
| user_id | FK | Quién creó la PO |
| sent_at | datetime, nullable | Cuándo se envió por email |
| timestamps | | |

**Modelo: `PurchaseOrderItem`**

| Campo | Tipo | Notas |
|-------|------|-------|
| id | ulid | PK |
| purchase_order_id | FK | |
| variant_id | FK | La variant que se compra |
| quantity_ordered | integer | Lo que se pidió |
| quantity_received | integer, default 0 | Lo que llegó (se actualiza en recepción) |
| unit_cost | decimal(10,2) | Precio unitario de compra |
| subtotal | decimal(10,2) | quantity_ordered * unit_cost |
| timestamps | | |

**Flujo de la Purchase Order:**

```
1. CREAR (borrador)
   → Se cargan proveedor, location destino, variants, cantidades, precios
   → Status: borrador

2. ENVIAR
   → Se genera PDF de la PO
   → Se envía por email al proveedor (usando email del Supplier)
   → Status: enviada
   → Se registra sent_at

3. RECIBIR
   → Pantalla de recepción: muestra lo pedido vs lo que llegó
   → El usuario ingresa cantidad recibida por cada línea
   → Si llegó todo: status → recibida
   → Si falta algo: status → recibida_parcial (se puede recibir en múltiples entregas)
   → Al confirmar recepción:
     - Se generan StockMovements de tipo entrada/compra en la location de la PO
     - Se actualiza cost_price de la variant con el último precio de compra

4. CANCELAR
   → Solo si está en borrador o enviada (no si ya se recibió algo)
```

**PDF de la Purchase Order:**
- Header: "Distribuidora Perú" + datos de la empresa (configurables)
- Datos del proveedor
- Tabla: producto, SKU, cantidad, precio unitario, subtotal
- Total
- Notas para el proveedor
- Fecha y número de PO

**Email:**
- Asunto: "Orden de Compra {po_number} - Distribuidora Perú"
- Body simple con texto introductorio
- PDF adjunto
- Reply-to configurable

**Funcionalidades Filament:**
- CRUD de proveedores
- Crear/editar PO con líneas de productos (Repeater de Filament)
- Acción "Enviar por email" con preview del PDF
- Acción "Recibir mercadería" — wizard o formulario con las líneas y campo para cantidad recibida
- Vista de POs pendientes de recepción
- Filtros por status, proveedor, fecha

### 4. Dashboard

- Widgets: total productos, total variants, variants con stock bajo, POs pendientes de recepción
- Lista rápida de variants con stock bajo (por location)
- POs recientes y su status

### 5. Usuarios y Permisos

**Roles iniciales:**
- **admin**: Acceso total
- **deposito**: Puede recibir mercadería (POs), hacer ajustes de stock, conteos físicos, ver productos. No puede crear POs ni modificar productos/proveedores.

Usar Filament Shield o similar para manejo de permisos basado en policies.

## Configuración del Sistema

**Modelo: `Setting`** (key-value o Spatie Settings)

- company_name: "Distribuidora Perú"
- company_address: Dirección
- company_phone: Teléfono
- company_tax_id: CUIT
- company_email: Email de contacto
- po_reply_to_email: Email para reply de POs

## Conteo Físico (Feature importante)

Para el inventario inicial y conteos periódicos:

**Modelo: `StockCount`**

| Campo | Tipo | Notas |
|-------|------|-------|
| id | ulid | PK |
| location_id | FK | En qué location se hace el conteo |
| status | enum | en_progreso, completado |
| started_at | datetime | |
| completed_at | datetime, nullable | |
| user_id | FK | Quién hizo el conteo |
| notes | text, nullable | |
| timestamps | | |

**Modelo: `StockCountItem`**

| Campo | Tipo | Notas |
|-------|------|-------|
| id | ulid | PK |
| stock_count_id | FK | |
| variant_id | FK | La variant que se cuenta |
| system_quantity | integer | Stock en sistema al momento del conteo (de InventoryLevel para esa variant+location) |
| counted_quantity | integer | Lo que se contó físicamente |
| difference | integer | counted - system (calculado) |
| timestamps | | |

**Flujo:**
1. Iniciar conteo para una location → captura stock actual del sistema de todas las variants con InventoryLevel en esa location (o filtro por categoría)
2. Ir variant por variant ingresando cantidad real (idealmente desde el celu, con búsqueda por barcode)
3. Al finalizar, mostrar diferencias
4. Confirmar → genera StockMovements de ajuste para todas las diferencias, en esa location

## Lo que NO incluye esta versión

- Ventas / registro de salida de mercadería (módulo futuro)
- Clientes (D2C/B2B)
- Precios de venta (se agregarán cuando se implemente ventas)
- Facturación electrónica AFIP
- Cuentas corrientes de clientes (fiado/crédito)
- Integración con bancos
- E-commerce / tienda online
- Reportes contables
- Multi-moneda

Estos pueden ser módulos futuros.

## Convenciones de Código

- IDs: ULID (usa `HasUlids` trait)
- Money: almacenar en decimal(10,2), sin minor units (es inventario, no fintech)
- Enums: usar PHP backed enums
- Services: lógica de negocio en Services, no en Controllers ni en Models. `InventoryService` es el punto central para todo movimiento de stock.
- Actions: usar Filament Actions para operaciones (enviar PO, recibir, ajustar, transferir)
- Tests: Feature tests para flujos críticos (recibir PO suma stock en la location correcta, transferencia descuenta en origin y suma en destino, conteo físico genera ajustes correctos)
- Observers o Events: para mantener `InventoryLevel.quantity` sincronizado via StockMovement
