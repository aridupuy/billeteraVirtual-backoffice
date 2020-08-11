<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of validacion_sabana_duplicada
 *
 * @author arieldupuy
 */
class Validacion_saldo extends Validacion_sistema{
   
    const EMISOR = 'info@cobrodigital.com';
    const DESTINATARIO = 'sistemas@cobrodigital.com';
    const SO_PASS = 'WklmH34$';
    const SO_USER = 'heisenberg';
    const PATH_ENVVARS =
        '/etc/apache2/envvars_tareas';
    
    public function ejecutar() {
        developer_log("Ejecutando: ".__CLASS__);
        
        $connection = ssh2_connect(DATABASE_HOST, 22);
        $this->conectar_db($connection);
        
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME."  -a -f  valida_saldo.sql");

        $recordset = Moves::validador_saldo();

        if($recordset->rowCount() >0){
            $table = new Table($recordset, 1, $recordset->rowCount(), null);
            //developer_log($table->saveHTML());
            Gestor_de_correo::enviar(self::EMISOR, self::DESTINATARIO, 'Arregla saldo', $table->saveHTML());
            Gestor_de_correo::enviar(self::EMISOR, "pbernardo@cobrodigital.com", 'Arregla saldo', $table->saveHTML());
            Gestor_de_correo::enviar(self::EMISOR, "doviedo@cobrodigital.com", 'Arregla saldo', $table->saveHTML());
        }
  
        return true;
    }
    
    private function conectar_db($connection){
        return ssh2_auth_password($connection, self::SO_USER, self::SO_PASS);
    }

    function resolve_stream($stream){
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
    
}
