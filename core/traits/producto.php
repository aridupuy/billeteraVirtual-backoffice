<?php

# Esta clase es analoga al trait pagador

class Producto {

    public $prodmarchand = false; # No optimizar
    public $prodcli_marchand = false; # No optimizar
    public static $xml = false; # Optimizar
    public static $xml_planilla = false; # Optimizar

    const DUMMY_ORDENPROD = '1';
    const DUMMY_ID_XML = 1;

    public function crear($id_marchand, $array,Pagador $pagador=null) {
//        var_dump("creando producto");
        Model::StartTrans();
        if (($codigo_xml = $this->generar_estructura($id_marchand, $array)) === false)
            Model::FailTrans();
        $this->prodmarchand = new Prodmarchand();
        $this->prodmarchand->set_id_marchand($id_marchand);
        $this->prodmarchand->set_id_authstat(Authstat::ACTIVO);
        $this->prodmarchand->set_id_xml(self::DUMMY_ID_XML);
        $this->prodmarchand->set_producto_xml($codigo_xml);
        $this->prodmarchand->set_ordenprod(self::DUMMY_ORDENPROD);
        
        if (!Model::hasFailedTrans()) {
            if (!$this->prodmarchand->set()){
                Model::FailTrans();
                developer_log("Error al asociar el producto");
            }
        }
        if(!Model::hasFailedTrans() and $pagador!=null){
            $this->prodcli_marchand=new Prodcli_marchand();
            $this->prodcli_marchand->set_id_climarchand($pagador->get_climarchand()->get_id_climarchand());
            $this->prodcli_marchand->set_id_confiteria(1);
            $this->prodcli_marchand->set_id_prodmarchand($this->prodmarchand->get_id());
            $this->prodcli_marchand->set_kant(1);
           if (!$this->prodcli_marchand->set()){
                Model::FailTrans();
                developer_log("Error al asociar el pagador al producto");
           }
        }
        if (Model::CompleteTrans() AND ! Model::hasFailedTrans())
            return $this;
        return false;
    }

    # Mismo tipo de array que crear()

    public function editar($id_prodmarchand, $array, $modificacion_parcial = 1) {
        Model::StartTrans();
        # Si es una modificacion parcial solo se actualizan los campos que se reciben.
        # En caso contrario, los datos que no se reciben quedan vacios.
        $this->prodmarchand = new Prodmarchand();
        if (!$this->prodmarchand->get($id_prodmarchand)) {
            Model::FailTrans();
        }
        if (!$modificacion_parcial) {
            if (!Model::hasFailedTrans()) {
                if (($codigo_xml = $this->generar_estructura($this->prodmarchand->get_id_marchand(), $array)) === false)
                    Model::FailTrans();
            }
        }
        else {
            if (!Model::hasFailedTrans()) {
                if (($codigo_xml = $this->editar_estructura($this->prodmarchand, $array)) === false)
                    Model::FailTrans();
            }
        }
        $this->prodmarchand->set_producto_xml($codigo_xml);
        if (!Model::hasFailedTrans()) {
            if (!$this->prodmarchand->set())
                Model::FailTrans();
        }

        if (Model::CompleteTrans())
            return $this;
        return false;
    }

    public function generar_estructura($id_marchand, $array) {
        $xml = new View();
        foreach ($array as $clave => $valor) {
            if (is_array($clave) OR is_array($valor))
                return false;
        }
        if (!$this->optimizar_xml($id_marchand))
            return false;
        if (!$xml->loadXML(self::$xml->get_xmlfield())) {
            Gestor_de_log::set('La estructura de productos no tiene un formato correcto.', 0);
            return false;
        }
        $items_estructura = $xml->getElementsByTagName('item');
        if ($items_estructura->length === 0) {
            Gestor_de_log::set('Hay un error en la estructura de productos.', 0);
            return false;
        }
        $estructura_productos = new View();
        $producto = $estructura_productos->createElement('producto');
        $producto->appendChild($estructura_productos->createElement('gendate', date('Ymd')));
        $items = $estructura_productos->createElement('items');
        $producto->appendChild($items);
        $estructura_productos->appendChild($producto);
        foreach ($items_estructura as $item_estructura) {
            $nombre = $item_estructura->getElementsByTagName('nombre');
            if ($nombre->length === 1) {
                $nombre = $nombre->item(0)->nodeValue;
                $label = $item_estructura->getElementsByTagName('label');
                if ($label->length == 1)
                    $label = $label->item(0)->nodeValue;
                else
                    $label = false;
                if (isset($array[$nombre]))
                    $value = $array[$nombre];
                elseif ($label AND isset($array[$label]))
                    $value = $array[$label];
                else
                    $value = '';
                $item = $estructura_productos->createElement('item');
                $nombre_elemento = $estructura_productos->createElement('nombre', $nombre);
                $value = $estructura_productos->createElement('value', $value);
                $item->appendChild($nombre_elemento);
                $item->appendChild($value);
                $items->appendChild($item);
            }
        }
        if (($codigo_xml = $estructura_productos->saveXML()) === false) {
            Gestor_de_log::set('Ha fallado la escritura de la estructura de productos.', 0);
            return false;
        }
        return $codigo_xml;
    }

    public function editar_estructura(Prodmarchand $prodmarchand, $array) {
        $id_marchand = $prodmarchand->get_id_marchand();
        if (!$this->optimizar_xml($id_marchand))
            return false;
        $valores_anteriores = self::armar_array($prodmarchand->get_producto_xml(), self::$xml->get_xmlfield());

        foreach ($valores_anteriores as $nombre => $label_value) {
            # Piso con los nuevos valores
            if (isset($array[$nombre]))
                $valores_nuevos[$nombre] = $array[$nombre];
            elseif (isset($array[$label_value['label']]))
                $valores_nuevos[$nombre] = $array[$label_value['label']];
            else
                $valores_nuevos[$nombre] = $label_value['value'];
        }

        return $this->generar_estructura($id_marchand, $valores_nuevos);
    }

    public static function armar_array($estructura_prodmarchand, $estructura_xml) {
        $array = array();
        $estructura_prodmarchand_dom = new View();
        if ($estructura_prodmarchand_dom->loadXML($estructura_prodmarchand) === false)
            return false;
        $estructura_xml_dom = new View();
        if ($estructura_xml_dom->loadXML($estructura_xml) === false)
            return false;

        $items = $estructura_xml_dom->getElementsByTagName('item');
        if ($items->length == 0)
            return false;
        foreach ($items as $item) {
            $nombres = $item->getElementsByTagName('nombre');
            if ($nombres->length == 1) {
                $nombre = $nombres->item(0)->nodeValue;
                $labels = $item->getElementsByTagName('label');
                if ($labels->length == 1)
                    $label = $labels->item(0)->nodeValue;
                else
                    $label = '';
                $value = self::buscar_por_nombre($nombre, $estructura_prodmarchand_dom);
                $array[$nombre] = array('value' => $value, 'label' => $label);
            }
        }

        return $array;
    }

    public static function buscar_por_nombre($nombre_item, View $estructura_prodmarchand) {
        $items = $estructura_prodmarchand->getElementsByTagName('item');
        if ($items->length == 0)
            return false;
        foreach ($items as $item) {
            $nombres = $item->getElementsByTagName('nombre');
            $values = $item->getElementsByTagName('value');
            if ($nombres->length == 1 AND $values->length == 1) {
                $nombre = $nombres->item(0)->nodeValue;
                $value = $values->item(0)->nodeValue;
                if (strtolower(trim($nombre)) == strtolower(trim($nombre_item))) {
                    return $value;
                }
            }
        }
        return false;
    }

    private function optimizar_xml($id_marchand) {
        # Funcion que Optimiza el XML
        if (self::$xml === false OR self::$xml->get_id_marchand() !== $id_marchand) {
            $row = Xml::row_productos_id_marchand($id_marchand, Entidad::ESTRUCTURA_PRODUCTOS);
            if (!$row) {
                Gestor_de_log::set('La estructura de productos no existe o no es única. ', 0);
                return false;
            }
            self::$xml = new Xml($row);
        }
        return self::$xml;
    }

    private function optimizar_xml_planilla_productos($id_marchand) {
        if (self::$xml_planilla === false) {
            $row = Xml::row_productos_id_marchand($id_marchand, Entidad::PLANILLA_PRODUCTOS);
            if (!$row) {
                Gestor_de_log::set('La estructura de productos no existe o no es única. ', 0);
                return false;
            }
            self::$xml_planilla = new view();
//            var_dump($row["xmlfield"]);
            self::$xml_planilla->loadXml($row["xmlfield"]);
//            var_dump(self::$xml_planilla);
        }
        return self::$xml_planilla;
    }

    public function asignar_productos(Pagador $pagador) {
//        self::$xml_planilla=new Xml();
        $x = $this->optimizar_xml($pagador->get_climarchand()->get_id_marchand());
        $xml = $this->optimizar_xml_planilla_productos($pagador->get_climarchand()->get_id_marchand());
        $identificador = $pagador->buscar_por_nombre("sap_consorcio", $pagador->get_climarchand()->get_cliente_xml());
        $uf = $pagador->buscar_por_nombre("sap_unidad", $pagador->get_climarchand()->get_cliente_xml());
        $codigo = "$identificador $uf";
        $item = new DOMNode();
        $items = self::$xml_planilla->getElementsByTagName("item");
        $identificadores = array();
        $tablita = self::$xml_planilla->getElementsByTagName("getx");
        $tablita = $tablita->nodeText;
        $error = false;
        $rs_prod = Prodmarchand::select_productos_hard();
        $i = 0;
        foreach ($rs_prod as $prod) {
            $producto = new Prodmarchand($prod);
            $array = $this->armar_array($producto->get_producto_xml(), self::$xml->get_xmlfield());
            $identificadores["sax_cod"] = $codigo;
            $identificadores["sax_ident"] = $array["sax_ident"]["value"];
            $identificadores["sax_celpos"] = $array["sax_celpos"]["value"];
            $identificadores["sax_cellabe"] = $array["sax_cellabe"]["value"];
            $identificadores["sax_detalle"] = $array["sax_detalle"]["value"];
            $identificadores["prekio"] = 0;
            if ($this->crear($pagador->get_climarchand()->get_id_marchand(), $identificadores,$pagador)) {
                developer_log("Producto asociado");
            } else {
                developer_log("error al asociar el producto");
                $error = true;
            }
            $i++;
        }




        if (!$error)
            return true;
        return false;
    }

}
