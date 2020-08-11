<?php
class webservice_consultar_debitos extends Webservice{
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
        $recordSet= Debito_cbu::select_daut_por_intervalos_filtros(self::$id_marchand, $fecha_desde, $fecha_hasta,$cuit,$concepto);
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
	    $array_a_enviar["observacion"]=$this->obtener_motivo_rechazo($row['motivo1']);
            
            if(isset($row['authstat2']) and $row['authstat2']!=null and $row['id_authstat2']!=Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO){
                $array_a_enviar["estado2"]=($row['authstat2']);
                
            }
            if(isset($row['monto2']) and $row['monto2']!=null and $row['id_authstat2']!=Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO){
                $array_a_enviar["monto2"]=($row['monto2']);
                
            }
            if(isset($row['fecha2']) and $row['fecha2']!=null and $row['id_authstat2']!=Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO){
                $array_a_enviar["fecha2"]=($row['fecha2']);
                
            }
            if(isset($row['motivo2'])and $row['motivo2']!=null and $row['id_authstat2']!=Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO){
                $array_a_enviar["observacion2"]=$this->obtener_motivo_rechazo($row['motivo3']);
                
            }
            
            if(isset($row['authstat3'])and $row['authstat3']!=null and $row['id_authstat3']!=Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO){
                $array_a_enviar["estado3"]=($row['authstat3']);
                
            }
            if(isset($row['monto3'])and $row['monto3']!=null and $row['id_authstat3']!=Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO){
                $array_a_enviar["monto3"]=($row['monto3']);
                
            }
            if(isset($row['fecha3'])and $row['fecha3']!=null and $row['id_authstat3']!=Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO){
                $array_a_enviar["fecha3"]=($row['fecha3']);
                
            }
            if(isset($row['motivo3'])and $row['motivo3']!=null and $row['id_authstat3']!=Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO){
                $array_a_enviar["observacion3"]=$this->obtener_motivo_rechazo($row['motivo3']);
                
            }
            if(isset($row['fecha_reverso'])and $row['fecha_reverso']!=null){
                $array_a_enviar["fecha_reverso"]=($row['fecha_reverso']);
                
            }
            if(isset($row['estado_rev'])and $row['estado_rev']!=null){
                $array_a_enviar["estado_rev"]=($row['estado_rev']);
                
            }
           
            $array_a_enviar["referencia"]=($row['referencia_externa']);
	    $this->adjuntar_dato_para_usuario($array_a_enviar);
        }
            $this->adjuntar_mensaje_para_usuario("Consulta realizada correctamente.");
        $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
    }
        private function obtener_motivo_rechazo($r){
        switch ($r) {
            case '':  
            case 'Ve ': $descerror=""; break;
            case 'R02':
            case 'Cu ': $descerror = 'Cuenta cerrada o suspendida';
                break;
            case 'R03': 
            case 'Cu ': $descerror = 'Cuenta inexistente';
            
                break;
            case 'R04':
            case 'Nú ': $descerror = 'Número de cuenta inválido';
                break;
            case 'R05': 
            $descerror = 'Orden de diferimiento';
                break;
            case 'R06': 
            $descerror = 'Defectos formales';
                break;
            case 'R07': 
            $descerror = 'Solicitud de la Entidad Originante';
                break;
            case 'R08': 
            case 'Or ': $descerror = 'Orden de no pagar';
                break;
            case 'R10': 
            case 'Fa ': $descerror = 'Falta de fondos';
                break;
            case 'R13': 
            case 'En': $descerror = 'Entidad destino inexistente';
                break;
            case 'R14': 
                $descerror = 'Identificación del Cliente de la Empresa errónea';
                break;
            case 'R15': 
            case 'Ba ': $descerror = 'Baja del servicio';
                break;
            case 'R17': 
                $descerror = 'Error de formato';
                break;
            case 'R19': 
            case 'Im ': $descerror = 'Importe erróneo';
                break;
            case 'R20': 
                $descerror = 'Moneda distinta a la cuenta de débito';
                break;
            case 'R23': 
            case 'Su ': $descerror = 'Sucursal no habilitada';
                break;
            case 'R24': 
            case 'Tr ': $descerror = 'Transacción duplicada';
                break;
            case 'R25': 
                $descerror = 'Error en registro adicional';
                break;
            case 'R26':
                $descerror = 'Error por campo mandatario';
                break;
            case 'R28': 
                $descerror = 'Rechazo primer vencimiento';
                break;
            case 'R29': 
                $descerror = 'Reversión ya efectuada';
                break;
            case 'R75': 
            case 'Fe ': $descerror = 'Fecha inválida';
                break;
            case 'R81': 
            case 'Fu ': $descerror = 'Fuerza mayor';
                break;
            case 'R87': 
            case 'Mo ': $descerror = 'Moneda inválida';
                break;
            case 'R89': 
            case 'Er ': $descerror = 'Errores en adhesiones';
                break;
            case 'R91':
            case 'Có ': $descerror = 'Código de Banco incompatible con moneda de transacción';
                break;
            case 'R95':
            case 'Re ': $descerror = 'Reversión receptora presentada fuera de término';
                break;
//            default: $descerror = 'Error no documentado';
  //              break;
        }
        return $descerror;
    }
}
