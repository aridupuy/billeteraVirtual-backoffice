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
class Api_rest_gire_cashout_consulta extends Api_rest_gire {

    public static $claves = array("id_clave", "cod_trx", "canal", "fecha_hora_operacion");

    //put your code here
    public function __construct() {
        parent::__construct();
    }

    public function run() {
        parent::run();
        return $this->responder($this->encontrar_disponibles($this->buscar()));
    }

    private function buscar() {
        $rs = Autorizados_retiros::select(array("documento" => $this->variables["id_clave"], "id_authstat" => Authstat::ACTIVO));
        if ($rs->rowCount() == 0) {
            throw new ExceptionApiGire("El cliente no esta autorizado para retirar", 11);
        }
        $marchands = array();
        foreach ($rs as $row) {
            $marchand = new Marchand();
            $marchand->get($row["id_marchand"]);
            $marchands[] = $marchand;
        }
        return $marchands;
    }

    private function encontrar_disponibles($marchands) {
        $cliente = new Cliente();
        $results = array();
        foreach ($marchands as $marchand) {
            if ($this->validar_marchand($marchand->get_id_marchand())) {
                $detalle_cuenta = $cliente->obtener_estado_de_cuenta($marchand->get_id_marchand());
                $results[$marchand->get_id_marchand()] = $detalle_cuenta["saldo_disponible"];
            }
        }
        return $results;
    }

    private function responder($results) {
        if (count($results) == 0) {
            throw new ExceptionApiGire("No existe registro", 6);
        }
        $deuda = array();
        $transaccion = new Transaccion();
        $deuda["id_clave"] = $this->variables["id_clave"];
        list($apellido, $nombre) = $this->obtener_datos_autorizado($this->variables["id_clave"]);
        $deuda["nombre"] = $nombre;
        $deuda["apellido"] = $apellido;
        $deuda["cod_trx"] = $this->clave_proceso;
        $deuda["codigo_respuesta"] = "0";
        $deuda["msg"] = "Trx ok";
        $deuda["dato_adicional"] = "";
        foreach ($results as $id_marchand => $disponible) {
            $marchand = new Marchand();
            $marchand->get($id_marchand);
            $array["id_numero"] = $marchand->get_mercalpha();
            $fecha_vto = new DateTime("now");
            $fecha_vto->add(new DateInterval("P1D"));
            $fecha_emi = new DateTime("now");
            $array["fecha_vencimiento"] = $fecha_vto->format("Y-m-d");
            $array["fecha_emision"] = $fecha_emi->format("Y-m-d");

            if ($disponible > 0) {
                $comision = $transaccion->calculo_directo($id_marchand, Mp::CASHOUT, $disponible);
                $comi = ($comision[0] - $comision[6]);
                $array["importe"] = -1 * $disponible - $comi;
            } else
                $array["importe"]  = 0;
            $array["barra"] = "7385" . $fecha_emi->format("ymdhisu") . str_replace(".", "", $disponible) . $id_marchand;
            //debo guadar la barra generada?
            $this->guardar_barra($array["barra"], $id_marchand);
//                    var_dump($factura);
            $array["texto1"] = "Para pago inmediato";
            $deuda["facturas"][] = $array;
        }
        return $deuda;
    }

    private function guardar_barra($barra, $id_marchand) {
        $cashout = new Cashout_barras();
        $cashout->set_barra($barra);
        $cashout->set_id_authstat(Authstat::SABANA_ENTRANDO);
        $cashout->set_id_marchand($id_marchand);
        $cashout->set_fechagen("now()");
        
        $rs = Autorizados_retiros::select(array("documento" => $this->variables["id_clave"], "id_authstat" => Authstat::ACTIVO,"id_marchand"=>$id_marchand));
        if ($rs->rowCount() == 0) {
            throw new ExceptionApiGire("El cliente no esta autorizado para retirar", 11);
        }
        $autorizado=new Autorizados_retiros($rs->fetchRow());
        $cashout->set_id_autorizado($autorizado->get_id());
        if ($cashout->set()) {
            return true;
        }
        throw new ExceptionApiGire("Error al generar la informacion.", 2);
    }

    private function obtener_datos_autorizado($clave) {
        $rs = Autorizados_retiros::select(array("documento" => $clave, "id_authstat" => Authstat::ACTIVO));
        $autorizado = new Autorizados_retiros($rs->fetchRow());
        return array($autorizado->get_apellido(), $autorizado->get_nombre());
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
