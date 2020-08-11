<?php
class Webservice_editar_pagador extends webservice{
    const PARAMETRO_IDENTIFICADOR='identificador';
    const PARAMETRO_IDENTIFICADOR_VALOR='buscar';
    const PARAMETRO_PAGADOR='pagador';
    const PARAMETRO_CODIGO_DE_BARRAS='codigo_de_barras';
    public function ejecutar ($array)
    {
        $this->parametros_de_entrada=$array;

        if(!isset($array[self::PARAMETRO_PAGADOR])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_PAGADOR."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
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
        
        if(!is_array($array[self::PARAMETRO_PAGADOR]) OR !es_vector_asociativo($array[self::PARAMETRO_PAGADOR])){
            $mensaje="El parámetro '".self::PARAMETRO_PAGADOR."' no es corecto: debe ser un vector asociativo. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!is_string($array[self::PARAMETRO_IDENTIFICADOR])){
            $mensaje="El parámetro '".self::PARAMETRO_IDENTIFICADOR."' debe ser una cadena de texto. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!is_string($array[self::PARAMETRO_IDENTIFICADOR_VALOR])){
            $mensaje="El parámetro '".self::PARAMETRO_IDENTIFICADOR_VALOR."' debe ser una cadena de texto. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(count($array)!=3){
            $mensaje="El número de argumentos no es correcto.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        $error=false;
        //levanto variables
        $pagador_nuevo=  $array[self::PARAMETRO_PAGADOR];
        $identificador_label=  $array[self::PARAMETRO_IDENTIFICADOR];
        $identificador_valor=  $array[self::PARAMETRO_IDENTIFICADOR_VALOR];
        
        if(!$error){
            $pagador=new Pagador();
            if($identificador_label!= self::PARAMETRO_CODIGO_DE_BARRAS){
                $identificador_nombre=$pagador->obtener_nombre_desde_label(self::$marchand->get_id(), $identificador_label);
                if(!$identificador_nombre){
                    $error=true;
                    $mensaje="El identificador no pertenece a la estructura de clientes. ";
                    $this->adjuntar_mensaje_para_usuario($mensaje);
                }
            }
        }
        if(!$error){
           if($identificador_label== self::PARAMETRO_CODIGO_DE_BARRAS){
               $climarchand=$this->obtener_climarchand_desde_barcode(self::$marchand->get_id(), $identificador_valor);
           }
            elseif(($climarchand=$this->obtener_climarchand(self::$marchand->get_id(), $identificador_nombre,$identificador_valor))===false){
                $error=true;
                # Mejorar este mensaje
                $mensaje="Error al obtener el Pagador. ";
                $this->adjuntar_mensaje_para_usuario($mensaje);
                developer_log($mensaje);
            }
        }   
        if(!$error){
            try{
                $modificacion_parcial=true;
                $permitir_pagador_vacio=false;
                if(!$pagador->editar($climarchand->get_id(), $pagador_nuevo,$modificacion_parcial,$permitir_pagador_vacio)){
                    $error=true;
                }
            }  
            catch (Exception $ex){
                Model::fallar_transacciones_pendientes(0);
                $mensaje=$ex->getMessage();
                $this->adjuntar_mensaje_para_usuario($mensaje);
                developer_log($mensaje);
                $error=true;
            }
        }

        if(!$error){
            $this->adjuntar_mensaje_para_usuario("Pagador editado correctamente. ");
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_CORRECTA;
            return true;
        }
        $this->adjuntar_mensaje_para_usuario("Ha ocurrido un error al editar el Pagador. ");
        $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
        return false;
    }
}
