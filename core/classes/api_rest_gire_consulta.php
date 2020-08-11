<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of api_rest_gire_consulta
 *
 * @author ariel
 */
class Api_rest_gire_consulta extends Api_rest_gire {

    public static $claves = array("id_clave", "cod_trx", "canal", "fecha_hora_operacion");

    //put your code here
    public function __construct() {
        parent::__construct();
    }

    public function run() {
        parent::run();
        $deuda = array();
        if (($barcode = $this->buscar_por_barcode()) != false) {
            list($result, $climarchand) = $this->encontrar_deuda($barcode);
            $deuda = $this->responder($result, $climarchand);
        } elseif (($barcode = $this->buscar_por_pmc19()) != false) {
            list($result, $climarchand) = $this->encontrar_deuda($barcode);
            $deuda = $this->responder_varios($result, array($climarchand));
        } elseif (($climarchands = $this->buscar_por_identificador()) != false) {
            list($results, $climarchands) = $this->encontrar_deuda_por_climarchand($climarchands);
            $deuda = $this->responder_varios($results, $climarchands);
        } elseif (($climarchand = $this->buscar_por_documento()) != false) {
            list($results, $climarchands) = $this->encontrar_deuda_por_climarchand($climarchand);
            $deuda = $this->responder_varios($results, $climarchands);
        } else {
            throw new ExceptionApiGire("Cliente no encontrado", 7);
        }


        return $deuda;
    }

    private function buscar_por_barcode() {
        $rs_barcode = Barcode::select_min($this->variables["id_clave"]);
        if ($rs_barcode->rowCount() == 0) {
            return false;
        }
        $barcodes = array();
        foreach ($rs_barcode as $row) {
//        $row = $rs_barcode->fetchRow();
            $barcodes[] = new Barcode($row);
        }
        return $barcodes;
    }

    private function buscar_por_pmc19() {
        $rs_barcode = Barcode::select(array("pmc19" => $this->variables["id_clave"]));
        if ($rs_barcode->rowCount() == 0) {
            return false;
        }
        $barcodes = array();
        $acum = array();
        foreach ($rs_barcode as $row) {
            if (!in_array($row["id_boletamarchand"], $acum)) {
                $barcodes[] = new Barcode($row);
                $acum[] = $row["id_boletamarchand"];
            }
        }
//        var_dump($barcodes);
        return $barcodes;
    }

    private function buscar_por_documento() {
        $rs_climarchand = Climarchand::select_cliente_documento_sin_marchand($this->variables["id_clave"]);
        if ($rs_climarchand->rowCount() == 0) {
            return false;
        }
        foreach ($rs_climarchand as $row) {
            $climarchands [] = new Climarchand($row);
        }
        return $climarchands;
    }

    private function buscar_por_identificador() {
        $rs_climarchand = Climarchand::select_cliente_identificador_sin_marchand($this->variables["id_clave"]);
        if ($rs_climarchand->rowCount() == 0) {
            return false;
        }
        $climarchands = array();
        foreach ($rs_climarchand as $row) {
            $climarchands [] = new Climarchand($row);
        }
        return $climarchands;
    }

    private function encontrar_deuda($barcodes) {
//        var_dump($barcodes);
        $this->validar_marchand($barcodes[0]->get_id_marchand());
        $results = array();
        foreach ($barcodes as $barcode) {
            $trix = new Trix();
            $trix->get($barcode->get_id_trix());
            $rsclimarchand = Climarchand::select_min($trix->get_id_marchand(),$trix->get_accountid());
            $climarchand = new Climarchand($rsclimarchand->fetchRow());
            $bolemarchand = new Bolemarchand();
            $bolemarchand->get($barcode->get_id_boletamarchand());

            $result = Bolemarchand::select_proximos_vencimientos(false, $bolemarchand->get_nroboleta(), $barcode->get_id_marchand(),3,0,$barcode->get_barcode());
            if($result->rowCount()==0){
//                $result= Barcode::select(array("barcode"=>$barcode->get_barcode()));
                 $result = $barcode;
            }
            $results[]=$result;
            
        }
        return array($results, $climarchand);
    }

    private function encontrar_deuda_por_climarchand($climarchands) {
        $result = array();
        foreach ($climarchands as $climarchand) {
            $this->validar_marchand($climarchand->get_id_marchand());
            $res = Bolemarchand::select_proximos_vencimientos($climarchand->get_id(), false, $climarchand->get_id_marchand());
            if (!$res)
                throw new ExceptionApiGire("Error en la consulta", 1);
            $result[] = $res;
        }

        return array($result, $climarchands);
    }

    private function responder($results, Climarchand $climarchand) {
        if (count($results) == 0) {
            throw new ExceptionApiGire("No existe registro", 6);
        }
        $deuda = array();
        $deuda["id_clave"] = $this->variables["id_clave"];
        list($apellido, $nombre) = $this->obtener_datos_climarchand($climarchand);
        $deuda["nombre"] = $nombre;
        $deuda["apellido"] = $apellido;
        $deuda["cod_trx"] = $this->clave_proceso;
        $deuda["codigo_respuesta"] = "0";
        $deuda["msg"] = "Trx ok";
        $deuda["dato_adicional"] = "";
        foreach ($results as $result) {
            if ($result->rowCount() == 0) {
                throw new ExceptionApiGire("No existe registro", 6);
            }
            $barcodes_vencimientos = array();
            $barcodes_pagados = array();
//            foreach ($result as $factura) {
//                var_dump($factura["id_authstat"]);
//                if($factura["id_authstat"]== Authstat::BARCODE_PAGADO){
//                    $barcodes_pagados[] = $factura["nroboleta"];
//                }
//            }
//            var_dump($barcodes_pagados);
            foreach ($result as $factura) {
                if(!isset($factura["nroboleta"]))
                    $factura["nroboleta"]=$factura["id_barcode"];
                if($factura["id_authstat"]== Authstat::BARCODE_PAGADO){
                    $barcodes_pagados[] = $factura["nroboleta"];
                }
                
                if (!isset($barcodes_vencimientos[$factura["nroboleta"]]) OR $barcodes_vencimientos[$factura["nroboleta"]] >= $factura["id_tipopago"]) {
                    $barcodes_vencimientos[$factura["nroboleta"]] = $factura["id_tipopago"];
//                    var_dump($barcodes_vencimientos[$factura["nroboleta"]]);
                }
                
                
            }
//             var_dump($barcodes_vencimientos);
//            var_dump(in_array($factura["nroboleta"], array_keys($barcodes_vencimientos)));
//            $result->move(0);
            foreach ($result as $factura) {
                if(!isset($factura["nroboleta"]))
                    $factura["nroboleta"]=$factura["id_barcode"];
                if (/*!in_array($factura["nroboleta"], $barcodes_pagados) and */in_array($factura["nroboleta"], array_keys($barcodes_vencimientos)) and $factura["id_tipopago"] == $barcodes_vencimientos[$factura["nroboleta"]] ) {
                    $marchand = new Marchand();
                    $marchand->get($factura["id_marchand"]);
                    if(isset($factura["nroboleta"]))
                        $array["id_numero"] = $factura["nroboleta"];
                    else
                        $array["id_numero"] = $factura["id_barcode"];
                    $fecha_vto = DateTime::createFromFormat("Y-m-d H:i:s", $factura["fecha_vto"]);
                    
                    $fecha_emi = DateTime::createFromFormat("Y-m-d", $factura["fechagen"]);
                    if(!$fecha_emi){
                        $fecha_emi = DateTime::createFromFormat("Y-m-d H:i:s.u", $factura["fechagen"]);
                    }
                    $array["fecha_vencimiento"] = $fecha_vto->format("Y-m-d");
                    $array["fecha_emision"] = $fecha_emi->format("Y-m-d");
                    $array["importe"] = $factura["monto"];
                    $array["barra"] = $factura["barcode"];
//                    var_dump($factura);
                    if ($factura["monto"] == 0)
                        $array["texto1"] = $marchand->get_minirs() . "-" . "Pago abierto";
                    else
                        if(isset ($factura["boleta_concepto"]))
                            $array["texto1"] = $marchand->get_minirs() . "-" . $factura["boleta_concepto"];
                        else{
                            $array["texto1"] = $marchand->get_minirs();
                        }
                    $deuda["facturas"][] = $array;
                }
            }
        }

        return $deuda;
    }

    private function responder_varios($results, $climarchands) {

        if (count($results) == 0) {
            throw new ExceptionApiGire("No existe registro", 6);
        }
        $deuda = array();
        $deuda["id_clave"] = $this->variables["id_clave"];
        $deuda["cod_trx"] = $this->clave_proceso;
        $deuda["codigo_respuesta"] = "0";
        $deuda["msg"] = "Trx ok";
        $deuda["dato_adicional"] = "";
        list($apellido, $nombre) = $this->obtener_datos_climarchand($climarchands[0]);
        $deuda["nombre"] = $nombre;
        $deuda["apellido"] = $apellido;
        foreach ($results as $result) {
//            if ($result->rowCount() == 0) {
//                throw new ExceptionApiGire("No existe registro", 6);
//            }
            $barcodes_pagados = array();
            foreach ($result as $factura) {
//                var_dump($barcodes_vencimientos[$factura["nroboleta"]]."<".$factura["id_tipopago"]);
                if (!in_array($factura["nroboleta"],$barcodes_pagados) and $factura["id_authstat"]== Authstat::BARCODE_PAGADO ) {
                    $barcodes_pagados[] = $factura["nroboleta"];
//                    var_dump($barcodes_vencimientos[$factura["nroboleta"]]);
                }
            }
            $barcodes_vencimientos = array();
	    $result->move(0);
            foreach ($result as $factura) {
//                var_dump($barcodes_vencimientos[$factura["nroboleta"]]."<".$factura["id_tipopago"]);
                if (!isset($barcodes_vencimientos[$factura["nroboleta"]]) OR $barcodes_vencimientos[$factura["nroboleta"]] >= $factura["id_tipopago"]) {
                    $barcodes_vencimientos[$factura["nroboleta"]] = $factura["id_tipopago"];
//                    var_dump($barcodes_vencimientos[$factura["nroboleta"]]);
                }
            }
//            var_dump($barcodes_vencimientos);
	    $result->move(0);
            foreach ($result as $factura) {
                if (!in_array($factura["nroboleta"], $barcodes_pagados) and in_array($factura["nroboleta"], array_keys($barcodes_vencimientos)) and $factura["id_tipopago"] == $barcodes_vencimientos[$factura["nroboleta"]]) {
                    $marchand = new Marchand();
                    $marchand->get($factura["id_marchand"]);
                    $array["id_numero"] = $factura["nroboleta"];
                    $fecha_vto = DateTime::createFromFormat("Y-m-d H:i:s", $factura["fecha_vto"]);
                    $fecha_emi = DateTime::createFromFormat("Y-m-d", $factura["fechagen"]);
                    $array["fecha_vencimiento"] = $fecha_vto->format("Y-m-d");
                    $array["fecha_emision"] = $fecha_emi->format("Y-m-d");
                    $array["importe"] = $factura["monto"];
                    $array["barra"] = $factura["barcode"];
                    if ($factura["monto"] == 0)
                        $array["texto1"] = $marchand->get_minirs() . "-" . "Pago abierto";
                    else
                        $array["texto1"] = $marchand->get_minirs() . "-" . $factura["boleta_concepto"];
                    $deuda["facturas"][] = $array;
                }
            }
        }
        if (count($deuda["facturas"]) == 0) {
            $deuda["facturas"] = array();
        }
        return $deuda;
    }

    public static function obtener_resultado_fallo(ExceptionApiGire $e = null) {
        if ($e !== null) {
            $result["codigo_respuesta"] = $e->getCode();
            $result["msg"] = $e->getMessage();
        } else {
            $result["codigo_respuesta"] = "10";
            $result["msg"] = "Error interno del sistema";
        }
        $vars = self::levantar_variables_fallo();
        if ($vars) {
            $result["cod_trx"] = $vars["cod_trx"];
            $result["id_clave"] = $vars["id_clave"];
        } else {
            $result["cod_trx"] = null;
            $result["id_clave"] = null;
        }
        $result["nombre"] = null;
        $result["apellido"] = null;
        $result["facturas"] = array();
        return $result;
    }

}
