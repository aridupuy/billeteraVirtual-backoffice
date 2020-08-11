<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of costeador_galicia_ufo
 *
 * @author ariel
 */
class Costeador_galicia_ufo extends Costeador_galicia{
     protected function obtener_recordset()
	{
                $variables=array();
                $variables[]=Mp::GALICIA;
                $variables[]=Authstat::SABANA_ORIGENUFO;
		return Sabana::registros_a_costear_galicia($variables,$this->limite_de_registros_por_ejecucion);
	}
        public function costear_sabana(Sabana $sabana) {
        $this->developer_log("COSTEADOR_GALICIA UFO");
//        var_dump(substr($sabana->get_barcode(),0,4));
        if ($sabana->get_id_authstat() == Authstat::SABANA_ORIGENUFO) {
            $this->developer_log('Costeando Ufo: Obteniendo registro desde codigo de barras');
            $debito = new Debito_cbu();
            $id_debito = str_replace(" ", "", $sabana->get_barcode());
            $debito->get($id_debito);
//            var_dump($debito);
            if ($debito->get_id() == null){
                $rs= Debito_cbu::select_ufos($sabana->get_barcode(),$sabana->get_monto(),$sabana->get_fecha_pago());
                if($rs->rowCount()>1){
                    developer_log("Debito no encontrado por id buscando por id_clima fecha y monto");
                    $row=$rs->fetchRow();
                    $debito = new Debito_cbu($row);
                }
            }
            unset($recordset);
            unset($row);
        } else {
            $debito = new Debito_cbu();
            $id_debito = str_replace(" ", "", $sabana->get_barcode());
            $debito->get($id_debito);
//            var_dump($debito);
            if ($debito->get_id() == null) {
                $this->developer_log("Error, no se pudo encontrar el debito a costear");
                return false;
            }
        }
        Model::setTransacctionMode("READ_UNCOMMITED");
        Model::StartTrans();
        $this->developer_log("INICIA TRANSACCION DE COSTEO");
        if ($this->actualizar_estados_debito($sabana, $debito)) {
            if ($this->consolidar_debito($sabana, $debito)) {
                if (Model::CompleteTrans() and ! Model::hasFailedTrans()) {
                    $this->developer_log("TERMINA TRANSACCION DE COSTEO");
                    return true;
                }
            } else {
                $this->developer_log("Ha ocurrido un error al consolidar. ");
            }
        } else {
            $this->developer_log(" Ha ocurrido un error al actualizar los estados. ");
        }
        Model::FailTrans();
        Model::CompleteTrans();
        return false;
    }
}
