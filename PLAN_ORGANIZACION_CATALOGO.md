# Plan de Organización: Marcas y Sucursales

Este plan detalla la implementación de Marcas (Brands) y la asociación granular de productos con sucursales.

## 1. Estructura de Datos (Marcas)
Como actualmente no existe la tabla de marcas, se propone crear `catalog_brands`:
- `id` (INT AI)
- `name` (VARCHAR 100)
- `logo_path` (VARCHAR 255)
- `active` (TINYINT)

## 2. Asociación de Productos
- **Marcas:** `brand_id` en `catalog_products` — fabricante del producto (HP, Dell, etc.).
- **Unidad de negocio:** `business_unit_id` → `org_branches.id` — sub-empresa que **comercializa** el producto (ej. Especialistas Hosting).
- **Inventario por sucursal:** `catalog_product_stock.org_branches_id` — existencias físicas en cada sucursal (independiente del comercializador).

## 3. Estado de Implementación Técnica
1. **Marcas (Completado):** Tabla `catalog_brands`, modelo, seeder y selector en formulario.
2. **Unidad de Negocio (Completado):** Columna `business_unit_id` en `catalog_products`.
3. **UI Unificada (Completado):** El campo "Unidad de Negocio" es obligatorio y visible para todos los tipos de producto (Servicio, Físico, Digital) con preselección inteligente de la sucursal matriz en altas.

## 4. Alertas de Inventario por Sucursal
- El sistema ya soporta `min_alert` por sucursal. 
- Se planea una vista de "Stock Crítico" que agrupe productos por debajo del mínimo filtrable por sucursal y marca.

---

## 📊 Estado y Seguimiento de Implementación

### [HITO 4.5] Estructura Organizacional y Marcas
- [x] **Creación de Datos Base:** Se ejecutó `InitialSeeder` para crear el perfil de empresa y sucursales iniciales.
- [x] **Módulo de Marcas (Brands):** Creada la tabla `catalog_brands` mediante migración y su modelo `CatalogBrandModel`.
- [x] **Integración Producto-Marca:** Añadida la columna `brand_id` en `catalog_products` (migración) y actualizada en `CatalogProductModel`.
- [x] **UI Marcas:** Añadido el selector en `product_form.php` y los controladores para inyectar los datos.

### [HITO 4.6] Producto ↔ Unidad de Negocio (Fase 2)
- [x] Migración `business_unit_id` en `catalog_products` (FK a `org_branches`).
- [x] Modelo, DataTables, formulario y filtro por unidad de negocio.
- [x] **UI Unificada:** Campo habilitado y obligatorio para Servicios, Físicos y Digitales (Alta/Edición/Clonación).
- [x] **Preselección:** Lógica de sucursal matriz por defecto en nuevos registros.

### Notas para el Próximo Agente:
1. El **Hito 4 (Inventario)** ya está funcional y utiliza `org_branches` para existencias físicas.
2. La **Unidad de Negocio** (`business_unit_id`) define quién comercializa el producto y es obligatoria para todos los tipos.
3. El catálogo está listo para iniciar el **Hito 5 (Descuentos)** o proceder con el módulo de **Proveedores** según el `TODO.md`.
