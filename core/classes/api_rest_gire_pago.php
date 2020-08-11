<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of api_rest_gire_pago
 *
 * @author ariel
 */
class Api_rest_gire_pago extends Api_rest_gire {

    //put your code here
    public static $claves = array("id_numero", "cod_trx", "canal", "importe", "barra", "fecha_hora_operacion");
    const TELERECARGAS = "trasa";
    public function __construct() {
        parent::__construct();
    }

    public function run() {
        Model::StartTrans();
        $variables = $this->get_variables();
        $importe = $variables["importe"];
        $codigo_barras = $variables["barra"];
        $fecha_pago = DateTime::createFromFormat("Y-m-d H:i:s", $variables["fecha_hora_operacion"]);
        if (($barcode = $this->obtener_barcode($codigo_barras)) != false) {
            $control = $this->obtener_ultimo_control();
            if (!$this->validar_barcode($control, $barcode)) {
		
                Model::FailTrans();
                throw new ExceptionApiGire("Este codigo ya fue cobrado.", 2);
            }
            if (!$control) {
                Model::FailTrans();
                throw new ExceptionApiGire("Error interno de la entidad", 10);
            }
//            var_dump($control);
            $sabana = new Sabana();
            $sabana->set_revno($control->get_revno());
            $sabana->set_fecha_vto($barcode->get_fecha_vto());
            $sabana->set_fechagen("now()");
            $sabana->set_fecha_pago("now()");
            $sabana->set_barrand($barcode->get_barrand());
            $sabana->set_barcode($barcode->get_barcode());
            $sabana->set_id_barcode($barcode->get_id_barcode());
            $sabana->set_id_authstat(Authstat::SABANA_ENTRANDO);
            if($variables["canal"]==self::TELERECARGAS){
                $sabana->set_id_mp(Mp::TELERECARGAS);
            }
            else 
                $sabana->set_id_mp(Mp::RAPIPAGO);
            $sabana->set_id_formapago(1);
            $sabana->set_nlinea(0);
            $sabana->set_sc($barcode->get_id_sc());
            $sabana->set_monto($importe);
            $sabana->set_id_marchand($barcode->get_id_marchand());
            $sabana->set_xml_extra("Sabana ingresada por CashIn ". json_encode($variables));
            if (!$sabana->set()) {
                Model::FailTrans();
                throw new ExceptionApiGire("Error al guardar el pago", 10);
            }
            if (!Model::HasFailedTrans() and Model::CompleteTrans())
                return $this->responder($sabana, $control);
        }
        Model::FailTrans();
        throw new ExceptionApiGire("Error interno del sistema", 10);
    }

    private function obtener_barcode($codigo_barras) {
        if (!validar_barcode(trim($codigo_barras))) {
            var_dump($codigo_barras);
	    throw new ExceptionApiGire("Error de validación del código de cliente: Barcode invalido", 8);
        }
        $rs_bar = Barcode::select(array("barcode" => $codigo_barras));
        if ($rs_bar->rowCount() < 1) {
            throw new ExceptionApiGire("Error de validación del código de cliente: Barcode no existe", 8);
        }
	developer_log($codigo_barras." obtenido");
        $barcode = new Barcode($rs_bar->fetchRow());
        return $barcode;
    }

    private function obtener_ultimo_control() {
        $hoy = new DateTime("now");
        if (($rscontrol = Control::obtener_ultimo_control_mp($hoy, Mp::RAPIPAGO)) != false and $rscontrol->rowCount() > 0) {
            developer_log("encontre el control");
            $control = new Control($rscontrol->fetchRow());
            return $control;
        } else {
            developer_log("No encontre el control, lo crearé.");
            $control = new Control();
            $control->set_date_run($hoy->format("Y-m-d H:i:s"));
            $control->set_script("CashIn Rapipago");
            $control->set_id_mp(Mp::RAPIPAGO);
            $control->set_success(0);
            $control->set_tplfile("CashIn: Rapipago");
            $control->set_csvfile("CashIn Rapipago: " . $hoy->format("Y-m-d"));
            $control->set_seq1(0);
            if ($control->set()) {
                return $control;
            }
        }
        return false;
    }

    private function responder(Sabana $sabana, Control $control) {
        /*
          "id_numero": "1234567890",
          "cod_trx": "1234567890123456789012",
          "barra": "12345678901234567890123456789012345678901234567890",
          "fecha_hora_operacion": "2014-09-19 15:34:56",
          "codigo_respuesta": "0",
          "msg": "Trx ok" */
        $id_sabana = $sabana->get_id();
        $sabana = new Sabana();
        $sabana->get($id_sabana);
        $respuesta = array();
        $variables = $this->get_variables();
        $respuesta["id_numero"] = $variables["id_numero"];
        $respuesta["cod_trx"] = $variables["cod_trx"];
        $respuesta["barra"] = $sabana->get_barcode();
        $respuesta["fecha_hora_operacion"] = $sabana->get_fechagen();
        $respuesta["codigo_respuesta"] = "0";
        $respuesta["msg"] = 'Trx ok';
        return $respuesta;
    }

    public static function obtener_resultado_fallo(ExceptionApiGire $e = null) {
        if ($e !== null) {
            $result["codigo_respuesta"] = "".$e->getCode();
            $result["msg"] = $e->getMessage();
        } else {
            $result["codigo_respuesta"] = "10";
            $result["msg"] = "Error interno del sistema";
        }
        $vars = self::levantar_variables_fallo();
        if ($vars) {
            $result["cod_trx"] = $vars["cod_trx"];
            $result["id_numero"] = $vars["id_numero"];
        } else {
            $result["cod_trx"] = null;
            $result["id_clave"] = null;
        }
        return $result;
    }

    private function validar_barcode(Control $control, Barcode $barcode) {
        $rs_sabana = Sabana::select(array("revno" => $control->get_revno(), "barcode" => $barcode->get_barcode()));
        if ($rs_sabana->rowCount() == 0) {
            return true;
        }

        return false;
    }

}
