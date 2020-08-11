<?php
// namespace Classes;
class Back_office extends Application
{     

	protected function dispatch($nav,$variables)
	{		
		$nav=explode('.', $nav);
		if(ACTIVAR_LOG_SQL_CONTROLLERS) $tiempo_inicio=microtime(true);
		switch ($nav[0]):
			case 'main_controller':
				$util=new Main_controller();
				$view=$util->dispatch($nav[1],$variables);
				break;
			case 'meta_controller':
				$util=new Meta_controller();
				$view=$util->meta_dispatch($nav[1],$nav[2],$variables);
				break;
		    default:
	        		$prefijo_ok=substr($nav[0], 0,5)==='util_';
	        		$clase=ucfirst(strtolower($nav[0]));
	        		$clase_existe=class_exists($clase);
	        		$metodo_existe=method_exists($clase, 'dispatch');
	        		$herencia_correcta=false;
	        		if($clase_existe){
	        			$reflector = new ReflectionClass($clase);
						$herencia_correcta=$reflector->isSubclassOf('Controller');
					}
	        		
	        		if($prefijo_ok AND $clase_existe AND $metodo_existe AND $herencia_correcta){
	        			$util=new $clase();
	        			$view=$util->dispatch($nav[1],$variables);	
	        		}
	        		else{
						$util=new Main_controller();
						#header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found"); 
						$view=$util->dispatch('navigation_error','');
	        		}
					break;
		endswitch;

		if(ACTIVAR_LOG_SQL_CONTROLLERS) {
			$duracion=microtime(true)-$tiempo_inicio;
			if(class_exists(ucfirst(strtolower($nav[0])))){
				Gestor_de_log::set_auto(strtoupper($nav[1]), strtolower($nav[0]),'',null, 1,$duracion);
			}
		}

		return $view;
	}	
}