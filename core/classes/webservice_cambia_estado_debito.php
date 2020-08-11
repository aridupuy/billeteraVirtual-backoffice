<?php

class webservice_cambia_estado_debito  extends Webservice{
    const PARAMETRO_CUIT="cuit";
    const PARAMETRO_NOMBRE="nombre";
    const PARAMETRO_EMAIL="email";
    const PARAMETRO_IMPORTE="importe";
    const PARAMETRO_FECHA="fecha";
    const PARAMETRO_CONCEPTO="concepto";
    
    public function ejecutar($array) {
        if(!isset($array[self::PARAMETRO_CUIT])){
            $mensaje="El parametro ".self::PARAMETRO_CUIT." no esta definido.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_NOMBRE])){
            $mensaje="El parametro ".self::PARAMETRO_NOMBRE." no esta definido.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_EMAIL])){
            $mensaje="El parametro ".self::PARAMETRO_EMAIL." no esta definido.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_IMPORTE])){
            $mensaje="El parametro ".self::PARAMETRO_IMPORTE." no esta definido.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_FECHA])){
            $mensaje="El parametro ".self::PARAMETRO_FECHA." no esta definido.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_CONCEPTO])){
            $mensaje="El parametro ".self::PARAMETRO_CONCEPTO." no esta definido.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        developer_log("Cambia_estado_debito | antes de la consulta");
	$recordset= Debito_cbu::select_debito_a_cambiar_estado(self::$id_marchand,$array);
        developer_log("Cambia_estado_debito | despues de la consulta");
	if(!$recordset OR $recordset->rowCount()==0){
            $mensaje="No se pudo encontrar un débito con los datos proporcionados";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        elseif($recordset->rowCount()>1){
            $mensaje="No se pudo encontrar un unico débito";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        else { 
            foreach ($recordset as $row){
	//	developer_log("Cambia_estado_debito | "json_encode($row);
                $debito = new Debitos_cbu();
                if(!$debito->cambiar_estado($row['id_debito'], self::$id_marchand)){
                    $mensaje="No se pudo cambiar el estado del débito";
                    $this->adjuntar_mensaje_para_usuario($mensaje);
                    $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
                }
                else{
                    $mensaje="Estado del débito ha sido modificado con exito.";
                    $this->adjuntar_mensaje_para_usuario($mensaje);
                    $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
                }
            }
        }
    }

}
