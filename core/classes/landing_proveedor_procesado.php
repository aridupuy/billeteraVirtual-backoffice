<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of proveedor_procesado
 *
 * @author juan
 */
class Landing_proveedor_procesado  extends Landing_proveedor_action {
    
    //put your code here
    public function procesar(Landing_proveedor $padre ) {
        
        $padre->set_view('views/landing_ya_se_proceso.html');
        
    }
    
    public function acepto(){
     // no hace nada ya se hizo antes
    }
    public function rechazo(){
        
    }
    
    public function putData($model,$view,$p){
        
    }
}
