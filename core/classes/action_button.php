<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of action_button
 *
 * @author ariel
 */
class Action_button extends Accion{
    
    
    private $icono;
    
    public function get_icono() {
        return $this->icono;
    }

    public function set_icono($icono) {
        $this->icono = $icono;
        return $this;
    }


}
