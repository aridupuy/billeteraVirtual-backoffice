<?php
class Service_calculo_de_comision extends Device_service{
    const MONTO="monto";
    const MP="medio_de_pago";
    public function ejecutar($array) {
        if(!isset($array[self::MONTO])){
            $this->adjuntar_mensaje_para_usuario ("Falta el parametro ".self::MONTO);
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        if(!isset($array[self::MP])){
            $this->adjuntar_mensaje_para_usuario ("Falta el parametro ".self::MP);
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        $transacciones=new Transaccion();
        $array_comision=$transacciones->calculo_directo(self::$id_marchand, $array[self::MP], $array[self::MONTO]);
        if(!$array_comision){
            $this->adjuntar_mensaje_para_usuario ("Ha ocurrido un error al calcular la comision.");
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        $respuesta=array();
        $respuesta["monto_pagador"]=$array_comision[0];
        $respuesta["pag_fix"]=$array_comision[1];
        $respuesta["pag_var"]=$array_comision[2];
        $respuesta["monto_cd"]=$array_comision[3];
        $respuesta["cdi_fix"]=$array_comision[4];
        $respuesta["cdi_var"]=$array_comision[5];
        $respuesta["monto_marchand"]=$array_comision[6];
        $this->adjuntar_dato_para_usuario($respuesta);
        $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
        return;
    }

}
