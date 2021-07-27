<?php

class Util_iv extends Controller {

    public static $nombre = 'Administracion';public static 
            $modulo = "util_iv";
    public static $mensaje = "Acá podés ver el panel de Administracion.";

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
        $recordset = Blacklist::select_blacklist($variables=null);
//        $pager = new Pager($recordset, $pagina_a_mostrar, $controller_name . '.filter');
        $filters = $this->preparar_filtros($variables);
//        $acciones = array();
//        $acciones[] = array('etiqueta' => Table::INTERRUPTOR, 'campo' => 'id_authstat', 'token' => $controller_name . '.cambiar_estado', 'id' => 'id_clima');
//        $acciones[] = array('etiqueta' => 'Editar', 'token' => $controller_name . '.edit', 'id' => 'id_clima');
        // $acciones[] = array('etiqueta' => 'Ver lista de CBU', 'token' => 'Mod_xxiii.filter', 'id' => 'id_clima');
//        list($array, $labels) = $this->preparar_array($recordset, $pager->desde_registro, $pager->hasta_registro);
        $form = $this->view->createElement('form');
        $form->setAttribute('id', 'miFormulario');
        $form->setAttribute('class', 'main-content usuarios');
        $div_80 = $this->view->createElement('div');
        $total_usr = 'Total: 1500';
        $encabezado = new Encabezado(self::$modulo, $total_usr);
        $div_80->setAttribute('class', 'content-80');
        $div_80->appendChild($this->view->importNode($encabezado->documentElement, true));
        
        $detalle = new Detalle("nombre_completo");
        $detalle->preparar_arrays($recordset);
        
        $acciones[] = (new Accion())->set_campo_id("id_clima")->set_nav("util_iv.ver_mas")->set_titulo_nav("Ver más");
        $acciones[] = (new Toggle())->set_nav_estado("util_iv.cambiar_estado")->set_id_estado("id_authstat")->set_campo_id("id_authstat");
        
        $container = new Table($recordset,1, 100);
        
        $div_cajita = $this->view->createElement('div');
        $div_cajita->setAttribute('class', 'contenedor');
        $div_cajita->appendChild($this->view->importNode($container->documentElement, true));
        
        $div_80->appendChild($div_cajita);
        
        $form->appendChild($div_80);
        $form->appendChild($this->view->importNode($filters->documentElement, true));
        
        $this->view->appendChild($form);
        return $this->view;
    }
    
    private function preparar_filtros($variables) {
        $filter = new view();
        if (isset($variables['id']))
            unset($variables['id']);
        $filter->cargar("views/util_iv.filters.html");
        $filter->cargar_variables($variables);
        return $filter;
    }

    

}
