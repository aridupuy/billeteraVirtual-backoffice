<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ordenador_mercadopago_token
 *
 * @author arieldupuy
 */
class Ordenador_mercadopago_token {

    public function ordenar() {
        developer_log("obteniendo debitos en TCO");
        $recordset = Agenda_vinvulo::select_ordenador_mercadopago();
        $tmp = new Tokenizer_mercadopago();
	developer_log("ordenando ".$recordset->rowCount()." Registros");
        foreach ($recordset as $row) {
            Model::StartTrans();
            $issuer = $tmp->obtener_tipo_tarjeta($row["tco"]);
            if ($issuer == false) {
		developer_log("Issuer falso ". $issuer);
                Model::FailTrans();
            }
		var_dump("fuera");
            if (!Model::HasFailedTrans()) {
		var_dump("entra");
                $tipo_tarjeta = $issuer["id"]; //number
                $nombre_tarjeta = $issuer["payment_method_id"]; //type credit card
                $external_reference = $row["barcode"];
                if (!isset($row["id_tarjeta"]) and $row["id_tarjeta"] == false) {
                    developer_log("tokenizando");
                    $tipo = $row["id_tipodoc"] == Tipodoc::DNI ? "DNI" : "CUIL";
                    $fecha = DateTime::createFromFormat("Y-m-d", $row["fecha_vto"]);
                    $row["token"] = $tmp->tokenizar($row["tco"], $row["cvv"], $fecha->format("m"), $fecha->format("Y"), $row["apellido_rs"] . ", " . $row["nombre"], $row["documento"], $tipo);
                    $result = $tmp->realizar_reserva($row["id_clima_tco"], floatval($row["monto_apagar"]), $row["token"], $row["boleta_concepto"], $tipo_tarjeta, $nombre_tarjeta, $row["email"], $row["id_customer_mp"], $external_reference, $row["nombre"], $row["apellido_rs"], $row["documento"], $row["telefonos"]);
                } else {
                    developer_log("retokenizando");
                    $row["token"] = $tmp->retokenizar($row["id_tarjeta"]);
//                    $result = $tmp->realizar_cobro_recurrente($row["id_clima_tco"], floatval($row["monto_apagar"]), $row["token"], $row["boleta_concepto"], $tipo_tarjeta, $nombre_tarjeta, $row["id_customer_mp"], $external_reference);
                    $result = $tmp->realizar_cobro_recurrente($row["id_clima_tco"], floatval($row["monto_apagar"]), $row["token"], $row["boleta_concepto"], $tipo_tarjeta, $nombre_tarjeta, $row["email"], $row["id_customer_mp"], $external_reference, $row["nombre"], $row["apellido_rs"], $row["documento"], $row["telefonos"]);
                }
                if (!isset($result["id"])) {
                    Model::FailTrans();
                }
                if (!Model::HasFailedTrans()) {
                    developer_log("Intentando realizar la reserva");
                    if ($tmp->is_pago_realizado($result)) {
//                var_dump($result);
                        developer_log("reserva realizada correctamente");
                        if (!isset($row["id_customer_mp"]) and $row["id_customer_mp"] == false) {
                            $tmp->asociar_tco_customer($row["id_clima_tco"], $row["token"], $nombre_tarjeta, $tipo_tarjeta, $row["email"], $row["tco"], $fecha);
                            $debitado = $tmp->procesar_cobro_reserva($result["id"]);
                        } else
                            $debitado = true;
                    }
                    else {
			developer_log("Fallo al realizar la reserva");
			var_dump($row);
                        $debitado = false;
                    }
                    developer_log("Actualizando agenda");
                    $agenda_vinvulo = new Agenda_vinvulo();
                    $agenda_vinvulo->get($row["id_agenda_vinvulo"]);
                    if ($debitado)
                        $agenda_vinvulo->set_id_authstat(Authstat::DEBITO_DEBITADO);
                    else
                        $agenda_vinvulo->set_id_authstat(Authstat::DEBITO_OBSERVADO);
                    if (!$agenda_vinvulo->set()) {
                        Gestor_de_log::set("Error al actualizar la agenda");
                        Model::FailTrans();
                    }
                    $rs = Transas::select(array("id_entidad" => Entidad::ENTIDAD_BARCODE, "id_referencia" => $row["id_barcode"]));
                    if ($rs->rowCount() > 0) {
                        $rw = $rs->fetchRow();
                        $transa = new Transas($rw);
//                var_dump($transa);
                        if ($debitado)
                            $transa->set_id_authstat(Authstat::MERCADOPAGO_TRANSACCION_APROBADA);
                        else
                            $transa->set_id_authstat(Authstat::MERCADOPAGO_TRANSACCION_RECHAZADA);
                        if (!$transa->set()) {
                            Gestor_de_log::set("Error al actualizar la Transa");
                            Model::FailTrans();
                        }
                    }
                }
            }
            if(Model::CompleteTrans())
                developer_log ("Completado correctamente.");
            else{
                developer_log("Completado incorrectamente");
            }
        }
    }

}
