# Especificaciones Técnicas y TODOs del Proyecto

Este documento describe especificaciones técnicas, convenciones de código y tareas pendientes para el desarrollo del ERP / CRM usando CodeIgniter 4 y el template AppStack.

## 1. Reglas de Peticiones AJAX (Frontend a Backend)

### Validación en el Backend (CodeIgniter 4)
Para proteger endpoints que están diseñados **solo** para ser consumidos vía AJAX (como la carga de un DataTables o un guardado silencioso de formulario), siempre se debe validar usando `$this->request->isAJAX()`. 

Si la solicitud no es AJAX, se debe bloquear el acceso retornando un error `404 Not Found` por razones de seguridad (previene *Information Disclosure* y accesos directos por URL).

**Ejemplo de implementación en el Controlador:**
```php
if (!$this->request->isAJAX()) {
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Endpoint exclusivo para peticiones AJAX.");
}
```

### Consumo en el Frontend (Fetch API)
A diferencia de librerías como jQuery (`$.ajax`), la API nativa `fetch()` de JavaScript **no** envía automáticamente metadatos que identifiquen la petición como AJAX. 

Para que la función `isAJAX()` de CodeIgniter 4 funcione correctamente con `fetch()`, es **paso obligatorio** inyectar explícitamente la cabecera `X-Requested-With` en cada llamada.

**Plantilla obligatoria para llamadas Fetch:**
```javascript
fetch('url_del_endpoint', {
    method: 'POST', // o GET
    headers: {
        'X-Requested-With': 'XMLHttpRequest' // <-- OBLIGATORIO PARA QUE CI4 LO DETECTE COMO AJAX
    },
    body: formData // Si aplica
})
.then(response => {
    // Si usas protección CSRF, recuerda actualizar el token aquí
    const newCsrfHash = response.headers.get('<?= csrf_header() ?>');
    if (newCsrfHash) {
         document.getElementById('csrf_token').value = newCsrfHash;
    }
    return response.json();
})
.then(data => {
    // Lógica del frontend
});
```

*(Nota: Si usas jQuery AJAX estricto o las opciones nativas de DataTables configurando `ajax: "url"`, esto no es necesario ya que jQuery incrusta la cabecera `X-Requested-With` por defecto).*

---

## 2. Renovación Automática del Token CSRF

Debido al filtro personalizado `CsrfTokenFilter.php`, cada vez que se realiza una petición segura (POST, PUT, DELETE, PATCH), CodeIgniter y el Filtro quemarán el Token CSRF actual y enviarán uno **NUEVO** en las cabeceras (`Headers`) de la respuesta.

El Frontend **siempre** debe atrapar ese nuevo token de los *Headers* y actualizar silenciosamente el `<input type="hidden">` del formulario actual para evitar que futuras peticiones AJAX (sin recarga de página) tiren un error 403 (*The action you requested is not allowed*). Ver el bloque `.then(response...)` del ejemplo Fetch de arriba.

---

## 3. Enrutamiento (Routes) para Peticiones Fetch/AJAX

En CodeIgniter 4, las rutas que van a recibir consultas AJAX o Fetch deben configurarse con las mismas reglas de seguridad que una API REST.

### A. Uso estricto de Verbos HTTP
Nunca utilices `$routes->match()` ni `$routes->any()` ni `$routes->get()` (si vas a modificar datos) para endpoints AJAX.
Debes especificar el Verbo HTTP exacto que el `fetch()` disparará.

- **Datos de listados (DataTables, selects, filtros):**
  Siempre usa `get()`. Ej. `$routes->get('lista_ajax', 'Users\UserController::lista_ajax');`
- **Inserciones (Guardar un modal):**
  Siempre usa `post()`. Ej. `$routes->post('create', 'Users\UserController::create');`
- **Actualizaciones (Ediciones de perfil):**
  Usa `post()` o `put()`. Ej. `$routes->post('update/(:num)', 'Users\UserController::update/$1');`
- **Eliminación (Borrados suaves o completos):**
  Usa `post()` o `delete()`.

### B. Evitar rutas automáticas y preferir alias (`as =>` )
Tal como se implementó en `user.create`, **siempre** asigna un alias a tu ruta AJAX con el parámetro `['as' => 'nombre.ruta']`.
Esto te permitirá cambiar la estructura de la URL en el futuro (Ej: pasar de `/nat/user/create` a `/api/v1/user/create`) **SIN tener que abrir todos los archivos de Javascript** para actualizar la URL, ya que en el frontend (vistas de CI4) lo estarás llamando dinámicamente:
`<script> fetch('<?= route_to('user.create') ?>', ...) </script>`

> [!TIP]
> **Gestión de Sidebar Activo (Técnico)**
> Los enlaces del sidebar se gestionan dinámicamente en `loggedin_sidebar.php`. Para que una sección (dropdown o link) se mantenga abierta y marcada como activa, utilizamos el helper `url_is()` de CodeIgniter 4:
> - **Para el contenedor `li`**: Usamos `<?= url_is('ruta*') ? 'active' : '' ?>`.
> - **Para el link `a` (toggle)**: Usamos `<?= url_is('ruta*') ? '' : 'collapsed' ?>` para que la flecha indique que está abierto.
> - **Para el submenú `ul`**: Usamos `<?= url_is('ruta*') ? 'show' : '' ?>` para que se mantenga expandido al cargar.


---

## 5. Patrón Arquitectónico para DataTables (DataTableTrait)

Para evitar duplicar código (Paginación, OrderBy, Like, Offset, Limit) en cada uno de los modelos que usen DataTables en el servidor, se implementó un `Trait` llamado `DataTableTrait` (`app/Traits/DataTableTrait.php`).

**Implementación en Modelos:**
1. Asegurarse que el modelo extienda de Model (o ShieldUserModel) e incluir el trait:
   ```php
   use \App\Traits\DataTableTrait;
   ```
2. Definir la propiedad protegida obligatoria `$datatableConfig` que maneja las columnas que se pueden mostrar y buscar.
3. Si el modelo es sencillo (1 sola tabla base de CI), el Trait hace todo automático. Solo se requiere llamar `$this->tuModelo->get_datatables($this->request->getPost())` desde el controlador.
4. Si el modelo usa `JOINs` complejos o **filtros personalizados adjuntos desde la vista** (ej. dropdown de status), se debe sobrescribir el método protegido `_get_datatables_query()`:

```php
    protected function _get_datatables_query($postData = [])
    {
        // 1. Escribir Selects y Joins personalizados
        $this->builder()
            ->select('orders.*, customers.name as custom_name')
            ->join('customers', 'customers.id = orders.customer_id', 'left');

        // 2. Aplicar filtros personalizados (enviados desde JS `ajax.data`)
        if (!empty($postData['status'])) {
            $this->builder()->where('orders.status', $postData['status']);
        }

        // 3. (Obligatorio) Llamar la lógica genérica del Trait para la búsqueda global (like) y orderBy 
        $this->_apply_datatables_filters($postData);
    }
```
> [!NOTE]
> Esto permite que el Controlador reciba la petición y le pase pasivamente `$postData` al modelo, evitando acoplamiento y permitiendo un alto nivel de escalabilidad y DRY (Don't Repeat Yourself) para futuras UI's (Ej. CRM, Órdenes).

---
## 6. Edición y listado de usuarios
- [x] En el listado de usuarios, es necesario cambiar el col-x-4 en el lateral derecho, con la informacion del usuario, por un offcanvas(similar a como lo tenemos en chisa recubrimientos).
- [x] Extender la tabla de data tables, con el listado de usuarios a toda la pantalla.
- [x] Agregar botones de acción en el listado de usuarios (editar, eliminar, ver , ver más información con icono de ojo).
- [x] El botón "ver más información" (icono de ojo), debe abrir el offcanvas y cargar la infoción del usuario de manera dinámica. Recuerda usar nuestros patrones de diseño asi como funciones como OutputData, etc. El offcanvas debe traer los datos del usario(puedes basarte en el template que ya existe) y cambiar o agregar la informacion necesaria. Ademas aqui puedes agregar el botón de editar permisos, y el listado de actividad del usuario.
- [x] Crear el modal con la opción para editar los datos del usuario como nombre, email, contraseña, etc. Crear o editar la funcionón para editar al usuario(recuerda usuar el registro de auditoria o logs) y dejarlo funcionando. 
- [x] Crear la sección de "Actividad reciente" del usuario, en la ruta "/nat/user/activity/(:num)" y que reciba el id del usuario. Debe mostrar una tabla de data tables, siguiendo nuestra arquitectura de data tables, mostrando todos los movimientos del usuario. En la parte superior del listado se deben tener los filtros correspondientes para filtrar por fecha, modulo, acción, etc. No olvidar el breadcrumb. 
- [x] Crear en la parte superior del listado del "actividad reciente" dentro del offcanvas del usuario, un enlace a la sección para ver todos los movimientos del usuario que se creo en el punto anterior


## 7. Crear ruta con formulario para editar los permisos del usuario. 
- [x] Crear la ruta "nat/user/permissions/(:num)" con el formulario para editar los permisos del usuario, asignar o eliminar- Puedes basarte en como usamos los permisos de chisa ERP, en el cual dimos un checkbox para cada permiso, permitiendo marcar o desmarcarlos para el usuario asignado. Recuerda que los usuarios ya tienen un rol con permisos preestablecidos, los cuales deben estar marcados sin poder desmarcarlos. Esta sección permitira únicamente agregar o quitar permisos adicionales al usuario, ajeno a su rol.Recuerda usar la arquitectura de ViewData o Output data, templates y partials que usamos anteriormente.
- [x] Una vez validado esto, agregaremos en el filter de codeigniter 4 la revisión de los permisos del usuario para restringir el acceso a las rutas(controladores, metodos etc) que no tiene permiso de acceder(filters de codeigniter). Esta parte es critica por lo que lo haremos paso a paso, con revisión manual de cada movimiento que se realice para desarrollar esta implementación 
- [x] Corroborar que metodos y acciones existentes (crear, editar usuarios, mostrar datos del usuario, etc) esten vigiladas por el inicio de sesion y permisos de shield. 




## 8. Validar inicio de sesión.
Actualmente el sistema ya tiene el login con la validación de Shield, por lo que es necesario validar lo siguiente:
- [x] Que los permisos y accesos funcionen correctamente con base en el sistema de filtros creados en el punto 7.
- [x] Validar que un usuario sin loguearse, no pueda acceder a las secciones internas del sistema, esto mediante las funciones de shield o funcionalidades de filtros.
- [x] Validar manualmente  que un usuario con permisos limitados, sea redirigido al dashboard o una vista con error 403, sin que pueda ver o ejecutar tareas no permitidas.
- [x] Validar detalladamente la integridad y seguridad del sistema.





---

## 9. Implementación de Avatar Dinámico y datos de sesión, configuración de perfil.

Actualmente, el sistema utiliza un avatar estático de marcador de posición (`avatar.jpg`) en el listado de usuarios y perfiles. 

- [] La arquitectura de usuarios tiene una tabla adicional con referencia o llave foranea a la tabla de usuarios, la cual tiene por nombre "data_contact_user" con el id, users_id(fk), contact_source(enum: phone_number, mobile_number, email, facebook, github, linkedin) y contac_value. Necesito agregar el formulario para que al agregar o editar al usuario, me permita agregar estos datos (relacion uno a muchos pues un usuario puede tener varios medios de contacto), ademas de que el mismo usuario pueda editarlos y agregarlos desde su perfil .


**Tareas Completadas:**
- [x] Verificar como se almacena el avatar del usuario y cargarlo con la sesión junto con el nombre del usuario en el topbar. Se carga en cada request desde `auth()->user()` con fallback a imagen por defecto.
- [x] Generar el enlace de cerrar sesión (sign out) de manera correcta en el loggedin_topbar.
- [x] Crear la ruta `/nat/user/profile` con el formulario para editar los datos del usuario y mostrar los datos actuales. Los datos no editables como el rol solo aparecen de manera informativa. 
- [x] Verificar y mantener en sesión la opción del tema (dark o light) usando `localStorage`. Se corrigó el error en DataTables (ver Nota Técnica abajo).




**Tareas Pendientes:**
- [x] Validar que el inicio de sesión de shield funcione correctamente, se tiene la ruta ya configurada en Routes y la vista personalizada del formulario de inicio de sesión. (Validado mediante revisión de código estático).
- [x] Validar que se genere correctamente la sesión. 
- [x] Validar de manera manual el acceso a rutas protegidas y no protegidas, para corroborar que el inicio de sesión y los permisos de shield funcionen correctamente. 

**Nota Técnica — DataTables: Guard contra Re-inicialización**

Al cambiar el tema (claro/oscuro) con el toggle de AppStack, el evento de click se propagaba hacia `app.js` del template y disparaba una re-inicialización de los DataTables, causando el error: `DataTables warning: Cannot reinitialise DataTable`.

**Solución aplicada (doble capa):**
1. **`e.stopPropagation()`** en el handler del toggle en `loggedin_topbar.php` — bloquea globalmente que AppStack ejecute su propio handler. Aplica a **todas** las páginas automáticamente.
2. **Guard `isDataTable()` al inicio de cada DataTable** — Añadir siempre esta línea antes de inicializar cualquier tabla:
   ```js
   if ($.fn.dataTable.isDataTable('#id-de-la-tabla')) return;
   ```

> ⚠️ **Regla para futuros DataTables:** Cada nueva tabla inicializada en el sistema debe incluir este guard como primera línea de su función de inicialización para evitar el error al cambiar tema o ante cualquier otra re-ejecución accidental del script.

**Nota Técnica sobre Seguridad y Filtros (Shield CI4)**:
1. **Filtro Global de Sesión (`session`)**: Para evitar que usuarios sin autenticar accedan al dashboard o API usando peticiones directas, se agrupó toda la lógica del módulo administrativo interno (después de registrar las rutas de login de shield) en un subgrupo de rutas en `Routes.php` con el filtro maestro `['filter' => 'session']`. Shield intercepta la petición; si no hay estado de login validado en el sistema, no llega al controlador y provoca un Redireccionamiento inmediato al inicio de sesión (302 a `/nat/login`).
2. **Filtro Granular de Permisos (`permission:xyz`)**: Se asignó de manera táctica el filtro a nivel individual (o de grupo) a las subrutas del panel. Ejemplo: `'filter' => 'permission:users.delete'` para borrar usuarios o `permission:admin.manage-users` para ver el listado. Shield lee la matriz de asignaciones (tanto del rol, como personales) en el objeto del Usuario; si este carece del requerimiento, interrumpe el acceso y dirige automáticamente al punto de denegación preconfigurado (error 403 / Forbidden), protegiendo todos los verbos HTTP.
3. **Reactividad UX/UI Condicional**: Además de bloquear a nivel Controlador/Router, se envuelve el HTML y botones (como en el *Sidebar*, los botones del *DataTable* y del *Offcanvas*) usando la función nativa `auth()->user()->can('permiso')`. Así, la plataforma jamás muestra elementos o menús con los que el usuario en sesión no tenga el nivel necesario para interactuar, mejorando la usabilidad y ofreciendo un entorno "limpio".



