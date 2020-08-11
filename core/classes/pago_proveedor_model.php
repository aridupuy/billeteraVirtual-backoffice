<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

Class Pago_proveedor_model extends Modmodel {

    private $state;
    
    public function getState(){
        return $this->state;
    }

    public function proveedorDesconocido() {
        //default 
        if (is_null($this->state)) {
            $this->set_state(new Pagos_proveedor_inexistente());
        } else {
            if (!(get_class($this->state) == "Pagos_proveedor_inexistente")) {
                developer_log("ya tenia state cambio de clase ");
            }
        }
    }

    public function proveedor_desconocido( $id){
        $state=new Pagos_proveedor_inexistente($id);
        $proveedor = new Proveedor_pendiente();
        $proveedor->set_id_proveedor_pendiente($id);
        $proveedor->get($id);
        
        $state->set_proveedor($proveedor);
        $this->set_state($state);
        return $state;
        
       // $this->state->enviar_correo($id) ;
    }
    private  function proveedorConocido($clienteConocido) {
        //cambia el state
        
        $this->set_state(new Pagos_proveedor_existente($clienteConocido));
    }

    public  static function get_recorset($variables=false) {

        return Marchand::select_proveedores(Application::$usuario->get_id_marchand(),$variables);
    }
    
    public function existe_cliente($variables) {

        return $this->ObtenerEstado($variables);
    }

    private function ObtenerEstado($variables) {
        error_log("variables".json_encode($variables));
        //$variables_requeridas = array('cuit');
        if(isset($variables["id"]) and !isset($variables["id2"]) and !isset($variables["cuit"])){
            $id=$variables["id"];
            unset($variables);
            $variables=array();
            $variables["id2"]=$id;
        }

        foreach ($variables_requeridas as $clave) {
            if (!isset($variables[$clave]) and !isset($variables["id2"])) {
                    throw new Exception('Debe completar todos los campos para continuar.');
            }
        }
        
        $recordset = $this-> get_record($variables);
        error_log($recordset->rowCount());
//        exit();
        if ($recordset AND $recordset->RowCount() == 1) {
            
            # Sirve el trait cliente o me arreglo solo con el $marchand ?
            $row = $recordset->FetchRow();
            developer_log("id_marchand= ".$row['id_marchand'] ." el otro  ".Application::$usuario->get_id_marchand());
            if ($row['id_marchand'] == Application::$usuario->get_id_marchand()) {
                
                throw new Exception('No puede realizar un Pago a su propia cuenta.');
            }
            
            $cliente = Cliente::createFromMarchand(new Marchand($row));
            $this->setState(new Pagos_proveedor_existente($cliente));
            return array($this,FALSE);
        }
        elseif($recordset AND $recordset->RowCount()>1){
            foreach ($recordset as $row){
                $marchand=new Marchand($row);
                $config= Configuracion::obtener_configuracion($marchand->get_id_marchand());
                if($config[Configuracion::CONFIG_PAGO_A_PROVEEDOR][Configuracion::CONFIG_GLOBAL_PAGO_A_PROVEEDOR][Configuracion::CONFIG_PAP_CUENTA_DEFAULT]["value"]!=0){
                    $id_marchand=$config[Configuracion::CONFIG_PAGO_A_PROVEEDOR][Configuracion::CONFIG_GLOBAL_PAGO_A_PROVEEDOR][Configuracion::CONFIG_PAP_CUENTA_DEFAULT]["value"];
                    $marchand=new Marchand();
                    $marchand->get($id_marchand);
                    $cliente = Cliente::createFromMarchand($marchand);
                    $this->setState(new Pagos_proveedor_existente($cliente));
                    return array($this,false);
                }
            }
	    error_log("Conflicto");
            return array("Conflicto",$recordset);
           
        }
        else
            return array($this->setState(new Pagos_proveedor_inexistente($variables)),false);
    }

    private function setState(Pagos_state $estado) {

        $this->set_state($estado);
        $this->update();
    }

    public function set_proveedor_conocido($marchand) {

        $this->setState(new Pagos_proveedor_existente($marchand));
    }

    public function proveedor_conocido($id) {
         developer_log("id_marchand= $id"." el otro  ".Application::$usuario->get_id_marchand());

         if ($id  == Application::$usuario->get_id_marchand()) {
                
                throw new Exception('No puede realizar un Pago a su propia cuenta.');
            }

        $marchand = new Marchand();
        $marchand->get($id);
          
         $cliente = Cliente::createFromMarchand($marchand);
        $this->proveedorConocido($cliente);
    }

    public function tieneCliente($variables = null) {
	error_log("tiene cliente. ".json_encode($variables));
	$state=false;
        if (!isset($this->state)){
            developer_log("NO TIENE STATE");
            list($state,$recordset)=$this->ObtenerEstado($variables);
        }
        developer_log("Conflicto");
        if($state==="Conflicto")
		return array($state,false);
//        developer_log("SETEA STATE");
//        $this->setState($state);
        return array($this->state->tieneCliente($variables),false);
    }

    public function debug() {
        return null;
        return get_class($this) . "->" . $this->state->debug();
    }

    public function procesar4($variables) {
        $transaccion = new Transaccion();
        $importe = $variables['importe'] + ($variables['importe_cv'] / 100);
        return $transaccion->calculo_directo(Application::$usuario->get_id_marchand(), Mp::PAGO_A_PROVEEDOR, $importe, null, null,false,false,true);
    }

    public function get_cliente($variables) {
        if (!isset($this->state)){
            list($state,$recordset)=$this->ObtenerEstado($variables);
            if($state=="Conflicto"){
		return $state;
		}
        }
       	return $this->state->get_proveedor($variables);
    }

    public function procesar($variables, $asumir) {
        developer_log(json_encode($variables));
        return $this->obtener_stado($variables)->procesar($variables, $asumir);
    }

    public function set_persona($variables) {
        if ($variables['id_pfpj'] == '2') {
            
        }
    }

    private function set_state($o) {
        $this->state = $o;
        $this->update();
    }

    private function get_state() {
        return $this->state;
    }

    private function obtener_stado() {
        return $this->state;
    }

    private function get_record($variables) {
        
        if (sizeof($variables) > 2 and isset($variables["cuit"])) {
            error_log("for array");
            $recordset = $this->get_row_for_array($variables);
        } 
        elseif(isset($variables["id2"])){
             $recordset = $this->get_row_for_id($variables);
        }
        else {
            $recordset = $this->get_row_for_id($variables);
            
        }
        return $recordset;
    }

    private function get_row_for_array($variables) {

        if(isset($variables["mercalpha"]) and !isset($variables["id2"])){
            error_log("cuit,tipodoc,mercalpha");
            return Marchand::select(array('documento' => $variables['cuit'], 'id_tipodoc' => Tipodoc::CUIT_CUIL,"mercalpha"=>$variables["mercalpha"]));
        }
        else if(isset($variables["id2"]) and !isset ($variables["cuit"])){
            error_log("documento(id2),tipodoc");
            return Marchand::select(array('documento' => $variables['id2'], 'id_tipodoc' => Tipodoc::CUIT_CUIL));
        }
        else{
            error_log("documento(cuit),id_tipodoc");
            error_log(json_encode($variables));
            return Marchand::select(array('documento' => $variables['cuit']/*, 'id_tipodoc' => Tipodoc::CUIT_CUIL*/));
        }
    }

    public function get_row_for_id($variables) {
        error_log(json_encode($variables));
            return Marchand::select(array('id_marchand' => $variables['id2']));
//        if(isset($variables["id2"]))
//            return Marchand::select(array('id_marchand' => $variables['id2']));
//        else{
//            return Marchand::select(array('mercalpha' => $variables['mercalpha']));
//        }
        
    }

}
