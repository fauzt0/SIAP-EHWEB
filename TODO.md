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

---

## 4. Implementación de Avatar Dinámico

Actualmente, el sistema utiliza un avatar estático de marcador de posición (`avatar.jpg`) en el listado de usuarios y perfiles. 

**Tareas Pendientes:**
- [ ] Definir la lógica de almacenamiento de avatares (Carpeta local vs S3/Cloud).
- [ ] Implementar la carga de archivos en el formulario de creación/edición de usuario (`UserController::create` / `UserController::update`).
- [ ] Actualizar el modelo `UserModel` para manejar el campo de ruta del avatar.
- [ ] Modificar la respuesta del DataTables en `UserController::lista_ajax` para que devuelva la URL real del avatar o una inicial generada si el usuario no tiene foto.

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

*(Agrega aquí futuras especificaciones o tareas pendientes del proyecto)*
