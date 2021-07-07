<?php

class Afip_carga_excepcion extends Arba_carga_padron{
    
    public function __construct() {
        
    }
    
    protected function ejecutar_backup($connection){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME."  -c  'select * into ho_excepciones_afip_bkp from ho_excepciones_afip'");
        return $this->resolve_stream($stream);
    }
    
    protected function truncar_tablas($connection){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME."  -c  ' truncate table ho_precarga_excepcion; '");
        return $this->resolve_stream($stream);
    }
    
    protected function ejecutar_copy($connection, $archivo){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ". DATABASE_NAME ." -c  \" copy ho_precarga_excepcion (linea_excepcion) from '/home/".DATABASE_USER_SO."/$archivo' encoding 'LATIN1' \" 2> /home/".DATABASE_USER_SO."/logPadron.log ");
        return $this->resolve_stream($stream);
    }
    
    protected function insertar_sujeto_retencion($connection){
         //hay que ver
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME ." -c  \" insert into ho_excepciones_afip (cuit) select linea_excepcion::text from ho_precarga_excepcion \"");
        return $this->resolve_stream($stream);
    }
    
    protected function restaurar_backup($connection){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME."  -c  'drop table ho_excepciones_afip; select * into ho_excepciones_afip from ho_excepciones_afip_bkp'");
        return $this->resolve_stream($stream);
    }
}
