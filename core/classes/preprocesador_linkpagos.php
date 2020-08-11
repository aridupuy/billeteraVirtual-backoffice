<?php
class Preprocesador_linkpagos extends Preprocesador 
{
	const PATRON="/^0AC8([0-9]{4})/";
	const PATRON_FECHA="md";
	const POSICION_ENCABEZADO=1;
	const POSICIONES_FINALES_DESCARTABLES=2; # Contar lineas que tienen solo un salto de carro
	
	const CONTROL_POSICION_REGISTRO=1;
    const CONTROL_POSICION_CANTIDAD_DE_REGISTROS=1;
    const CONTROL_LONGITUD_CANTIDAD_DE_REGISTROS=6;
    const CONTROL_POSICION_IMPORTE_TOTAL=7;
    const CONTROL_LONGITUD_IMPORTE_TOTAL=16;
    const CONTROL_DECIMALES_IMPORTE_TOTAL=2;
	const CONTROL_CANTIDAD_DE_REGISTROS_EXCLUYE_EXTRAS=true;

	public function __construct()
	{

		parent::__construct(self::CODIGO_ENTIDAD_LINKPAGOS.self::CODIGO_ENTIDAD_LINKPAGOS_ARCHIVO_RENDICION);
	}
	protected function obtener_barcode(Registro $registro)
	{
        $pmc19=$registro->obtener_codigo_electronico();
        $pmcdeuda=$registro->obtener_pmcdeuda();
        $monto=$registro->obtener_monto_numerico();
        $recordset=Barcode::select_pmc_linkpagos($pmc19,$pmcdeuda,$monto);
        //error_log(json_encode($recordset));
	if($recordset AND $recordset->rowCount()==1){
            $row=$recordset->FetchRow();
            $barcode=new Barcode($row);
			return $barcode;
        }
        elseif($recordset AND $recordset->RowCount()>1){
        	$this->developer_log($this->nlinea." | Hay más de un Barcode que coincide.");
        	return false;
        }
        $this->developer_log($this->nlinea." | No ha sido posible obtener el Barcode. ");
		return false;
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
		# Link Pagos envia el archivo a procesar en el propio dia
		# por eso debe utilizarse el dia actual. Ver preprocesador_multipago::nombrar_csvfile()
		# Bug: Si quiero procesar un archivo atrasado de otro anio
		return basename($archivo);//.'.'.date('Y');
	}
	protected function obtener_barcode_para_ufos(Registro $registro)
    {
        return "UFO: ".$registro->obtener_codigo_electronico();
    }
    public static function obtener_directorio_ftp($archivo=false){
        return "/home/handsolo/PRODcdi/boffice/sabana/linkpagos/";
    }
}
