<?php

class Registro_multipago extends Registro 
{
	const CARACTERES_POR_FILA=50;
	const INICIO_MONTO=10;
	const LONGITUD_MONTO=7;
	const DECIMALES_MONTO=2;
	const INICIO_FECHA_DE_PAGO=0;
	const LONGITUD_FECHA_DE_PAGO=6;
	const FORMATO_FECHA='!dmy';
	const INICIO_CODIGO_DE_BARRAS=17;

}