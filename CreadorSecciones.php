<?php
include 'ConexionMysql/Conexion.php';
?>
<html>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <head>
        <link rel="stylesheet" href="css/CreadorMapa.css"/>
        <link rel="stylesheet" href="css/Modal.css"/>
        <script src="js/jquery-3.2.1.min.js"></script>
        <script src="js/Modal.js"></script>
        <script src="js/CreadorMapa.js"></script>
        <link rel="stylesheet" href="css/jqueryDraggable/jquery-ui.css">
        <script src="js/jqueryDraggable/jquery-1.12.4.js"></script>
        <script src="js/jqueryDraggable/jquery-ui.js"></script>
    </head>
    <body>
        <div id="Principal">
            <div class="Modal-fondo d-none"></div>
            <div class="Modal-dialogo d-none"></div>
            <div id="Sectores" class="hide-scroll">
                <div id="Mapa" class="ui-droppable"></div>
                <div class="verticalLine"></div>
                <div id="ListaSecciones" class="viewport">
                    <div class="divGenerico" id="Titulo"><h2>Seleccionar Secciones</h2></div>
                    <hr/>
                    <div id="Secciones">
                        <?php
                        //agregamos bucle con los Secciones de la bd
                        $queryTraerSecciones = "select id, seccion from secciones;";
                        $stm = $connect->prepare($queryTraerSecciones);
                        //ejecutamos
                        $stm->execute();
                        while ($datos = $stm->fetch()) {
                            echo "<div class='wrapDivsSectores Seleccion' id='" . $datos['id'] . "'><div class='sectors ui-draggable interiorWrap'>" . $datos['seccion'] . "</div></div>";
                        }
                        ?>
                    </div>
                    <div class="divGenerico Seleccion" id="Anadir">+</div>
                </div>
            </div>
            <div id="finalizarSeccion">
                <div class="divGenerico" id="Aceptar">Aceptar</div>
                <div class="divGenerico" id="Reset">Reset</div>
                <div class="divGenerico"  id="Atras">Volver</div>
            </div>
            <?php
            // put your code here
            ?>
        </div>
    </body>
</html>
