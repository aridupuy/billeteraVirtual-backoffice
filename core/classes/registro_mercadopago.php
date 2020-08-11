<?php

class Registro_mercadopago
{
	const ESTADO_APROBADO='approved';
	const ESTADO_RECHAZADO='rejected';
	const ESTADO_EN_PROCESO='in_process';
	const ESTADO_PENDIENTE='pending';
	const ESTADO_EN_MEDIACION='in_mediation';
	const ESTADO_CANCELADO='cancelled';
	const ESTADO_REVERTIDO='refunded';
	const ESTADO_CONTRACARGO='charged_back';
	
	public $collection;
	const FORMATO_FECHA='Y-m-d\TH:i:s.uP';
	const TIMEZONE='America/Argentina/Buenos_Aires';
	public function __construct($collection)
	{	
		$this->collection=$collection;
		return $this;
	}
	public function get_collection()
	{
		return $this->collection;
	}
	# Es el gateway_op_id
	public function obtener_identificador_de_transaccion()
	{
		if(isset($this->collection['id'])){
			return $this->collection['id'];
		}
		return false;
	}
	public function obtener_estado()
	{
		if(isset($this->collection['status'])){
			return $this->collection['status'];
		}
		return false;
	}
	public function obtener_forma_de_pago()
	{
		if(isset($this->collection['payment_type'])){
			return $this->collection['payment_type'];
		}
		return false;
	}
	public function obtener_codigo_de_barras()
	{
		# Algunas cobranzas tienen mas caracteres en el campo
		if(isset($this->collection['external_reference'])){
			$external_reference=$this->collection['external_reference'];
			if(strlen($external_reference)!=Barcode::LONGITUD_BARCODE){
				if(strlen($external_reference)=='42' AND strpos($external_reference, 'A')=='29'){
					return substr($external_reference, 0, Barcode::LONGITUD_BARCODE);
				}
				developer_log('Código de barras desconocido: '.$external_reference);
			}
			return $external_reference;
		}
		return false;
	}
	public function obtener_monto_bruto()
	{
		if(isset($this->collection['transaction_amount'])){
			return $this->collection['transaction_amount'];
		}
		return false;
	}
	public function obtener_comision()
	{
		if(isset($this->collection['mercadopago_fee'])){
			return $this->collection['mercadopago_fee'];
		}
		return false;
	}
	public function obtener_monto_neto()
	{
		if(isset($this->collection['net_received_amount'])){
			return $this->collection['net_received_amount'];
		}
		return false;
	}
	public function obtener_fecha_de_creacion()
	{
		if(isset($this->collection['date_created'])){
			if(($fecha=DateTime::createFromFormat(self::FORMATO_FECHA,$this->collection['date_created']))){
				$fecha->setTimezone(new DateTimeZone(self::TIMEZONE));
				return $fecha;
			}
		}
		return false;
	}
	public function obtener_fecha_de_modificacion()
	{	
		if(isset($this->collection['date_last_updated'])){
			if(($fecha=DateTime::createFromFormat(self::FORMATO_FECHA,$this->collection['date_last_updated']))){
				$fecha->setTimezone(new DateTimeZone(self::TIMEZONE));
				return $fecha;
			}
		}
		return false;
	}
	public function obtener_fecha_de_aprobacion()
	{	
		if(isset($this->collection['date_approved'])){
			if(($fecha=DateTime::createFromFormat(self::FORMATO_FECHA,$this->collection['date_approved']))){
				$fecha->setTimezone(new DateTimeZone(self::TIMEZONE));
				return $fecha;
			}
		}
		return false;
	}
	public function obtener_fecha_de_liberacion_de_fondos()
	{	
		if(isset($this->collection['money_release_date'])){
			if(($fecha=DateTime::createFromFormat(self::FORMATO_FECHA,$this->collection['money_release_date']))){
				$fecha->setTimezone(new DateTimeZone(self::TIMEZONE));
				return $fecha;
			}
		}
		return false;
	}
	public function obtener_concepto()
	{
		if(isset($this->collection['reason'])){
			return $this->collection['reason'];
		}
		return false;
	}
	public function obtener_mensaje_para_usuario(){

		$mensaje=$this->collection['status_detail'];
		$traduccion=array();
		$traduccion['cc_rejected_bad_filled_card_number']='Número de tarjeta incorrecto.';
		$traduccion['cc_rejected_bad_filled_date']='Fecha de vencimiento incorrecta.';
		$traduccion['cc_rejected_bad_filled_other']='Datos incorrectos.';
		$traduccion['cc_rejected_bad_filled_security_code']='Código de seguridad incorrecto.';
		$traduccion['cc_rejected_blacklist']='No pudo procesarse el pago (Blacklist).';
		$traduccion['cc_rejected_call_for_authorize']='El pago debe ser autorizado';
		$traduccion['cc_rejected_card_disabled']='La tarjeta debe ser activada. ';
		$traduccion['cc_rejected_card_error']='No pudo procesarse el pago (Error).';
		$traduccion['cc_rejected_duplicated_payment']='Pago duplicado. ';
		$traduccion['cc_rejected_high_risk']='EL pago fue rechazado. ';
		$traduccion['cc_rejected_insufficient_amount']='No tiene fondos suficientes.';
		$traduccion['cc_rejected_invalid_installments']='No fue posible procesar pagos en cuotas.';
		$traduccion['cc_rejected_max_attempts']='Límite de intentos permitidos.';
		$traduccion['cc_rejected_other_reason']='No pudo procesarse el pago.';
		if(isset($traduccion[$mensaje])){
			return $traduccion[$mensaje];
		}
		else return false;
	}
}