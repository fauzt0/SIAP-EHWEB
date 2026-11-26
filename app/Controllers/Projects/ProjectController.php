<?php

declare(strict_types=1);

namespace App\Controllers\Projects;

use App\Controllers\BaseController;
use App\Libraries\Breadcrumb;
use App\Models\CRM\CrmClientModel;
use App\Models\Projects\PrmProjectItemModel;
use App\Models\Projects\PrmProjectMilestoneModel;
use App\Models\Projects\PrmProjectModel;
use App\Models\Users\UserActivityLogsModel;
use App\Services\ProjectDeliveryService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ProjectController extends BaseController
{
    protected Breadcrumb $breadcrumb;
    protected PrmProjectModel $projects;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->projects   = new PrmProjectModel();
        $this->breadcrumb = new Breadcrumb([
            'Inicio'    => base_url(),
            'Proyectos' => route_to('projects.index'),
        ]);
    }

    public function index(): string
    {
        $this->setViewSuccess('Proyectos');
        $this->setPageTittleAhead('Proyectos', 'Desarrollo y entrega');
        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'    => base_url(),
            'Proyectos' => '',
        ]);
        $this->viewData['response'] = [
            'clients'  => (new CrmClientModel())->getForSelect(),
            'statuses' => PrmProjectModel::STATUSES,
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'Projects/projects_index');
    }

    public function list_ajax(): ResponseInterface
    {
        $this->assertAjaxPost();
        $post = $this->request->getPost();
        $list = $this->projects->get_datatables($post);
        $data = [];
        foreach ($list as $row) {
            $row   = (array) $row;
            $id    = (int) $row['id'];
            $st    = $row['status'] ?? 'quoting';
            $label = PrmProjectModel::STATUSES[$st] ?? $st;
            $data[] = [
                esc($row['code'] ?? ''),
                esc($row['name'] ?? ''),
                esc($row['company_name'] ?? ''),
                '<span class="badge bg-secondary">' . esc($label) . '</span>',
                (int) ($row['progress'] ?? 0) . '%',
                '$' . number_format((float) ($row['budget'] ?? 0), 2),
                esc($row['due_date'] ?? '—'),
                '<button class="btn btn-sm btn-outline-info btn-show-prj" data-id="' . $id . '">Ver</button>',
            ];
        }

        return $this->response->setJSON([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $this->projects->count_all(),
            'recordsFiltered' => $this->projects->count_filtered($post),
            'data'            => $data,
            'csrf'            => csrf_hash(),
        ]);
    }

    public function show_ajax(int $id): ResponseInterface
    {
        if (! $this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }
        $project = $this->projects->find($id);
        if (! $project) {
            $this->setOutputError('Proyecto no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->json();
        }
        $this->setOutputSuccess('Proyecto', [
            'project'    => $project,
            'items'      => (new PrmProjectItemModel())->where('project_id', $id)->findAll(),
            'milestones' => (new PrmProjectMilestoneModel())->where('project_id', $id)->orderBy('sort_order', 'ASC')->findAll(),
            'statuses'   => PrmProjectModel::STATUSES,
        ]);

        return $this->json();
    }

    public function store(): ResponseInterface
    {
        $this->assertAjaxPost();
        $quoteId = (int) $this->request->getPost('quote_id');
        if ($quoteId > 0) {
            $result = (new ProjectDeliveryService())->fromQuote($quoteId);
            if (! ($result['ok'] ?? false)) {
                $this->setOutputError($result['message'] ?? 'No se pudo crear desde la cotización.');

                return $this->json();
            }
            $id = (int) $result['id'];
            (new UserActivityLogsModel())->logActivity('project_create', 'Proyecto ID ' . $id . ' desde cotización ' . $quoteId);
            $this->setOutputSuccess('Proyecto creado desde cotización.', ['id' => $id]);

            return $this->json();
        }

        $id = $this->projects->insert([
            'code'       => $this->projects->nextCode(),
            'name'       => trim((string) $this->request->getPost('name')),
            'client_id'  => (int) $this->request->getPost('client_id'),
            'status'     => $this->request->getPost('status') ?: 'quoting',
            'progress'   => (int) ($this->request->getPost('progress') ?: 0),
            'budget'     => (float) $this->request->getPost('budget'),
            'due_date'   => $this->request->getPost('due_date') ?: null,
            'notes'      => $this->request->getPost('notes'),
            'created_by' => auth()->id(),
            'manager_id' => auth()->id(),
        ]);
        if (! $id) {
            $this->setOutputError('No se pudo crear el proyecto.', $this->projects->errors());

            return $this->json();
        }
        (new UserActivityLogsModel())->logActivity('project_create', 'Proyecto ID ' . $id);
        $this->setOutputSuccess('Proyecto creado.', ['id' => $id]);

        return $this->json();
    }

    public function update_status(int $id): ResponseInterface
    {
        $this->assertAjaxPost();
        $project = $this->projects->find($id);
        if (! $project) {
            $this->setOutputError('Proyecto no encontrado.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->json();
        }
        $status   = (string) $this->request->getPost('status');
        $progress = (int) $this->request->getPost('progress');
        if (! isset(PrmProjectModel::STATUSES[$status])) {
            $this->setOutputError('Estatus no válido.');

            return $this->json();
        }
        $data = ['status' => $status, 'progress' => max(0, min(100, $progress))];
        if ($status === 'completed') {
            $data['delivered_at'] = date('Y-m-d H:i:s');
            $data['progress']     = 100;
        }
        $this->projects->update($id, $data);
        (new UserActivityLogsModel())->logActivity('project_status', $project->code . ' → ' . $status);
        $this->setOutputSuccess('Avance actualizado.');

        return $this->json();
    }

    public function add_milestone(int $id): ResponseInterface
    {
        $this->assertAjaxPost();
        $model = new PrmProjectMilestoneModel();
        $ok    = $model->insert([
            'project_id' => $id,
            'name'       => trim((string) $this->request->getPost('name')),
            'due_date'   => $this->request->getPost('due_date') ?: null,
            'status'     => 'pending',
            'sort_order' => 0,
        ]);
        if (! $ok) {
            $this->setOutputError('No se pudo agregar el hito.', $model->errors());

            return $this->json();
        }
        (new UserActivityLogsModel())->logActivity('project_milestone', 'Hito en proyecto ' . $id);
        $this->setOutputSuccess('Hito agregado.');

        return $this->json();
    }

    private function assertAjaxPost(): void
    {
        if (! $this->request->isAJAX() || ! $this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }
    }

    private function json(): ResponseInterface
    {
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }
}
