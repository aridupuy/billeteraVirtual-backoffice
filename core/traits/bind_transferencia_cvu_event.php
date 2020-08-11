<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bind_transferencia_cvu_event
 *
 * @author ariel
 */
class Bind_transferencia_cvu_event extends Bind_event  implements Bind_event_interface {
    //put your code here
    public function run() {
        return $this->get_manager()->get_variables();
    }

}
