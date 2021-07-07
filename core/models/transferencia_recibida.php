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
class Transferencia_recibida extends Model {

    //put your code here
    public static $id_tabla = "id_transferencia";
    
    public $id_transferencia;
    public $cbu_pagador;
    public $nombre_pagador;
    public $status;
    public $monto;
    public $id_authstat;
    public $cvu_cliente;
    public $cuit_cliente;
    public $id_cuenta;
    public $respuesta_servicio;
    public function get_id_transferencia() {
        return $this->id_transferencia;
    }

    public function get_cbu_pagador() {
        return $this->cbu_pagador;
    }

    public function get_nombre_pagador() {
        return $this->nombre_pagador;
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

    public function get_cvu_cliente() {
        return $this->cvu_cliente;
    }

    public function get_cuit_cliente() {
        return $this->cuit_cliente;
    }

    public function get_id_cuenta() {
        return $this->id_cuenta;
    }

    public function get_respuesta_servicio() {
        return $this->respuesta_servicio;
    }

    public function set_id_transferencia($id_transferencia) {
        $this->id_transferencia = $id_transferencia;
        return $this;
    }

    public function set_cbu_pagador($cbu_pagador) {
        $this->cbu_pagador = $cbu_pagador;
        return $this;
    }

    public function set_nombre_pagador($nombre_pagador) {
        $this->nombre_pagador = $nombre_pagador;
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

    public function set_cvu_cliente($cvu_cliente) {
        $this->cvu_cliente = $cvu_cliente;
        return $this;
    }

    public function set_cuit_cliente($cuit_cliente) {
        $this->cuit_cliente = $cuit_cliente;
        return $this;
    }

    public function set_id_cuenta($id_cuenta) {
        $this->id_cuenta = $id_cuenta;
        return $this;
    }

    public function set_respuesta_servicio($respuesta_servicio) {
        $this->respuesta_servicio = $respuesta_servicio;
        return $this;
    }



}
