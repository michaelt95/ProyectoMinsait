<?php

include './../ConexionMysql/Conexion.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Ambitos
 *
 * @author matorrest
 */
class Variable {

    //private $id;//id unico de cada variable
    private $numeracion; //campo que se asocia con su ambito
    private $nombre;
    private $descripcion;
    private $v_minimo;
    private $v_maximo;
    private $arrayAsocciativaAmbitos;

    function __construct($numeracion = 0, $nombre = "", $desc = "", $v_mini = "", $v_max = "") {
        $this->numeracion = $numeracion;
        $this->nombre = $nombre;
        $this->descripcion = $desc;
        $this->v_minimo = $v_mini;
        $this->v_maximo = $v_max;
    }

    function __get($name) {
        if (property_exists(__CLASS__, $name)) {
            return $this->$name;
        } else {
            return false;
        }
    }

    function cargaArray($connect, $arrayIdsAmbitos) {
        try {
            $key = 0;
            foreach ($arrayIdsAmbitos as $value) {
                //query
                $queryVariables = "Select numeracion,nombre,descripcion,valor_minimo,valor_maximo from variables where numeracion=" . $value . ";";
                //preparamos
                $stm = $connect->prepare($queryVariables);
                //ejecutamos
                $stm->execute();
                $contador = 0;
                while ($variables = $stm->fetch()) {

                    $objetoAux = new Variable($variables[0], $variables[1], $variables[2], $variables[3], $variables[4]);
                    $this->arrayAsocciativaAmbitos[$key][$contador] = $objetoAux;
                    $contador++;
                }
                $key++;
            }
            return $this->arrayAsocciativaAmbitos;
        } catch (PDOException $e) {
            return 'Error: ' . $e->getMessage();
        }
    }
    function getLenghtArrayAsocciativaAmbitos() {
        $contador = 0;
        foreach ($this->arrayAsocciativaAmbitos as $value) {
            foreach ($value as $value) {
                $contador++;
            }
        }
        return $contador;
    }

}