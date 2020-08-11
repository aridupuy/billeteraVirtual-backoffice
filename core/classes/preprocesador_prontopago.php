<?php
class Preprocesador_prontopago extends Preprocesador 
{
    const PATRON="/^PRP([0-9]{6}).TXT/";
    const PATRON_FECHA="ymd";
	const POSICION_ENCABEZADO=1;
	const POSICIONES_FINALES_DESCARTABLES=2;
	
	const CONTROL_POSICION_REGISTRO=1; 
    const CONTROL_POSICION_CANTIDAD_DE_REGISTROS=3; # Posicion del Array
    const CONTROL_POSICION_IMPORTE_TOTAL=4; # Posicion del Array
    const CONTROL_DECIMALES_IMPORTE_TOTAL=2;

    const DIRECTORIO_FTP='CobroDigital/';
    
	public function __construct()
	{

		parent::__construct(self::CODIGO_ENTIDAD_PRONTOPAGO.self::CODIGO_ENTIDAD_PRONTOPAGO_ARCHIVO_RENDICION);
	}
	protected function controlar()
    {
        $this->developer_log("Controlando total de registros e importe total. ");
        
        $this->puntero_fichero->seek($this->cantidad_de_registros-static::CONTROL_POSICION_REGISTRO+2);
        
        $registro=$this->puntero_fichero->current();
        $registro=explode(Registro_prontopago::DELIMITADOR_ARRAY, $registro);
        $cantidad_de_registros=intval($registro[static::CONTROL_POSICION_CANTIDAD_DE_REGISTROS]);    
        
        $importe_total=$registro[static::CONTROL_POSICION_IMPORTE_TOTAL];
        $importe_total=intval($importe_total);
        $importe_total=$importe_total/pow(10, static::CONTROL_DECIMALES_IMPORTE_TOTAL);
        
        $this->developer_log('Importe total: '.$importe_total);
        $this->developer_log('Importe controlado: '.$this->monto_acumulado);
        $this->developer_log('Cantidad de registros: '.$cantidad_de_registros);
        $this->developer_log('Cantidad de registros controlado: '.$this->cantidad_de_registros);
        
        if(($this->cantidad_de_registros==$cantidad_de_registros) AND (abs($this->monto_acumulado-$importe_total)<static::DIFERENCIA_CONTROL))
        {
            return true;
        }
        return false;
    }
}