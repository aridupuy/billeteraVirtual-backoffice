<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Landing_proveedor_correcto extends Landing_proveedor_action {

    // private $proveedor;
    private $boleta;
    private $user;
    private $passw;
    private $comercio;
    private $file;
    private $cliente;
    private $cbu_marchand;

    const DUMMY_REFERENCIA = 'Cuenta generada por tercero';

    public function acepto(Proveedor_pendiente $provpend) {
        Model::StartTrans();
        developer_log("crear cliente y_usuario");
        $this->crear_cliente_y_usuario($provpend);
        $this->cbu_marchand = $this->crear_cuenta_bancaria($provpend);
        developer_log("crear boleta");
//        $this->crear_boleta($provpend);
        $this->crear_transacciones($provpend);
//        var_dump( $this->crear_retiro($cbu_marchand,$provpend));
        if(!Model::HasFailedTrans()){
            $this->enviar_correo_acepto($provpend);
            $this->enviar_correo_pagador_acepto($provpend);
        }
        if(!Model::HasFailedTrans() and Model::CompleteTrans()){
         return true;
        }
        return false;
        // marca aceptado
    }

    private function crear_retiro(Cbumarchand $cbumarchand, Proveedor_pendiente $provp) {
        return false;
    }

    public function rechazo(Landing_proveedor $landing) {
        $landing->set_view('views/proveedor_rechazo.html');

        $this->reversar($landing->get_move_referencia());


        $this->enviar_correo_rechazo($landing);
        $this->enviar_correo_rechazo_pagador($landing);

        $landing->set_acepto(false);
    }

    public function reversar($id_referencia) {

        Model::StartTrans();
        $mov = new Moves();
        $mov->get($id_referencia);
        $mov->set_id_authstat(Authstat::TRANSACCION_PAGO_A_PROVEEDOR_REVERTIDO);
        if (!$mov->set()) {
            Model::FailTrans();
        }
        $reverso = new Transaccion();
        if (!$reverso->reversar($mov)) {
            Gestor_de_log::set("Error al reversar el movimiento.");
            Model::FailTrans();
        }

        $recordset = Moves::select_move_refencia($mov->get_id_referencia());
        // cambio el status del movimiento reversado para 
        // que no se liste en boffice.
        if ($recordset and $recordset->rowCount() > 0) {
            developer_log("encontro en moves");
            $registro = $recordset->FetchRow();
            $move = new Moves();
            $move->get($registro['id_moves']);
            $move->set_id_authstat(Authstat::TRANSACCION_PAGO_A_PROVEEDOR_REVERTIDO);
            if (!$move->set()) {
                Model::FailTrans();
                Gestor_de_log::set("Error al actualizar el movimiento.");
            }
        }
        if (Model::CompleteTrans()) {
            Gestor_de_log::set("Reverso realizado correctamente.");
        }
    }

    public function procesar(Landing_proveedor $padre) {
        //  $this->padre = $padre;
        //      $this->proveedor = $padre->get_row();
        $padre->set_view('views/proveedor_correcto.html');
    }

    public function putData(View $view, Landing_proveedor $model, $p) {
        $val = $this->armar_data($model);
        $p = $view->getElementById('texto_p');
        $n = $view->createTextNode($val);
        $p->appendChild($n);
    }

    private function enviar_correo_rechazo_pagador(Landing_proveedor $landing) {

        $emisor = Gestor_de_correo::MAIL_COBRODIGITAL_INFO;
        $id = $landing->get_id_marchand();
        $marchand = new Marchand();
        $marchand->get($id);

        $mail = $marchand->get_email();
        $asunto = "Pago a proveedor Rechazado";
//        $mensaje = $this->get_text_pagador_rechazo($landing);
        $view = new View();
        $view->cargar("views/proveedores_pagador_rechazo.html");
        $nombre_prov = $view->getElementById("nombre_prov");
        $nombre_prov2 = $view->getElementById("nombre_prov2");
        $nombre_marchand = $view->getElementById("nombre_marchand");
        $nombre_prov->appendChild($view->createTextNode($landing->getNombreProveedor()));
        $nombre_prov2->appendChild($view->createTextNode($landing->getNombreProveedor()));
        $nombre_marchand->appendChild($view->createTextNode($marchand->get_apellido_rs()));

        $mensaje = $view->saveHTML();
        if (!Gestor_de_correo::enviar($emisor, $mail, $asunto, $mensaje)) {
            developer_log('falló al enviar correo');
            return false;
        }

        return true;
    }

    private function get_text_pagador_rechazo(Landing_proveedor $landing) {

        return ' Estimado marchand su ' . $landing->getNombreProveedor() .
                ' rechazó un pago de ' . $landing->get_concepto() . ' por el  importe: ' . $landing->get_monto();
    }

    private function armar_data(Landing_proveedor $l) {

        developer_log(json_encode($l));


        $c = $l->cliente();
        $t = "\n\n\n\n Estimado PROVEEDOR: $c ";
        $t .= "Bienvenido a  la plataforma de recaudación y pago mas importante de Latinoamérica.";
        $t .= "Les estamos enviando un correo a su casilla personal";

        $t .= " con los datos necesarios para acceder a nuestra plataforma";
        $t .= " también instructivos y acceso al soporte de la misma.";

        return $t;
    }

    private function crear_boleta(Proveedor_pendiente $proveedor) {

        // $variables_requeridas = array('importe', 'concepto');

        $this->boleta = new Boleta_comprador();
        $id_marchand = $proveedor->get_id_marchand();
        $importes = array($proveedor->get_monto());
//        var_dump($importes);
        $fecha = new DateTime($proveedor->get_fecha_venc());

        $fechas_vencimiento = array($fecha->format('d/m/Y')); # TEMP

        $concepto = $proveedor->get_concepto();
        $datos = array();
        developer_log("id_marchand= $id_marchand");
        $marchand = new Marchand(); # Pensar en elevar esto
        $marchand->get($proveedor->get_id_marchand());

        $datos[Boleta_comprador::NOMBRE] = $marchand->get_nombre();
        $datos[Boleta_comprador::APELLIDO] = $marchand->get_apellido_rs();
        $datos[Boleta_comprador::CORREO] = $marchand->get_email();
        $datos[Boleta_comprador::DOCUMENTO] = $marchand->get_documento();
        $direccion = ucwords($marchand->get_gr_calle() . ' ' . $marchand->get_gr_numero() . ' ' . $marchand->get_gr_piso() . ' ' . $marchand->get_gr_depto());
        $datos[Boleta_comprador::DIRECCION] = $direccion;

        if (!$this->boleta->crear($id_marchand, $datos, $importes, $fechas_vencimiento, $concepto)) {
            developer_log("!!NO CREO BOLETA");
            throw new Exeception("No pudo crear boleta");
        }
        return true;
    }

    private function enviar_correo_pagador_acepto(Proveedor_pendiente $prove) {

        $emisor = Gestor_de_correo::MAIL_COBRODIGITAL_INFO;
        $mail = $this->cliente->marchand->get_email();
        $asunto = "Pago a proveedor aceptado";
//        $mensaje = $this->get_text_pagador_acepto($prove);
        $view = new View();
        $view->cargar("views/proveedores_proveedor_aceptado.html");
        $nombre_prov = $view->getElementById("nombre_prov");
        $nombre_prov2 = $view->getElementById("nombre_prov2");
        $marchand = new Marchand();
        $marchand->get($prove->get_id_marchand());
        $nombre_marchand = $view->getElementById("nombre_marchand");
        $nombre_prov->appendChild($view->createTextNode($marchand->get_apellido_rs()));
        $nombre_prov2->appendChild($view->createTextNode($prove->get_razon_social()));
        $nombre_marchand->appendChild($view->createTextNode($marchand->get_apellido_rs()));
        $id_moves = $view->getElementById("id_moves");
        $id_moves->appendChild($view->createTextNode($prove->get_id_move()));
        $concepto = $view->getElementById("concepto");
        $concepto->appendChild($view->createTextNode($prove->get_concepto()));
        $monto = $view->getElementById("monto");
        $monto->appendChild($view->createTextNode($prove->get_monto()));
        $mensaje = $view->saveHTML();
        if (!Gestor_de_correo::enviar($emisor, $mail, $asunto, $mensaje)) {
            developer_log('falló al enviar correo');
            return false;
        }

        return false;
    }

    function get_text_pagador_acepto(Proveedor_pendiente $prove) {

        return ' Estimado marchand su proveedor' . $prove->get_nombre_completo() .
                ' acepto un pago en   concepto: ' . $prove->get_concepto() . ' por el  monto: ' .
                $prove->get_monto();
    }

    private function crear_transacciones(Proveedor_pendiente $proveedor) {

        $egreso = new Transaccion();
        $id_marchand_egreso = $proveedor->get_id_marchand();

        $id_mp_egreso = Mp::PAGO_A_PROVEEDOR;
        $id_mp_ingreso = Mp::COBRO_COMO_PROVEEDOR;

        if ($proveedor->get_traslada()) { // asumo ?
            developer_log(" TRANSALADA TRUE ");
            $traslado_comision_ingreso = true;
            $traslado_comision_egreso = false;
        } else {
            developer_log(" TRANSALADA FALSE ");
            $traslado_comision_ingreso = false;
            $traslado_comision_egreso = true;
        }

        $reverso = new Transaccion();
        $mov = new Moves();

        $mov->get($proveedor->get_id_move());
        
        if(!$reverso->reversar($mov)){
            error_log("No se pudo reversar");
            Model::FailTrans();
        }
        $recordset = Moves::select_move_refencia($mov->get_id_referencia());

        if ($recordset->rowCount() > 0) {
            $registro = $recordset->FetchRow();
            $move = new Moves();
            $move->get($registro['id_moves']);
            $move->set_id_authstat(753);
            $move->set();
        }

        $monto_pagador_egreso = $proveedor->get_monto();
        $fecha_egreso = new DateTime('now');
        $id_referencia_egreso = $proveedor->get_id_marchand_final();

        try{
            error_log("CREANDO EGRESO $id_mp_egreso $id_marchand_egreso");
        if (!Model::HasFailedTrans() and $egreso->crear($id_marchand_egreso, $id_mp_egreso, $monto_pagador_egreso, $fecha_egreso, $id_referencia_egreso, null, null, $traslado_comision_egreso)) {
            error_log("se creo el pago a proveedor");
            $ingreso = new Transaccion();
            $retiro = new Transaccion();
            $id_marchand_ingreso = $this->cliente->marchand->get_id_marchand();
            $monto_pagador_ingreso = $proveedor->get_monto();
            list($monto_pagador, $pag_fix, $pag_var, $monto_cd, $cdi_fix, $cdi_var, $monto_marchand) = $ingreso->calculo_directo($id_marchand_ingreso, $id_mp_ingreso, $monto_pagador_ingreso);
            //  $dif = abs($monto_pagador - $monto_marchand);
            //  $monto_pagador_ingreso += $dif;
            $fecha_ingreso = $fecha_egreso;
            $id_referencia_ingreso = $id_referencia_egreso;
            error_log("CREANDO INGRESO $id_mp_ingreso $id_marchand_ingreso");
            if (!Model::HasFailedTrans() and $ingreso->crear($id_marchand_ingreso, $id_mp_ingreso, $monto_pagador_ingreso, $fecha_ingreso, $id_referencia_ingreso, null, null, $traslado_comision_ingreso)) {
                error_log("se creo el cobro proveedor");
                $fecha = Clone $fecha_egreso;
                $fecha->sub(new DateInterval("P1D"));
                $ingreso->moves->set_fecha_liq($fecha_egreso->format("Y-m-d"));
                developer_log("ACTUALIZANDO FECHA_LIQ");
                if ($ingreso->moves->set()) {
                    $id_referencia = $this->cbu_marchand->get_id();
                    $id_mp = Mp::RETIROS;
                    $id_marchand = $id_marchand_ingreso;
                    $monto_pagador_retiro = $ingreso->moves->get_monto_marchand();
//                    $suma_comision = $pag_fix + $pag_var + $cdi_fix + $cdi_var;
//                    $monto_pagador_retiro = $monto_pagador - $suma_comision;
                    $fecha = $fecha_ingreso;
                    $id_pricing_pag=Pricing::ID_PRICING_PAG_SIN_COMISION;
                    $id_pricing_cdi= Pricing::ID_PRICING_CDI_SIN_COMISION;
                    if ($retiro->crear($id_marchand, $id_mp, $monto_pagador_retiro, $fecha, $id_referencia,null,null,false,$id_pricing_pag,$id_pricing_cdi)) {
                        developer_log('cobro al proveedor ok');
                        return true;
                    }
                }
            }
            else {
                developer_log("Fallo al insertar el ingreso");
                Model::FailTrans ();
            }
        }
        else{
            developer_log("Fallo al insertar el egreso");
            Model::FailTrans ();
        
        }
        } catch (Exception $e){
            developer_log($e->getMessage());
            return false;
        }
        return false;
    }

//    private function crear_transacciones_old() {
//
//        $move = new Moves();
//        $move->get($this->proveedor->get_id_move());
//
//        $move->set_id_mp(Mp::PAGO_A_PROVEEDOR);
//        $move->set_id_authstat(Authstat::TRANSACCION_PAGO_A_PROVEEDOR_APROBADO);
//        if ($move->set()) {
//            $ingreso = new Transaccion();
//            $id_marchand_ingreso = $this->cliente->marchand->get_id_marchand();
//            $id_mp_ingreso = Mp::COBRO_COMO_PROVEEDOR;
//            $monto_pagador_ingreso = $this->proveedor->get_monto();
//            $fecha_ingreso = new DateTime('now');
//            $id_referencia_ingreso = $this->boleta->barcode_1->get_id_barcode();
//
//            if (!$ingreso->crear($id_marchand_ingreso, $id_mp_ingreso, $monto_pagador_ingreso, $fecha_ingreso, $id_referencia_ingreso)) {
//                throw new Exception('No se  pudo crear la transaccion de cobro');
//            }
//        } else {
//            throw new Exception('Error No se puede realizar la operación');
//        }
//
//        return true;
//    }

    private function crear_cliente_y_usuario(Proveedor_pendiente $proveedor) {

        $this->cliente = Cliente::create();
        $marchand = new Marchand();
        $marchand->set_id_pfpj($proveedor->get_id_tipo());
        $marchand->set_apellido_rs($proveedor->get_razon_social());
        $marchand->set_nombre($proveedor->get_nombre_completo());
        $marchand->set_email($proveedor->get_mail());
        $marchand->set_id_peucd(Peucd::COBRODIGITAL);
        $marchand->set_id_subrubro('787');
        $marchand->set_id_civa('1');
        $marchand->set_id_tipodoc(Tipodoc::CUIT_CUIL);
        $marchand->set_documento($proveedor->get_cuil());
        $marchand->set_id_tiposoc('1');
        $marchand->set_id_liquidac('1');
        $marchand->set_id_authstat(Authstat::ACTIVO);
        $marchand->set_fechaingreso((new DateTime("now"))->format("Y-m-d"));
        //
        $usumarchand = new Usumarchand();
	if($proveedor->get_nombre_completo()!="")
            $usumarchand->set_username(substr(str_replace(" ", "", $proveedor->get_nombre_completo()), 0,6));
        else 
            $usumarchand->set_username(substr(str_replace(" ", "", $proveedor->get_razon_social()), 0,6));
        $usumarchand->set_userpass(password_aleatorio(3,"."));
        $usumarchand->set_completo($proveedor->get_razon_social());
        $usumarchand->set_usermail($proveedor->get_mail());
        $usumarchand->set_id_authstat(Authstat::ACTIVO);


        if (!$this->cliente->crear($marchand, $usumarchand)) {
            throw new Exception("No pudo crear Cliente");
        }

        $id_m = $this->cliente->usumarchand->get_id();
        $permiso = new Usupermiso();
        $permiso->set_id_usumarchand($id_m);
        $idm = $this->cliente->usumarchand->get_id();

        // actualiza el nuevo marchand del proveedor pendiente
        $proveedor->set_id_marchand_final($marchand->get_id_marchand());

//        $permiso->dar_permiso_retiro();
        $proveedor->set();

        $permiso->set_id(null);
//        $permiso->dar_permiso_retiro_viejo(); // boton del retiro del viejo

        $permiso->set_id(null);
//        $permiso->dar_permiso_retiro_boton_viejo();
        // $this->modificar_mail_alta();
        return true;
    }

    function enmascarar_cbu($cbu) {

        $enmascarado = substr($cbu, 0, 3);
        $enmascarado .= 'X XXXX XXXX XXXX';
        $enmascarado .= substr($cbu, -4, 4);
        return $enmascarado;
    }

    public function enviar_correo_acepto($prove) {
        $file = $this->cargarFile();
        return $this->enviar_correo_bienvenida($prove);
    }

    private function get_text_html($prove) {

        $mensaje = "<b>" . "Estimado Proveedor '" . "</b>" . $prove->nombre_completo() . " '" . "<br>";
        $mensaje .= "<br>";
        $mensaje .= "<br>";
        $mensaje .= "<br>";
        $mensaje .= "Bienvenido a  la plataforma de recaudación y pago mas importante de Latinoamérica." . "<br>";
        $mensaje .= "Usted podrá gestionar sus fondos recaudados y generar nueva deuda para sus clientes y, también, podrá pagar a sus proveedores." . "<br>";
        $mensaje .= "<br>";
        $mensaje .= "<br>";
        $mensaje .= "Los datos de acceso a su cuenta son:" . "<br>";
        $mensaje .= "https://www.cobrodigital.com/" . "<br>";


        $mensaje .= "            Comercio:" . $this->comercio . "<br>";
        $mensaje .= "            Usuario:" . $this->user . "<br>";
        $mensaje .= "Palabra Clave:" . $this->passw . "<br>";

        $mensaje .= "            CobroDigital" . "<br>";

        $mensaje .= "            S.E.U.O." . "<br>";
        return $mensaje;
    }

    private function enviar_correo_bienvenida(Proveedor_pendiente $prove) {

        $emisor = Gestor_de_correo::MAIL_COBRODIGITAL_INFO;
        $mail = $prove->get_mail();
        $asunto = "Bienvenido a Cobro Digital";
        $view = new View();
        $view->cargar("views/proveedores_mail_bienvenida.html");
        $mercalpha = $this->cliente->marchand->get_mercalpha();
        $nombre_prov = $view->getElementById("nombre_prov");
        $nombre_prov->appendChild($view->createTextNode($prove->get_razon_social()));
        $mph = $view->getElementById("mercalpha");
        $mph->appendChild($view->createTextNode($mercalpha));
        $usuario = $view->getElementById("usuario");
        $usuario->appendChild($view->createTextNode($this->user));
        $clave = $view->getElementById("clave");
        $clave->appendChild($view->createTextNode($this->passw));
        $mensaje = $view->saveHTML();
        //
        //  developer_log($this->file);
        //
        if (!is_file($this->file)) {
            developer_log("NO ES FILE $this->file");
        }
        $vista_pdf = new View();
        $vista_pdf->loadHTMLFile(PATH_CDEXPORTS . $mercalpha . "/comprobante_de_alta.html");
        $path = PATH_CDEXPORTS . $mercalpha . "/comprobante_de_alta.pdf";

        $pdf = new Gestor_de_pdf(Gestor_de_pdf::MODO_PORTRAIT, Gestor_de_pdf::PAPEL_A4);
        $html = $vista_pdf->saveHTML();
        if (!($pdf->crear_pdf($html, $path))) {
            Gestor_de_log::set("Fallo al generar el pdf", 1);
        }
        developer_log($path);
        developer_log("enviando emisor:$emisor,  mail:$mail  asunto:$asunto path:$path");
        Gestor_de_correo::enviar($emisor, $mail, $asunto, $mensaje, $path);
        return true;
    }

    private function cargarFile() {
        libxml_use_internal_errors(true);
        $view = new DOMDocument('1.0', 'utf-8');
        libxml_clear_errors();
        $this->file = PATH_CDEXPORTS . $this->cliente->marchand->get_mercalpha() . '/comprobante_de_alta.html';
        if (!$view->loadHTMLFile($this->file)) {
            developer_log("fallo " . $this->file);
            return false;
        }

        $elemento = $view->getElementById('loginComercio');
        $this->comercio = $elemento->nodeValue;

        developer_log($this->comercio);


        $elemento = $view->getElementById('loginUsuario');
        $this->user = $elemento->nodeValue;

        developer_log($this->user);

        $elemento = $view->getElementById('loginPassword');
        $this->passw = $elemento->nodeValue;
        developer_log($this->passw);
        libxml_clear_errors();
        unset($view);

        return true;
    }

    private function enviar_correo_rechazo(Landing_proveedor $landing) {
        developer_log('enviando correo rechazo');
        $emisor = Gestor_de_correo::MAIL_COBRODIGITAL_INFO;
        developer_log($landing->get_mail());
        $mail = $landing->get_mail();
        $asunto = " Rechazo de pago de proveedor";
        $id = $landing->get_id_marchand();
        // recupera marchand
        $m = new Marchand();
        $m->get($id);
        unset($id);
        $view = new View();
        $view->cargar("views/proveedor_mail_rechazo.html");
        $nombre_prov = $view->getElementById("nombre_prov");
        $nombre_prov->appendChild($view->createTextNode($landing->getNombreProveedor()));
        $nombre_marchand = $view->getElementById("nombre_marchand");
        $nombre_marchand->appendChild($view->createTextNode($m->get_apellido_rs()));
        $nombre_marchand2 = $view->getElementById("nombre_marchand2");
        $nombre_marchand2->appendChild($view->createTextNode($m->get_apellido_rs()));

        $d = $view->saveHTML();
        if (!Gestor_de_correo::enviar($emisor, $mail, $asunto, $d)) {
            developer_log('falló al enviar correo');
            return true;
        }
        return true;
    }

    private function getNombreProveedor(Landing_proveedor $landing) {
        return $landing->getNombreProveedor();
    }

    private function crear_cuenta_bancaria(Proveedor_pendiente $prove) {
       
        # Simil Mod_xiii create_post
        $datos['id_marchand'] = $this->cliente->marchand->get_id_marchand();
        $datos['id_tipodoc'] = Tipodoc::CUIT_CUIL;
        $datos['id_authstat'] = Authstat::ACTIVO;
//        $datos['cbu'] = $prove->get_cbu();
        $datos['scbu'] = $prove->get_cbu();
//        $datos['xcbu'] = Cbumarchand::cifrar_cbu($prove->get_cuil());
        $datos['referencia'] = self::DUMMY_REFERENCIA;
        $datos['cuit'] = $prove->get_cuil();
        $datos['titular'] = $prove->nombre_completo();
        $datos['id_banco']= substr($prove->get_cbu(), 0,3);
        $datos['id_tipocuenta']= 9; //9 es adefinir
        $cbumarchand = new Cbumarchand($datos);
        if ($cbumarchand->set()) {
            return $cbumarchand;
        }
        return false;
    }

}
