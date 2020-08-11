<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of retencion_iva
 *
 * @author ariel
 */
class Retencion_iva extends Retencion_impositiva {

    const TIPO = "IVA";
    const MP = MP::RETENCION_IMPOSITIVA_IVA; //temp 
    const PARAMETRO_MAXIMO_SIN_RETENCION = 50000;
    const PARAMETRO_MAXIMA_CANTIDAD_SIN_RETENCION = 10;

    public $alicuota;
    public $id_mp = false;
    private $result;
    public static $TOPEADO=false;
    public static $RECIENTE_TOPEO = false;
    const ACTIVAR_DIFERENCIA_TARJETA = true;
    CONST ACTIVAR_RETENCION=true;
    public $debe_procesar=false;
    public function debe_procesar_retenciones($id_mp) {
developer_log("ANTES DEL IF COLO");       
 if (!static::ACTIVAR_RETENCION) {
            developer_log("RETENCION DESACTIVADA");
            return false;
        }
        $this->id_mp = $id_mp;
        $mp = $this->get_mp($id_mp);
        if ($this->buscar_mipyme(static::TIPO)){
            developer_log("EN mipyme NO SE RETIENE ".static::TIPO);
            return false;
        }
        developer_log("verificando si debe procesar".get_class($this) . "para id_mp ".$id_mp);
        if ($mp->get_sentido_transaccion() == "ingreso" AND $mp->get_sumaresta() == 1){
            developer_log("Verificando sujeto");
           $result= $this->esta_sujeto_a_retenciones();
            if($result){
               $this->debe_procesar=true;
                return $result; 
        }
                   }
        return false;
    }
    public function es_monotributista(Marchand $marchand){
        if($marchand->get_id_civa() == Civa::MONOTRIBUTO){
            return true;
        }
        return false;
    }

    public function debe_procesar($id_mp) {
        if (!static::ACTIVAR_RETENCION) {
            developer_log("RETENCION DESACTIVADA");
            return false;
        }
        if ($this->buscar_mipyme(static::TIPO)){
            developer_log("EN mipyme NO SE RETIENE ".static::TIPO);
            return false;
        }
        if($this->es_monotributista($this->marchand)){
            developer_log("Es monotributista NO SE RETIENE ".static::TIPO);
            return false;
        }
        $mp = $this->get_mp($id_mp);
        if ($mp->get_sentido_transaccion() == "ingreso" and ! in_array($mp->get_id(), array(MP::COBRODIGITAL_COMISION, Mp::RETENCION_IMPOSITIVA, Mp::RETENCION_IMPOSITIVA_IVA, Mp::RETENCION_IMPOSITIVA_GANANCIAS, Mp::COSTO_RAPIPAGO,
                    Mp::COSTO_PAGO_FACIL,
                    Mp::COSTO_PROVINCIA_PAGO,
                    Mp::COSTO_COBRO_EXPRESS,
                    Mp::COSTO_RIPSA,
                    Mp::COSTO_MULTIPAGO,
                    Mp::COSTO_BICA,
                    Mp::COSTO_PRONTO_PAGO))) {
            try{if($this->obtener_estado_impositivo($id_mp)){
                $this->debe_procesar=true;
                return true;
            }
            else
                return false;
            }catch(Exception $e){
                developer_log($e->getMessage());
                return false;
            }
        }
        return FALSE;
    }
    
    protected function esta_sujeto_a_retenciones($id_mp = false) {
        developer_log("verificando sujeto para id_mp: ".$id_mp);
        try{
            $this->result = $this->obtener_estado_impositivo($id_mp);
        } catch (Exception $e){
            developer_log("throw2:".$e->getMessage());
	    return false;
        }
	developer_log("ESTADO IMPOSITIVO ". json_encode($this->result));
        if ($this->result == false) {
            return false;
		//$regimen_iva= $is_tarjeta= $is_lista=0;
        }
        ///recursion no controlada
        else 
            list($regimen_iva, $is_tarjeta, $is_lista) = $this->result;
        developer_log("$regimen_iva, $is_tarjeta, $is_lista");
        $this->alicuota = $this->obtener_alicuota_iva($regimen_iva, $is_tarjeta, $is_lista);
        developer_log($this->alicuota->get_id_iva_ganancia());
//        exit();
        if ($this->alicuota == false) {
            developer_log("El marchand no tiene Retencion por IVA SE COBRA 10.5");
            return false;
        }
        return $this;
    }
    
    

    protected function obtener_estado_impositivo($id_mp) {
      developer_log("obtener_estado_impositivo IVA");
        $regimen_iva = 0;
        $is_tarjeta = 0;
        $is_lista = 0;
        $mp = $this->get_mp($id_mp);
        if ($this->buscar_mipyme(static::TIPO)){
            throw new Exception("EN mipyme NO SE RETIENE ".static::TIPO);
            return false;
        } //si da true es que existe y no hay que retener
        developer_log("verificando tipo de mp");
        if ($mp->get_sentido_transaccion() == "ingreso" and ! in_array($mp->get_id(), array(MP::COBRODIGITAL_COMISION, Mp::RETENCION_IMPOSITIVA, Mp::RETENCION_IMPOSITIVA_IVA, Mp::RETENCION_IMPOSITIVA_GANANCIAS, Mp::COSTO_RAPIPAGO,
                    Mp::COSTO_PAGO_FACIL,
                    Mp::COSTO_PROVINCIA_PAGO,
                    Mp::COSTO_COBRO_EXPRESS,
                    Mp::COSTO_RIPSA,
                    Mp::COSTO_MULTIPAGO,
                    Mp::COSTO_BICA,
                    Mp::COSTO_PRONTO_PAGO,
                    Mp::DEBITO_AUTOMATICO_COSTO_RECHAZO,
                    Mp::DEBITO_AUTOMATICO_COSTO_REVERSO))) {
            developer_log("es un mp retenible ");
            $recordset = Sujeto_retencion_afip::select(array("cuit" => $this->marchand->get_documento()));
            developer_log("Verificando sujeto retencion");
            if ($recordset->rowCount() > 0) {
                $sujeto_retencion = new sujeto_retencion_afip($recordset->fetchRow());
                if (self::ACTIVAR_DIFERENCIA_TARJETA) {
                    $is_tarjeta = (int) ($mp->get_id_mp() == Mp::TARJETA);
                    if ($is_tarjeta == 1)
                        $is_lista = (int) $this->buscar_lista($this->marchand->get_documento());
                }
                developer_log("Codigo encontrado".trim($sujeto_retencion->get_regimen_iva()));
                switch ( trim($sujeto_retencion->get_regimen_iva()) ) {
                    case "AC":
                        $regimen_iva = 1;
                        break;
                    case "EX":
                    case "NA":
                    case "XN":
                    case "AN":
                        developer_log("El marchand esta exento de IVA");
                        return false;
                        break;
                    case "NI":
                        $regimen_iva = 0;
                        break;
                }
                developer_log("Verificando monotributo");
                switch ( trim($sujeto_retencion->get_regimen_monotributo()) ) {
                    case "F":
                    case "BC":
                    case "BV":
                    case "61":
                    case "A":
                    case "C":
                    case "K":
                    case "E":
                    case "G2":
                    case "H":
                    case "G":
                    case "J":
                    case "I":
                    case "BL":
                    case "BT":
                    case "D":
                    case "H2":
                    case "BP":
                    case "B":
                        developer_log(trim($sujeto_retencion->get_regimen_monotributo()));
                        developer_log("El marchand esta exento de IVA POR MONOTRIBUTO");
                        throw  new Exception("El marchand esta exento de IVA POR MONOTRIBUTO");
                        break;
                }
            }
            developer_log("retornando resultados");
            error_log("VARIABLES: $regimen_iva, $is_tarjeta, $is_lista");
            return array($regimen_iva, $is_tarjeta, $is_lista);
        }
        else developer_log("no deberia retener nada ");
        return null;
    }

    protected function obtener_alicuota_iva($regimen_iva, $is_tarjeta, $is_lista) {
        $recordset = Iva_ganancia::select(array("regimen" => "IVA", "iva_activo" => $regimen_iva, "is_tarjeta" => $is_tarjeta, "is_lista" => $is_lista));
        $row = $recordset->fetchRow();
        $iva = new Iva_ganancia($row);
        return $iva;
    }

    protected function calcular_retencion($moves) {

        return (float)$moves->get_monto_pagador() * ((float)$this->alicuota->get_porcentaje_monto() / 100);
    }

    private function buscar_lista($cuit) {
        $recordset = Excepciones_afip::esta_en_lista($cuit);
        if (!$recordset)
            return false;
        $row = $recordset->fetchRow();
        if ($row["cantidad"] > 0)
            return true;
        return false;
    }

    protected function obtener_alicuota() {
        return $this->alicuota;
    }

    public static function deducir_id_entidad($id_mp, $id_marchand = false, Transas $transas = null, Sabana $sabana = null) {
        return Entidad::ENTIDAD_ALICUOTA;
    }

    public function retener(\Moves $moves) {
        ///recursion incontrolada
developer_log("FUNCION RETENER EN TRAIT IVA");
        if ($this->result == null) {
            $this->result = $this->obtener_estado_impositivo($moves->get_id_mp());
        }
        if ($this->result == false) {
            $regimen_iva= $is_tarjeta= $is_lista=0;
        }
        ///recursion no controlada
        else 
            list($regimen_iva, $is_tarjeta, $is_lista) = $this->result;
        
        if ($regimen_iva == 0) {
            $this->debe_procesar=true;           
            if(!$this->validar_retencion($moves)){
                developer_log("RETENCION NO VALIDADA LINEA 247 IVA");
                return false;
            }
            else{
                developer_log("RETENCION VALIDA");
            }
            //continua con la retencion
        }
        return parent::retener($moves);
    }

    public function validar_retencion(Moves $moves) {
        developer_log("IVA");
        
        if(self::$TOPEADO==true){
            developer_log("topeado POR SISTEMA");
            return true;
        }
        if(!$this->debe_procesar){
        developer_log("sale por aca gafo");
     return false;
}
        if (!No_inscriptos_tope::esta_topeado($this->marchand)) {
            if (Retenciones_no_inscr::esta_dentro_del_limite($this->marchand)) {
                developer_log("ESTA DENTRO DEL LIMITE");
                Retenciones_no_inscr::guardar_retencion_futura($moves);
                developer_log("SE GUARDA LA RETENCION PARA FUTURAS RETENCIONES");
                return false;
            } else {
                if (No_inscriptos_tope::topear($this->marchand)) {
                    developer_log("MARCHAND TOPEADO POR BASE");
                    self::$TOPEADO=true;
                    self::$RECIENTE_TOPEO=true; //para que ganancias sepa que recien se topeo y que no tiene que retener
                    $transaccion_iva = new Retencion_iva($this->marchand->get_id_marchand());
                    $transaccion_ganancias = new Retencion_ganancias($this->marchand->get_id_marchand());
		developer_log('RETENIENDO IVA');
                    $transaccion_iva->retener($moves);
//                        throw new Exception_costeo("Error al retener iva post topeo");
		developer_log('RETENIENDO GANANCIAS');
                    $transaccion_ganancias->retener($moves);
                        //throw new Exception_costeo("Error al retener ganancias post topeo");
                    
                    self::$RECIENTE_TOPEO=false;
    		developer_log('RETENIENDO ATRASADOS');
                    if (!Retenciones_no_inscr::retener_atrasado($this->marchand)) {
                        throw new Exception_costeo("Error al retener movimientos anteriores");
                    }
                    else{
                        developer_log("TERMINA PROCESO DE ATRASADOS.");
                        return true;
                    }
                } else {
                    developer_log("Error al aplicar tope");
                    throw new Exception_costeo("Error al aplicar tope");
                }
            }
        }
        else{ 
            developer_log("NO topeado");
            self::$TOPEADO=true;
            return true;
        }
        return false;
    }

}
