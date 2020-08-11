<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of api_rest_gire_pago
 *
 * @author ariel
 */
class Api_rest_gire_cashout_pago extends Api_rest_gire {

    //put your code here
    public static $claves = array("id_numero", "cod_trx", "canal", "importe", "barra", "fecha_hora_operacion");

    public function __construct() {
        parent::__construct();
    }

    public function run() {
        Model::StartTrans();
        $variables = $this->get_variables();
        if($variables["importe"]<0)
            $importe = -1*$variables["importe"];
        else
            $importe = $variables["importe"];
        $cod_barra = $variables["barra"];
        $barra= $this->obtener_barra($cod_barra);
        $transaccion=new Transaccion();
        $fecha=new DateTime("now");
        $id_referencia=$barra->get_id();
        if(!$transaccion->crear($barra->get_id_marchand(), Mp::CASHOUT, $importe, $fecha, $id_referencia)){
            Model::FailTrans();
            throw new ExceptionApiGire("No se pudo completar el retiro de dinero.",10);
        }
        else{
            $barra->set_id_authstat(Authstat::SABANA_COBRADA);
            if(!$barra->set()){
                Model::FailTrans ();
            }
        }
        if (!Model::HasFailedTrans() and Model::CompleteTrans()){
            return $this->responder($barra, $transaccion);
        }
        Model::FailTrans();
        throw new ExceptionApiGire("Error no se pudo completar el pago", 10);
    }
    private function obtener_barra($cod_barra){
        $rs=Cashout_barras::select(array("barra"=>$cod_barra,"id_authstat"=> Authstat::SABANA_ENTRANDO));
        if($rs->rowCount()==0){
            throw new ExceptionApiGire("Error al generar el pago",10);
        }
        return new Cashout_barras($rs->fetchRow());
    }
    
//   
    private function responder(Cashout_barras $barra, Transaccion $transaccion) {
        /*
          "id_numero": "1234567890",
          "cod_trx": "1234567890123456789012",
          "barra": "12345678901234567890123456789012345678901234567890",
          "fecha_hora_operacion": "2014-09-19 15:34:56",
          "codigo_respuesta": "0",
          "msg": "Trx ok" */
        $id_barra = $barra->get_id();
        $respuesta = array();
        $variables = $this->get_variables();
        $respuesta["id_numero"] = $variables["id_numero"];
        $respuesta["cod_trx"] = $variables["cod_trx"];
        $respuesta["barra"] = $barra->get_barra();
        $respuesta["fecha_hora_operacion"] = $transaccion->moves->get_fecha_move();
        $respuesta["codigo_respuesta"] = "0";
        $respuesta["msg"] = 'Trx ok';
        return $respuesta;
    }

    public static function obtener_resultado_fallo(ExceptionApiGire $e = null) {
        if ($e !== null) {
            $result["codigo_respuesta"] = $e->getCode();
            $result["msg"] = $e->getMessage();
        } else {
            $result["codigo_respuesta"] = "10";
            $result["msg"] = "Error interno del sistema";
        }
        $vars = self::levantar_variables_fallo();
        if ($vars) {
            $result["cod_trx"] = $vars["cod_trx"];
            $result["id_numero"] = $vars["id_numero"];
        } else {
            $result["cod_trx"] = null;
            $result["id_clave"] = null;
        }
        return $result;
    }

    private function validar_barcode(Control $control, Barcode $barcode) {
        $rs_sabana = Sabana::select(array("revno" => $control->get_revno(), "barcode" => $barcode->get_barcode()));
        if ($rs_sabana->rowCount() == 0) {
            return true;
        }

        return false;
    }

}
