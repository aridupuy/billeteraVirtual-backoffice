<?php

abstract class Carga_padron {
    
    const ARCHIVO_PADRON = "SINapellidoNombreDenominacion.zip";
    const URL_PADRON = "http://www.afip.gob.ar/genericos/cInscripcion/archivos/SINapellidoNombreDenominacion.zip";
    const EMISOR = "info@cobrodigital.com";
    const DESTINATARIO = "harzamendia@cobrodigital.com";
    private function __construct() {
        throw new Exception("Se debe usar el metodo factory para instanciar");
    }
    
      public function cargar($nombre_archivo){
//	developer_log(get_class($nombre_archivo));
        $archivo = $nombre_archivo;
        $connection = ssh2_connect(DATABASE_HOST, 22);
        
        if(!$this->conectar_db($connection)){
            $this->return_error("Error al autenticar en la base");
        }
        developer_log('Autenticado correctamente');
        
        if (!$this->eliminar_archivos_viejos($connection, substr($archivo, 0,3))) {
            $this->return_error("error al eliminar el archivo");
        }
        developer_log('Archivo eliminado');
        
        if (!$this->enviar_archivo($connection, $archivo)) {
            $this->return_error("error al enviar el archivo");
        }
        developer_log('Archivo enviado');
        
        if (!$this->unzip_archivo($connection, $archivo)) {
            $this->return_error("error al ejecutar unzip");
        }
        developer_log('Archivo descomprimido');
        
        $archivo_descomprimido = str_replace("zip", "txt", basename($archivo));
        if (!$this->dando_permisos($connection, $archivo_descomprimido)) {
            $this->return_error("error al ejecutar sudo chmod 777");
        }
        developer_log('Permisos otorgados');
        
        if (!$this->truncar_tablas($connection)) {
            $this->return_error("error al truncar tablas");
        }
        developer_log('Tablas truncadas');
        
        if (!$this->ejecutar_copy($connection, $archivo_descomprimido)) {
            $this->return_error("error al ejecutar copy");
        }
        developer_log('Archivo cargado');
        
        if (!$this->insertar_sujeto_retencion($connection, $archivo_descomprimido)) {
            $this->return_error("error al insertar sujeto retencion");
        }
        developer_log('Sujeto retencion cargado');
        
        if (!$this->insertar_alicuotas($connection, $archivo_descomprimido)) {
            $this->return_error("error al insertar alicuotas");
        }
        developer_log('Alicuotas cargadas');
    }
    
      public function precargar($nombre_archivo){
//	developer_log(get_class($nombre_archivo));
        $archivo = $nombre_archivo;
        $connection = ssh2_connect(DATABASE_HOST, 22);
        $ok = true;
        
        if(!$this->conectar_db($connection)){
            $this->return_error("Error al autenticar en la base");
            $ok = false;
        }
        developer_log('Autenticado correctamente');
        
	if (!$this->enviar_archivo($connection, $archivo)) {
            $this->return_error("error al enviar el archivo $archivo");
//	    $ok = false;
	}
        developer_log('Archivo enviado');
        
        if (!$this->unzip_archivo($connection, $archivo)) {
            $this->return_error("error al ejecutar unzip del archivo $archivo");
	    $ok = false;
        }
        developer_log('Archivo descomprimido');
        
        $archivo_descomprimido = str_replace("zip", "txt", basename($archivo));
        if (!$this->dando_permisos($connection, $archivo_descomprimido)) {
            $this->return_error("error al ejecutar chmod 777 del archivo $archivo_descomprimido");
            $ok = false;
        }
        developer_log('Permisos otorgados');
        
        if (!$this->truncar_tablas($connection)) {
            $this->return_error("error al truncar tablas");
            $ok = false;
        }
        developer_log('Tablas truncadas');
        
        if (!$this->ejecutar_copy($connection, $archivo_descomprimido)) {
            $this->return_error("error al ejecutar copy de archivo $archivo_descomprimido");
            $ok = false;
        }
        developer_log('Archivo cargado');
        
        if(!$ok){
            Gestor_de_correo::enviar(self::EMISOR, self::DESTINATARIO, "Error al realizar la precarga del padron", "Error al realizar la precarga del padron");
        }
	return $ok;
    }
    
    public function actualizar_datos_mensuales(){
        Mp::full_stop();
            $connection = ssh2_connect(DATABASE_HOST, 22);
        
            if(!$this->conectar_db($connection)){
                $this->return_error("Error al autenticar en la base");
            }
            developer_log('Autenticado correctamente');

            if (!$this->drop_backup($connection)){
                $this->return_error("error al dropear el backup");
            }
            developer_log('Backup blanqueado');
            
            if (!$this->ejecutar_backup($connection)){
                $this->return_error("error al ejecutar el backup");
            }
            developer_log('Backup realizado');

            if (!$this->truncar_tabla_sujeto_retencion($connection)) {
                $this->return_error("error al truncar sujeto retencion");
            }
            developer_log('Sujeto retencion truncado');

            if (!$this->insertar_sujeto_retencion($connection)) {
                $this->return_error("error al insertar sujeto retencion");
            }
            developer_log('Sujeto retencion cargado');

            if(!$this->cargado_correctamente()){
                $this->restaurar_backup($connection);
                $this->return_error("error en la insercion de sujeto de retencion");
                Gestor_de_correo::enviar(self::EMISOR, self::DESTINATARIO, "Error al actualizar el padron", "Error al actualizar el padron");
                return false;
            }

            if (!$this->insertar_alicuotas($connection)) {
                $this->return_error("error al insertar alicuotas");
            }
            developer_log('Alicuotas cargadas');
        
        Mp::full_speed();
    }

    public function descargar(){
        $connection = ssh2_connect(DATABASE_HOST, 22);
        $fail = false;
        
        if(!$this->conectar_db($connection)){
            $fail = true;
            $this->return_error("Error al autenticar en la base");
        }else{
            developer_log('Autenticado correctamente');
        }
        
        if($fail or !$this->descargar_archivo($connection)){
            $fail = true;
            $this->return_error("Error al descargar el archivo ". self::ARCHIVO_PADRON);
        }else{
            sleep(5);
 //           if(filesize('/home/heisenberg/'.self::ARCHIVO_PADRON) > 1000000){
                developer_log('Archivo descargado');
 //           }else{
 //               $fail = true;
 //               $this->return_error("El archivo se descargo con errores ". self::ARCHIVO_PADRON);
 //           }
        }

        if($fail or !$this->precargar(self::ARCHIVO_PADRON)){
            $fail = true;
            $this->return_error("Error al precargar la tabla");
        }else{
            developer_log('Archivo precargado');
        }
        
        if($fail or !$this->renombrar_archivo($connection, self::ARCHIVO_PADRON)){
            $fail = true;
            $this->return_error("Error al renombrar el archivo");
        }else{
            developer_log('Archivo renombrado');
        }
        
        if($fail){
            Gestor_de_correo::enviar(self::EMISOR, self::DESTINATARIO, "Error al realizar la descarga del padron", "Error al realizar la descarga del padron");
        }
        
    }
    private function conectar_db($connection){
        return ssh2_auth_password($connection, DATABASE_USER_SO, "WklmH34$");
//        return ssh2_auth_password($connection, DATABASE_USER_SO, "sihay2esque1esta"); //tuvok
    }
    
    private function eliminar_archivos_viejos($connection, $archivo_rm){
        return ssh2_exec($connection, "rm -f $archivo_rm"."*");
    }
    
    private function enviar_archivo($connection, $archivo){
        return ssh2_scp_send($connection, PATH_CDEXPORTS.$archivo, '/home/'.DATABASE_USER_SO.'/' . basename($archivo), 0644);
    }
    
    private function unzip_archivo($connection, $archivo){
        $archivo_descomprimido = str_replace("zip", "txt", basename($archivo));
        $stream = ssh2_exec($connection, "unzip -p " . basename($archivo) . " > $archivo_descomprimido");
        return $this->resolve_stream($stream);
    }
    
    private function dando_permisos($connection, $archivo){
        $stream = ssh2_exec($connection, "chmod 777 $archivo");
        return $this->resolve_stream($stream);
    }
    
    private function cargado_correctamente(){
        $recordset = Sujeto_retencion::select();
	developer_log('Cantidad de registros cargados: '. $recordset->rowCount());
        return $recordset->rowCount() > 0;
    }
    
    protected abstract function truncar_tablas($connection);
    
    protected abstract  function ejecutar_copy($connection, $archivo);
    
    protected abstract function insertar_sujeto_retencion($connection);
    
    protected abstract function truncar_tabla_sujeto_retencion($connection);
    
    protected abstract function insertar_alicuotas($connection);
    
    protected abstract function ejecutar_backup($connection);

    private function return_error($mensaje){
        Gestor_de_log::set($mensaje, 0);
        return $mensaje;
    }

    private function renombrar_archivo($connection, $archivo){
        $archivo_descomprimido = str_replace("zip", "txt", basename($archivo));
        $hoy = new DateTime('now');
        return ssh2_exec($connection, "mv " . $archivo_descomprimido . " padron-afip-". $hoy->format('Y-m-d'));
    }
    
    private function descargar_archivo($connection){
        developer_log("Borrando archivo viejo ");
        ssh2_exec($connection, "rm ". self::ARCHIVO_PADRON);
        developer_log("Descargando desde ". self::URL_PADRON);
        ssh2_exec($connection, "wget -b ". self::URL_PADRON);
        sleep(50);
        return true;
    }
    
    protected function resolve_stream($stream){
        $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($errorStream, true);
        stream_set_blocking($stream, true);
        $streamContents = stream_get_contents($stream);
        $streamError = stream_get_contents($errorStream);
        if($streamContents != null)
            developer_log($streamContents);
            
        if($streamError != null){
            Gestor_de_log::set($streamError);
            developer_log($streamError);
            fclose($stream);
            return false;
        }
        fclose($stream);
        return true;
    }
    
    public static function factory($tipo) {
        switch ($tipo){
            case "arba":
                return new Arba_carga_padron();
                break;
            case "afip":
                return new Afip_carga_padron();
                break;
            case "excep_afip":
                return new Afip_carga_excepcion();
                break;
        }
        return null;
    }
}
