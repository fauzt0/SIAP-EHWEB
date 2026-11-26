<?php

declare(strict_types=1);

namespace App\Controllers\CRM;

use App\Models\CRM\CrmFollowupModel;
use App\Models\CRM\CrmLostReasonModel;
use App\Models\CRM\CrmOpportunityModel;
use App\Models\CRM\CrmPipelineModel;
use App\Models\CRM\CrmPipelineStageModel;
use App\Models\Users\UserActivityLogsModel;
use App\Services\CrmAutomationService;
use App\Services\CrmRoundRobinService;
use CodeIgniter\HTTP\ResponseInterface;

class OpportunityController extends BaseCrmController
{
    public function index(): string
    {
        $this->setViewSuccess('Oportunidades');
        $this->setPageTittleAhead('Oportunidades', 'Embudos de venta');
        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'        => base_url(),
            'CRM'           => route_to('crm.clients'),
            'Oportunidades' => '',
        ]);
        $onlyMine = $this->onlyMineId();
        $this->viewData['response'] = [
            'pipelines' => (new CrmPipelineModel())->getActive(),
            'stages'    => (new CrmPipelineStageModel())->orderBy('sort_order', 'ASC')->findAll(),
            'reasons'   => (new CrmLostReasonModel())->getActive(),
            'clients'   => $this->clientModel->getForSelect(),
            'forecast'  => (new CrmOpportunityModel())->forecast($onlyMine),
            'only_mine' => $onlyMine !== null,
        ];

        return $this->renderLayout('Layouts/user_loggedin_layout', 'CRM/opportunities_index');
    }

    public function list_ajax(): ResponseInterface
    {
        $this->assertAjax();
        $model = new CrmOpportunityModel();
        $post  = $this->request->getPost();
        if ($uid = $this->onlyMineId()) {
            $post['filter_assigned'] = $uid;
        }
        $list = $model->get_datatables($post);
        $data = [];
        foreach ($list as $row) {
            $row = (array) $row;
            $id  = (int) $row['id'];
            $data[] = [
                esc($row['folio'] ?? ''),
                esc($row['title'] ?? ''),
                esc($row['company_name'] ?? ''),
                esc($row['pipeline_name'] ?? ''),
                esc($row['stage_name'] ?? ''),
                '$' . number_format((float) ($row['expected_value'] ?? 0), 2),
                (int) ($row['probability'] ?? 0) . '%',
                esc($row['close_date'] ?? '—'),
                '<button class="btn btn-sm btn-outline-primary btn-move-opp" data-id="' . $id . '">Etapa</button>',
            ];
        }

        return $this->response->setJSON([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $model->count_all(),
            'recordsFiltered' => $model->count_filtered($post),
            'data'            => $data,
            'csrf'            => csrf_hash(),
        ]);
    }

    public function board_ajax(): ResponseInterface
    {
        if (! $this->request->isAJAX()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }
        $pipelineId = (int) $this->request->getGet('pipeline_id');
        if ($pipelineId < 1) {
            $pipelineId = (int) ((new CrmPipelineModel())->defaultId() ?? 0);
        }
        $stages = (new CrmPipelineStageModel())->forPipeline($pipelineId);
        $board  = (new CrmOpportunityModel())->board($pipelineId, $this->onlyMineId());
        $this->setOutputSuccess('Tablero', [
            'pipeline_id' => $pipelineId,
            'stages'      => $stages,
            'columns'     => $board,
            'forecast'    => (new CrmOpportunityModel())->forecast($this->onlyMineId()),
        ]);
        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    public function store(): ResponseInterface
    {
        $this->assertAjax();
        $model      = new CrmOpportunityModel();
        $pipelineId = (int) $this->request->getPost('pipeline_id');
        if ($pipelineId < 1) {
            $pipelineId = (int) ((new CrmPipelineModel())->defaultId() ?? 0);
        }
        $stage = (new CrmPipelineStageModel())->firstOpenStage($pipelineId);
        if (! $stage) {
            $this->setOutputError('El embudo no tiene etapas.');

            return $this->json();
        }
        $assigned = (int) $this->request->getPost('assigned_user_id');
        if ($assigned < 1) {
            $assigned = (int) ((new CrmRoundRobinService())->nextUserId() ?? auth()->id());
        }
        $id = $model->insert([
            'folio'            => $model->nextFolio(),
            'title'            => trim((string) $this->request->getPost('title')),
            'client_id'        => (int) $this->request->getPost('client_id'),
            'pipeline_id'      => $pipelineId,
            'stage_id'         => (int) $stage->id,
            'assigned_user_id' => $assigned,
            'expected_value'   => (float) $this->request->getPost('expected_value'),
            'probability'      => (int) $stage->win_probability,
            'close_date'       => $this->request->getPost('close_date') ?: null,
            'notes'            => $this->request->getPost('notes'),
            'created_by'       => auth()->id(),
        ]);
        if (! $id) {
            $this->setOutputError('No se pudo crear la oportunidad.', $model->errors());

            return $this->json();
        }
        (new CrmFollowupModel())->createVersion([
            'client_id'       => (int) $this->request->getPost('client_id'),
            'followup_type'   => 'comment',
            'title'           => 'Oportunidad creada',
            'comment'         => $this->request->getPost('title'),
            'created_by'      => auth()->id(),
            'change_source'   => 'manual',
            'followup_status' => 'open',
        ]);
        (new UserActivityLogsModel())->logActivity('crm_opportunity_create', 'Oportunidad ID ' . $id);
        $this->setOutputSuccess('Oportunidad registrada.', ['id' => $id]);

        return $this->json();
    }

    public function move(int $id): ResponseInterface
    {
        $this->assertAjax();
        $model = new CrmOpportunityModel();
        $opp   = $model->find($id);
        if (! $opp) {
            $this->setOutputError('Oportunidad no encontrada.', null, ResponseInterface::HTTP_NOT_FOUND);

            return $this->json();
        }
        if (! $this->canSeeOpportunity($opp)) {
            $this->setOutputError('Sin permiso sobre esta oportunidad.', null, ResponseInterface::HTTP_FORBIDDEN);

            return $this->json();
        }
        $stageId = (int) $this->request->getPost('stage_id');
        $stage   = (new CrmPipelineStageModel())->find($stageId);
        if (! $stage || (int) $stage->pipeline_id !== (int) $opp->pipeline_id) {
            $this->setOutputError('Etapa no válida para este embudo.');

            return $this->json();
        }
        if ((int) $stage->is_lost === 1) {
            $reason = (int) $this->request->getPost('lost_reason_id');
            if ($reason < 1) {
                $this->setOutputError('Debe indicar el motivo de pérdida.');

                return $this->json();
            }
        }

        $data = [
            'stage_id'    => $stageId,
            'probability' => (int) $stage->win_probability,
        ];
        if ((int) $stage->is_lost === 1) {
            $data['lost_reason_id'] = (int) $this->request->getPost('lost_reason_id');
            $data['lost_notes']     = $this->request->getPost('lost_notes');
        }
        if ((int) $stage->is_won === 1) {
            $data['close_date'] = date('Y-m-d');
        }
        $model->update($id, $data);
        $fresh = $model->find($id);
        (new CrmFollowupModel())->createVersion([
            'client_id'       => (int) $opp->client_id,
            'followup_type'   => 'status_change',
            'title'           => 'Embudo: ' . $stage->name,
            'comment'         => $opp->folio . ' → ' . $stage->name,
            'created_by'      => auth()->id(),
            'change_source'   => 'manual',
            'followup_status' => 'closed',
        ]);
        (new UserActivityLogsModel())->logActivity('crm_opportunity_move', $opp->folio . ' → ' . $stage->name);

        if ((int) $stage->is_won === 1) {
            (new CrmAutomationService())->run('opportunity.won', $fresh);
        }

        $this->setOutputSuccess('Etapa actualizada.');

        return $this->json();
    }

    private function assertAjax(): void
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

    private function onlyMineId(): ?int
    {
        $user = auth()->user();
        if ($user && $user->can('crm.view-all')) {
            return null;
        }

        return (int) auth()->id();
    }

    private function canSeeOpportunity(object $opp): bool
    {
        if (auth()->user()->can('crm.view-all')) {
            return true;
        }

        return (int) $opp->assigned_user_id === (int) auth()->id()
            || (int) $opp->created_by === (int) auth()->id();
    }
}
