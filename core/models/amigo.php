<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of amigo
 *
 * @author ariel
 */
class Amigo extends Model {

    //put your code here

    public static $id_tabla = "id_amigo";
    private $id_amigo;
    private $id_cuenta;
    private $id_usuario;
    private $id_cuenta_amigo;
    private $id_usuario_amigo;
    private $fecha_gen;
    public function get_id_amigo() {
        return $this->id_amigo;
    }

    public function get_id_cuenta() {
        return $this->id_cuenta;
    }

    public function get_id_usuario() {
        return $this->id_usuario;
    }

    public function get_id_cuenta_amigo() {
        return $this->id_cuenta_amigo;
    }

    public function get_id_usuario_amigo() {
        return $this->id_usuario_amigo;
    }

    public function get_fecha_gen() {
        return $this->fecha_gen;
    }

    public function set_id_amigo($id_amigo) {
        $this->id_amigo = $id_amigo;
        return $this;
    }

    public function set_id_cuenta($id_cuenta) {
        $this->id_cuenta = $id_cuenta;
        return $this;
    }

    public function set_id_usuario($id_usuario) {
        $this->id_usuario = $id_usuario;
        return $this;
    }

    public function set_id_cuenta_amigo($id_cuenta_amigo) {
        $this->id_cuenta_amigo = $id_cuenta_amigo;
        return $this;
    }

    public function set_id_usuario_amigo($id_usuario_amigo) {
        $this->id_usuario_amigo = $id_usuario_amigo;
        return $this;
    }

    public function set_fecha_gen($fecha_gen) {
        $this->fecha_gen = $fecha_gen;
        return $this;
    }


}
