<?php

declare(strict_types=1);

namespace App\Controllers\PublicPages;

use App\Controllers\BaseController;
use App\Models\Sales\SalesQuoteModel;
use App\Models\Users\UserActivityLogsModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Aprobación en línea de cotización por token (equivalente ligero a firma).
 */
class QuoteApproveController extends BaseController
{
    public function show(string $token)
    {
        $quote = $this->findByToken($token);
        if (! $quote) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Enlace no válido.');
        }

        return view('Public/quote_approve', [
            'quote' => $quote,
            'token' => $token,
        ]);
    }

    public function approve(string $token): ResponseInterface
    {
        $model = new SalesQuoteModel();
        $quote = $this->findByToken($token);
        if (! $quote) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Enlace no válido.']);
        }
        if (in_array($quote->status, ['converted', 'rejected', 'expired'], true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Esta cotización ya no admite aprobación.']);
        }
        $model->update((int) $quote->id, [
            'status'      => 'approved',
            'approved_at' => date('Y-m-d H:i:s'),
        ]);
        (new UserActivityLogsModel())->logActivity('sales_quote_public_approve', 'Cotización ' . $quote->quote_number . ' aprobada en línea');

        return $this->response->setJSON(['success' => true, 'message' => 'Cotización aprobada. El equipo de ventas dará seguimiento.']);
    }

    private function findByToken(string $token): ?object
    {
        $token = preg_replace('/[^a-zA-Z0-9]/', '', $token) ?? '';
        if (strlen($token) < 16) {
            return null;
        }

        return (new SalesQuoteModel())->where('approve_token', $token)->first();
    }
}
