<?php

declare(strict_types=1);

namespace App\Controllers\PluginSystem;

use App\Controllers\BaseController;
use App\Models\Users\UserActivityLogsModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Base de los controladores de plugin y del Market.
 * Contrato estable para los plugins 001–006.
 */
abstract class PluginBaseController extends BaseController
{
    protected $helpers = ['form', 'url'];

    protected UserActivityLogsModel $activityLogModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->activityLogModel = new UserActivityLogsModel();
    }

    /**
     * §1 — oculta el endpoint si no es un POST AJAX.
     */
    protected function assertAjaxPost(): void
    {
        if (! $this->request->isAJAX() || ! $this->request->is('post')) {
            throw PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }
    }

    /**
     * §2/§15 — JSON con el hash CSRF renovado y el status del outputData.
     */
    protected function jsonWithCsrf(array $payload = []): ResponseInterface
    {
        if (! isset($payload['csrf'])) {
            $payload['csrf'] = csrf_hash();
        }

        $status = (int) ($payload['statusCode'] ?? 200);
        if ($status < 100 || $status > 599) {
            $status = 200;
        }

        return $this->response->setStatusCode($status)->setJSON($payload);
    }

    /**
     * Defensa en profundidad. La ruta ya lleva el filtro permission: de Shield.
     */
    protected function assertPermission(string $permission): void
    {
        $user = auth()->user();
        if ($user === null || ! $user->can($permission)) {
            throw PageNotFoundException::forPageNotFound('No autorizado.');
        }
    }
}
