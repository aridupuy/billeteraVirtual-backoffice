<?php

# ESTE MODULO NECESITA UN REFACTOR ENTRE TCO Y CBU

abstract class Debito {

    const ACTIVAR_DEBUG = true;
    const DIAS_DE_ANTICIPACION_DEL_BANCO_GALICIA = 2;
    const HORAS_DE_ANTICIPACION_DEL_BANCO_GALICIA = 48;
    const HORA_EJECUCION_SCRIPT = 5; # En realidad corre a las 7:30 Am pero le ponemos una banda de seguridad

    public static $clima_cbu = false; # OPTIMIZAR
    public static $clima_tco = false; # OPTIMIZAR
    public static $clima = false; # OPTIMIZAR
    public static $xml = false; # OPTIMIZAR # Es el config marchand
    public $clima_assoc = false; # NO OPTIMIZAR
    public $vinvulo = false; # NO OPTIMIZAR
    public $agenda_vinvulo = false; # El debito # NO OPTIMIZAR
    public $boleta = false; # Trait
    private $carrier = false;

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

    abstract public function crear($id_marchand, $id_clima_cbu, $id_clima_tco, $importe, $fecha, $concepto, $cantidad_cuotas = 1, $modalidad_cuotas = 'mensuales', $modo_estricto = true, $inmediato = false, $id_trans = false, $servicio = false, $tipo_pago = false);
//    {
//        if (self::ACTIVAR_DEBUG)
//            developer_log('Creando un débito de ' . $cantidad_cuotas . ' cuota/s.');
//        error_log("La modalidad cuotas es $modalidad_cuotas");
//        switch ((trim(strtolower($modalidad_cuotas)))) {
//            // case 'dia':
//            // case 'diario':
//            // case 'diaria':
//            // case 'diarios':
//            // case 'diarias': 
//            //     $intervalo = new DateInterval('P1D'); break;
//            case 'semana':
//            case 'semanal':
//            case 'semanales':
//                $intervalo = new DateInterval('P7D');
//                break;
//            case 'quincena':
//            case 'quincenal':
//            case 'quincenales':
//                $intervalo = new DateInterval('P14D');
//                break;
//            case 'mes':
//            case 'mensual':
//            case 'mensuales':
//                $intervalo = new DateInterval('P1M');
//                break;
//            default:
//                throw new Exception("La modalidad de las cuotas no es correcta. ");
//                break;
//        }
//        # Falta verificar los dias habiles
//        if (!($fecha_a_pagar = DateTime::createFromFormat('!d/m/Y', $fecha))) {
//            throw new Exception('La fecha no puede ser procesada debido a su formato. ');
//        }
//        if ($importe <= 0) {
//            throw new Exception("El importe no es correcto.");
//        }
//        Model::StartTrans();
//        if ($id_clima_cbu AND ! $id_clima_tco) {
//            if (!$this->optimizar_clima_cbu($id_clima_cbu)) {
//                Model::FailTrans();
//            }
//            if (!Model::hasFailedTrans()) {
//                $id_clima = self::$clima_cbu->get_id_clima();
//                if ($modo_estricto AND ! $this->comprobar_pertenencias_cbu($id_marchand, self::$clima_cbu)) {
//                    Model::FailTrans();
//                }
//            }
//            $referencia = false;
//        } elseif ($id_clima_tco AND ! $id_clima_cbu) {
//            if (!$this->optimizar_clima_tco($id_clima_tco)) {
//                Model::FailTrans();
//            }
//
//            if (!Model::hasFailedTrans()) {
//                $id_clima = self::$clima_tco->get_id_clima();
//                if ($modo_estricto AND ! $this->comprobar_pertenencias_tco($id_marchand, self::$clima_tco)) {
//                    Model::FailTrans();
//                }
//                $referencia = self::$clima_tco->get_referencia();
//            }
//        } else {
//            if (self::ACTIVAR_DEBUG)
//                developer_log('Llamado incorrecto');
//            Model::FailTrans();
//        }
//        if (!Model::hasFailedTrans()) {
//            if (!$this->optimizar_clima($id_clima)) {
//                Model::FailTrans(); # Innecesario, pero esta bueno tenerlo a mano
//            }
//        }
//        for ($i = 1; $i <= $cantidad_cuotas; $i++) {
//            if (!Model::hasFailedTrans()) {
//                $boleta = new Boleta_responsable();
//                $boleta::$clima = self::$clima;
//                $id_sc = self::DUMMY_ID_SC;
//                $modelo = self::DUMMY_MODELO;
////                $f_hoy = new Datetime("now");
//                if ($id_clima_cbu) {
//                    $fecha_a_pagar_habil = $this->obtener_fecha_a_pagar_habil(clone $fecha_a_pagar);
//                    $fecha_a_enviar_habil = $this->obtener_fecha_a_enviar_habil(clone $fecha_a_pagar_habil);
//                    if (self::ACTIVAR_DEBUG)
//                        developer_log('Fecha de informe al medio de pago: ' . $fecha_a_enviar_habil->format('d/m/Y H:i:s'));
//                    $fecha_desde = new DateTime("now");
//                    $fecha_hasta = clone $fecha_a_pagar_habil;
//                    $diff = new DateInterval("P1D");
//                    $fecha_hasta->setTime(6, 0, 0);
//                    $fecha_pivot = clone $fecha_desde;
//                    $horas_totales_habiles = 0;
//                    $fecha_orden = clone $fecha_pivot;
//                    $fecha_orden->setTime(6, 0, 0);
//                    $primero = true;
//		    $sin_comprobar=false;
//                    if ($fecha_desde->diff($fecha_hasta)->days < 7) {
//                        do {
//                            $fecha_orden->add(new DateInterval("P1D"));
//                            if ($primero) {
//                                $diff = $fecha_pivot->diff($fecha_orden);
//                                $fecha_orden->setTime($fecha_pivot->format("h"), $fecha_pivot->format("i"), $fecha_pivot->format("s"));
//                                $diferencia = ($diff->y * 365 * 24 ) + ($diff->m * 30 * 24 ) + ($diff->d * 24) + ($diff->h) + ($diff->i / 60);
//                                $primero = false;
//                                unset($diff);
//                            } else
//                                $diferencia = 0;
//			
//                            if ($fecha_orden->format("Y-m-d") > $fecha_hasta->format("Y-m-d")) {
//                                break;
//                            }
//                            $diff = $fecha_pivot->diff($fecha_orden);
//                            if (Calendar::es_dia_habil($fecha_pivot))
//                                $horas_totales_habiles += ($diff->y * 365 * 24 ) + ($diff->m * 30 * 24 ) + ($diff->d * 24) + ($diff->h) + ($diff->i / 60) + $diferencia;
//	                    error_log($horas_totales_habiles);        
//			    $fecha_pivot->add(new DateInterval("P1D"));
//                            $fecha_pivot->setTime(0, 0, 0);
//                            $fecha_orden->setTime(0, 0, 0);
//                      } while ($fecha_pivot->format("Y-m-d") <= $fecha_hasta->format("Y-m-d"));
//                    }
//		    else $sin_comprobar=true;
//
//                    if($sin_comprobar OR $horas_totales_habiles>=self::HORAS_DE_ANTICIPACION_DEL_BANCO_GALICIA)
//////        			 throw new Exception("se puede agendar el debito");
//				developer_log("El debito cumple con las horas de anticipacion");
//			else{
//				Gestor_de_correo::enviar(Gestor_de_correo::MAIL_DESARROLLO,"adupuy@cobrodigital.com","Error de fechas detectado en Lote de debitos","El idm".Application::$usuario->get_id_marchand()." el debito no cumple con las hs definidas, el resultado es $horas_totales_habiles");
//				error_log($horas_totales_habiles);
//           			throw new Exception("No cumple con las ".self::HORAS_DE_ANTICIPACION_DEL_BANCO_GALICIA."hs de anticipacion para enviar al banco.");
//                	}
//                }
//                if ($fecha_a_pagar_habil and $fecha !== $fecha_a_pagar_habil->format("d/m/Y")) {
//                    Gestor_de_correo::enviar(Gestor_de_correo::MAIL_DESARROLLO, "adupuy@cobrodigital.com", "Error de fechas detectado en Lote de debitos", "Error" . json_encode(array("id_marchand" => $id_marchand, "id_clima_cbu" => $id_clima_cbu, "id_clima_tco" => $id_clima_tco, "importe" => $importe, "fecha_ingresada" => $fecha, "fecha_habil_calculada" => $fecha_a_pagar_habil->format("d/m/Y"), "cuotas" => $cantidad_cuotas, "modalidad" => $modalidad_cuotas, "concepto" => $concepto)));
//                } else {
//                    $fecha_a_pagar_habil = clone $fecha_a_pagar;
//                    $fecha_a_enviar_habil = clone $fecha_a_pagar;
//                }
//                if (self::ACTIVAR_DEBUG)
//                    developer_log('Fecha de debito : ' . $fecha_a_pagar_habil->format('d/m/Y H:i:s'));
//
//
//                $fechas_vencimiento = array($fecha_a_pagar_habil->format('d/m/Y'));
//
//                if (!$boleta->crear($id_clima, $id_marchand, $id_sc, $modelo, $fechas_vencimiento, array($importe), $concepto, $referencia, $id_trans, $servicio, $tipo_pago)) {
//                    if (self::ACTIVAR_DEBUG)
//                        developer_log('Ha ocurrido un error al crear la boleta.');
//                    Model::FailTrans();
//                }
//                if (!Model::hasFailedTrans()) {
//                    $barcode = $boleta->barcode_1;
//                    $this->boleta = $boleta;
//                    if ($inmediato) {
//                        Model::CompleteTrans();
//                        return $this;
//                    }
//                    if (!$this->crear_una_cuota($id_marchand, $id_clima_cbu, $id_clima_tco, $barcode, $fecha_a_enviar_habil, $fecha_a_pagar_habil))
//                        Model::FailTrans();
//                    $fecha_a_pagar->add($intervalo);
//                }
//            }
//        }
//
//        if (Model::CompleteTrans() AND ! Model::hasFailedTrans()) {
//            Gestor_de_log::set('Ha creado un nuevo débito programado.', 1);
//            return $this;
//        }
//
//        Gestor_de_log::set('Ha ocurrido un error al crear un nuevo débito programado.', 0);
//        return false;
//    }

    abstract function comprobar_pertenencias_cbu($id_marchand, Clima_cbu $clima_cbu);
//    {
//        $existe_pertenencia_de_cbu = false;
//        $existe_pertenencia = false;
//
//        $id_clima = self::$clima_cbu->get_id_clima();
//        $recordset = Clima_assoc::select_pertenencia_cbu($id_marchand, $clima_cbu->get_id());
//        if (!$recordset)
//            $existe_pertenencia_de_cbu = false;
//        if ($recordset AND $recordset->RowCount() > 0) {
//            $existe_pertenencia_de_cbu = true;
//            $recordset = Vinvulo::select(array('id_clima' => $id_clima, 'id_entidad' => Entidad::ENTIDAD_MARCHAND, 'id_referencia' => $id_marchand));
//            if ($recordset AND $recordset->RowCount() > 0)
//                $existe_pertenencia = true;
//        }
//
//        return $existe_pertenencia_de_cbu AND $existe_pertenencia;
//    }

    abstract protected function comprobar_pertenencias_tco($id_marchand, Clima_tco $clima_tco);
//    {
//        $existe_pertenencia_de_tco = false;
//        $existe_pertenencia = false;
//
//        $id_clima = self::$clima_tco->get_id_clima();
//        $recordset = Clima_assoc::select_pertenencia_tco($id_marchand, $clima_tco->get_id());
//        if (!$recordset)
//            $existe_pertenencia_de_tco = false;
//        if ($recordset AND $recordset->RowCount() > 0) {
//            $existe_pertenencia_de_tco = true;
//            $recordset = Vinvulo::select(array('id_clima' => $id_clima, 'id_entidad' => Entidad::ENTIDAD_MARCHAND, 'id_referencia' => $id_marchand));
//            if ($recordset AND $recordset->RowCount() > 0)
//                $existe_pertenencia = true;
//        }
//
//        return $existe_pertenencia_de_tco AND $existe_pertenencia;
//    }

//    public function crear_una_cuota($id_marchand, $id_clima_cbu, $id_clima_tco, Barcode $barcode, Datetime $fecha_a_enviar_habil, Datetime $fecha_a_pagar_habil){
//        
//    }
//    {
//        if (self::ACTIVAR_DEBUG)
//            developer_log('Creando una cuota para un débito.');
//        if ($id_clima_tco) {
//            $id_clima_cbu = self::DUMMY_ID_CLIMA_CBU;
//            if (!$this->optimizar_clima_tco($id_clima_tco))
//                return false;
//            $id_clima_tco = self::$clima_tco->get_id_clima_tco();
//            $id_clima = self::$clima_tco->get_id_clima();
//
//            if ($this->carrier != self::CARRIER_TCDAUT AND $this->carrier != self::CARRIER_PVP) {
//                throw new Exception('Carrier incorrecto');
//            }
//            $carrier = $this->carrier;
//            $id_carrier = '1';
//        } elseif ($id_clima_cbu) {
//
//            $id_clima_tco = self::DUMMY_ID_CLIMA_TCO;
//            if (!$this->optimizar_clima_cbu($id_clima_cbu))
//                return false;
//            if (self::$clima_cbu->get_id_authstat() == Clima_cbu::CBU_ANULADO) {
//                throw new Exception("El CBU id tiene orden de no debitar. ");
//            }
//            $id_clima_cbu = self::$clima_cbu->get_id_clima_cbu();
//            $id_clima = self::$clima_cbu->get_id_clima();
//            $fecha_ejecucion = new DateTime('tomorrow');
//            $fecha_ejecucion->add(new DateInterval('PT' . self::HORA_EJECUCION_SCRIPT . 'H'));
//            if (self::ACTIVAR_DEBUG)
//                developer_log('Fecha de proxima ejecucion: ' . $fecha_ejecucion->format('d/m/Y H:i:s'));
//
//            if (intval($fecha_a_enviar_habil->format('Ymd')) < intval($fecha_ejecucion->format('Ymd'))) {
//                throw new Exception('No hay tiempo suficiente para enviar el débito al medio de pago: Retrase la fecha del débito. ');
//            }
//            list($carrier, $id_carrier) = $this->obtener_carrier($id_marchand);
//            if ($carrier === false OR $id_carrier === false) {
//                if (self::ACTIVAR_DEBUG)
//                    developer_log("No es posible obtener el carrier, la estructura configmarchand no es correcta. ");
//                if (true) {
//                    # BORRAR ESTE IF Y RETORNAR FALSE
//                    $carrier = self::CARRIER_DAUT; # TEMP
//                    $id_carrier = 51; # TEMP
//                } else {
//                    return false;
//                }
//            }
//        }
//        if (!$this->optimizar_clima($id_clima))
//            return false;# Innecesario, pero esta bueno tenerlo a mano
//        # Crear assoc, vinvulo y agenda_vinvulo
//        # Inserto un Vinvulo
//        $this->vinvulo = new Vinvulo();
//        $this->vinvulo->set_id_clima(self::$clima->get_id_clima());
//        $this->vinvulo->set_id_authstat(Authstat::ACTIVO);
//        $this->vinvulo->set_id_entidad(Entidad::ENTIDAD_MARCHAND);
//        $this->vinvulo->set_id_referencia($id_marchand);
//        $this->vinvulo->set_id_climarchand(self::DUMMY_ID_CLIMARCHAND);
//        $this->vinvulo->set_pmc19($barcode->get_pmc19());
//
//        if (!$this->vinvulo->set()) {
//            if (self::ACTIVAR_DEBUG)
//                developer_log('Ha ocurrido un error al insertar un registro en la tabla vinvulo.');
//            return false;
//        }
//
//        # Inserto un Clima_assoc
//        $this->clima_assoc = new Clima_assoc();
//        $this->clima_assoc->set_id_vinvulo($this->vinvulo->get_id());
//        $this->clima_assoc->set_id_clima_tco($id_clima_tco);
//        $this->clima_assoc->set_id_clima_cbu($id_clima_cbu);
//        $this->clima_assoc->set_id_authstat(Authstat::ACTIVO);
//        $this->clima_assoc->set_id_envistat(self::DUMMY_ID_ENVISTAT);
//        $this->clima_assoc->set_fechup('now()');
//
//        if (!$this->clima_assoc->set()) {
//            if (self::ACTIVAR_DEBUG)
//                developer_log('Ha ocurrido un error al insertar un registro en la tabla clima_assoc.');
//            return false;
//        }
//
//        # Inserto un agenda_vinvulo
//        $this->agenda_vinvulo = new Agenda_vinvulo();
//        $this->agenda_vinvulo->set_id_clima_assoc($this->clima_assoc->get_id());
//        $this->agenda_vinvulo->set_id_authstat(Authstat::ACTIVO);
//        $this->agenda_vinvulo->set_id_barcode($barcode->get_id());
//        $fecha_a_enviar_habil->setTime(15, 0, 0);
//        $fecha_a_pagar_habil->setTime(15, 0, 0);
//        $this->agenda_vinvulo->set_cuando_pagar($fecha_a_pagar_habil->format(FORMATO_FECHA_POSTGRES_SIN_TIMESTAMP));
//        $this->agenda_vinvulo->set_monto_apagar($barcode->get_monto());
//        $this->agenda_vinvulo->set_cuando_enviar($fecha_a_enviar_habil->format(FORMATO_FECHA_POSTGRES_SIN_TIMESTAMP));
//        $this->agenda_vinvulo->set_one1aut2(self::DUMMY_ONE1AUT2);
//        $this->agenda_vinvulo->set_efectivo_envio(NULL);
//
//        $this->agenda_vinvulo->set_carrier($carrier);
//        $this->agenda_vinvulo->set_id_carrier($id_carrier);
//        $this->agenda_vinvulo->set_agenda_xml(NULL);
//        $this->agenda_vinvulo->set_cuando_regen(NULL);
//        $this->agenda_vinvulo->set_cuando_proxven(NULL);
//        $this->agenda_vinvulo->set_id_regenstat(NULL);
//        $this->agenda_vinvulo->set_fechagen('now()');
//        $this->agenda_vinvulo->set_cuando_metermano(NULL);
//        $this->agenda_vinvulo->set_id_entidad(NULL);
//        $this->agenda_vinvulo->set_id_referencia(NULL);
//        //var_dump(Model::HasFailedTrans());
//        if (!$this->agenda_vinvulo->set()) {
//            if (self::ACTIVAR_DEBUG)
//                developer_log('Ha ocurrido un error al insertar un registro en la tabla agenda_vinvulo.');
//            return false;
//        }
//
//        return true;
//    }

    abstract protected function obtener_carrier($id_marchand) ;
//    {
//        if (!$this->optimizar_xml($id_marchand))
//            return array(false, false);
//        $view = new DOMDocument();
//        if (!($view->loadXML(self::$xml->get_xmlfield())))
//            return array(false, false);
//        $elementos = $view->getElementsByTagName('debito_automatico');
//        if (!$elementos OR $elementos->length != 1)
//            return array(false, false);
//        $elemento = $elementos->item(0);
//        if ($elemento->hasAttribute('habilitado') AND $elemento->getAttribute('habilitado') == 1) {
//            if ($elemento->hasAttribute('carrier')) {
//                $carrier = trim(strtolower($elemento->getAttribute('carrier')));
//                $id_carrier = Peucd::obtener_id_carrier($carrier);
//                return array($carrier, $id_carrier);
//            }
//        }
//        return array(false, false);
//    }

    abstract public function obtener_fecha_a_pagar_habil(Datetime $fecha_a_pagar);
//    {
//        if (Calendar::es_dia_habil($fecha_a_pagar))
//            return $fecha_a_pagar;
//        else {
//            $proximo_dia_habil = Calendar::proximo_dia_habil($fecha_a_pagar);
//            error_log(json_encode($proximo_dia_habil));
//            Gestor_de_correo::enviar(Gestor_de_correo::MAIL_DESARROLLO, "adupuy@cobrodigital.com", "error en procesamiento de debitos", json_encode(array("fecha_no_habil" => $fecha_a_pagar->format("d/m/Y"), "fecha_habil_siguiente" => $proximo_dia_habil->format("d/m/Y"))));
//        }
//        return Calendar::proximo_dia_habil($fecha_a_pagar);
//    }

    abstract public function obtener_fecha_a_enviar_habil(Datetime $fecha_a_pagar_habil) ;
//    {
//
//        return Calendar::anterior_dia_habil($fecha_a_pagar_habil, self::DIAS_DE_ANTICIPACION_DEL_BANCO_GALICIA);
//    }

    abstract public static function cambiar_estado($id_agenda_vinvulo, $id_marchand);
//    {
//        Model::StartTrans();
//        $error = true;
//        $mensaje = 'Ha ocurrido un error';
//        $agenda_vinvulo = new Agenda_vinvulo();
//        $agenda_vinvulo->get($id_agenda_vinvulo);
//
//        $clima_assoc = new Clima_assoc();
//        $clima_assoc->get($agenda_vinvulo->get_id_clima_assoc());
//
//        $vinvulo = new Vinvulo();
//        $vinvulo->get($clima_assoc->get_id_vinvulo());
//
//        if ($vinvulo->get_id_entidad() == Entidad::ENTIDAD_MARCHAND) {
//            if ($vinvulo->get_id_referencia() != $id_marchand) {
//                $error = true;
//                Model::FailTrans();
//                $mensaje = 'No puede cambiar el estado del Débito automático. ';
//                if (self::ACTIVAR_DEBUG)
//                    developer_log($mensaje);
//            }
//        }
//        else {
//            Model::FailTrans();
//            $error = true;
//        }
//
//        if ($agenda_vinvulo->get_carrier() == self::CARRIER_DAUT) {
//            $fecha_actual = new Datetime('today');
//            $fecha_a_enviar = Datetime::createFromFormat(Agenda_vinvulo::FORMATO_CUANDO_ENVIAR, $agenda_vinvulo->get_cuando_enviar());
//            if ($fecha_actual->format('Ymd') >= $fecha_a_enviar->format('Ymd')) {
//                developer_log('La fecha de envío es posterior o igual a la fecha actual.');
//                Model::FailTrans();
//            }
//        }
//        if (!Model::hasFailedTrans()) {
//            if ((($agenda_vinvulo->get_id_authstat() == Authstat::ACTIVO)
//                    AND ( $clima_assoc->get_id_authstat() == Authstat::ACTIVO))
//                    AND ( $vinvulo->get_id_authstat() == Authstat::ACTIVO)) {
//                $agenda_vinvulo->set_id_authstat(Authstat::INACTIVO);
//                $clima_assoc->set_id_authstat(Authstat::INACTIVO);
//                $vinvulo->set_id_authstat(Authstat::INACTIVO);
//
//                if (!$agenda_vinvulo->set())
//                    Model::FailTrans();
//                else {
//                    if (!$clima_assoc->set()) {
//                        Model::FailTrans();
//                    } else {
//                        if (!$vinvulo->set()) {
//                            Model::FailTrans();
//                        } else {
//                            $error = false;
//                            $mensaje = 'Ha desactivado el Débito automático. ';
//                            if (self::ACTIVAR_DEBUG)
//                                developer_log($mensaje);
//                        }
//                    }
//                }
//            }
//            elseif ((($agenda_vinvulo->get_id_authstat() == Authstat::INACTIVO)
//                    AND ( $clima_assoc->get_id_authstat() == Authstat::INACTIVO))
//                    AND ( $vinvulo->get_id_authstat() == Authstat::INACTIVO)) {
//
//                $agenda_vinvulo->set_id_authstat(Authstat::ACTIVO);
//                $clima_assoc->set_id_authstat(Authstat::ACTIVO);
//                $vinvulo->set_id_authstat(Authstat::ACTIVO);
//
//                if (!$agenda_vinvulo->set())
//                    Model::FailTrans();
//                else {
//                    if (!$clima_assoc->set()) {
//                        Model::FailTrans();
//                    } else {
//                        if (!$vinvulo->set()) {
//                            Model::FailTrans();
//                        } else {
//                            $mensaje = 'Ha activado el Débito automático. ';
//                            $error = false;
//                            if (self::ACTIVAR_DEBUG)
//                                developer_log($mensaje);
//                        }
//                    }
//                }
//            }
//        }
//
//        if ((Model::CompleteTrans() AND ! $error)AND ! Model::hasFailedTrans()) {
//            return true;
//        }
//        return false;
//    }

    abstract  static function optimizar_clima_cbu($id_clima_cbu);
//    {
//        if (self::$clima_cbu === false OR self::$clima_cbu->get_id_clima_cbu() !== $id_clima_cbu) {
//            $recordset = Clima_cbu::select(array('id_clima_cbu' => $id_clima_cbu));
//            if (!$recordset OR $recordset->RowCount() != 1) {
//                Gestor_de_log::set('Ha ocurrido un error al seleccionar el CBU.', 0);
//                return false;
//            }
//            self::$clima_cbu = new Clima_cbu($recordset->FetchRow());
//        }
//        return self::$clima_cbu;
//    }

    abstract protected static function optimizar_clima_tco($id_clima_tco); 
//    {
//        if (self::$clima_tco === false OR self::$clima_tco->get_id_clima_tco() !== $id_clima_tco) {
//            $recordset = Clima_tco::select(array('id_clima_tco' => $id_clima_tco));
//            if (!$recordset OR $recordset->RowCount() != 1) {
//                Gestor_de_log::set('Ha ocurrido un error al seleccionar el TCO.', 0);
//                return false;
//            }
//            self::$clima_tco = new Clima_tco($recordset->FetchRow());
//        }
//        return self::$clima_tco;
//    }

    abstract protected static function optimizar_clima($id_clima);
//    {
//        if (self::$clima === false OR self::$clima->get_id_clima() !== $id_clima) {
//            $recordset = Clima::select(array('id_clima' => $id_clima));
//            if (!$recordset OR $recordset->RowCount() != 1) {
//                Gestor_de_log::set('Ha ocurrido un error al seleccionar el Responsable.', 0);
//                return false;
//            }
//            self::$clima = new Clima($recordset->FetchRow());
//        }
//        return self::$clima;
//    }

    abstract static function optimizar_xml($id_marchand) ;
//    {
//        if (self::$xml === false OR self::$xml->get_id_marchand() !== $id_marchand) {
//            $recordset = Xml::select(array('id_marchand' => $id_marchand, 'id_entidad' => Entidad::ESTRUCTURA_CONFIG_MARCHAND));
//            if (!$recordset OR $recordset->RowCount() != 1) {
//                Gestor_de_log::set('Ha ocurrido un error al seleccionar el Config Marchand.', 0);
//                return false;
//            }
//            self::$xml = new Xml($recordset->FetchRow());
//        }
//        return self::$xml;
//    }

    abstract public function set_carrier($carrier) ;
//            {
//        $this->carrier = $carrier;
//    }

}
