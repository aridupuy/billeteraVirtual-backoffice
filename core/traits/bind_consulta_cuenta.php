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
class Bind_consulta_cuenta extends Bind_consulta_vista implements Bind_consulta_interface{
    //put your code here
    public $cuentas;
    const URL = "https://sandbox.bind.com.ar/v1/banks/322/accounts";
                
//    public function consulta_de_cuenta_bancaria() {
//        $this->llamado_api($url, $parametros);
//    }

    public function consultar() {
        parent::consultar();
        $this->obtener_cuentas_bancarias();
        return $this->cuentas;
    }

    public function obtener_cuentas_bancarias() {
        $this->cuentas=array();
        foreach ($this->vistas as $vista){
            $id=$vista["id"];
            $res = json_decode($this->consultar_una_vista($id),true);
            foreach ($res as $r){
                $this->cuentas[$id][$r["id"]] =$r;
            }
        }
        return $this->cuentas;
    }
    public function consultar_una_vista($id_vista){
        return $this->llamado_api(self::URL."/$id_vista",array(),"GET");
    }
    public function consultar_una(...$params){
        $id_cuenta  = $params[0];
        $id_vista = $params[1];
        return $this->llamado_api(self::URL."/$id_cuenta/$id_vista",array(),"GET");
    }

}
