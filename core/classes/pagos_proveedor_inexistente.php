<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/*
 * CREAR PROVEEDOR PENDIENTE 
 * ENVIAR CORREO
 */

Class Pagos_proveedor_inexistente extends Pagos_state {

    private $egreso;
    private $proveedor_pendiente;
    private $id_pendiente;

    const CUIT_CD = "33711566959";
    const CBU_CD_1 = "0070047430009750124267";
    const CBU_CD_2 = "0070047420000007719080";
    const CBU_CD_3 = "0070047420000008850700";
    const CBU_CD_4 = "0070047420000007852035";
    const CBU_CD_5 = "0070047420000007572913";
    const CBU_CD_6= "0070047431009750133143";
    const CBU_CD_7 = "2850721930094144383311";
    const CBU_CD_8 = "2850721930094148274831";
    const CBU_CD_9 = "1910241055024100250600";
    const CBU_CD_10 = "1910241055024101043900";
    const CBU_CD_11 = "0720429020000000075822";
    const CBU_CD_12 = "0720429020000000068004";
    const CBU_CD_13 = "0170466620000000146072";
    const CBU_CD_14 = "0170466620000000146140";
    public function __construct() {
        
    }

    public function debug() {

        if (is_null($this->proveedor_pendiente))
            return get_class($this) . "->[NULL]";

        return get_class($this) . "->[" . $this->proveedor_pendiente->debug() . "]";
    }

    public function procesar($variables, $asumir_costo) {

        Model::StartTrans();
        if (!$this->entrada1($variables, $asumir_costo)) {
            Model::FailTrans();
            return false;
        }

        if (!$this->enviar_correo($variables)) {
            Model::FailTrans();
            return false;
        }

        Model::CompleteTrans();

        return true;
    }

    public function set_proveedor(Proveedor_pendiente $p) {
        $this->proveedor_pendiente = $p;
        $this->id_pendiente = $p->get_id_proveedor_pendiente();
    }

    public function esta_vencido() {
        return $this->proveedor_pendiente->esta_vencido();
    }
    public function validar_cbu($cbu){
          $recordset= Cbumarchand::select_descifrado(Application::$usuario->get_id_marchand());
          if($recordset and $recordset->rowCount()>=1){
              foreach ($recordset as $row){
                  if($cbu===$row["cbu"]){
                      return false;
                  }
              }
          }
          return true;
      }
    private function entrada1($variables, $asumir_costo) {


        developer_log(get_class($this) . "->entrada1");
        if(!$this->validar_cbu($variables["cbu"])){
            Model::FailTrans();
            developer_log('No se puede realizar un pago a su propio cbu.');
            Gestor_de_log::set('No se puede realizar un pago a su propio cbu.',1);
            return false;
        }
     
        if (!$this->cargar_proveedor_pendiente($variables, $asumir_costo)) {
            if (self::ACTIVAR_DEBUG) {
                developer_log('falló al crear Proveedor pendiente');
            }
            return false;
        }
	else{
		developer_log("proveedor cargado correctamente.");
	}
        if(!$this->validar_campos($variables)){
            Gestor_de_log::set("No se pueden realizar pagos a esta cuenta",1);
            return false;
        }
	else{
		developer_log("campos validados.");
	}
	
//            Gestor_de_log::set("guardando proveedor_pendiente");
//        Model::CompleteTrans();
//        Model::CompleteTrans();
//        Model::StartTrans();
        developer_log("crear transacciones");
        if (!$this->crear_transacciones($variables, $asumir_costo)) {
            if (self::ACTIVAR_DEBUG) {
                developer_log('falló al crear transaccion');
            }
            return false;
        }

        developer_log("en carrera");
        return true;
    }
    public function enviar_correo($variables) {
        return $this->enviar_correo_proveedor($variables);
    }

    private function enviar_correo_proveedor($variables) {
        //
        $emisor = Gestor_de_correo::MAIL_COBRODIGITAL_INFO;
        $key = base64_encode((string) $this->id_pendiente);

        developer_log($this->proveedor_pendiente->get_mail());
        $mail = $this->proveedor_pendiente->get_mail();

        $asunto = "correo de: gestion de proveedor";
        $view=new View();
        developer_log(getcwd());
        $view->cargar("views/mail_proveedor_alta.html");
        $nombre_prov= $view->getElementById("nombre_prov");
        $nombre_marchand= $view->getElementById("nombre_marchand");
        $importe= $view->getElementById("importe");
        $importe2= $view->getElementById("importe2");
        $concepto= $view->getElementById("concepto");
        $concepto2= $view->getElementById("concepto2");
        $cbu= $view->getElementById("cbu");
        $nombre_prov->appendChild($view->createTextNode($this->proveedor_pendiente->get_razon_social()));
        $marchand=new Marchand();
        $marchand->get($this->proveedor_pendiente->get_id_marchand());
        $nombre_marchand->appendChild($view->createTextNode($marchand->get_apellido_rs()));
        $importe->appendChild($view->createTextNode($this->proveedor_pendiente->get_monto())); 
        $importe2->appendChild($view->createTextNode($this->proveedor_pendiente->get_monto())); 
        $concepto->appendChild($view->createTextNode($this->proveedor_pendiente->get_concepto()));
        $concepto2->appendChild($view->createTextNode($this->proveedor_pendiente->get_concepto()));
        $cbu->appendChild($view->createTextNode($this->enmascarar_cbu($this->proveedor_pendiente->get_cbu())));
        
        $acepta= $view->getElementById("acepta");
        $otro_aceptar= $view->getElementById("otro_aceptar");       
        $rechaza= $view->getElementById("rechaza");        
        $vars = array('id' => $key, 'acepto' => '1');
        $querystring = http_build_query($vars);
        $acepta->setAttribute('href', "https://cobro.digital:14365/externo/script_landing_proveedor.php/?$querystring");
        $otro_aceptar->setAttribute('href', "https://cobro.digital:14365/externo/script_landing_proveedor.php/?$querystring");
        $vars = array('id' => $key, 'acepto' => '0');
        $querystring = http_build_query($vars);

        $rechaza->setAttribute('href', "https://cobro.digital:14365/externo/script_landing_proveedor.php/?$querystring");
        //  developer_log($data);
        $data= $view->saveHTML();
        if (!Gestor_de_correo::enviar($emisor, $mail, $asunto, $data)) {
            developer_log('falló al enviar correo');
            return true;
        }
        return true;
    }

    private function crear_transacciones($variables, $asumir_costo) {

        // date_default_timezone_set('utc');

        $variables_requeridas = array('importe');
        foreach ($variables_requeridas as $clave) {
            if (!isset($variables[$clave])) {
                throw new Exception('Debe completar todos los campos para continuar.');
            }
        }

        developer_log("crear transa");
        try {
            $this->egreso = new Transaccion();
        } catch (Exception $exc) {
            developer_log("catch " . $exc->getTraceAsString());

            echo $exc->getTraceAsString();
        }

        $id_marchand_egreso = Application::$usuario->get_id_marchand();
        $id_mp_egreso = Mp::PAGO_PROVEEDOR_PENDIENTE;
        $monto_pagador_egreso = $variables['importe'] + ($variables['importe_cv'] / 100);

        $fecha_egreso = new DateTime('now');

        $id_referencia_egreso = $this->proveedor_pendiente->get_id_proveedor_pendiente();

        developer_log("crear transa 2");

        if ($this->egreso->crear($id_marchand_egreso, $id_mp_egreso, $monto_pagador_egreso, $fecha_egreso, $id_referencia_egreso, null, null, !$asumir_costo)) {

            /*      actualizar campos del proveedor pendiente con el egreso */
            $this->proveedor_pendiente->set_id_marchand($this->egreso->moves->get_id_marchand());
            $this->proveedor_pendiente->set_id_move($this->egreso->moves->get_id_moves());
            $this->proveedor_pendiente->set();
            developer_log('cobro al proveedor ok');
            return true;
        }

        return false;
    }

    private function enviar_correo_pagador() {
        return true;
    }

    private function armar_data() {

        $id = $this->proveedor_pendiente->get_id_marchand();
        // recupera marchand
        $m = new Marchand();
        $m->get($id);
        // recupera move
        $move = new Moves();
        $id = $this->proveedor_pendiente->get_id_move();
        $move->get($id);
        developer_log(" el id $id");

        $nameProveedor = $this->proveedor_pendiente->nombre_completo();


        $t = '<font face="verdana" size="5" color="black">';

        $t .= "<b>" . "Estimado PROVEEDOR: '$nameProveedor' " . "</font>" . "</b>" . "<br>";
        $t .= "<br>";
        $t .= "<br>";
        $t .= '<font face="verdana" size="4" color="black">';

        $t .= "Su Cliente:" . $m->get_nombre() . " " . $m->get_apellido_rs() . "' ha generado un pago a su nombre" . "<br>";
        $t .= "por un importe de $ " . $move->get_monto_pagador() . "<br>";
        $t .= "En concepto de  '" . $this->proveedor_pendiente->get_concepto() . "' , para aceptar el mismo por favor haga clic en 'Aceptar Pago'." . "<br>";
        $t .= "<br>";
        $t .= "</font>";

        $t .= "El mismo se transferrirá a su cuenta bancaria " . "<br>";
        $t .= "con CBU " . $this->enmascarar_cbu($this->proveedor_pendiente->cbu()) . "<br>";
        $t .= "Informado por '" . $m->get_nombre() . " " . $m->get_apellido_rs() . "' " . "<br>";
        $t .= " de no aceptar haga clic en 'No aceptar el pago' " . "<br>";

        $t .= "<hr>";
        $t .= "<P ALIGN=center>" . "<b>" . "Al aceptar el pago se creará una cuenta en Cobro Digital a su nombre </b>";
        $t .= "<P ALIGN=center>" . "<b>" . "Los próximos pagos que usted recibirá serán acreditados en esta cuenta </b>";
        $t .= "<hr>";
        $t .= "<br>";

        $t .= "<br>";
        $t .= "<br>";
        $t .= "S.E.U.O";
        $t .= "<br>";


        return $t;
    }

    private function boton() {
        /*
         * .button {
          display: block;
          width: 115px;
          height: 25px;
          background: #4E9CAF;
          padding: 10px;
          text-align: center;
          border-radius: 5px;
          color: white;
          font-weight: bold;
          }
         */
        return '<a  href="SALAME" style="text-decoration:none;border: 1px solid black;display:block;font-family:arial,helvetica,sans-serif;background:#4E9CAF;padding: 10px;padding-right:25px;padding-bottom:10px;padding-left:25px;border-color:#ffff;border-style:solid;color: white;border-width:10px;border-radius:8px;width: 200px;" data-saferedirecturl="https://www.google.com/url?hl=es&amp;q=SALAME;source=gmail&amp;ust=1503526032389000&amp;usg=AFQjCNEo6Oru5KX3wC955A6esru4KizKoQ">Aceptar Cobro clickeando aquí</a>';
        // return    '<a class="m_-4211245125394291336buttonstyles" style="text-decoration:none;display:block;font-family:arial,helvetica,sans-serif;font-size:16px;color:#ffffff;padding-top:10px;padding-right:25px;padding-bottom:10px;padding-left:25px;border-color:#009ddc;border-style:solid;border-width:0px;border-radius:8px" href="SALAME" title="" target="_blank" data-saferedirecturl="https://www.google.com/url?hl=es&amp;q=SALAME;source=gmail&amp;ust=1503526032389000&amp;usg=AFQjCNEo6Oru5KX3wC955A6esru4KizKoQ">Aceptar Cobro</a>';
    }

    private function boton2() {

        return '<a href="SALAME" style="text-decoration:none;border:1px solid black;display:block;font-family:arial,helvetica,sans-serif;background:#ce9caf;padding:10px;padding-right:25px;padding-bottom:10px;padding-left:25px;border-color:#e0fe;border-style:solid;color: #efe3ef;border-width:10px;border-radius:8px;width:200px;" target="_blank" data-saferedirecturl="https://www.google.com/url?hl=es&amp;q=SALAME;source=gmail&amp;ust=1503593831835000&amp;usg=AFQjCNHxPEpzzFQi7PbeQyKgYEgUelwL2Q">Rechazo Cobro </a>';
    }

    private function validar_campos($variables) {
        if ($variables["cuit"] == self::CUIT_CD) {
            return false;
        }
        if (in_array($variables["cbu"], array(self::CBU_CD_1,self::CBU_CD_2,self::CBU_CD_3,self::CBU_CD_4,
                                              self::CBU_CD_5,self::CBU_CD_6,self::CBU_CD_7,self::CBU_CD_8,
                                              self::CBU_CD_9,self::CBU_CD_10,self::CBU_CD_11,self::CBU_CD_12,
                                              self::CBU_CD_13,self::CBU_CD_14,))) {
            return false;
        }
        return true;
    }
    private function validar_preexistencia($variables) {
        //valida la existencia y las otras operaciones del proveedor.
        //solo permitimos una operacion por vez.
        //siempre que sea inexistente.
        $recordSet= Proveedor_pendiente::select(array("cuil"=>$variables["cuit"]));
        developer_log($recordSet->rowCount());
        if($recordSet->rowCount()==0)
            return true;
        foreach ($recordSet as $row){
            $proveedor_pendiente=new Proveedor_pendiente($row);
            if($proveedor_pendiente->get_acepto()===true){
		developer_log("El proveedor ya acepto en el pasado");
                return true;
            }
            if($proveedor_pendiente->get_acepto()===false){
		developer_log("El proveedor no acepto en el pasado");
                return true;
            }
            if($proveedor_pendiente->esta_vencido()){
		developer_log("El proveedor no acepto a tiempo en el pasado");
                return true;
            }
            if($proveedor_pendiente->get_acepto()===null){
		developer_log("El proveedor tiene otro pago vigente sin aceptar");
                return false;
            }
            
        }
        return false;
    }

    
    private function cargar_proveedor_pendiente($variables, $asumo_costo) {

        developer_log("cargar_proveedor_pendiente " . json_encode($variables));
        if (!$this->validar_campos($variables)) {
            developer_log('Proveedor no disponible');
            Gestor_de_log::set('Proveedor no disponible');
            return false;
        }
	else{
		developer_log("Campos Validados");	
	}
        if(!$this->validar_preexistencia($variables)){
            Gestor_de_log::set("El Proveedor no puede recibir más pagos por el momento.");
            developer_log("El Proveedor no puede recibir más pagos por el momento.");
            return false;
        }
	else{
		developer_log("el proveedor no tiene preexistencia");
	}
        $this->proveedor_pendiente = new Proveedor_pendiente();

        developer_log("Pago_proveedor_existente->cargar_proveedor_pendiente");
        $this->proveedor_pendiente->set_id_tipo($variables['id_pfpj']);
        if (isset($variables['cbu'])) {
            $this->proveedor_pendiente->set_cbu($variables['cbu']);
        }
        if (array_key_exists('cbu_alias', $variables)) {
            $this->proveedor_pendiente->set_alias_cbu($variables['cbu_alias']);
        }
        $this->proveedor_pendiente->set_cuil($variables['cuit']);
        //
        if (!isset($variables['apellido_rs']) && !isset($variables['nombre'])) {
            $variables['apellido_rs'] = "";
        }
        if (isset($variables['nombre'])) {
            $variables['apellido_rs'] = $variables['nombre'];
        }
        //

        if (!isset($variables['apellido_rs'])) {
            $variables['apellido_rs'] = "";
        }
        $this->proveedor_pendiente->set_razon_social($variables['apellido_rs']);

        if (isset($variables['nombre'])) {
            $this->proveedor_pendiente->set_nombre_completo($variables['nombre']);
        }
        $this->proveedor_pendiente->set_mail($variables['email']);
        $this->proveedor_pendiente->set_concepto($variables['concepto']);
        $this->proveedor_pendiente->set_monto($variables['importe'] + ($variables['importe_cv'] / 100));

        $this->proveedor_pendiente->set_id_marchand(Application::$usuario->get_id_marchand());
        $this->proveedor_pendiente->set_id_move(1);
        $fecha = new DateTime('now');
        $fecha->add(new DateInterval('P6D'));
        $this->proveedor_pendiente->set_fecha_venc($fecha->format('Y-m-d'));
        $this->proveedor_pendiente->set_traslada(!$asumo_costo);
        $this->proveedor_pendiente->set_fecha_gen($this->getDatetimeNow());

        if (!$this->proveedor_pendiente->set()) {
            developer_log('falló al cargar  proveedor pendiente nuevo ');
            return false;
        }
	else{
		developer_log("El proveedor fue cargado correctamente.");
	}
        $this->id_pendiente = $this->proveedor_pendiente->get_id();
        return true;
    }

    private function getDatetimeNow() {
        $tz_object = new DateTimeZone('America/Buenos_Aires');


        $datetime = new DateTime();
        $datetime->setTimezone($tz_object);
        return $datetime->format('Y\-m\-d\ h:i:s');
    }

    private function armarBotones($key) {

        // armar botones de landing
        /* example
          <button onclick="location.href='http://www.example.com'" type="button">
          www.example.com</button>
         */

        $vars = array('id' => $key, 'acepto' => '1');
        $querystring = http_build_query($vars);
        $root = 'http://172.20.10.94:456/externo/script_landing_proveedor.php/?';
        // $root = $root . (string) $key;
        //$root .= "& acepto=1";
        $root .= $querystring;

        $boton = $this->boton();
        $textoBoton = str_replace("SALAME", $root, $boton);
        $textoBoton .= "<br>";
        $textoBoton .= "<br>";
//
        $vars = array('id' => $key, 'acepto' => '0');
        $querystring = http_build_query($vars);

        $root = 'http://172.20.10.94:456/externo/script_landing_proveedor.php/?';
        // $root = $root . (string) $key;
        // $root .= "& acepto=0";
        $root .= $querystring;

        $boton = $this->boton2();
        $textoB = str_replace("SALAME", $root, $boton);
        $textoBoton .= $textoB;
        $textoBoton .= "<br>";
        $textoBoton .= "<br>";

        return $textoBoton;
    }

    private function enmascarar_cbu($cbu) {

        $enmascarado = substr($cbu, 0, 3);
        $enmascarado .= 'X XXXX XXXX XXXX';
        $enmascarado .= substr($cbu, -4, 4);
        return $enmascarado;
    }

    public function tieneCliente() {
        return false;
    }

    public function get_proveedor() {
        return false;
    }

    public function get_proveedor_pendiente() {
        return $this->proveedor_pendiente;
    }

}
