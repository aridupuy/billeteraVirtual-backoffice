<?php

class Registro_pagofacil extends Registro 
{
	# EJEMPLO
	
	const CARACTERES_POR_FILA=119;
	const INICIO_MONTO=113;
	const LONGITUD_MONTO=7; # Debe ser 7 para coincidir con barcode
	const DECIMALES_MONTO=2; # Debe ser 2 para coincidir con barcode
	const INICIO_FECHA_DE_PAGO=8;
	const LONGITUD_FECHA_DE_PAGO=8;
	const FORMATO_FECHA='!Ymd';
	const INICIO_CODIGO_DE_BARRAS=69;
        public $local_no_identificado=array();
        public function __construct($string)
	{
		if(!($string=$this->preparar_fila($string))){
			return false;
		}
		return parent::__construct($string);
	}
	private function preparar_fila($string)
	{
		$string=str_replace(' ', '', $string);
		$string=str_replace('\n', '', $string);
		return $string;
	}
        public function obtener_id_local(){
            $cod_local = substr($this->fila, 44 ,6);
            $rs = Local_pf::select(array("codigo_local"=>$cod_local));
            $row=$rs->fetchRow();
            if(!$row){
                $this->local_no_identificado[]=$cod_local;
            }
            return $row["id_local_pf"];
        }
	public function locales_no_encontrados(){
		return $this->local_no_identificado;
	}
}
