<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of webservice_checkpagos
 *
 * @author ariel
 */
class Webservice_checkpagos extends Webservice {
    const PARAMETRO_FECHA_DESDE='desde'; # Mayor o igual
    const PARAMETRO_FECHA_HASTA='hasta'; # Menor o igual
    const PARAMETRO_BARCODE='barcode29'; 
    const PARAMETRO_PMC='pmc19'; 
    const PARAMETRO_TIPOFECHA='fecha'; 
    const PARAMETRO_PAGINACION_OFFSET='offset'; # opcional
    const PARAMETRO_PAGINACION_LIMIT='limit'; # opcional
    public function ejecutar($array)
    {
        $this->parametros_de_entrada=$array;
        $offset=false;
        $limit=false;
        error_log(json_encode($array));
        if(!isset($array[self::PARAMETRO_FECHA_DESDE])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_FECHA_DESDE."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_FECHA_HASTA])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_FECHA_HASTA."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_PMC]) or $array[self::PARAMETRO_PMC]=='?' or $array[self::PARAMETRO_PMC]==''){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_PMC."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            $array[self::PARAMETRO_PMC]=null;
//            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_BARCODE]) or $array[self::PARAMETRO_BARCODE]=='?' or $array[self::PARAMETRO_BARCODE]==''){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_BARCODE."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            $array[self::PARAMETRO_BARCODE]=null;
//            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_TIPOFECHA])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_TIPOFECHA."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(isset($array[self::PARAMETRO_PAGINACION_OFFSET]) AND is_numeric($array[self::PARAMETRO_PAGINACION_OFFSET]))
            $offset=$array[self::PARAMETRO_PAGINACION_OFFSET];
        if(isset($array[self::PARAMETRO_PAGINACION_LIMIT]) AND is_numeric($array[self::PARAMETRO_PAGINACION_LIMIT]))
            $limit=$array[self::PARAMETRO_PAGINACION_LIMIT];
        
        
        $fecha_desde=$array[self::PARAMETRO_FECHA_DESDE];
        $fecha_hasta=$array[self::PARAMETRO_FECHA_HASTA];
        
        // levanto variables
        $fecha_desde_str=$fecha_desde;
        $fecha_hasta_str=$fecha_hasta;
        
        $fecha_desde=  DateTime::createFromFormat(self::FORMATO_FECHA, $fecha_desde);
        $fecha_hasta=  DateTime::createFromFormat(self::FORMATO_FECHA, $fecha_hasta);

        if(!$fecha_desde){
            $mascara=str_replace('!','',self::FORMATO_FECHA);
//            $mensaje="El parámetro '".self::PARAMETRO_FECHA_DESDE."' debe tener el formato '".$mascara."'. ";
//            $this->adjuntar_mensaje_para_usuario($mensaje);
//            throw  new Exception($mensaje);
        }
        if(!$fecha_hasta){
            $mascara=str_replace('!','',self::FORMATO_FECHA);
//            $mensaje="El parámetro '".self::PARAMETRO_FECHA_HASTA."' debe tener el formato '".$mascara."'. ";
//            $this->adjuntar_mensaje_para_usuario($mensaje);
//            throw  new Exception($mensaje);
        }
        if($fecha_desde)
            if($fecha_desde->format(self::FORMATO_FECHA)!="!".$fecha_desde_str){
                $mensaje="El parametro '".self::PARAMETRO_FECHA_DESDE."' no es válido. ";
                // developer_log("Overflow en ".self::PARAMETRO_FECHA_DESDE);
                $this->adjuntar_mensaje_para_usuario($mensaje);
                throw  new Exception($mensaje);
            }
        if($fecha_hasta)
            if($fecha_hasta->format(self::FORMATO_FECHA)!="!".$fecha_hasta_str){
                $mensaje="El parametro '".self::PARAMETRO_FECHA_HASTA."' no es válido. ";
                // developer_log("Overflow en ".self::PARAMETRO_FECHA_HASTA);
                $this->adjuntar_mensaje_para_usuario($mensaje);
                throw  new Exception($mensaje);
            }
            if(!$array[self::PARAMETRO_TIPOFECHA])
                $fecha="fechapago";
            else $fecha=$array[self::PARAMETRO_TIPOFECHA];
        if($fecha_desde<=$fecha_hasta OR (!isset($fecha_desde) and !isset($fecha_hasta) )){
//            $recordset=Moves::select_transacciones(self::$marchand->get_id(), $filtros,$fecha_desde,$fecha_hasta);
            $recordset=Moves::select_checkpagos(self::$marchand->get_id(),$fecha,$fecha_desde,$fecha_hasta,$array[self::PARAMETRO_BARCODE],$array[self::PARAMETRO_PMC],$offset,$limit);
            if($recordset->RowCount()==0){
                $this->adjuntar_mensaje_para_usuario("No se registran transacciones.");
                $this->respuesta_ejecucion=  self::RESPUESTA_EJECUCION_CORRECTA;
            }
            else{
                    $retornar_id_transaccion=true;
                    $titulo_id_transaccion='id_transaccion';
                    $registros=array();
                    $registros["recibido"]=$array;
                    $registros["comercio"]["idComercio"]= self::$marchand->get_mercalpha();
                    if(self::$marchand->get_id_authstat()==1)
                        $registros["comercio"]["status"]= "Activo";
                    else
                        $registros["comercio"]["status"]= "Inactivo";
                    $registros["comercio"]["Denominacion"]= self::$marchand->get_minirs();
                    $registros["comercio"]["documento"]= self::$marchand->get_documento();
                    $registros["comercio"]["existe_barcode"]=$recordset->rowCount();
                    foreach ($recordset as $row){
                        $array=array("barcode29"=>$row["barcode"],"pmc19"=>$row["pmc19"],"importe_pagado"=>$row["monto_pagador"],"fecha_pagado"=>$row["fecha"],"fecha_movimiento"=>$row["fecha_move"],"medio_de_pago"=>$row["mp"],"icono"=>$row["iconmov"],"unique"=>$this->obtener_id_transaccion(self::$marchand->get_mercalpha(),$row["id_moves"]));
                        $registros["barcode"]["pagos"]["item"][]=$array;
//                        $registros["unique"]= $this->obtener_id_transaccion(self::$marchand->get_mercalpha(),$row["id_moves"]);
//                        $registros[""]
                    }
                    $registros["cantidad_de_pagos"]=$recordset->rowCount();
                    
                    $this->adjuntar_dato_para_usuario($registros);
                }
            }
        else{
            $mensaje="El parámetro '".self::PARAMETRO_FECHA_DESDE."' es mayor al parámetro '".self::PARAMETRO_FECHA_HASTA."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw  new Exception($mensaje);
        }
        $this->adjuntar_mensaje_para_usuario("Consulta Realizada correctamente. ");
        $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_CORRECTA;
        return true;
    }
    private function obtener_id_transaccion($idComercio,$id_moves)
    {
//        $id_transaccion=Gestor_de_hash::cifrar($idComercio.$id_moves,self::$marchand->get_mercalpha());
         $id_transaccion=$idComercio.$id_moves;
        return $id_transaccion;
    }
}
