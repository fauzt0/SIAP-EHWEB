<?php

declare(strict_types=1);

namespace App\Controllers\Organization;

use App\Models\Sales\SalesDocumentTemplateModel;
use CodeIgniter\HTTP\ResponseInterface;

class SalesDocumentTemplateController extends BaseOrganizationController
{
    public function index(): string
    {
        $this->setViewSuccess('Plantillas de documentos de venta');
        $this->setPageTittleAhead('Plantillas PDF', 'Ventas y recibos');
        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Perfil Matriz'  => route_to('organization.profile'),
            'Plantillas PDF' => '',
        ]);
        $this->viewData['response'] = [
            'templates' => (new SalesDocumentTemplateModel())->orderBy('doc_kind', 'ASC')->findAll(),
            'branches'  => $this->branchModel->getActive(),
            'canManage' => auth()->user()->can('admin.sales-templates'),
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Organization/sales_document_templates');
    }

    public function update(int $id): ResponseInterface
    {
        $this->assertAjaxPost();

        $model = new SalesDocumentTemplateModel();
        $row   = $model->find($id);
        if (! $row) {
            $this->setOutputError('Plantilla no encontrada.');
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $ok = $model->update($id, [
            'title'             => trim((string) $this->request->getPost('title')),
            'legal_note'        => $this->request->getPost('legal_note'),
            'header_extra_html' => $this->request->getPost('header_extra_html'),
            'footer_extra_html' => $this->request->getPost('footer_extra_html'),
            'watermark_text'    => $this->request->getPost('watermark_text') ?: null,
            'paper'             => $this->request->getPost('paper') ?: 'Letter',
            'show_logo'         => $this->request->getPost('show_logo') ? 1 : 0,
            'default_branch_id' => $this->request->getPost('default_branch_id') ?: null,
            'active'            => $this->request->getPost('active') ? 1 : 0,
        ]);
        if (! $ok) {
            $this->setOutputError('No se guardó la plantilla.', $model->errors());
            $this->outputData['csrf'] = csrf_hash();

            return $this->response->setJSON($this->outputData);
        }

        $this->activityLogModel->logActivity(
            'sales_document_template_update',
            'Plantilla PDF ' . $row->doc_kind . ' ID ' . $id
        );
        $this->setOutputSuccess('Plantilla actualizada');
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }
}
