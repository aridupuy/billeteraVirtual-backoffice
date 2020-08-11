<?php

include_once '../public/sdk_decidir/vendor/autoload.php';

class Webservice_pago_tc_decidir extends Webservice {

    const MONTO = "monto";
    const MP = "220";
    const TC = "tarjeta";
    const CVV = "cvv";
    const MES_EXPIRACION = "mes_expiracion";
    const AÑO_EXPIRACION = "anio_expiracion";
    const DOCUMENTO = "documento";
    const NOMBRE = "nombre";
    const APELLIDO = "apellido";
    const TIPO_DOCUMENTO = "tipo_documento";
    const API_PUBLIC_KEY = '96e7f0d36a0648fb9a8dcb50ac06d260';
    const API_PRIVATE_KEY = '1b19bb47507c4a259ca22c12f78e881f';
    const CONCEPTO = "concepto";
    const EMAIL = "email";

    public function ejecutar($array) {
        if (!isset($array[self::MONTO])) {
            $this->adjuntar_mensaje_para_usuario("Falta el parametro " . self::MONTO);
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        if (!isset($array[self::TC])) {
            $this->adjuntar_mensaje_para_usuario("Falta el parametro " . self::TC);
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        if (!isset($array[self::CVV])) {
            $this->adjuntar_mensaje_para_usuario("Falta el parametro " . self::CVV);
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        if (!isset($array[self::MES_EXPIRACION])) {
            $this->adjuntar_mensaje_para_usuario("Falta el parametro " . self::MES_EXPIRACION);
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        if (!isset($array[self::AÑO_EXPIRACION])) {
            $this->adjuntar_mensaje_para_usuario("Falta el parametro " . self::AÑO_EXPIRACION);
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        if (!isset($array[self::TIPO_DOCUMENTO])) {
            $this->adjuntar_mensaje_para_usuario("Falta el parametro " . self::TIPO_DOCUMENTO);
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        if (!isset($array[self::DOCUMENTO])) {
            $this->adjuntar_mensaje_para_usuario("Falta el parametro " . self::DOCUMENTO);
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        if (!isset($array[self::EMAIL])) {
            $this->adjuntar_mensaje_para_usuario("Falta el parametro " . self::EMAIL);
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        if (!isset($array[self::CONCEPTO])) {
            $this->adjuntar_mensaje_para_usuario("Falta el parametro " . self::CONCEPTO);
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        if (!isset($array[self::EMAIL])) {
            $this->adjuntar_mensaje_para_usuario("Falta el parametro " . self::EMAIL);
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }





        $keys = array('public_key' => self::API_PUBLIC_KEY,
            'private_key' => self::API_PRIVATE_KEY);
        $ambiente = "test"; //valores posibles: "test" o "prod"
        $conector = new Decidir\Connector($keys, $ambiente);
        list($token, $bin) = $this->obtener_token($array);
        $email = $array["email"];
        $monto = $array["monto"];
        $concepto = $array["concepto"];
        $responsable = new Responsable();
        $debito_tco = new Debitos_tco();
        switch ($array[self::TIPO_DOCUMENTO]) {
            case "dni":
                $tipo_doc = Tipodoc::DNI;
                break;
            default :
                $tipo_doc = Tipodoc::CUIT_CUIL;
                break;
        }
        Model::StartTrans();
        try {
            if (($responsable = $responsable->crear($this->get_marchand()->get_id_marchand(), $array[self::NOMBRE], $array[self::APELLIDO], $array[self::DOCUMENTO], $tipo_doc, $array[self::EMAIL]))) {
                $fecha2 = (DateTime::createFromFormat("!ym", $array[self::AÑO_EXPIRACION] . $array[self::MES_EXPIRACION]));
                $array[self::AÑO_EXPIRACION] = $fecha2->format("Y");
                if (($responsable = $responsable->crear_tco($this->get_marchand()->get_id_marchand(), $responsable::$clima->get_id_clima(), $array[self::TC], $array[self::CVV], $array[self::MES_EXPIRACION], $array[self::AÑO_EXPIRACION], $array[self::NOMBRE] . " " . $array[self::APELLIDO], $array[self::DOCUMENTO]))) {
                    $fecha = new DateTime("now");
                    if (($debito_tco = $debito_tco->crear($this->get_marchand()->get_id(), $responsable::$clima_tco->get_id(), $monto, $fecha->format("d/m/Y"), $array[self::CONCEPTO], 1, "mensuales", false, false, false, false, 100, "Decidir por ws3", $responsable::$clima_tco->get_titular(), $array[self::TC], $array[self::CVV], $fecha2,"decidir"))) {

                        $data = array(
                            "site_transaction_id" => $debito_tco->debito_tco->get_id(),
                            "token" => $token,
                            "customer" => array(
                                "id" => $responsable::$clima->get_id(),
                                "email" => $email,
                                "ip_address" => $_SERVER["SERVER_ADDR"]
                            ),
                            "payment_method_id" => 1,
                            "bin" => $bin,
                            "amount" => $monto,
                            "currency" => "ARS",
                            "installments" => 1,
                            "fraud_detection" => true,
                            "description" => $concepto,
                            "establishment_name" => $this->get_marchand()->get_minirs(),
                            "payment_type" => "single",
                            "sub_payments" => array()
                        );

                        $response = $conector->payment()->ExecutePayment($data);
                        $status = $response->getStatus();
                        switch ($status) {
                            case "approved":
                                $debito_tco->debito_tco->set_id_authf1(Authstat::DEBITO_DEBITADO);
                                $this->costear_debito($debito_tco);
                                break;
                            case "preapporved":
                                $debito_tco->debito_tco->set_id_authf1(Authstat::DEBITO_ENVIADO);
                                break;
                            case "review":
                                $debito_tco->debito_tco->set_id_authf1(Authstat::DEBITO_OBSERVADO);
                                $debito_tco->debito_tco->set_motivorechazo("Cobro tc en revision");
                                break;
                            case "rejected":
                                $tipo_error = $response->getStatus_details()->error["type"];
                                $msj_error = $response->getStatus_details()->error["reason"]["description"];
//                                print_r($response);
//                                exit();
                                $debito_tco->debito_tco->set_id_authf1(Authstat::DEBITO_OBSERVADO);
                                $debito_tco->debito_tco->set_motivorechazo($tipo_error . " :  " . $msj_error);
                                $respuesta["error"]=$tipo_error . " :  " . $msj_error;
                                break;
                            default :
                                Model::HasFailedTrans();
                                $this->adjuntar_mensaje_para_usuario("Error en la respuesta");
                                break;
                        }
                        $debito_tco->debito_tco->set();
                        $this->adjuntar_dato_para_usuario($status);

                        $respuesta["token"] = $token;
                        $respuesta["estado"] = $status = $response->getStatus();
                    } else {
                        Model::HasFailedTrans();
                        $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
                    }
                } else {
                    Model::HasFailedTrans();
                    $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
                }
            } else {
                Model::HasFailedTrans();
                $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
            }
        } catch (\Exception $e) {
            $this->adjuntar_mensaje_para_usuario($e->getMessage());
//            $debito_tco->debito_tco->set_authf1(Authstat::DEBITO_OBSERVADO);
//            $debito_tco->debito_tco->set_motivorechazo($e-getMessage());
            Model::FailTrans();
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_INCORRECTA;
        }

        if (!Model::HasFailedTrans() and Model::CompleteTrans()) {
            $this->adjuntar_dato_para_usuario($respuesta);
            $this->respuesta_ejecucion = self::RESPUESTA_EJECUCION_CORRECTA;
        }
        return;
    }

    private function obtener_token($array) {


        $curl = curl_init();
        $tc = $array[self::TC];
        $mes = $array[self::MES_EXPIRACION];
        $año = $array[self::AÑO_EXPIRACION];
        $cvv = $array[self::CVV];
        $nombre = $array[self::NOMBRE];
        $apellido = $array[self::APELLIDO];
        $tipo_documento = $array[self::TIPO_DOCUMENTO];
        $documento = $array[self::DOCUMENTO];
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://developers.decidir.com/api/v2/tokens",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => '{"card_number":"' . $tc . '",
                                     "card_expiration_month":"' . $mes . '","card_expiration_year":"' . $año . '"
                                     ,"security_code":"' . $cvv . '","card_holder_name":"' . ($apellido . " " . $nombre) . '"
                                     ,"card_holder_identification":{"type":"' . $tipo_documento . '"
                                     ,"number":"' . $documento . '"}}',
            CURLOPT_HTTPHEADER => array(
                "apikey: " . self::API_PUBLIC_KEY,
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
            return array($response["id"], $response["bin"]);
        }
    }

}
