<?php

class Arba_carga_padron extends Carga_padron{
    
    public function __construct() {
        
    }
    protected function ejecutar_backup($connection){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME."  -c  'select * into cd_sujeto_retencion_bkp from cd_sujeto_retencion'");
        //$stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME."  -c  'drop table cd_sujeto_retencion_bkp; select * into cd_sujeto_retencion_bkp from cd_sujeto_retencion'");
        return $this->resolve_stream($stream);
    }
    
    protected function truncar_tablas($connection){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME."  -c  'truncate table ho_precarga_padron;'");
        return $this->resolve_stream($stream);
    }
    
    
    protected function ejecutar_copy($connection, $archivo){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ". DATABASE_NAME ." -c  \" copy ho_precarga_padron (linea) from '/home/".DATABASE_USER_SO."/$archivo' encoding 'LATIN1' \" 2> /home/".DATABASE_USER_SO."/logPadron.log");
        return $this->resolve_stream($stream);
    }
    
    protected function insertar_sujeto_retencion($connection){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME ." -c  \"insert into cd_sujeto_retencion (cuit,fecha_gen,agente,id_authstat, regimen, letra_alicuota)  select substring(linea, 3, 11)::numeric, now(),'ARBA', 1, substring(linea, 0, 3)::numeric, substring(linea, 58 , 1) from ho_precarga_padron  \"");
        return $this->resolve_stream($stream);
    }
    
    protected function insertar_alicuotas($connection){
        $stream =  ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME ." -c  \" insert into cd_alicuota (porcentaje, letra, id_authstat, fecha_gen, fecha_desde, fecha_hasta)"
                            . " select distinct porcentaje::numeric,letra ,1,now(), now()::date as desde, "
                            . "(((date_trunc('month' ,  (current_date )) + '1 month'::interval) - '1 day'::interval)) as hasta "
                            . "from  cd_sujeto_retencion A  left join cd_letra_alicuota B on A.letra_alicuota=B.letra  \"");
        
        return $this->resolve_stream($stream);
    }
    
    protected function restaurar_backup($connection){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME."  -c  'drop table cd_sujeto_retencion; select * into cd_sujeto_retencion from cd_sujeto_retencion_bkp'");
        return $this->resolve_stream($stream);
    }
  
    protected function truncar_tabla_sujeto_retencion($connection){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME."  -c  'truncate table cd_sujeto_retencion;'");
        return $this->resolve_stream($stream);
    }
    
    protected function drop_backup($connection){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME."  -c  'drop table cd_sujeto_retencion_bkp;'");
        return $this->resolve_stream($stream);
    }
}
