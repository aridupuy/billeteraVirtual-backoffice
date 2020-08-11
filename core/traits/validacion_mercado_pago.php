<?php

require_once PATH_PUBLIC . 'sdk-php/lib/mercadopago.php';

abstract class Validacion_mercado_pago {

    const ACTIVAR_DEBUG = true;
    const CANTIDAD_PAGINADO = 500;
    const MERCADO_PAGO_APROBADO = "approved";
    const MERCADO_PAGO_AUTORIZADO = "authorized";
    const MERCADO_PAGO_CANCELADO = "cancelled";
    const MERCADO_PAGO_CONTRACARGO = "charged_back";
    const MERCADO_PAGO_RECHAZADO = "rejected";
    const MERCADO_PAGO_REINTEGRADO = "refunded";
    const DUMMY_UNID = '25';
    const DUMMY_ID_FORMAPAGO = '3';
    const PERIODO_DE_CONSULTA_MERCADOPAGO = '1HOUR';
    const MERCADOPAGO_CORREO_1 = 'mp';
    const MERCADOPAGO_CORREO_2 = 'collect';
    const MERCADOPAGO_CORREO_3 = 'mp3e';
    const MERCADOPAGO_CORREO_4 = 'clicpagos';
    const MERCADOPAGO_CORREO_5 = 'pvp';
    const MERCADOPAGO_CORREO_6 = 'tcdaut';
    const MERCADOPAGO_CORREO_7 = 'pagodirecto';
    const DOMINIO_CORREO = '@cobrodigital.com';
    const MERCADOPAGO_CLAVE_PUBLICA_TCDAUT = "APP_USR-da785a21-2ac1-46b8-aeb6-f76927542255";
    const MERCADOPAGO_CLAVE_PRIVADA_TCDAUT = "APP_USR-6252326434264808-080215-46d4ecaa02044a933fcba5ca9994530e__LA_LC__-213555671";
    const MERCADOPAGO_CLAVE_PRIVADA_PAGODIRECTO = MERCADOPAGO_CLAVE_PRIVADA_PAGODIRECTO;
    const MERCADOPAGO_CLAVE_PUBLICA_PAGODIRECTO = MERCADOPAGO_CLAVE_PUBLICA_PAGODIRECTO;

    public function __construct() {
        $mp = new Mp();
        $mp->get(Mp::TARJETA);
    }

    public function ejecutar() {
        $barcodes = $this->obtener_barcodes();
//	echo "\n<pre>\n";
//        echo print_r($barcodes, true)."\n</pre>\n";
        #EJEMPLO DE UN BARCODE#
//        $barcodes=array("charged_backd"=>array(0=>array("barcode"=>"73852040595737515021800033447",
//                        "monto"=> 4257 ,"fecha_aprobado"=> "2018-01-15T10:40:33.000-04:00",
//                        "fecha_peticion"=> "2018-01-15T10:40:32.000-04:00","id_mercado_pago_transaction"=> 3349096556 ,
//                        "cuenta"=> "mp", "status_detail"=> "charged_backd")));
//        developer_log(json_encode($barcodes));
        if (($datos = $this->procesar($barcodes)) != false)
            return $datos;
        return false;
    }

    private function procesar($barcodes) {
        $resultado = array();
        foreach ($barcodes as $clave => $estado) {
            switch ($clave) {
                case self::MERCADO_PAGO_AUTORIZADO:
                case self::MERCADO_PAGO_APROBADO:
                    $objeto = new Validador_aceptado($barcodes);
                    break;
                case self::MERCADO_PAGO_CANCELADO:
                    $objeto = new Validador_cancelado($barcodes);
                    break;
                case self::MERCADO_PAGO_RECHAZADO:
                    $objeto = new Validador_rechazado($barcodes);
                    break;
                case self::MERCADO_PAGO_REINTEGRADO:
                    $objeto = new Validador_reintegrado($barcodes);
                    break;
                case self::MERCADO_PAGO_CONTRACARGO:
                    $objeto = new Validador_contracargo($barcodes);
                    break;
            }
            if ($objeto)
                $resultado[$clave] = $objeto->ejecutar();
//            if(!$resultado)
            //              return false;
        }
        return $resultado;
    }

    private function obtener_barcodes() {

        $listado_de_cuentas = $this->obtener_cuenta();
//var_dump($listado_de_cuentas);
	$nombre=$listado_de_cuentas[0];
        $array = array();
//        foreach ($listado_de_cuentas[1] as $cuenta) {
            if (isset($listado_de_cuentas[1]["client_id"]))
                $mp = new MP_lib($listado_de_cuentas[1]["client_id"], $listado_de_cuentas[1]["client_secret"]);
            elseif (isset($listado_de_cuentas[1]["access_token"]))
                $mp = new MP_lib($listado_de_cuentas[1]["access_token"]);
            $filters = array(
                "range" => "date_created",
                "begin_date" => "NOW-20DAYS",
                "end_date" => "NOW",
                "sort" => "date_created",
            );
//            print_r("<pre>");
//            print_r($mp);
//            print_r("</pre>");
//            exit();
            $cant_result = $mp->search_payment($filters);
            developer_log(json_encode($cant_result));
            $cant_result = $cant_result["response"]["paging"]["total"];
            $pos_anterior = 0;
            $pagina = ($cant_result / self::CANTIDAD_PAGINADO);
            developer_log(json_encode($cant_result));
            if (self::ACTIVAR_DEBUG)
                developer_log("Paginas $pagina");
            for ($i = 0; $i < ($cant_result / self::CANTIDAD_PAGINADO); $i++) {
//                var_dump($cant_result,$pos_anterior,$pagina,$i);
//                var_dump(($cant_result/$pagina)+$pos_anterior);
///                if (self::ACTIVAR_DEBUG)
                //                 developer_log("Procesando Pagina $i desde $pos_anterior hasta ".($cant_result/$pagina)+$pos_anterior." de la cuenta $nombre");
                $search_result = $mp->search_payment($filters, $pos_anterior, ($cant_result / $pagina));
                $pos_anterior += ($cant_result / $pagina);
                developer_log(json_encode($search_result));
                foreach ($search_result["response"]["results"] as $pago) {
//		   if($pago["collection"]["external_reference"]=="73858297044153526011800010006")//
//			developer_log("barcode encontrado "." ".json_encode($pago));
                    if ($pago["external_reference"] != null)
//		   if($pago["collection"]["external_reference"]!=null)
                        $array[$pago["status"]][] = array("barcode" => $pago["external_reference"], "monto" => $pago["transaction_amount"], "fecha_aprobado" => $pago["date_created"], "fecha_peticion" => $pago["date_created"], "id_mercado_pago_transaction" => $pago["id"], "cuenta" => $nombre, "status_detail" => Validador_mp::obtener_mensaje_para_usuario($pago["status_detail"]));
//                       $array[$pago["collection"]["status"]][] = array("barcode" => $pago["collection"]["external_reference"], "monto" => $pago["collection"]["transaction_amount"], "fecha_aprobado" => $pago["collection"]["date_created"], "fecha_peticion" => $pago["collection"]["date_created"], "id_mercado_pago_transaction" => $pago["collection"]["id"],"cuenta"=>$nombre,"status_detail"=> Validador_mp::obtener_mensaje_para_usuario ($pago["collection"]["status_detail"]));
                    else
                        developer_log(json_encode($pago));
                    developer_log($pago["external_reference"] . " " . $pago["status"]);
                }
            }
            unset($pagina);
            unset($pos_anterior);
            unset($mp);
//        }
        return $array;
    }

    abstract public function obtener_cuenta();

//    public function listado_de_cuentas() {
//        $preprocesar_cuenta_mp = true;
//        $preprocesar_cuenta_collect = true;
//        $preprocesar_cuenta_mp3e = true;
//        $preprocesar_cuenta_clicpagos = true;
//        $preprocesar_cuenta_pvp = true;
//        $preprocesar_cuenta_tcdaut = true;
//        $preprocesar_cuenta_pagodirecto=true;
//        $cuentas = array();
//        if ($preprocesar_cuenta_mp) {
//            $cuentas[self::MERCADOPAGO_CORREO_1] = array('client_id' => '1872102149935260',
//                'client_secret' => 'U8SInUyeb58ixtUQanTvTCrMnYWRuEff',
//                'correo' => self::MERCADOPAGO_CORREO_1 . self::DOMINIO_CORREO,
//                'alias' => 'MP1',
//                'id_peucd' => Peucd::MERCADOPAGO_MP1
//            );
//        }
//        if ($preprocesar_cuenta_collect) {
//            $cuentas[self::MERCADOPAGO_CORREO_2] = array('client_id' => '4812010488902074',
//                'client_secret' => 'RSXDuaXJJSI7FSl1OzhfPeu9hKdqIywR',
//                'correo' => self::MERCADOPAGO_CORREO_2 . self::DOMINIO_CORREO,
//                'alias' => 'MP2',
//                'id_peucd' => Peucd::MERCADOPAGO_MP2
//            );
//        }
//        if ($preprocesar_cuenta_mp3e) {
//            $cuentas[self::MERCADOPAGO_CORREO_3] = array('client_id' => '420311948104044',
//                'client_secret' => 'y3MnDnV0aBDXPUR5vmB3kTboyr9sFVbL',
//                'correo' => self::MERCADOPAGO_CORREO_3 . self::DOMINIO_CORREO,
//                'alias' => 'MP3',
//                'id_peucd' => Peucd::MERCADOPAGO_MP3
//            );
//        }
//        if ($preprocesar_cuenta_clicpagos) {
//            $cuentas[self::MERCADOPAGO_CORREO_4] = array('client_id' => '8255255304938580',
//                'client_secret' => '1f0M14QqNFOXkfqWJPyNOtdl4YdO8zeX',
//                'correo' => self::MERCADOPAGO_CORREO_4 . self::DOMINIO_CORREO,
//                'alias' => 'MP4',
//                'id_peucd' => Peucd::MERCADOPAGO_MP4
//            );
//        }
//        if ($preprocesar_cuenta_pvp) {
//            $cuentas[self::MERCADOPAGO_CORREO_5] = array(
//                'access_token' => self::MERCADOPAGO_CLAVE_PRIVADA_TCDAUT,
//                'correo' => self::MERCADOPAGO_CORREO_5 . self::DOMINIO_CORREO,
//                'alias' => 'MP5',
//                'id_peucd' => Peucd::MERCADOPAGO_MP5
//            );
//        }
//        if ($preprocesar_cuenta_tcdaut) {
//            $cuentas[self::MERCADOPAGO_CORREO_6] = array(
//                'access_token' => self::MERCADOPAGO_CLAVE_PRIVADA_TCDAUT,
//                'correo' => self::MERCADOPAGO_CORREO_6 . self::DOMINIO_CORREO,
//                'alias' => 'MP6',
//                'id_peucd' => Peucd::MERCADOPAGO_MP6
//            );
//        }
//        if ($preprocesar_cuenta_pagodirecto) {
//            $cuentas[self::MERCADOPAGO_CORREO_7] = array(
//                'access_token' => self::MERCADOPAGO_CLAVE_PRIVADA_PAGODIRECTO,
//                'correo' => self::MERCADOPAGO_CORREO_7 . self::DOMINIO_CORREO,
//                'alias' => 'MP6',
//                'id_peucd' => Peucd::MERCADOPAGO_MP7
//            );
//        }
//        return $cuentas;
//    }
}
