<?php

class Registro_pagomiscuentas extends Registro 
{
    const ACTIVAR_DEBUG=true;
    const CARACTERES_POR_FILA=100;
    const INICIO_MONTO=57;
    const LONGITUD_MONTO=11; # Debe ser 11 para coincidir con barcode
    const DECIMALES_MONTO=2; # Debe ser 2 para coincidir con barcode
    const INICIO_FECHA_DE_PAGO=69;
    const LONGITUD_FECHA_DE_PAGO=8;
    const FORMATO_FECHA='Ymd';
    const INICIO_CODIGO_ELECTRONICO=1;
    const ALINEACION_TEXTO=1;
    const ALINEACION_NUMERO=0;
    const CODIGO_REGISTRO="5"; #Código de registro valor fijo;
    const CODIGO_MONEDA="0"; # Moneda Pesos Argentinos.
    const INICIO_PMCABC=28;
    const LONGITUD_PMCABC=3;
    
    public function obtener_codigo_de_barras()
    {
    # El codigo de barras se obtiene en el Procesador
            return $this->codigo_de_barras;
    }
    public function cambiar_codigo_de_barras($codigo_de_barras)
    {
        # Usado en el procesamiento de Ufos
        $this->codigo_de_barras=$codigo_de_barras;
    }
    public function obtener_pmcabc()
    {
        return substr($this->fila, static::INICIO_PMCABC,static::LONGITUD_PMCABC);
    }
    public function construir_fila($id_cliente,$id_factura,  DateTime $fecha_1,$importe_1,$concepto,$codigo_de_barras,  DateTime $fecha_2=null ,$importe_2=null,Datetime $fecha_3=null,$importe_3=null)
    {
        $fila="\n";
        $fila.=self::CODIGO_REGISTRO; 
        if($id_cliente==""){
            if(self::ACTIVAR_DEBUG)
                developer_log("|| EL pmc esta vacio..");
        }
        $fila.=str_pad_utf8($id_cliente, 19," ",  self::ALINEACION_TEXTO);
        $fila.=str_pad_utf8($id_factura, 20," ", self::ALINEACION_TEXTO);
        $fila.=self::CODIGO_MONEDA;
        $fila.=$fecha_1->format(self::FORMATO_FECHA);
        $fila.= self::preparar_numero($importe_1);
        if(($fecha_2!=null AND $fecha_2!="" ) AND ($importe_2!=null AND $importe_2!="")){
            $fila.=$fecha_2->format(self::FORMATO_FECHA).self::preparar_numero($importe_2);
        }
        else
            $fila.=str_pad_utf8("", 19,"0");#fechay monto todo incluido
        
        if(($fecha_3!=null AND $fecha_3!="" ) AND ($importe_3!=null AND $importe_3!="")){
            $fila.=$fecha_3->format(self::FORMATO_FECHA).  self::preparar_numero($importe_3);
        }
        else
            $fila.=str_pad_utf8("", 19,"0"); #fechay monto todo incluido
        $fila.=str_pad_utf8("", 19,"0");
        $fila.=str_pad_utf8($id_cliente, 19,  self::ALINEACION_TEXTO);
        if(true){
            $concepto=  str_replace("ñ", "n", $concepto);
            $concepto=  str_replace("Ñ", "N", $concepto);
            $concepto=  str_replace("°", " ", $concepto);
            $concepto=  str_replace("º", " ", $concepto);
            $concepto=  str_replace("·", " ", $concepto);
            $concepto=  quitar_acentos($concepto);
        }
        $fila.=str_pad_utf8(substr($concepto, 0,40),40," ",  self::ALINEACION_TEXTO);
        $fila.=str_pad_utf8(substr($concepto, 0,15),15," ",  self::ALINEACION_TEXTO);
        $fila.=str_pad_utf8($codigo_de_barras, 60," ",  self::ALINEACION_TEXTO);
        $fila.=str_pad_utf8("", 29,"0");
        if($fila!=false){
            $this->fila=$fila;
            return $this;
        }
        return false;
    }
    private function preparar_numero($numero)
    {
        $numero=  number_format($numero,2);
        $numero= str_replace(",","",$numero);
        $numero= str_replace(".","",$numero);
        $numero=  str_pad_utf8($numero, 11,"0",  self::ALINEACION_NUMERO);
        return $numero;
    }
}
