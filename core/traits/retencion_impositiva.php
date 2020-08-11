<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of retencion_impositiva
 *
 * @author ariel
 */
abstract class Retencion_impositiva extends Transaccion{
    
    protected $alicuota;
    protected $marchand;
    protected $mp_retencion=null;
    CONST ACTIVAR_RETENCION=true;
    public function __construct($id_marchand) {
        $this->marchand = new Marchand();
        $this->marchand->get($id_marchand);
	developer_log('documento');
	developer_log($this->marchand->get_documento());
    }
    public function retener(Moves $moves){
        self::$grado_de_recursividad=0;
        $this->marchand=new Marchand();
        $this->marchand->get($moves->get_id_marchand());
        if($this->debe_procesar_retenciones($moves->get_id_mp())){
           developer_log("entro retencion imp GAFOO");
             $id_mp= STATIC::MP;
            $id_marchand=$moves->get_id_marchand();
            $monto_pagador=$this->calcular_retencion($moves);
            if($monto_pagador==0){
                //salgo sin realizar la retencion;
                developer_log("Monto 0");
                return true;
            }
            
            $fecha= DateTime::createFromFormat('Y-m-d H:i:s', $moves->get_fecha_liq());
            $id_referencia= $this->alicuota->get_id();
	try{
            if(($transaccion=parent::crear($id_marchand, $id_mp, $monto_pagador, $fecha, $id_referencia))!=false){
                $retencion= new Retencion();
                $retencion->set_fecha_gen("now()");
                $retencion->set_id_alicuota($this->alicuota->get_id());
                $retencion->set_id_marchand($moves->get_id_marchand());
                $retencion->set_id_move($moves->get_id());
                $retencion->set_id_moves_retencion($transaccion->moves->get_id_moves());
                $retencion->set_monto_retenido($monto_pagador);
                $retencion->set_monto_total($moves->get_monto_pagador());
                $retencion->set_fecha_liq($moves->get_fecha_liq());
                $retencion->set_tipo(STATIC::TIPO);
                if($retencion->set()){
                    developer_log("retencion generada");
                    return true;
                }
                else {
                    throw  new Exception("Error al guardar datos de la retencion");
                }
            }

            else                
                throw  new Exception("Error al Generar el movimiento retencion");
	}catch(Exception $e){
		developer_log('Error al tratar de retener');
	}
        }
	developer_log('sale por aca');
        return false;
    }
    public function obtener_fecha_de_liquidacion($fecha, $id_mp, $id_marchand) {
        
        return new DateTime("now");
    }
    protected function deducir_id_authstat($id_mp) {
        return Authstat::SABANA_COBRADA;
        
    }
    protected function deducir_id_tipomove($id_mp) {
        return Tipomove::NOTA_DE_DEBITO;
    }
    //
    public abstract function  debe_procesar($id_mp);
    protected abstract function esta_sujeto_a_retenciones($id_mp = false);
    protected abstract function debe_procesar_retenciones($id_mp);
    protected abstract function calcular_retencion($moves);
    private function obtener_referencia(Moves $moves){
        //la referencia debe ser el id de la alicuota aplicada.
        //para test ponemos la referencia del id_moves;
        return $moves->get_id_referencia();
    } 
    protected abstract function obtener_alicuota(); 
    protected function get_mp($id){
        if($this->mp_retencion==null){
            $this->mp_retencion=new Mp ();
            $this->mp_retencion->get($id);
        }
        return $this->mp_retencion;
    }
    
    public function precalcular_retencion($monto,$id_mp){
        if(!$this->esta_sujeto_a_retenciones($id_mp)){
            return 0;
        }
developer_log("this_obtener_alicuota---->".$this->alicuota->get_porcentaje_monto());
        $this->obtener_alicuota();
        return $monto * ($this->alicuota->get_porcentaje_monto() / 100);
    }
    
    
    protected function buscar_mipyme($tipo){
        $rsd_impuesto = Impuesto_activo::select_impuestos_por_id($tipo);
        $recordset=Excepciones_mipyme::select_certificado_activo($this->marchand->get_id_marchand(),new DateTime("now"),$rsd_impuesto);
        if($recordset->rowCount()==0)
            return false;
        return true;
    }
    
    /**
     * Obtiene la retencion de IVA o Ganancia de CUIT en base al padrón de AFIP.
     * @param string $monto String númerico. El monto base de la operación a retener.
     * @param string $id_mp String númerico. El id del medio de pago para verificar que sea un medio retenible.
     * @param string $cuit CUIT del sujeto al que se va a retener.
     * @param string $impuesto El tipo de impuesto a calcular. 'IVA' o 'GANANCIA'.
     * @param boolean $activar_diferencia_tarjeta Indica si se tiene en cuenta la diferenciación de medios con tarjeta. Valor por defecto: True.
     * @return float Valor de la retención. Tener en cuenta que no se redondea decimales.
     */
    public static function precalcular_retencion_afip($monto, $id_mp, $cuit, $impuesto, $activar_diferencia_tarjeta=TRUE){
        //VALIDACION: pendiente
        
        //$obtener 
        //if(!$this->esta_sujeto_a_retenciones($id_mp)){
        $mp_retenible = self::mp_es_retenible($id_mp);
        $alicuota = self::obtener_alicuota_iva_gnc($cuit, $id_mp, $impuesto);
        
        if(!$mp_retenible OR !$alicuota){
            return $mp_retenible === FALSE ? '0' : NULL;
        }
        
        error_log(json_encode($alicuota));
        developer_log("this_obtener_alicuota---->".$alicuota->get_porcentaje_monto());
        
        return $monto * ($alicuota->get_porcentaje_monto() / 100);
    }
    
    /**
     * Obtiene un objeto Iva_ganancia de un impuesto a partir del sujeto de retencion en padron.
     * @param sujeto_retencion_afip $sujeto_retencion
     * @param string $impuesto Tipo de regimen:  'IVA' o 'GANANCIA'. Valor por defecto: IVA
     * @param boolean $activar_diferencia_tarjeta. Valor por defecto: TRUE.
     * @return \Iva_ganancia Objeto modelo de la alicuota. FALSE es caso de estar excento o se monotributista. NULL en caso de error.
     */
    public static function obtener_alicuota_iva_gnc($cuit, $id_mp, $impuesto = 'IVA', $activar_diferencia_tarjeta=TRUE) {
        
        //VALIDAR ENTRADA E INICIALIZAR ENTRADA
        $impuesto = strtoupper($impuesto);
        
        if(validar_cuit($cuit) AND in_array($impuesto, ['IVA','GANANCIA'])){
            $sujeto_retencion = self::obtenerSujetoRetencionEnPadron($cuit);
     
        }else{
            $debug = debug_backtrace();
            error_log('(!)'.$debug[0]['file'].'==>'.$debug[0]['class'].'==>'.$debug[0]['function'].'==>'.json_encode($debug[0]['args']).'>>>> No se validan los datos de entrada.');
            return NULL;
        }
        
        //Validacion si se encuentra en padron
        if($sujeto_retencion === FALSE){
            //No esta en padron
            $regimen_iva = $is_tarjeta = $is_lista = 0;
            
        }elseif($sujeto_retencion !== NULL) {
            //VERIFICAR ESTADO DE IVA
            $estado = trim(($impuesto === 'IVA' ? $sujeto_retencion->get_regimen_iva() : $sujeto_retencion->get_regimen_ganancias()));
            $regimen_iva = self::verificar_estado_padron($estado);
            
            //VERIFICACION EXCEPCION Y MONOTRIBUTO
            if ($sujeto_retencion->get_regimen_monotributo() !== 'NI' OR $regimen_iva === -1) {
                return FALSE;
            }

            //VERIFICAR TARJETA
            if ($activar_diferencia_tarjeta === TRUE) {
                $is_tarjeta = (int) ($id_mp == Mp::TARJETA);
                //VERIFICAR LISTA DE EXCEPCIONES
                $recordset = Excepciones_afip::esta_en_lista($sujeto_retencion->get_cuit());
                $excepciones_lista = !empty($recordset) ? $recordset->fetchRow() : FALSE;
                $is_lista = (!empty($excepciones_lista) AND $excepciones_lista['cantidad'] > 0 ) ? 1 : 0;
            }
            
        }else{ //RETORNA POR ERROR
            $debug = debug_backtrace();
            error_log('(!)'.$debug[0]['file'].'==>'.$debug[0]['class'].'==>'.$debug[0]['function'].'==>'.json_encode($debug[0]['args']).'>>>> El sujeto de retencion es NULL.');
            return NULL;
        }
        
        //RETORNAR RLTDO
        $recordset = Iva_ganancia::select(array("regimen" => $impuesto, "iva_activo" => $regimen_iva, "is_tarjeta" => $is_tarjeta, "is_lista" => $is_lista));
        $row = $recordset->fetchRow();
        $alicuota = new Iva_ganancia($row);
        
        return $alicuota;
    }
    
    /**
     * Obtiene el regimen_iva para calcular la alicuota a partir del estado de padron de un sujeto retencion.
     * 
     * @param string $estado Estado del regimen del sujeto de retencion AFIP.
     * @return int Retorna 1 si se encuentra activo, 0 si no esta inscripto y -1 si esta excento. NULL si no corresponde a los regimenes de iva o ganancia.
     */
    private static function verificar_estado_padron($estado) {

        switch ($estado) {
            case "AC":
                $regimen_iva = 1;
                break;
            
            case "EX":
            case "NA":
            case "XN":
            case "AN":
                $regimen_iva = -1;
                break;
            
            case "NI":
                $regimen_iva = 0;
                break;
            
            default:
                NULL;
        }
        
        return $regimen_iva;
    }
    
    private static function obtenerSujetoRetencionEnPadron($cuit) {      
        $recordset = Sujeto_retencion_afip::select(["cuit" => $cuit]);
        
        if(!empty($recordset)){
            return $recordset->rowCount() > 0 ? new sujeto_retencion_afip($recordset->fetchRow()) : FALSE;
        }
        
        return NULL; //Hubo un error en la consulta
    }

    //fork de esta_sujeto_a_retenciones()
    private static function mp_es_retenible($id_mp) {
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
}
