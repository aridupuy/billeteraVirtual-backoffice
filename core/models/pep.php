<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of pep
 *
 * @author jhames
 */
class Pep extends Model {

    //put your code here
    public static $id_tabla = "id_pep";
    public static $prefijo_tabla="ho_";
    private $id_pep;
    private $pep;
    
    public function get_id_pep() {
        return $this->id_pep;
    }

    public function get_pep() {
        return $this->pep;
    }

    public function set_id_pep($id_pep) {
        $this->id_pep = $id_pep;
        return $this;
    }

    public function set_pep($pep) {
        $this->pep = $pep;
        return $this;
    }

}
