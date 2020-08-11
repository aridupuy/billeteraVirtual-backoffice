<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of service_post_express
 *
 * @author ariel
 */
class Post_express_vencidos extends Post_express {
    const ID_TABLA="id_barcode";
    public function __construct(Marchand $marchand) {
        $this->marchand = $marchand;
        $afururos = Afuturo::select_postexpress_config_vencidos($this->marchand->get_id());
        foreach ($afururos as $row) {
            $afururo = new Afuturo($row);
            $this->array_afuturo[] = $afururo;
            $config = simplexml_load_string($afururo->get_bruto_xml());
            $this->config[] = $config->sections->header->items->item;
        }
    }

    public function ejecutar() {
        developer_log("Ejecutando postexpress (Vencidos)");
        return parent::ejecutar();
    }

    public function consultar_db($tipo, $filtros_traduccion) {
        $rs_barcode = Barcode::select_barcodes_vencidos_sin_enviar($this->marchand->get_id_marchand(),$filtros_traduccion);
        developer_log("Se han encontrado ".$rs_barcode->rowCount()." Barcodes vencidos.");
	return $rs_barcode;
    }

    public function marcar_como_enviado(ADORecordSet_postgres8 $recordset) {
        developer_log("POSTEXPRESS (vencidos): marcando ultimo barcode  como enviado");

//        Moves::StartTrans();
        foreach ($recordset as $row) {
            $barcode = new Barcode();
            $barcode->get($row["id_barcode"]);
//            $b=new Barcode();
//            $b->set_id($barcode->get_id_barcode());
            $barcode->set_is_posted(1);
            developer_log("POSTEXPRESS (vencidos): Barcode  marcado es " . $barcode->get_barcode());
            if ($barcode->set()) {
                developer_log("POSTEXPRESS (vencidos): seteado correctamente");
                }
	    else{
		developer_log("POSTEXPRESS (vencidos): error al marcar todo");
//		Model::FailTrans();
            }
        }
  //      if (!Model::HasFailedTrans() and Model::CompleteTrans())
            return true;
    //    return false;
    }

    public function obtener_concepto($id) {
//        var_dump("POSTEXPRESS (Vencidos): obteniendo concepto");
        developer_log("POSTEXPRESS (Vencidos): obteniendo concepto");
        $barcode = new Barcode();
        $barcode->get($id);
        $concepto = $barcode->get_bc_xml();
        $concepto_v = new View();
        $concepto_v->loadXML($concepto, false);
        $items = $concepto_v->getElementsByTagName("item");
        $concepto_envio = new View();
        $conc = $concepto_envio->createElement("xml_concepto");
        $concepto_envio->appendChild($conc);
        foreach ($items as $item) {
            $label = $item->attributes->getNamedItem("label");
            if (!$label or $label == null)
                continue;
            $l = preg_replace("/[^A-Za-z0-9]/u", "", $label->value);
            if ($l == "" OR $l == false OR $l == null) {
                continue;
            }
            $l = $concepto_envio->createElement($l);
            foreach ($item->childNodes as $child) {
                if ($child->nodeName == 'value') {
                    $C = preg_replace("/[^A-Za-z0-9]/u", "", $child->nodeValue);
                    $contenido = $C;
                }
            }
            $l->appendChild($concepto_envio->createTextNode($contenido));
            $conc->appendChild($l);
        }
	error_log($concepto_envio->saveXML());
        return $concepto_envio->saveXML();
    }

    public function obtener_clave($row) {
        return $row["id_barcode"];
    }

    public function obtener_fecha($c, $valor,$formatos) {
        if (substr($c, 0, strlen("fecha")) == "fecha") {
            if ($valor != null) {
                error_log($valor);
                $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $valor);
                if (!$fecha)
                    $fecha = DateTime::createFromFormat('Y-m-d H:i:s.u', $valor);
                return $fecha->format($formatos[$c]);
            }
        }
	return $valor;
    }

    public  function traducir(SimpleXMLElement $filtros){
        $nuevos_filtros = array();
	//print_r($filtros);
        foreach ($filtros as $clave=> $filtro) {
            //var_dump($filtro);
	    $filtros = json_decode(json_encode($filtros), true);
            $filtro = json_decode(json_encode($filtro), true);
	 // foreach ($filtro as $clave=>$value){
//	    var_dump($filtro);
            if (isset($filtro["@attributes"]["move"]))
                switch ($filtro["@attributes"]["move"]) {
                    case "barcode":
                        $nuevos_filtros[$filtro[0]] = "barcode";
                        break;
                    case "pmc19":
                        $nuevos_filtros[$filtro[0]] = "pmc19";
                        break;
                    case "bc_xml":
                        $nuevos_filtros[$filtro[0]] = "boleta_concepto";
                        break;
                    case "monto":
                        $nuevos_filtros[$filtro[0]] = "monto";
                        break;
                   case "fecha_vto":
                        $nuevos_filtros[$filtro[0]] = "fecha_vto";
                        break;
                    case "fecha":
                        $nuevos_filtros[$filtro[0]] = "fecha_vto";
                        break;
                    
           //     }
        	}
	 
	}
//	var_dump($nuevos_filtros);
//	exit();
	return $nuevos_filtros;
    }
}
