# Replicar entorno SIAP-EHWEB (agente en la nube)

Runbook para clonar **código + base de datos + plugins** a partir de la rama
`feat/plugin-system-architecture`. El sandbox de referencia es CodeIgniter 4.5.3 +
Shield 1.1, PHP 8.1+.

**No commitear `.env` ni dumps SQL con datos reales.** Las credenciales las entrega
el responsable del hosting por canal seguro.

---

## A. Qué incluye el “entorno completo”

| Capa | Contenido |
|------|-----------|
| Código | Repo `fauzt0/SIAP-EHWEB`, rama `feat/plugin-system-architecture` |
| PHP | `composer install`; extensiones MySQLi, intl, mbstring, json |
| Config | `.env` (baseURL, DB, `encryption.key`) — copiar plantilla `env` |
| BD esquema | `php spark migrate` (ERP + Shield + Settings + tablas `sys_*`) |
| Plugins | Carpeta `plugins/<Directory>/` + `php spark plugin:action …` |
| BD datos | Seeders **o** restauración de dump (ver sección B vs C) |
| Verificación | `plugin:doctor`, scripts en `tests/smoke_*.php` |

**Sandbox de referencia (solo métricas, sin credenciales):** ~79 tablas, 60 filas en
`migrations` (incl. namespace `Plugins\NotasInternas`), plugin `notas-internas` 1.3.1
activo.

---

## B. Estrategia 1 — Clon exacto de datos (recomendada para “igual que sandbox”)

1. En el servidor origen (o con acceso MySQL al sandbox), generar dump:
   ```bash
   mysqldump -h HOST -u USER -p NOMBRE_BD \
     --single-transaction --routines --triggers \
     > siap_sandbox_YYYYMMDD.sql
   ```
2. Crear BD vacía en el entorno destino (mismo charset `utf8` / `utf8_general_ci`).
3. Restaurar:
   ```bash
   mysql -h HOST -u USER -p NOMBRE_BD_DESTINO < siap_sandbox_YYYYMMDD.sql
   ```
4. Clonar código y `.env` apuntando a la **BD destino** y al **baseURL** del nuevo host.
5. `composer install` (no hace falta `migrate` si el dump ya trae `migrations`).
6. Verificar (sección E).

**Nota:** Tras restore, ejecutar `php spark plugin:doctor`. Si hay plugins en disco no
registrados en `sys_plugins`, usar install/update según sección D.

---

## C. Estrategia 2 — BD nueva solo desde migraciones + seeders

Útil si no hay dump. Obtienes **esquema completo** y datos **mínimos de demo**, no una
copia bit a bit del sandbox.

### C.1 Repositorio y dependencias

```bash
git clone https://github.com/fauzt0/SIAP-EHWEB.git
cd SIAP-EHWEB
git checkout feat/plugin-system-architecture
git pull --ff-only origin feat/plugin-system-architecture
composer install
cp env .env
# Editar .env: CI_ENVIRONMENT, app.baseURL, database.default.*, encryption.key
```

`encryption.key` debe existir antes de guardar ajustes de plugins tipo password
(cifrado `enc1:`). En sandbox se usa formato `hex2bin:…` en `.env`.

### C.2 Base de datos vacía

Crear esquema MySQL vacío. Probar conexión:

```bash
php spark db:table migrations
```

(debe conectar; tabla puede no existir hasta el primer migrate)

### C.3 Núcleo ERP + sistema de plugins (`sys_*`)

```bash
php spark migrate
php spark migrate:status
```

Debe listar **todas** las migraciones `App`, `CodeIgniter\Shield` y
`CodeIgniter\Settings` como migradas, **sin pendientes**.

Orden interno: no ejecutar migraciones de plugins sueltas con `php spark migrate`;
las del ERP viven en `app/Database/Migrations/`. Las de cada plugin corren en
`plugin:action install|update`.

### C.4 Datos iniciales (seeders disponibles en repo)

Ejecutar en orden lógico (fallan si faltan tablas):

```bash
php spark db:seed App\\Database\\Seeds\\InitialSeeder
php spark db:seed App\\Database\\Seeds\\UserSeeder
# Opcionales según módulo a probar:
php spark db:seed App\\Database\\Seeds\\BranchSeeder
php spark db:seed App\\Database\\Seeds\\CatalogSeeder
php spark db:seed App\\Database\\Seeds\\BrandSeeder
php spark db:seed App\\Database\\Seeds\\SupplierSeeder
# Usuario maestro documentado en DatamasterSeeder (revisar clase antes de ejecutar)
```

Algunas categorías de proveedor se insertan **dentro de migraciones**
(`CreateFinSupplierCategories`). `SupplierSeeder` requiere esas filas.

### C.5 Permisos Shield

Grupos y permisos base están en `app/Config/AuthGroups.php`. Los permisos de plugins
se fusionan en `pre_system` vía `Events.php` (`permissionCatalog()`).

Tras instalar un plugin, asignar permisos a usuarios (UI
`/nat/user/permissions/{id}` con `admin.manage-users`, o SQL en
`auth_permissions_users` en entornos automatizados).

---

## D. Instalar el sistema de plugins y plugins en disco

### D.1 Prerrequisito (tablas `sys_*`)

Si seguiste C.3, ya existen:

- `sys_plugins`, `sys_plugin_permissions`, `sys_plugin_settings`, `sys_plugin_backups`

Si no:

```bash
php spark migrate
```

### D.2 Plugin incluido en repo: Notas internas

| Campo manifiesto | Valor |
|------------------|--------|
| slug | `notas-internas` |
| directory | `NotasInternas` |
| table_prefix | `plg_notasinternas_` |
| permiso | `notasinternas.access` |

```bash
php spark plugin:doctor
php spark plugin:action install notas-internas
```

`install` ejecuta migraciones del plugin (`Plugins\NotasInternas\Database\Migrations/`),
registra permisos en `sys_plugin_permissions` y deja el plugin **activo** si el
manifiesto es válido (incl. `description` 40–1000 caracteres).

Tras cambios en código o manifiesto en git:

```bash
php spark plugin:action update notas-internas
```

Otros comandos CLI:

```text
php spark plugin:action activate|deactivate <slug>
php spark plugin:action uninstall <slug>    # requiere inactive antes
php spark plugin:action restore <backup_id>
php spark plugin:action purge <backup_id> ELIMINAR
php spark plugin:make <slug>                # crear esqueleto nuevo
```

No guardar secretos con `plugin:action settings` si el ajuste es `type=password`.

### D.3 UI (opcional)

Ruta admin (con login Shield): mercado de plugins bajo permiso `admin.manage-plugins`.
En entornos con Basic Auth nginx del sandbox, la CLI es la vía fiable.

### D.4 Nuevo plugin en el futuro

1. `php spark plugin:make <slug>`
2. Editar `plugins/<Directory>/plugin.json` y código
3. `php spark plugin:action install <slug>`

---

## E. Verificación obligatoria

```bash
php spark plugin:doctor          # 0 errores
php spark migrate                # “Migrations complete” (sin pendientes)
php tests/smoke_notas_internas.php
php tests/smoke_recurring_due.php
php tests/smoke_supplier_reports.php
```

Consultas útiles (ajustar credenciales):

```sql
SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE();
SELECT namespace, version, batch FROM migrations ORDER BY batch, id;
SELECT slug, version, status, table_prefix FROM sys_plugins;
```

**Esperado con NotasInternas instalado:** tabla `plg_notasinternas_items`, filas en
`migrations` con `namespace = 'Plugins\\NotasInternas'`.

---

## F. Web server / sandbox

- Document root: carpeta `public/`, no la raíz del repo.
- `writable/` debe ser escribible por PHP.
- Sandbox puede tener Basic Auth en `.htaccess` / `.htpasswd` (independiente de Shield).
- `app.baseURL` en `.env` debe coincidir con la URL pública (con barra final según convención actual).

---

## G. Rama Git y sincronización

```bash
bash .cursor/skills/sync-rama/scripts/check.sh
# Si RESULTADO = detrás de origin → git pull --ff-only origin feat/plugin-system-architecture
```

Ramas remotas típicas: `main` (producción), `feat/plugin-system-architecture` (desarrollo).

---

## H. Errores frecuentes

| Síntoma | Causa / acción |
|---------|----------------|
| `php spark migrate` falla al bootear plugins | Las tablas `sys_*` deben existir; `Events.php` boot tolerante — migrar primero |
| 404 rutas plugin en web pero OK en CLI | Colisión de permisos en Shield; permisos de plugin deben fusionarse en `pre_system` (código en rama feat) |
| `plugin:doctor` migraciones pendientes | `php spark plugin:action update <slug>` |
| Plugin install rechaza manifiesto | Revisar `description`, permisos, `table_prefix`, slug único |
| BD incompleta tras solo migrate | Normal: usar dump (B) o seeders (C.4) |

---

## I. Checklist resumido para agente Cursor

- [ ] Checkout `feat/plugin-system-architecture` al día con `origin`
- [ ] `composer install`, `.env` completo (DB + baseURL + encryption.key)
- [ ] BD: **dump restore (B)** *o* **migrate + seeders (C)**
- [ ] `php spark plugin:action install notas-internas` (+ `update` si aplica)
- [ ] Asignar `notasinternas.access` a usuario de prueba si se prueba el módulo
- [ ] `plugin:doctor` + smoke tests en `tests/`
- [ ] Documentar host/baseURL y nombre de BD usados (sin contraseñas en el repo)
