<?php

namespace App\Controllers\HR;

use App\Controllers\BaseController;
use App\Models\HR\ContractTemplateModel;
use App\Libraries\Breadcrumb;

class ContractTemplateController extends BaseController
{
    protected $templateModel;
    protected $breadcrumb;

    public function __construct()
    {
        $this->templateModel = new ContractTemplateModel();
        $this->breadcrumb    = new Breadcrumb([
            'Inicio'           => route_to('dashboard.index'),
            'Recursos Humanos' => route_to('hr.workers'),
            'Plantillas'       => route_to('hr.contracts.templates.index'),
        ]);
        $this->setPageTittleAhead('Recursos Humanos', 'Gestión de Contratos');
    }

    /**
     * Vista general: Listado de plantillas
     */
    public function index()
    {
        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml();
        return $this->renderLayout('Layouts/user_loggedin_layout', 'HR/contracts/templates_index');
    }

    /**
     * Endpoint DataTables (AJAX)
     */
    public function listAjax()
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Endpoint exclusivo para AJAX.");
        }

        $postData = $this->request->getPost();
        $list     = $this->templateModel->get_datatables($postData);
        $data     = [];

        foreach ($list as $template) {
            $row = [];
            $row[] = esc($template->name);
            $row[] = esc($template->description ?? 'Sin descripción');
            $row[] = '<span class="badge bg-primary">' . strtoupper(esc($template->base_model)) . '</span>';
            
            $defaultBadge = $template->is_default ? '<span class="badge bg-success">Default</span>' : '';
            $row[] = $defaultBadge;
            
            $row[] = date('d/m/Y H:i', strtotime($template->updated_at));

            // Acciones
            $actions = '<div class="d-flex gap-1">';
            if (auth()->user()->can('hr.edit')) {
                $actions .= '<a href="' . route_to('hr.contracts.templates.edit', $template->id) . '" class="btn btn-sm btn-outline-warning" title="Editar"><i class="fas fa-fw fa-edit"></i></a>';
            }
            if (auth()->user()->can('hr.delete')) {
                $actions .= '<button class="btn btn-sm btn-outline-danger btn-delete-template" data-id="' . $template->id . '" title="Eliminar"><i class="fas fa-fw fa-trash"></i></button>';
            }
            $actions .= '</div>';
            $row[] = $actions;

            $data[] = $row;
        }

        $output = [
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $this->templateModel->countAllResults(),
            'recordsFiltered' => $this->templateModel->count_filtered($postData),
            'data'            => $data,
        ];

        return $this->response->setJSON($output);
    }

    /**
     * Formulario de creación/edición de plantilla
     */
    public function form($id = null)
    {
        $template = null;
        if ($id) {
            $template = $this->templateModel->find($id);
            if (!$template) {
                return redirect()->to(route_to('hr.contracts.templates.index'))->with('error', 'Plantilla no encontrada.');
            }
            $this->setPageTittleAhead('Editar Plantilla', 'Edición de Contrato Legal');
        } else {
            $this->setPageTittleAhead('Nueva Plantilla', 'Creación de Contrato Legal');
        }

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml();
        $this->viewData['template']   = $template;

        return $this->renderLayout('Layouts/user_loggedin_layout', 'HR/contracts/template_form');
    }

    /**
     * Guarda la plantilla (Crear o Actualizar)
     */
    public function save()
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $id = $this->request->getPost('id');
        $data = [
            'name'        => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'content'     => $this->request->getPost('content'), // Contenido HTML del editor
            'base_model'  => $this->request->getPost('base_model'),
            'is_default'  => $this->request->getPost('is_default') ? 1 : 0,
        ];

        // Manejo de la subida del logotipo
        $file = $this->request->getFile('header_logo');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            // Asegurarnos que el directorio exista
            if (!is_dir(FCPATH . 'uploads/hr/contracts/logos')) {
                mkdir(FCPATH . 'uploads/hr/contracts/logos', 0755, true);
            }
            $file->move(FCPATH . 'uploads/hr/contracts/logos', $newName);
            $data['header_logo'] = 'uploads/hr/contracts/logos/' . $newName;
        }

        if ($id) {
            $this->templateModel->update($id, $data);
            $msg = 'Plantilla actualizada correctamente.';
        } else {
            $this->templateModel->insert($data);
            $msg = 'Plantilla creada correctamente.';
        }

        if ($this->templateModel->errors()) {
            $this->setOutputError('Error de validación al guardar.', $this->templateModel->errors());
            return $this->response->setJSON($this->outputData);
        }

        session()->setFlashdata('success', $msg);
        $this->setOutputSuccess($msg);
        $this->outputData['redirect'] = route_to('hr.contracts.templates.index');

        return $this->response->setJSON($this->outputData);
    }

    /**
     * Elimina una plantilla
     */
    public function delete($id)
    {
        if (!$this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if ($this->templateModel->delete($id)) {
            $this->setOutputSuccess('Plantilla eliminada correctamente.');
        } else {
            $this->setOutputError('No se pudo eliminar la plantilla.');
        }

        return $this->response->setJSON($this->outputData);
    }

    /**
     * Obtiene el contenido de un modelo base legal
     */
    public function getBaseModel()
    {
        $type = $this->request->getGet('type');
        $content = \App\Libraries\ContractBaseModels::getModel($type);
        
        return $this->response->setJSON(['content' => $content]);
    }
}
