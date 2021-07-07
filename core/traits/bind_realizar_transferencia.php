<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of bind_realizar_transferencia
 *
 * @author ariel
 */
class Bind_realizar_transferencia extends Bind_consulta_cuenta{
    //put your code here
    
    
    public function transferir($id_cuenta, $id_vista, $id_transf,$to, $monto, $concepto, $descripcion, $emails, $moneda = "ARS") {
        //parent::login();
        $params = array(
            "origin_id"=>"",
            "to",
            "value",
            "description",
            "concept",
            "emails"
            
        );
        $params["origin_id"]= date("Y_m_d_")."01";
        if(is_numeric($to)){
            $params["to"]["CBU"]=$to;
        }
        if(is_string($to)){
            $params["to"]["label"]=$to;
        }
        $params["value"]["currency"]=$moneda;
        $params["value"]["amount"]= number_format($monto,2);
        $params["description"]=$descripcion;
        $params["concept"]=$concepto;
        foreach ($emails as $email)
            $params["emails"][]=$email;
        
        $resp = $this->llamado_api(self::URL."/$id_cuenta/$id_vista/transaction-request-types/TRANSFER/transaction-requests", $params,"POST");
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
    }

}
