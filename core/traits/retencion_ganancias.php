<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of retencion_ganancias
 *
 * @author ariel
 */
class Retencion_ganancias extends Retencion_iva {

    //put your code here

    const TIPO = "GANANCIAS";
    const MP = MP::RETENCION_IMPOSITIVA_GANANCIAS; //temp 

    public $alicuota;
    public $id_mp = false;
    CONST ACTIVAR_RETENCION=true;
//     protected function calcular_retencion($moves) {
//         return $moves->get_monto_pagador()*($this->alicuota->get_porcentaje_monto()/100);
//    }
    public function debe_procesar_retenciones($id_mp) {
        if (!static::ACTIVAR_RETENCION) {
            developer_log("RETENCION DESACTIVADA");
            return false;
        }
        return parent::debe_procesar_retenciones($id_mp);
    }

    public function debe_procesar($id_mp) {
        if (!static::ACTIVAR_RETENCION) {
            developer_log("RETENCION DESACTIVADA");
            return false;
        }
        $mp = $this->get_mp($id_mp);
        if ($this->buscar_mipyme(static::TIPO)){
            developer_log("EN mipyme NO SE RETIENE ".static::TIPO);
            return false;
        }
        if($this->es_monotributista($this->marchand)){
            developer_log("Es monotributista NO SE RETIENE ".static::TIPO);
            return false;
        }
        if ($mp->get_sumaresta() == 1 AND $mp->get_sentido_transaccion() == "ingreso" and ! in_array($mp->get_id(), array(MP::COBRODIGITAL_COMISION, Mp::RETENCION_IMPOSITIVA, Mp::RETENCION_IMPOSITIVA_IVA, Mp::RETENCION_IMPOSITIVA_GANANCIAS, Mp::COSTO_RAPIPAGO,
                    Mp::COSTO_PAGO_FACIL,
                    Mp::COSTO_PROVINCIA_PAGO,
                    Mp::COSTO_COBRO_EXPRESS,
                    Mp::COSTO_RIPSA,
                    Mp::COSTO_MULTIPAGO,
                    Mp::COSTO_BICA,
                    Mp::COSTO_PRONTO_PAGO,
                    Mp::DEBITO_AUTOMATICO_COSTO_RECHAZO,
                    Mp::DEBITO_AUTOMATICO_COSTO_REVERSO))) {

            return true;
        }
        return false;
    }

    protected function esta_sujeto_a_retenciones($id_mp = false) {
//       return parent::esta_sujeto_a_retenciones($id_mp);
        try{
        $result = $this->obtener_estado_impositivo($id_mp);
        } catch (Exception $e){
            developer_log($e->getMessage());
            return false;
        }
        if ($result == false) {
          //  $regimen_iva = $is_tarjeta =  $is_lista = 0;
		return false;
        }
        list($regimen_iva, $is_tarjeta, $is_lista) = $result;
        $this->alicuota = $this->obtener_alicuota_ganancias($regimen_iva, $is_tarjeta, $is_lista);
        if ($this->alicuota == false) {
            developer_log("El marchand no tiene Retencion por Ganancias No SE COBRA");
            return false;
        }
        return $this;
    }

    protected function obtener_alicuota_ganancias($regimen_iva, $is_tarjeta, $is_lista) {
        $recordset = Iva_ganancia::select(array("regimen" => "GANANCIA", "iva_activo" => $regimen_iva, "is_tarjeta" => $is_tarjeta, "is_lista" => $is_lista));
        $row = $recordset->fetchRow();
        $iva = new Iva_ganancia($row);
        return $iva;
    }

    protected function obtener_alicuota() {
        return $this->alicuota;
    }

    public static function deducir_id_entidad($id_mp, $id_marchand = false, Transas $transas = null, Sabana $sabana = null) {
        return Entidad::ENTIDAD_ALICUOTA_AFIP;
    }

//    public function retener(\Moves $moves) {
//        
//        return parent::retener_parent($moves);
//    }
    public function validar_retencion(Moves $moves) {
        developer_log("GANANCIAS");
        $ganancias=false;
        if(self::$RECIENTE_TOPEO)
            $ganancias=true;
        if (!No_inscriptos_tope::esta_topeado($this->marchand)) {
            if (Retenciones_no_inscr::esta_dentro_del_limite($this->marchand,$ganancias)) { //horrible pero bueno hay que seguir
                return false;
            } else {
                return true;
            }
        }
        else
            return true;
    }

}
