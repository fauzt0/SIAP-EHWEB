# Prompt de lanzamiento — PLUGIN-000 (Plugin System Core)

> **Estado:** PLUGIN-000 **ya está implementado** (`ec844b6`, parches `ea37139`). Este archivo es **histórico** (redactado antes del código, spec v1.1).
> **Para auditar el núcleo hoy:** usa el prompt en `agentic/avance.md` (sección «Prompt de auditoría»).
> **Spec vigente:** `agentic/plugin_000_plugin_system_core.md` **v1.2**, sección **0b**.

> **Uso original (archivado):** copiar el bloque de §Prompt en un chat nuevo — solo si se reinicia el núcleo desde cero.
> **Alcance:** infraestructura del sistema de plugins. No implementa los plugins 001–006.

---

## Contexto para quien lanza el chat

- Rama: `feat/plugin-system-architecture` (commits `7e1dbe5`, `85ec18f`, `5e5ab78`).
- No hay código PHP escrito todavía: esta tarea lo crea por primera vez.
- La arquitectura fue auditada y parcheada; **no hay decisiones de diseño abiertas** para el core. Si el modelo detecta una ambigüedad, debe detenerse y reportarla, no improvisar.
- Verificación: **solo CLI** (el sandbox devuelve 401 Basic Auth antes del login Shield, DT-5). No intentar navegador.

---

## Prompt

```
Actúa como desarrollador senior de CodeIgniter 4 y PHP 8.3. Implementa la
infraestructura del sistema de plugins del ERP siguiendo EXACTAMENTE la
especificación. No es una tarea de diseño: es implementación de una spec cerrada.

### Lectura obligatoria (solo esto; no explores el resto del repositorio)
1. @agentic/plugin_000_plugin_system_core.md  ← SPEC VIGENTE, autocontenida (v1.1)
2. @DOCUMENTACION_TECNICA.md                  ← §1–§21 obligatorios (§12, §15, §17 y §19 se usan aquí)
3. Consulta puntual, solo si necesitas contexto:
   - @agentic/ARQUITECTURA_PLUGIN_SYSTEM.md   (§6 hooks v1, §12 permisos, §16 autoload; §4 y §5 están OBSOLETOS)
   - @agentic/AUDITORIA_PLUGIN_SYSTEM.md      (por qué existe cada regla)

### Alcance
- Crea la infraestructura descrita en plugin_000 §3 y §7 (clases, migraciones, Market, partial, comando).
- Aplica los 7 touch points de plugin_000 §4, con el código tal cual se especifica.
- NO implementes ninguno de los plugins 001–006. NO crees lógica de negocio.
- NO toques composer.json ni ejecutes composer.
- NO modifiques los documentos de `agentic/` (ni specs ni arquitectura). Al final solo
  añadirás tu reporte a agentic/AUDITORIA_PLUGIN_SYSTEM.md.
- NO implementes los pendientes marcados (A-4, A-6, version_compare, tabs de offcanvas):
  están fuera de alcance a propósito.
- NO refactorices código existente del ERP ni "mejores" nada fuera de los 7 touch points.

### Reglas críticas que no puedes saltarte (el detalle está en la spec)
- Migraciones del plugin: registrar el namespace EXACTO con Autoloader::addNamespace()
  antes de setNamespace()+latest(). NUNCA usar setGroup() ni setPath() (explicado en §5).
- Permisos: SIN filtro propio. El único touch point es UserController::getAllPermissions()
  usado en permissions() y permissions_save() (§4.7). Nada de plugin_can().
- Desinstalación: PluginBackupService, RENAME atómico, registro ANTES de renombrar,
  sin DROP, y borrado del historial de migraciones del namespace.
- Arranque: tableExists() + try/catch por plugin; un plugin roto no puede tumbar el ERP.
- Settings: sys_plugin_settings es la única fuente de verdad (no existe sys_plugins.config).
- Hooks v1: solo alert.detect y layout.footer.
- Controladores: extienden App\Controllers\PluginSystem\PluginBaseController.
- Iconos: FontAwesome (§12). Fechas ISO en DB (§11). notifyShow() en AJAX (§17).

### Definición de terminado (todo verificable por CLI)
1. php spark migrate crea las 4 tablas sys_* y no falla.
2. php spark plugin:doctor reporta 0 errores.
3. Con un plugin fixture (plugins/Fixture/ con plugin.json + una migración que cree
   plg_fixture_items): instalar → crear tabla real → status active y class_dir='Fixture'.
4. Ciclo completo sin errores fatales: desactivar → desinstalar (tablas _bak_*, fila en
   sys_plugin_backups, y CERO filas en migrations para 'Plugins\Fixture') → restaurar
   (plugin INACTIVE, tablas de vuelta) → purgar (DROP + SQL borrado).
5. Reinstalar tras desinstalar no deja el plugin sin tablas (probar).
6. Un plugin con error fatal en registerHooks() no impide cargar el ERP.
7. php -l limpio en todos los archivos nuevos y modificados.

Si algún criterio no pasa, NO lo maquilles: arréglalo o repórtalo como bloqueo.

### Entrega
- Commitea con: feat(plugin-system): implementar Plugin System Core (PLUGIN-000)
  (un commit por hito lógico está bien; no mezcles cambios del ERP ajeno a esta tarea).
- Añade al final de @agentic/AUDITORIA_PLUGIN_SYSTEM.md una sección
  "Reporte de implementación PLUGIN-000" con: archivos creados, archivos modificados
  (con líneas), salida literal de plugin:doctor, resultado de los 7 criterios,
  ambigüedades encontradas y desviaciones respecto a la spec.
- Si encuentras una ambigüedad que te bloquea: detente, escribe la pregunta en el reporte
  y no inventes comportamiento nuevo.

### Eficiencia de tokens
- La spec ya contiene el código de referencia: cópialo/ajústalo en lugar de rediseñarlo.
- No leas archivos del ERP que no estén listados en §4 salvo que un error lo exija;
  si lo haces, indica en el reporte cuál y por qué.
- No releas los documentos completos en cada paso: usa la spec como fuente única.
```

---

## Presupuesto de alcance (para controlar el gasto de tokens)

| Fase | Trabajo | Archivos |
|------|---------|----------|
| A | Clases del core + `PluginBaseController` | ~7 nuevos |
| B | 4 migraciones + `plugins/.gitkeep` | 5 nuevos |
| C | Market (controlador + vista) + partial del sidebar | 3 nuevos |
| D | 7 touch points en el ERP | 7 modificados |
| E | `plugin:doctor` + plugin fixture + pruebas CLI | 2–4 nuevos |

Si el modelo termina la fase A y B pero no C–E, el trabajo es reanudable sin rehacer nada: las fases son independientes entre sí salvo la E, que depende de A–B.
