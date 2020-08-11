<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of service_alta_marchand
 *
 * @author ariel
 */
class service_alta_marchand extends Device_service{
    const PARAMETRO_NOMBRE="nombre";
    const PARAMETRO_APELLIDO_RS="apellido_rs";
    const PARAMETRO_DOCUMENTO="documento";
    const PARAMETRO_MINI_RS="mini_rs";
    const PARAMETRO_EMAIL="email";
    const PARAMETRO_LOCALIDAD="localidad";
    const PARAMETRO_CALLE="calle";
    const PARAMETRO_NUMERO="numero";
    const PARAMETRO_TELEFONO="telefono";
    const PARAMETRO_PROVINCIA="provincia";
    const PARAMETRO_PAIS="pais";
    const PARAMETRO_USERNAME="username";
    const PARAMETRO_USERPASS="password";
    const PARAMETRO_DISPOSITIVO="nombre_dispositivo";
    const PARAMETRO_TIPO_DISPOSITIVO="tipo_dispositivo";
    const PARAMETRO_CONDICION_IVA="condicion_iva";
    const PARAMETRO_TIPO_DOC="tipo_documento";
    const PARAMETRO_TIPO_SOC="tipo_sociedad";
    
    const OPS_WS=160;
    const TAMAÑO_SID=59;
    const WS_HABILITADO=151;
    const WS_COMOCRON="ever";
    const MENSAJE="Webservice3 por ws3.0";
    const COBRO_DIGITAL=2;
    const CD_RUBRO='51345';
    const CD_IVA=2;
    const CD_CUIT=1;
    const CD_LIQUIDAQ=1;
    const CD_ACTIVADO=1;
    public function ejecutar($array) {
        if(!$this->validar_campos($array)){
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
            return false;
        }
        $config=Configuracion::obtener_configuracion(self::$marchand->get_id_marchand());
        if(isset($config[Entidad::ENTIDAD_MARCHAND][Configuracion::CONFIG_PERMISO_WS]) and ($config[Entidad::ENTIDAD_MARCHAND][Configuracion::CONFIG_PERMISO_WS][Configuracion::CONFIG_PERMISO_ALTA_MARCHAND]['value']==1)){
            $cliente=new Cliente();
            //marchand;
            $marchand=new Marchand();
            $marchand->set_nombre($array[self::PARAMETRO_NOMBRE]);
            $marchand->set_apellido_rs($array[self::PARAMETRO_APELLIDO_RS]);
            $marchand->set_documento($array[self::PARAMETRO_DOCUMENTO]);
            $marchand->set_minirs($array[self::PARAMETRO_MINI_RS]);
            $marchand->set_email($array[self::PARAMETRO_EMAIL]);
            $marchand->set_gr_id_localidad($array[self::PARAMETRO_LOCALIDAD]);
            $marchand->set_gr_calle($array[self::PARAMETRO_CALLE]);
            $marchand->set_gr_numero($array[self::PARAMETRO_NUMERO]);
            $marchand->set_telefonos($array[self::PARAMETRO_TELEFONO]);
            $marchand->set_gr_id_provincia($array[self::PARAMETRO_PROVINCIA]);
            $marchand->set_gr_id_pais($array[self::PARAMETRO_PAIS]);
            $marchand->set_id_peucd(self::COBRO_DIGITAL);
            $marchand->set_id_subrubro(self::CD_RUBRO);
            $marchand->set_id_civa($array[self::PARAMETRO_CONDICION_IVA]);
            $marchand->set_id_tipodoc($array[self::PARAMETRO_TIPO_DOC]);
            $marchand->set_id_tiposoc($array[self::PARAMETRO_TIPO_SOC]);
            $marchand->set_id_liquidac(self::CD_LIQUIDAQ);
            $marchand->set_id_authstat(self::CD_ACTIVADO);
            //usumarchand;
            $usumarchand=new Usumarchand();
            $usumarchand->set_username($array[self::PARAMETRO_USERNAME]);
            $usumarchand->set_userpass($array[self::PARAMETRO_USERPASS]);
            $usumarchand->set_usermail($array[self::PARAMETRO_EMAIL]);
            $usumarchand->set_id_authstat(self::CD_ACTIVADO);
            Model::StartTrans();
            if($cliente->crear($marchand, $usumarchand)){
//                $usumarchand->setPassword($array[self::PARAMETRO_USERPASS]);
                //webservice
                $credenciales=$this->crear_credenciales_ws3($cliente);
                if(!$credenciales){
                    $this->respuesta_ejecucion= Device_service::RESPUESTA_EJECUCION_INCORRECTA;
                    $this->adjuntar_mensaje_para_usuario("Imposible crear las credeciales del cliente.");
                    Model::FailTrans();
                    return;
                }
                else
                    developer_log ("Credenciales creadas correctamente.");
                $dispositivo=$this->registrar_dispositivo($cliente->marchand->get_id_marchand(),$array[self::PARAMETRO_DISPOSITIVO],$array[self::PARAMETRO_TIPO_DISPOSITIVO]);
                if(!$dispositivo){
                    $this->respuesta_ejecucion= Device_service::RESPUESTA_EJECUCION_INCORRECTA;
                    $this->adjuntar_mensaje_para_usuario("Imposible crear las credeciales del dispositivo.");
                    Model::FailTrans();
                    return;
                }
                else
                    developer_log ("Dispositivo Registrado Correctamente.");
                //service_interno;
                if(!Model::HasFailedTrans() and Model::CompleteTrans()){
                    $this->respuesta_ejecucion= Device_service::RESPUESTA_EJECUCION_CORRECTA;
                    $this->adjuntar_dato_para_usuario(array("token"=>$dispositivo->get_token()));
                    $this->adjuntar_dato_para_usuario(array("mercalpha"=>$cliente->marchand->get_mercalpha()));
                    $this->adjuntar_dato_para_usuario(array("sid"=>$credenciales->get_sid()));
                    $this->adjuntar_mensaje_para_usuario("Cliente creado correctamente.");
                    return;
                }
                else{ $this->adjuntar_mensaje_para_usuario("Error no se puede generar el cliente.");
                    return;
                }
            }
            else{
                    $this->respuesta_ejecucion= Device_service::RESPUESTA_EJECUCION_CORRECTA;
                    $this->adjuntar_mensaje_para_usuario("Imposible crear al Cliente.");
                }
//            $this->adjuntar_mensaje_para_usuario("Operacion permitida.");
        }
        else{
            $this->adjuntar_mensaje_para_usuario("Operacion no permitida.");
            $this->respuesta_ejecucion= Device_service::RESPUESTA_EJECUCION_INCORRECTA;
        }
        
    }
    protected function validar_campos($array){
        $reflection = new ReflectionClass(get_class($this));
        $constantes=$reflection->getConstants();
        $error=false;
        unset($constantes["PARAMETRO_HANDSHAKE"]);
        unset($constantes["PARAMETRO_METODO"]);
        unset($constantes["PARAMETRO_TOKEN"]);
        unset($constantes["PARAMETRO_MERCALPHA"]);
        unset($constantes["PARAMETRO_SID"]);
        foreach ($constantes as $constante=>$valor){
            if(substr($constante,0,strlen("PARAMETRO_"))=="PARAMETRO_"){
                if(!isset($array[$valor]) OR $array[$valor]==""){
                    $error=true;
                    $this->adjuntar_mensaje_para_usuario("Falta el parametro $valor");
                }
            }
        }
        return !$error;
    }

    protected function crear_credenciales_ws3(Cliente $cliente){
        $afuturo=new Afuturo();
        $afuturo->set_solicitada("now()");
        $afuturo->set_id_marchand($cliente->marchand->get_id_marchand());
        $afuturo->set_id_clima(1);
        $afuturo->set_id_entidad(1);
        $afuturo->set_id_atcron(self::WS_HABILITADO);
        $afuturo->set_comocron(self::WS_COMOCRON);
        $afuturo->set_id_ops(self::OPS_WS);
        $afuturo->set_bruto_xml(self::MENSAJE);
        $afuturo->set_sid(Afuturo::generar_sid(self::OPS_WS, self::TAMAÑO_SID));
        if(!$afuturo->set()){
            Model::FailTrans();
            $this->adjuntar_mensaje_para_usuario("No se pueden crear las credenciales.");
            return false;
        }
        return $afuturo;
    }
    protected function registrar_dispositivo($id_marchand,$nombre_dispositivo,$tipo_dispositivo){
        $dispositivo= new Dispositivo();
        $dispositivo->set_identificador_dispositivo($nombre_dispositivo);
        $dispositivo->set_tipo($tipo_dispositivo);
        $dispositivo->set_token(Dispositivo::generar_token(128));
        $dispositivo->set_id_marchand($id_marchand);
        if(!$dispositivo->set()){
            return false;
        }
        return $dispositivo;
    }
}
