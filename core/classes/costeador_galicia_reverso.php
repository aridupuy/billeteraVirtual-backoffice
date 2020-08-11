<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of costeador_galicia_reverso
 *
 * @author ariel
 */
class Costeador_galicia_reverso extends Costeador_galicia {

    protected function obtener_recordset() {
        error_log("Reversos");
        $variables = array();
        $variables[] = Mp::DEBITO_AUTOMATICO_REVERSO;
        $variables[] = Authstat::SABANA_DEBITO_A_REVERTIR;

        return Sabana::registros_a_costear_galicia($variables, $this->limite_de_registros_por_ejecucion);
    }

    protected function actualizar_debito_cbu(Sabana $sabana, $estado_original_sabana, Debito_cbu $debito) {
        # Falta verificar que el la Agenda_vinvulo no esté Pagada!
        $error = false;
        $this->developer_log("Actualizando Debito reverso. ");
//	exit();
        $motivo_rechazo = $this->obtener_motivo_rechazo($sabana, $estado_original_sabana);

        if (!($id_authstat = $this->obtener_estado_a_actualizar_agenda_vinvulo($estado_original_sabana))) {
            $this->developer_log('Ha ocurrido un error al obtener el estado a actualizar en el debito. ');
            return false;
        }
	$this->developer_log($id_authstat);
	
        $fecha_pago = DateTime::createFromFormat("Y-m-d H:i:s", $sabana->get_fecha_pago());
        $fecha1 = DateTime::createFromFormat("Y-m-d", $debito->get_fecha_pago1());
        if ($debito->get_fecha_pago2() != null)
            $fecha2 = DateTime::createFromFormat("Y-m-d", $debito->get_fecha_pago2());
        if ($debito->get_fecha_pago3() != null)
            $fecha3 = DateTime::createFromFormat("Y-m-d", $debito->get_fecha_pago3());

        $id_debito = $debito->get_id();
        $debito = new Debito_cbu();
        $debito->set_id_debito($id_debito);
        $debito->set_fecha_reverso($sabana->get_fecha_pago());
        $debito->set_id_authrev($id_authstat);
        if (!$debito->set()) {
            $error = true;
        }
        else{
            $this->developer_log("debito actualizado correctamente");
        }
        if (!$error) {
            if (self::ACTIVAR_DEBUG)
                developer_log("Actualizado Correctamente.");
            return $debito;
        }

        return false;
    }

    protected function obtener_y_actualizar_agenda_vinvulo(Sabana $sabana, $estado_original_sabana, Barcode $barcode) {
        error_log($barcode->get_barcode());
        # Falta verificar que el la Agenda_vinvulo no esté Pagada!
        $this->developer_log("Actualizando Agenda_vinvulo REVERSO.");
        $recordset = Agenda_vinvulo::select_desde_id_barcode_reverso($barcode->get_id_barcode(), $sabana->get_monto(), $sabana->get_fecha_vto());
        if (!$recordset OR $recordset->RowCount() != 1) {
            $this->developer_log("Intentando encontrar la agenda solo por id_barcode " . $barcode->get_id_barcode());
            $recordset = Agenda_vinvulo::select(array("id_barcode" => $barcode->get_id_barcode()));
            if ($recordset AND $recordset->RowCount() == 0) {
                $this->developer_log("No hay ningún débito asociado al código de barras '" . $barcode->get_barcode() . "'. ");
                return false;
            } else {
                $this->developer_log("Hay mas de un débito para el código de barras '" . $barcode->get_barcode() . "'. ");
                if (!self::PERMITIR_ACTUALIZACION_DE_MULTIPLES_AGENDA_VINVULO) {
                    return false;
                } else {
                    $this->developer_log("ALERTA: Actualizando un solo registro de agenda_vinvulo. ");
                }
            }
        }
        $agenda_vinvulo = new Agenda_vinvulo($recordset->FetchRow());
        $agenda_xml = $this->obtener_motivo_rechazo($sabana, $estado_original_sabana);
        if ($agenda_xml) {
            $agenda_vinvulo->set_agenda_xml($agenda_xml);
        }
        if (!($id_authstat = $this->obtener_estado_a_actualizar_agenda_vinvulo($estado_original_sabana))) {
            $this->developer_log('Ha ocurrido un error al obtener el estado a actualizar en la agenda_vinvulo. ');
            return false;
        }

        $agenda_vinvulo->set_id_authstat($id_authstat);

        if ($agenda_vinvulo->set()) {
            if (self::ACTIVAR_DEBUG)
                developer_log("Actualizando Agendas Adicionales.");
            $rs_traer = Agenda_vinvulo::select_debitos_vencimientos_pendientes($sabana->get_id_barcode(), 1);
            $error = false;
            foreach ($rs_traer as $agenda) {
                $agenda_obj = new Agenda_vinvulo($agenda);
                $id_authstat_vencimiento = $this->obtener_estado_a_actualizar_agenda_adicional($estado_original_sabana, $agenda_obj);
                $motivo_vencimiento = $this->obtener_motivo_agenda_adicional($estado_original_sabana, $agenda_obj);
                $agenda_obj->set_id_authstat($id_authstat_vencimiento);
                $agenda_obj->set_agenda_xml($motivo_vencimiento);
                if (!$agenda_obj->set()) {
                    $error = true;
                    if (self::ACTIVAR_DEBUG)
                        $this->developer_log("No se pudo actualizar la agenda adicional MOTIVO: " . $agenda_obj->get_agenda_xml());
                }
            }
            if (!$error) {
                if (self::ACTIVAR_DEBUG)
                    
                if($debito= $this->obtener_debito_cbu_barcode($barcode)){
                    if($this->actualizar_debito_cbu($sabana, $estado_original_sabana, $debito)){
                       $this->developer_log("Actualizado Correctamente.");
                        return $agenda_vinvulo;
                    }else
                        $this->developer_log("Error al actualizar el debito.");
                }
                else
                    $this->developer_log("Error al obtener el debito por barcode.");
                return false;
            }
        }
        return false;
    }

//select_desde_id_barcode_reverso
}
