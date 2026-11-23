<?php

declare(strict_types=1);

namespace App\Services;

use App\Libraries\PluginSystem\PluginHookRegistry;
use App\Models\Sales\CustomerServiceModel;
use App\Models\Sales\SalesOrderItemModel;
use App\Models\Sales\SalesOrderModel;
use App\Models\Sales\SalesReceiptModel;

/**
 * Recibos de cobranza:
 * - one_time: un solo recibo + prefactura con el periodo contratado
 * - monthly / semiannual / yearly: un recibo por periodo de renovación
 */
final class SalesReceiptScheduler
{
    /**
     * @return array{one_time: ?int, renewals: list<int>}
     */
    public function issueInitialForOrder(int $orderId, ?int $userId = null, ?string $notes = null): array
    {
        $orders = new SalesOrderModel();
        $itemsM = new SalesOrderItemModel();
        $svcs   = new CustomerServiceModel();
        $recs   = new SalesReceiptModel();

        $order = $orders->find($orderId);
        $out   = ['one_time' => null, 'renewals' => []];
        if (! $order || empty($order->client_id)) {
            return $out;
        }

        $items        = $itemsM->where('order_id', $orderId)->findAll();
        $oneTimeLines = [];
        foreach ($items as $item) {
            if (SalesBillingCycle::isRecurring((string) ($item->billing_cycle ?? 'one_time'))) {
                continue;
            }
            $oneTimeLines[] = $this->lineFromItem($item);
        }

        if ($oneTimeLines !== [] && ! $recs->findInitialOneTime($orderId)) {
            $start = $items[0]->period_start ?? date('Y-m-d');
            $end   = $items[0]->period_end ?? $start;
            $id    = $recs->issue([
                'client_id'    => (int) $order->client_id,
                'order_id'     => $orderId,
                'receipt_kind' => 'one_time',
                'is_initial'   => 1,
                'period_label' => 'ONE-' . $orderId,
                'period_start' => $start,
                'period_end'   => $end,
                'due_date'     => date('Y-m-d'),
                'currency'     => $order->currency ?? 'MXN',
                'notes'        => $notes,
                'created_by'   => $userId,
                'plugin_ref'   => $order->plugin_ref ?? null,
            ], $oneTimeLines);
            if ($id) {
                $out['one_time'] = $id;
            }
        }

        foreach ($svcs->where('order_id', $orderId)->findAll() as $svc) {
            $rid = $this->issueForService($svc, true, $userId, $notes);
            if ($rid) {
                $out['renewals'][] = $rid;
            }
        }

        PluginHookRegistry::doAction('sales.order.receipts.issued', $orderId, $out);

        return $out;
    }

    /**
     * @return array{created: int, skipped: int, services: int, overdue: int}
     */
    public function generateDue(?string $horizonEnd = null, ?int $userId = null): array
    {
        $horizonEnd = $horizonEnd ?: date('Y-m-t', strtotime('first day of next month'));
        $recs       = new SalesReceiptModel();
        $overdue    = $recs->refreshOverdue();
        $services   = (new CustomerServiceModel())
            ->where('status', 'active')
            ->where('next_due_date <=', $horizonEnd)
            ->whereNotIn('billing_cycle', ['one_time'])
            ->findAll();

        $created = 0;
        $skipped = 0;
        foreach ($services as $svc) {
            $id = $this->issueForService($svc, false, $userId, 'Renovación automática ' . ($svc->next_due_date ?? ''));
            if ($id) {
                $created++;
            } else {
                $skipped++;
            }
        }

        return [
            'created'  => $created,
            'skipped'  => $skipped,
            'services' => count($services),
            'overdue'  => $overdue,
        ];
    }

    public function issueForService(object $svc, bool $isInitial, ?int $userId = null, ?string $notes = null): int|false
    {
        if (empty($svc->id) || empty($svc->client_id) || empty($svc->order_id)) {
            return false;
        }
        if (! SalesBillingCycle::isRecurring((string) $svc->billing_cycle)) {
            return false;
        }

        $items = new SalesOrderItemModel();
        $item  = null;
        if (! empty($svc->order_item_id)) {
            $item = $items->find((int) $svc->order_item_id);
        }
        if (! $item) {
            $q = $items->where('order_id', (int) $svc->order_id);
            if (! empty($svc->product_id)) {
                $q->where('product_id', $svc->product_id);
            }
            $item = $q->orderBy('id', 'DESC')->first();
        }

        $due = (string) ($svc->next_due_date ?: date('Y-m-d'));
        if ($isInitial) {
            $due = (string) (($item !== null && ! empty($item->period_start)) ? $item->period_start : date('Y-m-d'));
        }

        $label = SalesBillingCycle::periodLabel((string) $svc->billing_cycle, $due);
        $recs  = new SalesReceiptModel();
        if ($recs->findByServicePeriod((int) $svc->id, $label)) {
            return false;
        }

        $start = $due;
        $end   = SalesBillingCycle::periodEnd((string) $svc->billing_cycle, $start);
        $itemId   = $item !== null ? (int) $item->id : null;
        $itemName = $svc->reference_name ?: ($item !== null ? (string) $item->item_name : 'Renovación');
        $itemRef  = $svc->plugin_ref ?? ($item !== null ? ($item->plugin_ref ?? null) : null);
        $id       = $recs->issue([
            'client_id'     => (int) $svc->client_id,
            'order_id'      => (int) $svc->order_id,
            'service_id'    => (int) $svc->id,
            'order_item_id' => $itemId,
            'receipt_kind'  => 'renewal',
            'is_initial'    => $isInitial ? 1 : 0,
            'period_label'  => $label,
            'period_start'  => $start,
            'period_end'    => $end,
            'due_date'      => $due,
            'notes'         => $notes,
            'created_by'    => $userId,
            'plugin_ref'    => $svc->plugin_ref ?? null,
        ], [[
            'order_item_id' => $itemId,
            'service_id'    => (int) $svc->id,
            'product_id'    => $svc->product_id ?? null,
            'item_name'     => $itemName,
            'billing_cycle' => $svc->billing_cycle,
            'quantity'      => 1,
            'unit_price'    => $svc->amount,
            'plugin_ref'    => $itemRef,
        ]]);

        if (! $id) {
            return false;
        }

        $payload = ['last_renewal_at' => date('Y-m-d H:i:s')];
        if (! $isInitial) {
            $payload['next_due_date'] = SalesBillingCycle::nextDue((string) $svc->billing_cycle, $due);
        }
        (new CustomerServiceModel())->update((int) $svc->id, $payload);

        return $id;
    }

    /**
     * @return array<string,mixed>
     */
    private function lineFromItem(object $item): array
    {
        return [
            'order_item_id' => (int) $item->id,
            'service_id'    => $item->service_id ?? null,
            'product_id'    => $item->product_id ?? null,
            'item_name'     => $item->item_name,
            'billing_cycle' => $item->billing_cycle ?? 'one_time',
            'quantity'      => (int) ($item->quantity ?? 1),
            'unit_price'    => $item->unit_price,
            'plugin_ref'    => $item->plugin_ref ?? null,
        ];
    }
}
