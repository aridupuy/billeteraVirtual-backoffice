<?php

class Gestor_de_instancias {

    const NODO_RAIZ = 'root';
    const ATRIBUTO_CONTROLLER = 'controller';

    # Puede llamarse solo desde los controllers

    public static function inicializar_instancia_actual() {
        developer_log('Inicializando la instancia actual');
        if (!ACTIVAR_INSTANCIAS)
            return true;

        if (!Application::$usuario) {
            return false;
        }

        $id_auth = Application::$usuario->get_id();
        if (!($id_authcode = self::obtener_authcode()))
            return false;
        $instancia = Application::$instancia;

        $recordset = Instancias::select(array('id_instancias' => $instancia, 'id_auth' => $id_auth, 'id_authcode' => $id_authcode));

        if ($recordset AND $recordset->RowCount() == 1) {
            $xml = new DOMDocument();
            $xml->appendChild($xml->createElement(self::NODO_RAIZ));
            $objeto = new Instancias($recordset->FetchRow());
            $objeto->set_xml($xml->saveXML());
            if ($objeto->set()) {
                return true;
            }
        }
        if($recordset)
            developer_log("encontro instancias= " . $recordset->RowCount());
        else
            developer_log("encontro instancias= 0" );
        return false;
    }

    # LLamada desde los main_controller y dese Gestor_de_instancias #EXCLUSIVO

    public static function eliminar_instancias() {
        if (!ACTIVAR_INSTANCIAS)
            return true;

        $id_auth = Application::$usuario->get_id();
        if (!($id_authcode = self::obtener_authcode()))
            return false;
        return Instancias::eliminar_instancias($id_auth, $id_authcode);
    }

    # Llamada desde los main_controller #EXCLUSIVO

    public static function crear_primera_instancia() {
        if (!ACTIVAR_INSTANCIAS)
            return true;
        if (!self::eliminar_instancias())
            return false;
        return self::crear_instancia();
    }

    # Llamada desde la clase Application #EXCLUSIVO

    public static function crear_instancia() {
        if (!ACTIVAR_INSTANCIAS)
            return true;
        $id_auth = Application::$usuario->get_id();
        if (!($id_authcode = self::obtener_authcode()))
            return false;

        if (!($id_authcode = self::obtener_authcode()))
            return false;

        $objeto = new Instancias();
        $objeto->set_id_auth($id_auth);
        $objeto->set_id_authcode($id_authcode);
        $xml = new DOMDocument();
        $xml->appendChild($xml->createElement(self::NODO_RAIZ));
        $objeto->set_xml($xml->saveXML());
        if ($objeto->set()) {
            Application::$instancia = $objeto->get_id();
            if (ACTIVAR_LOG_INSTANCIAS)
                developer_log('Instancia nro: ' . Application::$instancia);
            return $objeto->get_id();
        }
        return false;
    }

    # Llamada desde la clase Application #EXCLUSIVO

    public static function integrar_instancia($variables, $id_instancias, $nombre_controller) {
        if (!ACTIVAR_INSTANCIAS)
            return $variables;
        if (!Application::$usuario) {
            if (ACTIVAR_LOG_INSTANCIAS)
                developer_log('Usuario no logeado.');
            return '';
        }
        $id_auth = Application::$usuario->get_id();
        if (!($id_authcode = self::obtener_authcode()))
            return false;
        $recordset = Instancias::select(array('id_auth' => $id_auth, 'id_authcode' => $id_authcode, 'id_instancias' => $id_instancias));

        if (!$recordset) {
            if (ACTIVAR_LOG_INSTANCIAS)
                developer_log('La consulta de instancias no es correcta.');
            return false;
        }
        if ($recordset->RowCount() == 1) {
            $objeto = new Instancias($recordset->FetchRow());
        } else {
            return false;
        }
        $xml = new DOMDocument();

        $xml->loadXML($objeto->get_xml());

        $root = $xml->getElementsByTagName(self::NODO_RAIZ);
        if ($root->length === 0) {
            if (ACTIVAR_LOG_INSTANCIAS)
                developer_log('No hay nodo <root>.');
            return false;
        }
        $root = $root->item(0);
        unset($variables[NOMBRE_HIDDEN_INSTANCIA]);
        if (($xml = self::comprobar_controller($nombre_controller, $xml)) !== false) {
            if (self::guardar_variables($variables, $xml, $objeto)) {
                if (ACTIVAR_LOG_INSTANCIAS)
                    developer_log('Variables guardadas: ' . json_encode($variables));
                return self::levantar_variables($xml);
            }
            elseif (ACTIVAR_LOG_INSTANCIAS)
                developer_log('Falla al guardar las variables: ' . json_encode($variables));
        }
        if (ACTIVAR_LOG_INSTANCIAS)
            developer_log('Condicion no controlada.');
        return false;
    }

    # Recibe un array y lo anexa a un archivo XML en la base de datos

    private static function guardar_variables($variables, DOMDOcument $xml, Instancias $objeto) {

        $root = $xml->getElementsByTagName(self::NODO_RAIZ);
        $root = $root->item(0);
        foreach ($variables as $clave => $valor) {
            if (!$clave !== null AND $valor !== null) {
                if(is_array($valor))
                    $valor=$valor[0];
                if (mb_detect_encoding($valor, 'UTF-8', true) AND mb_detect_encoding($clave, 'UTF-8', true)) {

                    if (is_array($valor) AND $clave !== null) {
                        if (ACTIVAR_LOG_INSTANCIAS)
                            developer_log('Error: Se intenta guardar un array en el xml.');
                        return false;
                    }

                    $nodos = $xml->getElementsByTagName($clave);

                    if ($nodos->length == 0) {
                        if ($clave !== '') {
                            $elemento = $xml->createElement($clave);

                            if ($valor !== '') {
                                $elemento->appendChild($xml->createTextNode(trim($valor)));
                            }
                            $root->appendChild($elemento);
                        }
                    } elseif ($nodos->length > 0) {
                        $elemento = $nodos->item(0);
                        if ($elemento->childNodes->item(0) !== null)
                            $elemento->removeChild($elemento->childNodes->item(0));
                        $elemento->appendChild($xml->createTextNode(trim($valor)));
                        $root->appendChild($elemento);
                    }
                }
                elseif (ACTIVAR_LOG_INSTANCIAS)
                    developer_log('Error. No se guarda la variable. La codificacion no es correcta.');
            }
        }

        $objeto->set_xml($xml->saveXML());
        return $objeto->set();
    }

    # Levanta un XML de la base de datos y retorna un array

    private static function levantar_variables(DOMDOcument $xml) {
        $root = $xml->getElementsByTagName(self::NODO_RAIZ);
        $root = $root->item(0);
        $array = array();
        foreach ($root->childNodes as $nodo) {
            if ($nodo->nodeValue !== '')
                $array[$nodo->tagName] = $nodo->nodeValue;
        }
        if (ACTIVAR_LOG_INSTANCIAS)
            developer_log('Variables levantadas: ' . json_encode($array));
        return $array;
    }

    private static function obtener_authcode() {
        if (get_class(Application::$usuario) == 'Auth')
            $id_authcode = 1;
        if (!isset($id_authcode))
            return false;
        return $id_authcode;
    }

    private static function comprobar_controller($nombre_controller, $xml) {
        $root = $xml->getElementsByTagName(self::NODO_RAIZ);
        $root = $root->item(0);

        if ($root->hasAttribute(self::ATRIBUTO_CONTROLLER)) {
            $guardado = $root->getAttribute(self::ATRIBUTO_CONTROLLER);
            if (trim(strtolower($guardado)) == trim(strtolower($nombre_controller))) {
                # Tiene el atributo y el valor guardado coincide con el controller actual
                if (ACTIVAR_LOG_INSTANCIAS)
                    developer_log('Sigue navegando en el controller: ' . $nombre_controller . ' .');
                return $xml;
            }
            else {
                # Tiene el atributo pero no coincide con el controller actual
                $nuevo_xml = new DOMDocument();
                if (ACTIVAR_LOG_INSTANCIAS)
                    developer_log('Cambia al controller : ' . $nombre_controller . ' .');
                $nuevo_xml->appendChild($nuevo_xml->createElement(self::NODO_RAIZ));
                $root = $nuevo_xml->getElementsByTagName(self::NODO_RAIZ);
                $root = $root->item(0);
                $root->setAttribute(self::ATRIBUTO_CONTROLLER, $nombre_controller);
                return $nuevo_xml;
            }
        }
        else {
            # No tiene atributo 'controller'
            $root->setAttribute(self::ATRIBUTO_CONTROLLER, $nombre_controller);
            if (ACTIVAR_LOG_INSTANCIAS)
                developer_log('Navega al primer controller.');
            return $xml;
        }
        if (ACTIVAR_LOG_INSTANCIAS)
            developer_log('Falla la comprobacion de instancias.');
        return false;
    }

}
