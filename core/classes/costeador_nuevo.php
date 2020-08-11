<?php

class Costeador_nuevo {

    const ACTIVAR_DEBUG = false;
    const ACTIVAR_TEST = false; # Falla la transaccion siempre  # No funciona bien con el trait transaccion
    const AGREGAR_LOGS_ANIDADOS = true; # Adjunta los logs de Transaccion a la Alerta por correo

    private $log = false;
    public $sabanas_correctas = 0;
    public $sabanas_incorrectas = 0;

    const ALERTA_ASUNTO = 'Alerta de Costeador de Cobranzas';
    const DUMMY_ID_BARCODE = 1;
    const TIEMPO_DE_INACTIVIDAD=20;

    protected $limite_de_registros_por_ejecucion = 15000;
    public $marchand;
    protected $cantidad_maxima_carga=45;

    public function ejecutar() {


//        ini_set("session.gc_maxlifetime","7200");
        //primero tomo mi pid V
        //2 busco el marchand mas chico con id_authstat 120 o 141 o 144 que no este bloqueado V
        //3 bloqueo el marchand en una transaccion y abro otra general V
        //4 quitar permisos de retiro y verificar en transacciones si esta antes de cerrar la transaccion. X
        //6 si el marchand esta bloqueado por otro busco otro hasta encontrar uno solo. X
        //7 cuando termina de costear las sabanas termina el proceso. V
        //8 desbloqueo al marchand en una sola transaccion. V
        //terminar el proceso si el servidor esta colapsado. X 
        $pid = getmypid();
        developer_log("Verificando carga del servidor");
        $cantidad=$this->estado_servidor();
        if($cantidad>$this->cantidad_maxima_carga){
            exit();
        }
        do {
            $id_marchand = $this->obtener_id_marchand();
            if (!$id_marchand) {
                developer_log("No hay marchand para costear terminando ....");
                return false;
            }
            $this->marchand = new Marchand();
            $this->marchand->get($id_marchand);
        } while ($this->esta_bloqueado($this->marchand));
        developer_log("El marchand $id_marchand no esta bloqueado, continuando....");
        if (!$this->bloquear_marchand($this->marchand)) {
            developer_log("Error al bloquear al marchand, termina el proceso $pid");
            return false;
        }
        else
            developer_log("Marchand bloqueado, continua el proceso $pid");
//        if (!($mp=$this->obtener_semaforo())) {
//	   Model::CompleteTrans();
//            throw new Exception("Ha ocurrido un error al obtener el semáforo. ", 0);
//        }
        if (self::ACTIVAR_TEST) {
            $this->developer_log('Es una prueba: Comienza transaccion global.');
            Model::StartTrans();
        }
        $this->developer_log('Obteniendo registros de sabana para costear. ');
        $recordset = $this->obtener_recordset();
        if ($recordset and $recordset->RowCount() > 0) {
            $this->developer_log('Se encontraron ' . $recordset->RowCount() . ' registros para costear.');
            $i = 1;
            foreach ($recordset as $row) {
                developer_log("****PID ($pid)****COSTEANDO IDM $id_marchand Nro ( $i / " . $recordset->rowCount() . " ) ************************");
                $sabana = new Sabana($row);
                $control = new Control;
                $control->set_id($sabana->get_revno());
                $control->set_pid($pid);
                if (!$control->set()) {
                    developer_log("No se pudo guardar el PID para controlar.");
                    $this->liberar_semaforo($this->marchand);
                    return false;
                }
                $id_mp=$sabana->get_id_mp();
                $costeador=$this->obtener_clase_costeador($id_mp);
                $costeador=new $costeador();
                if ($costeador->costear_sabana($sabana)) {
                    $this->sabanas_correctas++;
                } else {
                    $this->sabanas_incorrectas++;
                }
                $i++;
            }
        } elseif ($recordset and $recordset->RowCount() == 0) {
            $this->developer_log('No hay sabanas que costear. ');
        } else {
            $this->developer_log('Ha ocurrido un error.');
            return false;
        }

        $this->developer_log('Cantidad de sabanas costeadas correctamente: ' . $this->sabanas_correctas);
        $this->developer_log('Cantidad de sabanas incorrectas: ' . $this->sabanas_incorrectas);
        if (self::ACTIVAR_TEST) {
            $this->developer_log('Es una prueba: Falla transaccion global.');
            Model::FailTrans();
            Model::CompleteTrans();
        }
        if (!$this->desbloquear_marchand($this->marchand)) {
            developer_log("Error al bloquear al marchand, termina el proceso $pid");
            return false;
        }
        else
            developer_log("Marchand bloqueado, continua el proceso $pid");
        if (!$this->liberar_semaforo($this->marchand)) {
            developer_log('Ha ocurrido un error al liberar el semaforo');
        }
        return $this->sabanas_correctas;
    }
    private function desbloquear_retiro($marchand){
        $rs= Usumarchand::select(array("id_marchand"=>$marchand->get_id_marchand()));
        foreach ( $rs as $usuario){
            $user=new Usumarchand($usuario);
            $recordset= Usupermiso::select(array("id_usumarchand"=>$user->get_id_usumarchand(),"id_permiso"=> Usupermiso::PERMISO_DESACTIVADO_RETIRO_BOTON));
            foreach ($recordset as $usupe){                                                                 
                $usupermiso=new Usupermiso($usupe);
                $usupermiso->set_id_permiso(Usupermiso::PERMISO_BOTON_RETIRO_VIEJO);
                if(!$usupermiso->set()){
                    return false;
                }
            }
        }
        return true;
    }
    private function desbloquear_marchand(Marchand $marchand){
         Model::StartTrans();
        developer_log($marchand->get_id_marchand());
        $recordset = $this->liberar_semaforo($marchand);
        developer_log($recordset->RowCount());
        # Solo actualizo nops
        if(!$this->desbloquear_retiro($marchand)){
                Model::FailTrans();
                developer_log("No se pudo bloquear el boton de retiro.");
            developer_log("se termina el bloqueo: SEMAFORO MARCHAND LIBERADO");
            Model::CompleteTrans();
            return true;
        }
        Model::FailTrans();
        Model::CompleteTrans();
        return false;
    }
    private function estado_servidor(){
        $rs= Model::select_stat_activity();
        $row=$rs->fetchRow();
        return $row["cantidad"];
    }

    private function obtener_id_marchand() {
        $rs = Sabana::select_marchand_costeo();
        if ($rs->rowCount() > 0) {
            $row = $rs->fetchRow();
            return $row["id_marchand"];
        }
        return false;
    }

    private function esta_bloqueado(Marchand $marchand) {
        if ($marchand->get_nops() == 0)
            return false;
        return true;
    }

    protected function obtener_recordset() {
        
        $rs= Sabana::select_costeo_por_marchand($this->marchand,$this->limite_de_registros_por_ejecucion);
        
        return $rs;
    }

    private function costear_sabana(Sabana $sabana) {
        if ($sabana->get_id_authstat() == Authstat::SABANA_ORIGENUFO) {
            $this->developer_log('Costeando Ufo: Obteniendo registro desde codigo de barras');
            $recordset = Barcode::select(array('barcode' => $sabana->get_barcode()));
            if (!$recordset or $recordset->RowCount() != 1) {
                $this->developer_log('Ha ocurrido un error al obtener el Barcode por codigo de barras: ' . $sabana->get_barcode());
                return false;
            }
            $row = $recordset->FetchRow();
            $barcode = new Barcode($row);
            unset($recordset);
            unset($row);
        } else {
            $barcode = new Barcode();
            $id_barcode = $sabana->get_id_barcode();
            if ($id_barcode == self::DUMMY_ID_BARCODE) {
                $this->developer_log("ID_Barcode  es " . self::DUMMY_ID_BARCODE . " corrigiendo id_barcode en sabana.");
                $sabana = $this->corregir_id_barcode_sabana($sabana);
                $id_barcode = $sabana->get_id_barcode();
            }
            if ((!$sabana->get_id_barcode() or ! $barcode->get($id_barcode))) {
                $this->developer_log('Ha ocurrido un error al obtener el Barcode por id: ' . $sabana->get_id_barcode());
                return false;
            }
        }
        Model::setTransacctionMode("READ_UNCOMMITED");
        Model::StartTrans();
        $this->developer_log("INICIA TRANSACCION DE COSTEO");
        if ($this->actualizar_estados($sabana, $barcode)) {
            if ($this->consolidar($sabana, $barcode)) {
                if (Model::CompleteTrans() and ! Model::hasFailedTrans()) {
                    $this->developer_log("TERMINA TRANSACCION DE COSTEO");
                    return true;
                }
            } else {
                $this->developer_log("Ha ocurrido un error al consolidar. ");
            }
        } else {
            $this->developer_log(" Ha ocurrido un error al actualizar los estados. ");
        }
        Model::FailTrans();
        Model::CompleteTrans();
        return false;
    }

    protected function actualizar_estados(Sabana $sabana, Barcode $barcode) {
        if ($this->actualizar_barcode($sabana, $barcode)) {
            if ($this->actualizar_sabana($sabana)) {
                return true;
            }
        }
        return false;
    }

    protected function actualizar_barcode(Sabana $sabana, Barcode $barcode) {
        $this->developer_log("Actualizando Barcode: '" . $barcode->get_barcode() . "'");
        $id_authstat = Authstat::BARCODE_PAGADO;
        $barcode->set_id_authstat($id_authstat);
        if ($barcode->set()) {
            return true;
        }
        $this->developer_log("Ha ocurrido un error al actualizar el Barcode. ");
        return false;
    }

    protected function actualizar_sabana(Sabana $sabana) {
        $this->developer_log("Actualizando Sabana. ");
        $id_authstat = Authstat::SABANA_COBRADA;
        $sabana->set_id_authstat($id_authstat);
        if ($sabana->set()) {
            return true;
        }
        $this->developer_log("Ha ocurrido un error al actualizar el Barcode. ");
        return false;
    }

    protected function consolidar(Sabana $sabana, Barcode $barcode) {
        $this->developer_log("Consolidando. ");
        $id_marchand = $barcode->get_id_marchand();
        $id_mp = $sabana->get_id_mp();
        error_log($sabana->get_monto());
        $monto_pagador = $sabana->get_monto();
        $fecha = $sabana->get_fecha_pago();
        $fecha_datetime = Datetime::createFromFormat(Sabana::FORMATO_FECHA_FECHA_PAGO, $fecha);
        if (!$fecha_datetime) {
            $this->developer_log('La fecha no es correcta.');
            return false;
        }

        $transaccion = new Transaccion();
        $id_referencia = $barcode->get_id();
        $mensaje_excepcion = false;
        try {
            developer_log("se crea la transaccion sin bloquear ya estamos bloqueados.");
            $resultado = $transaccion->crear($id_marchand, $id_mp, $monto_pagador, $fecha_datetime, $id_referencia, $sabana, $barcode,false,false,false,null,true);
        } catch (Exception $e) {
            $resultado = false;
            $mensaje_excepcion = $e->getMessage();
        }
        if (self::AGREGAR_LOGS_ANIDADOS) {
            if (count($transaccion->log)) {
                foreach ($transaccion->log as $mensaje) {
                    $this->log[] = $mensaje;
                }
            }
            if ($mensaje_excepcion) {
                $this->log[] = $mensaje_excepcion;
            }
        }
        return $resultado;
    }

    protected function developer_log($mensaje) {
        if (self::ACTIVAR_DEBUG) {
            developer_log($mensaje);
        }
        $this->log[] = $mensaje;
    }

    private function enviar_alerta() {
        developer_log("Enviando Alerta. ");
        $emisor = Gestor_de_correo::MAIL_COBRODIGITAL_INFO;
        $destinatario = Gestor_de_correo::MAIL_DESARROLLO;
        $asunto = self::ALERTA_ASUNTO;
        $mensaje = '';
        if ($this->log) {
            foreach ($this->log as $linea) {
                $mensaje .= $linea . '<br/>';
            }
        }
        return Gestor_de_correo::enviar($emisor, $destinatario, $asunto, $mensaje);
    }

    protected function obtener_semaforo() {
        developer_log("INICIO TRANSACCION SEMAFORO COSTEO");
        Model::StartTrans();
        if (($id_mp = $this->obtener_id_mp())) {
            $recordset = Mp::semaforo_libre_para_costear($id_mp);
            developer_log($recordset->RowCount());
            if ($recordset and $recordset->RowCount() == 1) {
                $mp = new Mp($recordset->FetchRow());
                $mp->set_nops(Mp::SEMAFORO_OCUPADO);
                if ($mp->set() AND Model::CompleteTrans()) {
                    developer_log("FIN TRANS COSTEO");
                    return $mp;
                }
            } else {
                $recordset = Control::obtener_ultimo_control_mp(new DateTime("now"), $id_mp);
                if ($recordset and $recordset->rowCount() == 1) {
                    $row = $recordset->fetchRow();
                    $pid = $row['pid'];
                    if ($pid != null and file_exists("/proc/$pid")) {
                        developer_log("FIN TRANS COSTEO DESBLOQUEADO");
                        Model::CompleteTrans();
                        return false;
                    } else {
                        $mp = new Mp();
                        $mp->set_id($id_mp);
                        if ($this->liberar_semaforo($this->marchand)) {
                            developer_log("se libera el semaforo por pid muerto.");
                            Model::CompleteTrans();
                            return $this->obtener_semaforo();
                        }
                        developer_log("FIN TRANS COSTEO ERROR");
                        Model::CompleteTrans();
                        return false;
                    }
                }
            }
        }
        Gestor_de_correo::enviar(Gestor_de_correo::MAIL_DESARROLLO, Gestor_de_correo::MAIL_DESARROLLO, "Bloqúeo de Costeador", "El costeador no pudo obtener el semaforo para el id_mp:" . $id_mp);
        Model::CompleteTrans();
        developer_log("FIN trans costeo error 2");
        return false;
    }

    protected function liberar_semaforo($marchand) {
        $marchand->set_nops(Mp::SEMAFORO_LIBRE);
        if ($marchand->set()) {
            return true;
        }
        return false;
    }

    private function obtener_id_mp() {
        $id_mp = false;
        switch (get_class($this)) {
            case 'Costeador_galicia':
                $id_mp = Mp::GALICIA;
                break;
            case 'Costeador_galicia_reverso':
                $id_mp = Mp::DEBITO_AUTOMATICO_REVERSO;
                break;
            case 'Costeador_galicia_rechazo':
                $id_mp = Mp::GALICIA;
                break;
            case 'Costeador_galicia_ufo':
                $id_mp = Mp::GALICIA;
                break;
            case 'Costeador_mercadopago':
                $id_mp = Mp::MERCADOPAGO;
                break;
            case 'Costeador_rapipago':
                $id_mp = Mp::RAPIPAGO;
                break;
            case 'Costeador_pagofacil':
                $id_mp = Mp::PAGOFACIL;
                break;
            case 'Costeador_provinciapago':
                $id_mp = Mp::PROVINCIAPAGO;
                break;
            case 'Costeador_cobroexpress':
                $id_mp = Mp::COBROEXPRESS;
                break;
            case 'Costeador_ripsa':
                $id_mp = Mp::RIPSA;
                break;
            case 'Costeador_multipago':
                $id_mp = Mp::MULTIPAGO;
                break;
            case 'Costeador_bica':
                $id_mp = Mp::BICA;
                break;
            case 'Costeador_prontopago':
                $id_mp = Mp::PRONTOPAGO;
                break;
            case 'Costeador_pagomiscuentas':
                $id_mp = Mp::PAGOMISCUENTAS;
                break;
            case 'Costeador_linkpagos':
                $id_mp = Mp::LINKPAGOS;
                break;
            case 'Costeador_pagos_cobrodigital':
                $id_mp = Mp::PAGOS_COBRODIGITAL;
                break;
            case 'Costeador_cobradores':
                $id_mp = Mp::COBRO_COBRADORES;
                break;
            default :
                developer_log("Error, no se pudo encontrar la clase " . get_class($this));
                break;
        }
        return $id_mp;
    }

    public static function obtener_clase_costeador($id_mp, $ufo = false) {
        $clase_costeador = false;
        switch ($id_mp) {
            case Mp::GALICIA:
                if ($ufo) {
                    $clase_costeador = 'Costeador_galicia_ufo_nuevo';
                } else {
                    $clase_costeador = 'Costeador_galicia_nuevo';
                }
                break;
            case Mp::DEBITO_AUTOMATICO_COSTO_RECHAZO:
                $clase_costeador = 'Costeador_galicia_rechazo_nuevo';
                break;
            case Mp::DEBITO_AUTOMATICO_REVERSO:
                $clase_costeador = 'Costeador_galicia_reverso_nuevo';
                break;
            case Mp::MERCADOPAGO:
                $clase_costeador = 'Costeador_mercadopago_nuevo';
                break;
            case Mp::RAPIPAGO:
                $clase_costeador = 'Costeador_rapipago_nuevo';
                break;
            case Mp::PAGOFACIL:
                $clase_costeador = 'Costeador_pagofacil_nuevo';
                break;
            case Mp::PROVINCIAPAGO:
                $clase_costeador = 'Costeador_provinciapago_nuevo';
                break;
            case Mp::RIPSA:
                $clase_costeador = 'Costeador_ripsa_nuevo';
                break;
            case Mp::COBROEXPRESS:
                $clase_costeador = 'Costeador_cobroexpress_nuevo';
                break;
            case Mp::PAGOMISCUENTAS:
                $clase_costeador = 'Costeador_pagomiscuentas_nuevo';
                break;
            case Mp::MULTIPAGO:
                $clase_costeador = 'Costeador_multipago_nuevo';
                break;
            case Mp::BICA:
                $clase_costeador = 'Costeador_bica_nuevo';
                break;
            case Mp::LINKPAGOS:
                $clase_costeador = 'Costeador_linkpagos_nuevo';
                break;
            case Mp::PRONTOPAGO:
                $clase_costeador = 'Costeador_prontopago_nuevo';
                break;
            case Mp::COBRO_COBRADORES:
                $clase_costeador = 'Costeador_cobradores_nuevo';
                break;
            case Mp::PAGOS_COBRODIGITAL:
                $clase_costeador = 'Costeador_pagos_cobrodigital_nuevo';
                break;
            default :
                developer_log("Error al obtener la Clase_costeador del MP: " . $id_mp);
        }
        if (class_exists($clase_costeador)) {
            return $clase_costeador;
        }
        return false;
    }

    private function corregir_id_barcode_sabana(Sabana $sabana) {
//        Sabana::update_id_barcode_sabana($sabana->get_id_mp(), $sabana->get_id_authstat(), $sabana->get_barcode());
        $recordset = Barcode::select(array("barcode" => $sabana->get_barcode()));
        if (!$recordset AND $recordset->rowCount() != 1) {
            $this->developer_log("Falló al obtener el barcode " . $sabana->get_barcode());
            return false;
        } else {
            $row = $recordset->fetchRow();
            $sabana->set_id_barcode($row['id_barcode']);
            $this->developer_log("Corrigido id_barcode de sabana.");
        }
        return $sabana;
    }

    private function bloquear_marchand(Marchand $marchand) {
        Model::StartTrans();
        developer_log($marchand->get_id_marchand());
        $recordset = Marchand::obtener_semaforo($marchand->get_id_marchand());
        developer_log($recordset->RowCount());
        if ($recordset AND $recordset->RowCount() == 1) {
            $row = $recordset->FetchRow();
            $marchand = new Marchand();
            # Solo actualizo nops
            $marchand->set_id_marchand($row['id_marchand']);
            $marchand->set_nops(Marchand::SEMAFORO_OCUPADO);
            if(!$this->bloquear_retiro($marchand)){
                Model::FailTrans();
                developer_log("No se pudo bloquear el boton de retiro.");
            }
            if ($marchand->set() and Model::CompleteTrans()) {
                developer_log("TERMINA TRANSACCION SEMAFORO");
                developer_log("se termina el bloqueo: SEMAFORO MARCHAND OBTENIDO");
                Model::CompleteTrans();
                return true;
            } else {
                Model::failTrans();
                developer_log("TERMINA TRANSACCION SEMAFORO");
                Model::CompleteTrans();
                return false;
            }
        }
        return false;
    }
    public function bloquear_retiro(Marchand $marchand){
        $rs= Usumarchand::select(array("id_marchand"=>$marchand->get_id_marchand()));
        foreach ( $rs as $usuario){
            $user=new Usumarchand($usuario);
            $recordset= Usupermiso::select(array("id_usumarchand"=>$user->get_id_usumarchand(),"id_permiso"=> Usupermiso::PERMISO_BOTON_RETIRO_VIEJO));
            foreach ($recordset as $usupe){
                $usupermiso=new Usupermiso($usupe);
                $usupermiso->set_id_permiso(Usupermiso::PERMISO_DESACTIVADO_RETIRO_BOTON);
                if(!$usupermiso->set()){
                    return false;
                }
            }
        }
        return true;
    }
}
