<?php

include './../ConexionMysql/Conexion.php';

if (isset($_POST["Opcion"])) {
    switch ($_POST["Opcion"]) {
        //seccion
        case "NewSeccion":
            NuevaSeccion($_POST["Seccion"], $connect);
            break;
        case "DelSeccion":
            eliminarSeccion($_POST["id"], $connect);
            break;
        case "getSecciones":
            getSecciones($connect);
            break;
        case "updateSeccion":
            updateSeccion($_POST['valor'],$connect);
            break;
        //ambito
        case "NewAmbito":
            NuevoAmbito($_POST["Ambito"], $connect);
            break;
        case "getAmbitos":
            getAmbitos($connect);
            break;
        case "updateAmbito";
            updateAmbito($_POST['valor'],$connect);
            break;
        case "DelAmbito":
            eliminarAmbito($_POST["id"], $connect);
            break;
        //variables
        case "newVariable":
            newVariable($_POST["idAmbito"], explode(",",$_POST['valores']), $connect);
            break;
        case "getVariables":
            getVariables($_POST['numeracion'],$connect);
            break;
        case "updateVariable":
            $arrayFormdata= explode(",", $_POST['change']);
            updateVariable($arrayFormdata[0], $arrayFormdata[1], $arrayFormdata[2],$connect);
            break;
        case "eliminarVariable":
            eliminarVariable($_POST['id'],$connect);
            break;
        //ficheros
        case "getDocumentos":
            getDocumentos();
            break;
        case "eliminarDocumento":
            eliminarDocumento(urldecode($_POST['documento']));
            break;
    }
}
/*function para Secciones*/
function NuevaSeccion($nombreSeccion, $connect) {
    if (ExisteSeccion($nombreSeccion, $connect) !== "no") {
        //querys
        $querySeccion = "INSERT INTO secciones (seccion) VALUES(?);";
//Preparamos
        $stm = $connect->prepare($querySeccion);
        $stm->bindParam(1, $nombreSeccion);
//insertamos
        $stm->execute();
        echo "creado";
    } else {
        echo "existe";
    }
}
function updateSeccion($obj,$connect){
    $objeto= json_decode($obj,true);
    try {
//query
        $querySeccionUpdate = "Update secciones set seccion='".$objeto['valorNew']."' where id=".$objeto['id'].";";
//preparamos
        $stm = $connect->prepare($querySeccionUpdate);
//ejecutamos
        $stm->execute();
        echo "success";
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
function getSecciones($connect){
    try {
//query
        $querySeccion = "Select * from secciones;";
//preparamos
        $stm = $connect->prepare($querySeccion);
//ejecutamos
        $stm->execute();
        //concatenamos todos los datos
        while($datos = $stm->fetch()){
            $text[$datos[0]]=$datos[1];
        }
        echo json_encode($text);
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
function eliminarSeccion($id,$connect){
        //querys
        $querySeccion = "DELETE FROM secciones WHERE id=?";
//Preparamos
        $stm = $connect->prepare($querySeccion);
        $stm->bindParam(1, $id);
//insertamos
        $ok=$stm->execute();
        if(!$ok){
            echo "error";
        }else{
            echo "eliminado";
        }
}
function ExisteSeccion($nombreSeccion, $connect) {
    try {
//query
        $querySeccion = "Select id from secciones where seccion='" . $nombreSeccion . "';";
//preparamos
        $stm = $connect->prepare($querySeccion);
//ejecutamos
        $stm->execute();
        $datos = $stm->fetch();
        if ($datos[0] != null) {
            return "no";
        } else {
            return "si";
        }
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
/*function para Ambitos*/
function getAmbitos($connect){
    try {
//query
$queryAmbitos = "select * from ambitos";
//preparamos
        $stm = $connect->prepare($queryAmbitos);
//ejecutamos
        $stm->execute();
        $text=[];
        //concatenamos todos los datos
        while($datos = $stm->fetch()){
            $text[$datos[0]]= $datos[1];
        }
        //return $text;
        echo json_encode($text);
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
function updateAmbito($obj,$connect){
    $objeto= json_decode($obj,true);
    try {
//query
        $queryAmbitoUpdate = "Update ambitos set ambito='".$objeto['valorNew']."' where numeracion=".$objeto['id'].";";
//preparamos
        $stm = $connect->prepare($queryAmbitoUpdate);
//ejecutamos
        $stm->execute();
        echo "success";
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
function NuevoAmbito($nombreAmbito, $connect) {
    if (ExisteAmbito($nombreAmbito, $connect) !== "no") {
        //querys
        $querySeccion = "INSERT INTO ambitos (ambito) VALUES(?);";
//Preparamos
        $stm = $connect->prepare($querySeccion);
        $stm->bindParam(1, $nombreAmbito);
//insertamos
        $stm->execute();
        echo "creado";
    } else {
        echo "existe";
    }
}
function ExisteAmbito($nombreAmbito, $connect) {
    try {
//query
        $queryAmbito = "Select numeracion from ambitos where ambito='" . $nombreAmbito . "';";
//preparamos
        $stm = $connect->prepare($queryAmbito);
//ejecutamos
        $stm->execute();
        $datos = $stm->fetch();
        if ($datos[0] != null) {
            return "no";
        } else {
            return "si";
        }
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
function eliminarAmbito($id,$connect){
        //querys
        $queryAmbito = "DELETE FROM ambitos WHERE numeracion=?";
//Preparamos
        $stm = $connect->prepare($queryAmbito);
        $stm->bindParam(1, $id);
//insertamos
        $ok=$stm->execute();
        if(!$ok){
            echo "error";
        }else{
            echo "eliminado";
        }
}
/*function para Variables*/
function getVariables($numeracion,$connect){
    try {
//query
$queryVariables = "select * from variables where numeracion=".$numeracion;
//preparamos
        $stm = $connect->prepare($queryVariables);
//ejecutamos
        $stm->execute();
        $text=[];
        //concatenamos todos los datos
        while($datos = $stm->fetch()){
            $text[$datos[0]]= array($datos[2],$datos[3],$datos[4],$datos[5]);
        }
        //return $text;
        echo json_encode($text);
    } catch (PDOException $e) {
        return 'Error: ' . $e->getMessage();
    }
}
function updateVariable($id,$campo,$valorNew,$connect){
    try {
//query
        $queryVariableUpdate = "Update variables set $campo='$valorNew' where id=$id";
//preparamos
        $stm = $connect->prepare($queryVariableUpdate);
//ejecutamos
        $stm->execute();
        echo "success";
    } catch (PDOException $e) {
        echo "Query:\nUpdate variables set $campo='$valorNew' where id=$id "."\nError: " . $e->getMessage();
    }
}
function eliminarVariable($id,$connect){
    //querys
        $queryAmbito = "DELETE FROM variables WHERE id=?";
//Preparamos
        $stm = $connect->prepare($queryAmbito);
        $stm->bindParam(1, $id);
//insertamos
        $ok=$stm->execute();
        if(!$ok){
            echo "error";
        }else{
            echo "eliminado";
        }
}
function newVariable($Numeracion,$arrayValoresNew,$connect){
    if (existeVariable($arrayValoresNew[0], $connect) !== "no") {
        //querys
        $queryVariable = "INSERT INTO variables (numeracion,nombre,descripcion,valor_minimo,valor_maximo) VALUES(?,?,?,?,?);";
//Preparamos
        $stm = $connect->prepare($queryVariable);
        $stm->bindParam(1, $Numeracion);
        $stm->bindParam(2, $arrayValoresNew[0]);
        $stm->bindParam(3, $arrayValoresNew[1]);
        $stm->bindParam(4, $arrayValoresNew[2]);
        $stm->bindParam(5, $arrayValoresNew[3]);
//insertamos
        $stm->execute();
        $idVariable= getIDvariable($Numeracion,$arrayValoresNew[0],$connect);
        echo json_encode(array("creado",$idVariable));
    } else {
        echo json_encode(array("existe"));
    }
}
function existeVariable($nomVariable,$connect){
    try {
//query
        $queryVariable = "Select id from variables where nombre='" . $nomVariable . "';";
//preparamos
        $stm = $connect->prepare($queryVariable);
//ejecutamos
        $stm->execute();
        $datos = $stm->fetch();
        if ($datos[0] != null) {
            return "no";
        } else {
            return "si";
        }
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
function getIDvariable($Numeracion,$nombre,$connect){
    try {
//query
$queryVariable = "select id from variables where numeracion=$Numeracion and nombre='$nombre'";
//preparamos
        $stm = $connect->prepare($queryVariable);
//ejecutamos
        $stm->execute();
        $datos = $stm->fetch();
        if ($datos[0] != null) {
            return $datos[0];
        } else {
            return 0;
        }
        //return $text;
        echo json_encode($text);
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage();
    }
}
/*function para ficheros*/
function getDocumentos(){
    $cont=0;
    chdir("../CopiaFormulariosCreados/");
    array_multisort(array_map('filemtime', ($files = glob("*.*"))), SORT_DESC, $files);
    foreach($files as $archivo)
    {
        $arrayNombreFicheros[$cont]=Array($archivo, formatBytes(filesize("../CopiaFormulariosCreados/".$archivo)),date('d/m/Y H:i:s', filectime("../CopiaFormulariosCreados/".$archivo)));
	$cont++;
    }
    echo json_encode($arrayNombreFicheros);
}
function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 

    // Descomenta cualquiera de las 2 opciones
     $bytes /= pow(1024, $pow);
    //$bytes /= (1 << (10 * $pow)); 

    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 
function eliminarDocumento($documento){
    if(file_exists("../CopiaFormulariosCreados/".$documento)){
        if(unlink("../CopiaFormulariosCreados/".$documento)){
        echo "success";
        }else{
            echo "error";
        }
    }else{
        echo "<h1>Error fichero no encontrado</h1>";
    }
}
?>