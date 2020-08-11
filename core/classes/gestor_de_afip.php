<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Gestor_de_afip
 *
 * @author ariel
 */
class Gestor_de_afip {

    public function get_datos_constancia(Marchand $marchand) {
        $afip = new Afip_constancia_inscripcion();
        $afip->set_documento($marchand->get_documento());
        $constancia = $afip->CorrerMetodo();
        $persona = $tipo_doc = $nombre = $apellido = $razon_social = null;
        if (isset($constancia["personaReturn"]["datosGenerales"])) {
            if (isset($constancia["personaReturn"]["datosGenerales"]["razonSocial"]))
                $razon_social = $constancia["personaReturn"]["datosGenerales"]["razonSocial"];
            if (isset($constancia["personaReturn"]["datosGenerales"]["apellido"]))
                $apellido = $constancia["personaReturn"]["datosGenerales"]["apellido"];
            if (isset($constancia["personaReturn"]["datosGenerales"]["nombre"]))
                $nombre = $constancia["personaReturn"]["datosGenerales"]["nombre"];
            if (isset($constancia["personaReturn"]["datosGenerales"]["tipoClave"]))
                $tipo_doc = $constancia["personaReturn"]["datosGenerales"]["tipoClave"];
            if (isset($constancia["personaReturn"]["datosGenerales"]["tipoPersona"]))
                $persona = $constancia["personaReturn"]["datosGenerales"]["tipoPersona"];
        }
        $monotributo = isset($constancia["personaReturn"]["datosMonotributo"]);
        $impuestos = null;
        if (isset($constancia["personaReturn"]["datosRegimenGeneral"])) {
            if (isset($constancia["personaReturn"]["datosRegimenGeneral"]["impuesto"]))
                $impuestos = $constancia["personaReturn"]["datosRegimenGeneral"]["impuesto"];
            $impuestos_mono = null;
        }
        if (isset($constancia["personaReturn"]["datosMonotributo"])) {
            if (isset($constancia["personaReturn"]["datosMonotributo"]["impuesto"]))
                $impuestos_mono = $constancia["personaReturn"]["datosMonotributo"]["impuesto"];
        }
        if (isset($constancia["personaReturn"]["datosGenerales"])) {
            if (isset($constancia["personaReturn"]["datosGenerales"]["domicilioFiscal"])){
                $domicilioFiscal = $constancia["personaReturn"]["datosGenerales"]["domicilioFiscal"];
            }
        }
        
        $observaciones = null;
        if (isset($constancia["personaReturn"]["errorConstancia"]["error"]))
            $observaciones = $constancia["personaReturn"]["errorConstancia"]["error"];

        $ganancias = false;
        $iva = false;
        $iva_exento = false;
        if ($impuestos and count($impuestos) > 0)
            foreach ($impuestos as $impuesto) {
                if (isset($impuesto["idImpuesto"]))
                    switch ($impuesto["idImpuesto"]) {
                        case "10":
                            $ganancias = true;
                            break;
                        case "30":
                            $iva = true;
                            break;
                        case "32":
                            $iva = false;
                            $iva_exento = true;
                            break;
                    }
            }
        if (isset($impuestos_mono))
            if ($impuestos_mono and count($impuestos_mono) > 0)
                foreach ($impuestos_mono as $impuesto) {
                    if (isset($impuesto["idImpuesto"]))
                        switch ($impuesto["idImpuesto"]) {
                            case "10":
                                $ganancias = true;
                                break;
                            case "30":
                                $iva = true;
                                break;
                            case "32":
                                $iva = false;
                                $iva_exento = true;
                                break;
                        }
                }
                
        return array("apellido" => $apellido, "nombre" => $nombre, "tipo_doc" => $tipo_doc, "pfpj" => $persona, "monotributo" => $monotributo, "apellido_rs" => $razon_social, "iva" => $iva, "exento" => $iva_exento, "ganancias" => $ganancias, "direccion"=>$domicilioFiscal,"errormsg" => $observaciones);
    }
    
    public function get_datos_constancia_cuit($cuit){
        $marchands = Marchand::select_by_cuit($cuit);
        foreach ($marchands as $row){
            $marchand = new Marchand($row);
            return $this->get_datos_constancia($marchand);
        }
    }

}
