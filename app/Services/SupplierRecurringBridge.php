<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Financial\FinRecurringExpenseModel;

/**
 * Contrato ERP ↔ plugins para pagos recurrentes de proveedores.
 *
 * Los plugins (p. ej. ServerAdmin) NO deben escribir fin_ledger_entries ni tablas
 * de deuda propias: crean/actualizan una fila en fin_recurring_expenses y guardan
 * su id en recurring_expense_id. Los vencimientos (fin_expenses), recibos, reportes
 * y alertas los resuelve el módulo Proveedores.
 *
 * @see agentic/plugin_001_server_admin.md §8.5
 */
final class SupplierRecurringBridge
{
    private FinRecurringExpenseModel $templates;

    public function __construct(?FinRecurringExpenseModel $templates = null)
    {
        $this->templates = $templates ?? new FinRecurringExpenseModel();
    }

    /**
     * Crea o actualiza una plantilla recurrente ligada a un proveedor.
     *
     * @param array{
     *   description: string,
     *   amount: float|int|string,
     *   currency?: string,
     *   service_type?: string,
     *   frequency?: string,
     *   billing_day?: int|null,
     *   billing_weekday?: int|null,
     *   start_date?: string,
     *   end_date?: string|null,
     *   next_due_date?: string,
     *   notify_days_before?: int,
     *   is_active?: int|bool,
     *   org_branch_id?: int|null,
     *   category_id?: int|null
     * } $data
     *
     * @return array{success: bool, template_id?: int, occurrences_created?: int, errors?: array}
     */
    public function syncTemplate(
        int $supplierId,
        array $data,
        ?int $existingTemplateId = null,
        bool $generateOccurrences = true
    ): array {
        if ($supplierId <= 0) {
            return ['success' => false, 'errors' => ['supplier_id' => 'Proveedor inválido.']];
        }

        $existingTemplateId = ($existingTemplateId !== null && $existingTemplateId > 0)
            ? $existingTemplateId
            : null;

        if ($existingTemplateId !== null) {
            $row = $this->templates->find($existingTemplateId);
            if (!$row || (int) $row->supplier_id !== $supplierId) {
                return [
                    'success' => false,
                    'errors'  => ['template' => 'La plantilla no existe o no pertenece al proveedor.'],
                ];
            }

            if (!$this->templates->updateForSupplier($existingTemplateId, $data)) {
                return ['success' => false, 'errors' => $this->templates->errors()];
            }

            $templateId = $existingTemplateId;
        } else {
            $templateId = $this->templates->createForSupplier($supplierId, $data);
            if ($templateId === false) {
                return ['success' => false, 'errors' => $this->templates->errors()];
            }
        }

        $created = 0;
        $active  = array_key_exists('is_active', $data)
            ? (int) $data['is_active']
            : 1;

        if ($generateOccurrences && $active === 1) {
            $horizon = date('Y-m-t', strtotime('+2 months'));
            $result  = (new RecurringExpenseScheduler())->generate($horizon, $templateId);
            $created = (int) ($result['created'] ?? 0);
        }

        return [
            'success'               => true,
            'template_id'           => (int) $templateId,
            'occurrences_created'   => $created,
        ];
    }

    /**
     * Payload recomendado para VPS/servidores (plugin 001).
     *
     * @param array{name: string, monthly_cost: float|int|string, currency?: string, renewal_date?: string, notify_days_before?: int} $server
     */
    public static function hostingPayloadFromServer(array $server, ?string $providerLabel = null): array
    {
        $name  = trim((string) ($server['name'] ?? 'Servidor'));
        $label = $providerLabel ? $name . ' — ' . $providerLabel : $name;
        $renew = $server['renewal_date'] ?? null;

        $billingDay = 1;
        if (is_string($renew) && preg_match('/^\d{4}-\d{2}-(\d{2})$/', $renew, $m)) {
            $billingDay = (int) $m[1];
        }

        return [
            'description'          => $label,
            'service_type'         => 'hosting',
            'amount'               => (float) ($server['monthly_cost'] ?? 0),
            'currency'             => strtoupper(trim((string) ($server['currency'] ?? 'USD'))),
            'frequency'            => 'monthly',
            'billing_day'          => $billingDay,
            'start_date'           => $renew ?: date('Y-m-d'),
            'next_due_date'        => $renew,
            'notify_days_before'   => (int) ($server['notify_days_before'] ?? 15),
            'is_active'            => 1,
        ];
    }

    public function deactivateTemplate(int $templateId): bool
    {
        if ($templateId <= 0) {
            return false;
        }

        return $this->templates->update($templateId, ['is_active' => 0]);
    }
}