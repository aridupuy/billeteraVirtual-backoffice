<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ordenador_debitos_decidir
 *
 * @author ariel
 */
class Ordenador_debitos_decidir {

    //put your code here

    protected function consultar_bdd() {
        $decidir = true;
        $recordset = Debito_tco::select_ordenador_mercadopago(false, $decidir);
        return $recordset;
    }

    public function ordenar() {
        $cantidad_error = 0;
        $cantidad_correctos = 0;
        $rs = $this->consultar_bdd();
        $total = $rs->rowCount();
        foreach ($rs as $row) {
            Model::StartTrans();

            if (!Model::HasFailedTrans() and!$this->ordenar_debito($row)) {
                Model::FailTrans();
                developer_log("El debito no pudo ser ordenado a Decidir.");
            }

            if (!Model::HasFailedTrans() and Model::CompleteTrans()) {
                developer_log("Ordenado");
                $cantidad_correctos++;
            } else {
                $cantidad_error++;
            }
        }
        return array(!$error, $cantidad_correctos, $cantidad_error, $total);
    }

    public function cobrar_uno($debito) {
        return $this->ordenar_debito($debito);
    }

    protected function ordenar_debito($row) {

        list($token, $bin) = $this->obtener_token($row);
        if (!$token) {
            return false;
        }
        if (($respuesta = $this->cobrar($token, $bin, $row))) {
            return $respuesta;
        }
        return true;
    }

    protected function actualizar_deuda($debito_tco, $response, $row, $mensaje = false) {
        developer_log("Actualizando el debito");
        $debito = new Debito_tco();
        $debito->set_id_debito($row["id_debito"]);
        $debito->set_fecha_envio('now()');
        if ($mensaje) {
            $debito->set_motivorechazo($mensaje);
        }
        $debito->set_id_authf1(self::obtener_id_authstat($response));
        if(is_array($response)){
            $response = json_decode($response[1],true);
            $debito->set_motivorechazo("Parametro invalido ".$response[0]["validation_errors"][0]["param"]);
        }
        else if (isset($response->getStatus_details()->error["type"]) and isset($response->getStatus_details()->error["reason"]["description"])) {
            $debito->set_motivorechazo($response->getStatus_details()->error["reason"]["description"]);
        }
        
        if ($debito->set()) {
            return true;
        }

        return false;
    }

    private static function obtener_id_authstat($response) {
        if (is_array($response)) {
            $status = "rejected";
            
        } else {
            $status = $response->getStatus();
        }
        switch ($status) {
            case "approved":
                return Authstat::DEBITO_DEBITADO;
                break;
            case "preapporved":
                return Authstat::DEBITO_ENVIADO;
                break;
            case "review":
                return Authstat::DEBITO_OBSERVADO;
                break;
            case "rejected":
                return Authstat::DEBITO_OBSERVADO;
                break;
            default :
                return Authstat::DEBITO_OBSERVADO;
                break;
        }
        return false;
    }

    

    protected function obtener_token($row) {
        developer_log("Obteniendo el token");
        try {
//            $keys = array('public_key' => Pago_tc_decidir::API_PUBLIC_KEY,
//                'private_key' => Pago_tc_decidir::API_PRIVATE_KEY);
//            $ambiente = "test"; //valores posibles: "test" o "prod"
//            $conector = new Decidir\Connector($keys, $ambiente);
            $curl = curl_init();
            $tc = $row["tco"];
            $fecha_vto = DateTime::createFromFormat('Y-m-d', $row['fecha_vto']);
            $mes = $fecha_vto->format("m");
            $año = $fecha_vto->format("y");
            $cvv = $row["cvv"];
            $nombre = $row["titular"];
            switch ($row["id_tipodoc"]) {
                case Tipodoc::CUIT_CUIL:
                    $tipo_documento = "cuil";
                    break;
                case Tipodoc::DNI;
                    $tipo_documento = "dni";
                    break;
            }
//            $tipo_documento = $row["id_tipodoc"];
            $documento = $row["documento"];
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://live.decidir.com/api/v2/tokens",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => '{"card_number":"' . $tc . '",
                                     "card_expiration_month":"' . $mes . '","card_expiration_year":"' . $año . '"
                                     ,"security_code":"' . $cvv . '","card_holder_name":"' . ($nombre) . '"
                                     ,"card_holder_identification":{"type":"' . $tipo_documento . '"
                                     ,"number":"' . $documento . '"}}',
                CURLOPT_HTTPHEADER => array(
                    "apikey: " . Pago_tc_decidir::API_PUBLIC_KEY,
                    "cache-control: no-cache",
                    "content-type: application/json"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                $this->adjuntar_mensaje_para_usuario("cURL Error #:" . $err);
            } else {
                $response = json_decode($response, true);
                developer_log($response);
                if (isset($response["error_type"])) {
                    return false;
                }
                return array($response["id"], $response["bin"]);
            }
        } catch (Decidir\Exception\SdkException $e) {
            developer_log($e->getMessage());
            developer_log($e->getData());
            return false;
        }
    }

    protected function cobrar($token, $bin, $row) {
        developer_log("Cobrando");
//        $keys = array('public_key' => Pago_tc_decidir::API_PUBLIC_KEY,
//            'private_key' => Pago_tc_decidir::API_PRIVATE_KEY);
//        $ambiente = "prod"; //valores posibles: "test" o "prod"
//        $conector = new Decidir\Connector($keys, $ambiente);
        $rs = Debito_tco::select_one($row["id_debito"]);
        $debito_tco = new Debito_tco($rs->fetchRow());
//        $debito_tco->get($row["id_debito"]);
        $marchand = new Marchand();
        $marchand->get($debito_tco->get_id_marchand());
        $fecha = new DateTime("now");
        $pagos_tc = new Pago_tc_decidir();
        $response = $pagos_tc->generar_pago($token, $row["id_debito"] . "_fecha_" . $fecha->format("Y_m_d"), $marchand, $row["concepto"], $debito_tco->get_monto(), $bin, Pago_tc_decidir::obtener_issued($row["tco"]), $row["email"], $row["id_clima_tco"], Entidad::ENTIDAD_DEBITO_TCO, $debito_tco->get_id(), true);
        $response_in = $response;
//        var_dump($response_in);
        if ($debito_tco->set()) {
            if (!Model::HasFailedTrans() and!$this->actualizar_deuda($debito_tco, $response, $row)) {
                Model::FailTrans();
                developer_log("La deuda no pudo ser actualizada.");
            }
            return $response_in;
        }
        //////        
////        $data = array(
////            "site_transaction_id" => $row["id_debito"]."_fecha_".$fecha->format("Y_m_d"),
////            "token" => $token,
////            "customer" => array(
////                "id" => $row["id_clima_tco"],
////                "email" => $row["email"],
//////                "ip_address" => $_SERVER["SERVER_ADDR"]
////                "ip_address" => '190.184.254.70'
////            ),
////            "payment_method_id" => 15,
////            "bin" => $bin,
////            "amount" => $row["monto"],
////            "currency" => "ARS",
////            "installments" => 1,
////            "fraud_detection" => true,
////            "description" => $row["concepto"],
////            "establishment_name" => $marchand->get_minirs(),
////            "payment_type" => "single",
////            "sub_payments" => array()
////        );
////        var_dump($data);
////        try {
////            $response = $conector->payment()->ExecutePayment($data);
////            $status = $response->getStatus();
////            switch ($status) {
////                case "approved":
////                    $debito_tco->set_id_authf1(Authstat::DEBITO_DEBITADO);
////                    break;
////                case "preapporved":
////                    $debito_tco->set_id_authf1(Authstat::DEBITO_ENVIADO);
////                    break;
////                case "review":
////                    $debito_tco->set_id_authf1(Authstat::DEBITO_OBSERVADO);
////                    $debito_tco->set_motivorechazo("Cobro tc en revision");
////                    break;
////                case "rejected":
////                    $tipo_error = $response->getStatus_details()->error["type"];
////                    $msj_error = $response->getStatus_details()->error["reason"]["description"];
//////                                print_r($response);
//////                                exit();
////                    $debito_tco->set_id_authf1(Authstat::DEBITO_OBSERVADO);
////                    $debito_tco->set_motivorechazo($tipo_error . " :  " . $msj_error);
////                    $respuesta["error"] = $tipo_error . " :  " . $msj_error;
////                    break;
////                default :
////                    Model::FailTrans();
////            }
////            
////            
//        } catch (Exception $e) {
//            developer_log("Exception:".$e->getMessage());
//            developer_log($e->getData());
//            return $e->getData();
////            return false;
//        }
    }
    public function devolver($id) {
       $transaccion_decidir = Decidir_transaccion::obtener_transaccion_por_id($id);
       $pago_tc_decidir = new Pago_tc_decidir();
       return $pago_tc_decidir->devolver_pago($transaccion_decidir);
    }
//    protected function guardar_transaccion(Debito_tco $debito_tco,$response){
//        $pago_tc_decidir=new Pago_tc_decidir();
//        return $pago_tc_decidir->guardar_transaccion($response, Entidad::ENTIDAD_DEBITO_TCO, $debito_tco->get_id(), $debito_tco->get_id_authf1(), $debito_tco->get_id_marchand(), $debito_tco->get_monto(), $debito_tco->get_concepto());
//    }
}
