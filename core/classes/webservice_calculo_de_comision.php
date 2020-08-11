<?php
class Webservice_calculo_de_comision extends Webservice{
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
        $array_comision=$transacciones->calculo_indirecto(self::$id_marchand, $array[self::MP], $array[self::MONTO]);
        if($transacciones->debe_procesar_costo_asociado($array[self::MP])){
            $array_comision_costo=$transacciones->calculo_directo(self::$id_marchand, 1010+$array[self::MP], $array[self::MONTO]);
            $valor=$array_comision_costo[5];
        }
        else{
            $valor=0;
        }
        if(!$array_comision){
            $this->adjuntar_mensaje_para_usuario ("Ha ocurrido un error al calcular la comision.");
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        $respuesta=array();
//        var_dump($array_comision_costo);
//        return;
        $respuesta["monto_a_cobrar"]=$array_comision[0]+$valor;
        $respuesta["comision_total"]=$array_comision[0]-$array_comision[6]+$valor;
        $respuesta["comision_cd"]=$array_comision[0]-$array_comision[6];
        $respuesta["comision_traslado_fvto"]=$valor;
        $respuesta["monto_sin_comision"]=$array_comision[6];
        
        $this->adjuntar_dato_para_usuario($respuesta);
        $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
        return;
    }

}
