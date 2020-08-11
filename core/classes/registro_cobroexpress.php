<?php

class Registro_cobroexpress extends Registro 
{
	const CARACTERES_POR_FILA=52;
	const INICIO_MONTO=45;
	const LONGITUD_MONTO=7;
	const DECIMALES_MONTO=2;
	const INICIO_FECHA_DE_PAGO=30;
	const LONGITUD_FECHA_DE_PAGO=8;
	const FORMATO_FECHA='!Ymd';
	const INICIO_CODIGO_DE_BARRAS=1;
        
        
        
        public function crear_linea($codigo_de_barras,$fecha,$monto){
            $linea="D";
            $linea.=$codigo_de_barras;
            $fecha=DateTime::createFromFormat("Y-m-d", $fecha);
            $linea.=$fecha->format("Ymd");
            $monto= number_format($monto,2,",",".");
            $monto= str_replace(",", "", $monto);
            $monto= str_replace(".", "", $monto);
            $monto= str_pad($monto, 11,"0",STR_PAD_LEFT);
            $linea.=$monto;
//            $linea= str_pad($linea, self::CARACTERES_POR_FILA," ");
            return $linea;
        }
        public function crear_encabezado(DateTime $fecha,$cantidad_registros,$monto){
            $linea="C";
            $monto= number_format($monto,2,",",".");
            $monto= str_replace(",", "", $monto);
            $monto= str_replace(".", "", $monto);
            $monto= str_pad($monto, 12,"0",STR_PAD_LEFT);
            $cantidad_registros=str_pad($cantidad_registros, 9,"0",STR_PAD_LEFT);
            $linea.=$fecha->format("Ymd"). $cantidad_registros.$monto;
//            $linea= str_pad($linea, self::CARACTERES_POR_FILA," ");
            return $linea;
        }
        public function crear_pie($total_registros,$total_dinero){
            $linea="3";
            $linea.= str_pad($total_registros, 7,"0",STR_PAD_LEFT);
            $total_dinero=
            $total_dinero= str_replace(",", "", $total_dinero);
            $total_dinero= str_replace(".", "", $total_dinero);
            $linea.= str_pad($total_dinero, 14,"0",STR_PAD_LEFT);
            return $linea;
        }
}