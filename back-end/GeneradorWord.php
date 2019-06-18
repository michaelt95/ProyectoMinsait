<?php

include './../ConexionMysql/Conexion.php';
require_once("../vendor/autoload.php");
require_once ("Variable.php");

use PhpOffice\PhpWord\TemplateProcessor;

if (isset($_GET['fichero'])) {
    if (file_exists("../CopiaFormulariosCreados/".$_GET['fichero'])) {
        header("Content-Disposition: attachment; filename=" . $_GET['fichero']);
        echo file_get_contents("../CopiaFormulariosCreados/" . $_GET['fichero']);
    } else {
        echo "<h1>Error...\n!Fichero no encontrado!</h1>";
    }
}
if (isset($_POST["titulo"])) {
    //cargamos la plantilla
    $TemplateWord = new TemplateProcessor("../Plantillas/PlantillaFormulario.docx");
    //cargamos los campos a introducir
    $tituloProyecto = $_POST["titulo"];
    $nombreCliente = $_POST["nombreCliente"];
    $logoCliente = $_FILES["logo"]["tmp_name"];
    $idsAmbitos = json_decode($_POST["idsAmbitos"]);
    $date = new DateTime();
    $fechaHoy = $date->format('d/m/Y');
    //asignamos valores a plantila
    $TemplateWord->setValue("titulo_Proyecto", $tituloProyecto);
    $TemplateWord->setValue("nombre_cliente", $nombreCliente);
    $TemplateWord->setValue("fecha_hoy", $fechaHoy);
    $TemplateWord->setImageValue("logo_cliente",array('path' => $logoCliente,'width' => 200, 'height' => 100, 'ratio' => false));
    //hacer bucle para añadir ambitos y sus respectivos atributos
    $array_camposPlantilla = ["nombre_variable", "desc_variable", "vmin_variable", "vmax_variable"];
    //utilizamos un objeto Variable para poder acceder a las funciones
    $AuxVariable = new Variable();
    $arrayAsociativaVariables = $AuxVariable->cargaArray($connect, $idsAmbitos);
    $TemplateWord->cloneBlock("CLONEME", $AuxVariable->getLenghtArrayAsocciativaAmbitos());
    $numVariables = 1;
    foreach ($arrayAsociativaVariables as $key => $value) {
        foreach ($value as $valor) {
            $TemplateWord->setValue("nombre_variable#" . ($numVariables), $valor->nombre);
            $TemplateWord->setValue("desc_variable#" . ($numVariables), $valor->descripcion);
            $TemplateWord->setValue("vmin_variable#" . ($numVariables), $valor->v_minimo);
            $TemplateWord->setValue("vmax_variable#" . ($numVariables), $valor->v_maximo);
            $numVariables++;
        }
    }
    //guardamos el documento
    //comprobamos que no exista ese nombre de fichero
    if (!file_exists("../CopiaFormulariosCreados/Formulario_" . $nombreCliente . ".docx")) {
        $filename = "Formulario_" . $nombreCliente . ".docx";
    } else {
        do {
            $filename = "Formulario_".$nombreCliente."_".CrearClaveUnica().".docx";
        } while (file_exists("../CopiaFormulariosCreados/".$filename));
    }
    $TemplateWord->saveAs("../CopiaFormulariosCreados/" . $filename);
    echo $filename;
}
function CrearClaveUnica() {
    //Cadena de Letras
    $cadena = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    //Generamos un numero a partir de la fecha y hora del momento
    $cadena .= (string) strtotime(date("Y-m-d H:i:s"));
    //Creamos un array con la cadena
    $cadenaYtiempo = str_split($cadena);
    $clave = "";
    //Genero el Prefijo
    for ($i = 0; $i < 10; $i++) {
        $clave .= $cadenaYtiempo[rand(10, (count($cadenaYtiempo) - 1))];
    }
    //Retorno la Clave Generada
    return $clave;
}