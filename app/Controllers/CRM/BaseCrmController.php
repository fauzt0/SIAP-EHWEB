<?php

declare(strict_types=1);

namespace App\Controllers\CRM;

use App\Controllers\BaseController;
use App\Libraries\Breadcrumb;
use App\Models\CRM\CrmClientModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class BaseCrmController extends BaseController
{
    protected $helpers = ['form', 'url'];
    protected Breadcrumb $breadcrumb;
    protected CrmClientModel $clientModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->clientModel = new CrmClientModel();
        $this->breadcrumb  = new Breadcrumb([
            'Inicio' => base_url(),
            'CRM'    => route_to('crm.clients'),
        ]);
        $this->setPageTittleAhead('CRM', 'Clientes y seguimiento');
    }
}
