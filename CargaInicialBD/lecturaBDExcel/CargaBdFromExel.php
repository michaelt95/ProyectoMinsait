<?php

require_once("vendor/autoload.php");
include './../../ConexionMysql/Conexion.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
/*
 //Descomentar para cargar tablas
                                
//cogemos la ruta del archivo
$rutaArchivo = "./bdExcel/Formularios_Entrevistas.xlsm";
$documentoExcel = IOFactory::load($rutaArchivo);

//insertamos ambitos
CargarTablaAmbitos($documentoExcel, $connect);
echo "<hr/><strong>Tabla Ambitos Cargada</strong><hr/>";
//insertamos variables
CargarTablaVariables($documentoExcel, $connect);
echo "<hr/><strong>Tabla Variables Cargada</strong><hr/>";
*/
 function CargarTablaAmbitos($documentoExcel, $connect) {
//seleccionamos la hoja que queremos(tambien se puede iterar cada una, pero en este caso no lo haremos)
    $hoja = $documentoExcel->getSheet(0);
    $filtroLetra = "A";
    foreach ($hoja->getRowIterator() as $fila) {
        foreach ($fila->getCellIterator() as $celda) {
            # El valor, así como está en el documento
            $valorRaw = $celda->getValue();
            # Fila, que comienza en 1, luego 2 y así...
            $fila = $celda->getRow();
            # Columna, que es la A, B, C y así...
            $columna = $celda->getColumn();
            if ($fila !== 1 && $fila !== 2 && $columna === $filtroLetra) {
                //guardamos el nuevo ambito en la tabla ambito
                GuardarAmbito($valorRaw, $connect);
            }
        }
    }
}
function GuardarAmbito($ambito, $connect) {
//querys
    $queryAmbitos = "INSERT INTO ambitos (ambito) VALUES(?);";
//Preparamos
    $stmAmbito = $connect->prepare($queryAmbitos);
    $stmAmbito->bindParam(1, $ambito);
//insertamos
    $stmAmbito->execute();
}
function GetAmbitoNumeracion($nombreAmbito, $connect) {
    try {
//query
        $getAmbito = "Select numeracion from ambitos where ambito='" . $nombreAmbito . "';";
//preparamos
        $stmNumeracion = $connect->prepare($getAmbito);
//ejecutamos
        $stmNumeracion->execute();
        $datos = $stmNumeracion->fetch();
        $numeroAmbito = $datos[0];
        return $numeroAmbito;
    } catch (PDOException $e) {
        return 'Error: ' . $e->getMessage();
    }
}
function GuardarVariables($numeracion, $nombre, $descripcion, $valor_minimo, $valor_maximo, $connect) {
    try {
//querys
        $queryVariables = "INSERT INTO variables (numeracion,nombre,descripcion,valor_minimo,valor_maximo) values(?,?,?,?,?);";
//Preparamos
        $stmVariable = $connect->prepare($queryVariables);
        $stmVariable->bindParam(1, $numeracion);
        $stmVariable->bindParam(2, $nombre);
        $stmVariable->bindParam(3, $descripcion);
        $stmVariable->bindParam(4, $valor_minimo);
        $stmVariable->bindParam(5, $valor_maximo);
//insertamos
        $stmVariable->execute();
    } catch (PDOException $e) {
        echo 'Error: ' . $e->getMessage()."<br>";
    }
}
function CargarTablaVariables($documentoExcel, $connect) {
//seleccionamos la hoja que queremos(tambien se puede iterar cada una, pero en este caso no lo haremos)
    $hoja = $documentoExcel->getSheet(1);
//Iterar filas   
    foreach ($hoja->getRowIterator() as $fila) {
        $variables = array();
        foreach ($fila->getCellIterator() as $celda) {
            # El valor, así como está en el documento
            $valorRaw = $celda->getValue();
            # Fila, que comienza en 1, luego 2 y así...
            $fila = $celda->getRow();
            # Columna, que es la A, B, C y así...
            $columna = $celda->getColumn();
            if ($fila !== 1 && $fila !== 2) {
                switch ($columna) {
                    case "A":
                        //Numeracion fk en Ambitos
                        $variables[0] = GetAmbitoNumeracion($valorRaw, $connect);
                        break;
                    case "D":
                        //Nombre variable
                        $variables[1] = $valorRaw;
                        break;
                    case "E":
                        //Descripcion variable
                        $variables[2] = $valorRaw;
                        break;
                    case "F":
                        //Valor_minimo variable
                        $variables[3] = $valorRaw;
                        break;
                    case "G":
                        //Valor maximo
                        $variables[4] = $valorRaw;
                        break;
                }
            }
        }
        if ($fila !== 1 && $fila !== 2) {
            //Guardamos variables en la tabla de variables
            GuardarVariables($variables[0], $variables[1], $variables[2], $variables[3], $variables[4], $connect);
            //echo "<strong>Numero de ambito: $variables[0]</strong><br>Nombre: $variables[1]<br>Descripcion: $variables[2]<br>Valor_minimo: $variables[3]<br>Valor_maximo: $variables[4]<br>";
        }
    }
}
?>