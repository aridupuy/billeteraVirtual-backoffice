<?php

class Pagador {

    public static $climarchand = false;    #Objeto #OPTIMIZADO! #NO_OPTIMIZAR # Conviene optimizar, ver mod_xiv->actualizar_climarchand()
    public $trix = false;           #Objeto #NO_OPTIMIZAR
    public static $trixgroup = false;      #Objeto #OPTIMIZAR
    public static $xml = false;            #Objeto #OPTIMIZAR # Estructura de clientes

    const DUMMY_ID_CLIMA = 1;

    # El array es de la forma $array[{nombre_item}]={value_item}
    # o bien $array[{label_item}]={value_item}

    public function crear($id_marchand, $array, $permitir_pagador_vacio = false, $account_id = false) {
        Model::StartTrans();
        $para_editar = false;

        if (($codigo_xml = $this->generar_estructura($id_marchand, $array, $permitir_pagador_vacio, $para_editar)) === false) {
            Model::FailTrans();
            developer_log("Error al generar la estructura");
        }
        self::$climarchand = new Climarchand();
        self::$climarchand->set_id_marchand($id_marchand);
        if (isset($account_id) and $account_id != false)
            self::$climarchand->set_accountid($account_id);
        else
            self::$climarchand->set_accountid($this->generar_accountid($id_marchand));
        self::$climarchand->set_id_authstat(Authstat::ACTIVO);
        self::$climarchand->set_id_xml(self::$xml->get_id());
        self::$climarchand->set_cliente_xml($codigo_xml);
        self::$climarchand->set_id_clima(self::DUMMY_ID_CLIMA);
        if (!Model::hasFailedTrans()) {
            if (!self::$climarchand->set())
                Model::FailTrans();
        }

        if (!Model::hasFailedTrans()) {
            if (!$this->generar_trix(self::$climarchand)) {
                Model::FailTrans();
            }
        }

        if (Model::CompleteTrans() AND!Model::hasFailedTrans())
            return $this;
        return false;
    }

    # Mismo tipo de array que crear()

    public function editar($id_climarchand, $array, $modificacion_parcial = true, $permitir_pagador_vacio = false) {
        Model::StartTrans();
        # Si es una modificacion parcial solo se actualizan los campos que se reciben.
        # En caso contrario, los datos que no se reciben quedan vacios.
        if (!$this->optimizar_climarchand($id_climarchand)) {
            developer_log("Error al optmizar el climarchand");
            Model::FailTrans();
        }
        if (!$modificacion_parcial) {
            if (!Model::hasFailedTrans()) {
                $para_editar = true;
                if (($codigo_xml = $this->generar_estructura(self::$climarchand->get_id_marchand(), $array, $permitir_pagador_vacio, $para_editar)) === false) {
                    Model::FailTrans();
                    //var_dump($codigo_xml);
                    developer_log("Error al generar estructura");
                }
                self::$climarchand->set_cliente_xml($codigo_xml);
            }
        } else {
            if (!Model::hasFailedTrans()) {
                if (($codigo_xml = $this->editar_estructura(self::$climarchand, $array, $permitir_pagador_vacio)) === false) {
                    Model::FailTrans();
                    developer_log("Error al editar estructura");
                }
                self::$climarchand->set_cliente_xml($codigo_xml);
            }
        }
        if (!Model::hasFailedTrans()) {
            if (!self::$climarchand->set()) {
                developer_log("Error al editar registro climarchand");
                Model::FailTrans();
            }
        }

        if (Model::CompleteTrans() AND!Model::hasFailedTrans())
            return $this;
        return false;
    }

    private function generar_estructura($id_marchand, $array, $permitir_pagador_vacio = false, $para_editar = false) {
        $xml = new View();
        $nuevo_array = array();

        //Correcion para permitir editar pagador.
        if (array_key_exists('dataTable_length', $array)) {
            unset($array['dataTable_length']);
        }

        foreach ($array as $clave => $valor) {
            if (is_array($clave) OR is_array($valor))
                return false;
            $valor = str_replace('&', 'y', $valor); # Mmmm
            $nuevo_array[trim(strtolower($clave))] = $valor;
            unset($array[$clave]);
        }
        $array = $nuevo_array;
        unset($nuevo_array);
        if (!$this->optimizar_xml($id_marchand))
            return false;
        if (!$xml->loadXML(self::$xml->get_xmlfield())) {
            developer_log('La estructura de clientes no tiene un formato correcto.');
            return false;
        }
        $items_estructura = $xml->getElementsByTagName('item');
        if ($items_estructura->length === 0) {
            developer_log('Hay un error en la estructura de clientes.');
            return false;
        }
        $estructura_clientes = new View();
        $pagador = $estructura_clientes->createElement('pagador');
        $pagador->appendChild($estructura_clientes->createElement('gendate', date('Ymd')));
        $items = $estructura_clientes->createElement('items');
        $pagador->appendChild($items);
        $estructura_clientes->appendChild($pagador);
        $creados = 0;
        $vacios = 0;
        $no_enviados = 0;
        foreach ($items_estructura as $item_estructura) {
            $nombre = $item_estructura->getElementsByTagName('nombre');
            if ($nombre->length === 1) {
                $nombre = $nombre->item(0)->nodeValue;
                $nombre = trim(strtolower($nombre));
                $label = $item_estructura->getElementsByTagName('label');
                if ($label->length == 1) {
                    $label = $label->item(0)->nodeValue;
                    $label = trim(strtolower($label));
                } else
                    $label = false;
                if ($nombre AND isset($array[trim(strtolower($nombre))])) {
                    $value = $array[trim(strtolower($nombre))];
                } elseif ($label AND isset($array[trim(strtolower($label))])) {
                    $value = $array[trim(strtolower($label))];
                } else {
                    unset($value);
                }
                if (isset($value) AND $value) {
                    $creados++;
                } elseif (isset($value)) {
                    $vacios++;
                } else {
                    $no_enviados++;
                    $value = '';
                }
                //error_log($value);
                $item = $estructura_clientes->createElement('item');
                $nombre_elemento = $estructura_clientes->createElement('nombre', $nombre);
                $value = $estructura_clientes->createElement('value', $value);
                $item->appendChild($nombre_elemento);
                $item->appendChild($value);
                $items->appendChild($item);
            }
        }
        if (($codigo_xml = $estructura_clientes->saveXML()) === false) {
            developer_log('Ha fallado la escritura de la estructura de clientes.');
            return false;
        }
        error_log(json_encode($array));
        if ($creados + $vacios != count($array)) {
            error_log('Se enviaron campos para crear el Pagador que no pertenecen a la estructura de clientes.');
            throw new Exception('Se enviaron campos para crear el Pagador que no pertenecen a la estructura de clientes.');
        }
        if (!$permitir_pagador_vacio AND $creados === 0) {
            if ($para_editar)
                $string = 'editar';
            else
                $string = 'crear';
            throw new Exception('No es posible ' . $string . ' un Pagador vacío.');
        }
        return $codigo_xml;
    }

    public function editar_estructura(Climarchand $climarchand, $array, $permitir_pagador_vacio) {
        $id_marchand = $climarchand->get_id_marchand();
        if (!$this->optimizar_xml($id_marchand)) {
            developer_log("Error al optimizar xml");
            return false;
        }
        $valores_anteriores = self::armar_array($climarchand->get_cliente_xml(), self::$xml->get_xmlfield());
        $array_temp = array();
        foreach ($array as $key => $value) {
            $array_temp[trim(strtolower($key))] = $value;
        }
        unset($array);
        $array = $array_temp;
        unset($array_temp);
        $editados = 0;
        $vacios = 0;
        $valores_nuevos = array();
        foreach ($valores_anteriores as $nombre => $label_value) {
            # Piso con los nuevos valores
            if (isset($array[trim(strtolower($nombre))])) {
                if ($array[trim(strtolower($nombre))]) {
                    $valores_nuevos[trim(strtolower($nombre))] = $array[trim(strtolower($nombre))];
                    $editados++;
                } else {
                    $vacios++;
                }
            } elseif (isset($array[trim(strtolower($label_value['label']))])) {
                if ($array[trim(strtolower($label_value['label']))]) {
                    $valores_nuevos[trim(strtolower($nombre))] = $array[trim(strtolower($label_value['label']))];
                    $editados++;
                } else {
                    $vacios++;
                }
            } else
                $valores_nuevos[trim(strtolower($nombre))] = $label_value['value'];
        }
//	error_log($nombre);	
        error_log(json_encode($valores_nuevos));
        error_log($vacios);
        error_log($editados);
        error_log(count($array));
        //       if($editados+$vacios!=count($array)-1){
        //             throw new Exception('Se enviaron campos para editar el Pagador que no pertenecen a la estructura de clientes.');
        //   }
        if (!$permitir_pagador_vacio AND $vacios == count($valores_anteriores)) {
            throw new Exception('No es posible editar un Pagador vacio.');
        }
        return $this->generar_estructura($id_marchand, $valores_nuevos, $permitir_pagador_vacio);
    }

    public function generar_accountid($id_marchand) {
        $temp = Climarchand::select_max_account_id($id_marchand);
        $maximo_accountid = $temp->FetchRow();
        $maximo_accountid = $maximo_accountid['accountid'];
        return $maximo_accountid + 1;
    }

    public function generar_trix(Climarchand $climarchand) {
        $this->trix = new Trix();
        $this->trix->set_concepto('');
        $this->trix->set_id_marchand($climarchand->get_id_marchand());

        if (!$this->optimizar_trixgroup($climarchand))
            return false;

        $this->trix->set_id_trixgroup(self::$trixgroup->get_id_trixgroup());
        $this->trix->set_accountid($climarchand->get_accountid());
        $this->trix->set_id_climarchand($climarchand->get_id_climarchand());
        return $this->trix->set();
    }

    public static function obtener_trix(Climarchand $climarchand) {
        # Esta bien escojer el ultimo Trix?? Todo indica que el ultimo es el que se esta usando.
        $trixs = Trix::select_max_id_climarchand($climarchand->get_id());
        if (!$trixs OR $trixs->RowCount() != 1) {
            developer_log('Ha ocurrido un error al identificar el cliente/trix.');
            return false;
        }
        $trix = new Trix($trixs->FetchRow());
        return $trix;
    }

    public static function get_climarchand() {
        return self::$climarchand;
    }

    public function get_trix() {
        return $this->trix;
    }

    public static function get_trixgroup() {
        return self::$trixgroup;
    }

    public static function get_xml() {
        return self::$xml;
    }

    public static function set_climarchand($climarchand) {
        self::$climarchand = $climarchand;
    }

    public function set_trix($trix) {
        $this->trix = $trix;
    }

    public static function set_trixgroup($trixgroup) {
        self::$trixgroup = $trixgroup;
    }

    public static function set_xml($xml) {
        self::$xml = $xml;
    }

    public static function armar_array($estructura_climarchand, $estructura_xml) {
        $array = array();
        if ($estructura_climarchand == '')
            return $array;
        $estructura_climarchand_dom = new View();
        if (!@$estructura_climarchand_dom->loadXML($estructura_climarchand))
            return false;
        $estructura_xml_dom = new View();
        if (!$estructura_xml_dom->loadXML($estructura_xml))
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
                $value = self::buscar_por_nombre($nombre, $estructura_climarchand_dom);
                $array[$nombre] = array('value' => $value, 'label' => $label);
            }
        }
//	$array=sort($array,3);
        return $array;
    }

    public static function buscar_por_nombre($nombre_item, $estructura_climarchand) {
        if (!is_object($estructura_climarchand)) {
            $temp = new View();
            if (!$temp->loadXML($estructura_climarchand))
                return false;
            $estructura_climarchand = $temp;
            unset($temp);
        } elseif (get_class($estructura_climarchand) !== 'View')
            return false;
        $items = $estructura_climarchand->getElementsByTagName('item');
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
        if ((self::$xml === false OR self::$xml->get_id_marchand() !== $id_marchand) OR (self::$xml->get_id_entidad() == Entidad::ESTRUCTURA_CLIENTES)) {
            $row = Xml::row_clientes_id_marchand($id_marchand);
            if (!$row) {
                developer_log('La estructura de clientes no existe o no es única. ');
                return false;
            }
            self::$xml = new Xml($row);
        }
        return self::$xml;
    }

    # Usado en boleta_pagador por los --trix-de-excepcion--

    public static function optimizar_trixgroup(Climarchand $climarchand) {
        if (self::$trixgroup === false OR self::$trixgroup->get_id_marchand() !== $climarchand->get_id_marchand()) {
            if (($recordset = Trixgroup::select_trixgroup_activo($climarchand->get_id_marchand())) === false)
                return false;
            if (!$recordset OR $recordset->RowCount() !== 1)
                return false;
            $row = $recordset->FetchRow();
            self::$trixgroup = new Trixgroup($row);
        }
        return self::$trixgroup;
    }

    public static function obtener_nombres_y_labels($estructura_xml) {
        $array = array();
        if (is_string($estructura_xml)) {
            $estructura_xml_dom = new View();
            if ($estructura_xml_dom->loadXML($estructura_xml) === false)
                return false;
        } elseif (is_object($estructura_xml) OR get_class($estructura_xml) == 'View') {
            $estructura_xml_dom = $estructura_xml;
        } else
            return false;
        unset($estructura_xml);
        $items = $estructura_xml_dom->getElementsByTagName('item');
        foreach ($items as $item) {
            $nombre = $item->getElementsByTagName('nombre');
            $label = $item->getElementsByTagName('label');
            if ($nombre->length == 1 AND $label->length == 1) {
                $array[] = array('nombre' => $nombre->item(0)->nodeValue,
                    'label' => $label->item(0)->nodeValue);
            }
        }
        return $array;
    }

    private function optimizar_climarchand($id_climarchand) {
        if (!self::$climarchand OR self::$climarchand->get_id_climarchand() !== $id_climarchand) {
            self::$climarchand = new Climarchand();
            return self::$climarchand->get($id_climarchand);
        }
        return self::$climarchand;
    }

    public function obtener_nombre_desde_label($id_marchand, $label) {
        # OPTIMIZAR ESTO!
        $estructura = Xml::estructura($id_marchand, Entidad::ESTRUCTURA_CLIENTES);
        $array = $this->obtener_nombres_y_labels($estructura);
        foreach ($array as $campo) {
            if (trim(strtolower($campo['label'])) == trim(strtolower($label)))
                return $campo['nombre'];
        }
        return false;
    }

    public function obtener_pagador_por_identificador($identificador, $id_marchand) {

        $saps = array();
        $saps[] = 'sap_identificador';
        $variables = array();
        $variables['sap_identificador'] = $identificador;
        $variables['id_authstat'] = Authstat::ACTIVO;
        $recordset = Climarchand::select_clientes($id_marchand, $saps, $variables, true);
        if (!$recordset OR $recordset->RowCount() != 1) {
            if ($recordset AND $recordset->RowCount() > 1) {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('El identificador coincide con ' . $recordset->RowCount() . ' Climarchands.');
                }
            }
            return false;
        }
        if ($recordset->RowCount() === 0) {
            if (self::ACTIVAR_DEBUG) {
                developer_log('No hay un solo climarchand que coincida.');
            }
            return false;
        }
        $climarchand = new Climarchand($recordset->FetchRow());
        return $climarchand;
    }

}
