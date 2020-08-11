<?php

# ESTE MODULO NECESITA UN REFACTOR ENTRE TCO Y CBU

class Debitos_cbu extends Debito {

    const ACTIVAR_DEBUG = false;
    const DIAS_DE_ANTICIPACION_DEL_BANCO_GALICIA = 2;
    const HORAS_DE_ANTICIPACION_DEL_BANCO_GALICIA = 48;
    const HORA_EJECUCION_SCRIPT = 5; # En realidad corre a las 7:30 Am pero le ponemos una banda de seguridad
    const ACTIVAR_MONTOS=true;
    const MONTO_1="monto1";
    public static $clima_cbu = false; # OPTIMIZAR
    public static $clima = false; # OPTIMIZAR
    public static $xml = false; # OPTIMIZAR # Es el config marchand
    public $clima_assoc = false; # NO OPTIMIZAR
    public $debito_cbu = false; # El debito # NO OPTIMIZAR
    private $carrier = false;
    private $file;

    const CARRIER_TCDAUT = 'tcdaut';
    const CARRIER_PVP = 'pvp';
    const CARRIER_DAUT = 'galicia2';
    const DUMMY_ID_SC = '2040';
    const DUMMY_MODELO = 'debito_programado';
    const DUMMY_ONE1AUT2 = '1';
    const DUMMY_ID_CLIMARCHAND = '3';
    const DUMMY_ID_CLIMA_TCO = 25;
    const DUMMY_ID_CLIMA_CBU = 24;
    const DUMMY_ID_ENVISTAT = 2;
    public static $debitos_correctos = array();
    public function crear($id_marchand, $id_clima_cbu, $importe, $fecha, $concepto, $cantidad_cuotas = 1, $modalidad_cuotas = 'mensuales', $modo_estricto = true, $inmediato = false, $id_trans = false, $servicio = false, $tipo_pago = false, $file = "Sin file",$titular="Agregar",$cbu=false,$refe="") {
        if (self::ACTIVAR_DEBUG)
            developer_log('Creando un débito de ' . $cantidad_cuotas . ' cuota/s.');
        error_log("La modalidad cuotas es $modalidad_cuotas");
        error_log("La fecha es $fecha");
        error_log("concepto $concepto");
        error_log("cbu $cbu");
        error_log("titular $titular");
        switch ((trim(strtolower($modalidad_cuotas)))) {
            // case 'dia':
            // case 'diario':
            // case 'diaria':
            // case 'diarios':
            // case 'diarias': 
            //     $intervalo = new DateInterval('P1D'); break;
            case 'semana':
            case 'semanal':
            case 'semanales':
                $intervalo = new DateInterval('P7D');
                break;
            case 'quincena':
            case 'quincenal':
            case 'quincenales':
                $intervalo = new DateInterval('P14D');
                break;
            case 'mes':
            case 'mensual':
            case 'mensuales':
                $intervalo = new DateInterval('P1M');
                break;
            default:
                throw new Exception("La modalidad de las cuotas no es correcta. ");
                break;
        }
        $this->file = $file;
        # Falta verificar los dias habiles
        if (!($fecha_a_pagar = DateTime::createFromFormat('!d/m/Y', $fecha))) {
            throw new Exception('La fecha no puede ser procesada debido a su formato. ');
        }
        if ($importe <= 0) {
            throw new Exception("El importe no es correcto.");
        }
        Model::StartTrans();
        if (!$this->optimizar_clima_cbu($id_clima_cbu)) {
            Model::FailTrans();
        }
        if (!Model::hasFailedTrans()) {
            $id_clima = self::$clima_cbu->get_id_clima();
            if ($modo_estricto AND ! $this->comprobar_pertenencias_cbu($id_marchand, self::$clima_cbu)) {
                Model::FailTrans();
            }
        }
        $referencia = false;
        if (!Model::hasFailedTrans()) {
            if (!$this->optimizar_clima($id_clima)) {
                Model::FailTrans(); # Innecesario, pero esta bueno tenerlo a mano
            }
        }
        for ($i = 1; $i <= $cantidad_cuotas; $i++) {
            if (!Model::hasFailedTrans()) {
//                $boleta = new Boleta_responsable();
//                $boleta::$clima = self::$clima;
                $id_sc = self::DUMMY_ID_SC;
                $modelo = self::DUMMY_MODELO;
//                $f_hoy = new Datetime("now");
                if ($id_clima_cbu) {
                    $fecha_a_pagar_habil = $this->obtener_fecha_a_pagar_habil(clone $fecha_a_pagar);
                    $fecha_a_enviar_habil = $this->obtener_fecha_a_enviar_habil(clone $fecha_a_pagar_habil);
                    if (self::ACTIVAR_DEBUG)
                        developer_log('Fecha de informe al medio de pago: ' . $fecha_a_enviar_habil->format('d/m/Y H:i:s'));
                    $fecha_desde = new DateTime("now");
                    $fecha_hasta = clone $fecha_a_pagar_habil;
                    $diff = new DateInterval("P1D");
                    $fecha_hasta->setTime(6, 0, 0);
                    $fecha_pivot = clone $fecha_desde;
                    $horas_totales_habiles = 0;
                    $fecha_orden = clone $fecha_pivot;
                    $fecha_orden->setTime(6, 0, 0);
                    $primero = true;
                    $sin_comprobar = false;
                    if ($fecha_desde->diff($fecha_hasta)->days < 7) {
                        do {
                            $fecha_orden->add(new DateInterval("P1D"));
                            if ($primero) {
                                $diff = $fecha_pivot->diff($fecha_orden);
                                $fecha_orden->setTime($fecha_pivot->format("h"), $fecha_pivot->format("i"), $fecha_pivot->format("s"));
                                $diferencia = ($diff->y * 365 * 24 ) + ($diff->m * 30 * 24 ) + ($diff->d * 24) + ($diff->h) + ($diff->i / 60);
                                $primero = false;
                                unset($diff);
                            } else
                                $diferencia = 0;

                            if ($fecha_orden->format("Y-m-d") > $fecha_hasta->format("Y-m-d")) {
                                break;
                            }
                            $diff = $fecha_pivot->diff($fecha_orden);
                            if (Calendar::es_dia_habil($fecha_pivot))
                                $horas_totales_habiles += ($diff->y * 365 * 24 ) + ($diff->m * 30 * 24 ) + ($diff->d * 24) + ($diff->h) + ($diff->i / 60) + $diferencia;
                            error_log($horas_totales_habiles);
                            $fecha_pivot->add(new DateInterval("P1D"));
                            $fecha_pivot->setTime(0, 0, 0);
                            $fecha_orden->setTime(0, 0, 0);
                        } while ($fecha_pivot->format("Y-m-d") <= $fecha_hasta->format("Y-m-d"));
                    } else
                        $sin_comprobar = true;

                    if ($sin_comprobar OR $horas_totales_habiles >= self::HORAS_DE_ANTICIPACION_DEL_BANCO_GALICIA)
////        			 throw new Exception("se puede agendar el debito");
                        developer_log("El debito cumple con las horas de anticipacion");
                    else {
//                        Gestor_de_correo::enviar(Gestor_de_correo::MAIL_DESARROLLO, "adupuy@cobrodigital.com", "Error de fechas detectado en Lote de debitos", "El idm" . Application::$usuario->get_id_marchand() . " el debito no cumple con las hs definidas, el resultado es $horas_totales_habiles");
                        error_log($horas_totales_habiles);
                        throw new Exception("No cumple con las " . self::HORAS_DE_ANTICIPACION_DEL_BANCO_GALICIA . "hs de anticipacion para enviar al banco.");
                    }
                }
                if (self::ACTIVAR_DEBUG)
                    developer_log('Fecha de debito : ' . $fecha_a_pagar_habil->format('d/m/Y H:i:s'));


                $fechas_vencimiento = array($fecha_a_pagar_habil->format('d/m/Y'));
 		if($this->verificar_preexistencia($fecha_a_pagar_habil,$importe,$concepto,$id_clima,$id_clima_cbu,$id_marchand)){
           	 	Gestor_de_log::set('El debito ya se encuentra cargado.', 0);
            		Model::failTrans();
            		throw new Exception('El debito ya se encuentra cargado.');
            		return false;
        	}
		else{
		 if(self::ACTIVAR_DEBUG)
		 	developer_log("El debito no existe con anterioridad dando de alta");
		}
//                var_dump($refe);
//                var_dump($titular);
                if (!$this->crear_una_cuota($id_marchand, $id_clima_cbu, $fecha_a_enviar_habil, $fecha_a_pagar_habil, $concepto, $importe,$titular,$cbu,$refe))
                    Model::FailTrans();
                $fecha_a_pagar->add($intervalo);
            }
        }
	/*if($this->verificar_preexistencia($fecha_a_pagar_habil,$importe,$concepto,$id_clima,$id_clima_cbu,$id_marchand)){
            Gestor_de_log::set('El debito ya se encuentra cargado.', 0);
	    Model::failTrans();
	    throw new Exception('El debito ya se encuentra cargado.');
            return false;
        }*/
        if (Model::CompleteTrans() AND ! Model::hasFailedTrans()) {
            Gestor_de_log::set('Ha creado un nuevo débito programado.', 1);
            return $this;
        }

        Gestor_de_log::set('Ha ocurrido un error al crear un nuevo débito programado.', 0);
        return false;
    }
    public function verificar_preexistencia($fecha_a_pagar_habil,$importe,$concepto,$id_clima,$id_clima_cbu,$id_marchand){
        $fecha=$fecha_a_pagar_habil->format("Y-m-d");
        $flag=false;
        foreach (self::$debitos_correctos as $correctos){
//            var_dump($correctos==array("fecha_pago1"=>$fecha,"monto1"=>$importe,"concepto"=>$concepto,"id_clima"=>$id_clima,"id_clima_cbu"=>$id_clima_cbu,"id_marchand"=>$id_marchand));
            if($correctos==array("fecha_pago1"=>$fecha,"monto1"=>$importe,"concepto"=>$concepto,"id_clima"=>$id_clima,"id_clima_cbu"=>$id_clima_cbu,"id_marchand"=>$id_marchand))
                    $flag=true;
        }
        if($flag){
//            var_dump("$id_clima Duplicado");
            return true;
        }
        $rs_debito= Debito_cbu::select(array("fecha_pago1"=>$fecha,"monto1"=>$importe,"concepto"=>$concepto,"id_clima"=>$id_clima,"id_clima_cbu"=>$id_clima_cbu,"id_marchand"=>$id_marchand));
        developer_log(">>>>>>>>>>>>>>>>".$rs_debito->rowCount());
        if($rs_debito->rowCount()>0){
           developer_log("El debito esta cargado ".$rs_debito->rowCount()." veces. / $fecha , $importe,$concepto,$id_clima,$id_clima_cbu,$id_marchand"); 
            return true;
        }else{
            self::$debitos_correctos[] = array("fecha_pago1"=>$fecha,"monto1"=>$importe,"concepto"=>$concepto,"id_clima"=>$id_clima,"id_clima_cbu"=>$id_clima_cbu,"id_marchand"=>$id_marchand);

        }
        
        return false;
    }
    public function obtener_importe_minimo($importe,$id_marchand) {
        developer_log("Obteniendo porcentaje de importe minimo.");
        $porcentaje_monto_minimo = Configuracion::obtener_configuracion_de_tag($id_marchand, Entidad::ENTIDAD_MP, Mp::DEBITO_AUTOMATICO, Configuracion::CONFIG_IMPORTE_MINIMO);
        developer_log("porcentaje de importe minimo. $porcentaje_monto_minimo");
        if ($porcentaje_monto_minimo != false) {
            $importe_minimo = ($importe / 100) * $porcentaje_monto_minimo;
            return $importe_minimo;
        }
        return false;
    }

    private function obtener_fechas($fecha_1, $idm) {

        $fecha_1 = DateTime::createFromFormat("Y-m-d", $fecha_1);
        $fechas = array("fecha_1" => $fecha_1);
        developer_log("Obteniendo cantidad de dias.");
        $dias = Configuracion::obtener_configuracion_de_tag($idm, Entidad::ENTIDAD_MP, Mp::DEBITO_AUTOMATICO, Configuracion::CONFIG_DIAS);
        developer_log("Cantidad de dias $dias.");
        developer_log("Obteniendo vencimientos.");
        $vencimientos = Configuracion::obtener_configuracion_de_tag($idm, Entidad::ENTIDAD_MP, Mp::DEBITO_AUTOMATICO, Configuracion::CONFIG_VENCIMIENTOS);
        developer_log("Cantidad de vencimientos $vencimientos.");
        if ($vencimientos >= 2 AND $vencimientos < 4) {
            $fecha_2 = $this->proximo_dia_habil($dias, $fecha_1);
            $fechas["fecha_2"] = $fecha_2;
            if ($vencimientos == 3) {
                $fecha_3 = $this->proximo_dia_habil($dias, $fecha_2);
                $fechas["fecha_3"] = $fecha_3;
            }
        }
        return $fechas;
    }

    private function obtener_montos($monto, $idm) {
        $montos = array("monto_1" => $monto);
        if (self::ACTIVAR_MONTOS) {
            developer_log("Obteniendo vencimientos.");
            $vencimientos = Configuracion::obtener_configuracion_de_tag($idm, Entidad::ENTIDAD_MP, Mp::DEBITO_AUTOMATICO, Configuracion::CONFIG_VENCIMIENTOS);
            developer_log("Cantidad de vencimientos $vencimientos.");
            developer_log("Obteniendo Porcentaje de aumento.");
            $porcentajevencimientos = Configuracion::obtener_configuracion_de_tag($idm, Entidad::ENTIDAD_MP, Mp::DEBITO_AUTOMATICO, Configuracion::CONFIG_PORCENTAJE);
            developer_log("Porcentaje de aumento $porcentajevencimientos.");
            if ($vencimientos >= 2 AND $vencimientos < 4) {
                if (self::ACTIVAR_DEBUG)
                    developer_log("tiene mas de 1 vencimiento", 0);
                $monto_2 = (($porcentajevencimientos / 100) * $monto) + $monto;
                $montos["monto_2"] = $monto_2;
                if ($vencimientos == 3) {
                    if (self::ACTIVAR_DEBUG)
                        developer_log("tiene 3 vencimientos", 0);
                    $monto_3 = (($porcentajevencimientos / 100) * $monto_2) + $monto_2;
                    $montos["monto_3"] = $monto_3;
                }
            }
        }
        return $montos;
    }

    public function comprobar_pertenencias_cbu($id_marchand, Clima_cbu $clima_cbu) {
        $existe_pertenencia_de_cbu = false;
        $existe_pertenencia = false;

        $id_clima = self::$clima_cbu->get_id_clima();
        $recordset = Clima_assoc::select_pertenencia_cbu($id_marchand, $clima_cbu->get_id());
        if (!$recordset)
            $existe_pertenencia_de_cbu = false;
        if ($recordset AND $recordset->RowCount() > 0) {
            $existe_pertenencia_de_cbu = true;
            $recordset = Vinvulo::select(array('id_clima' => $id_clima, 'id_entidad' => Entidad::ENTIDAD_MARCHAND, 'id_referencia' => $id_marchand));
            if ($recordset AND $recordset->RowCount() > 0)
                $existe_pertenencia = true;
        }

        return $existe_pertenencia_de_cbu AND $existe_pertenencia;
    }

    private function crear_una_cuota($id_marchand, $id_clima_cbu, Datetime $fecha_a_enviar_habil, Datetime $fecha_a_pagar_habil, $concepto = "", $importe,$titular,$cbu,$refe) {
        if (self::ACTIVAR_DEBUG)
            developer_log('Creando una cuota para un débito.');
        if (!$this->optimizar_clima_cbu($id_clima_cbu))
            return false;
        if (self::$clima_cbu->get_id_authstat() == Clima_cbu::CBU_ANULADO) {
            throw new Exception("El CBU id tiene orden de no debitar. ");
        }
        $id_clima_cbu = self::$clima_cbu->get_id_clima_cbu();
        $id_clima = self::$clima_cbu->get_id_clima();
        $fecha_ejecucion = new DateTime('tomorrow');
        $fecha_ejecucion->add(new DateInterval('PT' . self::HORA_EJECUCION_SCRIPT . 'H'));
        if (self::ACTIVAR_DEBUG)
            developer_log('Fecha de proxima ejecucion: ' . $fecha_ejecucion->format('d/m/Y H:i:s'));

        if (intval($fecha_a_enviar_habil->format('Ymd')) < intval($fecha_ejecucion->format('Ymd'))) {
            throw new Exception('No hay tiempo suficiente para enviar el débito al medio de pago: Retrase la fecha del débito. ');
        }
        list($carrier, $id_carrier) = $this->obtener_carrier($id_marchand);
        if ($carrier === false OR $id_carrier === false) {
            if (self::ACTIVAR_DEBUG)
                developer_log("No es posible obtener el carrier, la estructura configmarchand no es correcta. ");
            if (true) {
                # BORRAR ESTE IF Y RETORNAR FALSE
                $carrier = self::CARRIER_DAUT; # TEMP
                $id_carrier = 51; # TEMP
            } else {
                return false;
            }
        }
        if (!$this->optimizar_clima($id_clima))
            return false;# Innecesario, pero esta bueno tenerlo a mano
        $importe_minimo = $this->obtener_importe_minimo($importe,$id_marchand);
        $fechas = $this->obtener_fechas($fecha_a_pagar_habil->format("Y-m-d"), $id_marchand);
        $montos = $this->obtener_montos($importe, $id_marchand);
        # Inserto un debito_cbu
        $this->debito_cbu = new Debito_cbu();
        $this->debito_cbu->set_titular($titular);
        $this->debito_cbu->set_cuit(self::$clima->get_documento());
        $this->debito_cbu->set_concepto($concepto);
        $this->debito_cbu->set_fechagen("now()");
        $this->debito_cbu->set_fecha_enviar($fecha_a_enviar_habil->format('Y-m-d'));

        $this->debito_cbu->set_id_authf1(Authstat::DEBITO_ACTIVO);
        $this->debito_cbu->set_id_authf2(Authstat::DEBITO_ACTIVO);
        $this->debito_cbu->set_id_authf3(Authstat::DEBITO_ACTIVO);
        $this->debito_cbu->set_fecha_pago1($fechas["fecha_1"]);
        $this->debito_cbu->set_monto1($montos["monto_1"]);
        if(isset($fechas["fecha_2"]) and isset($montos["monto_2"])){
            $this->debito_cbu->set_id_authf2(Authstat::DEBITO_ADICIONAL_VENCIMIENTO_2);
            $this->debito_cbu->set_fecha_pago2($fechas["fecha_2"]);
            $this->debito_cbu->set_monto2($montos["monto_2"]);
        }
        else 
            $this->debito_cbu->set_id_authf2(Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO);
        if(isset($fechas["fecha_3"]) and isset($montos["monto_3"])){
            $this->debito_cbu->set_id_authf3(Authstat::DEBITO_ADICIONAL_VENCIMIENTO_3);
            $this->debito_cbu->set_fecha_pago3($fechas["fecha_3"]);
            $this->debito_cbu->set_monto3($montos["monto_3"]);
        }
        else 
            $this->debito_cbu->set_id_authf3(Authstat::DEBITO_ADICIONAL_VENCIMIENTO_INACTIVO);
        if(isset($importe_minimo))
            $this->debito_cbu->set_monto_minimo($importe_minimo);
        else
            $this->debito_cbu->set_monto_minimo(0);
        $this->debito_cbu->set_id_clima(self::$clima->get_id());
        $this->debito_cbu->set_id_clima_cbu(self::$clima_cbu->get_id());
        $this->debito_cbu->set_id_marchand($id_marchand);
        list($carrier, $id_carrier) = $this->obtener_carrier($id_marchand);
        $this->debito_cbu->set_carrier($carrier);
        $this->debito_cbu->set_id_carrier($id_carrier);
        $this->debito_cbu->set_file($this->file);
        $this->debito_cbu->set_ingresa_cbu($cbu);
        $this->debito_cbu->set_email(self::$clima->get_email());
        
        $this->debito_cbu->set_referencia_externa($refe);
//        $this->debito_cbu->
        //var_dump(Model::HasFailedTrans());
        if (!$this->debito_cbu->set()) {
            if (self::ACTIVAR_DEBUG)
                developer_log('Ha ocurrido un error al insertar un registro en la tabla debito_cbu.');
            return false;
        }

        return true;
    }

    protected function obtener_carrier($id_marchand) {
        if (!$this->optimizar_xml($id_marchand))
            return array(false, false);
        $view = new DOMDocument();
        if (!($view->loadXML(self::$xml->get_xmlfield())))
            return array(false, false);
        $elementos = $view->getElementsByTagName('debito_automatico');
        if (!$elementos OR $elementos->length != 1)
            return array(false, false);
        $elemento = $elementos->item(0);
        if ($elemento->hasAttribute('habilitado') AND $elemento->getAttribute('habilitado') == 1) {
            if ($elemento->hasAttribute('carrier')) {
                $carrier = trim(strtolower($elemento->getAttribute('carrier')));
                $id_carrier = Peucd::obtener_id_carrier($carrier);
                return array($carrier, $id_carrier);
            }
        }
        return array(false, false);
    }

    public function obtener_fecha_a_pagar_habil(Datetime $fecha_a_pagar) {
        if (Calendar::es_dia_habil($fecha_a_pagar))
            return $fecha_a_pagar;
        else {
            $proximo_dia_habil = Calendar::proximo_dia_habil($fecha_a_pagar);
            error_log(json_encode($proximo_dia_habil));
//            Gestor_de_correo::enviar(Gestor_de_correo::MAIL_DESARROLLO, "adupuy@cobrodigital.com", "error en procesamiento de debitos", json_encode(array("fecha_no_habil" => $fecha_a_pagar->format("d/m/Y"), "fecha_habil_siguiente" => $proximo_dia_habil->format("d/m/Y"))));
        }
        return Calendar::proximo_dia_habil($fecha_a_pagar);
    }

    public function obtener_fecha_a_enviar_habil(Datetime $fecha_a_pagar_habil) {

        return Calendar::anterior_dia_habil($fecha_a_pagar_habil, self::DIAS_DE_ANTICIPACION_DEL_BANCO_GALICIA);
    }

    public static function cambiar_estado($id_agenda_vinvulo, $id_marchand) {
        Model::StartTrans();
        $error = true;
        $mensaje = 'Ha ocurrido un error';
        $Debitos_cbu = new Debito_cbu();
        $Debitos_cbu->get($id_agenda_vinvulo);
        if ($Debitos_cbu->get_carrier() == self::CARRIER_DAUT) {
            $fecha_actual = new Datetime('today');
            $fecha_a_enviar = Datetime::createFromFormat("Y-m-d", $Debitos_cbu->get_fecha_enviar());
            if ($fecha_actual->format('Ymd') >= $fecha_a_enviar->format('Ymd')) {
                developer_log('La fecha de envío es posterior o igual a la fecha actual.');
                Model::FailTrans();
            }
        }
        if (!Model::hasFailedTrans()) {
            if ($Debitos_cbu->get_id_authf1() == Authstat::ACTIVO) {
                $d=new Debito_cbu();
                $d->set_id($Debitos_cbu->get_id());
                $d->set_id_authf1(Authstat::INACTIVO);
                $d->set_id_authf2(Authstat::INACTIVO);
                $d->set_id_authf3(Authstat::INACTIVO);
                if (!$d->set())
                    Model::FailTrans();
                else {
                    $error = false;
                    $mensaje = 'Ha desactivado el Débito automático. ';
                    if (self::ACTIVAR_DEBUG)
                        developer_log($mensaje);
                }
            }
            elseif ($Debitos_cbu->get_id_authf1() == Authstat::INACTIVO) {
                $d=new Debito_cbu();
                $d->set_id($Debitos_cbu->get_id());
                $d->set_id_authf1(Authstat::ACTIVO);
                $d->set_id_authf2(Authstat::ACTIVO);
                $d->set_id_authf3(Authstat::ACTIVO);
//                $Debitos_cbu->set_id_authstat(Authstat::ACTIVO);
                if (!$d->set())
                    Model::FailTrans();
                else {
                    $error = false;
                    $mensaje = 'Ha desactivado el Débito automático. ';
                    if (self::ACTIVAR_DEBUG)
                        developer_log($mensaje);
                }
            }
        }

        if ((Model::CompleteTrans() AND ! $error)AND ! Model::hasFailedTrans()) {
            return true;
        }
        return false;
    }

    public static function optimizar_clima_cbu($id_clima_cbu) {
        if (self::$clima_cbu === false OR self::$clima_cbu->get_id_clima_cbu() !== $id_clima_cbu) {
            $recordset = Clima_cbu::select(array('id_clima_cbu' => $id_clima_cbu));
            if (!$recordset OR $recordset->RowCount() != 1) {
                Gestor_de_log::set('Ha ocurrido un error al seleccionar el CBU.', 0);
                return false;
            }
            self::$clima_cbu = new Clima_cbu($recordset->FetchRow());
        }
        return self::$clima_cbu;
    }

    protected static function optimizar_clima($id_clima) {
        if (self::$clima === false OR self::$clima->get_id_clima() !== $id_clima) {
            $recordset = Clima::select(array('id_clima' => $id_clima));
            if (!$recordset OR $recordset->RowCount() != 1) {
                Gestor_de_log::set('Ha ocurrido un error al seleccionar el Responsable.', 0);
                return false;
            }
            self::$clima = new Clima($recordset->FetchRow());
        }
        return self::$clima;
    }

    public static function optimizar_xml($id_marchand) {
        if (self::$xml === false OR self::$xml->get_id_marchand() !== $id_marchand) {
            $recordset = Xml::select(array('id_marchand' => $id_marchand, 'id_entidad' => Entidad::ESTRUCTURA_CONFIG_MARCHAND));
            if (!$recordset OR $recordset->RowCount() != 1) {
                Gestor_de_log::set('Ha ocurrido un error al seleccionar el Config Marchand.', 0);
                return false;
            }
            self::$xml = new Xml($recordset->FetchRow());
        }
        return self::$xml;
    }

    public function set_carrier($carrier) {
        $this->carrier = $carrier;
    }

    public function proximo_dia_habil($dias, DateTime $fecha_datetime) {
        $fecha_datetime_2 = clone $fecha_datetime;
        $fecha_datetime_2 = $this->obtener_fechas_habiles($fecha_datetime, $fecha_datetime_2, $dias);
        return $fecha_datetime_2;
    }

    public function obtener_fechas_habiles(DateTime $fecha1, DateTime $fecha_objetivo, $dias, $dias_habiles = 0) {
        if ($dias == $dias_habiles) {
            if (Calendar::es_dia_habil($fecha_objetivo))
                return $fecha_objetivo;
        }
//    var_dump($dias);
//    var_dump($dias_habiles);
        if (Calendar::es_dia_habil($fecha_objetivo)) {
            $dias_habiles += 1;
        }
        $fecha_objetivo->add(new DateInterval("P1D"));
        return $this->obtener_fechas_habiles($fecha1, $fecha_objetivo, $dias, $dias_habiles);
    }

    protected  function comprobar_pertenencias_tco($id_marchand, Clima_tco $clima_tco) {
        return FALSE;
        //no se utiliza tco en cbu
    }

    protected static function optimizar_clima_tco($id_clima_tco) {
        return FALSE;
        //no se utiliza tco en cbu
    }

}
