<?php

class Service_cobrar_por_correo extends Device_service{
    const PARAMETRO_NOMBRE="nombre";
    const PARAMETRO_APELLIDO="apellido";
    const PARAMETRO_EMAIL="correo";
    const PARAMETRO_DOCUMENTO="documento";
    const PARAMETRO_DIRECCION="direccion";
    const PARAMETRO_CONCEPTO="concepto";
    const PARAMETRO_FECHA_VTO="vencimiento";
    const PARAMETRO_IMPORTE="importe";
    const ACTIVAR_DEBUG=true;
    public function ejecutar($array) 
    {
        if(!isset($array[self::PARAMETRO_NOMBRE])){
            $this->adjuntar_mensaje_para_usuario("Error, falta el parametro ".self::PARAMETRO_NOMBRE);
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
            return false;
        }
        if(!isset($array[self::PARAMETRO_APELLIDO])){
            $this->adjuntar_mensaje_para_usuario("Error, falta el parametro ".self::PARAMETRO_APELLIDO);
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
            return false;
        }
        if(!isset($array[self::PARAMETRO_EMAIL])){
            $this->adjuntar_mensaje_para_usuario("Error, falta el parametro ".self::PARAMETRO_EMAIL);
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
            return false;
        }
        if(!isset($array[self::PARAMETRO_DOCUMENTO])){
            $this->adjuntar_mensaje_para_usuario("Error, falta el parametro ".self::PARAMETRO_DOCUMENTO);
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
            return false;
        }
        if(!isset($array[self::PARAMETRO_DIRECCION])){
            $this->adjuntar_mensaje_para_usuario("Error, falta el parametro ".self::PARAMETRO_DIRECCION);
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
            return false;
        }
        if(!isset($array[self::PARAMETRO_CONCEPTO])){
            $this->adjuntar_mensaje_para_usuario("Error, falta el parametro ".self::PARAMETRO_CONCEPTO);
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
            return false;
        }
        if(!isset($array[self::PARAMETRO_FECHA_VTO])){
            $this->adjuntar_mensaje_para_usuario("Error, falta el parametro ".self::PARAMETRO_FECHA_VTO);
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
            return false;
        }
        if(!isset($array[self::PARAMETRO_IMPORTE])){
            $this->adjuntar_mensaje_para_usuario("Error, falta el parametro ".self::PARAMETRO_IMPORTE);
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
            return false;
        }
        Model::StartTrans();
        $boleta=$this->generar_boleta($array);
        $concepto=$array[self::PARAMETRO_CONCEPTO];
        $asunto="Boleta con vencimiento el ".$array[self::PARAMETRO_FECHA_VTO];
        if(!Model::hasFailedTrans()){
            if(self::ACTIVAR_DEBUG) developer_log('Enviando correo. ');
            if(!$this->enviar_correo($array[self::PARAMETRO_NOMBRE],$array[self::PARAMETRO_APELLIDO],$array[self::PARAMETRO_EMAIL],$asunto,$boleta,$concepto))
                Model::FailTrans();
        }
        if(Model::CompleteTrans()) {
            $this->adjuntar_mensaje_para_usuario("Boleta enviada correctamente.");
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
            $resultado=true;
        }
        else{
            $this->adjuntar_mensaje_para_usuario("Imposible enviar la boleta.");
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
            $resultado=true;
        }
        
    }
    private function generar_boleta($array)
    {
        $boleta=new Boleta_comprador();
        $importes=array("0"=>$array[self::PARAMETRO_IMPORTE]);
        $fecha=Datetime::createFromFormat("Ymd",$array[self::PARAMETRO_FECHA_VTO]);
        $fechas_vencimiento=array("0"=>$fecha->format('d/m/Y'));
        $concepto=$array[self::PARAMETRO_CONCEPTO];
        unset($array[self::PARAMETRO_IMPORTE]);
        unset($array[self::PARAMETRO_FECHA_VTO]);
        unset($array[self::PARAMETRO_CONCEPTO]);
        try {
            if(!($boleta=$boleta->crear(self::$id_marchand, $array, $importes, $fechas_vencimiento, $concepto))) {
                Model::FailTrans();
            }
            else{
                if(self::ACTIVAR_DEBUG) developer_log('Boleta de comprador correctamente creada. ');
            }
        } catch (Exception $e) {
            Gestor_de_log::set($e->getMessage(),0);
            $this->adjuntar_mensaje_para_usuario($e->getMessage());
            Model::fallar_transacciones_pendientes(1);
        }
        return $boleta;
    }
    private function enviar_correo($nombre,$apellido,$destinatario,$asunto,$boleta,$concepto_boleta)
    {
        $boleta_html=$boleta->bolemarchand->get_boleta_html();
        $hoy=new DateTime('now');
        $hoy_format=$hoy->format('Ymdhis');
        $marchand=new Marchand();
        $marchand->get(self::$id_marchand);
        # Parametrizar este mensaje en un HTML
        $mensaje=$this->preparar_mensaje($nombre, $apellido,$concepto_boleta, $marchand);
        $directorio=PATH_CDEXPORTS.$marchand->get_mercalpha().'/';
        $archivo='cobrar_por_correo('.$hoy_format.').html';
        if(!Gestor_de_disco::crear_archivo($directorio,$archivo,$boleta_html)){
            if(self::ACTIVAR_DEBUG) developer_log('Error al crear el archivo. ');
            return false;
        }

        $file_path=$directorio.$archivo;
        try{
            $emisor=Gestor_de_correo::MAIL_COBRODIGITAL_INFO;
            if(!Gestor_de_correo::enviar($emisor, $destinatario, $asunto, $mensaje,$file_path)){
                if(self::ACTIVAR_DEBUG) developer_log('Error al enviar el correo. ');
                return false;
            }
            else{
                if(self::ACTIVAR_DEBUG) developer_log('Correo correctamente enviado. ');
                return true;
            }
        } catch (Exception $e){
            $this->adjuntar_mensaje_para_usuario($e->getMessage());
        }
    }
     private function preparar_mensaje($nombre_cliente,$apellido_cliente,$concepto,Marchand $marchand)
    {
        $hoy=new DateTime('now');
        $view = new View();
        if(($nombre_cliente!=null or $nombre_cliente!="") AND ($apellido_cliente!=null OR $apellido_cliente!="") AND ($concepto!=null OR $concepto!="") AND isset($marchand)){
            $view->cargar("/views/mod_xix.mensaje_correo.html");
            #######logos###########
            $logo=$view->getElementById('logo');
            $img=$view->createElement('img');
            $img->setAttribute('src', URL_LOGO.$marchand->get_mlogo());
            $logo_cd=$view->createElement('img');
            $logo_cd->setAttribute('src', PATH_PUBLIC."logo.png");
            $logo->appendChild($logo_cd);
            $logo->appendChild($img);

            ###################datos############################
            $nombre_cliente=$nombre_cliente." ".$apellido_cliente.".";
            $span_nombre_cliente=$view->getElementById("nombre_cliente");
            $span_nombre_cliente->appendChild($view->createTextNode(ucfirst(strtolower($nombre_cliente))));
            $span_nombre_marchand=$view->getElementById("nombre_marchand");
            if($marchand->get_id_pfpj()==Pfpj::PERSONA_FISICA)
                $span_nombre_marchand->appendChild($view->createTextNode(ucfirst(strtolower ($marchand->get_nombre()))));
            else
                $span_nombre_marchand->parentNode->removeChild ($span_nombre_marchand);
            $span_apellido_marchand=$view->getElementById("apellido_marchand");
            $span_apellido_marchand->appendChild($view->createTextNode(ucfirst(strtolower($marchand->get_apellido_rs()).",")));
            $span_concepto=$view->getElementById('concepto');
            $span_concepto->appendChild($view->createTextNode(ucfirst(strtolower($concepto))."."));
            $span_fecha=$view->getElementById("fecha");
            $span_fecha->appendChild($view->createTextNode($hoy->format('d/m/Y')));
            return $view->saveHTML();
        }
        else
            return false;
        
    }
}
