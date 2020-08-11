<?php

class Costeador_galicia_nuevo extends Costeador_nuevo
{
	const PERMITIR_ACTUALIZACION_DE_MULTIPLES_AGENDA_VINVULO=true;
	const ETIQUETA_ESTADO_SABANA='stat'; # Utilizada por Ufos en preprocesador_galicia
	const ETIQUETA_DESCRIPCION_RECHAZO='descerror';

//	protected function obtener_recordset()
//	{
//                $variables=array();
//                $variables[]=Mp::GALICIA;
//                $variables[]=Authstat::SABANA_ENTRANDO;
//               
////                $variables[]=Authstat::SABANA_ORIGENUFO;
//
//		return Sabana::registros_a_costear_galicia($variables,$this->limite_de_registros_por_ejecucion);
//	}
	protected function actualizar_estados(Sabana $sabana, Barcode $barcode)
	{
		# El estado original de la sabana es la interfaz con el estado de los ufos y los comunes
		$estado_original_sabana=$this->obtener_estado_original_sabana($sabana);
                developer_log($estado_original_sabana);
		if($this->actualizar_barcode($estado_original_sabana,$barcode)){
			if($this->obtener_y_actualizar_agenda_vinvulo($sabana,$estado_original_sabana, $barcode)){
				if($this->actualizar_sabana($sabana, $estado_original_sabana)){
					return true;
				}		
			}
		}
		return false;
	}
	protected function actualizar_barcode($estado_original_sabana, Barcode $barcode)
	{
		$this->developer_log("Actualizando Barcode. ");
		if(!($id_authstat=$this->obtener_estado_a_actualizar_barcode($estado_original_sabana))){
			$this->developer_log('Ha ocurrido un error al obtener el estado a actualizar en el barcode. ');
			return false;
		}
		$barcode->set_id_authstat($id_authstat);
		if($barcode->set()){
                    if (self::ACTIVAR_DEBUG)
                        developer_log("Barcode actualizado correctamente.");
			return true;
		}
		$this->developer_log("Ha ocurrido un error al actualizar el Barcode. ");
		return false;
	}
	protected function obtener_y_actualizar_agenda_vinvulo(Sabana $sabana, $estado_original_sabana, Barcode $barcode)
	{
		# Falta verificar que el la Agenda_vinvulo no esté Pagada!
		$this->developer_log("Actualizando Agenda_vinvulo. ");
                    $recordset=Agenda_vinvulo::select_una_agenda($barcode->get_id_barcode(),$estado_original_sabana);
		if(!$recordset OR $recordset->RowCount()!=1){
			if($recordset AND $recordset->RowCount()==0){
                                $this->developer_log("No hay ningún débito asociado al código de barras '".$barcode->get_barcode()."' con los datos id_barcode".$barcode->get_id_barcode()." Monto $".$sabana->get_monto(). " fecha_vto :".$sabana->get_fecha_vto());
//				$this->developer_log("No hay barcodes disponibles");
//				$recordset=Agenda_vinvulo::select(array("id_barcode"=>$barcode->get_id_barcode()));
//				if(!$recordset and $recordset->rowCount()==0){
//					return false;
//				}
			}
			else {
				$this->developer_log("Hay mas de un débito para el código de barras '".$barcode->get_barcode()."'. ");
				if(!self::PERMITIR_ACTUALIZACION_DE_MULTIPLES_AGENDA_VINVULO){
					return false;
				}
				else{
					$this->developer_log("ALERTA: Actualizando un solo registro de agenda_vinvulo. ");
				}
			}
			
		}
		$agenda_vinvulo=new Agenda_vinvulo($recordset->FetchRow());
		$agenda_xml=$this->obtener_motivo_rechazo($sabana, $estado_original_sabana);
		if($agenda_xml){
			$agenda_vinvulo->set_agenda_xml($agenda_xml);
		}
		if(!($id_authstat=$this->obtener_estado_a_actualizar_agenda_vinvulo($estado_original_sabana))){
			$this->developer_log('Ha ocurrido un error al obtener el estado a actualizar en la agenda_vinvulo. ');
			return false;
		}

		$agenda_vinvulo->set_id_authstat($id_authstat);

		if($agenda_vinvulo->set()){
                    if(self::ACTIVAR_DEBUG)
                        developer_log("Actualizando Agendas Adicionales.");
                        $rs_traer= Agenda_vinvulo::select_debitos_vencimientos_pendientes($sabana->get_id_barcode(),$agenda_vinvulo->get_id_agenda_vinvulo());
                        $error=false;
                        foreach ($rs_traer as $agenda){
                            $agenda_obj=new Agenda_vinvulo($agenda);
                            $id_authstat_vencimiento=$this->obtener_estado_a_actualizar_agenda_adicional($estado_original_sabana,$agenda_obj);
                            $motivo_vencimiento=$this->obtener_motivo_agenda_adicional($estado_original_sabana,$agenda_obj);
                            $agenda_obj->set_id_authstat($id_authstat_vencimiento);
                            $agenda_obj->set_agenda_xml($motivo_vencimiento);
                            if(!$agenda_obj->set()){
                                $error=true;
                                if (self::ACTIVAR_DEBUG)
                                    developer_log("No se pudo actualizar la agenda adicional MOTIVO: ".$agenda_obj->get_agenda_xml());
                            }
                        }
                        if(!$error){
                            if (self::ACTIVAR_DEBUG)
                                developer_log("Actualizado Correctamente.");
                            return $agenda_vinvulo;
                        }
		}
		return false;
	}
	protected function actualizar_sabana(Sabana $sabana, $estado_original_sabana)
	{
		$this->developer_log("Actualizando Sabana. ");
		if(!($id_authstat=$this->obtener_estado_a_actualizar_sabana($estado_original_sabana))){
			$this->developer_log('Ha ocurrido un error al obtener el estado a actualizar en la sabana. ');
			return false;
		}
		$sabana->set_id_authstat($id_authstat);
		if($sabana->set()){
			return true;
		}
		$this->developer_log("Ha ocurrido un error al actualizar el Barcode. ");
		return false;
	}
	protected function consolidar(Sabana $sabana, Barcode $barcode)
	{
		$resultado=false;
		$mensaje_excepcion=false;

		$this->developer_log("Consolidando Galicia. ");
		
		$id_marchand=$barcode->get_id_marchand();
		$id_mp=$sabana->get_id_mp();
		$monto_pagador=$sabana->get_monto();
		$fecha=$sabana->get_fecha_pago();
		$fecha_datetime=Datetime::createFromFormat(Sabana::FORMATO_FECHA_FECHA_PAGO,$fecha);
		if(!$fecha_datetime){
			$this->developer_log('La fecha no es correcta.');
			return false;
		}
		$transaccion=new Transaccion();
		$estado_original=$this->obtener_estado_original_sabana($sabana);
		if(in_array($estado_original,array(Authstat::SABANA_COBRADA,Authstat::SABANA_DAUT_RECHAZADO_Y_KOSTEADO))!==false){
			# Transaccion comun
			$id_referencia=$barcode->get_id();
			if($estado_original!=Authstat::SABANA_COBRADA){
				$monto_pagador=0;
			}
			try {
				$resultado=$transaccion->crear($id_marchand, $id_mp,$monto_pagador, $fecha_datetime, $id_referencia, $sabana, $barcode);
			} catch (Exception $e) {
				$resultado=false;
				$mensaje_excepcion=$e->getMessage();
			}
		}
		elseif($estado_original==Authstat::SABANA_DEBITO_REVERTIDO){
			# Transaccion reverso
                        
			if(($moves_original=$transaccion->encontrar_transaccion_a_reversar($barcode,$id_mp))!==false){
				try {
					$resultado=$transaccion->reversar($moves_original);
					
				} catch (Exception $e) {
					$resultado=false;
					$mensaje_excepcion=$e->getMessage();
				}
			}
			else{
				$this->developer_log('No se encontro la transacción a reversar. ');
			}
		}
		else{
			$this->developer_log('El estado de la sabana es desconocido: '.$estado_original);
		}
		if(self::AGREGAR_LOGS_ANIDADOS){
			if(count($transaccion->log)){
				foreach ($transaccion->log as $mensaje) {
					$this->log[]=$mensaje;
				}
			}
			if($mensaje_excepcion){
				$this->log[]=$mensaje_excepcion;
			}
		}
		return $resultado;
	}
	public function obtener_estado_a_actualizar_barcode($estado_original_sabana)
	{
        $estado = false;
        switch ($estado_original_sabana) {
            case Authstat::SABANA_ENTRANDO: $estado = Authstat::BARCODE_PAGADO;
                break;
         	case Authstat::SABANA_DEBITO_AUTOMATICO_RECHAZADO: $estado = Authstat::BARCODE_PENDIENTE;
                break;
            case Authstat::SABANA_DEBITO_A_REVERTIR: $estado = Authstat::BARCODE_PENDIENTE;
                break;
        }
        return $estado;
    }
	public function obtener_estado_a_actualizar_sabana($estado_original_sabana)
	{
        $estado = false;
        switch ($estado_original_sabana) {
            case Authstat::SABANA_ENTRANDO: $estado = Authstat::SABANA_COBRADA;
                break;
         	case Authstat::SABANA_DEBITO_AUTOMATICO_RECHAZADO: $estado = Authstat::SABANA_DAUT_RECHAZADO_Y_KOSTEADO;
                break;
            case Authstat::SABANA_DEBITO_A_REVERTIR: $estado = Authstat::SABANA_DEBITO_REVERTIDO;
                break;
        }
        return $estado;
    }
	public function obtener_estado_a_actualizar_agenda_vinvulo($estado_original_sabana)
	{
        $estado = false;
        switch ($estado_original_sabana) {
            case Authstat::SABANA_ENTRANDO: $estado = Authstat::DEBITO_DEBITADO;
                break;
         	case Authstat::SABANA_DEBITO_AUTOMATICO_RECHAZADO: $estado = Authstat::DEBITO_OBSERVADO;
                break;
            case Authstat::SABANA_DEBITO_A_REVERTIR: $estado = Authstat::DEBITO_REVERTIDO;
                break; 
        }
        return $estado;
    }
	
    public function obtener_estado_a_actualizar_agenda_adicional($estado_original_sabana, Agenda_vinvulo $agenda)
	{
        $estado = false;
        if (self::ACTIVAR_DEBUG)
            developer_log("Obteniendo estado para sabana adicional");
        switch ($estado_original_sabana) {
            case Authstat::SABANA_ENTRANDO: $estado = Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO;
                break;
            case Authstat::SABANA_DEBITO_AUTOMATICO_RECHAZADO: 
                if($agenda->get_id_authstat()== Authstat::DEBITO_ADICIONAL_VENCIMIENTO_2){
                    $estado = Authstat::DEBITO_ENVIADO;
                    break;
                    
                }
                if($agenda->get_id_authstat()== Authstat::DEBITO_ADICIONAL_VENCIMIENTO_3){
                    $estado = Authstat::DEBITO_ENVIADO;
                    break;
                }
            break;
            case Authstat::SABANA_DEBITO_A_REVERTIR: $estado = Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO;
                break; 
        }
        return $estado;
    }
        public function obtener_motivo_agenda_adicional($estado_original_sabana, Agenda_vinvulo $agenda_vinvulo){
             $motivo= "";
             if (self::ACTIVAR_DEBUG)
                 developer_log("Obteniendo motivo para sabana adicional");
        switch ($estado_original_sabana) {
            case Authstat::SABANA_ENTRANDO: $motivo = $agenda_vinvulo->get_agenda_xml()." Cobrado en vencimientos anteriores.";
                break;
         	case Authstat::SABANA_DEBITO_AUTOMATICO_RECHAZADO: $motivo = $agenda_vinvulo->get_agenda_xml();
                break;
            case Authstat::SABANA_DEBITO_A_REVERTIR: $motivo = $agenda_vinvulo->get_agenda_xml()." Revertido en vencimientos anteriores.";
                break; 
        }
        return $motivo;
        }
        protected function obtener_motivo_rechazo(Sabana $sabana, $estado_original_sabana)
        {
    	$descerror=false;
    	if($estado_original_sabana==Authstat::SABANA_DEBITO_AUTOMATICO_RECHAZADO){
    		$xml=new DOMDocument('1.0','utf-8');
    		if($xml->loadXml($sabana->get_xml_extra())){
    			$elemento=$xml->getElementsByTagName(self::ETIQUETA_DESCRIPCION_RECHAZO);
    			if($elemento AND $elemento->length==1){
    				return $elemento->item(0)->nodeValue;
    			}
    		}
    		else{
    			$this->developer_log('Ha ocurrido un error al leer xml_extra. ');
    		}


    	}
    	return $descerror;
    }
        private function obtener_estado_original_sabana(Sabana $sabana)
        {
    	if($sabana->get_id_authstat()==Authstat::SABANA_ORIGENUFO){
    		$xml=new DOMDocument('1.0','utf-8');
    		if($xml->loadXml($sabana->get_xml_extra())){
    			$elemento=$xml->getElementsByTagName(self::ETIQUETA_ESTADO_SABANA);
    			if($elemento AND $elemento->length==1){
    				return $elemento->item(0)->nodeValue;
    			}
    		}
    		else{
    			$this->developer_log('Ha ocurrido un error al leer xml_extra. ');
    		}


    	}
    	else{

			return $sabana->get_id_authstat();
    	}
    	return false;
    }
}
