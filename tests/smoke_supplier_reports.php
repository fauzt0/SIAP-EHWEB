<?php

declare(strict_types=1);

/**
 * Comprueba el reporte de proveedores y el contrato del plugin 001
 * sin base de datos. php tests/smoke_supplier_reports.php
 */

$failed = 0;

function sr_ok(bool $cond, string $message): void
{
    global $failed;
    if ($cond) {
        fwrite(STDOUT, "  [OK]    {$message}\n");

        return;
    }

    $failed++;
    fwrite(STDERR, "  [FAIL]  {$message}\n");
}

$root   = dirname(__DIR__);
$routes = file_get_contents($root . '/app/Config/Routes.php');
$model  = file_get_contents($root . '/app/Models/Financial/FinSupplierReportModel.php');
$view   = file_get_contents($root . '/app/Views/Financial/Suppliers/reports_index.php');
$side   = file_get_contents($root . '/app/Views/Partials/loggedin_sidebar.php');
$plugin = file_get_contents($root . '/agentic/plugin_001_server_admin.md');
$types  = file_get_contents($root . '/app/Models/Financial/FinRecurringExpenseModel.php');

sr_ok(str_contains($routes, "suppliers.reports.index"), 'ruta del reporte');
sr_ok(str_contains($routes, "suppliers.reports.export"), 'ruta CSV');
sr_ok(str_contains($view, 'Porcentaje pagado'), 'la vista explica el porcentaje');
sr_ok(str_contains($view, 'Órdenes abiertas'), 'la vista lista órdenes abiertas');
sr_ok(str_contains($view, 'Recordatorios de pago'), 'la vista lista recordatorios');
sr_ok(str_contains($side, 'suppliers.reports.index'), 'el menú enlaza reportes');
sr_ok(str_contains($model, "whereNotIn('status', ['cancelled'])"), 'los gastos cancelados no entran al monto');
sr_ok(str_contains($model, "whereIn('po.status', ['sent', 'approved'])"), 'OC abierta = enviada o aprobada');
sr_ok(str_contains($types, "'phone'") && str_contains($types, "'internet'") && str_contains($types, "'hosting'"), 'telefonía, internet y hosting existen');
sr_ok(str_contains($plugin, 'recurring_expense_id'), 'el plugin 001 guarda la plantilla de proveedores');
sr_ok(str_contains($plugin, 'service_type = hosting'), 'el servidor recurrente usa hosting');
sr_ok(str_contains($plugin, 'supplier.show.tabs') && str_contains($plugin, 'no están cableados'), 'el documento no trata supplier.show.tabs como hook v1');
sr_ok(str_contains($plugin, 'fin_ledger_entries'), 'el plugin no abre otro libro contable');

exit($failed === 0 ? 0 : 1);
