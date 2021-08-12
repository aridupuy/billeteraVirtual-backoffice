<?php

class Util_ii extends Controller{

    const PREFIJO_CHECKBOXES = "selector_";

    public static $nombre = 'Movimientos';
    public static
        $modulo = "util_ii";
    public static $mensaje = "Acá podés ver el listado de los movimientos.";

    //    private static $POSICION_7_IMPORTE = 107;

    public function dispatch($nav, $variables = null){
        switch ($nav) {
            case 'home':
                Gestor_de_instancias::inicializar_instancia_actual();
                $view = $this->home($variables);
                break;
            case 'cash_out':
                Gestor_de_instancias::inicializar_instancia_actual();
                $view = $this->home($variables, 'out');
                break;
            case 'cash_in':
                Gestor_de_instancias::inicializar_instancia_actual();
                $view = $this->home($variables, 'in');
                break;
            case 'filter':
                var_dump($variables);
                developer_log(json_encode($variables));
                $view = $this->home($variables, $variables['tipo_cash']);
                break;
            default:
                $view = $this->home();
                break;
        }

        return $view;
    }

    private function home($variables = null, $tipo_cash){
        $pagina_a_mostrar = 1;
        if (isset($variables['pagina'])) {
            $pagina_a_mostrar = $variables['pagina'];
            unset($variables['pagina']);
        }

        $controller_name = strtolower(get_class($this));

        $filters = $this->preparar_filtros($variables, $tipo_cash);
        unset($variables['tipo_cash']);
        $recordset = Transaccion::select_min($variables, $tipo_cash);
        $form = $this->view->createElement('form');
        $form->setAttribute('id', 'miFormulario');
        $form->setAttribute('class', 'main-content usuarios');
        $div_100_encabezado = $this->view->createElement('div');
        $total_usr = "Total Usuarios: " . $recordset->rowCount();
        $encabezado = new Encabezado(self::$modulo, $total_usr);
        $div_100_encabezado->setAttribute('class', 'content-100 encabezado-wrapper');
        // $lupa = $this->view->createElement('div');
        // $lupa->setAttribute('class', 'search-bar');
        // $buscador = $this->view->createElement('input');
        // $buscador->setAttribute('type', 'text');
        // $buscador->setAttribute('placeholder', 'Buscar por palabra clave');
        // $btn_lupa = $this->view->createElement('div');
        // $btn_lupa->setAttribute('class', 'btn-search');
        // $icono_lupa = $this->view->createElement('i');
        // $icono_lupa->setAttribute('class', 'fas fa-search');
        // $btn_lupa->appendChild($icono_lupa);
        // $lupa->appendChild($buscador);
        // $lupa->appendChild($btn_lupa);
        $exportar = $this->view->createElement('div');
        $exportar->setAttribute('class', 'btn-outline');
        $exportar->appendChild($this->view->createTextNode("Exportar"));

        $icono_exportar = $this->view->createElement('i');
        $icono_exportar->setAttribute('class', 'fas fa-download');
        $exportar->appendChild($icono_exportar);

        $detalle = new Detalle("nombre_completo");
        $detalle->preparar_arrays($recordset);
        $pager = new Pager($recordset, $pagina_a_mostrar, $controller_name . '.filter');
        if (is_object($recordset) and $recordset->rowCount() > 0) {
            list($array, $labels) = $this->preparar_array($recordset, $pager->desde_registro, $pager->hasta_registro, $tipo_cash);
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

        $div_100_tabla = $this->view->createElement('div');
        $div_contenedor = $this->view->createElement('div');

        $div_100_tabla->setAttribute('class', 'content-100');
        $div_contenedor->setAttribute('class', 'contenedor-tabla');

        $div_contenedor->appendChild($this->view->importNode($tabla->documentElement, true));
        $div_100_encabezado->appendChild($this->view->importNode($encabezado->documentElement, true));
        $div_100_tabla->appendChild($div_contenedor);

        // $div_100_encabezado->appendChild($lupa);
        $div_100_encabezado->appendChild($exportar);

        $form->appendChild($div_100_encabezado);
        $form->appendChild($this->view->importNode($filters->documentElement, true));
        $form->appendChild($div_100_tabla);

        $this->view->appendChild($form);
        return $this->view;
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

    private function preparar_array(ADORecordSet $recordset, $desde_registro, $hasta_registro, $tipo_cash){
        $matriz = array();
        if ($tipo_cash == 'out') {
            $labels = array('Nro', 'Fecha y Hora', 'Titular', 'Email Origen', 'Doc/Cuil', 'Monto', 'MP', 'Estado', 'Motivo', 'Referencia', 'Email Destino', 'CVU', 'CBU', 'Alias', 'Nombre', 'Apellido', 'Cuit Destino', 'Banco', 'Cod Banco');
        } else {
            $labels = array('Nro', 'Fecha y Hora', 'Titular', 'Email Origen', 'Doc/Cuil', 'Monto', 'MP', 'Estado', 'Motivo', 'Referencia', 'Email Destino', 'CVU', 'CBU', 'Alias', 'Nombre', 'Apellido', 'Cuit Destino', 'Banco', 'Cod Banco');
        }

        if (!$recordset or $recordset->RowCount() == 0) {
            return array($matriz, $labels);
        }
        $recordset->Move($desde_registro - 1);

        while ($desde_registro <= $hasta_registro) :
            if ($registro = $recordset->FetchRow()) {
                $array = array();

                if ($tipo_cash == 'out') {
                    $array[] = $registro['id_transaccion'];
                    $array[] = $registro['fecha_gen'];
                    $array[] = $registro['titular'];
                    $array[] = $registro['email_origen'];
                    $array[] = $registro['documento'];
                    $array[] = $registro['monto'];
                    $array[] = $registro['mp'];
                    $array[] = $registro['status'];
                    $array[] = $registro['motivo'];
                    $array[] = $registro['concepto'];
                    $array[] = $registro['email_destino'];
                    $array[] = $registro['cvu'];
                    $array[] = $registro['cbu'];
                    $array[] = $registro['alias'];
                    $array[] = $registro['nombre'];
                    $array[] = $registro['apellido'];
                    $array[] = $registro['cuit_destino'];
                    $array[] = $registro['nombre_banco'];
                    $array[] = $registro['cod_banco'];
                    $matriz[] = $array;
                } else {
                }
            }
            $desde_registro++;
        endwhile;

        return array($matriz, $labels);
    }

    private function preparar_filtros($variables,$tipo_cash)
    {
        $filter = new view();
        if (isset($variables['id'])) {
            unset($variables['id']);
        }
        $filter->cargar("views/util_ii.filters.html");

        $recordset = Motivos::select_concepto();
        $motivo = $filter->getElementById("motivo");
        foreach ($recordset as $row) {
            $option = $filter->createElement('option', $row['motivo']);
            $option->setAttribute('value', strtolower($row['motivo']));
            $motivo->appendChild($option);
        }

        $recordset = Bancos::select();
        $cuenta = $filter->getElementById("cuenta");

        foreach ($recordset as $row) {
            $option = $filter->createElement('option', $row['nombre']);
            $option->setAttribute('value', strtolower($row['id_banco']));
            $cuenta->appendChild($option);
        }

        $input_motivo = $filter->getElementById('tipo_cash');
        $input_motivo->setAttribute('value',$tipo_cash);

        $filter->cargar_variables($variables);
        return $filter;
    }
}
