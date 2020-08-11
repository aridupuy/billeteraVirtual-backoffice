<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bind_cuentas_bancarias
 *
 * @author ariel
 */
class Bind_consulta_vista extends Bind_login implements Bind_consulta_interface{
    const URL="https://sandbox.bind.com.ar/v1/banks/322/accounts";
    public $vistas;
    /*se implementa en bind_cuentas_bancarias*/
    public function consultar(){
        
        return $this->consultar_vistas();
        
    }
    public function consultar_una(...$params){
        
    }
    private function consultar_vistas(){
        $result = $this->llamado_api(self::URL,[],"GET");
        $resp = json_decode($result,true);
        foreach ($resp[0]["views_available"] as $row){
            $this->vistas[$row["id"]]=$row;
        }
        return $this->vistas;
    }
   
}
