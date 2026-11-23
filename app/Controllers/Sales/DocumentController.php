<?php

declare(strict_types=1);

namespace App\Controllers\Sales;

use App\Models\CRM\CrmClientModel;
use App\Models\Sales\SalesOrderModel;
use App\Models\Sales\SalesQuoteModel;
use App\Models\Sales\SalesReceiptModel;
use App\Services\SalesDocumentService;
use CodeIgniter\HTTP\ResponseInterface;

class DocumentController extends BaseSalesController
{
    public function quote(int $id)
    {
        return $this->outputFromKind('quote', $id);
    }

    public function order(int $id)
    {
        return $this->outputFromKind('order', $id);
    }

    public function receipt(int $id)
    {
        return $this->outputFromKind('receipt', $id);
    }

    public function previewQuote(int $id): ResponseInterface
    {
        return $this->preview($id, 'quote');
    }

    public function previewOrder(int $id): ResponseInterface
    {
        return $this->preview($id, 'order');
    }

    public function previewReceipt(int $id): ResponseInterface
    {
        return $this->preview($id, 'receipt');
    }

    public function preview(int $id, string $kind = 'quote'): ResponseInterface
    {
        $loaded = $this->loadPayload($kind, $id);
        if ($loaded === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Documento no encontrado.');
        }

        $html = (new SalesDocumentService())->buildHtml($kind, $loaded['payload'], $loaded['branch_id']);

        return $this->response->setHeader('Content-Type', 'text/html; charset=UTF-8')->setBody($html);
    }

    private function outputFromKind(string $kind, int $id)
    {
        $loaded = $this->loadPayload($kind, $id);
        if ($loaded === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Documento no encontrado.');
        }

        $rendered = (new SalesDocumentService())->renderPdf($kind, $loaded['payload'], $loaded['branch_id']);
        $inline   = $this->request->getGet('action') !== 'download';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader(
                'Content-Disposition',
                ($inline ? 'inline' : 'attachment') . '; filename="' . $rendered['filename'] . '"'
            )
            ->setBody($rendered['binary']);
    }

    /**
     * @return array{payload: array<string,mixed>, branch_id: ?int}|null
     */
    private function loadPayload(string $kind, int $id): ?array
    {
        if ($kind === 'quote') {
            $quote = (new SalesQuoteModel())->getWithItems($id);
            if (! $quote) {
                return null;
            }
            $client = (new CrmClientModel())->find((int) $quote->client_id);

            return [
                'branch_id' => $quote->branch_id ? (int) $quote->branch_id : null,
                'payload'   => [
                    'number'       => $quote->quote_number,
                    'status'       => $quote->status,
                    'notes'        => $quote->notes,
                    'subtotal'     => $quote->subtotal,
                    'tax_amount'   => $quote->tax_amount,
                    'total_amount' => $quote->total_amount,
                    'currency'     => $quote->currency ?? 'MXN',
                    'items'        => $quote->items ?? [],
                    'client'       => $this->clientPayload($client),
                    'valid_until'  => $quote->valid_until ?? null,
                ],
            ];
        }

        if ($kind === 'order') {
            $order = (new SalesOrderModel())->getWithItems($id);
            if (! $order) {
                return null;
            }
            $client = (new CrmClientModel())->find((int) $order->client_id);

            return [
                'branch_id' => $order->branch_id ? (int) $order->branch_id : null,
                'payload'   => [
                    'number'       => $order->order_number,
                    'status'       => $order->status,
                    'notes'        => $order->notes,
                    'subtotal'     => $order->subtotal,
                    'tax_amount'   => $order->tax_amount,
                    'total_amount' => $order->total_amount,
                    'currency'     => $order->currency ?? 'MXN',
                    'items'        => $order->items ?? [],
                    'client'       => $this->clientPayload($client),
                ],
            ];
        }

        $receipt = (new SalesReceiptModel())->detail($id);
        if (! $receipt) {
            return null;
        }
        $client = (new CrmClientModel())->find((int) $receipt->client_id);

        return [
            'branch_id' => $receipt->branch_id ? (int) $receipt->branch_id : null,
            'payload'   => [
                'number'       => $receipt->receipt_number,
                'status'       => $receipt->collection_status ?? $receipt->status,
                'notes'        => $receipt->notes,
                'subtotal'     => $receipt->subtotal,
                'tax_amount'   => $receipt->tax_amount,
                'total_amount' => $receipt->total_amount,
                'currency'     => $receipt->currency ?? 'MXN',
                'items'        => $receipt->items ?? [],
                'client'       => $this->clientPayload($client),
                'period'       => $receipt->period_label ?? null,
            ],
        ];
    }

    /**
     * @return array{name:string,code:string,rfc:string,email:string}
     */
    private function clientPayload(?object $client): array
    {
        if (! $client) {
            return ['name' => 'Público general', 'code' => '', 'rfc' => '', 'email' => ''];
        }

        return [
            'name'  => (string) $client->company_name,
            'code'  => (string) ($client->client_code ?? ''),
            'rfc'   => (string) ($client->rfc_tax_id ?? ''),
            'email' => (string) ($client->email ?? ''),
        ];
    }
}
