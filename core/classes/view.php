<?php

// namespace Classes;
class View extends DOMDocument {

    const OCULTAR_ERRORES = true;

    public function __construct() {

        return parent::__construct('1.0', 'utf-8');
    }

    public function cargar($view) {
        $nombre_mod = basename($view, ".html");
        $nombre_mod = explode('.', $nombre_mod);
        $nombre_mod = $nombre_mod[0];

        try {
            $temp = '';
            if (self::OCULTAR_ERRORES) {
                libxml_use_internal_errors(true);
            }
            if ($GLOBALS['SISTEMA'] == 'INTERNO')
                $directorio = PATH_INTERNO;
            elseif ($GLOBALS['SISTEMA'] == 'EXTERNO' OR $GLOBALS['SISTEMA'] == 'INTERFAZ')
                $directorio = PATH_EXTERNO;
            else
                return false;
            if (!$this->loadHTMLFile($directorio . $view))
                throw new Exception("Archivo de vista incorrecto: '" . $directorio . $view . "' .", 1);

            if ($this->getElementsByTagName('form')->length) {
                $forms = $this->getElementsByTagName('form');
                $form = $forms->item(0);
                $form->setAttribute('id', 'miFormulario');
                $form->setAttribute('method', 'post');
            }
            $this->cargar_manual($nombre_mod);
            if (self::OCULTAR_ERRORES) {
                libxml_clear_errors();
            }
        } catch (Exception $e) {
            # Hacer algo!
            #developer_log($e->getMessage());
            return false;
        }

        return $this;
    }

    public function saveHTML() {
        if ($this->getElementsByTagName('form')->length) {
            $forms = $this->getElementsByTagName('form');
            $form = $forms->item(0);
            if (isset(Application::$instancia) AND Application::$instancia !== false) {
                $input = $this->createElement('input');
                $input->setAttribute('type', 'hidden');
                $input->setAttribute('name', NOMBRE_HIDDEN_INSTANCIA);
                $input->setAttribute('value', Application::$instancia);
                $form->appendChild($input);
            }
        }
        return parent::saveHTML();
    }

    public function cargar_manual($nombre_mod) {
        if ($GLOBALS['SISTEMA'] == 'INTERNO')
            $directorio = PATH_INTERNO;
        else if ($GLOBALS['SISTEMA'] == 'EXTERNO' OR $GLOBALS['SISTEMA'] == 'INTERFAZ')
            $directorio = PATH_EXTERNO;

        $manual = new View();
        if ($manual->loadHTMLFile($directorio . 'views/' . $nombre_mod . '.manual.html')) {
            $div_manual = $this->createElement('div');
            $div_manual->setAttribute('class', 'manual');
            $div_manual->setAttribute('id', 'manual');
            $boton = $this->createElement('i');
            $boton->setAttribute('class', 'fa fa-lg fa-close');
            $boton->setAttribute('title', 'Cerrar manual de ayuda.');
            $boton->setAttribute('style', 'float:right;');
            $boton->setAttribute('onclick', "javascript: jQuery('#manual').toggle();");
            $div_manual->appendChild($boton);
            unset($boton);
            $header = $this->getElementsByTagName('header');
            if ($header->length == 1) {
                $boton = $this->createElement('i', 'Ayuda');
                $boton->setAttribute('style', ' float: right;
                                                cursor: pointer;
                                                line-height: 4px;
                                                padding: 0 2px 0px 20px;
                                                color: black;
                                                font-size: 16px;
                                                font-family: sans-serif;
                                                font-weight: 700;
                                                margin-top: -2%;
                                                margin-right: 1%;');
                $boton->setAttribute('onclick', "javascript: jQuery('#manual').toggle();");
                $boton->setAttribute('title', 'Abrir manual de ayuda.');
                $header = $header->item(0);
                $header->appendChild($boton);
            }
            $form = $this->getElementsByTagName('form');
            if ($form->length != 1)
                return false;
            $form = $form->item(0);
            $form->appendChild($div_manual);
            $div_manual->appendChild($this->importNode($manual->documentElement, true));
            return true;
        }
        return false;
    }

    public function cargar_variables($variables) {
        if(!is_array($variables))
            return false;
        else if (count($variables) === 0)
            return false;
        foreach ($variables as $clave => $valor) {
            $elementos = $this->getElementsByName($clave);
            if ($elementos->length > 0) {
                $elemento = $elementos->item(0);
                if ($elemento->tagName == 'input') {
                    switch ($elemento->getAttribute('type')) {
                        case 'checkbox':
                            $elemento->setAttribute('value', $valor);
                            if ($valor)
                                $elemento->setAttribute('checked', 'checked');
                            break;
                        case 'text':
                        case 'number':
                        default:
                            $elemento->setAttribute('value', $valor);
                            break;
                    }
                }
                elseif ($elemento->tagName == 'select') {
                    $options = $elemento->getElementsByTagName('option');
                    foreach ($options as $option) {
                        if ($option->hasAttribute('value') AND $option->getAttribute('value') === $valor)
                            $option->setAttribute('selected', 'selected');
                    }
                }
                elseif ($elemento->tagName == 'textarea') {
                    $elemento->appendChild($this->createTextNode($valor));
                }
            }
        }
    }

    public function getElementsByName($name) {
        $xpath = new DOMXPath($this);
        $consulta = "//*[@name='" . $name . "']";
        $elementos = $xpath->query($consulta);
        return $elementos;
    }

    public function getElementByName($name) {
        $elementos = $this->getElementsByName($name);
        if ($elementos->length == 1) {
            return $elementos->item(0);
        }
        return false;
    }

    function getElementsByClass($parentNode, $tagName, $className) {
        $nodes = array();

        $childNodeList = $parentNode->getElementsByTagName($tagName);
        for ($i = 0; $i < $childNodeList->length; $i++) {
            $temp = $childNodeList->item($i);
            if (stripos($temp->getAttribute('class'), $className) !== false) {
                $nodes[] = $temp;
            }
        }

        return $nodes;
    }

}
