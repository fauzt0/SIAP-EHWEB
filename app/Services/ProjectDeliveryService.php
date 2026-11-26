<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Projects\PrmProjectItemModel;
use App\Models\Projects\PrmProjectMilestoneModel;
use App\Models\Projects\PrmProjectModel;
use App\Models\Sales\SalesQuoteModel;

class ProjectDeliveryService
{
    /**
     * Crea un proyecto a partir de una cotización (partidas del carrito con precio personalizado).
     *
     * @return array{ok:bool,id?:int,skipped?:bool,message?:string}
     */
    public function fromQuote(int $quoteId, ?int $orderId = null, ?int $opportunityId = null): array
    {
        $quote = (new SalesQuoteModel())->getWithItems($quoteId);
        if (! $quote) {
            return ['ok' => false, 'message' => 'Cotización no encontrada.'];
        }

        $projects = new PrmProjectModel();
        $existing = $projects->where('quote_id', $quoteId)->first();
        if ($existing) {
            if ($orderId && empty($existing->order_id)) {
                $projects->update((int) $existing->id, ['order_id' => $orderId]);
            }

            return ['ok' => true, 'skipped' => true, 'id' => (int) $existing->id];
        }

        $projects->db->transStart();
        $id = $projects->insert([
            'code'            => $projects->nextCode(),
            'name'            => 'Proyecto ' . ($quote->quote_number ?? $quoteId),
            'client_id'       => (int) $quote->client_id,
            'quote_id'        => $quoteId,
            'order_id'        => $orderId,
            'opportunity_id'  => $opportunityId,
            'status'          => $orderId ? 'in_progress' : 'quoting',
            'progress'        => $orderId ? 10 : 0,
            'budget'          => (float) $quote->total_amount,
            'start_date'      => date('Y-m-d'),
            'created_by'      => auth()->id(),
            'manager_id'      => auth()->id(),
        ], true);

        if (! $id) {
            $projects->db->transRollback();

            return ['ok' => false, 'message' => 'No se pudo crear el proyecto.'];
        }

        $itemModel = new PrmProjectItemModel();
        foreach ($quote->items ?? [] as $item) {
            $itemModel->insert([
                'project_id'    => (int) $id,
                'product_id'    => $item->product_id ?? null,
                'quote_item_id' => $item->id ?? null,
                'item_name'     => $item->item_name ?? 'Servicio',
                'catalog_price' => (float) ($item->unit_price ?? 0),
                'unit_price'    => (float) ($item->unit_price ?? 0),
                'quantity'      => (float) ($item->quantity ?? 1),
                'billing_cycle' => $item->billing_cycle ?? null,
                'status'        => 'pending',
            ]);
        }

        (new PrmProjectMilestoneModel())->insert([
            'project_id' => (int) $id,
            'name'       => 'Kickoff',
            'due_date'   => date('Y-m-d', strtotime('+7 days')),
            'status'     => 'pending',
            'sort_order' => 1,
        ]);

        $projects->db->transComplete();
        if (! $projects->db->transStatus()) {
            return ['ok' => false, 'message' => 'Falló la transacción del proyecto.'];
        }

        return ['ok' => true, 'id' => (int) $id];
    }

    public function fromOpportunity(object $opportunity): array
    {
        if (! empty($opportunity->quote_id)) {
            return $this->fromQuote((int) $opportunity->quote_id, $opportunity->order_id ? (int) $opportunity->order_id : null, (int) $opportunity->id);
        }

        $projects = new PrmProjectModel();
        $existing = $projects->where('opportunity_id', (int) $opportunity->id)->first();
        if ($existing) {
            return ['ok' => true, 'skipped' => true, 'id' => (int) $existing->id];
        }

        $id = $projects->insert([
            'code'           => $projects->nextCode(),
            'name'           => $opportunity->title,
            'client_id'      => (int) $opportunity->client_id,
            'opportunity_id' => (int) $opportunity->id,
            'status'         => 'quoting',
            'budget'         => (float) $opportunity->expected_value,
            'created_by'     => auth()->id(),
            'manager_id'     => $opportunity->assigned_user_id ?: auth()->id(),
        ], true);

        return $id ? ['ok' => true, 'id' => (int) $id] : ['ok' => false, 'message' => 'No se creó el proyecto.'];
    }
}
