<?php

abstract class Registro
{
    public $fila;
    protected $codigo_de_barras=false; # Usado para PMC y Link
	public function __construct($string=false)
	{
            if(!$this->validar_fila($string)){
                    return false;
            }
            $this->fila=$string;
            return true;
        }
	public function set_codigo_de_barras($codigo_de_barras)
	{
		$this->codigo_de_barras=$codigo_de_barras;
	}
	public function get_fila()
	{

		return $this->fila;
	}
	public function validar_fila($string=null)
	{
		return true;
	}
	public function obtener_codigo_de_barras()
	{
		return substr($this->fila, static::INICIO_CODIGO_DE_BARRAS,Barcode::LONGITUD_BARCODE);
	}
	public function obtener_codigo_electronico()
	{
		return substr($this->fila, static::INICIO_CODIGO_ELECTRONICO,Barcode::LONGITUD_CODIGO_ELECTRONICO);
	}
	public function obtener_codigo_electronico_sin_dv()
	{
		return substr($this->obtener_codigo_electronico(), 0,-1);
	}
	public function obtener_monto()
	{
		return substr($this->fila, static::INICIO_MONTO,static::LONGITUD_MONTO);	
	}
	public function obtener_monto_numerico()
	{
		$numero=intval('1'.str_pad('', static::DECIMALES_MONTO,'0'));
		return intval($this->obtener_monto())/$numero;	
	}
	public function obtener_fecha_de_pago()
	{
	  error_log(substr($this->fila, static::INICIO_FECHA_DE_PAGO,static::LONGITUD_FECHA_DE_PAGO));
            return substr($this->fila, static::INICIO_FECHA_DE_PAGO,static::LONGITUD_FECHA_DE_PAGO);	
	}
	public function obtener_fecha_de_pago_datetime()
	{

		return Datetime::createFromFormat(static::FORMATO_FECHA,$this->obtener_fecha_de_pago());	
	}
	public function obtener_fecha_de_vencimiento()
	{
		return Barcode::obtener_fecha_de_vencimiento($this->obtener_codigo_de_barras());	
	}
	public function obtener_fecha_de_vencimiento_datetime()
	{
		return Barcode::obtener_fecha_de_vencimiento_datetime($this->obtener_codigo_de_barras());
	}
	public function obtener_segmento_comercial()
	{
		return Barcode::obtener_segmento_comercial($this->obtener_codigo_de_barras());		
	}
	public function obtener_barrand()
	{
		return Barcode::obtener_barrand($this->obtener_codigo_de_barras());
	}
	public function obtener_estado_a_insertar_sabana() {
        return Authstat::SABANA_ENTRANDO;
    }
	public function obtener_estado_a_actualizar_sabana() {
        return Authstat::SABANA_ENTRANDO;
    }
	public function cambiar_codigo_de_barras($codigo_de_barras)
	{
		# Usado en el procesamiento de Ufos
		$this->fila=substr($this->fila, 0,static::INICIO_CODIGO_DE_BARRAS).$codigo_de_barras.substr($this->fila, static::INICIO_CODIGO_DE_BARRAS+Barcode::LONGITUD_BARCODE);
	}
        public function obtener_id_local(){
            return null;
        }
}
