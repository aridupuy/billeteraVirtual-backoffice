<?php

class Transaccion_pei extends Transaccion {
    private static $transaccion ;
    public static function factory($transaccion) {
        self::$transaccion=$transaccion;
        return new self();
    }

    public static function deducir_id_entidad($id_mp, $id_marchand = false, Transas $transas = null, Sabana $sabana = null,$id_mp_rev=false) {
        $id_entidad = false;
        developer_log("aca");
        error_log("ENTIDAD PARA ID_MP:$id_mp");
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

}
