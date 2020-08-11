<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

Class Pagos_proveedor_existente extends Pagos_state {

    private $proveedor;

    public function __construct($p) {
        if(get_class($p)!='Cliente'){
              debug_backtrace();
        }
              
     //   developer_log( "__construct ".json_encode($p));
    
             $this->proveedor = $p;
    }
    private function esta_vencido() {
        return false;
    }
    public function debug(){
    
        return get_class($this)."->[".get_class($this->proveedor)."]";
    }
    
    public function tieneCliente() {
        return !is_null($this->proveedor) ;
    }

    public function get_proveedor() {
        return $this->proveedor;
    }

    public function procesar($variables, $asumir_costo) {

        Model::StartTrans();
        $var=$this->entrada2($variables, $asumir_costo);
        if($var)
            $this->enviar_mails($variables);
        return $var;
    }

    private function entrada1($variables) {
        # Entrada 1
    }
    private function enviar_mails($variables){
     $emisor = Gestor_de_correo::MAIL_COBRODIGITAL_INFO;
        $mail = $this->get_proveedor()->marchand->get_email();
        $asunto = "correo de: gestion de proveedor";
        $view=new View();
        $view->cargar("views/mail_proveedor_existente_aviso.html");
        $nombre_prov= $view->getElementById("nombre_prov");
        $nombre_marchand= $view->getElementById("nombre_marchand");
        $importe= $view->getElementById("importe");
        $importe2= $view->getElementById("importe2");
        $concepto= $view->getElementById("concepto");
        $concepto2= $view->getElementById("concepto2");
        
        $nombre_prov->appendChild($view->createTextNode($this->get_proveedor()->marchand->get_apellido_rs()));
        $marchand=new Marchand();
//        var_dump($variables);
//        exit();
        $marchand->get(Application::$usuario->get_id_marchand());
        $nombre_marchand->appendChild($view->createTextNode($marchand->get_apellido_rs()));
        $importe->appendChild($view->createTextNode(($variables["importe"])+($variables["importe_cv"]/100))); 
        $importe2->appendChild($view->createTextNode(($variables["importe"])+($variables["importe_cv"]/100))); 
        $concepto->appendChild($view->createTextNode($variables["concepto"]));
        $concepto2->appendChild($view->createTextNode($variables["concepto"]));
        
        //  developer_log($data);
        $data= $view->saveHTML();
        if (!Gestor_de_correo::enviar($emisor, $mail, $asunto, $data)) {
            developer_log('falló al enviar correo');
            return true;
        }
        return true;
    }

    private function validar_preexistencia($cuil) {
        $recordSet= Proveedor_pendiente::select(array("cuil"=>$cuil));
        foreach ($recordSet as $row){
            $proveedor_pendiente=new Proveedor_pendiente($row);
            if($proveedor_pendiente->get_acepto()===true){
                return true;
            }
            if($proveedor_pendiente->get_acepto()===false){
                return true;
            }
            if(!$this->esta_vencido()){
                return true;
            }
            if($proveedor_pendiente->get_acepto()===null){
                return false;
            }
            
        }
        return false;
    }
    public function validar_cbu($cbu){
	return true;
        $marchand=$this->proveedor->marchand;
        $recordset= Cbumarchand::select_descifrado($marchand->get_id_marchand());
        if($recordset and $recordset->rowCount()>=1){
            foreach ($recordset as $row){
                if($cbu===$row["cbu"]){
                    return true;
                }
            }
        }
        return true;
    }

    private function entrada2($variables, $asumir_costo) {
        developer_log("entrada2 llego");
	/*
         * crea boleta, crea la transaccion del pagador 
         * envia correo 
         */
        $fallar_transaccion = false;
        if($this->proveedor==null)
            if(!$this->validar_preexistencia($variables["cuit"])){
		developer_log("preexistencia no existe");
                return false;
                $fallar_transaccion=true;
            }
//            
//            if(!$this->validar_cbu($variables["cbu"])){
//		developer_log("cbu invalido");
//                return false;
//                $fallar_transaccion=true;
//            }
        try {
            if (!Model::hasFailedTrans()) {
//                if ($this->crear_boleta($variables, $asumir_costo)) {
//                    if (self::ACTIVAR_DEBUG) {
//                        developer_log('Boleta creada correctamente.');
//                    }
                    if ($this->crear_transacciones($variables, $asumir_costo)) {
                        if (self::ACTIVAR_DEBUG) {
                            developer_log('Transacciones creadas correctamente.');
                        }
                    } else {
                        if (self::ACTIVAR_DEBUG) {
                            developer_log('Ha ocurrido un error al crear las transacciones.');
                        }
                        $fallar_transaccion = true;
                    }
//                } else {
//                    if (self::ACTIVAR_DEBUG) {
//                        developer_log('Ha ocurrido un error al crear la boleta.');
//                    }
//                    $fallar_transaccion = true;
//                }
            }
        } catch (Exception $e) {
            Model::FailTrans();
            $mensaje = $e->getMessage();
            developer_log("catch " . $mensaje);
            $es_correcto = '0';
            $fallar_transaccion = true;
        }
        if ($fallar_transaccion !== false OR self::ACTIVAR_TEST) {
            Model::FailTrans();
	    developer_log("esta fallada");
        }
        if (Model::CompleteTrans() AND ! Model::hasFailedTrans()) {
            $mensaje = 'Ha pagado correctamente.';
            $es_correcto = '1';
	    developer_log($mensaje);
            session_destroy();
        } else {
            if (!isset($mensaje)) {
                # Si no viene de una excepcion
                $mensaje = 'Ha ocurrido un error al realizar el Pago. ';
		developer_log($mensaje);
                $es_correcto = '0';
            }
        }
        Gestor_de_log::set($mensaje, $es_correcto);
        if (self::ACTIVAR_DEBUG) {
            developer_log($mensaje);
        }
        return !$fallar_transaccion;
    }

      private function crear_transacciones($variables, $asumir_costo) {
//          var_dump($variables);
        $variables_requeridas = array('importe');
        foreach ($variables_requeridas as $clave) {
            if (!isset($variables[$clave])) {
                throw new Exception('Debe completar todos el campo '.$clave.' para continuar.');
            }
        }


        $egreso = new Transaccion();
        
        $id_marchand_egreso = Application::$usuario->get_id_marchand();
        $id_mp_egreso = Mp::PAGO_A_PROVEEDOR;
        $id_mp_ingreso = Mp::COBRO_COMO_PROVEEDOR;
        
      
         
        if ($asumir_costo) {
            $traslado_comision_ingreso=false;
            $traslado_comision_egreso=true;
        } else {
            $traslado_comision_ingreso=true;
            $traslado_comision_egreso=false;
        }

        $monto_pagador_egreso = $variables['importe']+($variables['importe_cv']/100);
        $fecha_egreso = new DateTime('now');
      
      
        $id_referencia_egreso = $this->proveedor->marchand->get_id_marchand();


        if ($egreso->crear($id_marchand_egreso, $id_mp_egreso, $monto_pagador_egreso, $fecha_egreso, $id_referencia_egreso,null,null ,$traslado_comision_egreso )) {

            $ingreso = new Transaccion();
            $id_marchand_ingreso = $this->proveedor->marchand->get_id_marchand();
            $monto_pagador_ingreso = $variables['importe']+($variables['importe_cv']/100);
            list($monto_pagador, $pag_fix, $pag_var, $monto_cd, $cdi_fix, $cdi_var, $monto_marchand) = $ingreso->calculo_directo($id_marchand_ingreso, $id_mp_ingreso, $monto_pagador_ingreso);
          //  $dif = abs($monto_pagador - $monto_marchand);
          //  $monto_pagador_ingreso += $dif;
            $fecha_ingreso = $fecha_egreso;
            $id_referencia_ingreso = $id_referencia_egreso;
            if ($ingreso->crear($id_marchand_ingreso, $id_mp_ingreso, $monto_pagador_ingreso, $fecha_ingreso, $id_marchand_egreso,null,null,$traslado_comision_ingreso)) {
                developer_log('cobro al proveedor ok');
                return true;
            }
        }
	//var_dump($ingreso);
	//var_dump($egreso);
	//Model::failTrans();
	//it();
        return false;
    }
//
//    private function crear_boleta($variables, $asumir_costo) {
//
//        if (!array_key_exists('email', $variables)) {
//            $this->completarArrayConOBjeto($variables);
//        }
//        $boleta = new Boleta_comprador();
//        $concepto = $variables['concepto'];
//        $fecha = new DateTime('now');
//        
//        $_importe = $variables['importe']+($variables['importe_cv']/100);
//        
//        $importe = array($_importe);
//        $fechas = array($fecha->format('d/m/Y'));
//
//        $datos = array();
//        $datos[Boleta_comprador::VENCIMIENTO] = $fechas[0];
//        $datos[Boleta_comprador::IMPORTE] = $importe[0];
//        $datos[Boleta_comprador::DETALLE] = $concepto;
//        $datos[Boleta_comprador::CORREO] = $variables['email'];
//        $datos[Boleta_comprador::DOCUMENTO] = $this->proveedor->marchand->get_documento(); //variables['cuit'];
//        //
//        $nombre="";
//        $datos[Boleta_comprador::DIRECCION] = '1';
//        
//        if( isset($variables['nombre'])){
//        if( !array_key_exists('nombre',$variables))
//        {
//            if( !array_key_exists('apoderado',$variables))
//                    $nombre=$variables['apoderado'];
//        } else
//        {
//            $nombre=$variables['nombre'];
//        }
//        
//        $datos[Boleta_comprador::NOMBRE] = $nombre;
//        }
//        
//        if( ! isset( $variables['apellido_rs']) && !isset( $variables['nombre'])){
//            $variables['apellido_rs']="";
//        }
//        
//        if(! isset( $variables['apellido_rs'])){
//            $variables['apellido_rs']="";
//        }
//        $datos[Boleta_comprador::APELLIDO] = $variables['apellido_rs'];
//
//        $id = $this->proveedor->marchand->get_id_marchand();
//        return $boleta->crear($id, $datos, $importe, $fechas, $concepto);
//    }

    private function completarArrayConOBjeto(&$variables) {
        $variables['email'] = $this->proveedor->marchand->get_email();
        $variables['nombre'] = $this->proveedor->marchand->get_nombre();
        $variables['apellido_rs'] = $this->proveedor->marchand->get_apellido_rs();
    }

}
