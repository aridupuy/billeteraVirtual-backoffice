<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Proveedor
 *
 * @author ariel
 */
class Trait_proveedor {

    private $pago_prov;
    private $proveedor;
    
    public function __construct(Marchand $marchand,Marchand $marchand_prov) {
        $recordset = Proveedor::obtener_proveedor_de_marchand($marchand->get_id_marchand(), $marchand_prov->get_documento(), $marchand_prov->get_id_marchand());
        $row=$recordset->fetchRow();
        $this->proveedor = new Proveedor($row);
        return $this;
    }
    public function crear_pagos($id_marchand, $monto, $traslada, $concepto, $movesEgreso) {
        return $this->agregar_pago($monto, $concepto, $traslada, $id_marchand, $movesEgreso);
    }

    private function agregar_pago($monto, $concepto, $traslada, $id_marchand, Moves $movesEgreso = null) {

        Model::StartTrans();
        $this->pago_prov = new Pago_proveedor();
        $this->pago_prov->set_id_proveedor($this->proveedor->get_id_proveedor());
        $this->pago_prov->set_monto($monto);
        $this->pago_prov->set_concepto($concepto);
        $this->pago_prov->set_traslada($traslada);
        $this->pago_prov->set_id_marchand($id_marchand);
        $this->pago_prov->set_id_authstat(Authstat::TRANSACCION_PAGO_A_PROVEEDOR_PENDIENTE);
        if ($movesEgreso !== null) {
            $id_moves = $movesEgreso->get_id_moves();
            $this->pago_prov->set_id_moves($id_moves);
        }
        $this->pago_prov->set();
        if (!Model::HasFailedTrans() and Model::CompleteTrans()) {
            Gestor_de_log::set('Pago generado correctamente');
            return true;
        } else {
            $this->pago_prov->save_log('Error al generar el pago');
            return false;
        }
    }

    public function crear_transaccion($id_marchand, $id_marchand_proveedor, $monto, $concepto,$traslada) {
        $egreso = new Transaccion();
        $id_mp_egreso = Mp::PAGO_A_PROVEEDOR;
        $id_mp_ingreso = Mp::COBRO_COMO_PROVEEDOR;

        if ($traslada== 't') {
            developer_log(" TRANSLADA TRUE ");
            $traslado_comision_ingreso = true;
            $traslado_comision_egreso = false;
        } else {
            developer_log(" TRANSLADA FALSE ");
            $traslado_comision_ingreso = false;
            $traslado_comision_egreso = true;
        }
        $fecha = new DateTime('now');
        developer_log("CREANDO EGRESO $id_mp_egreso $id_marchand");
        if (!Model::HasFailedTrans() and $egreso->crear($id_marchand, $id_mp_egreso, $monto, $fecha, $id_marchand_proveedor, null, null, $traslado_comision_egreso)) {

            if ($egreso->moves->set()) {
                Transaccion::$grado_de_recursividad = 0;
                $this->pago_prov->set_id_moves($egreso->moves->get_id_moves());
                $this->pago_prov->set();
                developer_log("se retiro el monto al pagador");
            } else {
                Model::FailTrans();
                throw new Exception("Fallo al insertar el egreso");
            }
            $ingreso = new Transaccion();
            if (!Model::HasFailedTrans() and $ingreso->crear($id_marchand_proveedor, $id_mp_ingreso, $monto, $fecha, $id_marchand, null, null, $traslado_comision_ingreso)) {
                developer_log($ingreso->moves->get_monto_marchand());
                $ingreso->moves->set_fecha_liq($fecha->format("Y-m-d"));
                if ($ingreso->moves->set()) {
                    Transaccion::$grado_de_recursividad = 0;
                    developer_log("se acredito el monto al proveedor");
                } else {
                    Model::FailTrans();
                    throw new Exception("Fallo al insertar el ingreso");
                    return false;
                }
            } else {
                Model::FailTrans();
                throw new Exception("Fallo al crear la transaccion de ingreso");
                return false;
            }
        } else {
            throw new Exception("Fallo al crear la transaccion de egreso");
            Model::FailTrans();
            return false;
        }
        return true;
    }

}
