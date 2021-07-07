<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

//namespace Models;

/**
 * Description of mp
 *
 * @author ariel
 */
class Mp extends \Model{
    public static $id_tabla="id_mp";
    private $id_mp;
    private $mp;
    private $id_tipo_trans;
    const TRANSFERENCIA_CVU = 1;
    const RECARGA_CD = 2;
    const TRANSFERENCIA_CVU_RECIBIDA = 3;
    const ENVIO_DE_DINERO=4;
    const RECEPCION_DE_DINERO=5;

    public function get_id_mp() {
        return $this->id_mp;
    }

    public function get_mp() {
        return $this->mp;
    }

    public function get_id_tipo_trans() {
        return $this->id_tipo_trans;
    }

    public function set_id_mp($id_mp) {
        $this->id_mp = $id_mp;
        return $this;
    }

    public function set_mp($mp) {
        $this->mp = $mp;
        return $this;
    }

    public function set_id_tipo_trans($id_tipo_trans) {
        $this->id_tipo_trans = $id_tipo_trans;
        return $this;
    }


}
