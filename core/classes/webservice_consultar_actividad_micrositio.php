<?php

class Webservice_consultar_actividad_micrositio extends Webservice{
    const MAXIMA_CANTIDAD_A_CONSULTAR=20;
    const PARAMETRO_FECHA_DESDE='desde'; # Mayor o igual
    const PARAMETRO_FECHA_HASTA='hasta'; # Menor o igual
    const PARAMETRO_IDENTIFICADOR="identificador";
    const PARAMETRO_IDENTIFICADOR_VALOR="buscar";

    public function ejecutar($array)
    {
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
        
        $fecha_desde=  DateTime::createFromFormat(self::FORMATO_FECHA, $array[self::PARAMETRO_FECHA_DESDE]);
        $fecha_hasta=  DateTime::createFromFormat(self::FORMATO_FECHA, $array[self::PARAMETRO_FECHA_HASTA]);

        if(!$fecha_desde){
            $mascara=str_replace('!','',self::FORMATO_FECHA);
            $mensaje="El parámetro '".self::PARAMETRO_FECHA_DESDE."' debe tener el formato '".$mascara."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw  new Exception($mensaje);
        }
        if(!$fecha_hasta){
            $mascara=str_replace('!','',self::FORMATO_FECHA);
            $mensaje="El parámetro '".self::PARAMETRO_FECHA_HASTA."' debe tener el formato '".$mascara."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw  new Exception($mensaje);
        }
        
        if($fecha_desde->format(self::FORMATO_FECHA)!="!".$array[self::PARAMETRO_FECHA_DESDE]){
            $mensaje="El parametro '".self::PARAMETRO_FECHA_DESDE."' no es válido. ";
            developer_log("Overflow en ".self::PARAMETRO_FECHA_DESDE);
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw  new Exception($mensaje);
        }
        if($fecha_hasta->format(self::FORMATO_FECHA)!="!".$array[self::PARAMETRO_FECHA_HASTA]){
            $mensaje="El parametro '".self::PARAMETRO_FECHA_HASTA."' no es válido. ";
            developer_log("Overflow en ".self::PARAMETRO_FECHA_HASTA);
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw  new Exception($mensaje);
        }
        if($fecha_desde>$fecha_hasta){
            $mensaje="El parámetro '".self::PARAMETRO_FECHA_DESDE."' es mayor al parámetro '".self::PARAMETRO_FECHA_HASTA."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw  new Exception($mensaje);
        }
        if(count($array)!=4){
            $mensaje="El número de argumentos no es correcto.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }

        $error=false;
        $pagador=new Pagador();
        $identificador_nombre=$pagador->obtener_nombre_desde_label(self::$marchand->get_id(), $array[self::PARAMETRO_IDENTIFICADOR]);
        if(!$identificador_nombre){
            $error=true;
            $mensaje="El identificador no pertenece a la estructura de pagadores. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
        }
        if(!$error){
            if(($climarchand=$this->obtener_climarchand(self::$marchand->get_id(), $identificador_nombre,$array[self::PARAMETRO_IDENTIFICADOR_VALOR]))===false){
                $error=true;
                $this->adjuntar_mensaje_para_usuario("Error al obtener el Pagador. ");
            }
        }
        if(!$error){
            $recordset=Ulog::select_actividad_id_climarchand($climarchand->get_id_climarchand(), $fecha_desde, $fecha_hasta, Gestor_de_log::LOGLEVEL_MICROSITIO, self::MAXIMA_CANTIDAD_A_CONSULTAR);
            if($recordset){
                if($recordset->RowCount()==0){
                        $this->adjuntar_mensaje_para_usuario("No se registra actividad entre las fechas: ".$fecha_desde->format('d/m/Y')." y ".$fecha_hasta->format('d/m/Y')." ");
                        $this->respuesta_ejecucion=  self::RESPUESTA_EJECUCION_CORRECTA;
                }
                else{
                    foreach ($recordset as $row) {
                        unset($row[0]);
                        unset($row[1]);
                        $this->adjuntar_dato_para_usuario($row);
                    }

                }
                $this->adjuntar_mensaje_para_usuario("Consulta Realizada correctamente. ");
                $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_CORRECTA;
                return true;
            }
        }
        $this->adjuntar_mensaje_para_usuario("Ha ocurrido un error al realizar la consulta. ");
        $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
        return false;
    }
}
