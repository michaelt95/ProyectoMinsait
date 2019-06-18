$(document).ready(function () {
    //evento al seleccionar
    $("#opcion").on("change", function () {
        //console.log($(this).val());
        $("#contenido").css("display", "block");
        $("#interiorContenido").html(" ");
        $("#atras").remove();
        cargaOpcion($(this).val());
        $("#Principal").append($("<div/>", {
            "id": "atras",
            "text": "Volver",
            "onclick": "atras()"
        }));
        document.getElementById("atras").addEventListener("click", function () {
            location.href = "Index.php";
        }, false);
    });
    //para quitar el modal
    $(".Modal-fondo").on("click", function () {
        $(this).addClass(" d-none");
        $(".Modal-dialogo").addClass(" d-none");
    });
});
var contenedor = document.getElementById("interiorContenido"), divContenido, divContenidoAyVInter, divTabla, tableVariables, valoresInputNew = [];
function cargaOpcion(opcion) {
    contenedor.innerHTML = " ";
    switch (opcion) {
        case "Secciones":
            buscar();
            //cargamos Secciones
            gestionSecciones();
            break;
        case "AmbitosYvariables":
            buscar(true);
            //cargamos Ambitos
            gestionAmbitos();
            break
        case "Documentos":
            buscar(false,"Documentos");
            //cargamos Ambitos
            gestionDocumentos();
            break
    }
}
function buscar(subirNivel,documentos) {
    $("#interiorContenido").append($("<div/>", {
        "id": "divBuscador"
    }).append($("<input/>", {
        "id": "buscadorInput",
        "type": "text",
        "placeholder": "buscador"
    })));
    eventoBuscador(subirNivel,documentos);
}
function eventoBuscador(subirNivel,documentos) {
    var textoBuscar;
    $("#buscadorInput").keyup(function (e) {
        //console.log($(this).val());
        textoBuscar = $(this).val();
        busqueda(textoBuscar, subirNivel,documentos);
        if (e.keyCode === 8) {
            if (subirNivel) {
                $(".divContenidoAyV").each(function () {
                    $(this).css("display", "inline-block");
                });
            } else {
                $(".bloque").each(function () {
                    $(this).css("display", "inline-block");
                });
            }
            //console.log($(this).val());
            textoBuscar = $(this).val();
            busqueda(textoBuscar, subirNivel,documentos);
        }
    });
}
function busqueda(textoBuscar, subirNivel,documentos) {
    //en un futuro para la opcion de documentos podemos buscar por fecha o tamaño de fichero
    let selector=(documentos==="Documentos")?"input[class=input-nombre]":".bloqueInteriorLeft input";
    if (textoBuscar.length !== 0) {
        $(selector).each(function () {
            if (typeof $(this).attr("placeholder") !== typeof undefined && $(this).attr("placeholder") !== false) {
                if (-1 === $(this).attr("placeholder").toLowerCase().indexOf(textoBuscar.toLowerCase())) {
                    if (subirNivel) {
                        $(this).parent().parent().parent().css("display", "none");
                    } else {
                        $(this).parent().parent().css("display", "none");
                    }
                }
            } else {
                if (-1 === $(this).val().toLowerCase().indexOf(textoBuscar.toLowerCase())) {
                    if (subirNivel) {
                        $(this).parent().parent().parent().css("display", "none");
                    } else {
                        $(this).parent().parent().css("display", "none");
                    }
                }
            }
        });
    } else {
        if (subirNivel) {
            $(".divContenidoAyV").each(function () {
                $(this).css("display", "inline-block");
            });
        } else {
            $(".bloque").each(function () {
                $(this).css("display", "inline-block");
            });
        }
    }
}
function gestionSecciones() {
    $.ajax({
        url: "back-end/InteraccionBD.php",
        type: "POST",
        data: "Opcion=getSecciones",
        success: function (resp) {
            //console.log(JSON.parse(resp));
            var obj = JSON.parse(resp);
            for (const prop in obj) {
                //console.log(prop+" = "+objSecciones[prop]);
                cargaDivsGenericos("Seccion:", obj[prop], prop);
            }
            //cargamos div para añadir         
            cargaDivNewSeccion("Seccion:");
            //cargamos Eventos para modificar,guardar y eliminar 
            eventoListenerInput();
            eventoGuardar();
            eventoEliminar("Seccion");
        }
    });
}
function gestionAmbitos() {
    $.ajax({
        url: "back-end/InteraccionBD.php",
        type: "POST",
        data: "Opcion=getAmbitos",
        success: function (resp) {
            //console.log(JSON.parse(resp));
            var obj = JSON.parse(resp);
            for (const prop in obj) {
                //console.log("Numeracion " + prop + " Ambito " + obj[prop] + "\n");
                cargaDivsGenericos("Ambito:", obj[prop], prop);
            }
            //cargamos div para añadir         
            cargaDivNewSeccion("Ambito:");
            //cargamos Eventos para modificar,guardar y eliminar 
            eventoListenerInput(true);
            eventoGuardar(true);
            eventoEliminar("Ambito");
            //hacer para agregar/eliminar/modificar variables
            eventoListenerDesplegar();
        }
    });
}
function gestionDocumentos() {
    console.log("Gestion Documentos");
    $.ajax({
        url: "back-end/InteraccionBD.php",
        type: "POST",
        data: "Opcion=getDocumentos",
        success: function (resp) {
            //console.log(JSON.parse(resp));
            var obj = JSON.parse(resp);
            for (const prop in obj) {
                //console.log(prop + " = " + obj[prop]);
                cargaDivsDocumentos(obj[prop]);
            }
             //cargamos Eventos para descargar y eliminar 
             eventoDescargar();
             eventoEliminar("Fichero");
        }
    });
}
function cargaDivsDocumentos(datos) {
    var divBloque = document.createElement("div");
    divBloque.className = "bloque";
    divBloque.style = "width:80%!important";

    var divBloqueInteriorLeft = document.createElement("div");
    divBloqueInteriorLeft.className = "bloqueInteriorLeft";
    divBloqueInteriorLeft.style = "width: 90%!important;";

    var inputNombre = document.createElement("input");
    inputNombre.type = "text";
    inputNombre.readOnly = true;
    inputNombre.className = "input-nombre";
    if (datos[0].split(/_|\.+/g).length === 4) {
        inputNombre.value = datos[0].split(/_|\.+/g)[0] + " " + datos[0].split(/_|\.+/g)[1] + "." + datos[0].split(/_|\.+/g)[3];
    } else {
        inputNombre.value = datos[0].replace("_", " ");
    }
    var inputFecha = document.createElement("input");
    inputFecha.type = "text";
    inputFecha.readOnly = true;
    inputFecha.className = "input-fecha";
    inputFecha.value = datos[2];

    var inputTam = document.createElement("input");
    inputTam.type = "text";
    inputTam.readOnly = true;
    inputTam.className = "input-tam";
    inputTam.value = datos[1];

    var divSave = document.createElement("div");
    divSave.className = "div-descarga";
    divSave.id= datos[0];
    
    //Aqui agregar a divBloque... los inputs y el div Download
    divBloqueInteriorLeft.appendChild(inputNombre);
    divBloqueInteriorLeft.appendChild(inputFecha);
    divBloqueInteriorLeft.appendChild(inputTam);
    divBloqueInteriorLeft.appendChild(divSave);

    var divBloqueInteriorRigth = document.createElement("div");
    divBloqueInteriorRigth.className = "bloqueInteriorRigth";
    divBloqueInteriorRigth.id = datos[0] + "*eliminar";

    divBloque.appendChild(divBloqueInteriorLeft);
    divBloque.appendChild(divBloqueInteriorRigth);
    contenedor.appendChild(divBloque);
}
function eventoDescargar(){
    $("div[class=div-descarga][id]").each(function(){
        $(this).on("click",function(){
            descargarDocumento($(this).attr("id"));
        });
    });
}
function cargaDivsGenericos(texto, valor, id) {
    var divBloque = document.createElement("div");
    divBloque.className = "bloque";
    divBloque.id = id;

    var divBloqueInteriorLeft = document.createElement("div");
    divBloqueInteriorLeft.className = "bloqueInteriorLeft";

    var divTexto = document.createElement("div");
    divTexto.textContent = texto;

    var input = document.createElement("input");
    input.id = id + "-input";
    input.type = "text";
    input.value = valor;
    if (texto !== "Seccion:") {
        input.style = "width: 450px!important;";
    }

    divBloqueInteriorLeft.appendChild(divTexto);
    divBloqueInteriorLeft.appendChild(input);

    var divBloqueInteriorRigth = document.createElement("div");
    divBloqueInteriorRigth.className = "bloqueInteriorRigth";
    divBloqueInteriorRigth.id = id + "-eliminar";

    divBloque.appendChild(divBloqueInteriorLeft);
    divBloque.appendChild(divBloqueInteriorRigth);
    if (texto === "Seccion:") {
        contenedor.appendChild(divBloque);
    } else {
        divBloque.className = "bloque AyV";
        divContenido = document.createElement("div");
        divContenido.className = "divContenidoAyV";
        divContenido.id = id + "-divContenidoAyV";
        var imgDespliegue = document.createElement("div");
        imgDespliegue.className = "desplegar";
        divBloque.appendChild(imgDespliegue);
        divContenidoAyVInter = document.createElement("div");
        divContenidoAyVInter.className = "divContenidoAyVInter";

        divContenido.appendChild(divBloque);
        divContenido.appendChild(divContenidoAyVInter);
        contenedor.appendChild(divContenido);
    }
}
function eventoListenerInput(ambito) {
    //cogemos los input
    var inputs = document.querySelectorAll("[id*=-input]");
    //creamos un div para añadir una imagen cuando estamos modificando el nombre de la seccion
    var divImgEditar = $("<div/>", {
        "class": "divImgEditar"
    });
    var valor = "";
    //evento para 
    for (var i = 0; i < inputs.length; i++) {
        $("#" + inputs[i].id).focusin(
                function () {
                    //console.log($("#"+$(this).parent().parent().attr("id")+" div[id*=-eliminar]").attr("id"));
                    divImgEditar.insertBefore($("#" + $("#" + $(this).parent().parent().attr("id") + " div[id*=-eliminar]").attr("id")));
                    valor = $(this).val();
                    console.log(valor);

                }).focusout(
                function () {
                    $(".divImgEditar").remove();
                    if (valor !== $(this).val()) {
                        //contemplar si quiero hacer varios updates, de momento lo dejo para hacer un
                        //update por cada vez que lo modifica uno por uno
                        //valoresInputNew.push($(this).val());
                        valoresInputNew[0] = $(this).val();

                        var objAux = new Object();
                        objAux['id'] = $(this).attr("id").split("-")[0];
                        objAux['valorNew'] = valoresInputNew[0];
                        console.log(objAux);
                        if (ambito) {
                            $.ajax({
                                url: "back-end/InteraccionBD.php",
                                type: "POST",
                                data: "Opcion=updateAmbito&valor=" + JSON.stringify(objAux),
                                success: function (resp) {
                                    console.log("respuesta: " + resp);
                                    if (resp === "success") {
                                        CrearModal("alerta", new Array("Campo actualizado", "green"));
                                    }
                                }
                            });
                        } else {
                            $.ajax({
                                url: "back-end/InteraccionBD.php",
                                type: "POST",
                                data: "Opcion=updateSeccion&valor=" + JSON.stringify(objAux),
                                success: function (resp) {
                                    console.log("respuesta: " + resp);
                                    if (resp === "success") {
                                        CrearModal("alerta", new Array("Campo actualizado", "green"));
                                    }
                                }
                            });
                        }
                    }
                }
        );
    }
}
function eventoGuardar(ambito) {
    $(".btn-anadir").on("click", function () {
        Anadir(ambito);
    });
}
function eventoEliminar(opcion) {
    $("div[class*=bloque] div[id*=eliminar]").each(function () {
        $(this).on("click", function () {
            switch (opcion) {
                case "Seccion":
                    CrearModal("decision", new Array("¿Quieres eliminar la seccion?", "red", "eliminarSeccion(" + $(this).attr("id").split("-")[0] + ")", "eliminarNo"));
                    break;
                case "Ambito":
                    CrearModal("decision", new Array("¿Quieres eliminar el Ambito? las variables asociadas se eliminaran tambien", "red", "eliminarAmbito(" + $(this).attr("id").split("-")[0] + ")", "eliminarNo"));
                    break;
                case "Fichero":
                    CrearModal("decision", new Array("¿Quieres eliminar el documento?", "red", "eliminarDocumento(\'" + $(this).attr("id").split("*")[0]+ "\')", "eliminarNo"));
                    break;
            }
        });
    });
}
function eliminarDocumento(documento){
    $.ajax({
        type: "POST",
        dataType: 'html',
        url: "back-end/InteraccionBD.php",
        data: "Opcion=eliminarDocumento&documento=" + documento,
        success: function (resp) {
            //console.log(resp)
            switch (resp) {
                case "error":
                    AnadirResultado("Error al borrar", "error");
                    break;
                case "success":
                    AnadirResultado(null, "successEliminar", documento);
                    cargaOpcion("Documentos");
                    break;
            }
        }
    });
}
function descargarDocumento(documento) {
    window.open("back-end/GeneradorWord.php?fichero=" + encodeURI(documento), "_black");
}
function eventDelVariable() {
    $("td div[id*=-eliminar]").each(function () {
        $(this).on("click", function () {
            CrearModal("decision", new Array("¿Quieres eliminar la variable?", "red", "eliminarVariable(" + $(this).attr("id").split("-")[0] + "," + $(this).attr("id").split("-")[1] + ")", "eliminarNo"));
        });
    });
}
function Anadir(ambito) {
    //cogemos el campo
    var nuevo = $("#inputNew").val();
    //expresion regular
    var exp1 = /^[A-z_-\d]{2,150}/;
    //validamos
    if (nuevo.length > 0) {
        if (!exp1.test(nuevo)) {
            CrearModal("alerta", new Array("No admite espacios al inicio, minimo de caracteres 3 maximo 150", "rgb(245, 202, 14)", "warning"));
            $("#inputNew").focus();
        } else {
            if (ambito) {
                $.ajax({
                    type: "POST",
                    dataType: 'html',
                    url: "back-end/InteraccionBD.php",
                    data: "Opcion=NewAmbito&Ambito=" + nuevo,
                    success: function (resp) {
                        //alert(resp)
                        switch (resp) {
                            case "existe":
                                CrearModal("alerta", new Array("Ya existe ese Ambito", "rgb(245, 202, 14)", "warning"));
                                $("#inputNew").val(" ");
                                break;
                            case "creado":
                                cargaOpcion("AmbitosYvariables");
                                break;
                        }
                    }
                });
            } else {
                $.ajax({
                    type: "POST",
                    dataType: 'html',
                    url: "back-end/InteraccionBD.php",
                    data: "Opcion=NewSeccion&Seccion=" + nuevo,
                    success: function (resp) {
                        //alert(resp)
                        switch (resp) {
                            case "existe":
                                CrearModal("alerta", new Array("Ya existe esa sección", "rgb(245, 202, 14)", "warning"));
                                $("#inputNew").val(" ");
                                break;
                            case "creado":
                                cargaOpcion("Secciones");
                                break;
                        }
                    }
                });
            }
        }
    } else {
        $("#inputNew").focus();
        $("#inputNew").attr("class", "box-shadow");
        $("#inputNew").focusout(function () {
            $(this).removeClass("box-shadow");
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
function eliminarAmbito(id) {
    $.ajax({
        type: "POST",
        dataType: 'html',
        url: "back-end/InteraccionBD.php",
        data: "Opcion=DelAmbito&id=" + id,
        success: function (resp) {
            //alert(resp)
            switch (resp) {
                case "error":
                    CrearModal("alerta", new Array("Error al borrar", "rgb(245, 202, 14)", "warning"));
                    break;
                case "eliminado":
                    removeNewModal();
                    //cargaOpcion("AmbitosYvariables");
                    $("div[id=" + id + "-divContenidoAyV]").remove();
                    break;
            }
        }
    });
}
function cargaDivNewSeccion(texto) {
    let contenedor = document.getElementById("interiorContenido");
    let divContenidoAyV = document.createElement("div");
    let bloqueAyV = document.createElement("div");
    let bloqueInteriorLeft = document.createElement("div");
    let text = document.createElement("div");
    let input = document.createElement("input");
    let btnanadir = document.createElement("div");
    let interiorBtnAnadir = document.createElement("div");

    text.textContent = texto;
    input.id = "inputNew";
    input.type = "text";

    input.id = "inputNew";
    input.placeholder = "Añade un/una " + texto.substring(0, texto.length - 1);

    if (texto === "Ambito:") {
        interiorBtnAnadir.style = "position:relative;left:3%;top:-20%;";
    } else {
        interiorBtnAnadir.style = "position:relative;left:3%;top:0%;";
    }

    interiorBtnAnadir.textContent = "+";

    btnanadir.className = "btn-anadir";
    btnanadir.appendChild(interiorBtnAnadir);

    bloqueInteriorLeft.appendChild(text);
    bloqueInteriorLeft.appendChild(input);
    bloqueInteriorLeft.className = "bloqueInteriorLeft";

    if (texto === "Ambito:") {
        bloqueAyV.className = "bloque AyV";
    } else {
        bloqueAyV.className = "bloque";
    }

    bloqueAyV.id = "anadir";

    bloqueAyV.appendChild(bloqueInteriorLeft);
    bloqueAyV.appendChild(btnanadir);

    if (texto === "Ambito:") {
        divContenidoAyV.style = "width:90%!important;";
        input.style = "width:450px!important;";
        divContenidoAyV.className = "divContenidoAyV";
        divContenidoAyV.id = "bloqueAnadir";

        divContenidoAyV.appendChild(bloqueAyV);
        contenedor.appendChild(divContenidoAyV);
    } else {
        input.style = "width:350px!important;";
        text.style = "font-size: 24px;";
        $("#bloqueAnadir").css("width", "60%!important");
        $(".bloqueInteriorLeft input[id=inputNew]").css("width", "350px");
        btnanadir.style = "margin-top: 0px!important;";
        contenedor.appendChild(bloqueAyV);
    }
}
/* Para la tabla variables */
function eventoListenerDesplegar() {
    $(".desplegar").each(function () {
        var desplegar = false;
        var nVeces = 0;
        $(this).on("click", function () {
            if (!desplegar) {
                $(this).parent().parent().css("height", "100%");
                desplegar = true;
                //creamos la tabla con su correspondientes campos de cada ambito y la agregamos a un div (divTabla)
                // y ese div lo metemos en divContenidoAyVInter y hay que ocultarlo cada vez que se cierre
                if (nVeces > 0) {
                    $("#" + $(this).parent().attr("id") + "-table").css("display", "block");
                } else {
                    cargaTabla($(this).parent().attr("id"), $(this).parent().siblings(".divContenidoAyVInter"));
                    nVeces++;
                }
            } else {
                $(this).parent().parent().css("height", "14%");
                $("#" + $(this).parent().attr("id") + "-table").css("display", "none");
                desplegar = false;
            }
        });
    });
}
function cargaTabla(idTabla, addPadre) {
    //creamos el div que va a contener la tabla
    divTabla = document.createElement("div");
    divTabla.id = idTabla + "-table";
    inicioTablaVariables();
    cargarTablaVariables(idTabla);
    //cargar la tabla en su div y añadir el div a el addPadre
    divTabla.appendChild(tableVariables);
    addPadre.append(divTabla);
}
function cargarTablaVariables(idTabla) {
    $.ajax({
        url: "back-end/InteraccionBD.php",
        type: "POST",
        data: "Opcion=getVariables&numeracion=" + idTabla,
        dataType: "json",
        success: function (resp) {
            if (Object.keys(resp).length !== 0) {
                //console.log(resp);
                var array = resp;
                for (const indice in array) {
                    //console.log("id: " + indice + "\n");
                    var tr = document.createElement("tr");
                    tr.className = "fila-variables";
                    tr.id = indice;
                    for (const value in array[indice]) {
                        //console.log(array[indice][value] + " posicion " + value + "\n");
                        var td = document.createElement("td");
                        var textarea = document.createElement("textarea");
                        textarea.textContent = array[indice][value];
                        textarea.readOnly = true;
                        textarea.className = "textarea-variables readOnly";
                        //dependiendo de la posicon (value) colocar un className para identificar en que campo esta
                        //esto nos ayudara para luego al hacer el update ya que le pasamos el value del textarea
                        //y hacemos un split(" ") por espacio cogiendo la primera poscion que es el nombre del campo
                        nombreCampo(td, value);
                        td.appendChild(textarea);
                        tr.appendChild(td);
                    }
                    tr.appendChild(btnEliminar(indice, idTabla));
                    //console.log("------------------------------");
                    tableVariables.appendChild(tr);
                }
            }
            //eventos a añadir
            anadirEventosVariables();
            //cargamos div para añadir
            addVariableNew(idTabla);
            //evento para guardar la nueva variable
            eventGuardarVariable();
        }
    });
}
function btnEliminar(indice, idTabla) {
    //cargamos el btn de eliminar que estara al final de cada fila
    var tdBtnEliminar = document.createElement("td");
    var div = document.createElement("div");
    div.className = "btn-variable-eliminar";
    div.id = indice + "-" + idTabla + "-eliminar";
    tdBtnEliminar.style = "border-color: cornflowerblue;";
    tdBtnEliminar.appendChild(div);
    return tdBtnEliminar;
}
function anadirEventosVariables() {
    //evento hover sobre los tds
    eventHoverTds();
    //Evento para modificar variables existentes
    eventoDoubleClickInputTextarea();
    //Evento para eliminar una variable (fila) de un ambito
    eventDelVariable();
}
function eventHoverTds() {
    $(".td-h").mouseenter(function () {
        $(this).addClass("hover").siblings(".td-h").addClass("hover");
    }).mouseleave(function () {
        $(this).removeClass("hover").siblings().removeClass("hover");
    });
}
function addVariableNew(idTabla) {
    var tr = document.createElement("tr");
    tr.className = "fila-variables";
    tr.id = idTabla;
    //valorar cuanto tamaño poner
    tr.style = "height: 100px;";
    for (var i = 0; i < 4; i++) {
        var td = document.createElement("td");
        td.className = "td-h";
        var textarea = document.createElement("textarea");
        textarea.className = "textarea-variablesNew";
        td.appendChild(textarea);
        tr.appendChild(td);
    }
    var tdBtn = document.createElement("td");
    var div = document.createElement("div");
    div.className = "btn-variable-anadir";
    div.textContent = "+";
    tdBtn.style = "border-color: cornflowerblue;";
    tdBtn.appendChild(div);
    tr.appendChild(tdBtn);
    tableVariables.appendChild(tr);
}
function eventoDoubleClickInputTextarea() {
    //evento al hacer doble click para quitar atributo readonly y al salir del focus ponerlo denuevo
    //al entrar guardamos el valor en una variable que luego al salir comparamos si sigue siendo igual
    //si es diferente se guardara en la base de datos
    $(".textarea-variables").each(function () {
        let valueOld;
        $(this).on("dblclick", function () {
            $(this).removeAttr("readonly");
            $(this).removeClass("readOnly");
            valueOld = $(this).val();
            console.log("valor antiguo: \n" + valueOld);
        });
        $(this).focusout(function () {
            console.log("Valor nuevo: \n" + $(this).val());
            updateVariable($(this), valueOld, $(this).val(), $(this).parent().parent().attr("id"), $(this).parent().attr("class").split(" ")[0]);

            $(this).attr("readonly", "readonly");
            $(this).addClass("readOnly");
        });
    });
}
function nombreCampo(td, value) {
    switch (value) {
        case "0":
            td.className = "nombre td-h";
            break;
        case "1":
            td.className = "descripcion td-h";
            break;
        case "2":
            td.className = "valor_minimo td-h";
            break;
        case "3":
            td.className = "valor_maximo td-h";
            break;
    }
}
function inicioTablaVariables() {
    tableVariables = document.createElement("table");
    var fields = ["Nombre", "Descripcion", "Valor Minimo", "Valor Maximo"];
    tableVariables.className = "table-variables";
    var tr = document.createElement("tr");
    tr.className = "nombres-campos";
    for (var i = 0; i < fields.length; i++) {
        var td = document.createElement("td");
        td.textContent = fields[i];
        //console.log(fields[i]);
        tr.appendChild(td);
    }
    tableVariables.appendChild(tr);
}
function updateVariable(textarea, valueOld, valorNew, id, campo) {
    if (valorNew !== valueOld) {
        var fd = new FormData();
        fd.append("change", new Array(id, campo, valorNew));
        fd.append("Opcion", "updateVariable");
        $.ajax({
            type: "POST",
            dataType: 'html',
            url: "back-end/InteraccionBD.php",
            data: fd,
            processData: false,
            contentType: false,
            success: function (resp) {
                //alert(resp)
                if (resp === "success") {
                    $(textarea).text(valorNew);
                } else {
                    CrearModal("alerta", new Array("Revisa la consola", "rgb(245, 202, 14)", "warning"));
                    console.log(resp);
                }
            }
        });
    } else {
        console.log("No se realizara ningun Update");
    }
}
function eliminarVariable(idVariable, idAmbito) {
    var fd = new FormData();
    fd.append("id", idVariable);
    fd.append("Opcion", "eliminarVariable");
    $.ajax({
        type: "POST",
        dataType: 'html',
        url: "back-end/InteraccionBD.php",
        data: fd,
        processData: false,
        contentType: false,
        success: function (resp) {
            //alert(resp)
            switch (resp) {
                case "error":
                    CrearModal("alerta", new Array("Error al borrar", "rgb(245, 202, 14)", "warning"));
                    break;
                case "eliminado":
                    removeNewModal();
                    reloadTabla("eliminar", idVariable, idAmbito);
                    break;
            }
        }
    });
}
function reloadTabla(opcion, idTr, idDivTabla, arrayValores) {
    if (opcion === "eliminar") {
        $("div[id=" + idDivTabla + "-table] table tr[id=" + idTr + "]").remove();
    } else {
        //console.log("opcion: "+opcion+"\n idTr: "+idTr+"\n idDivTabla: "+idDivTabla+"\n arrayValores: "+arrayValores);
        //vaciamos el tr input 
        $("div[id=" + idDivTabla + "-table] table tr:last td").each(function () {
            $(this).children().val("");
        });
        $("div[id=" + idDivTabla + "-table] table tr:last").before($("<tr/>", {
            "class": "fila-variables",
            "id": idTr
        }).append(
                $("<td/>", {
                    "class": "nombre td-h"
                }).append(
                $("<textarea/>", {
                    "readonly": "readonly",
                    "class": "textarea-variables readOnly",
                    "text": arrayValores[0]
                })
                )
                ,
                $("<td/>", {
                    "class": "descripcion td-h"
                }).append(
                $("<textarea/>", {
                    "readonly": "readonly",
                    "class": "textarea-variables readOnly",
                    "text": arrayValores[1]
                })
                )
                ,
                $("<td/>", {
                    "class": "valor_minimo td-h"
                }).append(
                $("<textarea/>", {
                    "readonly": "readonly",
                    "class": "textarea-variables readOnly",
                    "text": arrayValores[2]
                })
                )
                ,
                $("<td/>", {
                    "class": "valor_maximo td-h"
                }).append(
                $("<textarea/>", {
                    "readonly": "readonly",
                    "class": "textarea-variables readOnly",
                    "text": arrayValores[3]
                })
                ), btnEliminar(idTr, idDivTabla)
                )
                );
        //eventos a añadir
        anadirEventosVariables();
    }
}
function eventGuardarVariable() {
// acabar evento y cargar con reloadTabla
    $(".btn-variable-anadir").on("click", function () {
        let arrayVariables = new Array(4);
        for (var i = 0; i < $("tr[id=" + $(this).parent().parent().attr("id") + "] td[class*=td-h]").length; i++) {
            //console.log($(this).children().val());
            arrayVariables[i] = $("tr[id=" + $(this).parent().parent().attr("id") + "] td[class*=td-h]").eq(i).children().val();
        }
        console.log(arrayVariables);
        anadirVariable($(this).parent().parent().attr("id"), arrayVariables);
    });

}
function anadirVariable(idAmbito, arrayVariables) {
    var exp1 = /^[A-z_-\d]{2,150}/;
    var exp2 = /^[A-z_-\d]{2,}/;
    let CampoNombre = $("tr[id=" + idAmbito + "] td[class*=td-h]").eq(0);
    let CampoDescripcion = $("tr[id=" + idAmbito + "] td[class*=td-h]").eq(1);
    //quitar clase para dejarlos sin ningun error
    //validamos
    let continuar = true;
    if (!exp1.test(arrayVariables[0]) || typeof arrayVariables[0] === typeof undefined) {
        CrearModal("alerta", new Array("No admite espacios al inicio, minimo de caracteres 3 maximo 150", "rgb(245, 202, 14)", "warning"));
        continuar = false;
        CampoNombre.removeClass("quitarError");
        CampoNombre.addClass("error");
        setTimeout(function () {
            CampoNombre.addClass("quitarError");
        }, 1000);
    }
    if (!exp2.test(arrayVariables[1]) || typeof arrayVariables[1] === typeof undefined) {
        CrearModal("alerta", new Array("No admite espacios al inicio, minimo de caracteres 2", "rgb(245, 202, 14)", "warning"));
        continuar = false;
        CampoDescripcion.removeClass("quitarError");
        CampoDescripcion.addClass("error");
        setTimeout(function () {
            CampoDescripcion.addClass("quitarError");
        }, 1000);
    }
    if (continuar) {
        let fd = new FormData();
        fd.append("Opcion", "newVariable");
        fd.append("idAmbito", idAmbito);
        fd.append("valores", arrayVariables);
        $.ajax({
            type: "POST",
            dataType: 'html',
            url: "back-end/InteraccionBD.php",
            data: fd,
            processData: false,
            contentType: false,
            success: function (resp) {
                let array = JSON.parse(resp);
                console.log(array);
                switch (array[0]) {
                    case "existe":
                        CrearModal("alerta", new Array("Ya existe esa variable", "rgb(245, 202, 14)", "warning"));
                        break;
                    case "creado":
                        reloadTabla("agregar", array[1], idAmbito, arrayVariables);
                        //cargaOpcion("AmbitosYvariables");
                        break;
                }
            }
        });
    }
}