//notificaciones pequeñas para alertas del sistema
function notifyShow(message, type) {
    var duration = 5000; //duracion de las notificaciones default 5s
    var ripple = false;
    var dismissible = true;
    var positionX = "right";
    var positionY = "top";
    window.notyf.open({
        type,
        message,
        duration,
        ripple,
        dismissible,
        position: {
            x: positionX,
            y: positionY
        }
    });
}

//notificaciones grandes para alertas del sistema
function bigNotificationShow(message, title, type) {
    let modal_class = "modal-" + type;
    let modal_title = title;
    let modal_message = message;
    //quitamos las clases info, success, warning y danger
    $("#ModalAlert").removeClass('modal-success');
    $("#ModalAlert").removeClass('modal-warning');
    $("#ModalAlert").removeClass('modal-danger');
    $("#ModalAlert").removeClass('modal-info');

    //agregamos la clase correspondiente
    $("#ModalAlert").addClass(modal_class);
    $("#ModalAlertTitle").html(modal_title);
    $("#ModalAlertBody").html(modal_message);


    //mostramos el modal
    ModalAlert.show();

}