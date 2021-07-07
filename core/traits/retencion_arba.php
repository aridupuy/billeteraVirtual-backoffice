<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of retencion_arba
 *
 * @author ariel
 */
class Retencion_arba extends Retencion_impositiva{
    
    const MP=MP::RETENCION_IMPOSITIVA;
    const TIPO="ARBA";
    CONST ACTIVAR_RETENCION_ARBA = true;
    public function __construct($id_marchand) {
        parent::__construct($id_marchand);
    }
    public static function deducir_id_entidad($id_mp, $id_marchand = false, Transas $transas = null, Sabana $sabana = null) {
        //va con entidad marchand por ahora
        return Entidad::ENTIDAD_ALICUOTA;
    }
    
    protected function esta_sujeto_a_retenciones($id_mp = false){
        if(!self::ACTIVAR_RETENCION_ARBA){
            developer_log("RETENCION DESACTIVADA");   
            return false;
        }
        error_log($this->marchand->get_documento());
        if(Sujeto_retencion::existe_sujeto($this->marchand->get_documento())===0)
            return false;
        else
            return true;
    }
    protected function obtener_alicuota(){
//        $this->alicuota=new Alicuota();
        $sujeto_retencion=new Sujeto_retencion();
        $sujeto_retencion=$sujeto_retencion->obtener_sujeto($this->marchand->get_documento());
        if($sujeto_retencion){
            $rs=Alicuota::obtener_alicuota($sujeto_retencion->get_letra_alicuota());
            $this->alicuota=new Alicuota($rs->fetchRow());
            return $this->alicuota;
        }
        return false;
    }
    
    protected function calcular_retencion($moves){
        //2 es el valor de la alicuota
        $this->obtener_alicuota();
        if($this->alicuota!=null)
            return $moves->get_monto_pagador()*($this->alicuota->get_porcentaje()/100);
        return 0;
    }
    public function obtener_retencion($moves){
        return $this->calcular_retencion($moves);
    }

    //temp homologar con los otros de la especie
    
    public function debe_procesar($id_mp) {
        return;
    }
    public function debe_procesar_retenciones($id_mp){return true;}
}
