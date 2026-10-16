# Avance del núcleo de plugins

Fecha: 2026-10-16  
Rama: `feat/plugin-system-architecture`  
Estado: núcleo v1 implementado, verificado por CLI y corregido. Los plugins 001–006 y PLUGIN-000b no están codificados.

## Commits de esta línea

- `30f4e9a` — spec v1.2 del núcleo cerrada antes de codificar.
- `ec844b6` — implementación del núcleo (mercado, ciclo de vida, respaldos, hooks, doctor).
- El commit que acompaña este archivo corrige los fallos encontrados en la verificación posterior.

## Qué quedó hecho

El núcleo vive en el ERP, no en un plugin de ejemplo. Cubre:

- Tablas `sys_plugins`, `sys_plugin_permissions`, `sys_plugin_settings`, `sys_plugin_backups`.
- `PluginManager`, `PluginBackupService`, `PluginHookRegistry`, `PluginMarket`, `BasePlugin`.
- Mercado en `/nat/admin/plugins`, permiso `admin.manage-plugins`, filtros de sesión y permiso del grupo admin.
- Hooks v1: `alert.detect` (collect) y `layout.footer` (action). El sidebar sale de `getSidebarItems()`, las rutas de `loadRoutes()` y los assets de `renderAssets()`.
- Comandos `plugin:doctor` y `plugin:action` (`install`, `activate`, `deactivate`, `uninstall`, `restore`, `purge`, `update`, `settings`).
- Desinstalar no hace `DROP`. Archiva con `RENAME TABLE` atómico y un SQL en `writable/plugins/backups/`. Purgar exige el texto `ELIMINAR`.
- Reinstalar con un respaldo pendiente y no restaurado se rechaza. Restaurar deja el plugin `inactive` y reescribe el historial de migraciones del namespace `Plugins\{Directory}` para que el siguiente `update` no vuelva a crear tablas que ya existen.
- Permisos de plugin fuera de los prefijos comodín de Shield (`admin.*`, `superadmin.*`, `users.*`, `finance.*`, `inventory.*`, `sales.*`, `purchasing.*`, `hr.*`, `catalog.*`).

La spec que manda es `agentic/plugin_000_plugin_system_core.md` v1.2, sección **0b**. Esa sección pisa los bloques anteriores del mismo archivo. En `agentic/ARQUITECTURA_PLUGIN_SYSTEM.md` v1.2, las secciones 4 y 5 son historial y no se implementan. `DOCUMENTACION_TECNICA.md` §1–§21 sigue siendo la norma del ERP.

## Verificación de esta pasada

Hecha por CLI. `https://sandbox.especialistashosting.com/nat/admin/plugins` responde HTTP 401 con Basic Auth de nginx (`Area de desarrollo`) antes de Shield. Esa pantalla no se abrió en el navegador y no se evadió la autenticación.

Ciclo con un plugin temporal `fixture` (borrado al terminar, junto con sus SQL, filas y logs):

- Instalar crea la tabla del prefijo y deja el plugin activo.
- Un ajuste que no está en el manifiesto se rechaza.
- Desactivar y desinstalar archivan la tabla y el SQL. Volver a instalar se niega mientras el respaldo siga pendiente.
- Restaurar devuelve las tablas y deja el plugin inactivo. `update` después de restaurar termina bien y la tabla sigue existiendo.
- Purgar con `ELIMINAR` elimina las tablas archivadas y el SQL.

`php spark plugin:doctor` al cerrar: 0 errores, 0 advertencias, 0 plugins, 0 manifiestos. No quedan tablas `plg_fix_*` ni `_bak_plg_fix_*`.

## Correcciones de esta pasada

- `restore()` ya no concatena la ruta del plugin antes de comprobar que el directorio existe.
- Si el alta o el registro posterior a la restauración fallan, se borra la fila de `sys_plugins`, se retiran los permisos de ese slug, se borra el historial de migraciones de ese namespace y las tablas vuelven a su nombre archivado.
- `purge()` valida todos los nombres archivados antes del primer `DROP`. Un nombre ilegal ya no deja tablas anteriores eliminadas.
- Un respaldo sin tablas ya no inventa el prefijo `plg_none_`.
- `archive()` rechaza un `directory` que no sea PascalCase.
- El nombre del SQL de respaldo reintenta como máximo 5 segundos y luego falla, en lugar de dormir sin límite.
- `saveSettings()` rechaza claves que el manifiesto no declara.
- `renderAssets()` solo publica CSS/JS cuyo `realpath` queda dentro de `public_html/plugins/{slug}/`.
- El arranque ignora un slug de base de datos que no cumpla el formato del manifiesto.
- `plugin:doctor` no arma rutas con un `class_dir` inválido. Un manifiesto en disco cuyo `directory` no coincide con la carpeta, y que no está instalado, es advertencia. Un plugin instalado con manifiesto roto sigue siendo error.

## Fuera de alcance

- No se codificaron `agentic/plugin_001_*.md` … `plugin_006_*.md` ni PLUGIN-000b.
- No hay carga de ZIP. El código del plugin ya está en disco; un admin lo instala y corre con el usuario de base de datos de la aplicación. No es un sandbox.
- `min_erp_version` no se compara: el ERP no publica una constante de versión para eso.
- No hay caché de la lista de plugins.
- Los hooks reservados no despachan callbacks.
- `plugin:doctor` y el ciclo de vida se probaron por CLI. La UI del mercado queda pendiente de una sesión que ya haya pasado el Basic Auth.

## Riesgos que siguen abiertos

- Un plugin instalado ejecuta PHP con el mismo usuario de base de datos que el ERP.
- Si `registerPermissions` o `markMigrationsApplied` fallan a la mitad, el `catch` de `restore()` limpia la fila, los permisos del slug y el historial de ese namespace, y revierte el `RENAME`. Esa rama no tiene una prueba que fuerce la excepción.
- La UI del mercado (modales, CSRF, botón de purga deshabilitado hasta escribir `ELIMINAR`) está escrita y no se ejerció en el navegador por el 401.
- Un manifiesto inválido todavía no instalado puede dejar una advertencia en `plugin:doctor` y no tumba el comando.

---

## Prompt de auditoría

Copia el bloque siguiente a otro agente, con otro modelo. No implementes los plugins 001–006 como parte de esa auditoría.

```text
Audita el núcleo del sistema de plugins de este ERP CodeIgniter 4.5.3 + Shield 1.1. No reescribas la arquitectura y no codifiques los plugins descritos en agentic/plugin_001_*.md … agentic/plugin_006_*.md ni PLUGIN-000b. El trabajo es encontrar fallos reales en el código que ya está en la rama feat/plugin-system-architecture.

Lee, en este orden:
1. DOCUMENTACION_TECNICA.md secciones 1 a 21. Esa es la norma del ERP: permisos Shield, sin db_connect() en controladores, logs de actividad, filtros, layouts.
2. agentic/plugin_000_plugin_system_core.md v1.2. La sección 0b pisa cualquier bloque anterior del mismo archivo que la contradiga.
3. agentic/ARQUITECTURA_PLUGIN_SYSTEM.md v1.2. Las secciones 4 y 5 son historial; no las uses como contrato de implementación.
4. agentic/AUDITORIA_PLUGIN_SYSTEM.md (cierre v1.2 e informe de implementación) y agentic/AUDITORIA_ARQUITECTURA.md REPORTE-016.
5. agentic/avance.md, para saber qué ya se corrigió en la verificación del 2026-10-16 y no reportarlo otra vez como si siguiera abierto.

Código a revisar:
- app/Libraries/PluginSystem/ (PluginManager, PluginBackupService, PluginHookRegistry, PluginMarket, BasePlugin, PluginInterface)
- app/Controllers/Admin/PluginMarketController.php
- app/Controllers/PluginSystem/PluginBaseController.php
- app/Views/Admin/plugins_market.php
- app/Views/PluginSystem/sidebar_section.php
- app/Commands/PluginDoctor.php y app/Commands/PluginAction.php
- app/Database/Migrations/System/2026-09-18-200000_CreateSysPlugins.php hasta 200003_CreateSysPluginBackups.php
- Puntos de enganche: app/Config/Autoload.php, Events.php, Routes.php, AuthGroups.php, el sidebar de sesión, user_loggedin_layout.php, AlertController::_detectAll() y UserController::getAllPermissions()
- app/Models/Users/UserActivityLogsModel.php (user agent en CLI)

Hechos de CI4 4.5.3 que ya se comprobaron y no debes “corregir” al revés:
- Services::migrations(null, null, false) crea un runner no compartido. setGroup() es el grupo de conexión, no un grupo lógico de plugin. setPath() no existe. latest() con grupo null consulta historial sin filtrar por grupo; el filtro real es el namespace.
- FileLocator solo descubre migraciones si el namespace exacto Plugins\{Directory} está registrado con addNamespace() durante install/update/restore. El prefijo Plugins\ del Autoload no alcanza para descubrirlas y no debe registrarse en el arranque, porque php spark migrate recorrería las migraciones de plugins.
- php spark no dispara pre_system. loadRoutes() tiene que llamar a bootActivePlugins(), que es idempotente.
- FCPATH de la web es public_html/. spark apunta FCPATH a public/, que no sirve el sitio.
- where('id !=', $id) es SQL válido en este CI4: hasOperator() acepta '!'.
- La columna class de migrations es VARCHAR(255). El grupo por defecto es 'default'.
- El layout cierra </body> antes de app.js. layout.footer se dispara después de los scripts y antes de </html>.
- users_id del log de actividad es nullable. En CLI no existe getUserAgent(); el modelo usa 'CLI'.

Contrato de seguridad que el código debe cumplir:
- Slug ^[a-z0-9]+(?:-[a-z0-9]+)*$. directory PascalCase ^[A-Z][A-Za-z0-9]+$, sin rutas.
- table_prefix ^plg_[a-z0-9]+_$. Identificadores solo [A-Za-z0-9_].
- Nombres archivados ^_bak_{prefix}.+_\d{8}_\d{6}$. El archivo SQL solo puede vivir bajo writable/plugins/backups/{slug}_{stamp}.sql y el realpath no puede salir de ese directorio.
- LIKE trata '_' como comodín. La propiedad de tablas se decide por str_starts_with y por el prefijo más largo, para que plg_fix_ no se quede con tablas de plg_fix_extra_.
- Desinstalar nunca hace DROP. El RENAME es atómico. La fila de sys_plugin_backups se inserta antes del RENAME; si el RENAME falla, se borra la fila y el SQL.
- Purgar exige la confirmación exacta ELIMINAR, valida todos los nombres y solo entonces hace DROP. No exige que los archivos del plugin sigan en disco.
- Restaurar se niega si purged_at o restored_at están puestos, o si el slug ya está en sys_plugins. Deja el plugin inactive. Si algo falla después del RENAME, la fila, los permisos y el historial de ese namespace se retiran y las tablas vuelven al nombre archivado.
- Permisos de plugin no pueden usar un prefijo que AuthGroups.matrix ya concede por comodín, ni chocar con AuthGroups.permissions.
- Un plugin que lance en registerHooks() no puede tumbar php spark routes. Cada callback de hook va en try/catch. Si un filtro falla, se conserva el valor anterior.
- renderAssets() no puede leer fuera de public_html/plugins/{slug}/.
- El mercado remoto, si PLUGIN_MARKET_URL existe, es solo HTTPS, timeout 3, sin seguir redirecciones, y sus ítems no son instalables. Gana el manifiesto local. No hay carga de ZIP.
- Controladores del mercado sin db_connect(). El filtro de ruta es la autorización real; un 404 de assertPermission no la sustituye.
- No imprimas host, usuario ni contraseña de la base de datos. php spark db:table los muestra en la cabecera.

Ya corregido el 2026-10-16 (no lo reabras salvo que el código haya regresado):
concatenación null en restore(), DROP parcial en purge(), éxito silencioso de saveSettings() con claves desconocidas, traversal de renderAssets() por un slug hostil, uniqueStamp() sin tope, directory sin validar en archive(), rollback incompleto de restore(), class_dir sin validar en plugin:doctor, y el directorio en disco no instalado contado como error fatal.

Riesgos conocidos, para que los confirmes o descartes con evidencia:
- El PHP del plugin corre con el usuario de base de datos de la aplicación. No es un sandbox.
- La rama de rollback de restore() (excepción dentro de registerPermissions o markMigrationsApplied) no tiene una prueba que fuerce el fallo.
- La UI /nat/admin/plugins no se ejerció en navegador: el sandbox responde HTTP 401 Basic Auth antes de Shield. No evadas ese 401. Si no puedes entrar, dilo y verifica por CLI.
- min_erp_version no se compara. No hay caché de plugins. Los hooks reservados no despachan.

Cómo comprobar sin dejar basura:
- php -l en los PHP que toques y php spark plugin:doctor.
- Si necesitas un ciclo install → deactivate → uninstall → restore → update → purge, usa un plugin temporal bajo plugins/, con prefijo plg_ de prueba, y al terminar borra el directorio, los SQL de writable/plugins/backups/, las filas de sys_plugins, sys_plugin_permissions, sys_plugin_settings, sys_plugin_backups, el historial migrations del namespace Plugins\ de esa prueba, y los logs users_activity_logs de tipo plugin_%.
- No hagas commit ni push salvo que quien te encargó la auditoría lo pida.
- No modifiques .env ni la configuración de git.

Entrega:
- Bloqueantes: fallo que corrompe datos, salta permisos, ejecuta SQL/PHP fuera del directorio del plugin, o rompe el arranque del ERP.
- Defectos: comportamiento incorrecto con reproducción.
- Notas: estilo o riesgo residual que no impide usar el núcleo.
Cada hallazgo cita archivo y líneas, dice qué contrato rompe y cómo se reproduce. Si no hay bloqueantes, dilo explícitamente.
```
