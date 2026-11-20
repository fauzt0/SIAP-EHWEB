<?php

declare(strict_types=1);

namespace App\Controllers\Sales;

use App\Controllers\BaseController;
use App\Libraries\Breadcrumb;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class BaseSalesController extends BaseController
{
    protected $helpers = ['form', 'url'];
    protected Breadcrumb $breadcrumb;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->breadcrumb = new Breadcrumb([
            'Inicio' => base_url(),
            'Ventas' => route_to('sales.orders'),
        ]);
        $this->setPageTittleAhead('Ventas', 'Cotizaciones y órdenes');
    }
}
