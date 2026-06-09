<?php

namespace App\Controllers\Financial;

use CodeIgniter\HTTP\ResponseInterface;
use App\Models\Financial\FinSupplierContactModel;
use App\Models\Users\UserActivityLogsModel;

/**
 * SupplierContactController
 *
 * Manages individual contact records for a supplier.
 * All endpoints are AJAX-only (§1). Mutations are logged (§8).
 * DB operations are delegated to FinSupplierContactModel (§9).
 */
class SupplierContactController extends BaseFinancialController
{
    private FinSupplierContactModel $contactModel;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface  $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface            $logger
    ): void {
        parent::initController($request, $response, $logger);
        $this->contactModel = new FinSupplierContactModel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET AJAX — List contacts for a supplier
    // ─────────────────────────────────────────────────────────────────────────

    public function list_ajax(int $supplierId)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $supplier = $this->supplierModel->find($supplierId);
        if (!$supplier) {
            $this->setOutputError('Proveedor no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $this->setOutputSuccess('OK');
        $this->outputData['response'] = [
            'contacts' => $this->contactModel->getContactsBySupplier($supplierId),
        ];

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET AJAX — Get single contact data (for edit modal population)
    // ─────────────────────────────────────────────────────────────────────────

    public function get_ajax(int $contactId)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $contact = $this->contactModel->find($contactId);
        if (!$contact) {
            $this->setOutputError('Contacto no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $this->setOutputSuccess('OK');
        $this->outputData['response'] = ['contact' => $contact];

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Add a new contact to a supplier (§1, §8)
    // ─────────────────────────────────────────────────────────────────────────

    public function store(int $supplierId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $supplier = $this->supplierModel->find($supplierId);
        if (!$supplier) {
            $this->setOutputError('Proveedor no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $data = $this->_extractContactData($supplierId);

        $newId = $this->contactModel->createContact($data);

        if (!$newId) {
            $this->setOutputError(
                'Error al guardar el contacto.',
                $this->contactModel->errors()
            );
            return $this->response->setJSON($this->outputData);
        }

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'create_supplier_contact',
            'Alta de contacto "' . $data['contact_name'] . '" para proveedor ID: ' . $supplierId
        );

        $this->setOutputSuccess('Contacto agregado correctamente.');
        $this->outputData['response'] = ['id' => $newId];
        $this->outputData['csrf']     = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Update an existing contact (§1, §8)
    // ─────────────────────────────────────────────────────────────────────────

    public function update(int $contactId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $contact = $this->contactModel->find($contactId);
        if (!$contact) {
            $this->setOutputError('Contacto no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        $data = $this->_extractContactData($contact->fin_supplier_id);

        $ok = $this->contactModel->updateContact($contactId, (int) $contact->fin_supplier_id, $data);

        if (!$ok) {
            $this->setOutputError(
                'Error al actualizar el contacto.',
                $this->contactModel->errors()
            );
            return $this->response->setJSON($this->outputData);
        }

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'update_supplier_contact',
            'Edición de contacto ID: ' . $contactId . ' | ' . $data['contact_name']
        );

        $this->setOutputSuccess('Contacto actualizado correctamente.');
        $this->outputData['response'] = ['id' => $contactId];
        $this->outputData['csrf']     = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Soft-delete a contact (§1, §8)
    // ─────────────────────────────────────────────────────────────────────────

    public function delete(int $contactId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $contact = $this->contactModel->find($contactId);
        if (!$contact) {
            $this->setOutputError('Contacto no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        if (!$this->contactModel->softDelete($contactId)) {
            $this->setOutputError('No se pudo eliminar el contacto.');
            return $this->response->setJSON($this->outputData);
        }

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'delete_supplier_contact',
            'Eliminó contacto ID: ' . $contactId . ' | ' . $contact->contact_name
                . ' (proveedor ID: ' . $contact->fin_supplier_id . ')'
        );

        $this->setOutputSuccess('Contacto eliminado correctamente.');
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST AJAX — Set a contact as primary (§1, §8)
    // ─────────────────────────────────────────────────────────────────────────

    public function set_primary(int $contactId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Método no válido.');
        }

        $contact = $this->contactModel->find($contactId);
        if (!$contact) {
            $this->setOutputError('Contacto no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);
            return $this->response->setJSON($this->outputData);
        }

        // Demote existing primary, then promote the new one (delegated to model)
        $ok = $this->contactModel->setPrimary($contactId, $contact->fin_supplier_id);

        if (!$ok) {
            $this->setOutputError('Error al actualizar el contacto principal.');
            return $this->response->setJSON($this->outputData);
        }

        // Audit log (§8)
        (new UserActivityLogsModel())->logActivity(
            'set_primary_supplier_contact',
            'Estableció contacto ID: ' . $contactId . ' como principal del proveedor ID: ' . $contact->fin_supplier_id
        );

        $this->setOutputSuccess('Contacto principal actualizado.');
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE HELPER
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Extracts and sanitizes contact fields from the HTTP request.
     */
    private function _extractContactData(int $supplierId): array
    {
        return [
            'fin_supplier_id' => $supplierId,
            'contact_name'    => trim((string) $this->request->getPost('contact_name')),
            'job_title'       => trim((string) $this->request->getPost('job_title')) ?: null,
            'email'           => trim((string) $this->request->getPost('email')) ?: null,
            'phone'           => trim((string) $this->request->getPost('phone')) ?: null,
            'is_primary'      => $this->request->getPost('is_primary') ? 1 : 0,
        ];
    }
}
