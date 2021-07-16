<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Accion
 *
 * @author ariel
 */
class Accion {
   
    private $nav;
    private $titulo_nav;
//    
//    public function __construct($id,$campo_titulo=false,$nav_estado=false,$nav=false,$titulo_nav=false) {
//        $this->campo_id=$id;
//        $this->campo_titulo=$campo_titulo;
//        $this->nav_estado=$nav_estado;
//        $this->nav=$nav;
//        $this->titulo_nav=$titulo_nav;
//    }
//    
    public function get_titulo_nav() {
        return $this->titulo_nav;
    }

    public function set_titulo_nav($titulo_nav) {
        $this->titulo_nav = $titulo_nav;
        return $this;
    }

    public function get_nav() {
        return $this->nav;
    }

    public function set_nav($nav) {
        $this->nav = $nav;
        return $this;
    }

    

    public function get_campo_id() {
        return $this->campo_id;
    }

    public function set_campo_id($campo_id) {
        $this->campo_id = $campo_id;
        return $this;
    }
    public function get_nav_estado() {
        return $this->nav_estado;
    }

    public function set_nav_estado($nav_estado) {
        $this->nav_estado = $nav_estado;
        return $this;
    }



}
