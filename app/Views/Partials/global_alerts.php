<?php
/**
 * Lógica para interceptar los mensajes del BaseController (viewData flashdata)
 * y disparar las funciones de tools.js (notifyShow y bigNotificationShow)
 */
?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    <?php if (isset($showAlert) && $showAlert === true && isset($message) && !empty($message)): ?>
        <?php if (isset($success) && $success === true): ?>
            // Si todo fue bien, disparamos una notificación pequeña verde (success)
            notifyShow(<?= json_encode($message) ?>, "success");
        <?php else: ?>
            // Si hubo un error, comprobamos si hay detalle en la variable $error
            <?php 
                $errorDetail = isset($error) && !empty($error) ? $error : 'Ocurrió un error inesperado al procesar la solicitud.';
                // Si el error es un array (validaciones de form), lo formateamos
                if (is_array($errorDetail)) {
                    $errorDetail = implode('<br>', $errorDetail);
                }
            ?>
            // Disparamos el modal grande de error
            bigNotificationShow(
                <?= json_encode($errorDetail) ?>, 
                <?= json_encode($message) ?>, 
                "danger"
            );
        <?php endif; ?>
    <?php endif; ?>

    // También podemos inspeccionar CodeIgniter Flashdata directo de la sesión
    <?php if (session()->getFlashdata('success')): ?>
        notifyShow(<?= json_encode(session()->getFlashdata('success')) ?>, "success");
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        bigNotificationShow(
            <?= json_encode(session()->getFlashdata('error')) ?>, 
            "Error de Operación", 
            "danger"
        );
    <?php endif; ?>
});
</script>

<!-- Modal estático escondido para uso de bigNotificationShow -->
<div class="modal fade" id="ModalAlert" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ModalAlertTitle">Notificación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body m-3">
                <p class="mb-0" id="ModalAlertBody">Mensaje</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Inicializar la instancia de Bootstrap Modal para enviarla a global window y usarla en tools.js
    var ModalAlert;
    document.addEventListener("DOMContentLoaded", function() {
        var modalEl = document.getElementById('ModalAlert');
        if(modalEl) {
            ModalAlert = new bootstrap.Modal(modalEl);
        }
    });
</script>
