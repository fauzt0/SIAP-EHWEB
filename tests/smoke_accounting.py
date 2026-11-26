#!/usr/bin/env python3
"""Smoke del módulo de contabilidad y cumplimiento básico DOC TÉCNICA."""
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

ok("'as' => 'accounting.dashboard'" in routes, "ruta dashboard contabilidad")
ok("'as' => 'accounting.funds'" in routes, "ruta solicitud de fondos")
ok("'as' => 'accounting.expenses'" in routes, "ruta gastos")
ok("'as' => 'accounting.invoices'" in routes, "ruta facturas")
ok("'as' => 'accounting.accounts'" in routes, "ruta cuentas")
ok("'as' => 'users.list.page'" in routes, "alias user/list")
ok("permission:finance.access" in routes, "filtro finance.access")
ok("permission:finance.transactions" in routes, "filtro finance.transactions")

ok("'finance.folios'" in auth, "permiso folios")
ok("'finance.invoices'" in auth, "permiso facturas")
ok("accounting.dashboard" in sidebar, "menú contabilidad")
ok("accounting.funds" in sidebar, "menú solicitud de fondos")

for rel in [
    "app/Database/Migrations/Accounting/2026-11-23-200000_AccountingModuleLedgerFoliosAndInvoices.php",
    "app/Services/AccountingPostingService.php",
    "app/Controllers/Accounting/DashboardController.php",
    "app/Controllers/Accounting/FundRequestController.php",
    "app/Controllers/Accounting/ExpenseController.php",
    "app/Controllers/Accounting/InvoiceController.php",
    "app/Controllers/Accounting/AccountController.php",
    "app/Models/Financial/FinPaymentFolioModel.php",
    "app/Models/Financial/FinLedgerEntryModel.php",
    "app/Models/Financial/FinSupplierInvoiceModel.php",
    "app/Models/Financial/FinAccountModel.php",
    "app/Views/Accounting/dashboard.php",
    "app/Views/Accounting/fund_requests.php",
    "app/Views/Accounting/expenses.php",
    "app/Views/Accounting/invoices.php",
    "app/Views/Accounting/accounts.php",
]:
    ok(exists(rel), rel)

mig = read("app/Database/Migrations/Accounting/2026-11-23-200000_AccountingModuleLedgerFoliosAndInvoices.php")
ok("addColumnIfMissing" in mig, "migración idempotente columnas")
ok("fin_supplier_invoices" in mig, "tabla facturas proveedor")
ok("Solicitud de fondos" in mig, "categoría solicitud de fondos")
ok("Caja chica" in mig, "cuenta caja chica")

svc = read("app/Services/AccountingPostingService.php")
ok("fromPayrollPeriod" in svc, "asiento de nómina")
ok("fromExpensePayment" in svc, "asiento de gasto")
ok("fromFundRequest" in svc, "asiento de folio")
ok("fromPurchaseOrder" in svc, "factura desde OC")
ok("fromSalesPayment" in svc, "asiento de cobro")
ok("alreadyPosted" in svc, "idempotencia de asientos")

pay = read("app/Controllers/HR/PayrollController.php")
ok("AccountingPostingService" in pay, "nómina conectada a contabilidad")
rec = read("app/Controllers/Financial/RecurringExpenseController.php")
ok("AccountingPostingService" in rec, "recurrentes conectados a contabilidad")
po = read("app/Controllers/Financial/PurchaseOrderController.php")
ok("AccountingPostingService" in po, "OC conectada a facturas proveedor")
bill = read("app/Models/Sales/BillingPaymentModel.php")
ok("AccountingPostingService" in bill, "cobros de venta al mayor")

folio_ctrl = read("app/Controllers/Accounting/FundRequestController.php")
ok("assertAjaxPost" in folio_ctrl, "AJAX 404 en fondos")
ok("logActivity" in folio_ctrl, "bitácora de fondos")
ok("nextFolioNumber" in folio_ctrl, "folio SDF")

view = read("app/Views/Accounting/fund_requests.php")
ok("X-Requested-With" in view, "header AJAX en fondos")
ok("xhr.dt" in view, "csrf DataTables fondos")
ok("csrf_header()" in view, "csrf header fondos")

exp_view = read("app/Views/Accounting/expenses.php")
ok("xhr.dt" in exp_view and "X-Requested-With" in exp_view, "csrf/ajax gastos")

# Revisión DOC TÉCNICA en módulos previos
hr = read("app/Controllers/HR/PayrollController.php")
ok("assertAjaxPost" in hr, "RH nómina AJAX")
ok("logActivity" in hr, "RH bitácora")
users = read("app/Config/Routes.php")
ok("'as' => 'users.list'" in users, "admin usuarios named route")
crm_r = "crm.clients" in routes
ok(crm_r, "CRM named routes")
ok("suppliers.index" in routes, "proveedores named routes")

php_files = [
    "app/Controllers/Accounting/FundRequestController.php",
    "app/Services/AccountingPostingService.php",
    "app/Models/Financial/FinPaymentFolioModel.php",
]
for rel in php_files:
    src = read(rel)
    ok(src.count("{") == src.count("}"), f"llaves balanceadas {rel}")
    ok(src.count("(") == src.count(")"), f"paréntesis balanceados {rel}")

if failed:
    print(f"\n{failed} chequeos fallidos")
    sys.exit(1)
print("\nSmoke contabilidad OK")
