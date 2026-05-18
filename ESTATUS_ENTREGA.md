# Estatus de Entrega: Módulo de Organización y Catálogo

Este documento sirve como puente o *handoff* para el próximo agente desarrollador, detallando lo que se ha completado en las últimas sesiones y los siguientes pasos lógicos en el flujo del ERP.

## 📌 Contexto Actual
Se ha reestructurado la arquitectura para soportar un entorno multi-sucursal o "multi-unidad de negocio". Específicamente, se ha separado conceptualmente:
1. **Marcas (Brands):** Fabricantes de tecnología externos (Ej: HP, Dell, Microsoft).
2. **Organización (Matriz y Sucursales):** Nuestra propia empresa (`org_company_profile`) y nuestras unidades de negocio comercializadoras (`org_branches`), por ejemplo: Especialistas Hosting, Especialistas Web.

## ✅ Lo que ya está completado (Fase 1)
- **Documentación Técnica:** `DOCUMENTACION_TECNICA.md` ha sido actualizada rigurosamente. Contiene reglas para Ajax, CSRF, Rutas, Sidebar, DataTablesTrait, MVC (Transacciones), y ahora también **Flujo de Vistas, ViewData y OutputData**.
- **Diccionario de Datos:** Se creó `SCHEMA_ORGANIZACION.md` en la raíz con el detalle de las tablas de la empresa.
- **Módulo de Marcas (`CatalogBrands`):** Terminado e integrado en el alta de productos.
- **Módulo de Organización (`OrganizationController`):**
    - Rutas protegidas por el permiso `admin.manage-organization`.
    - `profile.php`: Edición de los datos legales de la empresa matriz.
    - `branches_index.php` y `branch_form.php`: CRUD completo de sucursales con subida de logotipos e integración con DataTables Server-Side.
- **Sidebars y Permisos:** El menú lateral ya despliega estos módulos condicionando su vista a que el usuario posea los permisos correspondientes.

## ⏳ Tareas Pendientes Inmediatas (Fase 2)
El siguiente desarrollador debe continuar con la **Fase 2 del Plan de Implementación**: Integrar las sucursales con el catálogo de productos.

**Pasos sugeridos para la Fase 2:**
1. **Migración en `catalog_products`:** Añadir el campo `business_unit_id` (INT FK a `org_branches.id`). Esto definirá qué unidad de negocio vende el producto.
2. **Modelo `CatalogProductModel`:** Agregar `business_unit_id` a los `allowedFields` y modificar `_get_datatables_query` para hacer un JOIN visual con `org_branches` si se requiere.
3. **Formulario de Producto (`product_form.php`):** Agregar un `<select>` obligatorio para asignar la **Unidad de Negocio** al momento de crear o editar un producto, inyectando las sucursales activas desde el controlador.
4. **Tabla de Productos (`products_index.php`):** Añadir una columna en la tabla de DataTables que muestre la Unidad de Negocio dueña del producto.

## 📝 Documentación Legal en Sucursales (Pendiente UI)
En el formulario `branch_form.php` se dejó un *placeholder* (botón deshabilitado) para subir documentación legal (Actas constitutivas, comprobantes de domicilio). Esto está anotado en el `TODO.md` para ser desarrollado en el futuro mediante una tabla pivote o gestión directa de archivos.

## ⚠️ Consideraciones Técnicas para el Nuevo Agente
- Por favor, revisa el archivo `DOCUMENTACION_TECNICA.md` antes de crear nuevos controladores o realizar consultas complejas a la base de datos.
- **NO hagas transacciones SQL en los controladores.** Si modificas múltiples tablas, la lógica de `$db->transStart()` debe ir estrictamente dentro de un Modelo.
- Mantén el estándar de DataTables usando el `DataTableTrait` tal como se describe en la sección 16 de la documentación.
