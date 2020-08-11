<?php

class webservice_generar_debito_automatico extends Webservice {

    const PARAMETRO_NOMBRE = "nombre";
    const PARAMETRO_APELLIDO = "apellido";
    const PARAMETRO_CUIT = "cuit";
    const PARAMETRO_EMAIL = "email";
    const PARAMETRO_CBU = "cbu";
    const PARAMETRO_IMPORTE = "importe";
    const PARAMETRO_FECHA = "fecha";
    const PARAMETRO_CONCEPTO = "concepto";
    const PARAMETRO_CUOTAS = "cuotas";
    const PARAMETRO_MODALIDAD_CUOTAS = "modalidad_cuotas";
    const PARAMETRO_REFERENCIA_EXTERNA = "referencia";
    
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
        if (!isset($array[self::PARAMETRO_CBU])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_CBU . "'. ";
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
        $cuotas=1;
        $modalidad_cuotas="mes";
        if (isset($array[self::PARAMETRO_CUOTAS])) {
            $cuotas=$array[self::PARAMETRO_CUOTAS];
        }
        if (!isset($array[self::PARAMETRO_MODALIDAD_CUOTAS])) {
           $modalidad_cuotas=$array[self::PARAMETRO_MODALIDAD_CUOTAS];
        }
        Model::StartTrans();
        $id_tipocuenta=1;
        $responsable=new Responsable();
        $fecha= DateTime::createFromFormat("Ymd", $array[self::PARAMETRO_FECHA]);
        $debito=new Debitos_cbu();
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
        if(!Model::HasFailedTrans() and ($responsable=$responsable->crear_cbu(self::$id_marchand, $responsable::$clima->get_id(), $array[self::PARAMETRO_CBU], $id_tipocuenta, $array[self::PARAMETRO_NOMBRE], $array[self::PARAMETRO_CUIT]))==false){
            Model::FailTrans();
            $mensaje = "No se puede generar el cbu";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!Model::HasFailedTrans() and !$debito->crear(self::$id_marchand,$responsable::$clima_cbu->get_id() , $array[self::PARAMETRO_IMPORTE], $fecha->format('d/m/Y'),$array[self::PARAMETRO_CONCEPTO], $cuotas, $modalidad_cuotas, true,  false, false,  false, false, "Cargado por ws3",$array[self::PARAMETRO_NOMBRE]." ".$array[self::PARAMETRO_APELLIDO], $array[self::PARAMETRO_CBU],$array[self::PARAMETRO_REFERENCIA_EXTERNA])){
        
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
