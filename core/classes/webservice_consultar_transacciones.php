<?php

class Webservice_consultar_transacciones extends Webservice{
    const PARAMETRO_FECHA_DESDE='desde'; # Mayor o igual
    const PARAMETRO_FECHA_HASTA='hasta'; # Menor o igual
    const PARAMETRO_FILTROS='filtros'; # Opcional
    const PARAMETRO_FILTRO_NRO_BOLETA='nro_boleta';
    const PARAMETRO_FILTRO_CONCEPTO='concepto'; # ILIKE
    const PARAMETRO_FILTRO_IDENTIFICADOR='identificador'; # ILIKE
    const PARAMETRO_FILTRO_NOMBRE='nombre'; # ILIKE
    const PARAMETRO_FILTRO_TIPO='tipo'; # Ingresos, egresos, debito_automatico, tarjeta_credito
    const PARAMETRO_TRADUCCION_TIPO='id_mp'; # Segun Moves::select_transacciones()
    const PARAMETRO_TRADUCCION_NOMBRE='apellido'; # Segun Moves::select_transacciones()
    const PARAMETRO_TRADUCCION_IDENTIFICADOR='identificacion'; # Segun Moves::select_transacciones()
    const PARAMETRO_PAGINACION_OFFSET='offset'; # opcional
    const PARAMETRO_PAGINACION_LIMIT='limit'; # opcional
    public function ejecutar($array)
    {
        $this->parametros_de_entrada=$array;
        $offset=false;
        $limit=false;
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
        if(isset($array[self::PARAMETRO_PAGINACION_OFFSET]) AND is_numeric($array[self::PARAMETRO_PAGINACION_OFFSET]))
            $offset=$array[self::PARAMETRO_PAGINACION_OFFSET];
        if(isset($array[self::PARAMETRO_PAGINACION_LIMIT]) AND is_numeric($array[self::PARAMETRO_PAGINACION_LIMIT]))
            $limit=$array[self::PARAMETRO_PAGINACION_LIMIT];
        if(count($array)!=6 and count($array)!=5 AND count($array)!=4 AND count($array)!=3 AND count($array)!=2){
            $mensaje="El número de argumentos no es correcto.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(count($array)==3 AND !isset($array[self::PARAMETRO_FILTROS])){
            $mensaje="Los argumentos no son correctos.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        
        $fecha_desde=$array[self::PARAMETRO_FECHA_DESDE];
        $fecha_hasta=$array[self::PARAMETRO_FECHA_HASTA];
        if(isset($array[self::PARAMETRO_FILTROS])){
            
            if(!is_array($array[self::PARAMETRO_FILTROS]) OR !es_vector_asociativo($array[self::PARAMETRO_FILTROS]) ){
                $mensaje="El parámetro '".self::PARAMETRO_FILTROS."' no es corecto: debe ser un vector asociativo. ";
                $this->adjuntar_mensaje_para_usuario($mensaje);
                throw new Exception($mensaje);
            }
            $filtros=$array[self::PARAMETRO_FILTROS];
            $filtros_disponibles=array(self::PARAMETRO_FILTRO_IDENTIFICADOR,self::PARAMETRO_FILTRO_NOMBRE,self::PARAMETRO_FILTRO_TIPO,self::PARAMETRO_FILTRO_CONCEPTO,self::PARAMETRO_FILTRO_NRO_BOLETA);
            foreach ($filtros as $key => $value) {
                if(!in_array($key, $filtros_disponibles)){
                    $mensaje="El filtro '".$key."' no es correcto.";
                    $this->adjuntar_mensaje_para_usuario($mensaje);
                    throw new Exception($mensaje);
                }
            }
            if(count($filtros)>count($filtros_disponibles)){
                $mensaje="Hay filtros que no son correctos.";
                $this->adjuntar_mensaje_para_usuario($mensaje);
                throw new Exception($mensaje);
            }
            if(isset($filtros[self::PARAMETRO_FILTRO_TIPO])){
                $tipos_disponibles=array('ingresos','egresos','debito_automatico','tarjeta_credito');
                if(!in_array($filtros[self::PARAMETRO_FILTRO_TIPO], $tipos_disponibles)){
                    $mensaje="El parámetro '".self::PARAMETRO_FILTRO_TIPO."' no es corecto. ";
                    $this->adjuntar_mensaje_para_usuario($mensaje);
                    throw new Exception($mensaje);
                }
                $filtros[self::PARAMETRO_TRADUCCION_TIPO]=$filtros[self::PARAMETRO_FILTRO_TIPO];
                unset($filtros[self::PARAMETRO_FILTRO_TIPO]);
            }
            if(isset($filtros[self::PARAMETRO_FILTRO_IDENTIFICADOR])){
                if(!is_numeric($filtros[self::PARAMETRO_FILTRO_IDENTIFICADOR]) AND !is_string($filtros[self::PARAMETRO_FILTRO_IDENTIFICADOR])){
                    $mensaje="El parámetro '".self::PARAMETRO_FILTRO_IDENTIFICADOR."' no es corecto. ";
                    $this->adjuntar_mensaje_para_usuario($mensaje);
                    throw new Exception($mensaje);
                }
                $filtros[self::PARAMETRO_TRADUCCION_IDENTIFICADOR]=$filtros[self::PARAMETRO_FILTRO_IDENTIFICADOR];
                unset($filtros[self::PARAMETRO_FILTRO_IDENTIFICADOR]);
            }
            if(isset($filtros[self::PARAMETRO_FILTRO_NOMBRE]) AND !is_string($filtros[self::PARAMETRO_FILTRO_NOMBRE])){
                $mensaje="El parámetro '".self::PARAMETRO_FILTRO_NOMBRE."' no es corecto: debe ser una cadena de texto. ";
                $this->adjuntar_mensaje_para_usuario($mensaje);
                throw new Exception($mensaje);
            }
            if(isset($filtros[self::PARAMETRO_FILTRO_NOMBRE])){
                $filtros[self::PARAMETRO_TRADUCCION_NOMBRE]=$filtros[self::PARAMETRO_FILTRO_NOMBRE];
                unset($filtros[self::PARAMETRO_FILTRO_NOMBRE]);
            }
            if(isset($filtros[self::PARAMETRO_FILTRO_CONCEPTO]) AND !is_string($filtros[self::PARAMETRO_FILTRO_CONCEPTO])){
                $mensaje="El parámetro '".self::PARAMETRO_FILTRO_CONCEPTO."' no es corecto: debe ser una cadena de texto. ";
                $this->adjuntar_mensaje_para_usuario($mensaje);
                throw new Exception($mensaje);
            }
            if(isset($filtros[self::PARAMETRO_FILTRO_NRO_BOLETA]) AND !is_numeric($filtros[self::PARAMETRO_FILTRO_NRO_BOLETA])){
                $mensaje="El parámetro '".self::PARAMETRO_FILTRO_NRO_BOLETA."' no es corecto: debe ser un número. ";
                $this->adjuntar_mensaje_para_usuario($mensaje);
                throw new Exception($mensaje);
            }
            $filtros["boleta_concepto"]=$filtros[self::PARAMETRO_FILTRO_CONCEPTO];
        }
        else {
            $filtros=array();
        }
        // levanto variables
        $fecha_desde_str=$fecha_desde;
        $fecha_hasta_str=$fecha_hasta;
        
        $fecha_desde=  DateTime::createFromFormat(self::FORMATO_FECHA, $fecha_desde);
        $fecha_hasta=  DateTime::createFromFormat(self::FORMATO_FECHA, $fecha_hasta);

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
        
        if($fecha_desde->format(self::FORMATO_FECHA)!="!".$fecha_desde_str){
            $mensaje="El parametro '".self::PARAMETRO_FECHA_DESDE."' no es válido. ";
            // developer_log("Overflow en ".self::PARAMETRO_FECHA_DESDE);
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw  new Exception($mensaje);
        }
        if($fecha_hasta->format(self::FORMATO_FECHA)!="!".$fecha_hasta_str){
            $mensaje="El parametro '".self::PARAMETRO_FECHA_HASTA."' no es válido. ";
            // developer_log("Overflow en ".self::PARAMETRO_FECHA_HASTA);
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw  new Exception($mensaje);
        }
        if($fecha_desde<=$fecha_hasta){
//            $recordset=Moves::select_transacciones(self::$marchand->get_id(), $filtros,$fecha_desde,$fecha_hasta);
            $tipo_fecha=false;
            
            $recordset=Moves::select_transacciones(self::$marchand->get_id(), $filtros,$fecha_desde,$fecha_hasta,false,$offset,$limit);
            if($recordset->RowCount()==0){
                $this->adjuntar_mensaje_para_usuario("No se registran transacciones entre las fechas: ".$fecha_desde->format('d/m/Y')." y ".$fecha_hasta->format('d/m/Y')." ");
                $this->respuesta_ejecucion=  self::RESPUESTA_EJECUCION_CORRECTA;
            }
            else{
                    $retornar_id_transaccion=true;
                    $titulo_id_transaccion='id_transaccion';
//                    if(in_array(self::$marchand->get_id_marchand(),array(108,518,3))){
                    $rows=  Mod_xxxvi::preparar_array($recordset,1,$recordset->rowCount(), $retornar_id_transaccion, self::$marchand->get_id(),true,true,true);
//                    }
//                    else
//                        $rows=  Mod_xxxvi::preparar_array($recordset,1,$recordset->rowCount(), $retornar_id_transaccion, self::$marchand->get_id(),true,true);
                    $titulos=$rows[1];
                    array_unshift($titulos, $titulo_id_transaccion);
                    $rows=$rows[0];
	          //  var_dump($titulos);
                    $registros=array();
                    $j=0;
                    //reindexo el array para ponerle los titulos.
                    foreach ($rows as $num_row=>$row){
                        $registros[$j][$titulos[0]]=$this->obtener_id_transaccion($row['id_moves']);
                        unset($row['id_moves']);
                        $registros[$j][$titulos[1]]=$row['fecha'];
                        unset($row['fecha']);
			$registros[$j][$titulos[12]]=$row['sumaresta'];
			unset($row['sumaresta']);
                        foreach ($row as $i=>$dato){
                            $registros[$j][$titulos[$i+2]]=$dato;
                        }
                        $j++;
                    }
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
    private function obtener_id_transaccion($id_moves)
    {
        $id_transaccion=Gestor_de_hash::cifrar($id_moves,self::$marchand->get_mercalpha());
        return $id_transaccion;
    }
}
