<?php
class Preprocesador_pagomiscuentas extends Preprocesador 
{
    const PATRON="/^cob4551.([0-9]{6})/";
    const PATRON_FECHA="dmy";
	const POSICION_ENCABEZADO=1;
	const POSICIONES_FINALES_DESCARTABLES=2; # Contar lineas que tienen solo un salto de carro
    
    const CONTROL_POSICION_REGISTRO=1;
    const CONTROL_POSICION_CANTIDAD_DE_REGISTROS=16;
    const CONTROL_LONGITUD_CANTIDAD_DE_REGISTROS=7;
    const CONTROL_POSICION_IMPORTE_TOTAL=23;
    const CONTROL_LONGITUD_IMPORTE_TOTAL=18;
    const CONTROL_DECIMALES_IMPORTE_TOTAL=2;
    const CONTROL_CANTIDAD_DE_REGISTROS_EXCLUYE_EXTRAS=false;

	public function __construct()
	{

		parent::__construct(self::CODIGO_ENTIDAD_PAGOMISCUENTAS.self::CODIGO_ENTIDAD_PAGOMISCUENTAS_ARCHIVO_RENDICION);
	}
	protected function obtener_barcode(Registro $registro)
	{
        # El pmcabc se repite para boletas con mas de un vencimiento
        # Pero no se puede repetir el monto para la misma boleta(boleta_pagador)
        $pmc19=$registro->obtener_codigo_electronico();
        $pmcabc=$registro->obtener_pmcabc();
        $monto=$registro->obtener_monto_numerico();
        $recordset=Barcode::select_pmc_preprocesador($pmc19,strtoupper($pmcabc),$monto);
	print_r($recordset->rowCount());
        if($recordset AND $recordset->RowCount()==1){
            $row=$recordset->FetchRow();
            $barcode=new Barcode($row);
			return $barcode;
        }
        elseif($recordset AND $recordset->RowCount()>1){
        	$this->developer_log($this->nlinea." | Hay más de un Barcode que coincide se toma el primero.");
                $row=$recordset->FetchRow();
                $barcode=new Barcode($row);
                return $barcode;
        }
        $this->developer_log($this->nlinea." | No ha sido posible obtener el Barcode. por $pmc19 $pmcabc $monto ");
		return false;
    }
    protected function obtener_barcode_para_ufos(Registro $registro)
    {
        return "UFO: ".$registro->obtener_codigo_electronico();
    }
    public static function obtener_directorio_ftp($archivo=false){
        return "/home/handsolo/PRODcdi/boffice/sabana/pagomiscuentas/";
    }

}
