<?php

namespace App\Controllers\Financial;

use App\Models\Financial\FinSupplierReportModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Reportes de proveedores: montos, órdenes, porcentaje pagado y recordatorios.
 * Solo lectura. El permiso es el mismo de consulta del módulo (catalog.access).
 */
class SupplierReportController extends BaseFinancialController
{
    private const PO_LABELS = [
        'draft'     => 'Borrador',
        'sent'      => 'Enviada',
        'approved'  => 'Aprobada',
        'completed' => 'Completada',
        'cancelled' => 'Cancelada',
    ];

    private const REMINDER_LABELS = [
        'overdue'      => 'Vencido',
        'due_soon'     => 'Por vencer',
        'ungenerated'  => 'Sin generar',
    ];

    private const EXPENSE_LABELS = [
        'pending' => 'Pendiente',
        'partial' => 'Parcial',
        'paid'    => 'Pagado',
    ];

    public function index(): string
    {
        $supplierId = (int) ($this->request->getGet('supplier_id') ?? 0);
        $report     = (new FinSupplierReportModel())->snapshot(['supplier_id' => $supplierId]);

        $this->setViewSuccess('Reportes de proveedores');
        $this->setPageTittleAhead('Reportes', 'Reportes de proveedores');

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'      => route_to('dashboard.index'),
            'Proveedores' => route_to('suppliers.index'),
            'Reportes'    => '',
        ]);
        $this->viewData['report']           = $report;
        $this->viewData['supplierOptions']  = $this->supplierModel->orderBy('commercial_name', 'ASC')->findAll();
        $this->viewData['selectedSupplier'] = $supplierId;
        $this->viewData['poLabels']         = self::PO_LABELS;
        $this->viewData['reminderLabels']   = self::REMINDER_LABELS;
        $this->viewData['expenseLabels']    = self::EXPENSE_LABELS;

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Financial/Suppliers/reports_index');
    }

    public function export(): ResponseInterface
    {
        $supplierId = (int) ($this->request->getGet('supplier_id') ?? 0);
        $report     = (new FinSupplierReportModel())->snapshot(['supplier_id' => $supplierId]);
        $filename   = 'reportes-proveedores-' . date('Y-m-d') . '.csv';

        return $this->response
            ->setHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setBody($this->toCsv($report));
    }

    /**
     * @param array<string, mixed> $report
     */
    private function toCsv(array $report): string
    {
        $handle = fopen('php://temp', 'w+');
        if ($handle === false) {
            return '';
        }

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, ['Sección', 'Proveedor', 'Moneda', 'Concepto', 'Estatus', 'Vencimiento', 'Total', 'Pagado', 'Pendiente', 'Porcentaje']);

        foreach ($report['money'] as $row) {
            fputcsv($handle, [
                'Montos',
                '',
                $row['currency'],
                'Gastos no cancelados',
                '',
                '',
                $row['total'],
                $row['paid'],
                $row['outstanding'],
                $row['percent'] === null ? '' : $row['percent'],
            ]);
        }

        foreach ($report['suppliers'] as $row) {
            $open = [];
            foreach (['sent', 'approved', 'draft', 'completed', 'cancelled'] as $status) {
                $n = (int) ($row['po'][$status]['n'] ?? 0);
                if ($n > 0) {
                    $open[] = (self::PO_LABELS[$status] ?? $status) . ':' . $n;
                }
            }
            fputcsv($handle, [
                'Proveedor',
                $row['name'],
                $row['currency'],
                'OC ' . ($open === [] ? 'sin órdenes' : implode(' ', $open)),
                $row['active'] ? 'Activo' : 'Inactivo',
                '',
                $row['expense_total'],
                $row['paid'],
                $row['outstanding'],
                $row['percent'] === null ? '' : $row['percent'],
            ]);
        }

        foreach ($report['reminders'] as $row) {
            fputcsv($handle, [
                'Recordatorio',
                $row['supplier_name'],
                $row['currency'],
                trim($row['service_label'] . ' ' . $row['label']),
                self::REMINDER_LABELS[$row['kind']] ?? $row['kind'],
                $row['due_date'],
                '',
                '',
                $row['outstanding'],
                '',
            ]);
        }

        foreach ($report['open_orders'] as $row) {
            fputcsv($handle, [
                'Orden abierta',
                (string) ($row->commercial_name ?? ''),
                (string) ($row->currency ?? ''),
                (string) $row->po_number,
                self::PO_LABELS[$row->status] ?? (string) $row->status,
                (string) ($row->delivery_date ?? ''),
                (float) $row->total_amount,
                '',
                '',
                '',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv === false ? '' : $csv;
    }
}
