<?php

class Registro_ripsa extends Registro 
{
	const CARACTERES_POR_FILA=70;
	const INICIO_MONTO=33;
	const LONGITUD_MONTO=7;
	const DECIMALES_MONTO=2;
	const INICIO_FECHA_DE_PAGO=14;
	const LONGITUD_FECHA_DE_PAGO=8;
	const FORMATO_FECHA='!dmY';
	const INICIO_CODIGO_DE_BARRAS=41;
}