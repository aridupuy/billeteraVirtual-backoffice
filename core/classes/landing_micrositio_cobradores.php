<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of landing_micrositio_cobradores
 *
 * @author ariel
 * 
 * 0940640004001201171010000000000000000018
 */
class landing_micrositio_cobradores extends Landing_micrositio {

    const NOMBRE_COOKIE = 'MICROSITIO_COBRADORES';
    const ACTIVAR_DEBUG = false;
    const NOMBRE_USUARIO = "email";
    const NOMBRE_PASSWORD = "password";
    const SIGNAL_SALIR = "salir";
    const CLAVE = "soyYoAri";
    const CLAVE_CIFRADOR = "MicroSitiO";
    const IVA=0.21;
    public $get;
    public $post;
    public $cookie;
    public $view;
    public $files;
    public $cobrador;
    private $factura;
    private $id_marchand;

    public function __construct($get, $post, $cookie, $files) {
        $this->get = $get;
        $this->post = $post;
        $this->cookie = $cookie;
        $this->files = $files;
        $this->view = new View();
    }

    public function dispatch() {

        $precarga = false;
        $hash = new Gestor_de_hash(self::CLAVE_CIFRADOR);
        if (isset($this->get["key"]) and isset($this->get["nav"])) {
            $this->post["key"] = $hash->descifrar($this->get["key"]);
            $this->get["nav"] = $hash->descifrar($this->get["nav"]);
        }
        if (isset($this->get["nav"]) and $this->get["nav"] == "cambia_pass" and ! isset($this->post["nav"])) {
            $this->cobrador = null;
            return $this->cambia_pass();
        }
        if (isset($this->post["nav"]) and $this->post["nav"] == "cambia_pass_post") {
            $this->cobrador = null;
            return $this->cambia_pass_post();
        }

        if (isset($this->post["nav"]) and $this->post["nav"] == "registrar_post") {
            $this->cobrador = null;
            return $this->registrar_post();
        }
        if (isset($this->post["nav"]) and $this->post["nav"] == "olvide_contraseña") {
            $this->cobrador = null;
            return $this->cambia_pass_no_loggin();
        }
        if (isset($this->post["nav"]) and $this->post["nav"] == "paso_2") {
            $this->cobrador = null;
            return $this->cambia_pass_paso_2();
        }
        if (isset($this->post["nav"]) and $this->post["nav"] == "ver_terminos_y_condiciones") {
            $this->cobrador = null;
            return $this->ver_terminos_y_condiciones();
        }
        if (isset($this->post[self::NOMBRE_USUARIO]))
            if (isset($this->post[self::NOMBRE_USUARIO]) AND isset($this->post[self::NOMBRE_PASSWORD])) {
                try {
                    $id_cobrador = $this->autenticar_cobrador($this->post[self::NOMBRE_USUARIO], $this->post[self::NOMBRE_PASSWORD]);
                } catch (Exception $e) {
                    return $this->view_loggin($e->getMessage());
                }
                if ($id_cobrador) {
//                    if (!ACTIVAR_HASH)
                    $this->cookie[self::NOMBRE_COOKIE] = $this->cifrar_cookie($id_cobrador, self::CLAVE);
//                    else {
//                        $this->cookie[self::NOMBRE_COOKIE] = $id_cobrador;
//                    }
                    $precarga = true;
                } else if (!isset($this->post["nav"]) OR $this->post["nav"] !== "registrar_post") {
                    return $this->view_loggin("Los datos no son correctos.");
                }
            }
        if (isset($this->post[self::SIGNAL_SALIR])) {
            $this->salir();
            $precarga = true;
        }
        if (!isset($this->cookie[self::NOMBRE_COOKIE])) {

            $this->view_loggin();
        } else {
            $this->loggear_con_cookie();
        }
//        print_r($this->post["nav"]);
        if (isset($this->post["nav"])) {
            if ($this->post["nav"] == "obtener_localidades") {
                return $this->obtener_localidades();
            }

            if (!isset($this->cobrador) and $this->post["nav"] != "registrar" and $this->post["nav"] != "registrar_post")
                return $this->view_loggin("Session expirada");
            switch ($this->post["nav"]) {
                case "registrar":
                    return $this->registrar();
                case "home":
                    return $this->home();
                case "pagar":
                    return $this->pagar();
                case "detalle":
                    return $this->detalle();
                case "rendir":
                    return $this->rendir();
                case "rendir_post":
                    return $this->rendir_post();
                case "ingresar_manual":
                    return $this->ingresar_manual();
                case "ingresar_manual_post":
                    return $this->ingresar_manual_post();
                case "agregar_marchand":
                    return $this->agregar_marchand();
                case "agregar_marchand_post":
                    return $this->agregar_marchand_post();
                case "prevalidar_rendicion":
                    return $this->prevalidar_rendicion();
                case "ingresar_manual_prevalidar":
                    return $this->ingresar_manual_prevalidar();
                case "regenerar_tiquet":
                    return $this->regenerar_tiquet();
                case "enviar_correo":
                    return $this->enviar_correo();
                case "enviar_correo_post":
                    return $this->enviar_correo_post();
                case "generar_pdf":
                    return $this->generar_pdf();
                case "cambia_pass":
                    return $this->cambia_pass();
                case "cambia_pass_post":
                    return $this->cambia_pass_post();
                case "agregar_cbu":
                    return $this->agregar_cbu();
                case "agregar_cbu_post":
                    return $this->agregar_cbu_post();
                case "cargar_facturacion":
                    return $this->cargar_facturacion();
                case "cargar_facturacion_post":
                    return $this->cargar_facturacion_post();
                case "ver_facturaciones":
                    return $this->ver_facturaciones();
                case "ver_factura":
                    return $this->ver_factura();
                default :
                    return $this->view;
            }
        }
        return $this->view;
    }

    private function view_loggin($mensaje = false) {
        $this->view->cargar("views/cobradores.loggin.html");
        $titulo = $this->view->getElementById("titulo");
        $form = $this->view->getElementById("miFormulario");
        $boton = $this->view->getElementById("boton");
        $input = $this->view->createElement("input");
        $input->setAttribute("name", "email");
        $input->setAttribute("placeholder", "Email");
        $input->setAttribute("type", "text");
        $form->insertBefore($input, $boton);
        $input = $this->view->createElement("input");
        $input->setAttribute("name", "password");
        $input->setAttribute("placeholder", "contraseña");
        $input->setAttribute("type", "password");
        $form->insertBefore($input, $boton);
        if ($mensaje) {
            $div_mens = $this->view->getElementById("mensaje");
            $span = $this->view->createElement("span", $mensaje);
            $div_mens->appendChild($span);
        }
        $titulo->appendChild($this->view->createTextNode("Micrositio Cobradores"));
        return true;
    }

    private function registrar() {
        $this->view->cargar("views/cobradores.registrar.html");
        $provincia = $this->view->getElementById('provincia');
        $recordSet = Provincia::select_id_pais(1);
        foreach ($recordSet as $row) {
            $option = $this->view->createElement('option', $row["provincia"]);
            $option->setAttribute('value', $row["id_provincia"]);
            $provincia->appendChild($option);
        }
    }

    protected function obtener_localidades() {
        $this->view = new View();
//        error_log(json_encode($this->post));
        if (isset($this->post["provincia"])) {
            $recordset = Localidad::select_id_provincia($this->post["provincia"]);
            if ($recordset->rowCount() <= 1) {
                $option = $this->view->createElement('option', "Seleccione una opcion");
//                $option->setAttribute('value', $row["id_localidad"]);
                $this->view->appendChild($option);
            }
            foreach ($recordset as $row) {
                $option = $this->view->createElement('option', $row["localidad"]);
                $option->setAttribute('value', $row["id_localidad"]);
                $this->view->appendChild($option);
//                error_log("Localidad obtenida de id_provincia".$this->post["provincia"]);
            }
            return $this->view;
        }
    }

    private function ver_terminos_y_condiciones() {
        $this->view->cargar("views/cobradores.tyc.html");
        $campos = $this->view->getElementById("campos");
        $campos->setAttribute("value", serialize($this->post));
    }

    private function registrar_post() {
        Model::StartTrans();
        developer_log($this->post["campos"]);
        $this->post = unserialize($this->post["campos"]);
        developer_log(json_encode($this->post));
        //por ahora solo victoria
        $this->post["nro_empresa"] = "094064"; //victoria
        $mensaje = false;
        try {
            $marchand_ente = $this->identificar_marchand($this->post["nro_empresa"]);
        } catch (Exception $e) {
            return $this->view_loggin($e->getMessage());
        }
        if ($this->verificar_preexistencia()) {
            Cliente::$es_cobrador = true;
            $cliente = new Cliente();
            $cliente::set_es_cobrador(true);
            $marchand = new Marchand();
            $usumarchand = new Usumarchand();
            //generacion de marchand
            $marchand->set_apellido_rs($this->post["apellido"]);
            $marchand->set_nombre($this->post["apellido"]);
            if (validar_cuit($this->post["documento"]))
                $marchand->set_documento($this->post["documento"]);
            else {
                return $this->view_loggin("El cuit no es válido.");
            }
            $marchand->set_minirs($this->post["apellido"]);
            $marchand->set_email($this->post["email"]);
            $marchand->set_evatest(2);
            $marchand->set_id_peucd(Peucd::COBRADOR);
            $marchand->set_id_civa(1);
            $marchand->set_gr_codigopostal($this->post["cp"]);
            $marchand->set_gr_calle($this->post["direccion"]);
            $marchand->set_gr_numero($this->post["numero"]);
            $marchand->set_gr_id_localidad($this->post["localidad"]);
            $marchand->set_gr_id_provincia($this->post["provincia"]);
            $marchand->set_id_authstat(Authstat::INACTIVO);
            $marchand->set_id_subrubro(487);
            $marchand->set_id_tipodoc(2);
            $marchand->set_id_tiposoc(2);
            $marchand->set_id_liquidac(1);

            //generacion de usuario
            $usumarchand->set_username($this->post["email"]);
            $usumarchand->set_userpass($this->post["password"]);
            $usumarchand->set_id_authstat(Authstat::INACTIVO);
            $usumarchand->set_usermail($this->post["email"]);
            $usumarchand->set_usuvip(0);
            $usumarchand->set_completo($this->post["nombre"] . " " . $this->post["apellido"]);
            $usumarchand->set_userarea("Cobrador");
            try {
                if ($cliente->crear($marchand, $usumarchand)) {
                    $cobrador = new Cobrador();
                    $cobrador_marchand = new Cobrador_marchand();
                    $gestor = new Gestor_de_hash(self::CLAVE_CIFRADOR);
                    $cobrador->set_password($gestor->cifrar($this->post["password"]));
                    $cobrador->set_email($this->post["email"]);
                    $cobrador->set_nombre($this->post["nombre"]);
                    $cobrador->set_id_marchand($cliente->marchand->get_id_marchand());
                    $cobrador->set_id_authstat(Authstat::REGISTRO);

//            $cobrador->set_nro_operador($this->post["nro_operador"]);
                    $cobrador->set_documento($this->post["documento"]);
                    if ($cobrador->set()) {
                        $cobrador_marchand->set_id_cobrador($cobrador->get_id_cobrador());
                        $cobrador_marchand->set_nro_operador($this->post["documento"]);

                        $cobrador_marchand->set_id_marchand($marchand_ente->get_id_marchand());
                        $cobrador_marchand->set_codigo_ente($marchand_ente->get_codigo_ente());
                        list($fija, $variable) = $this->obtener_comisiones($marchand->get_id_marchand());
                        $cobrador_marchand->set_comision_fija($fija);
                        $cobrador_marchand->set_comision_variable($variable);
                        if ($cobrador_marchand->set())
                            Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_NORESPONDER, $cobrador->get_email(), "Registro Cobro Digital.", "Muchas gracias por registrarse como cobrador en Cobrodigital. Para empezar a operar ingrese a https://www.cobrodigital.com:14365/cobradores. 
                                     Si desea adherirse al servicio complete la solicitud adjuntada y enviela a atencionaclientes@cobrodigital.com informando su numero de usuario: " . $cobrador->get_id_cobrador() . "
                                    ", PATH_PUBLIC . "/SOLICITUD DE ALTA COMO AGENTE COBRODIGITAL v.2.pdf");
                        else {
                            Model::FailTrans();
                            $mensaje = "Error al registrar en el ente.";
                        }
                    } else {
                        Model::FailTrans();
                        $mensaje = "Ha ocurrido un error al registrarse";
                    }
                } else {
                    Model::FailTrans();
                    $mensaje = "Error al generar el cliente.";
                }
            } catch (Exception $ex) {
                Model::FailTrans();
                $mensaje = $ex;
            }
        } else {
            Model::FailTrans();
            $mensaje = "El email ya se encuentra registrado";
        }
        if (!Model::HasFailedTrans() AND Model::CompleteTrans())
            return $this->view_loggin($mensaje);

        return $this->view_loggin("Error al generar la cuenta.");
    }

    protected function obtener_comisiones($id_marchand) {
        $con = new Configuracion();
        $conf = $con->obtener_configuracion($id_marchand);
        return array($conf[Configuracion::CONFIG_ENTIDAD_COBRADOR][Configuracion::CONFIG_COBRADOR][Configuracion::CONFIG_CONFIG_COBRADOR_COMISION_FIJA], $conf[Configuracion::CONFIG_ENTIDAD_COBRADOR][Configuracion::CONFIG_COBRADOR][Configuracion::CONFIG_CONFIG_COBRADOR_COMISION_VARIABLE]);
    }

    protected function identificar_marchand($nro_empresa) {
        $recordset = Marchand_ente::select(array("codigo_ente" => $nro_empresa));
        if ($recordset and $recordset->rowCount() == 1) {
            $row = $recordset->fetchRow();
            return new Marchand_ente($row);
        }
        throw new Exception("No esta registrado ningun comercio con ese codigo de empresa.");
        return false;
    }

    private function verificar_preexistencia() {
        $recordset = Cobrador::select(array("email" => $this->post["email"]));
        if ($recordset and $recordset->rowCount() == 0)
            return true;
        return false;
    }

    private function autenticar_cobrador($usuario, $password) {

        //completar_loggeo con la tabla de cobradores
        $gestor = new Gestor_de_hash(self::CLAVE_CIFRADOR);
        $recordset = Cobrador::select(array("email" => $usuario, "password" => $gestor->cifrar($password, null, true)));
        if ($recordset and $recordset->rowCount() == 1) {
            $row = $recordset->fetchRow();
            if ($row["id_authstat"] != Authstat::HABILITADO and $row["id_authstat"] != Authstat::CON_LOTES_PENDIENTES) {
                throw new Exception("El usuario se encuentra desactivado. Por favor comuniquese con CobroDigital indicando el mail de registro.");
            }
            $this->post["nav"] = "home";
            $this->cobrador = new Cobrador($row);
            return $this->cobrador->get_id();
        }
        return false;
    }

    private function loggear_con_cookie() {
        $id_cobrador = $this->descifrar_cookie($this->cookie[self::NOMBRE_COOKIE], self::CLAVE);
        if ($id_cobrador and $id_cobrador != "") {
            $cobrador = new Cobrador();
            $cobrador->get($id_cobrador);
            if ($cobrador->get_id_authstat() != Authstat::HABILITADO and $cobrador->get_id_authstat() != Authstat::CON_LOTES_PENDIENTES) {
                return $this->view_loggin("El usuario no se encuentra activo. Por favor comuniquese con CobroDigital indicando Su numero de cobrador: " . $cobrador->get_id() . ".");
            }
            $this->cobrador = $cobrador;
            if (!isset($this->post["nav"]))
                $this->post["nav"] = "home";
            return;
        }
        return false;
    }

    private function salir() {

        unset($this->cookie[self::NOMBRE_COOKIE]);
        return $this->view_loggin();
    }

    private function obtener_cantidad_de_lotes() {
        $recordset = Lote_cobrador::select_cantidad_lotes_pendientes($this->cobrador);
        if ($recordset and $recordset->rowCount() == 1) {
            $row = $recordset->fetchRow();
            return $row['count'];
        }
    }

    private function obtener_cantidad_permitida_de_lotes() {
        $configs = Configuracion::obtener_configuracion_de_tag($this->cobrador->get_id_cobrador(), Configuracion::CONFIG_ENTIDAD_COBRADOR, Configuracion::CONFIG_COBRADOR); //se asume que es un marchand para esta configuracion;
        return $configs[Configuracion::CONFIG_CONFIG_COBRADOR][Configuracion::CONFIG_FIELD_CANTIDAD_LOTE_COBRADOR];
    }

    private function home($mensaje = false) {

        $this->view->cargar("views/cobradores.home.html");
        if ($this->obtener_cantidad_de_lotes($this->cobrador) >= $this->obtener_cantidad_permitida_de_lotes($this->cobrador)) {
            $advertencia = $this->view->getElementById("mensaje_linea");
            $advertencia->appendChild($this->view->createTextNode("Su cuenta se ha bloqueado por lotes sin rendir acumulados. Comuniquese con CobroDigital indicando su numero de cobrador: " . $this->cobrador->get_id_cobrador() . "."));
            $barcode = $this->view->getElementById("barcode");
            $barcode->parentNode->removeChild($barcode);
            $manual = $this->view->getElementById("manual");
            $manual->parentNode->removeChild($manual);
            $this->cobrador->set_id_authstat(Authstat::CON_LOTES_PENDIENTES);
            $this->cobrador->set();
            Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, $this->cobrador->get_email(), "Lotes vencidos", "Estimado, " . $this->cobrador->get_nombre(). ". Usted posee lotes sin rendir, Automaticamente se le bloqueara la posibilidad de seguir generando lotes en tanto no rinda sus lotes pendientes. Saludos.");
        } else if ($this->verificar_tolerancia()) {
            $advertencia = $this->view->getElementById("mensaje_linea");
            $advertencia->appendChild($this->view->createTextNode("Su cuenta se ha bloqueado por no rendir los lotes a tiempo o bien supera el limite de tolerancia.Comuniquese con CobroDigital indicando su numero de cobrador: " . $this->cobrador->get_id_cobrador() . "."));
            $barcode = $this->view->getElementById("barcode");
            $barcode->parentNode->removeChild($barcode);
            $manual = $this->view->getElementById("manual");
            $manual->parentNode->removeChild($manual);
            $this->cobrador->set_id_authstat(Authstat::CON_LOTES_PENDIENTES);
            Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, $this->cobrador->get_email(), "Tolerancia de Deuda", "Estimado, " . $this->cobrador->get_nombre(). ". Usted posee lotes rendidos con diferencias de +/- $100 rinda un lote agregando o quitando su credito para desbloquear.");
            $this->cobrador->set();
        } else if ($this->verificar_bloqueo_factura()) {
            $advertencia = $this->view->getElementById("mensaje_linea");
            $advertencia->appendChild($this->view->createTextNode("Su cuenta se ha bloqueado por no presentar las facturas en el termino solicitado. Comuniquese con CobroDigital indicando su numero de cobrador: " . $this->cobrador->get_id_cobrador() . "."));
            $barcode = $this->view->getElementById("barcode");
            $barcode->parentNode->removeChild($barcode);
            $manual = $this->view->getElementById("manual");
            $manual->parentNode->removeChild($manual);
            $this->cobrador->set_id_authstat(Authstat::CON_LOTES_PENDIENTES);
            Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, $this->cobrador->get_email(), "Tolerancia de Deuda", "Estimado, " . $this->cobrador->get_nombre(), ". Usted posee lotes rendidos sin facturar por favor presentelos para desbloquear automáticamente.");
            $this->cobrador->set();
        }
        else {
            $advertencia = $this->view->getElementById("ADVERTENCIA");
            $advertencia->parentNode->removeChild($advertencia);
            $this->cobrador->set_id_authstat(Authstat::HABILITADO);
            $this->cobrador->set();
        }
        $fecha = new DateTime("now");
        $lotes_pendientes = Control::select_lotes_pendientes($this->cobrador, $fecha);
        $lotes_rendidos = Control::select_lotes_rendidos($this->cobrador, $fecha);
        $lotes_abiertos = Control::select_lotes_abiertos($this->cobrador, $fecha);
        $lotes_cerrados = Control::select_lotes_cerrados($this->cobrador, $fecha);
        $tabla = $this->view->getElementById("tabla_lote_abierto");
        $contenedor = $this->view->getElementById("cont");
        $marchands = Cobrador_marchand::select(array("id_cobrador" => $this->cobrador->get_id_cobrador()));
        if ($marchands and $marchands->fetchRow() > 1) {
            foreach ($marchands as $marchand) {
                $mar = new Marchand();
                $mar->get($marchand["id_marchand"]);
                $mercalpha = $mar->get_mercalpha();
                $url_logo = "https://cobrodigital.com/images/imgempresa/logoscomerciales/" . $mercalpha . ".png";
                $div = $this->view->createElement('div');
                $div->setAttribute('class', 'info_marchand button');
                $div2 = $this->view->createElement('div');
                $div->appendChild($div2);
                $div2->setAttribute('class', 'btn btn-register');
//                $span = $this->view->createElement('span', $mar->get_apellido_rs());
//                $div2->appendChild($span);
                $img = $this->view->createElement('img');
                $img->setAttribute("src", $url_logo);
                $img->setAttribute("class", "logo");
                $div2->appendChild($img);
                $frm = $this->view->createElement('form');
                $frm->setAttribute('method', 'post');
                $input = $this->view->createElement('input');
                $input->setAttribute('type', 'hidden');
                $input->setAttribute('name', 'nav');
                $input->setAttribute('value', 'ingresar_manual');

                $input2 = $this->view->createElement('input');
                $input2->setAttribute('type', 'hidden');
                $input2->setAttribute('name', 'id_marchand');
                $input2->setAttribute('value', $mar->get_id_marchand());
                $frm->appendChild($input);
                $frm->appendChild($input2);
                $div2->appendChild($frm);
                $contenedor->appendChild($div);
            }
        }
        foreach ($lotes_abiertos as $lote) {
            $fila = $this->view->createElement("div");
            $fila->setAttribute("class", "fila");
            $unafecha = DateTime::createFromFormat("Y-m-d", $lote["fecha_gen"]);
            $span_nlote = $this->view->createElement("span", $lote["id_lote_cobrador"]);
            $unafecha->add(new DateInterval("P1D"));
            $spancierre = $this->view->createElement("span", $unafecha->format("Y-m-d"));
            $spancobranza = $this->view->createElement("span");

            $importe_total = $lote["sum"];
            $spanimporte_total = $this->view->createElement("span", "$" . $importe_total);
            $ganancia_cobrador = $this->calcular_ganancia_cobrador($importe_total, $lote["id_marchand"]);
            $spanganancia = $this->view->createElement("span", "$" . $ganancia_cobrador);
            $div_detalle = $this->view->createElement("div");
            $div_detalle->setAttribute("class", "detalle");

            $form = $this->view->createElement("form");
            $form->setAttribute("method", "post");
            $form->setAttribute("action", "/cobradores");
            $div_detalle->appendChild($form);
            $input = $this->view->createElement("input");
            $input->setAttribute("type", "submit");
            $input->setAttribute("value", "Detalle");

            $hidden = $this->view->createElement("input");
            $hidden->setAttribute("type", "hidden");
            $hidden->setAttribute("value", $lote['id_cobrador']);
            $hidden->setAttribute("name", "id_cobrador");
            $form->appendChild($hidden);
            $hidden = $this->view->createElement("input");
            $hidden->setAttribute("type", "hidden");
            $hidden->setAttribute("value", "detalle");
            $hidden->setAttribute("name", "nav");
            $form->appendChild($hidden);
            $form->appendChild($input);

            $div_rendir = $this->view->createElement("div");
            $div_rendir->setAttribute("class", "rendir");
            $fila->appendChild($span_nlote);
            $fila->appendChild($spancierre);
            $fila->appendChild($spancobranza);
            $fila->appendChild($spanimporte_total);
            $fila->appendChild($spanganancia);
            $fila->appendChild($div_detalle);
            $fila->appendChild($div_rendir);
            $tabla->appendChild($fila);
        }
        $tabla = $this->view->getElementById("tabla_lote_pendiente");
//        print_r($lotes_pendientes->rowCount());

        foreach ($lotes_pendientes as $lote) {
            $lote_cobrador = new Lote_cobrador();
            $lote_cobrador->get($lote["id_lote_cobrador"]);

            $fila = $this->view->createElement("div");
            $fila->setAttribute("class", "fila");
            $unafecha = DateTime::createFromFormat("Y-m-d", $lote["fecha_gen"]);
            $unafecha->add(new DateInterval("P1D"));
            $span_nlote = $this->view->createElement("span", $lote["id_lote_cobrador"]);
            $spancierre = $this->view->createElement("span", $unafecha->format("Y-m-d"));

            $pagado = $this->obtener_importe_pagado($lote_cobrador->get_id_lote_cobrador());

            if ($pagado != 0)
                $spancobranza = $this->view->createElement("span", "$" . $pagado);
            else
                $spancobranza = $this->view->createElement("span", "$0");
            $importe_total = $lote["sum"];
            $spanimporte_total = $this->view->createElement("span", "$" . $importe_total);
            $ganancia_cobrador = $this->calcular_ganancia_cobrador($importe_total, $lote["id_marchand"]);
            $spanganancia = $this->view->createElement("span", "$" . $ganancia_cobrador);
            $div_detalle = $this->view->createElement("div");
            $div_detalle->setAttribute("class", "detalle");
            $form = $this->view->createElement("form");
            $form->setAttribute("method", "post");
            $form->setAttribute("action", "/cobradores");
            $div_detalle->appendChild($form);
            $input = $this->view->createElement("input");
            $input->setAttribute("type", "submit");
            $input->setAttribute("value", "Detalle");

            $hidden = $this->view->createElement("input");
            $hidden->setAttribute("type", "hidden");
            $hidden->setAttribute("value", $lote['id_lote_cobrador']);
            $hidden->setAttribute("name", "revno");
            $form->appendChild($hidden);
            $hidden = $this->view->createElement("input");
            $hidden->setAttribute("type", "hidden");
            $hidden->setAttribute("value", "detalle");
            $hidden->setAttribute("name", "nav");
            $form->appendChild($hidden);
            $form->appendChild($input);

            $div_rendir = $this->view->createElement("div");
            $div_rendir->setAttribute("class", "rendir");
            if ($lote["id_authstat"] == Authstat::ACTIVO) {
                $form = $this->view->createElement("form");
                $form->setAttribute("method", "post");
                $form->setAttribute("action", "/cobradores");
                $div_rendir->appendChild($form);
                $input = $this->view->createElement("input");
                $input->setAttribute("type", "submit");
                $input->setAttribute("value", "Rendir Lote");

                $hidden = $this->view->createElement("input");
                $hidden->setAttribute("type", "hidden");
                $hidden->setAttribute("value", $lote['id_lote_cobrador']);
                $hidden->setAttribute("name", "revno");
                $form->appendChild($hidden);
                $hidden = $this->view->createElement("input");
                $hidden->setAttribute("type", "hidden");
                $hidden->setAttribute("value", "rendir");
                $hidden->setAttribute("name", "nav");
                $form->appendChild($hidden);
                $form->appendChild($input);
            }



            $fila->appendChild($span_nlote);
            $fila->appendChild($spancierre);
            $fila->appendChild($spancobranza);
            $fila->appendChild($spanimporte_total);
            $fila->appendChild($spanganancia);
            $fila->appendChild($div_detalle);
            $fila->appendChild($div_rendir);
            $tabla->appendChild($fila);
        }
        $tabla = $this->view->getElementById("tabla_lote_rendido");
        foreach ($lotes_rendidos as $lote) {
            $lote_cobrador = new Lote_cobrador();
            $lote_cobrador->get($lote["id_lote_cobrador"]);

            $fila = $this->view->createElement("div");
            $fila->setAttribute("class", "fila");
            $unafecha = DateTime::createFromFormat("Y-m-d", $lote["fecha_gen"]);
            $unafecha->add(new DateInterval("P1D"));
            $span_nlote = $this->view->createElement("span", $lote["id_lote_cobrador"]);
            $spancierre = $this->view->createElement("span", $unafecha->format("Y-m-d"));
            $pagado = $this->obtener_importe_pagado($lote_cobrador->get_id_lote_cobrador());

            if ($pagado != 0)
                $spancobranza = $this->view->createElement("span", "$" . $pagado);
            else
                $spancobranza = $this->view->createElement("span", "$0");
            $importe_total = $lote["sum"];
            $spanimporte_total = $this->view->createElement("span", "$" . $importe_total);
            $ganancia_cobrador = $this->calcular_ganancia_cobrador($importe_total, $lote["id_marchand"]);
            $spanganancia = $this->view->createElement("span", "$" . $ganancia_cobrador);
            $div_detalle = $this->view->createElement("div");
            $div_detalle->setAttribute("class", "detalle");
            $form = $this->view->createElement("form");
            $form->setAttribute("method", "post");
            $form->setAttribute("action", "/cobradores");
            $div_detalle->appendChild($form);
            $input = $this->view->createElement("input");
            $input->setAttribute("type", "submit");
            $input->setAttribute("value", "Detalle");

            $hidden = $this->view->createElement("input");
            $hidden->setAttribute("type", "hidden");
            $hidden->setAttribute("value", $lote['id_lote_cobrador']);
            $hidden->setAttribute("name", "revno");
            $form->appendChild($hidden);
            $hidden = $this->view->createElement("input");
            $hidden->setAttribute("type", "hidden");
            $hidden->setAttribute("value", "detalle");
            $hidden->setAttribute("name", "nav");
            $form->appendChild($hidden);
            $form->appendChild($input);

            $div_rendir = $this->view->createElement("div");
            $div_rendir->setAttribute("class", "rendir");
            if ($lote["id_authstat"] == Authstat::ACTIVO) {
                $form = $this->view->createElement("form");
                $form->setAttribute("method", "post");
                $form->setAttribute("action", "/cobradores");
                $div_rendir->appendChild($form);
                $input = $this->view->createElement("input");
                $input->setAttribute("type", "submit");
                $input->setAttribute("value", "Rendir Lote");

                $hidden = $this->view->createElement("input");
                $hidden->setAttribute("type", "hidden");
                $hidden->setAttribute("value", $lote['id_lote_cobrador']);
                $hidden->setAttribute("name", "revno");
                $form->appendChild($hidden);
                $hidden = $this->view->createElement("input");
                $hidden->setAttribute("type", "hidden");
                $hidden->setAttribute("value", "rendir");
                $hidden->setAttribute("name", "nav");
                $form->appendChild($hidden);
                $form->appendChild($input);
            }



            $fila->appendChild($span_nlote);
            $fila->appendChild($spancierre);
            $fila->appendChild($spancobranza);
            $fila->appendChild($spanimporte_total);
            $fila->appendChild($spanganancia);
            $fila->appendChild($div_detalle);
            $fila->appendChild($div_rendir);
            $tabla->appendChild($fila);
        }
        $tabla = $this->view->getElementById("tabla_lote_cerrado");
        foreach ($lotes_cerrados as $lote) {
            $lote_cobrador = new Lote_cobrador();
            $lote_cobrador->get($lote["id_lote_cobrador"]);
            $fila = $this->view->createElement("div");
            $fila->setAttribute("class", "fila");
            $unafecha = DateTime::createFromFormat("Y-m-d", $lote["fecha_gen"]);
            $unafecha->add(new DateInterval("P1D"));
            $span_nlote = $this->view->createElement("span", "Lote_" . $lote["id_cobrador"] . "_" . $unafecha->format("Y-m-d"));
            $spancierre = $this->view->createElement("span", $unafecha->format("Y-m-d"));
            $spancobranza = $this->view->createElement("span");
            $importe_total = $lote["sum"];
            $pagado = $this->obtener_importe_pagado($lote_cobrador->get_id_lote_cobrador());
            if ($pagado != 0)
                $spancobranza = $this->view->createElement("span", "$" . $pagado);
            else
                $spancobranza = $this->view->createElement("span", "$0");
            $spanimporte_total = $this->view->createElement("span", "$" . $importe_total);
            $ganancia_cobrador = $this->calcular_ganancia_cobrador($importe_total, $lote["id_marchand"]);
            $spanganancia = $this->view->createElement("span", "$" . $ganancia_cobrador);

            $div_detalle = $this->view->createElement("div");
            $div_detalle->setAttribute("class", "detalle");
            $div_rendir = $this->view->createElement("div");
            $div_rendir->setAttribute("class", "rendir");
            $fila->appendChild($span_nlote);
            $fila->appendChild($spancierre);
            $fila->appendChild($spancobranza);
            $fila->appendChild($spanimporte_total);
            $fila->appendChild($spanganancia);
            $fila->appendChild($div_detalle);
            $fila->appendChild($div_rendir);
            $tabla->appendChild($fila);
        }
        if ($mensaje !== false) {
            $mensaje_div = $this->view->getElementById("mensaje");
            $span_mensaje = $this->view->createElement("span", $mensaje);
            $mensaje_div->appendChild($span_mensaje);
        }
    }

    private function pagar() {
        $barcode = $this->post["barcode"];
        $clase = Cobros_cobradores::obtener_ente_cobrador($barcode, $this->cobrador->get_id_cobrador());
        if ($clase === false)
            return $this->home("El codigo de ente no está habilitado para operar.");
        $objeto = new $clase($this->cobrador);
        try {
            if ($objeto->procesar_cobranza($barcode) != false) {
                $mensaje = "Codigo_pagado_correctamente";
            } else
                $mensaje = 'Error al interpretar el Código';
        } catch (Exception $e) {
            return $this->home($e->getMessage());
        }
        return $this->home($mensaje);
    }

    protected function developer_log($mensaje) {
        error_log("MICROSITIO-COBRADORES:" . $mensaje);
    }

    protected function obtener_importe_total($id_lote) {
        $recordSet = Lote_cobrador::select_monto_total($id_lote);
        if (!$recordSet OR $recordSet->rowCount() == 0)
            return 0;
        $row = $recordSet->fetchRow();
        return $row['sum'];
    }

    protected function calcular_ganancia_cobrador($importe_total, $id_marchand,$flag=false) {
//        $cobrador=new Cobrador_marchand();
        $cobradores = Cobrador_marchand::select(array("id_cobrador" => $this->cobrador->get_id_cobrador(), "id_marchand" => $id_marchand));
        if ($cobradores->rowCount() >= 1) {
            $cobrador_marchand = new Cobrador_marchand($cobradores->fetchRow());
            $ganancia_bruta = ($importe_total - $cobrador_marchand->get_comision_fija()) * ($cobrador_marchand->get_comision_variable() / 100);
		if(!$flag)
	            return number_format($ganancia_bruta, 2);
		else
		    return $ganancia_bruta;
        } else
            return "0";
    }

    protected function detalle() {
        $this->view->cargar("views/cobradores.detalle.html");
//        print_r($this->post);
        $tabla = $this->view->getElementById("tabla_detalle");

        if (isset($this->post["revno"])) {
            $recordSet = Sabana::select_lotes_cobradores_pendientes($this->post['revno']);
        } else {
            $recordSet = Cobros_cobrador::select_detalle_lote($this->post['id_cobrador'], new DateTime("now"));
        }
        foreach ($recordSet as $row) {
            $fecha = DateTime::createFromFormat("Y-m-d", $row["vencimiento"]);
            $div = $this->view->createElement('div');
            $div->setAttribute('class', 'fila');
            $span = $this->view->createElement("span", $fecha->format("Y-m-d"));
            $div->appendChild($span);
            $span = $this->view->createElement("span", $row["ramo"]);
            $div->appendChild($span);
            $span = $this->view->createElement("span", $row["poliza"]);
            $div->appendChild($span);
            $span = $this->view->createElement("span", $row["endoso"]);
            $div->appendChild($span);
            $span = $this->view->createElement("span", "$" . number_format($row["importe"], 2));
            $div->appendChild($span);
//            $span1 = $this->view->createElement("span");
            $form2 = $this->view->createElement('form');
            $form2->setAttribute('method', 'post');
            $form2->setAttribute('class', 'form_detalle');
            $span = $this->view->createElement("span", "Ver comprobante");
            $span->setAttribute('class', 'vr_comp');
            $hidden = $this->view->createElement("input");
            $hidden->setAttribute('type', 'hidden');
            $hidden->setAttribute('name', 'nav');
            $hidden->setAttribute('value', 'regenerar_tiquet');
            $hidden2 = $this->view->createElement("input");
            $hidden2->setAttribute('type', 'hidden');
            $hidden2->setAttribute('name', 'id_cobro_cobrador');
            $hidden2->setAttribute('value', $row["id_cobro_cobrador"]);
            $form2->appendChild($span);
            $form2->appendChild($hidden);
            $form2->appendChild($hidden2);
//            $span1->appendChild($form2);
            $div->appendChild($form2);
            $tabla->appendChild($div);
        }
        return $this->view;
    }

    protected function rendir($mensaje = false) {

        $this->view->cargar("views/cobradores.rendir.html");
        unset($this->post["nav"]);
        $this->view->cargar_variables($this->post);
        if (isset($this->post["serialize_post"])) {
            $post = unserialize($this->post["serialize_post"]);
            unset($this->post);
            $this->post["revno"] = $post["revno"];
        }
        if ($mensaje !== false) {
            $mensaje_div = $this->view->getElementById("mensaje");
            $span_mensaje = $this->view->createElement("span", $mensaje);
            $mensaje_div->appendChild($span_mensaje);
        }
        $lote = new Lote_cobrador();
        $lote->get($this->post["revno"]);
        $cobrador = new Cobrador();
        $cobrador->get($lote->get_id_cobrador());
        $marchand = new Marchand();
        $marchand->get($cobrador->get_id_marchand());
        if ($marchand->get_id_peucd() != Peucd::GALICIA) {
            $pagofondos = $this->view->getElementById("pagofondos");
            $pagofondos->parentNode->removeChild($pagofondos);
        }
        $afavor = $this->view->getElementById("saldo_a_favor");
        $deudor = $this->view->getElementById("saldo_deudor");
        $credito2 = $this->view->getElementById("credito2");
        $credito = $this->view->getElementById("credito");
        if ($this->cobrador->get_credito() > 0) {
            $afavor->parentNode->removeChild($afavor);
            $credito2->appendChild($this->view->createTextNode($this->cobrador->get_credito()));
        } elseif ($this->cobrador->get_credito() < 0) {
            $deudor->parentNode->removeChild($deudor);
            $credito->appendChild($this->view->createTextNode($this->cobrador->get_credito() * (-1)));
        } else {
            $afavor->parentNode->removeChild($afavor);
            $deudor->parentNode->removeChild($deudor);
        }

        $recordset = Lote_cobrador::select_monto_total_lote($lote->get_id_lote_cobrador());
        $monto_pagado = $this->obtener_importe_pagado($this->post["revno"]);
        if ($recordset and $recordset->rowCount() == 1) {
            $row = $recordset->fetchRow();
            $monto = $row["sum"] - floatval(str_replace(",", "", $this->calcular_ganancia_cobrador($row["sum"], $lote->get_id_marchand()))) - $monto_pagado;
        } else {
            $monto = 0;
        }

        $montolote = $this->view->getElementById("montolote");
        $montolote->appendChild($this->view->createTextNode($monto));
        $id_lote = $this->view->createElement("input");
        $id_lote->setAttribute("type", "hidden");
        $id_lote->setAttribute("name", "id_lote");
        $id_lote->setAttribute("value", $lote->get_id_lote_cobrador());
        $form = $this->view->getElementById("miFormulario");
        $form->appendChild($id_lote);
        $nro_lote = $this->view->getElementById("nro_lote");
        $nro_lote3 = $this->view->getElementById("nro_lote3");
        $nro_lote3->appendChild($this->view->createTextNode($lote->get_id_lote_cobrador()));
        $nro_lote->appendChild($this->view->createTextNode("Lote_" . $lote->get_id_cobrador() . "_" . $lote->get_fecha_gen()));
        $serialize = $this->view->getElementById("serialize");
        $serialize->setAttribute("value", serialize($this->post));
        $cbu_marchand_select = $this->view->getElementById("cbumarchand");
        $recordset = Cbumarchand::select(array("id_marchand" => $this->cobrador->get_id_marchand(), "id_authstat" => 2));
        foreach ($recordset as $row) {
            $option = $this->view->createElement("option", $row["referencia"]);
            $option->setAttribute("value", $row["id_cbumarchand"]);
            $cbu_marchand_select->appendChild($option);
        }
    }

    protected function prevalidar_rendicion() {
        $this->view->cargar("views/cobradores.prevalidar_rendicion.html");
        $nro_lote = $this->view->getElementById('nro_lote');
        $nro_lote2 = $this->view->getElementById('nro_lote2');
        $monto_lote = $this->view->getElementById('monto_lote');
        $fecha_rendido = $this->view->getElementById('fecha_rendido');
        $nombre_marchand = $this->view->getElementById('nombre_marchand');
        $serialize = $this->view->getElementById('serialize');
        $serialize_image = $this->view->getElementById('serialize_image');
        $lote = new Lote_cobrador();
        $lote->get($this->post["id_lote"]);
        $marchand = new Marchand();
        $marchand->get($lote->get_id_marchand());
        $fecha = DateTime::createFromFormat("Y-m-d H:i", $this->post["fecha"] . " " . $this->post["hora"]);
        $nro_lote->appendChild($this->view->createTextNode("Lote_" . $lote->get_id() . "_" . $fecha->format("Y-m-d")));
        $nro_lote2->appendChild($this->view->createTextNode("Lote_" . $lote->get_id() . "_" . $fecha->format("Y-m-d")));
        $monto_lote->appendChild($this->view->createTextNode($this->post["importe"]));
        $fecha_rendido->appendChild($this->view->createTextNode($fecha->format("d/m/Y")));
        $nombre_marchand->appendChild($this->view->createTextNode($marchand->get_apellido_rs()));
        $serialize->setAttribute("value", serialize($this->post));
//        print_r($this->files);
        if (isset($this->files["XI3v6HOboOqmmSz7ZJnYGQ=="]) AND $this->files["XI3v6HOboOqmmSz7ZJnYGQ=="]["tmp_name"] != "") {
            if ($this->files["XI3v6HOboOqmmSz7ZJnYGQ=="]["size"] > 1000000) {
                Model::FailTrans();
                return $this->rendir("Archivo demasiado grande.");
            }
            $tmp_name = $this->files["XI3v6HOboOqmmSz7ZJnYGQ=="]["tmp_name"];
            error_log("Moviendo archivo como temporal");
            $gestor = new Gestor_de_disco();
            $nombre = $gestor->mover_archivo_subido($this->files["XI3v6HOboOqmmSz7ZJnYGQ=="]["tmp_name"], PATH_CDEXPORTS, $this->files["XI3v6HOboOqmmSz7ZJnYGQ=="]["name"]);
            $porcentaje = 2;
            $size = getimagesize(PATH_CDEXPORTS . $nombre);
            list($ancho, $alto) = $size;
            $nuevo_ancho = $ancho * $porcentaje;
            $nuevo_alto = $alto * $porcentaje;
            $thumb = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);
            error_log("Cambiando tamaño a archivo.");
            switch ($this->files["XI3v6HOboOqmmSz7ZJnYGQ=="]["type"]) {
                case "image/jpeg":
                    $origen = imagecreatefromjpeg(PATH_CDEXPORTS . $nombre);
                    imagecopyresized($thumb, $origen, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);
                    imagejpeg($thumb, PATH_CDEXPORTS . $nombre, 3);
                    break;
                case "image/gif":
                    $origen = imagecreatefromgif(PATH_CDEXPORTS . $nombre);
                    imagecopyresized($thumb, $origen, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);
                    imagegif($thumb, PATH_CDEXPORTS . $nombre, 3);
                    break;
                case "image/png":
                    $origen = imagecreatefrompng(PATH_CDEXPORTS . $nombre);
                    imagecopyresized($thumb, $origen, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);
                    imagepng($thumb, PATH_CDEXPORTS . $nombre, 3);
                    break;
            }
            error_log("Guardando temporal del archivo.");
            $serialize_image->setAttribute("value", PATH_CDEXPORTS . $nombre);
        }
    }

    protected function rendir_post() {
        $buffer = false;
        if (isset($this->post["serialized_image"])) {
//            $buffer= pg_escape_bytea(base64_decode ($this->post["serialized_image"]));
            $tmp_name = $this->post["serialized_image"];
            $fp = fopen($tmp_name, "rb");
            $buffer = fread($fp, filesize($tmp_name));
            error_log("Convirtiendo el archivo en bytea");
            $buffer = pg_escape_bytea($buffer);
            error_log("Borrando el archivo");
            @unlink($tmp_name, $fp);
        }
        $this->post = unserialize($this->post["serialized_post"]);
        if (!isset($this->post["id_cbu"]) and isset($this->post["tipo_transaccion"]) AND $this->post["tipo_transaccion"] == "trans") {
            return $this->home("Rendicion incorrecta falta definir la cuenta bancaria.");
        }

        Model::StartTrans();
        $lote = new Lote_cobrador();
        $lote->get($this->post["id_lote"]);
        $lote_rendicion = new Lote_rendicion();
        $lote_rendicion->set_id_cobrador($lote->get_id_cobrador());
        $lote_rendicion->set_id_lote_cobrador($lote->get_id_lote_cobrador());
        $lote_rendicion->set_id_banco($this->post["id_banco"]);
        $lote_rendicion->set_id_authstat(Authstat::LOTE_RENDIDO);
        if (isset($this->post["id_cbu"]))
            $lote_rendicion->set_id_cbumarchand($this->post["id_cbu"]);
        $mensaje = "Rendicion Procesada Correctamente.";
        if (!isset($this->post["fecha"])) {
            Model::FailTrans();
            return $this->home("Rendicion incorrecta, falta el parametro fecha.");
        }
        if (!isset($this->post["hora"])) {
            Model::FailTrans();
            return $this->home("Rendicion incorrecta, falta el parametro hora.");
        }
//        if (!isset($this->post["motivo"]) and ! isset($this->post["nro_operacion"])) {
//            return $this->home("Rendicion incorrecta, falta el parametro motivo/nro_operacion.");
//        }
        if (!isset($this->post["detalle"])) {
            Model::FailTrans();
            return $this->home("Rendicion incorrecta, falta el parametro detalle.");
        }
        if (!isset($this->post["importe"])) {
            Model::FailTrans();
            return $this->home("Rendicion incorrecta, falta el parametro importe.");
        }
        $hoy = new DateTime("now");
        $fecha = DateTime::createFromFormat("Y-m-d H:i", $this->post["fecha"] . " " . $this->post["hora"]);
        if ($fecha > $hoy) {
            Model::FailTrans();
            return $this->home("Rendicion incorrecta, La fecha no puede ser mayor a la actual.");
        }
        //if ($fecha > $hoy) {
        //  Model::FailTrans();
        // return $this->home("Rendicion incorrecta, La fecha y la hora no pueden ser mayor a la actual.");
        // }
        $lote_rendicion->set_fecha($fecha->format("Y-m-d H:i:s"));
        if (isset($this->post["motivo"]))
            $lote_rendicion->set_motivo($this->post["motivo"]);
        elseif (isset($this->post["nro_operacion"]))
            $lote_rendicion->set_motivo($this->post["nro_operacion"]);
        else
            $lote_rendicion->set_motivo("Rendido con dinero en cuenta.");
        $lote_rendicion->set_detalle($this->post["detalle"]);
        $calculo = ($this->post["importe"] + $this->cobrador->get_credito()) - $this->calcular_importe_a_rendir($lote);
        //if ($calculo <= ($this->obtener_monto_tolerancia_total() * (-1))) {
        //  return $this->home("La rendicion fallo debido a que se supera el limite inferior de credito para su cuenta.");
        //}
        if ($calculo >= ($this->obtener_monto_tolerancia_total())) {
            return $this->home("La rendicion fallo debido a que se supera el limite superior de credito para su cuenta.");
        }
        $lote_rendicion->set_importe($this->post["importe"]);
        if (isset($buffer) and $buffer != false) {
            $lote_rendicion->set_file($buffer);
        }

        if (!Model::HasFailedTrans() and $lote_rendicion->set()) {
            $credito = $this->verificar_credito($lote_rendicion, $lote);
            if ($credito === null) {
                $mensaje = "Error al procesar la Rendicion el monto a supera el limite superior establecido.";
                Model::FailTrans();
            } else if ($credito === false) {
                $mensaje = "Se realizó un pago parcial.";
            } else {
                $this->cobrador->set_credito($credito);
                if (!$this->cobrador->set()) {
                    Model::FailTrans();
                } else {
                    $lote->set_id_authstat(Authstat::LOTE_RENDIDO);
                    if (!$lote->set()) {
                        $mensaje = "Error al procesar la rendicion";
                        Model::FailTrans();
                    }
                }
            }
        } else {
            Model::FailTrans();
            $mensaje = "Error al registrar la rendicion.";
        }

        if (!Model::HasFailedTrans() and isset($this->post["tipo_transaccion"]) AND $this->post["tipo_transaccion"] == "pago_en_cuenta") {
            try {
                //reservo el dinero
                $transaccion = new Transaccion();
                if (!$transaccion->crear($this->cobrador->get_id_marchand(), Mp::RENDICION_COBRANZA, $this->post["importe"], (new DateTime("now")), $lote->get_id_marchand())) {
                    Model::FailTrans();
                    $mensaje = "Error al procesar el pago con fondos en cuenta.";
                } else {
                    Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_NORESPONDER, $this->cobrador->get_email(), "Lote de cobranza Nº " . $lote->get_id_lote_cobrador(), "El importe \n $" . $lote->get_importe_rendido() . " Fuè debitado de su cuenta CobroDigital cuando el mismo sea aprobado serà acreditado en la cuenta del comercio correspondiente.");
                }
            } catch (Exception $e) {
                Model::FailTrans();
                $mensaje = $e->getMessage();
            }
        }
        if (!Model::HasFailedTrans() and Model::CompleteTrans())
            return $this->home($mensaje);
        return $this->home($mensaje);
    }

    protected function calcular_importe_a_rendir(Lote_cobrador $lote) {
        $total = $this->obtener_importe_total($lote->get_id());
        $total = $total - str_replace(",", "", $this->calcular_ganancia_cobrador($total, $lote->get_id_marchand()));
        return $total;
    }

    protected function verificar_credito(Lote_rendicion $lote_rendicion, Lote_cobrador $lote) {
        $credito = $this->cobrador->get_credito();
        $total = $this->obtener_importe_total($lote->get_id());
        $total = $total - (str_replace(",", "", $this->calcular_ganancia_cobrador($total, $lote->get_id_marchand())));
        $pagado = $this->obtener_importe_pagado($lote->get_id());
        $diferencia = $total - $pagado;
//        $rendido = $lote_rendicion->get_importe();
//        $diferencia = abs($rendido)-abs($total)  ;
        $config = Configuracion::obtener_configuracion_de_tag_multiple($this->cobrador->get_id_marchand(), Entidad::ENTIDAD_LOTE_COBRADOR, Configuracion::CONFIG_COBRADOR);
        $monto_tolerancia_total = $config["Tolerancia_de_rendicion"][0];
        if ($total === 0.00) {
            return 0;
        }

        if ((( $credito + $diferencia) >= ($monto_tolerancia_total * (-1)) and ( $credito + $diferencia) <= $monto_tolerancia_total)) {
            return $credito + $diferencia;
        } else
        if (($credito + $diferencia) < $monto_tolerancia_total) {
            return null;
        }
        return false;
    }

    protected function verificar_tolerancia() {
        $config = Configuracion::obtener_configuracion_de_tag_multiple($this->cobrador->get_id_marchand(), Entidad::ENTIDAD_LOTE_COBRADOR, Configuracion::CONFIG_COBRADOR);
        $monto_tolerancia_total = $config["Tolerancia_de_rendicion"][0];
        $dias_tolerancia = $config["dias de tolerancia"][0];
        $error = true;
        $recordset = Lote_cobrador::select_lotes_sin_rendir(new DateTime("now"), $dias_tolerancia, $this->cobrador->get_id());
        if ($recordset and $recordset->rowCount() == 0)
            $error = false;

        if ($this->cobrador->get_credito() >= $monto_tolerancia_total) {
            $error = true;
        }
        return $error;
    }

    protected function obtener_monto_tolerancia_total() {
        $config = Configuracion::obtener_configuracion_de_tag_multiple($this->cobrador->get_id_marchand(), Entidad::ENTIDAD_LOTE_COBRADOR, Configuracion::CONFIG_COBRADOR);
        $monto_tolerancia_total = $config["Tolerancia_de_rendicion"][0];
        return $monto_tolerancia_total;
    }

    protected function verificar_totalidad(Lote_rendicion $lote_rendicion, Lote_cobrador $lote) {
//        $cobrador=new Cobrador();
//        $cobrador->get($lote->get_id_cobrador());
        $importe_total = $this->obtener_importe_total($lote->get_id());
        $importe_total = $importe_total - $this->calcular_ganancia_cobrador($importe_total, $lote->get_id_marchand());
        $pagado = $this->obtener_importe_pagado($lote->get_id());
        if ($importe_total <= $lote_rendicion->get_importe() + $pagado)
            return true;
        return false;
    }

    protected function obtener_importe_pagado($id_lote) {
        $recordset = Lote_cobrador::select_total_pagado($id_lote);
        if ($recordset and $recordset->rowCount() >= 1) {
            $row = $recordset->fetchRow();
            return $row["sum"];
        }
        return 0.0;
    }

    protected function ingresar_manual() {
        $this->view->cargar("views/cobradores.ingreso_manual.html");
        if (isset($this->post["serialized_post2"])) {
            $this->post = unserialize($this->post["serialized_post2"]);
            if (isset($this->post["cod_ente"])) {
                $marchand_ente = $this->identificar_marchand($this->post["cod_ente"]);
                $this->post["id_marchand"] = $marchand_ente->get_id_marchand();
            }
            $this->view->cargar_variables($this->post);
        }
        $recordset = Cobrador_marchand::select_marchand_asociado($this->cobrador->get_id_cobrador());
        $codigo_ente = $this->view->getElementById("codigo_ente");
        foreach ($recordset as $row) {
            $option = $this->view->createElement("option", $row["apellido_rs"]);
            $option->setAttribute("value", $row["codigo_ente"]);
            $codigo_ente->appendChild($option);
            if ($row["id_marchand"] == $this->post["id_marchand"]) {
                $option->setAttribute("selected", "selected");
            }
        }
    }

    protected function ingresar_manual_prevalidar() {
        $this->view->cargar("views/cobradores.prevalidar_ingreso.html");
        $serialize = $this->view->getElementById("serialize");
        $serialize->setAttribute("value", serialize($this->post));
        $serialize2 = $this->view->getElementById("serialize2");
        $serialize2->setAttribute("value", serialize($this->post));
        $monto = $this->view->getElementById("monto");
        $monto->appendChild($this->view->createTextNode(number_format($this->post["importe"], 2, ",", ".")));
        $poliza = $this->view->getElementById("poliza");
        $poliza->appendChild($this->view->createTextNode($this->post["poliza"]));
        $marchand_ente = $this->identificar_marchand($this->post["cod_ente"]);
        $marchand = new Marchand();
        $marchand->get($marchand_ente->get_id_marchand());
        $nombre_marchand = $this->view->getElementById("nombre_marchand");
        $nombre_marchand->appendChild($this->view->createTextNode($marchand->get_apellido_rs()));
    }

    protected function ingresar_manual_post() {
        $this->post = unserialize($this->post["serialized_post"]);
        $codigo_ente = $this->post["cod_ente"];
        $ramo = $this->post["ramo"];
        $poliza = $this->post["poliza"];
        $endoso = $this->post["endoso"];
        $importe = $this->post["importe"];
        $barcode = $this->regenerar_codigo_de_barras($this->post);
        $clase = Cobros_cobradores::obtener_ente_cobrador($barcode, $this->cobrador->get_id_cobrador());
        $objeto = new $clase($this->cobrador);
        try {
            if ($objeto->procesar_cobranza($barcode, $endoso, $ramo) != false) {
                $mensaje = "Pago procesado correctamente.";
                $error = false;
            } else {
                $mensaje = 'Error al procesar la cobranza.';
                $error = true;
            }
        } catch (Exception $e) {
            return $this->home($e->getMessage());
        }
        if ($error)
            return $this->home($mensaje);
        return $this->mostrar_comprobante($mensaje, $objeto);
    }

    protected function mostrar_comprobante($mensaje, $objeto /* objeto de la clase cobrador */, $css = true) {
        $this->view->cargar("views/cobradores_comprobante.html");
        $lote = $objeto->get_lote();
        $cobrador = $objeto->get_cobrador();
        $cobro = $objeto->get_ultimo_cobro();
        $nro_comp = $this->view->getElementById('nro_comprobante');
        $nro_comp->appendChild($this->view->createTextNode($lote->get_id_lote_cobrador()));
        $marchand = new Marchand();
        $marchand->get($lote->get_id_marchand());
        $empresa = $this->view->getElementById('empresa');
        $empresa->appendChild($this->view->createTextNode($marchand->get_apellido_rs()));
        $fecha = new DateTime("now");
        $fecha_dom = $this->view->getElementById('fecha');
        $fecha_dom->appendChild($this->view->createTextNode($fecha->format("Y-m-d")));
        $hora_dom = $this->view->getElementById('hora');
        $fecha_dom->appendChild($this->view->createTextNode($fecha->format(" h:i")));
        $importe_dom = $this->view->getElementById('importe');
        $importe_dom->appendChild($this->view->createTextNode(number_format($cobro->get_importe(), 2)));
        $identificacion = $this->view->getElementById('identificacion');
        $identificacion->appendChild($this->view->createTextNode($cobrador->get_id_cobrador() . "/" . $cobro->get_id_cobro_cobrador() . "/" . $lote->get_id_lote_cobrador()));
        $enviarid = $this->view->getElementById("enviarid");
        $enviarid->setAttribute("value", $cobro->get_id_cobro_cobrador());
        $pdfid = $this->view->getElementById("pdfid");
        $pdfid->setAttribute("value", $cobro->get_id_cobro_cobrador());
        if ($css) {
            $css = $this->view->getElementById('css');
            $css->setAttribute("href", "css/cobradores.css");
        }
        return $this->view;
    }

    protected function regenerar_tiquet($css = true) {
        $cobro = new Cobros_cobrador();
        $cobro->get($this->post["id_cobro_cobrador"]);
        $lote = new Lote_cobrador();
        $lote->get($cobro->get_id_lote_cobrador());
        $cobrador = new Cobrador();
        $cobrador->get($lote->get_id_cobrador());
        $this->view->cargar("views/cobradores_comprobante.html");
//        $lote = $objeto->get_lote();
//        $cobrador = $objeto->get_cobrador();
//        $cobro = $objeto->get_ultimo_cobro();
        $nro_comp = $this->view->getElementById('nro_comprobante');
        $nro_comp->appendChild($this->view->createTextNode($lote->get_id_lote_cobrador()));
        $marchand = new Marchand();
        $marchand->get($lote->get_id_marchand());
        $empresa = $this->view->getElementById('empresa');
        $empresa->appendChild($this->view->createTextNode($marchand->get_apellido_rs()));
        $fecha = new DateTime("now");
        $fecha_dom = $this->view->getElementById('fecha');
        $fecha_dom->appendChild($this->view->createTextNode($fecha->format("Y-m-d")));
        $hora_dom = $this->view->getElementById('hora');
        $fecha_dom->appendChild($this->view->createTextNode($fecha->format(" h:i")));
        $importe_dom = $this->view->getElementById('importe');
        $importe_dom->appendChild($this->view->createTextNode(number_format($cobro->get_importe(), 2)));
        $identificacion = $this->view->getElementById('identificacion');
        $identificacion->appendChild($this->view->createTextNode($cobrador->get_id_cobrador() . "/" . $cobro->get_id_cobro_cobrador() . "/" . $lote->get_id_lote_cobrador()));
        if ($css) {
            $css = $this->view->getElementById('css');
            $css->setAttribute("href", "css/cobradores.css");
        }
        $enviarid = $this->view->getElementById("enviarid");
        $enviarid->setAttribute("value", $cobro->get_id_cobro_cobrador());
        $pdfid = $this->view->getElementById("pdfid");
        $pdfid->setAttribute("value", $cobro->get_id_cobro_cobrador());
    }

    private function regenerar_codigo_de_barras($post) {
        $barcode = $post["cod_ente"];
        $clase = Cobros_cobradores::obtener_ente_cobrador($barcode, $this->cobrador->get_id_cobrador());
        $barcode .= str_pad(str_replace(",", "", str_replace(".", "", number_format($post["importe"], 2))), 8, "0", STR_PAD_LEFT);
        $barcode .= "01";
        $fecha = new DateTime("now");
        $barcode .= $fecha->format("ymd");
//        $barcode .= str_pad($post["ramo"] . $post["endoso"] . $post["poliza"], 17, "0", STR_PAD_LEFT);
//	$barcode .= str_pad($post["ramo"],2,"0",STR_PAD_LEFT) . str_pad($post["endoso"],9,"0" , STR_PAD_LEFT). str_pad($post["poliza"], 6, "0", STR_PAD_LEFT);
        $barcode .= str_pad($post["ramo"], 2, "0", STR_PAD_LEFT) . str_pad($post["poliza"], 9, "0", STR_PAD_LEFT) . str_pad($post["endoso"], 6, "0", STR_PAD_LEFT);

        $barcode .= $clase::carcular_digito_verificador($barcode);
        return $barcode;
    }

    private function agregar_marchand() {
        $this->view->cargar("views/cobradores.registrar_marchand_nuevo.html");
        return $this->view;
    }

    private function agregar_marchand_post() {
        $cobrador_marchand = new Cobrador_marchand();
        try {
            $idm = $this->identificar_marchand($this->post["nro_empresa"]);
        } catch (Exception $e) {
            return $this->home($e->getMessage());
        }
        $recordset = Cobrador_marchand::select(array("id_cobrador" => $cobrador_marchand->get_id_cobrador(), "id_marchand" => $idm));
        if ($recordset and $recordset == 0) {
            $cobrador_marchand->set_id_cobrador($this->cobrador->get_id_cobrador());
            $cobrador_marchand->set_id_marchand($idm);
            $cobrador_marchand->set_nro_operador($this->post["nro_operador"]);
            $config = new Configuracion();
            $array = $config->obtener_configuracion_de_tag_multiple($this->cobrador->get_id_marchand(), Entidad::ENTIDAD_LOTE_COBRADOR, Configuracion::CONFIG_COBRADOR);
            $cobrador_marchand->set_comision_fija($array["comision_cobradores"][0]);
            $cobrador_marchand->set_comision_variable($array["comision_variable"][0]);
            if (!$cobrador_marchand->set())
                return $this->home("Error al asociar empresa");
            return $this->home("Empresa asociada correctamente");
        }
        else {
            return $this->home("Ya esta asociado con esta empresa.");
        }
    }

    private function generar_pdf() {
        $cobro = new Cobros_cobrador();
        $cobro->get($this->post["id_cobro_cobrador"]);
        $this->regenerar_tiquet(FALSE);
        $css = $this->view->getElementById("css");
        $css->setAttribute("href", $_SERVER["HTTP_ORIGIN"] . "/css/cobradores_pdf.css");
        $view = clone $this->view;
        $this->view = new View();
        $view_pdf = new View();
        $comprobante = $view->getElementById("comprobante");
        $view_pdf->appendChild($view_pdf->importNode($comprobante, true));
        $pdf = new Gestor_de_pdf();
        $pdf::$modo = Gestor_de_pdf::MODO_PORTRAIT;
        $pdf::$papel = Gestor_de_pdf::PAPEL_A4;
        $html = $view_pdf->saveHTML();
        $pdf->crear_pdf($html, false);
    }

    private function enviar_correo() {
        $this->view->cargar("views/cobradores_enviar_comprobante.html");
        $id = $this->view->getElementById('id');
        $cobro = new Cobros_cobrador();
        $cobro->get($this->post["id_cobro_cobrador2"]);
        $id->setAttribute('value', $cobro->get_id());
        return $this->view;
    }

    private function enviar_correo_post() {
        if ($this->post["mail"] == false or $this->post["mail"] == "") {
            return $this->home("El campo mail es obligatorio.");
        }
        $this->regenerar_tiquet(false);
        $css = $this->view->getElementById("css");
        $css->setAttribute("href", $_SERVER["HTTP_ORIGIN"] . "/css/cobradores_pdf.css");
        $tiquet = clone $this->view;
        $cobro = new Cobros_cobrador();
        $cobro->get($this->post["id_cobro_cobrador"]);
//        error_log(json_encode($this->post));
        $view_pdf = new View();
        $comprobante = $tiquet->getElementById("comprobante");
        $view_pdf->appendChild($view_pdf->importNode($comprobante, true));
        $pdf = new Gestor_de_pdf();
        $pdf::$modo = Gestor_de_pdf::MODO_PORTRAIT;
        $pdf::$papel = Gestor_de_pdf::PAPEL_A4;
        $html = $view_pdf->saveHTML();
        $marchand = new Marchand();
        $marchand->get($cobro->get_id_marchand());
        $pdf->crear_pdf($html, PATH_CDEXPORTS . $marchand->get_mercalpha() . "/comprobante" . $cobro->get_id() . ".pdf");
        if (!Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_NORESPONDER, $this->post["mail"], "Comprobante de pago", "Le enviamos su comprobante de pago", PATH_CDEXPORTS . $marchand->get_mercalpha() . "/comprobante" . $cobro->get_id() . ".pdf")) {
            return $this->home("Ha ocurrido un error al enviar el comprobante por correo.");
        }
        return $this->home("Comprobante enviado correctamente.");
    }

    private function cambia_pass() {
        $this->view->cargar("views/cobradores.set_pass.html");
    }

    private function cambia_pass_post() {
        if (isset($this->post["pass"]) and isset($this->post["pass2"]) and $this->post["pass"] == $this->post["pass2"]) {
            $gestor = new Gestor_de_hash(self::CLAVE_CIFRADOR);
            if ($this->cobrador === null) {
                $cobrador = new Cobrador();
                $cobrador->get($this->post["key"]);
                $cobrador->set_password($gestor->cifrar($this->post["pass"]));
                if ($cobrador->set()) {
                    header("Location:" . $_SERVER["SCRIPT_URI"]);
                    return $this->view_loggin("Contraseña actualizada correctamente.");
                } else {
                    header("Location:http://www.cobrodigital.com");
                    return $this->view_loggin("Error al actualizar contraseña.");
                }
            }
            $this->cobrador->set_password($gestor->cifrar($this->post["pass"]));
            if ($this->cobrador->set()) {
                return $this->home("Contraseña actualizada correctamente.");
            } else {
                return $this->home("Error al actualizar contraseña.");
            }
        } else {
            return $this->home("Las contraseñas no coinciden");
        }
    }

    private function cambia_pass_no_loggin() {
        $this->view->cargar("views/cobradores.paso1.html");
    }

    private function cambia_pass_paso_2() {
        if (isset($this->post["email"])) {
            $this->view->cargar("views/cobradores.mensaje_password.html");
            $email = $this->view->getElementById("mail");
            $email->appendChild($this->view->createTextNode($this->post["email"]));
            $recordset = Cobrador::select(array("email" => $this->post["email"]));
            if (!$recordset or $recordset->rowCount() !== 1) {
                return $this->view_loggin("El E-mail proporcionado no corresponde a un usuario registrado.");
            } else {
                $row = $recordset->fetchRow();
                $cobrador = new Cobrador($row);
                $usuario = $this->view->getElementById('usuario');
                $usuario->appendChild($this->view->createTextNode(ucwords($cobrador->get_nombre())));
                $hash = new Gestor_de_hash(self::CLAVE_CIFRADOR);
                $key = $hash->cifrar($cobrador->get_id());
                $nav = $hash->cifrar("cambia_pass");
                $nav = $hash->cifrar("cambia_pass");
                $enlace = "https://" . $_SERVER["SCRIPT_URI"] . "?" . $hash->cifrar("key") . "=" . $key . "&" . $hash->cifrar("nav") . "=" . $nav;
                error_log($enlace);
                Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_NORESPONDER, $this->post["email"], "Recuperacion de contraseña", "Estimado " . ucwords($cobrador->get_nombre()) . ". Haga click sobre el enlace " . $enlace . " para continuar con la recuperacion.");
            }
        } else
            return $this->view_loggin("Debe completar el campo email");
    }

    private function agregar_cbu() {
        $this->view->cargar("views/cobradores.set_cbu_cuit.html");
        return $this->view;
    }

    private function agregar_cbu_post() {
        $cbumarchand = new Cbumarchand();
        $cbumarchand->set_id_marchand($this->cobrador->get_id_marchand());
        $cbumarchand->set_id_tipodoc(Tipodoc::CUIT_CUIL);
        if (count($this->post["cuit"]) > 11) {
            return $this->home("El cuit es demaciado largo.");
        }
        if (!is_numeric($this->post["cuit"])) {
            return $this->home("El cuit no es un numero.");
        }
        if (count($this->post["cbu"]) > 29) {
            return $this->home("El cbu es demaciado largo.");
        }
        if (!is_numeric($this->post["cbu"])) {
            return $this->home("El cbu no es un numero.");
        }
        $cbumarchand->set_cuit($this->post["cuit"]);
        $cbumarchand->set_cbu($this->post["cbu"]);
        $cbumarchand->set_titular($this->post["titular"]);
        $cbumarchand->set_referencia($this->post["referencia"]);
        $cbumarchand->set_id_banco(11);
        $cbumarchand->set_id_authstat(2);
        if ($cbumarchand->set()) {
            return $this->home("Cbu añadido correctamente.");
        }
        return $this->home("Error en el cbu.");
    }

    private function esta_facturado($mes, $id_cobrador) {
        $recordset = Cobrador_factura::select_mes_facturado($id_cobrador, $mes);
        if ($recordset->rowCount() >= 1)
            return $recordset->fetchRow();
        return false;
    }

    private function ver_facturaciones() {
        $this->view = new View();
        $this->view->cargar("views/cobradores_ver_facturaciones.html");
        $id_cobrador = $this->cobrador->get_id_cobrador();
        $meses = Cobrador_factura::select_meses_a_rendir($id_cobrador);
        $facturaciones = array();
        foreach ($meses as $mes) {
            $array = array();
            $fecha = new DateTime("now");
            $array["mes"] = $mes["mes"];
            $this->id_marchand = $mes["id_marchand"];
            $afacturar=$this->calcular_ganancia_cobrador($mes["importe"], $mes["id_marchand"],true); //para que retorne sin formatear
            //$array["Comisiones sin iva"]="$". number_format($afacturar-($afacturar * self::IVA),2);
            $array["monto a facturar"] = "$" .number_format($afacturar,2) ; //solo la comision;
            $array["iva"]="$".number_format(((($afacturar * (1+self::IVA)) * self::IVA)/(1+self::IVA)),2);
            $array["Comisiones sin iva"]="$". number_format($afacturar * (1+self::IVA),2);
            if (($this->factura[$mes["mes"]] = $this->esta_facturado($mes["mes"], $id_cobrador)) != false) {
                $array["accion"] = "Facturado";
            } else {
                $array["accion"] = "No Facturado";
            }

            $facturaciones[] = $array;
        }
        $acciones = array();
        $funcion = function ($registro, $tr, $accion, Table $table) {
            $form = $table->createElement("form");
            $form->setAttribute("method", "post");
//            var_dump($this->factura);
            if ($this->factura[$registro[$accion["id"]]] != false) {
                $button = $table->createElement("input");
                $button->setAttribute("type", "submit");
                $button->setAttribute("class", "btn");
                $button->setAttribute("value", "ver");
                $hidden = $table->createElement("input");
                $hidden->setAttribute("type", "hidden");
                $hidden->setAttribute("name", "nav");
                $hidden->setAttribute("value", $accion["nav2"]);
                $hidden2 = $table->createElement("input");
                $hidden2->setAttribute("type", "hidden");
                $hidden2->setAttribute("name", "id");
                 $hash = new Gestor_de_hash(self::CLAVE_CIFRADOR);
                $id = $hash->cifrar($registro[$accion["id"]]);
                $hidden2->setAttribute("value", $id);
                $form->appendChild($button);
                $form->appendChild($hidden);
                $form->appendChild($hidden2);
            } else {
                $button = $table->createElement("input");
                $button->setAttribute("type", "submit");
                $button->setAttribute("class", "btn");
                $button->setAttribute("value", "Facturar");
                $hidden = $table->createElement("input");
                $hidden->setAttribute("type", "hidden");
                $hidden->setAttribute("name", "nav");
                $hidden->setAttribute("value", $accion["nav"]);
                $hidden2 = $table->createElement("input");
                $hidden2->setAttribute("type", "hidden");
                $hash = new Gestor_de_hash(self::CLAVE_CIFRADOR);
                $id = $hash->cifrar($registro[$accion["id"]]);
                $hidden2->setAttribute("value", $id);
                $hidden2->setAttribute("name", "id");
                $hidden3 = $table->createElement("input");
                $hidden3->setAttribute("type", "hidden");
                //$hash = new Gestor_de_hash(self::CLAVE_CIFRADOR);
                //$id_marchand=$hash->cifrar($this->id_marchand);
                $hidden3->setAttribute("value", $this->id_marchand);
                $hidden3->setAttribute("name", "id_marchand");
                $form->appendChild($button);
                $form->appendChild($hidden);
                $form->appendChild($hidden2);
                $form->appendChild($hidden3);
            }
            $td = $table->createElement('td');
            $td->appendChild($form);
            $tr->appendChild($td);
            return true;
        };
        $acciones[] = array('etiqueta' => Table::BOTON_MICROSITIO, 'campo' => 'id_cobrador_factura', 'nav' => 'cargar_facturacion', 'nav2' => 'ver_factura', 'id' => 'mes', "callback" => $funcion);

        $table = new Table($facturaciones, null, null, $acciones); //acciones no funciona con la logica del micrositio ojo!!!

        $table->cambiar_encabezados(array("mes", "comisiones sin iva","iva", "monto a facturar", "estado", "Accion"));
        $main = $this->view->getElementById("main");
        $main->appendChild($this->view->importNode($table->documentElement, true));
        return $this->view;
    }

    private function cargar_facturacion($mensaje = false, $id = false) {
        $this->view->cargar("views/cobradores_cargar_facturaciones.html");
        $mes = $this->view->getElementById('mes');
        $marchand = $this->view->getElementById("id_marchand");
        $message = $this->view->getElementById('mensaje');
        $message->appendChild($this->view->createTextNode($mensaje));
        //$hash = new Gestor_de_hash(self::CLAVE_CIFRADOR);
        $marchand->setAttribute("value", $this->post["id_marchand"]);
        if (!$id)
            $mes->setAttribute("value", $this->post["id"]);
        else
            $mes->setAttribute("value", $id);
        return $this->view;
    }

    private function cargar_facturacion_post() {
        if (empty($this->files)) {
            return $this->cargar_facturacion("Debe cargar un archivo", $this->post["mes"]);
        }
        if ($this->files["archivo"]["size"] > 1000000) {

            return $this->cargar_facturacion("Archivo muy demaciado grande.", $this->post["mes"]);
        }
        if (!isset($this->post["mes"])) {
            $fecha = new DateTime("now");
            $fecha->sub(new DateInterval("P1M"));
        } else {
            $fecha = DateTime::createFromFormat("!Ym", $this->post["mes"]);
            if ($fecha->format("Ym") == (new DateTime("now"))->format("Ym"))
                return $this->cargar_facturacion("La facturacion es a mes vencido.", $this->post["mes"]);
        }
        $recordset = Cobrador_factura::select_periodo_facturado($fecha, $this->cobrador->get_id_cobrador());
        if ($recordset->rowCount() > 1) {
            error_log("Ya existe una facturacion para el periodo " . $fecha->format("Y-m") . ".");
            return $this->cargar_facturacion("Ya existe una facturacion para el periodo " . $fecha->format("Y-m") . ".", $this->post["mes"]);
        }
        $monto = Cobrador_factura::select_monto_a_facturar($fecha, $this->cobrador->get_id_cobrador());
        $tmp_name = $this->files["archivo"]["tmp_name"];
        error_log("Moviendo archafivo como temporal");
        $gestor = new Gestor_de_disco();
        $nombre = $gestor->mover_archivo_subido($this->files["XI3v6HOboOqmmSz7ZJnYGQ=="]["tmp_name"], PATH_CDEXPORTS, $this->files["archivo"]["name"]);
        $porcentaje = 2;
        $size = getimagesize(PATH_CDEXPORTS . $nombre);
        list($ancho, $alto) = $size;
        $nuevo_ancho = $ancho * $porcentaje;
        $nuevo_alto = $alto * $porcentaje;
        $thumb = imagecreatetruecolor($nuevo_ancho, $nuevo_alto);
        error_log("Cambiando tamaño a archivo.");
        switch ($this->files["archivo"]["type"]) {
            case "image/jpeg":
                $origen = imagecreatefromjpeg(PATH_CDEXPORTS . $nombre);
                imagecopyresized($thumb, $origen, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);
                imagejpeg($thumb, PATH_CDEXPORTS . $nombre, 3);
                break;
            case "image/gif":
                $origen = imagecreatefromgif(PATH_CDEXPORTS . $nombre);
                imagecopyresized($thumb, $origen, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);
                imagegif($thumb, PATH_CDEXPORTS . $nombre, 3);
                break;
            case "image/png":
                $origen = imagecreatefrompng(PATH_CDEXPORTS . $nombre);
                imagecopyresized($thumb, $origen, 0, 0, 0, 0, $nuevo_ancho, $nuevo_alto, $ancho, $alto);
                imagepng($thumb, PATH_CDEXPORTS . $nombre, 3);
                break;
        }
        error_log("Guardando temporal del archivo.");
//            $serialize_image->setAttribute("value", );
        $tmp_name = PATH_CDEXPORTS . $nombre;
        $fp = fopen($tmp_name, "rb");
        $buffer = fread($fp, filesize($tmp_name));
        error_log("Convirtiendo el archivo en bytea");
        $buffer = pg_escape_bytea($buffer);
        error_log("Borrando el archivo");
        @unlink($tmp_name, $fp);
        $cobrador_factura = new Cobrador_factura();
        $cobrador_factura->set_id_cobrador($this->cobrador->get_id_cobrador());
        $cobrador_factura->set_file($buffer);
        $cobrador_factura->set_fecha_gen("now()");
        $cobrador_factura->set_fecha_fact($fecha->format("Y-m-d"));
        $cobrador_factura->set_id_marchand($this->cobrador->get_id_marchand());
        $monto = $this->calcular_ganancia_cobrador($monto, $this->post["id_marchand"]);
//	exit();
        $cobrador_factura->set_monto_facturado($monto); //no guarda el monto
        Model::StartTrans();
        if ($cobrador_factura->set()) {
            $recordset = Lote_rendicion::select_rendicion_periodo($this->cobrador->get_id_cobrador(), $fecha);
            if($recordset->rowCount()<1){
                Model::FailTrans();
                return $this->home("Error al procesar la rendicion");
            }
            foreach ($recordset as $row) {
                $lote_rendicion = new Lote_rendicion();
                $lote_rendicion->set_id_lote_rendicion($row["id_lote_rendicion"]);
                $lote_rendicion->set_id_cobrador_factura($cobrador_factura->get_id_cobrador_factura());
//                var_dump($lote_rendicion);
                if (!$lote_rendicion->set())
                    Model::FailTrans();
            }
            if (!Model::HasFailedTrans() and Model::CompleteTrans())
                return $this->home("Facturacion cargada correctamente.");
        }
        return $this->cargar_facturacion("Error al cargar la facturacion", $this->post["mes"]);
    }

    private function ver_factura() {
        
        $mes = $this->post["id"];
        $facturas = Cobrador_factura::select_factura_mes($mes,$this->cobrador->get_id_cobrador());
        $factura= new Cobrador_factura($facturas->fetchRow());
        $file = $factura->get_file();
        $f = finfo_open();
        $mime_type = finfo_buffer($f, $file, FILEINFO_MIME_TYPE);
        $this->view = new View();
        $this->view->cargar("views/cobradores.ver_factura.html");
//        var_dump($mime_type);
        switch ($mime_type) {
            case "image/jpeg":
                $elemento = $this->view->createElement("img");
                $elemento->setAttribute('src', "data:image/jpeg;base64," . base64_encode($file));
                $elemento->setAttribute('style', "width:100%;height:-webkit-fill-available");
                break;
            case "application/pdf":
            case "application/octet-stream":
                $elemento = $this->view->createElement("embed");
                $elemento->setAttribute('src', "data:application/pdf;base64," . base64_encode($file));
                $elemento->setAttribute('style', "width:100%;height:-webkit-fill-available");
                break;
            case 'image/png':
                $elemento = $this->view->createElement("img");
                $elemento->setAttribute('src', "data:image/png;base64," . base64_encode($file));
                $elemento->setAttribute('style', "width:100%;height:-webkit-fill-available");
                break;
        }
        $img = $this->view->getElementById("img");
        $img->appendChild($elemento);
//        error_log($this->view->saveHTML());
        return $this->view;
//        return $this->home();
    }

    private function verificar_bloqueo_factura() {
        $fecha_actual=new DateTime("now");
        $mes_actual= DateTime::createFromFormat("Y-m-d",$fecha_actual->format("Y-m-")."1");
        $config = Configuracion::obtener_configuracion_de_tag_multiple($this->cobrador->get_id_marchand(), Entidad::ENTIDAD_LOTE_COBRADOR, Configuracion::CONFIG_COBRADOR);
//      var_dump($config);
        $tolerancia_dias_facturacion = $config["tolerancia_dias_facturacion"][0];
        $activar_bloqueo_facturacion=$config["activar_bloqueo_facturacion"][0];
        if($activar_bloqueo_facturacion==0)
            return false;
//      var_dump($tolerancia_dias_facturacion);
        $recordset= Cobrador_factura::select_mes_facturado($this->cobrador->get_id_cobrador(), $mes_actual->format("Ym"));
//        var_dump($fecha_actual->diff($mes_actual));
        $diff=$fecha_actual->diff($mes_actual);
//        var_dump($diff->format('%d'))C;
        if($recordset->rowCount()==0 and $diff->format('%d')<=$tolerancia_dias_facturacion){
            return true;
       } //los dias deben salir del config;
        return false;

    }
}
