<?php

class webservice_generar_debito_automatico_tco extends Webservice {

    const PARAMETRO_NOMBRE = "nombre";
    const PARAMETRO_APELLIDO = "apellido";
    const PARAMETRO_CUIT = "cuit";
    const PARAMETRO_EMAIL = "email";
    const PARAMETRO_TCO = "tco";
    const PARAMETRO_CVV = "cvv";
    const PARAMETRO_MES_VTO = "mes_vto";
    const PARAMETRO_AÑO_VTO = "año_vto";
    const PARAMETRO_IMPORTE = "importe";
    const PARAMETRO_FECHA = "fecha";
    const PARAMETRO_CONCEPTO = "concepto";
    
    public function ejecutar($array) {
        if (!isset($array[self::PARAMETRO_NOMBRE])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_NOMBRE . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if (!isset($array[self::PARAMETRO_APELLIDO])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_APELLIDO . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if (!isset($array[self::PARAMETRO_CUIT])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_CUIT . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if (!isset($array[self::PARAMETRO_EMAIL])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_EMAIL . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if (!isset($array[self::PARAMETRO_TCO])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_TCO . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if (!isset($array[self::PARAMETRO_CVV])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_CVV . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if (!isset($array[self::PARAMETRO_MES_VTO])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_MES_VTO . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if (!isset($array[self::PARAMETRO_AÑO_VTO])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_AÑO_VTO. "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if (!isset($array[self::PARAMETRO_IMPORTE])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_IMPORTE . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        elseif(!is_numeric ($array[self::PARAMETRO_IMPORTE])){
            $mensaje = "El ".self::PARAMETRO_IMPORTE." no es correcto";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if (!isset($array[self::PARAMETRO_FECHA])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_FECHA . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if (!isset($array[self::PARAMETRO_CONCEPTO])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_CONCEPTO . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        Model::StartTrans();
        $id_tipocuenta=1;
        $responsable=new Responsable();
        $fecha= DateTime::createFromFormat("Ymd", $array[self::PARAMETRO_FECHA]);
        $fecha_vto_tarjeta=DateTime::createFromFormat("Y-m", $array[self::PARAMETRO_AÑO_VTO]."-".$array[self::PARAMETRO_MES_VTO]);
        $debito=new Debitos_tco();
        if($fecha==false){
            Model::FailTrans();
            $mensaje = "Formato de fecha inválido.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(($responsable=$responsable->crear(self::$id_marchand, $array[self::PARAMETRO_NOMBRE], $array[self::PARAMETRO_APELLIDO], $array[self::PARAMETRO_CUIT], Tipodoc::CUIT_CUIL))==false){
            Model::FailTrans();
            $mensaje = "No se puede generar el Responsable.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!Model::HasFailedTrans() and ($responsable=$responsable->crear_tco(self::$id_marchand, $responsable::$clima->get_id(), $array[self::PARAMETRO_TCO],$array[self::PARAMETRO_CVV],$array[self::PARAMETRO_MES_VTO],$array[self::PARAMETRO_AÑO_VTO], $array[self::PARAMETRO_NOMBRE], $array[self::PARAMETRO_CUIT]))==false){
            Model::FailTrans();
            $mensaje = "No se puede generar el tco";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        $titular=$array[self::PARAMETRO_APELLIDO]." ".$array[self::PARAMETRO_NOMBRE];
        $modalidad_cuotas="mensual";
        $cuotas=1;
        if(Debitos_tco::ACTIVAR_DECIDIR)
            $carrier="decidir";
        else
            $carrier=false;
        if(!Model::HasFailedTrans() and !$debito->crear(self::$id_marchand,$responsable::$clima_tco->get_id() , $array[self::PARAMETRO_IMPORTE], $fecha->format('d/m/Y'),$array[self::PARAMETRO_CONCEPTO], $cuotas, $modalidad_cuotas,false, false, false, false, false, "Webservice", $titular , $array[self::PARAMETRO_TCO],$array[self::PARAMETRO_CVV],$fecha_vto_tarjeta,$carrier)){
            Model::FailTrans();
            $mensaje = "No se puede generar el débito automático";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(Model::CompleteTrans()){
            $this->adjuntar_mensaje_para_usuario("Débito programado correctamente.");
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_CORRECTA;
            return true;
        }
        
        $this->adjuntar_mensaje_para_usuario("No se puede programar el débito.");
        $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
        return false;
            
    }

}
