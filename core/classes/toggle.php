<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of toggle
 *
 * @author ariel
 */
class Toggle extends Accion{
    private $campo_id;
    private $nav_estado;
    private $id_estado;
    
    public function get_id_estado() {
        return $this->id_estado;
    }

    public function set_id_estado($id_estado) {
        $this->id_estado = $id_estado;
        return $this;
    }

        public function get_campo_id() {
        return $this->campo_id;
    }

    public function get_nav_estado() {
        return $this->nav_estado;
    }

    public function set_campo_id($campo_id) {
        $this->campo_id = $campo_id;
        return $this;
    }

    public function set_nav_estado($nav_estado) {
        $this->nav_estado = $nav_estado;
        return $this;
    }


    
}
