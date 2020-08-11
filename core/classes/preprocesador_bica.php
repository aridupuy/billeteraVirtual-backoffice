<?php
class Preprocesador_bica extends Preprocesador 
{
    const PATRON="/^BA([0-9]{6}).txt/";
    const PATRON_FECHA="ymd";
	const POSICION_ENCABEZADO=1; 
	const POSICIONES_FINALES_DESCARTABLES=2;
	
	const CONTROL_POSICION_REGISTRO=1; 
    const CONTROL_POSICION_CANTIDAD_DE_REGISTROS=0; # Posicion del Array
    const CONTROL_POSICION_IMPORTE_TOTAL=1; # Posicion del Array
    const DIFERENCIA_CONTROL=0.10; # Suele venir redondeado

    const DIRECTORIO_FTP='/bicaagil/FTP/CobroDigital/';
    
	public function __construct()
	{

		parent::__construct(self::CODIGO_ENTIDAD_BICA.self::CODIGO_ENTIDAD_BICA_ARCHIVO_RENDICION);
	}
	# Bica no tiene encabezado único. Uso el nombre del archivo
	protected function obtener_encabezado($puntero_fichero)
	{
		return basename($this->archivo);
	}
	protected function controlar()
    {
        $this->developer_log("Controlando total de registros e importe total. ");
        
        $this->puntero_fichero->seek($this->cantidad_de_registros-static::CONTROL_POSICION_REGISTRO+2);
        
        $registro=$this->puntero_fichero->current();
        $registro=str_replace(Registro_bica::CARACTER_INNECESARIO, "", $registro);
        $registro=explode(Registro_bica::DELIMITADOR_ARRAY, $registro);
        $cantidad_de_registros=intval($registro[static::CONTROL_POSICION_CANTIDAD_DE_REGISTROS]);    
        
        $importe_total=$registro[static::CONTROL_POSICION_IMPORTE_TOTAL];
        $importe_total=floatval($importe_total);

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