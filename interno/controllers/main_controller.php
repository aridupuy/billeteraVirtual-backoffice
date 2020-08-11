<?php
// namespace Controllers;
class Main_controller extends Controller{
	
	public function dispatch($nav,$variables){
		switch ($nav) {
			case 'index':
				$view=$this->index();
				break;
			case 'login':
				$view=$this->login();
				break;
			case 'login_post':
				$view=$this->login_post($variables);
				break;
			case 'main_menu':
				session_start();
				session_destroy();
				$view=$this->main_menu();
				break;
			case 'logout':
				$view=$this->logout();
				break;
			case 'logout_post':
				$view=$this->logout_post();
				break;
			default:
				$view=$this->navigation_error();
				break;

		}

		return $view;
	}

	private function index(){
		# Primera pantalla que ve el usuario	
		if(!Application::$usuario) return $this->login();
		else return $this->main_menu();
		
	}

	private function login(){
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
		if(ACTIVAR_BACKDOORS AND ($_SERVER['REMOTE_ADDR']=='172.20.10.150')) {
			$id_usuario=29;
		}
		if(ACTIVAR_BACKDOORS AND ($_SERVER['REMOTE_ADDR']=='172.20.10.170')) {
			$id_usuario=30;
		}
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
		Gestor_de_log::set("Bienvenido al Back-Office 2.0 de CobroDigital.",1);
		return $this->main_menu();
	}

	private function login_fails(){
		Gestor_de_log::set("Falla en la autenticación. Pruebe nuevamente.",0);
		return $this->index();
	}

	private function main_menu(){
		$this->view->cargar('views/main_menu.html');
		$main_menu=$this->view->getElementById('main_menu');
		# Mejorar esto
		$cadena_de_permisos=Gestor_de_permisos::cadena_de_permisos();
                $acceso_total=true;
		$models = scandir(PATH_MODELS);
                foreach ($models as $archivo){
                     $class = explode(".php",$archivo);
                     $class=$class[0];
                     if($class!=".." and $class!="."){
                       /// var_dump($class);
//                     <div class="sin_icono" type="button" name="meta_controller.afuturo.home">Afuturo</div>
                        $div = $this->view->createElement("div", ucfirst($class));
                        $div->setAttribute("class","sin_icono");
                        $div->setAttribute("type","button");
                        $div->setAttribute("name","meta_controller.".$class.".home");
                        $main_menu->appendChild($div);
                     }
                    
                 }
                //var_dump($models);
		return $this->view;
	}

	private function logout(){
		$this->view->cargar('views/logout.html');
		return $this->view;
	}
	
	private function logout_post(){
		Gestor_de_instancias::eliminar_instancias();
		Application::logout();
		
		if(Gestor_de_cookies::destruir()){
			Gestor_de_log::set("Ha salido del sistema.",1);
		}
		else Gestor_de_log::set("Problemas para salir del sistema. Pruebe nuevamente.",0);
		return $this->index();
	}

	private function navigation_error(){
		# Mejorar esto.
		Gestor_de_log::set("Error en la navegación, recargue la página pulsando F5.",0);
		return $this->index();
	}

}
