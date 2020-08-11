<?php
// namespace Classes;
# Arreglar trycatches
class Gestor_de_disco
{
    const ACTIVAR_DEBUG=true;
    private $path_home=PATH_CDEXPORTS;
    const MAXIMO_PESO_PERMITIDO=100000000;
    const MINIMO_PESO_PERMITIDO=10;
    const TIPO_NUMERO='n';
    const FORMATO_NUMERO_ENTERO='0'; 
    const FORMATO_FECHA='dd/mm/yyyy'; 
    const FORMATO_PLATA='#,##0.00'; 
    const TIPO_TEXTO='s';
    const FORMATO_TEXTO='@'; 
    const FORMATO_GENERAL='General'; 
	public function exportar_csv($path,$filename,$filas,$delimiter=",")
    {
        # Recibe un array de arrays o un recordset
    	if(!count($filas)) return false; 
		$carpeta=$this->path_home.$path;
		$fichero=$carpeta.$filename;

		try 
		{
			$handler=fopen($fichero,'w');
            if(!$handler) return false;
			foreach ($filas as $fila):
                if(!fputcsv($handler, $fila,$delimiter)) {
                    fclose($handler);
                    return false;
                    }
			endforeach;
			fclose($handler);
			return true;
		} 
		catch (Exception $e) {
			return false;
			
		}	
    }
    public function exportar_xls($path,$filename,$filas,$tipos=null, $formatos=null,$no_formatear_primera_fila=false)
    {
        require_once PATH_PUBLIC."PHPExcel/Classes/PHPExcel/IOFactory.php";

        $archivo=$this->path_home.$path.$filename;
        if (file_exists($archivo)) {
            Gestor_de_log::set('El archivo ya existe.',0);
            return false;
        }
         try {
            $objeto = new PHPExcel();

            $objeto->getProperties()
            ->setCreator("cobrodigital.com")
            ->setLastModifiedBy("cobrodigital.com")
            ->setTitle("Documento Excel de cobrodigital.com")
            ->setSubject("Documento Excel de cobrodigital.com")
            ->setDescription("Este archivo ha sido exportado desde cobrodigital.com");
            $objeto->getActiveSheet()->setTitle('cobrodigital.com');
            foreach ($filas as $row => $array) {
                $numero_fila=$row+1;
                $col='A';
                foreach ($array as $columna => $valor) {
                    ################ TIPO##############
                    unset($el_tipo);
                    if($tipos!==null AND isset($tipos[$columna])){
                        if($numero_fila==1 AND $no_formatear_primera_fila)
                            $el_tipo=self::TIPO_TEXTO;
                        else
                            $el_tipo=$tipos[$columna];
                    }
                    else{
                        $el_tipo=self::TIPO_TEXTO;
                    }
                    ################ FORMATO ##############
                    unset($el_formato);
                    if($formatos!==null AND isset($formatos[$columna])){
                        if($numero_fila==1 AND $no_formatear_primera_fila)
                            $el_formato=self::FORMATO_GENERAL;   
                        else
                            $el_formato=$formatos[$columna];   
                    }
                    else{
                        $el_formato=self::FORMATO_GENERAL;   
                    }
                    ########################################
                    if($array[$columna]!==null AND $el_formato==self::FORMATO_FECHA){
                        if(!($fecha_temp=DateTime::createFromFormat('d/m/Y', $array[$columna]))){
                            if(self::ACTIVAR_DEBUG) developer_log('El formato de la fecha no es correcto.');
                            return false;
                        }
                        $el_valor=PHPExcel_Shared_Date::PHPToExcel($fecha_temp);
                    }
                    else $el_valor=$array[$columna];
                    $la_celda=$col.$numero_fila;
                    
                    if($el_valor==null) $el_tipo=self::TIPO_TEXTO; # Para que no ponga un 0 en los tipos numericos que son null
//                    elseif(self::ACTIVAR_DEBUG) developer_log($la_celda.': '.$el_valor.' ['.$el_tipo.']['.$el_formato.']');   

                    $objeto->getActiveSheet()->setCellValueExplicit($la_celda, $el_valor,$el_tipo);
                    $objeto->getActiveSheet()->getStyle($la_celda)->getNumberFormat()->setFormatCode($el_formato);
                    ########################################
                    $col++;

                }
            }

            $objWriter = PHPExcel_IOFactory::createWriter($objeto, 'Excel2007');
            $objWriter->save($archivo);

            return true;

        } catch (Exception $e) {
            developer_log($e->getMessage());
            return false;
        } 
    }
    //public function importar_csv($path,$filename)
    public function importar_csv($path, $filename,$trim=true){
        $arq = file($path.$filename);
            $csv = array_map(function($v) {
                if($trim)
                    $v = trim($v);
                return str_getcsv($v, ";"); //REPLACE comma with your separator(In PT_BR is semicollon (;)
            }, $arq);
        //print_r($csv);
        return $csv;
        # Recibe un un CSV y retorna un array de arrays
        # Inversa de exportar_csv   
//        return $this->importar_xls($path,$filename);
    }
    
    public function importar_xls($path,$filename)
    {
        # Esta funcion esta muy lenta. rehacer.
        require_once PATH_PUBLIC."PHPExcel/Classes/PHPExcel/IOFactory.php";
        $archivo=$path.$filename;
	//var_dump($archivo);
        if (!file_exists($archivo)) {
            developer_log("el archivo no existe.");
            return false;
        }
        try {
            if(self::ACTIVAR_DEBUG) {
                developer_log('Comienza importacion.');
                $tiempo_inicio=microtime(true);
            }
            if(!$objeto = PHPExcel_IOFactory::load($archivo)) return false;
            if(!$worksheet = $objeto->getActiveSheet()) return false;
            $maxCell = $worksheet->getHighestRowAndColumn();
            $data = $worksheet->rangeToArray('A1:' . $maxCell['column'] . $maxCell['row']);
            $fila=1;
            $mayor_fila=0;
            $mayor_celda=ord('A');
            foreach ($data as $row) {
                $celda=ord('A');
                foreach ($row as $cell) {
                        if(isset($cell)){
                            if($fila>$mayor_fila) {
                                $mayor_fila=$fila;
                            }
                            if($celda>$mayor_celda) {
                                $mayor_celda=$celda;
                            }
                        }
                    $celda++;
                }
            $fila++;
                
            }
            unset($data);
            $mayor_celda=chr($mayor_celda);
            $data = $worksheet->rangeToArray('A1:' . $mayor_celda . $mayor_fila);
            $fila=0;
            $matriz=array();
            foreach ($data as $row) {
                $matriz[$fila]=array();
                foreach ($row as $cell) {
                        $matriz[$fila][]=$cell;
                }
                $fila++;
            }
            if(self::ACTIVAR_DEBUG) {
                $tiempo=microtime(true)-$tiempo_inicio;
                developer_log('Duracion: '.$tiempo.' microsegundos.');
            }
            return $matriz;    
        } catch (Exception $e) {
            developer_log($e->getMessage());
            return false;
        }
    }
    public function descargar($path_local,$file_local,$url_remota)
    {
    	try {
    		$archivo=file_get_contents($url_remota);
    		if(!$archivo) {
                if(self::ACTIVAR_DEBUG)
                    developer_log("Ha ocurrido un error al descargar el archivo remoto: '".$url_remota."'");
                return false;
            }
    		$result=file_put_contents($this->path_home.$path_local.$file_local, $archivo);	
            if(self::ACTIVAR_DEBUG)
                    developer_log("Archivo remoto correctamente descargado: '".$url_remota."'");
    		return $result;
    	} catch (Exception $e) {
            if(self::ACTIVAR_DEBUG)
                    developer_log("Ha ocurrido un error al descargar el archivo remoto: '".$url_remota."'");
    		return false;
    	}
    }
    public function comprimir_zip($path,$filename,$archivos)
    {
        try {
            $zip = new ZipArchive();
            if ($zip->open($this->path_home.$path.$filename, ZipArchive::CREATE)!==TRUE) return false;
            foreach ($archivos as $archivo):
//                developer_log($this->path_home.$path.$archivo);
                if(!$zip->addFile($this->path_home.$path.$archivo,$archivo)) {
                    $zip->close();
                if(self::ACTIVAR_DEBUG) 
                    developer_log("Ha ocurrido un error al agregar el archivo al comprimido: '".$this->path_home.$path.$filename."' ");
                return false;
                }   
            endforeach;
            $zip->close();
            if(self::ACTIVAR_DEBUG) 
                developer_log("Archivo ZIP creado correctamente: '".$this->path_home.$path.$filename."' ");
            return true;
        } catch (Exception $e) {
            if(self::ACTIVAR_DEBUG) 
                developer_log ($e->getMessage ());
            if(self::ACTIVAR_DEBUG) 
                developer_log("Ha ocurrido un error al crear el archivo comprimido: '".$this->path_home.$path.$filename."' ");
            return false;	
        }
    }
    public function borrar($path,$archivos)
    {

    	try {
    		foreach ($archivos as $archivo):
    			if(!unlink($this->path_home.$path.$archivo)){
                    if(self::ACTIVAR_DEBUG) 
                        developer_log("Ha ocurrido un error al borrar el archivo: '".$this->path_home.$path.$archivo."' ");
                    return false;
                }
    		endforeach;	
            if(self::ACTIVAR_DEBUG) 
                        developer_log("Archivos correctamente borrados");
    		return true;
    	} catch (Exception $e) {
            if(self::ACTIVAR_DEBUG) 
                        developer_log("Ha ocurrido un error al borrar el archivo: '".$this->path_home.$path.$archivo."' ");
    		return false;
    	}
    }
    public function ocultar($path, $archivo)
    {
        # El usuario cree que borro el archivo
        if(rename($this->path_home.$path.$archivo, $this->path_home.$path.$archivo.EXTENSION_ARCHIVO_DE_SISTEMA)){
            return true;
        }
        return false;
    }
    public function mover_archivo_subido($nombre_temporal, $path, $nombre, $forzar_escritura=false)
    {
        if ($forzar_escritura OR !file_exists($path.$nombre)){
            if(move_uploaded_file($nombre_temporal, $path.$nombre)){
                return $nombre;
            }
            else
                return false;
        }
        else{
            $posicion=strrpos($nombre, '.');
            $principcio=substr($nombre, 0,$posicion);
            $final=substr($nombre, $posicion,strlen($nombre)-$posicion);
            $fecha=date('Ymdhis');
            $nombre_archivo=$principcio.'('.$fecha.')'.$final;
            return $this->mover_archivo_subido($nombre_temporal,$path,$nombre_archivo);
        }
    }
    public static function crear_carpeta($path)
    {        developer_log($path);
        if(! is_dir($path))
            return mkdir($path);
        return false;
    }
    public static function crear_archivo($path,$filename,$content,$forzar_escritura=false)
    {
        if(is_file($path.$filename) AND !$forzar_escritura)
            return false;
        try {
            if($fp = fopen($path.$filename, 'w')){
                if(fwrite($fp, $content)){
                    if(fclose($fp)) 
                        return true;
                }
            }

        } catch (Exception $e) {
            if(self::ACTIVAR_DEBUG) developer_log($e->getMessage(),0);
            return false;
            
        }
        return false;
    }
    public static function escanear_directorio($directorio, $escanear_subdirectorios=true)
    {
        if(!is_dir($directorio)){
            return false;
        }
        $archivos= @scandir($directorio,SCANDIR_SORT_ASCENDING);
        if(!$archivos){
            return array();
        }
        $array = array();
        unset($archivos[0]);
        unset($archivos[1]);
        foreach ($archivos as $nro=>$archivo){
            $datos=self::informacion_archivo($directorio, $archivo);
            if($escanear_subdirectorios OR $datos['tipo']!='directory'){
                $array[$nro]=array('nombre'=>$archivo,'peso'=>$datos['peso'],'fecha'=>$datos['fecha'],'tipo'=>$datos['tipo'],'extension'=>$datos['extension']);
            }
        }
        #necesario si no salen los puntos del directorio;
        return $array;
    }
    public static function informacion_archivo($directorio, $file)
    {
            $archivo=$directorio."/".$file;
            $array = array();
            if(!file_exists($archivo))
                return false;
            $array['peso']= filesize($archivo);
            $array['fecha']=filectime($archivo);
            $fileinfo=finfo_open(FILEINFO_MIME_TYPE);
            $array['tipo']= finfo_file ($fileinfo,$archivo);
            $partes=  explode(".", $archivo);
            $array['extension']=  end($partes);
            return $array;
    }
}

