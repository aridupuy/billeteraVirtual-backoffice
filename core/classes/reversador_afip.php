<?php

class Reversador_afip extends Moves{
    
    const TIPO_FECHAHORA = 'fecha_hora';
    const TIPO_FECHA = 'fecha';
    
    /**
    * Obtiene los datos de la base de datos para un medio de pago especifico
    */
    public function seleccionarRetencionesXfechas($id_mp, $documento, $fecha_desde, $fecha_hasta, $tipo_fecha=self::TIPO_FECHA){
        /*Falta validaciones*/
        
        if(self::TIPO_FECHAHORA === $tipo_fecha){
        /**
         * Fecha y hora especificas
         */
            $desde = $fecha_desde->format("Y-m-d H:i:s");
            $hasta = $fecha_hasta->format("Y-m-d H:i:s");
            $consulta = "SELECT mov.* FROM cd_moves mov INNER JOIN cd_marchand mar ON mar.id_marchand = mov.id_marchand  WHERE mar.documento::text = '$documento'  AND mov.id_mp = $id_mp AND mov.fecha_move BETWEEN '$desde' AND '$hasta'";
        
        }elseif(self::TIPO_FECHA === $tipo_fecha){
            /**
            * Solo rango de fecha
            */
            $desde = $fecha_desde->format('Y-m-d');
            $hasta = $fecha_hasta->format("Y-m-d");
            $consulta = "SELECT mov.* FROM cd_moves mov INNER JOIN cd_marchand mar ON mar.id_marchand = mov.id_marchand  WHERE mar.documento::text = '$documento'  AND mov.id_mp = $id_mp AND mov.fecha_move::date BETWEEN '$desde' AND '$hasta'";      
        }

        developer_log($consulta);
        return parent::execute_select($consulta);
    }

    /**
    * Obtiene los datos de la base de datos para un medio de pago especifico buscando por id_move_retencion
    */
    public function seleccionarRetencion($id_mp, $documento, $fecha_desde, $fecha_hasta, $tipo_fecha=self::TIPO_FECHA){
        /*Falta validaciones*/
        
        if(self::TIPO_FECHAHORA === $tipo_fecha){
        /**
         * Fecha y hora especificas
         */
            $desde = $fecha_desde->format("Y-m-d H:i:s");
            $hasta = $fecha_hasta->format("Y-m-d H:i:s");
            $consulta = "SELECT mov.* FROM cd_moves mov INNER JOIN cd_marchand mar ON mar.id_marchand = mov.id_marchand  WHERE mar.documento::text = '$documento'  AND mov.id_mp = $id_mp AND mov.fecha_move BETWEEN '$desde' AND '$hasta'";
        
        }elseif(self::TIPO_FECHA === $tipo_fecha){
            /**
            * Solo rango de fecha
            */
            $desde = $fecha_desde->format('Y-m-d');
            $hasta = $fecha_hasta->format("Y-m-d");
            $consulta = "SELECT mov.* FROM cd_moves mov INNER JOIN cd_marchand mar ON mar.id_marchand = mov.id_marchand  WHERE mar.documento::text = '$documento'  AND mov.id_mp = $id_mp AND mov.fecha_move::date BETWEEN '$desde' AND '$hasta'";      
        }

        developer_log($consulta);
        return parent::execute_select($consulta);
    }

    
    /**
     * Realiza el reverso de un movimiento. Verifica solo que no haya devoluciones de retencion impositiva.
     * @param string $movimiento Id_moves de retencion afip
     * @return int Resultado de la operacion. O error al retener, 1 se retuvo, -1 ya retenido. 
     */
    private function devolverRetencion($movimiento){
        
            $reversador = new Transaccion();
            //Verifica que no haya devoluciones (reversos) ya generadas
            $devolucionesGeneradas = Moves::select(["id_mp" => MP::DEVOLUCION_RETENCION_IMPOSITIVA,
                                                    "id_entidad" => Entidad::ENTIDAD_MOVES,
                                                    "id_referencia" => $movimiento["id_moves"]
                                                    ]);
            
            if($devolucionesGeneradas->rowCount() == 0){
                
                if(!Model::HasFailedTrans()){
                    //Instancia el movimiento a devolver (reversar)
                    $moves = new Moves($movimiento);
                    
                    //Se realiza la devolucion 
                    if(!$reversador->reversar($moves)){
                        error_log("(!)CLASE: reversador_afip.php -> Error al reversar una retencion.");
                        //Model::FailTrans();
                        return 0;
                    }
                    else {
                        return 1;
                    }
                }
            }else{
                error_log("(!)CLASE: reversador_afip.php -> Movimiento ya reversado para el medio de pago.");
                return -1;
            }
    }
    
    /**
     * Devuelve las retenciones de un array de cuits entre un periodo de fechas.
     * NOTAS: Pasar a FALSE la variable $modo_seguro_fallar para trabajar con el script en produccion. No se verifica la existencia de retenciones para mp de ARBA.
     * 
     * @param int $idMp ID del medio de pago a reversar: 5004, 5005, 5000.
     * @param array(string) $documentos Listados de CUITs.
     * @param string $fechaDesde Fecha desde donde se va a retener.
     * @param string $fechaHasta Fecha hasta donde se va a retener.
     * @param string $tipo_fecha Tipo de fecha con la que se va consultar a la BD. Constantes con valor: 'fecha_hora' y 'fecha'.
     * @return boolean
     */
    public function devolverRetencionesMasiva($idMp, $documentos, $fechaDesde, $fechaHasta, $tipo_fecha= self::TIPO_FECHA){
        
        /* Falta agregar mas validaciones que den mas estabilidad:
            Falta validar fechas, medio de pago >0, cuit sean validos y detalle sea un string.
        */
        if(empty($idMp) OR empty($fechaDesde) OR empty($fechaHasta) OR empty($documentos) OR !in_array($tipo_fecha, [self::TIPO_FECHA, self::TIPO_FECHAHORA])){
            error_log("(!)CLASE: reversador_afip.php -> Algunos de los parametros de entrada se encuentran vacios.");
            exit();
        }

        //Inicializacion de variables
        $modo_seguro_fallar = TRUE;
        $desde = new DateTime($fechaDesde);
        $hasta = new DateTime($fechaHasta);
        
        Model::StartTrans();
        foreach($documentos as $cuit){
            //Obtencion de movimientos para un cuit.
            error_log("(#) CLASE: reversador_afip.php -> Se busca retenciones para el CUIT: $cuit.");
            $recordset = self::seleccionarRetencion($idMp, $cuit, $desde, $hasta);
            
            if(!is_object($recordset)){
                error_log("(!) CLASE: reversador_afip.php -> Falla la consulta a la BD.");
                continue;
                
            }else if($recordset->rowCount() <= 0){
                error_log("(!) CLASE: reversador_afip.php -> No se obtienen retenciones para el CUIT: $cuit.");
                continue;
            }
            
            //Se inicia procesamiento de movimientos
            $movesTotales = $recordset->recordCount();
            $movesReversados=0;
            $movesNoReversados=0;
            $movesYaReversados=0;
            
            foreach($recordset as $move){
                $rltdo = $this->devolverRetencion($move);
                switch($rltdo){
                    case 1:
                        $movesReversados++;
                        break;
                        
                    case 0:
                        $movesNoReversados++;
                        $modo_seguro_fallar = true;
                        break;
                        
                    case -1:
                        $movesYaReversados++;
                        break;
                }

                error_log("(#) CLASE: reversador_afip.php -> CUIT: $cuit => Moves Totales: $movesTotales => Reversados: $movesReversados - No Reversados: $movesNoReversados - Reversados Anteriormente: $movesYaReversados.");
            }
        }

        if($modo_seguro_fallar){
            Model::FailTrans();
            error_log("(@) CLASE: reversador_afip.php -> Fallo al procesar las devoluciones, no se ha completado la transaccion!");            
            return false;
            
        }else if(!Model::HasFailedTrans() and Model::CompleteTrans()){
            error_log("(@) CLASE: reversador_afip.php -> Se proceso las devoluciones correctamente. Transaccion completa!");
            return true;
            
        }else {
            Model::FailTrans();
            error_log("(@) CLASE: reversador_afip.php -> No se ha podido completar la transaccion!");
            return false;
        }
        
    }

    /**
     * Devuelve la retenciones de un array de movimiento de retenciones.
     * NOTAS: Pasar a FALSE la variable $modo_seguro_fallar para trabajar con el script en produccion. No se verifica la existencia de retenciones para mp de ARBA.
     * 
     * @param array(string) $movimientos Listado de ID de movimientos de retencion a reversar.
     * @param int $idMp ID del medio de pago a reversar: 5004, 5005. Mp::RETENCION_IMPOSITIVA_IVA, Mp::RETENCION_IMPOSITIVA_GANANCIAS
     * @return boolean
     */
    public function devolverRetencionesXMovimiento($movimientos, $idMp = Mp::RETENCION_IMPOSITIVA_IVA){
        /**
         * Falta agregar mas validaciones que den mas estabilidad:
         * Falta validar fechas, medio de pago >0, cuit sean validos y detalle sea un string.
         */

        //verificar entrada
        $movimientos = is_array($movimientos) ? preg_grep("/^(\d+)+$/", $movimientos) : NULL;

        if(empty($movimientos) OR empty($idMp) OR !in_array($idMp, [Mp::RETENCION_IMPOSITIVA_IVA, Mp::RETENCION_IMPOSITIVA_GANANCIAS])){
            //fallar
            error_log("(!)CLASE: reversador_afip.php -> Algunos de los parametros de entrada se encuentran vacios o no se validaron.");
            exit();
        }

        //Inicializacion de variables
        $modo_seguro_fallar = FALSE;
        //Se inicia procesamiento de movimientos
        $movesTotales = count($movimientos);
        $movesReversados=0;
        $movesNoReversados=0;
        $movesYaReversados=0;
        Model::StartTrans();
        
        foreach($movimientos as $idMove){
            //Obtencion de movimiento.
            error_log("(#) CLASE: reversador_afip.php -> Se busca retencion: $idMove.");
            //obtener el move: Por razones de compatiblidad se usa el recordset
            $recordset = Moves::select(["id_moves" => $idMove, "id_mp" => $idMp]);

            if (empty($recordset) or $recordset->rowCount() == 0) {
                error_log("(!)CLASE: reversador_afip.php -> No se pudo obtener los datos de la retencion con el ID_MOVE: $idMove. No se van a procesar las transacciones!!!!");
                $modo_seguro_fallar = TRUE;
                $movesNoReversados++;
                continue;
            }
            
            $move = $recordset->fetchRow();
            $rltdo = $this->devolverRetencion($move);
            
            switch($rltdo){
                case 1:
                    $movesReversados++;
                    break;

                case 0:
                    $movesNoReversados++;
                    $modo_seguro_fallar = TRUE;
                    break;

                case -1:
                    $movesYaReversados++;
                    break;
            }

                error_log("(#) CLASE: reversador_afip.php -> Moves Totales: $movesTotales => Reversados: $movesReversados - No Reversados: $movesNoReversados - Reversados Anteriormente: $movesYaReversados.");
        }

        if($modo_seguro_fallar){
            Model::FailTrans();
            error_log("(@) CLASE: reversador_afip.php -> Fallo al procesar las devoluciones, no se ha completado la transaccion!");            
            return false;
            
        }else if(!Model::HasFailedTrans() and Model::CompleteTrans()){
            error_log("(@) CLASE: reversador_afip.php -> Se proceso las devoluciones correctamente. Transaccion completa!");
            return true;
            
        }else {
            Model::FailTrans();
            error_log("(@) CLASE: reversador_afip.php -> No se ha podido completar la transaccion!");
            return false;
        }
        
    }
}

