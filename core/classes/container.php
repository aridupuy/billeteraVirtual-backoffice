<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of container
 *
 * @author Ariel y Jhames y H se jode
 */
class Container extends View {

    private $recordset;
    private $acciones;
    private $detalle;
    private $pivote=0;

    public function __construct( $recordset, Detalle $detalle, array $acciones=null) {
        parent::__construct();
        $this->recordset = $recordset;
        $this->acciones = $acciones;
        $this->detalle = $detalle;
        return $this;
    }

    public function eliminar_columna($columna) {
        $titulos = $this->detalle->get_titulos();
        $this->detalle->eliminar_titulo($columna);
        foreach ($this->detalle->get_datos() as $key => $row) {
            $this->detalle->eliminar_dato($key, $columna);
        }
    }

    public function crear_desde_recordset() {
        $recordset = $this->recordset;
        
//        foreach ($recordset as $value) {
//            
//        print_r("<pre>");
//        print_r($value);
//        print_r("<pre>");
//        }
        
        $div_total = $this->createElement("div");
        $div_total->setAttribute("class", "contenedor-admin-usuarios");
        foreach ($recordset as $clave => $row) {
            //        print_r("<pre>");
//        print_r($value);
//        print_r("<pre>");
            $div_cont = $this->createElement("div");
            $div_cont->setAttribute("class", "usuario");
            $div_titulo = $this->createElement("div");
            $div_titulo->setAttribute("class", "titulo-usuario");
            $div_txt_titulo = $this->createElement("div");
            $div_txt_titulo->setAttribute("class", "txt-titulo-usuario");
            $input_titulo = $this->createElement("input");
            $div_acciones_titulo = $this->createElement("div");
            $div_acciones_titulo->setAttribute("class", "acciones-titulo-usuario");
            $div_activar = $this->createElement("div");
            $div_activar->setAttribute("class", "accion-activar");

            $input_titulo->setAttribute("class", "bold");
            $input_titulo->setAttribute("type", "text");
            $input_titulo->setAttribute("enabled", "false");
            if(is_array($row[$this->detalle->get_titulo()])){
                $cualquiera = $row[$this->detalle->get_titulo()];
                $input_titulo->setAttribute("value", ucfirst($cualquiera['value']));
            }
            else{
                if(isset($row[$this->detalle->get_titulo()])){
//                    var_dump($row[$this->detalle->get_titulo_at($this->pivote)]);
                    $input_titulo->setAttribute("value", ucfirst($row[$this->detalle->get_titulo()]));
                }
                else{
                    
//                    var_dump($row[$this->detalle->get_titulo_at($this->pivote)]);
                    $input_titulo->setAttribute("value", ucfirst($row[$this->detalle->get_titulo_at($this->pivote)]["value"]));
                }
            }
            if ($this->acciones != null) {
                $this->procesar_toogle($this->acciones, $row, $div_activar);
            }

            $div_desplegar = $this->createElement("div");
            $div_desplegar->setAttribute("class", "accion-desplegar active");
            $img_desplegar = $this->createElement("img");
            $img_desplegar->setAttribute("src", "public/img/icono-desplegar.svg");

            $div_cont->appendChild($div_titulo);
            $div_titulo->appendChild($div_txt_titulo);
            $div_txt_titulo->appendChild($input_titulo);
            $div_titulo->appendChild($div_acciones_titulo);
            $div_acciones_titulo->appendChild($div_activar);


            $div_acciones_titulo->appendChild($div_desplegar);
            $div_desplegar->appendChild($img_desplegar);
            $div_total->appendChild($div_cont);
            /* El detalle */
            $div_detalle = $this->createElement("div");
            $div_detalle->setAttribute("class", "detalles-usuario active");
            $count = 0;
            
            if($this->detalle->is_solouno())
                $detalles[]=$this->detalle->get_titulo_at($this->pivote);
            else
                $detalles=$this->detalle->get_titulos();
            foreach ($detalles as $key => $titulo) {
                if ($count == 2) {
                    $count = 0;
                }
                $div_dato = $this->createElement("div");
                $div_dato->setAttribute("class", "dato-group");
                if ($count == 0) {
                    $div_col = $this->createElement("div");
                    $div_col->setAttribute("class", "detalles-usuario-col");
                    $div_detalle->appendChild($div_col);
                }
                $div_col->appendChild($div_dato);
                $label = $this->createElement("label", ucfirst($titulo . " "));
                $label->setAttribute("for", "");
                $label->setAttribute("class", "bold");
                $input_dato = $this->createElement("input");
                $input_dato->setAttribute("value", "");
                $input_dato->setAttribute("type", "text");
                $input_dato->setAttribute("value",": ". ucfirst($this->detalle->get_datos_at($this->pivote,$titulo)));
                $div_cont->appendChild($div_detalle);
                $div_dato->appendChild($label);
                $div_dato->appendChild($input_dato);
                $count++;
            }
            unset($detalles);
            $this->pivote++;
            $this->procesar_acciones($this->acciones, $row, $div_detalle);
        }
        $this->appendChild($div_total);
        return $div_total;
    }

    public function procesar_toogle($acciones, $row, $div_activar) {
        $accion = new Toggle();
        foreach ($acciones as $accion) {
            if (get_class($accion) == "Toggle") {
                $div_switch = $this->createElement("div");
                $div_switch->setAttribute("class", "switch");
                $span_switch = $this->createElement("span", "Activar");
                $span_switch->setAttribute("name", $accion->get_nav_estado());
                $span_switch->setAttribute("type", "button");
                $span_switch->setAttribute("id", $row[$accion->get_campo_id()]);
                if (isset($row[$accion->get_id_estado()]) and $row[$accion->get_id_estado()] == Authstat::ACTIVO) {
                    $span_switch->setAttribute("class", "active");
                }
                $div_activar->appendChild($span_switch);
                $div_activar->appendChild($div_switch);
            }
        }
    }

    public function procesar_acciones($acciones, $row, $div_detalle) {
        $div_acciones = $this->createElement("div");
        $div_acciones_button= $this->createElement("div");
        $div_acciones_button->setAttribute("class", "iconos-acciones");
        $accion=new Accion();
        $div_acciones_usuario= $this->createElement("div");
        $div_acciones_usuario->setAttribute("class", "acciones-usuario");
        $div_acciones_usuario->appendChild($div_acciones_button);
        foreach ($acciones as $accion)
            if ($accion->get_nav() != false) {
                if(get_class($accion)=="Action_button"){
                    $div_icono= $this->createElement("div");
                    $div_icono->setAttribute("class", "icono");
                    $div_icono->setAttribute("type", "button");
                    $div_icono->setAttribute("name", $accion->get_nav());
                    $key=array_keys($row)[0];
                    if($row[$accion->get_campo_id()]!=null)
                        $div_icono->setAttribute("id", $row[$accion->get_campo_id()]);
                    else
                        $div_icono->setAttribute("id", $row[$key][$accion->get_campo_id()]);
                    $img=$this->createElement("img");
                    $img->setAttribute("src", $accion->get_icono());
                    $span = $this->createElement("span",$accion->get_titulo_nav());
                    $span->setAttribute("class", "tooltip");
                    $div_icono->appendChild($img);
                    $div_icono->appendChild($span);
                    $div_acciones_button->appendChild($div_icono);
                }
                elseif(get_class($accion)=="Distinct_button"){
                    $div_icono= $this->createElement("div");
                    $div_icono->setAttribute("class", "icono");
                    $div_icono->setAttribute("type", "button");
                    $div_icono->setAttribute("name", $accion->get_nav());
                    $callback=$accion->get_callback();
                    $callback($row,$accion);
                    $key=array_keys($row)[0];
                    if($row[$accion->get_campo_id()]!=null)
                        $div_icono->setAttribute("id", $row[$accion->get_campo_id()]);
                    else
                        $div_icono->setAttribute("id", $row[$key][$accion->get_campo_id()]);
                    $img=$this->createElement("img");
                    $img->setAttribute("src", $accion->get_icono());
                    $span = $this->createElement("span",$accion->get_titulo_nav());
                    $span->setAttribute("class", "tooltip");
                    $div_icono->appendChild($img);
                    $div_icono->appendChild($span);
                    $div_acciones_button->appendChild($div_icono);
                }
                else{
                    $div_acciones->setAttribute("class", "enlaces-acciones");
                    $div_button = $this->createElement("div", ucfirst($accion->get_titulo_nav()));
                    $div_button->setAttribute("name", $accion->get_nav());
                    $div_button->setAttribute("type", "button");
                    $key=array_keys($row)[0];
                    
                    if($row[$accion->get_campo_id()]!=null)
                        $div_button->setAttribute("id", $row[$accion->get_campo_id()]);
                    else
                        $div_button->setAttribute("id", $row[$key][$accion->get_campo_id()]);
                    $div_acciones->appendChild($div_button);
                }
                $div_acciones_usuario->appendChild($div_acciones);
            }
        $div_detalle->appendChild($div_acciones_usuario);
    }

}
