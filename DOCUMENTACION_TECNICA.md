# Documentación Técnica del Proyecto (ERP / CRM)

Este documento centraliza todas las especificaciones técnicas, convenciones de desarrollo y guías de arquitectura para el mantenimiento y evolución del sistema basado en CodeIgniter 4 y AppStack.

---

## 📑 Índice de Contenidos

1. [Reglas de Peticiones AJAX (Frontend a Backend)](#1-reglas-de-peticiones-ajax)
2. [Gestión de Seguridad CSRF](#2-gestión-de-seguridad-csrf)
3. [Enrutamiento y Convenciones de URL](#3-enrutamiento-y-convenciones-de-url)
4. [Lógica de Interfaz de Usuario (Sidebar Activo)](#4-lógica-de-interfaz-de-usuario-sidebar-activo)
5. [Arquitectura de Datos: DataTableTrait](#5-arquitectura-de-datos-datatabletrait)
6. [Prevención de Errores en UI (DataTables Guard)](#6-prevención-de-errores-en-ui-datatables-guard)
7. [Seguridad y Permisos (Shield CI4)](#7-seguridad-y-permisos-shield-ci4)
8. [Bitácora de Auditoría y Registro de Actividad](#8-bitácora-de-auditoría-y-registro-de-actividad)
9. [Estándar MVC y Transacciones de Base de Datos](#9-estandar-mvc-y-transacciones-de-base-de-datos)
10. [Integración de Componentes UI: Select2](#10-integración-de-componentes-ui-select2)
11. [Gestión de Fechas (Backend vs UI)](#11-gestión-de-fechas-backend-vs-ui)
12. [Estándar de Iconografía (FontAwesome)](#12-estándar-de-iconografía-fontawesome)
13. [Estructura de Navegación: Breadcrumbs](#13-estructura-de-navegación-breadcrumbs)
14. [Gestión de Vistas y Layouts (Templates, Partials, Layouts)](#14-gestión-de-vistas-y-layouts)
15. [Flujo de Datos del Controlador (ViewData y OutputData)](#15-flujo-de-datos-del-controlador-a-la-vista-viewdata-y-outputdata)
16. [Ampliación: Uso avanzado de DataTableTrait](#16-ampliacion-datatabletrait)
17. [Notificaciones UI (tools.showAlert)](#17-notificaciones-ui-toolsshowalert)
18. [Almacenamiento de Archivos (Uploads)](#18-almacenamiento-de-archivos-uploads)
19. [Estándares Adicionales en Modelos](#19-estandares-adicionales-en-modelos)
20. [Extensión de Catálogos de Datos (Tipos de Servicio)](#20-extensión-de-catálogos-de-datos-tipos-de-servicio)
21. [Sistema de Alertas y Notificaciones](#21-sistema-de-alertas-y-notificaciones)

---

<a name="1-reglas-de-peticiones-ajax"></a>
## 1. Reglas de Peticiones AJAX (Frontend a Backend)

Para garantizar la seguridad y el correcto funcionamiento de los endpoints dinámicos, se deben seguir estas reglas estrictas en el flujo de comunicación.

### A. Validación en el Backend (CodeIgniter 4)
Todos los endpoints diseñados exclusivamente para consumo asíncrono (DataTables, selects dinámicos, guardados silenciosos) deben ser protegidos en el controlador.

> [!IMPORTANT]
> Si una solicitud no es detectada como AJAX, se debe lanzar una excepción `404 Not Found`. Esto oculta la existencia del endpoint ante accesos directos por URL y previene la exposición accidental de datos.

**Ejemplo de implementación:**
```php
if (!$this->request->isAJAX()) {
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Endpoint exclusivo para peticiones AJAX.");
}
```

### B. Consumo en el Frontend (Fetch API)
Al usar la API nativa `fetch()` de JavaScript, es obligatorio inyectar manualmente la cabecera que identifica la petición, ya que de lo contrario el backend no la reconocerá como AJAX.

**Plantilla obligatoria de cabeceras:**
```javascript
headers: {
    'X-Requested-With': 'XMLHttpRequest' // Requerido para $this->request->isAJAX()
}
```

---

<a name="2-gestión-de-seguridad-csrf"></a>
## 2. Gestión de Seguridad CSRF y Renovación de Tokens

El sistema utiliza un filtro de seguridad personalizado (`CsrfTokenFilter.php`) que implementa una política de regeneración estricta.

> [!NOTE]
> Cada petición exitosa (POST, PUT, DELETE) invalida el token anterior y genera uno nuevo que se envía en las cabeceras de respuesta.

**Obligación del Desarrollador:**
El frontend debe capturar siempre el nuevo hash desde el header `<?= csrf_header() ?>` y actualizar el campo oculto del formulario. Esto evita errores `403 Forbidden` en peticiones subsecuentes.

### A. Estándar de Implementación en Vistas
Para asegurar que las peticiones AJAX (especialmente DataTables) siempre tengan acceso al token, se debe incluir un input hidden al inicio de la sección `main` de la vista, **independientemente de si existen modales o no**:

```html
<!-- Al inicio de la sección main -->
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token">
```

### B. Integración con DataTables
En la inicialización del DataTable (POST), se debe inyectar el token y, lo más importante, **escuchar el evento `xhr.dt`** para actualizar el input global con el nuevo token devuelto por el servidor:

```javascript
window.miTabla = $('#tabla').DataTable({
    ajax: {
        url: '...',
        type: 'POST',
        data: function (d) {
            // Inyectar token actual del input global
            d['<?= csrf_token() ?>'] = document.getElementById('csrf_token').value;
        }
    },
    // ...
});

// ESCUCHAR RENOVACIÓN (Crucial para evitar 403 en el siguiente clic)
$('#tabla').on('xhr.dt', function (e, settings, json, xhr) {
    if (xhr && xhr.getResponseHeader('<?= csrf_header() ?>')) {
        document.getElementById('csrf_token').value = xhr.getResponseHeader('<?= csrf_header() ?>');
    }
});
```

### C. Integración con Fetch API y Formularios (FormData)
Cuando se envían formularios nativos mediante `fetch()` utilizando `FormData`, enviar el token únicamente en el cuerpo de la petición puede causar conflictos si el payload es complejo (ej. subida de archivos múltiples). 

Por regla general en este proyecto, toda petición `fetch()` que realice mutaciones (POST, PUT, DELETE) debe inyectar el token CSRF explícitamente en las **cabeceras HTTP**, además de actualizarlo en la respuesta.

**Estándar de implementación:**
```javascript
// 1. Obtener el token actual del DOM
const currentToken = document.getElementById('csrf_token').value;

fetch(url, {
    method: 'POST',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        // 2. Inyección estricta en Header
        '<?= csrf_header() ?>': currentToken 
    },
    body: formData // El formData se envía normal
})
.then(response => {
    // 3. Capturar y renovar el token de inmediato
    const newToken = response.headers.get('<?= csrf_header() ?>');
    if (newToken) {
        document.getElementById('csrf_token').value = newToken;
    }
    return response.json();
})
```

---


<a name="3-enrutamiento-y-convenciones-de-url"></a>
## 3. Enrutamiento y Convenciones de URL

El archivo `Routes.php` debe reflejar una estructura limpia y semántica, similar a una API REST, para asegurar la mantenibilidad.

### A. Uso de Verbos HTTP
- **Consultas/Listados:** Usar siempre `$routes->get()`.
- **Creación/Guardado:** Usar siempre `$routes->post()`.
- **Edición/Actualización:** Usar `$routes->post()` o `$routes->put()`.
- **Eliminación:** Usar `$routes->post()` o `$routes->delete()`.

### B. Alias de Rutas (Named Routes)
Es **obligatorio** asignar un nombre a cada ruta mediante el parámetro `as`.
```php
$routes->post('create', 'Users\UserController::create', ['as' => 'user.create']);
```
**Ventaja:** Permite invocar la ruta desde PHP o JS usando `route_to('user.create')`. Si la URL cambia en el futuro (ej. de `/nat/user/create` a `/admin/user/add`), no será necesario editar ningún archivo de JS o Vista.

---

<a name="4-lógica-de-interfaz-de-usuario-sidebar-activo"></a>
## 4. Lógica de Interfaz de Usuario (Sidebar Activo)

La gestión del menú lateral se realiza en `loggedin_sidebar.php` de forma reactiva. Se utiliza el helper `url_is()` de CodeIgniter 4 para comparar la URL actual y aplicar clases CSS de Bootstrap.

- **Contenedor `li`:** `<?= url_is('ruta*') ? 'active' : '' ?>`
- **Link `a` (Flecha toggle):** `<?= url_is('ruta*') ? '' : 'collapsed' ?>`
- **Submenú `ul`:** `<?= url_is('ruta*') ? 'show' : '' ?>` (Mantiene el menú abierto al navegar entre subsecciones).

---

<a name="5-arquitectura-de-datos-datatabletrait"></a>
## 5. Arquitectura de Datos: DataTableTrait

Para centralizar la lógica de paginación, búsqueda y ordenamiento de tablas grandes, se utiliza el componente `app/Traits/DataTableTrait.php`.

### Implementación en el Modelo:
1. Incluir el trait con `use \App\Traits\DataTableTrait;`.
2. Definir la propiedad `$datatableConfig` con las columnas permitidas para búsqueda/orden.
3. Para consultas con JOINS complejos o filtros extra (ej. por estatus), se debe sobrescribir el método `_get_datatables_query($postData)`.

> [!TIP]
> Al separar la lógica de consulta en el Trait, el Controlador se mantiene "delgado", encargándose solo de pasar los datos del POST al modelo y devolver el JSON formateado.

---

<a name="6-prevención-de-errores-en-ui-datatables-guard"></a>
## 6. Prevención de Errores en UI (DataTables Guard)

El template AppStack dispara eventos al cambiar de tema (Modo Oscuro) que pueden re-inicializar scripts por error, causando colisiones en DataTables.

### Estrategia de Protección:
1. **Doble Capa:** Se implementó `e.stopPropagation()` en el toggle del menú superior para evitar que los clics lleguen a los scripts base de AppStack.
2. **Guardia de Inicialización:** Cada script de tabla debe comenzar con la verificación de existencia:
   ```javascript
   if ($.fn.dataTable.isDataTable('#mi-tabla')) return;
   ```
> [!WARNING]
> No omitir esta línea en nuevas tablas, ya que previene el error crítico `Cannot reinitialise DataTable`.

---

<a name="7-seguridad-y-permisos-shield-ci4"></a>
## 7. Seguridad y Permisos (Shield CI4)

La seguridad se gestiona en tres niveles concéntricos:

1. **Filtro Global (`session`):** Protege todo el grupo de rutas administrativas en `Routes.php`. Si no hay sesión válida, se redirige automáticamente al login central.
2. **Filtro Granular (`permission:xyz`):** Aplicado a nivel de ruta para acciones sensibles.
   - Ejemplo: `'filter' => 'permission:users.delete'` garantiza que solo usuarios autorizados ejecuten el borrado.
3. **Reactividad en la Vista:** Se utiliza `auth()->user()->can('permiso')` para ocultar botones o menús del DOM. Esto asegura que el usuario nunca vea opciones con las que no tiene permiso para interactuar.

---

<a name="8-bitácora-de-auditoría-y-registro-de-actividad"></a>
## 8. Bitácora de Auditoría y Registro de Actividad

Para garantizar la rastreabilidad de las acciones sensibles dentro del sistema, es **obligatorio** registrar en la bitácora de auditoría cualquier operación que mutue datos o represente un evento de seguridad.

### Operaciones Críticas Obligatorias
Se deben registrar siempre las siguientes acciones:
- **Autenticación:** Inicios y cierres de sesión.
- **Mutación de Datos:** Creación, edición o eliminación (física o lógica) de registros en la base de datos.
- **Seguridad:** Cambios de contraseñas, edición de permisos o asignación de roles.

### Estándar de Implementación
El registro se realiza a través del modelo `UserActivityLogsModel`. La firma del método es `logActivity(string $type, string $description, int $userId = null)`.

**Ejemplo de implementación (basado en `UserController`):**
```php
// 1. Instanciar el modelo de bitácora
$logModel = new \App\Models\Users\UserActivityLogsModel();

// 2. Registrar la acción justo antes del éxito de la operación
$logModel->logActivity('delete_user', 'Eliminó al usuario: ' . $user->username . ' (ID: ' . $userId . ')');
```

---

<a name="9-estandar-mvc-y-transacciones-de-base-de-datos"></a>
## 9. Estándar MVC y Transacciones de Base de Datos

### El Problema de las Transacciones en Controladores
De acuerdo a los principios estrictos de arquitectura MVC que rigen este proyecto, un Controlador **nunca debe interactuar directamente con la capa de conexión de base de datos** invocando métodos como `$db = \Config\Database::connect();`. El controlador actúa únicamente como orquestador de tráfico HTTP.

### Transacciones Multi-Tabla (El Estándar Correcto)
Cuando una operación de negocio requiere mutar datos en múltiples tablas de forma atómica (usando `$db->transStart()` y `$db->transComplete()`), esta lógica de infraestructura **NO debe residir en el Controlador**. 

Para resolver esto, la responsabilidad debe delegarse al **Modelo Principal** de la operación o a una clase de Servicio (Service Pattern).

**Ejemplo Incorrecto (Antipatrón en Controlador):**
```php
// ❌ PROHIBIDO EN CONTROLADORES
$db = \Config\Database::connect();
$db->transStart();
$modeloA->insert($dataA);
$modeloB->insert($dataB);
$db->transComplete();
```

**Ejemplo Correcto (Delegación al Modelo):**
El controlador simplemente llama a un método personalizado del modelo que agrupa la lógica:
```php
// ✅ Controlador delegando la responsabilidad (Capa HTTP pura)
if (!$this->profileModel->createWorkerWithEmployment($profileData, $employmentData)) {
    return $this->setOutputError('Error al guardar el trabajador.');
}
```

Y dentro del Modelo Principal (ej. `HrProfileModel.php`), se utiliza la instancia `$this->db` nativa del modelo para manejar la transacción:
```php
// ✅ Modelo encapsulando la transacción multi-tabla (Capa de Datos)
public function createWorkerWithEmployment(array $profileData, array $employmentData): bool
{
    $this->db->transStart();
    $this->insert($profileData); // Inserta en su propia tabla
    
    // Instancia el modelo secundario para la inserción vinculada
    $employmentModel = new \App\Models\HR\HrEmploymentModel();
    $employmentModel->insert($employmentData); 
    
    $this->db->transComplete();
    return $this->db->transStatus(); // Devuelve true/false según el éxito
}
```

> [!IMPORTANT]
> Es un mandato arquitectónico que cualquier controlador que actualmente implemente transacciones manuales con `\Config\Database::connect()` (incluyendo módulos legado) sea refactorizado para mover dicha lógica a la capa de Modelos.

---

<a name="10-integración-de-componentes-ui-select2"></a>
## 10. Integración de Componentes UI: Select2

Para mantener la consistencia visual y funcional de los selects dinámicos (especialmente con bases de datos grandes), se utiliza la librería Select2 integrada con el tema de Bootstrap 5 de AppStack.

### Consideraciones en Entornos Dinámicos (Modales/Offcanvas)
Si un Select2 se renderiza dentro de un contenedor dinámico (como un modal), pierde el contexto del `z-index` y su buscador deja de funcionar correctamente. Es obligatorio inicializarlo indicando el contenedor padre:

```javascript
$('#mi-select').select2({
    theme: 'bootstrap-5',
    placeholder: 'Seleccione una opción',
    allowClear: true,
    width: '100%',
    dropdownParent: $('#mi-select').parent() // Soluciona errores de foco y z-index
});
```

---

<a name="11-gestión-de-fechas-backend-vs-ui"></a>
## 11. Gestión de Fechas (Backend vs UI)

Es imperativo mantener una separación estricta en el formato de fechas:

*   **Capa de Datos (Base de Datos / Modelos):** Siempre en formato ISO 8601 (`YYYY-MM-DD`).
*   **Capa de Presentación (DataTables / UI):** Formateado localmente (`DD/MM/YYYY`) usando `date('d/m/Y', strtotime($fecha))`.
*   **Formularios de Ingreso:** Mantener el uso del input nativo HTML5 `<input type="date">`. Esto garantiza que los navegadores móviles desplieguen el selector nativo del SO, mejorando exponencialmente la UX sin depender de pesadas librerías de terceros (como flatpickr) a menos que se requiera rango de fechas/horas específico.

---

<a name="12-estándar-de-iconografía-fontawesome"></a>
## 12. Estándar de Iconografía (FontAwesome)

En este ecosistema basado en AppStack, aunque existan librerías secundarias (como Lucide o Feather), el **estándar primario y obligatorio es FontAwesome 5/6**.

### Directiva de Uso
Utilizar siempre la familia Solid (`fas`) o Regular (`far`) según el peso requerido. 
**Ejemplo:** `<i class="fas fa-fw fa-info-circle"></i>`

> [!WARNING]
> **Evitar librerías JS-based como Lucide** para componentes que se renderizan vía AJAX o DataTables. Dado que estas librerías inyectan un SVG usando JavaScript al cargar la página, no detectan contenido dinámico insertado posteriormente a menos que se re-ejecute manualmente su analizador del DOM, introduciendo posibles fugas de rendimiento y complejidad innecesaria.

---

<a name="13-estructura-de-navegación-breadcrumbs"></a>
## 13. Estructura de Navegación: Breadcrumbs

Para asegurar que el usuario mantenga el sentido de orientación en módulos jerárquicos (como Recursos Humanos o Ventas), se debe utilizar la clase de utilidad `App\Libraries\Breadcrumb`.

### Implementación en Controlador
Se instancia en el `initController` con el nivel base, y se complementa en cada vista particular:

```php
// En el constructor/initController:
$this->breadcrumb = new Breadcrumb([
    'Inicio'           => base_url(),
    'Recursos Humanos' => route_to('hr.workers'),
]);

// En el método de la vista (ej: edit()):
$this->viewData['breadcrumb'] = $this->breadcrumb->getBreadCrumbHtml([
    'Inicio'           => route_to('dashboard.index'),
    'Recursos Humanos' => route_to('hr.workers'),
    'Editar Trabajador'=> '', // Nivel actual sin link
]);
```

---

<a name="14-gestión-de-vistas-y-layouts"></a>
## 14. Gestión de Vistas y Layouts (Templates, Partials, Layouts)

El proyecto utiliza un sistema de jerarquía de vistas (Layouts y Partials) nativo de CodeIgniter 4, apoyado por el motor de AppStack.

### A. Estructura de Directorios
*   **`app/Views/Layouts/`**: Contiene los "esqueletos" principales de la aplicación. Ejemplo: `user_loggedin_layout.php` (layout maestro para usuarios autenticados).
*   **`app/Views/Partials/`**: Contiene componentes reutilizables como el `loggedin_sidebar.php` (menú lateral), `loggedin_topbar.php` (barra superior) y `footer.php`.
*   **`app/Views/{Modulo}/`**: Vistas específicas de cada módulo (ej. `Organization/profile.php`).

### B. Uso de Layouts en las Vistas
Toda vista de módulo debe extender un layout maestro y definir las secciones (`title`, `main`, `scripts`):

```php
<?php $this->extend($layout); ?> <!-- $layout se inyecta desde BaseController -->

<?php $this->section('title'); ?>
<?= esc($headTitle) ?>
<?php $this->endSection(); ?>

<?php $this->section('main'); ?>
<main class="content">
    <!-- Contenido HTML de la vista -->
</main>
<?php $this->endSection(); ?>

<?php $this->section('scripts'); ?>
<script>
    // JS específico de la vista
</script>
<?php $this->endSection(); ?>
```

---

<a name="15-flujo-de-datos-del-controlador-a-la-vista-viewdata-y-outputdata"></a>
## 15. Flujo de Datos del Controlador (ViewData y OutputData)

El controlador principal `BaseController` define dos variables globales protegidas para estandarizar el flujo de información hacia las vistas y hacia las respuestas AJAX (JSON): `$viewData` y `$outputData`.

### A. `$viewData` (Para renderizado HTML)
Este arreglo se inicializa automáticamente y contiene propiedades predefinidas como `pageTitle`, `headTitle`, `breadcrumb`, y `response` (para enviar arreglos o modelos a la vista).

**Flujo en Controlador:**
```php
public function index(): string
{
    // 1. Establecer variables usando helpers de BaseController
    $this->setViewSuccess('Listado de Módulo');
    $this->setPageTittleAhead('Mi Módulo', 'Gestión de Módulo');
    
    // 2. Inyectar datos específicos a la vista en la clave 'response'
    $this->viewData['response'] = [
        'data1' => $modelo->findAll()
    ];
    
    // 3. Renderizar usando el Layout maestro
    return $this->renderLayout('Layouts/user_loggedin_layout', 'Modulo/mi_vista');
}
```

### B. `$outputData` (Para respuestas AJAX/JSON)
Similar a `$viewData`, pero destinado exclusivamente a la comunicación de APIs o validaciones de formulario por Fetch/jQuery.

**Flujo en Controlador:**
```php
public function save()
{
    if (!$this->request->isAJAX()) return;
    
    // 1. Operación de negocio
    $modelo->insert($data);
    
    // 2. Formatear la respuesta de éxito
    $this->setOutputSuccess('Guardado correctamente');
    
    // 3. Inyectar el token CSRF renovado obligatoriamente
    $this->outputData['csrf'] = csrf_hash();
    
    // 4. Devolver JSON
    return $this->response->setJSON($this->outputData);
}
```

---

<a name="16-ampliacion-datatabletrait"></a>
## 16. Ampliación: Uso avanzado de DataTableTrait

Como se mencionó en la sección 5, `DataTableTrait` es **el único proceso del ERP que maneja Traits** de forma estandarizada para DataTables Server-Side. 

Para que un modelo funcione con el Trait, **debe** definir estrictamente las propiedades `$column_order` (columnas ordenables) y `$column_search` (columnas buscables).

```php
// En un modelo (ej. OrgBranchModel)
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
*Si se necesitan JOINS, se sobrescribe el método `private function _get_datatables_query($postData)` dentro del mismo modelo.*

---

<a name="17-notificaciones-ui-notifyshow"></a>
## 17. Notificaciones UI (`notifyShow`)

Para mantener consistencia en la retroalimentación al usuario tras peticiones AJAX, el proyecto utiliza un wrapper global en Javascript llamado `notifyShow`. Este reemplaza a las alertas nativas del navegador (`alert()`).

**Estándar de uso en vistas (AJAX success/error):**
```javascript
if (typeof notifyShow === 'function') {
    notifyShow(res.message, 'success'); // Tipos comunes: 'success', 'danger', 'warning', 'info'
} else {
    alert(res.message); // Fallback
}
```

---

<a name="18-almacenamiento-de-archivos-uploads"></a>
## 18. Almacenamiento de Archivos (Uploads)

La subida de archivos (imágenes, documentos legales, PDFs) debe mantener una organización modular en el directorio `public_html/uploads/` o equivalente (`FCPATH . 'uploads/'`).

**Reglas:**
1. Crear un subdirectorio por módulo (Ej. `uploads/organization/`, `uploads/hr/workers/`, `uploads/catalog/products/`).
2. Generar nombres aleatorios para evitar colisiones: `$file->getRandomName()`.
3. Guardar en la base de datos la ruta relativa, no la absoluta (Ej. `uploads/organization/169123.png`).

**Ejemplo en Controlador:**
```php
$logo = $this->request->getFile('logo');
if ($logo && $logo->isValid() && !$logo->hasMoved()) {
    $newName = $logo->getRandomName();
    $logo->move(FCPATH . 'uploads/organization/', $newName);
    $data['logo_path'] = 'uploads/organization/' . $newName;
}
```

---

<a name="19-estandares-adicionales-en-modelos"></a>
## 19. Estándares Adicionales en Modelos

Además de `DataTableTrait`, todos los modelos principales deben adherirse estrictamente a las convenciones de CodeIgniter 4 para seguridad e integridad de datos:

*   **Soft Deletes Obligatorios:** Todo modelo que maneje datos críticos (usuarios, empleados, sucursales, productos) debe usar `$useSoftDeletes = true` y definir la columna `deleted_at`. Nunca borrar físicamente a menos que sea una tabla pivot o de configuración temporal.
*   **Validaciones Centralizadas:** Definir siempre las reglas en `$validationRules` dentro del Modelo, en lugar de validar en el controlador.
*   **Campos Protegidos:** Definir explícitamente `$allowedFields`.

```php
protected $useSoftDeletes   = true;
protected $protectFields    = true;
protected $allowedFields    = ['name', 'email', 'status'];

protected $validationRules = [
    'email' => 'required|valid_email|max_length[100]',
];
```

---

<a name="20-extensión-de-catálogos-de-datos-tipos-de-servicio"></a>
## 20. Extensión de Catálogos de Datos (Tipos de Servicio)

Para mantener la flexibilidad y evitar la sobrecarga de la base de datos con tablas de configuración estáticas, ciertos catálogos de datos como los "tipos de servicio" (`service_type` en `fin_recurring_expenses`) se definen directamente en el Modelo `FinRecurringExpenseModel`.

### A. Estructura y Reglas de Extensión
1. **Definición en el Modelo:** La lista completa de tipos se encuentra centralizada en la constante `FinRecurringExpenseModel::SERVICE_TYPES`.
2. **Agrupación (Opcional):** Para mejorar la UX en selectores `optgroup`, se puede definir la constante `FinRecurringExpenseModel::SERVICE_TYPE_GROUPS`.
3. **Validación Automática:** Al estar definido en el modelo, cualquier nuevo tipo añadido a `SERVICE_TYPES` será automáticamente validado (`in_list`) y estará disponible en el controlador y las vistas sin cambios adicionales en la lógica de procesamiento.

**Ejemplo de cómo extender en el modelo:**
```php
// En app/Models/Financial/FinRecurringExpenseModel.php
public const SERVICE_TYPES = [
    'electricity'     => 'Electricidad (CFE)',
    'water'           => 'Agua',
    'gas'             => 'Gas natural / LP',
    // ... más tipos
    'custom_type'     => 'Mi Nuevo Tipo Personalizado', // Añadir aquí
    'other'           => 'Otro',
];

public const SERVICE_TYPE_GROUPS = [
    'Servicios públicos y utilities' => ['electricity', 'water', 'gas'],
    // ... otros grupos
    'Mis servicios'                  => ['custom_type'], // Agrupar si es necesario
];
```

### B. Beneficios del Estándar
- **Centralización:** Un solo lugar para gestionar los tipos.
- **Consistencia:** Validación y etiquetas uniformes en toda la aplicación.
- **Facilidad de Extensión:** No requiere tocar la base de datos ni controladores/vistas al añadir un nuevo tipo.
- **Seguridad:** El validador del modelo utiliza dinámicamente las claves de la constante para la regla `in_list`.

---

<a name="21-sistema-de-alertas-y-notificaciones"></a>
## 21. Sistema de Alertas y Notificaciones

El sistema de alertas es un componente híbrido diseñado para notificar a los usuarios sobre eventos importantes, combinando alertas persistentes (almacenadas en la base de datos) con alertas dinámicas en tiempo real (generadas on-the-fly, estilo CHISA).

### A. Arquitectura General

El sistema se compone de los siguientes elementos clave:

1.  **Alertas Almacenadas (Manuales):** Son alertas creadas programáticamente por los módulos de negocio a través de `AlertService::dispatch()`. Estas alertas se guardan en la tabla `sys_alerts`, se asignan a usuarios específicos y su estado (`leída`/`no leída`) persiste en la tabla pivote `sys_alert_user`.
2.  **Alertas Automáticas (CHISA-style):** Son alertas generadas en tiempo real por métodos detectores privados en `AlertController.php`. Estas alertas consultan tablas operativas y existen solo mientras la condición persista en la base de datos. No se almacenan y no tienen estado de lectura.
3.  **Frontend (TopBar):** Un componente JavaScript en `loggedin_topbar.php` consulta periódicamente (cada 60 segundos) el backend para obtener las alertas no leídas y las renderiza en un dropdown.

### B. Flujo de Obtención de Alertas (`AlertController::get_unread_ajax()`)

1.  **Obtención de Alertas Almacenadas:** `AlertService::getUnreadByUser()` consulta las alertas persistentes no leídas para el usuario actual.
2.  **Detección de Alertas Automáticas:** `AlertController::_detectAll()` ejecuta una serie de métodos privados (`_detect*()`) que consultan diversas tablas para identificar condiciones que requieren una alerta (ej., trabajadores sin datos, stock bajo). Estos detectores verifican los permisos del usuario antes de ejecutarse.
3.  **Fusión y Límite:** Las alertas almacenadas y automáticas se fusionan (dando prioridad visual a las almacenadas) y se limitan para la visualización en el dropdown.
4.  **Respuesta JSON:** Se devuelve una respuesta JSON unificada con las alertas para el frontend.

### C. Detectores Automáticos

*   Cada detector es un método privado en `AlertController` (ej., `_detectWorkersMissingNss()`).
*   Realiza una consulta directa a la base de datos para verificar una condición específica.
*   Si la condición se cumple, construye un objeto `stdClass` con la estructura de una alerta, marcando `is_auto=1`.
*   Estas alertas no se almacenan; desaparecen cuando la condición subyacente se corrige en la base de datos.
*   Los errores en la ejecución de un detector son capturados (`_safeDetect()`) para no afectar al resto del sistema.
*   **Inclusión en Historial:** Las alertas automáticas se incluyen en la respuesta de `history_ajax()`, permitiendo que aparezcan en la vista de historial completo junto a las alertas almacenadas.

### D. Interacción con el Frontend

#### 1. TopBar (`loggedin_topbar.php`)

*   **Polling:** El JavaScript en `loggedin_topbar.php` realiza una petición AJAX cada 60 segundos a `AlertController::get_unread_ajax()`.
*   **Manejo de Clicks:**
    *   **Alertas Automáticas (`is_auto=1`):** Al hacer click, el sistema solo navega a la URL de destino (`target_url`). No se marcan como leídas porque no están persistidas.
    *   **Alertas Almacenadas (`is_auto=0`):** Al hacer click, el sistema envía una petición AJAX a `AlertController::mark_read_ajax()` para marcar la alerta como leída y luego navega a la `target_url`.
*   **CSRF:** El token CSRF se maneja globalmente en el layout `user_loggedin_layout.php` y se actualiza en cada petición/respuesta AJAX para evitar errores de seguridad.

#### 2. Formato de Respuesta (JSON)

Tanto `get_unread_ajax()` como `history_ajax()` devuelven alertas con el siguiente formato unificado:

```json
{
  "id": 0,
  "pivot_id": 0,
  "title": "Proveedores sin RFC",
  "message": "3 proveedores sin RFC registrado",
  "type": "warning",
  "icon": "fa-file-invoice",
  "target_url": "/nat/suppliers",
  "module": "Proveedores",
  "is_auto": 1,
  "is_read": 0,
  "created_at": "2026-06-12 18:30:00"
}
```

*   **`is_auto=1`:** Alerta automática (CHISA-style). `id` y `pivot_id` son siempre `0`.
*   **`is_auto=0`:** Alerta almacenada. `id` y `pivot_id` contienen los IDs reales de las tablas `sys_alerts` y `sys_alert_user`.

#### 3. Historial de Notificaciones (`alerts_history.php`)

*   **Fusión de Alertas:** La vista de historial (`AlertController::history_ajax()`) muestra tanto alertas almacenadas como alertas automáticas en una sola tabla. Las alertas automáticas aparecen primero en la página 1.
*   **Identificación Visual:** Cada fila incluye una columna **Tipo** que distingue:
    *   **En vivo** (badge azul `bg-info`): Alertas automáticas detectadas en tiempo real según condiciones de datos del sistema.
    *   **Notificación** (badge gris claro): Alertas almacenadas persistentes despachadas manualmente vía `AlertService::dispatch()`.
*   **Estado Diferenciado:**
    *   Alertas automáticas muestran estado **Activa** (badge amarillo) y fecha **Tiempo real**.
    *   Alertas almacenadas muestran estados **Nueva** / **Leída** con su timestamp real.
*   **Marcado Masivo:** El botón "Marcar notificaciones como leídas" solo afecta a alertas almacenadas. Las alertas automáticas no se pueden marcar como leídas; desaparecen automáticamente cuando se corrige la condición subyacente en la base de datos.
*   **Navegación:** El link "Ver todas las notificaciones" en el dropdown del topbar dirige a esta vista de historial completo.

### E. Cómo Agregar un Nuevo Detector Automático

Para añadir una nueva alerta dinámica (ej., "Órdenes de Compra Pendientes"):

1.  **Definir la Ruta de Destino:** Asegúrate de que exista una ruta nombrada a la que la alerta pueda redirigir (ej., `route_to('purchase_orders.index')`).
2.  **Crear el Método Detector en `AlertController`:**
    *   Añade un nuevo método privado (ej., `_detectPendingPurchaseOrders()`) que contenga la lógica de consulta a la base de datos para detectar la condición.
    *   Asegúrate de que el método devuelva un array de objetos `stdClass` con la estructura de alerta, utilizando `_buildAutoAlert()`.
    *   **Ejemplo:**
        ```php
        private function _detectPendingPurchaseOrders(): array
        {
            $db    = db_connect();
            $count = $db->table('fin_purchase_orders')
                ->where('status', 'pendiente')
                ->where('deleted_at', null)
                ->countAllResults();

            if ($count === 0) return [];

            return [$this->_buildAutoAlert([
                'type'       => 'info',
                'icon'       => 'fa-shopping-cart',
                'title'      => 'Órdenes de compra pendientes',
                'message'    => $count . ' órdenes pendientes de recibir',
                'module'     => 'Compras',
                'target_url' => route_to('purchase_orders.index'),
            ])];
        }
        ```
3.  **Integrar en `_detectAll()`:**
    *   Dentro del método `_detectAll()`, añade una condición de permiso y llama al nuevo detector usando `_safeDetect()`.
    *   **Ejemplo:**
        ```php
        // En AlertController.php, dentro del método _detectAll()
        if ($user->can('purchasing.access')) {
            $alerts = array_merge($alerts, $this->_safeDetect('_detectPendingPurchaseOrders'));
        }
        ```
    *   Asegúrate de que el permiso (`purchasing.access`) sea apropiado para los usuarios que deben ver esta alerta.

### F. Cómo Despachar una Alerta Almacenada (Manual)

Desde cualquier controlador o servicio, puedes usar `AlertService::dispatch()`:

```php
// Ejemplo: Despachar una alerta por un pago vencido a usuarios con permiso 'billing.access'
$alertService = new \App\Libraries\AlertService();
$alertId = $alertService->dispatch(
    title: 'Pago de cliente vencido',
    message: 'El cliente "XYZ" tiene una factura vencida por $1,200 MXN.',
    type: 'danger',
    icon: 'fa-file-invoice-dollar',
    targetUrl: route_to('clients.invoices.show', $clientId, $invoiceId),
    requiredPermission: 'billing.access',
    module: 'Cobranza',
    referenceType: 'invoice',
    referenceId: $invoiceId
);

if ($alertId === 0) {
    // Manejar error si la alerta no pudo ser creada
    log_message('error', 'No se pudo despachar la alerta de pago vencido.');
}
