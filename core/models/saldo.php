<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//namespace Models;

/**
 * Description of saldo
 *
 * @author ariel
 */
class Saldo extends Model{
    public static $id_tabla = "id_saldo";
    private $id_saldo;
    private $id_transaccion;
    private $operacion;
    private $id_cuenta;
    private $fecha;
    private $monto;
    private $pri_fijo;
    private $pri_variable;
    private $monto_final;
    private $saldo;
    private $id_pricing;
    
    public function get_id_pricing() {
        return $this->id_pricing;
    }

    public function set_id_pricing($id_pricing) {
        $this->id_pricing = $id_pricing;
        return $this;
    }

        public function get_pri_fijo() {
        return $this->pri_fijo;
    }

    public function get_pri_variable() {
        return $this->pri_variable;
    }

    public function get_monto_final() {
        return $this->monto_final;
    }

    public function set_pri_fijo($pri_fijo) {
        $this->pri_fijo = $pri_fijo;
        return $this;
    }

    public function set_pri_variable($pri_variable) {
        $this->pri_variable = $pri_variable;
        return $this;
    }

    public function set_monto_final($monto_final) {
        $this->monto_final = $monto_final;
        return $this;
    }

        
    public function get_id_saldo() {
        return $this->id_saldo;
    }

    public function get_id_transaccion() {
        return $this->id_transaccion;
    }

    public function get_monto() {
        return $this->monto;
    }

    public function get_operacion() {
        return $this->operacion;
    }

    public function get_id_cuenta() {
        return $this->id_cuenta;
    }

    public function get_fecha() {
        return $this->fecha;
    }

    public function get_saldo() {
        return $this->saldo;
    }

    public function set_id_saldo($id_saldo) {
        $this->id_saldo = $id_saldo;
        return $this;
    }

    public function set_id_transaccion($id_transaccion) {
        $this->id_transaccion = $id_transaccion;
        return $this;
    }

    public function set_monto($monto) {
        $this->monto = $monto;
        return $this;
    }

    public function set_operacion($operacion) {
        $this->operacion = $operacion;
        return $this;
    }

    public function set_id_cuenta($id_cuenta) {
        $this->id_cuenta = $id_cuenta;
        return $this;
    }

    public function set_fecha($fecha) {
        $this->fecha = $fecha;
        return $this;
    }

    public function set_saldo($saldo) {
        $this->saldo = $saldo;
        return $this;
    }
    public static function select_saldo_actual($id_cuenta){
        //ojo con el sum
        $sql = "select coalesce(sum(saldo ), 0.00) as saldo,id_saldo from ef_saldo where id_cuenta = ? group by 2 order by 2 desc ";
        $variables[]=$id_cuenta;
        $rs=self::execute_select($sql,$variables,1);
        $row=$rs->fetchRow();
        return $row["saldo"];
    }

}
