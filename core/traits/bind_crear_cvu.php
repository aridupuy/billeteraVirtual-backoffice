<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bind_crear_cvu
 *
 * @author ariel
 */
class Bind_crear_cvu extends Bind_consulta_cuenta{
    //put your code here
    
    
    public function crear_cvu($titular,$cuit,$moneda="ARS"){
        //ver despues como asignamos las cuentas por ahora solo se asocia a la primera.
        parent::consultar();
        $id_vista = array_keys($this->cuentas)[0];
        $id_cuenta = array_keys($this->cuentas[$id_vista])[0];
        $parametros=array("client_id"=>1,"cuit"=>$cuit,"name"=>$titular,"currency"=>"ARS");
        $resp=$this->llamado_api(self::URL."/$id_cuenta/$id_vista/wallet/cvu", $parametros);
        $resp = json_decode($resp,true);
        return $resp;
        
    }
    
    
    
}
