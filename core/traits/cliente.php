<?php

class Cliente {

    const ACTIVAR_DEBUG = true;

    public $rubro_sc = false;
    public $marchand = false; #NO OPTIMIZO
    public $trixgroup = false; #NO OPTIMIZO
    public $sc = false; #NO OPTIMIZO
    public $xml = false; #NO OPTIMIZO Es el config marchand
    public $usumarchand = false; #NO OPTIMIZO
    public $pwd = '';

    # Usadas al crear la config Marchand

    const DUMMY_XMLFIELD = "<config></config>";
    const DUMMY_MODELO = "estandar";
    const DUMMY_DESCRIPCION = "Configuracion Autogenerada";
    const DUMMY_ID_TRIXGROUP = "3";
    const USUVIP = '0';
    const ASUNTO_DEL_CORREO_DE_BIENVENIDA = 'Bienvenido a CobroDigital';
    public static $es_cobrador;
    public static $es_proveedor;

    public static function set_es_cobrador($escobrador){
        self::$es_cobrador=$escobrador;
    }
    public static function set_es_proveedor($esproveedor){
        self::$es_proveedor=$esproveedor;
    }

    public static function create($es_cobrador=false,$es_proveedor=false){
        $instance = new self();
        $instance::$es_cobrador=$es_cobrador;
        $instance::$es_proveedor=$es_proveedor;
        return $instance;
    }

    public static function createFromMarchand($marchand){
        $instance = new self();
         $instance->marchand= $marchand;
         return $instance;
    }
    public function crear(Marchand $marchand, Usumarchand $usumarchand) {
        # Blanqueo valores
        $hoy = new DateTime("now");
        $marchand->set_id_marchand('');
        $marchand->set_mercalpha('');
        $marchand->set_fechaingreso($hoy->format("Y-m-d"));
        $usumarchand->set_id_usumarchand('');
        $usumarchand->set_id_marchand('');
        $usumarchand->set_id_authcode('');
        $usumarchand->set_usuvip('');
        $password_sin_cifrar = $usumarchand->get_userpass();
        $this->pwd = $password_sin_cifrar;
        //$usumarchand->set_userpass('');

        $marchand->set_mercalpha(Marchand::generar_mercalpha());
        if (!$this->validar_campos($marchand)) {
            throw new Exception('Los campos no son correctos.');
        }
        if (!$this->validar_campos_usumarchand($usumarchand)) {
            throw new Exception('Los campos del Usuario no son correctos.');
        }
        Model::StartTrans();
        $this->marchand = $marchand;


        if (!Model::HasFailedTrans()) {
            if (!$this->marchand->set()) {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('Ha ocurrido un error al crear el cliente.');
                }
                Model::FailTrans();
            } else {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('Cliente creado correctamente.');
                }
            }
        }

        if (!Model::HasFailedTrans()) {
            if (!($this->sc = $this->obtener_sc($this->marchand))) {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('Ha ocurrido un error al obtener el segmento comercial.');
                }
                Model::FailTrans();
            } else {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('Segmento Comercial obtenido correctamente.');
                }
            }
        }

        if (!Model::HasFailedTrans()) {
            if (!($this->trixgroup = $this->crear_trixgroup($this->marchand, $this->sc))) {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('Ha ocurrido un error al crear el trixgroup.');
                }
                Model::FailTrans();
            } else {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('Trixgroup creado correctamente.');
                }
            }
        }

        if (!Model::HasFailedTrans()) {
            if (!($this->xml = $this->crear_config_marchand_basica($this->marchand))) {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('Ha ocurrido un error al crear la configuración básica.');
                }
                Model::FailTrans();
            } else {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('Configuración básica creada correctamente.');
                }
            }
        }
        if (!Model::HasFailedTrans()) {
	    error_log("Creando usuario Administrador con $password_sin_cifrar y ".json_encode($usumarchand));
            if (!($this->usumarchand = $this->crear_usuario_administrador($this->marchand, $usumarchand,$password_sin_cifrar))) {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('Ha ocurrido un error al crear el usuario administrador.');
                }
                Model::FailTrans();
            } else {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('Usuario administrador creado correctamente.');
                }
            }
        }
        if (!Model::HasFailedTrans()) {
            if (!$this->crear_carpeta($this->marchand)) {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('Ha ocurrido un error al crear las carpetas del cliente.');
                }
                Model::FailTrans();
            } else {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('Carpetas del cliente creadas correctamente.');
                }
            }
        }
        if (Model::CompleteTrans() AND ! Model::hasFailedTrans()) {
            if (self::ACTIVAR_DEBUG) {
                developer_log('Cliente dado de alta correctamente.');
            }
            if (!Model::HasFailedTrans() AND !self::$es_proveedor) {
                if (!$this->enviar_correo_de_bienvenida($this->marchand, $this->usumarchand, $password_sin_cifrar)) {
                    if (self::ACTIVAR_DEBUG) {
                        developer_log('Ha ocurrido un error al enviar el correo de bienvenida.');
                    }
                    Model::FailTrans();
                } else {
                    if (self::ACTIVAR_DEBUG) {
                        developer_log('Correo electrónico de bienvenida enviado correctamente.');
                    }
                }
            }
            return $this;
        }

        if (self::ACTIVAR_DEBUG) {
            developer_log('Ha ocurrido un error al dar de alta al Cliente.');
        }
        return false;
    }

    public function editar(Marchand $marchand) {
        if (!$this->validar_campos($marchand)) {
            throw new Exception('Los campos no son correctos.');
        }
        # No permitir edicion de campos de identificacion!
        Model::StartTrans();
        $this->marchand = $marchand;

        if (!Model::HasFailedTrans()) {
            if (!$this->marchand->set()) {
                Model::FailTrans();
            }
        }
        if (Model::CompleteTrans() AND ! Model::hasFailedTrans()) {
            if (self::ACTIVAR_DEBUG) {
                developer_log('Cliente Editado correctamente.');
            }
            return $this;
        }

        if (self::ACTIVAR_DEBUG) {
            developer_log('Ha ocurrido un error al Editar al Cliente.');
        }
        return false;
    }

    private function validar_campos($marchand) {

        return true;
    }

    private function validar_campos_usumarchand($usumarchand) {

        return true;
    }

    private function obtener_sc(Marchand $marchand) {
        # De acuerdo al rubro al que pertenezca el marchand,
        # se le asignara un segmento comercial
        $recordset = Rubro_sc::select_maximo_id_subrubro($marchand->get_id_subrubro());

        if (!$recordset OR $recordset->RowCount() != 1)
            return false;
        $row = $recordset->FetchRow();
        $sc = new Sc();
        if (!isset($row['max']))
            return false;
        if ($sc->get($row['max']))
            return $sc;
        return false;
    }

    private function crear_trixgroup(Marchand $marchand, Sc $sc) {
        $trixgroup = new Trixgroup();
        $trixgroup->set_id_marchand($marchand->get_id_marchand());
        $trixgroup->set_id_sc($sc->get_id());
        $trixgroup->set_trixgroup($marchand->get_minirs());
        if (($prefijo = $this->obtener_prefijo($sc)) === false) {
            return false;
        }
        $trixgroup->set_trixgroupid($prefijo);
        if ($trixgroup->set()) {
            return $trixgroup;
        }
        return false;
    }

    private function obtener_prefijo(Sc $sc) {
        $recordset = Trixgroup::select_mayor_prefijo($sc->get_id());
        $row = $recordset->FetchRow();
        $maximo_prefijo = $row['max'];
        $prefijo = trim($maximo_prefijo) + 1;
        $maximo_soportado = str_pad('', $sc->get_ndigstrix(), '9', STR_PAD_LEFT);
        if ($prefijo > $maximo_soportado) {
            return false;
        }
        $prefijo_formateado = str_pad($prefijo, $sc->get_ndigstrix(), '0', STR_PAD_LEFT);
        return $prefijo_formateado;
    }

    public static function crear_config_marchand_basica(Marchand $marchand) {
        $xml = new Xml();
        $xml->set_id_marchand($marchand->get_id());
        $xml->set_id_entidad(Entidad::ESTRUCTURA_CONFIG_MARCHAND);
        $xml->set_xmlfield(self::DUMMY_XMLFIELD);
        $xml->set_modelo(self::DUMMY_MODELO);
        $xml->set_id_trixgroup(self::DUMMY_ID_TRIXGROUP);
        $xml->set_descripcion(self::DUMMY_DESCRIPCION);
        if ($xml->set()) {
            return $xml;
        }
        return false;
    }

    public function apellido_rs(){
        return $this->marchand-get_apellido_rs();
    }
    
    public function nombre(){
        $this->marchand->get_nombre();
    }
    
    public function documento(){
        return $this->marchand->get_documento();
    }
    public function email(){
        return $this->marchand->get_email(); 
    }
    
    public function id_pfpj(){
        return $this->marchand->get_id_pfpj();
    }
    
    public static function crear_usuario_administrador(Marchand $marchand, Usumarchand $usumarchand,$password_sin_cifrar, $es_proveedor = false) {
//         = $usumarchand->get_userpass();
        $usumarchand->set_userpass('');

        $usumarchand->set_usuvip(self::USUVIP);

        
        $usumarchand->set_id_authcode(Authcode::USUARIO_EXTERNO);
        $usumarchand->set_id_marchand($marchand->get_id_marchand());
        if ($usumarchand->set()) {
            error_log($password_sin_cifrar);
            $usumarchand->setPassword($password_sin_cifrar);
            $usumarchand->set();
            error_log($password_sin_cifrar);
            error_log("Password seteado");
            if(self::$es_proveedor){
                if (self::asignar_permisos_de_proveedor($usumarchand)) {
                    if (self::ACTIVAR_DEBUG) {
                        developer_log('Permisos de Usuario proveedor creados correctamente.');
                    }
                    return $usumarchand;
                }
            }else{
                if (self::asignar_permisos_de_administrador($usumarchand)) {
                    if (self::ACTIVAR_DEBUG) {
                        developer_log('Permisos de Usuario administrador creados correctamente.');
                    }
                    return $usumarchand;
                }    
            }
            
        }
        if (self::ACTIVAR_DEBUG) {
            developer_log('Ha ocurrido un error al asignar permisos al usuario.');
        }
        return false;
    }

    private static function asignar_permisos_de_administrador(Usumarchand $usumarchand) {

        return Usumarchand::asignar_permisos_de_administrador($usumarchand->get_id());
    }
    
    private static function asignar_permisos_de_proveedor(Usumarchand $usumarchand) {

        return Usumarchand::asignar_permisos_de_proveedor($usumarchand->get_id());
    }
    

    private function crear_carpeta(Marchand $marchand) {
        $carpeta_cd_exports = PATH_CDEXPORTS . $marchand->get_mercalpha() . '/';
        if (Gestor_de_disco::crear_carpeta($carpeta_cd_exports)) {
            return true;
        }
        return false;
    }

    public static function enviar_correo_de_bienvenida(Marchand $marchand, Usumarchand $usumarchand = null, $password_sin_cifrar = '******') {
        $emisor = Gestor_de_correo::MAIL_COBRODIGITAL_INFO;
        $destinatario = $marchand->get_email();
        $asunto = self::ASUNTO_DEL_CORREO_DE_BIENVENIDA;
        if ($usumarchand === null) {
            if (!($usumarchand = self::obtener_usuario_administrador($marchand))) {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('Ha ocurrido un error al obtener el Usuario Administrador.');
                }
                #MDC 
                return false;
            } else {
                if (self::ACTIVAR_DEBUG) {
                    developer_log('Usuario Administrador correctamente obtenido.');
                }
            }
        }
        if (!($mensaje = self::crear_mensaje_de_bienvenida($marchand))) {
            if (self::ACTIVAR_DEBUG) {
                developer_log('Ha ocurrido un error al crear el mensaje de bienvenida.');
            }
            return false;
        }
        if (!($file_path = self::crear_archivo_de_bienvenida($marchand, $usumarchand, $password_sin_cifrar))) {
            if (self::ACTIVAR_DEBUG) {
                developer_log('Ha ocurrido un error al crear el archivo de bienvenida.');
            }
            return false;
        }

        if (Gestor_de_correo::enviar($emisor, $destinatario, $asunto, $mensaje, $file_path)) {
            return true;
        }
        return false;
    }

    private static function crear_mensaje_de_bienvenida(Marchand $marchand) {
        # Retorna el codigo HTML del mensaje
        if(!self::$es_cobrador){
            libxml_use_internal_errors(true);
            $mensaje = new DOMDocument('1.0', 'utf-8');
            libxml_clear_errors();
            if (!$mensaje->loadHTMLFile(PATH_INTERNO . 'views/mail_bienvenida.html')) {
                return false;
            }
            $elemento = $mensaje->getElementById('nombre');
            if ($marchand->get_id_pfpj() == 1) {
                $reemplazo = $marchand->get_nombre() . ' ' . $marchand->get_apellido_rs();
            } else {
                $reemplazo = $marchand->get_nombre();
            }
            $elemento->appendChild($mensaje->createTextNode($reemplazo));

            if ($marchand->get_presentadocumentacion()) {
                $elemento = $mensaje->getElementById('presentadocumentacion');
                $elemento->parentNode->removeChild($elemento);
            }
            return $mensaje->saveHTML();
        }
        else{
            libxml_use_internal_errors(true);
            $mensaje = new DOMDocument('1.0', 'utf-8');
            libxml_clear_errors();
            if (!$mensaje->loadHTMLFile(PATH_INTERNO . 'views/mail_bienvenida_cobrador.html')) {
                return false;
            }
            $elemento = $mensaje->getElementById('nombre');
            if ($marchand->get_id_pfpj() == 1) {
                $reemplazo = $marchand->get_nombre() . ' ' . $marchand->get_apellido_rs();
            } else {
                $reemplazo = $marchand->get_nombre();
            }
            return $mensaje->saveHTML();
        }
    }

    private static function crear_archivo_de_bienvenida(Marchand $marchand, Usumarchand $usumarchand, $password_sin_cifrar = '******') {
        # Retorna la ruta al archivo
        if(self::$es_cobrador){
            return PATH_PUBLIC."Manual_Sitio_de_Cobradores.pdf";
        }
        if ($marchand->get_id_pfpj() == 1) {
            $nombrePrincipal = 'Nombre';
            $nombrePrincipalDato = $marchand->get_nombre();
            $nombreSecundario = 'Apellido';
            $nombreSecundarioDato = $marchand->get_apellido_rs();
        } else {
            $nombrePrincipal = 'Nombre Completo';
            $nombrePrincipalDato = $marchand->get_nombre();
            $nombreSecundario = 'Razón Social';
            $nombreSecundarioDato = $marchand->get_apellido_rs();
        }
        $pais = new Pais();
        $pais->get($marchand->get_gr_id_pais());
        $provincia = new Provincia();
        $provincia->get($marchand->get_gr_id_provincia());
        $localidad = new Localidad();
        $localidad->get($marchand->get_gr_id_localidad());
        $civa = new Civa();
        $civa->get($marchand->get_id_civa());
        $subrubro = new Subrubro();
        $subrubro->get($marchand->get_id_subrubro());
        $rubro = new Rubro();
        $rubro->get($subrubro->get_id_rubro());

        $direccion = $marchand->get_gr_calle() . ' ' . $marchand->get_gr_numero() . ' ' . $marchand->get_gr_piso() . ' ' . $marchand->get_gr_depto();
        libxml_use_internal_errors(true);
        $view = new DOMDocument('1.0', 'utf-8');
        libxml_clear_errors();
        if (!$view->loadHTMLFile(PATH_INTERNO . 'views/comprobante_de_alta.html')) {
            return false;
        }
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        $array = array();
        $array[] = array('fecha', date('l jS \of F Y'));
        $array[] = array('loginComercio', $marchand->get_mercalpha());
        $array[] = array('loginUsuario', $usumarchand->get_username());
        $array[] = array('loginPassword', $password_sin_cifrar);
        $array[] = array('mercalpha', $marchand->get_mercalpha());
        $array[] = array('username', $usumarchand->get_username());
        $array[] = array('userpass', $password_sin_cifrar);
        $array[] = array('nombrePrincipal', $nombrePrincipal);
        $array[] = array('nombrePrincipalDato', $nombrePrincipalDato);
        $array[] = array('nombreSecundario', $nombreSecundario);
        $array[] = array('nombreSecundarioDato', $nombreSecundarioDato);
        $array[] = array('documentoDato', $marchand->get_documento());
        $array[] = array('direccion', $direccion);
        $array[] = array('codigoPostal', $marchand->get_gr_ncpo());
        $array[] = array('pais', $pais->get_pais());
        $array[] = array('provincia', $provincia->get_provincia());
        $array[] = array('localidad', $localidad->get_localidad());
        $array[] = array('telefono', $marchand->get_telefonos());
        $array[] = array('correo', $marchand->get_email());
        $array[] = array('condicion', $civa->get_civa());
        $array[] = array('rubro', $rubro->get_rubro());
        $array[] = array('subrubro', $subrubro->get_subrubro());

        foreach ($array as $row) {
            self::reemplazar_por_id($view, $row[0], $row[1]);
        }
        if ($marchand->get_presentadocumentacion()) {
            $elemento = $view->getElementById('presentadocumentacion');
            $elemento->parentNode->removeChild($elemento);
        }
        $path = PATH_CDEXPORTS . $marchand->get_mercalpha() . '/';
        $filename = 'comprobante_de_alta.html';

        if (Gestor_de_disco::crear_archivo($path, $filename, $view->saveHTML())) {
            return $path . $filename;
        } 
        
        return false;
    }

    public static function obtener_usuario_administrador(Marchand $marchand) {
        $recordset = Usumarchand::select_administradores($marchand->get_id_marchand());
        if ($recordset AND $recordset->RowCount() >= 1) {
            $row = $recordset->FetchRow();
            $usumarchand = new Usumarchand($row);
            return $usumarchand;
        }
        return false;
    }

    private static function reemplazar_por_id(DOMDocument $view, $id, $reemplazo) {
        if (!($elemento = $view->getElementById($id))) {
            return false;
        }
        $elemento->appendChild($view->createTextNode($reemplazo));
        return true;
    }

    public static function obtener_estado_de_cuenta($id_marchand) {
        $retenciones = self::obtener_retenciones($id_marchand);
        $recordset = Moves::obtener_estado_de_cuenta($id_marchand, $retenciones);

        if ($recordset AND $recordset->RowCount() == 1) {
            $registro = $recordset->FetchRow();
            if (!$registro['saldo'])
                $registro['saldo'] = 0;
            if (!$registro['aun_no_liquidado'])
                $registro['aun_no_liquidado'] = 0;
            foreach ($retenciones as $retencion) {
                if (!$registro[$retencion['mpflag']])
                    $registro[$retencion['mpflag']] = 0;
            }
        }
        elseif ($recordset AND $recordset->RowCount() == 0) {
            $registro = array();
            $registro['saldo'] = 0;
            $registro['aun_no_liquidado'] = 0;
            foreach ($retenciones as $retencion) {
                $registro[$retencion['mpflag']] = 0;
            }
        } else {
            if (self::ACTIVAR_DEBUG) {
                developer_log('Es posible que algun mpflag definido en el Config Marchand no sea columna de la tabla. ');
            }
            return false;
        }

        $registro['saldo_disponible'] = $registro['saldo'] - abs($registro['aun_no_liquidado']);
        $registro['encaje'] = 0;
        foreach ($retenciones as $retencion) {
            $registro['saldo_disponible'] = $registro['saldo_disponible'] - $registro[$retencion['mpflag']];
//	    $registro['saldo']=$registro['saldo'] - $registro[$retencion['mpflag']];
            $registro['encaje'] = $registro['encaje'] + $registro[$retencion['mpflag']];
        }
        if($registro['encaje']>$registro['saldo'])
            $registro['encaje']=$registro['saldo'];
/*        if ($registro['saldo_disponible'] < 0)
            $registro['saldo_disponible'] = 0;
  */      
        foreach ($registro as $key => $value) {
            if (is_numeric($key))
                unset($registro[$key]);
        }
        
        return $registro;
    }
    public function obtener_id_marchand(){
        
        return $this->marchand->get_id();
    }
    
    private static function obtener_retenciones($id_marchand) {
        $array = array();
        $recordset = Xml::select(array('id_marchand' => $id_marchand, 'id_entidad' => Entidad::ESTRUCTURA_CONFIG_MARCHAND));

        if ($recordset AND $recordset->RowCount() == 1) {
            $row = $recordset->FetchRow();
            $xmlfield = new View();
            $xmlfield->LoadXML($row['xmlfield']);
            $retenciones = $xmlfield->getElementsByTagName('retencion');
            if ($retenciones->length > 0) {

                foreach ($retenciones as $retencion) {
                    $porciento = $retencion->getElementsByTagName('porciento');
                    $plazo = $retencion->getElementsByTagName('plazo');

                    if ($retencion->hasAttribute('mpflag')
                            AND ( $porciento->length == 1 AND $plazo->length == 1)) {
                        $fila = array();
                        $fila['mpflag'] = strtolower(trim($retencion->getAttribute('mpflag')));
                        $fila['porciento'] = intval($porciento->item(0)->nodeValue);
                        $fila['plazo'] = intval($plazo->item(0)->nodeValue);
                        $array[] = $fila;
                    }
                }
            }
        }
        return $array;
    }

}
