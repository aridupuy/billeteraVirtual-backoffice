<?php

class Afip_carga_padron extends Arba_carga_padron{
    
    public function __construct() {
        
    }
    
    protected  function drop_backup($connection){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME."  -c  'drop table cd_sujeto_retencion_afip_bkp;'");
        return $this->resolve_stream($stream);
    }

    protected function ejecutar_backup($connection){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME."  -c  'select * into cd_sujeto_retencion_afip_bkp from cd_sujeto_retencion_afip'");
        return $this->resolve_stream($stream);
    }
    
    protected function truncar_tablas($connection){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME."  -c  'truncate table ho_precarga_padron_afip;'");
        return $this->resolve_stream($stream);
    }
    
    protected function truncar_tabla_sujeto_retencion($connection){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME."  -c  'truncate table cd_sujeto_retencion_afip ;'");
        return $this->resolve_stream($stream);
    }
    
    protected function ejecutar_copy($connection, $archivo){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ". DATABASE_NAME ." -c  \" copy ho_precarga_padron_afip (linea) from '/home/".DATABASE_USER_SO."/$archivo' encoding 'LATIN1' \" 2> /home/".DATABASE_USER_SO."/logPadron.log");
        return $this->resolve_stream($stream);
    }
    
    protected function insertar_sujeto_retencion($connection){
         //hay que ver
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME ." -c  \"insert into cd_sujeto_retencion_afip (cuit,fecha_gen,agente,id_authstat, regimen_ganancias,regimen_iva,regimen_monotributo,integrante_sociedad,empleador,actividad_monotributo) select substring(linea, 0, 12)::numeric, now(),'AFIP', 1, substring(linea, 12, 2), substring(linea, 14 , 2),substring(linea, 16 ,2),substring(linea, 18 ,1),substring(linea, 19 ,1),substring(linea, 20 ,22) from ho_precarga_padron_afip\"");
        return $this->resolve_stream($stream);
    }
    
    protected function insertar_alicuotas($connection){
        developer_log('No se insertan alicuotas');
        return true;
    }
    
    protected function restaurar_backup($connection){
        $stream = ssh2_exec($connection, "PGPASSWORD=".DATABASE_USERPASS." psql -U".DATABASE_USERNAME." ".DATABASE_NAME."  -c  'drop table cd_sujeto_retencion_afip; select * into cd_sujeto_retencion_afip from cd_sujeto_retencion_afip_bkp'");
        return $this->resolve_stream($stream);
    }
    
}
