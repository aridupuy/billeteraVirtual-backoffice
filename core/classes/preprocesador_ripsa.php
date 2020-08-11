<?php
class Preprocesador_ripsa extends Preprocesador 
{
    const PATRON="/^RI([0-9]{6}).TXT/";
    const PATRON_FECHA="ymd";
	const POSICION_ENCABEZADO=1;
	const POSICIONES_FINALES_DESCARTABLES=0;
	
	const CONTROL_POSICION_REGISTRO=0;
    const CONTROL_POSICION_CANTIDAD_DE_REGISTROS=1; # Array
    const CONTROL_POSICION_IMPORTE_TOTAL=2; # Array
    const CONTROL_DECIMALES_IMPORTE_TOTAL=2;
    const DELIMITADOR_ARRAY=";";

	public function __construct()
	{

		parent::__construct(self::CODIGO_ENTIDAD_RIPSA.self::CODIGO_ENTIDAD_RIPSA_ARCHIVO_RENDICION);
	}
	protected function controlar()
    {
    	if($this->cantidad_de_registros===0){
    		$this->developer_log("No hay nada que controlar");
    		return true;
    	}
        $this->developer_log("Controlando total de registros e importe total. ");
        
        $this->puntero_fichero->seek(0);
        
        $registro=$this->puntero_fichero->current();
        $registro=explode(self::DELIMITADOR_ARRAY, $registro);
        $cantidad_de_registros=intval($registro[static::CONTROL_POSICION_CANTIDAD_DE_REGISTROS]);    
        
        $importe_total=$registro[static::CONTROL_POSICION_IMPORTE_TOTAL];
        $importe_total=intval($importe_total);
        $aux=pow(10, static::CONTROL_DECIMALES_IMPORTE_TOTAL);
        $importe_total=$importe_total/($aux);

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
    protected function obtener_encabezado($puntero_fichero)
	{
		if($this->cantidad_de_registros===0){
			$encabezado=basename($this->archivo);
		}
		else{
			$encabezado=parent::obtener_encabezado($puntero_fichero);
		}
		return $encabezado;
	}
}