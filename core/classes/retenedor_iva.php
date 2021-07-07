<?php

class Retenedor_iva extends Retencion_iva{
    //constructor
    public function __construct($id_marchand) {
        parent::__construct($id_marchand);
    } 
  
    public function marchand_retenible($id_marchand){
        //verifica MiPyme
        if (parent::buscar_mipyme(parent::TIPO)){
            error_log("El CUIT se encuentra en listado de excepciones MiPyme. No se retiene " . parent::TIPO);
            
            return FALSE;
        }
    }
     
    public function tiene_excepcion_iva($cuit_marchand) {
        //error_log(json_encode($cuit_marchand));
        $estado_padron = Sujeto_retencion_afip::select(["cuit" => $cuit_marchand]);
        
        if($estado_padron->rowCount() != 0){
            $padron = new Sujeto_retencion_afip($estado_padron->fetchRow());
            //error_log("GANANCIAS: " . $padron->get_regimen_ganancias());
            return $padron->get_regimen_iva() == 'EX';
            
        }else{
            return false;
        }
    }
    
    //FORK A REESCRIBIR
    public function retener(\Moves $moves) {
        //Legado->//recursion incontrolada
        //Control de excepciones
        $marchand = new Marchand();
        $marchand->get($moves->get_id_marchand());
        
        //error_log("MARCHAND CARGADO: " . json_encode($marchand));
        
        //Validaciones
        if($this->tiene_excepcion_iva($marchand->get_documento()) OR Retenedor_afip::tiene_excepcion_monotributo($marchand->get_documento())){
            //error_log("El CUIT ". $marchand->get_documento() . " exento de ganancias o monotributo.");
            
            return FALSE;
        }     
        
        //Inicializa la variable result
        if (!isset($this->result)) {
            $this->result = $this->obtener_estado_impositivo($moves->get_id_mp());
        
        }else if(empty($this->result)) {
            $regimen_iva= $is_tarjeta= $is_lista=0;
            
        }else{ ////recursion no controlada 
            list($regimen_iva, $is_tarjeta, $is_lista) = $this->result;
        }
        
        return Retencion_impositiva::retener($moves);
    }
    
    public function obtener_estado_impositivo($id_mp) {
      developer_log("obtener_estado_impositivo IVA");
        $regimen_iva = 0;
        $is_tarjeta = 0;
        $is_lista = 0;
        
        $mp = $this->get_mp($id_mp);

        if ($mp->get_sentido_transaccion() == "ingreso" and !in_array($mp->get_id(), array(MP::COBRODIGITAL_COMISION, Mp::RETENCION_IMPOSITIVA, Mp::RETENCION_IMPOSITIVA_IVA, Mp::RETENCION_IMPOSITIVA_GANANCIAS, Mp::COSTO_RAPIPAGO,
                    Mp::COSTO_PAGO_FACIL,
                    Mp::COSTO_PROVINCIA_PAGO,
                    Mp::COSTO_COBRO_EXPRESS,
                    Mp::COSTO_RIPSA,
                    Mp::COSTO_MULTIPAGO,
                    Mp::COSTO_BICA,
                    Mp::COSTO_PRONTO_PAGO,
                    Mp::DEBITO_AUTOMATICO_COSTO_RECHAZO,
                    Mp::DEBITO_AUTOMATICO_COSTO_REVERSO))) {
            developer_log("Es un MP retenible: " . (string)$mp->get_id_mp());
            $recordset = Sujeto_retencion_afip::select(array("cuit" => $this->marchand->get_documento()));
            developer_log("Verificando sujeto retencion...");
            if ($recordset->rowCount() > 0) {
                $sujeto_retencion = new sujeto_retencion_afip($recordset->fetchRow());
                if (self::ACTIVAR_DIFERENCIA_TARJETA) {
                    $is_tarjeta = (int) ($mp->get_id_mp() == Mp::TARJETA);
                    if ($is_tarjeta == 1)
                        $is_lista = self::estaEnListaExcepcionesAfip($this->marchand->get_documento());
                }            
                developer_log("Codigo encontrado: ".trim($sujeto_retencion->get_regimen_iva()));
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
                        developer_log("El marchand esta exento de IVA POR MONOTRIBUTO");
                        throw  new Exception("El marchand esta exento de IVA POR MONOTRIBUTO");
                        break;
                }
            }
            developer_log("Retornando resultados...");
            //error_log("VARIABLES: $regimen_iva, $is_tarjeta, $is_lista");
            return array($regimen_iva, $is_tarjeta, $is_lista);
        }
        else developer_log("No deberia retenerse nada!");
        return null;
    }
    
    public static function estaEnListaExcepcionesAfip($cuit) {
            $recordset = Excepciones_afip::esta_en_lista($cuit);
            $excepciones_lista = !empty($recordset) ? $recordset->fetchRow() : FALSE;
            $is_lista = (!empty($excepciones_lista) AND $excepciones_lista['cantidad'] > 0 ) ? 1 : 0;
            
            return $is_lista;
    }
}

