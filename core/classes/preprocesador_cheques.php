<?php

class Preprocesador_cheques extends Preprocesador {

    const PATRON = "/^PP_Cons_([0-3][0-9])_([0-1][0-9])_([0-9]{4}).txt/";
    //const PATRON_REVERSOS = "/^REND.REV-REVC8496.REV-([0-9]{8}).TXT/";
    //const PATRON_FECHA = "Ymd";


    const DIRECTORIO_FTP = "/home/samba/shares/mp/";
    public function __construct() {
        $codigo_entidad = self::CODIGO_ENTIDAD_CHEQUE_SUCURSAL;
        $codigo_archivo = false;
//        $tipo = 1;
//        switch ($tipo) {
//            # Esto no va a nada -_-
//            case '1': $codigo_archivo = self::CODIGO_ENTIDAD_GALICIA_ARCHIVO_RENDICION;
//                break;
//            default: $codigo_archivo = self::CODIGO_ENTIDAD_GALICIA_ARCHIVO_RENDICION_DE_REVERSOS;
//                break;
//        }

        parent::__construct($codigo_entidad . $codigo_archivo);
    }
   
    public function procesar_registro(Registro $registro, Control $control) {

        
        $this->insertar_sabana_debito($registro, $control);
        
        return true; # No esta bueno
    }

    public function ejecutar($archivo) {
        $tiempo_inicio = microtime(true);
        if (!is_file($archivo)) {
            $this->developer_log('Archivo incorrecto. ');
            return false;
        }
        if (!($clase_registro = $this->obtener_clase_registro($this->obtener_id_mp()))) {
            $this->developer_log('Clase de registro incorrecta.');
            return false;
        }
        $this->archivo = $archivo;
        unset($archivo);

        
        Model::StartTrans();
        $this->puntero_fichero = new SplFileObject($this->archivo);
        //$encabezado = trim($this->obtener_encabezado($this->puntero_fichero));

        $this->developer_log('*************************INICIO*********************************');
        $this->developer_log('Medio de Pago: ' . $this->obtener_entidad());
        $this->developer_log("Nombre archivo: '" . basename($this->archivo) . "'");
        //$this->developer_log("Encabezado: '" . $encabezado . "'");
        $this->developer_log('Cantidad de lineas: ' . $this->get_cantidad_de_lineas($this->puntero_fichero));
        $this->developer_log('Día:' . date('d/m/Y') . ' Hora:' . date('H:i'));
        $this->developer_log('*****************************************************************');
        if (!Model::hasFailedTrans()) {
            if (!($control = $this->insertar_control())) {
                $this->developer_log('Ha ocurrido un error al insertar el registro de Control.');
                Model::FailTrans();
            }
        }
        $this->nlinea = 0;
        unset($fila);
        while (!Model::hasFailedTrans() AND ( $fila = $this->obtener_registro_siguiente($this->puntero_fichero)) != false) {
            $this->nlinea = $this->puntero_fichero->key();
            unset($registro);
            if (($registro = new $clase_registro($fila))) {
                if (!$this->procesar_registro($registro, $control)) {
//                     var_dump(Model::hasFailedTrans());
                    Model::FailTrans();
                    $this->developer_log($this->nlinea . " | Ha ocurrido un error al procesar el registro. ");
                }
            } else {
                $this->developer_log($this->nlinea . " | El registro no es válido. ");
            }
            unset($fila);
        }
        
        if (!Model::hasFailedTrans()) {
            
            if (!$this->controlar()) {
                Model::FailTrans();
                $this->developer_log('Ha ocurrido un error al controlar el archivo.');
            }
        }
        
        if (self::ACTIVAR_TEST) {
            $this->developer_log('Esto es un test. Fallando transacciones.');
            Model::FailTrans();
        }
        
        if (!Model::hasFailedTrans()) {
            if (!$this->post_ejecucion($this->archivo)) {
                Model::FailTrans();
            }
            if (!$this->archivar()) {
                Model::FailTrans();
            }
        }
        $tiempo_total = microtime(true) - $tiempo_inicio;
        $this->developer_log('Tiempo de preprocesamiento total: ' . $tiempo_total);

        $mensaje_aux = 'Hay un total de ' . $this->cantidad_de_registros_ufo . ' registro/s Ufo. ';
        $this->developer_log($mensaje_aux);
        $read_output = '';
        $read_output .= 'Importe total: $' . formato_plata($this->monto_acumulado) . '. ';
        $read_output .= 'Cantidad de registros: ' . $this->cantidad_de_registros . '. ';
        $read_output .= $mensaje_aux;
        $mensaje_aux_2 = "Todos los registros del archivo '" . basename($this->archivo) . "' han sido completados correctamente. ";
        $read_output .= $mensaje_aux_2;

        if (!Model::hasFailedTrans()) {
            if (!$this->actualizar_control($control, $read_output)) {
                $this->developer_log('Ha ocurrido un error al actualizar el control. ');
                Model::FailTrans();
            }
        }
        if (Model::CompleteTrans() AND ! Model::hasFailedTrans()) {
            $this->developer_log($mensaje_aux_2);
            $this->developer_log('************************FIN CORRECTO*****************************');
            return true;
        }
        $this->developer_log("Ningún registro del archivo '" . basename($this->archivo) . "' ha sido completado correctamente. ");
        $this->developer_log('************************FIN INCORRECTO*****************************');
        if (!$this->enviar_alerta()) {
            developer_log('Ha ocurrido un error al enviar la alerta.');
        } else {
            developer_log('Alerta correctamente enviada.');
        }
        $this->developer_log('*******************************************************************');
        return false;
    }

    public function insertar_sabana_debito(Registro_cheque $registro, Control $control, Debito_cbu $debito = null) {
        error_log("insertar_sabana cheques");
	$fecha_vto = new DateTime('now');
        
        $barrand = $registro->obtener_estado_cheque();
        
        $id_barcode = self::DUMMY_ID_BARCODE;
        $barcode2 = $registro->obtener_id_cheque_por_sucursal();
        $monto = $registro->obtener_monto();
        $fecha_pago = new DateTime ($registro->obtener_fecha_de_pago_cheque());
        
        $id_authstat = $registro->obtener_estado_a_insertar_sabana();
        //$fecha_vto = $registro->obtener_fecha_de_vencimiento();
        $mp = $this->obtener_id_mp($registro);
        $sc = '0';
        
        $sabana = new Sabana();
        $this->developer_log($this->nlinea . " | Insertando sabana. ");

        $sabana->set_id_authstat($id_authstat);
        $sabana->set_id_barcode($id_barcode);
        $sabana->set_id_mp($mp);
        $sabana->set_barcode($barcode2);


        $sabana->set_fecha_vto($fecha_vto->format('Y-m-d'));
        $sabana->set_monto($monto);
        $sabana->set_fechagen('now');
        $sabana->set_sc($sc);
        $sabana->set_barrand($barrand);

        if (!$fecha_pago) {
            $this->developer_log($this->nlinea . " | La fecha de pago no es válida. [1] ");
            return false;
        }
        $sabana->set_fecha_pago($fecha_pago->format('Y-m-d'));
        $sabana->set_id_formapago('1');
        $sabana->set_revno($control->get_revno());
        $sabana->set_nlinea($this->nlinea);

        if ($sabana->set()) {
            return $sabana;
        }
        return false;
    }

    public static function nombre_archivo_interfaz($id_mp, $identificador, Datetime $fecha = null) {
        unset($identificador);
        if ($fecha === null)
            $fecha = new Datetime('now');
        $nombre_archivo = false;
	error_log("adupuy| ".$id_mp);
        if ($id_mp == Mp::GALICIA) {
            $nombre_archivo = preg_replace("/\((.*)\)/", $fecha->format(static::PATRON_FECHA), substr(static::PATRON, 2, -1));
        } elseif ($id_mp == Mp::DEBITO_AUTOMATICO_REVERSO) {
            $nombre_archivo = preg_replace("/\((.*)\)/", $fecha->format(static::PATRON_FECHA), substr(static::PATRON_REVERSOS, 2, -1));
        }
        return $nombre_archivo;
    }

    protected function crear_xml_extra(Registro $registro) {
        $xml = new DOMDocument('1.0', 'utf-8');
        $detalle = $xml->createElement('detalle');
        $xml->appendChild($detalle);
        $stat = $xml->createElement(Costeador_galicia::ETIQUETA_ESTADO_SABANA, $registro->obtener_estado_a_actualizar_sabana());
        $detalle->appendChild($stat);
        $tipo_registro = $xml->createElement('tipo_registro', $registro->obtener_tipo_registro());
        $detalle->appendChild($tipo_registro);
        $xmlpd = $xml->createElement('xmlpd');
        $detalle->appendChild($xmlpd);
        $PAGODIRECTO = $xml->createElement('PAGODIRECTO');
        $xmlpd->appendChild($PAGODIRECTO);
        $carrier = $xml->createElement('carrier', 'galicia2');
        $PAGODIRECTO->appendChild($carrier);
        $tiporegistro = $xml->createElement('tiporegistro', $registro->obtener_tipo_registro());
        $PAGODIRECTO->appendChild($tiporegistro);
        $coderror = $xml->createElement('coderror', $registro->obtener_codigo_de_rechazo());
        $PAGODIRECTO->appendChild($coderror);
        $descerror = $xml->createElement(Costeador_galicia::ETIQUETA_DESCRIPCION_RECHAZO, $registro->obtener_descripcion_de_rechazo());
        $PAGODIRECTO->appendChild($descerror);
        if ($registro->obtener_codigo_de_rechazo()) {
            $errores = $xml->createElement('errores');
            $PAGODIRECTO->appendChild($errores);
            $rechazo = $xml->createElement('rechazo', $registro->obtener_codigo_de_rechazo() . ': ' . $registro->obtener_descripcion_de_rechazo());
            $errores->appendChild($rechazo);
        }
        return $xml->saveXML();
    }

    protected function obtener_barcode_para_ufos(Registro $registro) {
        return "UFO: " . $registro->obtener_codigo_electronico();
    }

    protected function obtener_debito_cbu(Registro $registro) {
        $id_debito_cbu = $registro->obtener_referencia_univoca();
        $id_debito_cbu = preg_replace('/^0+/', '', $id_debito_cbu);
	//var_dump($id_debito_cbu);
	
        $debito_cbu = new Debito_cbu();
        if(strlen($id_debito_cbu)>=10){
            $this->developer_log("prevengo cierre de transaccion");
            return false;
        } //supera el valor integer de la base
        
        $debito_cbu->get($id_debito_cbu);
        if(Model::HasFailedTrans()){
            Model::CompleteTrans();
            Model::StartTrans();
            $this->developer_log("Reinicio la transaccion");
        }
            
        if ($debito_cbu->get_id() == false)
            return false;
	//var_dump($debito_cbu->get_id());
	
        return $debito_cbu;
//            $this->developer_log($this->nlinea." | Obteniendo Barcode '".$codigo_de_barras."'. ");
//            $recordset=Barcode::select(array('barcode'=>$codigo_de_barras));
//            if($recordset AND $recordset->RowCount()==1){
//                    developer_log("Barcode obtenido correctamente.");
//                    $barcode=new Barcode($recordset->FetchRow());
//                    return $barcode;
//            }
//            else{
//                $pmc=$registro->obtener_codigo_electronico();
//                $monto=$registro->obtener_monto_numerico();
//                $fecha=$registro->obtener_fecha_de_pago_datetime();
//                if (self::ACTIVAR_DEBUG)
//                    developer_log("Obteniendo Barcode de sabanas adicionales");
//                $recordset2= Agenda_vinvulo::obtener_de_agendas_adicionales($pmc,$monto,$fecha);
//                if($recordset2 AND $recordset2->RowCount()==1){
//                    if (self::ACTIVAR_DEBUG)
//                        developer_log("Barcode de sabanas adicionales Obtenido correctamente.");
//                    $barcode=new Barcode($recordset2->FetchRow());
//                    return $barcode;
//                }
//            }
//            $this->developer_log($this->nlinea." | No ha sido posible obtener el Barcode. ");
//            return false;
    }

    protected function obtener_barcode(Registro $registro) {
        $codigo_de_barras = $registro->obtener_codigo_de_barras();
        
        $this->developer_log($this->nlinea . " | Obteniendo Barcode '" . $codigo_de_barras . "'. ");
        $recordset = Barcode::select(array('pmc19' => $codigo_de_barras));
        if ($recordset AND $recordset->RowCount() == 1) {
            developer_log("Barcode obtenido correctamente.");
            $barcode = new Barcode($recordset->FetchRow());
            return $barcode;
        } else {
            $pmc = $registro->obtener_codigo_electronico();
            $monto = $registro->obtener_monto_numerico();
            $fecha = $registro->obtener_fecha_de_pago_datetime();
            if (self::ACTIVAR_DEBUG)
                developer_log("Obteniendo Barcode de sabanas adicionales");
            $recordset2 = Agenda_vinvulo::obtener_de_agendas_adicionales($pmc, $monto, $fecha);
            if ($recordset2 AND $recordset2->RowCount() == 1) {
                if (self::ACTIVAR_DEBUG)
                    developer_log("Barcode de sabanas adicionales Obtenido correctamente.");
                $barcode = new Barcode($recordset2->FetchRow());
                return $barcode;
            }
        }
        $this->developer_log($this->nlinea . " | No ha sido posible obtener el Barcode. ");
        return null;
    }

    public function obtener_encabezado($puntero_fichero) {
        var_dump($puntero_fichero);
        return $puntero_fichero;
    }
    
    private function insertar_control() {
        $this->developer_log($this->archivo);
        $recordset = Control::select(array("csvfile" => $this->archivo, "id_mp" => $this->obtener_id_mp()));
        if ($recordset->rowCount() == 0) {
            $this->developer_log("Insertando control. ");
            $control = new Control();
            $control->set_date_run('now');
            $control->set_success('0');
            $control->set_script(get_called_class());
            $control->set_id_mp($this->obtener_id_mp());
            $control->set_seq1(Control::SEQ1_NUEVO_PROCESADOR);
            $control->set_tplfile("cheques");
            $control->set_csvfile($this->nombrar_csvfile($this->archivo)); # AGREGAR INDICE UNIQUE!
            if ($control->set()) {
                return $control;
            }
        }
        return false;
    }
    
    private function enviar_alerta() {
        developer_log("Enviando Alerta. ");
        $emisor = Gestor_de_correo::MAIL_COBRODIGITAL_INFO;
        $destinatario = Gestor_de_correo::MAIL_DESARROLLO;
        $asunto = self::ALERTA_ASUNTO . ' | ' . $this->obtener_entidad();
        $mensaje = '';
        if ($this->log) {
            foreach ($this->log as $linea) {
                $mensaje .= $linea . '<br/>';
            }
        }
        return Gestor_de_correo::enviar($emisor, $destinatario, $asunto, $mensaje);
    }
}
