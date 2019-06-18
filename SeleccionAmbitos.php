<?php

include 'ConexionMysql/Conexion.php';

//agregamos bucle con los Secciones de la bd
$queryTraerAmbitos = "select numeracion,ambito from ambitos;";
$stm = $connect->prepare($queryTraerAmbitos);
//ejecutamos
$stm->execute();
while ($datos = $stm->fetch()) {
    echo "<div class='wrapDivsSectores Seleccion' id='" . $datos['numeracion'] . "'><div class='ambito ui-draggable interiorWrapAmbitos'>" . $datos['ambito'] . "</div></div>";
}                    
?>                  