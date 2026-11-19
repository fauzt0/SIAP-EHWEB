<?php

namespace App\Commands\Financial;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\RecurringExpenseScheduler;

class GenerateRecurringExpenses extends BaseCommand
{
    protected $group       = 'Financial';
    protected $name        = 'financial:generate-recurring';
    protected $description = 'Genera vencimientos en fin_expenses desde plantillas recurrentes activas.';
    protected $usage       = 'financial:generate-recurring';
    protected $arguments   = [];
    protected $options     = [];

    public function run(array $params)
    {
        $scheduler = new RecurringExpenseScheduler();
        $result    = $scheduler->generate();

        CLI::write(
            sprintf(
                'Procesadas %d plantillas. Creados %d vencimientos y %d OC automáticas.',
                $result['templates'],
                $result['created'],
                $result['created_pos'] ?? 0
            ),
            'green'
        );

        if (($result['created'] ?? 0) > 0 || ($result['created_pos'] ?? 0) > 0) {
            (new \App\Services\SupplierReminderService())->afterRecurringGeneration($result);
        }
    }
}
