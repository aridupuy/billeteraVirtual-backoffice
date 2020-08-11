<?php
class webservice_consultar_debitos_tco extends Webservice{
    const PARAMETRO_CUIT="cuit";
    const PARAMETRO_FECHA_DESDE="fecha_desde";
    const PARAMETRO_FECHA_HASTA="fecha_hasta";
    const PARAMETRO_CONCEPTO="concepto";
    
    public function ejecutar($array) {
        if(isset($array[self::PARAMETRO_FECHA_DESDE])){
           $fecha_desde=Datetime::createFromFormat("Y-m-d",$array[self::PARAMETRO_FECHA_DESDE]);
           if($fecha_desde==false){
               $mensaje="El parámetro '".self::PARAMETRO_FECHA_DESDE."' no es correcto. ";
               $this->adjuntar_mensaje_para_usuario($mensaje);
               $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
               throw new Exception($mensaje);
           }
        }
        if(isset($array[self::PARAMETRO_FECHA_HASTA])){
            $fecha_hasta=Datetime::createFromFormat("Y-m-d",$array[self::PARAMETRO_FECHA_HASTA]);
           if($fecha_hasta==false){
               $mensaje="El parámetro '".self::PARAMETRO_FECHA_HASTA."' no es correcto.";
               $this->adjuntar_mensaje_para_usuario($mensaje);
               $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
               throw new Exception($mensaje);
           }
        }
        
        if(isset($array[self::PARAMETRO_CUIT])){
            $cuit=$array[self::PARAMETRO_CUIT];
            if(!validar_cuit($cuit)){
               $mensaje="El parámetro '".self::PARAMETRO_CUIT."' no es correcto.";
               $this->adjuntar_mensaje_para_usuario($mensaje);
               $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
               throw new Exception($mensaje);
            }
        }
        if(isset($array[self::PARAMETRO_CONCEPTO])){
            $concepto=$array[self::PARAMETRO_CONCEPTO];
        }
        $recordSet= Debito_tco::select_daut_por_intervalos_filtros(self::$id_marchand, $fecha_desde, $fecha_hasta,$cuit,$concepto);
        //var_dump($recordSet->rowCount());
	if($recordSet->rowCount()==0){
            $this->adjuntar_mensaje_para_usuario("No se encontraron debitos para esas fechas.");
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
        }
        foreach ($recordSet as $row){
	    $array_a_enviar=array();
            $array_a_enviar["estado"]=($row['authstat']);
            $array_a_enviar["monto"]=($row['monto']);
            $array_a_enviar["fecha_debito"]=($row['fecha']);
            $array_a_enviar["apellido"]=($row['titular']);
            $array_a_enviar["documento"]=($row['documento']);
            $array_a_enviar["email"]=($row['email']);
            $array_a_enviar["concepto"]=($row['concepto']);
	    $array_a_enviar["observacion"]=$row['motivo1'];
            
            if(isset($row['fecha_reverso'])and $row['fecha_reverso']!=null){
                $array_a_enviar["fecha_reverso"]=($row['fecha_reverso']);
                
            }
            if(isset($row['estado_rev'])and $row['estado_rev']!=null){
                $array_a_enviar["estado_rev"]=($row['estado_rev']);
                
            }
           
            $this->adjuntar_mensaje_para_usuario("Consulta realizada correctamente.");
	    $this->adjuntar_dato_para_usuario($array_a_enviar);
        }
        $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
    }
}
