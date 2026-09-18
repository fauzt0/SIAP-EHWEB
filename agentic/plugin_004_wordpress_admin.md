# Plugin 004 — Administrador Avanzado de WordPress

> **Slug:** `wp-admin` | **Prefijo DB:** `plg_wpadm_` | **Versión:** 1.0.0
> **Referencia:** `ARQUITECTURA_PLUGIN_SYSTEM.md`
> **Ruta base:** `/nat/plugins/wp-admin/`

---

## 1. Alcance y Objetivo

Centraliza la administración de múltiples sitios WordPress desde el ERP, usando la **WordPress REST API**. Integra opcionalmente con el plugin de Servidores para auto-descubrir sitios en servidores gestionados.

Funcionalidades principales:
- Registro y conexión de múltiples sitios WP (Application Passwords de WP 5.6+)
- Panel de estado: versión WP, plugins desactualizados, temas
- Gestión de usuarios de WP desde el ERP
- Publicación y gestión de entradas/páginas básica
- Sincronización de clientes WP con CRM (cuando plugin CRM esté instalado)
- Monitor de disponibilidad de sitios WP
- Gestión de plugins WP instalados (activar, desactivar, actualizar)

---

## 2. Base de Datos

### `plg_wpadm_sites` — Sitios WordPress registrados

```sql
id                INT AI PK
name              VARCHAR(150)         -- "Blog Empresa", "Tienda MX"
url               VARCHAR(255)         -- "https://miempresa.com"
wp_version        VARCHAR(20) NULL     -- detectado vía API
api_user          VARCHAR(150)         -- usuario WP con permisos admin
api_password      TEXT                 -- Application Password (cifrado AES-256)
server_id         INT NULL FK plg_srvadm_servers.id  -- (si plugin servers activo)
crm_client_id     INT NULL             -- FK futura a crm_clients.id
branch_id         INT NULL FK org_branches.id
status            ENUM('active','error','disconnected') DEFAULT 'active'
last_sync_at      DATETIME NULL
notes             TEXT NULL
created_by        INT FK users.id
created_at, updated_at, deleted_at
```

### `plg_wpadm_plugins` — Plugins WP detectados por sitio

```sql
id                INT AI PK
site_id           INT FK plg_wpadm_sites.id (CASCADE)
plugin_file       VARCHAR(255)         -- "woocommerce/woocommerce.php"
name              VARCHAR(200)
version           VARCHAR(20)
latest_version    VARCHAR(20) NULL
status            ENUM('active','inactive','update_available')
last_checked_at   DATETIME NULL
```

### `plg_wpadm_sync_log` — Historial de sincronizaciones

```sql
id                INT AI PK
site_id           INT FK plg_wpadm_sites.id
synced_at         DATETIME
items_synced      INT DEFAULT 0
errors            TEXT NULL
status            ENUM('success','partial','failed')
```

---

## 3. Cliente WordPress REST API

```php
// plugins/WordpressAdmin/Services/WpRestClient.php
namespace Plugins\WordpressAdmin\Services;

class WpRestClient
{
    private string $baseUrl;
    private string $user;
    private string $appPassword;

    public function __construct(array $siteConfig)
    {
        $this->baseUrl     = rtrim($siteConfig['url'], '/') . '/wp-json/wp/v2';
        $this->user        = $siteConfig['api_user'];
        $this->appPassword = decrypt($siteConfig['api_password']);
    }

    private function get(string $endpoint, array $params = []): array { /* curl */ }
    private function post(string $endpoint, array $data): array { /* curl POST */ }
    private function delete(string $endpoint): array { /* curl DELETE */ }

    // ── Sitio ────────────────────────────────────────────────────────────────
    public function getSiteInfo(): array
    {
        return $this->get('/'); // devuelve versión WP, nombre, URL
    }

    // ── Plugins WP ────────────────────────────────────────────────────────────
    public function listPlugins(): array   { return $this->get('/plugins'); } // Requiere WP 5.5+
    public function activatePlugin(string $plugin): array { /* ... */ }
    public function deactivatePlugin(string $plugin): array { /* ... */ }
    public function updatePlugin(string $plugin): array { /* ... */ }

    // ── Usuarios WP ─────────────────────────────────────────────────────────
    public function listUsers(int $page = 1): array { return $this->get('/users', ['page' => $page]); }
    public function createUser(array $data): array  { return $this->post('/users', $data); }
    public function updateUser(int $id, array $data): array { return $this->post("/users/$id", $data); }
    public function deleteUser(int $id, int $reassign): array { return $this->delete("/users/$id?reassign=$reassign"); }

    // ── Posts ────────────────────────────────────────────────────────────────
    public function listPosts(array $filters = []): array { return $this->get('/posts', $filters); }
    public function getPost(int $id): array { return $this->get("/posts/$id"); }
    public function createPost(array $data): array { return $this->post('/posts', $data); }
    public function updatePost(int $id, array $data): array { return $this->post("/posts/$id", $data); }
    public function deletePost(int $id, bool $force = false): array { return $this->delete("/posts/$id?force=$force"); }

    // ── Pages ────────────────────────────────────────────────────────────────
    public function listPages(): array { return $this->get('/pages'); }
}
```

---

## 4. Controladores y Rutas

| Método | Alias | Permiso |
|--------|-------|---------|
| `index()` | `wpadm.index` | `wp.access` |
| `show(id)` | `wpadm.show` | `wp.access` |
| `store()` | `wpadm.store` | `wp.manage` |
| `update(id)` | `wpadm.update` | `wp.manage` |
| `delete(id)` | `wpadm.delete` | `wp.manage` |
| `sync(id)` | `wpadm.sync` | `wp.manage` |
| `users_ajax(id)` | `wpadm.users` | `wp.access` |
| `create_wp_user(id)` | `wpadm.user.create` | `wp.manage` |
| `plugins_ajax(id)` | `wpadm.plugins` | `wp.access` |
| `plugin_action(id)` | `wpadm.plugin.action` | `wp.manage` |
| `posts_ajax(id)` | `wpadm.posts` | `wp.access` |
| `monitor_ajax()` | `wpadm.monitor` | `wp.access` |

---

## 5. Vistas

### `wp_sites_index.php`
- Cards de estado por sitio: versión WP, plugins desactualizados, estado
- DataTable de sitios
- Modal de alta/edición con campos básicos + test de conexión AJAX

### `wp_site_show.php` — 5 tabs
1. **Dashboard:** info del sitio, versión WP, actualizaciones disponibles
2. **Plugins WP:** tabla de plugins instalados, botones activar/desactivar/actualizar
3. **Usuarios:** tabla de usuarios WP, botón crear nuevo, enlace a perfil
4. **Posts/Páginas:** tabla con últimos 50 posts, botón de edición básica
5. **Monitor:** historial de pings y uptime

---

## 6. Integración con Plugin Servidores

Si `server-admin` está activo:
- Al crear un sitio WP, Select2 con los servidores disponibles → `site.server_id`
- En la ficha del servidor, tab "Sitios WordPress" que lista los sitios en ese servidor

---

## 7. Permisos

```json
"permissions": {
  "wp.access":  "Ver sitios WordPress",
  "wp.manage":  "Administrar sitios, plugins y usuarios WP"
}
```

---

## 8. Alertas Automáticas

| Detector | Condición |
|----------|-----------|
| `_detectWpPluginsOutdated` | Sitios con 3+ plugins desactualizados |
| `_detectWpSiteDown` | Sitios con `status = 'error'` tras última sincronización |

---

## 9. Cumplimiento §1–§21

| § | Aplicación en este plugin |
|---|--------------------------|
| §1 | `assertAjaxPost()` en sync, users_ajax, plugins_ajax, plugin_action, posts_ajax, monitor_ajax |
| §2 | CSRF en vista principal. `jsonWithCsrf()` en todos los POST |
| §3 | Alias `wpadm.*`. Solo `route_to()` en vistas |
| §7 | `permission:wp.access` / `permission:wp.manage` en todas las rutas |
| §8 | `logActivity` en store (wp_site_created), update, delete, sync, plugin_action, create_wp_user |
| §9 | `SiteModel::createSite()` con transacción si hay operaciones en múltiples tablas |
| §10 | Select2 con `dropdownParent` en modal de alta de sitio WP |
| §11 | `<input type="date">` no aplica; fechas mostradas en `d/m/Y H:i` |
| §12 | FontAwesome: `fa-wordpress`, `fa-globe`, `fa-plug`, `fa-users` |
| §13 | Breadcrumbs en vistas |
| §14 | Vistas extienden `$layout` |
| §15 | `renderLayout()` para GET. `jsonWithCsrf()` para POST |
| §17 | `notifyShow()` para feedback. Nunca `alert()` |
| §18 | Credenciales API almacenadas cifradas en DB (campo `api_password` con AES-256) — no en archivos |
| §19 | `useSoftDeletes = true` en `WpSiteModel`. `$allowedFields` explícito |
| §21 | Detectores de alertas con formato estándar del ERP |

---

## Prompt para el agente implementador

```
Actúa como un desarrollador experto en CodeIgniter 4. Lee y ejecuta exactamente el plan:
@agentic/plugin_004_wordpress_admin.md

Lee también:
- @DOCUMENTACION_TECNICA.md (§1–§21 obligatorios)
- @agentic/ARQUITECTURA_PLUGIN_SYSTEM.md
- @agentic/plugin_000_plugin_system_core.md (prerequisito ya instalado)

Prefix de tablas: plg_wpadm_
Namespace: Plugins\WordpressAdmin\

Importante: las credenciales API de WP (api_password) se almacenan CIFRADAS en la base de datos.
Usa encrypt()/decrypt() del helper de CI4 o una implementación AES-256.
Verifica la conexión WP vía REST API antes de guardar (botón Test Conexión en el modal).

Al terminar, añade tu reporte al final de @agentic/AUDITORIA_ARQUITECTURA.md.
```
