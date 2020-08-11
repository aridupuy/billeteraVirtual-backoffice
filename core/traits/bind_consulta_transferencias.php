<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bind_consulta_transferencias
 *
 * @author ariel
 */
class Bind_consulta_transferencias extends Bind_consulta_cuenta{
    //put your code here
    
    ///transaction-request-types/TRANSFER/
    public function consultar() {
        parent::consultar();
        foreach ($this->cuentas as $vista =>$cuentas){
            foreach ($cuentas as $id_cuenta =>$cuenta){
                $this->consultar_una_cuenta($id_cuenta,$vista);
            }
        }
        
    }
    public function consultar_una_cuenta(...$params) {
        list($id_cuenta,$id_vista)=$params;
        $resp=$this->llamado_api(self::URL."/$id_cuenta/$id_vista/transaction-request-types/TRANSFER", [],"GET");
        
        return json_decode($resp,true);
    }
    public function consultar_una(...$params) {
        list($id_cuenta,$id_vista,$id_transferencia)=$params;
        $resp=$this->llamado_api(self::URL."/$id_cuenta/$id_vista/transaction-request-types/TRANSFER/$id_transferencia", [],"GET");
        $r = json_decode($resp,true);
        if(!isset($r["status"])){
            return array(0,$r["message"]);
        }
        else{
            switch ($r["status"]){
                case "IN_PROGRESS":
                    return array(2,$r["status_description"]);
                case "UNKNOWN":
                case "UNKNOWN_FOREVER":
                case "FAILED":
                    return array(0,$r["status_description"]);
                case "COMPLETED":
                    return array(1,$r["status_description"]);
            }
        }
        return $r;
//        return json_decode($resp,true);
    }

}
