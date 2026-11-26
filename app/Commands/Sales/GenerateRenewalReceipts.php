<?php

namespace App\Commands\Sales;

use App\Services\SalesReceiptScheduler;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class GenerateRenewalReceipts extends BaseCommand
{
    protected $group       = 'Sales';
    protected $name        = 'sales:generate-receipts';
    protected $description = 'Emite recibos (prefactura) de servicios que se renuevan en el horizonte de cobranza.';
    protected $usage       = 'sales:generate-receipts';

    public function run(array $params)
    {
        $result = (new SalesReceiptScheduler())->generateDue();
        CLI::write(sprintf(
            'Servicios revisados: %d. Recibos nuevos: %d. Omitidos: %d. Marcados vencidos: %d.',
            $result['services'],
            $result['created'],
            $result['skipped'],
            $result['overdue']
        ), 'green');
    }
}
