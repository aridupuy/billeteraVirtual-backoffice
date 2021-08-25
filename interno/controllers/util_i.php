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
            case 'acciones':
                developer_log(json_encode($variables));
                $view = $this->acciones($variables);
                break;
            case 'vista_previa':
                developer_log(json_encode($variables));
                $view = $this->vista_previa($variables);
                break;

            default:
                $view = $this->home();
                break;
        }

        return $view;
    }

    private function home($variables = null) {
//        var_dump($variables);
        $this->view->cargar("views/util_i.html");
        
        $pagina_a_mostrar = 1;
        if (isset($variables['pagina'])) {
            $pagina_a_mostrar = $variables['pagina'];
            unset($variables['pagina']);
        }
        $controller_name = strtolower(get_class($this));
        $recordset = Usuario::select_usuarios($variables);
        $filters = $this->preparar_filtros($variables);
        $form = $this->view->getElementById('miFormulario');
//        $form->setAttribute('id', 'miFormulario');
        $form->setAttribute('class', 'main-content usuarios');
        $div_100_encabezado = $this->view->createElement('div');
        $total_usr = "Total Usuarios: ". $recordset->rowCount();
        $encabezado = new Encabezado(self::$modulo, $total_usr);
        $div_100_encabezado->setAttribute('class', 'content-100 encabezado-wrapper');
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
        $exportar = $this->view->createElement('div');
        $exportar->setAttribute('class', 'btn-outline');
        $exportar->appendChild($this->view->createTextNode("Exportar"));
        
        $icono_exportar = $this->view->createElement('i');
        $icono_exportar->setAttribute('class', 'fas fa-download');
        $exportar->appendChild($icono_exportar);
        
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
        $acciones[] = array('etiqueta' => 'Vista previa', 'token' => $controller_name . '.vista_previa', 'id' => 'id_usuario');
        $acciones[] = array('etiqueta' => 'checkbox', 'id' => 'id_usuario', 'prefijo' => self::PREFIJO_CHECKBOXES);
        $div_tbl_action = $this->view->createElement('div');
        $div_tbl_action->setAttribute('class', 'dataTables_actions');
        
        $slct_action = $this->view->createElement('select');
        $slct_action->setAttribute('name', 'accion');
        $slct_action->setAttribute('style', 'width: 15%;');
        $slct_action->setAttribute('id', 'massive-actions');
            $option_0 = $this->view->createElement('option', 'Status Usuario');
            $option_0->setAttribute('value', '');
            $option_1 = $this->view->createElement('option', 'Activo');
            $option_1->setAttribute('value', '1');
            $option_2 = $this->view->createElement('option', 'Inactivo');
            $option_2->setAttribute('value', '4');
            $option_3 = $this->view->createElement('option', 'Pre-registro');
            $option_3->setAttribute('value', '6');
            $option_4 = $this->view->createElement('option', 'Rechazado');
            $option_4->setAttribute('value', '13');
            $option_5 = $this->view->createElement('option', 'Suspendido');
            $option_5->setAttribute('value', '3');
        $slct_action->appendChild($option_0);
        $slct_action->appendChild($option_1);
        $slct_action->appendChild($option_2);
        $slct_action->appendChild($option_3);
        $slct_action->appendChild($option_4);
        $slct_action->appendChild($option_5);
//        <button class="actions_submit" type="submit" value="Aplicar">
        
        $btn_action = $this->view->createElement('input');
        $btn_action->setAttribute('type','button');
        $btn_action->setAttribute('style', 'width: 15%; margin-left: 15px; padding: 12px 15px; border: none; background: #237c69; border-radius: 5px; color: #f5f9f8; -webkit-appearance: none;');
        $btn_action->setAttribute('class','actions_submit');
        $btn_action->setAttribute('value','Aplicar');
        $btn_action->setAttribute('name','util_i.acciones');
        $div_tbl_action->appendChild($slct_action);
        $div_tbl_action->appendChild($btn_action);
        
        $tabla = new Table($array,null,null,$acciones,null);
        $tabla->cambiar_encabezados($labels);
        $this->colocar_checkbox_todo($recordset->RowCount(), $tabla);
        $div_100_tabla = $this->view->createElement('div');
        $div_contenedor = $this->view->createElement('div');
        $div_100_tabla->setAttribute('class', 'content-100');
        $div_contenedor->setAttribute('class', 'contenedor-tabla');
//        $div_100_tabla->appendChild($lupa);
        $div_contenedor->appendChild($div_tbl_action);
        $div_contenedor->appendChild($this->view->importNode($tabla->documentElement, true));
        $div_100_encabezado->appendChild($this->view->importNode($encabezado->documentElement, true));
        $div_100_tabla->appendChild($div_contenedor);
        $div_100_encabezado->appendChild($lupa);
        $div_100_encabezado->appendChild($exportar);
        
        $form->appendChild($div_100_encabezado);
        $form->appendChild($this->view->importNode($filters->documentElement, true));
        $form->appendChild($div_100_tabla);
        $this->view->appendChild($form);
        return $this->view;
    }

    private function vista_previa($variables) { 
        
        print_r("<pre>");
        var_dump($variables);
        print_r("</pre>");
        $this->view->cargar("views/util_i.ver_mas.html");
        
        
        
        
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
        $labels = array('Id Usuario','Id Cuenta', 'Email', 'Nombre Completo', 'Celular', 'Fecha Creacion', 'Estado', 'Motivo', "Mensaje");
        if (!$recordset or $recordset->RowCount() == 0) {
            return array($matriz, $labels);
        }
        $recordset->Move($desde_registro - 1);

        while ($desde_registro <= $hasta_registro):
            if ($registro = $recordset->FetchRow()) {
                $array = array();
                
                $array['id_usuario'] = $registro['id_usuario'];
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

        $recordset = Motivos::select_usuario();
        $motivo = $filter->getElementById("motivo");
        foreach ($recordset as $row) {
            $option = $filter->createElement('option', $row['motivo']);
            $option->setAttribute('value', $row['id_motivo']);
            $motivo->appendChild($option);
        }
        $rsd_pep = Pep::select();
//        var_dump($rsd_pep);
        $pep = $filter->getElementById("condicion");
        foreach ($rsd_pep as $row) {
            $option_pep = $filter->createElement('option', $row['pep']);
            $option_pep->setAttribute('value', $row['id_pep']);
            $pep->appendChild($option_pep);
        }
        $filter->cargar_variables($variables);
        return $filter;
    }
    private function acciones($variables) {
        
        
        
        if($variables["accion"]!=null){
            
            print_r("<pre>");
            var_dump($variables);
            print_r("<pre>");
        }else {
            Gestor_de_log::set("Debe elegir una opció valida", 0);
            return $this->home();
        }
        
//        return $filter;
    }

}
