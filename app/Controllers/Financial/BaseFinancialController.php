<?php

namespace App\Controllers\Financial;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\Breadcrumb;

use App\Models\Financial\FinSupplierModel;
use App\Models\Financial\FinPurchaseOrderModel;

/**
 * BaseFinancialController
 *
 * Base controller for the Financial / Suppliers module.
 * Instantiates shared models and sets the common breadcrumb root.
 */
class BaseFinancialController extends BaseController
{
    protected $helpers = ['form', 'url'];

    protected Breadcrumb $breadcrumb;
    protected FinSupplierModel $supplierModel;
    protected FinPurchaseOrderModel $purchaseOrderModel;

    public function initController(
        RequestInterface  $request,
        ResponseInterface $response,
        LoggerInterface   $logger
    ): void {
        parent::initController($request, $response, $logger);

        $this->supplierModel      = new FinSupplierModel();
        $this->purchaseOrderModel = new FinPurchaseOrderModel();

        $this->breadcrumb = new Breadcrumb([
            'Inicio'      => base_url(),
            'Proveedores' => route_to('suppliers.index'),
        ]);

        $this->setPageTittleAhead('Proveedores', 'Gestión de Proveedores');
    }
}
