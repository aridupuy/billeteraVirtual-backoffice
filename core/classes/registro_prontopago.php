<?php

class Registro_prontopago extends Registro 
{
	const CANTIDAD_DE_CAMPOS=8;
	const DELIMITADOR_ARRAY=';';
	const RELLENO='*';
	const RELLENO_NUMERICO='0';

	const CAMPO_1_PADDING=5;
	const CAMPO_1_POSICION=0;
	const CAMPO_2_PADDING=5;
	const CAMPO_2_POSICION=1;
	const CAMPO_3_PADDING=0;
	const CAMPO_3_POSICION=2;
	const CAMPO_4_PADDING=0;
	const CAMPO_4_POSICION=3;
	const FECHA_DE_PAGO_PADDING=0;
	const FECHA_DE_PAGO_POSICION=4;
	const CAMPO_6_PADDING=0;
	const CAMPO_6_POSICION=5;
	const IMPORTE_PADDING=0;
	const IMPORTE_POSICION=6;
	const CODIGO_DE_BARRAS_PADDING=0;
	const CODIGO_DE_BARRAS_POSICION=7;


	# Todas las siguientes constantes son calculos de las anteriores.
	# El soporte a expresiones comienza en PHP 5.6
	const CARACTERES_POR_FILA=81; # Todo
	const INICIO_MONTO=40; 
	const LONGITUD_MONTO=12; 
	const DECIMALES_MONTO=2; 
	const INICIO_FECHA_DE_PAGO=26;
	const LONGITUD_FECHA_DE_PAGO=8;
	const FORMATO_FECHA='!Ymd';
	const INICIO_CODIGO_DE_BARRAS=52;

	public function __construct($array)
	{
		if(!($string=$this->preparar_fila($array))){
			return false;
		}
		return parent::__construct($string);
	}
	private function preparar_fila($array)
	{
		$array=explode(self::DELIMITADOR_ARRAY, $array);
		if(!$array OR count($array)!=self::CANTIDAD_DE_CAMPOS){
			return false;
		}
		
		$string='';
		$string.=str_pad($array[self::CAMPO_1_POSICION], self::CAMPO_1_PADDING,self::RELLENO,STR_PAD_LEFT);
		$string.=str_pad($array[self::CAMPO_2_POSICION], self::CAMPO_2_PADDING,self::RELLENO,STR_PAD_LEFT);
		$string.=str_pad($array[self::CAMPO_3_POSICION], self::CAMPO_3_PADDING,self::RELLENO,STR_PAD_LEFT);
		$string.=str_pad($array[self::CAMPO_4_POSICION], self::CAMPO_4_PADDING,self::RELLENO,STR_PAD_LEFT);
		$string.=str_pad($array[self::FECHA_DE_PAGO_POSICION], self::FECHA_DE_PAGO_PADDING,self::RELLENO,STR_PAD_LEFT);
		$string.=str_pad($array[self::CAMPO_6_POSICION], self::CAMPO_6_PADDING,self::RELLENO,STR_PAD_LEFT);
		$string.=str_pad($array[self::IMPORTE_POSICION],self::IMPORTE_PADDING,self::RELLENO_NUMERICO,STR_PAD_LEFT);
		$string.=str_pad($array[self::CODIGO_DE_BARRAS_POSICION], self::CODIGO_DE_BARRAS_PADDING,self::RELLENO,STR_PAD_LEFT);

		return $string;
	}
}