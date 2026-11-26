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

        // ── RH (expediente: quien puede ver/editar trabajadores) ───────────────
        if ($user->can('hr.view') || $user->can('hr.edit') || $user->can('hr.employees')) {
            $alerts = array_merge($alerts, $this->_safeDetect('_detectWorkersMissingNss'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectWorkersMissingRfc'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectWorkersMissingCurp'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectWorkersMissingBankData'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectWorkersMissingLaborData'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectWorkersMissingDocuments'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectWorkersWithoutActiveContract'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectWorkersWithoutSchedule'));
        }

        if ($user->can('hr.edit')) {
            $alerts = array_merge($alerts, $this->_safeDetect('_detectPendingVacationRequests'));
        }

        if ($user->can('hr.manage-payroll')) {
            $alerts = array_merge($alerts, $this->_safeDetect('_detectPendingPayrolls'));
        }

        // ── Proveedores / compras (no mezclar con solo lectura de catálogo) ───
        if ($user->can('catalog.manage-suppliers') || $user->can('purchasing.suppliers')) {
            $alerts = array_merge($alerts, $this->_safeDetect('_detectSuppliersInactive'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectSuppliersMissingTaxId'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectOverdueRecurringExpenses'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectDueSoonRecurringExpenses'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectPendingPurchaseOrders'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectOverduePurchaseOrders'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectPoMissingDocuments'));
        }

        // ── Catálogo / Inventario ─────────────────────────────────────────────
        if ($user->can('catalog.manage') || $user->can('catalog.access')) {
            $alerts = array_merge($alerts, $this->_safeDetect('_detectLowStockProducts'));
        }

        if ($user->can('crm.access')) {
            $alerts = array_merge($alerts, $this->_safeDetect('_detectStaleLeads'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectOpenFollowups'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectDueCrmFollowups'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectStaleOpportunities'));
        }

        if ($user->can('projects.access')) {
            $alerts = array_merge($alerts, $this->_safeDetect('_detectOverdueProjects'));
        }

        if ($user->can('sales.access')) {
            $alerts = array_merge($alerts, $this->_safeDetect('_detectExpiringQuotes'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectDueCustomerServices'));
        }

        if ($user->can('sales.collections') || $user->can('sales.billing')) {
            $alerts = array_merge($alerts, $this->_safeDetect('_detectUnpaidOrders'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectUnpaidReceipts'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectOverdueReceipts'));
        }

        if ($user->can('sales.activate')) {
            $alerts = array_merge($alerts, $this->_safeDetect('_detectPendingActivations'));
            $alerts = array_merge($alerts, $this->_safeDetect('_detectUnprocessedItems'));
        }

        try {
            $alerts = array_merge($alerts, \App\Libraries\PluginSystem\PluginManager::runAlertDetectors($user));
        } catch (\Throwable $e) {
            log_message('error', 'AlertController plugin detectors — {message}', [
                'message' => $e->getMessage(),
            ]);
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

    /**
     * Trabajadores activos sin fila en hr_employment_data (datos laborales).
     */
    private function _detectWorkersMissingLaborData(): array
    {
        $db   = db_connect();
        $rows = $db->query("
            SELECT hp.id, hp.first_name, hp.last_name
            FROM hr_profiles hp
            LEFT JOIN hr_employment_data hed ON hed.profile_id = hp.id
            WHERE hp.deleted_at IS NULL AND hed.profile_id IS NULL
            LIMIT 10
        ")->getResult();

        $alerts = [];
        foreach ($rows as $row) {
            $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $alerts[] = $this->_buildAutoAlert([
                'type'       => 'warning',
                'icon'       => 'fa-briefcase',
                'title'      => 'Sin datos laborales',
                'message'    => $name . ' — Falta registrar puesto, salario y alta laboral',
                'module'     => 'RH',
                'target_url' => route_to('hr.worker.edit', $row->id),
            ]);
        }

        return $alerts;
    }

    /**
     * Expediente: tipos de documento del catálogo sin archivo cargado (trabajadores activos).
     */
    private function _detectWorkersMissingDocuments(): array
    {
        $db   = db_connect();
        $rows = $db->query("
            SELECT hp.id, hp.first_name, hp.last_name, COUNT(dt.id) AS missing_types
            FROM hr_profiles hp
            INNER JOIN hr_employment_data hed ON hed.profile_id = hp.id AND hed.status = 'active'
            CROSS JOIN hr_cat_document_types dt
            LEFT JOIN hr_documents hd ON hd.profile_id = hp.id
                AND hd.document_type_id = dt.id
                AND hd.deleted_at IS NULL
            WHERE hp.deleted_at IS NULL
              AND dt.deleted_at IS NULL
              AND hd.id IS NULL
            GROUP BY hp.id, hp.first_name, hp.last_name
            HAVING missing_types > 0
            ORDER BY missing_types DESC
            LIMIT 10
        ")->getResult();

        $alerts = [];
        foreach ($rows as $row) {
            $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $alerts[] = $this->_buildAutoAlert([
                'type'       => 'danger',
                'icon'       => 'fa-folder-open',
                'title'      => 'Expediente incompleto',
                'message'    => $name . ' — Faltan ' . (int) $row->missing_types . ' tipo(s) de documento',
                'module'     => 'RH',
                'target_url' => route_to('hr.worker.edit', $row->id),
            ]);
        }

        return $alerts;
    }

    /**
     * Trabajadores activos sin contrato digital con estatus active.
     */
    private function _detectWorkersWithoutActiveContract(): array
    {
        $db   = db_connect();
        $rows = $db->query("
            SELECT hp.id, hp.first_name, hp.last_name
            FROM hr_profiles hp
            INNER JOIN hr_employment_data hed ON hed.profile_id = hp.id AND hed.status = 'active'
            LEFT JOIN hr_contracts hc ON hc.profile_id = hp.id
                AND hc.status = 'active'
                AND hc.deleted_at IS NULL
            WHERE hp.deleted_at IS NULL AND hc.id IS NULL
            LIMIT 10
        ")->getResult();

        $alerts = [];
        foreach ($rows as $row) {
            $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $alerts[] = $this->_buildAutoAlert([
                'type'       => 'warning',
                'icon'       => 'fa-file-signature',
                'title'      => 'Sin contrato vigente',
                'message'    => $name . ' — Generar o activar contrato digital',
                'module'     => 'RH',
                'target_url' => route_to('hr.contracts.generate', $row->id),
            ]);
        }

        return $alerts;
    }

    /**
     * Trabajadores activos sin horario asignado vigente.
     */
    private function _detectWorkersWithoutSchedule(): array
    {
        $db   = db_connect();
        $today = date('Y-m-d');
        $rows = $db->query("
            SELECT hp.id, hp.first_name, hp.last_name
            FROM hr_profiles hp
            INNER JOIN hr_employment_data hed ON hed.profile_id = hp.id AND hed.status = 'active'
            LEFT JOIN hr_worker_schedules hws ON hws.profile_id = hp.id
                AND hws.deleted_at IS NULL
                AND (hws.end_date IS NULL OR hws.end_date >= ?)
            WHERE hp.deleted_at IS NULL AND hws.id IS NULL
            LIMIT 10
        ", [$today])->getResult();

        $alerts = [];
        foreach ($rows as $row) {
            $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $alerts[] = $this->_buildAutoAlert([
                'type'       => 'info',
                'icon'       => 'fa-clock',
                'title'      => 'Sin horario vigente',
                'message'    => $name . ' — Asignar turno en expediente',
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
    //  DETECTOR: RH — Nóminas pendientes de pago
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Detecta entradas de nómina pendientes cuyo periodo cerró hace más de 5 días.
     */
    private function _detectPendingPayrolls(): array
    {
        $db    = db_connect();
        $count = $db->table('hr_payroll_entries hpe')
            ->join('hr_payroll_periods hpp', 'hpp.id = hpe.period_id')
            ->where('hpe.payment_status', 'pending')
            ->where('hpp.end_date <', date('Y-m-d', strtotime('-5 days')))
            ->where('hpe.deleted_at', null)
            ->where('hpp.deleted_at', null)
            ->countAllResults();

        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'warning',
            'icon'       => 'fa-money-check-alt',
            'title'      => 'Nóminas pendientes de pago',
            'message'    => $count . ' pago(s) de nómina están pendientes.',
            'module'     => 'RH',
            'target_url' => route_to('hr.payroll.index'),
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
    //  DETECTOR: Gastos recurrentes vencidos
    // ──────────────────────────────────────────────────────────────────────

    private function _detectOverdueRecurringExpenses(): array
    {
        $expenseModel = new \App\Models\Financial\FinExpenseModel();
        $count        = $expenseModel->countOverdueRecurring();

        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'danger',
            'icon'       => 'fa-calendar-times',
            'title'      => 'Servicios recurrentes vencidos',
            'message'    => $count . ' vencimiento(s) pendiente(s) con fecha pasada',
            'module'     => 'Proveedores',
            'target_url' => route_to('suppliers.recurring.index'),
        ])];
    }

    private function _detectDueSoonRecurringExpenses(): array
    {
        $expenseModel = new \App\Models\Financial\FinExpenseModel();
        $count        = $expenseModel->countDueSoonRecurring();

        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'warning',
            'icon'       => 'fa-bell',
            'title'      => 'Servicios recurrentes por vencer',
            'message'    => $count . ' vencimiento(s) dentro de la ventana de aviso (notify_days_before)',
            'module'     => 'Proveedores',
            'target_url' => route_to('suppliers.recurring.index'),
        ])];
    }

    private function _detectPendingPurchaseOrders(): array
    {
        $poModel = new \App\Models\Financial\FinPurchaseOrderModel();
        $draft   = $poModel->countByStatus('draft');
        $sent    = $poModel->countByStatus('sent');
        $count   = $draft + $sent;

        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'info',
            'icon'       => 'fa-file-invoice-dollar',
            'title'      => 'Órdenes de compra pendientes',
            'message'    => $draft . ' en borrador y ' . $sent . ' enviada(s) sin aprobar',
            'module'     => 'Proveedores',
            'target_url' => route_to('suppliers.po.index'),
        ])];
    }

    private function _detectOverduePurchaseOrders(): array
    {
        $count = (new \App\Models\Financial\FinPurchaseOrderModel())->countApprovedOverdue();
        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'danger',
            'icon'       => 'fa-clock',
            'title'      => 'OC aprobadas con entrega vencida',
            'message'    => $count . ' orden(es) aprobada(s) con fecha de entrega pasada',
            'module'     => 'Proveedores',
            'target_url' => route_to('suppliers.po.index'),
        ])];
    }

    private function _detectPoMissingDocuments(): array
    {
        $count = (new \App\Models\Financial\FinPurchaseOrderModel())->countWithoutDocuments();
        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'warning',
            'icon'       => 'fa-paperclip',
            'title'      => 'OC sin factura o recibo',
            'message'    => $count . ' orden(es) enviada(s)/aprobada(s) sin documentos adjuntos',
            'module'     => 'Proveedores',
            'target_url' => route_to('suppliers.po.index'),
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

    private function _detectDueCrmFollowups(): array
    {
        $db = db_connect();
        if (! $db->tableExists('crm_clients') || ! $db->fieldExists('next_followup_at', 'crm_clients')) {
            return [];
        }
        $count = $db->table('crm_clients')
            ->where('next_followup_at <=', date('Y-m-d'))
            ->where('next_followup_at IS NOT NULL', null, false)
            ->whereIn('status', ['lead', 'prospect', 'active'])
            ->where('deleted_at', null)
            ->countAllResults();
        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'warning',
            'icon'       => 'fa-calendar-check',
            'title'      => 'Seguimientos CRM vencidos',
            'message'    => $count . ' cliente(s) con próximo seguimiento vencido o para hoy',
            'module'     => 'CRM',
            'target_url' => route_to('crm.clients'),
        ])];
    }

    private function _detectStaleLeads(): array
    {
        $db    = db_connect();
        if (! $db->tableExists('crm_clients')) {
            return [];
        }
        $count = $db->table('crm_clients')
            ->whereIn('status', ['lead', 'prospect'])
            ->where('deleted_at', null)
            ->where('updated_at <', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->countAllResults();
        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'warning',
            'icon'       => 'fa-user-clock',
            'title'      => 'Leads sin seguimiento',
            'message'    => $count . ' lead/prospecto(s) sin actualización en 7 días',
            'module'     => 'CRM',
            'target_url' => route_to('crm.clients'),
        ])];
    }

    private function _detectExpiringQuotes(): array
    {
        $db = db_connect();
        if (! $db->tableExists('sales_quotes')) {
            return [];
        }
        $count = $db->table('sales_quotes')
            ->whereIn('status', ['draft', 'sent'])
            ->where('valid_until <=', date('Y-m-d', strtotime('+5 days')))
            ->where('valid_until >=', date('Y-m-d'))
            ->where('deleted_at', null)
            ->countAllResults();
        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'info',
            'icon'       => 'fa-file-signature',
            'title'      => 'Cotizaciones por vencer',
            'message'    => $count . ' cotización(es) vencen en 5 días o menos',
            'module'     => 'Ventas',
            'target_url' => route_to('sales.quotes'),
        ])];
    }

    private function _detectDueCustomerServices(): array
    {
        $db = db_connect();
        if (! $db->tableExists('customer_services')) {
            return [];
        }
        $count = $db->table('customer_services')
            ->where('status', 'active')
            ->where('next_due_date <=', date('Y-m-d', strtotime('+7 days')))
            ->where('deleted_at', null)
            ->countAllResults();
        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'warning',
            'icon'       => 'fa-sync',
            'title'      => 'Servicios por renovar',
            'message'    => $count . ' servicio(s) con cobro en los próximos 7 días',
            'module'     => 'Ventas',
            'target_url' => route_to('sales.services'),
        ])];
    }

    private function _detectUnpaidOrders(): array
    {
        $db = db_connect();
        if (! $db->tableExists('sales_orders')) {
            return [];
        }
        $count = $db->table('sales_orders')
            ->whereIn('status', ['confirmed', 'pending', 'partial', 'draft'])
            ->where('total_amount >', 0)
            ->where('deleted_at', null)
            ->countAllResults();
        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'danger',
            'icon'       => 'fa-file-invoice-dollar',
            'title'      => 'Cobros pendientes',
            'message'    => $count . ' orden(es) sin liquidar',
            'module'     => 'Ventas',
            'target_url' => route_to('sales.billing'),
        ])];
    }

    private function _detectPendingActivations(): array
    {
        $db = db_connect();
        $count = 0;
        if ($db->tableExists('customer_services') && $db->fieldExists('activation_status', 'customer_services')) {
            $count += $db->table('customer_services')
                ->where('activation_status', 'pending')
                ->where('deleted_at', null)
                ->countAllResults();
        }
        if ($db->tableExists('sales_order_items') && $db->fieldExists('activation_status', 'sales_order_items')) {
            $count += $db->table('sales_order_items')
                ->where('activation_status', 'pending')
                ->where('deleted_at', null)
                ->countAllResults();
        }
        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'warning',
            'icon'       => 'fa-toggle-off',
            'title'      => 'Productos o servicios sin activar',
            'message'    => $count . ' partida(s) vendida(s) pendientes de activación',
            'module'     => 'Ventas',
            'target_url' => route_to('sales.services'),
        ])];
    }

    private function _detectUnpaidReceipts(): array
    {
        $db = db_connect();
        if (! $db->tableExists('sales_receipts') || ! $db->fieldExists('collection_status', 'sales_receipts')) {
            return [];
        }
        $count = $db->table('sales_receipts')
            ->whereIn('collection_status', ['pending', 'partial'])
            ->where('status !=', 'void')
            ->where('deleted_at', null)
            ->countAllResults();
        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'danger',
            'icon'       => 'fa-hand-holding-usd',
            'title'      => 'Cobranza: recibos sin liquidar',
            'message'    => $count . ' prefactura(s) pendiente(s) o parcial(es)',
            'module'     => 'CRM',
            'target_url' => route_to('sales.billing'),
        ])];
    }

    private function _detectOverdueReceipts(): array
    {
        $db = db_connect();
        if (! $db->tableExists('sales_receipts') || ! $db->fieldExists('collection_status', 'sales_receipts')) {
            return [];
        }
        $count = $db->table('sales_receipts')
            ->where('collection_status', 'overdue')
            ->where('status !=', 'void')
            ->where('deleted_at', null)
            ->countAllResults();
        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'danger',
            'icon'       => 'fa-exclamation-circle',
            'title'      => 'Recibos vencidos',
            'message'    => $count . ' recibo(s) de renovación o venta vencido(s)',
            'module'     => 'CRM',
            'target_url' => route_to('sales.billing'),
        ])];
    }

    private function _detectUnprocessedItems(): array
    {
        $db = db_connect();
        if (! $db->tableExists('sales_order_items') || ! $db->fieldExists('fulfillment_status', 'sales_order_items')) {
            return [];
        }
        $count = $db->table('sales_order_items')
            ->whereIn('fulfillment_status', ['pending', 'processing'])
            ->where('deleted_at', null)
            ->countAllResults();
        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'warning',
            'icon'       => 'fa-truck',
            'title'      => 'Productos sin procesar o enviar',
            'message'    => $count . ' partida(s) pendientes de gestión (físico, digital o servicio)',
            'module'     => 'Ventas',
            'target_url' => route_to('sales.orders'),
        ])];
    }

    private function _detectOpenFollowups(): array
    {
        $db = db_connect();
        if (! $db->tableExists('crm_followups') || ! $db->fieldExists('followup_status', 'crm_followups')) {
            return [];
        }
        $count = $db->table('crm_followups')
            ->whereIn('followup_status', ['open', 'in_progress', 'waiting'])
            ->where('status', 'active')
            ->where('updated_at <', date('Y-m-d H:i:s', strtotime('-5 days')))
            ->where('deleted_at', null)
            ->countAllResults();
        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'info',
            'icon'       => 'fa-headset',
            'title'      => 'Seguimientos CRM abiertos',
            'message'    => $count . ' incidencia(s) o contacto(s) sin cerrar en 5 días',
            'module'     => 'CRM',
            'target_url' => route_to('crm.clients'),
        ])];
    }

    private function _detectStaleOpportunities(): array
    {
        $db = db_connect();
        if (! $db->tableExists('crm_opportunities')) {
            return [];
        }
        $count = $db->table('crm_opportunities o')
            ->join('crm_pipeline_stages s', 's.id = o.stage_id', 'left')
            ->where('o.deleted_at', null)
            ->where('s.is_won', 0)
            ->where('s.is_lost', 0)
            ->groupStart()
                ->where('o.next_followup_at <', date('Y-m-d H:i:s'))
                ->orWhere('o.updated_at <', date('Y-m-d H:i:s', strtotime('-7 days')))
            ->groupEnd()
            ->countAllResults();
        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'warning',
            'icon'       => 'fa-hourglass-half',
            'title'      => 'Oportunidades sin actividad',
            'message'    => $count . ' trato(s) abiertos sin seguimiento reciente',
            'module'     => 'CRM',
            'target_url' => route_to('crm.opportunities'),
        ])];
    }

    private function _detectOverdueProjects(): array
    {
        $db = db_connect();
        if (! $db->tableExists('prm_projects')) {
            return [];
        }
        $count = $db->table('prm_projects')
            ->where('deleted_at', null)
            ->whereIn('status', ['quoting', 'in_progress', 'delivery'])
            ->where('due_date <', date('Y-m-d'))
            ->where('due_date IS NOT NULL', null, false)
            ->countAllResults();
        if ($count === 0) {
            return [];
        }

        return [$this->_buildAutoAlert([
            'type'       => 'danger',
            'icon'       => 'fa-tasks',
            'title'      => 'Proyectos con entrega vencida',
            'message'    => $count . ' proyecto(s) con fecha de entrega retrasada',
            'module'     => 'Proyectos',
            'target_url' => route_to('projects.index'),
        ])];
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
