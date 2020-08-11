<?php

class Costeador_mercadopago {

    const ACTIVAR_DEBUG = true;
    const ACTIVAR_TEST = false; # Falla la transaccion siempre  # No funciona bien con el trait transaccion
    const INTERVALO_MINUTOS_DE_CONSULTA = 30;

    private $limite_de_registros_por_ejecucion = 15000;
    private $log = false;
    public $transas_correctas = 0;
    public $transas_incorrectas = 0;

    public function ejecutar() {
        if (!($mp = $this->obtener_semaforo())) {
            throw new Exception("Ha ocurrido un error al obtener el semáforo. ", 0);
        }
        if (self::ACTIVAR_TEST) {
            $this->developer_log('Es una prueba: Comienza transaccion global.');
            Model::StartTrans();
        }
        $pagos_procesados_correctamente = 0;
        # Solo busco rechazadas y aprobadas
        $recordset = Transas::select_modificaciones(self::INTERVALO_MINUTOS_DE_CONSULTA, $this->limite_de_registros_por_ejecucion);
        if ($recordset->RowCount() > 0) {
            if (self::ACTIVAR_DEBUG) {
                $this->developer_log('Costeando cobranzas modificadas. ');
            }
            foreach ($recordset as $row) {
                $transas = new Transas($row);
                if ($this->costear_transas($transas)) {
                    developer_log("pagos_procesados_correctamente: $pagos_procesados_correctamente");
                    $pagos_procesados_correctamente++;
                }
            }
        }

        $this->transas_correctas = $pagos_procesados_correctamente;
        $this->transas_incorrectas = $recordset->RowCount() - $pagos_procesados_correctamente;

        if (self::ACTIVAR_DEBUG) {
            $this->developer_log('Pagos correctamente procesados: ' . $this->transas_correctas);
            $this->developer_log('Pagos no procesados por error: ' . $this->transas_incorrectas);
        }
        if (!$this->liberar_semaforo($mp)) {
            $this->developer_log('Ha ocurrido un error al liberar el semaforo');
        }
        if (self::ACTIVAR_TEST) {
            $this->developer_log('Es una prueba: Falla transaccion global.');
            Model::FailTrans();
            Model::CompleteTrans();
        }
        return $pagos_procesados_correctamente;
    }
    private function es_con_barcode($transas){
        if($transas->get_id_entidad()==Entidad::ENTIDAD_BARCODE)
            return true;
        return false;
    }

    private function costear_transas(Transas $transas) {
        $barcode=null;
        $debito_tco=null;
        if ($transas->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_APROBADA
                AND ! $this->doble_confirmacion_de_transas($transas->get_gateway_op_id(), $transas->get_id_gateway())) {
            $this->developer_log('La transas no pasó la doble confirmación.' . $transas->get_gateway_op_id());
            return false;
        }
        Model::StartTrans();
        if ($this->es_con_barcode($transas)) {
            if (!($barcode = $this->obtener_barcode($transas))) {
                if (self::ACTIVAR_DEBUG) {
                    $this->developer_log('Ha ocurrido un error al obtener el Barcode. ');
                }
                Model::FailTrans();
//                return false;
            }

            if (!Model::hasFailedTrans()) {
                $id_authstat = $this->obtener_estado_para_barcode($transas);
                if (!$id_authstat) {
                    if (self::ACTIVAR_DEBUG) {
                        developer_log('Estado desconocido para Barcode');
                    }
                    Model::FailTrans();
                } else {
                    if (!($barcode = $this->actualizar_barcode($barcode, $id_authstat))) {
                        $this->developer_log('Ha ocurrido un error al actualizar el Barcode. ');
                        Model::FailTrans();
                    }
                }
            }
        }
        $id_authstat = false;
        if (!Model::hasFailedTrans()) {
            if (($debito_tco = $this->obtener_debito_tco($transas))) {
                $id_authstat= $this->obtener_estado_para_debito_tco($transas);
                if (!$id_authstat) {
                    if (self::ACTIVAR_DEBUG) {
                        developer_log('Estado desconocido para debito');
                    }
                    Model::FailTrans();
                } else {
                    $agenda_xml = false;
                    $dom = new View();
                    $dom->loadXML($transas->get_transas_xml());
                    $agenda_xml_elements = $dom->getElementsByTagName('mensaje_para_usuario');
                    if ($agenda_xml_elements->length == 1) {
                        $agenda_xml = $agenda_xml_elements->item(0)->nodeValue;
                    }
                    if (!$this->actualizar_debito_tco($debito_tco, $id_authstat, $agenda_xml)) {
                        $this->developer_log('Ha ocurrido un error al actualizar la agenda_vinvulo. ');
                        Model::FailTrans();
                    }
                }
            }
        }

        if ($transas->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_APROBADA) {
            if (!Model::hasFailedTrans()) {
                if(!$debito_tco)
                    $debito_tco=null;
                if (!($transaccion = $this->consolidar($barcode,$debito_tco, $transas->get_monto_pagador(), new DateTime('now'), $transas))) {
                    if (self::ACTIVAR_DEBUG) {
                        $this->developer_log('Ha ocurrido un error al crear la transaccion. ');
                    }
                    Model::FailTrans();
                }
            }
        }

        $id_authstat = false;
        if (!Model::hasFailedTrans()) {
            $id_authstat = $this->obtener_estado_para_transas($transas);
            if (!$id_authstat) {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('Estado desconocido para transas');
                }
                Model::FailTrans();
            } else {
                if (!$this->actualizar_transas($transas, $id_authstat)) {
                    $this->developer_log('Ha ocurrido un error al actualizar la Transas. ');
                    Model::FailTrans();
                }
            }
        }
        
        if (Model::CompleteTrans() AND !Model::hasFailedTrans()) {
//            var_dump("COMPLETANDO");
            if($barcode!=null)
                developer_log("CORRECTO PARA BARCODE: " . $barcode->get_barcode());
            if($debito_tco!=null)
                developer_log("CORRECTO PARA DEBITO: " . $debito_tco->get_id());
            return true;
        } else {
          if($this->es_con_barcode($transas) and $barcode!=null)
                developer_log("INCORRECTO PARA BARCODE: " . $barcode->get_barcode());
            if($this->es_con_barcode($transas) and $debito_tco!=null)
                developer_log("INCORRECTO PARA DEBITO: " . $debito_tco->get_id());
        }
        return false;
    }

    private function doble_confirmacion_de_transas($gateway_op_id, $id_gateway) {
        return true;
        $listado_de_cuentas = Preprocesador_mercadopago::listado_de_cuentas();
        foreach ($listado_de_cuentas as $cuenta) {
            if ($id_gateway == $cuenta['id_peucd']) {
                $cuenta_elegida = $cuenta;
            }
        }
        if (!$cuenta_elegida) {
            return false;
        }
        unset($listado_de_cuentas);
        try {
            require_once PATH_PUBLIC . Preprocesador_mercadopago::LIBRARY;
            if (isset($cuenta_elegida['access_token'])) {
                $mp = new MP_lib($cuenta_elegida['access_token']);
            } else {
                $mp = new MP_lib($cuenta_elegida['client_id'], $cuenta_elegida['client_secret']);
            }
        } catch (Exception $e) {
            if (self::ACTIVAR_DEBUG) {
                developer_log('Excepción de Construcción: ' . $e->getMessage());
            }
            return false;
        }
        $parametros = array();
        $parametros["id"] = $gateway_op_id;
        $response = $mp->get("/v1/payments/search", $parametros);

        if (isset($response['status']) AND $response['status'] == '200') {
            $this->developer_log($response['response']['paging']['total']);
            if ($response['response']['paging']['total'] == 1) {
                $this->developer_log('El Payment existe.');
                $registro = new Registro_mercadopago($response['response']['results'][0]);
//			var_dump($registro->obtener_estado()." : ".json_encode($response));
                if ($registro->obtener_estado() == Registro_mercadopago::ESTADO_APROBADO) {
                    return true;
                }
            }
        }
        return false;
    }

    private function obtener_barcode(Transas $transas) {
        $barcode = new Barcode();
        if ($barcode->get($transas->get_id_referencia())) {
            if($barcode->get_id_tipopago()== Tipopago::PAGO_UNICO){
                $rs=Barcode::select_tarjeta_asociada($barcode);
                if($rs->rowCount()>0)
                    $barcode = new Barcode($rs->fetchRow());
                
            }
            return $barcode;
        }
        return false;
    }
    private function obtener_debito_tco(Transas $transas) {
        $debito_tco=new Debito_tco();
        $debito_tco->get($transas->get_id_referencia());
        if ($debito_tco->get_id()!=null) {
            return $debito_tco;
        }
        return false;
    }

    private function actualizar_barcode(Barcode $barcode, $id_authstat) {
        $barcode->set_id_authstat($id_authstat);
        if ($barcode->set()) {
            return $barcode;
        }
        return false;
    }

    private function actualizar_debito_tco(Debito_tco $debito_tco, $id_authstat, $agenda_xml = false) {

        $d=new Debito_tco();
        $d->set_id($debito_tco->get_id());
        $d->set_id_authf1($id_authstat);
        if ($agenda_xml) {
            $d->set_motivorechazo($agenda_xml);
        }
        if ($d->set()) {
            return $debito_tco;
        }
        return false;
    }

    private function actualizar_transas(Transas $transas, $id_authstat) {
        $transas->set_id_authstat($id_authstat);
        if ($transas->set()) {
            return $transas;
        }
        return false;
    }

    private function consolidar(Barcode $barcode=null, Debito_tco $debito_tco=null, $monto, DateTime $fecha, Transas $transas = null) {
        $transaccion = new Transaccion();
        if($barcode!=null){
            $id_marchand = $barcode->get_id_marchand();
            $id_referencia = $barcode->get_id_barcode();
        }
        if($debito_tco!=null){
            $id_marchand = $debito_tco->get_id_marchand();
            $id_referencia = $debito_tco->get_id_debito();
        }
        $id_mp = Mp::TARJETA_DE_CREDITO;
        try {
            $resultado = $transaccion->crear($id_marchand, $id_mp, $monto, $fecha, $id_referencia, null, null, false, false, false, $transas);
        } catch (Exception $e) {
            $this->developer_log($e->getMessage());
            $resultado = false;
        }
        if ($resultado) {
            return $transaccion;
        }
        return false;
    }

    private function obtener_semaforo() {
        $recordset = Mp::semaforo_libre_para_costear(Mp::TARJETA);
        if ($recordset AND $recordset->RowCount() == 1) {
            $mp = new Mp($recordset->FetchRow());
            $mp->set_nops(Mp::SEMAFORO_OCUPADO);
            if ($mp->set()) {
                return $mp;
            }
        }
        return false;
    }

    private function liberar_semaforo($mp) {
        $mp->set_nops(Mp::SEMAFORO_LIBRE);
        if ($mp->set()) {
            return $mp;
        }
        return false;
    }

    private function enviar_alerta() {
        $this->developer_log("Enviando Alerta. ");
        $emisor = Gestor_de_correo::MAIL_COBRODIGITAL_INFO;
        $destinatario = Gestor_de_correo::MAIL_DESARROLLO;
        $asunto = self::ALERTA_ASUNTO;
        $mensaje = '';
        if ($this->log) {
            foreach ($this->log as $linea) {
                $mensaje .= $linea . '<br/>';
            }
        }
        return Gestor_de_correo::enviar($emisor, $destinatario, $asunto, $mensaje);
    }

    protected function developer_log($mensaje) {
        if (self::ACTIVAR_DEBUG) {
            developer_log($mensaje);
        }
        $this->log[] = $mensaje;
    }

    private function obtener_estado_para_barcode(Transas $transas) {
        $id_authstat = false;
        switch ($transas->get_id_authstat()) {
            case Authstat::MERCADOPAGO_TRANSACCION_APROBADA: $id_authstat = Authstat::BARCODE_PAGADO;
                break;
            case Authstat::MERCADOPAGO_TRANSACCION_RECHAZADA: $id_authstat = Authstat::BARCODE_CANCELADO;
                break;
        }
        return $id_authstat;
    }

    private function obtener_estado_para_transas(Transas $transas) {
        $id_authstat = false;
        switch ($transas->get_id_authstat()) {
            case Authstat::MERCADOPAGO_TRANSACCION_APROBADA: $id_authstat = Authstat::MERCADOPAGO_TRANSACCION_VERIFICADA;
                break;
            case Authstat::MERCADOPAGO_TRANSACCION_RECHAZADA: $id_authstat = Authstat::MERCADOPAGO_TRANSACCION_RECHAZADA;
                break;
        }
        return $id_authstat;
    }

    private function obtener_estado_para_debito_tco(Transas $transas) {
        $id_authstat = false;
        switch ($transas->get_id_authstat()) {
            case Authstat::MERCADOPAGO_TRANSACCION_APROBADA: $id_authstat = Authstat::DEBITO_DEBITADO;
                break;
            case Authstat::MERCADOPAGO_TRANSACCION_RECHAZADA: $id_authstat = Authstat::DEBITO_OBSERVADO;
                break;
        }

        return $id_authstat;
    }

}
