<?php

class Costeador_cheque_sucursal_reverso extends Costeador
{
    const VERSION_RP = "PPV4";
    const FORMA_PAGO = 0; // 0 = Cheque
    const MONEDA = "ARP";
    
    protected function obtener_recordset()
    {
            return Sabana::registros_a_costear_reversos_cheques($this->limite_de_registros_por_ejecucion);
    }
    
    public function ejecutar(){
        
        $pid=getmypid();
        
        $this->developer_log('Obteniendo registros de sabana para costear. ');
        $recordset=$this->obtener_recordset();
        $cantidades = [];
        $cantidades['total'] = $recordset->RowCount();
        $contador = 0;
        
        if ($recordset and $recordset->RowCount()>0) {
            $this->developer_log('Se encontraron '.$recordset->RowCount().' registros para costear.');
            $i=1;
            
            foreach ($recordset as $row) {
                developer_log("****PID ($pid)****COSTEANDO Nro ( $i / ".$recordset->rowCount()." ) ************************");
                $cheque = new Cheque_por_sucursal();
                $cheque->get((int)$row['barcode']);
                
                Model::StartTrans();
                
                $estado = $this->obtener_estado_cheque($row);
                
                if($this->actualizar_estado_trans($cheque, $estado)){
                    if($this->actualizar_sabana_cheque($row, $estado)){
                        if($this->actualizar_estado_cheque_sucursal($cheque, $estado)){
                            if (Model::CompleteTrans() and !Model::hasFailedTrans()) {
                                $this->developer_log("Actualizando estados");
                                $contador++;
                            }else{
                                Model::FailTrans();
                            }
                        }else{
                            Model::FailTrans();
                        }
                    }else{
                        Model::FailTrans();
                    }
                }else{
                    Model::FailTrans();
                }
                Model::CompleteTrans();
            }
        }else{
            Gestor_de_log::set("No hay registros para costear");
            return true;
        }
        
        $cantidades['procesados'] = $contador;
        return $cantidades;
    }
    
    private function obtener_estado_cheque($row){
        $estado_cheque = $row['barrand'];
        
        switch ($estado_cheque){
            case 6:     # Rechazado camara 48 hs
                $id_authstat = Authstat::TRANSACCION_RETIRO_CANCELADO;
                break;
            case 7:     # Anulado
                $id_authstat = Authstat::TRANSACCION_ANULADA;
                break;
        }
        return $id_authstat;
    }
    
    private function actualizar_estado_trans(Cheque_por_sucursal $cheque, $estado){
        $moves = new Moves();
        $moves->get($cheque->get_id_moves());
        # VER SI HAY QUE SETEAR EL AUTHSTAT
        $moves->set_id_authstat($estado);
        $moves->set();
        
        if (!$moves->set()) {
            if (self::ACTIVAR_DEBUG)
                developer_log("||No se pudo actualizar Moves.");
            return false;
        }
        $trans = new Transaccion();
        $trans->reversar($moves);
        return true;
    }
    
    public function actualizar_estado_cheque_sucursal(Cheque_por_sucursal $cheque, $estado){
        $cheque->set_id_authstat($estado);
        if(!$cheque->set()){
            if (self::ACTIVAR_DEBUG)
                developer_log("|| No se pudo actualizar Cheque_por_sucursal.");
            return false;
        }
        return true;
    }
    
    public function actualizar_sabana_cheque($row, $estado){
        $sabana = new Sabana();
        $sabana->get($row['id_sabana']);
        $sabana->set_id_authstat($estado);
        $sabana->set();
        if (!$sabana->set()) {
            if (self::ACTIVAR_DEBUG)
                developer_log("||No se pudo actualizar Sabana.");
            return false;
        }
        return true;
    }
}
