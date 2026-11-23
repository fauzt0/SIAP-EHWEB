<?php

declare(strict_types=1);

namespace App\Services;

use App\Libraries\PluginSystem\PluginHookRegistry;
use App\Models\CRM\CrmClientModel;
use App\Models\CRM\CrmPluginLinkModel;

/**
 * Contrato ERP ↔ plugins para clientes, órdenes y servicios contratados.
 * Los plugins no deben escribir crm_* ni sales_* directo: usan este puente.
 */
final class ClientCommerceBridge
{
    public function getClient(int $clientId): ?object
    {
        return (new CrmClientModel())->find($clientId);
    }

    /**
     * @return list<array{hook:string,html?:string,title?:string,icon?:string}>
     */
    public function collectClientTabs(int $clientId): array
    {
        $tabs = PluginHookRegistry::doActionCollect('crm.client.show.tabs', $clientId);

        return is_array($tabs) ? $tabs : [];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function collectOrderTabs(int $orderId): array
    {
        $tabs = PluginHookRegistry::doActionCollect('sales.order.show.tabs', $orderId);

        return is_array($tabs) ? $tabs : [];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function collectQuoteTabs(int $quoteId): array
    {
        $tabs = PluginHookRegistry::doActionCollect('sales.quote.show.tabs', $quoteId);

        return is_array($tabs) ? $tabs : [];
    }

    /**
     * Enlaza un recurso de plugin (p. ej. servidor) a un cliente y opcionalmente a una OC/servicio.
     *
     * @param array<string,mixed> $meta
     */
    public function attachPluginLink(
        int $clientId,
        string $pluginSlug,
        string $externalRef,
        ?int $orderId = null,
        ?int $serviceId = null,
        array $meta = []
    ): int|false {
        $model = new CrmPluginLinkModel();
        $id    = $model->insert([
            'client_id'      => $clientId,
            'sales_order_id' => $orderId,
            'service_id'     => $serviceId,
            'plugin_slug'    => $pluginSlug,
            'external_ref'   => $externalRef,
            'meta_json'      => $meta === [] ? null : json_encode($meta, JSON_UNESCAPED_UNICODE),
        ]);

        return $id ? (int) $id : false;
    }

    public function linksForClient(int $clientId): array
    {
        return (new CrmPluginLinkModel())->where('client_id', $clientId)->findAll();
    }

    /**
     * Productos/servicios contratados del cliente (dominios, certificados, físicos, etc.).
     * Hosting/VPS lo aporta un plugin vía plugin_ref + crm.client.show.tabs.
     *
     * @return list<object>
     */
    public function clientServices(int $clientId): array
    {
        return (new \App\Models\Sales\CustomerServiceModel())->where('client_id', $clientId)->findAll();
    }
}
