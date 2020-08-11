<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of api_rest_gire
 *
 * @author ariel
 */
include 'exceptionApiGire.php';

class Api_rest_gire {

    protected $variables;
    protected $clave_proceso;

    public function __construct() {
        $this->leer_variables();
        $this->validar_parametros(static::$claves);
        $this->set_clave_proceso($this->variables["cod_trx"]);
    }

    public static function Factory($metodo) {
        switch ($metodo) {
            case "consulta":
            case "Consulta":
                return new Api_rest_gire_consulta();
                break;
            case "pago":
            case "Pago":
                return new Api_rest_gire_pago();
                break;
            case "Reversa":
            case "reversa":
                return new Api_rest_gire_reversa();
            default :
                throw new ExceptionApiGire("Operación inválida", 5);
        }
    }

    public static function Factory_cashout($metodo) {
        switch ($metodo) {
            case "consulta":
            case "Consulta":
                return new Api_rest_gire_cashout_consulta();
                break;
            case "pago":
            case "Pago":
                return new Api_rest_gire_cashout_pago();
                break;
            default :
                throw new ExceptionApiGire("Operación inválida", 5);
        }
    }

    public function set_clave_proceso($clave_proceso) {
        $this->clave_proceso = $clave_proceso;
    }

    public function get_clave_proceso() {
        return $this->clave_proceso;
    }

    private function leer_variables() {
//        var_dump("leyendo vars");
        $input = file_get_contents('php://input');
        $variables = json_decode($input, true);
        if (!$variables) {
            throw new ExceptionApiGire("Parametros faltantes.", 9);
        }
        $this->variables = $variables;
        developer_log("variables leidas correctamente.");
        return true;
    }

    public function get_variables() {
        return $this->variables;
    }

    public function run() {
        //override
    }

    protected function validar_parametros($params_esperados) {
        foreach ($params_esperados as $param) {
            $claves = array_keys($this->variables);
            if (!in_array($param, $claves)) {
                throw new ExceptionApiGire("Parametro $param faltante.", 9);
            }
        }
        developer_log("variables validadas correctamente.");
        return true;
    }

    protected function obtener_datos_climarchand(Climarchand $climarchand) {

        $pagador = new Pagador();
        $estructura_xml = Xml::estructura($climarchand->get_id_marchand(), Entidad::ESTRUCTURA_CLIENTES);
        $array = $pagador->armar_array($climarchand->get_cliente_xml(), $estructura_xml);
        foreach ($array as $sap => $dato) {
            if ($sap == "sap_apellidors" OR $sap == "sap_apellido")
                $apellido = $dato["value"];

            if ($sap == "sap_nombre" OR $sap == "sap_email" or $sap == "sap_mailing")
                $nombre = $dato["value"];
        }
        return array($apellido, $nombre);
    }

    protected function validar_marchand($id_marchand) {
        $marchand = new Marchand();
        $marchand->get($id_marchand);
        if ($marchand->get_id_authstat() != Authstat::ACTIVO) {
            throw new ExceptionApiGire("Usuario no habilitado para operar", 11);
        }
        return true;
    }

    public static function levantar_variables_fallo() {
        $input = file_get_contents('php://input');
        $vars = json_decode($input, true);
        if (!$vars) {
            return false;
        }
        return $vars;
    }

}
