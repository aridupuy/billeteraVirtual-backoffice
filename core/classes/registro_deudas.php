<?php

class Registro_deudas extends Registro 
{
	const CARACTERES_POR_FILA=52;
        const LONGITUD_FILA=79;
	const INICIO_MONTO=8;
	const LONGITUD_MONTO=15;
	const DECIMALES_MONTO=2;
	const INICIO_FECHA_DE_PAGO=0;
	const LONGITUD_FECHA_DE_PAGO=8;
	const FORMATO_FECHA='!Ymd';
	const INICIO_CODIGO_DE_BARRAS=23;
        const LONGITUD_NOMBRE_EMPRESA=20;
        const LONGITUD_ID_MARCHAND=4;
        const LONGITUD_IMPORTE_TOTAL=18;
        const LONGITUD_CANTIDAD_TOTAL=8;
        const LONGITUD_AGENCIA=8;
        const LONGITUD_CREDITO=14;

        public function obtener_cabecera(Marchand $marchand){
            $fecha=new DateTime("now");
            $cabecera="00000000".str_pad($marchand->get_apellido_rs(), self::LONGITUD_NOMBRE_EMPRESA, " ", STR_PAD_RIGHT);
            $cabecera.=$fecha->format("Ymd")."COBRANZAS";
            $cabecera= str_pad($cabecera, self::LONGITUD_FILA," ",STR_PAD_RIGHT);
            return $cabecera."\r\n";
        }
        public function obtener_fila(Barcode $barcode, Moves $moves){
            $fecha= DateTime::createFromFormat("Y-m-d h:i:s", $moves->get_fecha());
            $id_marchand = str_pad($moves->get_id_marchand(), self::LONGITUD_ID_MARCHAND, "0", STR_PAD_LEFT);
            $importe = str_replace(",", "", $moves->get_monto_pagador());    //saco comas del monto
            $importe = str_replace(".", "", $moves->get_monto_pagador());    //saco puntos del monto
            $importe = str_pad($importe, self::LONGITUD_MONTO,"0",STR_PAD_LEFT);
            $deuda = Deuda::obtener_deuda_desde_barcode($barcode->getId());
            $deuda = $deuda->fetchRow();
            $agencia = $deuda['agencia'];
            $credito = $deuda['credito'];
            
            $fila = $fecha->format("Ymd");
            $fila.= $id_marchand;
            $fila.= $importe;
            $fila.= str_pad($agencia, self::LONGITUD_AGENCIA,"0",STR_PAD_LEFT);
            $fila.= str_pad($credito, self::LONGITUD_CREDITO,"0",STR_PAD_LEFT);
            $fila.= $barcode->get_barcode();
            $fila = str_pad($fila, self::LONGITUD_FILA," ",STR_PAD_RIGHT);
            return $fila."\r\n";
        }
        public function obtener_pie($cantidad_total,$importe_total){
            $importe_total= str_replace(",", "", $importe_total);
            $importe_total= str_replace(".", "", $importe_total);
            $pie="99999999".str_pad($cantidad_total, self::LONGITUD_CANTIDAD_TOTAL, "0", STR_PAD_LEFT);
            $pie.= str_pad($importe_total, self::LONGITUD_IMPORTE_TOTAL,"0",STR_PAD_LEFT);
            $pie=str_pad($pie, self::LONGITUD_FILA," ",STR_PAD_RIGHT);
            return $pie."\r\n";
        }
}
