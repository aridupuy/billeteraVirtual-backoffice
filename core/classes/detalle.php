<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of detalle
 *
 * @author ariel
 */
class Detalle {

    private $titulos;
    private $datos;
    private $titulo;
    private $solo_uno;
        /**
     * Description of detalle
     *
     * @titulo $titulo es la referencia al campo de la consulta que actua como titulo
     * 
     */
    public function __construct($titulo,$solo_uno=0) {
        $this->titulo=$titulo;
        $this->solo_uno=$solo_uno;
    }
    public function get_titulos() {
        return $this->titulos;
    }

    public function get_datos() {
        return $this->datos;
    }

    public function set_titulos($titulos) {
        $this->titulos = $titulos;
    }

    public function set_datos($datos) {
        $this->datos = $datos;
    }

    public function preparar_arrays(ADORecordSet $recordset) {
        $datos = array();
        $d = array();
        $titulos = array();
        $recordset->Move(0);
        $row = $recordset->fetchRow();
        foreach ($row as $titulo=>$valor)
            if (!is_numeric($titulo)) {
                $titulos[] = $titulo;
        }
        $recordset->Move(0);
        foreach ($recordset as $clave => $r) {
            $d[$clave]=$r;
        }
        $datos=$d;
        $this->set_datos($datos);
        $this->set_titulos($titulos);
        return true;
    }
    public function is_solouno(){
        return $this->solo_uno;
    }
    /* preparar_arrays_from_array es solo para la estructura y modulo de pagadores */
    public function preparar_arrays_from_array( $array ) {
        $datos = array();
        $d = array();
        $titulos = array();
        $cabecera = $array[0];
        foreach ($cabecera as $sap=>$variable) {
            if(is_array($variable))
                $titulos[] = $variable['label'];
            
        }
        foreach ($array as $titulo=>$valor){
            foreach ($valor as $sap=>$variable) {
                if(is_array($variable))
                    $d[$variable['label']]=$variable['value'];
            }
            $datos[]=$d;
        }
        $this->set_datos($datos);
        $this->set_titulos($titulos);
        return true;
    }
    
    public function get_titulo_at($clave){
//        var_dump($this->titulos);
        return $this->titulos[$clave];
    }
    public function get_datos_at($linea,$clave){ 
        if($this->solo_uno){
            return $this->datos[0][$clave];
        }
        return $this->datos[$linea][$clave];
    }
    public function eliminar_titulo($columna){
        unset($this->titulos[$columna]);
    }
    public function eliminar_dato($key,$columna){
        unset($this->datos[$key][$columna]);
    }
    public function get_titulo() {
        return $this->titulo;
    }

    public function set_titulo($titulo) {
        $this->titulo = $titulo;
    }



}
