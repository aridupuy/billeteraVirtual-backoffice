<?php

class Service_estadisticas_tenencias  extends Device_service{
    const PARAMETRO_FECHA_DESDE="fecha_desde";
    const PARAMETRO_FECHA_HASTA="fecha_hasta";
    const PARAMETRO_SENTIDO_TRANSACCION="sentido_transaccion";
    public function ejecutar($array) {
        if(!isset($array[self::PARAMETRO_FECHA_DESDE])){
            $this->adjuntar_mensaje_para_usuario("No se enviaron datos para el parametro '".self::PARAMETRO_FECHA_DESDE."'");
            $this->respuesta_ejecucion= Webservice::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        if(!isset($array[self::PARAMETRO_FECHA_HASTA])){
            $this->adjuntar_mensaje_para_usuario("No se enviaron datos para el parametro '".self::PARAMETRO_FECHA_HASTA."'");
            $this->respuesta_ejecucion= Webservice::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        if(!isset($array[self::PARAMETRO_SENTIDO_TRANSACCION])){
            $this->adjuntar_mensaje_para_usuario("No se enviaron datos para el parametro '".self::PARAMETRO_SENTIDO_TRANSACCION."'");
            $this->respuesta_ejecucion= Webservice::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        $fecha_desde= DateTime::createFromFormat("d-m-Y", $array[self::PARAMETRO_FECHA_DESDE]);
        $fecha_hasta= DateTime::createFromFormat("d-m-Y", $array[self::PARAMETRO_FECHA_HASTA]);
        if($fecha_desde===null OR $fecha_hasta===null){
            $this->adjuntar_mensaje_para_usuario("las fechas son invalidas");
            $this->respuesta_ejecucion= Webservice::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        $recordset=Moves::estadistica_de_tenencias(self::$id_marchand, $fecha_desde, $fecha_hasta, $array[self::PARAMETRO_SENTIDO_TRANSACCION]);
        $datos=array();
        if(!$recordset OR $recordset->rowCount()==0)
            $this->adjuntar_mensaje_para_usuario ("No hay datos disponibles.");
        foreach ($recordset as $row){
            $array=array();
            $array["monto_total"]=$row["monto_total"];
            $array["fecha"]=$row["fecha_move"];
            $array["sentido_transaccion"]=$row["sentido_transaccion"];
            $array["hoy"]=$row["hoy"];
            $datos[]=$array;
        }
        $this->adjuntar_dato_para_usuario($datos);
        $this->respuesta_ejecucion= Webservice::RESPUESTA_EJECUCION_CORRECTA;
        return;
    }
}
