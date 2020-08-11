<?php

class Adelanto extends Transaccion {

    public $marchand;
    public $config;
    public $semaforo;

    const IDENTIFICADOR_ADELANTO = "ADELANTO";

    public function __construct($id_marchand = false) {
        $this->marchand = new Marchand();
        $this->config = false;
        if ($id_marchand) {
            $this->marchand->get($id_marchand);
        }
	$this->semaforo= $this->obtener_semaforo($id_marchand);
        return $this;
    }

    public function obtener_comisiones_adelanto($monto) {
        developer_log("monto: " . $monto);
        $fecha = new DateTime("now");
        $costo_adelanto = $this->obtener_costo_actual();
        $fecha = new DateTime("now");
        $dias = array();
        $recordset = Moves::select_sin_liquidar($this->marchand->get_id_marchand());
        if (!$recordset or $recordset->rowCount() == 0) {
            developer_log("No hay movimientos sin liquidar");
            $this->liberar_semaforo($this->semaforo);
            throw new Exception("No hay movimientos sin liquidar");
        }
        $comision = 0;
        $ids_moves = array();
        $array_adelantos_nuevos = array();
        $array_adelantos_viejos = array();
        $monto_aux = $monto;
//        var_dump($recordset->rowCount());
        foreach ($recordset as $row) {
//            var_dump($row);
            $m = substr($row["moves"], 1, strlen($row["moves"]));
            $m = substr($m, 0, strlen($m) - 1);
            if (strlen($m) > 1)
                $move = explode(",", $m);
            else
                $move = array($m);
            foreach ($move as $id) {
                if (isset($moves))
                    unset($moves);
                $moves = new Moves();
                if ($row["tipo"] == "1") {
                    $moves_liq = new Moves_liquidacion();
                    $moves_liq->get($id);
                    $monto_aux -= $moves_liq->get_monto_restante();
                    $array_adelantos_viejos[$id] = $moves_liq;
                } else {
                    $moves->get($id);
                    $monto_aux -= $moves->get_monto_marchand();
                    $array_adelantos_nuevos[$id] = $moves;
                }
                $dias[$id] = $row["cant_dias"];
                if ($dias[$id] == 0)
                    $dias[$id] = 1;
                if ($monto_aux <= 0) {
                    break;
                }
            }
        }
//        var_dump(count($array_adelantos_viejos));
        foreach ($array_adelantos_viejos as $id => $row) {
            $ids_moves[$id] = $row->get_id_moves();
            $comision_anterior = $comision;
//           developer_log($row->get_monto_restante());
            $monto_restante = $row->get_monto_restante() == 0 ? 0 : $row->get_monto_restante();
            $comision += $monto_restante * (($costo_adelanto * $dias[$id]) / 100);
            developer_log("$comision_anterior +" . $monto_restante . " * (($costo_adelanto * " . $dias[$id] . ")/100 )=$comision");
        }
//        var_dump($array_adelantos_nuevos);
        foreach ($array_adelantos_nuevos as $id => $row) {
            $ids_moves[$id] = $row->get_id_moves();
            $comision_anterior = $comision;
            $comision += $row->get_monto_marchand() * (($costo_adelanto * $dias[$id]) / 100);
            developer_log("$comision_anterior +" . $row->get_monto_marchand() . " * (($costo_adelanto * " . $dias[$id] . ")/100 )=$comision");
        }
        $comision += $this->obtener_comision_dia($monto + $comision);
        $comision += $this->obtener_costo_mes($monto + $comision);
//        error_log(json_encode(array($comision, $ids_moves, $dias)));
	$this->liberar_semaforo($this->semaforo);
        return array($comision, $ids_moves, $dias);
    }

    private function procesar_subcomisiones($moves, $mp, $sabana, $traslada_comision) {
        //no se requiere esta funcion;
        return true;
    }

    private function actualizar_liquidacion($ids_moves, $monto_pagador) {
        $fecha = new DateTime("now");
        $monto_aux = $monto_pagador;
        foreach ($ids_moves as $id_moves => $valor) { //no se por que esta al revez pero esta bien
            $recordset = Moves_liquidacion::select_existencia($id_moves);
            $moves = new Moves();
            $moves_liq = new Moves_liquidacion();
            $moves->get($id_moves);
            if ($recordset->rowCount() > 0) {
                $row = $recordset->fetchRow();
                if ($row["monto_restante"] == 0 OR $row["monto_restante"] == null) {
                    continue 1; //continue en 1 es para que salga hasta el foreach sino sale hasta el if de arriba
                }
                $monto_aux -= $row["monto_restante"];
                $moves_liq->set_id_moves_liquidacion($row["id_moves_liquidacion"]);
            } else {
                $monto_aux -= $moves->get_monto_marchand();
                $moves_liq->set_fecha_gen($fecha->format("Y-m-d H:i:s"));
            }
            if ($monto_aux > 0)
                $monto_pendiente = doubleval("0");
            else
                $monto_pendiente = $monto_aux * -1;
            $moves_liq->set_id_moves($id_moves);
            $moves_liq->set_monto_restante((number_format($monto_pendiente, 2))); //cambio de signo
            $moves_liq->set_fecha_modificada($fecha->format("Y-m-d H:i:s"));
            $moves_liq->set_fecha_liq($moves->get_fecha_liq());
            $moves_liq->set_id_marchand($this->marchand->get_id_marchand());
            if (!$moves_liq->set()) {
                Model::FailTrans();
                $this->liberar_semaforo($this->semaforo);
                Throw new Exception("Error al insertar guardar el estado de liquidacion.");
            }
            if ($monto_aux <= 0) {
                break;
            }
        }
        return true;
    }

    public function crear($id_marchand, $id_mp, $monto_pagador, \DateTime $fecha, $id_referencia, \Sabana $sabana = null, \Barcode $barcode = null, $traslado_comision = false, $id_pricing_pag = false, $id_pricing_cdi = false, Transas $transas = null, $no_bloquear = false) {
        self::$grado_de_recursividad++;

//        if (!is_numeric($monto_pagador) OR $monto_pagador < 0) {
//            #  Los rechazos tienen monto_pagador igual a cero
//            throw new Exception('El monto debe ser numérico y mayor o igual a cero. ( "' . $monto_pagador . '" )');
//
//            return false;
//        }
        if (!($semaforo = $this->obtener_semaforo($id_marchand))) {
            $this->developer_log('No ha sido posible obtener el semáforo.');
            Model::StartTrans();
            Model::FailTrans();
        } else
            Model::StartTrans();
        $this->semaforo=$semaforo;
        if ($monto_pagador > $this->obtener_maximo_global()) {
            $this->liberar_semaforo($semaforo);
            throw new Exception("Error el monto supera al maximo definido para adelantos.");
            Model::FailTrans();
        }

        $array = Cliente::obtener_estado_de_cuenta(Application::$usuario->get_id_marchand());
        $disponible = $array["saldo_disponible"];
        $no_liquidado = $array["aun_no_liquidado"];
        error_log("disponible: " . $disponible);
        error_log("disponible: " . $no_liquidado);
        $monto_adelanto = $monto_pagador;
        if ($disponible = $monto_pagador) {
            
        } else
        if ($disponible >= $monto_pagador) {
            error_log("Monto disponible acanza para un retiro normal");
            throw new Exception("Monto disponible acanza para un retiro normal");
        } else
        if ($disponible < $monto_pagador) {
            error_log("Monto disponible menor al monto_pagador");
            $monto_adelanto = $monto_pagador - $disponible;
        }
        error_log("disponible: " . $monto_adelanto);

        if (!Model::hasFailedTrans()) {
            $moves = new Moves();
            $id_entidad = $this->deducir_id_entidad($id_mp);
            if ($id_entidad === false) {
                $this->developer_log('No es posible deducir la entidad.');
                Model::FailTrans();
            }
        }
        if (!Model::hasFailedTrans()) {
            $verificado = $this->verificar_propiedad_entidad_referencia($id_marchand, $id_entidad, $id_referencia);
            if (!$verificado) {
                $this->developer_log('Intenta registrar una transaccion asociada a tablas que no le pertenecen. ');
                Model::FailTrans();
            }
        }

        if (!Model::hasFailedTrans()) {
            $id_authstat = $this->deducir_id_authstat($id_mp);
            if ($id_authstat === false) {
                $this->developer_log('No es posible deducir el estado.');
                Model::FailTrans();
            }
        }
        if (!Model::hasFailedTrans()) {
            $id_tipomove = $this->deducir_id_tipomove($id_mp);
            if ($id_tipomove === false) {
                $this->developer_log('No es posible deducir el tipomove.');
                Model::FailTrans();
            }
        }
        if (!Model::hasFailedTrans()) {
            if (!$this->optimizar_mp($id_mp)) {
                $this->developer_log('Ha ocurrido un error al optimizar el Mp.');
                Model::FailTrans();
            }
        }

        if (!Model::hasFailedTrans()) {
            $moves->set_straight(self::STRAIGHT_DIFERENCIADOR_DE_MOVES);
            $moves->set_id_entidad($id_entidad);
            $moves->set_id_referencia($id_referencia);
            $moves->set_id_authstat($id_authstat);
            $moves->set_id_tipomove($id_tipomove);
            $moves->set_id_mp($id_mp);
            if ($sabana) {
                $moves->set_id_sabana($sabana->get_id_sabana());
            }

            # FECHAS
            $moves->set_fecha($fecha->format('Y-m-d'));
            $ahora = new DateTime('now');
            $moves->set_fecha_move($ahora->format('Y-m-d H:i:s'));
            $fecha_liq = $this->obtener_fecha_de_liquidacion($fecha, $id_mp, $moves->get_id_marchand());
            if (!$fecha_liq) {
                $this->developer_log("La fecha de liquidación no es válida. ");
                Model::FailTrans();
            } else {
                $moves->set_fecha_liq($fecha_liq->format('Y-m-d H:i:s'));
            }
        }
        if (!Model::hasFailedTrans()) {
            //print_r("$id_marchand, $id_mp, $monto_pagador, $sabana, $barcode, $id_pricing_pag, $id_pricing_cdi");
            $array = $this->calculo_directo($id_marchand, $id_mp, $monto_pagador, $sabana, $barcode, false, false);
            if ($array === false) {
                $this->developer_log("Ha ocurrido un error al realizar el calculo directo. ");
                Model::FailTrans();
            } else {
                list($monto_pagador, $pag_fix, $pag_var, $monto_cd, $cdi_fix, $cdi_var, $monto_marchand) = $array;

                unset($array);
            }
        }
        if (!Model::hasFailedTrans()) {
            list($comisiones_adel, $ids_moves, $dias) = $this->obtener_comisiones_adelanto($monto_adelanto, $id_marchand);
            $moves->set_monto_pagador($monto_pagador);
            $moves->set_pag_fix($pag_fix);
            $moves->set_pag_var($pag_var);
            $moves->set_monto_cd($monto_cd);
            $moves->set_cdi_fix($cdi_fix);
            $moves->set_cdi_var($comisiones_adel + $cdi_var);
            $comisiones = $cdi_var + $cdi_fix + $pag_fix + $pag_var;
            $monto_marchand += $comisiones_adel;
            $moves->set_monto_marchand($monto_marchand);
            # Semaforo hasta el insert
        }
        if (!Model::hasFailedTrans()) {
            $sumar = function($a, $b, $c = 0) {
                return $a + $b + $c;
            };
            $restar = function($a, $b, $c = 0) {
                return $a - $b - $c;
            };
            $saldo_actual = Moves::select_ultimo_saldo($id_marchand);
            $operacion_saldo = $this->operacion_saldo(self::$mp);
            $saldo_marchand = $operacion_saldo($saldo_actual, $monto_marchand);

            # $moves->set_id_bolemarchand();  # CD_MOVES ACEPTA ID_BOLEMARCHAND NULL AHORA
            $moves->set_saldo_marchand($saldo_marchand);
            $saldo_md5 = crypt(sprintf("%01.2f", $moves->get_saldo_marchand()), self::PASSWORD_CIFRADO_SALDOS);
            $moves->set_saldo_md5($saldo_md5);
            $moves->set_id_marchand($id_marchand);
            $moves->set_id_pricing($this->pricing_pag->get_id_pricing());
            $moves->set_id_pricing_mch($this->pricing_cdi->get_id_pricing());
            $moves->set_unid(1);
            $moves->set_moves_xml(self::IDENTIFICADOR_ADELANTO);

            $this->developer_log("Insertando Transacción Adelantada. ");
            if (!$this->validar_capital_disponible($moves)) {
                Model::FailTrans();
                $this->liberar_semaforo($semaforo);
                throw new Exception("No hay capital disponible para realizar la transacción. ");
            } else {
                $this->developer_log('El capital disponible es suficiente para realizar la transacción. ');
            }
        }
        if (!Model::hasFailedTrans()) {
            developer_log("Actualizando_liquidaciones");
            if (!$this->actualizar_liquidacion($ids_moves, $monto_pagador)) {
                Model::FailTrans();
                $this->liberar_semaforo($semaforo);
                throw new Exception("Imposible procesar saldo sin liquidar.");
            } else {
                $this->developer_log("Liquidaciones actualizadas correctamente.");
            }
        }
        if (!Model::hasFailedTrans()) {
            if (in_array($moves->get_id_mp(), array(Mp::COSTO_RAPIPAGO, Mp::COSTO_PAGO_FACIL, Mp::COSTO_PROVINCIA_PAGO, Mp::COSTO_COBRO_EXPRESS, Mp::COSTO_RIPSA, Mp::COSTO_MULTIPAGO, Mp::COSTO_BICA, Mp::COSTO_PRONTO_PAGO))) {
                if ($moves->get_monto_marchand() == 0) {
                    developer_log("EL COSTO ES 0 SALTEANDO...");
                    if (!$this->liberar_semaforo($semaforo)) {
                        developer_log('No ha sido posible liberar el semáforo.');
                    }
                    if (Model::CompleteTrans() AND ! Model::hasFailedTrans()) {
                        $this->developer_log('Transacción salteada correctamente. ');
                        # punto de salida correcto
                        self::$grado_de_recursividad--;
                        return $this;
                    }
                }
            }
            if ($this->validar($moves) AND ( $moves = $this->insertar_moves($moves))) {
                if (!$this->liberar_semaforo($semaforo)) {
                    developer_log('No ha sido posible liberar el semáforo.');
                    Model::FailTrans();
                }
                $iva=new Retencion_iva($id_marchand);
                $ganancias=new Retencion_ganancias($id_marchand);
                if (!Model::hasFailedTrans()) {
                    $this->moves = $moves;
                    $debeprocesar_retencion_iva=false;
                    $debeprocesar_retencion_ganancias=false;
                    if ($this->debe_procesar_retencion($iva,$moves->get_id_mp())) {
                            $debeprocesar_retencion_iva=true;
                        }
                    if ($this->debe_procesar_retencion($ganancias,$moves->get_id_mp())) {
                        $debeprocesar_retencion_ganancias=true;
                   }
                    if ($this->procesar_subcomisiones($moves, self::$mp, $sabana, false)) {
                        # Problema entre static y recursividad
                        if ($this->debe_procesar_costo_asociado($id_mp)) {
                            $debe_procesar_costo_asociado = true;
                            $this->developer_log('La transacción tiene costo asociado.');
                        } else {
                            $debe_procesar_costo_asociado = false;
                            $this->developer_log('La transacción no tiene costo asociado.');
                        }
                         if (($retencion_arba = $this->debe_procesar_retenciones_arba($id_mp)) != false) {
                            $es_sujeto_de_retencion = true;
                            $this->developer_log('El marchand es sujeto de retenciones.');
                        } else {
                            $es_sujeto_de_retencion = false;
                            $this->developer_log('El marchand no es sujeto de retenciones.');
                        }
                        if($debeprocesar_retencion_iva==true){
                            $this->procesar_retencion($iva,$moves);
                        }
                        if($debeprocesar_retencion_ganancias==true) {
                            $this->procesar_retencion($ganancias,$moves);
                        }
                        if ($debe_procesar_costo_asociado) {
                            if (!$this->procesar_costo_asociado($moves, $sabana)) {
                                $this->developer_log('Fallo al procesar costo asociado ' . $this->moves->get_id_mp() . " " . $sabana->get_id_mp());
                                Model::FailTrans();
                            }
                            if (!Model::hasFailedTrans() AND Model::CompleteTrans()) {
                                $this->developer_log('Transacción procesada correctamente. ');
                                # Unico punto de salida correcto
                                self::$grado_de_recursividad--;
                                return $this;
                            }
                        }
                         else if($es_sujeto_de_retencion){
                            $this->procesar_retencion();
                            if (!Model::hasFailedTrans() AND Model::CompleteTrans()) {
                                $this->developer_log('Transacción procesada correctamente. ');
                                # Unico punto de salida correcto
                                self::$grado_de_recursividad--;
                                $this->developer_log("TERMINA TRANSACCION TRANSACCIONES");
                                return $this;
                            }
                        }
                        else {
                            if (!Model::hasFailedTrans() AND Model::CompleteTrans()) {
                                $this->developer_log('Transacción procesada correctamente. ');
                                # Unico punto de salida correcto
                                self::$grado_de_recursividad--;
                                return $this;
                            }
                        }
                    } else {
                        $this->developer_log("Ha ocurrido un error al asignar subcomisiones. ");
                    }
                }
            }
        }
        $this->developer_log('Ha ocurrido un error al procesar la transacción. ');
//        exit();
        Model::FailTrans();
        Model::CompleteTrans();
        self::$grado_de_recursividad--;
        return false;
    }

    public function obtener_semana_del_mes(DateTime $date) {
        $dia = $date->format("d");
        $primer_dia = new DateTime();
        $primer_dia->setDate($date->format("Y"), $date->format("m"), "1");
        $primer_dia_d = $primer_dia->format("d");
        $primer_dia_d = number_format($primer_dia_d);
        $primer_dia_ds = $primer_dia->format("N");
        $dia_semana = $date->format("N");
        $semana = (($primer_dia_ds - 1) + $dia + (7 - $dia_semana)) / 7;
        return $semana;
    }

    public function obtener_dias_liquidacion($monto, DateTime $fecha, $id_marchand) {

        $rs_sin_liq = Moves::obtener_total_sin_liquidar($id_marchand);
//        var_dump($rs_sin_liq->rowCount());
        $monto_liq = array();
        $total_liq = 0;
        $dias_necesarios = array();
//        $monto_anterior = 0;
        $id_moves = array();
        $monto_aux = $monto;
        foreach ($rs_sin_liq as $row) {
            $total_liq += $row["monto_marchand"];
            $monto_aux -= $row["monto_marchand"];
            $id_moves[] = $row["id_moves"];
            $fecha_liq = DateTime::createFromFormat("Y-m-d h:i:s", $row['fecha_liq']);
            $diff = $fecha->diff($fecha_liq);
            $dias_necesarios[$row["id_moves"]] = $diff->format("%d");
            if ($monto >= $total_liq)
                break;
//            error_log(json_encode($row));
//            $total_liq += $row['monto_marchand'];
//            $id_moves[]=$row["id_moves"];
//            if($total_liq>=$monto)
//                break;
//            if ($total_liq < $monto) {
//                $monto_liq[] = $row['monto_marchand'];
//                $fecha_liq = DateTime::createFromFormat("Y-m-d", $row['fecha_liq']);
//                $diff = $fecha->diff($fecha_liq);
//                $dias_necesarios[] = $diff->format("%d");
//                $monto_anterior = $row['monto_marchand'];
//            } elseif ($total_liq >= $monto) {
//                $monto_liq[] = ($monto - $monto_anterior);
//                $fecha_liq = DateTime::createFromFormat("Y-m-d", $row['fecha_liq']);
//                $diff = $fecha->diff($fecha_liq);
//                $dias_necesarios[] = $diff->format("%d");
//            }
        }

        return array($dias_necesarios, $monto_liq, $id_moves);
    }

    public function developer_log($mensaje) {
        parent::developer_log($mensaje);
    }

    protected function validar_capital_disponible(Moves $moves) {
        $row = Cliente::obtener_estado_de_cuenta($moves->get_id_marchand());
        $saldo_disponible = $row['aun_no_liquidado'];
        if ($moves->get_monto_marchand() <= $saldo_disponible and $moves->get_saldo_marchand() >= 0) {
            return true;
        }
        return false;
    }

    public static function deducir_id_entidad($id_mp, $id_marchand = false, Transas $transas = null, Sabana $sabana = null) {
        switch ($id_mp) {
            case Mp::ADELANTOS_CHEQUE:
                $entidad = Entidad::ENTIDAD_MARCHAND;
                break;
            case Mp::ADELANTOS_TRANSFERENCIA:
                $entidad = Entidad::ENTIDAD_CBUMARCHAND;
                break;
            case Mp::REVERSO_DE_EGRESO:
                $entidad = Entidad::ENTIDAD_MOVES;
                break;
        }
        return $entidad;
    }

    protected function deducir_id_tipomove($id_mp) {
        switch ($id_mp) {
            case Mp::ADELANTOS_CHEQUE:
                $id_tipomove = Tipomove::CHEQUE;
                break;
            case Mp::ADELANTOS_TRANSFERENCIA:
                $id_tipomove = Tipomove::TRANSFERENCIA_BANCARIA;
                break;
            case Mp::REVERSO_DE_EGRESO:
                $id_tipomove = Tipomove::REVERSO_EGRESO;
                break;
            default :
                $this->liberar_semaforo($this->semaforo);
                Throw new Exception("No se puede deducir la el tipo move.");
                break;
        }
        return $id_tipomove;
    }

    protected function deducir_id_authstat($id_mp) {
        switch ($id_mp) {
            case Mp::ADELANTOS_TRANSFERENCIA:
            case Mp::ADELANTOS_CHEQUE:
                return Authstat::TRANSACCION_RETIRO_PENDIENTE;
                break;
            case Mp::REVERSO_DE_EGRESO:
                return Authstat::TRANSACCION_CANCELACION_REALIZADA;
        }
    }

    public function obtener_maximo_global() {
        $this->singleton();
        $adelantos = $this->config[2]["total_adelantos"];
        foreach ($adelantos as $adelanto) {
//            ver como hacer para que reste todos los retiros del dia ver cuaderno.
//            idea para modulo de carga de valores a descontar.
            if ($adelanto["concepto"] == "total_mensual") {
                $total = $adelanto['value'];
                $total = $total - $this->obtener_adelantos_del_dia();
                return $total;
            }
//            return $adelanto['value'];
        }
    }

    private function obtener_adelantos_del_dia() {
        $fecha = new DateTime("now");
        $recordset = Moves::select_adelantos_del_dia($fecha);
        if ($recordset and $recordset->rowCount() > 0) {
            return $recordset->fetchRow()["total"];
        }
    }

//    public function actualizar_maximo_global($monto) {
//        return true;
//        $this->singleton();
//        $adelantos = $this->config[2]["total_adelantos"];
//        foreach ($adelantos as $id=>$adelanto) {
////            var_dump($adelanto);
////            ver como hacer para que reste todos los retiros del dia ver cuaderno.
////            idea para modulo de carga de valores a descontar.
//            if ($adelanto["concepto"] == "total_mensual"){
//                $config=new Config();
//                $config->set_id_config($adelanto["id_config"]);
//                if($adelanto['value']-$monto>=0){
////                    var_dump($adelanto['value']-$monto);
//                    $config->set_value($adelanto['value']-$monto);
//                    if(!$config->set()){
//                        throw new Exception("Ha ocurrido un error al procesar el limite global.");
//                    }
//                    return true;
//                }
//                else 
//                    throw new Exception("El monto supera el limite global para adelantos.");
//                
//            }
//        }
//    }

    private function obtener_costo_actual() {

        $this->singleton();
//	var_dump($this->config);
//	exit();
        $adelantos = $this->config[2]["total_adelantos"];
        foreach ($adelantos as $adelanto) {
            if ($adelanto["concepto"] == "costo_adelanto") {
//                var_dump($adelanto);
                return $adelanto['value'];
            }
        }
        return 0;
    }

    protected function singleton() {
        if ($this->config == false) {
            $configuracion = new Configuracion();
            $this->config = $configuracion->obtener_configuracion($this->marchand->get_id_marchand());
        }
        return $this->config;
    }

    protected function obtener_costo_mes($comision) {
        $this->singleton();
        $configs = $this->config[2]["total_adelantos"];
        $fecha = new DateTime("now");
        foreach ($configs as $row) {
            foreach ($row as $key => $value) {
                if ($key == "concepto" and strpos($value, "semana_") !== false AND ( substr($value, -1) == $this->obtener_semana_del_mes($fecha))) {
                    $comision_anterior = $comision;
                    $comision *= ($row ['value']);
                    error_log("$comision_anterior +( $comision_anterior * (" . $row["value"] . " ) = $comision");
                }
            }
        }
        error_log($comision);
        return $comision;
    }

    protected function obtener_comision_dia($comision, DateTime $fecha = null) {
        $this->singleton();
        $configs = $this->config[2]["total_adelantos"];
        if ($fecha == null)
            $fecha = new DateTime("now");
        foreach ($configs as $row) {
            foreach ($row as $key => $value) {
                if ($key == "concepto" and strpos($value, "dia_") !== false AND ( substr($value, -1) == $fecha->format("N"))) {
                    $comision_anterior = $comision;
                    $comision *= ($row ['value']);
                    error_log("$comision_anterior +( $comision_anterior * (" . $row["value"] . ") = $comision");
                }
            }
        }
        error_log($comision);
        return $comision;
    }

    public function reversar(Moves $moves) {
        # Pedir solo id_moves y optimizar ?
//        print_r($moves);
        Model::StartTrans();
        $id_marchand = $moves->get_id_marchand();
        $monto_pagador = $moves->get_monto_pagador() + $moves->get_cdi_var();
        if (!($id_mp = $this->deducir_id_mp_para_reverso($moves->get_id_mp()))) {
            $this->developer_log('Ha ocurrido un error al deducir el id_mp para reverso');
            return false;
        }
        $fecha = new Datetime('now');
        $id_entidad = $this->deducir_id_entidad($id_mp);
        $id_referencia = $moves->get_id_moves();
        $id_pricing_pag = $moves->get_id_pricing();
        $id_pricing_cdi = $moves->get_id_pricing_mch();
        $moves->set_id_authstat(Authstat::TRANSACCION_CANCELACION_REALIZADA);
        if (!$moves->set())
            Model::FailTrans();
        if (!Model::HasFailedTrans())
            try {
                $transaccion = new Transaccion();
                $result = $transaccion->procesar($id_marchand, $id_mp, $monto_pagador, $fecha, $id_referencia, null, null, $id_pricing_pag, $id_pricing_cdi);
            } catch (Exception $e) {
                return false;
                Model::FailTrans();
            }
        if (Model::CompleteTrans())
            return $result;
        else
            return false;
    }

    public function debe_procesar_retenciones($id_mp) {
        if (!self::ACTIVAR_RETENCIONES)
            return false;
        switch ($id_mp) {
            case Mp::ADELANTOS_CHEQUE:
                return true;
            case Mp::ADELANTOS_TRANSFERENCIA:
                $marchand = new Marchand();
                $marchand->get($this->moves->get_id_marchand());
                $cbumarchand = new Cbumarchand();
                $cbumarchand->get($this->moves->get_id_referencia());
                if ($marchand->get_documento() !== $cbumarchand->get_cuit()) {
                    return true;
                } else {
                    return false;
                }
        }
    }
    public function obtener_semaforo($id_marchand) {
        if(!$this->semaforo)
            return parent::obtener_semaforo($id_marchand);
        return $this->semaforo;
    }
}
