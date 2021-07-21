<?php

// namespace Classes;
class Encabezado extends View {
    # Retorna los filtros del util. Recibe un recordset y arma los filtros para cada columna

    public function __construct($modulos,$mensaje=null,$nav=null,$value=null) {
       
        parent::__construct();
        $Controlador_sitio = Controlador_sitio::select_menu_bienvenida($modulos);
        $arrControlador_sitio = $Controlador_sitio->FetchRow();
        
//        $menu_padre=str_replace("<br/>", " ", $arrControlador_sitio['menu_padre']);
//        $menu_hijo=str_replace("<br/>", " ", $arrControlador_sitio['menu_hijo']);
//        $menu_hijo=str_replace(">>", "", $menu_hijo);
        $modulo=str_replace("<br/>", " ", $arrControlador_sitio['modulo']);
        
        //$section = $this->createElement('section');
        //$section->setAttribute('class','contenedor-contenido');
        $div = $this->createElement('div');
        $div->setAttribute('class','encabezado');
//        $h6 = $this->createElement('h6');
////        $span_h6 = $this->createElement('span',$menu_padre.' &gt; ');
//        if($arrControlador_sitio['menu_hijo'])
//            $span_h6_2 = $this->createElement('span',$menu_hijo.' &gt; ');
//        $span_h6_3 = $this->createElement('span',$modulo);
//        $span_h6_3->setAttribute('class','bold');
        $h1 = $this->createElement('h1');
        $span_h1 = $this->createElement('span',$modulo);
        $h5 = $this->createElement('h6');
        $h5->setAttribute('style','margin-left: 2%');
        $span_h5 = $this->createElement('span',$mensaje);
        
        $h5->appendChild($span_h5);
        $h1->appendChild($span_h1);
//        $h6->appendChild($span_h6);
//        if($arrControlador_sitio['menu_hijo'])
//            $h6->appendChild($span_h6_2);
//        $h6->appendChild($span_h6_3);
//        $div->appendChild($h6);
        $div->appendChild($h1);
        $div->appendChild($h5);
       // $section->appendChild($div);
        if($nav!=null and $value!=null){
            
            $input = $this->createElement('div');
            $span_n= $this->createElement('span',$value);
            $span_n->setAttribute('style', 'border-right: none');
            $input->setAttribute('type', 'submit');
            $input->setAttribute('style', '    width: 17%;');
    //        $input->setAttribute('value', 'Nuevo');
            $input->setAttribute('class', 'btn-encabezado');
            $input->setAttribute('name', $modulos. '.'.$nav);
            $input->appendChild($span_n);
            $div->appendChild($input);
        }
        
        $this->appendChild($div);
        return $this;
    }

    public function eliminar($posicion) {

        $inputs = $this->getElementsByTagName("input");
        
        $va = $inputs->item($posicion);
        $va->parentNode->removeChild($va);
    }

}
