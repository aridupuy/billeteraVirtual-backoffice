<?php

class webservice_generar_debito_automatico_tco_inmediato extends Webservice {
    const ACTIVAR_MP = false;
    const PARAMETRO_NOMBRE = "nombre";
    const PARAMETRO_APELLIDO = "apellido";
    const PARAMETRO_CUIT = "documento";
    const PARAMETRO_EMAIL = "email";
    const PARAMETRO_TCO = "tco";
    const PARAMETRO_CVV = "cvv";
    const PARAMETRO_MES_VTO = "mes_vto";
    const PARAMETRO_AÑO_VTO = "año_vto";
    const PARAMETRO_IMPORTE = "importe";
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
        if (!isset($array[self::PARAMETRO_CONCEPTO])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_CONCEPTO . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        Model::StartTrans();
        $id_tipocuenta=1;
        $responsable=new Responsable();
        $fecha= new DateTime("now");
        $fecha_vto_tarjeta=DateTime::createFromFormat("Y-m", $array[self::PARAMETRO_AÑO_VTO]."-".$array[self::PARAMETRO_MES_VTO]);
        $debito=new Debitos_tco();
        if($fecha==false){
            Model::FailTrans();
            $mensaje = "Formato de fecha inválido.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(validar_cuit($array[self::PARAMETRO_CUIT])){
            $tipo_doc = Tipodoc::CUIT_CUIL;
        }
        else{
            $tipo_doc = Tipodoc::DNI;
        }
        if(($responsable=$responsable->crear(self::$id_marchand, $array[self::PARAMETRO_NOMBRE], $array[self::PARAMETRO_APELLIDO], $array[self::PARAMETRO_CUIT], $tipo_doc))==false){
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
        $carrier="decidir";
        if(self::ACTIVAR_MP)
            $carrier="tcdaut";
        if(!Model::HasFailedTrans() and !$debito->crear(self::$id_marchand,$responsable::$clima_tco->get_id() , $array[self::PARAMETRO_IMPORTE], $fecha->format('d/m/Y'),$array[self::PARAMETRO_CONCEPTO], $cuotas, $modalidad_cuotas,false, false, false, false, false, "Webservice", $titular , $array[self::PARAMETRO_TCO],$array[self::PARAMETRO_CVV],$fecha_vto_tarjeta,$carrier,false)){
            Model::FailTrans();
            $mensaje = "No se puede generar el débito automático";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        $decidir = new Ordenador_debitos_decidir();
        //hay que reescribir esto y manejar mas objetos;
        /*Esto solamente tiene sentido para reutilizar la logica*/
        $debitos = Debito_tco::select_ordenador_mercadopago($debito->debito_tco->get_id(),$decidir);
        $debito_a_cobrar = $debitos->fetchRow(); 
//        var_dump($debito->debito_tco->get_id());
//        var_dump($debito_a_cobrar);
       $respuesta =$decidir->cobrar_uno($debito_a_cobrar);
       if(is_array($respuesta)){
            $status="rejected";
       }
       else
        $status = $respuesta->getStatus();
        $data = $respuesta->dataResponse;
            switch ($status) {
                case "approved":
//                    var_dump($data);
                    $id=$data["id"];
                    $respuesta="El Pago Fue Aprobado Correctamente";
                    $this->adjuntar_dato_para_usuario(array("estado"=> Authstat::DEBITO_DEBITADO));
                    $this->adjuntar_dato_para_usuario(array("id_transaccion"=>$id));
                    break;
                case "preapporved":
                    $this->adjuntar_dato_para_usuario(array("estado"=> Authstat::DEBITO_ENVIADO));
                    $respuesta = "El pago no pudo ser procesado en el momento. espere a que se procese automaticamente mas tarde.";
                    break;
                case "review":
                    $this->adjuntar_dato_para_usuario(array("estado"=> Authstat::DEBITO_ACTIVO));
                    $respuesta = "El debito esta observado, verifique respuesta mas tarde";
                    break;
                case "rejected":
                    $this->adjuntar_dato_para_usuario(array("estado"=> Authstat::DEBITO_OBSERVADO));
                    if (is_array($respuesta)){
//                        $respuesta = json_decode($respuesta[1],true);
//                        $mensaje=("Error: ".$respuesta[1]);
                        $this->adjuntar_dato_para_usuario(array("error",$respuesta[1]));

                    }
                    else
                        if(isset($respuesta->getStatus_details()->error["type"]) and isset($respuesta->getStatus_details()->error["reason"]["description"])){
                        $mensaje=", Motivo: ".$respuesta->getStatus_details()->error["reason"]["description"];
                    }
                    $respuesta = "El pago ha sido rechazado ".$mensaje;
                    break;
                default :
                    return Authstat::DEBITO_OBSERVADO;
                    break;
            }
       
       $this->adjuntar_mensaje_para_usuario($respuesta);
       if(Model::CompleteTrans()){
            $this->adjuntar_mensaje_para_usuario("Pago procesado correctamente.");
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_CORRECTA;
            return true;
        }
        
        $this->adjuntar_mensaje_para_usuario("No se puede procesar el pago.");
        $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
        return false;
            
    }

}
