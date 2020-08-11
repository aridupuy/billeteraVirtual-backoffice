<?php
class Webservice_crear_pagador extends Webservice{
    const PARAMETRO_PAGADOR='pagador';
    public function ejecutar($array)
    {
        $this->parametros_de_entrada=$array;
        if(!isset($array[self::PARAMETRO_PAGADOR])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_PAGADOR."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!is_array($array[self::PARAMETRO_PAGADOR]) OR !es_vector_asociativo($array[self::PARAMETRO_PAGADOR])){
            $mensaje="El parámetro '".self::PARAMETRO_PAGADOR."' no es corecto: debe ser un vector asociativo. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(count($array)!=1){
            $mensaje="El número de argumentos no es correcto. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        $pagador=new Pagador();
        $error=true;
        $permitir_pagador_vacio=false;
        try{
            if(($resultado=$pagador->crear(self::$marchand->get_id(), $array[self::PARAMETRO_PAGADOR], $permitir_pagador_vacio))){
                $error=false;
            }
        }     
        catch (Exception $ex){
            Model::fallar_transacciones_pendientes(0);
            $this->adjuntar_mensaje_para_usuario($ex->getMessage());
        }

        if(!$error){
            $this->adjuntar_mensaje_para_usuario("Pagador creado correctamente. ");
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_CORRECTA;
            return true;
        }
        $this->adjuntar_mensaje_para_usuario("Ha ocurrido un error al crear el Pagador. ");
        $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
        return false;
    }
}
