<?php

# ESTE MODULO NECESITA UN REFACTOR ENTRE TCO Y CBU

class Debitos_tco extends Debito {

    const ACTIVAR_DEBUG = true;
    const DIAS_DE_ANTICIPACION_DEL_BANCO_GALICIA = 2;
    const HORAS_DE_ANTICIPACION_DEL_BANCO_GALICIA = 48;
    const HORA_EJECUCION_SCRIPT = 5; # En realidad corre a las 7:30 Am pero le ponemos una banda de seguridad
    const ACTIVAR_MONTOS = true;
    const ACTIVAR_DECIDIR = false;
    const MONTO_1 = "monto1";

    public static $clima_tco = false; # OPTIMIZAR
    public static $clima = false; # OPTIMIZAR
    public static $xml = false; # OPTIMIZAR # Es el config marchand
    public $clima_assoc = false; # NO OPTIMIZAR
    public $debito_tco= false; # El debito # NO OPTIMIZAR
    private $carrier = false;
    private $file;

    const CARRIER_TCDAUT = 'tcdaut';
    const CARRIER_PVP = 'pvp';
    const CARRIER_DAUT = 'galicia2';
    const CARRIER_TC_DECIDIR = 'decidir';
    const DUMMY_ID_SC = '2040';
    const DUMMY_MODELO = 'debito_programado';
    const DUMMY_ONE1AUT2 = '1';
    const DUMMY_ID_CLIMARCHAND = '3';
    const DUMMY_ID_CLIMA_TCO = 25;
    const DUMMY_ID_CLIMA_CBU = 24;
    const DUMMY_ID_ENVISTAT = 2;
    /**
     * $id_marchand, $id_clima_tco, $importe, $fecha, $concepto, $cantidad_cuotas = 1, $modalidad_cuotas = 'mensuales', $modo_estricto = true, $inmediato = false, $id_trans = false, $servicio = false, $tipo_pago = false, $file = "Sin file", $titular = "Agregar", $tco = false,$cvv=false,Datetime $fecha_tco=null,$carrier=false
     */
    public function crear($id_marchand, $id_clima_tco, $importe, $fecha, $concepto, $cantidad_cuotas = 1, $modalidad_cuotas = 'mensuales', $modo_estricto = true, $inmediato = false, $id_trans = false, $servicio = false, $tipo_pago = false, $file = "Sin file", $titular = "Agregar", $tco = false,$cvv=false,Datetime $fecha_tco=null,$carrier=false,$verificar_preexistencia=true) {
        if (self::ACTIVAR_DEBUG)
            developer_log('Creando un débito de ' . $cantidad_cuotas . ' cuota/s.');
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
        if (!$this->optimizar_clima_tco($id_clima_tco)) {
            Model::FailTrans();
        }
        if (!Model::hasFailedTrans()) {
            $id_clima = self::$clima_tco->get_id_clima();
            if ($modo_estricto AND ! $this->comprobar_pertenencias_tco($id_marchand, self::$clima_tco)) {
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
                $fecha_a_pagar_habil = $this->obtener_fecha_a_pagar_habil(clone $fecha_a_pagar);
                $fecha_a_enviar_habil = $this->obtener_fecha_a_enviar_habil(clone $fecha_a_pagar_habil);
                if (self::ACTIVAR_DEBUG)
                    developer_log('Fecha de informe al medio de pago: ' . $fecha_a_enviar_habil->format('d/m/Y H:i:s'));
                $fecha_desde = new DateTime("now");
                if (self::ACTIVAR_DEBUG)
                    developer_log('Fecha de debito : ' . $fecha_a_pagar_habil->format('d/m/Y H:i:s'));


                $fechas_vencimiento = array($fecha_a_pagar_habil->format('d/m/Y'));
                if ($verificar_preexistencia and $this->verificar_preexistencia($fecha_a_pagar_habil, $importe, $concepto, $id_clima, $id_clima_tco, $id_marchand)) {
                    Gestor_de_log::set('El debito ya se encuentra cargado.', 0);
                    Model::failTrans();
                    throw new Exception('El debito ya se encuentra cargado.');
                    return false;
                } else {
                    if (self::ACTIVAR_DEBUG)
                        developer_log("El debito no existe con anterioridad dando de alta");
                }
                //$id_marchand, $id_clima_tco, Datetime $fecha_a_enviar_habil, Datetime $fecha_a_pagar_habil, $concepto = "", $importe, $titular, $tco,$cvv
                if (!$this->crear_una_cuota($id_marchand, $id_clima_tco, $fecha_a_enviar_habil, $fecha_a_pagar_habil, $concepto, $importe, $titular, $tco,$cvv,$fecha_tco,$carrier))
                    Model::FailTrans();
                $fecha_a_pagar->add($intervalo);
            }
        }
       
        if (Model::CompleteTrans() AND ! Model::hasFailedTrans()) {
            Gestor_de_log::set('Ha creado un nuevo débito programado.', 1);
            return $this;
        }

        Gestor_de_log::set('Ha ocurrido un error al crear un nuevo débito programado.', 0);
        return false;
    }

    public function verificar_preexistencia($fecha_a_pagar_habil, $importe, $concepto, $id_clima, $id_clima_tco, $id_marchand) {
        $fecha = $fecha_a_pagar_habil->format("Y-m-d");
        $rs_debito = Debito_tco::select(array("fecha_pago" => $fecha, "monto" => $importe, "concepto" => $concepto, "id_clima" => $id_clima, "id_clima_tco" => $id_clima_tco, "id_marchand" => $id_marchand));
        if ($rs_debito->rowCount() > 0) {
            developer_log("El debito esta cargado " . $rs_debito->rowCount() . " veces. / $fecha , $importe,$concepto,$id_clima,$id_clima_cbu,$id_marchand");
            return true;
        }
        return false;
    }

    protected function comprobar_pertenencias_tco($id_marchand, Clima_tco $clima_tco) {
        $existe_pertenencia_de_cbu = false;
        $existe_pertenencia = false;

        $id_clima = self::$clima_tco->get_id_clima();
        $recordset = Clima_assoc::select_pertenencia_tco($id_marchand, $clima_tco->get_id());
        if (!$recordset)
            $existe_pertenencia_de_tco = false;
        if ($recordset AND $recordset->RowCount() > 0) {
            $existe_pertenencia_de_tco = true;
            $recordset = Vinvulo::select(array('id_clima' => $id_clima, 'id_entidad' => Entidad::ENTIDAD_MARCHAND, 'id_referencia' => $id_marchand));
            if ($recordset AND $recordset->RowCount() > 0)
                $existe_pertenencia = true;
        }

        return $existe_pertenencia_de_cbu AND $existe_pertenencia;
    }

    protected function crear_una_cuota($id_marchand, $id_clima_tco, Datetime $fecha_a_enviar_habil, Datetime $fecha_a_pagar_habil, $concepto = "", $importe, $titular, $tco,$cvv,Datetime $fecha_tco,$carrier=false) {
        if (self::ACTIVAR_DEBUG)
            developer_log('Creando una cuota para un débito.');
        if (!$this->optimizar_clima_tco($id_clima_tco))
            return false;
        if (self::$clima_tco->get_id_authstat() == Clima_cbu::CBU_ANULADO) {
            throw new Exception("El CBU id tiene orden de no debitar. ");
        }
        $id_clima_tco = self::$clima_tco->get_id_clima_tco();
        $id_clima = self::$clima_tco->get_id_clima();
        $fecha_ejecucion = new DateTime('tomorrow');
        $fecha_ejecucion->add(new DateInterval('PT' . self::HORA_EJECUCION_SCRIPT . 'H'));
        if (self::ACTIVAR_DEBUG)
            developer_log('Fecha de proxima ejecucion: ' . $fecha_ejecucion->format('d/m/Y H:i:s'));
        list($carrier, $id_carrier) = $this->obtener_carrier($id_marchand,$carrier);
        if ($carrier === false OR $id_carrier === false) {
            if (self::ACTIVAR_DEBUG)
                developer_log("No es posible obtener el carrier, la estructura configmarchand no es correcta. ");
            return false;
        }
        $fecha = $fecha_a_pagar_habil->format("Y-m-d");
        $monto = $importe;
        # Inserto un debito_cbu
        $this->debito_tco= new Debito_tco();
        $this->debito_tco->set_titular($titular);
        $this->debito_tco->set_cuit(self::$clima->get_documento());
        $this->debito_tco->set_concepto($concepto);
        $this->debito_tco->set_fechagen("now()");
        $this->debito_tco->set_fecha_enviar($fecha_a_enviar_habil->format('Y-m-d'));

        $this->debito_tco->set_id_authf1(Authstat::DEBITO_ACTIVO);
        $this->debito_tco->set_fecha_pago($fecha);
        $this->debito_tco->set_monto($monto);

        $this->debito_tco->set_id_clima(self::$clima->get_id());
        $this->debito_tco->set_id_clima_tco(self::$clima_tco->get_id());
        $this->debito_tco->set_id_marchand($id_marchand);
        list($carrier, $id_carrier) = $this->obtener_carrier($id_marchand,$carrier);
        $this->debito_tco->set_carrier($carrier);
        $this->debito_tco->set_id_carrier($id_carrier);
        $this->debito_tco->set_file($this->file);
        $this->debito_tco->set_ingresa_tco($tco);
        $this->debito_tco->set_ingresa_cvv($cvv);
        $this->debito_tco->set_fecha_vto_tco($fecha_tco->format("Y-m-d"));
        $this->debito_tco->set_email(self::$clima->get_email());
        if (!$this->debito_tco->set()) {
            if (self::ACTIVAR_DEBUG)
                developer_log('Ha ocurrido un error al insertar un registro en la tabla debito_cbu.');
            return false;
        }

        return true;
    }

    protected function obtener_carrier($id_marchand,$carrier=false) {
        if(!self::ACTIVAR_DECIDIR){
            return array(self::CARRIER_TCDAUT, 2);
        }
        if(!$carrier)
            return array(self::CARRIER_TCDAUT, 2);
        return array(self::CARRIER_TC_DECIDIR, 3);
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
        return $fecha_a_pagar_habil;
    }

    public static function cambiar_estado($id_debito_tco, $id_marchand) {
        Model::StartTrans();
        $error = true;
        $mensaje = 'Ha ocurrido un error';
        $Debitos_tco = new Debito_tco();
        $Debitos_tco->get($id_debito_tco);
        if ($Debitos_tco->get_carrier() == self::CARRIER_DAUT) {
            $fecha_actual = new Datetime('today');
            $fecha_a_enviar = Datetime::createFromFormat("Y-m-d", $Debitos_tco->get_fecha_enviar());
            if ($fecha_actual->format('Ymd') >= $fecha_a_enviar->format('Ymd')) {
                developer_log('La fecha de envío es posterior o igual a la fecha actual.');
                Model::FailTrans();
            }
        }
        if (!Model::hasFailedTrans()) {
            if ($Debitos_tco->get_id_authf1() == Authstat::ACTIVO) {
                $d = new Debito_cbu();
                $d->set_id($Debitos_tco->get_id());
                $d->set_id_authf1(Authstat::INACTIVO);
                if (!$d->set())
                    Model::FailTrans();
                else {
                    $error = false;
                    $mensaje = 'Ha desactivado el Débito automático. ';
                    if (self::ACTIVAR_DEBUG)
                        developer_log($mensaje);
                }
            }
            elseif ($Debitos_tco->get_id_authf1() == Authstat::INACTIVO) {
                $d = new Debito_cbu();
                $d->set_id($Debitos_tco->get_id());
                $d->set_id_authf1(Authstat::ACTIVO);
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

    protected static function optimizar_clima_tco($id_clima_tco) {
        if (self::$clima_tco === false OR self::$clima_tco->get_id_clima_tco() !== $id_clima_tco) {
            $recordset = Clima_tco::select(array('id_clima_tco' => $id_clima_tco));
            if (!$recordset OR $recordset->RowCount() != 1) {
                Gestor_de_log::set('Ha ocurrido un error al seleccionar el CBU.', 0);
                return false;
            }
            self::$clima_tco = new Clima_tco($recordset->FetchRow());
        }
        return self::$clima_tco;
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

    public function comprobar_pertenencias_cbu($id_marchand, Clima_cbu $clima_cbu) {
        //no se utiliza cbu en  tco
        return false;
    }

    public static function optimizar_xml($id_marchand) {
        //no se utiliza
        return false;
    }

    public static function optimizar_clima_cbu($id_clima_cbu) {
        
    }

}
