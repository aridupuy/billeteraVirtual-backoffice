<?php

class Registro_bica extends Registro 
{
	const CANTIDAD_DE_CAMPOS=5;
	const DELIMITADOR_ARRAY=';';
	const CARACTER_INNECESARIO="\"";
	const RELLENO='*';
	const RELLENO_NUMERICO='0';

	const ID_LINEA_PADDING=5;
	const ID_LINEA_POSICION=0;
	const CODIGO_DE_BARRAS_PADDING=0;
	const CODIGO_DE_BARRAS_POSICION=1;
	const IMPORTE_PADDING=10;
	const IMPORTE_POSICION=2;
	const IMPORTE_SEPARADOR_DE_MILES='.';
	const FECHA_DE_PAGO_PADDING=0;
	const FECHA_DE_PAGO_POSICION=3;
	const BOCA_DE_COBRO_PADDING=50;
	const BOCA_DE_COBRO_POSICION=4;

	# Todas las siguientes constantes son calculos de las anteriores.
	# El soporte a expresiones comienza en PHP 5.6
	const CARACTERES_POR_FILA=102; # Todo
	const INICIO_MONTO=34; # Padding id_linea + Longitud codigo de barras
	const LONGITUD_MONTO=10; # Importe Padding
	const DECIMALES_MONTO=2; # Funcion
	const INICIO_FECHA_DE_PAGO=44; # Padding id_linea + Longitud codigo de barras + Padding importe
	const LONGITUD_FECHA_DE_PAGO=8; # Fijo
	const FORMATO_FECHA='!Ymd'; # Fijo
	const INICIO_CODIGO_DE_BARRAS=5; # Padding id_linea

	public function __construct($array, $preparar_fila=true)
	{
		if($preparar_fila){
			if(!($string=$this->preparar_fila($array))){
				return false;
			}
		}
		else{
			$string=$array; # Ya es un string 
		}
		return parent::__construct($string);
	}
	private function preparar_fila($array)
	{
		$array=explode(self::DELIMITADOR_ARRAY, $array);
		if(!$array OR count($array)!=self::CANTIDAD_DE_CAMPOS){
			return false;
		}
		foreach ($array as $key => $value) {
			$array[$key]=str_replace(self::CARACTER_INNECESARIO, '', $value);
		}
		if(!($importe=$this->preparar_importe($array[self::IMPORTE_POSICION]))) {
			return false;
		}
		$string='';
		$string.=str_pad($array[self::ID_LINEA_POSICION], self::ID_LINEA_PADDING,self::RELLENO,STR_PAD_LEFT);
		$string.=str_pad($array[self::CODIGO_DE_BARRAS_POSICION], self::CODIGO_DE_BARRAS_PADDING,self::RELLENO,STR_PAD_LEFT);
		$string.=str_pad($importe,self::IMPORTE_PADDING,self::RELLENO_NUMERICO,STR_PAD_LEFT);
		$string.=str_pad($array[self::FECHA_DE_PAGO_POSICION], self::FECHA_DE_PAGO_PADDING,self::RELLENO,STR_PAD_LEFT);
		$string.=str_pad($array[self::BOCA_DE_COBRO_POSICION], self::BOCA_DE_COBRO_PADDING,self::RELLENO,STR_PAD_LEFT);

		return $string;
	}
	private function preparar_importe($importe)
	{
		$aux=explode(self::IMPORTE_SEPARADOR_DE_MILES, $importe);
		if(count($aux)==1){
			$aux[1]='';
		}
		if(count($aux)==2){
			return $aux[0].str_pad($aux[1], self::DECIMALES_MONTO,'0');
		}
		return false;
	}
}