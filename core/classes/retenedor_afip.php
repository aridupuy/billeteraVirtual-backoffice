<?php

/**
 * Clase para generar retenciones
 */
class Retenedor_afip extends Model{
    /**
     * @var string
     */
    protected $tipoRetencionIva = 'IVA';
    /**
     * @var string
     */
    protected $tipoRetencionGnc = 'GANANCIAS';

    /**
     * Obtiene los datos de la base de datos para un tipo de retencion y cuit especifico.
     * 
     * @param string $cuit
     * @param DateTime $fecha_desde
     * @param DateTime $fecha_hasta
     * @param string $tipo Tipo de retenciones a seleccionar. Valor por defecto: IVA.
     * @return boolean
     */
    public function seleccionarRetenciones($cuit, $fecha_desde, $fecha_hasta, $tipo='IVA'){
        $utilDOM = new Utilidades_dom();
        
        /*Faltan validaciones*/
        if(!is_a($fecha_desde, 'DateTime') 
            OR !is_a($fecha_hasta, 'DateTime') 
            OR !in_array($tipo, [$this->tipoRetencionIva, $this->tipoRetencionGnc])
            OR !$utilDOM->validarCUIT($cuit)
        ){
            error_log("(!)retenedor_afip_trait.php -> seleccionarRetenciones()->Algunos de los parametros de entrada no se validaron.");
            return FALSE;
        }
        
        /*PARA MODO TIMESTAMP: usar el formato: 'Y-m-d H:i:s'*/        
        $desde = $fecha_desde->format('Y-m-d');
        $hasta = $fecha_hasta->format('Y-m-d');

        $consulta = "SELECT
                        * 
                    FROM cd_retencion 
                    WHERE
                        fecha_gen::timestamp BETWEEN '$desde' AND '$hasta'
                        AND tipo = '$tipo'
                        AND id_marchand IN (SELECT id_marchand FROM cd_marchand WHERE documento = '$cuit')";
        //developer_log($consulta);
        
        return parent::execute_select($consulta);
    }
    
    /**
     * Genera retenciones de iva y ganancias a move especifico.
     * 
     * @param array[string] $movimientos Listado de movimientos
     * @param boolean $retenerIva Valor por defecto: true.
     * @param boolean $retenerGnc Valor por defecto: true.
     * @return boolean
     */
    public function generarRetencionAMoves($movimientos, $retenerIva=true, $retenerGnc=true) {
        //verificar entrada
        $movimientos = is_array($movimientos) ? preg_grep("/^(\d+)+$/", $movimientos) : NULL;

        if(empty($movimientos) OR !is_bool($retenerIva)  OR !is_bool($retenerGnc)){
            //fallar
            error_log("(!)retenedor_afip.php -> Algunos de los parametros de entrada se encuentran vacios o no se validaron.");
            return FALSE;
        }
        
        //Procesamiento de movimientos
        $modo_seguro_fallar = FALSE;
        
        //contadores
        $totalMoves = count($movimientos);
        $totalRetencionesIva = $retenerIva ? count($movimientos) : 0;
        $totalRetencionesGnc = $retenerGnc ? count($movimientos) : 0;
        $procesadosMoves = 0;
        $noRetenibles = 0;
        $yaRetenidosIVA = 0;
        $yaRetenidosGNC = 0;
        $correctosIva = 0;
        $correctosGnc = 0;
        $incorrectosIva = 0;
        $incorrectosGnc = 0;
        
        Model::StartTrans();   
        foreach ($movimientos as $idMove) {
            
            //instanciar el move
            $move = new Moves();
            $move->get($idMove);

            if (empty($move)) {
                error_log("(!)retenedor_afip.php -> No se pudo obtener los datos del movimiento con el ID: $idMove. No se van a procesar las transacciones.");
                $modo_seguro_fallar = TRUE;
                $noRetenibles++;
                $procesadosMoves++;
                error_log("(#)retenedor_afip.php -> ID_MOVE PROCESADO: $idMove => Total Moves: $totalMoves ->Procesados: $procesadosMoves (No retenibles: $noRetenibles) => \n|||Total Ret. IVA: $totalRetencionesIva |Correctas: $correctosIva |Ya Retenidos: $yaRetenidosIVA |Incorrectos: $incorrectosIva \n|||Total Ret. GNC: $totalRetencionesGnc |Correctas: $correctosGnc |Ya retenidos: $yaRetenidosGNC |Incorrectas: $incorrectosGnc.");

                continue;
            }
            
            if(!$this->esRetenible($idMove, $retenerIva, $retenerGnc)){
                $modo_seguro_fallar = TRUE;
                error_log("(!)retenedor_afip.php -> No es retenible el movimiento. No se van a procesar las transacciones.");
                $noRetenibles++;
                $procesadosMoves++;
                error_log("(#)retenedor_afip.php -> ID_MOVE PROCESADO: $idMove => Total Moves: $totalMoves ->Procesados: $procesadosMoves (No retenibles: $noRetenibles) => \n|||Total Ret. IVA: $totalRetencionesIva |Correctas: $correctosIva |Ya Retenidos: $yaRetenidosIVA |Incorrectos: $incorrectosIva \n|||Total Ret. GNC: $totalRetencionesGnc |Correctas: $correctosGnc |Ya retenidos: $yaRetenidosGNC |Incorrectas: $incorrectosGnc.");

                continue;
            }    
                
            /*
             * RETENCION IVA
             */
            if($retenerIva === TRUE){
                //Busca si ya existe una retencion
                $retencionGenerada = Retencion::select(["tipo" => 'IVA', "id_move" => $idMove]);

                //verificar que no exista una retencion para ese move
                if (!is_object($retencionGenerada) OR $retencionGenerada->rowCount() != 0) {
                    error_log("(!)retenedor_afip.php -> Ya existe una retencion IVA para el movimiento con el ID: $idMove. No se va a procesar la transaccion.");
                    $modo_seguro_fallar = TRUE;
                    $yaRetenidosIVA++;
                    
                } else {
                    //retener
                    $retenedorIVA = new Retenedor_iva($move->get_id_marchand());

                    if (!$retenedorIVA->retener($move)) {
                        error_log("(!)retenedor_afip.php -> ERROR al intentar retener IVA el movimiento con el ID: $idMove. No se va a procesar la transaccion.");
                        $modo_seguro_fallar = TRUE;
                        $incorrectosIva++;
                    }else{
                        $correctosIva++;
                    }
                }
            }

            /*
             * RETENCION GANANCIA
             */
            if($retenerGnc === TRUE){
                //Busca si ya existe una retencion
                $retencionGenerada = Retencion::select(["tipo" => 'GANANCIAS', "id_move" => $idMove]);

                //verificar que no exista una retencion para ese move
                if (!is_object($retencionGenerada) OR $retencionGenerada->rowCount() != 0) {       
                    error_log("(!)retenedor_afip.php -> Ya existe una retencion GNC para el movimiento con el ID: $idMove. No se va a procesar la transaccion.");
                    $modo_seguro_fallar = TRUE;
                    $yaRetenidosGNC++;
                    
                } else {
                    //retener
                    $retenedorGNC = new Retenedor_gnc($move->get_id_marchand());

                    if(!$retenedorGNC->retener($move)){
                        error_log("(!)retenedor_afip.php -> ERROR al intentar retener GNC el movimiento con el ID: $idMove.  No se va a procesar la transaccion.");
                        $modo_seguro_fallar = TRUE;
                        $incorrectosGnc++;
                    }else{                    
                        $correctosGnc++;
                    }
                }   
            }
           
            $procesadosMoves++;
            error_log("(#)retenedor_afip.php -> ID_MOVE PROCESADO: $idMove => Total Moves: $totalMoves ->Procesados: $procesadosMoves (No retenibles: $noRetenibles) => \n|||Total Ret. IVA: $totalRetencionesIva |Correctas: $correctosIva |Ya Retenidos: $yaRetenidosIVA |Incorrectos: $incorrectosIva \n|||Total Ret. GNC: $totalRetencionesGnc |Correctas: $correctosGnc |Ya retenidos: $yaRetenidosGNC |Incorrectas: $incorrectosGnc.");
        }
        
        error_log("(#)retenedor_afip.php -> ID_MOVE PROCESADO: $idMove => Total Moves: $totalMoves ->Procesados: $procesadosMoves (No retenibles: $noRetenibles) => \n|||Total Ret. IVA: $totalRetencionesIva |Correctas: $correctosIva |Ya Retenidos: $yaRetenidosIVA |Incorrectos: $incorrectosIva \n|||Total Ret. GNC: $totalRetencionesGnc |Correctas: $correctosGnc |Ya retenidos: $yaRetenidosGNC |Incorrectas: $incorrectosGnc.");
        
        //Verificacion si debe completar la transaccion o fallar
        if($modo_seguro_fallar){
            Model::FailTrans();
            error_log("(@)retenedor_afip.php -> Fallo al procesar las retenciones, no se ha completado la transaccion!");
            return false;
        //DESCOMENTAR PARA TRABAJAR EN PRODUCCION    
        }else if(!Model::HasFailedTrans() and Model::CompleteTrans()){
          error_log("(@)retenedor_afip.php -> Se proceso las retenciones correctamente. Transaccion completa!");
          return true;
        
        }else{
            Model::FailTrans();
            error_log("(@)retenedor_afip.php -> No se ha podido completar la transaccion!");
            return false;
        }
    }
 
    //fork de esta_sujeto_a_retenciones()
    public function mp_es_retenible($id_mp) {
        try{
            developer_log("Verificando MP");
            $mp = new Mp();
            $mp->get($id_mp);
            
            if($mp->get_sumaresta() == 1 
                AND $mp->get_sentido_transaccion() === "ingreso" 
                AND !in_array($mp->get_id_mp(),[
                    MP::COBRODIGITAL_COMISION,
                    Mp::RETENCION_IMPOSITIVA,
                    Mp::RETENCION_IMPOSITIVA_IVA,
                    Mp::RETENCION_IMPOSITIVA_GANANCIAS,
                    Mp::COSTO_RAPIPAGO,
                    Mp::COSTO_PAGO_FACIL,
                    Mp::COSTO_PROVINCIA_PAGO,
                    Mp::COSTO_COBRO_EXPRESS,
                    Mp::COSTO_RIPSA,
                    Mp::COSTO_MULTIPAGO,
                    Mp::COSTO_BICA,
                    Mp::COSTO_PRONTO_PAGO,
                    Mp::DEBITO_AUTOMATICO_COSTO_RECHAZO,
                    Mp::DEBITO_AUTOMATICO_COSTO_REVERSO
                ])
            ){
                developer_log("MP Retenible: $id_mp");
                return TRUE;
                
            }else{
                return FALSE;
            }
            
        } catch (Exception $e){
            developer_log($e->getMessage());
            return NULL;
        }
    }
    
    //verifica si el marchand tiene una excepcion de impuestos MiPyme de ganancias o de todos los impuestos
    public function tiene_excepcion_mipyme($id_marchand, $impuesto_tipo, $fecha='now'){
        $impuesto = Impuesto_activo::select_impuestos_por_id($impuesto_tipo);
        $recordset = Excepciones_mipyme::select_certificado_activo($id_marchand, new DateTime($fecha),$impuesto);
        //error_log("MYPIME: " . $recordset->rowCount());
        
        if($recordset->rowCount() == 0){
            return false;
        }
            
        return true;
    }
    
    /**
     * Verifica que el medio de pago sea retenible, que el marchand no tenga excepcion mipyme.
     * @param string $idMove Id del movimiento a evaluar.
     * @return boolean
     */
    public function esRetenible($idMove, $retenerIva, $retenerGnc) {
        //verificar que sea un move retenible
        //instanciar el move
        $move = new Moves();
        $move->get($idMove);

        if (empty($move)) {
            error_log("(!)retenedor_afip.php -> No se pudo obtener los datos del movimiento con el ID: $idMove.");
            return FALSE;
        }
        
        //verificar si el mp es retenible
        if (!$this->mp_es_retenible($move->get_id_mp())) {
            error_log("(!)retenedor_afip.php -> El MP del movimiento ID: $idMove no es retenible.");
            return FALSE;
        }

        //verficar que el marchand sea retenible
        if(($retenerIva === TRUE AND $this->tiene_excepcion_mipyme($move->get_id_marchand(), 'IVA'))
            OR ( $retenerGnc === TRUE AND $this->tiene_excepcion_mipyme($move->get_id_marchand(), 'GANANCIAS'))
        ){
            error_log("(!)retenedor_afip.php -> No es retenible el movimiento con el ID: $idMove porque el marchand tiene certificado MiPyme vigente.");
            return FALSE;
        }
        
        //Verifica que el monotributo del marchand del movimiento
        $marchand = new Marchand();
        $marchand->get($move->get_id_marchand());
        
        if(self::tiene_excepcion_monotributo($marchand->get_documento())){
            error_log("(!)retenedor_afip.php -> No es retenible el movimiento con el ID: $idMove porque el marchand es monotributista.");
            return FALSE;
        }
        
        return TRUE;
    }
    
    public static function tiene_excepcion_monotributo($cuit_marchand) {
        $estado_padron = Sujeto_retencion_afip::select(["cuit" => $cuit_marchand]);
        
        if($estado_padron->rowCount() != 0){
            $padron = new Sujeto_retencion_afip($estado_padron->fetchRow());
            //error_log("MONOTRIBUTO:" . $padron->get_regimen_monotributo());
            return $padron->get_regimen_monotributo() != 'NI';
            
        }else{
            return false;
        }
    }
}