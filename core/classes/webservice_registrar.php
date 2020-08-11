<?php

class Webservice_registrar extends Webservice{
    const PARAMETRO_TOKEN="token";
    const PARAMETRO_TIPO="tipo";
    const PARAMETRO_IDENTIFICADOR_DISPOSITIVO="identificador_dispositivo";
    public function ejecutar($array) {
        error_log(json_encode($array));
        if(!isset($array[self::PARAMETRO_TOKEN]))
            $this->adjuntar_mensaje_para_usuario ("Falta el parametro ".self::PARAMETRO_TOKEN);
        if(!isset($array[self::PARAMETRO_TIPO]))
            $this->adjuntar_mensaje_para_usuario ("Falta el parametro ".self::PARAMETRO_TIPO);
        if(!isset($array[self::PARAMETRO_IDENTIFICADOR_DISPOSITIVO]))
            $this->adjuntar_mensaje_para_usuario ("Falta el parametro ".self::PARAMETRO_IDENTIFICADOR_DISPOSITIVO);
        $recordset= Dispositivo::select(array("identificador_dispositivo"=>$array[self::PARAMETRO_IDENTIFICADOR_DISPOSITIVO]));
        if($recordset->rowCount()==0){
            error_log("no existe dispositivo registrando...");
            $device=new Dispositivo();
            $device->set_token($array[self::PARAMETRO_TOKEN]);
            $device->set_tipo($array[self::PARAMETRO_TIPO]);
            $device->set_identificador_dispositivo($array[self::PARAMETRO_IDENTIFICADOR_DISPOSITIVO]);
            $device->set_id_marchand(self::$id_marchand);
            if($device->set()){
                $this->adjuntar_mensaje_para_usuario("Registrado correctamente.");
                $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
                return;
            }
        }
        elseif($recordset->rowCount()==1){
            error_log("existe dispositivo actualizando token...");
            $row=$recordset->fetchRow();
            $device=new Dispositivo();
            $device->setId($row["id_dispositivo"]);
            $device->set_token($array[self::PARAMETRO_TOKEN]);
            $device->set_id_marchand(self::$id_marchand);
            if($device->set()){
                $this->adjuntar_mensaje_para_usuario("Registrado correctamente.");
                $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
                return;
            }
        }
        else{
            error_log("Ha ocurrido un error mas de una vez el mismo dispositivo.");
            $this->adjuntar_mensaje_para_usuario("Error, no se pudo registrar.");
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
        }
    }
}
