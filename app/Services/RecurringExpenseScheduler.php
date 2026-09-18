<?php

namespace App\Services;

use App\Models\Financial\FinRecurringExpenseModel;
use App\Models\Financial\FinExpenseModel;

/**
 * Genera vencimientos en fin_expenses a partir de plantillas recurrentes activas.
 */
class RecurringExpenseScheduler
{
    private FinRecurringExpenseModel $templateModel;
    private FinExpenseModel $expenseModel;

    public function __construct()
    {
        $this->templateModel = new FinRecurringExpenseModel();
        $this->expenseModel  = new FinExpenseModel();
    }

    /**
     * @return array{created: int, templates: int}
     */
    public function generate(?string $horizonEnd = null): array
    {
        $horizonEnd = $horizonEnd ?: date('Y-m-t', strtotime('first day of next month'));

        $templates = $this->templateModel
            ->where('is_active', 1)
            ->where('deleted_at IS NULL', null, false)
            ->findAll();

        $created   = 0;
        $processed = 0;

        foreach ($templates as $template) {
            $processed++;
            $start = $template->start_date ?: date('Y-m-d');
            $end   = $template->end_date ?: $horizonEnd;
            if ($end > $horizonEnd) {
                $end = $horizonEnd;
            }
            if ($start > $end) {
                continue;
            }

            $periods = $this->computePeriods($template, $start, $end);

            $this->templateModel->db->transStart();

            $nextDue = null;
            foreach ($periods as $period) {
                $exists = $this->expenseModel
                    ->where('recurring_expense_id', $template->id)
                    ->where('period_label', $period['label'])
                    ->first();

                if ($exists) {
                    if ($period['due_date'] >= date('Y-m-d')) {
                        $nextDue = $nextDue === null ? $period['due_date'] : min($nextDue, $period['due_date']);
                    }
                    continue;
                }

                $this->expenseModel->insert([
                    'supplier_id'          => $template->supplier_id,
                    'category_id'          => $template->category_id,
                    'recurring_expense_id' => $template->id,
                    'org_branch_id'        => $template->org_branch_id,
                    'total_amount'         => $template->amount,
                    'paid_amount'          => 0,
                    'currency'             => $template->currency ?? 'MXN',
                    'due_date'             => $period['due_date'],
                    'status'               => 'pending',
                    'period_label'         => $period['label'],
                ]);
                $created++;

                if ($period['due_date'] >= date('Y-m-d')) {
                    $nextDue = $nextDue === null ? $period['due_date'] : min($nextDue, $period['due_date']);
                }
            }

            $this->templateModel->update($template->id, [
                'last_generated_at' => date('Y-m-d H:i:s'),
                'next_due_date'     => $nextDue,
            ]);

            $this->templateModel->db->transComplete();
        }

        return ['created' => $created, 'templates' => $processed];
    }

    /**
     * @return array<int, array{label: string, due_date: string}>
     */
    private function computePeriods(object $template, string $start, string $end): array
    {
        return match ($template->frequency) {
            'weekly'      => $this->weeklyPeriods($template, $start, $end),
            'monthly'     => $this->monthlyPeriods($template, $start, $end, 1),
            'quarterly'   => $this->monthlyPeriods($template, $start, $end, 3),
            'semiannual'  => $this->monthlyPeriods($template, $start, $end, 6),
            'yearly'      => $this->monthlyPeriods($template, $start, $end, 12),
            default       => $this->monthlyPeriods($template, $start, $end, 1),
        };
    }

    private function weeklyPeriods(object $template, string $start, string $end): array
    {
        $periods   = [];
        $weekday   = (int) ($template->billing_weekday ?: 1);
        $cursor    = new \DateTime($start);
        $endDate   = new \DateTime($end);

        while ($cursor <= $endDate) {
            $isoYear = (int) $cursor->format('o');
            $isoWeek = (int) $cursor->format('W');
            $label   = sprintf('%d-W%02d', $isoYear, $isoWeek);

            $due = clone $cursor;
            $due->setISODate($isoYear, $isoWeek, $weekday);
            $dueStr = $due->format('Y-m-d');

            if ($dueStr >= $start && $dueStr <= $end) {
                $periods[$label] = ['label' => $label, 'due_date' => $dueStr];
            }

            $cursor->modify('+1 week');
        }

        return array_values($periods);
    }

    private function monthlyPeriods(object $template, string $start, string $end, int $stepMonths): array
    {
        $periods    = [];
        $billingDay = (int) ($template->billing_day ?: 1);
        $cursor     = new \DateTime(date('Y-m-01', strtotime($start)));
        $endDate    = new \DateTime($end);

        while ($cursor <= $endDate) {
            $year  = (int) $cursor->format('Y');
            $month = (int) $cursor->format('m');

            if ($stepMonths === 3) {
                $quarter = (int) ceil($month / 3);
                $label   = sprintf('%d-Q%d', $year, $quarter);
            } elseif ($stepMonths === 6) {
                $half = $month <= 6 ? 1 : 2;
                $label = sprintf('%d-H%d', $year, $half);
            } elseif ($stepMonths === 12) {
                $label = (string) $year;
            } else {
                $label = sprintf('%d-%02d', $year, $month);
            }

            $daysInMonth = (int) $cursor->format('t');
            $day         = min($billingDay, $daysInMonth);
            $dueStr      = sprintf('%d-%02d-%02d', $year, $month, $day);

            if ($dueStr >= $start && $dueStr <= $end) {
                $periods[$label] = ['label' => $label, 'due_date' => $dueStr];
            }

            $cursor->modify('+' . $stepMonths . ' month');
        }

        return array_values($periods);
    }
}
