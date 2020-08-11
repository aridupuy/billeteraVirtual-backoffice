<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of pei
 *
 * @author ariel
 */
class Pei extends Pago_electronico {

    private $usuario = "sv_33711566959_pei";
    private $pass = "n7*8p7fTe1y3kLlxbv.Pyi";
    private $client_id = "85d71240-0fd5-41bc-a49e-fd44812ff7c3";
                          
    private $autenticate_token = "";

    const ENDPOINT_AUTENTICACION = "https://api.redlink.com.ar/redlink/produccion/enlacepagos-np-seg/0/0/38/sesion/";
    const ENDPOINT_PAGO = "https://api.redlink.com.ar/redlink/produccion/enlacepagos-np-seg/0/0/38/pagos/sinbilletera/td/medionopresente";
    const ID_COMERCIO = "1481";
    
    const enum_concepto = array(
        "COMPRA_DE_BIENES",
        "PAGO_DE_SERVICIOS",
        "EXTRACCION",
        "PAGO_DE_COMBUSTIBLE",
        "COMPRA_DE_DIVISA"
    );
    const MARCA_TARJETA = array(
        "Visa Debito" => "VISA_ELECTRON",
        "Visa" => "VISA_ELECTRON",
        "Maestro" => "MAESTRO",
        "Mastercard Debit" => "MASTER_DEBIT",
        "Cabal 24" => "CABALDEB",
        "Cabal Prisma" => "CABALDEB"
    );

    public function __construct() {
        $this->autenticar();
    }

    public function get_token() {
        return $this->autenticate_token;
    }

    public function autenticar() {
        $usuario = $this->usuario;
        $pass = $this->pass;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::ENDPOINT_AUTENTICACION . self::ID_COMERCIO,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => " {\n\n   \"credenciales\": {\n\n               "
            . "                     \"usuario\": \"$usuario\",\n\n"
            . "                     \"contrasena\": \"$pass\"\n\n   }\n\n}",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/json",
                "x-ibm-client-id: " . $this->client_id,
                "cliente:190.184.254.68"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "cURL Error #:" . $err;
        } 
        $ress= json_decode($response,true);
        error_log($response);
       $this->autenticate_token= $ress["token"];
    }

    public function generar_pago(...$param) {
        list($titular, $documento, $marchand, $tarjeta, $fecha_vto_tc, $cvv, $refe, $id_operacion, $barcode, $monto, $concepto) = $param;
        $json["pago"] = array(
            "titularNombre" => $titular,
            "titularDocumento" => $documento,
            "tarjeta" => Encriptador_pei::encriptar($tarjeta),
            "tarjetaMarca" => strtoupper(self::obtener_issued($tarjeta)),
            "vencimiento" => Encriptador_pei::encriptar($fecha_vto_tc->format("my")),
            "codigoSeguridad" => Encriptador_pei::encriptar($cvv),
            //  "idVendedor"=> self::ID_COMERCIO,
//        "idVendedor"=> (int) self::ID_COMERCIO,
//        "idVendedor"=> "000000",
            "idCanal" => "PEIECOM",
            "idReferenciaTrxComercio" => $refe,
            "idReferenciaOperacionComercio" => $id_operacion,
            "importe" => $monto * 100,
            "moneda" => "ARS",
            "concepto" => $this->obtener_concepto()
        );
        $request = json_encode($json);
        developer_log(strtoupper(self::obtener_issued($tarjeta)));
        developer_log(json_encode($json));
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => self::ENDPOINT_PAGO,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $request,
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "cliente: 190.184.254.70",
                "content-type: application/json",
                "requerimiento: 12",
                "token:" . $this->autenticate_token,
                "x-ibm-client-id:" . $this->client_id
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        /* response tentativo */
        /* {

          "tipoOperacion": "PAGO",

          "fecha": "2020-05-22T14:19:53Z",

          "idOperacion": "533208",

          "idOperacionOrigen": null,

          "numeroReferenciaBancaria": "000000220805"

          } */
        $respuesta = json_decode($response, true);
        $authstat = Authstat::ACTIVO;
        if (isset($respuesta["tipoOperacion"])) {
            $status = "approved";
        } else
            $status = "rejected";
        if ($err != "") {
            $status = "rejected";
        }
        developer_log("antes de guardar");
        $res = $this->guardar_transaccion($request, $response, Entidad::ENTIDAD_BARCODE, $barcode->get_id_barcode(), $authstat, $marchand->get_id_marchand(), $monto, $concepto, $status);
        developer_log("despues de guardar");
        developer_log($res);
        if ($status == "approved") {
            return array(true, "Pago procesado correctamente");
        }
        if (isset($respuesta["codigo"]) and isset($respuesta["descripcion"]))
            return array(false, $respuesta["descripcion"]);
    }

    public static function obtener_issued($tarjeta) {
        $res = parent::obtener_issued($tarjeta);
        return self::MARCA_TARJETA[$res];
    }

    public function obtener_concepto($param = false) {

        switch ($param) {
            case "COMPRA_DE_BIENES":
                return "COMPRA_DE_BIENES";

            case "PAGO_DE_SERVICIOS":
                return "PAGO_DE_SERVICIOS";

            case "EXTRACCION":
                return "EXTRACCION";

            case "PAGO_DE_COMBUSTIBLE":
                return "PAGO_DE_COMBUSTIBLE";

            case "COMPRA_DE_DIVISA":
                return "COMPRA_DE_DIVISA";

            default :
                return "PAGO_DE_SERVICIOS";
        }
    }

    public function devolver($param) {
        $pei = new Pei_transaccion();
        $pei = $param;
        $array_response_pago = json_decode($pei->get_response(), true);
//        var_dump($array_response_pago);
        $array_request_pago = json_decode($pei->get_request(), true);
//        var_dump($array_datos_pago);
//        var_dump($array_datos_pago["operaciones"][0]["pan"]);
        $documento = $array_request_pago["pago"]["titularDocumento"];
        $tarjeta = $array_request_pago["pago"]["tarjeta"];
        $idReferenciaTrxComercio = $array_request_pago["pago"]["idReferenciaTrxComercio"];
        $idReferenciaOperacionComercio = $array_request_pago["pago"]["idReferenciaOperacionComercio"]+150;
//        var_dump($idReferenciaOperacionComercio);
        $idCanal = $array_request_pago["pago"]["idCanal"];
        list($rs,$err) = $this->devolver_ws($array_response_pago["idOperacion"],$documento,$tarjeta,$idReferenciaTrxComercio,$idReferenciaOperacionComercio,$idCanal);
        $respuesta = json_decode($rs,true);
        if(isset($respuesta["tipoOperacion"])){
            $pei->set_status(Preprocesador_pei::ESTADO_DEVUELTO);
            $pei->set();
            
            return array(true,null);
        }
        //var_dump($err);
        //var_dump(json_encode($respuesta));
        return array(false,$respuesta["descripcion"]);
    }

    private function devolver_ws(...$array) {
        list($id_pago,$documento,$tarjeta,$idReferenciaTrxComercio,$idReferenciaOperacionComercio,$idCanal)=$array;
        $idReferenciaTrxComercio+="_rev";
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.redlink.com.ar/redlink/produccion/enlacepagos-np-seg/0/0/34/pagos/sinbilletera/td/medionopresente/devolucion/total",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\n  \"devolucion\": {\n    \"idPago\": \"$id_pago\",\n    \"titularDocumento\": \"$documento\",\n    \"tarjeta\":\"$tarjeta\",\n    \"idReferenciaTrxComercio\": \"$idReferenciaTrxComercio\",\n    \"idReferenciaOperacionComercio\": \"$idReferenciaOperacionComercio\",\n    \"idCanal\": \"$id_canal\"\n  }\n}",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "cliente: 190.184.254.70",
                "content-type: application/json",
                "requerimiento: 12",
                "token:" . $this->autenticate_token,
                "x-ibm-client-id:" . $this->client_id
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        return array($response,$err);

        /*
          {
          {
          "devolucion": {
          "idPago": "539440",
          "titularDocumento": "34000944",
          "tarjeta":"1O+bfpZTW3hcS4ZwLtkx3pZVmQO/rxjoufbYHa7MJGEPRz/gNLXJfHu2M3o5F0KE",
          "idReferenciaTrxComercio": "73859000000233731052900000005200527_19",
          "idReferenciaOperacionComercio": "632468500019",
          "idCanal": "PEIECOM"
          }
          }
          }
          } */
    }
    private function devolver_ws_parcial(...$array) {
        list($id_pago,$documento,$tarjeta,$idReferenciaTrxComercio,$idReferenciaOperacionComercio,$idCanal,$importe)=$array;
        $idReferenciaTrxComercio="_rev".date("His");
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.redlink.com.ar/redlink/produccion/enlacepagos-np-seg/0/0/34/pagos/sinbilletera/td/medionopresente/devolucion/parcial",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{\n  \"devolucion\": {\n    \"idPago\": \"$id_pago\",\n    \"titularDocumento\": \"$documento\",\n    \"tarjeta\":\"$tarjeta\",\n    \"idReferenciaTrxComercio\": \"$idReferenciaTrxComercio\",\n    \"idReferenciaOperacionComercio\": \"$idReferenciaOperacionComercio\",\n    \"idCanal\": \"$id_canal\"\n , \"importe\":$importe,\"moneda\":\"ARS\" }\n}",
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "cliente: 190.184.254.70",
                "content-type: application/json",
                "requerimiento: 12",
                "token:" . $this->autenticate_token,
                "x-ibm-client-id:" . $this->client_id
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        return array($response,$err);

        /*
          {
          {
          "devolucion": {
          "idPago": "539440",
          "titularDocumento": "34000944",
          "tarjeta":"1O+bfpZTW3hcS4ZwLtkx3pZVmQO/rxjoufbYHa7MJGEPRz/gNLXJfHu2M3o5F0KE",
          "idReferenciaTrxComercio": "73859000000233731052900000005200527_19",
          "idReferenciaOperacionComercio": "632468500019",
          "idCanal": "PEIECOM"
          }
          }
          }
          } */
    }

    public function devolver_parcial($param,$monto) {
        Model::StartTrans();
        $pei = new Pei_transaccion();
        $pei = $param;
        $array_response_pago = json_decode($pei->get_response(), true);
        $array_request_pago = json_decode($pei->get_request(), true);
//      var_dump($array_datos_pago);
//      var_dump($array_datos_pago["operaciones"][0]["pan"]);
        $documento = $array_request_pago["pago"]["titularDocumento"];
        $tarjeta = $array_request_pago["pago"]["tarjeta"];
        $idReferenciaTrxComercio = $array_request_pago["pago"]["idReferenciaTrxComercio"];
        $idReferenciaOperacionComercio = $array_request_pago["pago"]["idReferenciaOperacionComercio"];
        $idCanal = $array_request_pago["pago"]["idCanal"];
        /*el webservice no verifica del lado de pei si la devolucion supera el monto original*/
        if($pei->get_monto()<$monto){
            developer_log("El monto supera al monto original de la transaccion");
            return false;
        }
        list($rs,$err) = $this->devolver_ws_parcial($array_response_pago["idOperacion"],$documento,$tarjeta,$idReferenciaTrxComercio,$idReferenciaOperacionComercio,$idCanal,$monto);
        $respuesta = json_decode($rs,true);
        //$respuesta["tipoOperacion"]=true;
        if(isset($respuesta["tipoOperacion"])){
            $barcode = new Barcode();
            $barcode->get($pei->get_id_referencia());
            $transaccion = new Transaccion_pei_reverso_parcial();
            if($transaccion->crear($barcode->get_id_marchand(), Mp::PEI_DEVOLUCION, $monto, (new DateTime("now")), $pei->get_id_referencia(), null, $barcode)){
                $pei->set_status(Preprocesador_pei::ESTADO_DEVUELTO_PARCIAL);
                $pei->set_monto($pei->get_monto()-$monto);
                $pei->set();
                Model::CompleteTrans();
            return true;
            }
        }
        Model::FailTrans();
        return false;
    }

    public function consultar_pagos(...$param) {
        throw new Exception("use clase pei_conciliacion para consultas.");
    }

    public function obtener_pagos() {
        $rs = Pei_transaccion::select_pagos_validacion();
        $array = array();
        foreach ($rs as $row) {
            $array[] = new Pei_transaccion($row);
        }
        return $array;
    }

    public function obtener_siguiente_num_transaccion() {
        
    }

    public function guardar_transaccion(...$param) {
        list($request, $response, $entidad, $referencia, $authstat, $id_marchand, $monto, $concepto, $status) = $param;
        $transa = new Pei_transaccion();
        self::$mp = new Mp();
        self::$mp->get(MP::PEI); #ver
        $fecha = new DateTime("now");
        $transa->set_fecha($fecha->format("Y-m-d"));
        $transa->set_fecha_gen("now()");
        $transa->set_id_authstat($authstat);
        $transa->set_id_entidad($entidad);
        $transa->set_id_referencia($referencia);
        $transa->set_concepto($concepto);
        $transa->set_status($status);
        $transa->set_id_marchand($id_marchand);
        $transa->set_monto($monto);
        $transa->set_response($response);
        $transa->set_request($request);
        if ($transa->set()) {
            developer_log("guardado");
            return "Pago realizado correctamente.";
        }
        return "Error al registrar el pago en cobrodigital";
    }

}
