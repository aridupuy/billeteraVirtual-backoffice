<?php
// namespace Controllers;
class Main_controller extends Controller{
	
	public function dispatch($nav, $variables) {
        switch ($nav) {
            case 'index':
                $view = $this->index();
                break;
            case 'login':
                $view = $this->login();
                break;
            case 'login_post':
                $view = $this->login_post($variables);
                break;
            case 'main_menu':
                $view = $this->main_menu();
                break;
            case 'logout':
                $view = $this->logout();
                break;
            case 'logout_post':
                $view = $this->logout_post();
                break;
            case 'navigation_error_interfaz':
//                $view = $this->navigation_error_interfaz(); //para la pag vieja
                $view = $this->navigation_error();
                break;
            case 'dashboard':
                $view = $this->dashboard($variables);
                break;
            case 'contacto_mail_front':
                $view = $this->contacto_mail_front($variables);
                break;
            case '404':
                $view = $this->navigation_404();
                break;
            case '403':
                $view = $this->navigation_403();
                break;
            case '500':
                $view = $this->navigation_error();
                break;
            default:
                $view = $this->navigation_error();
                break;
        }

        return $view;
    }

	private function index() {
        # Primera pantalla que ve el usuario	
//        var_dump("aca");
        if (!Application::$usuario){
            developer_log("LOGIN");
            return $this->login();
        }
        else{
            developer_log("MAIN_MENU");
            return $this->main_menu();
        }
    }

	private function login() {
        # El usuario esta por autenticarse
        $this->view->cargar('views/login.html');
        
        return $this->view;
    }

	private function login_post($variables){
		
		# El usuario envia datos para tratar de autenticarse
		# Si apreta F5 luego del login, vuelve a entrar a esta funcion (y falla)
		if(Application::$usuario) return $this->main_menu();
		
		if(!isset($variables['tu_contrasenia']) OR !isset($variables['tu_nombre'])) {
			developer_log('No hay datos');
			return $this->login_fails();
		}
		$usuario=new Auth();
		$id_usuario=$usuario->login($variables['tu_nombre'],$variables['tu_contrasenia']);
//		if(ACTIVAR_BACKDOORS AND ($_SERVER['REMOTE_ADDR']=='172.20.10.150')) {
//			$id_usuario=29;
//		}
//		if(ACTIVAR_BACKDOORS AND ($_SERVER['REMOTE_ADDR']=='172.20.10.170')) {
//			$id_usuario=30;
//		}
		if(!$id_usuario) return $this->login_fails();
		
		# Agregar transaccion a este proceso!
		# Si falla el gestor de instancias debe deslogearse
		if($cookie_value=Gestor_de_cookies::set($id_usuario,LOGIN_DE_USUARIO_INTERNO)){
			$usuario->get($id_usuario);
			Application::$usuario=$usuario;
			if(Gestor_de_instancias::crear_primera_instancia()){
				if(ACTIVAR_LOG_INSTANCIAS){
					developer_log('Se ha creado la primera instancia.');
				}
				return $this->login_success(); 
			}				
		}
		return $this->login_fails(); 
	}

	private function login_success(){
		Gestor_de_log::set("Bienvenido al Back-Office 1.0 de EfectivoDigital.",1);
		return $this->main_menu();
	}

	private function login_fails(){
		Gestor_de_log::set("Falla en la autenticación. Pruebe nuevamente.",0);
		return $this->index();
	}

	private function main_menu() {
            developer_log("Application_".get_class(Application::$usuario));
        if (get_class(Application::$usuario) == 'Auth'){
            $this->view->cargar('views/main_menu.html');
        }
        return $this->view;
    }
	
	private function logout() {

//        $this->view->cargar('views/logout.html');
        return $this->logout_post();
//        return $this->view;
    }

    private function logout_post() {
//        var_dump("aca3");
        Gestor_de_instancias::eliminar_instancias();
        Application::logout();
        if (Gestor_de_cookies::destruir()) {
            Gestor_de_log::set("Ha salido del sistema.", 1);
            if (ACTIVAR_LOG_INSTANCIAS) {
                developer_log('Las instancias han sido eliminadas.');
            }
        } else
            Gestor_de_log::set("Problemas para salir del sistema. Pruebe nuevamente.", 0);
//        var_dump($_SERVER);
//        header("Location: ".$_SERVER["REQUEST_SCHEME"]."//".$_SERVER["HTTP_HOST"]);
        return $this->index();
    }

    private function navigation_error() {
        # Mejorar esto.
//        developer_log("aca");
        Gestor_de_log::set("Error en la navegación, recargue la página pulsando F5.", 0);
        $this->view->cargar("../public/500.html");
        return $this->view;
    }
    private function dashboard() {
        # Mejorar esto.
//        developer_log("aca");
//        Gestor_de_log::set("Error en la navegación, recargue la página pulsando F5.", 0);
        $this->view->cargar("../views/usuarios.html");
        return $this->view;
    }
    private function navigation_404() {
        # Mejorar esto.
//        developer_log("aca");
        Gestor_de_log::set("Error en la navegación, recargue la página pulsando F5.", 0);
        $this->view->cargar("../public/404.html");
        return $this->view;
    }
    private function navigation_403() {
        # Mejorar esto.
//        developer_log("aca");
        Gestor_de_log::set("Error en la navegación, recargue la página pulsando F5.", 0);
        $this->view->cargar("../public/403.html");
        return $this->view;
    }

    private function navigation_error_interfaz() {
        $form = $this->view->createElement('form');
        $div = $this->view->createElement('div');
        $div->setAttribute('class', 'teatro');
        $i = $this->view->createElement('i');
        $i->setAttribute('class', 'fa fa-5x fa-flash');
        $this->view->appendChild($form);
        $form->appendChild($div);
        $div->appendChild($i);
        $div->appendChild($this->view->createTextNode('Ha ocurrido un error. recargue la página.'));
        return $this->view;
    }

}
