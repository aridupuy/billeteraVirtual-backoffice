<?php

class Preprocesador_rapipago extends Preprocesador {

    const PATRON = "/^RP([0-9]{6}).1661/";
    const PATRON_ZIPEO = "/^RP([0-9]{8})e1661g([0-9]{4}).zip/";
//    RP20170529e1661g2902
    const PATRON_FECHA = "dmy";
    const NOMBRE_ARCHIVO_FTP = "1661g*.zip";
    const POSICION_ENCABEZADO = 1;
    const POSICIONES_FINALES_DESCARTABLES = 2;
    const CONTROL_POSICION_REGISTRO = 1;
    const CONTROL_POSICION_CANTIDAD_DE_REGISTROS = 8;
    const CONTROL_LONGITUD_CANTIDAD_DE_REGISTROS = 8;
    const CONTROL_POSICION_IMPORTE_TOTAL = 16;
    const CONTROL_LONGITUD_IMPORTE_TOTAL = 18;
    const CONTROL_DECIMALES_IMPORTE_TOTAL = 2;
    const CONTROL_CANTIDAD_DE_REGISTROS_EXCLUYE_EXTRAS = false;
    const DIRECTORIO_FTP = "/ent1661/envia/";
    const PREFIJO_ARCHIVO= "RP";

    public function __construct() {
        parent::__construct(self::CODIGO_ENTIDAD_RAPIPAGO . self::CODIGO_ENTIDAD_RAPIPAGO_ARCHIVO_RENDICION);
    }

    public static function obtener_directorio_ftp($nombre_archivo = null) {
        $directorio = "/ent1661/envia/";
        return $directorio;
    }

    public static function nombre_archivo_interfaz($id_mp, $identificador, Datetime $fecha = null,$comprimido=false) {
        if(!$comprimido)
            return self::PREFIJO_ARCHIVO.$fecha->format("dmy").".1661";
        $archivos=glob(PATH_ARCHIVO.self::PREFIJO_ARCHIVO.$fecha->format("Ymd")."e1661g".$fecha->format("d")."*.zip");
	if(count($archivos)==0){
		$fecha_arch= clone $fecha;
		$fecha_arch->sub(new dateInterval('P1D'));
	  	$archivo='RP'.$fecha_arch->format('dmy').'.1661';
	}
	else{
		$archivo=$archivos[0];
		$archivo=basename($archivo);
	}
//	var_dump($archivo);
        return $archivo;
        
    }
     public static function nombre_herencia($archivo) {
        $fecha= DateTime::createFromFormat("dmy", substr($archivo, 2,3));
//	var_dump($fecha);
        $identificador="";
        return self::nombre_archivo_interfaz($id_mp, $identificador, $fecha, true);
    }

    public static function get_nombre_archivo_guardado($server_file) {
        if (($handle = fopen($server_file, "r")) !== FALSE) {
		$header = fgets($handle);
		$fech=substr($header,28,8);
	//$fecha = new Datetime("now");
		$fecha = dateTime::createFromFormat('Ymd',$fech);
	}
	else
		$fecha = new Datetime("now");
	print_r(PATH_PROCESOS."RP" . $fecha->format('Ymd') ."e". basename($server_file));
        return PATH_PROCESOS."RP" . $fecha->format('Ymd') ."e". basename($server_file);
    }

    public static function obtener_archivos_del_dia(DateTime $fecha,$id_mp) {
        $servidores = Servidor::select(array("id_mp" => 1));
        if (!$servidores and $servidores->rowCount() == 0)
            return false;
        $archivos = array();
        $row=$servidores->fetchRow();
        $conn_id = @ftp_connect($row["host"], $row["port"], $row["timeout"]);
//        var_dump($row);
        if ($conn_id) {
            $user_pass=$password = base64_decode($row['userpass']);
            $login_result = @ftp_login($conn_id, $row["username"], $user_pass);
//            var_dump($login_result);
            if ($login_result) {
                if (ftp_pasv($conn_id, true) == true) {
                    if (self::ACTIVAR_DEBUG) {
                        developer_log('FTP: Cambio a Modo Pasivo realizado correctamente.');
                    }
                    $archivos_zip = ftp_nlist($conn_id, self::DIRECTORIO_FTP);
                    foreach ($archivos_zip as $key=>$archivo){
                        $archivos[$key]= str_replace("/ent1661/envia/", "", $archivo);
                    }
                }
                else{
                    if (self::ACTIVAR_DEBUG) {
                        developer_log('FTP: Error al Realizar el Cambio a Modo Pasivo.');
                    }
                }
            }
            ftp_close($conn_id);
        }
        
//        var_dump($archivos);
        return $archivos;
    }
    protected function pre_ejecucion($archivo_comprimido)
    {
        if(preg_match(Preprocesador_rapipago::PATRON_ZIPEO,basename($archivo_comprimido))){
            $zip = new ZipArchive;
            $zip->open( $archivo_comprimido ); 
            $zip->extractTo(dirname($archivo_comprimido)); 
                       
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $archivo_sin_comprimir = PATH_PROCESOS.$zip->getNameIndex($i);
      
           }
          $zip->close();
            if(is_file($archivo_sin_comprimir)){
//                    rename(PATH_PROCESOS.$archivo_sin_comprimir, PATH_ARCHIVO.$archivo_sin_comprimir);
                    $this->archivo_sin_comprimir=$archivo_sin_comprimir;
                    if(self::ACTIVAR_DEBUG){ developer_log('Archivo descomprimido correctamente.'); }
                    if(self::ACTIVAR_DEBUG){ developer_log($archivo_sin_comprimir); }
                    return $archivo_sin_comprimir;
            }
            if(self::ACTIVAR_DEBUG){ developer_log('Ha ocurrido un error al descomprimir el archivo.'); }
            return false;
        }
        else
            return $archivo_comprimido;
    }
    public function borrar_archivo_descomprimido(){
        if(unlink($this->archivo_sin_comprimir)){
            return true;
        }
        return false;
    }
    public static function nombrar_csvfile($archivo){
//        if($this->archivo_sin_comprimir==null)
            return basename($archivo);
//        return self::nombre_archivo_interfaz(Mp::RAPIPAGO, "",null,true);
    //    return basename($this->archivo_sin_comprimir);
    }
}
