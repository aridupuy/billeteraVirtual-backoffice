<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of authstat
 *
 * @author ariel
 */
class Authstat extends \Model {

    //put your code here
    public static $id_tabla = "id_authstat";
    private $id_authstat;
    private $authstat;
    CONST ACTIVO=1;
    CONST BLOQUEADO = 3;
    CONST INACTIVO=4;
    public function get_id_authstat() {
        return $this->id_authstat;
    }

    public function get_authstat() {
        return $this->authstat;
    }

    public function set_id_authstat($id_authstat) {
        $this->id_authstat = $id_authstat;
        return $this;
    }

    public function set_authstat($authstat) {
        $this->authstat = $authstat;
        return $this;
    }

}
