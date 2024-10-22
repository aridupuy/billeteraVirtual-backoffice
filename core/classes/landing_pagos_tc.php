<?php

class landing_pagos_tc {
    public $get;
    public $view;
    public $barcode;
    public function __construct($get) {
        $this->get = $get;
        $this->view = new View();
    }
    
    public function mostrar(){
        header("Access-Control-Allow-Origin:*");
        return $this->dispatch();
        
    }
    public function dispatch(){
        if(!isset($this->get["nav"]))
            return $this->home();
        else{
            if($this->get["nav"]=="pagar"){
                return $this->pagar();
            }
        }
    }
    
    public function home(){
        if(isset($this->get["barcode"]) and $this->get["barcode"]!=false){
            $this->view->cargar("views/form_pago_tc.html");
            $rsBarcode = Barcode::select(array("barcode"=> $this->get["barcode"]));
            $this->barcode= new Barcode($rsBarcode->fetchRow());
            if($this->barcode->get_id_barcode()==null){
                return $this->retornar_mensaje("Error codigo de barras inexistente!.");
            }
            
            $monto=$this->view->getElementById("monto");
            $monto->appendChild($this->view->createTextNode($this->barcode->get_monto()));
            $monto=$this->view->getElementById("monto_hidden");
            $monto->setAttribute("value", $this->barcode->get_monto());
            $fecha = new DateTime("now");
//            $fecha_view = $this->view->getElementById("fecha_pago_view");
//            $fecha_view->setAttribute("value", $fecha->format("Y-m-d"));
            $fecha_pago = $this->view->getElementById("fecha_pago");
            $fecha_pago->setAttribute("value", $fecha->format("Y-m-d"));
            $barcode = $this->view->getElementById("barcode");
            $barcode->setAttribute("value", $this->get["barcode"]);
            $minirs= $this->view->getElementById("minirs");
            $concepto= $this->view->getElementById("concepto");
            $bolemarchand = new Bolemarchand();
            $bolemarchand->get($this->barcode->get_id_boletamarchand());
            $marchand = new Marchand();
            $marchand->get($this->barcode->get_id_marchand());
            $concepto->appendChild($this->view->createTextNode($bolemarchand->get_boleta_concepto()));
            $minirs->appendChild($this->view->createTextNode($marchand->get_minirs()));
//            $monto_abierto->setAttribute("value", $this->barcode->get_monto());
            if($this->barcode->get_id_tipopago() != Tipopago::TARJETAS_DE_COBRANZA){
                $monto_abierto= $this->view->getElementById("monto_abierto");
                $monto_abierto->parentNode->removeChild($monto_abierto);
                $monto_cerrado = $this->view->getElementById("monto_cerrado");
                $monto_cerrado->setAttribute("class", "monto-fijo");
                $monto_cerrado->appendChild($this->view->createTextNode($this->barcode->get_monto()));
            }
            else{
                $monto_cerrado= $this->view->getElementById("monto_cerrado");
                $monto_cerrado->parentNode->removeChild($monto_cerrado);
                $monto_abierto = $this->view->getElementById("monto_abierto");
                $monto_abierto->setAttribute("class", "monto-variable");
//                $monto_cerrado->setAttribute("class", "monto-fijo inactive");
//                $monto_cerrado->appendChild($this->view->createTextNode($this->barcode->get_monto()));
            }
        }
        else {
            return $this->retornar_error("Se requiere un Codigo de barras");
        }
        return $this->retornar_vista();
    }
    public function pagar(){
        $pago_tc_decidir=new Pago_tc_decidir();
        if($this->validar_entrada($this->get)){
            Model::StartTrans();
            $rs = Barcode::select(array("barcode"=> $this->get["barcode"]));
            $barcode  = new Barcode($rs->fetchRow());
            $marchand = new Marchand();
            $marchand->get($barcode->get_id_marchand());
            $bolemarchand = new Bolemarchand();
            $bolemarchand->get($barcode->get_id_boletamarchand());
            $concepto = $bolemarchand->get_boleta_concepto();
            if(!$concepto)
                $concepto = $barcode->get_barcode ();
            $id_transaccion=$pago_tc_decidir->obtener_siguiente_num_transaccion();
            //$token,$id_transacction, Marchand $marchand,$concepto,$monto,$bin,$email,$entidad,$referencia,__class $class
            try{
                list($status,$mensaje)=$pago_tc_decidir->generar_pago(isset($this->get["tokenAgroMyOwnID"]) ? $this->get["tokenAgroMyOwnID"]: $this->get["token"],$id_transaccion,$marchand, $concepto, $this->get["monto"], $this->get["bin"],$this->get["issued"],$this->get["email"],$barcode->get_id_trix(),Entidad::ENTIDAD_BARCODE,$barcode->get_id());
                if($status==true){
                    Model::CompleteTrans();
                    developer_log($mensaje);
                    return $this->retornar_mensaje($mensaje);
                }
                else{
                    Model::FailTrans();
                    developer_log($mensaje);
                    return $this->retornar_error($mensaje);
                }
            }catch(Exception $e){
                return $this->retornar_error("Ha tardado demasiado tiempo, por favor intenta mas tarde!.");
            }
        }
        else {
            return $this->retornar_error("Faltan parametros");
        }
    }
    private function  validar_entrada($get){
        if(!isset($this->get["email"]))
            return false;
        
        return true;
    }

    public function retornar_vista(){
        return $this->view->saveHTML();
    }
    public function retornar_error($string){
        developer_log("error " . json_encode(array("estado"=>"true","mensaje"=>$string)));
        return json_encode(array("estado"=>"false","mensaje"=>$string));
    }
    public function retornar_mensaje($string){
        developer_log("correcto " . json_encode(array("estado"=>"true","mensaje"=>$string)));
        return json_encode(array("estado"=>"true","mensaje"=>$string));
    }
}
