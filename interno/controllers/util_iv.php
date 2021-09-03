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
            case 'blacklist':
                Gestor_de_instancias::inicializar_instancia_actual();
                // var_dump(Application::$usuario->get_id());
                $view = $this->blacklist();
                break;
            case 'blacklist_create':
                $view = $this->blacklist_create($variables['id'],'create');
                break;
            case 'blacklist_create_post':
                $view = $this->blacklist_create_post($variables);
                break;
            case 'blacklist_edit':
                $view = $this->blacklist_create($variables['id'],'edit');
                break;
            case 'blacklist_edit_post':
                $view = $this->blacklist_create_post($variables);
                break;
            case 'filter':
                // exit;
                $view = $this->blacklist($variables);
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

        $agregar = $this->view->createElement('a');
        $agregar->setAttribute('class', 'btn-outline');
        $agregar->setAttribute('type', 'button');
        $agregar->setAttribute('name', "util_iv.blacklist_create");
        $agregar->appendChild($this->view->createTextNode("Agregar "));

        $icono_agregar = $this->view->createElement('i');
        $icono_agregar->setAttribute('class', 'fas fa-user-plus');
        $agregar->appendChild($icono_agregar);

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
        $acciones[] = array('etiqueta' => 'Editar', 'token' => $controller_name . '.blacklist_edit', 'id' => 'id_blacklist');
        $acciones[] = array('etiqueta' => 'checkbox', 'id' => 'id_blacklist', 'prefijo' => self::PREFIJO_CHECKBOXES);

        $tabla = new Table($array, null, null, $acciones);
        $tabla->cambiar_encabezados($labels);

        $this->colocar_checkbox_todo($recordset->RowCount(), $tabla);

        $div_100_tabla = $this->view->createElement('div');
        $div_contenedor = $this->view->createElement('div');
        $div_popup = $this->view->createElement('div');

        $div_100_tabla->setAttribute('class', 'content-100');
        $div_popup->setAttribute('class', 'content-100');
        $div_contenedor->setAttribute('class', 'contenedor-tabla');

        $div_contenedor->appendChild($this->view->importNode($tabla->documentElement, true));
        $div_100_encabezado->appendChild($this->view->importNode($encabezado->documentElement, true));
        $div_100_tabla->appendChild($div_contenedor);

        $div_100_encabezado->appendChild($agregar);

        $form->appendChild($div_100_encabezado);
        $form->appendChild($this->view->importNode($filters->documentElement, true));
        $form->appendChild($div_100_tabla);
        $form->appendChild($div_popup);
        

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

    //Carga la vista de filtros y termina de armar los input faltantes
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

    private function cargar_blacklist_para_editar(Blacklist $blacklist){
        $hiddenblacklist = $this->view->getElementById('id_blacklist');
        $hiddenblacklist->setAttribute('value',$blacklist->id_blacklist);

        $blacklist_select = Blacklist::select(array("id_blacklist"=>$blacklist->id_blacklist));

        foreach ($blacklist_select as $blackoption){
            $regla_blacklist = $blackoption['regla'];
            $comentario_blacklist = $blackoption['comentario'];
            $id_motivo_blacklist = $blackoption['id_motivo'];
            $id_authstat_blacklist = $blackoption['id_authstat'];
        }

        $input_regla = $this->view->getElementById('regla');
        $input_regla->setAttribute('value',Blacklist::procesarJSON($regla_blacklist));

        $input_motivo = $this->view->getElementById('motivo');
        $recordset = Motivos::select_blacklist();
        foreach($recordset as $row){
            $option = $this->view->createElement('option', $row['motivo']);
            $option->setAttribute('value',$row['id_motivo']);
            if($row['id_motivo']==$id_motivo_blacklist){
                $option->setAttribute('selected', 'selected');
            }
            $input_motivo->appendChild($option);
        }

        $input_comentario = $this->view->getElementById('comentario');
        $input_comentario->setAttribute('value',$comentario_blacklist);

        //Estados del input 
        $estados = array('activo'=>1,'inactivo'=>4);
        $input_estado = $this->view->getElementById('estado');
        foreach($estados as $estado => $valor){
            $option = $this->view->createElement('option', $estado);
            $option->setAttribute('value',$valor);
            if($valor==$id_authstat_blacklist){
                $option->setAttribute('selected', 'selected');
            }
            $input_estado->appendChild($option);
        }
        $div_estado = $this->view->getElementById('div_estado');
        $div_estado->removeAttribute('hidden');

        $btn = $this->view->getElementById('btn_create_blacklist');
        $btn->setAttribute('name','util_iv.blacklist_edit_post');

        return true;
    }

    private function blacklist_create($id_blacklist=null,$accion){
        $blacklist = new Blacklist();

        if($accion!='erase'){
            $this->view->cargar('views/util_iv.create.blacklist.html');
        
            $titulo = $this->view->getElementById('titulo_create');
        
            $texto_titulo = ($id_blacklist == null)?'Crear Regla':'Editar Regla';
            $titulo->appendChild($this->view->createTextNode($texto_titulo));
        }else{
            $blacklist->get($id_blacklist);
            $variables['id']=$blacklist->get_id_blacklist();
            $blacklist->borrar_regla($id_blacklist);
        }

        if($accion=='edit'){
            $blacklist->get($id_blacklist);
            unset($id_blacklist);
        }

        if($accion=='edit'){
            $this->cargar_blacklist_para_editar($blacklist);
        }else if($accion=='create'){

            $input_motivo = $this->view->getElementById('motivo');
            $recordset = Motivos::select_blacklist();
            foreach($recordset as $row){
                $option = $this->view->createElement('option', $row['motivo']);
                $option->setAttribute('value',$row['id_motivo']);
                $input_motivo->appendChild($option);
            }

            $inputBlacklist = $this->view->getElementById('id_blacklist');
            $inputBlacklist->setAttribute('value',$id_blacklist);
        }

        if($accion=='erase'){
            Gestor_de_log::set('Regla borrado correctamente', 0);
            developer_log('Borro Regla Correctamente',0);
            return $this->blacklist($variables);
        }else{
            return $this->view;
        }
    }

    private function blacklist_create_post($variables){
        var_dump($variables);
        if (isset($variables['regla']))
            $blacklist_regla['regla'] = $variables['regla'];
        unset($variables['regla']);
        if (isset($variables['motivo']))
            $blacklist_regla['motivo'] = $variables['motivo'];
        unset($variables['motivo']);
        if (isset($variables['comentario']))
            $blacklist_regla['comentario'] = $variables['comentario'];
        unset($variables['comentario']);
        $blacklist_regla['estado'] = (isset($variables['estado']))?$variables['estado']:'1';
        unset($variables['estado']);
        
        $blacklist = new Blacklist();
        if(isset($variables['id_blacklist'])){
            $blacklist->get($variables['id_blacklsit']);
        }
        $blacklist->set_comentario($blacklist_regla['comentario']);
        $blacklist->set_id_authstat($blacklist_regla['estado']);
        $blacklist->set_id_motivo($blacklist_regla['motivo']);
        $blacklist->set_regla(Blacklist::generarJSON($blacklist_regla['regla']));
        $blacklist->set_id_auth(Application::$usuario->get_id());

        if($blacklist->set()){
            if($variables['id_blacklist']){
                Gestor_de_log::set('Regla guardada correctamente',0);
            }else{
                Gestor_de_log::set('Regla generada correctamente',0);
            }
        }else{
            Gestor_de_log::set('No se guardo la regla',0);
        }
    return $this->blacklist();
    }
}
