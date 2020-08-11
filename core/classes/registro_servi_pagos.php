<?php

class Registro_servi_pagos extends Registro
{
    // const CARACTERES_POR_FILA       = 116;
    // const INICIO_MONTO              = 33;
    // const LONGITUD_MONTO            = 7; # Debe ser 7 para coincidir con barcode
    // const DECIMALES_MONTO           = 2; # Debe ser 2 para coincidir con barcode
    // const INICIO_FECHA_DE_PAGO      = 40;
    // const LONGITUD_FECHA_DE_PAGO    = 8;
    // const FORMATO_FECHA             = '!Ymd';
    // const FORMATO_FECHA_EXPORT      = 'ymd';
    // const INICIO_CODIGO_ELECTRONICO = 9;
    // const IDENTIFICADOR_CONCEPTO    = "003";
    // const INICIO_PMCDEUDA           = 90;
    const COD_AGE = 37;

    public function generar_fila($fecha_pago, $cod_emp, $num_trans, $importe, $trn_cliente)
    {
//        $salto_de_linea="\n";
        $fecha_pago = new DateTime($fecha_pago);

        $fila = self::COD_AGE;
        $fila .= ";" . $fecha_pago->format('d/m/Y H:i:s');
        $fila .= ";" . $cod_emp;
        $fila .= ";" . $num_trans;
        $fila .= ";" . $importe;
        $fila .= ";" . $trn_cliente;
        $fila .= "\n";
//        $fila.=$salto_de_linea;
        $this->fila = $fila;
        return $this;
    }
}
