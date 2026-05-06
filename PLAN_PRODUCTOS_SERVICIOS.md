# 📋 Plan de Implementación: Módulo de Catálogo de Productos y Servicios

Este documento detalla la hoja de ruta para el desarrollo del módulo de catálogo de productos y servicios. Este módulo es el "corazón" de la definición de la oferta comercial del sistema, integrando productos físicos, servicios recurrentes (Hosting, SaaS) y productos digitales (Licencias).

## 0. Antecedentes y Objetivos

### Contexto del Negocio
El sistema está dirigido inicialmente a una empresa de hosting (Shared, VPS, Dedicados, Dominios, SSL).

### Filosofía del Diseño
**Agnóstico y Escalable:** Aunque el enfoque inicial es Hosting, la base de datos y la lógica deben ser lo suficientemente sólidas y genéricas para controlar cualquier tipo de producto en futuros sistemas ERP (ej: venta de hardware, ropa, suscripciones de software).

### Dolor Actual (Sistema Legacy a reemplazar)
El sistema actual presenta limitaciones críticas que este nuevo módulo **debe** resolver:
1.  **Servicios Acoplados:** No se pueden gestionar fechas de renovación independientes si se compraron juntos (ej: Hosting y Dominio en una misma orden tienen la misma fecha de vencimiento obligatoriamente, aunque el dominio se haya transferido después).
2.  **Falta de Jerarquía:** No existen categorías ni familias de productos.
3.  **Sin Atributos Dinámicos:** Imposible agregar especificaciones técnicas estructuradas (RAM, CPU para hosting; Talla, Color para físicos).
4.  **Sin Control de Stock:** No hay control de existencias de hardware ni de capacidad de inventario digital/servicios.
5.  **Motor de Precios Rígido:** Sin soporte nativo para ciclos diferidos, costos de instalación (`setup_fee`) o precios promocionales.

## 1. Alcance Funcional (Core Features)

Para resolver los dolores actuales, el módulo implementará:

### A. Gestión Estructurada (Jerarquía y Atributos)
- [ ] **Categorías Ilimitadas:** Sistema de árbol jerárquico (Padre/Hijo) para organizar por familias y subfamilias (ej: Hosting -> Shared -> Linux).
- [ ] **Atributos Dinámicos (EAV Model):** Capacidad de definir características específicas por producto o categoría (ej: para VPS: "RAM", "Disco", "Ancho de Banda"; para Hosting: "Paneles de Control Soportados").

### B. Definición Maestra de Producto (`catalog_products`)
- [ ] **Ficha Central:** SKU único, códigos de barra (para físicos), nombres comerciales e internos, descripciones cortas y largas.
- [ ] **Clasificación por Tipo:**
    *   `physical`: Requiere stock duro en sucursales (ej: Servidores, Cables).
    *   `service`: Recurrente, requiere configuración de red/hostname en la venta, stock basado en capacidad (ej: Hosting, VPS).
    *   `digital`: Entrega instantánea de inventario, stock de llaves (ej: Licencias cPanel, SSL).
- [ ] **Galería de Imágenes:** Soporte para múltiples imágenes por producto (una principal, n adicionales) con Glassmorphism UI para visualización en carrito/ficha.

### C. Motor Potente de Precios y Planes (`catalog_product_plans`)
Soporte completo para la complejidad del negocio de Hosting y retail:
- [ ] **Multi-Plan por Producto:** Un producto (ej: VPS Pro) puede tener múltiples esquemas de cobro.
- [ ] **Ciclos de Cobro:** Único (`one_time`), Mensual, Anual, o ciclos personalizados.
- [ ] **Diferenciación de Precios:** Manejo separado de `sale_price` (pago inicial) y `renewal_price` (pagos futuros).
- [ ] **Costo de Instalación (`setup_fee`):** Cobro único adicional en la primera compra.

### D. Inventario y Relaciones
- [ ] **Control de Stock Multisucursal:** (Para físicos/digitales) Existencias por sucursal (`org_branches`) y alertas de mínimo.
- [ ] **Movimientos de Inventario (Kardex):** Registro de entradas, salidas y transferencias.
- [ ] **Relaciones y Bundles (`catalog_product_relations`):** Vincular productos (ej: "VPS Gold" *requiere* relación con "Licencia cPanel" o "Soporte Técnico Administrado").
- [ ] **Manejo de Descuentos (`marketing_discounts`):** Integración nativa para cupones globales, por categoría o producto específico con vigencia temporal.

## 2. Arquitectura de Base de Datos (Definida e Integrada)

Las tablas  y migraciones relacionadas al catalogo de productos y servicios ya existen en el sistema, por lo que unicamente es necesario crear las migraciones para la creación de la tabla "catalog_product_images"

### 2.1. Tablas Maatras y Jerarquía
![Diagrama de tablas existentes](<Sistema ERP - Diagrama de Usuarios - Catalog_products (1).png>)

#### Proposed New Table: `st32477_ci4.catalog_product_images`
Manejo de galería de imágenes.
*   `id`: INT (PK).
*   `product_id`: INT (FK).
*   `path`: VARCHAR. Ruta del archivo.
*   `is_main`: TINYINT (1 o 0).

### Tabla existentes del sistema para el módulo de catalogo de productos y servicios.
- 'catalog_categories'
- 'catalog_products'
- 'catalog_product_plans'
- 'catalog_product_attributes'
- 'catalog_product_stock'
- 'inventory_movements'
- 'catalog_product_relations'
- 'marketing_discounts'


## 3. Especificaciones UI/UX (Lineamientos AppStack + Glassmorphism)

Siguiendo la línea estética definida en el módulo de RRHH:
-   **Tablas de Listado:** Uso estricto de **DataTables con Server-Side Rendering** (métodos nativos `outputData` ya generados y arquitectura previa del server side rendering ya existente, esto se puede verificar en los mopdulos de RRHH y Users).
-   **Estilo:** Glassmorphism suave, micro-animaciones en hover, paleta de colores del template AppStack.
-   **Formularios:** Layouts limpios. El formulario de producto será complejo y dinámico (cambiará secciones si es físico o servicio usando JS).
-   **Interacciones:** Uso de Modales y AJAX para CRUD rápido de categorías, atributos y ajustes de stock sin recargar página(verificar arquiteccturas y manejs ya existentes de formularios.)


## 4. Hitos de Desarrollo (Roadmap)

1.  **Hito 1: Base Estructural y Catálogos (Backend & DB)**
    - Creación de Migraciones de la tabla catalog_product_images faltante.
    - Revisar las migraciones y tablas ya existentes del modulo.
    - Modelos CI4 configurados (relaciones, validaciones nativas, soft deletes).
    - CRUD completo de Categorías (árbol jerárquico en JS) (verificar arquitectura y manejo de datos existente en modulo de RRHH y Users).
    - Revisar todos los detalles de la configuración técnica en "DOCUMENTACION_TECNICA.md".

2.  **Hito 2: Gestión Maestra de Productos y Atributos (UI)**
    - Imagen de referencia para diseño de listado de producto y edicion con caracteristicas:
    ![referencia del listado de productos](image.png)
    ![referencia del detalle de producto](image-1.png) -> cambiar botones "buy now" y "add to cart" por "editar", "asignar a plan" o  cualquier funcionalidad que se requiera del modulo de catálogos   productos y servicios. Se debe incluir aqui el modal de edición del producto/plan y verificar como generar los planes a partir de productos individuales.
    - Listado Server-side de Productos.
    - Formulario de Alta de Producto (Info Base + Atributos Dinámicos).
    - Gestión de Galería de Imágenes (Upload y ordenamiento).
3.  **Hito 3: Motor de Precios, Planes y Relaciones**
    - UI para gestionar múltiples Planes (`catalog_product_plans`) por producto.
    - Lógica para diferenciar `sale_price` vs `renewal_price`.
    - Configuración de Relaciones (`catalog_product_relations`).
4.  **Hito 4: Inventario (Stock y Movimientos)**
    - Vista de stock multisucursal.
    - Formulario de movimientos de inventario (Entradas/Salidas/Ajustes).
    - Reporte básico de Kardex.
5.  **Hito 5: Descuentos y Refinamiento**
    - CRUD de `marketing_discounts`.
    - Pruebas unitarias de lógica de precios (Plan + Descuento).