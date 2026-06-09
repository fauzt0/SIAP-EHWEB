<?php

namespace App\Controllers\Organization;

use App\Controllers\BaseController;
use App\Libraries\Breadcrumb;
use App\Models\Organization\OrgBranchModel;
use App\Models\Organization\OrgCompanyModel;
use App\Models\Users\UserActivityLogsModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Controlador base del módulo Organización.
 * Instancia modelos compartidos y breadcrumb raíz (§13).
 */
class BaseOrganizationController extends BaseController
{
    protected $helpers = ['form', 'url'];

    protected Breadcrumb $breadcrumb;
    protected OrgCompanyModel $companyModel;
    protected OrgBranchModel $branchModel;
    protected UserActivityLogsModel $activityLogModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);

        $this->companyModel     = new OrgCompanyModel();
        $this->branchModel      = new OrgBranchModel();
        $this->activityLogModel = new UserActivityLogsModel();

        $this->breadcrumb = new Breadcrumb([
            'Inicio'        => base_url(),
            'Organización'  => route_to('organization.profile'),
        ]);
    }

    protected function assertAjaxPost(): void
    {
        if (! $this->request->isAJAX() || ! $this->request->is('post')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound(
                'Endpoint exclusivo para peticiones AJAX.'
            );
        }
    }

    protected function jsonWithCsrf(array $payload = []): ResponseInterface
    {
        if (! isset($payload['csrf'])) {
            $payload['csrf'] = csrf_hash();
        }

        return $this->response->setJSON($payload);
    }
}
