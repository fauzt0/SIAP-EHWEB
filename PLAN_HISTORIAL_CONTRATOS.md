# PLAN DE IMPLEMENTACIÓN: HISTORIAL DE CONTRATOS Y CORRECCIÓN PDF

Este documento contiene la hoja de ruta para completar el sistema de historial de contratos y corregir los errores detectados en la previsualización PDF.

## 1. Corrección del Bug de PDF (Caracteres Extraños)
- **Problema:** El navegador muestra código binario en lugar del archivo PDF.
- **Causa:** El controlador no está enviando el encabezado `Content-Type: application/pdf`.
- **Solución:**
    - Modificar `App\Services\ContractService::exportToPdf` para que devuelva el contenido binario del PDF.
    - Modificar `App\Controllers\HR\WorkerController::downloadContract` para que reciba ese binario y lo devuelva usando el objeto Response de CodeIgniter 4 con el tipo de contenido correcto.

## 2. Nueva Pantalla de Historial Completo
- **Ruta:** `nat/hr/contracts/history/(:num)`
- **Funcionalidad:**
    - Tabla con todas las versiones del contrato del trabajador.
    - Buscador integrado (Filtro por texto/comentario).
    - Selector de rango de fechas.
    - Columnas: Versión, Tipo de Contrato, Motivo/Comentario, Fecha de Generación, Estatus.
- **Acciones:**
    - Botón **Ver**: Abrirá un modal (tipo "popup") que cargará un iframe con la vista previa del contrato para evitar salir de la página.
    - Botón **Descargar**: Descarga directa del PDF.

## 3. Mejoras en el Offcanvas (Panel Lateral)
- **Vista Rápida:** Mostrar los últimos 5 movimientos del contrato en la pestaña de "Contratos".
- **Botón de Enlace:** Añadir un botón "Gestionar Historial Completo" que redirija a la nueva pantalla detallada.
- **Lógica AJAX:** Actualizar el cargador del offcanvas para que traiga estos últimos 5 registros automáticamente al abrirse.

## 4. Automatización del Histórico
- Asegurar que cualquier cambio en los datos del trabajador (sueldo, puesto, dirección) dispare la generación de un nuevo contrato en el histórico usando la plantilla predeterminada (Default).
- El comentario automático debe ser: "Actualización automática por cambios en datos del trabajador".

## 5. Resumen de Archivos a Tocar
- `app/Config/Routes.php` (Nuevas rutas).
- `app/Controllers/HR/WorkerController.php` (Lógica de descarga y nueva vista de historial).
- `app/Services/ContractService.php` (Ajuste en la exportación binaria).
- `app/Views/HR/Partials/worker_offcanvas.php` (Diseño de la lista resumida).
- `app/Views/HR/contracts/worker_contracts_history.php` [NUEVO] (Diseño de la pantalla de gestión).

---
**Instrucción para la siguiente sesión:**
Al retomar el trabajo, inicia ejecutando la corrección de `ContractService.php` para habilitar la previsualización PDF correcta, y luego procede a crear la vista `worker_contracts_history.php`.

**Nota de recuperación (si el historial del chat no aparece):**
Si al abrir el chat en otro equipo el historial aparece vacío, indícame lo siguiente:
> "Lee los logs de la conversación **c432cc7f-fcb4-43ee-a47b-9ecb0a3dfd06** para retomar el contexto y sigue el plan en **PLAN_HISTORIAL_CONTRATOS.md**."

Con esto podré leer los registros guardados en el servidor y continuar sin perder información.

