<?php

class Costeador_decidir_reverso extends Costeador{
    const id_mp=221;
    protected function actualizar_entidad($entidad,$moves) {
        
        switch (get_class($entidad)){
            case Barcode::class:
                $objeto = new Barcode();
                $objeto->set_id_barcode($entidad->get_id_barcode());
                $objeto->set_id_authstat(Authstat::BARCODE_CANCELADO);
                break;
            case Debito_tco::class:
                $objeto = new Debito_tco();
                $objeto->set_id_debito($entidad->get_id_debito());
                $objeto->set_id_authf1(Authstat::DEBITO_REVERTIDO);
                $objeto->set_id_movesrev($moves->get_id_moves());
                break;
        }
        return $objeto->set();
    }
    public function actualizar_transaccion_decidir(Decidir_transaccion $transaccion) {
        $transaccion->set_costeado("true");
        $transaccion->set_reversado("true");
        return $transaccion->set();
    }
    public function reversar(Decidir_transaccion $sabana) {
        Model::StartTrans();
        $entidad = $this->obtener_entidad($sabana);
        $transaccion = Transaccion_decidir::factory($entidad);
        switch (get_class($entidad)){
            case Barcode::class:
                $fecha=$entidad->get_fecha_vto();
                $id_entidad = Entidad::ENTIDAD_BARCODE;
                break;
            case Debito_tco::class:
                $fecha=$entidad->get_fecha_pago();
                $id_entidad = Entidad::ENTIDAD_DEBITO_TCO;
                break;
        }
        developer_log($fecha);
        $fecha_aux= DateTime::createFromFormat("Y-m-d",$fecha);
        if(!$fecha_aux){
            $fecha_aux= DateTime::createFromFormat("Y-m-d H:i:s",$fecha);
        }
        $fecha=$fecha_aux;
        $moves = $this->obtener_moves_a_reversar($entidad->get_id_marchand(), Mp::DECIDIR, $sabana->get_monto(), $entidad->get_id(),$id_entidad);
        if(!$moves){
            Model::FailTrans();
            developer_log("Error al identificar moves a reversar");
            Model::CompleteTrans();
            return false;
        }
            
        if(!$transaccion->reversar($moves))
        {
            Model::FailTrans();
            developer_log("Error al crear la transaccion.");
        }
        if($this->actualizar_entidad($entidad,$transaccion->moves)){
            if(!$this->actualizar_transaccion_decidir($sabana)){
                Model::FailTrans();
                developer_log("Error al actualizar transaccion decidir");
            }
        }
        else {
            Model::FailTrans();
            developer_log("Error al actualizar la entidad");    
        }
        if(Model::CompleteTrans()){
            
            return true;
        }
        developer_log("sale por aca");
        return false;
    }
//    private function verificar_permiso_servidor(){
//        return parent::verificar_permiso_servidor();
//    }
    private function obtener_moves_a_reversar($id_marchand, $mp, $monto, $referencia,$id_entidad){
        $rs_moves = Moves::select(array("id_marchand"=>$id_marchand,"id_mp"=>$mp,"monto_pagador"=>$monto,"id_referencia"=>$referencia,"id_entidad"=> $id_entidad));
        developer_log($rs_moves->rowCount());
        if($rs_moves->rowCount()==1){
            $moves = (new Moves($rs_moves->fetchRow()));
            developer_log($moves->get_id());
            $rs_moves_rev = Moves::select(array("id_entidad"=> $id_entidad,"id_referencia"=>$referencia,"id_mp"=> Mp::REVERSO_DE_INGRESO));
            if($rs_moves_rev->rowCount()==0)
                return $moves;
            else{
                developer_log("El movimiento ya fue reversado");
                return false;
            }
        }
        return false;
    }
    public function ejecutar() {
           ini_set("session.gc_maxlifetime","7200");
        $pid=getmypid();
        if(!$this->verificar_permiso_servidor()){
            Model::CompleteTrans();
            throw new Exception("No se puede costear en este servidor. dirijase a ".NOMBRE_SERVER_COSTEADOR, 0);
        }
        if (!($mp=$this->obtener_semaforo())) {
	   Model::CompleteTrans();
            throw new Exception("Ha ocurrido un error al obtener el semáforo. ", 0);
        }
        if (self::ACTIVAR_TEST) {
            $this->developer_log('Es una prueba: Comienza transaccion global.');
            Model::StartTrans();
        }
        $this->developer_log('Obteniendo registros de sabana para costear. ');
        $recordset=$this->obtener_recordset();
        if ($recordset and $recordset->RowCount()>0) {
            $this->developer_log('Se encontraron '.$recordset->RowCount().' registros para costear.');
            $i=1;
            foreach ($recordset as $row) {
                $sabana=new Decidir_transaccion($row);
                developer_log("****PID ($pid)****Reversando Nro ( $i / ".$recordset->rowCount()." ) ID_MP (".self::id_mp.") ************************");
                if ($this->reversar($sabana)) {
                    $this->sabanas_correctas++;
                } else {
                    $this->sabanas_incorrectas++;
                }
                            $i++;
                            developer_log("");
                            developer_log("");
            }
        } elseif ($recordset and $recordset->RowCount()==0) {
            $this->developer_log('No hay sabanas que costear. ');
        } else {
            $this->developer_log('Ha ocurrido un error.');
            
        }
        $this->developer_log('Cantidad de sabanas costeadas correctamente: '.$this->sabanas_correctas);
        $this->developer_log('Cantidad de sabanas incorrectas: '.$this->sabanas_incorrectas);
        if (self::ACTIVAR_TEST) {
            $this->developer_log('Es una prueba: Falla transaccion global.');
            Model::FailTrans();
            Model::CompleteTrans();
        }

        if (!$this->liberar_semaforo($mp)) {
            developer_log('Ha ocurrido un error al liberar el semaforo');
        }
        return $this->sabanas_correctas;
    }
    protected function consolidar(\Sabana $sabana, \Barcode $barcode) {
        parent::consolidar($sabana, $barcode);
    }
    protected function obtener_recordset() {
        return Decidir_transaccion::select_pagos_a_reversar();
    }
    protected function obtener_entidad(Decidir_transaccion $pago){
        $barcode = new Barcode();
        $debito_tco = new Debito_tco();
        switch ($pago->get_id_entidad()){
            case Entidad::ENTIDAD_BARCODE:
            $barcode->get($pago->get_id_referencia());
            return $barcode;
                break;
            case Entidad::ENTIDAD_DEBITO_TCO:
                $debito_tco->get($pago->get_id_referencia());
                return $debito_tco;
                break;
                
        }
    }

    protected function obtener_semaforo() {
        $recordset = Mp::semaforo_libre_para_costear(Mp::DECIDIR);
        if ($recordset AND $recordset->RowCount() == 1) {
            $mp = new Mp($recordset->FetchRow());
            $mp->set_nops(Mp::SEMAFORO_OCUPADO);
            if ($mp->set()) {
                return $mp;
            }
        }
        return false;
    }

    protected function liberar_semaforo($mp) {
        developer_log("liberando semaforo");
        developer_log($mp->get_id());
        $mp->set_nops(Mp::SEMAFORO_LIBRE);
        if ($mp->set()) {
            developer_log("semaforo liberado");
            return $mp;
        }
        return false;
    }
}
