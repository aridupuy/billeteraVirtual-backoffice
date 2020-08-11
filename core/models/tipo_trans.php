<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//namespace Models;

/**
 * Description of tipo_trans
 *
 * @author ariel
 */
class Tipo_trans extends Model{
    //put your code here
    CONST SENTIDO_TRANSACCION_INGRESO = "ingreso";
    CONST SENTIDO_TRANSACCION_EGRESO = "egreso";
    public static $id_tabla = "id_tipo_trans";
    public static $prefijo_tabla = "ho_";
    private $id_tipo_trans;
    private $tipo;
    private $sentido;
    private $procesa_saldo;
    
    public function get_id_tipo_trans() {
        return $this->id_tipo_trans;
    }

    public function get_tipo() {
        return $this->tipo;
    }

    public function get_sentido() {
        return $this->sentido;
    }

    public function get_procesa_saldo() {
        return $this->procesa_saldo;
    }

    public function set_id_tipo_trans($id_tipo_trans) {
        $this->id_tipo_trans = $id_tipo_trans;
        return $this;
    }

    public function set_tipo($tipo) {
        $this->tipo = $tipo;
        return $this;
    }

    public function set_sentido($sentido) {
        $this->sentido = $sentido;
        return $this;
    }

    public function set_procesa_saldo($procesa_saldo) {
        $this->procesa_saldo = $procesa_saldo;
        return $this;
    }


    
}
