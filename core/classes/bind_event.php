<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bind_event
 *
 * @author ariel
 */
class Bind_event  {
    protected static $manager;
    public static function set_manager(Bind_event_manager $manager){
        self::$manager=$manager;
    }
    public static function factory ($metodo){
            switch ($metodo){
                case "transfcvu":  
                    return new Bind_transferencia_cvu_event();
                    break;
                case "transfcvurev": 
                    return new Bind_transferencia_cvu_reverso_event();
                    break;
                case "debinaceptado": 
                    return new Bind_transferencia_debin_aceptado_event();
                    break;
                case "debinacreditado": 
                    return new Bind_transferencia_debin_acreditado_event();
                    break;
                case "debinarechazado": 
                    return new Bind_transferencia_debin_rechazado_event();
                    break;
            }
        
    }
    public function get_manager() {
        return self::$manager;
    }
}
