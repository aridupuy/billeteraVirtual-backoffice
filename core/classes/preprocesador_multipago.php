<?php
class Preprocesador_multipago extends Preprocesador 
{
	const PATRON="/^COBD([0-9]{4}).TXT/";
	const PATRON_FECHA="dm";
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

		parent::__construct(self::CODIGO_ENTIDAD_MULTIPAGO.self::CODIGO_ENTIDAD_MULTIPAGO_ARCHIVO_RENDICION);
	}
	protected function verificar_inexistencia_archivo($archivo)
	{
		$nombre_nuevo=$this->nombrar_csvfile($archivo);
		if(file_exists(PATH_ARCHIVO.$nombre_nuevo)){
			$this->developer_log("El fichero '".$nombre_nuevo."' ya existe en el directorio '".PATH_ARCHIVO."'(1)");
			return false;
		}
		return true;
	}
	protected function archivar()
	{
		$nombre_nuevo=$this->nombrar_csvfile($this->archivo);
		$this->developer_log("Archivando fichero. ");
		if(file_exists(PATH_ARCHIVO.$nombre_nuevo)){
			$this->developer_log("El fichero '".$nombre_nuevo."' ya existe en el directorio '".PATH_ARCHIVO."'(3)");
			return false;
		}
		if(rename($this->archivo, PATH_ARCHIVO.$nombre_nuevo)){
			return true;
		}
		return false;
	}
	public static function nombrar_csvfile($archivo)
	{
		# Como MultiPago envia los archivos del dia anterior
		# debo renombrar segun el anio del dia anterior
		# Bug: Si quiero procesar un archivo de otro anio (Y mayor a un dia de retraso)
		$fecha=new DateTime('yesterday');
		return basename($archivo).'.'.$fecha->format('Y');
	}
	public static function obtener_directorio_ftp($nombre_archivo=null)
    {
    	if($nombre_archivo==null) {
    		return false;
    	}
    	else{
    		$mes=substr($nombre_archivo, 6,2);
    	}
    	$fecha=new DateTime('yesterday');
    	$anio=$fecha->format('Y');
    	# Como MultiPago envia los archivos del dia anterior
		# debo renombrar segun el anio del dia anterior
		# Bug: Si quiero procesar un archivo atrasado de otro anio (Y mayor a un dia de retraso)
    	$carpeta=$anio.'-'.$mes;
    	return '/'.$carpeta.'/';
    }
}
