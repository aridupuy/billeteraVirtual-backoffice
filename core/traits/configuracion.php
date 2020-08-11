<?php

class Configuracion {

    const CONFIG_IMPORTE_MINIMO = "importe minimo";
    const CONFIG_VENCIMIENTOS = "vencimientos";
    const CONFIG_DIAS = "dias";
    const CONFIG_IMPORTES = "importes";
    const CONFIG_PORCENTAJE = "porcentaje";
    const CONFIG_SUBCOMISION= "subcomision";
    const CONFIG_DIAS_LIQ= "dias_liq";
    const CONFIG_PERMISO_WS= 'permisos_ws';
    const CONFIG_COBRADOR= "cobrador";
    const CONFIG_COSTEADOR= "costeador";
    const CONFIG_ADELANTOS="total_adelantos";
    const CONFIG_ADELANTOS_TOTAL_MENSUAL="total_mensual";
    const CONFIG_ADELANTOS_COSTO="costo_adelanto";
    const CONFIG_ADELANTOS_DIA_1="dia_1";
    const CONFIG_ADELANTOS_DIA_2="dia_2";
    const CONFIG_ADELANTOS_DIA_3="dia_3";
    const CONFIG_ADELANTOS_DIA_4="dia_4";
    const CONFIG_ADELANTOS_DIA_5="dia_5";
    const CONFIG_ADELANTOS_DIA_6="dia_6";
    const CONFIG_ADELANTOS_DIA_7="dia_7";
    const CONFIG_ADELANTOS_SEMANA_1="semana_1";
    const CONFIG_ADELANTOS_SEMANA_2="semana_2";
    const CONFIG_ADELANTOS_SEMANA_3="semana_3";
    const CONFIG_ADELANTOS_SEMANA_4="semana_4";
    const CONFIG_ADELANTOS_SEMANA_5="semana_5";
    const CONFIG_ADELANTOS_SEMANA_6="semana_6";
    const CONFIG_COMISIONA_IDM="subcomision para IDM";
    const CONFIG_COMISIONA_FIX="subcomision FIX";
    const CONFIG_COMISIONA_VAR="subcomision VAR";
    const CONFIG_CARRIER_TC = "carrier";
    const TAG_CARRIER_TC = "carrier_tc";
    const CONFIG_FIELD_CANTIDAD_LOTE_COBRADOR= "cantidad_lote_pendiente";
    const CONFIG_COBRADOR_TOLERANCIA= "Tolerancia de rendicion";
    const CONFIG_SUBCOMISION_VAR= 23;
    const CONFIG_SUBCOMISION_FIX= 24;
    const CONFIG_SUBCOMISION_IDM= 25;
    const CONFIG_DIAS_PLUS_RAPIPAGO= 27;
    const CONFIG_PAGO_A_PROVEEDOR= 10137;
    const CONFIG_GLOBAL_PAGO_A_PROVEEDOR= "Pago_Proveedor";
    const CONFIG_DIAS_PLUS_PAGOFACIL= 28;
    const CONFIG_DIAS_PLUS_PAGOFACIL_CAPITAL= 225;
    const CONFIG_DIAS_PLUS_PAGOFACIL_GBA= 226;
    const CONFIG_DIAS_PLUS_PAGOFACIL_INTERIOR= 227;
    const CONFIG_DIAS_PLUS_RETIRO_TRANSFERENCIA= 221;
    const CONFIG_DIAS_PLUS_ADELANTO_TRANSFERENCIA= 222;
    const CONFIG_DIAS_PLUS_PROVINCIA_PAGOS= 29;
    const CONFIG_DIAS_PLUS_COBROEXPRESS= 30;
    const CONFIG_DIAS_PLUS_RIPSA= 31;
    const CONFIG_DIAS_PLUS_MULTIPAGO= 32;
    const CONFIG_DIAS_PLUS_PAGOMISCUENTAS= 33;
    const CONFIG_DIAS_PLUS_BICA= 34;
    const CONFIG_DIAS_PLUS_PRONTOPAGO= 35;
    const CONFIG_DIAS_PLUS_LINKPAGOS= 36;
    const CONFIG_DIAS_PLUS_DEBITO= 37;
    const CONFIG_DIAS_PLUS_TARJETA=109;
    const CONFIG_DIAS_PLUS_TELERECARGAS=231;
    const CONFIG_PERMISO_ALTA_MARCHAND= 69;
    const CONFIG_ENTIDAD_COBRADOR= 10129;
    const CONFIG_CONFIG_COBRADOR= 102;
    const CONFIG_CONFIG_COBRADOR_COMISION_FIJA= 88;
    const CONFIG_CONFIG_COBRADOR_COMISION_VARIABLE= 89;
    const ID_TAG_CARRIER = 230;
    const CONFIG_REFERENCIA_COSTO_EFECTIVO= "104";
    const CONFIG_LIMITE_REGISTROS="10119";
    const CONFIG_PAP_CUENTA_DEFAULT=111; //en produccion
    const CONFIG_TC=12; 
//    const CONFIG_PAP_CUENTA_DEFAULT=118; //en desarrollo
    const LIMITE_REGISTROS="limite_de_registros";
    const LIMITE_REGISTROS_PAGINA="limite_de_registros_pagina";
    const LIMIT="107";
    const LIMIT_PAGINA="108";
//    const CONFIG_REFERENCIA_COSTO_EFECTIVO= "96";
    
//    const CONFIG_CONFIG_COBRADOR= 87;
//    const CONFIG_PADRE_COBRADOR= 10123;
//    const CONFIG_DESCUENTO_PORCENTAJE= 37;
//    const CONFIG_DESCUENTO_PORCENTAJE= 37;
    private static $config;
    private static $entidad;
    private static $referencia;
    private static $id_marchand;

    public function crear_tag($id_entidad, $concepto, $detalle, $tipo, $value = false, Tags $tag_padre = null) {
        if (!empty($concepto) and ! empty($detalle) and ! empty($id_entidad) and ! empty($tipo)) {
            $tag = new Tags();
            $tag->set_concepto($concepto);
            $tag->set_detalle($detalle);
            
            $tag->set_tipo($tipo);
            if ($value)
                $tag->set_value($value);
            if ($tag_padre) {
                developer_log("Tag padre recibido" . $tag_padre->get_id_tag());
                $tag->set_id_padre($tag_padre->get_id_tag());
            } else {
                developer_log("Tag padre no recibido");
                $tag->set_id_padre(0);
                $tag->set_id_entidad($id_entidad);
            }
//            var_dump($tag);
            if ($tag->set())
                return $tag;
        }
        return false;
    }

    public function crear_config(Tags $tag, DateTime $fecha_desde, DateTime $fecha_hasta, $value = false, $field = false, $id_marchand = false) {
        if (!empty($tag) and ! empty($fecha_desde) and ! empty($fecha_hasta)) {
            $config = new Config();
            $config->set_id_tag($tag->get_id_tag());
            $config->set_fecha_desde($fecha_desde->format('Y-m-d'));
            $config->set_fecha_hasta($fecha_hasta->format('Y-m-d'));
            $config->set_habilitado(true);
            if ($value)
                $config->set_value($value);
            if ($field)
                $config->set_field($field);
            if ($id_marchand)
                $config->set_id_marchand($id_marchand);
            if ($config->set())
                return $config;
        }
        return false;
    }

    public static function obtener_configuracion($id_marchand) {
        $config = Tags::select_config($id_marchand);
        return $config;
    }

    public function obtener_tags($variables, $subfijo = null) {
        $tags = Tags::select_tags($variables, $subfijo);
        return $tags;
    }

    public function obtener_tags_padres($variables, $subfijo = null) {
        $tags = Tags::select_tags_padres($variables, $subfijo);
        return $tags;
    }

    public function obtener_tags_hijos($variables, $subfijo = null) {
        var_dump($variables);
        $tags = Tags::select_tags($variables, $subfijo);
        return $tags;
    }

    public function obtener_configuraciones_de_tag($tag) {
        return Config::select_config_tag($tag);
    }

    public static function obtener_configuracion_de_tag($id_marchand, $id_entidad, $id_referencia, $concepto = false) {
        self::config_singleton($id_marchand, $id_entidad, $id_referencia);
        $config = array();
        foreach (self::$config as $row) {
            if ($row['value'] != "")
                $config[$row['id_tag']][$row['concepto']] = $row['value'];
            else
                $config[$row['id_tag']][$row['concepto']] = $row['field'];
            $config["id_config"]=$row["id_config"];
            if ($concepto) {
                if ($concepto == $row['concepto'])
                    return $row['value'];
            }
        }
        if ($concepto)
            return false;
       error_log(json_encode($config));
	 return $config;
    }
    public static function obtener_configuracion_de_tag_multiple($id_marchand, $id_entidad, $id_referencia){
        self::config_singleton($id_marchand, $id_entidad, $id_referencia);
        $config = array();
        foreach (self::$config as $row) {
            if ($row['value'] != "")
                $config[$row['concepto']][] = $row['value'];
            else
                $config[$row['concepto']][] = $row['field'];
            if(!isset($config[$row['concepto']]["id_config"]))
                $config[$row['concepto']]["id_config"]=$row["id_config"];
//            if ($concepto) {
//                if ($concepto == $row['concepto']){
//                    return $row['value'];
//                }
//            }
        }
        return $config;
    }
    private static function config_singleton($id_marchand, $id_entidad, $id_referencia) {
       // if (self::$entidad != $id_entidad and self::$referencia != $id_referencia and self::$id_marchand != $id_marchand) {
            self::$config = Tags::select_configuraciones_de_tag($id_marchand, $id_entidad, $id_referencia);
            self::$entidad = $id_entidad;
            self::$referencia = $id_referencia;
            self::$id_marchand = $id_marchand;
       // }
    }
    public static function obtener_config_tag_concepto($concepto,$id_marchand){
        $tags = Config::select_tag_marchand($concepto, $id_marchand);
        if ($tags and $tags->rowCount() == 1) {
            $row = $tags->fetchRow();
            return $row;
        }
        return false;
    }
    public function existe_tag($concepto, $id_padre = 0) {
        $tags = Tags::select(array("concepto" => $concepto, "id_padre" => $id_padre));
        if ($tags and $tags->rowCount() == 1) {
            $row = $tags->fetchRow();
            return new Tags($row);
        }
        return false;
    }

    public function existe_config($id_tag, $id_marchand, $value = null, $field = null) {

        $tags = Config::select(array("id_tag" => $id_tag, "id_marchand" => Marchand::IDM_DEFAULT, 'value' => $value, 'field' => $field));
        if ($tags and $tags->rowCount() == 1) {
            $row = $tags->fetchRow();
            return new Config($row);
        } else {
            $tags = Config::select(array("id_tag" => $id_tag, "id_marchand" => $id_marchand));
            if ($tags and $tags->rowCount() == 1) {
                $row = $tags->fetchRow();
                return new Config($row);
            }
        }
        return false;
    }

    public function procesar_entrys($row, $hijos, $tag_padre) {
        $configuracion = new Configuracion();
//        developer_log("entry encontrado.");

        $entrys = $hijos->getElementsByTagName("entry");

        foreach ($entrys as $nodes) {
            developer_log("entre");
            if (get_class($nodes) != "DOMText" and $nodes->nodeName == "entry") {
                $attrs = $nodes->attributes;
                foreach ($attrs as $value) {
                    if ($value->name == "idm") {
//                        developer_log("valor idm encontrado.");
                        $idm_comision = $value->value;
                    }
                }
//            if(!isset($idm_comision))
//                $idm_comision=$row['id_marchand'];
//                if(!$nodes->childNodes->length<1){
//                    print_r($nodes);
//                    print_r("<br/>");
//                    print_r("<br/>");
//                    print_r($nodes->childNodes);
//                    print_r("<br/>");
//                    print_r("<br/>");
//                    print_r($row['id_marchand']);
//                    print_r("<br/>");
//                    print_r("<br/>");
//                    print_r("<br/>");
            }
            foreach ($nodes->childNodes as $node) {
                if ($node->tagName == "var") {
                    if (!($tag_hijo_config = $configuracion->existe_tag("subcomision VAR", $tag_padre->get_id()))) {
//                            developer_log("creando tag hijo. VAR");
                        $tag_hijo_config = $configuracion->crear_tag(2, "subcomision VAR", "Autogenerado por Util_lii.compatibilizar", 1, false, $tag_padre);
                    }
                    $value_config = $node->nodeValue;
                } elseif ($node->tagName == "fix") {
                    if (!($tag_hijo_config = $configuracion->existe_tag("subcomision FIX", $tag_padre->get_id()))) {
//                            developer_log("creando tag hijo. FIX");
                        $tag_hijo_config = $configuracion->crear_tag(2, "subcomision FIX", "Autogenerado por Util_lii.compatibilizar", 1, false, $tag_padre);
                    }
                    if ($node->nodeValue != null and $node->nodeValue != "")
                        $value_config = $node->nodeValue;
                    else
                        $value_config = 0;
                } else {
//                        developer_log("no coincide");
                    $concepto_config = false;
                    $value_config = false;
                }
                if (isset($tag_hijo_config)) {
                    if (!$configuracion->existe_config($tag_hijo_config->get_id_tag(), $row['id_marchand'], $value_config)) {
//                            developer_log("no existe config creando.");
                        $hoy = new DateTime("now");
                        $hasta = clone $hoy;
                        $hasta->add(new DateInterval("P1Y"));
                        if (!$configuracion->crear_config($tag_hijo_config, $hoy, $hasta, $value_config, false, $row['id_marchand'])) {
                            Model::FailTrans();
//                                developer_log("fallo al crear el config.");
                            Gestor_de_log::set("Fallo al crear la configuracion para el marchand " . $row['id_marchand']);
                        }
                    }
                }
            }
        }
//            else{
//                print_r(get_class($nodes));
//                print_r("<br/>");
//                print_r($nodes);
//                print_r("<br/>");
//                print_r("<br/>");
//                print_r("<br/>");
//            }

        if (isset($tag_hijo_config)) {
            if (!($tag_hijo_config_extra = $configuracion->existe_tag("subcomision para IDM", $tag_padre->get_id()))) {
                developer_log("creando tag hijo. IDM");
                $tag_hijo_config_extra = $configuracion->crear_tag(2, "subcomision para IDM", "Autogenerado por Util_lii.compatibilizar", 1, false, $tag_padre);
            }
            if (!$configuracion->existe_config($tag_hijo_config_extra->get_id_tag(), $row['id_marchand'], $idm_comision)) {
                developer_log("no existe config creando.");
                $hoy = new DateTime("now");
                $hasta = clone $hoy;
                $hasta->add(new DateInterval("P1Y"));
                if (!$configuracion->crear_config($tag_hijo_config_extra, $hoy, $hasta, $idm_comision, false, $row['id_marchand'])) {
                    Model::FailTrans();
                    developer_log("fallo al crear el config.");
                    Gestor_de_log::set("Fallo al crear la configuracion para el marchand " . $row['id_marchand']);
                }
            }
        }
//        }
    }

//    public function procesar_xml($row, $elementos, Tags $tag_padre) {
//        $configuracion = new Configuracion();
//        Model::StartTrans();
//        if ($tag_padre !== false) {
//            foreach ($elementos->childNodes as $elemento) {
//                $elemento=new DOMElement();
//                if (get_class($elemento) != "DOMText") {
//                    $attr = $elemento->attributes;
//                    foreach ($attr as $val) {
//                        $value = $val->value;
//                        $nombre_tag = $val->name;
//                        $this->singleton_tag_config($nombre_tag, $tag_padre, $row['id_marchand'], $value);
//                    }
//                    if()
//                    else{
//                        $value = $elemento->nodeValue;
//                        $nombre_tag = $elemento->tagName;
//                        $this->singleton_tag_config($nombre_tag, $tag_padre, $row['id_marchand'], $value);
//                    }
//                    
//                }
//            }
//        } else {
//            developer_log("tag padre falso");
//        }
//        if (!Model::HasFailedTrans() and Model::CompleteTrans())
//            Gestor_de_log::set("Configurado correctamente", 1);
//        else {
//            Gestor_de_log::set("Error al configurar ".$tag_padre->get_concepto(), 0);
//        }
//    }
    public $recursion = 0;

    public function procesar_xml($row, $hijos, $tag_padre) {
//        developer_log("Procesando XML");
        $this->recursion++;
        $error = false;
        if (get_class($hijos) !== "DOMText" AND get_class($hijos) != "DOMAttr") {
            if ($hijos->hasChildNodes()) {
//                $attrs= new DOMAttr();
//                $nodo= new DOMNode();
                foreach ($hijos->childNodes as $nodo) {
                    if ($nodo->hasAttributes()) {
                        $attrs = $nodo->attributes;
                        foreach ($attrs as $attr) {
                            $this->procesar_xml($row, $attr, $tag_padre);
                        }
                    }
                    $this->procesar_xml($row, $nodo, $tag_padre);
                }
            }
        } else {
            if (get_class($hijos) == "DOMAttr") {
//                var_dump($hijos);
//                exit();
                $name = $hijos->name;
                $value = $hijos->value;
            } else {
                $value = $hijos->nodeValue;
                $name = $hijos->parentNode->nodeName;
            }
            if ($this->singleton_tag_config($name, $tag_padre, $row['id_marchand'], $value))
                $error = true;
        }
        $this->recursion--;
        developer_log("Recursion $this->recursion");
        return !$error;
    }

    private function singleton_tag_config($nombre_tag, Tags $tag_padre, $id_marchand, $value) {
        $configuracion = new Configuracion();
        if ($value != false AND trim($value) != "" AND $value != null) {
            if ($nombre_tag == "retencion") {
                var_dump($value);
                developer_log($value);
            }
            if (!($tag = $configuracion->existe_tag("$nombre_tag", $tag_padre->get_id()))) {
//                developer_log("creando tag hijo. IDM");
                $tag = $configuracion->crear_tag(2, "$nombre_tag", "Autogenerado por Util_lii.compatibilizar", 1, false, $tag_padre);
            }
            if (!$configuracion->existe_config($tag->get_id_tag(), $id_marchand, $value)) {
//                developer_log("no existe config creando.");
                $hoy = new DateTime("now");
                $hasta = clone $hoy;
                $hasta->add(new DateInterval("P100Y"));
                if (!$configuracion->crear_config($tag, $hoy, $hasta, $value, false, $id_marchand)) {
                    developer_log("fallo al crear el config.");
                    Gestor_de_log::set("Fallo al crear la configuracion para el marchand " . $id_marchand);
                    return false;
                }
            }
        }
        return true;
    }

    public function procesar_costo_adicional($row, $hijos, $tag_padre) {
//            <costo_adicional>
//                <medio_pago>
//                    <id_mp>XXXX</id_mp>
//                    <porcentaje_descuento>n.nn</porcentaje_descuento>
//                    <Fecha_Desde>YYYYMMDD</Fecha_Desde>
//                    <Fecha_Hasta>YYYYMMDD</Fecha_Hasta>
//                </medio_pago> 	
//            </costo_adicional>
        //seguir con es
        $this->recursion++;
        $error = false;
        $node = new DOMNode();
        developer_log("PROCESANDO COSTO_ADICIONAL");
        developer_log($hijos->nodeName);
        foreach ($hijos->childNodes as $hijo) {
            developer_log($hijo->nodeName);
            foreach ($hijo->childNodes as $node) {
                switch ($node->nodeName) {
                    case 'id_mp':
                        $id_mp = $node->nodeValue;
                        break;
                    case 'porcentaje_descuento':
                        $porcentaje = $node->nodeValue;
                        break;
                    case 'Fecha_Desde':
                        $desde = DateTime::createFromFormat("Ymd", $node->nodeValue);
                        break;
                    case 'Fecha_Hasta':
                        $hasta = DateTime::createFromFormat("Ymd", $node->nodeValue);
                        break;
                    default :developer_log($node->nodeName);
                        break;
                }
            }
            $mp = new Mp();
            $mp->get($id_mp);
            $config = $this->singleton_tag_config("Porcentaje Descuento " . $mp->get_mp(), $tag_padre, $row["id_marchand"], $porcentaje);
        }
        if (!$config)
            Model::FailTrans();
        return false;
    }

}
