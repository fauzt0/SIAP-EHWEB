# Reglas del Proyecto ERP / CRM (CodeIgniter 4 + AppStack)

Estas reglas se derivan directamente de [`DOCUMENTACION_TECNICA.md`](DOCUMENTACION_TECNICA.md) y son de observancia obligatoria para todo desarrollo en el sistema.

---

## 1. Peticiones AJAX

### A. Validación en Backend
Todos los endpoints de consumo asíncrono (DataTables, selects dinámicos, guardados silenciosos) **deben** proteger el controlador con:

```php
if (!$this->request->isAJAX()) {
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Endpoint exclusivo para peticiones AJAX.");
}
```

### B. Frontend con Fetch API
Toda petición `fetch()` **debe** incluir la cabecera:

```javascript
headers: {
    'X-Requested-With': 'XMLHttpRequest'
}
```

---

## 2. Seguridad CSRF

### A. Token oculto en vistas
Incluir **siempre** al inicio de `section('main')`:

```html
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">
```

### B. DataTables y renovación de token
En la inicialización de DataTable, inyectar el token CSRF en `data` y escuchar el evento `xhr.dt` para actualizar el hash:

```javascript
window.miTabla = $('#tabla').DataTable({
    ajax: {
        url: '...',
        type: 'POST',
        data: function (d) {
            d['<?= csrf_token() ?>'] = document.getElementById('csrf_token').value;
        }
    }
});

$('#tabla').on('xhr.dt', function (e, settings, json, xhr) {
    if (xhr && xhr.getResponseHeader('<?= csrf_header() ?>')) {
        document.getElementById('csrf_token').value = xhr.getResponseHeader('<?= csrf_header() ?>');
    }
});
```

### C. Fetch API con FormData
Inyectar el token CSRF en las **cabeceras HTTP** para peticiones mutantes (POST, PUT, DELETE) y renovarlo en la respuesta:

```javascript
const currentToken = document.getElementById('csrf_token').value;

fetch(url, {
    method: 'POST',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        '<?= csrf_header() ?>': currentToken
    },
    body: formData
})
.then(response => {
    const newToken = response.headers.get('<?= csrf_header() ?>');
    if (newToken) {
        document.getElementById('csrf_token').value = newToken;
    }
    return response.json();
});
```

---

## 3. Enrutamiento

### A. Verbos HTTP
- `$routes->get()` → Consultas/Listados.
- `$routes->post()` → Creación, guardado, edición, eliminación.
- `$routes->put()` → Edición/Actualización (alternativa).
- `$routes->delete()` → Eliminación (alternativa).

### B. Rutas con nombre (Named Routes)
**Obligatorio** asignar un nombre con `as`:

```php
$routes->post('create', 'Users\UserController::create', ['as' => 'user.create']);
```

Usar `route_to('user.create')` en PHP y JS, nunca URLs hardcodeadas.

---

## 4. Sidebar Activo

Usar el helper `url_is()` en `loggedin_sidebar.php`:

- `<li>`: `<?= url_is('ruta*') ? 'active' : '' ?>`
- `<a>` (toggle): `<?= url_is('ruta*') ? '' : 'collapsed' ?>`
- `<ul>` (submenú): `<?= url_is('ruta*') ? 'show' : '' ?>`

---

## 5. DataTableTrait

### Implementación en modelos
Incluir el trait y definir propiedades:

```php
use \App\Traits\DataTableTrait;

private $column_order  = [null, 'name', 'branch_code', 'phone', 'email', 'active', null];
private $column_search = ['name', 'branch_code', 'phone', 'email'];

public function get_datatables($postData)
{
    $this->_get_datatables_query($postData);
    if ($postData['length'] != -1) {
        $this->limit($postData['length'], $postData['start']);
    }
    return $this->findAll();
}
```

Para JOINS complejos, sobrescribir `_get_datatables_query($postData)`.

---

## 6. DataTables Guard (Inicialización Segura)

Todo script de DataTable **debe** iniciar con:

```javascript
if ($.fn.dataTable.isDataTable('#mi-tabla')) return;
```

Esto previene el error `Cannot reinitialise DataTable` al cambiar de tema.

---

## 7. Seguridad y Permisos (Shield CI4)

Tres niveles:

1. **Filtro global `session`** → Grupo de rutas protegidas en Routes.php.
2. **Filtro granular** `'filter' => 'permission:permiso.accion'` por ruta.
3. **Reactividad en vista** con `auth()->user()->can('permiso')` para ocultar botones/menús.

---

## 8. Bitácora de Auditoría

**Obligatorio** registrar en `UserActivityLogsModel` para:

- Inicios/cierres de sesión.
- Creación, edición o eliminación de registros.
- Cambios de contraseña, permisos o roles.

```php
$logModel = new \App\Models\Users\UserActivityLogsModel();
$logModel->logActivity('delete_user', 'Eliminó al usuario: ' . $user->username . ' (ID: ' . $userId . ')');
```

---

## 9. MVC y Transacciones de Base de Datos

### Prohibido
El controlador **nunca** debe usar `\Config\Database::connect()` para transacciones manuales.

### Correcto
Delegar la transacción al **Modelo principal** o a una **clase de Servicio**:

```php
// Controlador
if (!$this->profileModel->createWorkerWithEmployment($profileData, $employmentData)) {
    return $this->setOutputError('Error al guardar el trabajador.');
}
```

```php
// Modelo
public function createWorkerWithEmployment(array $profileData, array $employmentData): bool
{
    $this->db->transStart();
    $this->insert($profileData);
    $employmentModel = new \App\Models\HR\HrEmploymentModel();
    $employmentModel->insert($employmentData);
    $this->db->transComplete();
    return $this->db->transStatus();
}
```

---

## 10. Select2 en Contenedores Dinámicos

Inicializar con `dropdownParent` para modales/offcanvas:

```javascript
$('#mi-select').select2({
    theme: 'bootstrap-5',
    placeholder: 'Seleccione una opción',
    allowClear: true,
    width: '100%',
    dropdownParent: $('#mi-select').parent()
});
```

---

## 11. Gestión de Fechas

- **Base de Datos / Modelos:** ISO 8601 (`YYYY-MM-DD`).
- **DataTables / UI:** `date('d/m/Y', strtotime($fecha))`.
- **Formularios:** `<input type="date">` nativo HTML5.
- Evitar flatpickr a menos que se requiera rango de fechas/horas.

---

## 12. Iconografía (FontAwesome)

- **Estándar primario:** FontAwesome 5/6 (`fas` / `far`).
- **Prohibido** usar librerías JS-based como Lucide para contenido dinámico (AJAX/DataTables) porque no renderizan SVG en contenido insertado posteriormente.

```html
<i class="fas fa-fw fa-info-circle"></i>
```

---

## 13. Breadcrumbs

Usar la clase `App\Libraries\Breadcrumb`:

```php
$this->breadcrumb = new Breadcrumb([
    'Inicio'           => base_url(),
    'Recursos Humanos' => route_to('hr.workers'),
]);

$this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
    'Inicio'           => route_to('dashboard.index'),
    'Recursos Humanos' => route_to('hr.workers'),
    'Editar Trabajador'=> '',
]);
```

---

## 14. Vistas y Layouts

### Estructura de directorios
- `app/Views/Layouts/` → Esqueletos principales.
- `app/Views/Partials/` → Componentes reutilizables (sidebar, topbar, footer).
- `app/Views/{Modulo}/` → Vistas específicas.

### Toda vista debe extender el layout
```php
<?php $this->extend($layout); ?>

<?php $this->section('title'); ?>
<?= esc($headTitle) ?>
<?php $this->endSection(); ?>

<?php $this->section('main'); ?>
<main class="content">
    <!-- Contenido -->
</main>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
    // JS específico
</script>
<?php $this->endSection(); ?>
```

---

## 15. Flujo ViewData y OutputData

### ViewData (HTML)
```php
$this->setViewSuccess('Listado de Módulo');
$this->setPageTittleAhead('Mi Módulo', 'Gestión de Módulo');
$this->viewData['response'] = ['data1' => $modelo->findAll()];
return $this->renderLayout('Layouts/user_loggedin_layout', 'Modulo/mi_vista');
```

### OutputData (JSON/AJAX)
```php
$this->setOutputSuccess('Guardado correctamente');
$this->outputData['csrf'] = csrf_hash();
return $this->response->setJSON($this->outputData);
```

---

## 16. Notificaciones UI (notifyShow)

Usar el wrapper global `notifyShow` para feedback al usuario:

```javascript
if (typeof notifyShow === 'function') {
    notifyShow(res.message, 'success');
} else {
    alert(res.message);
}
```

Tipos comunes: `'success'`, `'danger'`, `'warning'`, `'info'`.

---

## 17. Subida de Archivos (Uploads)

- Directorio base: `FCPATH . 'uploads/'`.
- Crear subdirectorio por módulo.
- Generar nombres aleatorios: `$file->getRandomName()`.
- Guardar ruta relativa en BD (ej. `uploads/organization/169123.png`).

```php
$logo = $this->request->getFile('logo');
if ($logo && $logo->isValid() && !$logo->hasMoved()) {
    $newName = $logo->getRandomName();
    $logo->move(FCPATH . 'uploads/organization/', $newName);
    $data['logo_path'] = 'uploads/organization/' . $newName;
}
```

---

## 18. Estándares de Modelos

### Soft Deletes
Todo modelo de datos críticos **debe** usar:

```php
protected $useSoftDeletes = true;
protected $deletedField   = 'deleted_at';
```

### Validaciones centralizadas
Definir reglas en el modelo, no en el controlador:

```php
protected $allowedFields = ['name', 'email', 'status'];
protected $validationRules = [
    'email' => 'required|valid_email|max_length[100]',
];
```

### Campos protegidos
```php
protected $protectFields = true;
```
