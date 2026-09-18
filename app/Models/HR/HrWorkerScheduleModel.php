<?php

namespace App\Models\HR;

use CodeIgniter\Model;

class HrWorkerScheduleModel extends Model
{
    protected $table            = 'hr_worker_schedules';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'profile_id',
        'shift_id',
        'start_date',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    protected $validationRules = [
        'profile_id' => 'required|is_natural_no_zero',
        'shift_id'   => 'required|is_natural_no_zero',
        'start_date' => 'required|valid_date[Y-m-d]',
    ];

    /**
     * Horario vigente del trabajador (turno activo no eliminado).
     */
    public function getCurrentSchedule(int $profileId): ?object
    {
        return $this->select('hr_worker_schedules.*, hr_cat_shifts.name, hr_cat_shifts.start_time, hr_cat_shifts.end_time, hr_cat_shifts.work_days')
            ->join('hr_cat_shifts', 'hr_cat_shifts.id = hr_worker_schedules.shift_id AND hr_cat_shifts.deleted_at IS NULL', 'left')
            ->where('hr_worker_schedules.profile_id', $profileId)
            ->where('hr_worker_schedules.deleted_at', null)
            ->orderBy('hr_worker_schedules.start_date', 'DESC')
            ->orderBy('hr_worker_schedules.id', 'DESC')
            ->first();
    }

    /**
     * Asigna o actualiza el turno del trabajador.
     *
     * @return array{success: bool, changed: bool}
     */
    public function assignSchedule(int $profileId, ?int $shiftId, ?string $startDate = null): array
    {
        if (empty($shiftId)) {
            return ['success' => true, 'changed' => false];
        }

        $startDate = $startDate ? substr($startDate, 0, 10) : date('Y-m-d');

        $current = $this->where('profile_id', $profileId)
            ->orderBy('start_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();

        if ($current) {
            if ((int) $current->shift_id === (int) $shiftId) {
                $currentStart = substr((string) $current->start_date, 0, 10);
                if ($currentStart !== $startDate) {
                    $updated = $this->update($current->id, ['start_date' => $startDate]);

                    return ['success' => (bool) $updated, 'changed' => (bool) $updated];
                }

                return ['success' => true, 'changed' => false];
            }

            $this->db->transStart();
            $this->delete($current->id);
            $this->insert([
                'profile_id' => $profileId,
                'shift_id'   => $shiftId,
                'start_date' => $startDate,
            ]);
            $this->db->transComplete();

            $success = $this->db->transStatus();
            return ['success' => $success, 'changed' => $success];
        }

        $inserted = $this->insert([
            'profile_id' => $profileId,
            'shift_id'   => $shiftId,
            'start_date' => $startDate,
        ]);

        return ['success' => (bool) $inserted, 'changed' => (bool) $inserted];
    }

    /**
     * Cuenta asignaciones vigentes de un turno.
     */
    public function countByShiftId(int $shiftId): int
    {
        return $this->where('shift_id', $shiftId)->countAllResults();
    }
}
