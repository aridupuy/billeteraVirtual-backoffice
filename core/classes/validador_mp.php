<?php

abstract class Validador_mp {

    protected $barcodes;

    const ACTIVAR_DEBUG = true;

    public static $mp;
    protected $cuentas;

    const DUMMY_UNID = '25';
    const DUMMY_ID_FORMAPAGO = '3';
    const PERIODO_DE_CONSULTA_MERCADOPAGO = '12HOUR';
    const MERCADOPAGO_CORREO_1 = 'mp';
    const MERCADOPAGO_CORREO_2 = 'collect';
    const MERCADOPAGO_CORREO_3 = 'mp3e';
    const MERCADOPAGO_CORREO_4 = 'clicpagos';
    const MERCADOPAGO_CORREO_5 = 'pvp';
    const MERCADOPAGO_CORREO_6 = 'tcdaut';
    const MERCADOPAGO_CORREO_7 = 'pagodirecto';
    const DOMINIO_CORREO = '@cobrodigital.com';
    const MERCADOPAGO_CLAVE_PUBLICA_TCDAUT = "TEST-972cbbb5-c641-48c7-8d0e-cdc4e05d5e76";
    const MERCADOPAGO_CLAVE_PRIVADA_TCDAUT = "TEST-6252326434264808-080215-a4b1a98697df161225d2a4b45034e982__LA_LB__-213555671";

    public function __construct($barcode) {
        $this->barcodes = $this->obtener_barcodes_a_procesar($barcode);
        $this->cargar_cuentas();
        return $this;
    }

    public function ejecutar() {

        if (($datos = $this->procesar()) != false)
            return $datos;
        return false;
    }

    abstract public function obtener_barcodes_a_procesar($barcodes);

    abstract function procesar();

    public final function es_debito_tco($barcode) {
        
    }

    public final function insertar_transas($cobranza, $cuenta, Barcode $barcode = null, Debito_tco $debito_tco = null, $id_authstat) {
	    $transas = new Transas();
        if ($barcode != null) {
            $transas->set_id_entidad(Entidad::ENTIDAD_BARCODE);
            $transas->set_id_referencia($barcode->get_id_barcode());
            $id_marchand = $barcode->get_id_marchand();
        } elseif ($debito_tco != null) {
            $transas->set_id_entidad(Entidad::ENTIDAD_DEBITO_TCO);
            $transas->set_id_referencia($debito_tco->get_id_debito());
            $id_marchand = $debito_tco->get_id_marchand();
        }
        if (!$id_authstat) {
            developer_log('Estado desconocido');
            return false;
        }
        $transas->set_id_authstat($id_authstat);
        $transas->set_id_mp(Mp::TARJETA);
        developer_log($cobranza["fecha_aprobado"]);
        $f = new DateTime("now");

        $fecha_modificacion = DateTime::createFromFormat("Y-m-d\TH:i:s.uP", $cobranza["fecha_aprobado"]);

        if (!$fecha_modificacion) {
//		var_dump($cobranza);
            if (self::ACTIVAR_DEBUG) {
                developer_log('Error en la fecha de modificacion. ');
            }
            return false;
        }
        $transas->set_fecha($fecha_modificacion->format('Y-m-d H:i:s'));

        $diasplus_liq = intval(self::$mp->get_diaplus_liq());
        $intervalo = new DateInterval('P' . $diasplus_liq . 'D');

        $ahora = new DateTime('now');
        $fecha_liq = $ahora->add($intervalo);

        $transas->set_fecha_liq($fecha_liq->format('Y-m-d'));
        $transaccion = new Transaccion();
        $array = $transaccion->calculo_directo($id_marchand, Mp::TARJETA, $cobranza["monto"], null, $barcode);
        if (!$array) {
            developer_log('No existe comision.');
        }
        list($monto_pagador, $pag_fix, $pag_var, $monto_cd, $cdi_fix, $cdi_var, $monto_marchand) = $array;
        $transas->set_monto_pagador($monto_pagador);
        $transas->set_pag_fix($pag_fix);
        $transas->set_pag_var($pag_var);
        $transas->set_monto_cd($monto_cd);
        $transas->set_cdi_fix($cdi_fix);
        $transas->set_cdi_var($cdi_var);
        $transas->set_monto_marchand($monto_marchand);
        $crypt = crypt(sprintf("%01.2f", $monto_marchand), Transaccion::PASSWORD_CIFRADO_SALDOS);
        $transas->set_transa_md5($crypt);
        $transas->set_id_marchand($id_marchand);
        $transas->set_id_pricing($transaccion->pricing_pag->get_id());
        $transas->set_id_pricing_mch($transaccion->pricing_cdi->get_id());
        $transas->set_fecha_move('now');
        $transas->set_unid(self::DUMMY_UNID);
        $transas->set_transas_xml($this->crear_transas_xml($cobranza, $barcode, $debito_tco, $pag_fix + $pag_var, $cdi_var + $cdi_fix, $cuenta, $transaccion->pricing_pag, $transaccion->pricing_cdi));
        $transas->set_gateway_op_id($cobranza["id_mercado_pago_transaction"]);
        $transas->set_id_gateway($cuenta['id_peucd']);
        $transas->set_id_formapago(self::DUMMY_ID_FORMAPAGO);

        if ($transas->set()) {
            return $transas;
        }
        return false;
    }

    public final function crear_transas_xml($cobranza, Barcode $barcode = null, Debito_tco $debito_tco = null, $sum_pag, $sum_cdi, $cuenta, Pricing $pricing_pag, Pricing $pricing_cdi) {
        $xml = new DOMDocument('1.0', 'utf-8');
        $transa = $xml->createElement('transa');
        $gateway = $xml->createElement('gateway', 'Mercado Pago (' . $cuenta['alias'] . ')');
        $mensaje_para_usuario = $xml->createElement('mensaje_para_usuario', self::obtener_mensaje_para_usuario($cobranza['status_detail']));
        $gateway_op_id = $xml->createElement('gateway_op_id', $cobranza["id_mercado_pago_transaction"]); //identificador de la transaccion
        $transa->appendChild($gateway);
        $transa->appendChild($mensaje_para_usuario);
        $transa->appendChild($gateway_op_id);
        $pricing_tco = $xml->createElement('pricing_tco');
        $tipo = $xml->createElement('tipo', 'pagador');
        $valor = $xml->createElement('valor', $sum_pag);
        $valido_desde = $xml->createElement('valido_desde');
        $valor_fijo = $xml->createElement('valor_fijo', $pricing_pag->get_pri_fijo());
        $valor_variable = $xml->createElement('valor_variable', $pricing_pag->get_pri_variable());
        if ($pricing_pag->get_id_marchand()) {
            $mensaje = 'Pricing dedicado';
        } else
            $mensaje = 'Pricing generico';
        $usando = $xml->createElement('usando', 'STEP X ID ' . $pricing_pag->get_id() . '(' . $mensaje . ')');
        $pricing_tco->appendChild($tipo);
        $pricing_tco->appendChild($valor);
        $pricing_tco->appendChild($valido_desde);
        $pricing_tco->appendChild($valor_fijo);
        $pricing_tco->appendChild($valor_variable);
        $pricing_tco->appendChild($usando);
        $transa->appendChild($pricing_tco);

        $pricing_marchand = $xml->createElement('pricing_marchand');
        $tipo = $xml->createElement('tipo', 'pagador');
        $valor = $xml->createElement('valor', $sum_cdi);
        $valido_desde = $xml->createElement('valido_desde');
        $valor_fijo = $xml->createElement('valor_fijo', $pricing_cdi->get_pri_fijo());
        $valor_variable = $xml->createElement('valor_variable', $pricing_cdi->get_pri_variable());
        if ($pricing_cdi->get_id_marchand()) {
            $mensaje = 'Pricing dedicado';
        } else
            $mensaje = 'Pricing generico';
        $usando = $xml->createElement('usando', 'STEP X ID ' . $pricing_cdi->get_id() . '(' . $mensaje . ')');
        $pricing_marchand->appendChild($tipo);
        $pricing_marchand->appendChild($valor);
        $pricing_marchand->appendChild($valido_desde);
        $pricing_marchand->appendChild($valor_fijo);
        $pricing_marchand->appendChild($valor_variable);
        $pricing_marchand->appendChild($usando);
        $transa->appendChild($pricing_marchand);
        $status = strtoupper($this->obtener_estado($cobranza));
        $status_ini = $xml->createElement('status_ini', $status);
        if ($barcode != null) {
            $importe_bc = $xml->createElement('importe_bc', $barcode->get_monto());
        } elseif ($debito_tco != null) {
            $importe_bc = $xml->createElement('importe_bc', $debito_tco->get_monto());
        }
        $importe_gw = $xml->createElement('importe_gw', $cobranza["monto"]);
        $importe_exacto = '0';
        if (($barcode != null and ( $barcode->get_monto() == $cobranza["monto"])) OR ( $debito_tco != null and ( $debito_tco->get_monto() == $cobranza["monto"]))) {
            $importe_exacto = '1';
        }
        $importe_exacto = $xml->createElement('importe_exacto', $importe_exacto);
        $transa->appendChild($status_ini);
        $transa->appendChild($importe_bc);
        $transa->appendChild($importe_gw);
        $transa->appendChild($importe_exacto);

        $xml->appendChild($transa);

        return $xml->saveXML();
    }

    public final static function obtener_mensaje_para_usuario($mensaje) {

//        $mensaje = $collection['status_detail']; 
        $traduccion = array();
        $traduccion['cc_rejected_bad_filled_card_number'] = 'Número de tarjeta incorrecto.';
        $traduccion['cc_rejected_bad_filled_date'] = 'Fecha de vencimiento incorrecta.';
        $traduccion['cc_rejected_bad_filled_other'] = 'Datos incorrectos.';
        $traduccion['cc_rejected_bad_filled_security_code'] = 'Código de seguridad incorrecto.';
        $traduccion['cc_rejected_blacklist'] = 'No pudo procesarse el pago (Blacklist).';
        $traduccion['cc_rejected_call_for_authorize'] = 'El pago debe ser autorizado';
        $traduccion['cc_rejected_card_disabled'] = 'La tarjeta debe ser activada. ';
        $traduccion['cc_rejected_card_error'] = 'No pudo procesarse el pago (Error).';
        $traduccion['cc_rejected_duplicated_payment'] = 'Pago duplicado. ';
        $traduccion['cc_rejected_high_risk'] = 'El pago fue rechazado. ';
        $traduccion['cc_rejected_insufficient_amount'] = 'No tiene fondos suficientes.';
        $traduccion['cc_rejected_invalid_installments'] = 'No fue posible procesar pagos en cuotas.';
        $traduccion['cc_rejected_max_attempts'] = 'Límite de intentos permitidos.';
        $traduccion['cc_rejected_other_reason'] = 'No pudo procesarse el pago.';
        if (isset($traduccion[$mensaje])) {
            return $traduccion[$mensaje];
        } else
            return false;
    }

    public function obtener_estado($cobranza) {
        return "";
    }

    public function actualizar_debito(Debito_tco $debito_tco=null, $id_authstat, $detalle = false) {
	if($debito_tco==null){
		developer_log("No es un debito_tco error!");
		return false;
	}
        developer_log($id_authstat);
        developer_log($debito_tco->get_id_authf1());
        $debito_tco_aux=new Debito_tco();
        $debito_tco_aux->set_id($debito_tco->get_id());
        if ($debito_tco->get_id_authf1() == $id_authstat) {
            developer_log("El debito ya tiene el estado correcto.");
            return true;
        }
        if ($debito_tco->get_id_authf1() != $id_authstat) {
            $debito_tco_aux->set_id_authf1($id_authstat);
            if ($detalle != false) {
                $debito_tco_aux->set_motivorechazo($detalle);
            }
        }
        if ($debito_tco_aux->set()) {
            developer_log("Debito Actualizado correctamente.");
            return true;
        }
        else
            developer_log("Error al actualizar el debito.");
        return false;
    }

    public function cargar_cuentas() {
        $this->cuentas = array();
       $this->cuentas[self::MERCADOPAGO_CORREO_1] = array('client_id' => '1872102149935260',
            'client_secret' => 'U8SInUyeb58ixtUQanTvTCrMnYWRuEff',
            'correo' => self::MERCADOPAGO_CORREO_1 . self::DOMINIO_CORREO,
            'alias' => 'MP1',
            'id_peucd' => Peucd::MERCADOPAGO_MP1
        );
        $this->cuentas[self::MERCADOPAGO_CORREO_2] = array('client_id' => '4812010488902074',
            'client_secret' => 'RSXDuaXJJSI7FSl1OzhfPeu9hKdqIywR',
            'correo' => self::MERCADOPAGO_CORREO_2 . self::DOMINIO_CORREO,
            'alias' => 'MP2',
            'id_peucd' => Peucd::MERCADOPAGO_MP2
        );
        $this->cuentas[self::MERCADOPAGO_CORREO_3] = array('client_id' => '420311948104044',
            'client_secret' => 'y3MnDnV0aBDXPUR5vmB3kTboyr9sFVbL',
            'correo' => self::MERCADOPAGO_CORREO_3 . self::DOMINIO_CORREO,
            'alias' => 'MP3',
            'id_peucd' => Peucd::MERCADOPAGO_MP3
        );
        $this->cuentas[self::MERCADOPAGO_CORREO_4] = array('client_id' => '8255255304938580',
            'client_secret' => '1f0M14QqNFOXkfqWJPyNOtdl4YdO8zeX',
            'correo' => self::MERCADOPAGO_CORREO_4 . self::DOMINIO_CORREO,
            'alias' => 'MP4',
            'id_peucd' => Peucd::MERCADOPAGO_MP4
        );
        $this->cuentas[self::MERCADOPAGO_CORREO_5] = array(
            'access_token' => MERCADOPAGO_CLAVE_PRIVADA_TCDAUT,
            'correo' => self::MERCADOPAGO_CORREO_5 . self::DOMINIO_CORREO,
            'alias' => 'MP5',
            'id_peucd' => Peucd::MERCADOPAGO_MP5
        );
        $this->cuentas[self::MERCADOPAGO_CORREO_6] = array(
            'access_token' => MERCADOPAGO_CLAVE_PRIVADA_TCDAUT,
            'correo' => self::MERCADOPAGO_CORREO_6 . self::DOMINIO_CORREO,
            'alias' => 'MP6',
            'id_peucd' => Peucd::MERCADOPAGO_MP6
        );
        $this->cuentas[self::MERCADOPAGO_CORREO_7] = array(
            'access_token' => MERCADOPAGO_CLAVE_PRIVADA_PAGODIRECTO,
            'correo' => self::MERCADOPAGO_CORREO_7 . self::DOMINIO_CORREO,
            'alias' => 'MP7',
            'id_peucd' => Peucd::MERCADOPAGO_MP7
        );
    }

}
