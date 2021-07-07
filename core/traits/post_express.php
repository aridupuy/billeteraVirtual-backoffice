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
class Post_express {

    protected $marchand;
    protected $array_afuturo = array();
    protected $config = array();
    protected $postexpress = false;
    protected $afuturo_run;
    const ID_TABLA="id_moves";
    public function __construct(Marchand $marchand) {
        $this->marchand = $marchand;
        $afururos = Afuturo::select_postexpress_config($this->marchand->get_id());
        foreach ($afururos as $row) {
            $afururo = new Afuturo($row);
            $this->array_afuturo[] = $afururo;
            $config = simplexml_load_string($afururo->get_bruto_xml());
            $this->config[] = $config->sections->header->items->item;
        }
    }

    public function ejecutar() {
//        var_dump($this->array_afuturo);
        foreach ($this->array_afuturo as $clave => $afuturo) {
            if ($this->verificar_bloqueo()) {
                continue;
            }
            if (!$this->bloquear_afuturo($afuturo)) {
                throw new Exception("No se puede bloquear el registro");
            }
           
            $config = $this->config[$clave];
            $this->postexpress = false;
            if (isset($config->url) and (String) $config->url != "") {
                $url = (String) $config->url;
                $url2 = (String) $config->no_url[0];
                $url3 = (String) $config->no_url[1];
                $this->postexpress = true;
            }
            if (isset($config->attach)) {
                $nombre_archivo = $config->attach;
            }
            if (isset($config->tipomovimiento)) {
                $tipo = $config->tipomovimiento;
            } else {
                $tipo = "ingresos";
            }
            if (isset($config->subject)) {
                $asunto = $config->subject;
            } else {
                $asunto = "Informe Apsi";
            }
            if (isset($config->to)) {
                $para = $config->to;
                if ($para == "solicitante") {
                    $solicitante = (int) $config->solicitante;
                    $usumarchand = new Usumarchand();
                    $usumarchand->get($solicitante);
                    $para = $usumarchand->get_usermail();
                }
            } elseif ($this->postexpress) {
                throw new Exception("No es un postexpress y no tiene mail configurado");
            }
            if (isset($config->cc)) {
                $cc = $config->cc;
            } else {
                $cc = "";
            }
            if (isset($config->mensaje)) {
                $prefijo_mensaje = $config->mensaje;
            } else {
                $prefijo_mensaje = "";
            }
            $formatos = array();
            if (isset($config->postdata->pdi)) {
                developer_log("POSTEXPRESS: traduciendo parametros");
                foreach ($config->postdata->pdi as $clave => $elemento) {
                    $elemento = json_decode(json_encode($elemento), true);
                    if (isset($elemento["@attributes"]["fmt"])) {
                        switch ($elemento["@attributes"]["fmt"]) {
                            case 2:
                                $formatos[$elemento[0]] = "Y-m-d h:i:s";
                                break;
                            case 1:
                                $formatos[$elemento[0]] = "d/m/Y";
                                break;
                            case 3:
                            case 4:
                            default:
                                $formatos[$elemento[0]] = "Y-m-d h:i:s";
                                break;
                        }
                    }
                }
                $filtros_traduccion = $this->traducir($config->postdata->pdi);
//                var_dump($filtros_traduccion);
//                $filtros_nombres= array_keys($filtros_traduccion);
//                print_r($formatos);
//                exit();
            } else {
                throw new Exception("No se definieron los campos a enviar");
            }
            
            $recordset = $this->consultar_db($tipo, $filtros_traduccion);
//            var_dump($this->postexpress);
//            var_dump($recordset->rowCount());
            Model::StartTrans();
            if ($this->postexpress) { //revisar posteo
                $ids_moves = array();
                developer_log("POSTEXPRESS: Iniciando posteo de datos");
                $recordset->moveFirst();
                if ($recordset->rowCount() == 0) {
                    developer_log("POSTEXPRESS: no hay registros que informar");
                    Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, $para, $asunto, "No Hay registros para informar");
                    Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, "doviedo@cobrodigital.com", $asunto, "No Hay registros para informar");
//                    Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, "adupuy@cobrodigital.com", $asunto, "No Hay registros para informar");
                    $this->desbloquear_afuturo($afuturo);
	               continue;
                }
                foreach ($recordset as $clave => $row) {
  //                  var_dump($row);
                    foreach ($row as $c => $valor)
                        $row[$c] = $this->obtener_fecha($c, $valor,$formatos);
                    developer_log("POSTEXPRESS: posteando");
//                    var_dump($clave);
                    $ids_moves[$clave] = $this->obtener_clave($row);
                    $row["CDcomercio"] = $this->marchand->get_mercalpha();
                    $row["sid"] = $afuturo->get_sid();
                    $row["clave"] = $afuturo->get_elcampo();
                    $row["aplicacion"] = "postExpress";
                    $row["generador"] = "";
                    $row["concepto"] = $this->obtener_concepto($row[static::ID_TABLA]);
                    unset($row["id_moves"]);
                    unset($row["id_mp"]);
                    $envio_a = array();
                    foreach ($row as $clave => $valor) {
                        if (!is_numeric($clave) and ! in_array($clave, array("monto_pagador", "monto_marchand")))
                            $envio_a[$clave] = $valor;
                    }
                    $envio = (http_build_query($envio_a));
                    $options = array(
                        'http' => array(
                            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                            'method' => 'POST',
                            'content' => $envio,
                            'http' =>
                            array(
                                'timeout' => 150, //1200 Segundos son 2.5 Minutos
                            ),
                        )
                    );
                    $context = stream_context_create($options);
                    developer_log("POSTEXPRESS: Posteando a $url");
                    $result = file_get_contents($url, false, $context);
                    if (!$result) {
                        if ($url2) {
                            developer_log("POSTEXPRESS: Posteando a $url2");
                            $result = file_get_contents($url2, false, $context);
                        }
                    }
                    if (!$result) {
                        if ($url3) {
                            developer_log("POSTEXPRESS: Posteando a $url3");
                            $result = file_get_contents($url3, false, $context);
                        }
                    }
                }
            }
            if (isset($para) and $para != "") {
                developer_log("POSTEXPRESS: Generando archivo");
                $disco = new Gestor_de_disco();
                $fecha = new DateTime("now");
                $filename = "$nombre_archivo" . $fecha->format("Ymd-hi") . ".csv";
                $array = array();
                $recordset->moveFirst();
//                var_dump($recordset->rowCount());
                $cant = 0;
                $sum_neto = 0;
                $sum_bruto = 0;
                
                foreach ($recordset as $clave => $row) {
                    
                    $row["concepto"] = $this->obtener_concepto($row[STATIC::ID_TABLA]);
                    $array[] = $row;
                    $cant++;
                    if(isset($row["monto_pagador"] ) and isset($row["monto_marchand"] )){
                        $sum_bruto += $row["monto_pagador"];
                        $sum_neto += $row["monto_marchand"];
                        unset($row["monto_pagador"]);
                        unset($row["monto_marchand"]);
                    }
                }
                $archivo = $this->armar_archivo($array, $afuturo, $filtros_traduccion);
                $mercalpaha = $this->marchand->get_mercalpha();
                $path = "$mercalpaha";

                developer_log("POSTEXPRESS: generando archivo " . PATH_CDEXPORTS . $path . "/$filename");
                if ((file_put_contents(PATH_CDEXPORTS . $path . "/$filename", implode(PHP_EOL, $archivo))) != false) {
                    developer_log("POSTEXPRESS: Enviando por mail a $para con asunto $asunto mensaje $prefijo_mensaje ");
                    $nom_marchand = $this->marchand->get_nombre() . " " . $this->marchand->get_apellido_rs();
                    $mensaje = "Estimado $nom_marchand,le adjuntamos un archivo el cual contiene , "
                            . "Cantidad de registros : $cant " .
                            "Monto_bruto : $sum_bruto" .
                            "Monto_neto: $sum_neto"
                            . $prefijo_mensaje;
                    if (Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, $para, $asunto, $mensaje, PATH_CDEXPORTS . $path . "/" . $filename)) {
                        $result = true;
                    }
                    if (Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, "doviedo@cobrodigital.com", $asunto, $mensaje, PATH_CDEXPORTS . $path . "/" . $filename)) {
                        $result = true;
                    }
//                    if (Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, "adupuy@cobrodigital.com", $asunto, $mensaje, PATH_CDEXPORTS . $path . "/" . $filename)) {
  //                      $result = true;
    //                }
                    developer_log("POSTEXPRESS: Enviando por mail a $cc con asunto $asunto mensaje $prefijo_mensaje ");
                    if ($cc AND Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, $cc, $asunto, $mensaje, PATH_CDEXPORTS . $path . "/" . $filename)) {
                        $result = true;
                    }
                    $result = true;
                }
            }
            developer_log("POSTEXPRESS: $result");
            if ($result) {
                developer_log("POSTEXPRESS: marcando los enviados");
                if (!$this->marcar_como_enviado($recordset)) {
                    Model::FailTrans();
                }
            } else {
                Model::FailTrans();
            }
            if (Model::HasFailedTrans()) {
                developer_log("POSTEXPRESS: Hay transacciones fallidas.");
            }
            if (!Model::HasFailedTrans() and Model::CompleteTrans()) {

                developer_log("POSTEXPRESS: Desbloqueando apsi.");
                if (!$this->desbloquear_afuturo($afuturo)) {
                    throw new Exception("No se puede desbloquear el registro pero el envio se realizo con exito.");
                }
            } else {
                developer_log("Completando transaccion fallada 1.");
                Model::CompleteTrans();
                developer_log("Completando transaccion fallada 2.");
                Model::CompleteTrans();
                developer_log("iniciando nueva transaccion.");
                Model::StartTrans();
                if (!$this->desbloquear_afuturo($afuturo)) {
                    throw new Exception("No se puede desbloquear el registro pero el envio se realizo con exito.");
                }
                developer_log("Completando transaccion.");
                Model::CompleteTrans();
                throw new Exception("Error en el envio.");
                
            }
        }
	 if (!$this->desbloquear_afuturo($afuturo)) {
                  throw new Exception("No se puede desbloquear el registro pero el envio se realizo con exito.");
         }

        return true;
    }

    protected function obtener_concepto($id_moves) {
//        var_dump("POSTEXPRESS: obteniendo concepto");
        developer_log("POSTEXPRESS: obteniendo concepto");
        $moves = new Moves();
        $moves->get($id_moves);
        $barcode = new Barcode();
        $barcode->get($moves->get_id_referencia());
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
        $xml_concepto = $concepto_v->getElementsByTagName("xml_concepto");
	if($xml_concepto->length > 0){
		$nodo=$xml_concepto;
        	foreach ($xml_concepto->item(0)->childNodes as $node){
	            $conc->appendChild($concepto_envio->importNode($node,true));
        	}
	}
        return $concepto_envio->saveXML();
    }

    public function traducir(SimpleXMLElement $filtros) {
        $nuevos_filtros = array();

        foreach ($filtros as $filtro) {
            $filtro = json_decode(json_encode($filtro), true);
//            print_r($filtro[0]);
            if (isset($filtro["@attributes"]["move"]))
                switch ($filtro["@attributes"]["move"]) {
//                 A.id_moves, C.mercalpha, B.mp, D.barcode, D.pmc19, A.monto_pagador, A.monto_marchand,
////                            D.fechagen::Date as fechagen, D.fecha_vto::Date as fecha_vto, A.fecha::Date as fecha, 
////                            A.fecha_move::Date as fecha_move, A.fecha_liq::Date as fecha_liq,E.documento
                    case "mp":
                        $nuevos_filtros[$filtro[0]] = "F.mp";
                        break;
                    case "barcode":
                        $nuevos_filtros[$filtro[0]] = "D.barcode";
                        break;
                    case "pmc19":
                        $nuevos_filtros[$filtro[0]] = "D.pmc19";
                        break;
                    case "bc_xml":
                        $nuevos_filtros[$filtro[0]] = "G.boleta_concepto";
                        break;
                    case "monto_cd":
                        $nuevos_filtros[$filtro[0]] = "A.monto_pagador";
                        break;
                    case "monto_marchand":
                        $nuevos_filtros[$filtro[0]] = "A.monto_marchand";
                        break;
                    case "fecha_gen":
                        $nuevos_filtros[$filtro[0]] = "D.fechagen";
                        break;
                    case "fecha_vto":
                        $nuevos_filtros[$filtro[0]] = "D.fecha_vto";
                        break;
                    case "fecha":
                        $nuevos_filtros[$filtro[0]] = "A.fecha";
                        break;
                    case "fecha_move":
                        $nuevos_filtros[$filtro[0]] = "A.fecha_move";
                        break;
                    case "fecha_liq":
                        $nuevos_filtros[$filtro[0]] = "A.fecha_liq";
                        break;
                }
        }
        return $nuevos_filtros;
    }

    public function marcar_como_enviado(ADORecordSet_postgres8 $recordset) {
        developer_log("POSTEXPRESS: marcando ultima transaccion como enviada");
        $recordset->MoveLast();
	$row = $recordset->FetchRow();
	$recordset->MoveFirst();
	$row2=$recordset->FetchRow();
        $moves = new Moves();
        $moves->get($row["id_moves"]);
        $moves->set_is_posted(1);
	$moves2 = new Moves();
        $moves2->get($row2["id_moves"]);
        $moves2->set_is_posted(1);
        developer_log("POSTEXPRESS: movimientos marcados son" . $row["id_moves"]."Y el Movimiento ".$row2["id_moves"] );
        if ($moves2->set() and $moves->set()) {
            developer_log("POSTEXPRESS: seteado correctamene");
            return true;
        }
        return false;
    }

    private function verificar_bloqueo() {
        $rs = Afuturo_run::select_bloqueo($this->marchand->get_id_marchand());
        if (!$rs or $rs->rowCount() == 0) {
            return false;
        }

        $row = $rs->fetchRow();

        return $row["id_afuturo_run"];
    }

    private function bloquear_afuturo(Afuturo $afuturo) {
//        print_r("bloqueado");
        $afuturo_run = new Afuturo_run();
        $afuturo_run->set_id_marchand($afuturo->get_id_marchand());
        $afuturo_run->set_sid($afuturo->get_sid());
        $afuturo_run->set_solicitada("now()");
        if ($afuturo_run->set()) {
            $this->afuturo_run = $afuturo_run->get_id_afuturo_run();
            $this->bloquear_marchand($afuturo->get_id_marchand());
            return true;
        }

        return false;
    }

    public function desbloquear_afuturo(Afuturo $afuturo) {
//        print_r("desbloqueado");
        if (Afuturo_run::eliminar_registro($this->afuturo_run)) {
            $this->desbloquear_marchand($afuturo->get_id_marchand());
            developer_log("POSTEXPRESS: Desbloqueado");
	   if(!Model::HasFailedTrans() and Model::CompleteTrans())
	     return true;
        }
        return false;
    }

    public function bloquear_marchand($id_marchand) {
        $marchand = new Marchand();
        $marchand->set_id($id_marchand);
        $marchand->set_nops("1");
        if ($marchand->set()) {
            return true;
        }
    }

    public function desbloquear_marchand($id_marchand) {
        $marchand = new Marchand();
        $marchand->set_id($id_marchand);
        $marchand->set_nops("0");
        if ($marchand->set()) {
            return true;
        }
    }

    public function obtener_fecha($c,$valor,$formatos) {
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

    private function armar_archivo($recordset, Afuturo $afuturo, $filtros_traduccion) {
        $titulos = array_keys($filtros_traduccion);
        $campos_a_mostrar = array();
        foreach ($filtros_traduccion as $clave => $filtros) {
            $campos_a_mostrar = substr($filtros, 2);
        }
        $filas = array();
        $filas[] = '"INFORME APSI";"PERIORICIDAD";"SOLICITADA";"CORRIDA"';
        $filas[] = '"' . $afuturo->get_sid() . '";"Correr&aacute; diariamente";"' . $afuturo->get_solicitada() . '";"' . (new DateTime("now"))->format("Y-m-d H:i:s") . '"';
        $f = "";
        $f .= '"CDcomercio";';
        foreach ($titulos as $titulo) {
            $f .= '"' . $titulo . '";';
        }
        $filas[] = $f;

        $f = "" . $this->marchand->get_mercalpha();
        ;
        foreach ($recordset as $row) {
            $f = '"";';

            foreach ($row as $campo => $valor) {
                if (isset($filtros_traduccion[$campo]))
                    $f .= '"' . $valor . '";';
            }
            $filas[] = $f;
            unset($f);
        }
        return $filas;
    }

    public function consultar_db($tipo, $filtros_traduccion) {
        if ($tipo == "ingresos") {
            developer_log("POSTEXPRESS: Procesando ingresos");
            $recordset = Moves::select_apsi_ingresos($this->marchand->get_id_marchand(), $filtros_traduccion);
        } elseif ($tipo == "egresos") {
            developer_log("POSTEXPRESS: Procesando egresos");
            $recordset = Moves::select_apsi_egresos($this->marchand->get_id_marchand(), $filtros_traduccion);
        }
        return $recordset;
    }
    public function obtener_clave($row){
        return $row["id_moves"];
    }
}   

