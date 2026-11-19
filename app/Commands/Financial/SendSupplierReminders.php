<?php

namespace App\Commands\Financial;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\SupplierReminderService;

class SendSupplierReminders extends BaseCommand
{
    protected $group       = 'Financial';
    protected $name        = 'financial:send-supplier-reminders';
    protected $description = 'Despacha alertas y encola/envía recordatorios por email de proveedores (SMTP opcional).';
    protected $usage       = 'financial:send-supplier-reminders';

    public function run(array $params)
    {
        $service = new SupplierReminderService();
        $result  = $service->sendDueReminders();

        CLI::write(
            sprintf(
                'Vencidos: %d | Por vencer: %d | Email: %s | Canal: %s',
                $result['overdue'],
                $result['due_soon'],
                $result['email'],
                $service->emailChannelStatus()
            ),
            'green'
        );
    }
}
