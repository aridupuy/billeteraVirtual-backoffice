<?php

class Transaccion {

    const ACTIVAR_DEBUG = true;
    const INTENTOS_DE_OBTENER_SEMAFORO = 10;
    const MICROSEGUNDOS_DE_REINTENTO_SEMAFORO = 250000; # Medio segundo
    const ACTIVAR_RETENCIONES = TRUE;

    public $log = false;
    public $moves = false;
    public $pricing_pag = false; # Es mas complicado pero podria optimizarlo mas adelante
    public $pricing_cdi = false; # Es mas complicado pero podria optimizarlo mas adelante
    protected static $mp = false; # Optimizar #Atencion a las variables static luego de recursividad
    protected static $tipomove = false; # Optimizar
    public static $grado_de_recursividad = 0;
    public $no_bloquear = false;
    public $id_marchand;

    const PROPAGACION_DE_SUBCOMISIONES = 1; # Aun no implementado (nivel de recursividad)
    const LIMITE_INFERIOR_MONTO_PAGADOR = 0; # Al margen de esto, debe ser mayor a cero 
    const LIMITE_SUPERIOR_MONTO_PAGADOR = 100000000; # Limite de Codigo de Barras
    const LIMITE_INFERIOR_PAG_FIX = 0;
    const LIMITE_SUPERIOR_PAG_FIX = false;
    const LIMITE_INFERIOR_PAG_VAR = 0;
    const LIMITE_SUPERIOR_PAG_VAR = false;
    const LIMITE_INFERIOR_MONTO_CD = -100; # Solo casos en que la comision sea mayor que el monto
    const LIMITE_SUPERIOR_MONTO_CD = false;
    const LIMITE_INFERIOR_CDI_FIX = 0;
    const LIMITE_SUPERIOR_CDI_FIX = false;
    const LIMITE_INFERIOR_CDI_VAR = 0;
    const LIMITE_SUPERIOR_CDI_VAR = false;
    const LIMITE_INFERIOR_MONTO_MARCHAND = -1550; # Solo casos en que la comision sea mayor que el monto
    const LIMITE_SUPERIOR_MONTO_MARCHAND = false;
    const LIMITE_INFERIOR_SALDO_MARCHAND = false;
    const LIMITE_SUPERIOR_SALDO_MARCHAND = false;
    const PASSWORD_CIFRADO_SALDOS = 'cdJOikok'; # Heredado
    const STRAIGHT_DIFERENCIADOR_DE_MOVES = 70; # Usado para detectar quien "costeo"

    public function crear($id_marchand, $id_mp, $monto_pagador, DateTime $fecha, $id_referencia, Sabana $sabana = null, Barcode $barcode = null, $traslado_comision = false, $id_pricing_pag = false, $id_pricing_cdi = false, Transas $transas = null, $no_bloquear = false) {
        developer_log("transaccion crear");
        if ($traslado_comision) {
            $tr = "true";
        } else {
            $tr = "false";
        }
        if (self::ACTIVAR_DEBUG){
            $this->developer_log("crear  $id_mp " . $tr);
        }
        if ($no_bloquear == true)
            $this->no_bloquear = $no_bloquear;
        $this->id_marchand = $id_marchand;
        return $this->procesar($id_marchand, $id_mp, $monto_pagador, $fecha, $id_referencia, $sabana, $barcode, $id_pricing_pag, $id_pricing_cdi, $traslado_comision, $transas, $no_bloquear);
    }

    public function reversar(Moves $moves) {
        # Pedir solo id_moves y optimizar ?
//                print_r($moves);
        developer_log("REVERSANDO ".$moves->get_id_moves());
        $id_marchand = $moves->get_id_marchand();
        if ($this->es_costo_de_efectivo($moves->get_id_mp())) {
            $monto_pagador = $moves->get_cdi_var();
        } else {
            $monto_pagador = $moves->get_monto_pagador();
        }

        if (!($id_mp = $this->deducir_id_mp_para_reverso($moves->get_id_mp()))) {
            $this->developer_log('Ha ocurrido un error al deducir el id_mp para reverso');
            return false;
        }
        $fecha = new Datetime('now');
        $id_entidad = $this->deducir_id_entidad($id_mp, $moves->get_id_marchand(),null,null,$moves->get_id_mp());
        error_log("entidad:" . $id_entidad . "  mp: " . $id_mp);

        if ($id_entidad == Entidad::ENTIDAD_MOVES) {
            $id_referencia = $moves->get_id_moves();
        } elseif ($id_entidad == Entidad::ENTIDAD_MARCHAND) {
            $id_referencia = $moves->get_id_marchand();
        }
        elseif ($id_entidad == Entidad::ENTIDAD_BARCODE) {
            $id_referencia = $moves->get_id_referencia();
        } else {
            $id_referencia = $moves->get_id_referencia();
        }
        if ($moves->get_id_mp() == Mp::PAGO_DE_SERVICIOS or $moves->get_id_mp() == Mp::COBRO_DE_SERVICIOS) {
            $id_entidad = Entidad::ENTIDAD_MOVES;
            $id_referencia = $moves->get_id_moves();
            $this->moves = $moves;
        }

        $id_pricing_pag = $moves->get_id_pricing();
        $id_pricing_cdi = $moves->get_id_pricing_mch();
        if ($this->debe_reversar_retenciones($id_mp)) {
            $rs = Retencion::select(array("id_move" => $moves->get_id()));
            if ($rs->rowCount() !== 0) {
                $row = $rs->fetchRow();
                if (self::ACTIVAR_DEBUG)
                    developer_log("Reversando retencion");

                $retencion = new Retencion($row);
                //var_dump($retencion);
//	    exit();
                $moves_retencion = new Moves();
                $moves_retencion->get($retencion->get_id_moves_retencion());
                $id_pricing_cdi_ret = $moves_retencion->get_id_pricing_mch();
                $id_pricing_pag_ret = $moves_retencion->get_id_pricing();
                $id_mp_ret = Mp::REVERSO_RETENCION_IMPOSITIVA;
                $id_referencia_ret = $moves_retencion->get_id();
                if (!$this->procesar($id_marchand, $id_mp_ret, $retencion->get_monto_retenido(), $fecha, $id_referencia_ret, null, null, $id_pricing_pag_ret, $id_pricing_cdi_ret)) {
                    throw new Exception_costeo("No se pudo reversar la retencion");
                }
                $retencion->set_id_authstat(Authstat::INACTIVO);
                //var_dump($retencion);
                if (!$retencion->set()) {
                    throw new Exception_costeo("No se pudo inhabilitar la retencion");
                }
            }
        }
        return $this->procesar($id_marchand, $id_mp, $monto_pagador, $fecha, $id_referencia, null, null, $id_pricing_pag, $id_pricing_cdi);
    }

    public function es_costo_de_efectivo($id_mp) {
        $array = array(Mp::COSTO_RAPIPAGO, Mp::COSTO_PAGO_FACIL, Mp::COSTO_PROVINCIA_PAGO, Mp::COSTO_COBRO_EXPRESS, Mp::COSTO_RIPSA, Mp::COSTO_MULTIPAGO, Mp::COSTO_BICA, Mp::COSTO_PRONTO_PAGO);
        if (in_array($id_mp, $array)) {
            return true;
        }
        return false;
    }

    private function procesar($id_marchand, $id_mp, $monto_pagador, DateTime $fecha, $id_referencia, Sabana $sabana = null, Barcode $barcode = null, $id_pricing_pag = false, $id_pricing_cdi = false, $traslada_comision = false, Transas $transas = null) {
        self::$grado_de_recursividad++;

//        if (!is_numeric($monto_pagador) OR $monto_pagador < 0) {
//            #  Los rechazos tienen monto_pagador igual a cero
//            throw new Exception('El monto debe ser numérico y mayor o igual a cero. ( "' . $monto_pagador . '" )');
//
//            return false;
//        }
        # Semaforo hasta el insert
        
        if (!$this->no_bloquear and ! ($semaforo= $this->obtener_semaforo($id_marchand))) {
            $this->developer_log('No ha sido posible obtener el semáforo.' . $id_marchand);
            Model::StartTrans();
            if (self::ACTIVAR_DEBUG)
                $this->developer_log("inicia transaccion fallida");
            Model::FailTrans();
        }
        else {
            if (self::ACTIVAR_DEBUG)
                $this->developer_log("inicia transaccion transacciones");
            Model::StartTrans();
        }
        if (!Model::hasFailedTrans()) {
            $moves = new Moves();
            $id_entidad = $this->deducir_id_entidad($id_mp, $id_marchand, $transas, $sabana);
            if ($id_entidad === false) {
                $this->developer_log('No es posible deducir la entidad.');
                Model::FailTrans();
            }
            if ($this->moves != null) {

                if ($this->moves->get_id_mp() == Mp::PAGO_DE_SERVICIOS or $this->moves->get_id_mp() == Mp::COBRO_DE_SERVICIOS) {
                    $id_entidad = Entidad::ENTIDAD_MOVES;
                }
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
                $this->developer_log('No es posible deducir  el estado.');
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
            if ($transas) {
                $moves->set_id_transas($transas->get_id_transas());
                
            }
            # FECHAS
            $moves->set_fecha($fecha->format('Y-m-d'));
            $ahora = new DateTime('now');
            $moves->set_fecha_move($ahora->format('Y-m-d H:i:s'));
            $fecha_liq = $this->obtener_fecha_de_liquidacion($fecha, $id_mp, $moves->get_id_marchand(),$sabana);
//            var_dump($fecha_liq);
            if (!$fecha_liq) {
                $this->developer_log("La fecha de liquidación no es válida. ");
                Model::FailTrans();
            } else {
                $moves->set_fecha_liq($fecha_liq->format('Y-m-d H:i:s'));
            }
        }
        //   var_dump($traslada_comision);
        if (!Model::hasFailedTrans()) {
            //print_r("$id_marchand, $id_mp, $monto_pagador, $sabana, $barcode, $id_pricing_pag, $id_pricing_cdi");
            try {
                developer_log("EN calculo_directo $monto_pagador");
                $array = $this->calculo_directo($id_marchand, $id_mp, $monto_pagador, $sabana, $barcode, $id_pricing_pag, $id_pricing_cdi, $traslada_comision);
            } catch (Exception $e) {
                $this->developer_log("Se para la ejecucion");
                $marchand = new Marchand();
                $marchand->get($id_marchand);
                
                $this->developer_log("Liberando semaforo para IDM: $id_marchand para el ID_MP: $id_mp  ID_PAGCD: " . $e->getMessage() . "con ID_ENTIDAD : $id_entidad Y ID_REFERENCIA: $id_referencia ");
                $this->liberar_semaforo($semaforo);
                throw new Exception_costeo("libera costeo");
            }
            if ($array === false) {
                $this->developer_log("Ha ocurrido un error al realizar el calculo directo. ");
                Model::FailTrans();
            } else {
                list($monto_pagador, $pag_fix, $pag_var, $monto_cd, $cdi_fix, $cdi_var, $monto_marchand) = $array;

                unset($array);
            }
        }
        if (!Model::hasFailedTrans()) {
            $moves->set_monto_pagador($monto_pagador);
            //
            if ($traslada_comision) {
                $comisiones = $pag_fix + $pag_var;
                $this->pricing_cdi->get(Pricing::ID_PRICING_CDI_SIN_COMISION);
            } else {
                $comisiones = $cdi_var + $cdi_fix;
                $this->pricing_pag->get(Pricing::ID_PRICING_PAG_SIN_COMISION);
            }
            if ($id_mp == Mp::PAGO_PROVEEDOR_PENDIENTE AND $traslada_comision) {
                //si es proveedor pendiente y traslada comision no hay que cobrar nada
                $monto_marchand -= $comisiones;
                $monto_cd = $monto_pagador;
                $comisiones = 0;
                $this->pricing_cdi->get(Pricing::ID_PRICING_CDI_SIN_COMISION);
                $this->pricing_pag->get(Pricing::ID_PRICING_PAG_SIN_COMISION);
                $cdi_fix = 0;
                $cdi_var = 0;
                $pag_fix = 0;
                $pag_var = 0;
            } elseif ($id_mp == Mp::PAGO_PROVEEDOR_PENDIENTE AND ! $traslada_comision) {
                //si es proveedor pendiente y no traslada comision no hay que cobrar la comision del pagador
                $this->pricing_pag->get(Pricing::ID_PRICING_PAG_SIN_COMISION);
            }   
            if ($id_mp == Mp::AJUSTE_DE_COMISIONES) {
                $moves->set_monto_pagador(0);
                $monto_cd = 0;
                $cdi_fix = $monto_marchand;
                $this->developer_log("Se le quita dinero al marchand el CDI_FIX es igual a = ".$cdi_fix);
                $cdi_var = 0;
                $monto_marchand = $monto_marchand;
//                exit();
            }elseif ($id_mp == Mp::CORRECCION_DE_COMISIONES) {
                $moves->set_monto_pagador(0);
                $monto_cd = 0;
                $cdi_fix = -$monto_marchand;
                $this->developer_log("Se devuelve dinero al marchand el CDI_FIX es igual a = ".$cdi_fix);
                $cdi_var = 0;
//                var_dump($cdi_fix);
//                exit();
            }
            $moves->set_pag_fix($pag_fix);
            $moves->set_pag_var($pag_var);
            $moves->set_monto_cd($monto_cd);
            $moves->set_cdi_fix($cdi_fix);
            $moves->set_cdi_var($cdi_var);
            $moves->set_monto_marchand($monto_marchand);
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
            if($id_mp == Mp::AJUSTE_DE_COMISIONES){
                 $monto_marchand = $monto_marchand * -1;
                $moves->set_monto_marchand($monto_marchand);
                
            }

            # $moves->set_id_bolemarchand();  # CD_MOVES ACEPTA ID_BOLEMARCHAND NULL AHORA
            $moves->set_saldo_marchand($saldo_marchand);
            $saldo_md5 = crypt(sprintf("%01.2f", $moves->get_saldo_marchand()), self::PASSWORD_CIFRADO_SALDOS);
            $moves->set_saldo_md5($saldo_md5);
            $moves->set_id_marchand($id_marchand);
            $moves->set_id_pricing($this->pricing_pag->get_id_pricing());
            $moves->set_id_pricing_mch($this->pricing_cdi->get_id_pricing());
            $moves->set_unid(1);

            $this->developer_log("Insertando Transacción. ");
            if (!$this->validar_capital_disponible($moves)) {
                Model::FailTrans();
                throw new Exception("No hay capital disponible para realizar la transacción. ");
            } else {
                $this->developer_log('El capital disponible es suficiente para realizar la transacción. ');
            }
        }
        if (!Model::hasFailedTrans()) {
            if (in_array($moves->get_id_mp(), array(Mp::COSTO_RAPIPAGO, Mp::COSTO_PAGO_FACIL, Mp::COSTO_PROVINCIA_PAGO, Mp::COSTO_COBRO_EXPRESS, Mp::COSTO_RIPSA, Mp::COSTO_MULTIPAGO, Mp::COSTO_BICA, Mp::COSTO_PRONTO_PAGO,Mp::COSTO_PEI_DEVOLUCION,Mp::COSTO_DECIDIR_DEVOLUCION))) {
                if ($moves->get_monto_marchand() == 0) {
                    $this->developer_log("EL COSTO ES 0 SALTEANDO...");
                    if (!$this->no_bloquear and ! $this->liberar_semaforo($semaforo)) {
                        $this->developer_log('No ha sido posible liberar el semáforo.');
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
                if (!$this->no_bloquear and ! $this->liberar_semaforo($semaforo)) {
                    $this->developer_log('No ha sido posible liberar el semáforo.');
                    Model::FailTrans();
                }
                $iva=new Retencion_iva($id_marchand);
                $ganancias=new Retencion_ganancias($id_marchand);
                if (!Model::hasFailedTrans()) {
                    if (self::$grado_de_recursividad == 1)
                        $this->moves = $moves;
                    $debeprocesar_retencion_iva=false;
                    $debeprocesar_retencion_ganancias=false;
                    if ($this->debe_procesar_retencion($iva,$moves->get_id_mp())) {
                            $debeprocesar_retencion_iva=true;
                        }
                    if ($this->debe_procesar_retencion($ganancias,$moves->get_id_mp())) {
                        $debeprocesar_retencion_ganancias=true;
                   }
                    if ($this->procesar_subcomisiones($moves, self::$mp, $sabana, $traslada_comision)) {
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
                            if (!$this->procesar_costo_asociado($moves, $sabana, $traslada_comision)) {
                                if($sabana != null )
                                    $this->developer_log('Fallo al procesar costo asociado ' . $this->moves->get_id_mp() . " " . $sabana->get_id_mp());
                                else 
                                    $this->developer_log('Fallo al procesar costo asociado ' . $this->moves->get_id_mp());
                                Model::FailTrans();
                            }
                            if (!Model::hasFailedTrans() AND Model::CompleteTrans()) {
                                $this->developer_log('Costo asociado procesado correctamente. ');
                                # Unico punto de salida correcto
                                self::$grado_de_recursividad--;
                                $this->developer_log("TERMINA TRANSACCION TRANSACCIONES");
                                return $this;
                            }
                        } if ($es_sujeto_de_retencion and $retencion_arba) {
                            $this->procesar_retencion($retencion_arba,$moves);
                            if (!Model::hasFailedTrans() AND Model::CompleteTrans()) {
                                $this->developer_log('Retencion ARBA procesada correctamente. ');
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
                                $this->developer_log("TERMINA TRANSACCION TRANSACCIONES");
                                return $this;
                            }
                        }
                        
                        
                        
                    } else {
                        $this->developer_log("Ha ocurrido un error al asignar subcomisiones. ");
                    }
//                   
                }
            }
        }
        $this->developer_log('Ha ocurrido un error al procesar la transacción. ');
//        exit();
        Model::FailTrans();
        $this->developer_log("TERMINA TRANSACCION TRANSACCIONES");
        Model::CompleteTrans();
        self::$grado_de_recursividad--;
        return false;
    }

    protected function debe_reversar_retenciones($id_mp) {
        developer_log("en reversar retencion id_mp $id_mp");
        if ($id_mp == Mp::REVERSO_DE_EGRESO OR $id_mp == Mp::REVERSO_DE_INGRESO)
            return true;
        return false;
    }

    protected function debe_procesar_retenciones_arba($id_mp) {
        if (!self::ACTIVAR_RETENCIONES)
            return false;
            $marchand = new Marchand();
            $marchand->get($this->id_marchand);
        if (in_array($id_mp, array(Mp::RETIROS_CHEQUE, Mp::RETIROS_CHEQUE_POR_CORREO, Mp::PAGO_PROVEEDOR_PENDIENTE, Mp::RETIROS_CHEQUE_POR_SUCURSAL, MP::PAGO_DE_SERVICIOS, Mp::PAGO_A_PROVEEDOR))) {
            return new Retencion_arba($marchand->get_id_marchand());
        } elseif ($id_mp == Mp::RETIROS) {
            
            $cbumarchand = new Cbumarchand();
            $cbumarchand->get($this->moves->get_id_referencia());
            if ($marchand->get_documento() !== $cbumarchand->get_cuit()) {
                return new Retencion_arba($marchand->get_id_marchand());
            } else {
                return false;
            }
        } else
            return false;
    }
    protected function debe_procesar_retencion(Retencion_impositiva $retencion,$id_mp){
    try{
        return $retencion->esta_sujeto_a_retenciones($id_mp);
    }catch(Exception $e){
        developer_log("Throw:".$e->getMessage());
        return false;
    }
    }
    protected function procesar_retencion(Retencion_impositiva $retencion,$moves) {
        return $retencion->retener($moves);
    }

    public function obtener_comisiones($id_marchand, $id_mp, $id_pagcd, Sabana $sabana = null, Barcode $barcode = null) {

        $id_trixgroup = 1;
        $id_738 = 5;
        $id_formapago = 1;
        $id_liquidac = 1;

//        if (!$this->debe_procesar_comisiones($id_mp)) {
//            $pricing = new Pricing();
//            if($id_pagcd==2)
//                $pricing->get(Pricing::PRICING_DEFAULT_CDI);
//            else
//                $pricing->get(Pricing::PRICING_DEFAULT_PAG);
//            return $pricing;
//        }

        if ($sabana) {
            $id_formapago = $sabana->get_id_formapago();
        }
        if ($barcode) {
            $id_738 = $barcode->get_id_738();
            $trixgroup = new Trixgroup();

            $recordset = Trixgroup::select(array('id_sc' => $barcode->get_id_sc()));
            if ($recordset AND $recordset->RowCount() == 1) {
                $row = $recordset->FetchRow();
                $id_trixgroup = $row['id_trixgroup'];
                unset($row);
            }
            unset($recordset);
            unset($trixgroup);
        }
        $id_pricing = false;

        $niveles = array();
//        $niveles[] = array('id_marchand' => $id_marchand, 'id_trixgroup' => $id_trixgroup, 'id_mp' => $id_mp, 'id_738' => $id_738, 'id_liquidac' => $id_liquidac, 'lastest' => '1', 'id_pagcd' => $id_pagcd);
//        $niveles[] = array('id_marchand' => $id_marchand, 'id_mp' => $id_mp,  'id_738' => $id_738, 'id_liquidac' => $id_liquidac, 'lastest' => '1', 'id_pagcd' => $id_pagcd);
//        $niveles[] = array('id_marchand' => $id_marchand, 'id_mp' => $id_mp,  'id_738' => $id_738, 'lastest' => '1', 'id_pagcd' => $id_pagcd);
//        $niveles[] = array('id_marchand' => $id_marchand, 'id_mp' => $id_mp, 'lastest' => '1', 'id_pagcd' => $id_pagcd);
//        $niveles[] = array('id_marchand' => $id_marchand, 'id_mp' => $id_mp, 'xomision' => '1', 'lastest' => '1', 'id_pagcd' => $id_pagcd);
//        $niveles[] = array('id_marchand' => '2', 'id_mp' => $id_mp,  'id_738' => $id_738, 'id_liquidac' => $id_liquidac, 'lastest' => '1', 'id_pagcd' => $id_pagcd);
//        $niveles[] = array('id_marchand' => '2', 'id_mp' => $id_mp,  'id_738' => $id_738, 'lastest' => '1', 'id_pagcd' => $id_pagcd);
//        $niveles[] = array('id_marchand' => '2', 'id_mp' => $id_mp, 'id_738' => $id_738, 'lastest' => '1', 'id_pagcd' => $id_pagcd);
//        $niveles[] = array('id_marchand' => '2', 'id_mp' => $id_mp, 'lastest' => '1', 'id_pagcd' => $id_pagcd);
//        $niveles[] = array('id_marchand' => '2', 'id_mp' => $id_mp, 'xomision' => '1', 'lastest' => '1', 'id_pagcd' => $id_pagcd);
//	$niveles[]=  array('id_marchand' => '2', 'id_mp' => $id_mp, 'lastest' => 1,  'id_pagcd'=>$id_pagcd,'xomision'=>1);
        $niveles[] = array('id_marchand' => $id_marchand, 'id_mp' => $id_mp, 'id_pagcd' => $id_pagcd, 'lastest' => 1, 'id_trixgroup' => $id_trixgroup, 'id_738' => $id_738, 'id_liquidac' => $id_liquidac);
        $niveles[] = array('id_marchand' => $id_marchand, 'id_mp' => $id_mp, 'id_pagcd' => $id_pagcd, 'lastest' => 1, 'id_trixgroup' => $id_trixgroup);
        $niveles[] = array('id_marchand' => $id_marchand, 'id_mp' => $id_mp, 'id_pagcd' => $id_pagcd, 'lastest' => 1);
        $niveles[] = array('id_marchand' => '2', 'id_mp' => $id_mp, 'id_pagcd' => $id_pagcd, 'lastest' => 1, 'id_738' => $id_738, 'id_liquidac' => $id_liquidac);
        $niveles[] = array('id_marchand' => '2', 'id_mp' => $id_mp, 'id_pagcd' => $id_pagcd, 'lastest' => 1);

        if ($id_pagcd == 2) {
            if (in_array($id_mp, array(Mp::RAPIPAGO, Mp::PAGOFACIL, Mp::PROVINCIAPAGO, Mp::COBROEXPRESS, Mp::RIPSA, Mp::MULTIPAGO, Mp::PAGOMISCUENTAS, Mp::BICA, Mp::PRONTOPAGO, Mp::LINKPAGOS))) {
                $id_pricing = Pricing::PRICING_DEFAULT_CDI;
                $this->developer_log("Estoy costeando con default: " . Pricing::PRICING_DEFAULT_CDI);
            } else {
//                $id_pricing = $this->obtener_max_pricing_id($id_pagcd,$id_mp);
            }
        } elseif ($id_pagcd == 1) {
            if (in_array($id_mp, array(Mp::RAPIPAGO, Mp::PAGOFACIL, Mp::PROVINCIAPAGO, Mp::COBROEXPRESS, Mp::RIPSA, Mp::MULTIPAGO, Mp::PAGOMISCUENTAS, Mp::BICA, Mp::PRONTOPAGO, Mp::LINKPAGOS))) {
                $id_pricing = Pricing::PRICING_DEFAULT_PAG;
                $this->developer_log("Estoy costeando con default: " . Pricing::PRICING_DEFAULT_PAG);
            } else {
//		$id_pricing = $this->obtener_max_pricing_id($id_pagcd,$id_mp);
            }
        }
//        if(!isset($id_pricing) or !$id_pricing){
//                 $id_pricing = $this->obtener_max_pricing_id($id_pagcd);
//        }    
        $niveles[] = array('id_pricing' => $id_pricing);

        $error = true;
        $cant_niveles = count($niveles);
        for ($i = 0; $i < $cant_niveles AND $error; $i++) {
            $recordset = Pricing::select($niveles[$i]);
            if ($recordset AND $recordset->RowCount() == 1) {
                $error = false;
                $row = $recordset->FetchRow();
            }
        }
        if (!$error) {
            $pricing = new Pricing($row);
            developer_log("Costeando con el pricing: " . $pricing->get_id_pricing());
            return $pricing;
        }
        throw new Exception("Error al encontrar pricing: $id_pagcd para id_mp $id_mp");
        return false;
    }

//    protected function obtener_max_pricing_id($id_pagcd,$id_mp=false){
//        if($id_mp!=false){
//		$recordset= Pricing::select_max_id_pricing_mp($id_pagcd,$id_mp);
//        	if($recordset->rowCount()>1){
//	     		throw new Exception("Mas de un pricing no se costea.");
//	        }
//	}
//	else{
//	     $recordset= Pricing::select_max_id_pricing($id_pagcd);
//	      if($recordset->rowCount()>1){
//                 throw new Exception("Mas de un pricing no se costea.");
//              }
//	}
//        $row=$recordset->fetchRow();
//        $id_pricing=$row["id_pricing"];
//        return $id_pricing;
//    }

    public function debe_procesar_costo_asociado($id_mp) {
        # WhiteList
        $debe_procesar_costo_asociado = array(Mp::DEBITO_AUTOMATICO_REVERSO, Mp::RAPIPAGO, Mp::PAGOFACIL, Mp::PROVINCIAPAGO, Mp::COBROEXPRESS, Mp::RIPSA, Mp::MULTIPAGO, Mp::BICA, Mp::PRONTOPAGO);
        return in_array($id_mp, $debe_procesar_costo_asociado);
    }

//    protected function debe_procesar_comisiones($id_mp) {
//        # BlackList
//        $no_debe_procesar_comisiones = array(Mp::COBRODIGITAL_COMISION);
//        if (in_array($id_mp, $no_debe_procesar_comisiones) !== false) {
//            return false;
//        }
//        return true;
//    }

    protected function debe_procesar_subcomisiones($id_mp) {
        # WhiteList
        $debe_procesar_subcomisiones = array(Mp::RAPIPAGO, Mp::PAGOFACIL, Mp::PROVINCIAPAGO,
            Mp::COBROEXPRESS, Mp::RIPSA, Mp::MULTIPAGO,
            Mp::PAGOMISCUENTAS, Mp::BICA, Mp::PRONTOPAGO,
            Mp::LINKPAGOS, Mp::DEBITO_AUTOMATICO, Mp::MERCADOPAGO);

        if (in_array($id_mp, $debe_procesar_subcomisiones) !== false) {
            return true;
        }
        return false;
    }

    # Llama a $this->crear() # Recursiva

    protected function procesar_costo_asociado($moves, Sabana $sabana = null, $traslada_comision = false) {
        $id_marchand = $moves->get_id_marchand();
        $id_mp = $this->deducir_id_mp_para_costo_asociado($moves->get_id_mp());
        $id_referencia = $this->deducir_id_referencia_para_costo_asociado($id_mp, $moves);
        $monto_pagador = $moves->get_monto_pagador(); # Los costos asociados son registros extra que solo tienen pricing
        if ($id_mp == Mp::DEBITO_AUTOMATICO_COSTO_REVERSO)
            $monto_pagador = 0;
        $fecha = new Datetime('now');
        return $this->crear($id_marchand, $id_mp, $monto_pagador, $fecha, $id_referencia, $sabana, null, false, false, $traslada_comision);
    }

    # Llama a $this->crear() # Recursiva

    private function procesar_subcomisiones(Moves $moves, $mp, Sabana $sabana = null, $traslada_comision = false) {


        if ($this->debe_procesar_subcomisiones($moves->get_id_mp())) {
            $this->developer_log('La transacción procesa subcomisiones. ');
            $subcomisiones = $this->obtener_subcomisiones($moves->get_id_marchand());
            if (count($subcomisiones) == 0) {
                $this->developer_log("No hay subcomisiones que procesar. ");
                return true;
            }
            $this->developer_log('Procesando subcomisiones para la transacción.');

            foreach ($subcomisiones as $subcomision) {
                # var y fix ya vienen en importes absolutos
                $var = $subcomision['var'];
                $fix = $subcomision['fix'];
                $id_marchand = $subcomision['idm'];

                if ((is_numeric($var) AND is_numeric($fix)) AND ( is_numeric($id_marchand))) {
                    # Las comisiones se redondean para abajo -_-
                    $monto_pagador = round($fix + (($var * $moves->get_monto_cd()) / 100), 2, PHP_ROUND_HALF_DOWN);
                    $fecha = new Datetime('now');
                    $id_mp = Mp::COBRODIGITAL_COMISION;
                    if ($monto_pagador == 0) {
                        $this->developer_log('La subcomisión es cero.');
                    } elseif ($id_marchand == 208) {
                        $this->developer_log('Salteando comision de Jorge. Temporal.');
                    } else {
                        if (!$this->crear($id_marchand, $id_mp, $monto_pagador, $fecha, $moves->get_id(), null, null, false, false, $traslada_comision)) {
                            $this->developer_log('Ha ocurrido un error al procesar una subcomision del idm:' . $moves->get_id_marchand() . ' para el idm:' . $id_marchand . '. ');
                            return false;
                        } else {
                            $this->developer_log('Subcomisión procesada del idm:' . $moves->get_id_marchand() . ' para el idm:' . $id_marchand . ' .');
                        }
                    }
                }
            }
            return true;
        }
        $this->developer_log('La transaccion no procesa subcomisiones.');
        return true;
    }

    public function obtener_subcomisiones($id_marchand) {
        $configuracion = new Configuracion();
        $fix = $configuracion::obtener_config_tag_concepto(Configuracion::CONFIG_COMISIONA_FIX, $id_marchand);
        $var = $configuracion::obtener_config_tag_concepto(Configuracion::CONFIG_COMISIONA_VAR, $id_marchand);
        $idm = $configuracion::obtener_config_tag_concepto(Configuracion::CONFIG_COMISIONA_IDM, $id_marchand);

        $array_subcomisiones = array();
        if ((isset($idm["habilitado"]) and $idm["habilitado"] == 't') and isset($idm["value"])) {
            if (!isset($var["value"])) {
                $var = 0;
            } else
                $var = $var["value"];

            if (!isset($fix["value"]))
                $fix = 0;
            else
                $fix = $fix["value"];
            $idm_comision = $idm["value"];
            if ($var != 0 OR $fix != 0)
                $array_subcomisiones[] = array("var" => $var, "fix" => $fix, "idm" => $idm_comision);
        }
        return $array_subcomisiones;
    }

    public function obtener_fecha_de_liquidacion($fecha, $id_mp, $id_marchand,Sabana $sabana=null) {
        if (!$this->optimizar_mp($id_mp)) {
            return false;
        }
        $configuracion = new Configuracion();
        $config = $configuracion->obtener_configuracion($id_marchand);
        $array = array(
            Mp::RAPIPAGO => Configuracion::CONFIG_DIAS_PLUS_RAPIPAGO,
            Mp::PAGOFACIL => $this->obtener_dias_plus_pf($sabana),
            Mp::PROVINCIAPAGO => Configuracion::CONFIG_DIAS_PLUS_PROVINCIA_PAGOS,
            Mp::COBROEXPRESS => Configuracion::CONFIG_DIAS_PLUS_COBROEXPRESS,
            Mp::RIPSA => Configuracion::CONFIG_DIAS_PLUS_RIPSA,
            Mp::MULTIPAGO => Configuracion::CONFIG_DIAS_PLUS_MULTIPAGO,
            Mp::PAGOMISCUENTAS => Configuracion::CONFIG_DIAS_PLUS_PAGOMISCUENTAS,
            Mp::BICA => Configuracion::CONFIG_DIAS_PLUS_BICA,
            Mp::PRONTOPAGO => Configuracion::CONFIG_DIAS_PLUS_PRONTOPAGO,
            Mp::LINKPAGOS => Configuracion::CONFIG_DIAS_PLUS_LINKPAGOS,
            Mp::DEBITO_AUTOMATICO => Configuracion::CONFIG_DIAS_PLUS_DEBITO,
            Mp::TARJETA_DE_CREDITO => Configuracion::CONFIG_DIAS_PLUS_TARJETA,
            Mp::RETIROS => Configuracion::CONFIG_DIAS_PLUS_RETIRO_TRANSFERENCIA,
            Mp::ADELANTOS_TRANSFERENCIA => Configuracion::CONFIG_DIAS_PLUS_ADELANTO_TRANSFERENCIA,
            Mp::TELERECARGAS=> Configuracion::CONFIG_DIAS_PLUS_TELERECARGAS    
        );
        if (isset($array[self::$mp->get_id()])) {
            $dias_plus = $config[Entidad::ENTIDAD_MP][Configuracion::CONFIG_DIAS_LIQ][$array[self::$mp->get_id()]]['value'];
        } else
            $dias_plus = 0;

//var_dump(self::$mp->get_id());
//	var_dump($config[Entidad::ENTIDAD_MP][Configuracion::CONFIG_DIAS_LIQ][$array[self::$mp->get_id()]]);
        return Calendar::sumar_dias_habiles($fecha, $dias_plus);
    }
    private function obtener_dias_plus_pf(Sabana $sabana = null){
        if($sabana==null)
            return Configuracion::CONFIG_DIAS_PLUS_PAGOFACIL;
        $local_pf=new Local_pf();
        $local_pf->get($sabana->get_id_local_pf());
        if($local_pf->get_is_capital()){
            $this->developer_log("CAPITAL");
            return Configuracion::CONFIG_DIAS_PLUS_PAGOFACIL_CAPITAL;
        }
        if($local_pf->get_is_gba()){
            $this->developer_log("GBA");
            return Configuracion::CONFIG_DIAS_PLUS_PAGOFACIL_GBA;
        }
        if($local_pf->get_is_interior()){
            $this->developer_log("INTERIOR");
            return Configuracion::CONFIG_DIAS_PLUS_PAGOFACIL_INTERIOR;
        }
        return Configuracion::CONFIG_DIAS_PLUS_PAGOFACIL;
    }

    public static function id_mp_reverso($id_mp) {
        $transaccion = new Transaccion();
        developer_log($id_mp);
        return $transaccion->deducir_id_mp_para_reverso($id_mp);
    }

    protected function deducir_id_mp_para_reverso($id_mp) {
        if (!$this->optimizar_mp($id_mp)) {
            $this->developer_log('Ha ocurrido un error al optimizar el Mp para reversar.');
            return false;
        }
        $id_mp_reverso = false;
        switch ($id_mp) {
            case Mp::RETENCION_IMPOSITIVA_IVA:
                $id_mp_reverso=Mp::DEVOLUCION_RETENCION_IMPOSITIVA ;
                break;
            case Mp::RETENCION_IMPOSITIVA_GANANCIAS:
                $id_mp_reverso=Mp::DEVOLUCION_RETENCION_IMPOSITIVA ;
                break;
            case Mp::DEBITO_AUTOMATICO_CBU:
                $id_mp_reverso = Mp::DEBITO_AUTOMATICO_REVERSO;
                break;
            case Mp::DEBITO_AUTOMATICO_REVERSO:
                $id_mp_reverso = Mp::DEBITO_AUTOMATICO_CBU;
                break;
            case Mp::COSTO_RAPIPAGO:
            case Mp::COSTO_PAGO_FACIL:
            case Mp::COSTO_PROVINCIA_PAGO:
            case Mp::COSTO_COBRO_EXPRESS:
            case Mp::COSTO_RIPSA:
            case Mp::COSTO_MULTIPAGO:
            case Mp::COSTO_BICA:
            case Mp::COSTO_PRONTO_PAGO:
                $id_mp_reverso = Mp::REVERSO_COSTO_EFECTIVO;
                break;
            case Mp::PAGO_PROVEEDOR_PENDIENTE:
                $id_mp_reverso = Mp::REVERSO_RESERVA_PROVEEDOR;
                break;
            case Mp::PAGO_A_PROVEEDOR:
                $id_mp_reverso = Mp::REVERSO_PAGO_PROVEEDOR;
                break;
            case Mp::RETIROS_CHEQUE_POR_SUCURSAL:
                $id_mp_reverso = Mp::REVERSO_CHEQUE_POR_SUCURSAL;
                break;
            default:
                switch (self::$mp->get_sentido_transaccion()) {
                    case Mp::SENTIDO_TRANSACCION_INGRESO:
                        $id_mp_reverso = Mp::REVERSO_DE_INGRESO;
                        break;
                    case Mp::SENTIDO_TRANSACCION_EGRESO:
                        $id_mp_reverso = Mp::REVERSO_DE_EGRESO;
                        break;
                    case Mp::SENTIDO_TRANSACCION_REVERSO_DE_INGRESO:
                        $id_mp_reverso = Mp::NOTA_DE_CREDITO;
                        break;
                    case Mp::SENTIDO_TRANSACCION_REVERSO_DE_EGRESO:
                        $id_mp_reverso = Mp::NOTA_DE_DEBITO;
                        break;
                    default:
                        $id_mp_reverso = false;
                        break;
                }
                break;
        }
        return $id_mp_reverso;
    }

    protected function deducir_id_mp_para_costo_asociado($id_mp) {
        $id_mp_costo_asociado = false;
        switch ($id_mp) {
            case Mp::DEBITO_AUTOMATICO_REVERSO: $id_mp_costo_asociado = Mp::DEBITO_AUTOMATICO_COSTO_REVERSO;
                break;
            case Mp::RAPIPAGO: $id_mp_costo_asociado = Mp::COSTO_RAPIPAGO;
                break;
            case Mp::PAGOFACIL: $id_mp_costo_asociado = Mp::COSTO_PAGO_FACIL;
                break;
            case Mp::PROVINCIAPAGO: $id_mp_costo_asociado = Mp::COSTO_PROVINCIA_PAGO;
                break;
            case Mp::COBROEXPRESS: $id_mp_costo_asociado = Mp::COSTO_COBRO_EXPRESS;
                break;
            case Mp::RIPSA: $id_mp_costo_asociado = Mp::COSTO_RIPSA;
                break;
            case Mp::MULTIPAGO: $id_mp_costo_asociado = Mp::COSTO_MULTIPAGO;
                break;
            case Mp::BICA: $id_mp_costo_asociado = Mp::COSTO_BICA;
                break;
            case Mp::PRONTOPAGO: $id_mp_costo_asociado = Mp::COSTO_PRONTO_PAGO;
                break;
        }
        return $id_mp_costo_asociado;
    }

    protected function deducir_id_referencia_para_costo_asociado($id_mp, Moves $moves) {
        $this->developer_log("Deduciendo referencia para el COSTO MP " . $id_mp);
        switch ($id_mp) {
            case Mp::COSTO_RAPIPAGO:
            case Mp::COSTO_PAGO_FACIL:
            case Mp::COSTO_PROVINCIA_PAGO:
            case Mp::COSTO_COBRO_EXPRESS:
            case Mp::COSTO_RIPSA:
            case Mp::COSTO_MULTIPAGO:
            case Mp::COSTO_BICA:
            case Mp::COSTO_PRONTO_PAGO:
            case Mp::DEBITO_AUTOMATICO_COSTO_REVERSO:
                $config = Configuracion::obtener_configuracion($moves->get_id_marchand());
                if (isset($config[Entidad::ENTIDAD_COSTEADOR]) and $config[Entidad::ENTIDAD_COSTEADOR][Configuracion::CONFIG_COSTEADOR][Configuracion::CONFIG_REFERENCIA_COSTO_EFECTIVO]["field"] == "barcode") {
                    $this->developer_log("Referencia: SETEADA POR CONFIG MARCHAND DEL IDM" . $moves->get_id_marchand());
                    $id_referencia = $moves->get_id_referencia();
                    break;
                } else {
                    $this->developer_log("Referencia: id_moves del Mp " . $id_mp);
                    $id_referencia = $moves->get_id();
                    break;
                }
            default :
                $this->developer_log("Referencia: Default del Mp " . $id_mp);
                $id_referencia = $moves->get_id_referencia();
                break;
        }
        return $id_referencia;
    }

    public function encontrar_transaccion_a_reversar_debito(Debito_cbu $debito, $id_mp = false) {

        $id_mp2 = $this->deducir_id_mp_para_reverso($id_mp);
        if (!$id_mp)
            $array = array('id_marchand' => $debito->get_id_marchand(), 'id_entidad' => Entidad::ENTIDAD_DEBITO_CBU, 'id_referencia' => $debito->get_id_debito());
        else
            $array = array('id_marchand' => $debito->get_id_marchand(), 'id_entidad' => Entidad::ENTIDAD_DEBITO_CBU, 'id_referencia' => $debito->get_id_debito(), 'id_mp' => $id_mp2);

        $recordset = Moves::select_transaccion_a_reversar_fifo($array);
        if (!$recordset) {
            $this->developer_log('Error en la consulta de búsqueda de la transacción a reversar.');
        } elseif ($recordset->RowCount() === 0) {
            $this->developer_log('No existe la transacción a reversar para debito.');
        } elseif ($recordset->RowCount() > 1) {
            $this->developer_log('Hay mas de una transacción a reversar que coincide con la busqueda.');
        } elseif ($recordset->RowCount() == 1) {
            $this->developer_log('Transacción a reversar encontrada.');
            $moves_original = new Moves($recordset->FetchRow());
            return $moves_original;
        }
        return false;
    }

    public function encontrar_transaccion_a_reversar(Barcode $barcode, $id_mp = false) {
        $id_mp2 = $this->deducir_id_mp_para_reverso($id_mp);
        if (!$id_mp)
            $array = array('id_marchand' => $barcode->get_id_marchand(), 'id_entidad' => Entidad::ENTIDAD_BARCODE, 'id_referencia' => $barcode->get_id_barcode());
        else
            $array = array('id_marchand' => $barcode->get_id_marchand(), 'id_entidad' => Entidad::ENTIDAD_BARCODE, 'id_referencia' => $barcode->get_id_barcode(), 'id_mp' => $id_mp2);

        $recordset = Moves::select_transaccion_a_reversar_fifo($array);
        if (!$recordset) {
            $this->developer_log('Error en la consulta de búsqueda de la transacción a reversar.');
        } elseif ($recordset->RowCount() === 0) {
            $this->developer_log('No existe la transacción a reversar para barcode.');
        } elseif ($recordset->RowCount() > 1) {
            $this->developer_log('Hay mas de una transacción a reversar que coincide con la busqueda.');
        } elseif ($recordset->RowCount() == 1) {
            $this->developer_log('Transacción a reversar encontrada.');
            $moves_original = new Moves($recordset->FetchRow());
            return $moves_original;
        }
        return false;
    }

    public static function deducir_id_entidad($id_mp, $id_marchand = false, Transas $transas = null, Sabana $sabana = null,$id_mp_rev=false) {
        $id_entidad = false;
        error_log("ENTIDAD PARA ID_MP:$id_mp");
        switch ($id_mp) {
            case Mp::COBRO_COBRADORES:
                $id_entidad = Entidad::ENTIDAD_COBROS_COBRADOR;
                break;
            case Mp::REVERSO_RESERVA_PROVEEDOR:
                $id_entidad = Entidad::ENTIDAD_PROVEEDOR_PENDIENTE;
                break;
            case Mp::DEVOLUCION_RETENCION_IMPOSITIVA:
                $id_entidad = Entidad::ENTIDAD_MOVES;
                break;
            case Mp::RAPIPAGO:
            case Mp::PAGOFACIL:
            case Mp::PROVINCIAPAGO:
            case Mp::RIPSA:
            case Mp::BICA:
            case Mp::PRONTOPAGO:
            case Mp::LINKPAGOS:
            case Mp::COBROEXPRESS:
            case Mp::MULTIPAGO:
            case Mp::PAGOMISCUENTAS:
            case Mp::PAGOS_COBRODIGITAL:
            case Mp::TELERECARGAS:
                $id_entidad = Entidad::ENTIDAD_BARCODE;
                break;
            case Mp::TARJETA_DE_CREDITO:
                if (isset($transas))
                    $id_entidad = $transas->get_id_entidad();
                else
                    $id_entidad = Entidad::ENTIDAD_BARCODE;
                break;
            case Mp::COSTO_RAPIPAGO:
            case Mp::COSTO_PAGO_FACIL:
            case Mp::COSTO_PROVINCIA_PAGO:
            case Mp::COSTO_COBRO_EXPRESS:
            case Mp::COSTO_RIPSA:
            case Mp::COSTO_MULTIPAGO:
            case Mp::COSTO_BICA:
            case Mp::COSTO_PRONTO_PAGO:
            case Mp::DEBITO_AUTOMATICO_REVERSO:
            case Mp::DEBITO_AUTOMATICO_COSTO_REVERSO:
                $config = Configuracion::obtener_configuracion($id_marchand);
                if (isset($config[Entidad::ENTIDAD_COSTEADOR]) and $config[Entidad::ENTIDAD_COSTEADOR][Configuracion::CONFIG_COSTEADOR][Configuracion::CONFIG_REFERENCIA_COSTO_EFECTIVO]["field"] == "barcode") {
                    developer_log("Referencia: SETEADA POR CONFIG MARCHAND DEL IDM" . $id_marchand);
                    $id_entidad = Entidad::ENTIDAD_BARCODE;
                    break;
                } else {
//                    $this->developer_log("Referencia: id_moves del Mp " . $id_mp);
                    $id_entidad = Entidad::ENTIDAD_MOVES;
                    break;
                }
            case Mp::COBRODIGITAL_DEC: # Estaba en barcode
            case Mp::COBRODIGITAL_COMISION:
            case Mp::REVERSO_COSTO_EFECTIVO:
            case Mp::REVERSO_RETENCION_IMPOSITIVA:
            case MP::RECEPCION_TRANSFERENCIA_ENTRE_CUENTAS:
                $id_entidad = Entidad::ENTIDAD_MOVES;
                break;
            case Mp::REVERSO_DE_INGRESO:
            case Mp::REVERSO_DE_EGRESO:
                developer_log("--------------___>".$id_mp_rev);
                if($id_mp_rev and in_array($id_mp_rev,array(Mp::PAGO_A_PROVEEDOR,Mp::COBRO_COMO_PROVEEDOR))){
                    developer_log("entidad MOVES");
                    $id_entidad = Entidad::ENTIDAD_MOVES;
                }else{
                    developer_log("entidad barcode");
                    $id_entidad = Entidad::ENTIDAD_BARCODE;
                }
                break;
            case Mp::NOTA_DE_CREDITO:
            case Mp::NOTA_DE_DEBITO:
            case Mp::RESPUESTAS_REQUERIMIENTOS:
            case Mp::TARJETAS_DE_COBRO:
            case Mp::APERTURA_DE_CUENTA:
            case Mp::MANTENIMIENTO_DE_CUENTA:
            case Mp::ACREDITACION_DE_FONDOS:
            case Mp::RETIROS_CHEQUE:
            case Mp::RETIROS_CHEQUE_POR_CORREO:
            case Mp::RETIROS_CHEQUE_POR_SUCURSAL:
            case Mp::REVERSO_CHEQUE_POR_SUCURSAL:
            case Mp::RENDICION_COBRANZA:
            case Mp::CORRECCION_DE_COMISIONES:
            case Mp::AJUSTE_DE_COMISIONES:
            case Mp::DEVOLUCION_RETENCION_IMPOSITIVA:
                $id_entidad = Entidad::ENTIDAD_MARCHAND;
                break;
            case Mp::RETIROS:
            case Mp::RETIROS_CHEQUE:
                $id_entidad = Entidad::ENTIDAD_CBUMARCHAND;
                break;
            case Mp::PAGO_A_PROVEEDOR:
            case Mp::COBRO_COMO_PROVEEDOR:
            case Mp::TRANSFERENCIA_ENTRE_CUENTAS:
	    case Mp::REVERSO_PAGO_PROVEEDOR:
                $id_entidad = Entidad::ENTIDAD_MARCHAND;
                break;
            case Mp::PAGO_PROVEEDOR_PENDIENTE:
                $id_entidad = Entidad::ENTIDAD_PROVEEDOR;
                break;
            case Mp::DEBITO_AUTOMATICO:
            case Mp::DEBITO_AUTOMATICO_COSTO_RECHAZO:

                //revisar esto ya que algunos reversos se estan guardando con la entidad equivocada (solo hasta que no lleguen mas debitos con barcode)
                $id_entidad = Entidad::ENTIDAD_DEBITO_CBU;
                break;
            case Mp::PAGO_DE_SERVICIOS:
            case Mp::COBRO_DE_SERVICIOS:
                $id_entidad = Entidad::ENTIDAD_SERVICIOS_PAGO;
                break;
            case Mp::CASHOUT:
                $id_entidad = Entidad::ENTIDAD_CASHOUT_BARRAS;
                break;
            case Mp::PRESTAMO_DIGITAL_ACREDITACION:
            case Mp::PRESTAMO_DIGITAL_DEBITO:
            case Mp::PRESTAMO_DIGITAL_COMPENSACION_CREDITO:

            case Mp::PRESTAMO_DIGITAL_COMPENSACION_DEBITO:
                $id_entidad = Entidad::ENTIDAD_PRESTAMOS;
                break;
            case Mp::COSTO_DECIDIR_DEVOLUCION:
            case Mp::COSTO_PEI_DEVOLUCION:
                $id_entidad = Entidad::ENTIDAD_MOVES;
                break;
        }
        error_log("ENTIDAD_ENCONTRADA $id_entidad");
        return $id_entidad;
    }

    protected function deducir_id_tipomove($id_mp) {
        $id_tipomove = false;
        switch ($id_mp) {
            case Mp::RAPIPAGO:
            case Mp::PAGOFACIL:
            case Mp::PROVINCIAPAGO:
            case Mp::RIPSA:
            case Mp::BICA:
            case Mp::PRONTOPAGO:
            case Mp::LINKPAGOS:
            case Mp::COBROEXPRESS:
            case Mp::MULTIPAGO:
            case Mp::DEBITO_AUTOMATICO_CBU:
            case Mp::PAGOMISCUENTAS:
            case Mp::PAGO_DE_SERVICIOS:
            case Mp::COBRO_DE_SERVICIOS:
            case Mp::TELERECARGAS:
            case Mp::PAGOS_COBRODIGITAL:
                $id_tipomove = Tipomove::COBRO_EN_EFECTIVO;
                break;
            case Mp::TARJETA_DE_CREDITO:
                $id_tipomove = Tipomove::COBRO_CON_TCO_ONLINE;
                break;
            case Mp::COBRODIGITAL_COMISION:
                $id_tipomove = Tipomove::COBRO_COMISION_RESELLER;
                break;
            case Mp::COBRODIGITAL_DEC:
                $id_tipomove = Tipomove::COBRO_CON_DINERO_EN_CUENTA;
                break;
            case Mp::DEBITO_AUTOMATICO_COSTO_RECHAZO:
                $id_tipomove = Tipomove::PAGO_COMISION_POR_RECHAZO_DEBITO_AUTOMATICO;
                break;
            case Mp::APERTURA_DE_CUENTA:
                $id_tipomove = Tipomove::APERTURA_DE_CUENTA;
                break;
            case Mp::MANTENIMIENTO_DE_CUENTA:
            case Mp::CORRECCION_DE_COMISIONES:
            case Mp::AJUSTE_DE_COMISIONES:
            case Mp::DEVOLUCION_RETENCION_IMPOSITIVA:
                $id_tipomove = Tipomove::MANTENIMIENTO_DE_CUENTA;
                break;
            case Mp::COSTO_RAPIPAGO:
            case Mp::COSTO_PAGO_FACIL:
            case Mp::COSTO_PROVINCIA_PAGO:
            case Mp::COSTO_COBRO_EXPRESS:
            case Mp::COSTO_RIPSA:
            case Mp::COSTO_MULTIPAGO:
            case Mp::COSTO_BICA:
            case Mp::COSTO_PRONTO_PAGO:
            case Mp::COBRO_COBRADORES:
                $id_tipomove = Tipomove::COSTO_COBRO_EFECTIVO;
                break;
            case Mp::TARJETAS_DE_COBRO:
                $id_tipomove = Tipomove::TARJETAS_DE_COBRO;
                break;
            case Mp::ACREDITACION_DE_FONDOS:
                $id_tipomove = Tipomove::CREDITO;
                break;
            case Mp::CASHOUT:
                $id_tipomove = Tipomove::NOTA_DE_DEBITO;
                break;
            case Mp::NOTA_DE_CREDITO:
                $id_tipomove = Tipomove::NOTA_DE_CREDITO;
                break;
            case Mp::NOTA_DE_DEBITO:
                $id_tipomove = Tipomove::NOTA_DE_DEBITO;
                break;
            case Mp::RESPUESTAS_REQUERIMIENTOS:
                $id_tipomove = Tipomove::NOTA_DE_DEBITO;
                break;
            case Mp::DEBITO_AUTOMATICO_COSTO_REVERSO:
                $id_tipomove = Tipomove::PAGO_COMISION_POR_REVERSO_DEBITO_AUTOMATICO;
                break;
            case Mp::DEBITO_AUTOMATICO_REVERSO:
                $id_tipomove = Tipomove::REVERSO_DE_DEBITO_AUTOMATICO;
                break;
            case Mp::REVERSO_DE_EGRESO:
            case Mp::REVERSO_RESERVA_PROVEEDOR:
            case Mp::REVERSO_RETENCION_IMPOSITIVA:
                $id_tipomove = Tipomove::REVERSO_EGRESO;
                break;
            case Mp::REVERSO_DE_INGRESO:
                $id_tipomove = Tipomove::REVERSO_INGRESO;
                break;
            case Mp::REVERSO_COSTO_EFECTIVO:
                $id_tipomove = Tipomove::COSTO_REVERSO_EFECTIVO;
                break;
            case Mp::RETIROS:
                $id_tipomove = Tipomove::TRANSFERENCIA_BANCARIA;
                break;
            case Mp::RETIROS_CHEQUE:
            case Mp::RETIROS_CHEQUE_POR_CORREO:
            case Mp::RETIROS_CHEQUE_POR_SUCURSAL:
            case Mp::REVERSO_CHEQUE_POR_SUCURSAL:
                $id_tipomove = Tipomove::CHEQUE;
                break;
            case Mp::PAGO_A_PROVEEDOR:
            case Mp::PAGO_PROVEEDOR_PENDIENTE:
            case MP::PAGO_A_PROVEEDOR:
            case Mp::RENDICION_COBRANZA:
            case Mp::PRESTAMO_DIGITAL_ACREDITACION:
            case Mp::PRESTAMO_DIGITAL_DEBITO:
            case Mp::PRESTAMO_DIGITAL_COMPENSACION_CREDITO:
            case Mp::PRESTAMO_DIGITAL_COMPENSACION_DEBITO:
            case Mp::TRANSFERENCIA_ENTRE_CUENTAS:
            case Mp::REVERSO_PAGO_PROVEEDOR:
                $id_tipomove = Tipomove::PAGO_A_PROVEEDOR;
                break;
            case MP::COBRO_COMO_PROVEEDOR:
            case Mp::RECEPCION_TRANSFERENCIA_ENTRE_CUENTAS:
                $id_tipomove = Tipomove::COBRO_COMO_PROVEEDOR;
                break;
        }
        return $id_tipomove;
    }

    protected function deducir_id_authstat($id_mp) {
        $id_authstat = false;
        switch ($id_mp) {
            case Mp::RAPIPAGO:
            case Mp::PAGOFACIL:
            case Mp::PROVINCIAPAGO:
            case Mp::COBROEXPRESS:
            case Mp::RIPSA:
            case Mp::MULTIPAGO:
            case Mp::PAGOMISCUENTAS:
            case Mp::BICA:
            case Mp::PRONTOPAGO:
            case Mp::LINKPAGOS:
            case Mp::DEBITO_AUTOMATICO_CBU:
            case Mp::COBRODIGITAL_DEC:
            case Mp::COBRO_COMO_PROVEEDOR:
            case Mp::PAGO_A_PROVEEDOR:
            case MP::COBRO_COMO_PROVEEDOR:
            case Mp::PAGOS_COBRODIGITAL:
            case Mp::PAGO_PROVEEDOR_PENDIENTE:
            case Mp::RENDICION_COBRANZA:
            case Mp::PAGO_DE_SERVICIOS:
            case Mp::COBRO_DE_SERVICIOS:
            case Mp::COBRO_COBRADORES:
            case Mp::PRESTAMO_DIGITAL_ACREDITACION:
            case Mp::PRESTAMO_DIGITAL_DEBITO:
            case Mp::PRESTAMO_DIGITAL_COMPENSACION_CREDITO:
            case Mp::PRESTAMO_DIGITAL_COMPENSACION_DEBITO:
            case Mp::DEVOLUCION_RETENCION_IMPOSITIVA:
            case Mp::TELERECARGAS:
                $id_authstat = Authstat::TRANSACCION_COBRADO;
                break;
            case Mp::DEBITO_AUTOMATICO_COSTO_RECHAZO:

                $id_authstat = Authstat::TRANSACCION_COBRO_COMISION_RECHAZO;
            case Mp::COBRODIGITAL_COMISION:
            case Mp::CORRECCION_DE_COMISIONES:
            case Mp::AJUSTE_DE_COMISIONES:
                $id_authstat = Authstat::TRANSACCION_PAGADA;
            case Mp::NOTA_DE_CREDITO:
            case Mp::NOTA_DE_DEBITO:
            case Mp::RESPUESTAS_REQUERIMIENTOS:
            case Mp::APERTURA_DE_CUENTA:
            case Mp::MANTENIMIENTO_DE_CUENTA:
            case Mp::TARJETAS_DE_COBRO:
            case Mp::ACREDITACION_DE_FONDOS:
            case Mp::TRANSFERENCIA_ENTRE_CUENTAS:
            case Mp::RECEPCION_TRANSFERENCIA_ENTRE_CUENTAS:
                $id_authstat = Authstat::TRANSACCION_REALIZADO;
                break;
            case Mp::DEBITO_AUTOMATICO_COSTO_REVERSO:
                $id_authstat = Authstat::TRANSACCION_COBRO_COMISION;
                break;
            case Mp::COSTO_RAPIPAGO:
            case Mp::COSTO_PAGO_FACIL:
            case Mp::COSTO_PROVINCIA_PAGO:
            case Mp::COSTO_COBRO_EXPRESS:
            case Mp::COSTO_RIPSA:
            case Mp::COSTO_MULTIPAGO:
            case Mp::COSTO_BICA:
            case Mp::COSTO_PRONTO_PAGO:
                $id_authstat = Authstat::TRANSACCION_COBRO_COMISION_COSTO;
                break;
            case Mp::REVERSO_DE_INGRESO:
            case Mp::REVERSO_DE_EGRESO:
            case Mp::REVERSO_COSTO_EFECTIVO:
            case Mp::DEBITO_AUTOMATICO_REVERSO:
            case Mp::REVERSO_RESERVA_PROVEEDOR:
            case Mp::REVERSO_CHEQUE_POR_SUCURSAL:
            case Mp::REVERSO_RETENCION_IMPOSITIVA:
            case Mp::REVERSO_PAGO_PROVEEDOR:
                $id_authstat = Authstat::TRANSACCION_CANCELACION_REALIZADA;
                break;
            case Mp::RETIROS:
            case Mp::RETIROS_CHEQUE_POR_SUCURSAL:
                $id_authstat = Authstat::TRANSACCION_RETIRO_PENDIENTE;
                break;
            case Mp::RETIROS_CHEQUE:
            case Mp::RETIROS_CHEQUE_POR_CORREO:
            case Mp::CASHOUT:
                $id_authstat = Authstat::TRANSACCION_RETIRO_COMPLETADO;
                break;
            case Mp::TARJETA_DE_CREDITO:
                $id_authstat = Authstat::TRANSACCION_VERIFICADA;
                break;
        }
        return $id_authstat;
    }

    protected function optimizar_mp($id_mp) {
        if (!self::$mp OR self::$mp->get_id_mp() !== $id_mp) {
            self::$mp = new Mp();
            developer_log($id_mp);
            return self::$mp->get($id_mp);
        }
        return self::$mp;
    }

    protected function optimizar_tipomove($id_tipomove) {
        if (!self::$tipomove OR self::$tipomove->get_id_tipomove() !== $id_tipomove) {
            self::$tipomove = new Tipomove();
            return self::$tipomove->get($id_tipomove);
        }
        return self::$tipomove;
    }

    public function validar(Moves $moves) {
        # Esta funcion podria ser mejor -_-
        $this->developer_log('Validando Transaccion. ');

        if (self::LIMITE_INFERIOR_MONTO_PAGADOR !== false and $moves->get_id_mp() != Mp::REVERSO_DE_EGRESO) {
            if ($moves->get_monto_pagador() < self::LIMITE_INFERIOR_MONTO_PAGADOR) {
                $this->developer_log("Error. La transacción no cumple con el límite inferior en el campo 'monto_pagador'. ");
                return false;
            }
        }
        if (self::LIMITE_SUPERIOR_MONTO_PAGADOR !== false and $moves->get_id_mp() != Mp::REVERSO_DE_EGRESO) {
            if ($moves->get_monto_pagador() > self::LIMITE_SUPERIOR_MONTO_PAGADOR) {
                $this->developer_log("Error. La transacción no cumple con el límite superior en el campo 'monto_pagador'. ");
                return false;
            }
        }
        if (self::LIMITE_INFERIOR_PAG_FIX !== false) {
            if ($moves->get_pag_fix() < self::LIMITE_INFERIOR_PAG_FIX) {
                $this->developer_log("Error. La transacción no cumple con el límite inferior en el campo 'pag_fix'. ");
                return false;
            }
        }
        if (self::LIMITE_SUPERIOR_PAG_FIX !== false) {
            if ($moves->get_pag_fix() > self::LIMITE_SUPERIOR_PAG_FIX) {
                $this->developer_log("Error. La transacción no cumple con el límite superior en el campo 'pag_fix'. ");
                return false;
            }
        }
        if (self::LIMITE_INFERIOR_PAG_VAR !== false) {
            if ($moves->get_pag_var() < self::LIMITE_INFERIOR_PAG_VAR) {
                $this->developer_log("Error. La transacción no cumple con el límite inferior en el campo 'pag_var'. ");
                return false;
            }
        }
        if (self::LIMITE_SUPERIOR_PAG_VAR !== false) {
            if ($moves->get_pag_var() > self::LIMITE_SUPERIOR_PAG_VAR) {
                $this->developer_log("Error. La transacción no cumple con el límite superior en el campo 'pag_var'. ");
                return false;
            }
        }
        if (self::LIMITE_INFERIOR_MONTO_CD !== false) {
            if ($moves->get_monto_cd() < self::LIMITE_INFERIOR_MONTO_CD) {
                $this->developer_log("Error. La transacción no cumple con el límite inferior en el campo 'monto_cd'. ");
                return false;
            }
        }
        if (self::LIMITE_SUPERIOR_MONTO_CD !== false) {
            if ($moves->get_monto_cd() > self::LIMITE_SUPERIOR_MONTO_CD) {
                $this->developer_log("Error. La transacción no cumple con el límite superior en el campo 'monto_cd'. ");
                return false;
            }
        }
        if (self::LIMITE_INFERIOR_CDI_FIX !== false and $moves->get_id_mp() != Mp::CORRECCION_DE_COMISIONES) {
            if ($moves->get_cdi_fix() < self::LIMITE_INFERIOR_CDI_FIX) {
                $this->developer_log("Error. La transacción no cumple con el límite inferior en el campo 'cdi_fix'. ");
                return false;
            }
        }
        if (self::LIMITE_SUPERIOR_CDI_FIX !== false) {
            if ($moves->get_cdi_fix() > self::LIMITE_SUPERIOR_CDI_FIX) {
                $this->developer_log("Error. La transacción no cumple con el límite superior en el campo 'cdi_fix'. ");
                return false;
            }
        }
        if (self::LIMITE_INFERIOR_CDI_VAR !== false) {
            if ($moves->get_cdi_var() < self::LIMITE_INFERIOR_CDI_VAR) {
                $this->developer_log("Error. La transacción no cumple con el límite inferior en el campo 'cdi_var'. ");
                return false;
            }
        }
        if (self::LIMITE_SUPERIOR_CDI_VAR !== false) {
            if ($moves->get_cdi_var() > self::LIMITE_SUPERIOR_CDI_VAR) {
                $this->developer_log("Error. La transacción no cumple con el límite superior en el campo 'cdi_var'. ");
                return false;
            }
        }
        if (self::LIMITE_INFERIOR_MONTO_MARCHAND !== false) {
            if ($moves->get_monto_marchand() < self::LIMITE_INFERIOR_MONTO_MARCHAND) {
                $this->developer_log("Error. La transacción no cumple con el límite inferior en el campo 'monto_marchand'. ");
                return false;
            }
        }
        if (self::LIMITE_SUPERIOR_MONTO_MARCHAND !== false) {
            if ($moves->get_monto_marchand() > self::LIMITE_SUPERIOR_MONTO_MARCHAND) {
                $this->developer_log("Error. La transacción no cumple con el límite superior en el campo 'monto_marchand'. ");
                return false;
            }
        }
        if (self::LIMITE_INFERIOR_SALDO_MARCHAND !== false) {
            if ($moves->get_saldo_marchand() < self::LIMITE_INFERIOR_SALDO_MARCHAND) {
                $this->developer_log("Error. La transacción no cumple con el límite inferior en el campo 'saldo_marchand'. ");
                return false;
            }
        }
        if (self::LIMITE_SUPERIOR_SALDO_MARCHAND !== false) {
            if ($moves->get_saldo_marchand() > self::LIMITE_SUPERIOR_SALDO_MARCHAND) {
                $this->developer_log("Error. La transacción no cumple con el límite superior en el campo 'saldo_marchand'. ");
                return false;
            }
        }
        $this->developer_log("Transaccion Válida.");
        return true;
    }

    public static function signar($sentido_transaccion, $tipo_importe, $importe) {
        $signo = false;
        if (!is_numeric($importe)) {
            return false;
        }
        switch ($sentido_transaccion) {
            case Mp::SENTIDO_TRANSACCION_INGRESO:
                switch ($tipo_importe) {
                    case 'bruto': $signo = 1;
                        break;
                    case 'comision': $signo = -1;
                        break;
                    case 'neto': $signo = 1;
                        break;
                }
                break;
            case Mp::SENTIDO_TRANSACCION_EGRESO:
                switch ($tipo_importe) {
                    case 'bruto': $signo = -1;
                        break;
                    case 'comision': $signo = -1;
                        break;
                    case 'neto': $signo = -1;
                        break;
                }
                break;
            case Mp::SENTIDO_TRANSACCION_REVERSO_DE_EGRESO:
                switch ($tipo_importe) {
                    case 'bruto': $signo = 1;
                        break;
                    case 'comision': $signo = 1;
                        break;
                    case 'neto': $signo = 1;
                        break;
                }
                break;
            case Mp::SENTIDO_TRANSACCION_REVERSO_DE_INGRESO:
                switch ($tipo_importe) {
                    case 'bruto': $signo = -1;
                        break;
                    case 'comision': $signo = 1;
                        break;
                    case 'neto': $signo = -1;
                        break;
                }
                break;
        }
        if ($signo)
            return $signo * abs($importe);
        return false;
    }

    protected function developer_log($string) {
        $tabulacion = '';
        for ($i = 0; $i < self::$grado_de_recursividad - 1; $i++) {
            $tabulacion .= "+";
        }
        if ($tabulacion !== '') {
            $tabulacion .= " ";
        }
        if (self::ACTIVAR_DEBUG) {
            developer_log($tabulacion . $string);
        }
        $this->log[] = $tabulacion . $string;
    }

    public function obtener_semaforo($id_marchand) {
        $this->developer_log("TERMINA TRANSACCION SEMAFORO");
        Model::StartTrans();
        for ($i = 0; $i < self::INTENTOS_DE_OBTENER_SEMAFORO; $i++) {
            $this->developer_log("SE INTENTA OBTENER EL SEMAFORO: BLOQUEANDO TABLA PARA $id_marchand");
            $recordset = Semaforo_marchand::obtener_semaforo($id_marchand);
	    if(!$recordset)
		developer_log("SEMAFORO DEVUELVE FALSE");
	    else
       	        developer_log(json_encode($recordset->fetchRow()));
	    $recordset->move(0);
	    //exit();
            if ($recordset AND $recordset->RowCount() == 1) {
                
                $row = $recordset->fetchRow();
                $Semaforo= new Semaforo_marchand($row);
                # Solo actualizo nops
                $Semaforo->set_id_semaforo($row[0]);
		$Semaforo->set_id_marchand($id_marchand);
                $Semaforo->set_block(Semaforo_marchand::SEMAFORO_OCUPADO);
                if ($Semaforo->set() and Model::CompleteTrans()) {
                    $this->developer_log("TERMINA TRANSACCION SEMAFORO");
                    $this->developer_log("se termina el bloqueo: SEMAFORO MARCHAND OBTENIDO");
                    return $Semaforo;
                } else {
                    Model::failTrans();
                    $this->developer_log("TERMINA TRANSACCION SEMAFORO NO SE PUDO SETEAR");
                    Model::CompleteTrans();
                }
            }
            else {
                $recordset = Semaforo_marchand::select(array("id_marchand"=>$id_marchand));
                if($recordset and $recordset->rowCount()==0){
                    $row = $recordset->FetchRow();
                    $Semaforo= new Semaforo_marchand();
                    # Solo actualizo nops
                    $Semaforo->set_id_marchand($id_marchand);
                    $Semaforo->set_block(Semaforo_marchand::SEMAFORO_OCUPADO);
                    if ($Semaforo->set() and Model::CompleteTrans()) {
                        $this->developer_log("TERMINA CREACION SEMAFORO");
                        $this->developer_log("se termina el bloqueo: SEMAFORO MARCHAND OBTENIDO");
                        return $Semaforo;
                    } else {
                        Model::failTrans();
                        $this->developer_log("TERMINA CREAR SEMAFORO CON ERROR");
                        Model::CompleteTrans();
                    }
                }
            }
            usleep(self::MICROSEGUNDOS_DE_REINTENTO_SEMAFORO);
        }
        developer_log("se termina el bloqueo: SEMAFORO MARCHAND NO OBTENIDO");
        developer_log("TERMINA TRANSACCION SEMAFORO");
        Model::CompleteTrans();
        return false;
    }

    public function liberar_semaforo($semaforo) {
	if(!$semaforo)
		return true;
        if(get_class($semaforo)== "Semaforo_marchand"){
            $semaforo->set_block(Semaforo_marchand::SEMAFORO_LIBRE);
            if ($semaforo->set()) {
                return true;
            }
            return false;
        }
        return liberar_semaforo_marchand($semaforo);
    }
    public function liberar_semaforo_marchand(Marchand $marchand) {
        $marchand->set_nops(Marchand::SEMAFORO_LIBRE);
        if ($marchand->set()) {
            return true;
        }
        return false;
    }
    protected function validar_capital_disponible(Moves $moves) {
        $mp_que_validan_capital_disponible = array(Mp::RETIROS, Mp::RETIROS_CHEQUE, Mp::RETIROS_CHEQUE_POR_CORREO, Mp::PAGO_A_PROVEEDOR);
        if (in_array($moves->get_id_mp(), $mp_que_validan_capital_disponible)) {
            $row = Cliente::obtener_estado_de_cuenta($moves->get_id_marchand());
            $saldo_disponible = $row['saldo_disponible'];
            developer_log("VALIDANDO: ".$moves->get_monto_marchand() ." <= $saldo_disponible");
            if ($moves->get_monto_marchand() <= $saldo_disponible) {

                return true;
            }
//            var_dump($row);
//            exit();
//	    developer_log('monto');
//	    developer_log($moves->get_monto_marchand());
            developer_log('saldo disponible');
            developer_log($saldo_disponible);
            return false;
        }
        return true;
    }

    
    function floordec($zahl,$decimals=2){   
         return floor($zahl*pow(10,$decimals))/pow(10,$decimals);
    }
    public function calculo_directo($id_marchand, $id_mp, $monto_pagador, Sabana $sabana = null, Barcode $barcode = null, $id_pricing_pag = false, $id_pricing_cdi = false, $traslada_comision = false) {
        
        if (!is_numeric($monto_pagador)) {
            $this->developer_log("Solo puede realizar el calculo directo de números: '" . $monto_pagador);
            return false;
        }
        
        if (!$this->optimizar_mp($id_mp)) {
            $this->developer_log('Ha ocurrido un error al optimizar el Mp.');
            return false;
        }
        if ((self::$mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_REVERSO_DE_EGRESO OR self::$mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_REVERSO_DE_INGRESO OR self::$mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_REVERSO_DE_COSTO)
                AND ( $id_pricing_cdi !== false AND $id_pricing_pag !== false)) {
            # Reversos
            $this->pricing_pag = new Pricing();
            if (!$this->pricing_pag->get($id_pricing_pag)) {
                $this->pricing_pag = false;
            }
            $this->pricing_cdi = new Pricing();
            if (!$this->pricing_cdi->get($id_pricing_cdi)) {
                $this->pricing_cdi = false;
            }
        } elseif ((self::$mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_INGRESO OR self::$mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_EGRESO )AND ( $id_pricing_cdi === false AND $id_pricing_pag === false)) {
            # No Reversos
            $pag = '1';
            $cdi = '2';
            $this->pricing_pag = $this->obtener_comisiones($id_marchand, $id_mp, $pag, $sabana, $barcode);
            $this->pricing_cdi = $this->obtener_comisiones($id_marchand, $id_mp, $cdi, $sabana, $barcode);
        } elseif ((self::$mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_INGRESO OR self::$mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_EGRESO ) AND ( $id_pricing_cdi !== false AND $id_pricing_pag !== false)) {
            $this->pricing_pag = new Pricing();
            $this->pricing_pag->get($id_pricing_pag);
            $this->pricing_cdi = new Pricing();
            $this->pricing_cdi->get($id_pricing_cdi);
        } elseif (self::$mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_REVERSO_DE_EGRESO OR
                self::$mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_REVERSO_DE_INGRESO ) {
            $this->developer_log('No es posible calcular al monto neto de un reverso.');
            return false;
        } elseif (self::$mp->get_id_mp() == Mp::RETIROS AND ( $id_pricing_pag !== false and $id_pricing_cdi != false)) {
            developer_log("Error");
            $this->pricing_pag = new Pricing();
            $this->pricing_pag->get($id_pricing_pag);
            $this->pricing_cdi = new Pricing();
            $this->pricing_cdi->get($id_pricing_cdi);
        } else {
            $this->developer_log("No es posible identificar el pricing.");
            return false;
        }
        list($pricing_cdi, $pricing_pag) = $this->calcular_descuento($this->pricing_pag, $this->pricing_cdi, $id_marchand);
        $this->pricing_cdi = $pricing_cdi;
        $this->pricing_pag = $pricing_pag;
        if ($this->pricing_pag === false OR $this->pricing_cdi === false) {
            $this->developer_log("Ha ocurrido un error al obtener las Comisiones. ");
            return false;
        }

        $operacion_comisiones = $this->operacion_comisiones(self::$mp);
//        var_dump($operacion_comisiones);
        $monto_pagador = $this->floordec(abs($monto_pagador),2);
        // reset todos los valores 
        $pag_fix = 0;
        $pag_var = 0;
        $monto_cd = 0;
        $cdi_fix = 0;
        $cdi_var = 0;

        $monto_marchand = 0;
        //    echo  "<br> transaccion : $id_mp ";
        //   var_dump($traslada_comision);
        if (in_array($id_mp, array(Mp::COBRO_COMO_PROVEEDOR, Mp::PAGO_A_PROVEEDOR, Mp::PAGO_PROVEEDOR_PENDIENTE, Mp::RETIROS))) {
            if ($traslada_comision) { // paga el proveedor
                $array = $this->monto_siguiente($monto_pagador, $this->pricing_pag->get_pri_fijo(), $this->pricing_pag->get_pri_variable(), $this->pricing_pag->get_pri_minimo(), $this->pricing_pag->get_pri_maximo(), $operacion_comisiones);
                if ($array !== false) {
                    developer_log(json_encode($array));
                    list($monto_cd, $pag_fix, $pag_var) = $array;
                    $monto_marchand = $monto_cd;
                    unset($array);
                } else {
                    return false;
                }
            } else {  //page el marchand
                developer_log($this->pricing_cdi->get_pri_variable() . " " . $this->pricing_cdi->get_id_pricing());
                $array = $this->monto_siguiente($monto_pagador, $this->pricing_cdi->get_pri_fijo(), $this->pricing_cdi->get_pri_variable(), $this->pricing_cdi->get_pri_minimo(), $this->pricing_cdi->get_pri_maximo(), $operacion_comisiones);
                if ($array !== false) {
                    list($monto_marchand, $cdi_fix, $cdi_var) = $array;
                    $monto_cd = $monto_pagador;
                    unset($array);
                } else {
                    return false;
                }
            }
        } else {
            $array = $this->monto_siguiente($monto_pagador, $this->pricing_pag->get_pri_fijo(), $this->pricing_pag->get_pri_variable(), $this->pricing_pag->get_pri_minimo(), $this->pricing_pag->get_pri_maximo(), $operacion_comisiones);
            if ($array !== false) {
                list($monto_cd, $pag_fix, $pag_var) = $array;
                unset($array);
            } else {
                return false;
            }
            $array = $this->monto_siguiente($monto_cd, $this->pricing_cdi->get_pri_fijo(), $this->pricing_cdi->get_pri_variable(), $this->pricing_cdi->get_pri_minimo(), $this->pricing_cdi->get_pri_maximo(), $operacion_comisiones);
            if ($array !== false) {
                list($monto_marchand, $cdi_fix, $cdi_var) = $array;
                unset($array);
            } else {
                return false;
            }
        }
        $this->developer_log('CD: ' . $monto_pagador . ' | ' . $pag_fix . ' | ' . $pag_var . ' | ' . $monto_cd . ' | ' . $cdi_fix . ' | ' . $cdi_var . ' | ' . $monto_marchand);
        if (in_array(self::$mp->get_id_mp(), array(Mp::COSTO_RAPIPAGO, Mp::COSTO_PAGO_FACIL, Mp::COSTO_PROVINCIA_PAGO, Mp::COSTO_COBRO_EXPRESS, Mp::COSTO_RIPSA, Mp::COSTO_MULTIPAGO, Mp::COSTO_BICA, Mp::COSTO_PRONTO_PAGO,Mp::COSTO_DECIDIR_DEVOLUCION,Mp::COSTO_PEI_DEVOLUCION))) {
            $diferencia = $monto_marchand - $monto_pagador; //se da vuelta el signo por que es 0 y sino queda positivo siempre
//                    $pag_fix=$diferencia;
            $monto_pagador = 0;
            $monto_marchand = $diferencia;
            $monto_cd = 0;
            $this->developer_log('COSTO EFECTIVO CD: ' . $monto_pagador . ' | ' . $pag_fix . ' | ' . $pag_var . ' | ' . $monto_cd . ' | ' . $cdi_fix . ' | ' . $cdi_var . ' | ' . $monto_marchand);
            return array($monto_pagador, $pag_fix, $pag_var, $monto_cd, $cdi_fix, $cdi_var, $monto_marchand);
        }

        return array($monto_pagador, $pag_fix, $pag_var, $monto_cd, $cdi_fix, $cdi_var, $monto_marchand);
    }

    public function calculo_indirecto($id_marchand, $id_mp, $monto_marchand, Sabana $sabana = null, Barcode $barcode = null) {
        $this->developer_log('El calculo indirecto puede no coincidir con la comision aplicado sobre un codigo de barras concreto.');
        if (!is_numeric($monto_marchand)) {
            $this->developer_log('Solo puede realizar el calculo indirecto de números.');
            return false;
        }
        if (!$this->optimizar_mp($id_mp)) {
            $this->developer_log('Ha ocurrido un error al optimizar el Mp.');
            return false;
        }
        if (self::$mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_REVERSO_DE_EGRESO OR
                self::$mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_REVERSO_DE_INGRESO) {
            $this->developer_log('No es posible calcular al monto bruto de un reverso.');
            return false;
        }
        $pag = '1';
        $cdi = '2';
        $this->pricing_pag = $this->obtener_comisiones($id_marchand, $id_mp, $pag, $sabana, $barcode);
        $this->pricing_cdi = $this->obtener_comisiones($id_marchand, $id_mp, $cdi, $sabana, $barcode);
        list($pricing_cdi, $pricing_pag) = $this->calcular_descuento($this->pricing_pag, $this->pricing_cdi, $id_marchand);
        $this->pricing_cdi = $pricing_cdi;
        $this->pricing_pag = $pricing_pag;
        unset($pricing_cdi);
        unset($pricing_pag);
        if ($this->pricing_pag === false OR $this->pricing_cdi === false) {
            $this->developer_log("Ha ocurrido un error al obtener las Comisiones. ");
            return false;
        }
        if (!$this->optimizar_mp($id_mp)) {
            $this->developer_log('Ha ocurrido un error al optimizar el Mp.');
            return false;
        }
        $operacion_comisiones = $this->operacion_comisiones(self::$mp);

        $array = $this->monto_anterior($monto_marchand, $this->pricing_cdi->get_pri_fijo(), $this->pricing_cdi->get_pri_variable(), $this->pricing_cdi->get_pri_minimo(), $this->pricing_cdi->get_pri_maximo(), $operacion_comisiones);
        if ($array !== false) {
            list($monto_cd, $cdi_fix, $cdi_var) = $array;
            unset($array);
        } else {
            $this->developer_log('Error al calcular el monto anterior(1).');
            return false;
        }
        $array = $this->monto_anterior($monto_cd, $this->pricing_pag->get_pri_fijo(), $this->pricing_pag->get_pri_variable(), $this->pricing_pag->get_pri_minimo(), $this->pricing_pag->get_pri_maximo(), $operacion_comisiones);
        if ($array !== false) {
            list($monto_pagador, $pag_fix, $pag_var) = $array;
            $monto_pagador = round($monto_pagador, 2);
            unset($array);
        } else {
            $this->developer_log('Error al calcular el monto anterior(2).');
            return false;
        }
        if ($monto_pagador < 0) {
            $this->developer_log('No es posible realizar el cálculo ya que las comisiones son mayores que el monto bruto.');
            return false;
        }
        $this->developer_log('CI: ' . $monto_pagador . ' | ' . $pag_fix . ' | ' . $pag_var . ' | ' . $monto_cd . ' | ' . $cdi_fix . ' | ' . $cdi_var . ' | ' . $monto_marchand);
        return array($monto_pagador, $pag_fix, $pag_var, $monto_cd, $cdi_fix, $cdi_var, $monto_marchand);
    }

    protected function monto_siguiente($monto, $fijo, $variable, $minimo, $maximo, Closure $operacion_comisiones) {
        if ((((!is_numeric($monto))OR ( !is_numeric($fijo)))OR ( !is_numeric($minimo)))OR ( !is_numeric($maximo))) {
            return false;
        }
        $fix = round(abs($fijo), 2);
        $var = round(abs($monto * $variable / 100), 2);
        if ($var < $minimo) {
            $var = round(abs($minimo), 2);
        } elseif ($maximo != 0 AND $fix > $maximo) {
            $fix = round(abs($maximo), 2);
        }
        $monto_siguiente = $operacion_comisiones($monto, $fix, $var);
        return array($monto_siguiente, $fix, $var);
    }

    protected function monto_anterior($monto, $fijo, $variable, $minimo, $maximo, Closure $operacion_comisiones) {
        if ((((!is_numeric($monto))OR ( !is_numeric($fijo)))OR ( !is_numeric($minimo)))OR ( !is_numeric($maximo))) {
            $this->developer_log('Error al no recibir numeros.');
            return false;
        }
        # Siempre se cobra el Fijo
        $fix = $fijo;
        # Hay tres soluciones posibles
        # Cota inferior
        $variable = $variable / 100;
        $soluciones = array();
        $solucion_1 = $operacion_comisiones($monto, -1 * $minimo, -1 * $fix);
        $comision_1 = $variable * $solucion_1;
        if ($comision_1 <= $minimo) {
            $soluciones[] = $solucion_1;
            $var = $minimo;
        }
        # Libre
        $solucion_2 = ($operacion_comisiones($monto, -1 * $fijo)) / ($operacion_comisiones(1, $variable));
        $comision_2 = $variable * $solucion_2;
        if ($maximo == 0)
            $maximo = $comision_2 + 1;# Garantizo que no esta acotado

        if ($minimo < $comision_2 AND $comision_2 < $maximo) {
            $soluciones[] = $solucion_2;
            $var = $variable * $solucion_2;
        }
        # Cota superior
        if ($maximo != 0) {
            $solucion_3 = $operacion_comisiones($monto, -1 * $maximo, -1 * $fix);
            $comision_3 = $variable * $solucion_3;
            if ($comision_3 >= $maximo) {
                $soluciones[] = $solucion_3;
                $var = $maximo;
            }
        }
        if (count($soluciones) === 1) {
            ## $monto_anterior=$soluciones[0]; Pero falta el redondeo 
            $fix = round(abs($fix), 2);
            $var = round(abs($var), 2);
            $monto_anterior = $operacion_comisiones($monto, -1 * $fix, -1 * $var);
            return array($monto_anterior, $fix, $var);
        }
        if (count($soluciones) > 1) {
            $this->developer_log('Hay mas de una solución posible.');
        } else {
            $this->developer_log('No hay ninguna solución posible.');
        }
        return false;
    }

    protected function operacion_comisiones(Mp $mp) {
        $operacion_comisiones = false;
        $sumar = function($a, $b, $c = 0) {
            return $a + $b + $c;
        };
        $restar = function($a, $b, $c = 0) {
            return $a - $b - $c;
        };
        if ($mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_INGRESO) {
            $operacion_comisiones = $restar;
        } elseif ($mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_EGRESO) {
            $operacion_comisiones = $sumar;
        } elseif ($mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_REVERSO_DE_EGRESO) {
            $operacion_comisiones = $sumar;
        } elseif ($mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_REVERSO_DE_INGRESO) {
            $operacion_comisiones = $restar;
        } elseif ($mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_REVERSO_DE_COSTO) {
            $operacion_comisiones = $sumar;
        }
        return $operacion_comisiones;
    }

    protected function operacion_saldo(Mp $mp) {
        $operacion_saldo = false;
        $sumar = function($a, $b, $c = 0) {
            return $a + $b + $c;
        };
        $restar = function($a, $b, $c = 0) {
            return $a - $b - $c;
        };
        if ($mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_INGRESO) {
            $operacion_saldo = $sumar;
        } elseif ($mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_EGRESO) {
            $operacion_saldo = $restar;
        } elseif ($mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_REVERSO_DE_EGRESO) {
            $operacion_saldo = $sumar;
        } elseif ($mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_REVERSO_DE_INGRESO) {
            $operacion_saldo = $restar;
        } elseif ($mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_REVERSO_DE_COSTO) {
            $operacion_saldo = $sumar;
        }
        return $operacion_saldo;
    }

    protected function verificar_propiedad_entidad_referencia($id_marchand, $id_entidad, $id_referencia) {
        return true; # OJO CON SUBCOMISIONES!!!
        switch ($id_entidad) {
            case Entidad::ENTIDAD_CBUMARCHAND:
                $recordset = Cbumarchand::select(array('id_marchand' => $id_marchand, 'id_cbumarchand' => $id_referencia));
                break;
            case Entidad::ENTIDAD_BARCODE:
                $recordset = Barcode::select(array('id_marchand' => $id_marchand, 'id_barcode' => $id_referencia));
                break;
        }
        if (isset($recordset) AND $recordset->RowCount() == 1) {
            return true;
        }
        return false;
    }

    protected function insertar_moves(Moves $moves) {
        if (!$this->optimizar_mp($moves->get_id_mp())) {
            $this->developer_log('Ha ocurrido un error al optimizar el Mp.');
            return false;
        }

        if (self::$mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_INGRESO OR self::$mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_EGRESO OR self::$mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_REVERSO_DE_COSTO) {
            $sign_monto_pagador = 1;
            $sign_pag_fix = 1;
            $sign_pag_var = 1;
            $sign_monto_cd = 1;
            $sign_cdi_fix = 1;
            $sign_cdi_var = 1;
            $sign_monto_marchand = 1;
        } elseif (self::$mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_REVERSO_DE_INGRESO OR self::$mp->get_sentido_transaccion() == Mp::SENTIDO_TRANSACCION_REVERSO_DE_EGRESO) {
            $sign_monto_pagador = -1;
            $sign_pag_fix = -1;
            $sign_pag_var = -1;
            $sign_monto_cd = -1;
            $sign_cdi_fix = -1;
            $sign_cdi_var = -1;
            $sign_monto_marchand = -1;
        } else {
            $this->developer_log('Sentido de transaccion incorrecto.');
            return false;
        }
        $moves->set_monto_pagador($sign_monto_pagador * $moves->get_monto_pagador());
        $moves->set_pag_fix($sign_pag_fix * $moves->get_pag_fix());
        $moves->set_pag_var($sign_pag_var * $moves->get_pag_var());
        $moves->set_monto_cd($sign_monto_cd * $moves->get_monto_cd());
        $moves->set_cdi_fix($sign_cdi_fix * $moves->get_cdi_fix());
        $moves->set_cdi_var($sign_cdi_var * $moves->get_cdi_var());
        $moves->set_monto_marchand($sign_monto_marchand * $moves->get_monto_marchand());
        if (in_array($moves->get_id_mp(), array(Mp::RAPIPAGO, Mp::PAGOFACIL, Mp::PROVINCIAPAGO, Mp::COBROEXPRESS, Mp::RIPSA, Mp::MULTIPAGO, Mp::PAGOMISCUENTAS, Mp::BICA, Mp::PRONTOPAGO, Mp::LINKPAGOS, Mp::DEBITO_AUTOMATICO,Mp::TELERECARGAS)) AND $moves->get_id_sabana() == 1) {
            Model::FailTrans();
            $this->developer_log("LA SABANA TIENE ID 1, NO SE COSTEARÁ.");
            return false;
        }
        if (in_array($moves->get_id_mp(), array(Mp::RAPIPAGO, Mp::PAGOFACIL, Mp::PROVINCIAPAGO, Mp::COBROEXPRESS, Mp::RIPSA, Mp::MULTIPAGO, Mp::PAGOMISCUENTAS, Mp::BICA, Mp::PRONTOPAGO, Mp::LINKPAGOS, Mp::DEBITO_AUTOMATICO,Mp::TELERECARGAS))) {
            $sabanas = Moves::select(array("id_mp" => $moves->get_id_mp(), "id_sabana" => $moves->get_id_sabana()));
            if ($sabanas->rowCount() > 0) {
                $this->developer_log("LA SABANA QUE INTENTA COSTEAR YA EXISTE CON EL " . $moves->get_id_mp() . " Y EL ID_SABANA " . $moves->get_id_sabana() . " NO SERÁ COSTEADO.");
                Model::FailTrans();
                return false;
            }
        } elseif ($moves->get_id_mp() == Mp::TARJETA) {
            $transas = Moves::select(array("id_mp" => $moves->get_id_mp(), "id_transas" => $moves->get_id_transas()));
            if ($transas->rowCount() > 0) {
                $this->developer_log("LA TRANSA QUE INTENTA COSTEAR YA EXISTE CON EL " . $moves->get_id_mp() . " Y EL ID_TRANSA " . $moves->get_id_transas() . " NO SERÁ COSTEADO.");
                Model::FailTrans();
                return false;
            }
        }
        $this->developer_log("LA SABANA SE COSTEARÁ.");
        if ($moves->set()) {
            $this->developer_log('Transaccion insertada correctamente.');
            return $moves;
        }
        return false;
    }

    protected function calcular_descuento(Pricing $pricing_pag, Pricing $pricing_cd, $id_marchand) {
//            <costo_adicional>
//                <medio_pago>
//                    <id_mp>XXXX</id_mp>
//                    <porcentaje_descuento>n.nn</porcentaje_descuento>
//                    <Fecha_Desde>YYYYMMDD</Fecha_Desde>
//                    <Fecha_Hasta>YYYYMMDD</Fecha_Hasta>
//                </medio_pago> 	
//            </costo_adicional>
//            
//            $recordset=Xml::select(array("id_entidad"=>125,"id_marchand"=> Application::$usuario->get_id_marchand()));
        if (in_array($pricing_pag->get_id_mp(), array(Mp::PAGO_A_PROVEEDOR, Mp::COBRO_COMO_PROVEEDOR, Mp::PAGO_PROVEEDOR_PENDIENTE))
                or in_array($pricing_cd->get_id_mp(), array(Mp::PAGO_A_PROVEEDOR, Mp::COBRO_COMO_PROVEEDOR, Mp::PAGO_PROVEEDOR_PENDIENTE))) {
            $porcentaje = 35 / 100;
            $pricing_pag->set_pri_fijo($pricing_pag->get_pri_fijo() - ($pricing_pag->get_pri_fijo() * $porcentaje));
            $pricing_pag->set_pri_variable($pricing_pag->get_pri_variable() - ($pricing_pag->get_pri_variable() * $porcentaje));
            $pricing_pag->set_pri_minimo($pricing_pag->get_pri_minimo() - ($pricing_pag->get_pri_minimo() * $porcentaje));
            $pricing_pag->set_pri_maximo($pricing_pag->get_pri_maximo() - ($pricing_pag->get_pri_maximo() * $porcentaje));
            $pricing_cd->set_pri_fijo($pricing_cd->get_pri_fijo() - ($pricing_cd->get_pri_fijo() * $porcentaje));
            $pricing_cd->set_pri_variable($pricing_cd->get_pri_variable() - ($pricing_cd->get_pri_variable() * $porcentaje));
            $pricing_cd->set_pri_minimo($pricing_cd->get_pri_minimo() - ($pricing_cd->get_pri_minimo() * $porcentaje));
            $pricing_cd->set_pri_maximo($pricing_cd->get_pri_maximo() - ($pricing_cd->get_pri_maximo() * $porcentaje));
            return array($pricing_cd, $pricing_pag);
        }

        $recordset = Xml::select(array("id_entidad" => 125, "id_marchand" => $id_marchand)); //test
        $row = $recordset->fetchRow();
        if($row["xmlfield"]==""){
             if (self::ACTIVAR_DEBUG)
                $this->developer_log("No tiene config marchand definido no hay descuentos para este marchand");
            return array($pricing_cd, $pricing_pag);
        }
        $config = new View('1.0', 'utf-8');
        $config->loadXML($row['xmlfield']);
        if ($config->getElementsByTagName("costo_adicional") != false) {
            $items = $config->getElementsByTagName("id_mp");
            $procesado = array();
            $i = 0;
            foreach ($items as $item) {
                if (!(in_array($item->nodeValue, $procesado)) AND ( $pricing_cd->get_id_mp() == $item->nodeValue OR $pricing_pag->get_id_mp() == $item->nodeValue)) {
                    $fecha_xml = $config->getElementsByTagName("Fecha_Desde")->item($i)->nodeValue;
                    $fecha_desde = DateTime::createFromFormat("Ymd", $fecha_xml);
                    $fecha_xml = $config->getElementsByTagName("Fecha_Hasta")->item($i)->nodeValue;
                    $fecha_hasta = DateTime::createFromFormat("Ymd", $fecha_xml);
                    $ahora = new DateTime("now");
                    if ($fecha_desde != false AND $fecha_hasta != false) {
                        if ($fecha_desde->format("Y-m-d") < $ahora->format("Y-m-d") AND $fecha_hasta->format("Y-m-d") >= $ahora->format("Y-m-d")) {
                            $procesado [] = $item->nodeValue;
                            $porcentaje = $config->getElementsByTagName("porcentaje_descuento")->item($i)->nodeValue;
                            if ($porcentaje <= 100 AND $porcentaje >= 0) {
                                $porcentaje = $porcentaje / 100;
                                $pricing_pag->set_pri_fijo($pricing_pag->get_pri_fijo() - ($pricing_pag->get_pri_fijo() * $porcentaje));
                                $pricing_pag->set_pri_variable($pricing_pag->get_pri_variable() - ($pricing_pag->get_pri_variable() * $porcentaje));
                                $pricing_pag->set_pri_minimo($pricing_pag->get_pri_minimo() - ($pricing_pag->get_pri_minimo() * $porcentaje));
                                $pricing_pag->set_pri_maximo($pricing_pag->get_pri_maximo() - ($pricing_pag->get_pri_maximo() * $porcentaje));
                                $pricing_cd->set_pri_fijo($pricing_cd->get_pri_fijo() - ($pricing_cd->get_pri_fijo() * $porcentaje));
                                $pricing_cd->set_pri_variable($pricing_cd->get_pri_variable() - ($pricing_cd->get_pri_variable() * $porcentaje));
                                $pricing_cd->set_pri_minimo($pricing_cd->get_pri_minimo() - ($pricing_cd->get_pri_minimo() * $porcentaje));
                                $pricing_cd->set_pri_maximo($pricing_cd->get_pri_maximo() - ($pricing_cd->get_pri_maximo() * $porcentaje));
                                return array($pricing_cd, $pricing_pag);
                            } else
                            if (self::ACTIVAR_DEBUG)
                                $this->developer_log("Porcentaje invalido.");
                        } else
                        if (self::ACTIVAR_DEBUG)
                            $this->developer_log("Fecha fuera del rango, ignorando...");
                    }
                    else
                    if (self::ACTIVAR_DEBUG)
                        $this->developer_log("Fechas invalidas");
                }
                $i++;
            }
        }
        else
        if (self::ACTIVAR_DEBUG)
            $this->developer_log("No tienedescuentos para el mp " . $pricing_cd->get_id_mp() . ", ignorando...");
        return array($pricing_cd, $pricing_pag);
    }
}
