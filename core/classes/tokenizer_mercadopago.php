<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of tokenizer_mercadopago
 *
 * @author arieldupuy
 */
class Tokenizer_mercadopago {

    //put your code here
    public static $tipo_tarjeta = null;

    
    const ESTADO_ACREDITADO = "accredited";
    const ESTADO_APROBADO = "approved";
    const ESTADO_AUTORIZADO = "authorized";
    
    public function tokenizar($card, $cvv, $mes_v, $año_v, $nombre, $dni, $tipo = "DNI") {
        developer_log("Obteniendo el token");
//        $envio = array("card_number" => $card,
        $envio = array("card_number" => $card,
            "security_code" => $cvv,
            "expiration_month" => $mes_v,
            "expiration_year" => $año_v,
            "cardholder" => array(
                "name" => $nombre,
                "identification" => array(
                    "number" => $dni,
                    "type" => $tipo
                )
            )
        );
        var_dump(json_encode($envio));
        $url = "https://api.mercadopago.com/v1/card_tokens?public_key=" . MERCADOPAGO_CLAVE_PUBLICA_TCDAUT;
        $result = $this->post($url, $envio);
        $data = json_decode($result, true);
        $token = $data["id"];
        return $token;
    }

    public function realizar_reserva ($id_clima_tco, $monto, $token, $description, $tipo_tarjeta, $nombre_tarjeta, $email_pagador, $id_customer_mp, $external_reference, $nombre, $apellido, $nro_documento, $telefono) {
        developer_log("realizando la reserva");
        $telefono_validado = $this->tel_argentino($telefono);
        if($telefono_validado){
            $area = $telefono_validado["area"];
            $numero = $telefono_validado["numero"];
            $envio = array(
                "transaction_amount" => $monto,
                "token" => "$token",
                "description" => "$description",
                "installments" => 1,
                "payment_method_id" => "$nombre_tarjeta",
                "issuer_id" => "$tipo_tarjeta",
                "external_reference" => $external_reference,
                "payer" => array(
                    "id" => "$id_customer_mp",
                    "type" => "customer",
                    "entity_type" => "individual",
                    "first_name" => "$nombre",
                    "last_name" => "$apellido",
                    "email" => "$email_pagador",
                    "identification" => array(
                        "type" => "DNI",
                        "number" => "$nro_documento"
                    )
                ),
                "additional_info" => array(
                    "payer" => array(
                        "phone" => array(
                            "area_code" => "$area",
                            "number" => "$numero"
                        )
                    )
                ),
                "capture" => false
            );
    //        var_dump(json_encode($envio));
            $url = "https://api.mercadopago.com/v1/payments?access_token=" . MERCADOPAGO_CLAVE_PRIVADA_TCDAUT;
            $result = $this->post($url, $envio);
            var_dump($result);
            return json_decode($result, true);
            }
        developer_log("Error al procesar el numero de telefono");
        return false;
    }

    public function realizar_cobro_recurrente ($id_clima_tco, $monto, $token, $description, $tipo_tarjeta, $nombre_tarjeta, $email_pagador, $id_customer_mp, $external_reference, $nombre, $apellido, $nro_documento, $telefono) {
        developer_log("realizando cobro recurrente");
//        $envio = array(
//            "transaction_amount" => $monto,
//            "token" => "$token",
//            "description" => "$description",
//            "installments" => 1,
//            "payment_method_id" => "$nombre_tarjeta",
//            "issuer_id" => "$tipo_tarjeta",
//            "external_reference" => $external_reference,
//            "payer" => array(
//                "type" => "customer",
//                "id" => $id_customer_mp
//            ),
//        );
        $telefono_validado = $this->tel_argentino($telefono);
        if($telefono_validado){
            $area = $telefono_validado["area"];
            $numero = $telefono_validado["local"];
            $envio = array(
                "transaction_amount" => $monto,
                "token" => "$token",
                "description" => "$description",
                "installments" => 1,
                "payment_method_id" => "$nombre_tarjeta",
                "issuer_id" => "$tipo_tarjeta",
                "external_reference" => $external_reference,
                "payer" => array(
                    "id" => "$id_customer_mp",
                    "type" => "customer",
                    "entity_type" => "individual",
                    "first_name" => "$nombre",
                    "last_name" => "$apellido",
                    "email" => "$email_pagador",
                    "identification" => array(
                        "type" => "DNI",
                        "number" => "$nro_documento"
                    )
                ),
                "additional_info" => array(
                    "payer" => array(
                        "phone" => array(
                            "area_code" => "$area",
                            "number" => "$numero"
                        )
                    )
                ),
                "capture" => false
            );
    //        var_dump(json_encode($envio));
            $url = "https://api.mercadopago.com/v1/payments?access_token=" . MERCADOPAGO_CLAVE_PRIVADA_TCDAUT;
            $result = $this->post($url, $envio);
    //        var_dump($result);
            return json_decode($result, true);
        }
            developer_log("Error al procesar el numero de telefono");
            return false;
    }

    private function put($url, $data) {
        $postdata= json_encode($data);
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => $postdata,
            CURLOPT_HTTPHEADER => array(
                "Accept: application/json",
                "Cache-Control: no-cache",
                "Content-Type: application/json",
                "Postman-Token: 6dde1c28-05b8-4b89-ab15-6ffad23f7d61"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    private function post($url, $data) {
//	var_dump($data);
        $postdata = json_encode($data);
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $postdata,
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Content-Type: application/json",
                "Postman-Token: 1a943b81-5148-4400-9849-d48b80a3fd88"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    private function get($url) {
//	var_dump($data);
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Cache-Control: no-cache",
                "Postman-Token: d11523ae-3a0b-4993-8b7d-935dfa92bb1a"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public function obtener_tipo_tarjeta($tco) {
        developer_log("obteniendo tipo de tarjeta ". $tco);
       if (self::$tipo_tarjeta == null) {
            $url = "https://api.mercadolibre.com/v1/payment_methods/search?public_key=" . MERCADOPAGO_CLAVE_PUBLICA_TCDAUT;
            $mediosPagoDisponibles = $this->get($url);
            $mediosPagoDisponibles = json_decode($mediosPagoDisponibles, true);
            self::$tipo_tarjeta = $mediosPagoDisponibles;
        } else
            $mediosPagoDisponibles = self::$tipo_tarjeta;
        foreach ($mediosPagoDisponibles["results"] as $valor) {
            if (isset($valor["settings"][0])) {
                $patron = $valor["settings"][0]["bin"]["pattern"];
                $patronexclusivo = $valor["settings"][0]["bin"]["exclusion_pattern"];
                if ($patron != null) {
                    if (preg_match("/" . $patron . "/", $tco)) {
                        if ($patronexclusivo == null OR ! preg_match("/" . $patronexclusivo . "/", $tco)) {
                            $issuer = $valor["issuer"];
                            $issuer["payment_method_id"] = $valor["id"];
                        }
                    }
                }
            }
            developer_log("Tarjeta " . json_encode($issuer));
            if(!isset($issuer)){
//                var_dump($tco);
            }
        }
        return $issuer;
    }

    public function is_pago_realizado($resultado) {
        var_dump($resultado["status"]);
        if ($resultado["status"] == self::ESTADO_APROBADO OR $resultado["status"] == self::ESTADO_ACREDITADO OR $resultado["status"] == self::ESTADO_AUTORIZADO)
            return true;
        return false;
    }

    public function asociar_tco_customer($id_clima_tco, $token, $nombre_tarjeta, $issuer_id, $email,$tco, DateTime $fecha) {
        developer_log("asociando un customer a tco");
        if(!($id=$this->obtener_customer($email)))
            $id = $this->crear_tco_customer($email);
        $resultado=array();
        if(!($resultado["id"]=$this->obtener_tco($id,$tco,$fecha))){
            $url = "https://api.mercadopago.com/v1/customers/$id/cards?access_token=" . MERCADOPAGO_CLAVE_PRIVADA_TCDAUT;
            $envio = array("token" => $token, "payment_method_id" => $nombre_tarjeta, "issuer_id" => $issuer_id);
            developer_log("asociando tco");
            $resultado = $this->post($url, $envio);
            $resultado = json_decode($resultado, true);
        }
        if (isset($resultado["id"])) {
            developer_log("Guardando tarjeta del pagador");
            $clima_tco_token = new Clima_tco_token();
            $clima_tco_token->set_id_clima_tco($id_clima_tco);
            $clima_tco_token->set_token($token);
            $clima_tco_token->set_id_tarjeta($resultado["id"]);
            $clima_tco_token->set_id_customer_mp($id);
            if ($clima_tco_token->set()) {
                return true;
            }
        }
    }

    private function crear_tco_customer($email) {
        developer_log("creando un customer");
        $url = "https://api.mercadopago.com/v1/customers?access_token=" . MERCADOPAGO_CLAVE_PRIVADA_TCDAUT;
        $envio = array("email" => $email);
        $resultado = $this->post($url, $envio);
        $resultado = json_decode($resultado, true);
//        var_dump($resultado);
        return $resultado["id"]; //id del customer
    }

    public function retokenizar($id_tarjeta) {
        $url = "https://api.mercadopago.com/v1/card_tokens?public_key=" . MERCADOPAGO_CLAVE_PUBLICA_TCDAUT;
        $envios = array("card_id" => $id_tarjeta);
        $respuesta = $this->post($url, $envios);
        $respuesta = json_decode($respuesta, true);
//        var_dump($respuesta);
        return $respuesta["id"];
    }
    
    public function procesar_cobro_reserva($id){
        $url="https://api.mercadopago.com/v1/payments/$id?access_token=".MERCADOPAGO_CLAVE_PRIVADA_TCDAUT;
	var_dump($url);
        $envio=array("capture"=>true);
        $resultado=$this->put($url, $envio);
        $resultado= json_decode($resultado,true);
        return $this->is_pago_realizado($resultado);
    }
    
    private function obtener_customer($email){
        $url="https://api.mercadopago.com/v1/customers/search?access_token=".MERCADOPAGO_CLAVE_PRIVADA_TCDAUT."&email=".$email;
        $resultado=$this->get($url);
        $resultado= json_decode($resultado, true);
        if(isset($resultado["paging"]["total"]) and  $resultado["paging"]["total"]>0){
            return $resultado["results"][0]["id"];
        }
        return false;
    }
    private function obtener_tco($id_customer,$tco, DateTime $fecha){
        $first= substr($tco, 0,6);
        $last= substr($tco, strlen($tco)-4,4);
        $expiration_month=$fecha->format("m");
        $expiration_year=$fecha->format('y');
        $url="https://api.mercadopago.com/v1/customers/$id_customer/?access_token=".MERCADOPAGO_CLAVE_PRIVADA_TCDAUT."&last_four_digits=$last&first_six_digits=$first&expiration_month=$expiration_month&expiration_year=$expiration_year";
        $result=$this->get($url);
        $result= json_decode($result,true);
        if(isset($result["cards"][0]["id"]))
            return $result["cards"][0]["id"];
        return false;
    }
    
    private function tel_argentino($tel) {
    $re = '/^(?:((?P<p1>(?:\( ?)?+)(?:\+|00)?(54)(?<p2>(?: ?\))?+)(?P<sep>(?:[-.]| (?:[-.] )?)?+)(?:(?&p1)(9)(?&p2)(?&sep))?|(?&p1)(0)(?&p2)(?&sep))?+(?&p1)(11|([23]\d{2}(\d)??|(?(-10)(?(-5)(?!)|[68]\d{2})|(?!))))(?&p2)(?&sep)(?(-5)|(?&p1)(15)(?&p2)(?&sep))?(?:([3-6])(?&sep)|([12789]))(\d(?(-5)|\d(?(-6)|\d)))(?&sep)(\d{4})|(1\d{2}|911))$/D';
    if (preg_match($re,$tel,$match)) {
        //texto capturado por cada grupo -> variables individuales
        list(
            ,$internacional_completo,,$internacional,,,$internacional_celu,$prefijo_acceso,$area,,,
            $prefijo_celu,$local_1a,$local_1b,$local_1c,$local_2,$numero_social
        ) = array_pad($match,20,'');

        //arreglar un poco los valores
        $local_1 = $local_1a . $local_1b . $local_1c;
        $local = $local_1 . $local_2;
        $es_fijo = !($internacional_celu || $prefijo_celu);
        $numero = $area.$local.$numero_social;
        $completo = $internacional.$internacional_celu.$area.$prefijo_celu.$local.$numero_social;

        //devolver sólo lo que importa en un array
        return compact(
                   'numero','completo','internacional','internacional_celu','area',
                   'prefijo_celu','local','local_1','local_2','numero_social','es_fijo'
               );
    }
    return false;
}

}
