<?php

class Registro_linkpagos extends Registro 
{
	const CARACTERES_POR_FILA=116;
	const INICIO_MONTO=33;
	const LONGITUD_MONTO=7; # Debe ser 7 para coincidir con barcode
	const DECIMALES_MONTO=2; # Debe ser 2 para coincidir con barcode
	const INICIO_FECHA_DE_PAGO=40;
	const LONGITUD_FECHA_DE_PAGO=8;
	const FORMATO_FECHA='!Ymd';
	const FORMATO_FECHA_EXPORT='ymd';
	const INICIO_CODIGO_ELECTRONICO=9;
    const IDENTIFICADOR_CONCEPTO="003";
    const INICIO_PMCDEUDA=90;
    const LONGITUD_PMCDEUDA=5;
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
    public function generar_fila($pmcdeuda,$pmc19,$codigo_de_barras_1,  DateTime $fecha_vto1,$importe_1,DateTime $fecha_vto2=null,$importe_2=null,$codigo_de_barras_2="",DateTime $fecha_vto3=null, $importe_3=null,$codigo_de_barras_3="")
    {
//        $salto_de_linea="\n";
        $fila=  $pmcdeuda.self::IDENTIFICADOR_CONCEPTO;
        $fila.=$pmc19;
        $fila.=$fecha_vto1->format(self::FORMATO_FECHA_EXPORT);
        $fila.=str_pad($importe_1, 12,"0",STR_PAD_LEFT);
        if($importe_2!=null AND $fecha_vto2!=null){
            $fila.=$fecha_vto2->format(self::FORMATO_FECHA_EXPORT);
            $fila.=str_pad($importe_2, 12,"0",STR_PAD_LEFT);    
        }
        else{
            $fila.=str_pad("", 6,"0",STR_PAD_LEFT);
            $fila.=str_pad("", 12,"0",STR_PAD_LEFT);
        }
        if($importe_3!=null AND $fecha_vto3!=null){
            $fila.=$fecha_vto3->format(self::FORMATO_FECHA_EXPORT);

            $fila.=str_pad($importe_3, 12,"0",STR_PAD_LEFT);
        }
        else{
            $fila.=str_pad("", 6,"0",STR_PAD_LEFT);
            $fila.=str_pad("", 12,"0",STR_PAD_LEFT);
        }
        $discrecional=substr($codigo_de_barras_1, 15);
        if($codigo_de_barras_2!="")
            $discrecional.=substr($codigo_de_barras_2, 15);
        else
            $discrecional.=str_pad("", 15,"0");
        if($codigo_de_barras_3!="")
            $discrecional.=substr($codigo_de_barras_3, 15);
        else
            $discrecional.=str_pad("", 15,"0");
        $fila.=str_pad($discrecional, 50,"0",STR_PAD_RIGHT);
//        $fila.=$salto_de_linea;
        $this->fila=$fila;
        return $this;
    }
    public function obtener_pmcdeuda()
    {
        return substr($this->fila, static::INICIO_PMCDEUDA,static::LONGITUD_PMCDEUDA);
    }
}
