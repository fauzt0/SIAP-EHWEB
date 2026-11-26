<?php

/**
 * Smoke CRM + Ventas: rutas, permisos, hooks y archivos.
 */
$failed = 0;
function crm_ok(bool $cond, string $msg): void
{
    global $failed;
    if ($cond) {
        fwrite(STDOUT, "OK  {$msg}\n");
        return;
    }
    $failed++;
    fwrite(STDERR, "FAIL {$msg}\n");
}

$root = dirname(__DIR__);
$routesFile = (string) file_get_contents($root . '/app/Config/Routes.php');
crm_ok(str_contains($routesFile, "'as' => 'crm.clients'"), 'alias crm.clients');
crm_ok(str_contains($routesFile, 'permission:crm.manage'), 'filtro crm.manage en store');
crm_ok(str_contains($routesFile, "'as' => 'sales.quotes'"), 'alias sales.quotes');
crm_ok(str_contains($routesFile, "'as' => 'sales.orders'"), 'alias sales.orders');
crm_ok(str_contains($routesFile, "'as' => 'sales.pos'"), 'alias POS de venta');
crm_ok(str_contains($routesFile, "'as' => 'sales.documents.quote'"), 'PDF cotización');
crm_ok(str_contains($routesFile, "'as' => 'sales.documents.order'"), 'PDF orden');
crm_ok(str_contains($routesFile, "'as' => 'sales.documents.receipt'"), 'PDF recibo');
crm_ok(str_contains($routesFile, "'as' => 'organization.sales_templates'"), 'plantillas PDF admin');
crm_ok(str_contains($routesFile, 'quotes/update'), 'actualizar cotización');
crm_ok(str_contains($routesFile, "'as' => 'crm.pipeline.ajax'"), 'pipeline CRM');

$auth = (string) file_get_contents($root . '/app/Config/AuthGroups.php');
crm_ok(str_contains($auth, "'crm.access'"), 'permiso crm.access');
crm_ok(str_contains($auth, "'crm.manage'"), 'permiso crm.manage');
crm_ok(str_contains($auth, "'admin.manage-organization'"), 'permiso organización');
crm_ok(str_contains($auth, "'admin.sales-templates'"), 'permiso plantillas PDF');
crm_ok(str_contains($auth, "'sales.pos'"), 'permiso POS');
crm_ok(str_contains($auth, "'sales.documents'"), 'permiso PDF ventas');

$hooks = (string) file_get_contents($root . '/app/Libraries/PluginSystem/PluginHookRegistry.php');
crm_ok(str_contains($hooks, 'crm.client.show.tabs'), 'hook crm.client.show.tabs cableado');
crm_ok(str_contains($hooks, 'sales.order.show.tabs'), 'hook sales.order.show.tabs cableado');
crm_ok(str_contains($hooks, 'sales.quote.show.tabs'), 'hook sales.quote.show.tabs cableado');

crm_ok(is_file($root . '/app/Services/ClientCommerceBridge.php'), 'ClientCommerceBridge');
crm_ok(is_file($root . '/app/Models/CRM/CrmFollowupModel.php'), 'CrmFollowupModel');
crm_ok(is_file($root . '/app/Database/Migrations/CRM/2026-11-20-160000_CreateCrmClientsAndFollowups.php'), 'migración CRM');
crm_ok(is_file($root . '/app/Database/Migrations/Sales/2026-11-20-160100_LinkSalesToCrmAndQuotes.php'), 'migración ventas↔CRM');
crm_ok(is_file($root . '/app/Database/Migrations/Sales/2026-11-23-120000_CrmSalesCollectionsFollowupsAndPos.php'), 'migración cobranza/OC/seguimiento');
crm_ok(is_file($root . '/app/Services/SalesReceiptScheduler.php'), 'SalesReceiptScheduler');
crm_ok(is_file($root . '/app/Services/SalesBillingCycle.php'), 'SalesBillingCycle');
crm_ok(is_file($root . '/app/Commands/Sales/GenerateRenewalReceipts.php'), 'comando sales:generate-receipts');
crm_ok(is_file($root . '/app/Controllers/Sales/PosController.php'), 'PosController');
crm_ok(is_file($root . '/app/Controllers/Sales/DocumentController.php'), 'DocumentController');
crm_ok(is_file($root . '/app/Services/SalesDocumentService.php'), 'SalesDocumentService');
crm_ok(is_file($root . '/app/Models/Sales/SalesDocumentTemplateModel.php'), 'SalesDocumentTemplateModel');
crm_ok(is_file($root . '/app/Views/Sales/pos_index.php'), 'vista POS');
crm_ok(is_file($root . '/app/Views/Sales/document_pdf.php'), 'plantilla HTML PDF');
crm_ok(is_file($root . '/app/Views/Organization/sales_document_templates.php'), 'vista plantillas admin');
crm_ok(is_file($root . '/app/Database/Migrations/Sales/2026-11-23-180000_CrmPipelinePosAndDocumentTemplates.php'), 'migración pipeline/POS/PDF');
crm_ok(str_contains((string) file_get_contents($root . '/app/Views/Partials/loggedin_sidebar.php'), 'sales.pos'), 'POS en sidebar');
crm_ok(str_contains((string) file_get_contents($root . '/app/Views/CRM/clients_index.php'), 'crm-pipeline'), 'pipeline en CRM');
crm_ok(str_contains((string) file_get_contents($root . '/app/Models/Sales/SalesQuoteModel.php'), 'updateWithItems'), 'actualizar cotización en modelo');
crm_ok(str_contains((string) file_get_contents($root . '/app/Libraries/PdfLibrary.php'), 'function setWatermarkText'), 'PdfLibrary marca de agua');
crm_ok(str_contains($hooks, 'sales.document.html'), 'hook sales.document.html');
crm_ok(str_contains($routesFile, "'as' => 'sales.billing.generate'"), 'ruta revisión del mes');
crm_ok(str_contains($routesFile, "'as' => 'sales.orders.customer_po'"), 'ruta OC del cliente');
crm_ok(str_contains($hooks, 'sales.receipt.issued'), 'hook sales.receipt.issued');
crm_ok(str_contains($hooks, 'sales.order.item.prepare'), 'filtro sales.order.item.prepare');
crm_ok(str_contains((string) file_get_contents($root . '/app/Views/CRM/clients_index.php'), 'oc-tab-cob'), 'pestaña cobranza CRM');
crm_ok(str_contains((string) file_get_contents($root . '/app/Views/CRM/clients_index.php'), 'whatsapp'), 'canal WhatsApp en seguimiento');

$cycleFile = (string) file_get_contents($root . '/app/Services/SalesBillingCycle.php');
crm_ok(str_contains($cycleFile, "strtotime('+1 month'"), 'ciclo mensual');
crm_ok(str_contains($cycleFile, "strtotime('+6 months'"), 'ciclo semestral');
crm_ok(str_contains($cycleFile, "strtotime('+1 year'"), 'ciclo anual');

if ($failed > 0) {
    fwrite(STDERR, "{$failed} comprobaciones fallaron\n");
    exit(1);
}
fwrite(STDOUT, "CRM/Ventas: smoke en verde\n");
exit(0);
