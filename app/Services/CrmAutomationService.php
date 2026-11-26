<?php

declare(strict_types=1);

namespace App\Services;

use App\Libraries\AlertService;
use App\Models\CRM\CrmAutomationRuleModel;
use App\Models\CRM\CrmFollowupModel;
use App\Models\CRM\CrmOpportunityModel;

class CrmAutomationService
{
    public function run(string $event, object $opportunity): void
    {
        $rules = (new CrmAutomationRuleModel())->activeFor($event);
        foreach ($rules as $rule) {
            $this->execute((string) $rule->action, $opportunity, (string) ($rule->payload_json ?? ''), $event);
        }
    }

    private function execute(string $action, object $opportunity, string $payloadJson, string $event): void
    {
        $payload = json_decode($payloadJson, true);
        if (! is_array($payload)) {
            $payload = [];
        }

        if ($action === 'create_project') {
            $result = (new ProjectDeliveryService())->fromOpportunity($opportunity);
            if (($result['ok'] ?? false) && ! empty($result['id'])) {
                (new CrmOpportunityModel())->update((int) $opportunity->id, ['project_id' => (int) $result['id']]);
                (new CrmFollowupModel())->createVersion([
                    'client_id'       => (int) $opportunity->client_id,
                    'followup_type'   => 'service',
                    'title'           => 'Proyecto creado desde oportunidad ' . $opportunity->folio,
                    'comment'         => 'Proyecto ID ' . $result['id'],
                    'created_by'      => auth()->id(),
                    'change_source'   => 'auto',
                    'followup_status' => 'open',
                ]);
            }
        }

        if (($payload['notify'] ?? false) === true) {
            try {
                (new AlertService())->dispatch(
                    'CRM: ' . $event,
                    $opportunity->folio . ' — ' . $opportunity->title,
                    'info',
                    'fa-handshake',
                    site_url('nat/crm/opportunities'),
                    'crm.access',
                    'CRM',
                    'crm_opportunity',
                    (int) $opportunity->id
                );
            } catch (\Throwable $e) {
                log_message('error', 'CRM automation notify: {msg}', ['msg' => $e->getMessage()]);
            }
        }
    }
}
