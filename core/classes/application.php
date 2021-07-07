<?php

// namespace Classes;
abstract class Application {

    public static $usuario = false;
    public static $template_cargado = false;
    public static $login = false; # LOGIN_DE_USUARIO_INTERNO si es login desde boffice, LOGIN_DE_USUARIO_EXTERNO si es desde el sitio, false si no esta logeado
    public static $instancia = false; # El numero de ejecucion del usuario. Es decir, uno por cada pestaña (por.ej.)
    public static $no_log_navegador = false;
    public static $json = false; //variable que determina la salida

    const USUARIO_INTERNO_LOG_NAVEGADOR = 67;
    const USUARIO_EXTERNO_LOG_NAVEGADOR = 4067;

    public  function __construct($id_usuario_cookie = null) {
        switch (get_called_class()) {
            case 'Back_office':
                if ($id_usuario_cookie == null OR ! $id_usuario = Gestor_de_cookies::get($id_usuario_cookie, LOGIN_DE_USUARIO_INTERNO))
                    return false;
                $resultado = $this->login_de_usuario_interno($id_usuario);
                break;
            case 'Efectivo_digital':
                if ($id_usuario_cookie == null OR ! $id_usuario = Gestor_de_cookies::get($id_usuario_cookie, LOGIN_DE_USUARIO_EXTERNO))
                    return false;
                $resultado = $this->login_de_usuario_interno($id_usuario);
                break;
            default: exit();
                break;
        }
        return $resultado;
    }
    public  function fabrica($usuario) {
        $application=new Back_office();
        $resultado = $application->login_de_usuario_interno($usuario->get_id_auth());
	error_log("Clase fabricada! ".get_class($application));
	//developer_log(get_called_class());
        return $application;
    }
    protected final function login_de_usuario_interno($id_auth) {
        #Login desde BackOffice#
        self::$usuario = new Auth();

        if (self::$usuario->get($id_auth)) {
            self::$login = LOGIN_DE_USUARIO_INTERNO;
            return true;
        } else {
            self::logout();
            return false;
        }
    }

   
    public final static function logout() {
        self::$login = false;
        Gestor_de_cookies::destruir();
        self::$usuario = false;
        self::$instancia = false;
        return true;
    }

    public function navigate($nav, $variables) {
        # Solo usuarios logeados

        $gestor_de_hash = $this->hacer_gestor_de_hash();

        $tratar_variables = true;
        if ($nav === null) {
            $render_template = false;
//            developer_log(json_encode($variables));
            $nav = 'main_controller.index';
            if ((isset($variables['id']) and $gestor_de_hash->descifrar($variables['id']) == 'logout_post') OR ( $variables['logout_post'] == 'logout_post')) {
                if (ACTIVAR_LOG_APACHE_LOGIN)
                    developer_log('El usuario intenta cerrar sesion.');
                $nav = 'main_controller.logout_post';
            }
            else
            if (isset($variables['login_post'])) {
                if (!Application::$usuario) {
                    if (ACTIVAR_LOG_APACHE_LOGIN)
                        developer_log('El usuario intenta iniciar sesion.');
                    $tratar_variables = false;
                    $nav = 'main_controller.login_post';
                    $render_template = true;
                }
                else {
                    if (ACTIVAR_LOG_APACHE_LOGIN)
                        developer_log('El usuario reenvía los datos de inicio de sesion. Redireccion.');
                    if ($gestor_de_hash->descifrar($nav) != "main_controller.logout")
                        $nav = 'main_controller.main_menu';
                    else {
                        $render_template = true;
                    }
                }
            }
        } else {
            if($nav === 'main_controller.contacto_mail_front')
                $tratar_variables = false;
            $render_template = false;
            if ($gestor_de_hash)
                $nav = $gestor_de_hash->descifrar($nav);
        }

        $render_template = $this->obtener_render_template($nav);
//        var_dump($render_template);
        if ($tratar_variables)
            $variables = $this->tratamiento_de_variables($variables);
        if ($gestor_de_hash)
            $variables = $gestor_de_hash->unmask($variables);
        if (isset($variables["hidden_instancia"])) {
            self::$template_cargado = true;
            unset($variables["identificador_template"]);
        }
        $nav = strtolower($nav);
        if (ACTIVAR_LOG_APACHE_LOGIN) {
            if (Application::$usuario)
                developer_log(' [IDU:' . self::$usuario->get_id() . '| IDM:' . self::$usuario->get_id_marchand() . '] Navega: ' . $nav);
            else {
//                $nav="main_controller.index";
                developer_log('Usuario: No Logeado ' . 'Navega: ' . $nav);
            }
        }
        if (($variables = $this->administrar_instancias($variables, $nav)) === false) {
            Gestor_de_log::set('Ha ocurrido un error fatal al integrar las instancias.', 0);
            exit();
        }
        if (isset($variables[NOMBRE_HIDDEN_INSTANCIA]))
            unset($variables[NOMBRE_HIDDEN_INSTANCIA]);
        if (ACTIVAR_LOG_INSTANCIAS)
            developer_log('La instancia de ejecucion es: ' . Application::$instancia . '.');
        ##############################################
        $nav = $this->controlar_permisos($nav);
        ##############################################
        #TEMP
        if (Application::$usuario) {
            if (( self::$login == LOGIN_DE_USUARIO_INTERNO AND Application::$usuario->get_id() == self::USUARIO_INTERNO_LOG_NAVEGADOR and ! self::$no_log_navegador)OR ( self::$login == LOGIN_DE_USUARIO_EXTERNO AND Application::$usuario->get_id() == self::USUARIO_EXTERNO_LOG_NAVEGADOR and ! self::$no_log_navegador)) {
                $GLOBALS['ACTIVAR_LOG_NAVEGADOR'] = '1';
            }
        }
        ##############################################
        try {
//            var_dump($nav);
            $view = $this->dispatch($nav, $variables);
        } catch (Exception $e) {
            error_log($e->getTraceAsString());
            Gestor_de_log::set_exception($e->getMessage());
            developer_log('Excepciones del sistema: ' . $e->getMessage());
        }
        ##############################################
        unset($variables);

        if ($nav == 'main_controller.login_post' AND Application::$usuario) {
            if (ACTIVAR_LOG_APACHE_LOGIN)
                developer_log('El usuario acaba de iniciar sesion.');
            $gestor_de_hash = $this->hacer_gestor_de_hash();
        }
        if ($nav == 'main_controller.login_post' AND ! Application::$usuario) {

            $render_template = false;
        }

        if ($nav == 'main_controller.logout_post' AND ! Application::$usuario) {
            if (ACTIVAR_LOG_APACHE_LOGIN)
                developer_log('El usuario acaba de cerrar sesion.');
            Application::$instancia = NULL;
            $gestor_de_hash = false;
            $render_template = true;
        }
        if ($gestor_de_hash) {
            if (!is_array($view))
                $view = $gestor_de_hash->mask($view);
        }

        if (CONSOLA) {
            if (!CONSOLA and (is_object($view) OR !is_string($view) OR !json_decode($view))){
                
                return false;
            }
            # View tiene el nombre del controller cuando se ejecuta correctamente
            return $view;
        }
        elseif (is_object($view) AND get_class($view) == 'View') {
            if ($nav == 'main_controller.logout_post' and get_class($this) == 'Efectivo_digital') {
                return $this->render($view, "borrar");
            }
            if ($render_template) {
                return $this->render_template($view, $nav);
            } else
                return $this->render($view, $nav);
        }
        
//        var_dump($render_template);
        return false;
    }

    protected abstract function dispatch($nav, $variables);

    protected function render_template($view) {
        $documento = new View();
        developer_log("SISTEMA_".$GLOBALS['SISTEMA']);
        if (Application::$usuario) {
            
                $documento->cargar('template.html');
            $gestor_de_hash = $this->hacer_gestor_de_hash();
            $documento = $gestor_de_hash->mask($documento);
            $elemento = $documento->getElementById('sesion');
            if ($elemento) {
                $elemento->setAttribute('title', DATABASE_NAME);
                $elemento->appendChild($documento->createTextNode(Application::$usuario->getName()));
            }
        } else {
            $documento->cargar('template_no_login.html');
        }
        $forms = $view->getElementsByTagName('form');
        $div = $view->getElementById("home");
//        print_r($div);
        if ($div != null) {
            if (Gestor_de_log::ultimos_logs(1) !== false) {
                $mensaje = $view->createElement('a', Gestor_de_log::ultimos_logs(1));
                $mensaje->setAttribute('title', Gestor_de_log::ultimos_logs(10));
                $mensaje->setAttribute('class', 'mensaje_log');
                $div->appendChild($mensaje);
            }
        } else {
            $form = $forms->item(0);
            if (Gestor_de_log::ultimos_logs(1) !== false) {
                $mensaje = $view->createElement('a', Gestor_de_log::ultimos_logs(1));
                $mensaje->setAttribute('title', Gestor_de_log::ultimos_logs(10));
                $mensaje->setAttribute('class', 'mensaje_log');
                $form->appendChild($mensaje);
            }
        }
        $main = $documento->getElementById('main');
        if ($main) {
            //$nodo=$documento->importNode($view->documentElement, true);
            
                $main->appendChild($documento->importNode($view->documentElement, true));
        }
        return $documento->saveHTML();
    }

    protected function render($view) {
        if (is_object($view) AND get_class($view) == 'View') {
            $forms = $view->getElementsByTagName('form');
            $div = $view->getElementById("home");
//                        print_r($div);
            if ($div != null) {
                if (Gestor_de_log::ultimos_logs(1) !== false) {
                    $mensaje = $view->createElement('a', Gestor_de_log::ultimos_logs(1));
                    $mensaje->setAttribute('title', Gestor_de_log::ultimos_logs(10));
                    $mensaje->setAttribute('class', 'mensaje_log');
                    $div->appendChild($mensaje);
                }
            } else
            if ($forms->length == 1) {
                $form = $forms->item(0);
                if (Gestor_de_log::ultimos_logs(1) !== false) {
                    $mensaje = $view->createElement('a', Gestor_de_log::ultimos_logs(1));
                    $mensaje->setAttribute('title', Gestor_de_log::ultimos_logs(10));
                    $mensaje->setAttribute('class', 'mensaje_log');
                    $form->appendChild($mensaje);
                }
            }

            return $view->saveHTML();
        }
    }

    protected final function tratamiento_de_variables($variables) {

        $temp = array();
        if (isset($variables['data'])) {
            $explotado = explode('&', $variables['data']);

            foreach ($explotado as $unaVariable):
                $array = explode('=', $unaVariable);
                unset($clave);
                unset($valor);
                if (isset($array[0]))
                    $clave = $array[0];
                if (isset($array[1]) AND $array[1] !== '') {
                    $aux = str_replace('+', ' ', $array[1]);
                    $valor = trim(rawurldecode($aux));
                    unset($aux);
                } else {
                    # YA NO SE DESCARTAN LOS CAMPOS VACIOS 
                    # (Se descartan al levantar variables en el gestor de instancias)
                    $valor = '';
                }
                if (isset($temp[$clave]) AND isset($valor)) {
                    # Para soportar multiples $_POST con el mismo Name
                    if (!is_array($temp[$clave])) {
                        $aux = $temp[$clave];
                        unset($temp[$clave]);
                        $temp[$clave] = array();
                        $temp[$clave][] = trim($aux);
                    }

                    $temp[$clave][] = trim($valor);
                } else if (isset($clave) AND isset($valor))
                    $temp[$clave] = trim($valor);
                unset($array);
            endforeach;
        }
        // if (isset($variables['alta_post'])) {
        //     return $variables;
        // }
        if (isset($variables['id']) AND $variables['id'] !== '') {
            $temp['id'] = $variables['id'];
        }
        if (isset($variables['pagina']) AND $variables['pagina'] !== '') {
            if (is_numeric($variables['pagina']) AND $variables['pagina'] > 0)
                $temp['pagina'] = (int) $variables['pagina'];
        }

        return $temp;
    }

    protected final function hacer_gestor_de_hash() {

        if (Application::$usuario) {
            if (get_class(Application::$usuario) == 'Auth')
                $id_authcode = 1;
            if (!isset($id_authcode))
                return false;
            $recordset = Useris::select(array('id_auth' => Application::$usuario->get_id(), 'id_authcode' => $id_authcode));

            if (!$recordset OR $recordset->RowCount() != 1)
                return false;
            $row = $recordset->FetchRow();
            $useris = new Useris($row);
            $clave_de_cifrado = self::$usuario->getPassword() . $useris->get_fecha()  . $useris->get_sesion();
            $gestor_de_hash = new Gestor_de_hash($clave_de_cifrado);
            return $gestor_de_hash;
        }
        return false;
    }

    private final function administrar_instancias($variables, $nav) {
        $nav = explode('.', $nav);
        $nombre_controller = $nav[0];
        # Setear la variable $instancia
        if (isset($variables[NOMBRE_HIDDEN_INSTANCIA]) AND is_numeric($variables[NOMBRE_HIDDEN_INSTANCIA])) {
            if (isset($nav[1]) AND $nav[1] != 'login_post') {
                Application::$instancia = $variables[NOMBRE_HIDDEN_INSTANCIA];
            } else {
                return $variables;
            }
        } else {
            if (Application::$login) {
                if ((Application::$instancia = Gestor_de_instancias::crear_instancia()) === false) {
                    developer_log('Ha ocurrido un error al crear la siguiente instancia.');
                    return false;
                } elseif (ACTIVAR_LOG_INSTANCIAS) {
                    developer_log('Se ha creado la instancia numero ' . Application::$instancia . '.');
                }
            } else
                return $variables;# El usuario no esta logeado, no hay instancias.
        }
        # El usuario ya esta logeado y debo integrar las instancias
        if (($variables = Gestor_de_instancias::integrar_instancia($variables, Application::$instancia, $nombre_controller)) === false) {
            developer_log('Ha ocurrido un error al integrar la instancia ' . Application::$instancia . '.');
            return false;
        } else {
            if (ACTIVAR_LOG_INSTANCIAS)
                developer_log('Se han integrado las variables de la instancia ' . Application::$instancia . '.');
            return $variables;
        }
        return false; # En cualquier otro caso.
    }

    private final function controlar_permisos($nav) {
        if (!ACTIVAR_PERMISOS)
            return $nav;
        $mensaje = 'Acceso denegado';
        $nav_array = explode('.', $nav);
        if ($nav_array[0] == 'main_controller') {
            # No hace falta nada
            # Se controla dentro del main_controller
        } elseif ($nav_array[0] == 'meta_controller') {
            if (!Application::$login OR ! Gestor_de_permisos::puede('acceso_meta')) {
                Gestor_de_log::set($mensaje, 0);
                $nav = 'main_controller.index';
            }
        } else {
            # Utilidades o Mods
            if (!Application::$login OR ! Gestor_de_permisos::puede($nav_array[0])) {
                Gestor_de_log::set($mensaje, 0);
                $nav = 'main_controller.index';
            }
        }
        return $nav;
    }

}
