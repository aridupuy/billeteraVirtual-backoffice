<?php

class Preprocesador_galicia extends Preprocesador {

    const PATRON = "/^REND.COB-COBC8496.COB-([0-9]{8}).TXT/";
    const PATRON_REVERSOS = "/^REND.REV-REVC8496.REV-([0-9]{8}).TXT/";
    const PATRON_FECHA = "Ymd";

    private $archivo_de_control = false;

    const POSICION_ENCABEZADO = 1;
    const POSICIONES_FINALES_DESCARTABLES = 2;
    const CANTIDAD_DE_REGISTROS_DE_DIFERENCIA = 6; # NO FUNCIONA EL CONTROL AUN
    const PERMITIR_ACTUALIZACION_DE_MULTIPLES_AGENDA_VINVULO = false; # Solo actualiza el mas nuevo. Dejar en false
    const CONTROL_POSICION_REGISTRO = false;
    const CONTROL_POSICION_CANTIDAD_DE_REGISTROS = 39;
    const CONTROL_LONGITUD_CANTIDAD_DE_REGISTROS = 7;
    const CONTROL_POSICION_IMPORTE_TOTAL = 25;
    const CONTROL_LONGITUD_IMPORTE_TOTAL = 14;
    const CONTROL_DECIMALES_IMPORTE_TOTAL = 2;
    const CONTROL_CANTIDAD_DE_REGISTROS_EXCLUYE_EXTRAS = false;
    const DIRECTORIO_FTP = "/home/samba/shares/mp/";
    public function __construct() {
        $codigo_entidad = self::CODIGO_ENTIDAD_GALICIA;
        $codigo_archivo = false;
        $tipo = 1;
        switch ($tipo) {
            # Esto no va a nada -_-
            case '1': $codigo_archivo = self::CODIGO_ENTIDAD_GALICIA_ARCHIVO_RENDICION;
                break;
            default: $codigo_archivo = self::CODIGO_ENTIDAD_GALICIA_ARCHIVO_RENDICION_DE_REVERSOS;
                break;
        }
        if (!$codigo_archivo) {
            return false;
        }
        parent::__construct($codigo_entidad . $codigo_archivo);
    }

    public function procesar_registro(Registro $registro, Control $control) {
        
        error_log("preprocesador_galicia");
        $this->cantidad_de_registros++;
        $this->monto_acumulado += $registro->obtener_monto_numerico();
        $this->monto_acumulado_cobrado += $registro->obtener_monto_numerico();
        if (!($debito_cbu = $this->obtener_debito_cbu($registro))) {
            $this->developer_log("obteniendo desde Barcode");
				
            if (!($barcode = $this->obtener_barcode($registro))) {
                
                if (!self::PERMITIR_REGISTROS_UFO) {
                    return false;
                } else {
                    $this->developer_log($this->nlinea . " | Nuevo debito_cbu no identificado: Ufo. ");
                    $this->cantidad_de_registros_ufo++;
                    $barcode = null; # Atento a esto
                }
            }
        } else {
            error_log("ufo");
            # Codigo de barras encontrado
            # Solo Link y PagoMisCuentas necesitan esto, para el resto es indistinto
            if($debito_cbu->get_id_debito()!=null)
                $registro->set_codigo_de_barras($debito_cbu->get_id_debito());
            else
                $registro->set_codigo_de_barras($barcode->get_barcode());
        }
//        error_log("barcode".$barcode);
	error_log(json_encode($debito_cbu));
        if (!isset($barcode) and $debito_cbu and !($sabana = $this->insertar_sabana_debito($registro, $control, $debito_cbu))) {
            $this->developer_log($this->nlinea . " | Ha ocurrido un error al Insertar la sabana de debito_cbu. ");
            return false;
        }
        elseif (isset($barcode) and $barcode and $barcode->get_id() != false) {
            error_log("buscando  desde Barcode");
            parent::procesar_registro($registro, $control);
        } 
        elseif(!(isset ($barcode) and ($barcode)) and !(isset ($debito_cbu) and $debito_cbu)){
            error_log("procesa ufo");
            parent::procesar_registro($registro, $control);
        }
        
        return true; # No esta bueno
    }

    #override

    public function insertar_sabana_debito(Registro $registro, Control $control, Debito_cbu $debito = null) {
        error_log("insertar_sabana galicia");
	$fecha_vto = new DateTime('now');
        if ($debito->get_id() == null) {
            # UFO
            $id_authstat = Authstat::SABANA_ORIGENUFO;
            $id_barcode = self::DUMMY_ID_BARCODE;
            $barcode2 = $this->obtener_barcode_para_ufos($registro); # METER CODIGO PMC
            $fecha_vto = new DateTime('now');
            $sc = '0';
            $barrand = '0';
        } else {

            $id_authstat = $registro->obtener_estado_a_insertar_sabana();
            $id_barcode = self::DUMMY_ID_BARCODE;
            $barcode2 = $debito->get_id_debito();
            $fecha_vto = $registro->obtener_fecha_pago1_datetime($debito->get_id());
            $sc = "2040";
            $barrand = 1;
        }
        $monto = $registro->obtener_monto_numerico();
        $fecha_pago = $registro->obtener_fecha_de_pago_datetime();
        $sabana = new Sabana();
        $this->developer_log($this->nlinea . " | Insertando sabana. ");

        $sabana->set_id_authstat($id_authstat);
        $sabana->set_id_barcode($id_barcode);
        $sabana->set_id_mp($this->obtener_id_mp($registro));
        $sabana->set_barcode($barcode2);

        if (!$fecha_vto) {
            $this->developer_log($this->nlinea . " | La fecha de vencimiento no es válida. ");
            return false;
        }
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
// 	developer_log(substr($barcode, 0,4));
        if (($this->obtener_id_mp($registro) == Mp::DEBITO_AUTOMATICO_REVERSO AND $id_authstat != Authstat::SABANA_DEBITO_REVERTIDO) OR $id_authstat == Authstat::SABANA_ORIGENUFO OR ( is_string($debito) and substr($barcode2, 0, 4) == "UFO:"))
            $sabana->set_id_marchand(1);
        else
            $sabana->set_id_marchand($debito->get_id_marchand());
        try {
            $sabana->set_xml_extra($this->crear_xml_extra($registro));
        } catch (Exception $e) {
            developer_log($e->getMessage());
            return $sabana;
        }
//        var_dump($sabana);
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
//	var_dump($id_debito_cbu);
	
        $debito_cbu = new Debito_cbu();
        if(strlen($id_debito_cbu)>=10){ //raro
            $this->developer_log("prevengo cierre de transaccion");
            return false;
        } //supera el valor integer de la base
        
        $debito_cbu->get($id_debito_cbu);
        if(Model::HasFailedTrans()){
            Model::CompleteTrans();
            Model::StartTrans();
            $this->developer_log("Reinicio la transaccion");
        }
            
        if ($debito_cbu->get_id() == false){
            developer_log("debito_cbu NO obtenido: ".$id_debito_cbu);
            return false;
        }
	developer_log("debito_cbu obtenido: ".$debito_cbu->get_id());
	
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
        //0000000001529439530
        $codigo_de_barras = preg_replace('/^0+/', '', $codigo_de_barras );
        developer_log("logitud del pmc19: ".strlen($codigo_de_barras));
        if(strlen($codigo_de_barras)!= Barcode::LONGITUD_CODIGO_ELECTRONICO){
            developer_log("No es un debito por barcode");
            return null;
        }
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

}
