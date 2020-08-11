<?php
// namespace Controllers;
abstract class Controller {
	protected $view;
    public static $nombre;
    public static $parametros=array();
    public function __construct(){
		
		$this->view = new View();
	}

	public function dispatch($nav,$variables)
	{
    
		switch ($nav) {
			case 'home':
				$view=$this->home();
				break;
			case 'filter':
				$view=$this->home($variables);
				break;
			case 'create':
				$view=$this->create();
				break;
			case 'create_post':
				$view=$this->create_post($variables);
				break;
			case 'edit':
				$view=$this->edit($variables['id']);
				break;
			case 'edit_post':
				$view=$this->edit_post($variables);
				break;
			case 'delete':
				$view=$this->delete($variables['id']);
				break;
			case 'delete_post':
				$view=$this->delete_post($variables['id']);
				break;
			default:
				Gestor_de_log::set("Error en la navegación.",0);
				return false; # Podria deslogear al usuario?
				break;
		}
		
		return $view;
	}

	protected function options($recordset, $value, $content,$selected=false,$hacer_nodo_raiz=true)
	{
		$view=new View();
		if($hacer_nodo_raiz) {
			$raiz=$view->createElement('root');
			$view->appendChild($raiz);
		}
		foreach ($recordset as $row) {		
			$option=$view->createElement('option',$row[$content]);
			$option->setAttribute('value',$row[$value]);
			if($selected AND $selected==$row[$value])
				$option->setAttribute('selected','selected');
			if($hacer_nodo_raiz) $raiz->appendChild($option);
			else $view->appendChild($option);
		}
		return $view;
	}

}
