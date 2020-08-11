<?php
class Interfaz{
	private $modulo=false;
	public function __construct($modulo)
	{	error_log($modulo);
		$this->modulo=strtolower(trim($modulo));
	}
	public function render()
	{
		if(!$this->modulo) return '';
		$GLOBALS['SISTEMA']='INTERFAZ';
		define('FORZAR_MODULO',$this->modulo);
		ob_start();
	    	include "externo/index.php";
		$html=ob_get_clean();
	     return $html;		
	}
	public static function render_scripts()
	{
		# Codigo javascript agregado en la primera linea de index.php de CobroDigital
		$string="<script type='text/javascript'>
	  			function dispatch(modulo){
				    if(document.getElementById('theCopete')){
				    	document.getElementById('theCopete').setAttribute('class','copete0');
				    }
				    if(document.getElementById('theInner')){
						document.getElementById('theInner').load('mods/do.php?mod='+modulo);
				    }
	  			}
			</script>";
		return trim(preg_replace('/\s\s+/', ' ', $string));;
	}
}

