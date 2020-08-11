<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of webservice_enviar_correo
 *
 * @author ariel
 */
class webservice_enviar_boleta_correo extends Webservice {
    const PARAMETRO_DATOS="datos";
    const PARAMETRO_DATOS_DESTINATARIO="destinatario";
    const PARAMETRO_DATOS_ASUNTO="asunto";
    const PARAMETRO_DATOS_MENSAJE="mensaje";
    const PARAMETRO_DATOS_NRO_BOLETA="nro_boleta";
    public function ejecutar($array){
        error_log(json_encode($array));
        if(!isset($array[self::PARAMETRO_DATOS])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_DATOS."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_DATOS][self::PARAMETRO_DATOS_DESTINATARIO])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_DATOS_DESTINATARIO."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_DATOS][self::PARAMETRO_DATOS_ASUNTO])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_DATOS_ASUNTO."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_DATOS][self::PARAMETRO_DATOS_MENSAJE])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_DATOS_MENSAJE."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_DATOS][self::PARAMETRO_DATOS_NRO_BOLETA])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_DATOS_NRO_BOLETA."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(isset($array[self::PARAMETRO_DATOS])){
                if(!$this->enviar_correo($array[self::PARAMETRO_DATOS],true)){
                    $this->respuesta_ejecucion=0;
                    return;
            }
        }
        $this->respuesta_ejecucion=1;
        return;
    }
    private function enviar_correo($array)
    {
        $hoy=new DateTime('now');
        $hoy_format=$hoy->format('Ymdhis');
        $marchand= self::$marchand;
        $directorio=PATH_CDEXPORTS.$marchand->get_mercalpha().'/';
        $path=$array[self::PARAMETRO_DATOS_NRO_BOLETA].".html";
        $boletas=Bolemarchand::select_boleta_html(self::$id_marchand,$array[self::PARAMETRO_DATOS_NRO_BOLETA]);
        if($boletas->rowCount()!==1){
            $this->adjuntar_mensaje_para_usuario("No existe una boleta asociada a ese numero.");
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
        }
        $row=$boletas->fetchRow();
        $boleta_html=new View();
        if(!$boleta_html->loadHTML($row["boleta_html"])){
            $this->adjuntar_mensaje_para_usuario ("Error al adjuntar la boleta");
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
        }
        $html=$boleta_html->saveHTML();
        if(!Gestor_de_disco::crear_archivo($directorio,$path,$html,true)){
            if(self::ACTIVAR_DEBUG) developer_log('Error al crear el archivo. ');
            $this->adjuntar_mensaje_para_usuario("Error al adjuntar archivo.");
            return false;
        }
        $file_path=$directorio.$path;
        $emisor=Gestor_de_correo::MAIL_COBRODIGITAL_INFO;
        if(!Gestor_de_correo::enviar($emisor, $array[self::PARAMETRO_DATOS_DESTINATARIO], $array[self::PARAMETRO_DATOS_ASUNTO], $array[self::PARAMETRO_DATOS_MENSAJE],$file_path)){
            if(self::ACTIVAR_DEBUG) developer_log('Error al enviar el correo. ');
                $this->adjuntar_mensaje_para_usuario("Error al enviar el correo. ");
            return false;
        }
        else{
            if(self::ACTIVAR_DEBUG) developer_log('Correo correctamente enviado. ');
                $this->adjuntar_mensaje_para_usuario("Correo correctamente enviado.");
            return true;
        }
    }
}