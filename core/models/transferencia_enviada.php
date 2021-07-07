<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of transferencia_recibida
 *
 * @author ariel
 */
class Transferencia_enviada extends Model {

    //put your code here
    public static $id_tabla = "id_transferencia";
    
    public $id_transferencia;
    public $id_destinatario;
    public $status;
    public $monto;
    public $id_authstat;
    public $id_cuenta;
    public $id_usuario;
    public $respuesta_servicio;
    public function get_id_transferencia() {
        return $this->id_transferencia;
    }

    public function get_id_destinatario() {
        return $this->id_destinatario;
    }

    public function get_status() {
        return $this->status;
    }

    public function get_monto() {
        return $this->monto;
    }

    public function get_id_authstat() {
        return $this->id_authstat;
    }

    public function get_id_cuenta() {
        return $this->id_cuenta;
    }

    public function get_id_usuario() {
        return $this->id_usuario;
    }

    public function get_respuesta_servicio() {
        return $this->respuesta_servicio;
    }

    public function set_id_transferencia($id_transferencia) {
        $this->id_transferencia = $id_transferencia;
        return $this;
    }

    public function set_id_destinatario($id_destinatario) {
        $this->id_destinatario = $id_destinatario;
        return $this;
    }

    public function set_status($status) {
        $this->status = $status;
        return $this;
    }

    public function set_monto($monto) {
        $this->monto = $monto;
        return $this;
    }

    public function set_id_authstat($id_authstat) {
        $this->id_authstat = $id_authstat;
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

    public function set_respuesta_servicio($respuesta_servicio) {
        $this->respuesta_servicio = $respuesta_servicio;
        return $this;
    }



}
