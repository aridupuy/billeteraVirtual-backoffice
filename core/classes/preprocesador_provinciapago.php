<?php
class Preprocesador_provinciapago extends Preprocesador 
{
	const PATRON="/^PP_CobroDigital([0-9]{2,3})([0-9]{8}).zip/";
	const PATRON_FECHA="Ymd";
	const POSICION_ENCABEZADO=1;
	const POSICIONES_FINALES_DESCARTABLES=0; # Contar lineas que tienen solo un salto de carro
	
	const CONTROL_POSICION_REGISTRO=0; 
    const CONTROL_POSICION_CANTIDAD_DE_REGISTROS=6;
    const CONTROL_LONGITUD_CANTIDAD_DE_REGISTROS=6;
    const CONTROL_POSICION_IMPORTE_TOTAL=12;
    const CONTROL_LONGITUD_IMPORTE_TOTAL=10;
    const CONTROL_DECIMALES_IMPORTE_TOTAL=2;
    const CONTROL_CANTIDAD_DE_REGISTROS_EXCLUYE_EXTRAS=false;

	public function __construct()
	{

		parent::__construct(self::CODIGO_ENTIDAD_PROVINCIAPAGO.self::CODIGO_ENTIDAD_PROVINCIAPAGO_ARCHIVO_RENDICION);
	}
	protected function pre_ejecucion($archivo_comprimido)
	{
		$zip = new ZipArchive;
		$zip->open( $archivo_comprimido ); 
		$zip->extractTo(dirname($archivo_comprimido)); 
		$zip->close();
		$archivo_sin_comprimir=dirname($archivo_comprimido).'/'.substr(basename($archivo_comprimido), 0,-4).'.txt';
		if(is_file($archivo_sin_comprimir)){
			if(self::ACTIVAR_DEBUG){ developer_log('Archivo descomprimido correctamente.'); }
			if(self::ACTIVAR_DEBUG){ developer_log($archivo_sin_comprimir); }
			return $archivo_sin_comprimir;
		}
		if(self::ACTIVAR_DEBUG){ developer_log('Ha ocurrido un error al descomprimir el archivo.'); }
		return false;
	}
	protected function post_ejecucion($archivo_comprimido)
	{
		$archivo_sin_comprimir=dirname($archivo_comprimido).'/'.substr(basename($archivo_comprimido), 0,-4).'.txt';
		if(is_file($archivo_sin_comprimir)){
			if(unlink($archivo_sin_comprimir)){
				if(self::ACTIVAR_DEBUG){ developer_log('Archivo descomprimido eliminado correctamente.'); }
				return true;
			}
		}
		if(self::ACTIVAR_DEBUG){ developer_log('Ha ocurrido un error al eliminar el archivo descomprimido.'); }
		return false;

	}
	public static function nombre_archivo_interfaz($id_mp, $identificador, Datetime $fecha=null)
    {
    	unset($id_mp);
        if($fecha===null) $fecha=new Datetime('now');
        $nombre_archivo=substr(static::PATRON, 2,15);
        $nombre_archivo.=$identificador;
        $nombre_archivo.=$fecha->format(static::PATRON_FECHA);
        $nombre_archivo.=substr(static::PATRON, -5,4);;
        return $nombre_archivo;
    }
}