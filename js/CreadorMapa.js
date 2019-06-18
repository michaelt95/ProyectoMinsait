$(document).ready(function () {
    /* Cargando CreadorSecciones.php */
    console.log("cargando CreadorSecciones.php");
    //drags
    dragSecciones();
    /*añadir botones de eliminar seccion*/
    anadirBotonEliminar();
    /*Fin Añadir botones*/
    BtnEliminarSeccion();
    $("#ListaSecciones div").on("click", function () {
        switch ($(this).attr("id")) {
            case "Anadir":
                CrearModal("insertar", new Array("white"));
                break;
        }
    });
    var arrayNomSecciones;
    $("#finalizarSeccion div").on("click", function () {
        switch ($(this).attr("id")) {
            case "Aceptar":
                window.history.replaceState(new Date(), null, "Index.php?P=SeleccionAmbitos");
                //Comprobamos que no este vacio el Div Mapa
                arrayNomSecciones = document.getElementById("Mapa").childNodes;
                console.log(arrayNomSecciones);
                //console.log(arrayNomSecciones);
                if (arrayNomSecciones.length > 0) {
                    CargaSeleccionAmbitos();
                    //creacion del btn Visualizar
                    $("#finalizarSeccion").css("width", "40%!important");
                    $("#finalizarSeccion").prepend($("<div/>", {
                        "class": "divGenerico",
                        "id": "Visualizar",
                        "text": "Visualizar"
                    }));
                } else {
                    CrearModal("alerta", new Array("Tienes que seleccionar al menos una sección", "rgb(245, 202, 14)", "warning"));
                }
                break;
            case "Reset":
                window.history.replaceState(new Date(), null, "Index.php?P=CreadorSecciones");
                $("#Secciones").load("CreadorSecciones.php #Secciones", function () {
                    anadirBotonEliminar();
                    BtnEliminarSeccion();
                    dragSecciones();
                });
                $("#Mapa").html("");
                break;
            case "Atras":
                window.history.replaceState(new Date(), null, "Index.php");
                window.location.replace("Index.php");
                break;
        }
    });
    $(".Modal-fondo").on("click", function () {
        $(this).addClass(" d-none");
        $(".Modal-dialogo").addClass(" d-none");
    });
});
//Variables para dragAmbitos() y dragSecciones()
var divClone, divInterior, coordenadas, idNew, position, divGirar;
//fin declaracion variables
/* Function SeleccionAmbitos.php */
var arrayIdsAmbitos = [], mapa, ambito;
;
function CargaSeleccionAmbitos() {
    /* Cargando SeleccionAmbitos.php */
    console.log("cargando SeleccionAmbitos.php");
    //carga de Nombre de Ambitos
    cargaAmbitos();
    //introducimos el nuevo menu contextual
    $("#Principal").prepend($("<ul/>", {
        "id": "menu"
    }).append(
            $("<li/>", {
                "text": "Selecciona un color para el fondo "
            }).append($("<input/>", {
        "type": "color",
        "id": "NewColorBg"
    })),
            $("<li/>", {
                "text": "Selecciona un color para el borde "
            }).append($("<input/>", {
        "type": "color",
        "id": "NewColorBorder"
    })), $("<li/>", {
        "text": "Aplicar el estilo guardado"
    }).append(), $("<li/>", {
        "text": "Aplicar a todos el mismo estilo"
    }).append(), $("<li/>", {
        "text": "Aplicar el estilo predeterminado"
    }).append(), $("<li/>", {
        "text": "Aplicar a todos el estilo predeterminados"
    }).append()
            ));
    setTimeout(function () {
        //borramos los eventos que tenian antes los botonos Aceptar,Reset, Atras y Añadir 
        $("#finalizarSeccion div").unbind("click");
        $("#ListaSecciones div").unbind("click");
        //hacemos draggable los Ambitos
        dragAmbitos();
        //eliminamos boton añadir
        $("#Anadir").remove();
        //evento para el btn Visualizar
        eventoVisualizarMapa();
        //evento menu
        eventoMenu();
        //creamos nuevos eventos para los botonos Aceptar,Reset y Atras
        $("#finalizarSeccion div").on("click", function () {
            switch ($(this).attr("id")) {
                case "Aceptar":
                    var idsAmbitos = $("#Mapa div[class*='interiorWrapAmbitos']");

                    for (var i = 0; i < idsAmbitos.length; i++) {
                        arrayIdsAmbitos[i] = idsAmbitos[i].id;
                    }
                    console.log(arrayIdsAmbitos);

                    if (arrayIdsAmbitos.length > 0) {
                        //Generacion de Word
                        CrearModal("FormularioGenerarWord", new Array("cornflowerblue", "Informacion del Mapa"));
                    } else {
                        CrearModal("alerta", new Array("Tienes que seleccionar al menos un Ambito", "rgb(245, 202, 14)", "warning"));
                    }
                    break;
                case "Reset":
                    //eliminar todos los divs de ambitos arrastrados a mapa y recargar la zona de Ambitos
                    //añadir dragas botones
                    window.history.replaceState(new Date(), null, "Index.php?P=GenerarMapa");
                    $("#Mapa div[class*=interiorWrapAmbitos]").each(function () {
                        $(this).remove();
                    });
                    CargaSeleccionAmbitos();
                    arrayIdsAmbitos = [];
                    break;
                case "Atras":
                    //recargar pagina creadorSecciones.php
                    window.history.replaceState(new Date(), null, "Index.php?P=CreadorSecciones");
                    window.location.replace("CreadorSecciones.php");
                    break;
            }
        });
        //fin SeleccionAmbitos.php
    }, 100);
}
function eventoMenu() {
    //evento para seleccionar color de fondo
    $("#menu li input").eq(0).on("change", function (event) {
        //console.log(event.target.value);
        $(ambito).css("background-color", event.target.value);
    });
    //evento para seleccionar color del borde
    $("#menu li input").eq(1).on("change", function (event) {
        //console.log(event.target.value);
        $(ambito).css("border", "2px dashed " + event.target.value);
    });
    //evento para aplicar el estilo guardado
    $("#menu li").eq(2).on("click", function () {
        console.log(ambito);
        $(ambito).css({"background-color": $("#NewColorBg").val(), "border": "2px dashed "+$("#NewColorBorder").val()});
    });
    //evento para aplicar a todos el mismo estilo
    $("#menu li").eq(3).on("click", function () {
        $("#Mapa div[class*=interiorWrapAmbitos]").each(function (){
            $(this).css({"background-color": $("#NewColorBg").val(), "border": "2px dashed "+$("#NewColorBorder").val()});
        });
    });
    //evento para aplicar el estilo predeterminado
    $("#menu li").eq(4).on("click", function () {
        console.log(ambito);
        $(ambito).css({"background-color": "white", "border": "2px dashed #23527c"});
    });
    //evento para aplicar el estiloa predeterminado a todos
    $("#menu li").eq(5).on("click", function () {
        $("#Mapa div[class*=interiorWrapAmbitos]").each(function (){
            $(this).css({"background-color": "white", "border": "2px dashed #23527c"});
        });
    });
}
function eventoVisualizarMapa() {
    let click = false;
    $("#Visualizar").on("click", function () {
        if (!click) {
            $(this).addClass("selecVisu");
            $("#Mapa div[class*=interiorWrap] div[id*=-Sector-draggable]").each(function () {
                $(this).css("border", "none").children("div").css("display", "none");
                $(this).parent().addClass("ui-resizable-disabled");
            });//
            $("div[id=Mapa] div[class*=interiorWrapAmbitos] div").each(function () {
                $(this).css("display", "none");
            });
            click = true;
        } else {
            $(this).removeClass("selecVisu");
            $("#Mapa div[class*=interiorWrap] div[id*=-Sector-draggable]").each(function () {
                $(this).css({"border": "2px dashed #23527c", "border-radius": "26px 26px 26px 26px"}).children("div").css("display", "block");
                $(this).parent().removeClass("ui-resizable-disabled");
            });
            $("div[id=Mapa] div[class*=interiorWrapAmbitos] div").each(function () {
                $(this).css("display", "block");
            });
            click = false;
        }
    });
}
function eventoClickDerechoAmbito(selector) {
    $(selector).bind("contextmenu", function (e) {
        e.preventDefault();
        /*quitamos el display none y traemos el nuevo menu contextual*/
        ambito = selector;
        localizacionPuntero(e);
        // conjunto de acciones a realizar
        //console.log("btn derecho capturado");

    });
}
var arrayEstilo;
function localizacionPuntero(e) {
    cm = document.getElementById("menu");
    //colocamos las coordenadas
    cm.style.top = !e ? event.clientY : e.clientY;
    cm.style.left = !e ? event.clientX : e.clientX;
    cm.style.display = "block";
    //console.log(ambito);
    //eventos onChange y click en predetederminado

    //ocultar menu
    $("#Principal").on("click", "div", function () {
        if ($(this).attr("id") === "Principal" || $(this).attr("id") === "Sectores" || $(this).attr("id") === "finalizarSeccion") {
            cm.style.display = "none";
        }
    });
}
function cargaAmbitos() {
    $("#Titulo h2").text("Seleccionar Ambitos");
    $("#Secciones").attr("id", "Ambitos");
    $("#Ambitos").load("SeleccionAmbitos.php");
}
function GenerarWord() {
    //eliminamos el shadowBox
    $(".Modal-Cuerpo input[class*=error-shadowBox]").each(function () {
        $(this).removeClass("error-shadowBox");
    });
    //variable para continuar
    var generar = true;
    //Recoger campos
    var titu_proyect = document.getElementById("Titu_proyecto");
    var name_cliente = document.getElementById("Nombre_cliente");
    var logo_cliente = document.getElementById("logo_cliente").files[0];


    //expresion regular
    var exp1 = /^([A-z]+[]+|[A-z]+)/;
    var exp2 = /(.png|.jpg|.jpeg)/;
    //validaciones
    if (!exp1.test(titu_proyect.value)) {
        AnadirResultado("Solo puede contener palabras y No puede estar vacio", "error");
        generar = false;
        titu_proyect.focus();
        titu_proyect.className = "error-shadowBox";
        return;
    }
    if (!exp1.test(name_cliente.value)) {
        AnadirResultado("Solo puede contener palabras y No puede estar vacio", "error");
        generar = false;
        name_cliente.focus();
        name_cliente.className = "error-shadowBox";
        return;
    }
    if (logo_cliente) {
        if (!exp2.test(logo_cliente.name.toLowerCase())) {
            AnadirResultado("Tiene que ser en formato jpg,png,jpeg y No puede estar vacio", "error");
            generar = false;
            document.getElementById("logo_cliente").focus();
            document.getElementById("logo_cliente").className = "error-shadowBox";
            return;
        }
    } else {
        AnadirResultado("Tienes que seleccionar un logo de cliente", "error");
        generar = false;
        document.getElementById("logo_cliente").focus();
        document.getElementById("logo_cliente").className = "error-shadowBox";
        return;
    }
    if (generar) {
        var formData = new FormData();
        formData.append("titulo", titu_proyect.value);
        formData.append("nombreCliente", name_cliente.value);
        formData.append("logo", logo_cliente);
        formData.append("idsAmbitos", JSON.stringify(arrayIdsAmbitos));
        $.ajax({
            url: "back-end/GeneradorWord.php",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (resp) {
                if (resp !== "error") {
                    window.open("back-end/GeneradorWord.php?fichero=" + encodeURI(resp), "_black");
                    setTimeout(function () {
                        CrearModal("alertaRedireccionamiento", new Array("Documento Generado volviendo a Indice", "cornflowerblue"));
                    }, 200);
                } else {
                    AnadirResultado("Error al crear el fichero", "error");
                }
                removeNewModal();
            }
        });
    }
}
function inicio() {
    window.location.href = 'Index.php';
}
function dragAmbitos() {
    //console.log("entra");
    //console.log($(".ambito").length);
    $(".ambito").each(function () {
        $(this).on("click", function () {
            idNew = $(this).parent().attr("id");
            //console.log(idNew);
            //Creacion de los divs
            divClone = document.createElement("div");
            divGirar = document.createElement("div");
            //atributos de los divs

            divGirar.className = "icon-girar";
            divGirar.id = "iconAmbito-" + idNew.split("-")[0];

            divClone.id = idNew;
            divClone.className = "interiorWrapAmbitos mover";
            divClone.textContent = $(this).text();
            divClone.style.position = "absolute";
            //removemos divs origen
            $(this).parent().remove();
            //añadimos divs
            /*arreglar aqui por que esta cogiendo el id igual que el de sectores.*/
            divClone.append(divGirar);
            $("#Mapa").append(divClone);
            //evento cuando quiera girar un div
            girarDiv(divGirar);
            //damos opcion draggable y resizable
            divResizable(divClone, "#Mapa", 82, 20);
            divDraggable(divClone, "#Mapa", "Ambito");
            setTimeout(function () {
                eventoClickDerechoAmbito("div[id=" + divClone.id + "][class*=interiorWrapAmbitos]");
                console.log("cargado menu");
            }, 100);

        });
    });

}
//fin functions
/* Function CreadorSecciones.php */
function dragSecciones() {
    console.log("haciendo .sectors clickeable");
    //var secciones = document.querySelectorAll(".sectors");
    $(".sectors").each(function () {
        $(this).on("click", function () {
            idNew = $(this).parent().attr("id");
            //console.log(idNew);
            divClone = document.createElement("div");
            divGirar = document.createElement("div");
            divInterior = document.createElement("div");
            //atributos de los divs
            divInterior.className = "Sector-draggable";
            divInterior.id = idNew.split("-")[0] + "-Sector-draggable";
            divInterior.textContent = $(this).text();
            divInterior.style = "width:150px;";

            divGirar.className = "icon-girar";
            divGirar.id = "icon-" + idNew.split("-")[0];

            divClone.id = idNew;
            divClone.style = "height: 64px;width:180px;";
            divClone.className = "interiorWrap mover";
            divClone.style.position = "absolute";
            //removemos divs origen
            $(this).parent().remove();
            //añadimos divs
            $("#Mapa").append(divClone);
            $("#" + divClone.id).append(divInterior);
            $("#" + divInterior.id).append(divGirar);
            //evento cuando quiera girar un div
            girarDiv(divGirar);
            //damos opcion draggable y resizable
            divResizable(divClone, "#Mapa", 60, 60);
            divDraggable(divClone, "#Mapa");
            divResizable(divInterior, "#Sectores", 90, 40);
            divDraggable(divInterior, "#Sectores");
        });
    });
}
function girarDiv(icon) {
    //var para evento girar div al hacer click
    var click = true, width, height, className;
    $("#" + icon.id).on("click", function () {
        if (click) {

            width = $(this).parent().css("width");
            height = $(this).parent().css("height");
            console.log($(this).css("width") + " parent: " + width);
            console.log($(this).css("height") + " parent: " + height);
            var clase = $(this).parent().attr("class").split(" ");
            className = clase[clase.indexOf("Sector-draggable")];
            $(this).parent().removeClass(className).addClass("div-girado margenesAmbitosGiradas");
            $(this).parent().resizable("disable");

            click = false;
        } else {
            $(this).parent().removeClass("div-girado margenesAmbitosGiradas").addClass(className);
            $(this).parent().css("width", width);
            $(this).parent().css("height", height);
            $(this).parent().resizable("enable");
            click = true;
        }
    });
}
function divResizable(id, contenedor, minWidth, minHeight) {
    $(id).resizable({
        containment: contenedor,
        minWidth: minWidth,
        minHeight: minHeight
    });
}
function divDraggable(id, contenedor, Opcion) {
    //console.log(id);
    //En evento start colocamos las cordenas del cursor para que este centrado
    if (Opcion === "Ambito") {
        $(id).draggable({
            containment: contenedor,
            start: function (event, ui) {
                $(this).draggable("option", "cursorAt", {
                    left: Math.floor(this.clientWidth / 2),
                    top: Math.floor(this.clientHeight / 2)
                });
            }
        });
    } else {
        $(id).draggable({
            containment: contenedor
        });
    }

}
function BtnEliminarSeccion() {
    for (var i = 0; i < $(".eliminarSeccion").length; i++) {
        $(".eliminarSeccion").eq(i).on("click", function () {
            var explode = $(this).attr("id").split("-");
            var id = explode[1];
            CrearModal("decision", new Array("¿Quieres eliminar la seccion?", "red", "eliminarSeccion(" + id + ")", "eliminarNo"));
        });
    }
}
function anadirBotonEliminar() {
    for (var i = 0; i < $(".wrapDivsSectores").length; i++) {
        crearBotonEliminar($(".wrapDivsSectores").eq(i).attr('id'));
    }
}
function crearBotonEliminar(id) {
    $("#" + id).prepend($("<div/>", {
        "id": "eliminarSeccion-" + id,
        "class": "eliminarSeccion",
        "text": "X"
    }));
}
function AnadirNuevaSeccion() {
    //cogemos el campo
    var nuevaSeccion = $("#NewSeccion").val();
    //expresion regular
    var exp1 = /^[a-zA-ZÀ-ÿ\u00f1\u00d1]+(\s*[a-zA-ZÀ-ÿ\u00f1\u00d1]*)*[a-zA-ZÀ-ÿ\u00f1\u00d1]+$/;
    //validamos
    if (!exp1.test(nuevaSeccion) | nuevaSeccion.length > 120 || nuevaSeccion.length < 3) {
        AnadirResultado("Solo puede contener palabras y minimo 3 maximo 120", "error");
    } else {
        $.ajax({
            type: "POST",
            dataType: 'html',
            url: "back-end/InteraccionBD.php",
            data: "Opcion=NewSeccion&Seccion=" + nuevaSeccion,
            success: function (resp) {
                //alert(resp)
                switch (resp) {
                    case "existe":
                        AnadirResultado("Ya existe esa Seccion", "error");
                        break;
                    case "creado":
                        AnadirResultado("Creado con exito! =)", "success");
                        break;
                }
            }
        });
    }
}
function AnadirNuevoAmbito() {
    //cogemos el campo
    var nuevoAmbito = $("#NewAmbito").val();
    //expresion regular
    var exp1 = /^[a-zA-ZÀ-ÿ\u00f1\u00d1]+(\s*[a-zA-ZÀ-ÿ\u00f1\u00d1]*)*[a-zA-ZÀ-ÿ\u00f1\u00d1]+$/;
    //validamos
    if (!exp1.test(nuevoAmbito) | nuevoAmbito.length > 120 || nuevoAmbito.length < 3) {
        AnadirResultado("Solo puede contener palabras y minimo 3 maximo 120", "error");
    } else {
        $.ajax({
            type: "POST",
            dataType: 'html',
            url: "back-end/InteraccionBD.php",
            data: "Opcion=NewAmbito&Ambito=" + nuevoAmbito,
            success: function (resp) {
                //alert(resp)
                switch (resp) {
                    case "existe":
                        AnadirResultado("Ya existe esa Seccion", "error");
                        break;
                    case "creado":
                        //mirar si anado para crear variables
                        AnadirResultado("Creado con exito! =)", "success");
                        break;
                }
            }
        });
    }
}
function eliminarSeccion(id) {
    $.ajax({
        type: "POST",
        dataType: 'html',
        url: "back-end/InteraccionBD.php",
        data: "Opcion=DelSeccion&id=" + id,
        success: function (resp) {
            //alert(resp)
            switch (resp) {
                case "error":
                    AnadirResultado("Error al borrar", "error");
                    break;
                case "eliminado":
                    AnadirResultado(null, "successEliminar", id);
                    break;
            }
        }
    });
}