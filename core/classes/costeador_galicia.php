<?php

class Costeador_galicia extends Costeador {

    const PERMITIR_ACTUALIZACION_DE_MULTIPLES_AGENDA_VINVULO = true;
    const ETIQUETA_ESTADO_SABANA = 'stat'; # Utilizada por Ufos en preprocesador_galicia
    const ETIQUETA_DESCRIPCION_RECHAZO = 'coderror';

    protected function obtener_recordset() {
        $variables = array();
        $variables[] = Mp::GALICIA;
        $variables[] = Authstat::SABANA_ENTRANDO;

//                $variables[]=Authstat::SABANA_ORIGENUFO;

        return Sabana::registros_a_costear_galicia($variables, $this->limite_de_registros_por_ejecucion);
    }

    private function actualizar_id_moves(Moves $moves, Debito_cbu $debito) {

	if($moves->get_id_mp()==Mp::DEBITO_AUTOMATICO_COSTO_REVERSO){
	   $moves->get($moves->get_id_referencia());
        }
	$this->developer_log("actualizando id_moves en debito_cbu");
        $error = false;
	//var_dump($moves->get_fecha());
        $fecha_pago = DateTime::createFromFormat("Y-m-d H:i:s", $moves->get_fecha());
        if (!$fecha_pago)
            $fecha_pago = DateTime::createFromFormat("Y-m-d", $moves->get_fecha());
        $fecha1 = DateTime::createFromFormat("Y-m-d", $debito->get_fecha_pago1());
        if ($debito->get_fecha_pago2() != null)
            $fecha2 = DateTime::createFromFormat("Y-m-d", $debito->get_fecha_pago2());
        if ($debito->get_fecha_pago3() != null)
            $fecha3 = DateTime::createFromFormat("Y-m-d", $debito->get_fecha_pago3());
        $id_debito = $debito->get_id();
        $debito = new Debito_cbu();
        $debito->set_id_debito($id_debito);
	$this->developer_log("ID_MP: ".$moves->get_id_mp());
	//exit();
        if ($moves->get_id_mp() == Mp::DEBITO_AUTOMATICO_REVERSO) {
	    $this->developer_log("ACTUALIZANDO ID MOVES REVERSO");
            $debito->set_id_moves_reverso($moves->get_id());
        } else if ($fecha_pago->format("Y-m-d") == $fecha1->format("Y-m-d")) {
            $debito->set_id_moves1($moves->get_id());
        } else if (isset($fecha2) and $fecha_pago->format("Y-m-d") == $fecha2->format("Y-m-d")) {
            $debito->set_id_moves2($moves->get_id());
        } else if (isset($fecha3) and $fecha_pago->format("Y-m-d") == $fecha3->format("Y-m-d")) {
            $debito->set_id_moves3($moves->get_id());
        } else {
            $this->developer_log('Ha ocurrido un error al obtener el vencimiento del debito B. ');
            return false;
        }
        if (!$debito->set()) {
            $error = true;
            $this->developer_log("Error al actualizar el debito");
        } else {
            $this->developer_log("Debito_cbu actualizado correctamente");
        }
        if (!$error) {
            if (self::ACTIVAR_DEBUG)
                developer_log("Actualizado Correctamente.");
            return $debito;
        }
    }

    public function costear_sabana(Sabana $sabana) {
        $this->developer_log("COSTEADOR_GALICIA");
//        var_dump(substr($sabana->get_barcode(),0,4));
        if (substr($sabana->get_barcode(), 0, 4) == '7385') {
            return parent::costear_sabana($sabana);
        }
        if ($sabana->get_id_authstat() == Authstat::SABANA_ORIGENUFO) {
            $this->developer_log('Costeando Ufo: Obteniendo registro desde codigo de barras');
            $debito = new Debito_cbu();
            $id_debito = str_replace(" ", "", $sabana->get_barcode());
            $debito->get($id_debito);
//            var_dump($debito);
            if ($debito->get_id() == null)
                return false;
            unset($recordset);
            unset($row);
        } else {
            $debito = new Debito_cbu();
            $id_debito = str_replace(" ", "", $sabana->get_barcode());
            $debito->get($id_debito);
//            var_dump($debito);
            if ($debito->get_id() == null) {
                developer_log($sabana->get_barcode());
                $rs= Debito_cbu::select_ufos($sabana->get_barcode(),$sabana->get_monto(),$sabana->get_fecha_pago());
                if($rs->rowCount()>=1){
                    developer_log("Debito no encontrado por id buscando por id_clima fecha y monto");
                    $row=$rs->fetchRow();
                    $debito = new Debito_cbu($row);
                }
                else{
                    $this->developer_log("Error, no se pudo encontrar el debito a costear");
                    return false;
                }
            }
        }
        Model::setTransacctionMode("READ_UNCOMMITED");
        Model::StartTrans();
        $this->developer_log("INICIA TRANSACCION DE COSTEO");
        if ($this->actualizar_estados_debito($sabana, $debito)) {
            if ($this->consolidar_debito($sabana, $debito)) {
                if (Model::CompleteTrans() and ! Model::hasFailedTrans()) {
                    $this->developer_log("TERMINA TRANSACCION DE COSTEO");
                    return true;
                }
            } else {
                $this->developer_log("Ha ocurrido un error al consolidar. ");
            }
        } else {
            $this->developer_log(" Ha ocurrido un error al actualizar los estados. ");
        }
        Model::FailTrans();
        Model::CompleteTrans();
        return false;
    }

    protected function actualizar_estados(Sabana $sabana, Barcode $barcode) {
        # El estado original de la sabana es la interfaz con el estado de los ufos y los comunes
        $estado_original_sabana = $this->obtener_estado_original_sabana($sabana);
        developer_log($estado_original_sabana);
        if ($this->actualizar_barcode($sabana, $barcode, $estado_original_sabana)) {
            if ($this->obtener_y_actualizar_agenda_vinvulo($sabana, $estado_original_sabana, $barcode)) {
                if ($this->actualizar_sabana($sabana, $estado_original_sabana)) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function obtener_y_actualizar_agenda_vinvulo(Sabana $sabana, $estado_original_sabana, Barcode $barcode) {
	$debito_cbu = $this->obtener_debito_cbu_barcode($barcode);
         if ($debito_cbu)
           if(!$this->actualizar_debito_cbu($sabana, $estado_original_sabana, $debito_cbu)){
		$this->developer_log("No se pudo actualizar el debito_cbu por Barcode.");
		return false;
	   }
	$this->developer_log("Debito_cbu a travez de Barcode actualizado correctamente. ");
	return true;

 	//deprecado
	# Falta verificar que el la Agenda_vinvulo no esté Pagada!
        $this->developer_log("Actualizando Agenda_vinvulo. ");
        $recordset = Agenda_vinvulo::select_una_agenda($barcode->get_id_barcode(), $estado_original_sabana);
        if (!$recordset OR $recordset->RowCount() != 1) {
            if ($recordset AND $recordset->RowCount() == 0) {
                $this->developer_log("No hay ningún débito asociado al código de barras '" . $barcode->get_barcode() . "' con los datos id_barcode" . $barcode->get_id_barcode() . " Monto $" . $sabana->get_monto() . " fecha_vto :" . $sabana->get_fecha_vto());
//				$this->developer_log("No hay barcodes disponibles");
//				$recordset=Agenda_vinvulo::select(array("id_barcode"=>$barcode->get_id_barcode()));
//				if(!$recordset and $recordset->rowCount()==0){
//					return false;
//				}
            } else {
                $this->developer_log("Hay mas de un débito para el código de barras '" . $barcode->get_barcode() . "'. ");
                if (!self::PERMITIR_ACTUALIZACION_DE_MULTIPLES_AGENDA_VINVULO) {
                    return false;
                } else {
                    $this->developer_log("ALERTA: Actualizando un solo registro de agenda_vinvulo. ");
                }
            }
        }
        $agenda_vinvulo = new Agenda_vinvulo($recordset->FetchRow());
        $agenda_xml = $this->obtener_motivo_rechazo($sabana, $estado_original_sabana);
        if ($agenda_xml) {
            $agenda_vinvulo->set_agenda_xml($agenda_xml);
        }
        if (!($id_authstat = $this->obtener_estado_a_actualizar_agenda_vinvulo($estado_original_sabana))) {
            $this->developer_log('Ha ocurrido un error al obtener el estado a actualizar en la agenda_vinvulo. ');
            return false;
        }

        $agenda_vinvulo->set_id_authstat($id_authstat);

        if ($agenda_vinvulo->set()) {
            if (self::ACTIVAR_DEBUG)
                developer_log("Actualizando Agendas Adicionales.");
            $rs_traer = Agenda_vinvulo::select_debitos_vencimientos_pendientes($sabana->get_id_barcode(), $agenda_vinvulo->get_id_agenda_vinvulo());
            $error = false;
            foreach ($rs_traer as $agenda) {
                $agenda_obj = new Agenda_vinvulo($agenda);
                $id_authstat_vencimiento = $this->obtener_estado_a_actualizar_agenda_adicional($estado_original_sabana, $agenda_obj);
                $motivo_vencimiento = $this->obtener_motivo_agenda_adicional($estado_original_sabana, $agenda_obj);
                $agenda_obj->set_id_authstat($id_authstat_vencimiento);
                $agenda_obj->set_agenda_xml($motivo_vencimiento);
                if (!$agenda_obj->set()) {
                    $error = true;
                    if (self::ACTIVAR_DEBUG)
                        developer_log("No se pudo actualizar la agenda adicional MOTIVO: " . $agenda_obj->get_agenda_xml());
                }
            }
            if (!$error) {
                if (self::ACTIVAR_DEBUG)
                    developer_log("Actualizado Correctamente.");
                $debito_cbu = $this->obtener_debito_cbu_barcode($barcode);
                if ($debito_cbu)
                    $this->actualizar_debito_cbu($sabana, $estado_original_sabana, $debito_cbu);
                return $agenda_vinvulo;
            }
        }

        return false;
    }

    public function obtener_debito_cbu_barcode(Barcode $barcode) {
        $rs = Debito_cbu::select(array("id_barcode" => $barcode->get_id_barcode()));
        if ($rs->rowCount() > 0) {
            return new Debito_cbu($rs->fetchRow());
        }
        return false;
    }

    protected function actualizar_barcode(Sabana $sabana, Barcode $barcode, $estado_original_sabana = null) {
        $this->developer_log("Actualizando Barcode. ");

        if (!($id_authstat = $this->obtener_estado_a_actualizar_barcode($estado_original_sabana))) {
            $this->developer_log('Ha ocurrido un error al obtener el estado a actualizar en el barcode. ');
            return false;
        }
        $barcode->set_id_authstat($id_authstat);
        if ($barcode->set()) {
            if (self::ACTIVAR_DEBUG)
                developer_log("Barcode actualizado correctamente.");
            return true;
        }
        $this->developer_log("Ha ocurrido un error al actualizar el Barcode. ");
        return false;
    }

    protected function actualizar_estados_debito(Sabana $sabana, Debito_cbu $debito) {


        # El estado original de la sabana es la interfaz con el estado de los ufos y los comunes
        $estado_original_sabana = $this->obtener_estado_original_sabana($sabana);
        developer_log($estado_original_sabana);
        if ($this->actualizar_debito_cbu($sabana, $estado_original_sabana, $debito)) {
            if ($this->actualizar_sabana($sabana, $estado_original_sabana)) {
                return true;
            }
        }

        return false;
    }

//    protected function actualizar_barcode($estado_original_sabana, Barcode $barcode) {
//        $this->developer_log("Actualizando Barcode. ");
//        if (!($id_authstat = $this->obtener_estado_a_actualizar_barcode($estado_original_sabana))) {
//            $this->developer_log('Ha ocurrido un error al obtener el estado a actualizar en el barcode. ');
//            return false;
//        }
//        $barcode->set_id_authstat($id_authstat);
//        if ($barcode->set()) {
//            if (self::ACTIVAR_DEBUG)
//                developer_log("Barcode actualizado correctamente.");
//            return true;
//        }
//        $this->developer_log("Ha ocurrido un error al actualizar el Barcode. ");
//        return false;
//    }

    protected function actualizar_debito_cbu(Sabana $sabana, $estado_original_sabana, Debito_cbu $debito) {

        # Falta verificar que el la Agenda_vinvulo no esté Pagada!
        $error = false;
        $this->developer_log("Actualizando Debito. ");
        $motivo_rechazo = $this->obtener_motivo_rechazo($sabana, $estado_original_sabana);
        $this->developer_log("ESTADO ORIGINAL SABANA:" . $estado_original_sabana);
        if (!($id_authstat = $this->obtener_estado_a_actualizar_agenda_vinvulo($estado_original_sabana))) {
            $this->developer_log('Ha ocurrido un error al obtener el estado a actualizar en el debito. ');
            return false;
        }

        $fecha_pago = DateTime::createFromFormat("Y-m-d H:i:s", $sabana->get_fecha_pago());
        $fecha1 = DateTime::createFromFormat("Y-m-d", $debito->get_fecha_pago1());
        $this->developer_log("El debito a actualizar es " . $debito->get_id());
        if ($debito->get_fecha_pago2() != null)
            $fecha2 = DateTime::createFromFormat("Y-m-d", $debito->get_fecha_pago2());
        if ($debito->get_fecha_pago3() != null)
            $fecha3 = DateTime::createFromFormat("Y-m-d", $debito->get_fecha_pago3());
        $id_debito = $debito->get_id();
        $deb = $debito;
        $debito = new Debito_cbu();
        $debito->set_id_debito($id_debito);
        $fecha_pago->format("Y-m-d");
        $fecha1->format("Y-m-d");
        developer_log($fecha_pago->format("Y-m-d"));
        developer_log($fecha1->format("Y-m-d"));
        if ($fecha_pago->format("Y-m-d") == $fecha1->format("Y-m-d")) {
            if ($motivo_rechazo) {
                $debito->set_motivorechazo1($motivo_rechazo);
            }
            $debito->set_id_authf1($id_authstat);
            if ($id_authstat == Authstat::DEBITO_DEBITADO) {
                $debito->set_id_authf2(Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO);
                $debito->set_id_authf3(Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO);
            }
        } else
        if (isset($fecha2) and $fecha_pago->format("Y-m-d") == $fecha2->format("Y-m-d")) {
            if ($motivo_rechazo) {
                $debito->set_motivorechazo2($motivo_rechazo);
            }
            $debito->set_id_authf2($id_authstat);
            if ($id_authstat == Authstat::DEBITO_DEBITADO)
                $debito->set_id_authf3(Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO);
        } else if (isset($fecha3) and $fecha_pago->format("Y-m-d") == $fecha3->format("Y-m-d")) {
            if ($motivo_rechazo) {
                $debito->set_motivorechazo3($motivo_rechazo);
            }
            $debito->set_id_authf3($id_authstat);
        } else if (!$this->actualizar_debitos_fallados($deb, $id_authstat, $estado_original_sabana, $motivo_rechazo)) {
            $this->developer_log('No se pudo actualizar el debito_cbu por estados. ');
            return false;
        } else {

            $this->developer_log('Ha ocurrido un error al obtener el vencimiento del debito A. ');
            return false;
        }
        if (!$debito->set()) {
            $error = true;
        } else {
            $this->developer_log("debito actualizado correctamente");
        }
        if (!$error) {
            if (self::ACTIVAR_DEBUG)
                developer_log("Actualizado Correctamente.");
            return $debito;
        }

        return false;
    }

    private function actualizar_debitos_fallados(Debito_cbu $deb, $id_authstat, $estado_original, $motivo_rechazo) {
        $debito = new Debito_cbu();
        $debito->set_id($deb->get_id());
        $this->developer_log("ESTADO" . $estado_original);
        if ($debito->get_fecha_pago1() == $debito->get_fecha_pago2() and $debito->get_fecha_pago1() == $debito->get_fecha_pago3()) {
            switch ($estado_original) {
                case Authstat::SABANA_ENTRANDO:
                case Authstat::SABANA_DEBITO_AUTOMATICO_RECHAZADO:
                    if ($debito->get_id_authf1() == Authstat::DEBITO_ENVIADO) {
                        $debito->set_id_authf1($id_authstat);
                        if ($estado_original == Authstat::SABANA_DEBITO_AUTOMATICO_RECHAZADO) {
                            $debito->set_motivorechazo1($motivo_rechazo);
                        }
                    } elseif ($debito->get_id_authf2() == Authstat::DEBITO_ENVIADO OR $debito->get_id_authf2() == Authstat::DEBITO_ADICIONAL_VENCIMIENTO_2) {
                        $debito->set_id_authf2($id_authstat);
                        if ($estado_original == Authstat::SABANA_DEBITO_AUTOMATICO_RECHAZADO) {
                            $debito->set_motivorechazo2($motivo_rechazo);
                        }
                    } elseif ($debito->get_id_authf3() == Authstat::DEBITO_ENVIADO OR $debito->get_id_authf3() == Authstat::DEBITO_ADICIONAL_VENCIMIENTO_3) {
                        $debito->set_id_authf3($id_authstat);
                        if ($estado_original == Authstat::SABANA_DEBITO_AUTOMATICO_RECHAZADO) {
                            $debito->set_motivorechazo3($motivo_rechazo);
                        }
                    }
                    break;
                case Authstat::SABANA_DEBITO_A_REVERTIR:
                    $debito->set_id_authrev($id_authstat);
                    break;
            }
            if ($debito->set())
                return true;
        }
        return false;
    }

    public function actualizar_sabana(Sabana $sabana, $estado_original_sabana = null) {
        $this->developer_log("Actualizando Sabana Galicia. ");
        $id_Sabana = $sabana->get_id();
        $sabana = new Sabana();
        $sabana->set_id($id_Sabana);
        if (!($id_authstat = $this->obtener_estado_a_actualizar_sabana($estado_original_sabana))) {
            $this->developer_log('Ha ocurrido un error al obtener el estado a actualizar en la sabana. ');
            return false;
        }
        $sabana->set_id_authstat($id_authstat);
        if ($sabana->set()) {
            return true;
        }
        $this->developer_log("Ha ocurrido un error al actualizar la sabana. ");
        return false;
    }

    protected function consolidar(Sabana $sabana, Barcode $barcode) {
        $resultado = false;
        $mensaje_excepcion = false;

        $this->developer_log("Consolidando Galicia barcode. ");

        $id_marchand = $barcode->get_id_marchand();
        $id_mp = $sabana->get_id_mp();
        $monto_pagador = $sabana->get_monto();
        $fecha = $sabana->get_fecha_pago();
        $fecha_datetime = Datetime::createFromFormat(Sabana::FORMATO_FECHA_FECHA_PAGO, $fecha);
        if (!$fecha_datetime) {
            $this->developer_log('La fecha no es correcta.');
            return false;
        }
        $transaccion = new Transaccion();
        $rs1 = Debito_cbu::select(array("id_barcode" => $barcode->get_id()));
        if ($rs1->rowCount() > 0) {
            $debito_cbu = new Debito_cbu($rs1->fetchRow());
        }
        $estado_original = $this->obtener_estado_original_sabana($sabana);
        if (in_array($estado_original, array(Authstat::SABANA_ENTRANDO, Authstat::SABANA_DEBITO_AUTOMATICO_RECHAZADO)) !== false) {
            # Transaccion comun
            if ($debito_cbu != null)
                $id_referencia = $debito_cbu->get_id();
            else {
                $this->developer_log("No se pudo encontrar el debito en cd_debito_cbu con el id_barcode=" . $barcode->get_id());
                return false;
            }
            if ($estado_original != Authstat::SABANA_ENTRANDO) {
                $monto_pagador = 0;
            }
            try {
                $resultado = $transaccion->crear($id_marchand, $id_mp, $monto_pagador, $fecha_datetime, $id_referencia, $sabana, $barcode);
                if ($resultado) {
                    $moves = $transaccion->moves;
                    if(!$debito = $this->obtener_debito_cbu_barcode($barcode)){
			$this->developer_log("Error al actualizar estado debito_cbu por barcode");
			return false;
		    }
                    if ($debito)
                        if(!$this->actualizar_id_moves($moves, $debito)){
             		   $this->developer_log("Error al actualizar id_moves en debito_cbu por barcode");
			   return false;
		        }
                }
            } catch (Exception $e) {
                $resultado = false;
                $mensaje_excepcion = $e->getMessage();
            }
        } elseif ($estado_original == Authstat::SABANA_DEBITO_A_REVERTIR) {
            # Transaccion reverso
            
            
            $moves_original = $transaccion->encontrar_transaccion_a_reversar($barcode, $id_mp);
            if(!$moves_original){
                $debito = $this->obtener_debito_cbu_barcode($barcode);
                $moves_original = $transaccion->encontrar_transaccion_a_reversar_debito ($debito, $id_mp);
            }
            if ($moves_original) {
                //           if (($moves_original = $transaccion->encontrar_transaccion_a_reversar($barcode, $id_mp)) !== false) {
                try {
                    $resultado = $transaccion->reversar($moves_original);
                    if ($resultado) {
                        $moves = $transaccion->moves;
                             $debito = $this->obtener_debito_cbu_barcode($barcode);
                        if ($debito)
                            if(!$this->actualizar_id_moves($moves, $debito)){
				$this->developer_log("Error al actualizar id_moves");
				return false;
                            }
			if ($debito)
                            if(!$this->actualizar_debito_cbu($sabana, $estado_original, $debito)){
				 $this->developer_log("Error al actualizar estado debito_cbu por barcode");
				return false;
		            }
                    }
                } catch (Exception $e) {
                    $resultado = false;
                    $mensaje_excepcion = $e->getMessage();
                }
            } else {
                $this->developer_log('No se encontro la transacción a reversar. ');
            }
        } else {
            $this->developer_log('El estado de la sabana es desconocido: ' . $estado_original);
        }
        if (self::AGREGAR_LOGS_ANIDADOS) {
            if (count($transaccion->log)) {
                foreach ($transaccion->log as $mensaje) {
                    $this->log[] = $mensaje;
                }
            }
            if ($mensaje_excepcion) {
                $this->log[] = $mensaje_excepcion;
            }
        }
        return $resultado;
    }

    protected function consolidar_debito(Sabana $sabana, Debito_cbu $debito) {
        $resultado = false;
        $mensaje_excepcion = false;

        $this->developer_log("Consolidando Galicia. ");

        $id_marchand = $debito->get_id_marchand();
        $id_mp = $sabana->get_id_mp();
        $monto_pagador = $sabana->get_monto();
        $fecha = $sabana->get_fecha_pago();
        $fecha_datetime = Datetime::createFromFormat(Sabana::FORMATO_FECHA_FECHA_PAGO, $fecha);
        if (!$fecha_datetime) {
            $this->developer_log('La fecha no es correcta.');
            return false;
        }
        $transaccion = new Transaccion();
	Transaccion::$grado_de_recursividad=0;
        $estado_original = $this->obtener_estado_original_sabana($sabana);
//        var_dump($estado_original);
        if (in_array($estado_original, array(Authstat::SABANA_ENTRANDO, Authstat::SABANA_DEBITO_AUTOMATICO_RECHAZADO)) !== false) {
            # Transaccion comun
            $id_referencia = $debito->get_id();
            if ($estado_original != Authstat::SABANA_ENTRANDO) {
                $monto_pagador = 0;
            }
            try {
                $resultado = $transaccion->crear($id_marchand, $id_mp, $monto_pagador, $fecha_datetime, $id_referencia, $sabana);
                if ($resultado) {
                    $moves = $transaccion->moves;
                    $this->actualizar_id_moves($moves, $debito);
                }
            } catch (Exception $e) {
                $resultado = false;
                $mensaje_excepcion = $e->getMessage();
                Model::CompleteTrans();
            }
        } elseif ($estado_original == Authstat::SABANA_DEBITO_A_REVERTIR) {
            # Transaccion reverso

            if (($moves_original = $transaccion->encontrar_transaccion_a_reversar_debito($debito, $id_mp)) !== false) {
                try {
                    $resultado = $transaccion->reversar($moves_original);
                    if ($resultado) {
                        $moves = $transaccion->moves;
                        $this->actualizar_id_moves($moves, $debito);
                    }
                } catch (Exception $e) {
                    $resultado = false;
                    $mensaje_excepcion = $e->getMessage();
                }
            } else {
                $this->developer_log('No se encontro la transacción a reversar. ');
            }
        } else {
            $this->developer_log('El estado de la sabana es desconocido: ' . $estado_original);
        }
        if (self::AGREGAR_LOGS_ANIDADOS) {
            if (count($transaccion->log)) {
                foreach ($transaccion->log as $mensaje) {
                    $this->log[] = $mensaje;
                }
            }
            if ($mensaje_excepcion) {
                $this->log[] = $mensaje_excepcion;
            }
        }
        return $resultado;
    }

    public function obtener_estado_a_actualizar_barcode($estado_original_sabana) {
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

    public function obtener_estado_a_actualizar_sabana($estado_original_sabana) {
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

    public function obtener_estado_a_actualizar_agenda_vinvulo($estado_original_sabana) {
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

    public function obtener_estado_a_actualizar_agenda_adicional($estado_original_sabana, Agenda_vinvulo $agenda) {
        $estado = false;
        if (self::ACTIVAR_DEBUG)
            developer_log("Obteniendo estado para sabana adicional");
        switch ($estado_original_sabana) {
            case Authstat::SABANA_ENTRANDO: $estado = Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO;
                break;
            case Authstat::SABANA_DEBITO_AUTOMATICO_RECHAZADO:
                if ($agenda->get_id_authstat() == Authstat::DEBITO_ADICIONAL_VENCIMIENTO_2) {
                    $estado = Authstat::DEBITO_ENVIADO;
                    break;
                }
                if ($agenda->get_id_authstat() == Authstat::DEBITO_ADICIONAL_VENCIMIENTO_3) {
                    $estado = Authstat::DEBITO_ENVIADO;
                    break;
                }
                break;
            case Authstat::SABANA_DEBITO_A_REVERTIR: $estado = Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO;
                break;
        }
        return $estado;
    }

    public function obtener_motivo_agenda_adicional($estado_original_sabana, Agenda_vinvulo $agenda_vinvulo) {
        $motivo = "";
        if (self::ACTIVAR_DEBUG)
            developer_log("Obteniendo motivo para sabana adicional");
        switch ($estado_original_sabana) {
            case Authstat::SABANA_ENTRANDO: $motivo = $agenda_vinvulo->get_agenda_xml() . " Cobrado en vencimientos anteriores.";
                break;
            case Authstat::SABANA_DEBITO_AUTOMATICO_RECHAZADO: $motivo = $agenda_vinvulo->get_agenda_xml();
                break;
            case Authstat::SABANA_DEBITO_A_REVERTIR: $motivo = $agenda_vinvulo->get_agenda_xml() . " Revertido en vencimientos anteriores.";
                break;
        }
        return $motivo;
    }

    protected function obtener_motivo_rechazo(Sabana $sabana, $estado_original_sabana) {
        $descerror = false;
        if ($estado_original_sabana == Authstat::SABANA_DEBITO_AUTOMATICO_RECHAZADO) {
            $xml = new DOMDocument('1.0', 'utf-8');
            if ($xml->loadXml($sabana->get_xml_extra())) {
                $elemento = $xml->getElementsByTagName(self::ETIQUETA_DESCRIPCION_RECHAZO);
                if ($elemento AND $elemento->length >= 1) {
                    return $elemento->item(0)->nodeValue;
                }
            } else {
                $this->developer_log('Ha ocurrido un error al leer xml_extra. ');
            }
        }
        return $descerror;
    }

    private function obtener_estado_original_sabana(Sabana $sabana) {
        if ($sabana->get_id_authstat() == Authstat::SABANA_ORIGENUFO) {
            $xml = new DOMDocument('1.0', 'utf-8');
            if ($xml->loadXml($sabana->get_xml_extra())) {
                $elemento = $xml->getElementsByTagName(self::ETIQUETA_ESTADO_SABANA);
                if ($elemento AND $elemento->length == 1) {
                    return $elemento->item(0)->nodeValue;
                }
            } else {
                $this->developer_log('Ha ocurrido un error al leer xml_extra. ');
            }
        } else {

            return $sabana->get_id_authstat();
        }
        return false;
    }

}
