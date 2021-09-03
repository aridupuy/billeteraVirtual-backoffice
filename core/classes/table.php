<?php

// namespace Classes;
class Table extends View {

    const INTERRUPTOR = 'Interruptor';
    const CLASE_ACTIVADO = 'fa fa-toggle-on green';
    const CLASE_DESACTIVADO = 'fa fa-toggle-off red';
    const REEMPLAZO_BINARIOS = 'Bin';
    const REEMPLAZO_TRUE = 'True';
    const REEMPLAZO_FALSE = 'False';
    const REEMPLAZO_NO_DETERMINADO = 'No determinado';
    const COLOR = "color";
    const BOTON_MICROSITIO = "BOTON";
    const BOTON_MAS = "+";

    private $procesa_colores = true;

    # Retorna la tabla. Recibe un recordset y un conjunto de acciones

    public function __construct($registros, $desde_registro, $hasta_registro, $acciones = null, $fcond = false) {
        parent::__construct();
        if ($desde_registro == null AND $hasta_registro == null) {
            if (is_array($registros))
                return $this->construir_desde_array($registros, $acciones, $fcond);
        }
        if (is_numeric($desde_registro) AND is_numeric($hasta_registro)) {
            if (is_object($registros) AND get_class($registros) == 'ADORecordSet_postgres8')
                return $this->construir_desde_recordset($registros, $desde_registro, $hasta_registro, $acciones, $fcond);
        }
//	error_log("error");
        return $this->retornar_tabla_vacia();
    }

    private function construir_desde_recordset(ADORecordSet $registros, $desde_registro, $hasta_registro, $acciones = null, $fcond = false, $order = false) {

        $table = $this->createElement('table');
        $table->setAttribute("id", "dataTable");
        $table->setAttribute("class", "tabla");
//        $table->setAttribute("role", "grid");
        $this->appendChild($table);
        if ($registros === false)
            return $table;

        if ($order) {
            $input = $this->createElement('input');
            $input->setAttribute('type', 'hidden');
//            $input->setAttribute('name', 'ordering');
            $input->setAttribute('id', 'col-ordering');
            $input->setAttribute('value', $order[0]);
            $table->appendChild($input);

            $input2 = $this->createElement('input');
            $input2->setAttribute('type', 'hidden');
//            $input->setAttribute('name', 'ordering');
            $input2->setAttribute('id', 'way-ordering');
            $input2->setAttribute('value', $order[1]);
            $table->appendChild($input2);
        }
        #Encabezado de la tabla
        if ($registros->rowCount() === 0) {
            error_log("tabla vacia");
            return $table;
        }
        $table_head = $this->createElement('thead');
        $table->appendChild($table_head);
        $tr = $this->createElement('tr');
        $tr->setAttribute("role", "row");
        
        foreach ($registros as $columna => $valor): #Registros originalmente llamaba a fields ($registros->fields )pero no esta funcionando
            if (is_numeric($columna)) {
                $meta = $registros->FetchField($columna);
                if($meta->name!=false){ #esta validacion es para que la tabla no muestre campos en false del Thead
                    
                switch ($registros->MetaType($meta->type)) {
                    case 'X':
                        #Si son textos (o xml) recorto 50 caracteres
                        $th = $this->createElement('th');
//                        if(strlen($meta->name)>20)
//                            $th->setAttribute('style', 'min-width: 158px;');
                        $th->setAttribute('title', 'Se muestran los primeros ' . MAXIMO_CARACTERES_CELDA . ' caracteres.');
                        $text = $this->createTextNode(ucfirst($meta->name));
//                        $span = $this->createElement('span');
//                        $span->setAttribute('class', 'fa fa-sort');
//                        $span->setAttribute('style', 'margin-right: 3%; margin-left: 0%');
//                        $th->appendChild($span);
                        $th->appendChild($text);
//                        $th->setAttribute('style', "text-align:left");
                        $tr->appendChild($th);
                        break;
                    case 'I':
                    case 'N':
                        $th = $this->createElement('th');
//                        if(strlen($meta->name)>20)
//                            $th->setAttribute('style', 'white-space: nowrap');
//                        else
                        $th->setAttribute('style', 'white-space: nowrap');
                        $text = $this->createTextNode(ucfirst($meta->name));
//                        $span = $this->createElement('span');
//                        $span->setAttribute('class', 'fa fa-sort');
//                        $span->setAttribute('style', 'margin-right: 3%;  margin-left: 0%');
                        $th->appendChild($text);
//                        $th->appendChild($span);
//                        $th->setAttribute('style', "text-align:left");
                        $tr->appendChild($th);
                        break;
                    case 'C':
                        $th = $this->createElement('th');
//                        if(strlen($meta->name)>20)
                        $th->setAttribute('style', 'white-space: nowrap');
//                        else
//                            $th->setAttribute('style', 'min-width: 1%; white-space: nowrap');
                        $text = $this->createTextNode(ucfirst($meta->name));
//                        $span = $this->createElement('span');
//                        $span->setAttribute('class', 'fa fa-sort');
//                        $span->setAttribute('style', 'margin-left: 0%');
                        $th->appendChild($text);
//                        $th->appendChild($span);
                        $tr->appendChild($th);
                        break;
                    case 'T':
                        $th = $this->createElement('th');
                        $th->setAttribute('class', 'dateorder');
//                        if(strlen($meta->name)>20)
                        $th->setAttribute('style', 'white-space: nowrap');
//                        else
//                            $th->setAttribute('style', 'min-width: 1%; white-space: nowrap');
                        $text = $this->createTextNode(ucfirst($meta->name));
//                        $span = $this->createElement('span');
//                        $span->setAttribute('class', 'fa fa-sort');
//                        $span->setAttribute('style', 'margin-left: 0%');
                        $th->appendChild($text);
//                        $th->appendChild($span);
                        $tr->appendChild($th);
                        break;
                    default:
//                        break;
                            $th = $this->createElement('th');
//                        if(strlen($meta->name)>20)
                            $th->setAttribute('style', 'white-space: nowrap');
//                        else
//                            $th->setAttribute('style', 'min-width: 1%; white-space: nowrap');
                            $text = $this->createTextNode(ucfirst($meta->name));
                            $span = $this->createElement('span');
                            $span->setAttribute('class', 'fa fa-sort');
                            $span->setAttribute('style', 'margin-left: 0%');
                            $th->appendChild($text);
                            $th->appendChild($span);
                            $tr->appendChild($th);
                            break;
                    }
                }
            }
        endforeach;
        if ($acciones != null)
            foreach ($acciones as $accion):
                $th = $this->createElement('th');
                $tr->appendChild($th);
            endforeach;

        $table_head->appendChild($tr);

        #Cuerpo de la tabla
//	var_dump($desde_registro);
        $registros->Move($desde_registro - 1);
        $verificados = array();
        while ($desde_registro <= $hasta_registro):

            $registro = $registros->FetchRow();
            $tr = $this->createElement('tr');
            $tr->setAttribute("role", "row");
            foreach ($registro as $columna => $valor):
                if (is_numeric($columna)) {
                    $meta = $registros->FetchField($columna);
                    switch ($registros->MetaType($meta->type)) {
                        case 'L':
                            #Si son campos Booleanos
                            if (!isset($valor))
                                $reemplazo = self::REEMPLAZO_NO_DETERMINADO;
                            elseif ($valor == 't')
                                $reemplazo = self::REEMPLAZO_TRUE;
                            else
                                $reemplazo = self::REEMPLAZO_FALSE;
                            $td = $this->createElement('td', $reemplazo);
                            $td->setAttribute('style', "text-align:center");
                            $td->setAttribute('title', 'Es un campo booleano.');
                            $tr->appendChild($td);
                            break;
                        case 'B':
                            #Si son cadenas Binarias
                            $td = $this->createElement('td');
                            $td->setAttribute('style', "text-align:center");
                            $td->setAttribute('title', 'Es una cadena Binaria que no se puede mostrar.');
                            $td->appendChild($this->createTextNode(self::REEMPLAZO_BINARIOS));
                            $tr->appendChild($td);
                            break;
                        case 'X':
                            # Si son textos o XML recorto X caracteres
                            $td = $this->createElement('td');
//                            var_dump($valor);
                            if (strstr($valor, ".png")) {
                                $options['http'] = array(
                                    'method' => "HEAD",
                                    'ignore_errors' => 1,
                                    'max_redirects' => 0
                                );
//                                var_dump($_SERVER);
                                $url = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $valor;
                                if (in_array($valor, $verificados)) {
                                    $img = $this->createElement("img");
                                    $img->setAttribute("src", $valor);
                                    $img->setAttribute("style", "width: 50%;");
                                    $td->appendChild($img);
                                    $img->setAttribute("title",str_replace("-", " ", str_replace(".png", "", str_replace("/medios-pagos/", "",$valor))));
                                    //$img->setAttribute("title",$url);
                                } else {
                                    $body = @file_get_contents($url, NULL, stream_context_create($options));
                                    if (isset($http_response_header)) {
                                        sscanf($http_response_header[0], 'HTTP/%*d.%*d %d', $httpcode);

                                        $accepted_response = array(200, 301, 302);
                                        if (in_array($httpcode, $accepted_response)) {
                                            $img = $this->createElement("img");
                                            $img->setAttribute("src", $valor);
                                            $img->setAttribute("style", "width: 50%;");
                                            $td->appendChild($img);
                                            $img->setAttribute("title",str_replace("-", " ", str_replace(".png", "", str_replace("/medios-pagos/", "",$valor))));
                                            $verificados[] = $valor;
                                        } else {
                                            $td->appendChild($this->createTextNode(str_replace(".png", "", str_replace("/medios-pagos/", "", $valor))));
                                            $td->appendChild($this->createTextNode($url));
                                        }
                                    } else {
                                        $td->appendChild($this->createTextNode(str_replace(".png", "", str_replace("/medios-pagos/", "", $valor))));
//                                        $td->appendChild($this->createTextNode($url));
                                    }
                                }
//                                var_dump(file_exists());
//                                    if (file_exists($_SERVER["REQUEST_SCHEME"]."://".$_SERVER["SERVER_ADDR"].":".$_SERVER["SERVER_PORT"].$valor)){
//                                    }
//                                    else{
//                                        
//                                    }
                            } else {
                                $td->setAttribute('style', "text-align:center");
                                $td->appendChild($this->createTextNode(substr($valor, 0, MAXIMO_CARACTERES_CELDA)));
                                $td->setAttribute('title', $valor);
                            }
                            $tr->appendChild($td);
                            break;
                        case 'D':
                        case 'T':
                            $td = $this->createElement('td');
                            $td->setAttribute('style', "text-align:center");
                            if (!$valor) {
                                $td->appendChild($this->createTextNode(' '));
                                $td->setAttribute('title', 'El campo se encuentra vacío en la base de datos.');
                            } else {
                                $td->appendChild($this->createTextNode(formato_fecha($valor)));
//                                var_dump(strstr($valor,".png"));
//                              
                                $td->setAttribute('title', $valor);
                            }
                            $tr->appendChild($td);
                            break;
                        case 'I':
                        case 'N':
                            $td = $this->createElement('td');
                            $td->appendChild($this->createTextNode($valor));
                            $td->setAttribute('style', "text-align:center");
                            $td->setAttribute('data-order', $valor);
                            $tr->appendChild($td);
                            break;
                        default:
                            $td = $this->createElement('td');
                            $td->appendChild($this->createTextNode($valor));
                            $tr->appendChild($td);
                            break;
                    }
                }
            endforeach;

            $this->procesar_acciones($tr, $registro, $acciones, $fcond);
            $table->appendChild($tr);

            $desde_registro++;
        endwhile;

        return $table;
    }

    private function construir_desde_array($registros, $acciones = null, $fcond = false, $order = false) {
        $table = $this->createElement('table');
        $table->setAttribute("id", "dataTable");
        $table->setAttribute("class", "table hover border bordered table-data dataTable no-footer");
        $this->appendChild($table);
        if (count($registros) === 0)
            return $table;

        if ($order) {
            $input = $this->createElement('input');
            $input->setAttribute('type', 'hidden');
//            $input->setAttribute('name', 'ordering');
            $input->setAttribute('id', 'col-ordering');
            $input->setAttribute('value', $order[0]);
            $table->appendChild($input);

            $input2 = $this->createElement('input');
            $input2->setAttribute('type', 'hidden');
//            $input->setAttribute('name', 'ordering');
            $input2->setAttribute('id', 'way-ordering');
            $input2->setAttribute('value', $order[1]);
            $table->appendChild($input2);
        }

        $table_head = $this->createElement('thead');
        $table->appendChild($table_head);
        $tr = $this->createElement('tr');
        $tr->setAttribute("role", "row");
        foreach ($registros[0] as $clave => $valor) {
            $th = $this->createElement('th', $valor);
            if (validar_fecha($valor)) {
                $th->setAttribute('class', 'dateorder');
            }

//            $span = $this->createElement('span');
//            $span->setAttribute('class', 'fa fa-sort');
//            $span->setAttribute('style', 'margin-right: 3%; margin-left: 0%');
//            $th->appendChild($span);
            $tr->appendChild($th);
        }
        if (!$acciones == null) {
            foreach ($acciones as $accion):
                $th = $this->createElement('th');
                if (isset($meta->name) and strlen($meta->name) > 20)
                    $th->setAttribute('style', 'min-width: 158px; white-space: nowrap');
                $tr->appendChild($th);
                $tr->appendChild($th);
            endforeach;
        }

        $table_head->appendChild($tr);
//        array_shift($registros);
        foreach ($registros as $registro) {
            $tr = $this->createElement('tr');
            $tr->setAttribute("role", "row");
            foreach ($registro as $clave => $valor) {
                $td = $this->createElement('td', $registro[$clave]);
                if (numeric_comma($registro[$clave])) {
                    $td->setAttribute('data-order', basic_num_order($registro[$clave]));
                }
                $td->setAttribute('style', "text-align:center");
                $tr->appendChild($td);
            }
            $this->procesar_acciones($tr, $registro, $acciones);
            $table->appendChild($tr);
        }
        return $table;
    }

    public static function procesar_acciones_interfaz(DOMDocument $view, $tr, $registro, $acciones) {
        $lacc = true; //default

        if (is_array($acciones) AND count($acciones)) {
            foreach ($acciones as $accion):
                $td = $view->createElement('td');
                $td->setAttribute('class', 'link acciones');
                $td->setAttribute('type', 'button');
                if (isset($accion['token']))
                    $td->setAttribute('name', $accion['token']);
                if (isset($accion['id']))
                    $td->setAttribute('id', $registro[$accion['id']]);

                if (isset($accion['etiqueta'])) {
                    if ($lacc) {
                        $td->appendChild($view->createTextNode($accion['etiqueta']));
                    }
                }
                $tr->appendChild($td);
            endforeach;
        }
    }

    private function procesar_acciones($tr, $registro, $acciones, $fcond = false) {
//        print_r("<pre>");
//        var_dump($acciones);
//        print_r("</pre>");
        $lacc = true; //default
        if (is_array($acciones) AND count($acciones)) {
            foreach ($acciones as $accion):
//                var_dump($accion);
                if (isset($accion['etiqueta']) AND $accion['etiqueta'] == 'checkbox') {
                    $this->procesar_selectores($tr, $registro, $accion);
                } elseif (isset($accion['etiqueta']) AND $accion['etiqueta'] == self::COLOR) {
                    $this->procesar_colores($tr, $registro, $accion);
                    if ($this->procesa_colores) {
                        $th = $this->createElement('th', "Estado");
                        $thead = $this->getElementsByTagName("thead");
                        $thead = $thead->item(0);
                        $tr_res = $thead->childNodes->item(0);
                        $th_res = $tr_res->childNodes->item(0);
                        $tr_res->insertBefore($th, $th_res);
                        $this->procesa_colores = false;
                    }
                } elseif (isset($accion['etiqueta']) AND ( $accion['etiqueta'] == self::BOTON_MICROSITIO)) {
                    if ($accion["campo"] != null) {


                        if (isset($accion["callback"]) and ! $accion["callback"]($registro, $tr, $accion, $this)) {

                            return false;
                        }
                    }
                } else if (isset($accion['etiqueta']) and isset($accion["callback"])) {
                    if (isset($accion["callback"]) and ! $accion["callback"]($registro, $tr, $accion, $this)) {
//                        if($accion['etiqueta']=="colores")

                        return false;
                    }
                } else {
                    $td = $this->createElement('td');
                    $td->setAttribute('style', 'cursor: pointer;');
                    $td->setAttribute('type', 'button');
                    if (isset($accion['token']))
                        $td->setAttribute('name', $accion['token']);
                    if (isset($accion['id']))
                        // Se realiza un cambio a registro[0] porque el array no es asociativo
                        // $td->setAttribute('id', $registro[$accion['id']]);
                        $td->setAttribute('id', $registro[0]);

                    if (isset($accion['etiqueta']) AND $accion['etiqueta'] == self::INTERRUPTOR) {
                        $td = $this->armar_interruptor($td, $accion, $registro);
                    } else if (isset($accion['etiqueta']) AND $accion['etiqueta'] == self::BOTON_MAS) {
                        $td = $this->armar_boton($td, $accion, $registro);
                    } else {
                        if (isset($accion['etiqueta'])) {

                            /*
                             * $fcond es un clousure se le envia el registro
                             * decide si si o no poner una accion segun el registro
                             * el clousure decide con true o false
                             */
                            if ($fcond) {
                                $lacc = $fcond($registro, $accion);
                            }
                            if (isset($accion["callback"])) {
                                $lacc = $accion["callback"]($registro);
                            }

                            if ($lacc) {
                                $td->appendChild($this->createTextNode($accion['etiqueta']));
                            }
                        }
                    }
                    $tr->appendChild($td);
                }
            endforeach;
        }
    }

    private function procesar_colores(DOMNode $tr, $registro, $colores) {

        foreach ($registro as $clave => $elemento) {
            if ($clave === $colores["id"]) {
                $td = $this->createElement('td');
                $span = $this->createElement('span');
                $span->setAttribute('class', 'fa fa-circle');
                $color = $colores["callback"]($registro[$colores["id"]]);
                $span->setAttribute('style', "color:" . $color . ";");
                $td->appendChild($span);
                $tr->insertBefore($td, $tr->firstChild);
                unset($tr);
                unset($td);
                unset($color);
                unset($span);
            }
        }
    }

    public function procesar_selectores($tr, $registro, $accion) {
        $prefijo_para_names = $accion['prefijo'];
        if (isset($accion['checked']))
            $checked = $accion['checked'];
        else
            $checked = null;
        // Se realiza un cambio a registro[0] porque el array no es asociativo
        // $valor = $registro[$accion['id']];
        $valor = $registro[0];
        $td = $this->createElement('td');
        $checkbox = $this->createElement('input');
        $checkbox->setAttribute('type', 'checkbox');
        $checkbox->setAttribute('style', '-webkit-appearance: auto');
        $checkbox->setAttribute('name', $prefijo_para_names . $valor);
        if ($checked != null and in_array($valor, $checked)) {
            $checkbox->setAttribute('value', '1');
            $checkbox->setAttribute('checked', 'checked');
        }

        $td->appendChild($checkbox);
        $tr->insertBefore($td, $tr->childNodes->item(0));
    }

    public function cambiar_encabezados($encabezados) {

        $ths = $this->getElementsByTagName('th');
        $i = 0;
//        var_dump(count($encabezados));
        foreach ($ths as $th) {
            foreach ($th->childNodes as $node) {
                $th->removeChild($node);
            }
            if (isset($encabezados[$i])) {
                $span = $this->createElement('span');
                if ($th->childNodes->length == 1)
                    $span->setAttribute('class', 'fa fa-sort');

//                if ($th->childNodes->length !== 0){
//                }
                if ($th->childNodes->length === 0)
                    $th->appendChild($span);
                if (strlen($encabezados[$i]) > 20)
                    $th->setAttribute('style', 'min-width: 158px; white-space: nowrap');
                else {
                    $th->setAttribute('style', 'min-width: 80px; white-space: nowrap');
                }
                $th->appendChild($this->createTextNode($encabezados[$i]));
            }
            $i++;
        }
        return $this;
    }

    public function cambiar_id_tabla($id) {
        $tables = $this->getElementsByTagName("table");
        $tables->item(0)->setAttribute("id", $id);
    }

    /*
     * Elimina una columna
     */

    public function eliminar_columna($numero) {

        $filas = $this->getElementsByTagName('tr');
        foreach ($filas as $fila) {
            $fila->removeChild($fila->childNodes->item($numero - 1));
        }
    }

    public function eliminar_fila($numero) {

        $filas = $this->getElementsByTagName('table');
        foreach ($filas as $fila) {
            $fila->removeChild($fila->childNodes->item($numero - 1));
        }
    }

    private function armar_interruptor($td, $accion, $registro) {
        if (!(isset($accion["campo"]) and isset($registro[$accion["campo"]]))) {
            $accion['campo'] = "id_authstat";
//                $registro[$accion['campo']]="id_authstat";
        }
        if (!isset($accion["id_activo"]))
            $accion["id_activo"] = 0;
        if (!isset($accion["id_inactivo"]))
            $accion["id_inactivo"] = 0;
        switch ($registro[$accion['campo']]) {
            case $accion["id_activo"]:
            case Authstat::ACTIVO:
                $td->setAttribute('class', self::CLASE_ACTIVADO);
                $td->setAttribute('title', 'Activado');
                break;
            case $accion["id_inactivo"]:
            case Authstat::INACTIVO:
                $td->setAttribute('title', 'Desactivado');
                $td->setAttribute('class', self::CLASE_DESACTIVADO);
                break;
            default:
                $td->removeAttribute('type');
                $td->removeAttribute('name');
                $td->removeAttribute('class');
                break;
        }
        return $td;
    }

    private function retornar_tabla_vacia() {
        $table = $this->createElement('table');
        $this->appendChild($table);
        return $table;
    }

    private function armar_boton($td, $accion, $registro) {
        $td->setAttribute('title', 'VER MAS');
        $img = $this->createElement("img");
        $img->setAttribute("class", "mostrar-detalle");
        $img->setAttribute("src", "public/img/icono-agregar.svg");
//        $td->removeAttribute('type');
//        $td->removeAttribute('name');
//        $td->setAttribute('type', 'button');
//        $td->setAttribute("id",$accion["id"]);
        $td->appendChild($img);
        return $td;
    }

}
