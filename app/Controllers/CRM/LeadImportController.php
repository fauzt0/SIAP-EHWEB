<?php

declare(strict_types=1);

namespace App\Controllers\CRM;

use App\Models\CRM\CrmClientModel;
use App\Models\Users\UserActivityLogsModel;
use App\Services\CrmRoundRobinService;
use CodeIgniter\HTTP\ResponseInterface;

class LeadImportController extends BaseCrmController
{
    public function import_csv(): ResponseInterface
    {
        if (! $this->request->isAJAX() || ! $this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $file = $this->request->getFile('csv_file');
        if (! $file || ! $file->isValid()) {
            $this->setOutputError('Seleccione un archivo CSV (empresa,email,telefono,rfc).');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $handle = fopen($file->getTempName(), 'r');
        if ($handle === false) {
            $this->setOutputError('No se pudo leer el CSV.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $created = 0;
        $skipped = 0;
        $header  = true;
        $round   = new CrmRoundRobinService();
        while (($cols = fgetcsv($handle)) !== false) {
            if ($header) {
                $header = false;
                if (isset($cols[0]) && stripos((string) $cols[0], 'empresa') !== false) {
                    continue;
                }
            }
            $name  = trim((string) ($cols[0] ?? ''));
            $email = trim((string) ($cols[1] ?? ''));
            $phone = trim((string) ($cols[2] ?? ''));
            $rfc   = strtoupper(preg_replace('/\s+/', '', (string) ($cols[3] ?? '')) ?? '');
            if ($name === '') {
                continue;
            }
            if ($this->clientModel->findDuplicateContact($email !== '' ? $email : null, $phone !== '' ? $phone : null, $rfc !== '' ? $rfc : null)) {
                $skipped++;
                continue;
            }
            $ok = $this->clientModel->insert([
                'client_code'      => $this->clientModel->nextClientCode(),
                'company_name'     => $name,
                'email'            => $email !== '' ? $email : null,
                'phone'            => $phone !== '' ? $phone : null,
                'rfc_tax_id'       => $rfc !== '' ? $rfc : null,
                'status'           => 'lead',
                'source'           => 'csv',
                'assigned_user_id' => $round->nextUserId(),
                'active'           => 1,
            ]);
            if ($ok) {
                $created++;
            }
        }
        fclose($handle);

        (new UserActivityLogsModel())->logActivity('crm_lead_import', 'CSV altas ' . $created . ' omitidas ' . $skipped);
        $this->setOutputSuccess('Importación terminada: ' . $created . ' altas, ' . $skipped . ' duplicados.');
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function webhook(): ResponseInterface
    {
        $token = (string) ($this->request->getHeaderLine('X-CRM-Token') ?: $this->request->getPost('token') ?: $this->request->getGet('token'));
        $expected = env('crm.leadWebhookToken', '');
        if ($expected === '' || ! hash_equals($expected, $token)) {
            return $this->response->setStatusCode(401)->setJSON(['success' => false, 'message' => 'Token inválido.']);
        }

        $payload = $this->request->getJSON(true);
        if (! is_array($payload)) {
            $payload = $this->request->getPost();
        }
        $name  = trim((string) ($payload['company_name'] ?? $payload['name'] ?? ''));
        $email = trim((string) ($payload['email'] ?? ''));
        $phone = trim((string) ($payload['phone'] ?? ''));
        $rfc   = strtoupper(preg_replace('/\s+/', '', (string) ($payload['rfc'] ?? '')) ?? '');
        if ($name === '') {
            return $this->response->setJSON(['success' => false, 'message' => 'Falta company_name.']);
        }

        $db = db_connect();
        if ($db->tableExists('crm_lead_inbox')) {
            $db->table('crm_lead_inbox')->insert([
                'source'     => (string) ($payload['source'] ?? 'webhook'),
                'token'      => substr($token, 0, 64),
                'payload'    => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'status'     => 'received',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $dup = $this->clientModel->findDuplicateContact($email !== '' ? $email : null, $phone !== '' ? $phone : null, $rfc !== '' ? $rfc : null);
        if ($dup) {
            return $this->response->setJSON(['success' => true, 'duplicate' => true, 'client_id' => (int) $dup->id]);
        }

        $id = $this->clientModel->insert([
            'client_code'      => $this->clientModel->nextClientCode(),
            'company_name'     => $name,
            'email'            => $email !== '' ? $email : null,
            'phone'            => $phone !== '' ? $phone : null,
            'rfc_tax_id'       => $rfc !== '' ? $rfc : null,
            'status'           => 'lead',
            'source'           => (string) ($payload['source'] ?? 'webhook'),
            'assigned_user_id' => (new CrmRoundRobinService())->nextUserId(),
            'active'           => 1,
        ]);

        return $this->response->setJSON(['success' => (bool) $id, 'client_id' => $id ?: null]);
    }
}
