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
