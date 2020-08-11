<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of service_pago_proveedor
 *
 * @author ariel
 */
class service_pago_proveedor extends Device_service{
    const PARAMETRO_TOKEN_PROV="tokenProv";
    const PARAMETRO_ID_COMERCIO="idComercioPagador";
    const PARAMETRO_MONTO="monto";
    const PARAMETRO_CONCEPTO="concepto";
    const PARAMETRO_TRASLADA="traslada";
    public function ejecutar($array) {
        if(!isset($array[self::PARAMETRO_TOKEN_PROV])){
            $this->adjuntar_mensaje_para_usuario("falta el parametro: ".self::PARAMETRO_TOKEN_PROV);
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
            return false;
        }
        if(!isset($array[self::PARAMETRO_ID_COMERCIO])){
            $this->adjuntar_mensaje_para_usuario("falta el parametro: ".self::PARAMETRO_ID_COMERCIO);
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
            return false;
        }
        if(!isset($array[self::PARAMETRO_MONTO])){
            $this->adjuntar_mensaje_para_usuario("falta el parametro: ".self::PARAMETRO_MONTO);
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
            return false;
        }
        if(!isset($array[self::PARAMETRO_CONCEPTO])){
            $this->adjuntar_mensaje_para_usuario("falta el parametro: ".self::PARAMETRO_CONCEPTO);
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
            return false;
        }
        if(!isset($array[self::PARAMETRO_TRASLADA])){
            $this->adjuntar_mensaje_para_usuario("falta el parametro: ".self::PARAMETRO_TRASLADA);
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
            return false;
        }
        $marchand_prov=$this->obtener_marchand_prov($array[self::PARAMETRO_ID_COMERCIO]);
        try{
        $proveedor=new Trait_proveedor(self::$marchand, $marchand_prov);
        }catch(Exception $e){ var_dump($e);}
        try{
            $marchand= self::$marchand;
            if($proveedor->crear_pagos($marchand->get_id_marchand(), $array[self::PARAMETRO_MONTO], $array[self::PARAMETRO_TRASLADA], $array[self::PARAMETRO_CONCEPTO],null)){
                   if($proveedor->crear_transaccion($marchand->get_id_marchand(), $marchand_prov->get_id_marchand(), $array[self::PARAMETRO_MONTO], $array[self::PARAMETRO_CONCEPTO],$array[self::PARAMETRO_TRASLADA])){
                    $this->adjuntar_mensaje_para_usuario("Pago a proveedor realizado correctamente");
                    $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_CORRECTA;
                    //var_dump("aca");
                    return;
                }
                else{
                    $this->adjuntar_mensaje_para_usuario("Error al realizar el pago");
                    $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
                    return;
                }
                
            }
            else{
                $this->adjuntar_mensaje_para_usuario("Error al preparar el pago");
                $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
                return;
            }
        } catch (Exception $e){
                $this->adjuntar_mensaje_para_usuario($e->getMessage());
                $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
                return;
        }
        $this->adjuntar_mensaje_para_usuario("Error desconocido");
        $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
         
        return;
    }
    private function obtener_marchand_prov($mercalpha){
        
        $marchands = Marchand::select(array("mercalpha"=>$mercalpha));
        $marchand= new Marchand($marchands->fetchRow());
        return $marchand;
    }
}
