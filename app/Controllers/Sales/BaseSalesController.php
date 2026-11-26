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

    protected function pdfButtons(string $kind, int $id): string
    {
        if (! auth()->user()->can('sales.documents')) {
            return '';
        }
        $pdf  = site_url('nat/sales/documents/' . $kind . '/' . $id);
        $html = site_url('nat/sales/documents/' . $kind . '/' . $id . '/html');

        return '<a class="btn btn-outline-secondary btn-sm" href="' . $html . '" target="_blank" title="Vista previa"><i class="fas fa-eye"></i></a>'
            . '<a class="btn btn-outline-danger btn-sm" href="' . $pdf . '" target="_blank" title="PDF"><i class="fas fa-file-pdf"></i></a>'
            . '<a class="btn btn-outline-dark btn-sm" href="' . $pdf . '?action=download" title="Descargar"><i class="fas fa-download"></i></a>';
    }
}
