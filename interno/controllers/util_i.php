<?php

class Util_i extends Controller {

    public static $nombre = 'Usuarios';public static 
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
        # Revisar esto, no postear id_marchand
//        $input = $this->view->createElement('input');
//        $input->setAttribute('type', 'button');
//        $input->setAttribute('value', 'Nuevo');
//        $input->setAttribute('name', 'mod_xxii.create');
        $controller_name = strtolower(get_class($this));
        $recordset = Usuario::select_usuarios($variables=null);
//        $pager = new Pager($recordset, $pagina_a_mostrar, $controller_name . '.filter');
        $filters = $this->preparar_filtros($variables);
//        $acciones = array();
//        $acciones[] = array('etiqueta' => Table::INTERRUPTOR, 'campo' => 'id_authstat', 'token' => $controller_name . '.cambiar_estado', 'id' => 'id_clima');
//        $acciones[] = array('etiqueta' => 'Editar', 'token' => $controller_name . '.edit', 'id' => 'id_clima');
        // $acciones[] = array('etiqueta' => 'Ver lista de CBU', 'token' => 'Mod_xxiii.filter', 'id' => 'id_clima');
//        list($array, $labels) = $this->preparar_array($recordset, $pager->desde_registro, $pager->hasta_registro);
//        foreach ($recordset as $value) {
            
//        print_r("<pre>");
//        print_r($value);
//        print_r("<pre>");
//        }
        # CAPTURAR arrray==false
//        $labels = array("Apellido", "Nombre", "CUIT/CUIL/DNI", "E-mail", "Estado");
        
//        $encabezados = $labels;
//        $table->cambiar_encabezados($encabezados);
        $form = $this->view->createElement('form');
        $form->setAttribute('id', 'miFormulario');
        $form->setAttribute('class', 'main-content usuarios');
        $section = $this->view->createElement('section');
//        $nav = 'create';
//        $value = 'NUEVO';
        $encabezado = new Encabezado(self::$modulo, self::$mensaje);
//        $section->setAttribute('class', 'main-content usuarios');
        $section->appendChild($this->view->importNode($encabezado->documentElement, true));
//        $header = $this->view->createElement('header');

//        $titulo = $this->view->createElement('div');
//        $titulo->setAttribute('class', 'titulo');
//        $titulo->appendChild($this->view->createTextNode(self::$nombre));
//        $header->appendChild($titulo);
//        $section->appendChild($header);
        $section->appendChild($this->view->importNode($filters->documentElement, true));
//        $section->appendChild($this->view->importNode($pager->documentElement, true));
//        $section->appendChild($this->view->importNode($table->documentElement, true));
//        $section->appendChild($input);
        
        $detalle = new Detalle("nombre_completo");
//        var_dump($recordset);
        $detalle->preparar_arrays($recordset);
        $acciones[] = (new Accion())->set_campo_id("id_clima")->set_nav("util_i.ver_mas")->set_titulo_nav("Ver más");
        $acciones[] = (new Toggle())->set_nav_estado("util_i.cambiar_estado")->set_id_estado("id_authstat")->set_campo_id("id_authstat");
//        var_dump($detalle);
        $container = new Container($recordset, $detalle, $acciones);
//                $container->eliminar_columna(0);
                $container->eliminar_columna(2);
                $container->eliminar_columna(3);
                $container->eliminar_columna(4);
                $container->eliminar_columna(6);
                $container->eliminar_columna(9);
                $container->eliminar_columna(10);
                $container->eliminar_columna(11);
        $container->crear_desde_recordset();
        $div_contenido = $this->view->createElement('div');
       
        $div_cajita = $this->view->createElement('div');
        $div_cajita->setAttribute('class', 'contenido contenedor-cuentas-bancarias');
        $div_cajita->appendChild($this->view->importNode($container->documentElement, true));
        $section->appendChild($div_cajita);
        $form->appendChild($section);
        $this->view->appendChild($form);
        return $this->view;
    }
    
    private function preparar_filtros($variables) {
        $filter = new view();
        if (isset($variables['id']))
            unset($variables['id']);
        $filter->cargar("views/util_i.filters.html");
        $filter->cargar_variables($variables);
        return $filter;
    }

    

}
