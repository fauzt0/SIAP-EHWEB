# Sistema de Alertas y Notificaciones — ERP CHISA

## Descripción General

El sistema de notificaciones es un componente **global** que se muestra en el topbar de todas las páginas del ERP. Su función es agregar y mostrar alertas operativas en tiempo real provenientes de distintos módulos del sistema (Almacén, Ventas, Obras, RH, Compras, Producción), sin que el usuario tenga que navegar a cada sección para enterarse de problemas.

---

## Archivos Involucrados

| Archivo | Rol |
|---|---|
| `public_html/application/views/layouts/topNavbar.php` | HTML del ícono campana y dropdown `#alertsDropdown` |
| `public_html/application/views/layouts/general_template.php` | JavaScript del sistema (polling, renderizado) |
| `public_html/application/controllers/Notifications.php` | Controlador PHP — consulta la BD y devuelve JSON |

> **Nota:** También existe un `wrapper.php` con un `#alertsDropdown` estático (datos hardcodeados en inglés, es el template original del tema AppStack). **No está conectado** al sistema dinámico; el sistema activo es el de `topNavbar.php` + `general_template.php`.

---

## Arquitectura del Sistema

```
Usuario carga cualquier página
        │
        ▼
general_template.php (script JS embebido)
        │
        ├── $(document).ready() ──► loadNotifications()  ◄──────────┐
        │                                   │                         │
        └── setInterval(120 000 ms) ────────┘                         │
                                            │                         │
                                            ▼                         │
                              GET /Notifications/get_notifications    │
                                            │                         │
                                            ▼                         │
                              Notifications.php (CodeIgniter)         │
                              ├── _get_stock_bajo()                   │
                              ├── _get_ordenes_pendientes()           │
                              ├── _get_obras_retrasadas()             │
                              ├── _get_empleados_datos_faltantes()    │
                              ├── _get_ordenes_compra_pendientes()    │
                              └── _get_productos_sin_formulacion()    │
                                            │                         │
                                            ▼                         │
                              array_slice($notifications, 0, 10)      │
                                            │                         │
                                            ▼                         │
                              JSON: {success, total_count, notifications[]}
                                            │
                                            ▼
                              updateNotificationsUI(data)
                              ├── #notifications-badge  (conteo)
                              ├── #notifications-header (texto)
                              └── #notifications-list   (items)
```

---

## Flujo Completo Paso a Paso

### 1. Renderizado del HTML (`topNavbar.php` — Líneas 65–83)

Cuando el servidor entrega la página, el HTML ya incluye el dropdown vacío:

```html
<li class="nav-item dropdown">
  <a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" data-bs-toggle="dropdown">
    <div class="position-relative">
      <i class="align-middle text-body" data-lucide="bell"></i>
      <span class="indicator" id="notifications-badge" style="display: none;"></span>
    </div>
  </a>
  <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" aria-labelledby="alertsDropdown">
    <div class="dropdown-menu-header" id="notifications-header">
      <span class="spinner-border spinner-border-sm me-2"></span> Cargando notificaciones...
    </div>
    <div class="list-group" id="notifications-list">
      <!-- Las notificaciones se cargarán dinámicamente aquí -->
    </div>
    <div class="dropdown-menu-footer">
      <a href="#" class="text-muted" onclick="refreshNotifications(); return false;">
        Actualizar notificaciones
      </a>
    </div>
  </div>
</li>
```

**Estado inicial:** El badge está oculto (`display: none`) y el header muestra un spinner de carga.

---

### 2. Primera Carga del JavaScript (`general_template.php` — Líneas 48–133)

Al cargar el DOM (`$(document).ready()`), se ejecuta automáticamente:

```javascript
$(document).ready(function() {
    loadNotifications();                                       // carga inmediata al entrar
    notificationsInterval = setInterval(loadNotifications, 120000); // refresco cada 2 minutos
});
```

**No hay almacenamiento en caché ni en `localStorage`/`sessionStorage`**. Cada llamada hace una petición fresca al servidor.

---

### 3. Petición Ajax al Backend

```javascript
function loadNotifications() {
    $.get('<BASE_URL>/Notifications/get_notifications', function(response) {
        var data = typeof response === 'string' ? JSON.parse(response) : response;
        if (data.success) {
            updateNotificationsUI(data);
        }
    });
}
```

- **Método:** `GET`
- **URL:** `<base_url>/Notifications/get_notifications`
- **Sin parámetros:** la consulta no filtra por usuario, devuelve alertas globales del sistema
- **Autenticación:** depende de la sesión PHP activa (herencia de `MY_Controller`)

---

### 4. Procesamiento en el Controlador (`Notifications.php`)

El controlador `Notifications` extiende `MY_Controller` y carga dos modelos en el constructor: `UserModel` y `AlmacenModel` (aunque las consultas en `get_notifications` van directamente con `$this->db`).

#### Fuentes de Datos por Módulo

---

#### 🟡 Almacén — Stock Bajo

```
Tablas:      productos, insumos (consultas separadas, resultado unificado con array_merge)
Condición:   stock_actual <= stock_minimo  AND  stock_actual > 0  AND  estatus = 'Activo'
Límite:      5 productos + 5 insumos = hasta 10 notificaciones de esta fuente
Tipo:        'warning'
Icono:       fa-exclamation-triangle
Tiempo:      'Ahora'
Link:        /almacen/Inventario
```

Código clave:
```php
$this->db->where('stock_actual <=', 'stock_minimo', FALSE);
$this->db->where('stock_actual >', 0);
$this->db->where('estatus', 'Activo');
$this->db->limit(5);
```

---

#### 🔴 Ventas — Órdenes Retrasadas

```
Tabla:       ordenes_venta
Condición:   estatus IN ('Confirmada', 'En Proceso')  AND  fecha_entrega_real IS NULL
Límite:      5 órdenes candidatas
Filtro:      Solo se agrega si _calcular_dias_retraso(fecha_entrega_estimada) > 0
Tipo:        'danger'
Icono:       fa-exclamation-circle
Tiempo:      "Xd" (número de días de retraso)
Link:        /ventas/Ordenes
```

> **Importante:** Si `fecha_entrega_estimada` es `NULL` en algún registro, la función devuelve 0 y **no se genera notificación** para esa orden.

Cálculo de días de retraso:
```php
private function _calcular_dias_retraso($fecha_estimada) {
    if (empty($fecha_estimada)) return 0;
    $fecha_est = new DateTime($fecha_estimada);
    $hoy       = new DateTime();
    if ($hoy > $fecha_est) {
        $diff = $hoy->diff($fecha_est);
        return $diff->days;
    }
    return 0;
}
```

---

#### 🟡 Obras — Obras Retrasadas

```
Tabla:       obras
Condición:   estatus IN ('En Ejecución', 'Aprobada')  AND  fecha_fin_estimada < HOY  AND  activo = 1
Límite:      5 obras
Tipo:        'warning'
Icono:       fa-clock
Tiempo:      'Hoy'
Link:        /obras/Obras/detalle/{id}
```

---

#### 🔵 Recursos Humanos — Datos Incompletos

```
Tabla:       empleados
Condición:   estatus = 'Activo'
             AND (nss IS NULL OR nss = '' OR rfc IS NULL OR rfc = '' OR curp IS NULL OR curp = '')
Límite:      5 empleados
Tipo:        'info'
Icono:       fa-user-circle
Tiempo:      'Hoy'
Link:        /rh/RecursosHumanos
Mensaje:     "[Nombre completo] - Falta: NSS, RFC, CURP" (solo los campos ausentes)
```

---

#### 🔵 Compras — Órdenes de Compra Pendientes

```
Tabla:       ordenes_compra
Condición:   estatus IN ('Pendiente', 'En Tránsito')
Límite:      5 órdenes
Agrupación:  Una sola notificación que dice "X órdenes pendientes de recibir"
Tipo:        'info'
Icono:       fa-shopping-cart
Tiempo:      'Hoy'
Link:        /compras/OrdenesCompra
```

---

#### 🟡 Producción — Productos sin Formulación

```
Tablas:      productos p  LEFT JOIN  formulaciones f  ON f.producto_id = p.id
Condición:   f.id IS NULL  AND  p.estatus = 'Activo'
Límite:      5 productos
Agrupación:  Una sola notificación que dice "X productos necesitan formulación"
Tipo:        'warning'
Icono:       fa-box
Tiempo:      'Hoy'
Link:        /produccion/Productos
```

---

### 5. Estructura de la Respuesta JSON

```json
{
  "success": true,
  "total_count": 7,
  "notifications": [
    {
      "type": "warning",
      "icon": "exclamation-triangle",
      "module": "Almacén",
      "title": "Stock bajo",
      "message": "Pintura Blanca tiene solo 3 litros",
      "link": "https://erp.chisarecubrimientos.com.mx/almacen/Inventario",
      "time": "Ahora"
    },
    {
      "type": "danger",
      "icon": "exclamation-circle",
      "module": "Ventas",
      "title": "Orden retrasada",
      "message": "Orden OV-2025-001 con 5 días de retraso",
      "link": "https://erp.chisarecubrimientos.com.mx/ventas/Ordenes",
      "time": "5d"
    }
  ]
}
```

> **Nota sobre `total_count`:** Refleja el total **antes** del corte de 10. Si hay 15 alertas reales, `total_count` = 15 pero `notifications[]` solo tendrá 10 elementos.

---

### 6. Actualización de la UI (`updateNotificationsUI`)

```javascript
function updateNotificationsUI(data) {
    var total = data.total_count || 0;
    var notifications = data.notifications || [];

    // --- Badge del ícono campana ---
    if (total > 0) {
        $('#notifications-header').html(total + ' Notificación' + (total > 1 ? 'es' : ''));
        $('#notifications-badge').text(total > 9 ? '9+' : total).show();
    } else {
        $('#notifications-header').html('Sin notificaciones');
        $('#notifications-badge').hide();
    }

    // --- Lista de notificaciones ---
    var html = '';
    if (notifications.length === 0) {
        html = '<div class="list-group-item text-center text-muted py-4">' +
               '<i class="fas fa-check-circle fa-2x mb-2"></i><br>' +
               'No hay notificaciones pendientes' +
               '</div>';
    } else {
        notifications.forEach(function(notif) {
            var iconClass = getIconClass(notif.type); // color CSS
            html += '<a href="' + notif.link + '" class="list-group-item">' +
                    '<div class="row g-0 align-items-center">' +
                    '  <div class="col-2">' +
                    '    <i class="' + iconClass + ' fas fa-' + notif.icon + '"></i>' +
                    '  </div>' +
                    '  <div class="col-10">' +
                    '    <div><strong>' + notif.module + ':</strong> ' + notif.title + '</div>' +
                    '    <div class="text-muted small mt-1">' + notif.message + '</div>' +
                    '    <div class="text-muted small mt-1">' + notif.time + '</div>' +
                    '  </div>' +
                    '</div>' +
                    '</a>';
        });
    }
    $('#notifications-list').html(html);
}
```

#### Colores por Tipo

| `type`    | Clase CSS       | Color   |
|-----------|-----------------|---------|
| `danger`  | `text-danger`   | Rojo    |
| `warning` | `text-warning`  | Amarillo|
| `info`    | `text-info`     | Azul    |
| `success` | `text-success`  | Verde   |
| _(otro)_  | `text-primary`  | Primario|

---

### 7. Actualización Manual

El footer del dropdown tiene un link:

```html
<a href="#" onclick="refreshNotifications(); return false;">Actualizar notificaciones</a>
```

`refreshNotifications()` muestra el spinner de carga y llama a `loadNotifications()` de inmediato.

---

## Mecanismo de Polling (Auto-refresco)

| Parámetro              | Valor                              |
|------------------------|------------------------------------|
| Carga inicial          | Inmediata al cargar la página      |
| Intervalo de refresco  | **120 000 ms (2 minutos)**         |
| Mecanismo              | `setInterval` en JavaScript        |
| Almacenamiento local   | **Ninguno** (sin caché)            |
| Persistencia entre páginas | **No** — cada página reinicia el intervalo |

---

## Tablas de Base de Datos Consultadas

| Tabla            | Módulo          | Campos clave usados                                      |
|------------------|-----------------|----------------------------------------------------------|
| `productos`      | Almacén / Prod. | `stock_actual`, `stock_minimo`, `estatus`, `nombre`      |
| `insumos`        | Almacén         | `stock_actual`, `stock_minimo`, `estatus`, `nombre_tecnico` |
| `ordenes_venta`  | Ventas          | `folio`, `estatus`, `fecha_entrega_estimada`, `fecha_entrega_real` |
| `obras`          | Obras           | `nombre`, `estatus`, `fecha_fin_estimada`, `activo`      |
| `empleados`      | RH              | `nombre`, `apellido_paterno`, `apellido_materno`, `nss`, `rfc`, `curp`, `estatus` |
| `ordenes_compra` | Compras         | `folio`, `estatus`                                       |
| `formulaciones`  | Producción      | `producto_id` (join con `productos`)                     |

---

## Limitaciones y Observaciones del Sistema Actual

### ⚠️ Sin filtrado por usuario / rol
Todas las notificaciones son globales del sistema. No existe filtrado por usuario conectado ni por rol (gerente, almacenista, vendedor). Todos ven exactamente las mismas alertas.

### ⚠️ Sin marcado de "leída"
No existe ningún mecanismo para que un usuario marque una notificación como leída. El badge reaparecerá cada 2 minutos mientras el problema persista en la base de datos.

### ⚠️ Sin persistencia / historial
Las alertas **no se almacenan** en ninguna tabla propia de notificaciones. Se generan en tiempo real consultando las tablas operativas. No hay historial de cuándo apareció una alerta.

### ℹ️ Límite de 10 notificaciones con orden fija
`array_slice($notifications, 0, 10)` corta el resultado. El orden de inserción es:
1. Almacén (hasta 10 items)
2. Ventas
3. Obras
4. RH
5. Compras ← puede perderse si hay muchas alertas anteriores
6. Producción ← puede perderse si hay muchas alertas anteriores

### ℹ️ `wrapper.php` vs `topNavbar.php`
Existe un segundo `#alertsDropdown` en `wrapper.php` con datos hardcodeados del tema original AppStack (en inglés). Este archivo es el template base del tema y **no está conectado** al sistema de notificaciones dinámico.

---

## Ideas para Futuras Mejoras

- [ ] Agregar tabla `notificaciones` en BD para persistencia e historial
- [ ] Marcar notificaciones como leídas por usuario
- [ ] Filtrar alertas por rol (solo RH ve datos faltantes de empleados, etc.)
- [ ] Usar WebSockets o Server-Sent Events en lugar de polling cada 2 minutos
- [ ] Agregar sonido o notificación del navegador (`Notification API`) para alertas críticas
- [ ] Ordenar notificaciones por prioridad (danger > warning > info)
