<?php

namespace App\Models\Financial;

use CodeIgniter\Model;

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
        'category_id',
        'account_id',
        'description',
        'service_type',
        'amount',
        'currency',
        'billing_day',
        'frequency',
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

        $payload = [
            'supplier_id'   => $supplierId,
            'description'   => trim((string) ($data['description'] ?? '')),
            'service_type'  => $serviceType,
            'amount'        => (float) ($data['amount'] ?? 0),
            'currency'    => strtoupper(trim((string) ($data['currency'] ?? 'MXN'))),
            'billing_day' => $data['frequency'] === 'weekly' ? null : (int) ($data['billing_day'] ?? 1),
            'frequency'   => $data['frequency'] ?? 'monthly',
            'is_active'   => array_key_exists('is_active', $data) ? (int) $data['is_active'] : 1,
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
}
