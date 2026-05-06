<?php

namespace App\Controllers\Catalog;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Libraries\Breadcrumb;

// Modelos principales de Catálogo
use App\Models\Catalog\CatalogCategoryModel;
use App\Models\Catalog\CatalogProductModel;
use App\Models\Catalog\CatalogPlanModel;

/**
 * BaseCatalogController
 * 
 * Controlador base para el módulo de Catálogo de Productos y Servicios.
 */
class BaseCatalogController extends BaseController
{
    protected $helpers = ['form', 'url'];
    protected $breadcrumb;

    protected $categoryModel;
    protected $productModel;
    protected $planModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // Instanciar modelos principales
        $this->categoryModel = new CatalogCategoryModel();
        $this->productModel  = new CatalogProductModel();
        $this->planModel     = new CatalogPlanModel();

        // Configuración inicial del Breadcrumb
        $this->breadcrumb = new Breadcrumb([
            'Inicio'   => base_url(),
            'Catálogo' => route_to('catalog.products'),
        ]);

        // Título por defecto
        $this->setPageTittleAhead('Catálogo', 'Gestión de Productos y Servicios');
    }
}
