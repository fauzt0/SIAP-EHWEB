# 📋 Plan de Implementación: Módulo de Catálogo de Productos y Servicios

Este documento detalla la hoja de ruta para el desarrollo del catálogo maestro, integrando productos físicos, servicios recurrentes y la lógica de precios/planes.

## 1. Alcance Funcional
- [ ] **Gestión de Categorías:** Árbol jerárquico (Padres e hijos) para organizar el catálogo.
- [ ] **Ficha de Producto:**
    - Registro de información base (SKU, Barcode, Nombres, Descripciones).
    - Clasificación por tipo: `service` (Hosting/SaaS), `physical` (Hardware), `digital` (Licencias).
- [ ] **Motor de Precios y Planes (Recurrencia):**
    - Configuración de ciclos de cobro (Mensual, Anual, Único).
    - Diferenciación entre `sale_price` (Compra inicial) y `renewal_price` (Renovaciones).
    - Manejo de `setup_fee` (Costo de instalación).
- [ ] **Atributos y Características:** Especificaciones técnicas dinámicas (RAM, Disco, Color, Talla, etc.).
- [ ] **Relaciones y Bundles:** Productos vinculados (ej. Dominio incluido con Hosting) o ventas sugeridas.
- [ ] **Control de Stock (Para Físicos):** Listado de existencias por sucursal y alertas de mínimo.

## 2. Arquitectura Técnica (CodeIgniter 4)
- **Modelos:**
    - `CatalogProductModel`: Tabla central.
    - `CatalogCategoryModel`: Manejo de categorías y slugs.
    - `CatalogPlanModel`: Lógica de precios recurrentes.
    - `CatalogAttributeModel`: Características dinámicas.
- **Controladores:**
    - `CatalogController`: Operaciones principales CRUD.
    - `InventoryController`: Movimientos de stock y kardex.
- **Vistas:**
    - `catalog/index.php`: Listado avanzado con filtros por tipo y categoría.
    - `catalog/form.php`: Formulario dinámico que cambia según si el producto es físico o servicio.

## 3. Especificaciones del Usuario (Por detallar)
> [!IMPORTANT]
> **Escribe aquí tus requerimientos adicionales:**
> *   ¿Cómo manejaremos las imágenes de los productos? (Galería o imagen única).
> *   ¿Necesitas integración con algún proveedor de dominios (API) en esta fase?
> *   ¿El stock debe afectar a los servicios o solo a productos físicos?

## 4. Diseño y UI/UX
- Seguir la línea estética de **RRHH** (Dark Mode, Glassmorphism, Micro-animaciones).
- Uso de **DataTables Server-side** para el listado.
- Modales para edición rápida de precios y stock.

## 5. Hitos de Entrega
1. **Fase 1:** CRUD de Categorías y Listado de Productos.
2. **Fase 2:** Formulario complejo de Producto (Planes y Atributos).
3. **Fase 3:** Gestión de Stock y Movimientos.
4. **Fase 4:** Relaciones entre productos y descuentos.
