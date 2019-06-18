function removeNewModal() {
    $(".Modal-Content").remove();
    $(".Modal-dialogo").addClass(" d-none");
    $(".Modal-fondo").addClass(" d-none");
}
function CrearModal(tipo, datos) {
    var modal = "";
    switch (tipo) {
        case "alerta":
            modal += "<div class='Modal-content btn' style='background-color: " + datos[1] + ";width:80%!important;'>" +
                    "<div style='padding-left: 35px!important;padding-right: 21px;text-align: center;' class='Modal-Cabecera'>" + datos[0] + "</div>" +
                    "<div class='Modal-Cuerpo'><button class='btn' style='margin-left:40%;' onclick='removeNewModal();' id='" + datos[2] + "'>Aceptar</button></div>" +
                    "</div>";
            $(".Modal-dialogo").html(modal);
            break;
        case "insertar":
            modal += "<div class='Modal-content btn' style='background-color: " + datos[0] + ";height: 85%!important;'>" +
                    "<div class='Modal-Cabecera' style='padding-bottom: 0%;height:40%; padding-top:0px; text-align: center;padding-right: 30px;'>Dime el nombre de la nueva Seccion</div>" +
                    "<div class='Modal-Cuerpo' style='display:block; max-width: 59%;margin-left: 55px;'><div class='Modal-Input'><input style='width: 100%;' type='text' id='NewSeccion'/></div>" +
                    "<div id='errorValidacion'></div>" +
                    "<div class='Modal-Btn' style='float: left;'><button class='btn' onclick='AnadirNuevaSeccion();'>Aceptar</button></div>" +
                    "<div class='Modal-Btn' style='float: left;'><button class='btn' onclick='removeNewModal();'>Cancelar</button></div>" +
                    "</div></div>";
            $(".Modal-dialogo").html(modal);
            break;
        case "insertarAmbito":
            modal += "<div class='Modal-content btn' style='background-color: " + datos[0] + ";'>" +
                    "<div class='Modal-Cabecera' style='height:40%; padding-top:0px; text-align: center;padding-right: 30px;'>Dime el nombre del nuevo Ambito</div>" +
                    "<div class='Modal-Cuerpo' style='padding-top: 10px;display:block; max-width: 50%;margin-left: 100px;'><div class='Modal-Input'><input type='text' id='NewAmbito'/></div>" +
                    "<div id='errorValidacion'></div>" +
                    "<div class='Modal-Btn' style='float: left;'><button class='btn' onclick='AnadirNuevoAmbito();'>Aceptar</button></div>" +
                    "<div class='Modal-Btn' style='float: left;'><button class='btn' onclick='removeNewModal();'>Cancelar</button></div>" +
                    "</div></div>";
            $(".Modal-dialogo").html(modal);
            break;
        case "decision":
            modal += "<div class='Modal-content btn' style='background-color: " + datos[1] + ";height: 82%!important;'>";
            modal += "<div class='Modal-Cabecera' style='text-align: center;height: 60%;'>" + datos[0] + "</div>" +
                    "<div class='Modal-Cuerpo' style='padding-top: 5%;'><div class='Modal-Btn' style='padding-top=0%!important;'><button class='btn' onclick=\""+datos[2]+";\">Aceptar</button></div>" +
                    "<div class='Modal-Btn' style='padding-top=0%;'><button class='btn' onclick='removeNewModal();' id='" + datos[3] + "'>Cancelar</button></div>" +
                    "</div></div>";
            $(".Modal-dialogo").html(modal);
            break;
        case "FormularioGenerarWord":
            modal += "<div class='Modal-content btn' style='background-color: " + datos[0] + "; height: 170%;'>" +
                    "<div class='Modal-Cabecera' style='height: 40px; padding-top:0px; text-align: center;padding-right: 30px;'>" + datos[1] + "</div>" +
                    "<div class='Modal-Cuerpo' style='padding-top: 10px;display:block; max-width: 70%;margin-left: 50px;'>" +
                    "<div>Titulo de Proyecto</div><div style='padding-top:10px;padding-bottom: 10px;'><input type='text' id='Titu_proyecto'></div>" +
                    "<div>Nombre de Cliente</div><div style='padding-top:10px;padding-bottom: 10px;'><input type='text' id='Nombre_cliente'></div>" +
                    "<div>Logo de cliente</div><div style='padding-top:10px;padding-bottom: 10px;'><input type='file' id='logo_cliente'></div>" +
                    "<div id='errorValidacion'></div>" +
                    "<div class='Modal-Btn' style='float: left;'><button class='btn' onclick='GenerarWord();'>Aceptar</button></div>" +
                    "<div class='Modal-Btn' style='float: left;'><button class='btn' onclick='removeNewModal();'>Cancelar</button></div>" +
                    "</div></div>";
            $(".Modal-dialogo").html(modal);
            break;
        case "alertaRedireccionamiento":
            modal += "<div class='Modal-content btn' style='background-color: " + datos[1] + ";width: 80%;'>" +
                    "<div style='text-align: center;' class='Modal-Cabecera'>" + datos[0] + "</div>" +
                    "<div class='Modal-Cuerpo'><button class='btn' style='margin-left: 40%;height: 50px;' onclick='inicio();'>Aceptar</button></div>" +
                    "</div>";
            $(".Modal-dialogo").html(modal);
            break;
    }
    $(".Modal-dialogo").removeClass(" d-none");
    $(".Modal-fondo").removeClass(" d-none");
}
function AnadirResultado($texto, $resul, idEliminar) {
    switch ($resul) {
        case "success":
            CrearModal("alerta", new Array("Se realizo con exito!", "green", "CerrarModal"));
            //CrearModal("Se realizo con exito!", "green", "CerrarModal", null);
            //removeNewModal();
            $("#Secciones").load("CreadorSecciones.php #Secciones", function () {
                anadirBotonEliminar();
                BtnEliminarSeccion();
                dragSecciones();
            });
            break;
        case "error":
            $("#errorValidacion").html($texto);
            $("#errorValidacion").addClass("error");
            $("#NewSeccion").focus();
            break;
        case "successEliminar":
            //CrearModal("Se realizo con exito!", "green", "CerrarModal", null);
            CrearModal("alerta", new Array("Se elimino con exito!", "green", "CerrarModal"));
            $("#" + idEliminar).remove();
            break;
    }
}