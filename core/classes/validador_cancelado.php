<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of validador_cancelado
 *
 * @author ariel
 */
class Validador_cancelado extends Validador_mp{
    //put your code here
    public function obtener_barcodes_a_procesar($barcodes) {
        $cancelados = $barcodes["cancelled"];
        return $cancelados;
    }

    public function procesar() {
        $total = count($this->barcodes);
        developer_log("Ejecutando Cancelados. Cantidad encontrada $total");
        $j = 1;
        $correctos = 0;
        $incorrectos = 0;
        foreach ($this->barcodes as $array) {
            Model::StartTrans();
            self::$mp = new Mp();
            self::$mp->get(Mp::TARJETA);
            developer_log("Procesando barcode cancelado $j / $total");
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
                $recordset = Debito_tco::select(array("id_debito" => $barcode_str));    
            }
            if ($recordset and $recordset->rowCount() >= 1) {
                if (!Model::HasFailedTrans()) {
                    $row=$recordset->fetchRow();
                    if(isset($row["id_debito"]))
                        $debito_tco=new Debito_tco($row);
                    
                    else
                        $barcode = new Barcode($row);
                    
                    $rs_transas = Transas::select(array("id_entidad" => Entidad::ENTIDAD_BARCODE, "id_referencia" => $barcode->get_id_barcode(),"gateway_op_id"=>$array["id_mercado_pago_transaction"]));
                    if ($rs_transas and $rs_transas->rowCount() ==0 and $debito_tco!=null) {
                        $rs_transas = Transas::select(array("id_entidad" => Entidad::ENTIDAD_DEBITO_TCO, "id_referencia" => $debito_tco->get_id_debito(),"gateway_op_id"=>$array["id_mercado_pago_transaction"]));
                    }
                    if ($rs_transas and $rs_transas->rowCount() >= 1) {
                        developer_log("Transa encontrada actualizando.");
                        $transa = new Transas($rs_transas->fetchRow());
                        //actualiza solo si esta pendiente.
                        if ($transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_PENDIENTE OR $transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_UFO OR $transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_UFO_AMIGABLE) {
                            $transa->set_id_authstat(Authstat::MERCADOPAGO_TRANSACCION_RECHAZADA);
                            if ($transa->set()) {
                                developer_log("Transa insertada correctamente.");
                            } else {
                                Model::FailTrans();
                                developer_log("Error al insertar la transa barcode: " . $barcode->get_barcode() . ".");
                            }
                        } elseif (!($transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_PENDIENTE OR $transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_UFO OR $transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_UFO_AMIGABLE)) {
                            developer_log("No hace falta actualizar.");
                        }
                    } else {
                        developer_log("Transa no encontrada creando.");
                        if (!$this->insertar_transas($array, $this->cuentas[$array["cuenta"]], $barcode,$debito_tco, Authstat::MERCADOPAGO_TRANSACCION_RECHAZADA)) {
                            developer_log("Error al insertar la transa.");
                            Model::FailTrans();
                        }
                        
                        if (!$this->actualizar_debito($debito_tco, Authstat::DEBITO_OBSERVADO)) {
                            developer_log("Fallo la actualizacion de la agenda");
                            Model::FailTrans();
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
    public function obtener_estado($cobranza){
        return "Cancelado";
    }

}
