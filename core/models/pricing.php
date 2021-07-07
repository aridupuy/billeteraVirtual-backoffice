<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of pricing
 *
 * @author ariel
 */
class Pricing extends Model {

    //put your code here
    public static $id_tabla = "id_pricing";
    private $id_pricing;
    private $id_mp;
    private $id_cuenta;
    private $pri_fijo;
    private $pri_variable;
    private $pri_minimo;
    private $pri_maximo;
    private $valido_desde;

    public function get_id_pricing() {
        return $this->id_pricing;
    }

    public function get_id_mp() {
        return $this->id_mp;
    }

    public function get_id_cuenta() {
        return $this->id_cuenta;
    }

    public function get_pri_fijo() {
        return $this->pri_fijo;
    }

    public function get_pri_variable() {
        return $this->pri_variable;
    }

    public function get_pri_minimo() {
        return $this->pri_minimo;
    }

    public function get_pri_maximo() {
        return $this->pri_maximo;
    }

    public function get_valido_desde() {
        return $this->valido_desde;
    }

    public function set_id_pricing($id_pricing) {
        $this->id_pricing = $id_pricing;
        return $this;
    }

    public function set_id_mp($id_mp) {
        $this->id_mp = $id_mp;
        return $this;
    }

    public function set_id_cuenta($id_cuenta) {
        $this->id_cuenta = $id_cuenta;
        return $this;
    }

    public function set_pri_fijo($pri_fijo) {
        $this->pri_fijo = $pri_fijo;
        return $this;
    }

    public function set_pri_variable($pri_variable) {
        $this->pri_variable = $pri_variable;
        return $this;
    }

    public function set_pri_minimo($pri_minimo) {
        $this->pri_minimo = $pri_minimo;
        return $this;
    }

    public function set_pri_maximo($pri_maximo) {
        $this->pri_maximo = $pri_maximo;
        return $this;
    }

    public function set_valido_desde($valido_desde) {
        $this->valido_desde = $valido_desde;
        return $this;
    }
    public static function select_min($array){
        $where  = " true ";
        foreach ($array as $key=>$value){
            if($value=="")
                $where .=" and $key is null  ";
            else
                $where .=" and $key=$value";
        }
        $sql = "select * from ef_pricing where  $where order by valido_desde desc ";
//        var_dump($sql);
        return self::execute_select($sql);
    }
}
