
<?php

require_once "public/nusoap/nusoap.php";

class Modelcobrxpr {

    private $wsGuardar = "http://cobranzaexterna.cobroexpress.com.ar/wsCobranzaGlobal.asmx?WSDL";
    private $wsValidar = "http://servicios7.cobroexpress.com.ar/pwsCobranzaGlobal.dll/wsdl/IpwsCobranzaGlobal/?wsdl";
    private $usuario = "CoDiCe";
    private $clave = "CeCd41";

//    private $usuario   = "Co36bro";//test
//    private $clave     = "test1274";//test

    public function buscarServiRubro() {
        $arrBuscarRubro["Param"] = array("Entidad" => array("Usuario" => $this->usuario, "Clave" => $this->clave));
        $objeto = new nusoap_client($this->wsValidar, true);
        $arrRubros = $objeto->call("BuscarRubros", $arrBuscarRubro);
        return $arrRubros;
    }

    public function buscarEmpresa() {
        $arrBuscarEmpresa["Param"] = array("Entidad" => array("Usuario" => $this->usuario, "Clave" => $this->clave));
        $objeto = new nusoap_client($this->wsValidar, true);
        $arrEmpresa = $objeto->call("BuscarEmpresas", $arrBuscarEmpresa);
        return $arrEmpresa;
    }

    public function validarDatos($codEmp = null, $codBarra = null, $cliente = null, $arrForm = null, $marchand = null, $id_usumarchand=null) {
        $fecha_hoy_form = new DateTime('now');
        $fecha_hoy = $fecha_hoy_form->format('Y-m-d');
        $cliente=$id_usumarchand;
        if (isset($codBarra)) {
            $arrValidarDatosConFac["Param"] = array("Entidad" => array("Usuario" => $this->usuario, "Clave" => $this->clave), "CodEmp" => $codEmp, "CodBarra" => $codBarra, "Client" => $cliente);
            $objeto = new nusoap_client($this->wsValidar, true);
            $arrDatosConFact = $objeto->call("ValidarDatos", $arrValidarDatosConFac);
            $log = new Cobro_expres_log();
            $log->set_log_ce(json_encode($arrValidarDatosConFac["Param"]));
            $log->set_fecha_log($fecha_hoy);
            $log->set_id_marchand($marchand);
            $log->set_tipo_pedido("llamada");
            $log->set_id_moves("validar_datos");
            $log->set_id_usumarchand($id_usumarchand);
            $log->set();

            $log2 = new Cobro_expres_log();
            $log2->set_log_ce(json_encode($arrDatosConFact));
            $log2->set_fecha_log($fecha_hoy);
            $log2->set_id_marchand($marchand);
            $log2->set_tipo_pedido("respuesta");
            $log2->set_id_moves("validar_datos");
            $log2->set_id_usumarchand($id_usumarchand);
            $log2->set();

            return $arrDatosConFact;
        } else {
            $count = count($arrForm);
            //print_r($arrForm);
            if ($count == 2) {
                //print_r("ARRAY 2");
                $arrValidarDatosSinFac["Param"] = array(
                    "Entidad" => array("Usuario" => $this->usuario, "Clave" => $this->clave),
                    "CodEmp" => $codEmp,
                    "CodBarra" => "",
                    "Client" => $cliente,
                    "ValoresIngManual" => array(
                        "1" => array(
                            "CodIngreso" => $arrForm[0]['CodIngreso'], "Dato" => $arrForm[0]['Dato']),
                        "2" => array(
                            "CodIngreso" => $arrForm[1]['CodIngreso'], "Dato" => $arrForm[1]['Dato'])));
                $objeto = new nusoap_client($this->wsValidar, true);
                $arrDatosSinFact = $objeto->call("ValidarDatos", $arrValidarDatosSinFac);

                $log = new Cobro_expres_log();
                $log->set_log_ce(json_encode($arrValidarDatosSinFac["Param"]));
                $log->set_fecha_log($fecha_hoy);
                $log->set_id_marchand($marchand);
                $log->set_tipo_pedido("llamada");
                $log->set_id_moves("validar_datos");
                $log->set_id_usumarchand($id_usumarchand);
                $log->set();

                $log2 = new Cobro_expres_log();
                $log2->set_log_ce(json_encode($arrDatosSinFact));
                $log2->set_fecha_log($fecha_hoy);
                $log2->set_id_marchand($marchand);
                $log2->set_tipo_pedido("respuesta");
                $log2->set_id_moves("validar_datos");
                $log2->set_id_usumarchand($id_usumarchand);
                $log2->set();



                return $arrDatosSinFact;
            } else {
                //print_r("ARRAY 1");
                $arrValidarDatosSinFac["Param"] = array("Entidad" => array("Usuario" => $this->usuario, "Clave" => $this->clave), "CodEmp" => $codEmp, "CodBarra" => "", "Client" => $cliente, "ValoresIngManual" => array("tValoresManuales" => array("CodIngreso" => $arrForm[0]['CodIngreso'], "Dato" => $arrForm[0]['Dato'])));
                $objeto = new nusoap_client($this->wsValidar, true);
                $arrDatosSinFact = $objeto->call("ValidarDatos", $arrValidarDatosSinFac);

                $log = new Cobro_expres_log();
                $log->set_log_ce(json_encode($arrValidarDatosSinFac["Param"]));
                $log->set_fecha_log($fecha_hoy);
                $log->set_id_marchand($marchand);
                $log->set_tipo_pedido("llamada");
                $log->set_id_moves("validar_datos");
                $log->set_id_usumarchand($id_usumarchand);
                $log->set();

                $log2 = new Cobro_expres_log();
                $log2->set_log_ce(json_encode($arrDatosSinFact));
                $log2->set_fecha_log($fecha_hoy);
                $log2->set_id_marchand($marchand);
                $log2->set_tipo_pedido("respuesta");
                $log2->set_id_moves("validar_datos");
                $log2->set_id_usumarchand($id_usumarchand);
                $log2->set();
                return $arrDatosSinFact;
            }
        }
    }

    public function grabarCobranza($trnCliente = null, $codEmp = null, $codBar = null, $codAge = null, $cliente = null, $importe = null, $hash = null, $id_marchand = null, $id_moves = null, $id_usumarchand = null) {
        $fecha_hoy_form = new DateTime('now');
        $fecha_hoy = $fecha_hoy_form->format('Y-m-d');
        $codAge = "37";
//        if ($codAge == null) {
//            $codAge = "?";
//            $arrGuardarCobranza["Param"] = array("ENTIDAD" => array("USUARIO" => $this->usuario, "CLAVE" => $this->clave), "TRN_CLIENTE" => $trnCliente, "CODEMP" => $codEmp, "CODBARRA" => $codBar, "COD_AGE" => $codAge, "CLIENTE" => $cliente, "IMPORTE" => $importe, "HASH" => $hash);
//            $objeto = new nusoap_client($this->wsGuardar, true);
//            $arrGuardado = $objeto->call("GrabarCobranza", $arrGuardarCobranza);
//
//            $log = new Cobro_expres_log();
//            $log->set_log_ce(json_encode($arrGuardarCobranza["Param"]));
//            $log->set_fecha_log($fecha_hoy);
//            $log->set_id_marchand($id_marchand);
//            $log->set_id_marchand("llamada");
//            $log->set_id_moves($id_moves);
//            $log->set_id_usumarchand($id_usumarchand);
//            $log->set();
//
//            $log2 = new Cobro_expres_log();
//            $log2->set_log_ce(json_encode($arrGuardado));
//            $log2->set_fecha_log($fecha_hoy);
//            $log2->set_id_marchand($id_marchand);
//            $log2->set_tipo_pedido("respuesta");
//            $log2->set_id_moves($id_moves);
//            $log2->set_id_usumarchand($id_usumarchand);
//            $log2->set();
//
//
//            return $arrGuardado;
//        } else {
        $trnCliente=$id_moves;
            $arrGuardarCobranza["Param"] = array("ENTIDAD" => array("USUARIO" => $this->usuario, "CLAVE" => $this->clave), "TRN_CLIENTE" => $trnCliente, "CODEMP" => $codEmp, "CODBARRA" => $codBar, "COD_AGE" => $codAge, "CLIENTE" => $cliente, "IMPORTE" => $importe, "HASH" => $hash);
            $objeto = new nusoap_client($this->wsGuardar, true);
            $arrGuardado = $objeto->call("GrabarCobranza", $arrGuardarCobranza);

             $log = new Cobro_expres_log();
            $log->set_log_ce(json_encode($arrGuardarCobranza["Param"]));
            $log->set_fecha_log($fecha_hoy);
            $log->set_id_marchand($id_marchand);
            $log->set_id_marchand("llamada");
            $log->set_id_moves($id_moves);
            $log->set_id_usumarchand($id_usumarchand);
            $log->set();

            $log2 = new Cobro_expres_log();
            $log2->set_log_ce(json_encode($arrGuardado));
            $log2->set_fecha_log($fecha_hoy);
            $log2->set_id_marchand($id_marchand);
            $log2->set_tipo_pedido("respuesta");
            $log2->set_id_moves($id_moves);
            $log2->set_id_usumarchand($id_usumarchand);
            $log2->set();
            return $arrGuardado;
//        }
    }

    public function anularPago($codEmp = null, $nroOpe = null, $import = null,$id_marchand=null,$id_auth=null) {
        $fecha_hoy_form = new DateTime('now');
        $fecha_hoy = $fecha_hoy_form->format('Y-m-d');
        $arrAnulacion["Param"] = array(
            "Entidad" => array("Usuario" => $this->usuario, "Clave" => $this->clave),
            "Motivo" => "A solicitud del cliente",
            "Client" => "",
            "TrnCli" => "",
            "CodEmp" => $codEmp,
            "NroOpe" => $nroOpe,
            "Import" => $import);
        $objeto = new nusoap_client($this->wsValidar, true);
        $arrAnula = $objeto->call("Anulacion", $arrAnulacion);
        //print_r("RESPUESTA DEL WS <br>");
        // print_r("<pre>");
        // print_r($arrAnula);
        // print_r("</pre>");
        
        $log = new Cobro_expres_log();
            $log->set_log_ce(json_encode($arrAnulacion["Param"]));
            $log->set_fecha_log($fecha_hoy);
            $log->set_id_marchand(3);
            $log->set_tipo_pedido("llamada");
            $log->set_id_moves("anular pago");
            $log->set_id_usumarchand($id_auth);
            $log->set();

            $log2 = new Cobro_expres_log();
            $log2->set_log_ce(json_encode($arrAnula));
            $log2->set_fecha_log($fecha_hoy);
            $log2->set_id_marchand(3);
            $log2->set_tipo_pedido("respuesta");
            $log2->set_id_moves("anular pago");
            $log2->set_id_usumarchand($id_auth);
            $log2->set();
        
        return $arrAnula;
    }

}
