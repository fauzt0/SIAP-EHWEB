<?php

namespace App\Controllers\System;

use App\Controllers\BaseController;
use App\Libraries\AlertService;
use App\Libraries\Breadcrumb;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Controlador del sistema de alertas y notificaciones.
 *
 * Combina dos fuentes de alertas:
 *   1. Alertas almacenadas (manuales vía AlertService::dispatch) — con estado leído/no leído.
 *   2. Alertas automáticas (CHISA-style) — métodos _detect*() que consultan tablas operativas
 *      en tiempo real; la alerta existe mientras la condición persista en BD.
 *
 * Todos los métodos que retornan JSON son exclusivos para peticiones AJAX.
 * Las rutas están definidas en Routes.php dentro del grupo protegido por session.
 */
class AlertController extends BaseController
{
    protected $helpers = ['form', 'url'];

    protected Breadcrumb $breadcrumb;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->breadcrumb = new Breadcrumb([
            'Inicio' => route_to('dashboard.index'),
        ]);

        $this->setViewNoContent('Sin alertas disponibles');
        $this->setPageTittleAhead('Notificaciones', 'Centro de Notificaciones');
    }

    // ────────────────────────────────────────────────────────────────────────
    //  ENDPOINTS AJAX
    // ────────────────────────────────────────────────────────────────────────

    /**
     * GET via AJAX
     * Devuelve las alertas del usuario combinando:
     *   - Alertas almacenadas no leídas (manuales / dispatch)
     *   - Alertas automáticas detectadas en tablas operativas (métodos _detect*)
     *
     * Endpoint: /nat/alerts/get-unread
     * Uso: Llamado desde el topbar al cargar la página o mediante polling (c/60s).
     */
    public function get_unread_ajax(): ResponseInterface
    {
        if (!$this->request->isAJAX()) {
            throw PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $alertService = new AlertService();
        $userId       = auth()->id();
        $limit        = (int) ($this->request->getGet('limit') ?? 10);

        // 1. Alertas almacenadas (manuales) no leídas
        $storedAlerts = $alertService->getUnreadByUser($userId, $limit);
        $storedCount  = $alertService->getUnreadCount($userId);

        // 2. Alertas automáticas detectadas en tiempo real (CHISA-style)
        $autoAlerts = $this->_detectAll();
        $autoCount  = count($autoAlerts);

        // 3. Fusionar: manuales primero, automáticas después. Limitar a $items para el dropdown.
        $merged  = array_merge($storedAlerts, $autoAlerts);
        $total   = $storedCount + $autoCount;
        $display = array_slice($merged, 0, $limit);

        $this->setOutputSuccess('Alertas obtenidas correctamente', [
            'alerts'     => $display,
            'count'      => $total,
            'auto_count' => $autoCount,
        ]);

        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    /**
     * POST via AJAX
     * Marca una alerta específica como leída para el usuario actual.
     *
     * Endpoint: /nat/alerts/mark-read
     * Body: alert_id (int)
     */
    public function mark_read_ajax(): ResponseInterface
    {
        if (!$this->request->isAJAX()) {
            throw PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $alertId = (int) $this->request->getPost('alert_id');

        if ($alertId <= 0) {
            $this->setOutputError('ID de alerta inválido.');
            return $this->response->setJSON($this->outputData);
        }

        $alertService = new AlertService();
        $result       = $alertService->markAsRead($alertId, auth()->id());

        if ($result) {
            $this->setOutputSuccess('Alerta marcada como leída.');
        } else {
            $this->setOutputError('No se pudo marcar la alerta como leída. Verifique que exista y pertenezca al usuario.');
        }

        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    /**
     * POST via AJAX
     * Marca todas las alertas no leídas del usuario como leídas.
     *
     * Endpoint: /nat/alerts/mark-all-read
     */
    public function mark_all_read_ajax(): ResponseInterface
    {
        if (!$this->request->isAJAX()) {
            throw PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $alertService = new AlertService();
        $result       = $alertService->markAllAsRead(auth()->id());

        if ($result) {
            $this->setOutputSuccess('Todas las alertas se marcaron como leídas.');
        } else {
            $this->setOutputNoContent('No había alertas pendientes por marcar.');
        }

        $this->outputData['csrf'] = csrf_hash();

        return $this->response->setJSON($this->outputData);
    }

    /**
     * POST via AJAX (DataTable)
     * Devuelve JSON paginado con alertas almacenadas + alertas automáticas en vivo.
     *
     * Endpoint: /nat/alerts/history-ajax
     */
    public function history_ajax(): ResponseInterface
    {
        if (!$this->request->isAJAX()) {
            throw PageNotFoundException::forPageNotFound('Endpoint exclusivo para peticiones AJAX.');
        }

        $alertService = new AlertService();
        $userId       = auth()->id();

        $start   = (int) ($this->request->getPost('start') ?? 0);
        $length  = (int) ($this->request->getPost('length') ?? 10);
        $perPage = ($length > 0) ? $length : 10;

        $autoAlerts = $this->_detectAll();
        $autoCount  = count($autoAlerts);
        $autoRows   = ($start === 0) ? array_map(fn ($alert) => $this->_formatHistoryRow($alert, true), $autoAlerts) : [];

        $storedOffset = ($start === 0) ? 0 : max(0, $start - $autoCount);
        $storedLimit  = ($start === 0) ? max(0, $perPage - $autoCount) : $perPage;

        $storedResult = $alertService->getAllByUserSlice($userId, $storedOffset, $storedLimit);
        $storedRows   = array_map(fn ($alert) => $this->_formatHistoryRow($alert, false), $storedResult['data']);

        $data        = array_merge($autoRows, $storedRows);
        $recordsTotal = $storedResult['total'] + $autoCount;

        return $this->response->setJSON([
            'draw'            => (int) ($this->request->getPost('draw') ?? 1),
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data'            => $data,
            'csrf'            => csrf_hash(),
        ]);
    }

    /**
     * Normaliza una alerta (almacenada o automática) para el DataTable del historial.
     */
    private function _formatHistoryRow(object $alert, bool $isAuto): array
    {
        return [
            'id'         => (int) ($alert->id ?? 0),
            'pivot_id'   => $isAuto ? 0 : (int) ($alert->pivot_id ?? 0),
            'title'      => $alert->title ?? '',
            'message'    => $alert->message ?? '',
            'type'       => $alert->type ?? 'info',
            'icon'       => $alert->icon ?? 'fa-bell',
            'target_url' => $alert->target_url ?? '',
            'module'     => $alert->module ?? '',
            'is_auto'    => $isAuto ? 1 : 0,
            'is_read'    => $isAuto ? 0 : (int) ($alert->is_read ?? 0),
            'created_at' => $alert->created_at ?? date('Y-m-d H:i:s'),
        ];
    }

    // ────────────────────────────────────────────────────────────────────────
    //  VISTAS HTML
    // ────────────────────────────────────────────────────────────────────────

    /**
     * GET — Vista HTML
     * Muestra la página completa con el historial de todas las alertas del usuario.
     *
     * Endpoint: /nat/alerts/history
     */
    public function history(): string
    {
        $this->setViewSuccess('Historial de notificaciones cargado correctamente.');
        $this->setPageTittleAhead('Notificaciones', 'Historial de Notificaciones');

        $this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
            'Inicio'         => route_to('dashboard.index'),
            'Notificaciones' => '',
        ]);

        $this->viewData['headTitle'] = 'Historial de Notificaciones';

        return $this->renderLayout('Layouts/user_loggedin_layout', 'System/alerts_history');
    }

    // ────────────────────────────────────────────────────────────────────────
    //  DETECTORES AUTOMÁTICOS (CHISA-style)
    //  Se ejecutan en tiempo real desde get_unread_ajax().
    //  No almacenan nada en BD; la alerta existe solo mientras la condición persista.
    // ────────────────────────────────────────────────────────────────────────

    /**
     * Ejecuta todos los detectores según los permisos del usuario autenticado.
     *
     * @return array Lista de alertas detectadas como objetos stdClass
     */
    private function _detectAll(): array
    {
        $user   = auth()->user();
        $alerts = [];

        if (!$user) {
            return $alerts;
        }

        // ── RH ────────────────────────────────────────────────────────────────
        if ($user->can('hr.access')) {
            $alerts = array_merge($alerts, $this->_safeDetect('_detectWorkersMissingNss'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectWorkersMissingRfc'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectWorkersMissingCurp'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectWorkersMissingBankData'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectPendingVacationRequests'));
        }

        // ── Proveedores ───────────────────────────────────────────────────────
        if ($user->can('purchasing.suppliers') || $user->can('catalog.manage-suppliers')) {
            $alerts = array_merge($alerts, $this->_safeDetect('_detectSuppliersInactive'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectSuppliersMissingTaxId'));
        }

        // ── Catálogo / Inventario ─────────────────────────────────────────────
        if ($user->can('catalog.access')) {
            $alerts = array_merge($alerts, $this->_safeDetect('_detectLowStockProducts'));
        }

        return $alerts;
    }

    /**
     * Ejecuta un detector capturando errores para no interrumpir el resto.
     */
    private function _safeDetect(string $method): array
    {
        try {
            return $this->{$method}();
        } catch (\Throwable $e) {
            log_message('error', 'AlertController::{method} — {message}', [
                'method'  => $method,
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    //  DETECTOR: RH — Trabajadores sin NSS
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Detecta trabajadores activos sin NSS registrado.
     */
    private function _detectWorkersMissingNss(): array
    {
        $db   = db_connect();
        $rows = $db->table('hr_profiles')
            ->select('id, first_name, last_name')
            ->where('deleted_at', null)
            ->where('(nss IS NULL OR nss = "")', null, false)
            ->limit(10)
            ->get()
            ->getResult();

        $alerts = [];
        foreach ($rows as $row) {
            $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $alerts[] = $this->_buildAutoAlert([
                'type'       => 'warning',
                'icon'       => 'fa-id-card',
                'title'      => 'Trabajador sin NSS',
                'message'    => $name . ' — Falta registrar número de seguro social',
                'module'     => 'RH',
                'target_url' => route_to('hr.worker.edit', $row->id),
            ]);
        }
        return $alerts;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  DETECTOR: RH — Trabajadores sin RFC
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Detecta trabajadores activos sin RFC registrado.
     */
    private function _detectWorkersMissingRfc(): array
    {
        $db   = db_connect();
        $rows = $db->table('hr_profiles')
            ->select('id, first_name, last_name')
            ->where('deleted_at', null)
            ->where('(rfc IS NULL OR rfc = "")', null, false)
            ->limit(10)
            ->get()
            ->getResult();

        $alerts = [];
        foreach ($rows as $row) {
            $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $alerts[] = $this->_buildAutoAlert([
                'type'       => 'warning',
                'icon'       => 'fa-file-invoice',
                'title'      => 'Trabajador sin RFC',
                'message'    => $name . ' — Falta registrar RFC',
                'module'     => 'RH',
                'target_url' => route_to('hr.worker.edit', $row->id),
            ]);
        }
        return $alerts;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  DETECTOR: RH — Trabajadores sin CURP
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Detecta trabajadores activos sin CURP registrado.
     */
    private function _detectWorkersMissingCurp(): array
    {
        $db   = db_connect();
        $rows = $db->table('hr_profiles')
            ->select('id, first_name, last_name')
            ->where('deleted_at', null)
            ->where('(curp IS NULL OR curp = "")', null, false)
            ->limit(10)
            ->get()
            ->getResult();

        $alerts = [];
        foreach ($rows as $row) {
            $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $alerts[] = $this->_buildAutoAlert([
                'type'       => 'warning',
                'icon'       => 'fa-address-card',
                'title'      => 'Trabajador sin CURP',
                'message'    => $name . ' — Falta registrar CURP',
                'module'     => 'RH',
                'target_url' => route_to('hr.worker.edit', $row->id),
            ]);
        }
        return $alerts;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  DETECTOR: RH — Trabajadores sin datos bancarios
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Detecta trabajadores activos sin CLABE ni cuenta bancaria.
     */
    private function _detectWorkersMissingBankData(): array
    {
        $db   = db_connect();
        $rows = $db->table('hr_profiles')
            ->select('id, first_name, last_name')
            ->where('deleted_at', null)
            ->groupStart()
                ->where('bank_clabe', null)
                ->orWhere('bank_clabe', '')
                ->orWhere('bank_account', null)
                ->orWhere('bank_account', '')
            ->groupEnd()
            ->limit(5)
            ->get()
            ->getResult();

        $alerts = [];
        foreach ($rows as $row) {
            $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $alerts[] = $this->_buildAutoAlert([
                'type'       => 'info',
                'icon'       => 'fa-university',
                'title'      => 'Trabajador sin datos bancarios',
                'message'    => $name . ' — Falta registrar CLABE o cuenta bancaria',
                'module'     => 'RH',
                'target_url' => route_to('hr.worker.edit', $row->id),
            ]);
        }
        return $alerts;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  DETECTOR: RH — Solicitudes de vacaciones pendientes
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Detecta solicitudes de vacaciones pendientes de aprobación.
     */
    private function _detectPendingVacationRequests(): array
    {
        $db    = db_connect();
        $count = $db->table('hr_vacation_requests')
            ->where('status', 'pendiente')
            ->where('deleted_at', null)
            ->countAllResults();

        if ($count === 0) {
            return [];
        }

        $s = $count === 1 ? '' : 's';
        return [$this->_buildAutoAlert([
            'type'       => 'info',
            'icon'       => 'fa-plane',
            'title'      => 'Solicitudes de vacaciones pendientes',
            'message'    => $count . ' solicitude' . $s . ' pendiente' . $s . ' de aprobación',
            'module'     => 'RH',
            'target_url' => route_to('hr.worker.vacations.global'),
        ])];
    }

    // ──────────────────────────────────────────────────────────────────────
    //  DETECTOR: Proveedores — Inactivos
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Detecta proveedores inactivos (status = 0).
     */
    private function _detectSuppliersInactive(): array
    {
        $db    = db_connect();
        $count = $db->table('fin_suppliers')
            ->where('status', 0)
            ->where('deleted_at', null)
            ->countAllResults();

        if ($count === 0) {
            return [];
        }

        $s = $count === 1 ? '' : 'es';
        return [$this->_buildAutoAlert([
            'type'       => 'warning',
            'icon'       => 'fa-building',
            'title'      => 'Proveedores inactivos',
            'message'    => $count . ' proveedor' . $s . ' inactivo' . $s . ' en el sistema',
            'module'     => 'Proveedores',
            'target_url' => route_to('suppliers.index'),
        ])];
    }

    // ──────────────────────────────────────────────────────────────────────
    //  DETECTOR: Proveedores — Sin RFC
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Detecta proveedores sin RFC (tax_id) registrado.
     */
    private function _detectSuppliersMissingTaxId(): array
    {
        $db    = db_connect();
        $count = $db->table('fin_suppliers')
            ->where('(tax_id IS NULL OR tax_id = "")', null, false)
            ->where('deleted_at', null)
            ->countAllResults();

        if ($count === 0) {
            return [];
        }

        $s = $count === 1 ? '' : 'es';
        return [$this->_buildAutoAlert([
            'type'       => 'warning',
            'icon'       => 'fa-file-invoice',
            'title'      => 'Proveedores sin RFC',
            'message'    => $count . ' proveedor' . $s . ' sin RFC registrado',
            'module'     => 'Proveedores',
            'target_url' => route_to('suppliers.index'),
        ])];
    }

    // ──────────────────────────────────────────────────────────────────────
    //  DETECTOR: Catálogo — Productos con stock bajo
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Detecta productos con stock por debajo del mínimo de alerta.
     * Agrupa por producto (puede tener stock bajo en múltiples sucursales).
     */
    private function _detectLowStockProducts(): array
    {
        $db   = db_connect();
        $rows = $db->table('catalog_product_stock cps')
            ->select('cps.product_id, cp.commercial_name, cps.stock, cps.min_alert, cps.org_branches_id')
            ->join('catalog_products cp', 'cp.id = cps.product_id')
            ->where('cps.stock <= cps.min_alert', null, false)
            ->where('cps.stock >', 0)
            ->where('cps.deleted_at', null)
            ->where('cp.deleted_at', null)
            ->limit(10)
            ->get()
            ->getResult();

        if (empty($rows)) {
            return [];
        }

        // Agrupar por producto (varias sucursales)
        $grouped = [];
        foreach ($rows as $row) {
            $pid = $row->product_id;
            if (!isset($grouped[$pid])) {
                $grouped[$pid] = [
                    'name'   => $row->commercial_name,
                    'total'  => 0,
                    'detail' => [],
                ];
            }
            $grouped[$pid]['total'] += (int) $row->stock;
            $grouped[$pid]['detail'][] = 'Suc. ' . $row->org_branches_id . ': ' . $row->stock . ' uds.';
        }

        $alerts = [];
        foreach ($grouped as $pid => $info) {
            $detail = implode(' | ', $info['detail']);
            $alerts[] = $this->_buildAutoAlert([
                'type'       => 'danger',
                'icon'       => 'fa-boxes',
                'title'      => 'Stock bajo: ' . $info['name'],
                'message'    => 'Total: ' . $info['total'] . ' uds. — ' . $detail,
                'module'     => 'Inventario',
                'target_url' => route_to('catalog.products.edit', $pid),
            ]);
        }

        return $alerts;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  CONSTRUCTOR DE ALERTA AUTOMÁTICA
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Construye un objeto stdClass con la estructura estándar de alerta automática.
     * is_auto=1 indica que no está almacenada en sys_alerts y no se puede "marcar como leída".
     *
     * @param array $data Datos: type, icon, title, message, module, target_url
     * @return \stdClass
     */
    private function _buildAutoAlert(array $data): \stdClass
    {
        return (object) [
            'id'         => 0,
            'title'      => $data['title'] ?? 'Sin título',
            'message'    => $data['message'] ?? '',
            'type'       => $data['type'] ?? 'info',
            'icon'       => $data['icon'] ?? 'fa-bell',
            'target_url' => $data['target_url'] ?? '#',
            'module'     => $data['module'] ?? 'Sistema',
            'is_auto'    => 1,
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }
}
