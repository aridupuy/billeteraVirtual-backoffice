<?php

class Boleta_producto extends Boleta_pagador {

    const ACTIVAR_VERIFICAR_INEXISTENCIA_DE_CODIGO = true;
    const ID_TIPOPAGO_TARJETA_DE_COBRANZA = 900;
    const PERMITIR_CLIMARCHANDS_SIN_TRIX = true; # BORRAR ESTO SI YA ES 2017

    public static $climarchand = false;     #Objeto #OPTIMIZAR
    public static $trix = false;             #Objeto #OPTIMIZAR
    public static $xml = false; # Plantilla HTML #Objeto #OPTIMIZAR
    public static $xml_descartable = false; # Estructura de boletas. #Deprecar #Objeto #OPTIMIZAR
    public static $xml_clientes = false; # Estructura de clientes #OPTIMIZAR
    public static $codebar=false;
    const PORCENTAJE_AUMENTO="porcentaje_aumento";
    const DIAS_VENCIMIENTO="dias_entre_venc";
    const BARCODE_ABIERTO="barcode_abierto";
    const DUMMY_ID_CLIMA = 1;

    private $fecha_precio_producto;

    public function __construct($fecha_precio = null) {
        if ($fecha_precio != null)
            $this->fecha_precio_producto = DateTime::createFromFormat("Y-m-d", $fecha_precio);
        else
            $this->fecha_precio_producto = new DateTime("now");
    }

    public function crear($id_climarchand, $modelo = "init", $fechas_vencimiento, $importes, $concepto, $detalle = null, $id_tipopago = false, $cuotas = false) {
        if (!is_array($fechas_vencimiento)) {
            throw new Exception('Las fechas de vencimiento no son vectores.');
        }
        if (count($fechas_vencimiento) > 4) {
            throw new Exception('No es posible generar Boletas con más de cuatro fechas de vencimiento.');
        }
        for ($aux = 0; $aux < count($fechas_vencimiento); $aux++) {
//            var_dump($fechas_vencimiento[$aux]);
            if (!isset($fechas_vencimiento[$aux])) {
                throw new Exception("El vector de fechas de vencimientos no es correcto.");
            }
        }
        unset($aux);

        Model::StartTrans();
//        var_dump($id_climarchand);
        if (!$this->optimizar_climarchand($id_climarchand)) {
//            var_dump("llegue");
            developer_log("Error al optimizar climarchand");
            Model::FailTrans();
        }
        $id_marchand = Boleta_pagador::$climarchand->get_id_marchand();
        if (!Model::HasFailedTrans()) {
            if (!$this->optimizar_xml($modelo, $id_marchand)) {
                developer_log("Error al optimizar xml");
                Model::FailTrans();
            }
        }
        if (!Model::HasFailedTrans()) {
            if (!$this->optimizar_xml_descartable($id_marchand)) {
                developer_log("Error al optimizar xml descartable");
                Model::FailTrans();
            }
        }

        $productos = $this->obtener_productos();
        
        $importe = $this->obtener_importes($productos);
//	var_dump($importe);
        $fechas_vencimiento[0];
        $fecha_ven= DateTime::createFromFormat("d/m/Y", $fechas_vencimiento[0]);
        $fecha2 = clone $fecha_ven;
        $fecha_abierto = clone $fecha_ven;
        
        $conf_aumento= Configuracion::obtener_config_tag_concepto(self::PORCENTAJE_AUMENTO,Application::$usuario->get_id_marchand());
        $conf_cant_dias= Configuracion::obtener_config_tag_concepto(self::DIAS_VENCIMIENTO,Application::$usuario->get_id_marchand());
        $conf_barcode_abierto= Configuracion::obtener_config_tag_concepto(self::BARCODE_ABIERTO,Application::$usuario->get_id_marchand());
//        var_dump($conf_aumento);
//        var_dump($conf_cant_dias);
//        var_dump($conf_barcode_abierto);
//        exit();
        $dias=$conf_cant_dias["value"];
        $aumento=$conf_aumento["value"];
        $barcode_abierto=$conf_barcode_abierto["value"];
        list($importe,$pricing)= $this->sumar_comision($importe, Boleta_pagador::$climarchand);
	
//        exit();
        $importes[0] = $importe;
        $fechas_vencimiento[0]=$fecha_ven->format("d/m/Y");
        if($dias){
            $fecha2->add(new DateInterval("P".$dias."D"));
            $importes[1] = abs($importe * (1+($aumento/100))); //por ahora solo adecco
            $fechas_vencimiento[1]=$fecha2->format("d/m/Y");
        }
        if($barcode_abierto){
            $fecha_abierto->add(new DateInterval("P10Y"));
            $importes[2] = 0; //monto abierto 
            $fechas_vencimiento[2]=$fecha_abierto->format("d/m/Y");
        }
  //      var_dump($importes);
        $this->bolemarchand = new Bolemarchand();
        $this->bolemarchand->set_id_marchand($id_marchand);
        $this->bolemarchand->set_emitida(date('Y-m-d H:i:s.u'));
        $this->bolemarchand->set_boleta_concepto($concepto);
        if (!Model::HasFailedTrans()) {
            if (count($fechas_vencimiento) === 1 AND $id_tipopago === false) # Un solo vencimiento
                $id_tipopago = '100';
            elseif ($id_tipopago === false)
                $id_tipopago = '501';
            if ($this->barcode_1 === false) {
                if (($this->barcode_1 = $this->generar_codigo_de_barras(Boleta_pagador::$climarchand, $importes[0], $fechas_vencimiento[0], $id_tipopago, $concepto)) === false) {
                    developer_log("Error al generar barcode1");
                    Model::FailTrans();
                }
            } else if (!$cuotas and $this->barcode_1 !== false) {
                if (($this->barcode_1 = $this->generar_codigo_de_barras(Boleta_pagador::$climarchand, $importes[0], $fechas_vencimiento[0], $id_tipopago, $concepto)) === false) {
                    developer_log("Error al generar barcode1 de coutas");
                    Model::FailTrans();
                }
            }
        }
        if (!Model::HasFailedTrans() AND isset($importes[1])) {
            $id_tipopago = '502';
//            error_log("502");
            if ($importes[0] >= $importes[1] AND $importes[1] != 0) {
                throw new Exception("El segundo importe debe ser superior al primero.");
            }
//            var_dump($fechas_vencimiento[1]);
            if (($this->barcode_2 = $this->generar_codigo_de_barras(Boleta_pagador::$climarchand, $importes[1], $fechas_vencimiento[1], $id_tipopago, $concepto)) === false) {
                Model::FailTrans();
            }
        }
        if (!Model::HasFailedTrans() AND isset($importes[2])) {
            $id_tipopago = '503';
            if ($importes[1] >= $importes[2] AND $importes[2] != 0) {

                throw new Exception("El tercer importe debe ser superior al segundo.");
            }
            if (($this->barcode_3 = $this->generar_codigo_de_barras(Boleta_pagador::$climarchand, $importes[2], $fechas_vencimiento[2], $id_tipopago, $concepto)) === false) {
                Model::FailTrans();
            }
        }
        if (!Model::HasFailedTrans() AND isset($importes[3])) {
            $id_tipopago = '504';
            if ($importes[2] >= $importes[3] AND $importes[3] != 0) {
                throw new Exception("El cuarto importe debe ser superior al tercero.");
            }
            if (($this->barcode_4 = $this->generar_codigo_de_barras(Boleta_pagador::$climarchand, $importes[3], $fechas_vencimiento[3], $id_tipopago, $concepto)) === false) {
                Model::FailTrans();
            }
        }
        if (!Model::HasFailedTrans()) {
            $this->bolemarchand->set_boletaid($modelo); # Identifica la plantilla
            $this->bolemarchand->set_id_xml(Boleta_pagador::$xml_descartable->get_id());
            $this->bolemarchand->set_id_authstat(Authstat::BOLETA_PENDIENTE_DE_PAGO);
            $total_de_boletas = Bolemarchand::cantidad_de_boletas($id_marchand);
            $this->bolemarchand->set_nroboleta($total_de_boletas + 1);
            $this->bolemarchand->set_id_climarchand(Boleta_pagador::$climarchand->get_id());
            $this->optimizar_xml($modelo, Application::$usuario->get_id_marchand());
            $plantilla = clone Boleta_pagador::$xml;
            if (!($boleta_html = $this->cruzar_plantilla_y_datos_de_boleta_con_productos(Boleta_pagador::$climarchand, $this->bolemarchand, $plantilla, $this->barcode_1, $this->barcode_2, $this->barcode_3, $this->barcode_4, $detalle, $productos,$pricing))) {
                Model::FailTrans();
            } else {
                $this->bolemarchand->set_boleta_html($boleta_html);
            }
        }

        if (!Model::HasFailedTrans()) {
            if (!$this->bolemarchand->set()) {
                Model::FailTrans();
            }
        }
        if ($this->barcode_1 AND ! Model::HasFailedTrans()) {
            $this->barcode_1->set_id_boletamarchand($this->bolemarchand->get_id());
            if (!$this->barcode_1->set()) {
                error_log("Error barcode 1");
                Model::FailTrans();
            }
        }
        if ($this->barcode_2 AND ! Model::HasFailedTrans()) {
            $this->barcode_2->set_id_boletamarchand($this->bolemarchand->get_id());
            if (!$this->barcode_2->set()) {
                error_log("Error barcode 2");
                Model::FailTrans();
            }
        }
        if ($this->barcode_3 AND ! Model::HasFailedTrans()) {
            $this->barcode_3->set_id_boletamarchand($this->bolemarchand->get_id());
            if (!$this->barcode_3->set()) {
                error_log("Error barcode 3");
                Model::FailTrans();
            }
        }
        if ($this->barcode_4 AND ! Model::HasFailedTrans()) {
            $this->barcode_4->set_id_boletamarchand($this->bolemarchand->get_id());
            if (!$this->barcode_4->set()) {
                error_log("Error barcode 4");
                Model::FailTrans();
            }
        }
        if (Model::CompleteTrans() AND ! Model::HasFailedTrans()) {
            return $this;
        }
        if (self::ACTIVAR_DEBUG)
            developer_log('Ha ocurrido un error. No se generó la boleta.');
        return false;
    }
    protected function sumar_comision($importe, Climarchand $climarchand){
        $consorcio=Pagador::buscar_por_nombre("sap_consorcio", $climarchand->get_cliente_xml());
        $rs= Pricing::obtener_pricing_consorcio($consorcio, "sap_consorcio",$climarchand->get_id_marchand());
//	var_dump("$consorcio");
        if($rs->rowCount()>0){
            $row=$rs->fetchRow();
            $pricing=new Pricing($row);
            if($pricing->get_pri_minimo()!=0 and $importe+$pricing->get_pri_fijo()*(1+($pricing->get_pri_variable()/100))<$pricing->get_pri_minimo()){
                return array($pricing->get_pri_minimo(),$pricing);
            }
                
            elseif($pricing->get_pri_maximo()!=0 and $importe+$pricing->get_pri_fijo()*(1+($pricing->get_pri_variable()/100))>$pricing->get_pri_maximo())
            {
                return array($pricing->get_pri_maximo(),$pricing);
            }
            $importe=($importe+$pricing->get_pri_fijo())*(1+($pricing->get_pri_variable()/100));
            
            return array($importe,$pricing);
        }
        return array($importe,new Pricing());
        
    }
    public function generar_codigo_de_barras(Climarchand $climarchand, $importe, $fecha_vencimiento, $id_tipopago,$concepto)
    {
        try{
        $result=parent::generar_codigo_de_barras($climarchand, $importe, $fecha_vencimiento, $id_tipopago, $concepto);
        
	} catch (Exception $e){
	    //var_dump($climarchand);
            developer_log("intentando reutilizar el barcode: ".self::$codebar);
            $result= $this->obtener_barcode(self::$codebar);
	   self::$codebar=0;
        }
        if($result==false){	
	    var_dump(self::$codebar." retorno false");
          //  throw new Exception($e->getMessage());
        }
	//var_dump($result);
        return $result;
    }
    protected function obtener_barcode($codebar){
        $rs=Barcode::select(array("barcode"=>$codebar));
        if($rs->rowCount()==0)
            return false;
        return new Barcode($rs->fetchRow());
        
    }

    protected static function cruzar_plantilla_y_datos_de_boleta_con_productos(Climarchand $climarchand, Bolemarchand $bolemarchand, Xml $plantilla, Barcode $barcode_1, $barcode_2 = null, $barcode_3 = null, $barcode_4 = null, $detalle = null, $productos,Pricing $pricing) {
        if (!(Boleta_pagador::$xml_clientes = Boleta_pagador::optimizar_xml_clientes($bolemarchand->get_id_marchand()))) {
            if (self::ACTIVAR_DEBUG)
                developer_log('Ha ocurrido un error. No se pudo obtener la estructura de clientes.');
            return false;
        }

        $nombre_cliente = Pagador::buscar_por_nombre('sap_apellido', $climarchand->get_cliente_xml());
        $id_cliente = Pagador::buscar_por_nombre('sap_identificador', $climarchand->get_cliente_xml());

        $reemplazos_genericos = Boleta_pagador::cargar_reemplazos($bolemarchand, $barcode_1, $barcode_2, $barcode_3, $barcode_4, $detalle);
        $reemplazos = array();
        $reemplazos[self::ID_CLIENTE] = $id_cliente;
        $reemplazos[self::NOMBRE_CLIENTE] = $nombre_cliente;

        $reemplazos = array_merge($reemplazos_genericos, $reemplazos);

        $cantidad_barcodes = 1;
        if ($barcode_2 != null) {
            $cantidad_barcodes++;
            if ($barcode_3 != null) {
                $cantidad_barcodes++;
                if ($barcode_4 != null) {
                    $cantidad_barcodes++;
                }
            }
        }
        $codigos_ajustados = Boleta_pagador::ajustar_cantidad_de_codigos_de_barra_en_plantilla($plantilla->get_xmlfield(), $cantidad_barcodes);
        if ($codigos_ajustados === false) {
            return false;
        }
        $plantilla->set_xmlfield($codigos_ajustados);

        $paquetes_reemplazados = self::reemplazar_paquetes($plantilla->get_xmlfield());
        $plantilla->set_xmlfield($paquetes_reemplazados);
        $productos_cargados = self::cargar_productos($plantilla->get_xmlfield(), $productos,$pricing);
        $plantilla->set_xmlfield($productos_cargados);
        $datos_cliente_reemplazados = self::reemplazar_datos_cliente($climarchand, Boleta_pagador::$xml_clientes, $plantilla);
        $plantilla->set_xmlfield($datos_cliente_reemplazados);
        
        return Boleta_pagador::reemplazar($plantilla->get_xmlfield(), $reemplazos);
    }

    private static function cargar_productos($xml, $productos,Pricing $pricing) {
        $total = 0;
        $nombre_prod = new View();
        $importe_prod = new View();
        $importe_nom_prod=new View();
        $tr_imp = $importe_prod->createElement("tr");
        $tr_imp->setAttribute("style", "font-size: 11px;");
        $tr_nom = $nombre_prod->createElement("tr");
        $tr_nom->setAttribute("style", "font-size: 11px;");
        $tr_nom_prec = $importe_nom_prod->createElement("tr");
        $tr_nom_prec->setAttribute("style", "font-size: 11px;");
//        var_dump($productos);
        foreach ($productos as $producto) {
            list($codigo, $ident, $nombre) = self::obtener_info($producto["producto_xml"]);
//            var_dump($codigo, $ident, $nombre);
            if($ident ){
                $tr_nom_prec->appendChild($importe_nom_prod->createElement("td",$ident));
            }
            if($codigo ){
             $tr_nom_prec->appendChild($importe_nom_prod->createElement("td",$codigo));   
            }
            $tr_nom->appendChild($nombre_prod->createElement("td",$nombre));
            $tr_nom_prec->appendChild($importe_nom_prod->createElement("td",$nombre));
            $tr_nom->setAttribute("class", "titulo_prod");
            $tr_nom_prec->setAttribute("class", "titulo_prod");
            $nombre_prod->appendChild($tr_nom);
            $importe_nom_prod->appendChild($tr_nom_prec);
            $tr_imp->appendChild($importe_prod->createElement("td", $producto["prekio"]));
            $tr_nom_prec->appendChild($importe_nom_prod->createElement("td", "1"));
            $tr_nom_prec->appendChild($importe_nom_prod->createElement("td", $producto["prekio"]));
            $importe_prod->appendChild($tr_imp);
            $total += $producto["prekio"];
//            $tr_nom_prec->appendChild($tr_nom_prec);
            $tr_nom_prec->appendChild($importe_nom_prod->createElement("td", $total));
        }
        
        $tr_nom->appendChild($nombre_prod->createElement("td", ""));
//        $td_imp=$importe_prod->createElement("tr");
        $b = $importe_prod->createElement("b", $total);
        $td= $importe_prod->createElement("td");
        $td->setAttribute("class", "total");
        $td->appendChild($b);
        $tr_imp->appendChild($td);
        $importe_prod->appendChild($tr_imp);
        
        $xml = preg_replace('/{{NOMBRES_PRODUCTO}}/', $nombre_prod->saveHTML(), $xml, 1);
        $xml = preg_replace('/{{NOMBRES_PRODUCTO_PRECIO}}/', $importe_nom_prod->saveHTML(), $xml, 1);
        $xml = preg_replace('/{{IMPORTES_PRODUCTO}}/', $importe_prod->saveHTML(), $xml, 1);

        $xml = preg_replace('/{{PORCENTAJE_AUMENTO}}/', $pricing->get_pri_variable(), $xml, 1);
//        print_r($xml);
//        exit();
        return $xml;
    }

    private static function obtener_info($xml) {
        $prod = new View();
        $prod->loadHTML($xml, LIBXML_NOERROR);
        $items = $prod->getElementsByTagName("item");
        foreach ($items as $item) {
//            print_r($item->getElementsByTagName("nombre")->item(0));
            if ($item->getElementsByTagName("nombre")->item(0)->nodeValue == "sax_cod") {
                $codigo = $item->getElementsByTagName("value")->item(0)->nodeValue;
            }
            if ($item->getElementsByTagName("nombre")->item(0)->nodeValue == "sax_ident") {
                $ident = $item->getElementsByTagName("value")->item(0)->nodeValue;
            }
//            $nombre = "";
            
            if ($item->getElementsByTagName("nombre")->item(0)->nodeValue == "sax_detalle") {
                if(!in_array(trim(strtolower($item->getElementsByTagName("value")->item(0)->nodeValue)),array("expensas","saldo anterior","intereses"))){
                    $nombre = $item->getElementsByTagName("value")->item(0)->nodeValue;
                }   
                else
                    $nombre ="";
            }
        }
        
        return array($codigo, $ident, $nombre);
    }

    protected function obtener_importes($productos) {
        $total=0;
        foreach ($productos as $producto){
            $total+=$producto["prekio"];
        }
        return $total;
    }

    public function obtener_productos() {
        $recordset = Prodmarchand::select_productos_climarchand(Boleta_pagador::$climarchand, $this->fecha_precio_producto);
        if ($recordset and $recordset->rowCount() > 0) {
            $productos = array();
            foreach ($recordset as $row) {
//                var_dump($row["id_prodmarchand"]);
                $rs = Prodpre_marchand::select_precio_producto($row["id_prodmarchand"], $this->fecha_precio_producto);
                if($rs->rowCount()==0){
                    throw new Exception("Revise la fecha de los importes.");
                }
                foreach ($rs as $r) {
                    $row["prekio"] = $r["prekio"];
//                    var_dump($r["prekio"]);
                }
                $productos[] = $row;
            }
            return $productos;
        }
    }

}

