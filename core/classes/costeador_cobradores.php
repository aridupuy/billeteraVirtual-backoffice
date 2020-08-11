<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of costeador_cobradores
 *
 * @author ariel
 */
class Costeador_cobradores extends Costeador {

    protected function obtener_recordset() {
        $recordSet = Cobros_cobrador::select_cobros_a_costear();
        return $recordSet;
    }

    public function ejecutar() {


//        ini_set("session.gc_maxlifetime","7200");
        
        $pid = getmypid();
        if (!($mp = $this->obtener_semaforo())) {
            throw new Exception("Ha ocurrido un error al obtener el semáforo. ", 0);
        }
        if (self::ACTIVAR_TEST) {
            $this->developer_log('Es una prueba: Comienza transaccion global.');
            Model::StartTrans();
        }
        $this->developer_log('Obteniendo registros de sabana para costear. ');
        $recordset = $this->obtener_recordset();
        if ($recordset and $recordset->RowCount() > 0) {
            $this->developer_log('Se encontraron ' . $recordset->RowCount() . ' registros para costear.');
            $i = 1;
            foreach ($recordset as $row) {
                developer_log("****PID ($pid)****COSTEANDO Nro ( $i / " . $recordset->rowCount() . " ) ************************");
                $sabana = new Cobros_cobrador();
                $sabana->get($row["id_cobro_cobrador"]);
                if ($this->costear_sabana($sabana)) {
                    $this->sabanas_correctas++;
                } else {
                    $this->sabanas_incorrectas++;
                }
                $i++;
                developer_log("");
                developer_log("");
            }
        } elseif ($recordset and $recordset->RowCount() == 0) {
            Model::FailTrans();
            $this->developer_log('No hay sabanas que costear. ');
        } else {
            $this->developer_log('Ha ocurrido un error.');
            return false;
        }
        $this->developer_log('Cantidad de sabanas costeadas correctamente: ' . $this->sabanas_correctas);
        $this->developer_log('Cantidad de sabanas incorrectas: ' . $this->sabanas_incorrectas);
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

    public function costear_sabana(Cobros_cobrador $sabana) {
        $lote_cobrador = new Lote_cobrador();
        $lote_cobrador->get($sabana->get_id_lote_cobrador());
        Model::StartTrans();
        if ($this->actualizar_estados($sabana,$lote_cobrador)) {
            if ($this->consolidar($sabana, $lote_cobrador)) {
                if (Model::CompleteTrans() and ! Model::hasFailedTrans()) {
                    return true;
                }
            } else {
                $this->developer_log("Ha ocurrido un error al consolidar. ");
            }
        } else {
            $this->developer_log(" Ha ocurrido un error al actualizar los estados. ");
        }
        Model::FailTrans();
        Model::CompleteTrans();
        return false;
    }

    protected function actualizar_estados(Cobros_cobrador $cobro, Lote_cobrador $lote) {
        $cobro->set_id_authstat(Authstat::SABANA_COBRADA);
        if ($cobro->set()) {
            if ($lote->get_id_authstat() == Authstat::LOTE_APROBADO) {
                $lote->set_id_authstat(Authstat::LOTE_COSTEADO);
                $lote->set_file(null);
                if (!$lote->set())
                    return false;
            }
            return true;
        }
        return false;
    }

    protected function consolidar(Cobros_cobrador $cobro, Lote_cobrador $lote) {
        $this->developer_log("Consolidando. ");
        $id_marchand = $cobro->get_id_marchand();
        $id_mp = Mp::COBRO_COBRADORES;
        $monto_pagador = $this->calcular_monto_final($cobro->get_importe(),$cobro);
        $fecha = $cobro->get_vencimiento();
        error_log($fecha);
        $fecha_datetime = Datetime::createFromFormat("Y-m-d", $fecha);
        if (!$fecha_datetime) {
            $this->developer_log('La fecha no es correcta.');
            return false;
        }

        $transaccion = new Transaccion();
        $id_referencia = $cobro->get_id();
        $mensaje_excepcion = false;
        try {
            $resultado = $transaccion->crear($id_marchand, $id_mp, $monto_pagador, $fecha_datetime, $id_referencia);
        } catch (Exception $e) {
            $resultado = false;
            $mensaje_excepcion = $e->getMessage();
        }
        if (self::AGREGAR_LOGS_ANIDADOS) {
            if (count($transaccion->log)) {
                foreach ($transaccion->log as $mensaje) {
                    $this->log[] = $mensaje;
                }
            }
            if ($mensaje_excepcion) {
                $this->log[] = $mensaje_excepcion;
            }
        }
        return $resultado;
    }
//    private function calcular_monto_final($importe, Cobros_cobrador $cobro){
//      $recordset=Cobrador_marchand::select($cobro->get_id_cobrador(),$cobro->get_id_marchand());
//      if($recordset and $recordset->rowCount()==1){
//        $cob_marchand=new Cobrador_marchand($row);
//        $cob_marchand->get_comision_fija();
//        $cob_marchand->get_comision_variable();
//        
//          
//      }
//      return false;
//         
//    }
    protected function calcular_monto_final($importe_total, Cobros_cobrador $cobro) {
//        $cobrador=new Cobrador_marchand();
        $cobradores = Cobrador_marchand::select(array("id_cobrador" => $cobro->get_id_cobrador(), "id_marchand" => $cobro->get_id_marchand()));
        if ($cobradores->rowCount() >= 1) {
            $cobrador_marchand = new Cobrador_marchand($cobradores->fetchRow());
            $ganancia_bruta = ($importe_total - $cobrador_marchand->get_comision_fija()) * ($cobrador_marchand->get_comision_variable() / 100);
            return ($importe_total-$ganancia_bruta);
//            return number_format($ganancia_bruta, 2);
        } else
            return false;
    }
}

