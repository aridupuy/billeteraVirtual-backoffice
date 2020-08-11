<?php

class Transaccion_decidir extends Transaccion {
    private static $transaccion ;
    public static function factory($transaccion) {
        self::$transaccion=$transaccion;
        return new self();
    }

    public static function deducir_id_entidad($id_mp, $id_marchand = false, Transas $transas = null, Sabana $sabana = null,$id_mp_rev=false) {
        $id_entidad = false;
        if(in_array($id_mp, array(Mp::COSTO_PEI_DEVOLUCION, Mp::COSTO_DECIDIR_DEVOLUCION)) ){
            return parent::deducir_id_entidad($id_mp, $id_marchand, $transas, $sabana,$id_mp_rev);
        }
        error_log("ENTIDAD PARA ID_MP:$id_mp");
        if($id_mp == Mp::DECIDIR_DEVOLUCION){
            return Entidad::ENTIDAD_MOVES;
        }
        switch (get_class(self::$transaccion)){
            case Barcode::class:
                $id_entidad=Entidad::ENTIDAD_BARCODE;
                break;
            case Debito_tco::class:
                $id_entidad=Entidad::ENTIDAD_DEBITO_TCO;
                break;
        }
        error_log("ENTIDAD_ENCONTRADA $id_entidad");
        return $id_entidad;
    }

    protected function deducir_id_tipomove($id_mp) {
        return Tipomove::COBRO_CON_TCO_ONLINE;
    }

    protected function deducir_id_authstat($id_mp) {
        return Authstat::TRANSACCION_VERIFICADA;
    }
    
//    public function reversar_parcial(Moves $moves, Decidir_transaccion $sabana) {
//        //el moves tiene que ser el ultimo que este disponible 
//        // puede ser el moves original del ingreso o cualquier otra devolucion existente.
//        $movess = $this->buscar_moves_devolucion_parcial_anterior($moves);
//        $response= json_decode($sabana->get_response(),true);
//        $monto = $response["amount"]/100;
//        $id_mp = Mp::DECIDIR_DEVOLUCION;
//        if($monto !=$sabana->get_monto()){
//            //$monto_final = ($sabana->get_monto()-$moves->get_monto_pagador())*-1;
//            $monto_final = $this->calcular_monto_final($movess,$sabana->get_monto());
//            
//            if($moves->get_monto_pagador()-$monto_final!=$sabana->get_monto()){
//                var_dump($moves->get_monto_pagador());
//                var_dump($monto_final);
//                var_dump($sabana->get_monto());
//                throw new Exception("NO se pudo calcular bien el monto a costear");
//            }
//            var_dump($monto_final);
//            if($monto_final<=0){
//                var_dump($monto_final);
//                throw new Exception("NO se puede costear un movimiento por menos que el total");
//            }
//            $fecha = new DateTime("now");
//            $id_referencia = $moves->get_id_moves();
//            $id_entidad = Entidad::ENTIDAD_MOVES;
//            if($this->crear($moves->get_id_marchand(), $id_mp, $monto_final, $fecha, $id_referencia)){
//                $sabana->set_reversado("true");
//                $sabana->set_costeado("true");
//                if($sabana->set()){
//                    return true;
//                }
//            }
//            
//            //$this->crear($id_marchand, $id_mp, $monto_final, $fecha, $id_referencia, $sabana, $barcode, $traslado_comision, $id_pricing_pag, $id_pricing_cdi)
//            
//        } 
//    }
//    public function buscar_moves_devolucion_parcial_anterior(Moves $moves){
//        $movess[]=$moves;
//        $rs = Moves::select_moves_devoluciones($moves);
//        foreach ($rs as $row){
//                $movess[]=new Moves($row);
//            }
//        return $movess;
//    }
//    public function calcular_monto_final($movess,$monto_actual){
//        //$monto_final = $movess[0]->get_monto_pagador();
//        $monto_final = 0;
//        $monto_inicial = $movess[0]->get_monto_pagador();
//        unset($movess[0]);
//        foreach ($movess as $moves){
//            $monto_final+=($moves->get_monto_pagador());
//        }
//        return ($monto_inicial-$monto_final)-$monto_actual;
//    }
   
}
