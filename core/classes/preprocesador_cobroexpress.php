<?php
class Preprocesador_cobroexpress extends Preprocesador 
{
	const PATRON="/^cecd([0-9]{6}).txt/";
	const PATRON_FECHA="ymd";
	const POSICION_ENCABEZADO=1;
	const POSICIONES_FINALES_DESCARTABLES=2; # Contar lineas que tienen solo un salto de carro
	
	const CONTROL_POSICION_REGISTRO=1; 
    const CONTROL_POSICION_CANTIDAD_DE_REGISTROS=1;
    const CONTROL_LONGITUD_CANTIDAD_DE_REGISTROS=8;
    const CONTROL_POSICION_IMPORTE_TOTAL=9;
    const CONTROL_LONGITUD_IMPORTE_TOTAL=15;
    const CONTROL_DECIMALES_IMPORTE_TOTAL=2;
    const CONTROL_CANTIDAD_DE_REGISTROS_EXCLUYE_EXTRAS=false;

    const DIRECTORIO_FTP='/sftp/sftpcobrodigital/';
    
    
	public function __construct()
	{

		parent::__construct(self::CODIGO_ENTIDAD_COBROEXPRESS.self::CODIGO_ENTIDAD_COBROEXPRESS_ARCHIVO_RENDICION);
	}
}