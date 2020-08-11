<?php

// namespace Controllers;
class Meta_controller extends Controller {
    public static $vars;
    public function meta_dispatch($model, $nav, $variables) {
        session_start();
        $model = ucfirst($model);
        unset($variables["dataTable_length"]);
        if (!is_subclass_of($model, 'Model'))
            $nav = 'error';
        switch ($nav) {
            case 'home':
                $view = $this->home($model);
                break;
            case 'filter':
                $view = $this->home($model, $variables);
		if(empty($variables))
			session_destroy();
                else 
			guardar_variables_filtro($model,$variables);
                break;
            case 'create':
                $view = $this->create($model);
                break;
            case 'create_post':
                $view = $this->create_post($model, $variables);
                break;
            case 'edit':
                $view = $this->edit($model, $variables['id']);
                break;
            case 'edit_post':
                $view = $this->edit_post($model, $variables);
                break;
            case 'export_post':
                $view = $this->export_post($model, $variables);
                break;
            case 'delete':
            case 'delete_post':
            default:
                Gestor_de_log::set("Error en la navegación.", 0);
                $view = $this->home();
                break;
        }

        return $view;
    }

    private function home($model, $variables = null,$path=false) {
	# HACER ESTE UNSET NO ESTA BUENO
        if($variables==null){
            $variables= obtener_variables_de_filtro($model);
        }
        unset($variables['id']);
        $pagina_a_mostrar = 1;
        if (isset($variables['pagina'])) {
            $pagina_a_mostrar = $variables['pagina'];
            unset($variables['pagina']);
        }
        $controller_name = strtolower(get_class($this));
        $recordset = $model::select($variables);
        $model = strtolower($model);
        $pager = new Pager($recordset, $pagina_a_mostrar, $controller_name . '.' . $model . '.filter');
        $filters = new Filters($recordset, $variables, $controller_name . '.' . $model . '.filter');
        $acciones = array();
        $acciones[] = array('etiqueta' => 'Editar', 'token' => $controller_name . '.' . $model . '.edit', 'id' => $model::$id_tabla);
        #$acciones[]=array('etiqueta'=>'Eliminar','token'=>$controller_name.'.'.$model.'.delete','id'=>$model::$id_tabla);

        $table = new Table($recordset, $pager->desde_registro, $pager->hasta_registro, $acciones);
        $header = $this->view->createElement('header');

        $form = $this->view->createElement('form');
        $form->setAttribute('id', 'miFormulario');


        $row = $this->view->createElement('div');
        $row->setAttribute('class', 'row flex-middle');
        $d = $this->view->createElement('div');
        $d->setAttribute('class', 'col xs-8');

        $titulo = $this->view->createElement('h1');
        $titulo->setAttribute('class', 'text-light text-uppercase');
        $titulo->appendChild($this->view->createTextNode('Meta Controller | ' . ucfirst($model)));
        
        $d->appendChild($titulo);
       
        $d2 = $this->view->createElement('div');
        $d2->setAttribute('class', 'col xs-2');
        $header->appendChild($row);
        $row->appendChild($d);
        
        $button = $this->view->createElement('input');
        $button->setAttribute('type', 'button');
        $button->setAttribute('value', 'crear');
        $button->setAttribute('name', 'meta_controller.' . $model . '.create');
        $d2->appendChild($button);
        $button = $this->view->createElement('input');
        $button->setAttribute('type', 'button');
        $button->setAttribute('value', 'Exportar');
        $button->setAttribute('name', 'meta_controller.' . $model . '.export_post');
        $d2->appendChild($button);
        
        $row->appendChild($d2);
        $form->appendChild($header);

//        $form->appendChild($header);
        if($path){
            $div = $this->view->createElement('div');
            $a=$this->view->createElement("a","Descargar ". basename($path));
            $a->setAttribute('href', URL_DOWNLOAD. basename($path));
            $a->setAttribute('download', basename($path));
            $div->appendChild($a);
            $form->appendChild($div);
        }
        $form->appendChild($this->view->importNode($filters->documentElement, true));
        $form->appendChild($this->view->importNode($pager->documentElement, true));
        $form->appendChild($this->view->importNode($table->documentElement, true));
        $this->view->appendChild($form);
        return $this->view;
    }

    private function create($model) {

        if ($this->view->cargar('views/' . strtolower($model) . '.create.html'))
            return $this->view;
        return $this->meta_create($model);
    }

    private function meta_create($model) {

        return $this->meta_edit($model);
    }

    private function create_post($model, $variables) {
        if ($model == 'Moves') {
            Gestor_de_log::set('Operación no permitida.', 0);
            return $this->home($model, obtener_variables_de_filtro($model));
        }

        if (isset($variables[$model::$id_tabla]))
            unset($variables[$model::$id_tabla]);
        if (isset($variables['id']))
            unset($variables['id']);

        $objeto = new $model($variables);

        if ($objeto->set()) {
            Gestor_de_log::set('Ha insertado un registro de la tabla ' . $model . '.', 1);
        } else {
            Gestor_de_log::set('Ha ocurrido un error al insertar un registro en la tabla ' . $model . '.', 0);
        }
        return $this->home($model,obtener_variables_de_filtro($model));
    }

    private function edit($model, $id = null) {

        if ($this->view->cargar('views/' . strtolower($model) . '.edit.html'))
            return $this->view;
        else
            return $this->meta_edit($model, $id);
        #return $this->view;
    }

    private function meta_edit($model, $id = null) {
        # Si $id=null es un alta (viene de create)

        $form = $this->view->createElement('form');
        $form->setAttribute('id', 'miFormulario');
        $this->view->appendChild($form);
        $row = $this->view->createElement('div');
        $row->setAttribute('class', 'row flex-middle');
        $header= $this->view->createElement("header");
        $d = $this->view->createElement('div');
        $d->setAttribute('class', 'col xs-8');

        $titulo = $this->view->createElement('h1');
        $titulo->setAttribute('class', 'text-light text-uppercase');
        
        
       
        if ($id)
            $titulo->appendChild($this->view->createTextNode('Meta Controller | Editar registro de la tabla ' . $model));
        else
            $titulo->appendChild($this->view->createTextNode('Meta Controller | Crear registro de la tabla ' . $model));
         $d->appendChild($titulo);
       
        $d2 = $this->view->createElement('div');
        $d2->setAttribute('class', 'col xs-2');
        $header->appendChild($row);
        $row->appendChild($d);
        $row->appendChild($d2);
        $form->appendChild($header);
//        $div->setAttribute('class', 'titulo');
//        $header->appendChild($div);
        $submenu = $this->view->createElement('div');
        $form->appendChild($submenu);
        $page = $this->view->createElement('page');
        $form->appendChild($page);
        $ayuda = $this->view->createElement('div');
        $ayuda->setAttribute('class', 'ayuda');
        $page->appendChild($ayuda);

        $recordset = $model::select();

        if (!$recordset)
            return $this->view;

        if ($id) {
            $objeto = new $model();
            $registro_a_editar = $objeto->get($id);
        }
        $model = strtolower($model);
        $recorrer = array();
        $i = 0;
        while ($campo = $recordset->FetchField($i) AND $campo->name) {
            $recorrer[$campo->name] = '';
            $i++;
        }
        foreach ($recorrer as $columna => $valor):
            if (!is_numeric($columna) AND $columna != $model::$id_tabla) {
                #$meta=$recordset->FetchField($columna);
                $div = $this->view->createElement('div');
                $span = $this->view->createElement('span', $columna);
                $div->appendChild($span);
                $input = $this->view->createElement('input');
                #Switch para el tipo
                $input->setAttribute('type', 'text');

                $input->setAttribute('name', $columna);
                if ($id) {
                    $metodo = 'get_' . $columna; # HACER ESTE VALOR CONSTANTE EN CONFIG.PHP
                    if (method_exists($objeto, $metodo)) {
                        $input->setAttribute('value', $objeto->$metodo());
                    }
                }
                $div->appendChild($input);
                $page->appendChild($div);
            }
        endforeach;

        $div = $this->view->createElement('div');
        $button = $this->view->createElement('input');
        $button->setAttribute('type', 'button');
        $button->setAttribute('name', 'meta_controller.' . $model . '.home');
        $button->setAttribute('value', 'Cancelar');
        $button->setAttribute('class', 'cancelar');
        $div->appendChild($button);
        $button = $this->view->createElement('input');
        $button->setAttribute('type', 'submit');
        if ($id)
            $button->setAttribute('name', 'meta_controller.' . $model . '.edit_post');
        else
            $button->setAttribute('name', 'meta_controller.' . $model . '.create_post');
        $button->setAttribute('id', $id);
        $button->setAttribute('value', 'Aceptar');
        $button->setAttribute('class', 'aceptar');
        $div->appendChild($button);
        $page->appendChild($div);
        return $this->view;
    }

    private function edit_post($model, $variables) {

        if (!isset($variables['id'])) {
            Gestor_de_log::set('Ha ocurrido un error. Ningun registro ha sido seleccionado.', 0);
            return $this->home($model, obtener_variables_de_filtro($model));
        }
        $id_tabla = $model::$id_tabla;
        $variables[$id_tabla] = $variables['id'];
        unset($variables['id']);

        $objeto = new $model();
        if (!$objeto->get($variables[$id_tabla])) {
            Gestor_de_log::set('Ha ocurrido un error al modificar un registro en la tabla ' . $model . '. El registro no existe.', 0);
        } else {
            $objeto = new $model($variables);
            if ($objeto->set()) {
                Gestor_de_log::set('Ha modificado un registro de la tabla ' . $model . '.', 1);
                if (CONSOLA) {
                    $metodo = str_replace('::', '::' . $model . '::', __METHOD__);
                    return $metodo;
                }
            } else {
                Gestor_de_log::set('Ha ocurrido un error al modificar un registro en la tabla ' . $model . '.', 0);
            }
        }
        return $this->home($model, obtener_variables_de_filtro($model));
    }

    private function export_post($model, $variables) {

        $planilla = new Planilla();
        $recordset = $model::select($variables);
        $encabezado = array();
        $columnas = $recordset->FetchRow(0);
        foreach ($columnas as $columna => $valor):
            if (!is_numeric($columna))
                $encabezado[] = ucfirst($columna);
        endforeach;
        $planilla->cargar(array($encabezado));
        $planilla->cargar($recordset);
        $gestor_de_disco = new Gestor_de_disco();
        $path = '';
        $filename = date('Y-m-d-h-i-s_') . '_planilla_' . $model . '.xlsx';
        if (!$gestor_de_disco->exportar_xls($path, $filename, $planilla->get_filas())) {
            Gestor_de_log::set('Ha ocurrido un error al exportar la planilla.', 0);
            return $this->home($model, obtener_variables_de_filtro($model),$path);
        } else {
            Gestor_de_log::set('Ha exportado correctamente la planilla', 1);
            return $this->home($model, obtener_variables_de_filtro($model),$filename);
        }
    }

}
