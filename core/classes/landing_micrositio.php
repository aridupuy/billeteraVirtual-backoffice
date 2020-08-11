<?php

class Landing_micrositio
{
    const NOMBRE_COOKIE='micrositio_cookie';
    const ACTIVAR_DEBUG=false;
    const VARIABLE_DE_AUTENTICACION='private';
    const TOKEN='token';
    const TOKEN_INGRESAR='Ingresar';
    const TOKEN_BOLETAS_DE_PAGADOR='Volver';
    const TOKEN_PAGAR_EFECTIVO='Efectivo';
    const TOKEN_PAGAR_MEDIOS_ELECTRONICOS='Medios electrónicos';
    const TOKEN_PAGAR_TARJETA_DE_CREDITO='Tarjeta de crédito';
    const TOKEN_SALIR='Salir';
    const NAME_NRO_BOLETA='nroboleta';
    const FORMATO_PRESENTACION_FECHA='d/m/Y';

    const ETIQUETA_ESTRUCTURA='sap_apellido';
    const ETIQUETA_ESTRUCTURA_AUXILIAR='sap_apellidors';

    public $view;
    private $token=false;
    private $post=false;
    private $get=false;
    public $cookie=false; # Tiene la cookie a grabar luego de procesar
    private $micrositio=false;
    private $marchand=false;
    private $climarchand=false;

    private $mensaje=false;
    public function __construct($get, $post, $cookie)
    {
        if (!isset($get[self::VARIABLE_DE_AUTENTICACION])) {
            throw new Exception("No hay datos para autenticar el servidor.");
        }
        if (($id_marchand=$this->autenticar_servidor($get[self::VARIABLE_DE_AUTENTICACION]))) {
            if (($this->marchand=$this->obtener_marchand($id_marchand))) {
                if (isset($post[self::TOKEN])) {
                    $this->token=$post[self::TOKEN];
                    unset($post[self::TOKEN]);
                }
                if (($climarchand=$this->autenticar_pagador($this->marchand->get_id_marchand(), $post, $cookie))) {
                    if ($climarchand->get_id_marchand()==$this->marchand->get_id_marchand()) {
                        if (!isset($this->cookie[self::NOMBRE_COOKIE])) {
                            $clave=$this->micrositio->get_token();
                            if (ACTIVAR_HASH)
                                $this->cookie[self::NOMBRE_COOKIE]=$this->cifrar_cookie($climarchand->get_id_climarchand(), $clave);
                            else
                                $this->cookie[self::NOMBRE_COOKIE]=$climarchand->get_id_climarchand();
                        }
                        if (self::ACTIVAR_DEBUG) {
                            developer_log('Pagador autenticado. ');
                        }
                        $this->climarchand=$climarchand;
                        $this->post=$post;
                    } else {
                        developer_log('Ha ocurrido un error. ');
                    }
                } else {
                    if (isset($cookie[self::NOMBRE_COOKIE]) or $post) {
                        $this->mensaje="Acceso denegado";
                    }
                    if (self::ACTIVAR_DEBUG) {
                        developer_log('Pagador no autenticado. ');
                    }
                }
            }
        } else {
            throw new Exception("Servidor no autenticado.");
        }
    }
    public function dispatch()
    {
        $view=false;
        # Si se instancia la clase esta seteado $this->marchand
        if ($this->climarchand) {
            if (!$this->token) {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('El pagador actualiza la página.');
                }
                $this->token=self::TOKEN_BOLETAS_DE_PAGADOR;
            }
            switch ($this->token) {
                case self::TOKEN_INGRESAR:
                    # Si recien accede al micrositio
                case self::TOKEN_BOLETAS_DE_PAGADOR:
                    $this->grabar_log('El usuario lista las boletas pendientes de pago. ');
                    $view=$this->mostrar_boletas_pendientes_de_pagador($this->marchand->get_id_marchand(), $this->climarchand->get_id_climarchand());
                    break;
                case self::TOKEN_PAGAR_EFECTIVO:
                    if (isset($this->post[self::NAME_NRO_BOLETA])) {
                        $this->grabar_log('El usuario accede al pago en efectivo. Boleta nro '.$this->post[self::NAME_NRO_BOLETA].'. ');
                        $view=$this->mostrar_boleta_pago_efectivo($this->climarchand->get_id_climarchand(), $this->post[self::NAME_NRO_BOLETA]);
                    }
                    break;
                case self::TOKEN_PAGAR_MEDIOS_ELECTRONICOS:
                    if (isset($this->post[self::NAME_NRO_BOLETA])) {
                        $this->grabar_log('El usuario accede al pago con medios electrónicos. Boleta nro '.$this->post[self::NAME_NRO_BOLETA].'. ');
                        $view=$this->mostrar_boleta_pago_medios_electronicos($this->climarchand->get_id_climarchand(), $this->post[self::NAME_NRO_BOLETA]);
                    }
                    break;
                case self::TOKEN_PAGAR_TARJETA_DE_CREDITO:
                    if (isset($this->post[self::NAME_NRO_BOLETA])) {
                        $this->grabar_log('El usuario accede al pago con tarjeta de crédito. Boleta nro '.$this->post[self::NAME_NRO_BOLETA].'. ');
                        if (!($view=$this->mostrar_boleta_pago_tarjeta_de_credito($this->climarchand->get_id_climarchand(), $this->post[self::NAME_NRO_BOLETA]))) {
                            $this->mensaje='No es posible realizar el Pago con Tarjeta de Crédito.';
                        }
                    }
                    break;
                case self::TOKEN_SALIR:
                    $this->grabar_log('El usuario cierra su sesión. ');
                    unset($this->cookie[self::NOMBRE_COOKIE]);
                    break;
            }
        }
        if ($this->mensaje) {
            $view=$this->mostrar_mensaje_de_error($this->mensaje);
        }
        if (!$view) {
            $view=$this->mostrar_formulario_de_autenticacion($this->marchand->get_id_marchand(), $this->mensaje);
        }
        $this->view=$view;
        return true;
    }
    private function autenticar_servidor($token)
    {
        $array=array('token'=>$token);
        $recordset=Micrositio::select($array);
        if ($recordset and $recordset->RowCount()==1) {
            $this->micrositio=new Micrositio($recordset->FetchRow());
            return $this->micrositio->get_id_marchand();
        }
        return false;
    }
    private function mostrar_formulario_de_autenticacion($id_marchand, $mensaje = false)
    {
        $view=new View();
        if (self::ACTIVAR_DEBUG) {
            developer_log('Visualiza formulario de inicio de sesión.');
        }
        $view->cargar('views/micrositio.paso_1.html');
        $titulo=$this->obtener_titulo_de_micrositio();
        $view->getElementById('titulo')->appendChild($view->createTextNode($titulo));
        $view->getElementById('title')->appendChild($view->createTextNode($titulo));
        $boton=$view->getElementById('boton');
        $boton->setAttribute('name', self::TOKEN);
        $boton->setAttribute('value', self::TOKEN_INGRESAR);
        $envvars=$this->obtener_envvars($id_marchand);
        if ((!$envvars or !isset($envvars['autenticacion']))or(!isset($envvars['autenticacion']['inputs']))) {
            developer_log('No hay atributos de autenticación.');
            return false;
        }
        if (isset($envvars['autenticacion']['primer_mensaje'])) {
            $primer_mensaje=$view->getElementById('primer_mensaje');
            $primer_mensaje->appendChild($view->createTextNode($envvars['autenticacion']['primer_mensaje']));
        }
        $formulario=$view->getElementById('miFormulario');
        foreach ($envvars['autenticacion']['inputs'] as $in) {
            $input=$view->createElement('input');
            $input->setAttribute('type', $in['type']);
            $input->setAttribute('placeholder', $in['placeholder']);
            $input->setAttribute('name', $in['name']);
            $input->setAttribute('required', $in['required']);
            $formulario->insertBefore($input, $boton);
            if (isset($in['span'])) {
                $span=$view->createElement('span', $in['span']);
                $span->setAttribute('class', 'span_autenticacion');
                $formulario->insertBefore($span, $boton);
            }
        }
        if (isset($envvars['autenticacion']['titulo_leyenda']) and isset($envvars['autenticacion']['leyenda'])) {
            $titulo_leyenda=$envvars['autenticacion']['titulo_leyenda'];
            $span=$view->createElement('span', $titulo_leyenda);
            $span->setAttribute('class', 'titulo_leyenda');
            $formulario->appendChild($span);
            $leyenda=$envvars['autenticacion']['leyenda'];
            $leyenda_doc = new DOMDocument();
            $leyenda_doc->loadHTML(utf8_decode($leyenda));
            // $p=$view->createElement('p');
            // $p->setAttribute('class','leyenda');
            $formulario->appendChild($view->importNode($leyenda_doc->documentElement, true));
            // $formulario->appendChild($p);
        }
        if ($mensaje) {
            $div_mensaje=$view->createElement('p', $mensaje);
            $view->getElementById('contenedor_mensaje')->appendChild($div_mensaje);
            $div_mensaje->setAttribute('class', 'tip');
        }
        return $view;
    }
    private function obtener_envvars($id_marchand)
    {
        $array=json_decode($this->micrositio->get_envvars(), true);
        return $array;
    }
    private function autenticar_pagador($id_marchand, $post, $cookie)
    {
        if (isset($cookie[self::NOMBRE_COOKIE])) {
            if (self::ACTIVAR_DEBUG) {
                developer_log('Intenta autenticación con Cookie.');
            }
            return $this->autenticar_pagador_con_cookie($id_marchand, $cookie);
        } elseif ($post) {
            if (self::ACTIVAR_DEBUG) {
                developer_log('Intenta autenticación con Post.');
            }
            $climarchand=$this->autenticar_pagador_con_post($id_marchand, $post);
            if ($climarchand) {
                $this->grabar_log('El usuario inicia su sesión. ');
            } else {
                $this->grabar_log('El usuario falla al iniciar sesión. ');
            }
            return $climarchand;
        }
        return false;
    }
    private function autenticar_pagador_con_cookie($id_marchand, $cookie)
    {
        $clave=$this->micrositio->get_token();
        $id_climarchand=$this->descifrar_cookie($cookie[self::NOMBRE_COOKIE], $clave);
        if ($id_climarchand) {
            $climarchand=new Climarchand();
            if ($climarchand->get($id_climarchand)) {
                return $climarchand;
            }
        }
        return false;
    }
    private function autenticar_pagador_con_post($id_marchand, $atributos)
    {
        $envvars=$this->obtener_envvars($id_marchand);
        if (!$envvars or !isset($envvars['autenticacion']['inputs'])) {
            developer_log('No hay atributos de autenticación.');
            return false;
        }
        foreach ($envvars['autenticacion']['inputs'] as $atributo_de_autenticacion) {
            if (!isset($atributos[$atributo_de_autenticacion['name']]) or !$atributos[$atributo_de_autenticacion['name']]) {
                if ($atributo_de_autenticacion['required']=='required') {
                    error_log($atributo_de_autenticacion['name']);
                    developer_log('Faltan campos requeridos. ');
                    return false;
                }
            }
        }
        $saps=array();
        $variables=array();
        $exactitud=true;
        $variables['id_authstat']=Authstat::ACTIVO;
        foreach ($envvars['autenticacion']['inputs'] as $in) {
            if (isset($atributos[$in['name']])) {
                $variables[$in['tag']]=$atributos[$in['name']];
                $saps[]=$in['tag'];
            }
        }

        $recordset=Climarchand::select_clientes($id_marchand, $saps, $variables, $exactitud);
        if ($recordset and $recordset->RowCount()==1) {
            $row=$recordset->FetchRow();
            $climarchand=new Climarchand($row);
            return $climarchand;
        }
        if ($recordset->RowCount()==0) {
            if (self::ACTIVAR_DEBUG) {
                developer_log('No existe el pagador.');
            }
        }
        if ($recordset->RowCount()>1) {
            developer_log('El pagador no es único.');
        }
        return false;
    }
    private function obtener_footer(){
        $footer="";
        $json= $this->micrositio->get_envvars();
        $array= json_decode($json,true);
        if(isset($array["autenticacion"]["paso2.footer"]))
            $footer.=$array["autenticacion"]["paso2.footer"];
        return $footer;
    }
    private function mostrar_boletas_pendientes_de_pagador($id_marchand, $id_climarchand)
    {
        $view=new View();
        $view->cargar('views/micrositio.paso_2.html');
        $titulo=$this->obtener_titulo_de_micrositio();
        $footer= $view->getElementById("footer");
        $footer->appendChild($view->createTextNode($this->obtener_footer()));
        $view->getElementById('title')->appendChild($view->createTextNode($titulo));
        $view->getElementById('boton')->setAttribute('name', self::TOKEN);
        $view->getElementById('boton')->setAttribute('value', self::TOKEN_SALIR);
        $view->getElementById('boton_efectivo')->setAttribute('name', self::TOKEN);
        $view->getElementById('boton_efectivo')->setAttribute('value', self::TOKEN_PAGAR_EFECTIVO);
        $view->getElementById('boton_efectivo')->removeAttribute('id');
        $view->getElementById('boton_medios_electronicos')->setAttribute('name', self::TOKEN);
        $view->getElementById('boton_medios_electronicos')->setAttribute('value', self::TOKEN_PAGAR_MEDIOS_ELECTRONICOS);
        $view->getElementById('boton_medios_electronicos')->removeAttribute('id');
        $view->getElementById('boton_tarjeta_de_credito')->setAttribute('name', self::TOKEN);
        $view->getElementById('boton_tarjeta_de_credito')->setAttribute('value', self::TOKEN_PAGAR_TARJETA_DE_CREDITO);
        $view->getElementById('boton_tarjeta_de_credito')->removeAttribute('id');
        $string_etiqueta=false;
        if (!($string_etiqueta=Pagador::buscar_por_nombre(self::ETIQUETA_ESTRUCTURA, $this->climarchand->get_cliente_xml()))) {
            $string_etiqueta=Pagador::buscar_por_nombre(self::ETIQUETA_ESTRUCTURA_AUXILIAR, $this->climarchand->get_cliente_xml());
        }
        if ($string_etiqueta) {
            $view->getElementById('campo_mostrado')->appendChild($view->createTextNode('Bienvenido, '.$string_etiqueta));
        }

        $table=$view->getElementById('div_boletas');
        $boletas=$this->obtener_boletas_pendientes_de_pagador($id_climarchand);
        $boletas_pendientes=0;

        $i=0;
        if (count($boletas)>0) {
            foreach ($boletas as $boleta) {
                if ($boleta['id_authstat']==Authstat::BARCODE_PENDIENTE) {
                    $boletas_pendientes++;
                    $i++;
                    $tr=$view->createElement('div');
                    $tr->setAttribute('class', 'fila');
                    $tr->setAttribute('data-boleta', $boleta['nroboleta']);
                    $td_1=$view->createElement('span', $boleta['nroboleta']);
                    $td_2=$view->createElement('span', $boleta['boleta_concepto']);
                    $datetime_fecha=DateTime::createFromFormat(Barcode::FORMATO_FECHA_VTO, $boleta['fecha_vto']);
                    $fecha_presentable=$datetime_fecha->format(self::FORMATO_PRESENTACION_FECHA);
                    $td_3=$view->createElement('span', $fecha_presentable);
                    $td_4=$view->createElement('span', '$ '.formato_plata($boleta['monto']));
                    $td_5=$view->createElement('div', '$');
                    $td_5->setAttribute('class', 'pagar');
                    $tr->appendChild($td_1);
                    $tr->appendChild($td_2);
                    $tr->appendChild($td_3);
                    $tr->appendChild($td_4);
                    $tr->appendChild($td_5);
                    $table->appendChild($tr);
                }
            }
        }

        if (!$boletas_pendientes) {
            $tr=$view->createElement('div', 'No hay boletas pendiente de pago. ');
            $tr->setAttribute('class', 'pagar');
            $table->appendChild($tr);
            if (self::ACTIVAR_DEBUG) {
                developer_log('No hay boletas pendientes de pago.');
            }
        }
        return $view;
    }
    private function obtener_boletas_pendientes_de_pagador($id_climarchand, $nroboleta = false)
    {
        # NO TERMINADA!!!
        $recordset=Bolemarchand::select_proximos_vencimientos($id_climarchand, $nroboleta,$this->marchand->get_id_marchand());
        return $recordset;
    }
    private function mostrar_boleta_pago_efectivo($id_climarchand, $nroboleta)
    {
        $view=new View();
        $view->cargar('views/micrositio.pago_efectivo.html');
        $titulo = $view->getElementById("titulo");
        $title = $view->getElementById("title");
        $title->appendChild($view->createTextNode("Micrositio ".$this->obtener_titulo_de_micrositio()));
        $titulo->appendChild($view->createTextNode($this->obtener_titulo_de_micrositio()));
        $view->getElementById('boton_volver')->setAttribute('name', self::TOKEN);
        $view->getElementById('boton_volver')->setAttribute('value', self::TOKEN_BOLETAS_DE_PAGADOR);
        if (($boleta=$this->obtener_boleta_html_de_pagador($id_climarchand, $nroboleta))) {
            $boleta_div=$view->getElementById('boleta');
            $boleta_div->appendChild($view->importNode($boleta->documentElement, true));
            return $view;
        }
        return false;
    }
    private function obtener_boleta_html_de_pagador($id_climarchand, $nroboleta)
    {
        $id_marchand=$this->marchand->get_id_marchand();
        $array=array('id_marchand'=>$id_marchand,'nroboleta'=>$nroboleta,'id_climarchand'=>$id_climarchand);
        $recordset=Bolemarchand::select($array);
        if ($recordset and $recordset->RowCount()==1) {
            $bolemarchand=new Bolemarchand($recordset->FetchRow());
            $dom_document=new DOMDocument('1.0', 'UTF-8');
            if ($dom_document->loadHTML($bolemarchand->get_boleta_html())) {
                return $dom_document;
            }
        }
        return false;
    }
    private function mostrar_boleta_pago_medios_electronicos($id_climarchand, $nroboleta)
    {
        $recordset=$this->obtener_boletas_pendientes_de_pagador($id_climarchand, $nroboleta);
        if (!$recordset or $recordset->RowCount()!=1) {
            return false;
        }
        $row=$recordset->FetchRow();
        $pmc19=$row['pmc19'];
        if (!$row['pmc19']) {
            return false;
        }
        if ($row['id_authstat']!=Authstat::BARCODE_PENDIENTE) {
            return false;
        }
        $view=new View();
        $view->cargar('views/micrositio.pago_medios_electronicos.html');
        $titulo=$this->obtener_titulo_de_micrositio();
        $view->getElementById('boton_volver')->setAttribute('name', self::TOKEN);
        $view->getElementById('boton_volver')->setAttribute('value', self::TOKEN_BOLETAS_DE_PAGADOR);
        $view->getElementById('codigo_electronico')->appendChild($view->createTextNode($pmc19));
        $view->getElementById('link1')->appendChild($view->createTextNode($pmc19));
        $view->getElementById('pmc1')->appendChild($view->createTextNode($pmc19));
        $view->getElementById('pmc2')->appendChild($view->createTextNode($pmc19));

        return $view;
    }
    private function mostrar_boleta_pago_tarjeta_de_credito($id_climarchand, $nroboleta)
    {
        
        $recordset=$this->obtener_boletas_pendientes_de_pagador($id_climarchand, $nroboleta);
        if (!$recordset or $recordset->RowCount()!=1) {
            Gestor_de_log::set('No existen boletas pendientes de pago. ');
            return false;
        }
        $row=$recordset->FetchRow();
        if ($row['id_authstat']!=Authstat::BARCODE_PENDIENTE) {
            Gestor_de_log::set('Tiene al menos un barcode pagado.');
            return false;
        }
        $codigo_de_barras=$row['barcode'];
        $monto=$row['monto'];
        $monto=floatval($monto); # ES NECESARIO ESTO?!
        $concepto=$row['boleta_concepto'];

        $identificador_cliente=$nombre=$apellido=$correo=false;
        
        $estructura_xml=Xml::estructura($this->marchand->get_id_marchand(), Entidad::ESTRUCTURA_CLIENTES);
        if ($estructura_xml) {
            $array=Pagador::armar_array($this->climarchand->get_cliente_xml(), $estructura_xml);

            if (isset($array['sap_identificador']) and $array['sap_identificador']['value']) {
                $identificador_cliente=$array['sap_identificador']['value'];
            } elseif (isset($array['sap_idcliente']) and $array['sap_idcliente']['value']) {
                $identificador_cliente=$array['sap_idcliente']['value'];
            }
            if (isset($array[self::ETIQUETA_ESTRUCTURA]) and $array[self::ETIQUETA_ESTRUCTURA]['value']) {
                $explode=explode(' ', $array[self::ETIQUETA_ESTRUCTURA]['value']);
                $apellido=$explode[0];
                if (isset($explode[1])) {
                    $nombre=$explode[1];
                }
            } elseif (isset($array[self::ETIQUETA_ESTRUCTURA_AUXILIAR]) and $array[self::ETIQUETA_ESTRUCTURA_AUXILIAR]['value']) {
                $explode=explode(' ', $array[self::ETIQUETA_ESTRUCTURA_AUXILIAR]['value']);
                $apellido=$explode[0];
                if (isset($explode[1])) {
                    $nombre=$explode[1];
                }
            }

            if (isset($array['sap_delivery']) and $array['sap_delivery']['value']) {
                $correo=$array['sap_delivery']['value'];
            } elseif (isset($array['sap_email']) and $array['sap_email']['value']) {
                # Existe sap_email ???
                $correo=$array['sap_email']['value'];
            }
        }
        $preference=$this->crear_preferencia_mercadopago($codigo_de_barras, $concepto, $monto, $identificador_cliente, $nombre, $apellido, $correo);
        if (!$preference) {
            return false;
        }
        header('location: '.$preference["response"]["init_point"]);
        exit();  # ES NECESARIO ESTO?!
    }
    private function crear_preferencia_mercadopago($codigo_de_barras, $concepto, $monto, $identificador_cliente = false, $nombre = false, $apellido = false, $correo = false)
    {
        require_once PATH_PUBLIC."sdk-php/lib/mercadopago.php";
        # Hacer esta funcion estatica y sacarla de esta clase
        if (!is_numeric($monto)) {
            return false;
        }
        $acc_id=$this->obtener_cuenta_de_mercadopago($codigo_de_barras);
        if (!$acc_id) {
            developer_log('El cliente no tiene activado el acceso a MercadoPago. ');
            Gestor_de_log::set('El cliente no tiene activado el acceso a MercadoPago. ');
            return false;
        }
        $credenciales=Preprocesador_mercadopago::obtener_credenciales($acc_id);
        if (!$credenciales) {
            developer_log('La cuenta no esta bien configurada.');
            Gestor_de_log::set('El cliente no tiene activado el acceso a MercadoPago. ');
            return false;
        }
        list($client_id,$client_secret)=$credenciales;
        $mp = new MP_lib($client_id, $client_secret);
        $item=array(
                    "id" => $codigo_de_barras,
                    "title" => $concepto,
                    "currency_id" => "ARG",
                    "description" => $concepto,
                    "category_id" => "Micrositio",
                    "quantity" => 1,
                    "unit_price" => $monto
                );


        $pagador=array(
                "date_created" => date('Y-m-d H:i:s')
            );
        if ($nombre) {
            $pagador['name']=$nombre;
        }
        if ($apellido) {
            $pagador['surname']=$apellido;
        }
        if ($correo) {
            $pagador['email']=$correo;
        }
        if ($identificador_cliente and is_numeric($identificador_cliente)) {
            $pagador['identification']=array('type'=>'identificador','number'=>$identificador_cliente);
        }
        $url='http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}";
        $preference_data = array(
            "items" => array(
                $item
            ),
            "back_urls" => array(
                "success" => $url,
                "failure" => $url,
                "pending" => $url
            ),
            "auto_return" => "approved",
            "external_reference" => $codigo_de_barras
        );
        if (count($pagador)>1) {
            $preference_data['payer']=$pagador;
        }
    
        return $mp->create_preference($preference_data);
    }
    private function obtener_cuenta_de_mercadopago($codigo_de_barras)
    {
        $recordset=Xml::select_cuenta_de_mercadopago($codigo_de_barras);
        if ($recordset and $recordset->RowCount()==1) {
            $row=$recordset->FetchRow();
            return $row['acc_id'];
        }
        return false;
    }
    private function obtener_marchand($id_marchand)
    {
        $marchand=new Marchand();
        if ($marchand->get($id_marchand)) {
            return $marchand;
        }
        return false;
    }
    private function obtener_titulo_de_micrositio()
    {

        return $this->micrositio->get_titulo();
    }
    protected  function cifrar_cookie($cookie, $clave)
    {
        $gestor=new Gestor_de_hash($clave);
        return $gestor->cifrar($cookie);
    }
    protected  function descifrar_cookie($cookie, $clave)
    {
        $gestor=new Gestor_de_hash($clave);
        return $gestor->descifrar($cookie);
    }
    protected  function grabar_log($mensaje)
    {
        if ($this->marchand) {
            $dbmenso_array=array();
            $dbmenso_array['ip']=$_SERVER['REMOTE_ADDR'];
            $dbmenso_array['port']=$_SERVER['REMOTE_PORT'];
            $dbmenso_array['script']=basename(__FILE__);
            $dbmenso_array['class']=basename(get_called_class());
            $dbmenso=json_encode($dbmenso_array);
            $id_marchand=$this->marchand->get_id_marchand();
            if ($this->climarchand) {
                $id_climarchand=$this->climarchand->get_id_climarchand();
            } else {
                $id_climarchand=null;
            }
            Gestor_de_log::set($mensaje, 0);
            return Gestor_de_log::set_micrositio($id_marchand, $id_climarchand, $mensaje, $dbmenso);
        }
    }
    protected function mostrar_mensaje_de_error($mensaje)
    {
        $view=new View();
        $view->cargar('views/micrositio.error.html');
        $titulo=$this->obtener_titulo_de_micrositio();
        $view->getElementById('titulo')->appendChild($view->createTextNode($titulo));
        $view->getElementById('title')->appendChild($view->createTextNode($titulo));
        if ($mensaje) {
            $div=$view->getElementById('contenedor_mensaje');
            $div_mensaje=$view->createElement('p', $mensaje);
            $div->appendChild($div_mensaje);
            $div_mensaje->setAttribute('class', 'tip');
        } else {
        }
        $boton=$view->getElementById('boton');
        $boton->setAttribute('name', self::TOKEN);
        # No hace falta ponerle ningun value, es como un refresh
        $boton->setAttribute('value', 'Volver');
        return $view;
    }
}
