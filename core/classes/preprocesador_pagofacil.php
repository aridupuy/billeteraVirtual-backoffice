<?php
class Preprocesador_pagofacil extends Preprocesador 
{
	const PATRON="/^PF([0-9]{6}).900/";
	const PATRON_FECHA="dmy";
	const POSICION_ENCABEZADO=1;
	const CANTIDAD_DE_LINEAS_ENCABEZADO=2;
	const POSICIONES_FINALES_DESCARTABLES=3; # Contar lineas que tienen solo un salto de carro
	const CANTIDAD_DE_LINEAS_POR_REGISTRO=3;
	const DESCARTAR_REGISTROS_CADA=500; # Cada 500 registros hay un par de registro de encabezado
	const LINEAS_DE_DESCARTE=2; # Se descartan dos lineas cada 500 registros

	const CONTROL_POSICION_REGISTRO=1; 
    const CONTROL_POSICION_CANTIDAD_DE_REGISTROS=15;
    const CONTROL_LONGITUD_CANTIDAD_DE_REGISTROS=7;
    const CONTROL_POSICION_IMPORTE_TOTAL=22;
    const CONTROL_LONGITUD_IMPORTE_TOTAL=12;
    const CONTROL_DECIMALES_IMPORTE_TOTAL=2;
    private $locales_no_encontrados=array();
    public function __construct()
	{
		parent::__construct(self::CODIGO_ENTIDAD_PAGOFACIL.self::CODIGO_ENTIDAD_PAGOFACIL_ARCHIVO_RENDICION);
	}
	protected function obtener_registro($puntero_fichero)
	{
		# Sobrecarga del método para descartar lineas
		$linea_actual=$puntero_fichero->key();

		$numero_de_paquete=floor($linea_actual/(self::CANTIDAD_DE_LINEAS_POR_REGISTRO*self::DESCARTAR_REGISTROS_CADA));
		$resultado=$linea_actual-self::CANTIDAD_DE_LINEAS_ENCABEZADO-(2*$numero_de_paquete-2);

		if($resultado>0 AND($resultado % (self::DESCARTAR_REGISTROS_CADA*self::CANTIDAD_DE_LINEAS_POR_REGISTRO))===0){
			for ($i=0; $i < self::LINEAS_DE_DESCARTE; $i++) { 
				$linea_actual=$puntero_fichero->key();
				$this->developer_log(($linea_actual+1).' | Descartando línea: '.$puntero_fichero->current());
				$puntero_fichero->next();	
			}
		}
		$linea='';
		for ($i=0; $i < static::CANTIDAD_DE_LINEAS_POR_REGISTRO; $i++) { 
			$linea.= $puntero_fichero->current();
			$puntero_fichero->next();
		}
		return $linea;
	}
	protected function controlar()
    {
        $this->developer_log("Controlando total de registros e importe total. ");
       	$this->puntero_fichero->seek($this->get_cantidad_de_lineas($this->puntero_fichero)-static::CONTROL_POSICION_REGISTRO);
    	$registro=$this->puntero_fichero->current();  
        $cantidad_de_registros=intval(substr($registro, static::CONTROL_POSICION_CANTIDAD_DE_REGISTROS,static::CONTROL_LONGITUD_CANTIDAD_DE_REGISTROS));

        $importe_total=substr($registro, static::CONTROL_POSICION_IMPORTE_TOTAL,static::CONTROL_LONGITUD_IMPORTE_TOTAL);
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
    
  //ESTOS METODOS NO ESTABAN EN TIO PERO EN CLYDE SI -- EN CLYDE SE ROMPE PERO EN TIO FUNCIONA -- POR LAS DUDAS VOY A COMENTARLOS
      public function procesar_registro(Registro $registro, Control $control) {
        $respuesta = parent::procesar_registro($registro, $control);
        $this->locales_no_encontrados[]=$registro->locales_no_encontrados();
        return $respuesta;
    }
    protected function post_ejecucion($archivo) {
        $resultado = parent::post_ejecucion($archivo);
        Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, "sistemas@cobrodigital.com", "locales pago facil sin identificar", "Los codigos de los locales son los siguientes, (en json): ".json_encode($this->locales_no_encontrados));
        return $resultado;
    }
}
