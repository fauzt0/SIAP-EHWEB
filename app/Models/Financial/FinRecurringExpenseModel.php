<?php

namespace App\Models\Financial;

use CodeIgniter\Model;
use App\Traits\DataTableTrait;

/**
 * FinRecurringExpenseModel
 * 
 * Modelo para la gestión de gastos recurrentes (fin_recurring_expenses).
 * Cada registro asocia un servicio periódico (luz, teléfono, basura, etc.)
 * a un proveedor específico.
 * 
 * @property int $id
 * @property int|null $supplier_id
 * @property int|null $category_id
 * @property int|null $account_id
 * @property string $description
 * @property float $amount
 * @property string $currency
 * @property int $billing_day
 * @property string $frequency
 * @property string|null $last_generated_at
 * @property int $is_active
 */
class FinRecurringExpenseModel extends Model
{
    use DataTableTrait;
    /**
     * Tipos de gasto recurrente (catálogo México / ERP).
     * Clave = valor en BD; valor = etiqueta en UI.
     */
    public const SERVICE_TYPES = [
        'electricity'     => 'Electricidad (CFE)',
        'water'           => 'Agua',
        'gas'             => 'Gas natural / LP',
        'trash'           => 'Basura / Saneamiento',
        'phone'           => 'Telefonía fija',
        'internet'        => 'Internet',
        'mobile'          => 'Telefonía móvil',
        'rent'            => 'Renta de inmueble',
        'maintenance'     => 'Mantenimiento',
        'cleaning'        => 'Limpieza',
        'security'        => 'Vigilancia / Seguridad',
        'parking'         => 'Estacionamiento',
        'accounting'      => 'Contabilidad y fiscal',
        'legal'           => 'Honorarios legales / notariales',
        'insurance'       => 'Seguros',
        'licenses'        => 'Licencias y permisos',
        'software'        => 'Software / SaaS',
        'hosting'         => 'Hosting / Cloud',
        'marketing'       => 'Publicidad / Marketing',
        'courier'         => 'Mensajería / Paquetería',
        'fuel'            => 'Combustible / Flotilla',
        'pest_control'    => 'Fumigación / Control de plagas',
        'payroll_service' => 'Servicios de nómina (outsourcing)',
        'banking'         => 'Comisiones bancarias',
        'other'           => 'Otro',
    ];

    /** Agrupación para el selector del modal. */
    public const SERVICE_TYPE_GROUPS = [
        'Servicios públicos y utilities' => ['electricity', 'water', 'gas', 'trash'],
        'Telecomunicaciones'           => ['phone', 'internet', 'mobile'],
        'Inmuebles y operación'        => ['rent', 'maintenance', 'cleaning', 'security', 'parking'],
        'Profesionales y cumplimiento' => ['accounting', 'legal', 'insurance', 'licenses'],
        'Tecnología'                   => ['software', 'hosting'],
        'Otros gastos recurrentes'     => [
            'marketing', 'courier', 'fuel', 'pest_control',
            'payroll_service', 'banking', 'other',
        ],
    ];

    protected $table            = 'fin_recurring_expenses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'supplier_id',
        'org_branch_id',
        'category_id',
        'account_id',
        'description',
        'service_type',
        'amount',
        'currency',
        'billing_day',
        'billing_weekday',
        'frequency',
        'start_date',
        'end_date',
        'next_due_date',
        'notify_days_before',
        'last_generated_at',
        'is_active',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'supplier_id' => [
            'label' => 'Proveedor',
            'rules' => 'permit_empty|is_natural_no_zero|is_not_unique[fin_suppliers.id]',
        ],
        'category_id' => [
            'label' => 'Categoría de Gasto',
            'rules' => 'permit_empty|is_natural_no_zero',
        ],
        'account_id' => [
            'label' => 'Cuenta Contable',
            'rules' => 'permit_empty|is_natural_no_zero',
        ],
        'description' => [
            'label' => 'Descripción del Servicio',
            'rules' => 'required|max_length[255]',
        ],
        'service_type' => [
            'label' => 'Tipo de Servicio',
            'rules' => 'required',
        ],
        'amount' => [
            'label' => 'Monto Estimado',
            'rules' => 'required|decimal|greater_than[0]',
        ],
        'currency' => [
            'label' => 'Moneda',
            'rules' => 'permit_empty|max_length[3]',
        ],
        'billing_day' => [
            'label' => 'Día de Facturación',
            'rules' => 'permit_empty|integer|greater_than[0]|less_than[32]',
        ],
        'frequency' => [
            'label' => 'Frecuencia',
            'rules' => 'required|in_list[weekly,monthly,quarterly,semiannual,yearly]',
        ],
        'is_active' => [
            'label' => 'Activo',
            'rules' => 'permit_empty|in_list[0,1]',
        ],
        'org_branch_id' => [
            'label' => 'Sucursal',
            'rules' => 'permit_empty|is_natural_no_zero|is_not_unique[org_branches.id]',
        ],
        'start_date' => [
            'label' => 'Fecha de inicio',
            'rules' => 'permit_empty|valid_date',
        ],
        'end_date' => [
            'label' => 'Fecha de fin',
            'rules' => 'permit_empty|valid_date',
        ],
        'next_due_date' => [
            'label' => 'Próximo vencimiento',
            'rules' => 'permit_empty|valid_date',
        ],
        'billing_weekday' => [
            'label' => 'Día de la semana',
            'rules' => 'permit_empty|integer|greater_than[0]|less_than[8]',
        ],
        'notify_days_before' => [
            'label' => 'Días de aviso',
            'rules' => 'permit_empty|integer|greater_than_equal_to[0]|less_than[366]',
        ],
    ];

    protected $validationMessages = [
        'description' => [
            'required'   => 'La descripción del servicio es obligatoria.',
            'max_length' => 'La descripción no puede exceder los 255 caracteres.',
        ],
        'amount' => [
            'required'     => 'El monto estimado es obligatorio.',
            'decimal'      => 'El monto debe ser un valor decimal válido.',
            'greater_than' => 'El monto debe ser mayor a 0.',
        ],
        'billing_day' => [
            'integer'      => 'El día de pago debe ser un número entero.',
            'greater_than' => 'El día de pago debe estar entre 1 y 31.',
            'less_than'    => 'El día de pago debe estar entre 1 y 31.',
        ],
        'frequency' => [
            'required' => 'La frecuencia es obligatoria.',
            'in_list'  => 'La frecuencia debe ser: weekly, monthly, quarterly, semiannual o yearly.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $datatableConfig = [
        'column_order'  => [
            'fin_recurring_expenses.description',
            'fin_suppliers.commercial_name',
            'org_branches.name',
            'fin_recurring_expenses.service_type',
            'fin_recurring_expenses.amount',
            'fin_recurring_expenses.frequency',
            'fin_recurring_expenses.next_due_date',
            'fin_recurring_expenses.is_active',
            null,
        ],
        'column_search' => [
            'fin_recurring_expenses.description',
            'fin_suppliers.commercial_name',
            'org_branches.name',
        ],
        'order' => ['fin_recurring_expenses.id' => 'desc'],
    ];

    protected function _get_datatables_query($postData = [])
    {
        $this->builder()
            ->select([
                'fin_recurring_expenses.*',
                'fin_suppliers.commercial_name AS supplier_name',
                'org_branches.name AS branch_name',
            ])
            ->join('fin_suppliers', 'fin_suppliers.id = fin_recurring_expenses.supplier_id', 'left')
            ->join('org_branches', 'org_branches.id = fin_recurring_expenses.org_branch_id', 'left')
            ->where('fin_recurring_expenses.deleted_at IS NULL');

        if (isset($postData['filter_active']) && $postData['filter_active'] !== '') {
            $this->builder()->where('fin_recurring_expenses.is_active', (int) $postData['filter_active']);
        }

        if (!empty($postData['filter_supplier_id'])) {
            $this->builder()->where('fin_recurring_expenses.supplier_id', (int) $postData['filter_supplier_id']);
        }

        if (!empty($postData['filter_branch_id'])) {
            $this->builder()->where('fin_recurring_expenses.org_branch_id', (int) $postData['filter_branch_id']);
        }

        if (!empty($postData['filter_service_type'])) {
            $this->builder()->where('fin_recurring_expenses.service_type', $postData['filter_service_type']);
        }

        $this->_apply_datatables_filters($postData);
    }

    public function count_all($where = [])
    {
        $builder = $this->db->table($this->table);
        $builder->where('deleted_at IS NULL');
        if (!empty($where)) {
            $builder->where($where);
        }

        return $builder->countAllResults();
    }

    public function __construct()
    {
        parent::__construct();
        $this->validationRules['service_type']['rules'] = 'required|in_list[' . implode(',', array_keys(self::SERVICE_TYPES)) . ']';
    }

    public static function getServiceTypeOptions(): array
    {
        return self::SERVICE_TYPES;
    }

    public static function getServiceTypeGroups(): array
    {
        return self::SERVICE_TYPE_GROUPS;
    }

    public static function getServiceTypeLabel(?string $code): string
    {
        if ($code === null || $code === '') {
            return '—';
        }

        return self::SERVICE_TYPES[$code] ?? $code;
    }

    /**
     * getBySupplier
     * 
     * Obtiene todos los gastos recurrentes activos (sin soft-delete)
     * asociados a un proveedor específico.
     *
     * @param int $supplierId ID del proveedor
     * @return array
     */
    public function getBySupplier(int $supplierId): array
    {
        return $this->where('supplier_id', $supplierId)
            ->orderBy('description', 'ASC')
            ->findAll();
    }

    /**
     * getActiveServices
     * 
     * Obtiene únicamente los servicios activos (is_active = 1)
     * asociados a un proveedor.
     *
     * @param int $supplierId ID del proveedor
     * @return array
     */
    public function getActiveServices(int $supplierId): array
    {
        return $this->where('supplier_id', $supplierId)
            ->where('is_active', 1)
            ->orderBy('description', 'ASC')
            ->findAll();
    }

    /**
     * createForSupplier
     *
     * Crea un gasto recurrente asociado a un proveedor.
     * La validación se ejecuta en el modelo (§19).
     *
     * @param int   $supplierId ID del proveedor
     * @param array $data       Datos del servicio (description, amount, etc.)
     * @return int|false ID del registro creado o false si falla
     */
    public function createForSupplier(int $supplierId, array $data): int|false
    {
        $serviceType = $data['service_type'] ?? 'other';
        if (! array_key_exists($serviceType, self::SERVICE_TYPES)) {
            $serviceType = 'other';
        }

        $frequency = $data['frequency'] ?? 'monthly';
        $branchModel = new \App\Models\Organization\OrgBranchModel();
        $mainBranchId = $branchModel->getMainBranchId();

        $payload = [
            'supplier_id'          => $supplierId,
            'org_branch_id'        => !empty($data['org_branch_id']) ? (int) $data['org_branch_id'] : $mainBranchId,
            'description'          => trim((string) ($data['description'] ?? '')),
            'service_type'         => $serviceType,
            'amount'               => (float) ($data['amount'] ?? 0),
            'currency'             => strtoupper(trim((string) ($data['currency'] ?? 'MXN'))),
            'billing_day'          => $frequency === 'weekly' ? null : (int) ($data['billing_day'] ?? 1),
            'billing_weekday'      => $frequency === 'weekly' ? (int) ($data['billing_weekday'] ?? 1) : null,
            'frequency'            => $frequency,
            'start_date'           => $data['start_date'] ?? date('Y-m-d'),
            'end_date'             => !empty($data['end_date']) ? $data['end_date'] : null,
            'next_due_date'        => $data['next_due_date'] ?? null,
            'notify_days_before'   => isset($data['notify_days_before']) ? (int) $data['notify_days_before'] : 3,
            'is_active'            => array_key_exists('is_active', $data) ? (int) $data['is_active'] : 1,
        ];

        if (isset($data['category_id'])) {
            $payload['category_id'] = (int) $data['category_id'] ?: null;
        }

        if (isset($data['account_id'])) {
            $payload['account_id'] = (int) $data['account_id'] ?: null;
        }

        $newId = $this->insert($payload);

        return $newId ?: false;
    }

    public function updateForSupplier(int $id, array $data): bool
    {
        $existing = $this->find($id);
        if (!$existing) {
            return false;
        }

        $frequency = $data['frequency'] ?? $existing->frequency;

        $payload = [
            'description'        => trim((string) ($data['description'] ?? $existing->description)),
            'service_type'       => $data['service_type'] ?? $existing->service_type,
            'amount'             => (float) ($data['amount'] ?? $existing->amount),
            'currency'           => strtoupper(trim((string) ($data['currency'] ?? $existing->currency))),
            'billing_day'        => $frequency === 'weekly' ? null : (int) ($data['billing_day'] ?? $existing->billing_day ?? 1),
            'billing_weekday'    => $frequency === 'weekly' ? (int) ($data['billing_weekday'] ?? $existing->billing_weekday ?? 1) : null,
            'frequency'          => $frequency,
            'org_branch_id'      => !empty($data['org_branch_id']) ? (int) $data['org_branch_id'] : $existing->org_branch_id,
            'start_date'         => $data['start_date'] ?? $existing->start_date,
            'end_date'           => array_key_exists('end_date', $data) ? ($data['end_date'] ?: null) : $existing->end_date,
            'notify_days_before' => isset($data['notify_days_before']) ? (int) $data['notify_days_before'] : $existing->notify_days_before,
            'is_active'          => array_key_exists('is_active', $data) ? (int) $data['is_active'] : (int) $existing->is_active,
        ];

        if (!empty($data['supplier_id'])) {
            $payload['supplier_id'] = (int) $data['supplier_id'];
        }

        return $this->update($id, $payload);
    }
}
