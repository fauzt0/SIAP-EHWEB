<?php

namespace App\Controllers\Financial;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\Breadcrumb;

use App\Models\Financial\FinSupplierModel;
use App\Models\Financial\FinSupplierCategoryModel;
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
    protected FinSupplierCategoryModel $categoryModel;
    protected FinPurchaseOrderModel $purchaseOrderModel;

    public function initController(
        RequestInterface  $request,
        ResponseInterface $response,
        LoggerInterface   $logger
    ): void {
        parent::initController($request, $response, $logger);

        $this->supplierModel      = new FinSupplierModel();
        $this->categoryModel      = new FinSupplierCategoryModel();
        $this->purchaseOrderModel = new FinPurchaseOrderModel();

        $this->breadcrumb = new Breadcrumb([
            'Inicio'      => base_url(),
            'Proveedores' => route_to('suppliers.index'),
        ]);

        $this->setPageTittleAhead('Proveedores', 'Gestión de Proveedores');
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
