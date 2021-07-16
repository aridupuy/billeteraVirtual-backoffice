<?php

// namespace Classes;
class Efectivo_digital extends Application {

    protected function dispatch($nav, $variables) {
        $nav = explode('.', $nav);
        $nav = $this->forzar_modulo($nav);
        if (ACTIVAR_LOG_SQL_CONTROLLERS)
            $tiempo_inicio = microtime(true);
        switch ($nav[0]):
            # MODULOS
            case 'main_controller':
                $mod = new Main_controller();
                $view = $mod->dispatch($nav[1], $variables);
                break;
            default:
//                $prefijo_ok = substr($nav[0], 0, 4) === 'util_';
                $prefijo_ok=substr($nav[0], 0,5)==='util_';
                $clase = ucfirst(strtolower($nav[0]));
                $clase_existe = class_exists($clase);
                $metodo_existe = method_exists($clase, 'dispatch');
                $herencia_correcta = false;
                if ($clase_existe) {
                    $reflector = new ReflectionClass($clase);
                    $herencia_correcta = $reflector->isSubclassOf('Controller');
                }

                if ($prefijo_ok AND $clase_existe AND $metodo_existe AND $herencia_correcta) {
                    $mod = new $clase();
                    $view = $mod->dispatch($nav[1], $variables);
                    if(CONSOLA){
                        return $view;
                    }
                    if(!$view  or (is_string($view) and strpos($view,"mod_")===false)){
                        developer_log("error 500");
                        $mod = new Main_controller();
                        $view = $mod->dispatch('404', '');
                    }
                } else {
                    $mod = new Main_controller();
//                    header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
                    if ($GLOBALS['SISTEMA'] == 'INTERNO')
                        $view = $mod->dispatch('navigation_error', '');
                    else
                        $view = $mod->dispatch('navigation_error_interfaz', '');
                }
                break;
        endswitch;

        if (ACTIVAR_LOG_SQL_CONTROLLERS) {
            $duracion = microtime(true) - $tiempo_inicio;
            if (class_exists(ucfirst(strtolower($nav[0])))) {
                Gestor_de_log::set_auto(strtoupper($nav[1]), strtolower($nav[0]), '', null, 1, $duracion);
            }
        }

        return $view;
    }

    private function forzar_modulo($nav) {
//		error_log(json_encode($nav));
        if (Application::$login AND defined('FORZAR_MODULO')) {
            list($modulo, $metodo) = explode('-', FORZAR_MODULO);
            if ($nav[0] != $modulo) {
                //	list($modulo,$metodo)=explode('-',FORZAR_MODULO);
                $nav[0] = $modulo;
                $nav[1] = $metodo;
                if ($nav[1] == null OR $nav[1] == 'index')
                    $nav[1] = 'home';
                # Habria que verificar que la funcion que se intenta llamar
                # no existe para el controller en cuestion
                if ($nav[1] == 'main_menu') 
                    $nav[1] = 'home';
                developer_log('Se fuerza la navegación al módulo: ' . $nav[0] . '.' . $nav[1] . ' ');
            }
            return $nav;
        }
        return $nav;
    }

    public function render_template($view, $nav = null) {
            developer_log("SISTEMA_".$GLOBALS['SISTEMA']);
        $documento = new View();
        if (Application::$usuario) {
            if ($GLOBALS['SISTEMA'] == 'INTERFAZ')
                $documento->cargar('template_interfaz.html');
            else
            $documento->cargar('template.html');
            $documento = $this->validar_menu($documento);
            $gestor_de_hash = $this->hacer_gestor_de_hash();
//            $documento = $this->appendMessages($documento);
            
            if ($GLOBALS['SISTEMA'] !== 'INTERFAZ' and Gestor_de_permisos::puede("acceso_total_cobro_digital")) {
                $bottom = $documento->getElementById("sidebar_bottom");
                $div1 = $documento->getElementById("div1");
                $developer_menu = $documento->createElement("div", "Menu Desarrollador");
                $developer_menu->setAttribute("class", "titulo-developer red bold");
                $developer_menu->setAttribute("type", "button");
                $developer_menu->setAttribute("name", "main_controller.developer_menu");
                $bottom->insertBefore($developer_menu, $div1);
            }
            $documento = $gestor_de_hash->mask($documento);

            $elemento = $documento->getElementById('sesion');
//            $recordset = Afuturo::select_sid_mercalpha_webnoti(Application::$usuario->get_id_marchand());
//            if($recordset->rowCount()==0){
//                if(Gestor_de_notificaciones::generar_sid(Application::$usuario->get_id_marchand()))
//                    $recordset = Afuturo::select_sid_mercalpha_webnoti(Application::$usuario->get_id_marchand());    
//            }
//            if ($recordset->rowCount() > 0) {
//                $row = $recordset->fetchRow();
//                $sid = $documento->getElementById('sid');
//                $mercalpha = $documento->getElementById('mercalpha');
//                $sid->setAttribute("data-value", $row["sid"]);
//                $mercalpha->setAttribute("data-value", $row["mercalpha"]);
//            }
            
            $nombre_usuario = $documento->getElementById("nombre_usuario");
            $area_usuario = $documento->getElementById("area_usuario");
            $cant_notif = $documento->getElementById("nro_noti");
            $minirs = $documento->getElementById("minirs");
//            var_dump(Application::$usuario);
//            $rs = Notificacion::select_notificaciones_marchand(Application::$usuario->get_id_marchand());
//            $marchand = new Marchand();
//            $marchand->get(Application::$usuario->get_id_marchand());
////            $r=$rs->fetchRow();
//            $nro = $rs->rowCount();
//            $nombre_usuario->appendChild($documento->createTextNode(Application::$usuario->get_completo()));
//            $area_usuario->appendChild($documento->createTextNode(Application::$usuario->get_userarea()));
//            $cant_notif->appendChild($documento->createTextNode($nro));
//            $minirs->appendChild($documento->createTextNode($marchand->get_apellido_rs() . "(" . $marchand->get_minirs() . ")"));

            if ($elemento) {
                $elemento->setAttribute('title', DATABASE_NAME);
                $elemento->appendChild($documento->createTextNode(Application::$usuario->getName()));
            }
        } else {
            $documento->cargar('template_no_login.html');
        }

        $main = $documento->getElementById('main');
        if ($main) {
           
            //$nodo=$documento->importNode($view->documentElement, true);
            
                $main->appendChild($documento->importNode($view->documentElement, true));
            
        }
//        var_dump($nav);
//        if($nav=="login" ){
//            var_dump("JSON");
//            return json_encode(array("view"=>$documento->saveHTML(),"reload"=>1));
//        }
//        else
        return $documento->saveHTML();
    }

    private function appendMessages(\View $view) {
        $forms = $view->getElementsByTagName('form');

        $div = $view->getElementById("home");
//        print_r($div);
        if ($div != null) {

            if (Gestor_de_log::ultimos_logs(1) !== false) {
                $view_mensaje = new View();
                $rsulog = Gestor_de_log::ultimos_ulogs(1);
                $view_mensaje->cargar("views/pop_mensaje_sistema.html");
                $mensaje = $view_mensaje->getElementById("texto");
                $pop = $view_mensaje->getElementById("mensaje_sistema");
                $pop->setAttribute("class", "contenedor-pop-up");
                if($mensaje->firstChild!=NULL){
                    
                    $mensaje->removeChild($mensaje->firstChild);
                }
                $mensaje->appendChild($view_mensaje->createTextNode(Gestor_de_log::ultimos_logs(1)[0]));
                $mensaje->setAttribute('title', Gestor_de_log::ultimos_logs(10)[0]);
                if ($rsulog->get_loglevel() == 1500) {
                    $boton_ok = $view_mensaje->getElementById("boton_pop_ok");
                    $boton_ok->setAttribute("name", "main_controller.index");
                }
//                $mensaje->setAttribute('class', 'mensaje_log');
                $div->appendChild($view->importNode($view_mensaje->documentElement, true));
            }
        } else {
            $form = $forms->item(0);
            if (Gestor_de_log::ultimos_logs(1) !== false) {
                $view_mensaje = new View();
                $rsulog = Gestor_de_log::ultimos_ulogs(1);
                $view_mensaje->cargar("views/pop_mensaje_sistema.html");
                $mensaje = $view_mensaje->getElementById("texto");
                $pop = $view_mensaje->getElementById("mensaje_sistema");
                $pop->setAttribute("class", "contenedor-pop-up");
                
                if($mensaje->firstChild!=NULL){
                    
                    $mensaje->removeChild($mensaje->firstChild);
                }
                $mensaje->appendChild($view_mensaje->createTextNode(Gestor_de_log::ultimos_logs(1)[0]));
                $mensaje->setAttribute('title', Gestor_de_log::ultimos_logs(10)[0]);
//                $mensaje->setAttribute('class', 'mensaje_log');
                if ($rsulog->get_loglevel() == 1500) {
                    $boton_ok = $view_mensaje->getElementById("boton_pop_ok");
                    $boton_ok->setAttribute("name", "main_controller.index");
                }
                if ($form)
                    $form->appendChild($view->importNode($view_mensaje->documentElement, true));
            }
        }
        return $view;
    }

    private function appendMessagesRender(View $view) {
        $forms = $view->getElementsByTagName('form');
        $div = $view->getElementById("home");
//                        print_r($div);
        if ($div != null) {
            if (Gestor_de_log::ultimos_logs(1) !== false) {
                $view_mensaje = new View();
                $view_mensaje->cargar("views/pop_mensaje_sistema.html");
                $mensaje = $view_mensaje->getElementById("texto");
                $pop = $view_mensaje->getElementById("mensaje_sistema");
                $pop->setAttribute("class", "contenedor-pop-up");
                if($mensaje->firstChild!=NULL){
                    
                    $mensaje->removeChild($mensaje->firstChild);
                }
                
                $mensaje->appendChild($view_mensaje->createTextNode(Gestor_de_log::ultimos_logs(1)[0]));
                $mensaje->setAttribute('title', Gestor_de_log::ultimos_logs(10)[0]);
                $rsulog = Gestor_de_log::ultimos_ulogs(1);
//                    $mensaje->setAttribute('class', 'mensaje_log');
                $imagen = $view_mensaje->getElementById("icono");
                switch ($rsulog->get_transaccion_correcta()) {
                    case 0 :
                        $imagen->setAttribute("src", "public/img/icono-importante.svg");
                        break;
                    case 1 :
                        $imagen->setAttribute("src", "public/img/icono-exito.svg");
                        break;
                }
                if ($rsulog->get_loglevel() == 1500) {
                    $boton_ok = $view_mensaje->getElementById("boton_pop_ok");
                    $botonera = $view_mensaje->getElementById("botonera");
                    $boton_ok_2 = $view_mensaje->createElement("a", "Ok");
                    $boton_ok_2->setAttribute("href", $_SERVER["ADDR"]);
                    $boton_ok_2->setAttribute("class", "button active");
                    $boton_ok->parentNode->removeChild($boton_ok);
                    $botonera->appendChild($boton_ok_2);
                }
                for ($i = 0; $i < $rsulog->get_loglevel(); $i++) {
                    $elemento = $view_mensaje->getElementById($i);
                    if ($elemento !== null) {
                        $elemento->parentNode->removeChild($elemento);
                    }
                }
                $div->appendChild($view->importNode($view_mensaje->documentElement, true));
            }
        } else
        if ($forms->length == 1) {
            $form = $forms->item(0);
            if (Gestor_de_log::ultimos_logs(1) !== false) {
                $view_mensaje = new View();
                $logs_1 = new Ulog();
                $logs = Gestor_de_log::ultimos_ulogs(1);
                $view_mensaje->cargar("views/pop_mensaje_sistema.html");
                $mensaje = $view_mensaje->getElementById("texto");
                if($mensaje->firstChild!=NULL){
                    
                    $mensaje->removeChild($mensaje->firstChild);
                }
                $mensaje_bajada = $view_mensaje->getElementById("bajada");
                $imagen = $view_mensaje->getElementById("icono");
                $pop = $view_mensaje->getElementById("mensaje_sistema");
                switch ($logs_1->get_transaccion_correcta()) {
                    case 0 :
                        $imagen->setAttribute("src", "public/img/icono-importante.svg");
                        break;
                    case 1 :
                        $imagen->setAttribute("src", "public/img/icono-exito.svg");
                        break;
                }


//                    public/img/icono-importante.svg
                $pop->setAttribute("class", "contenedor-pop-up");
                $mensaje->appendChild($view_mensaje->createTextNode($logs_1->get_mensaje()));
                $first_message = Gestor_de_log::ultimos_logs(10);
                $mensaje->setAttribute('title', $first_message[0]);
                $rsulog = Gestor_de_log::ultimos_ulogs(1);
                $mensaje_bajada->appendChild($view_mensaje->createTextNode($logs_1->get_dbmenso()));
                if ($logs_1->get_loglevel() == 1500) {
                    $boton_ok = $view_mensaje->getElementById("boton_pop_ok");
                    $boton_ok->parentNode->removeChild($boton_ok);
                    $boton_ok = $view_mensaje->createElement("a", "Ok");
                    $boton_ok->setAttribute("href", $_SERVER["ADDR"]);
                }
                for ($i = 0; $i < $logs_1->get_loglevel(); $i++) {
                    $elemento = $view_mensaje->getElementById($i);
                    if ($elemento !== null) {
                        $elemento->parentNode->removeChild($elemento);
                    }
                }
//                    $mensaje->setAttribute('class', 'mensaje_log');
                $form->appendChild($view->importNode($view_mensaje->documentElement, true));
            }
        }
        return $view;
    }

    protected function render($view, $nav = null, Gestor_de_hash $gestor_de_hash = null) {

        if (is_object($view) AND get_class($view) == 'View') {
            $view = $this->appendMessagesRender($view);
            if ($gestor_de_hash != null)
                $view = $gestor_de_hash->mask($view);
        }
//            var_dump($nav);
        if (Application::$template_cargado and ! Application::$usuario and $nav !== 'borrar') {
//            $nav = 'Cookie_vencida';
            developer_log("Reseteo pagina por vencimiento de cookie");
                $view = new View();
                $view->cargar('views/sesion_expirada.html');
                $gestor_de_hash = $this->hacer_gestor_de_hash();
//            var_dump($gestor_de_hash);
                if ($gestor_de_hash!= null)
                    $view= $gestor_de_hash->mask($view);
        }
        if ($nav == 'borrar') {
            developer_log("Json para logout retornado");
            Application::$json = true;
            return json_encode(array("view" => base64_encode($view->saveHTML()), "reload" => 1));
        } elseif ($nav == 'Cookie_vencida') {
            developer_log("alerta para logout retornado");
            Application::$json = true;
            return json_encode(array("view" => base64_encode($view->saveHTML()), "reload" => 2));
        }
        return $view->saveHTML();
    }

    protected function obtener_render_template($nav) {
        $render_template = false;
        if ($nav == 'main_controller.login_post' and ! self::$usuario)
            $render_template = true;
        if ($nav == 'main_controller.main_menu' and self::$usuario != false)
            $render_template = true;
        if ($nav == 'main_controller.index' and self::$usuario != false)
            $render_template = true;
        if ($nav == 'main_controller.logout_post' and self::$usuario != false)
            $render_template = false;
        if ($nav == 'main_controller.index' and self::$usuario == false)
            $render_template = true;
        
        return $render_template;
    }

    private function validar_menu(View $documento) {
        developer_log("VALIDAR MENU");
        $menu_principal = $documento->getElementById("menu_principal");
//        $herramientas_de_cobro = $documento->getElementById("herramientas_de_cobranzas");
//        $reporte_movimientos = $documento->getElementById("reporte_movimientos");
//        $retiros = $documento->getElementById("retiros");
//        $menu_mi_cuenta = $documento->getElementById("menu_mi_cuenta");
//        $menu_4 = $documento->getElementById("menu_4");
//        if (!Gestor_de_permisos::puede(Permiso::PUEDE_MENU_DATOS_CUENTA)) {
//            $menu_mi_cuenta->parentNode->removeChild($menu_mi_cuenta);
//        }
//        if (!Gestor_de_permisos::puede(Permiso::PUEDE_MENU_HERRAMIENTAS_COBRANZAS)) {
//            $herramientas_de_cobro->parentNode->removeChild($herramientas_de_cobro);
//        }
//        if (!Gestor_de_permisos::puede(Permiso::PUEDE_MENU_RETIROS)) {
//            $retiros->parentNode->removeChild($retiros);
//        }
//        if (!Gestor_de_permisos::puede(Permiso::PUEDE_MENU_MOVIMIENTOS)) {
//            $reporte_movimientos->parentNode->removeChild($reporte_movimientos);
//        }
//        $this->obtener_elementos_menu_0(Controlador_sitio::MENU_1, $menu_mi_cuenta, $documento);
        $this->obtener_menu_principal($menu_principal, $documento);
//        exit();
//        $elementos_menu_0 = $menu_mi_cuenta->childNodes;
//        $elementos_menu_1 = $herramientas_de_cobro->nextSibling->nextSibling->childNodes;
//        $elementos_menu_2 = $reporte_movimientos->nextSibling->nextSibling->childNodes;
//        $elementos_menu_3 = $retiros->nextSibling->nextSibling->childNodes;
        $eliminar = array();
//        foreach ($elementos_menu_0 as $i => $mod) {
//            if (get_class($mod) != "DOMText") {
//                $m = explode(".", $mod->getAttribute("name"));
//                $modulo = $m[0];
//                if (!Gestor_de_permisos::puede($modulo)) {
//                    $eliminar[] = $mod;
//                }
//            }
//        }
//        $eliminar = $this->obtener_modulos_prohibidos($elementos_menu_1, $eliminar);
//        $eliminar = $this->obtener_modulos_prohibidos($elementos_menu_2, $eliminar);
//        $eliminar = $this->obtener_modulos_prohibidos($elementos_menu_3, $eliminar);
//        foreach ($eliminar as $mod) {
//            try {
//                $mod->parentNode->removeChild($mod);
//            } catch (Exception $e) {
//                developer_log("Evito exepcion");
//            }
//        }
        return $documento;
    }

    private function obtener_modulos_prohibidos($elementos_menu, $eliminar) {
        foreach ($elementos_menu as $mod) {
            if (get_class($mod) != "DOMText") {
                $modl = $mod->childNodes->item(0);
                if ($modl->nodeName == "div") {
                    $m = explode(".", $modl->getAttribute("name"));
                    if ($m[0] == "") {
                        $modl = $modl->nextSibling;
                        if ($modl)
                            $modl = $modl->nextSibling;
                        if ($modl and $modl->nodeName == "ul") {
                            $modl = $modl->childNodes;
                            foreach ($modl as $a) {
                                if (get_class($a) != "DOMText") {
                                    $div = $a->childNodes->item(0);
                                    $m = explode(".", $div->getAttribute("name"));
                                    if (!Gestor_de_permisos::puede($m[0]) and ! Gestor_de_permisos::esta_publicado($m[0])) {
                                        $eliminar[] = $div;
                                    }
                                }
                            }
                        }
                    } else {
                        $modulo = $m[0];
                        if (!Gestor_de_permisos::puede($modulo) and ! Gestor_de_permisos::esta_publicado($modulo)) {
                            $eliminar[] = $mod;
                        }
                    }
                }
            }
        }
        return $eliminar;
    }

    private function obtener_elementos_menu_0($ordi, $destino, View $view) {
        $rs_site = Controlador_sitio::select_menu($ordi);
//        var_dump($rs_site->rowCount());
        foreach ($rs_site as $site) {
            $site = new Controlador_sitio($site);
            $div = $view->createElement("div", $site->get_ds_descrip());
            $div->setAttribute("class", "button");
            $div->setAttribute("type", "button");
            $name = str_replace("javascript: dispatch('", "", str_replace("')", "", $site->get_ds_dothis()));
            $n = explode($name, ".");
            if (count($n) == 1) {
                $name = $name . ".home";
            }
            $div->setAttribute("name", $name);
            $destino->appendChild($div);
        }
    }

    private function obtener_elementos_menu_lateral($ordi, $destino, View $view, $sub = false) {
        $rs_site = Controlador_sitio::select_menu($ordi);
        foreach ($rs_site as $site) {
            $site = new Controlador_sitio($site);
            $permiso = new Permiso();
            $permiso->get($site->get_id_permiso());
            if (!Gestor_de_permisos::puede($permiso->get_puede()))
                continue;

            $a = $view->createElement("li");
            if (!$site->get_submenu()) {
                $a->setAttribute("class", "second");
                $div = $view->createElement("div");
                $a->appendChild($div);
                $div->setAttribute("class", "button");
                $div->setAttribute("type", "button");
                $name = str_replace("javascript: dispatch('", "", str_replace("')", "", $site->get_ds_dothis()));
                $n = explode(".", $name);
                if (count($n) == 1) {
                    $name = $name . ".home";
                }
                $div->setAttribute("name", $name);
                $div->appendChild($view->createTextNode($site->get_ds_descrip()));
                $destino->appendChild($a);
            } else {
                //developer_log($site->get_submenu());
                $a->setAttribute("class", "second trigger-third");
                $rs_site2 = Controlador_sitio::select_menu($site->get_submenu());

                $div = $view->createElement("div", $site->get_ds_descrip());

                $a->appendChild($div);
                $ul2 = $view->createElement("ul");
                $ul2->setAttribute("class", "acordeon");
                $a->appendChild($ul2);
                $destino->appendChild($a);
//                var_dump($site->get_ds_descrip());
//                exit();
                foreach ($rs_site2 as $row) {
//                    var_dump($row);
                    $site2 = new Controlador_sitio($row);
                    $a2 = $view->createElement("li");
                    $a2->setAttribute("class", "third");
                    $div2 = $view->createElement("div");
                    $a2->appendChild($div2);
                    $div2->setAttribute("class", "button");
                    $div2->setAttribute("type", "button");
                    $name = str_replace("javascript: dispatch('", "", str_replace("')", "", $site2->get_ds_dothis()));
                    $n = explode(".", $name);
                    if (count($n) == 1) {
                        $name = $name . ".home";
                    }
                    $div2->setAttribute("name", $name);
                    $div2->appendChild($view->createTextNode($site2->get_ds_descrip()));
//                    $a2->appendChild($newnode)
                    $ul2->appendChild($a2);
                }
//                exit();
            }
        }
//        exit(); 
    }

    private function obtener_menu_principal($destino, View $documento) {
        developer_log("Menu principal");
        $primero_sub = true;
        $rs_menu = Controlador_sitio::select_menu_principal();
        
        foreach ($rs_menu as $menu) {
//            developer_log("####### VUELTA");
            $site = new Controlador_sitio($menu);
            $a = $documento->createElement("a");
            $div = $documento->createElement("div");
            $div->setAttribute("class", "button");
            $div->setAttribute("type", "button");
            $a->appendChild($div);
            $span = $documento->createElement("span");
            $span2 = $documento->createElement("span");
//            $img = $documento->createElement("img");
//            $span->appendChild($img);
            $span->setAttribute("class", "icono-sidebar");
            $span2->setAttribute("class", "txt");
            $div->appendChild($span);
            $div->appendChild($span2);
            $ul = $documento->createElement("ul");
            $ul->setAttribute("class", "desplegable");
            $a->appendChild($ul);
            $permiso = new Permiso();
            $permiso->get($site->get_id_permiso());
            if (Gestor_de_permisos::puede($permiso->get_puede())){
                switch ($permiso->get_puede()) {
                    case Permiso::PUEDE_MENU_DASHBOARD:
                        $a->setAttribute("class", "primary herramientas-cobranza");
                        $a->setAttribute("type", "button");
                        $a->setAttribute("name", "main_controller.dashboard");

//                        $img->setAttribute("src", "public/img_2/icono-redes-cobranza.png");
                        $span2->appendChild($documento->createTextNode("DashBoard"));

                        $this->obtener_elementos_menu_lateral($site->get_ordi(), $ul, $documento);
                        break;
                    case Permiso::PUEDE_MENU_USUARIOS:
                        //sordi 20
                        $a->setAttribute("class", "primary  reportes-movimientos");
                        $a->setAttribute("type", "button");
                        $a->setAttribute("name", "util_i.home");
//                        $img->setAttribute("src", "public/img/icono-rep-movimientos.svg");
                        $span2->appendChild($documento->createTextNode("Usuarios"));
                        $this->obtener_elementos_menu_lateral($site->get_ordi(), $ul, $documento);
                        break;
                    case Permiso::PUEDE_MENU_RETIROS:
                        //sordi 30
                        $a->setAttribute("class", "primary  retiros-de-fondos");
//                        $img->setAttribute("src", "public/img/icono-ret-fondos.svg");
                        $span2->appendChild($documento->createTextNode("Retiros de Fondos / Pagos"));
                        $this->obtener_elementos_menu_lateral($site->get_ordi(), $ul, $documento);
                        break;
                }
            }
            $destino->appendChild($a);
        }
//        <li class="primary herramientas-cobranza">
//                        <div class="button" type="button" name="" >
//                            <span class="icono-sidebar"><img src="public/img/icono-herr-cobranza.svg"></span>
//                            <span class="txt">Herramientas de Cobranza</span></div>				
//                        <ul class="desplegable">
//                            <li class="second"><div class="button" type="button" name="mod_xlix.home">Cupones de pago</div></li>
//                            <li class="second"><div class="button" type="button" name="mod_xix.home">Cobrar por correo</div></li>
//                            <li class="second"><div class="button" type="button" name="mod_xxi.home">Débito automático</div></li>
//                            <li class="second trigger-third"><div class="button" >Boletas de pago</div>
//                                <ul class="acordeon" id="menu_4">
//                                    <li class="third"><div class="button" type="button" name="mod_xiv.home">Generar boletas en lote</div></li>
//                                    <li class="third"><div class="button" type="button" name="mod_xxv.home">Generar debitos en lote</div></li>
//                                    <li class="third"><div class="button" type="button" name="mod_xl.home">Consultar boletas anteriores</div></li>
//                                    <li class="third"><div class="button" type="button" name="mod_xl.create">Nueva boleta</div></li>
//                                    <li class="third"><div class="button" type="button" name="">Enviar liquidaciones</div></li>
//                                    <li class="third"><div class="button" type="button" name="">Ver instructivo</div></li>
//                                </ul>
//                            </li>
//                            <li class="second"><div class="button" type="button" name="mod_xxxvii.home">Tarjetas de Cobranza / Códigos de barras</div></li>
//                            <li class="second"><div class="button" type="button" name="mod_xxviii.home">Botón de pago</div></li>
//                            <li class="second desarrolladores"><div class="button" type="button" name="">Herramienta para desarrolladores</div></li>
//                        </ul>
//                    </li>
    }

}
