<?php
class Webservice_generar_boleta extends Webservice {
    const PARAMETRO_IDENTIFICADOR="identificador";
    const PARAMETRO_IDENTIFICADOR_VALOR="buscar";
    const PARAMETRO_VENCIMIENTOS="fechas_vencimiento";
    const PARAMETRO_IMPORTES="importes";
    const PARAMETRO_CONCEPTO="concepto";
    const PARAMETRO_PLANTILLA="plantilla"; # ESTO NO ESTA BUENO
    public function ejecutar($array) {
        $this->parametros_de_entrada=$array;
        if(!isset($array[self::PARAMETRO_IDENTIFICADOR])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_IDENTIFICADOR."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_IDENTIFICADOR_VALOR])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_IDENTIFICADOR_VALOR."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_VENCIMIENTOS])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_VENCIMIENTOS."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!is_array($array[self::PARAMETRO_VENCIMIENTOS]) OR es_vector_asociativo($array[self::PARAMETRO_VENCIMIENTOS])){
            $mensaje="El parámetro '".self::PARAMETRO_VENCIMIENTOS."' no es corecto: debe ser un vector no asociativo. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_IMPORTES]) OR es_vector_asociativo($array[self::PARAMETRO_VENCIMIENTOS])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_IMPORTES."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!is_array($array[self::PARAMETRO_IMPORTES]) OR es_vector_asociativo($array[self::PARAMETRO_IMPORTES])){
            $mensaje="El parámetro '".self::PARAMETRO_IMPORTES."' no es corecto: debe ser un vector no asociativo. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_CONCEPTO])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_CONCEPTO."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_PLANTILLA])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_PLANTILLA."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!is_string($array[self::PARAMETRO_IDENTIFICADOR])){
            $mensaje="El parámetro '".self::PARAMETRO_IDENTIFICADOR."' debe ser una cadena de texto'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!is_string($array[self::PARAMETRO_IDENTIFICADOR_VALOR])){
            $mensaje="El parámetro '".self::PARAMETRO_IDENTIFICADOR_VALOR."' debe ser una cadena de texto. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!is_string($array[self::PARAMETRO_PLANTILLA])){
            $mensaje="El parámetro '".self::PARAMETRO_PLANTILLA."' debe ser una cadena de texto. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!is_string($array[self::PARAMETRO_CONCEPTO])){
            $mensaje="El parámetro '".self::PARAMETRO_CONCEPTO."' debe ser una cadena de texto. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(count($array)!=6){
            $mensaje="El número de argumentos no es correcto.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        $i=0;
        $texto=array('primera','segunda','tercera','cuarta');
        foreach ($array[self::PARAMETRO_VENCIMIENTOS] as &$vencimiento) {
            $str_date=$vencimiento;
            $date_aux=  DateTime::createFromFormat(self::FORMATO_FECHA, $vencimiento);
            if($date_aux){
                if($date_aux->format(self::FORMATO_FECHA)=="!".$str_date){
                    $vencimiento=$date_aux->format('d/m/Y');
                }
                else{
                    $mensaje="El formato de la ".$texto[$i]." fecha de vencimiento no es correcto.";
                    $this->adjuntar_mensaje_para_usuario($mensaje);
                    developer_log("Overflow fecha");
                    throw new Exception($mensaje);
                }
            }
            else{
                $mensaje="El formato de la ".$texto[$i]." fecha de vencimiento no es correcto.";
                $this->adjuntar_mensaje_para_usuario($mensaje);
                throw new Exception($mensaje);
            }
            $i++;
        }
        
        $error=false;
        $identificador=  $array[self::PARAMETRO_IDENTIFICADOR];
        $dato_buscar=  $array[self::PARAMETRO_IDENTIFICADOR_VALOR];
        $pagador=new Pagador();
        $identificador_nombre=$pagador->obtener_nombre_desde_label(self::$marchand->get_id(), $identificador);
        if(!$identificador_nombre){
            $error=true;
            $mensaje="El identificador no pertenece a la estructura de pagadores. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
        }
        if(!$error){
            if(($climarchand=$this->obtener_climarchand(self::$marchand->get_id(), $identificador_nombre,$dato_buscar))===false){
                $error=true;
                $this->adjuntar_mensaje_para_usuario("Error al obtener el Pagador.");
            }
        }
        if(!$error){
            $boleta=new Boleta_pagador;
            try{
                if(!$boleta=$boleta->crear($climarchand->get_id_climarchand(), $array[self::PARAMETRO_PLANTILLA], $array[self::PARAMETRO_VENCIMIENTOS], $array[self::PARAMETRO_IMPORTES], $array[self::PARAMETRO_CONCEPTO])){
                    $this->adjuntar_mensaje_para_usuario("Ha ocurrido un error al generar la boleta.");
                    $error=true;
                }
                else { 
                    $this->adjuntar_dato_para_usuario($boleta->bolemarchand->get_nroboleta());
                    $this->adjuntar_mensaje_para_usuario("La boleta se ha generado correctamente.");
                }
            }  catch (Exception $ex){
                Model::fallar_transacciones_pendientes(0);
                $this->adjuntar_mensaje_para_usuario($ex->getMessage());
                $error=true;
            }
        }
        if(!$error){
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_CORRECTA;
            return true;
        }
        $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
        return false;
    }
    
}