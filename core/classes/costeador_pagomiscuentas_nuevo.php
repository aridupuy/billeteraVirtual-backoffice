<?php

class Costeador_pagomiscuentas_nuevo extends Costeador_nuevo
{
//    protected function obtener_recordset()
//    {
//        return Sabana::registros_a_costear_pagomiscuentas($this->limite_de_registros_por_ejecucion);
//    }
    protected function consolidar(Sabana $sabana, Barcode $barcode)
    {
        $this->developer_log("Consolidando Pagomiscuentas. ");
        $id_marchand=$barcode->get_id_marchand();
        $id_mp=$sabana->get_id_mp();
        $monto_pagador=$sabana->get_monto();
        $fecha=$sabana->get_fecha_pago();
        if($fecha ===false OR $fecha===null){
            $fecha=$sabana->get_fecha_vto();
        }
        $fecha_datetime=Datetime::createFromFormat(Sabana::FORMATO_FECHA_FECHA_PAGO, $fecha);
        if (!$fecha_datetime) {
            $this->developer_log('La fecha no es correcta.');
            return false;
        }

        $transaccion=new Transaccion();
        $id_referencia=$barcode->get_id();
        $mensaje_excepcion=false;
        try {
            $resultado=$transaccion->crear($id_marchand, $id_mp, $monto_pagador, $fecha_datetime, $id_referencia, $sabana, $barcode);
        } catch (Exception $e) {
            $resultado=false;
            $mensaje_excepcion=$e->getMessage();
        }
        if (self::AGREGAR_LOGS_ANIDADOS) {
            if (count($transaccion->log)) {
                foreach ($transaccion->log as $mensaje) {
                    $this->log[]=$mensaje;
                }
            }
            if ($mensaje_excepcion) {
                $this->log[]=$mensaje_excepcion;
            }
        }
        return $resultado;
    }
}
