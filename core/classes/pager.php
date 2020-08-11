<?php
// namespace Classes;
class Pager extends View{
	# La tabla se muestra desde $this->desde_registro hasta $this->hasta_registro
	public $desde_registro;
	public $hasta_registro;
	# Retorna el paginador. Recibe un recordset y arma los controles necesarios

	public function __construct($registros,$pagina_a_mostrar,$controller){
		
		parent::__construct();
		$this->desde_registro=1;
                $this->hasta_registro=$registros->rowCount();
                $pager=$this->createElement('div');
		$this->appendChild($pager);
                return $this;
                //trunco la salida
               
		$pager->setAttribute('class','paginador');
		$pager->setAttribute('name',$controller);
		$pager->setAttribute('data-pagina-actual',$pagina_a_mostrar);
		if($registros===false) return $pager;

		$cantidad_de_registros=$registros->rowCount();
		$cantidad_de_paginas=ceil($cantidad_de_registros/$GLOBALS['REGISTROS_POR_PAGINA']);
		$pager->setAttribute('data-cantidad-paginas',$cantidad_de_paginas);
		if($cantidad_de_paginas==0) $pagina_a_mostrar=0;

		if($cantidad_de_registros<($pagina_a_mostrar-1)*$GLOBALS['REGISTROS_POR_PAGINA']+1){
			$pagina_a_mostrar=1;
		}
		
//		$this->desde_registro=($pagina_a_mostrar-1)*REGISTROS_POR_PAGINA+1;
//		$this->hasta_registro=$this->desde_registro+REGISTROS_POR_PAGINA-1;
//		
//		
//		if($this->hasta_registro>$cantidad_de_registros) $this->hasta_registro=$cantidad_de_registros;
                
		$primera=$this->createElement('i');
		$primera->setAttribute('class','fa fa-fast-backward');
		$pager->appendChild($primera);		

		$anterior=$this->createElement('i');
		$anterior->setAttribute('class','fa fa-backward');
		$pager->appendChild($anterior);

		$descripcion=$this->createElement('i');
		$descripcion->appendChild($this->createTextNode('Página '.$pagina_a_mostrar.' de '.$cantidad_de_paginas));
		$descripcion->setAttribute('title','Se muestran '.$GLOBALS['REGISTROS_POR_PAGINA'].' registros por página');
		$pager->appendChild($descripcion);

		$siguiente=$this->createElement('i');
		$siguiente->setAttribute('class','fa fa-forward');
		$pager->appendChild($siguiente);

		$ultima=$this->createElement('i');
		$ultima->setAttribute('class','fa fa-fast-forward');
		$pager->appendChild($ultima);

		if($cantidad_de_registros<$GLOBALS["MAXIMO_REGISTROS_POR_CONSULTA"]){
			$mensaje=$cantidad_de_registros.' Registros.';
		}
		else{
			$mensaje='Más de '.$cantidad_de_registros.' Registros. ';
		}
		$cantidad_registros=$this->createElement('div',$mensaje);
		$cantidad_registros->setAttribute('class','cantidad_registros');
		$pager->appendChild($cantidad_registros);
		return $this;
	}


}







