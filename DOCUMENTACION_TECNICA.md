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

El sistema utiliza un filtro de seguridad personalizado (`CsrfTokenFilter.php`) que implementa una política de regeneración estricta para mitigar ataques de falsificación de peticiones.

> [!NOTE]
> Cada petición exitosa (POST, PUT, DELETE) invalida el token anterior y genera uno nuevo que se envía en las cabeceras de respuesta (`Headers`).

**Obligación del Desarrollador:**
El frontend debe capturar siempre el nuevo hash desde el header `<?= csrf_header() ?>` y actualizar el campo oculto del formulario. Esto evita errores `403 Forbidden` en peticiones subsecuentes sin recarga de página.

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
