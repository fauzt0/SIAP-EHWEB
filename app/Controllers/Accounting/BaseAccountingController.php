<?php

namespace App\Controllers\Accounting;

use App\Controllers\BaseController;
use App\Libraries\Breadcrumb;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class BaseAccountingController extends BaseController
{
    protected $helpers = ['form', 'url'];
    protected Breadcrumb $breadcrumb;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);

        $this->breadcrumb = new Breadcrumb([
            'Inicio'       => base_url(),
            'Contabilidad' => route_to('accounting.dashboard'),
        ]);
        $this->setPageTittleAhead('Contabilidad', 'Tesorería y mayor');
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
