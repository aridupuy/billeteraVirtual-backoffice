<?php

class Util_i extends Controller {

    const PREFIJO_CHECKBOXES = "selector_";

    public static $nombre = 'Usuarios';
    public static
            $modulo = "util_i";
    public static $mensaje = "Acá podés ver el listado de Usuarios.";

//    private static $POSICION_7_IMPORTE = 107;

    public function dispatch($nav, $variables = null) {
        switch ($nav) {
            case 'home':
                Gestor_de_instancias::inicializar_instancia_actual();
                $view = $this->home($variables);
                break;
            case 'filter':
                developer_log(json_encode($variables));
                $view = $this->home($variables);
                break;

            default:
                $view = $this->home();
                break;
        }

        return $view;
    }

    private function home($variables = null) {

        $pagina_a_mostrar = 1;
        if (isset($variables['pagina'])) {
            $pagina_a_mostrar = $variables['pagina'];
            unset($variables['pagina']);
        }
        $controller_name = strtolower(get_class($this));
        $recordset = Usuario::select_usuarios($variables = null);
        $filters = $this->preparar_filtros($variables);
        $form = $this->view->createElement('form');
        $form->setAttribute('id', 'miFormulario');
        $form->setAttribute('class', 'main-content usuarios');
        $div_80 = $this->view->createElement('div');
        $total_usr = "Total Usuarios: ". $recordset->rowCount();
        $encabezado = new Encabezado(self::$modulo, $total_usr);
        $div_80->setAttribute('class', 'content-80');
        $div_80->appendChild($this->view->importNode($encabezado->documentElement, true));
        $lupa = $this->view->createElement('div');
        $lupa->setAttribute('class', 'search-bar');
        $buscador = $this->view->createElement('input');
        $buscador->setAttribute('type', 'text');
        $buscador->setAttribute('placeholder', 'Buscar por palabra clave');
        $btn_lupa = $this->view->createElement('div');
        $btn_lupa->setAttribute('class', 'btn-search');
        $icono_lupa = $this->view->createElement('i');
        $icono_lupa->setAttribute('class', 'fas fa-search');
        $btn_lupa->appendChild($icono_lupa);
        $lupa->appendChild($buscador);
        $lupa->appendChild($btn_lupa);
        $detalle = new Detalle("nombre_completo");
        $detalle->preparar_arrays($recordset);
        $pager = new Pager($recordset, $pagina_a_mostrar, $controller_name . '.filter');
        if (is_object($recordset) AND $recordset->rowCount() > 0) {
            list($array, $labels) = $this->preparar_array($recordset, $pager->desde_registro, $pager->hasta_registro);
        } else {
            $array = $labels = array();
        }
        array_unshift($labels, "");
        $acciones = array();
        $acciones[] = array('etiqueta' => 'Vista previa', 'token' => $controller_name . '.vista_previa', 'id' => 'id_bolemarchand');
        $acciones[] = array('etiqueta' => 'checkbox', 'id' => 'id_bolemarchand', 'prefijo' => self::PREFIJO_CHECKBOXES);
        $tabla = new Table($array, null, null, $acciones);
        $tabla->cambiar_encabezados($labels);
        $this->colocar_checkbox_todo($recordset->RowCount(), $tabla);
        $div_cajita = $this->view->createElement('div');
        $div_cajita->setAttribute('class', 'contenedor');
        $div_cajita->appendChild($lupa);
        $div_cajita->appendChild($this->view->importNode($tabla->documentElement, true));
        $div_80->appendChild($div_cajita);
        $form->appendChild($div_80);
        $form->appendChild($this->view->importNode($filters->documentElement, true));
        $this->view->appendChild($form);
        return $this->view;
    }

    private function colocar_checkbox_todo($cantidad, Table $table) {
        if (($th = $table->getElementsByTagName('th')->item(0)) !== null) {
            $checkbox = $table->createElement('input');
            $checkbox->setAttribute('type', 'checkbox');
            $checkbox->setAttribute('style', '-webkit-appearance: auto');
            $checkbox->setAttribute('id', 'checkbox_todo');
            $checkbox->setAttribute('name', 'checkbox_todo');
            $th->setAttribute('title', "Seleccione $cantidad boleta/s");
//            $text = $table->createTextNode($cantidad);
            $th->appendChild($checkbox);
//            $th->appendChild($text);
        }
    }

    private function preparar_array(ADORecordSet $recordset, $desde_registro, $hasta_registro) {

        $matriz = array();
        $labels = array('Id Cuenta', 'Email', 'Nombre Completo', 'Celular', 'Fecha Creacion', 'Estado', 'Motivo', "Mensaje");
        if (!$recordset or $recordset->RowCount() == 0) {
            return array($matriz, $labels);
        }
        $recordset->Move($desde_registro - 1);

        while ($desde_registro <= $hasta_registro):
            if ($registro = $recordset->FetchRow()) {
                $array = array();

                $array[] = $registro['id_cuenta'];
                $array[] = $registro['email'];
                $array[] = $registro['nombre_completo'];
                $array[] = $registro['celular'];
                $array[] = $registro['fecha_creacion'];
                $array[] = $registro['authstat'];
                $array[] = $registro['motivo'];
                $array[] = $registro['mensaje'];

                $matriz[] = $array;
            }
            $desde_registro++;
        endwhile;

        return array($matriz, $labels);
    }

    private function preparar_filtros($variables) {
        $filter = new view();
        if (isset($variables['id'])) {
            unset($variables['id']);
        }
        $filter->cargar("views/util_i.filters.html");

        $recordset = Motivos::select();
        $motivo = $filter->getElementById("motivo");
        foreach ($recordset as $row) {
            $option = $filter->createElement('option', $row['motivo']);
            $option->setAttribute('value', $row['id_motivo']);
            $motivo->appendChild($option);
        }
        $filter->cargar_variables($variables);
        return $filter;
    }

}
