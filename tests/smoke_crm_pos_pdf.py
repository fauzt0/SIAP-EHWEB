#!/usr/bin/env python3
"""Smoke CRM avanzado + POS + plantillas PDF + revisión de módulos existentes."""
from __future__ import annotations

import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
failed = 0


def ok(cond: bool, msg: str) -> None:
    global failed
    if cond:
        print(f"OK  {msg}")
        return
    failed += 1
    print(f"FAIL {msg}", file=sys.stderr)


def read(rel: str) -> str:
    return (ROOT / rel).read_text(encoding="utf-8")


def exists(rel: str) -> bool:
    return (ROOT / rel).is_file()


routes = read("app/Config/Routes.php")
auth = read("app/Config/AuthGroups.php")
sidebar = read("app/Views/Partials/loggedin_sidebar.php")

ok("'as' => 'sales.pos'" in routes, "ruta POS")
ok("'as' => 'sales.quotes.update'" in routes, "ruta actualizar cotización")
ok("'as' => 'sales.documents.quote'" in routes, "ruta PDF cotización")
ok("'as' => 'sales.documents.order'" in routes, "ruta PDF orden")
ok("'as' => 'sales.documents.receipt'" in routes, "ruta PDF recibo")
ok("'as' => 'organization.sales_templates'" in routes, "ruta plantillas PDF")
ok("'as' => 'crm.pipeline.ajax'" in routes, "ruta pipeline CRM")
ok("permission:admin.sales-templates" in routes, "filtro plantillas PDF")
ok("permission:sales.pos" in routes, "filtro POS")
ok("permission:sales.documents" in routes, "filtro documentos")

ok("'admin.manage-organization'" in auth, "permiso organización declarado")
ok("'admin.sales-templates'" in auth, "permiso plantillas declarado")
ok("'sales.pos'" in auth, "permiso POS declarado")
ok("'sales.documents'" in auth, "permiso documentos declarado")

ok("sales.pos" in sidebar, "POS en menú")
ok("organization.sales_templates" in sidebar, "plantillas PDF en menú")

ok(exists("app/Controllers/Sales/PosController.php"), "PosController")
ok(exists("app/Controllers/Sales/DocumentController.php"), "DocumentController")
ok(exists("app/Controllers/Organization/SalesDocumentTemplateController.php"), "template controller")
ok(exists("app/Services/SalesDocumentService.php"), "SalesDocumentService")
ok(exists("app/Models/Sales/SalesDocumentTemplateModel.php"), "template model")
ok(exists("app/Views/Sales/pos_index.php"), "vista POS")
ok(exists("app/Views/Sales/document_pdf.php"), "HTML PDF")
ok(exists("app/Views/Organization/sales_document_templates.php"), "vista admin plantillas")
ok(exists("app/Database/Migrations/Sales/2026-11-23-180000_CrmPipelinePosAndDocumentTemplates.php"), "migración")

quote = read("app/Models/Sales/SalesQuoteModel.php")
ok("updateWithItems" in quote, "modelo actualiza cotización")
ok("'branch_id'" in quote and "'channel'" in quote, "cotización sucursal/canal")
ok("converted" in quote, "bloqueo convertida")

svc = read("app/Services/SalesDocumentService.php")
ok("issuer" in svc and "logo_path" in svc, "logo sucursal/empresa")
ok("renderPdf" in svc, "exportar PDF")
ok("buildHtml" in svc, "previsualizar HTML")

pdf_view = read("app/Views/Sales/document_pdf.php")
ok("logoAbs" in pdf_view, "logo en plantilla PDF")
ok("kindLabel" in pdf_view, "título por tipo de documento")

crm = read("app/Views/CRM/clients_index.php")
ok("crm-pipeline" in crm, "tablero pipeline")
ok("expected_value" in crm, "valor esperado")
ok("next_followup_at" in crm, "próximo seguimiento")
ok("assigned_user_id" in crm, "dueño CRM")

client_m = read("app/Models/CRM/CrmClientModel.php")
ok("getPipeline" in client_m, "getPipeline")
ok("findDuplicateRfc" in client_m, "RFC duplicado")

pos = read("app/Controllers/Sales/PosController.php")
ok("'quote'" in pos and "walk_in" in pos, "POS venta y cotización de mostrador")
ok("channel" in pos and "'pos'" in pos, "canal pos")

qc = read("app/Controllers/Sales/QuoteController.php")
ok("function convert" in qc and "converted_order_id" in qc, "convertir cotización a venta")
ok("function update" in qc, "QuoteController::update")

# Módulos existentes no se rompen
ok("'as' => 'crm.clients'" in routes, "CRM clientes intacto")
ok("'as' => 'sales.billing'" in routes, "cobranza intacta")
ok("'as' => 'organization.profile'" in routes, "organización intacta")
ok("'as' => 'organization.branches'" in routes, "sucursales intactas")
ok("'as' => 'suppliers.index'" in routes, "proveedores intactos")
ok("'as' => 'hr.payroll.index'" in routes or "hr.payroll" in routes, "RH nómina intacta")
ok("catalog.access" in routes, "catálogo intacto")
ok("NotasInternas" in read("plugins/NotasInternas/Config/Routes.php") or exists("plugins/NotasInternas/Config/Routes.php"), "plugin NotasInternas")

ok(exists("app/Models/Catalog/CatalogBrandModel.php"), "marcas de catálogo existen")
ok(exists("app/Models/Organization/OrgBranchModel.php"), "sucursales existen")
ok("logo_path" in read("app/Models/Organization/OrgBranchModel.php"), "logo de sucursal")
ok("logo_path" in read("app/Models/Organization/OrgCompanyModel.php"), "logo de empresa")
ok("function setWatermarkText" in read("app/Libraries/PdfLibrary.php"), "mPDF watermark")

hooks = read("app/Libraries/PluginSystem/PluginHookRegistry.php")
ok("sales.document.html" in hooks, "hook PDF plugins")
ok("sales.order.created" in hooks, "hook orden creada intacto")
ok("crm.client.show.tabs" in hooks, "hook ficha CRM intacto")

alerts = read("app/Controllers/System/AlertController.php")
ok("_detectDueCrmFollowups" in alerts, "alerta seguimiento CRM")
ok("_detectUnpaidReceipts" in alerts, "alerta recibos sin pagar")

# Otros smokes de archivos siguen presentes
ok(exists("tests/smoke_crm_sales.php"), "smoke CRM PHP")
ok(exists("tests/smoke_notas_internas.php"), "smoke notas internas")
ok(exists("tests/smoke_suppliers_integration.php"), "smoke proveedores")

if failed:
    print(f"{failed} comprobaciones fallaron", file=sys.stderr)
    sys.exit(1)
print("CRM/POS/PDF: smoke en verde")
sys.exit(0)
