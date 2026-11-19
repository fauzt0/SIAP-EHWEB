<?php

namespace App\Services;

use App\Libraries\AlertService;
use App\Models\Financial\FinExpenseModel;
use App\Models\Financial\FinSupplierReminderLogModel;

/**
 * Recordatorios del módulo proveedores: alerta in-app + canal email (listo aunque SMTP no esté activo).
 */
class SupplierReminderService
{
    public const PERMISSION = 'catalog.access';

    private FinSupplierReminderLogModel $logModel;
    private AlertService $alerts;
    private AppMailer $mailer;

    public function __construct()
    {
        $this->logModel = new FinSupplierReminderLogModel();
        $this->alerts   = new AlertService();
        $this->mailer   = new AppMailer();
    }

    /**
     * @param array{created?:int,created_pos?:int,templates?:int} $result
     */
    public function afterRecurringGeneration(array $result): void
    {
        $created    = (int) ($result['created'] ?? 0);
        $createdPos = (int) ($result['created_pos'] ?? 0);

        if ($created <= 0 && $createdPos <= 0) {
            return;
        }

        $message = sprintf(
            'Se generaron %d vencimiento(s) y %d orden(es) de compra automática(s) de servicios recurrentes.',
            $created,
            $createdPos
        );

        $this->dispatchSafe(
            'Servicios recurrentes generados',
            $message,
            'info',
            'fa-sync-alt',
            route_to('suppliers.recurring.index'),
            'recurring_generate'
        );

        $this->queueEmail(
            'recurring_generated',
            'SIAP — Vencimientos recurrentes generados',
            $this->wrapHtml('<p>' . esc($message) . '</p><p>Revise Pagos recurrentes y Órdenes de compra.</p>'),
            'recurring_batch',
            null
        );
    }

    /**
     * Cron: avisos de vencidos / por vencer. Email se encola o se envía si SMTP está listo.
     *
     * @return array{overdue:int,due_soon:int,email:string}
     */
    public function sendDueReminders(): array
    {
        $expenseModel = new FinExpenseModel();
        $overdue      = $expenseModel->countOverdueRecurring();
        $dueSoon      = $expenseModel->countDueSoonRecurring();

        if ($overdue > 0) {
            $this->dispatchSafe(
                'Servicios recurrentes vencidos',
                $overdue . ' vencimiento(s) pendiente(s) con fecha pasada.',
                'danger',
                'fa-calendar-times',
                route_to('suppliers.recurring.index'),
                'recurring_overdue'
            );
        }

        if ($dueSoon > 0) {
            $this->dispatchSafe(
                'Servicios recurrentes por vencer',
                $dueSoon . ' vencimiento(s) dentro de la ventana de aviso.',
                'warning',
                'fa-bell',
                route_to('suppliers.recurring.index'),
                'recurring_due_soon'
            );
        }

        $parts = [];
        if ($overdue > 0) {
            $parts[] = $overdue . ' vencido(s)';
        }
        if ($dueSoon > 0) {
            $parts[] = $dueSoon . ' por vencer';
        }

        $emailStatus = 'skipped_empty';
        if ($parts !== []) {
            $emailStatus = $this->queueEmail(
                'recurring_due_digest',
                'SIAP — Recordatorio de pagos a proveedores',
                $this->wrapHtml('<p>Hay ' . esc(implode(' y ', $parts)) . ' en servicios recurrentes.</p>'),
                'recurring_digest',
                null
            );
        }

        return [
            'overdue'  => $overdue,
            'due_soon' => $dueSoon,
            'email'    => $emailStatus,
        ];
    }

    public function emailChannelStatus(): string
    {
        return $this->mailer->isSmtpConfigured() ? 'smtp_ready' : 'queued_until_smtp';
    }

    private function dispatchSafe(
        string $title,
        string $message,
        string $type,
        string $icon,
        string $targetUrl,
        string $referenceType
    ): void {
        try {
            $this->alerts->createAlert([
                'title'               => $title,
                'message'             => $message,
                'type'                => $type,
                'icon'                => $icon,
                'target_url'          => $targetUrl,
                'required_permission' => self::PERMISSION,
                'module'              => 'Proveedores',
                'reference_type'      => $referenceType,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'SupplierReminderService::dispatchSafe — {msg}', ['msg' => $e->getMessage()]);
        }
    }

    private function queueEmail(
        string $eventType,
        string $subject,
        string $html,
        ?string $referenceType,
        ?int $referenceId
    ): string {
        $recipients = $this->recipientEmails();
        $payload    = json_encode([
            'to'      => $recipients,
            'subject' => $subject,
            'html'    => $html,
        ], JSON_UNESCAPED_UNICODE);

        $status = 'queued';
        $error  = null;

        if ($recipients === []) {
            $status = 'skipped_no_recipients';
            $error  = 'Sin destinatarios con permiso ' . self::PERMISSION;
        } elseif (! $this->mailer->isSmtpConfigured()) {
            $status = 'queued_until_smtp';
            $error  = 'SMTP no configurado (email.SMTPHost). El canal queda listo.';
        } else {
            $ok = $this->mailer->sendHtml($recipients, [], $subject, $html);
            $status = $ok ? 'sent' : 'failed';
            $error  = $ok ? null : $this->mailer->getError();
        }

        $this->logModel->insert([
            'channel'        => 'email',
            'event_type'     => $eventType,
            'status'         => $status,
            'subject'        => $subject,
            'payload'        => $payload,
            'error_message'  => $error,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
        ]);

        return $status;
    }

    /**
     * @return list<string>
     */
    private function recipientEmails(): array
    {
        $groupsCfg = config('AuthGroups');
        $groups    = [];
        foreach ($groupsCfg->matrix as $group => $perms) {
            foreach ($perms as $perm) {
                if (
                    $perm === self::PERMISSION
                    || $perm === 'catalog.*'
                    || $perm === '*'
                    || $perm === 'catalog.manage-suppliers'
                ) {
                    $groups[] = $group;
                    break;
                }
            }
        }

        $db = db_connect();
        $emails = [];

        if ($groups !== []) {
            $builder = $db->table('users u')
                ->select('ai.secret AS email')
                ->distinct()
                ->join('auth_identities ai', "ai.user_id = u.id AND ai.type = 'email_password'", 'inner')
                ->join('auth_groups_users agu', 'agu.user_id = u.id', 'inner')
                ->where('u.deleted_at', null)
                ->whereIn('agu.group', $groups);
            foreach ($builder->get()->getResult() as $row) {
                $emails[] = trim((string) $row->email);
            }
        }

        $direct = $db->table('users u')
            ->select('ai.secret AS email')
            ->distinct()
            ->join('auth_identities ai', "ai.user_id = u.id AND ai.type = 'email_password'", 'inner')
            ->join('auth_permissions_users apu', 'apu.user_id = u.id', 'inner')
            ->where('u.deleted_at', null)
            ->whereIn('apu.permission', [self::PERMISSION, 'catalog.manage-suppliers', 'catalog.*']);
        foreach ($direct->get()->getResult() as $row) {
            $emails[] = trim((string) $row->email);
        }

        $out = [];
        foreach ($emails as $email) {
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $out[] = $email;
            }
        }

        return array_values(array_unique($out));
    }

    private function wrapHtml(string $inner): string
    {
        return '<html><body style="font-family:sans-serif;font-size:14px;color:#222">'
            . $inner
            . '<p style="color:#888;font-size:12px">Notificación automática del ERP SIAP. Canal email de proveedores.</p>'
            . '</body></html>';
    }
}
