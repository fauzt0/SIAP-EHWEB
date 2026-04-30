<?php

namespace App\Controllers\HR;

class WorkerDocumentController extends BaseHrController
{
    /**
     * Descarga segura de un documento del expediente digital.
     */
    public function downloadDocument(int $documentId)
    {
        $docModel = new \App\Models\HR\HrDocumentModel();
        $document = $docModel->find($documentId);

        if (!$document) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Documento no encontrado.');
        }

        $filePath = WRITEPATH . 'uploads/' . $document->file_path;

        if (!file_exists($filePath)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('El archivo físico no existe.');
        }

        $mimeType = mime_content_type($filePath);
        $action   = $this->request->getGet('action') === 'view' ? 'inline' : 'attachment';

        return $this->response->download($filePath, null)
            ->setFileName(basename($document->file_path))
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Disposition', $action . '; filename="' . basename($document->file_path) . '"');
    }

    /**
     * Elimina un documento (Soft Delete)
     */
    public function deleteDocument(int $documentId)
    {
        $docModel = new \App\Models\HR\HrDocumentModel();
        if ($docModel->delete($documentId)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Documento eliminado correctamente.']);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'No se pudo eliminar el documento.']);
    }

    /**
     * Restaura un documento eliminado
     */
    public function restoreDocument(int $documentId)
    {
        $docModel = new \App\Models\HR\HrDocumentModel();
        // Se requiere withDeleted() para actualizar un registro ya eliminado
        if ($docModel->withDeleted()->update($documentId, ['deleted_at' => null])) {
            return $this->response->setJSON(['success' => true, 'message' => 'Documento restaurado correctamente.']);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'No se pudo restaurar el documento.']);
    }

    /**
     * Actualiza un documento (Metadatos y/o reemplazo de archivo)
     */
    public function updateDocument(int $documentId)
    {
        $docModel = new \App\Models\HR\HrDocumentModel();
        $oldDoc   = $docModel->find($documentId);

        if (!$oldDoc) {
            return $this->response->setJSON(['success' => false, 'message' => 'Documento no encontrado.']);
        }

        $data = [
            'document_type_id' => $this->request->getPost('document_type_id'),
            'notes'            => $this->request->getPost('notes'),
        ];

        // Manejo de reemplazo de archivo
        $newFile = $this->request->getFile('document_file');
        if ($newFile && $newFile->isValid() && !$newFile->hasMoved()) {
            $uploadPath = WRITEPATH . 'uploads/hr/documents/' . $oldDoc->profile_id;
            
            // Asegurar que el directorio existe
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }

            // Generar nombre y mover
            $newName = $newFile->getRandomName();
            $newFile->move($uploadPath, $newName);
            
            $data['file_path'] = 'hr/documents/' . $oldDoc->profile_id . '/' . $newName;

            // ELIMINAR FÍSICAMENTE EL ARCHIVO ANTERIOR PARA AHORRAR ESPACIO
            $oldFullPath = WRITEPATH . 'uploads/' . $oldDoc->file_path;
            if (file_exists($oldFullPath) && is_file($oldFullPath)) {
                unlink($oldFullPath);
            }
        }

        if ($docModel->update($documentId, $data)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Documento actualizado correctamente.']);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'No se pudo actualizar el documento.']);
    }
    
    /**
     * Agrega un documento individual vía AJAX (solo en edición)
     */
    public function addDocumentAjax(int $profileId)
    {
        if (!$this->request->isAJAX() || !$this->request->is('post')) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Petición denegada.']);
        }

        $file = $this->request->getFile('document_file');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Archivo no válido.']);
        }

        $typeId = $this->request->getPost('document_type_id');
        $notes  = $this->request->getPost('notes');

        $uploadPath = WRITEPATH . 'uploads/hr/documents/' . $profileId;

        // Asegurar que el directorio existe
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0777, true);
        }

        $newName = $file->getRandomName();
        
        if ($file->move($uploadPath, $newName)) {
            $docModel = new \App\Models\HR\HrDocumentModel();
            $docId = $docModel->insert([
                'profile_id'       => $profileId,
                'document_type_id' => !empty($typeId) ? (int)$typeId : null,
                'file_path'        => 'hr/documents/' . $profileId . '/' . $newName,
                'notes'            => $notes,
            ]);

            $typeModel = new \App\Models\HR\HrDocumentTypeModel();
            $typeName = $typeModel->find($typeId)->name ?? 'Otro';

            return $this->response->setJSON([
                'success' => true, 
                'message' => 'Documento subido correctamente.',
                'doc' => [
                    'id' => $docId,
                    'type_name' => $typeName,
                    'notes' => $notes,
                    'created_at' => date('Y-m-d H:i:s'),
                ]
            ]);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Error al mover el archivo físico.']);
    }
}
