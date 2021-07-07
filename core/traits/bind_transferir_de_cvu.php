<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bind_transferir_de_cvu
 *
 * @author ariel
 */
class Bind_transferir_de_cvu extends Bind_consulta_cuenta {
    //put your code here
    //:account_id/:view_id/transaction-request-types/TRANSFER-CVU/transaction-requests
    
    
    public function transferir(...$params){
        list($id_vista,$id_cuenta,$id_transaccion,$cvu,$cuit,$to,$currency,$amount,$description,$concepto,$emails)=$params;
        parent::consultar(); //este metodo carga las cuentas activas
        $id_vista = array_keys($this->cuentas)[0];
        $id_cuenta = array_keys($this->cuentas[$id_vista])[0];
        $parametros["origin_id"]=10;
        $parametros["origin_debit"]["cvu"]=$cvu;
        $parametros["origin_debit"]["cuit"]=$cuit;
//        $parametros["to"]["cbu"]=$cbu;
        if(is_numeric($to))
            $parametros["to"]["cbu"]=$to;
        else if ( !is_numeric($to))
            $parametros["to"]["label"]=$to;
        $parametros["value"]["currency"]=$currency;
        $parametros["value"]["amount"]=$amount;
        $parametros["description"]=$description;
        $parametros["concept"]=$concepto;
        foreach ($emails as $email){
            $parametros["emails"][]=$email;
        }
        $resp=$this->llamado_api(self::URL."/$id_cuenta/$id_vista/transaction-request-types/TRANSFER-CVU/transaction-requests", $parametros);
        return json_decode($resp,true);
    }
    
}
