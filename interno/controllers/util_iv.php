<?php

class Util_iv extends Controller{

    const PREFIJO_CHECKBOXES = "selector_";

    public static $nombre = 'Administracion';
    public static
        $modulo = "util_iv";
    public static $mensaje = "Acá podés ver el panel de Administracion.";

    //    private static $POSICION_7_IMPORTE = 107;

    public function dispatch($nav, $variables = null){
        switch ($nav) {
            case 'home':
                Gestor_de_instancias::inicializar_instancia_actual();
                $view = $this->home($variables);
                break;
            case 'blacklist':
                Gestor_de_instancias::inicializar_instancia_actual();
                $view = $this->blacklist($variables);
                break;
            case 'filter':
                $view = $this->home($variables);
                break;
            default:
                $view = $this->home($variables);
                break;
        }

        return $view;
    }

    private function blacklist($variables = null){
        unset($variables['id']);
        $pagina_a_mostrar = 1;
        if (isset($variables['pagina'])) {
            $pagina_a_mostrar = $variables['pagina'];
            unset($variables['pagina']);
        }

        $controller_name = strtolower(get_class($this));

        $filters = $this->preparar_filtros($variables);
        $recordset = Blacklist::select_min($variables);
        $form = $this->view->createElement('form');
        $form->setAttribute('id', 'miFormulario');
        $form->setAttribute('class', 'main-content usuarios');
        $div_100_encabezado = $this->view->createElement('div');
        $total_usr = "Total Reglas: " . $recordset->rowCount();
        $encabezado = new Encabezado(self::$modulo, $total_usr);
        $div_100_encabezado->setAttribute('class', 'content-100 encabezado-wrapper');

        $exportar = $this->view->createElement('a');
        $exportar->setAttribute('class', 'btn-outline');
        $exportar->setAttribute('type', 'button');
        $exportar->setAttribute('name', 'util_iv.add_blacklist');
        $exportar->appendChild($this->view->createTextNode("Agregar "));

        $icono_exportar = $this->view->createElement('i');
        $icono_exportar->setAttribute('class', 'fas fa-user-plus');
        $exportar->appendChild($icono_exportar);

        $detalle = new Detalle("nombre_completo");
        $detalle->preparar_arrays($recordset);
        $pager = new Pager($recordset, $pagina_a_mostrar, $controller_name . '.filter');
        if (is_object($recordset) and $recordset->rowCount() > 0) {
            list($array, $labels) = $this->preparar_array($recordset, $pager->desde_registro, $pager->hasta_registro);
        } else {
            $array = $labels = array();
        }
        array_unshift($labels, "");

        $acciones = array();
        $acciones[] = array('etiqueta' => 'Editar', 'token' => $controller_name . '.edit_blacklist', 'id' => 'id_bolemarchand');
        $acciones[] = array('etiqueta' => 'checkbox', 'id' => 'id_bolemarchand', 'prefijo' => self::PREFIJO_CHECKBOXES);

        $tabla = new Table($array, null, null, $acciones);
        $tabla->cambiar_encabezados($labels);

        $this->colocar_checkbox_todo($recordset->RowCount(), $tabla);

        $div_100_tabla = $this->view->createElement('div');
        $div_contenedor = $this->view->createElement('div');

        $div_100_tabla->setAttribute('class', 'content-100');
        $div_contenedor->setAttribute('class', 'contenedor-tabla');

        $div_contenedor->appendChild($this->view->importNode($tabla->documentElement, true));
        $div_100_encabezado->appendChild($this->view->importNode($encabezado->documentElement, true));
        $div_100_tabla->appendChild($div_contenedor);

        $div_100_encabezado->appendChild($exportar);

        $form->appendChild($div_100_encabezado);
        $form->appendChild($this->view->importNode($filters->documentElement, true));
        $form->appendChild($div_100_tabla);

        $this->view->appendChild($form);
        return $this->view;
    }

    private function home($variables = null){

    }

    private function colocar_checkbox_todo($cantidad, Table $table){
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

    private function preparar_array(ADORecordSet $recordset, $desde_registro, $hasta_registro){
        $matriz = array();
            $labels = array('ID', 'Fecha y Hora', 'Regla', 'Comentario', 'Analista', 'Estado', 'Motivo');

        if (!$recordset or $recordset->RowCount() == 0) {
            return array($matriz, $labels);
        }
        $recordset->Move($desde_registro - 1);

        while ($desde_registro <= $hasta_registro) :
            if ($registro = $recordset->FetchRow()) {
                $array = array();
                $array[] = $registro['id_blacklist'];
                $array[] = $registro['fechahora'];
                $array[] = Blacklist::procesarJSON($registro['regla']);
                $array[] = $registro['comentario'];
                $array[] = $registro['authname'];
                $array[] = $registro['authstat'];
                $array[] = $registro['motivo'];
                $matriz[] = $array;
            }
            $desde_registro++;
        endwhile;

        return array($matriz, $labels);
    }

    private function preparar_filtros($variables){
        $filter = new view();
        if (isset($variables['id'])) {
            unset($variables['id']);
        }
        $filter->cargar("views/util_iv.filters.blacklist.html");

        $recordset = Motivos::select_blacklist();
        $motivo = $filter->getElementById("motivo");
        foreach ($recordset as $row) {
            $option = $filter->createElement('option', $row['motivo']);
            $option->setAttribute('value', $row['id_motivo']);
            $motivo->appendChild($option);
        }

        $recordset = Auth::select_auth();
        $analista = $filter->getElementById("analista");

        foreach ($recordset as $row) {
            $option = $filter->createElement('option', $row['authname']);
            $option->setAttribute('value', $row['id_auth']);
            $analista->appendChild($option);
        }

        $filter->cargar_variables($variables);
        return $filter;
    }
}
