# Plugin 001 — Administrador de Servidores

> **Slug:** `server-admin` | **Prefijo DB:** `plg_srvadm_` | **Versión:** 1.0.0
> **Referencia arquitectónica:** `ARQUITECTURA_PLUGIN_SYSTEM.md`
> **Ruta base:** `/nat/plugins/servers/`

---

## 📑 Índice

1. [Alcance y objetivo](#1-alcance-y-objetivo)
2. [Módulos internos del plugin](#2-módulos-internos-del-plugin)
3. [Estructura de directorios](#3-estructura-de-directorios)
4. [Base de datos](#4-base-de-datos)
5. [Modelos](#5-modelos)
6. [Controladores y Rutas](#6-controladores-y-rutas)
7. [Vistas](#7-vistas)
8. [Integración con módulos del ERP base](#8-integración-con-módulos-del-erp-base)
9. [API DirectAdmin](#9-api-directadmin)
10. [API InterServer](#10-api-interserver)
11. [Terminal SSH Web](#11-terminal-ssh-web)
12. [Monitor de Estatus](#12-monitor-de-estatus)
13. [Base de Conocimiento (KB) + IA](#13-base-de-conocimiento-kb--ia)
14. [Daemon de recursos (futuro)](#14-daemon-de-recursos-futuro)
15. [Hooks registrados](#15-hooks-registrados)
16. [Permisos Shield](#16-permisos-shield)
17. [Alertas automáticas](#17-alertas-automáticas)
18. [Cumplimiento §1–§21](#18-cumplimiento-1-21)

---

## 1. Alcance y Objetivo

El plugin **Administrador de Servidores** extiende el ERP con capacidad de:
- Inventariar y administrar servidores físicos/VPS/compartidos
- Integrarse con la API de **DirectAdmin** para gestionar dominios, cuentas y usuarios
- Integrarse con **InterServer** (conmutador en instalación)
- Exponer los servidores como **productos/insumos en el catálogo de proveedores**
- Vincular clientes CRM a servidores específicos (cliente X → servidor Y)
- Monitorear el estatus en tiempo real de cada servidor
- Proveer una **terminal SSH web** con selector de servidor
- Mantener una **base de conocimiento** de soluciones técnicas (con futura integración IA)

---

## 2. Módulos Internos del Plugin

| Módulo | Descripción |
|--------|-------------|
| **Servers** | CRUD de servidores + metadatos técnicos |
| **DirectAdmin** | Cliente API hacia DirectAdmin: dominios, cuentas, resellers, packages |
| **InterServer** | Cliente API hacia InterServer (opcional, activado en instalación) |
| **Domains** | Gestión de dominios vinculados a servidores |
| **Accounts** | Gestión de cuentas de hosting en cada servidor |
| **Monitor** | Ping + HTTP check + SNMP (ping nativo; SNMP futuro) |
| **SSH Terminal** | Terminal web via PHP SSH2 o websocket proxy |
| **Knowledge Base** | Base de conocimiento técnica con búsqueda y futura IA |
| **Supplier Integration** | Hook que inyecta servidores como insumos en proveedor |
| **CRM Integration** | Vinculación cliente ↔ servidor ↔ servicio contratado |

---

## 3. Estructura de Directorios

```
plugins/ServerAdmin/
├── ServerAdmin.php              (clase principal — extiende BasePlugin)
├── plugin.json
├── Config/
│   └── Routes.php
├── Controllers/
│   ├── ServerController.php     (CRUD + monitor + SSH)
│   ├── DomainController.php     (gestión de dominios)
│   ├── AccountController.php    (gestión de cuentas DirectAdmin)
│   └── KnowledgeBaseController.php
├── Models/
│   ├── ServerModel.php
│   ├── ServerDomainModel.php
│   ├── ServerAccountModel.php
│   ├── ServerMonitorLogModel.php
│   └── KnowledgeBaseModel.php
├── Views/
│   ├── servers_index.php        (listado DataTable + cards de estatus)
│   ├── server_show.php          (detalle: tabs — info, dominios, cuentas, monitor, KB)
│   ├── server_monitor.php       (dashboard de estatus en tiempo real)
│   ├── server_ssh.php           (terminal SSH web)
│   ├── knowledge_base.php       (listado + búsqueda de soluciones)
│   ├── knowledge_entry.php      (detalle de solución)
│   └── Partials/
│       ├── supplier_tab.php     (tab inyectado en vista de proveedor)
│       └── sidebar_section.php
├── Hooks/
│   ├── SidebarHook.php
│   ├── AlertHook.php
│   └── SupplierHook.php        (inyecta tab de servidores en vista de proveedor)
├── Services/
│   ├── DirectAdminClient.php    (cliente HTTP hacia DirectAdmin API)
│   ├── InterServerClient.php    (cliente HTTP hacia InterServer API)
│   └── SshProxyService.php      (abstracción SSH)
├── Database/
│   ├── Migrations/
│   │   ├── 2026-09-18-001_CreateSrvadmServers.php
│   │   ├── 2026-09-18-002_CreateSrvadmDomains.php
│   │   ├── 2026-09-18-003_CreateSrvadmAccounts.php
│   │   ├── 2026-09-18-004_CreateSrvadmMonitorLog.php
│   │   └── 2026-09-18-005_CreateSrvadmKnowledgeBase.php
│   └── Seeds/
│       └── ServerTypesSeeder.php
└── Assets/
    ├── css/plugin.css
    └── js/plugin.js             (monitor polling, terminal xterm.js)
```

---

## 4. Base de Datos

### `plg_srvadm_servers` — Inventario de servidores

```sql
id               INT AI PK
name             VARCHAR(150)        -- "DA-Server-01", "VPS-Prod-MX"
type             ENUM('dedicated','vps','shared','cloud','baremetal')
provider         ENUM('interserver','hetzner','digitalocean','custom','other')
ip_address       VARCHAR(45)         -- IPv4 o IPv6
hostname         VARCHAR(255)
location         VARCHAR(100)        -- "Dallas TX", "Frankfurt"
os               VARCHAR(100)        -- "CentOS 7", "AlmaLinux 8"
panel            ENUM('directadmin','cpanel','plesk','none')
panel_url        VARCHAR(255) NULL   -- URL del panel de administración
panel_api_key    TEXT NULL           -- Cifrado: AES-256 en DB
panel_api_user   VARCHAR(100) NULL
ssh_port         SMALLINT DEFAULT 22
ssh_user         VARCHAR(100) NULL
ssh_key_path     VARCHAR(255) NULL   -- Ruta relativa a clave privada en WRITEPATH
status           ENUM('active','down','maintenance','retired') DEFAULT 'active'
monthly_cost     DECIMAL(10,2) NULL  -- Costo de renovación
renewal_date     DATE NULL           -- Próxima renovación
supplier_id      INT NULL FK fin_suppliers.id  -- Proveedor del servidor
notes            TEXT NULL
interserver_id   VARCHAR(100) NULL   -- ID en InterServer (si aplica)
created_by       INT FK users.id
created_at, updated_at, deleted_at
```

### `plg_srvadm_domains` — Dominios vinculados a servidor

```sql
id               INT AI PK
server_id        INT FK plg_srvadm_servers.id
domain           VARCHAR(255)
status           ENUM('active','suspended','expired','transferring')
owner_account    VARCHAR(100) NULL   -- cuenta DA del propietario
crm_client_id    INT NULL            -- FK futura a crm_clients.id (plugin CRM)
catalog_product_id INT NULL FK catalog_products.id  -- servicio contratado
expires_at       DATE NULL
notes            TEXT NULL
created_at, updated_at, deleted_at
```

### `plg_srvadm_accounts` — Cuentas de hosting en servidores

```sql
id               INT AI PK
server_id        INT FK plg_srvadm_servers.id
domain_id        INT NULL FK plg_srvadm_domains.id
da_username      VARCHAR(100)        -- usuario DirectAdmin
email            VARCHAR(150) NULL
package          VARCHAR(100) NULL   -- plan de hosting
disk_quota_mb    INT NULL
bandwidth_quota_gb INT NULL
status           ENUM('active','suspended','terminated')
created_at, updated_at, deleted_at
```

### `plg_srvadm_monitor_log` — Historial de monitoreo

```sql
id               INT AI PK
server_id        INT FK plg_srvadm_servers.id
checked_at       DATETIME
ping_ms          SMALLINT NULL       -- -1 si no responde
http_status      SMALLINT NULL       -- código HTTP del panel
cpu_percent      DECIMAL(5,2) NULL   -- futuro (vía daemon)
ram_percent      DECIMAL(5,2) NULL   -- futuro
disk_percent     DECIMAL(5,2) NULL   -- futuro
is_up            TINYINT(1) DEFAULT 1
notes            VARCHAR(255) NULL
```

### `plg_srvadm_knowledge_base` — Base de conocimiento técnica

```sql
id               INT AI PK
title            VARCHAR(255)
category         VARCHAR(100)        -- "DirectAdmin","SSH","Performance","cPanel"
problem          TEXT                -- descripción del problema
solution         TEXT                -- solución paso a paso (markdown)
tags             VARCHAR(500) NULL   -- "apache,mod_security,403"
server_id        INT NULL FK plg_srvadm_servers.id  -- solución específica de servidor
author_id        INT FK users.id
is_public        TINYINT(1) DEFAULT 0  -- futuro: portal de soporte público
views            INT DEFAULT 0
created_at, updated_at, deleted_at
```

---

## 5. Modelos

### `ServerModel`
- `use \App\Traits\DataTableTrait`
- `$table = 'plg_srvadm_servers'`
- `$useSoftDeletes = true`
- `$allowedFields` — todos excepto `id`, timestamps
- `getWithSupplier(int $id)` → JOIN con `fin_suppliers`
- `getForSupplierTab(int $supplierId)` → lista de servidores de ese proveedor
- `getExpiringSoon(int $days = 30)` → para alertas de renovación
- `getDashboardStats()` → count por status (activos, caídos, mantenimiento)
- `getForSelect()` → para Select2 en modal de dominio/cuenta

### `ServerMonitorLogModel`
- `$table = 'plg_srvadm_monitor_log'`
- `$useSoftDeletes = false` (append-only)
- `getLatestByServer(int $serverId)` → último ping
- `getUptimePercent(int $serverId, int $days = 7)` → % uptime de N días
- `getDownServers()` → servidores con `is_up = 0` en su último registro

### `KnowledgeBaseModel`
- `use \App\Traits\DataTableTrait`
- `$table = 'plg_srvadm_knowledge_base'`
- `$useSoftDeletes = true`
- `search(string $query, string $category = '')` → búsqueda por título/tags/problema/solución
- `incrementViews(int $id)` → contador de vistas

---

## 6. Controladores y Rutas

### `ServerController` — 18 métodos

| Método | HTTP | Ruta | Alias | Permiso |
|--------|------|------|-------|---------|
| `index()` | GET | `/nat/plugins/servers` | `servers.index` | `servers.access` |
| `list_ajax()` | POST | `/nat/plugins/servers/list_ajax` | `servers.list_ajax` | `servers.access` |
| `show(id)` | GET | `/nat/plugins/servers/show/(:num)` | `servers.show` | `servers.access` |
| `get_ajax(id)` | GET | `/nat/plugins/servers/get_ajax/(:num)` | `servers.get_ajax` | `servers.access` |
| `store()` | POST | `/nat/plugins/servers/store` | `servers.store` | `servers.manage` |
| `update(id)` | POST | `/nat/plugins/servers/update/(:num)` | `servers.update` | `servers.manage` |
| `delete(id)` | POST | `/nat/plugins/servers/delete/(:num)` | `servers.delete` | `servers.delete` |
| `restore(id)` | POST | `/nat/plugins/servers/restore/(:num)` | `servers.restore` | `servers.manage` |
| `monitor()` | GET | `/nat/plugins/servers/monitor` | `servers.monitor` | `servers.access` |
| `monitor_ajax()` | POST | `/nat/plugins/servers/monitor_ajax` | `servers.monitor_ajax` | `servers.access` |
| `ping_ajax(id)` | POST | `/nat/plugins/servers/ping/(:num)` | `servers.ping` | `servers.access` |
| `ssh(id)` | GET | `/nat/plugins/servers/ssh/(:num)` | `servers.ssh` | `servers.ssh` |
| `ssh_exec()` | POST | `/nat/plugins/servers/ssh/exec` | `servers.ssh.exec` | `servers.ssh` |
| `da_sync(id)` | POST | `/nat/plugins/servers/da-sync/(:num)` | `servers.da_sync` | `servers.manage` |
| `da_accounts(id)` | GET | `/nat/plugins/servers/da-accounts/(:num)` | `servers.da_accounts` | `servers.access` |
| `da_domains(id)` | GET | `/nat/plugins/servers/da-domains/(:num)` | `servers.da_domains` | `servers.access` |
| `settings()` | GET | `/nat/plugins/servers/settings` | `servers.settings` | `servers.manage` |
| `settings_save()` | POST | `/nat/plugins/servers/settings/save` | `servers.settings.save` | `servers.manage` |

### `KnowledgeBaseController` — 8 métodos

| Método | HTTP | Ruta | Alias |
|--------|------|------|-------|
| `index()` | GET | `/nat/plugins/servers/kb` | `servers.kb.index` |
| `list_ajax()` | POST | `/nat/plugins/servers/kb/list_ajax` | `servers.kb.list_ajax` |
| `show(id)` | GET | `/nat/plugins/servers/kb/show/(:num)` | `servers.kb.show` |
| `store()` | POST | `/nat/plugins/servers/kb/store` | `servers.kb.store` |
| `update(id)` | POST | `/nat/plugins/servers/kb/update/(:num)` | `servers.kb.update` |
| `delete(id)` | POST | `/nat/plugins/servers/kb/delete/(:num)` | `servers.kb.delete` |
| `search_ajax()` | POST | `/nat/plugins/servers/kb/search` | `servers.kb.search` |
| `ai_suggest()` | POST | `/nat/plugins/servers/kb/ai-suggest` | `servers.kb.ai_suggest` |

---

## 7. Vistas

### `servers_index.php`
- Breadcrumb: Inicio > Servidores
- CSRF hidden al inicio del `<main>` (§2)
- **4 cards de resumen:** Servidores activos, caídos, en mantenimiento, próximos a renovar
- DataTable server-side con columnas: Nombre, Tipo, IP, Panel, Proveedor, Estado, Renovación, Acciones
- `isDataTable` Guard (§6)
- `xhr.dt` renovación CSRF (§2)
- Modal de alta/edición: campos del servidor + Select2 de proveedor (`dropdownParent` §10)
- Botones de acción con `plugin_can()` (§7)

### `server_show.php`
- Breadcrumb: Inicio > Servidores > {nombre}
- **Tabs:**
  - **Info:** datos del servidor + metadatos técnicos
  - **DirectAdmin:** dominios, cuentas, packages (carga vía AJAX desde DA API)
  - **Dominios:** tabla de `plg_srvadm_domains` vinculados
  - **Monitor:** historial de uptime + gráfica simple (últimos 7 días)
  - **Base de Conocimiento:** KB específica de este servidor

### `server_monitor.php`
- Dashboard en tiempo real de todos los servidores
- Cards por servidor con: nombre, IP, uptime %, último ping, status badge
- Auto-refresh cada 60 segundos via `setInterval` + `fetch` (§1: header AJAX)
- Semáforo visual: verde = up, rojo = down, naranja = degradado

### `server_ssh.php`
- Selector de servidor (Select2)
- Terminal emulada via **xterm.js** (asset en `Assets/js/`)
- Comunicación via WebSocket (si disponible) o polling AJAX hacia `ssh_exec()`
- **Advertencia de seguridad** visible: "Solo ejecutar comandos autorizados"
- Log automático de sesión (tabla futura: `plg_srvadm_ssh_logs`)

### `knowledge_base.php`
- Búsqueda en tiempo real (debounce 300ms) via `search_ajax()`
- Filtros por categoría (ENUM del campo `category`)
- Cards de soluciones con título, categoría, tags, vistas
- Botón "Sugerir solución con IA" (placeholder en v1, conecta en v2)

---

## 8. Integración con Módulos del ERP Base

### 8.1 Integración con Proveedores (`fin_suppliers`)

El plugin añade un **tab "Servidores"** en la ficha de proveedor (`SupplierController::show()`).

```php
// plugins/ServerAdmin/Hooks/SupplierHook.php
PluginHookRegistry::addAction('supplier.show.tabs', [self::class, 'renderTab'], 10);

public static function renderTab(array $supplier): void
{
    $serverModel = new \Plugins\ServerAdmin\Models\ServerModel();
    $servers = $serverModel->getForSupplierTab($supplier['id']);
    echo view('Plugins\ServerAdmin\Views\Partials\supplier_tab', compact('servers', 'supplier'));
}
```

El tab muestra:
- Lista de servidores de ese proveedor (con IP, tipo, estado, próxima renovación)
- Botón "Nuevo servidor en este proveedor" → abre modal pre-llenado con `supplier_id`
- Total de costo mensual de servidores del proveedor

### 8.2 Integración con Catálogo de Productos (`catalog_products`)

Cuando se crea/actualiza un servidor, el plugin puede:
1. **Auto-crear un producto** en `catalog_products` de tipo "Servicio" con el nombre del servidor
2. **Vincular** el `catalog_product_id` en `plg_srvadm_servers.catalog_product_id`

Esto permite que el servidor aparezca en el catálogo de insumos/compras del proveedor.

Hook disparado: `catalog.product.show.tabs` → muestra en la ficha del producto qué servidor representa.

### 8.3 Integración con CRM/Clientes (Plugin CRM, futuro)

Cuando el plugin CRM esté instalado, el hook `crm.client.show.tabs` mostrará los servidores/dominios asociados al cliente.

La tabla `plg_srvadm_domains.crm_client_id` ya tiene la FK preparada.

### 8.4 Auto-poblado de insumos en proveedor

```php
// En ServerController::store() / update() al guardar un servidor:
if ($request->getPost('create_as_product') === '1') {
    $productData = [
        'name'            => $data['name'],
        'product_type'    => 'service',
        'supplier_id'     => $data['supplier_id'],
        'description'     => "Servidor {$data['type']} — {$data['ip_address']}",
        'cost_price'      => $data['monthly_cost'],
        'business_unit_id'=> null,
    ];
    $productId = (new \App\Models\Catalog\CatalogProductModel())->insert($productData);
    // Actualizar el server con el product_id
    $serverModel->update($savedId, ['catalog_product_id' => $productId]);
    logActivity('server_linked_product', "Servidor ID $savedId vinculado al producto ID $productId");
}
```

---

## 9. API DirectAdmin

### `DirectAdminClient.php`

```php
namespace Plugins\ServerAdmin\Services;

class DirectAdminClient
{
    private string $baseUrl;
    private string $username;
    private string $apiKey;
    private int    $timeout = 10;

    public function __construct(array $serverConfig)
    {
        $this->baseUrl  = rtrim($serverConfig['panel_url'], '/');
        $this->username = $serverConfig['panel_api_user'];
        $this->apiKey   = decrypt($serverConfig['panel_api_key']); // AES-256
    }

    private function request(string $endpoint, array $params = [], string $method = 'GET'): array
    {
        $client = \Config\Services::curlrequest([
            'baseURI' => $this->baseUrl,
            'timeout' => $this->timeout,
        ]);
        $response = $client->request($method, $endpoint, [
            'auth'        => [$this->username, $this->apiKey],
            'form_params' => $params,
        ]);
        parse_str($response->getBody(), $result);
        return $result;
    }

    // ── Dominios ────────────────────────────────────────────────────────────
    public function listDomains(string $user): array { /* ... */ }
    public function createDomain(string $user, string $domain, string $package): array { /* ... */ }
    public function suspendDomain(string $user, string $domain): array { /* ... */ }
    public function deleteDomain(string $user, string $domain): array { /* ... */ }

    // ── Usuarios/Cuentas ─────────────────────────────────────────────────────
    public function listAccounts(): array { /* ... */ }
    public function createAccount(array $data): array { /* ... */ }
    public function suspendAccount(string $user): array { /* ... */ }
    public function changePassword(string $user, string $password): array { /* ... */ }

    // ── Packages ─────────────────────────────────────────────────────────────
    public function listPackages(): array { /* ... */ }
    public function changePackage(string $user, string $package): array { /* ... */ }

    // ── Stats ─────────────────────────────────────────────────────────────────
    public function getUsageStats(string $user): array { /* ... */ }
    public function getServerLoad(): array { /* ... */ }
}
```

### Endpoints DA usados

| Función | Endpoint DA | Método |
|---------|-------------|--------|
| Listar dominios | `CMD_API_SHOW_USER_DOMAINS` | GET |
| Crear cuenta | `CMD_API_ACCOUNT_USER` | POST |
| Suspender | `CMD_API_SELECT_USERS` (action=suspend) | POST |
| Cambiar contraseña | `CMD_API_USER_PASSWD` | POST |
| Stats del servidor | `CMD_API_ADMIN_STATS` | GET |
| Listar packages | `CMD_API_PACKAGES_USER` | GET |

---

## 10. API InterServer

```php
// plugins/ServerAdmin/Services/InterServerClient.php
class InterServerClient
{
    private const BASE_URL = 'https://my.interserver.net/apiv2/';
    private string $apiKey;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function listVps(): array { /* GET /vps */ }
    public function getVps(string $id): array { /* GET /vps/{id} */ }
    public function stopVps(string $id): array { /* POST /vps/{id}/stop */ }
    public function startVps(string $id): array { /* POST /vps/{id}/start */ }
    public function rebootVps(string $id): array { /* POST /vps/{id}/reboot */ }
    public function getInvoices(): array { /* GET /invoices */ }
}
```

**Activación:** Al instalar el plugin, si el usuario marca "Usar InterServer API", el wizard solicita el API Key y lo guarda en `sys_plugin_settings(plugin_slug='server-admin', key='interserver_api_key', value=encrypt($key))`.

---

## 11. Terminal SSH Web

### Estrategia técnica

**Opción A (v1 — Simple):** `phpseclib/phpseclib` para conexión SSH desde PHP. El usuario ejecuta comandos via AJAX, cada comando es una petición HTTP.

```bash
composer require phpseclib/phpseclib
```

```php
// ServerController::ssh_exec()
public function sshExec(): ResponseInterface
{
    $this->assertAjaxPost();
    $this->assertPermission('servers.ssh');

    $serverId = (int) $this->request->getPost('server_id');
    $command  = trim($this->request->getPost('command'));

    // Blacklist de comandos peligrosos
    $forbidden = ['rm -rf', 'mkfs', 'dd if=', ':(){:|:&};:'];
    foreach ($forbidden as $blocked) {
        if (stripos($command, $blocked) !== false) {
            return $this->setOutputError('Comando bloqueado por política de seguridad.')
                        ->jsonWithCsrf($this->outputData);
        }
    }

    $server = (new ServerModel())->find($serverId);
    $ssh = new \phpseclib3\Net\SSH2($server->ip_address, $server->ssh_port);
    $key = \phpseclib3\Crypt\PublicKeyLoader::load(
        file_get_contents(WRITEPATH . $server->ssh_key_path)
    );
    $ssh->login($server->ssh_user, $key);
    $output = $ssh->exec($command);

    // §8: Registrar comando ejecutado
    logActivity('ssh_exec', "SSH server #{$serverId}: " . mb_substr($command, 0, 200));

    $this->setOutputSuccess('OK');
    $this->outputData['output'] = htmlspecialchars($output);
    return $this->jsonWithCsrf($this->outputData);
}
```

**Opción B (v2 — WebSocket):** Un proxy WebSocket en Node.js o un servicio separado que mantiene sesiones SSH persistentes. Planificado para versión futura.

### Vista `server_ssh.php`

- Select2 para selector de servidor
- Terminal emulada con **xterm.js** (CDN o en Assets/)
- Historial de comandos local (localStorage del browser)
- Botón de limpieza de terminal
- **Restricciones visibles** en pantalla de advertencia

---

## 12. Monitor de Estatus

### Estrategia de monitoreo

**Nivel 1 — Ping HTTP (v1):** En cada visita a `monitor_ajax()` el sistema hace un `curl` a la URL del panel del servidor con timeout de 5s. Resultado guardado en `plg_srvadm_monitor_log`.

**Nivel 2 — Comando Spark (v1):** Un comando CI4 ejecutable desde cron:

```php
// plugins/ServerAdmin/Commands/MonitorServers.php
namespace Plugins\ServerAdmin\Commands;

class MonitorServers extends BaseCommand
{
    protected $group   = 'Plugins';
    protected $name    = 'servers:monitor';
    protected $description = 'Verifica el estatus de todos los servidores activos.';

    public function run(array $params)
    {
        $servers = (new ServerModel())->where('status', 'active')->findAll();
        foreach ($servers as $server) {
            $isUp = $this->pingServer($server->ip_address, $server->panel_url);
            // Guardar en monitor_log
            (new ServerMonitorLogModel())->insert([
                'server_id'  => $server->id,
                'checked_at' => date('Y-m-d H:i:s'),
                'is_up'      => $isUp ? 1 : 0,
                'ping_ms'    => $this->pingMs,
                'http_status'=> $this->httpStatus,
            ]);
            // Si cambió a DOWN: disparar alerta §21
            if (!$isUp) {
                AlertService::dispatch(
                    title: "Servidor caído: {$server->name}",
                    message: "El servidor {$server->ip_address} no responde.",
                    type: 'danger',
                    icon: 'fa-server',
                    targetUrl: route_to('servers.monitor'),
                    requiredPermission: 'servers.access',
                    module: 'Servidores'
                );
            }
        }
        $this->write('[Servers Monitor] Verificación completa.');
    }
}
```

**Cron (en servidor):**
```cron
*/5 * * * * php /path/to/spark servers:monitor >> /dev/null 2>&1
```

---

## 13. Base de Conocimiento (KB) + IA

### v1 — KB Manual

- CRUD de entradas con título, categoría, problema (texto plano), solución (Markdown renderizado)
- Tags para búsqueda rápida
- Búsqueda full-text en `problem + solution + title + tags`
- Vinculación opcional a servidor específico
- Contador de vistas
- Sistema de likes/utilidad (campo `helpful_count` — futuro)

### v2 — Integración IA (planificado, no implementar en v1)

Al hacer clic en "Sugerir solución con IA":
1. El sistema envía el texto del problema al modelo configurado (Gemini o DeepSeek)
2. El modelo busca similitudes con entradas existentes de KB
3. Si no hay coincidencia, genera una sugerencia basada en conocimiento general
4. El usuario puede aceptar la sugerencia y guardarla en KB
5. Las respuestas de IA se marcan con badge "IA — pendiente de verificación"

```php
// Endpoint preparado (implementación IA en v2):
// POST /nat/plugins/servers/kb/ai-suggest
public function ai_suggest(): ResponseInterface
{
    $this->assertAjaxPost();
    $problem = $this->request->getPost('problem');
    // v1: retorna las KB más similares (búsqueda local)
    $suggestions = (new KnowledgeBaseModel())->search($problem);
    $this->setOutputSuccess('Sugerencias encontradas.');
    $this->outputData['suggestions'] = $suggestions;
    return $this->jsonWithCsrf($this->outputData);
    // v2: además llama a GeminiClient o DeepSeekClient
}
```

---

## 14. Daemon de Recursos (Futuro — v3)

### Diseño conceptual

Un daemon liviano instalado en cada servidor monitorado que envía métricas al ERP via HTTP POST cada N segundos.

**Lenguaje recomendado:** Go (compilado, liviano, cross-platform)

```
Flujo:
Servidor → (POST cada 30s) → /nat/plugins/servers/metrics/push → ERP guarda en plg_srvadm_monitor_log
```

**Payload enviado:**
```json
{
  "server_token": "abc123...",
  "cpu_percent": 23.5,
  "ram_percent": 64.2,
  "disk_percent": 45.1,
  "load_avg": [0.5, 0.8, 1.2],
  "network_in_mbps": 12.4,
  "network_out_mbps": 8.1,
  "uptime_seconds": 864000
}
```

**Autenticación del daemon:** Token único por servidor guardado en `plg_srvadm_servers.daemon_token` (generado al instalar el daemon).

**Nueva tabla (v3):** `plg_srvadm_resource_metrics` — almacena series de tiempo de CPU/RAM/Disk.

---

## 15. Hooks Registrados

```php
// plugins/ServerAdmin/ServerAdmin.php
public function registerHooks(): void
{
    \App\Libraries\PluginSystem\PluginHookRegistry::addAction(
        'sidebar.render', [\Plugins\ServerAdmin\Hooks\SidebarHook::class, 'render'], 10
    );
    \App\Libraries\PluginSystem\PluginHookRegistry::addAction(
        'alert.detect', [\Plugins\ServerAdmin\Hooks\AlertHook::class, 'detect'], 20
    );
    \App\Libraries\PluginSystem\PluginHookRegistry::addAction(
        'supplier.show.tabs', [\Plugins\ServerAdmin\Hooks\SupplierHook::class, 'renderTab'], 10
    );
    \App\Libraries\PluginSystem\PluginHookRegistry::addAction(
        'catalog.product.show.tabs', [\Plugins\ServerAdmin\Hooks\SupplierHook::class, 'renderProductTab'], 10
    );
}
```

---

## 16. Permisos Shield

```json
"permissions": {
  "servers.access":  "Ver listado y detalle de servidores",
  "servers.manage":  "Crear, editar y configurar servidores",
  "servers.ssh":     "Acceso a terminal SSH web",
  "servers.delete":  "Eliminar/desactivar servidores",
  "servers.kb":      "Ver base de conocimiento",
  "servers.kb.manage": "Crear y editar entradas de KB"
}
```

Grupos sugeridos al instalar:
- `superadmin` → todos los permisos de servidores
- `admin` → `servers.access`, `servers.manage`, `servers.kb`
- Nuevo grupo `sysadmin` → todos los permisos incluyendo SSH

---

## 17. Alertas Automáticas

| Detector | Condición | Tipo | Permiso |
|----------|-----------|------|---------|
| `_detectServersDown` | Servidores con `is_up=0` en último log | `danger` | `servers.access` |
| `_detectRenewalsSoon` | `renewal_date` en los próximos 15 días | `warning` | `servers.manage` |
| `_detectMissingMonitorLog` | Servidor sin log en las últimas 2 horas | `warning` | `servers.access` |

---

## 18. Cumplimiento §1–§21

| § | Implementación en este plugin |
|---|-------------------------------|
| §1 | `assertAjaxPost()` en todos los métodos POST del plugin |
| §2 | CSRF en todas las vistas + `xhr.dt` + header fetch |
| §3 | Alias `servers.*` en todas las rutas. `route_to()` en vistas |
| §4 | `url_is('nat/plugins/servers*')` en ítems de sidebar |
| §5/§16 | `DataTableTrait` en `ServerModel` y `KnowledgeBaseModel` |
| §6 | `isDataTable` guard en todas las vistas con tabla |
| §7 | Todas las rutas con `'filter' => 'permission:servers.*'` |
| §8 | `logActivity` en store, update, delete, restore, da_sync, ssh_exec, plugin_install |
| §9 | Transacciones en modelos (no en controladores) |
| §10 | Select2 con `dropdownParent` en modales |
| §11 | `<input type="date">` para fechas. `d/m/Y` en UI |
| §12 | FontAwesome: `fa-server`, `fa-terminal`, `fa-globe`, `fa-book` |
| §13 | Breadcrumbs en todas las vistas de página completa |
| §14 | Vistas extienden `$layout` del ERP |
| §15 | `renderLayout()` para GET. `jsonWithCsrf()` para POST |
| §17 | `notifyShow()` — cero `alert()` |
| §18 | Uploads (claves SSH) en `WRITEPATH/uploads/plugins/server-admin/` |
| §19 | `useSoftDeletes`, `protectFields`, `allowedFields` en todos los modelos |
| §21 | `_buildAutoAlert()` en detectores, `_safeDetect()` en `_detectAll()` |

---

## Prompt para el agente implementador

```
Actúa como un desarrollador experto en CodeIgniter 4. Lee y ejecuta exactamente el plan:
@agentic/plugin_001_server_admin.md

Lee también:
- @DOCUMENTACION_TECNICA.md (§1–§21 obligatorios)
- @agentic/ARQUITECTURA_PLUGIN_SYSTEM.md (arquitectura del sistema de plugins)
- @agentic/plugin_000_plugin_system_core.md (prerequisito ya instalado)

Prefix de tablas: plg_srvadm_
Namespace: Plugins\ServerAdmin\

Este es el plugin más complejo. Implementa en este orden:
1. Migraciones (5 tablas en orden)
2. Modelos (ServerModel, KnowledgeBaseModel con DataTableTrait)
3. Services (DirectAdminClient, InterServerClient, SshProxyService con phpseclib)
4. Hooks (SidebarHook, AlertHook, SupplierHook)
5. Controladores (ServerController 18 métodos, KnowledgeBaseController 8 métodos)
6. Vistas (servers_index, server_show con 5 tabs, server_monitor, server_ssh, knowledge_base)
7. Rutas en Config/Routes.php del plugin
8. Clase principal ServerAdmin.php que extiende BasePlugin

Importante:
- Las claves SSH se guardan cifradas en WRITEPATH (nunca en DB directamente ni en public_html)
- La terminal SSH usa phpseclib/phpseclib (composer require phpseclib/phpseclib en el plugin)
- El monitor de servidores tiene un Command CI4 (spark servers:monitor) para cron
- SupplierHook inyecta un tab en la vista de proveedor del ERP base — usa PluginHookRegistry

Cumple TODOS los §1–§21 de la sección "Cumplimiento §1–§21" al final del plan.
Al terminar, añade tu reporte al final de @agentic/AUDITORIA_ARQUITECTURA.md.
```
