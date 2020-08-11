<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of validador_contracargo
 *
 * @author ariel
 */
class Validador_contracargo extends Validador_mp {

    const ACTIVAR_REVERSO = false;

    public function obtener_barcodes_a_procesar($barcodes) {
        $contracargos = $barcodes["charged_backd"];
        return $contracargos;
    }

    public function procesar() {
        $total = count($this->barcodes);
        developer_log("Ejecutando Contracargos. Cantidad encontrada $total");
        $j = 1;
        $correctos = 0;
        $incorrectos = 0;
        foreach ($this->barcodes as $array) {
            Model::StartTrans();
            self::$mp = new Mp();
            self::$mp->get(Mp::TARJETA);
            developer_log("Procesando barcode contracargado $j / $total");
            $j++;
//            if (count($array["barcode"]) > 29)
                $barcode_str = substr($array["barcode"], 0, 29);
  //          else
    //            $barcode_str = $array["barcode"];
            $importe = $array["monto"];
            $fecha_appro = $array["fecha_aprobado"]; //en duda
            $fecha_peticion = $array["fecha_peticion"];
            $fecha_appro = DateTime::createFromFormat("Y-m-dTh:i:s.u-e:00", $fecha_appro);
            $fecha_peticion = DateTime::createFromFormat("Y-m-dTh:i:s.u-e:00", $fecha_peticion);
            $recordset = Barcode::select(array("barcode" => $barcode_str));
            $barcode=null;
            $debito_tco=null;
            if($recordset and $recordset->rowCount() == 0 and strlen($barcode_str)<=29){
                $recordset = Debitos_tco::select(array("id_debito" => $barcode_str));    
            }
            if ($recordset and $recordset->rowCount() >= 1) {
                if (!Model::HasFailedTrans()) {
                    $row=$recordset->fetchRow();
                    if(isset($row["id_debito"]))
                        $debito_tco=new Debito_tco($row);
                    
                    else
                        $barcode = new Barcode($row);
                    
                    $rs_transas = Transas::select(array("id_entidad" => Entidad::ENTIDAD_BARCODE, "id_referencia" => $barcode->get_id_barcode(),"gateway_op_id"=>$array["id_mercado_pago_transaction"]));
                    if ($rs_transas and $rs_transas->rowCount() >= 1) {
                        developer_log("Transa encontrada actualizando.");
                        $transa = new Transas($rs_transas->fetchRow());
                        //actualiza solo si esta pendiente.
                        if ($transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_PENDIENTE OR $transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_UFO OR $transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_UFO_AMIGABLE OR  $transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_APROBADA OR $transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_VERIFICADA) {
                            $transa->set_id_authstat(Authstat::MERCADOPAGO_TRANSACCION_RECHAZADA);
                            if ($transa->set()) {
                                developer_log("Transa insertada correctamente.");
                            } else {
                                Model::FailTrans();
                                developer_log("Error al insertar la transa barcode: " . $barcode->get_barcode() . ".");
                            }
                            if (!Model::FailTrans() and self::ACTIVAR_REVERSO AND !$this->reversar($barcode)) {
                                developer_log("Fallo al reversar el movimiento.");
                                Model::FailTrans();
                            }
                        } elseif (!($transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_VERIFICADA AND $transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_APROBADA AND $transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_PENDIENTE OR $transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_UFO OR $transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_UFO_AMIGABLE)) {
                            developer_log("No hace falta actualizar.");
                        }
                    } else {
                        developer_log("Transa no encontrada creando.");
                        if (!$this->insertar_transas($array, $this->cuentas[$array["cuenta"]], $barcode, Authstat::MERCADOPAGO_TRANSACCION_RECHAZADA)) {
                            developer_log("Error al insertar la transa.");
                            Model::FailTrans();
                        }
                        if (!$this->actualizar_debito($debito_tco, Authstat::DEBITO_OBSERVADO)) {
                            developer_log("Fallo la actualizacion de la agenda");
                            Model::FailTrans();
                        }
                        if (self::ACTIVAR_REVERSO AND !($moves=$this->reversar($barcode))) {
                            developer_log("Fallo al reversar el movimiento. o ya reversado.");
                            Model::FailTrans();
                        }
                        else{
                            $this->enviar_correos($moves);
                        }
                    }
                    
                }
            } else {
                developer_log("Barcode $barcode_str no encontrado.");
                Model::FailTrans();
            }
            if (!Model::HasFailedTrans() and Model::CompleteTrans()) {
                developer_log("Procesado correctamente.");
                $correctos++;
            } else {
                developer_log("Error al Procesar Barcode:" . $barcode_str);
                $incorrectos++;
            }
        }
        developer_log("Procesados correctamente $correctos");
        developer_log("Procesados incorrectamente $incorrectos");
        return array($correctos,$total,$incorrectos);
    }

    public function obtener_estado($cobranza) {
        return "Contracargo";
    }

    private function reversar(Barcode $barcode) {
        developer_log("Reversando contracargo.");
        $recordset = Moves::select(array("id_entidad" => Entidad::ENTIDAD_BARCODE, "id_referencia" => $barcode->get_id(), "id_mp" => Mp::TARJETA));
        $transaccion=new Transaccion();
        if($recordset and $recordset->rowCount()==1){
            $moves=new Moves($recordset->fetchRow());
            if(!$this->ya_reversado($moves))
                if($transaccion->reversar($moves)){
                    return $moves;
                }
            else  
                return false;
            return true;
            
        }
        return false;
    }
    private function ya_reversado(Moves $moves){
        $recordset= Moves::select(array("id_entidad"=> Entidad::ENTIDAD_MOVES,"id_referencia"=>$moves->get_id_moves(),"id_mp"=>Mp::REVERSO_DE_INGRESO));
        if($recordset->rowCount()!==0)
            return true;
        return false;
    }
    private function enviar_correos(Moves $moves){
            $marchand=new Marchand();
            $marchand->get($moves->get_id_marchand());
            $correo_cliente=$marchand->get_email();
            $correo_operaciones= "Operaciones@cobrodigital.com";
            $asunto="reverso de transaccion";
            $barcode=new Barcode();
            $barcode->get($moves->get_id_referencia());
            $mensaje_cliente="reverso de transaccion ".$moves->get_id().". Motivo Contracargo por parte del pagador."
                    . " Referencia: ".$barcode->get_barcode().".";
            $mensaje_operaciones="Nuevo reverso por contracargo. Datos: ".json_encode((array) $moves);
            Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, $correo_cliente, $asunto, $mensaje_cliente);
            Gestor_de_correo::enviar(Gestor_de_correo::MAIL_COBRODIGITAL_INFO, $correo_operaciones, $asunto, $mensaje_operaciones);
            return true; 
    }
}
