<?php

class Responsable 
{
    const ACTIVAR_DEBUG=true;
    const PERMITIR_ALTA_CON_DNI=true;
    const COMPROBAR_NOMBRE=false;
    const COMPROBAR_APELLIDO=false;
    const PORCENTAJE_VALIDACION=50; # Para el nombre y apellido
    const VALIDAR_CBU=true;
    const VALIDAR_TCO=true;
    const VALIDAR_CUIT=false;
    const VALIDAR_DNI=false;
    const VALIDAR_CORREO=false;
    
    const VALIDACION_NOMBRE='/^[a-zA-Z\'\´ ]+$/';
    const VALIDACION_APELLIDO='/^[a-zA-Z\'\´ ]+$/';

	public static $clima=false; # OPTIMIZAR
	public static $clima_cbu=false; # OPTIMIZAR
    public static $clima_tco=false; # OPTIMIZAR

    # Debe existir el cuit DUMMY_CUIT_BYPASS en produccion
    const DUMMY_CUIT_BYPASS=1; # Usada para evitar las restricciones cruzadas entre clima,climadocs y clima_cbu
	const DUMMY_EVATEST=2;
    const DUMMY_ID_SCAN=1;
	const DUMMY_ID_LIQUIDAC=1;
	const DUMMY_ID_CLIMARCHAND=3;
	const DUMMY_ID_CLIMA_TCO=25;
    const DUMMY_ID_CLIMA_CBU=24;
	const DUMMY_ID_ENVISTAT=2;

    const DUMMY_REFERENCIA='Cuenta autogenerada por Lote de Débitos.';
    
    private $DB = null;
	# Crea el clima y lo asocia # Obtiene el clima y lo asocia # Obtiene el clima ya asociado
	public function crear($id_marchand, $nombre, $apellido, $documento, $id_tipodoc, $email=false){
            list($validez, $mensaje)=$this->validar_campos($nombre, $apellido, $documento, $id_tipodoc, $email);
            if(!$validez){
                throw new Exception($mensaje);
            }
            list($nombre,$apellido,$documento)=$this->preparar_campos($nombre,$apellido,$documento);

            Model::StartTrans();
            # Verifica si el responsable existe para Algun Cliente o para el 'Propio'
            $row= Clima::select_unique_clima($id_tipodoc, $documento);
            if($row===false){
                # El responsable no existe
                if(self::ACTIVAR_DEBUG) developer_log('No existe un responsable con el mismo documento. Dando de alta responsable.');
                self::$clima = new Clima();
                self::$clima->set_nombre($nombre);
                self::$clima->set_apellido_rs($apellido);
                self::$clima->set_documento($documento);
                if($email===false) $email=letras_aleatorias(32);
                self::$clima->set_email($email);
                self::$clima->set_evatest(self::DUMMY_EVATEST);
                $maskaron = letras_aleatorias(8);
                self::$clima->set_maskaron($maskaron);
                self::$clima->set_id_tipodoc($id_tipodoc);
                self::$clima->set_id_civa(Civa::CONSUMIDOR_FINAL);
                self::$clima->set_id_authstat(Authstat::ACTIVO);
                self::$clima->set_id_liquidac(self::DUMMY_ID_LIQUIDAC);
                self::$clima->set_upass(letras_aleatorias(32));

                if ($id_tipodoc!=Tipodoc::CUIT_CUIL OR substr($documento, 1, 2) == '20'){
                    self::$clima->set_id_tiposoc(9); # Crear Model Tiposoc
                    self::$clima->set_id_pfpj(Pfpj::PERSONA_FISICA);
                }
                elseif(substr($documento, 1, 2) == '30'){
                    self::$clima->set_id_tiposoc(4); # Crear Model Tiposoc
                    self::$clima->set_id_pfpj(Pfpj::PERSONA_JURIDICA);
                }

                if (!self::$clima->set()) {
                    Gestor_de_log::set("Ha ocurrido un error al crear el Responsable.", 0);
                    Model::FailTrans();
                }
                else{
                    # Aparentemente el Climadocs se genera solo por un Trigger
                }
            }        
            else {
                # El responsable ya existe para otro Cliente o para el 'propio'
                if(self::ACTIVAR_DEBUG) developer_log('Ya existe un responsable con el mismo documento. No es necesario dar de alta.');
                $clima=new Clima($row);

                $error=false;
                $mensaje='';
                if(self::COMPROBAR_NOMBRE AND !comparar_cadenas_relativas($nombre, $clima->get_nombre(),self::PORCENTAJE_VALIDACION)) {
                    $error=true;
                    $mensaje.='El nombre no coincide con el documento ingresado. ';
                }
                if(self::COMPROBAR_APELLIDO AND !comparar_cadenas_relativas($apellido, $clima->get_apellido_rs(),self::PORCENTAJE_VALIDACION)) {
                    $error=true;
                    $mensaje.='El apellido no coincide con el documento ingresado. ';
                }
//                if($email!==false AND strtolower(trim($clima->get_email()))!=strtolower(trim($email))) {
//                    $error=true;
//                    $mensaje.='El correo no coincide con el documento ingresado.';
//                }
                if($error) throw new Exception($mensaje);

                self::$clima=$clima;
            }   

            if (!Model::HasFailedTrans()) {
                    if(!$this->crear_pertenencia($id_marchand, self::$clima))
                            Model::FailTrans();
            } 

            if (Model::CompleteTrans() AND !Model::HasFailedTrans()) {
                if(self::ACTIVAR_DEBUG) developer_log('Ha procesado el Responsable correctamente.');
                return $this;
            }
            if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error al procesar el Responsable.');
            return false;
	}
        
	public function editar($id_marchand, $id_clima, $nombre, $apellido, $documento, $id_tipodoc, $email=false){
            list($validez, $mensaje)=$this->validar_campos($nombre, $apellido, $documento, $id_tipodoc,$email);
            if(!$validez){
                throw new Exception($mensaje);
            }
            list($nombre,$apellido,$documento)=$this->preparar_campos($nombre,$apellido,$documento);

            Model::StartTrans();
            # Editar un responsable solo si pertenece a un solo marchand
            $recordset_vinvulo=Vinvulo::select(array('id_clima'=>$id_clima));
            foreach ($recordset_vinvulo as $row) {
                if($row['id_entidad']==Entidad::ENTIDAD_MARCHAND AND $row['id_referencia']!=$id_marchand){
                    throw new Exception("Solo puede editar los pagadores que estan asociados nada mas que a su cuenta. ");
                }
            }

            $clima = new Clima();
            if(!$clima->get($id_clima)){
                Model::FailTrans();
            }

            list($temp,$temp,$documento_guardado)=$this->preparar_campos('','',$clima->get_documento());
            unset($temp);
            $id_tipodoc_guardado=$clima->get_id_tipodoc();
            if(!Model::hasFailedTrans()){
                $recordset_clima_cbu=Clima_cbu::select_id_clima($id_clima);
                foreach($recordset_clima_cbu as $row){
                    $clima_cbu=new Clima_cbu($row);
                    $clima_cbu->set_titular($nombre_titular=ucwords(strtolower(trim($nombre.' '.$apellido))));
                    if($documento!=$documento_guardado){
                        # Cambio el documento
                        $clima_cbu->set_cuit($documento);
                    }
                    $clima_cbu_array[]=clone $clima_cbu;
                }
            }

            if(!Model::hasFailedTrans()){
                foreach ($clima_cbu_array as $clima_cbu) {
                    $clima_cbu->set_cuit(self::DUMMY_CUIT_BYPASS);
                    if(!Model::hasFailedTrans()){
                        if(!$clima_cbu->set()){
                            Model::FailTrans();
                        }
                    }
                }
            }

            $clima->set_nombre($nombre);
            $clima->set_apellido_rs($apellido);            
            $clima->set_documento($documento);
            $clima->set_id_tipodoc($id_tipodoc);
            $clima->set_email($email);


            if(!Model::hasFailedTrans()){
                if(!$clima->set()){
                    Model::FailTrans();
                }
            }

            if(!Model::hasFailedTrans()){
                foreach ($clima_cbu_array as $clima_cbu) {
                    if(!Model::hasFailedTrans()){
                        $clima_cbu->set_cuit($documento);
                        if(!$clima_cbu->set()){
                            Model::FailTrans();
                        }
                    }
                }
            }

            if(Model::CompleteTrans() AND !Model::HasFailedTrans()){
                if(self::ACTIVAR_DEBUG) 
                    developer_log('Responsable editado correctamente.');
                return $this;
            }
            if(self::ACTIVAR_DEBUG) 
                    developer_log('Ha ocurrido un error al editar el responsable.');
                    return false;
	}
        
	# Crea el cbu y lo asocia # Obtiene el cbu y lo asocia # Obtiene el cbu ya asociado
	public function crear_cbu($id_marchand, $id_clima, $cbu, $id_tipocuenta, $nombre_titular, $cuit_titular, $referencia=false )
	{
        $cbu=trim($cbu);
        $cuit_titular=trim(str_replace('-', '', $cuit_titular));
        $nombre_titular=ucwords(strtolower(trim($nombre_titular)));
        if((self::VALIDAR_CUIT AND !validar_cuit($cuit_titular)) AND !self::PERMITIR_ALTA_CON_DNI) {
            throw new Exception('El CUIT/CUIL no es válido. ');
        }
        elseif(self::PERMITIR_ALTA_CON_DNI){
            if(self::VALIDAR_DNI AND !validar_dni($cuit_titular)){
                throw new Exception('El Documento no es válido: '.$cuit_titular);   
            }
        }
        if(self::VALIDAR_CBU AND !validar_cbu($cbu)){
            throw new Exception('El CBU no es válido. ');
        }

		Model::StartTrans();

        $row=Clima_cbu::existe_cbu($cbu,$id_clima);
	developer_log($row);
        if($row===false){
		developer_log("El clima_cbu no existe");
	       	Model::FailTrans();
	}
		if(!Model::HasFailedTrans()){
			if($this->optimizar_clima($id_clima)===false)
				Model::FailTrans();
		}
        if(!Model::hasFailedTrans())
        {
            $recordset=Climadocs::select(array('id_tipodoc'=>Tipodoc::CUIT_CUIL,'doctxt'=>$cuit_titular));
            if($recordset AND $recordset->RowCount()===0){
                $climadocs=new Climadocs();
                $climadocs->set_id_clima(self::$clima->get_id_clima());
                $climadocs->set_id_tipodoc(Tipodoc::CUIT_CUIL);
                $climadocs->set_id_scan(self::DUMMY_ID_SCAN);
                $climadocs->set_doctxt($cuit_titular);
                if (!$climadocs->set()) {
                    Gestor_de_log::set("Ha ocurrido un error al dar de alta el Documento del Responsable.", 0);
                    Model::FailTrans();
                }

            }
        }
		if(!Model::HasFailedTrans()){
        	$maskaron=self::$clima->get_maskaron();
	        if($row===0){
	            # El CBU no existe para ningun Marchand
                if(self::ACTIVAR_DEBUG) developer_log('El CBU no existe para el responsable, para ningun Cliente. Dando de alta.');
	            $banco=  substr($cbu, 0,3);
                    $banco=str_pad($banco,5,"0",STR_PAD_LEFT);
                    $arr_banco = Banco::select(array("codbanco"=>$banco));
                    $datos_banco = $arr_banco->FetchRow();
                    $id_banco = $datos_banco['id_banco'];
	            $sucursal=substr($cbu,4,3);
	            $cuenta=substr($cbu,8,13);
	            self::$clima_cbu=new Clima_cbu();
                if(!$referencia){
                    $referencia=enmascarar_cbu($cbu);
                }
	            self::$clima_cbu->set_referencia($referencia);            
	            self::$clima_cbu->set_id_clima(self::$clima->get_id_clima());
	            self::$clima_cbu->set_cuit($cuit_titular);
	            self::$clima_cbu->set_id_tipocuenta($id_tipocuenta);
	            self::$clima_cbu->set_titular($nombre_titular);
	            self::$clima_cbu->set_cbu(self::$clima_cbu->encriptar($cbu, $maskaron));
	            self::$clima_cbu->set_id_banco($id_banco);
	            self::$clima_cbu->set_sucursal(self::$clima_cbu->encriptar($sucursal, $maskaron));
                    self::$clima_cbu->set_id_sucursal("'".intval($sucursal)."'");
	            self::$clima_cbu->set_cuenta(self::$clima_cbu->encriptar($cuenta, $maskaron));
	            self::$clima_cbu->set_id_tipodoc(Tipodoc::CUIT_CUIL);
	            self::$clima_cbu->set_id_authstat(Authstat::ACTIVO);
	            if(!self::$clima_cbu->set()){
	               developer_log('Error al crear el clima_cbu.');
		       Model::FailTrans();
		    }
	        }
	        else{
	            # El CBU existe para otro Marchand o para el propio
                if(self::ACTIVAR_DEBUG) developer_log('El CBU existe para el responsable, para algun Cliente. No es necesaria el alta.');
	           self::$clima_cbu=new Clima_cbu($row);
	        }
        }
        if(!Model::hasFailedTrans()){
                if(!$this->crear_pertenencia_cbu($id_marchand, $id_clima, self::$clima_cbu)){
		       developer_log("Error al crear la pertenencia");
        		Model::FailTrans();
                }
        }

        if(Model::CompleteTrans() AND !Model::hasFailedTrans()){
            if(self::ACTIVAR_DEBUG) developer_log('El CBU ha sido procesado correctamente.');
            return $this;
        }if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error al procesar el CBU2.');
        return false;
	}
        
        
    # Crea el tco y lo asocia # Obtiene el tco y lo asocia # Obtiene el tco ya asociado
    public function crear_tco($id_marchand, $id_clima, $tco, $cvv, $mes_vencimiento, $anio_vencimiento, $nombre_titular, $cuit_titular, $token=false ){
        $tco=trim($tco);
        $mes_vencimiento=str_pad($mes_vencimiento, 2, '0',STR_PAD_LEFT);
        $cuit_titular=trim(str_replace('-', '', $cuit_titular));
        $nombre_titular=ucwords(strtolower(trim($nombre_titular)));
        if((self::VALIDAR_CUIT AND !validar_cuit($cuit_titular)) AND !self::PERMITIR_ALTA_CON_DNI) {
            throw new Exception('El CUIT/CUIL no es válido. ');
        }
        elseif(self::PERMITIR_ALTA_CON_DNI){
            if(self::VALIDAR_DNI AND !validar_dni($cuit_titular)){
                throw new Exception('El Documento no es válido. ');   
            }
        }
        if(self::VALIDAR_TCO AND !validar_tco($tco)){
            throw new Exception('La tarjeta de crédito no es válida. ');
        }
        $fecha=Datetime::createFromFormat('Ym',$anio_vencimiento.$mes_vencimiento);
        if(!$fecha){
            throw new Exception("La fecha de vencimiento de la tarjeta no es válida. ");
        }
        if($anio_vencimiento.$mes_vencimiento!=$fecha->format('Ym')){
            throw new Exception("El mes de vencimiento de la tarjeta no es válido(Verificar máximos). ");
        }
        $hoy=new DateTime('today');
        if($anio_vencimiento.$mes_vencimiento<=$hoy->format('Ym')){
            throw new Exception("La tarjeta de crédito está vencida. ");   
        }
        if(!is_numeric($cvv) OR ($cvv<1 OR $cvv>99999)){
            throw new Exception("El código de seguridad no es válido. ");   
        }
        
        Model::StartTrans();
	        
        $row=Clima_tco::existe_tco($id_clima,$tco, $cvv, $fecha);
        if($row===false)
            Model::FailTrans();

        if(!Model::HasFailedTrans()){
            if($this->optimizar_clima($id_clima)===false)
                Model::FailTrans();
        }
        if(!Model::hasFailedTrans())
        {
            $recordset=Climadocs::select(array('id_tipodoc'=>Tipodoc::CUIT_CUIL,'doctxt'=>$cuit_titular));
            if($recordset AND $recordset->RowCount()===0){
                $climadocs=new Climadocs();
                $climadocs->set_id_clima(self::$clima->get_id_clima());
                $climadocs->set_id_tipodoc(Tipodoc::CUIT_CUIL);
                $climadocs->set_id_scan(self::DUMMY_ID_SCAN);
                $climadocs->set_doctxt($cuit_titular);
                if (!$climadocs->set()) {
                    Gestor_de_log::set("Ha ocurrido un error al dar de alta el Documento del Responsable.", 0);
                    Model::FailTrans();
                }

            }
        }
        if(!Model::HasFailedTrans()){
            $maskaron=self::$clima->get_maskaron();
            if($row===0){
//            if(true){
                # El tco no existe para ningun Marchand
                if(self::ACTIVAR_DEBUG) developer_log('El TCO no existe para el responsable, para ningun Cliente. Dando de alta.');
                    
                # TEMP
                # REVISAR TODOS LOS CAMPOS DE ESTA TABLA
                self::$clima_tco=new Clima_tco();
                self::$clima_tco->set_id_authstat(Authstat::ACTIVO);
                
                self::$clima_tco->set_tco(Clima_cbu::encriptar($tco, $maskaron));
                self::$clima_tco->set_referencia('Tarjeta '.truncar_tco($tco));
                self::$clima_tco->set_pinecret(1);
                self::$clima_tco->set_titular($nombre_titular);
                self::$clima_tco->set_id_mp(220);
                self::$clima_tco->set_id_clima(self::$clima->get_id_clima());
                
                self::$clima_tco->set_fecha_vto($fecha->format('Y-m-d'));
                self::$clima_tco->set_ccv(Clima_cbu::encriptar($cvv, $maskaron));
                self::$clima_tco->set_tco1td2(1);
                self::$clima_tco->set_id_tipodoc(Tipodoc::CUIT_CUIL);
                self::$clima_tco->set_documento($cuit_titular);
                self::$clima_tco->set_fechagen('now');
                self::$clima_tco->set_stco('1');
                self::$clima_tco->set_cred1deb2('1');
                self::$clima_tco->set_id_tipocuenta('99');
		if(isset($token))
	                self::$clima_tco->set_token($token);
              
                # OBTENER CONFIGURACION PARA GUARDAR O NO GUARDAR EN DB EXT
                $conf_marchand = Configuracion::obtener_configuracion($id_marchand);
                
                # GUARDO DATOS DEL PAGO EN COBRODIG
                developer_log("Guardo datos en Cobro Digital");
                if(!self::$clima_tco->set())
                    Model::FailTrans();
                
                if($conf_marchand[10130]['pvp'][115]['value']){
                    # GUARDO DATOS EN BASE EXTERNA
                    $this->DB = NewADOConnection(DATABASE_ENGINE);
                    $url = $conf_marchand[10130]['pvp'][117]['value'];
                    $port = $conf_marchand[10130]['pvp'][118]['value'];
                    $username = $conf_marchand[10130]['pvp'][119]['value'];
                    $userpass = $conf_marchand[10130]['pvp'][120]['value'];
                    $database_name = $conf_marchand[10130]['pvp'][121]['value'];
                    if(self::ACTIVAR_DEBUG) developer_log("Guardado de datos en Base externa ". $url);
                    try {
                        $resultado = $this->DB->Connect($url.":".$port, $username, $userpass, $database_name);
                    } catch (Exception $ex) {
                        $resultado=false;
                        throw new Exception('Fallo al establecerse la conexion con la base de datos.'); 
                    }
                    if($resultado)
                        developer_log('Conexion establecida con la base de datos.');
                    if (!$resultado){
			Gestor_de_log::set("Error al guardar los datos");
			return false;
			}
                        
		    
                    $this->DB->SetCharSet('utf8');
                    $tco = (Clima_cbu::encriptar($tco, $maskaron));
                    $cvv = (Clima_cbu::encriptar($cvv, $maskaron));
                    $parametros['tco'] = $tco;
                    $parametros['titular'] = $nombre_titular;
                    $parametros['fecha_vto'] = $fecha->format('Y-m-d'); 
                    $parametros['cvv'] = $cvv;
                    $parametros['id_tipodoc'] = Tipodoc::CUIT_CUIL;
                    $parametros['documento'] = $cuit_titular;
                    $parametros['token'] = $token;

                    $fecha2 = $fecha->format('Y-m-d');
                    $result = $this->DB->Execute("insert into cd_clima_tco (tco, titular, fecha_vto, ccv, id_clima, id_tipodoc, documento, token) values('$tco', '$nombre_titular', '$fecha2', '$cvv', '$id_clima','1', '$cuit_titular', '$token')");
//                    return $result;
                }else{
                    # NO GUARDO DATOS
                    if(self::ACTIVAR_DEBUG) developer_log("Los datos no se guardaron en la base externa");
                }
            }else{
                # El CBU existe para otro Marchand o para el propio
                if(self::ACTIVAR_DEBUG) developer_log('El TCO existe para el responsable, para algun Cliente. No es necesaria el alta.');
               self::$clima_tco=new Clima_tco($row);
            }
        }
        if(!Model::hasFailedTrans()){
            if(!$this->crear_pertenencia_tco($id_marchand, $id_clima, self::$clima_tco)){
                Model::FailTrans();
            }
        }

        if(Model::CompleteTrans() AND !Model::hasFailedTrans()){
            if(self::ACTIVAR_DEBUG) developer_log('El TCO ha sido procesado correctamente.');
            return $this;
        }if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error al procesar el TCO.');
        return false;
    }
	# Esta funcion genera un registro 'innecesario' en la tabla cd_vinvulo que rompe la logica, 
	# pero sirve para asociar un Clima a un Marchand (y que no todos vean todos)
	# Crea una pertenencia SI HACE FALTA
	private function crear_pertenencia($id_marchand, Clima $clima)
	{
		# Primero verifica si existe la pertenencia, si existe retorna true.
		# Si no existe, intenta crearla y retorna true.
		# En caso de error retorna false;

		# El primer registro de Vinvulo es la pertenencia
		# Los registros siguientes son 1 a 1 con agenda_vinvulo
		# Si ya existia para el propio Cliente?
		$recordset=Vinvulo::select(array('id_clima'=>$clima->get_id(),'id_entidad'=>Entidad::ENTIDAD_MARCHAND, 'id_referencia'=>$id_marchand));
		if($recordset AND $recordset->RowCount()>0){
            if(self::ACTIVAR_DEBUG) developer_log('Ya existe la pertenencia para dicho responsable. No es necesario crear la pertenencia.');
			return true;
		}
        if(self::ACTIVAR_DEBUG) developer_log('No existe la pertenencia para dicho responsable. Dando de alta la pertenencia.');
	    $vinvulo = new Vinvulo();
        $vinvulo->set_id_clima(self::$clima->get_id_clima());
        $vinvulo->set_id_authstat(Authstat::ACTIVO);
        $vinvulo->set_id_entidad(Entidad::ENTIDAD_MARCHAND);
        $vinvulo->set_id_referencia($id_marchand);
        $vinvulo->set_id_climarchand(self::DUMMY_ID_CLIMARCHAND);
        #Ponemos este registro solo para emular la pertenencia al Marchand
        #en si, este campo no tiene sentido
        $pmc_19_burla="";
        for($i=0; $i<18;$i++)
            $pmc_19_burla.="".rand(0,9);
        $pmc_19_burla=$pmc_19_burla.Barcode::calcular_digito_verificador($pmc_19_burla);
        $vinvulo->set_pmc19($pmc_19_burla);
        
        if ($vinvulo->set())
        	return true;

        Gestor_de_log::set("Ha ocurrido un error al asociar al Responsable.", 0);
        return false;
	}
	# Esta funcion genera un registro 'innecesario' en la tabla cd_clima_assoc que rompe la logica, 
	# pero sirve para asociar un Cbu a un Marchand (y que no todos vean todos)
	# Crea una pertenencia SIEMPRE!
    private function crear_pertenencia_cbu($id_marchand, $id_clima, Clima_cbu $clima_cbu)
	{
        # Si no esta asociado el Responsable al Cliente, falla
		$recordset_vinvulo=Vinvulo::select(array('id_clima'=>$id_clima,'id_entidad'=>Entidad::ENTIDAD_MARCHAND, 'id_referencia'=>$id_marchand));
		if(!$recordset_vinvulo OR $recordset_vinvulo->RowCount()===0){
            if(self::ACTIVAR_DEBUG) developer_log('El CBU no existe para el responsable. Error.');
			return false;
        }
		else{
            $recordset=Clima_assoc::select_pertenencia_cbu($id_marchand, $clima_cbu->get_id());
            if(!$recordset) return false;
            if($recordset AND $recordset->RowCount()>0){
                if(self::ACTIVAR_DEBUG) developer_log('El CBU ya existe para el responsable. No es necesario crear la pertenecia.');
                return true;
            }
            if(self::ACTIVAR_DEBUG) developer_log('El CBU no existe para el responsable. Es necesario crear la pertenecia.');
			$vinvulo=new Vinvulo($recordset_vinvulo->FetchRow());
	        $clima_assoc=new Clima_assoc();
	        $clima_assoc->set_id_vinvulo($vinvulo->get_id());
	        $clima_assoc->set_id_clima_tco(self::DUMMY_ID_CLIMA_TCO);
	        $clima_assoc->set_id_clima_cbu($clima_cbu->get_id());
	        $clima_assoc->set_id_authstat(Authstat::ACTIVO);
	        $clima_assoc->set_id_envistat(self::DUMMY_ID_ENVISTAT);
	        $clima_assoc->set_fechup('now()');
	        if ($clima_assoc->set()){
	            return true;
	        }
        }
        Gestor_de_log::set('Ha ocurrido un error al dar de alta la pertenencia del CBU.',0);
        return false;
	}
    # Esta funcion genera un registro 'innecesario' en la tabla cd_clima_assoc que rompe la logica, 
    # pero sirve para asociar una tarjetan a un Marchand (y que no todos vean todos)
    # Crea una pertenencia SIEMPRE!
    private function crear_pertenencia_tco($id_marchand, $id_clima, Clima_tco $clima_tco)
    {
        # Si no esta asociado el Responsable al Cliente, falla
        $recordset_vinvulo=Vinvulo::select(array('id_clima'=>$id_clima,'id_entidad'=>Entidad::ENTIDAD_MARCHAND, 'id_referencia'=>$id_marchand));
        if(!$recordset_vinvulo OR $recordset_vinvulo->RowCount()===0){
            if(self::ACTIVAR_DEBUG) developer_log('El TCO no existe para el responsable. Error.');
            return false;
        }
        else{
            $recordset=Clima_assoc::select_pertenencia_tco($id_marchand, $clima_tco->get_id());
            if(!$recordset) return false;
            if($recordset AND $recordset->RowCount()>0){
                if(self::ACTIVAR_DEBUG) developer_log('El TCO ya existe para el responsable. No es necesario crear la pertenecia.');
                return true;
            }
            if(self::ACTIVAR_DEBUG) developer_log('El TCO no existe para el responsable. Es necesario crear la pertenecia.');
            $vinvulo=new Vinvulo($recordset_vinvulo->FetchRow());
            $clima_assoc=new Clima_assoc();
            $clima_assoc->set_id_vinvulo($vinvulo->get_id());
            $clima_assoc->set_id_clima_tco($clima_tco->get_id());
            $clima_assoc->set_id_clima_cbu(self::DUMMY_ID_CLIMA_CBU);
            $clima_assoc->set_id_authstat(Authstat::ACTIVO);
            $clima_assoc->set_id_envistat(self::DUMMY_ID_ENVISTAT);
            $clima_assoc->set_fechup('now()');
            if ($clima_assoc->set()){
                return true;
            }
        }
        Gestor_de_log::set('Ha ocurrido un error al dar de alta la pertenencia del TCO.',0);
        return false;
    }
	public function editar_cbu()
	{

		return false;
	}
    # Falta validar el nombre y el apellido
    private function validar_campos($nombre, $apellido, $documento, $id_tipodoc,$email)
    {
        $validez=true;
        $mensaje='';
        # Valida los documentos
        switch($id_tipodoc){
            case Tipodoc::CUIT_CUIL:
                if(self::VALIDAR_CUIT AND !validar_cuit($documento)){
                    $validez=false;
                    $mensaje.='El CUIT/CUIL no es válido. ';
                }
            case Tipodoc::DNI:
                if(self::PERMITIR_ALTA_CON_DNI){
                    if( self::VALIDAR_DNI AND !validar_dni($documento)) {
                        $validez=false;
                        $mensaje.='El DNI no es válido. ';
                    }
                }
                else{
                    $validez=false;
                    $mensaje.="No puede dar de alta un responsable utilzando su DNI, intente con un CUIT/CUIL. ";
                }
            case Tipodoc::CI:
            case Tipodoc::LC:
            case Tipodoc::LE:
                $validez = true;
                $mensaje = '';
        }
//        
        # Valida el correo
        if(($email!==false AND self::VALIDAR_CORREO)AND(!validar_correo($email))){
            $validez=false;
            $mensaje.='El correo electrónico no es válido.';
        }
       if(preg_match(self::VALIDACION_NOMBRE,quitar_acentos($nombre))!==1){
           $validez=false;
           $mensaje.='El nombre no es válido. ';   
       }
       if(preg_match(self::VALIDACION_APELLIDO,quitar_acentos($apellido))!==1){
           $validez=false;
           $mensaje.='El apellido no es válido. ';   
       }
        return array($validez,$mensaje);
    }
    private function preparar_campos($nombre,$apellido,$documento)
    {
        $nombre=ucwords(strtolower(trim($nombre))) ;
        $apellido=ucwords(strtolower(trim($apellido)));
        $documento=trim(str_replace('-', '', $documento));
        return array($nombre,$apellido,$documento);
    }
    private static function optimizar_clima($id_clima)
    {
        if(self::$clima===false OR self::$clima->get_id()!==$id_clima){
            $recordset=Clima::select(array('id_clima'=>$id_clima));
            if(!$recordset OR $recordset->RowCount()!=1){
                Gestor_de_log::set('Ha ocurrido un error al seleccionar el Responsable.',0);
                return false;
            }
            self::$clima=new Clima($recordset->FetchRow());
        }
        return self::$clima;
    }
    public function get_clima_cbu(){
        return self::$clima_cbu;
    }
}
