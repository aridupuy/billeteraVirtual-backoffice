<?php

class Registro_rapipago extends Registro 
{
	const CARACTERES_POR_FILA=52;
        const LONGITUD_FILA=73;
	const INICIO_MONTO=8;
	const LONGITUD_MONTO=15;
	const DECIMALES_MONTO=2;
	const INICIO_FECHA_DE_PAGO=0;
	const LONGITUD_FECHA_DE_PAGO=8;
	const FORMATO_FECHA='!Ymd';
	const INICIO_CODIGO_DE_BARRAS=23;
        const LONGITUD_NOMBRE_EMPRESA=20;
        const LONGITUD_IMPORTE_TOTAL=18;
        const LONGITUD_CANTIDAD_TOTAL=8;

        public function obtener_cabecera(Marchand $marchand){
            $fecha=new DateTime("now");
            $cabecera="00000000".str_pad($marchand->get_apellido_rs(), self::LONGITUD_NOMBRE_EMPRESA, " ", STR_PAD_RIGHT);
            $cabecera.=$fecha->format("Ymd")."COBRANZAS   RAPIPAGO";
            $cabecera= str_pad($cabecera, self::LONGITUD_FILA," ",STR_PAD_RIGHT);
            return $cabecera."\n";
        }
        public function obtener_fila(Barcode $barcode, Moves $moves){
            $fecha= DateTime::createFromFormat("Y-m-d h:m:s", $moves->get_fecha());
            $importe= str_replace(",", "", $moves->get_monto_pagador());
            $importe= str_replace(".", "", $moves->get_monto_pagador());
            $importe= str_pad($importe, self::LONGITUD_MONTO,"0",STR_PAD_LEFT);
            $fila=$fecha->format("Ymd").$importe.$barcode->get_barcode();
            $fila= str_pad($fila, self::LONGITUD_FILA," ",STR_PAD_RIGHT);
            return $fila."\n";
        }
        public function obtener_pie($cantidad_total,$importe_total){
            $importe_total= str_replace(",", "", $importe_total);
            $importe_total= str_replace(".", "", $importe_total);
            $pie="99999999".str_pad($cantidad_total, self::LONGITUD_CANTIDAD_TOTAL, "0", STR_PAD_LEFT);
            $pie.= str_pad($importe_total, self::LONGITUD_IMPORTE_TOTAL,"0",STR_PAD_LEFT);
            $pie=str_pad($pie, self::LONGITUD_FILA," ",STR_PAD_RIGHT);
            return $pie."\n";
        }
}