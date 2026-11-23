# Verificación CRM avanzado, POS y PDF (2026-11-23)

## Qué se agregó

- Pipeline CRM (kanban lead → baja), dueño, sucursal, valor esperado, próximo seguimiento, RFC duplicado.
- POS (`nat/sales/pos`): venta directa o cotización (borrador/enviada), actualizar cotización y convertir a venta (emite recibo/prefactura).
- PDF de cotización, orden y recibo: vista previa HTML + exportar/descargar. Logo de sucursal o, si no hay, de la empresa.
- Plantillas PDF editables en Organización (`admin.sales-templates`).
- Permisos nuevos: `sales.pos`, `sales.documents`, `admin.sales-templates`; se declaró `admin.manage-organization` (ya se usaba).

## Ya existía

- Marcas: `catalog_brands` / `CatalogBrandModel`.
- Sucursales y logos: `org_branches.logo_path`, `org_company_profile.logo_path`.
- mPDF: `App\Libraries\PdfLibrary`.

## Cómo probar (VPS, con PHP)

```bash
php spark migrate
php tests/smoke_crm_sales.php
php tests/smoke_crm_sales_db.php
python3 tests/smoke_crm_pos_pdf.py
```

En este entorno de agente nube no hay binario PHP; el smoke Python cubre rutas, permisos, archivos y que los módulos existentes siguen cableados.

## Recorrido UI

1. Organización → Perfil matriz / Sucursales: logo. Plantillas PDF (ventas): título, nota legal, sucursal por defecto, marca de agua.
2. CRM: alta con valor/dueño/sucursal; botón Pipeline; convertir lead.
3. Ventas → POS: carrito, mostrador o cliente, emitir venta o cotización, abrir PDF.
4. Cotizaciones: editar estatus/partidas, vista previa, convertir a venta.
5. Órdenes / Recibos: botones PDF.
