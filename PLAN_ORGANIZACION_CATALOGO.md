# Plan de Organización: Marcas y Sucursales

Este plan detalla la implementación de Marcas (Brands) y la asociación granular de productos con sucursales.

## 1. Estructura de Datos (Marcas)
Como actualmente no existe la tabla de marcas, se propone crear `catalog_brands`:
- `id` (INT AI)
- `name` (VARCHAR 100)
- `logo_path` (VARCHAR 255)
- `active` (TINYINT)

## 2. Asociación de Productos
- **Marcas:** Se agregará `brand_id` a la tabla `catalog_products`. Un producto pertenece a una marca.
- **Sucursales:** La relación ya existe mediante `catalog_product_stock`. Un producto puede estar en múltiples sucursales con diferentes existencias.

## 3. Próximos Pasos Técnicos
1. **Migración de Marcas:** Crear la tabla `catalog_brands` y agregar la FK en productos.
2. **Seeder de Marcas:** Generar marcas base (ej: HP, Dell, Cisco, Genérico).
3. **UI en Formulario:**
   - Agregar selector de Marca en la pestaña "Información General".
   - Filtro por Marca en el listado principal de productos (DataTables).

## 4. Alertas de Inventario por Sucursal
- El sistema ya soporta `min_alert` por sucursal. 
- Se planea una vista de "Stock Crítico" que agrupe productos por debajo del mínimo filtrable por sucursal y marca.

---

## 📊 Estado y Seguimiento de Implementación

### [HITO 4.5] Estructura Organizacional y Marcas
- [x] **Creación de Datos Base:** Se ejecutó `InitialSeeder` para crear el perfil de empresa y sucursales iniciales.
- [ ] **Módulo de Marcas (Brands):** Pendiente crear tabla `catalog_brands` y modelo.
- [ ] **Integración Producto-Marca:** Pendiente agregar columna `brand_id` en `catalog_products`.
- [ ] **UI Marcas:** Pendiente agregar selector en `product_form.php` y filtros en DataTables.

### Notas para el Próximo Agente:
1. El **Hito 4 (Inventario)** ya está funcional pero requiere que existan sucursales en `org_branches`.
2. Las pruebas manuales de inventario se realizaron con el producto SKU `TEST-INV-001`.
3. Se debe priorizar la creación de la tabla de marcas antes de iniciar el **Hito 5 (Descuentos)** para que el catálogo esté operativamente completo.
