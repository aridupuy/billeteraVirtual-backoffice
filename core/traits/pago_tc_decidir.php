<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of debito_tco_decidir
 *
 * @author ariel
 */
include_once PATH_PUBLIC.'/sdk_decidir/vendor/autoload.php';

class Pago_tc_decidir extends Pago_electronico{

//    const API_PUBLIC_KEY = 'e9cdb99fff374b5f91da4480c8dca741';
//    const API_PRIVATE_KEY = '92b71cf711ca41f78362a7134f87ff65';
    /* test para cobrodigital */
//    const API_PUBLIC_KEY = '96e7f0d36a0648fb9a8dcb50ac06d260';
//    const API_PRIVATE_KEY = '1b19bb47507c4a259ca22c12f78e881f';
    /* Produccion */
//    site id = 00160829 real
    const API_PUBLIC_KEY = '792ead6671d24c59933a7394f13e7101';
    const API_PRIVATE_KEY = '017ed21f64bd489ab9a5bf2da3c8f573';

    public $keys = array('public_key' => self::API_PUBLIC_KEY,
        'private_key' => self::API_PRIVATE_KEY);
    public $ambiente = "prod"; //valores posibles: "test" o "prod"
    public static $mp;

    public function get_keys() {
        return $this->keys;
    }

    public function get_ambiente() {
        return $this->ambiente;
    }

    public function generar_pago(...$param) {
       list($token, $id_transacction, $marchand, $concepto, $monto, $bin,$tipotarjeta, $email, $id_customer, $entidad, $referencia)=$param;
        
        $conector = new Decidir\Connector($this->keys, $this->ambiente);
        $data = array(
            "site_transaction_id" => "pago_$bin" . "_" . $id_transacction,
            "token" => $token,
            "customer" => array(
                "id" => $id_customer,
                "email" => $email,
//                "ip_address" => $_SERVER["SERVER_ADDR"]
                "ip_address" => "190.184.254.70"
            ),
            "payment_method_id" => (int) $this->obtener_id_metodo_de_pago($tipotarjeta, $bin),
            "bin" => "$bin",
            "amount" => $monto,
            "currency" => "ARS",
            "installments" => 1,
            "fraud_detection" => "false",
            "description" => $concepto,
            "establishment_name" => $marchand->get_minirs(),
            "payment_type" => "single",
            "sub_payments" => array()
        );
//        var_dump($data);
        developer_log(json_encode($data));
//        var_dump($data);
        try {
            $response = $conector->payment()->ExecutePayment($data);

            $status = $response->getStatus();

            switch ($status) {
                case "approved":
                    $mensaje = $this->guardar_transaccion($response, $entidad, $referencia, Authstat::MERCADOPAGO_TRANSACCION_APROBADA, $marchand->get_id(), $monto, $concepto);
                    $status = true;
                    break;
                case "preapporved":
                    $mensaje = $this->guardar_transaccion($response, $entidad, $referencia, Authstat::MERCADOPAGO_TRANSACCION_PENDIENTE, $marchand->get_id(), $monto, $concepto);
                    $status = true;
                    break;
                case "review":
                    $mensaje = $this->guardar_transaccion($response, $entidad, $referencia, Authstat::MERCADOPAGO_TRANSACCION_PENDIENTE, $marchand->get_id(), $monto, $concepto);
                    $status = true;
                    break;
                case "rejected":
                    $tipo_error = $response->getStatus_details()->error["type"];
                    $msj_error = $response->getStatus_details()->error["reason"]["description"];
                    developer_log($response->getStatus_details());
//                    var_dump($response->getStatus_details());
                    $mensaje = $this->guardar_transaccion($response, $entidad, $referencia, Authstat::MERCADOPAGO_TRANSACCION_RECHAZADA, $marchand->get_id(), $monto, $concepto);
                    $mensaje = $msj_error;
                    $status = false;
                    break;
                default :
                    Model::FailTrans();
                    return array(false, "Error en la respuesta");
                    break;
            }
            if($return_respuesta)
                return $response;
            return array($status, $mensaje);
        } catch (Decidir\Exception\SdkException $e) {
            $json = json_encode([$e->getData(),$e->getMessage()]);
            $data = $e->getData();
            if (isset($data[0]["param"])) {
                if ($data[0]["param"] == "payment_method_id") {
                    $json = "No podemos procesar este tipo de tarjeta aun, por favor intenta con otra!";
                }
            }
            return array(false, $json);
        }
    }

    private function obtener_id_metodo_de_pago($dato, $bin) {
//        var_dump($dato);
        $array = array(
            "Visa" => "1",
            "MasterCard" => "104", //prisma ej uala
//                    "MasterCard"=>"15",
            "Amex" => "65",
//                    "Amex"=>"1",
            "Diners Club" => "8",
            "Cabal Prisma" => "63",
            "Tarjeta Shopping" => "23",
            "Naranja" => "24",
//                    "Naranja"=>"1",
            "Tarjeta Nevada" => "39",
            "Nativa" => "42",
            "Tarjeta Más" => "43",
            "Tarjeta Carrefour / Cetelem" => "44",
            "Tarjeta Club Día" => "56",
            "Tarjeta La Anónima" => "61",
            "ArgenCard" => "30",
            "Credimás" => "38",
            "Maestro" => "99",
            "Grupar" => "54",
            "Tuya" => "59",
            "Favacard" => "103",
            "Italcred" => "29",
            "CoopePlus" => "34",
            "Nexo" => "37",
            "Tarjeta PymeNacion" => "45",
            "BBPS" => "50",
            "Qida" => "52",
            "Patagonia 365" => "55",
            "Distribution" => "60",
            "CrediGuia" => "62",
            "Tarjeta SOL" => "64",
            "PagoFacil" => "25",
            "RapiPago" => "26",
            "Caja de Pagos" => "48",
            "Cobro Express" => "51",
            "Visa Débito" => "31",
            "MasterCard Debit" => "66",
            "Cabal Débito" => "67"
        );
//            if(substr($bin, 0,2)=="52" || substr($bin, 0,2)=="53"){ // agregar mas a futuro si es posible
//                developer_log("el codigo que se devuelve es 104");
//                return $array["MasterCard Prisma"];
//            }

        if (isset($array[$dato])) {
            developer_log($array[$dato]);
            return (int) $array[$dato];
        }
        return 1;
    }
    public function guardar_transaccion(...$param) {
        list($response, $entidad, $referencia, $authstat,$id_marchand, $monto, $concepto)=$param;
        $transa = new Decidir_transaccion();
        self::$mp = new Mp();
        self::$mp->get(221); #ver
        $fecha = new DateTime("now");
        $transa->set_fecha($fecha->format("Y-m-d"));
        $transa->set_fecha_gen((new DateTime("now"))->format("Y-m-d"));
        $transa->set_id_authstat($authstat);
        $transa->set_id_entidad($entidad);
        $transa->set_id_referencia($referencia);
        $transa->set_concepto($concepto);
        $transa->set_status($response->getStatus());
        $transa->set_id_marchand($id_marchand);
        $transa->set_monto($monto);
        $transa->set_response(json_encode($response->dataResponse));
        if ($transa->set()) {
            developer_log("guardado");
            return "Pago realizado correctamente.";
        }
        return "Error al registrar el pago en cobrodigital";
    }

    public function obtener_siguiente_num_transaccion() {
        $transaccion = new Decidir_transaccion();
        return $transaccion->obtener_id();
    }

    public function obtener_pagos() {
        $rs = Decidir_transaccion::select_pagos_validacion();
        $array = array();
        foreach ($rs as $row) {
            $array[] = new Decidir_transaccion($row);
        }
        return $array;
    }
    
    public function obtener_pagos_realizados() {
        $rs = Decidir_transaccion::select_pagos_realizados_validacion();
        $array = array();
        foreach ($rs as $row) {
            $array[] = new Decidir_transaccion($row);
        }
        return $array;
    }
    
    public function devolver_pago(Decidir_transaccion $transaccion) {
        $conector = new Decidir\Connector($this->keys, $this->ambiente);
        $response_pago = json_decode($transaccion->get_response());
        $data = array();
        return $conector->payment()->Refund($data, $response_pago->id);
    }

    public function devolucion_parcial(Decidir_transaccion $transaccion,$monto) {
        try{
        Model::StartTrans();
        $conector = new Decidir\Connector($this->keys, $this->ambiente);
        $response_pago = json_decode($transaccion->get_response());
        $data = array(
            "amount" => $monto
	);
        $rs = $conector->payment()->partialRefund($data, $response_pago->id);
        switch ($transaccion->get_id_entidad()){
            case Entidad::ENTIDAD_BARCODE:
                $entidad= new Barcode();
                break;
            case Entidad::ENTIDAD_DEBITO_TCO:
                $entidad= new Debito_tco();
                break;
        }
        $entidad->get($transaccion->get_id_referencia());
        $transacciones = new Transaccion_decidir_reverso_parcial();
        if($transacciones->crear($entidad->get_id_marchand(), Mp::DECIDIR_DEVOLUCION, $monto, (new DateTime("now")), $entidad->get_id())){
            $transaccion->set_status(Preprocesador_pei::ESTADO_DEVUELTO_PARCIAL);
            $transaccion->set_monto($transaccion->get_monto()-$monto);
            $transaccion->set();
            Model::CompleteTrans();
        }
        else{
            throw new Exception("Error al generar la devolucion parcial");
        }
        return $rs;
        } catch (Exception $e){
            developer_log($e->getMessage());
            Model::FailTrans();
            Model::CompleteTrans();
        }
        //return $conector->payment()->partialRefund($data, $response_pago->id);
    }

    public static function obtener_issued($nrotarj) {


//        visa debito return new RegExp("^(" + this.visaDebitBinesRegex + ")[0-9]{10}$"
        //visa debito return [400276, 400448, 400615, 402789, 402914, 404625, 405069, 405515, 405516, 405517, 405755, 405896, 405897, 406290, 406291, 406375, 406652, 406998, 406999, 408515, 410082, 410083, 410121, 410122, 410123, 410853, 411849, 417309, 421738, 423623, 428062, 428063, 428064, 434795, 437996, 439818, 442371, 442548, 444060, 444493, 446343, 446344, 446345, 446346, 446347, 450412, 451377, 451701, 451751, 451756, 451757, 451758, 451761, 451763, 451764, 451765, 451766, 451767, 451768, 451769, 451770, 451772, 451773, 457596, 457665, 462815, 463465, 468508, 473227, 473710, 473711, 473712, 473713, 473714, 473715, 473716, 473717, 473718, 473719, 473720, 473721, 473722, 473725, 476520, 477051, 477053, 481397, 481501, 481502, 481550, 483002, 483020, 483188, 489412, 492528, 499859]

        $array_issues = array(
            "WishGift" => "/^637046[0-9]{10}$/",
            "Favacard" => "/^504408[0-9]{12}$/",
            "Naranja" => "/^589562[0-9]{10}$/",
            "Visa DÃ©bito" => self::obtener_visa_debito_regex(),
            "CoopePlus" => "/^627620[0-9]{10}$/",
            "Nevada" => "/^504363[0-9]{10}$/",
            "Nativa" => "/^(520053|546553|487017)[0-9]{10}$/",
            "Cencosud" => "/^603493[0-9]{10}$/",
            "Carrefour" => "/^(507858|585274)[0-9]{10}(?:[0-9]{3})?$/",
            "PymeNaciÃ³n" => "/^504910[0-9]{10}$/",
            "BBPS" => "/^627401[0-9]{10}$/",
            "Qida" => "/^504570[0-9]{10}$/",
            "Grupar" => "/^(606301|605915)[0-9]{10}$/",
            "Patagonia 365" => "/^504656[0-9]{10}$/",
            "Club DÃ­a" => "/^636897[0-9]{10}$/",
            "Tuya" => "/^588800[0-9]{10}$/",
            "La AnÃ³nima" => "/^421024[0-9]{10}$/",
            "CrediGuia" => "/^603288[0-9]{10}$/",
            "Cabal Prisma" => "/^589657[0-9]{10}$/",
            "SOL" => "/^504639[0-9]{10}$/",
            "Cabal 24" => "/^(6042|6043)[0-9]{12}$/",
            "Musicred" => "/^636435[0-9]{10}$/",
            "Credimas" => "/^504520[0-9]{10}$/",
            "Discover" => "/^(65[0-9]2|6011)[0-9]{12}$/",
            "Diners" => "/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/",
            "Shopping" => "/^(279[0-9]{3}|603488|606488|589407)[0-9]{10}(?:[0-9]{3})?$/",
            "Amex" => "/^3[47][0-9]{13}$/",
            "Visa" => "/^4[0-9]{12}(?:[0-9]{3})?$/",
            "Mastercard Debit" => self::rangosMcDebito(),
            "MasterCard" => self::rangosMcCredito(),
            "MasterCard" => "/^(5[1-6]|^2[2-7])[0-9]{14}$/",
            "Maestro" => "/^5[0,8][0-9]{14},16$/"
        );


        foreach ($array_issues as $issued => $regex) {
            if(is_callable($regex)){
                if($regex($nrotarj)){
                    return utf8_decode($issued);
                }
            }
            if(is_string($regex)){
                if (preg_match($regex, $nrotarj)) {
                    return utf8_decode($issued);
                }
            }
        }






//        Visa 	 4- 
//        Mastercard 51-, 52-, 53-, 54-, 55-
//        Diners Club 36-, 38-
//        Discover 6011-, 65-
//        JCB 35-
//        American Express 34-, 37-
    }

   

    public function devolver($param) {
        try{
            $param->set_status("refounded");
            if($param->set()){
                $rs = $this->devolver_pago($param);       
                if($rs->dataResponse["status_details"]["error"]==null){
                    return array(true,"");
                }
            }
        }catch(Exception $e){
            return array(false,$e->getMessage());
        }
    }

    public function devolver_parcial($param, $monto) {
        Model::StartTrans();
        try{
            $param->set_status("parcial_refounded");
            $param->set_monto("".$param->get_monto()-$monto);
            if($param->set()){
                $rs = $this->devolucion_parcial($param,$monto);       
                if($rs->dataResponse["status_details"]["error"]==null){
                    if(Model::CompleteTrans())
                        return array(true,"");
                }
            }
        }catch(Exception $e){
            Model::FailTrans();
            return array(false,$e->getMessage());
        }
    }

}
