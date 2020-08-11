<?php
require_once '../public/MercadoPago_last/autoload.php';
class landing_barcode {

    private $get;
    private $view;

    public function __construct($get) {
        $this->get = $get;
        $this->view = new View();
    }

    public function mostrar() {
        developer_log($this->get);
        $this->view->cargar("views/landing_barcode_1.html");
        if (!isset($this->get['pdf']))
            $pdf = false;
        else {
            $pdf = $this->get["pdf"];
        }
        if (isset($this->get["nav"]) ) {
            $rsbarcode = Barcode::select(["barcode" => $this->get["barcode"]]);
            $barcode = new Barcode($rsbarcode->fetchRow());
            $debito_cbu = new Debitos_cbu();
            $responsable=new Responsable();
            $id_tipocuenta=1;
            Model::StartTrans();
            $mensaje = "Debito agendado correctamente.";
            $fecha = DateTime::createFromFormat("Y-m-d", $this->get["fecha_pago"]);
            $fecha_vto= DateTime::createFromFormat("Y-m-d h:i:s", $barcode->get_fecha_vto());
	    while(!Calendar::es_dia_habil($fecha_vto)){
                $fecha_vto->sub(new DateInterval("P1D"));
            }
            if((int)$fecha->format("Ymd")>(int)$fecha_vto->format("Ymd")){
                $fecha=$fecha_vto;
                $mensaje="la fecha ingresada puede ser como maximo la fecha de vencimiento";
            }
	    developer_log($fecha->format("Y-m-d"));
            developer_log($fecha_vto->format("Y-m-d"));
	    developer_log($this->get);
            try{
            if (!Model::HasFailedTrans() and ($responsable = $responsable->crear($barcode->get_id_marchand(), $this->get["nombre"], $this->get["apellido"], $this->get["cuit"], Tipodoc::CUIT_CUIL)) == false) {
                Model::FailTrans();
                $mensaje = "No se puede generar el Responsable.";
                
            }
            if (!Model::HasFailedTrans() and ( $responsable = $responsable->crear_cbu($barcode->get_id_marchand(), $responsable::$clima->get_id(), $this->get["cbu"], $id_tipocuenta, $this->get["nombre"], $this->get["cuit"])) == false) {
                Model::FailTrans();
                $mensaje = "No se puede generar el cbu";
                
            }
//            if(!Model::HasFailedTrans() and !$debito_cbu->crear($barcode->get_id_marchand(), $responsable::$clima_cbu->get_id(), $this->get["importe"], $fecha->format("d/m/Y"), $this->get["concepto"])){
  	     if(!Model::HasFailedTrans() and !$debito_cbu->crear($barcode->get_id_marchand(), $responsable::$clima_cbu->get_id(), $this->get["importe"], $fecha->format("d/m/Y"), $this->get["concepto"],1,"mensuales",false,false,false,false,false,false,$this->get["nombre"]." ".$this->get["apellido"])){
                Model::FailTrans();
                $mensaje = "No  se puede agendar el debito por cbu";
             }
            } catch (Exception $e){
                $mensaje = $e->getMessage();
                Model::FailTrans();
            }
            if(!Model::HasFailedTrans() and Model::CompleteTrans()){
                $this->view->getElementById("contenedor_mensaje_1")->appendChild($this->view->createTextNode("Debito agendado Correctamente"));
            }
            else
                $this->view->getElementById("contenedor_mensaje_1")->appendChild($this->view->createTextNode($mensaje));
            unset($this->get["cbu"]);
        }

        if (!isset($this->get['cbu']))
            $cbu = false;
        else {
            $cbu = $this->get["cbu"];
            $this->view->cargar("views/landing_barcode_cbu.html");
            $rsbarcode = Barcode::select(array("barcode" => $this->get["barcode"]));
            $row = $rsbarcode->fetchRow();
            $barcode = new Barcode($row);
            $marchand = new Marchand();
            $marchand->get($barcode->get_id_marchand());
            $bolemarchand = new Bolemarchand();
            $bolemarchand->get($barcode->get_id_boletamarchand());
            if ($bolemarchand->get_detalle() != "") {
                $concepto = $bolemarchand->get_detalle();
            } else {
                $concepto = $barcode->get_barcode();
            }
            $this->view->getElementById("concepto")->appendChild($this->view->createTextNode($concepto));
            $this->view->getElementById("monto")->appendChild($this->view->createTextNode($barcode->get_monto()));
            $this->view->getElementById("marchand_span")->appendChild($this->view->createTextNode($marchand->get_apellido_rs()));
            $this->view->getElementById("barcode_1")->setAttribute("value", $barcode->get_barcode());
            $this->view->getElementById("concepto_1")->setAttribute("value", $concepto);
            $this->view->getElementById("monto_1")->setAttribute("value", $barcode->get_monto());
            if ($barcode->get_monto() == 0) {
                $this->view->getElementById("monto")->parentNode->removeChild($this->view->getElementById("monto"));
                $this->view->getElementById("monto_1")->parentNode->removeChild($this->view->getElementById("monto_1"));
                $newMonto = $this->view->createElement("input");
                $newMonto->setAttribute("name", "monto");
                $newMonto->setAttribute("id", "monto");
//                $newMonto->setAttribute("id", "monto");
                $this->view->getElementById("span_monto")->appendChild($newMonto);
            }
            $imgemp = $this->view->getElementById("imgemp");
            if (isset($this->get["imagen"])) {
                $imgemp->setAttribute("src", $this->get["imagen"]);
            } else {

                $imgemp->setAttribute("src", "https://www.cobrodigital.com/images/imgempresa/logoscomerciales/" . $marchand->get_mlogo());
            }
            if($mensaje)
                $this->view->getElementById("contenedor_mensaje")->appendChild($this->view->createTextNode($mensaje));
            return $this->view->saveHTML();
        }
        
//        and $this->get["nav"] == "crear_debito"
        
        if (!isset($this->get["barcode"]))
            return "Error debe ingresar un barcode";
        if (isset($this->get["valores_variables"])) {
            $valores_variables = $this->get["valores_variables"];
            $elemento = $this->view->getElementById("parametros");
            foreach ($valores_variables as $clave => $valor) {
                $d = $this->view->createElement("div");
                $d->setAttribute("class", "left");
                $strong = $this->view->createElement("strong", $clave);
                $span1 = $this->view->createElement("span", " : " . $valor);
                $strong->setAttribute("class", "strong");
                $d->appendChild($strong);
                $d->appendChild($span1);
                $elemento->appendChild($d);
            }
        }
        
        $codigobarras = $this->get["barcode"];
        $rs_barcode = Barcode::select(array("barcode" => $codigobarras));
        if ($rs_barcode->rowCount() > 0)
            $barcode = new Barcode($rs_barcode->fetchRow());
        else {
            $rs_barcode = Barcode::select(array("pmc19" => $codigobarras, "id_tipopago" => "100"));
            if ($rs_barcode->rowCount() > 0) {
                $barcode = new Barcode($rs_barcode->fetchRow());
            } else
                return "Error el codigo no existe";
        }

        $marchand = new Marchand();
        $marchand->get($barcode->get_id_marchand());
        $rs_xml = Xml::select(array("id_marchand" => $marchand->get_id_marchand(), "id_entidad" => Entidad::ESTRUCTURA_CONFIG_MARCHAND));
        $xml = new Xml($rs_xml->fetchRow());
        $xml_view = new View();
        $xml_view->loadHTML($xml->get_xmlfield());

//        print_r($marchand);
        $bc = $this->view->getElementById("barcode");
        $bc->appendChild($this->view->createTextNode($barcode->get_barcode()));
        $pmc = $this->view->getElementById("pmc");
        $pmc->appendChild($this->view->createTextNode($barcode->get_pmc19()));

        $mch = $this->view->getElementById("marchand");
//        $mch->appendChild($this->view->createTextNode($marchand->get_nombre() . " " . $marchand->get_apellido_rs()));
        $mch->appendChild($this->view->createTextNode($marchand->get_minirs()));
        $imgemp = $this->view->getElementById("imgemp");
        if (isset($this->get["imagen"])) {
            $imgemp->setAttribute("src", $this->get["imagen"]);
        } else {

            $imgemp->setAttribute("src", "https://www.cobrodigital.com/images/imgempresa/logoscomerciales/" . $marchand->get_mlogo());
        }

        $direccion = $this->view->getElementById("direccion");
        $direccion->appendChild($this->view->createTextNode($marchand->get_gr_calle() . " " . $marchand->get_gr_numero()));
        $imgBarcode = $this->view->getElementById("imgBarcode");
        $imgBarcode->setAttribute('src', 'https://www.cobrodigital.com/wse/bccd/' . $barcode->get_barcode() . "H.png");
//        $imgBarcode->setAttribute('src', 'http://localhost:456/getbarcode/' . $barcode->get_barcode() . "HL.png");

        $rs = Moves::select_moves_barcode($barcode->get_id_barcode());
        developer_log($rs->rowCount());
//        if ($rs->rowCount() <= 0) {
        $config = new Configuracion();
        $conf=$config->obtener_configuracion_de_tag($barcode->get_id_marchand(), Entidad::ENTIDAD_MARCHAND,Configuracion::CONFIG_CARRIER_TC);
        if($conf[Configuracion::ID_TAG_CARRIER][Configuracion::TAG_CARRIER_TC]=='decidir'){
            $pagar = $this->view->getElementById("pagar_tc");
            $pagar->parentNode->removeChild($pagar);
            $decidir = $this->view->getElementById("web_decidir");
            $decidir->setAttribute("href", "/externo/landing_pagos_tc.php?barcode=".$barcode->get_barcode());
            $pei = $this->view->getElementById("web_pei");
            $pei->setAttribute("href", "/externo/landing_pagos_pei.php?barcode=".$barcode->get_barcode());
        }
        else{
            $decidir = $this->view->getElementById("pagar_decidir");
            $decidir->parentNode->removeChild($decidir);
            
            
            $mercadopago = $xml_view->getElementsByTagName("mercadopago");
            $mercadopago = $mercadopago->item(0);
            if ($mercadopago !== null)
                $habilitado = $mercadopago->getAttribute("habilitado");
            if ($habilitado != 1) {
                developer_log("habilitado");
                $tc = $this->view->getElementById("pagar_tc");
                $tc->parentNode->removeChild($tc);
            }
            else{
                developer_log("no habilitado");
            }
            $archivo = $this->view->getElementById("descargar");
            if (!$pdf) {

                $archivo->parentNode->removeChild($archivo);
            } else {
                $archivo->setAttribute("href", URL_DOWNLOAD . $marchand->get_mercalpha() . "/" . $pdf);
                $archivo->setAttribute("download", $pdf);
            }
            $array = array();
            foreach ($mercadopago->childNodes as $child) {
                if (get_class($child) !== "DOMText") {
                    if ($child->tagName == "sonda_key")
                        $array["sonda_key"] = $child->nodeValue;
                    if ($child->tagName == "acc_id")
                        $array["acc_id"] = $child->nodeValue;
                    if ($child->tagName == "enc")
                        $array["enc"] = $child->nodeValue;
                    if ($child->tagName == "button_src")
                        $array["button_src"] = $child->nodeValue;
                }
            }
            switch ($array["acc_id"]){
                case "511585208":
                    developer_log("Collect");
                    developer_log(MERCADOPAGO_CLAVE_PRIVADA_COLLECT);
                     MercadoPago\SDK::setAccessToken(MERCADOPAGO_CLAVE_PRIVADA_COLLECT);
                     break;
                case "27344279":
                    developer_log("MP");
                     MercadoPago\SDK::setAccessToken(MERCADOPAGO_CLAVE_PRIVADA_MP);
                     break;
                case "512305941":
                    developer_log("MP3");
                     MercadoPago\SDK::setAccessToken(MERCADOPAGO_CLAVE_PRIVADA_MP3);
                     break;
                case "549098890":
                    developer_log("CLICKPAGOS");
                     MercadoPago\SDK::setAccessToken(MERCADOPAGO_CLAVE_PRIVADA_CLICKPAGOS);
                     break;
                default :
                    developer_log("DEFAULT");
                     MercadoPago\SDK::setAccessToken(MERCADOPAGO_CLAVE_PRIVADA_MP3);
                     break;
            }
            $preferencia = new MercadoPago\Preference();
            $preferencia->external_reference = $barcode->get_barcode();
            $item = new MercadoPago\Item();
            if(isset($bolemarchand))
                $item->title = $bolemarchand->get_boleta_concepto();
            elseif (isset ($concepto))
                $item->title = $concepto;
            else
                $item->title = $barcode->get_barcode ();
            $item->quantity = 1 ; 
            $item->picture_url = "https://www.cobrodigital.com/images/imgempresa/logoscomerciales/" . $marchand->get_mlogo();
            if (isset($this->get["a_pagar"]) and $this->get["a_pagar"])
                $item->unit_price = $this->get['a_pagar'];
            else
                $item->unit_price = $barcode->get_monto();
//            $preferenci
            $preferencia->items = array($item);
            $preferencia->save();
            $script = $this->view->getElementById("script_nuevo");
            $script->setAttribute("data-preference-id", $preferencia->id);
//            if (isset($array["acc_id"])) {
//                $acc_id = $this->view->getElementById("acc_id");
//                $acc_id->setAttribute('value', $array["acc_id"]);
//            }
//            if (isset($array["enc"])) {
//                $enc = $this->view->getElementById("enc");
//                $enc->setAttribute("value", $array["enc"]);
//            }
//            $price = $this->view->getElementById("price");
//            if (isset($this->get["a_pagar"]) and $this->get["a_pagar"])
//                $price->setAttribute('value', $this->get['a_pagar']);
//            else
//                $price->setAttribute('value', $barcode->get_monto());
////        $price->setAttribute('value', $barcode->get_monto());
//            $seller_op_id = $this->view->getElementById("seller_op_id");
//            $seller_op_id->setAttribute('value', $barcode->get_barcode());
        }
        if($rs->rowCount()>0){
            $table = new Table($rs, 1, $rs->rowCount());
            $pagar_tc = $this->view->getElementById("tabla");
            $table->cambiar_encabezados(array("Medio de Pago", "Fecha Pago", ""));
            $pagar_tc->appendChild($this->view->createElement("h2", "Pagos Recibidos"));
            $pagar_tc->appendChild($this->view->createElement("br"));
            $pagar_tc->appendChild($this->view->importNode($table->documentElement, true));
//            $pagar = $this->view->getElementById("pagar");
//            $pagar->parentNode->removeChild($pagar);
            $pagar = $this->view->getElementById("mensaje_barcode");
//            $pagar->parentNode->removeChild($pagar);
        }
        $this->view->getElementById("hdn-barcode")->setAttribute("value", $this->get["barcode"]);
        
        
        return $this->view->saveHTML();
    }

}
