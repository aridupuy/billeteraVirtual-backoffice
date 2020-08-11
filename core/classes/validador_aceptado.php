<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of validador_aceptado
 *
 * @author ariel
 */
class Validador_aceptado extends Validador_mp {

    public function obtener_barcodes_a_procesar($barcodes) {
        $aprobados = $barcodes["approved"];
	
        return $aprobados;
    }
    protected function obtener_semaforo(){
        if (($id_mp=220)) {
            $recordset=Mp::semaforo_libre_para_costear($id_mp);
            developer_log($recordset->RowCount());
            if ($recordset and $recordset->RowCount()==1) {
                $mp=new Mp($recordset->FetchRow());
                $mp->set_nops(Mp::SEMAFORO_OCUPADO);
                if ($mp->set()) {
                    return $mp;
                }
            }
        }
        Gestor_de_correo::enviar(Gestor_de_correo::MAIL_DESARROLLO, Gestor_de_correo::MAIL_DESARROLLO, "Bloqúeo de Validador", "El validador mercadopago no pudo obtener el semaforo para el id_mp:".$id_mp);
        return false;
    }
    public function procesar() {

        $total = count($this->barcodes);
        developer_log("Ejecutando aprobados. Cantidad encontrada $total");
        $j = 1;
        $correctos = 0;
        $incorrectos = 0;
//	if($this->obtener_semaforo())
        foreach ($this->barcodes as $array) {
            if(Model::HasFailedTrans())
               	Model::CompleteTrans ();
            Model::StartTrans();
  //          var_dump($array);
            self::$mp=new Mp();
            self::$mp->get(Mp::TARJETA);
            developer_log("Procesando barcode aprobado $j / $total");
            $j++;
	
//            if (count($array["barcode"]) > 29)
                $barcode_str = substr($array["barcode"], 0, 29);
    //        else
      //          $barcode_str = $array["barcode"];
	    developer_log("procesando barcode: $barcode_str");
            $importe = $array["monto"];
            $fecha_appro = $array["fecha_aprobado"];
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
                    if(isset($row["id_debito"])){
                        $debito_tco=new Debito_tco($row);
                        developer_log("Por Debito tco");
                    }
                    else{
                        $barcode = new Barcode($row);
                        developer_log("Por barcode");
                    }
		    if(isset($rs_transas))
                        unset($rs_transas);
		     if(isset($barcode) and $barcode!=null){
                        $rs_transas = Transas::select(array("id_entidad" => Entidad::ENTIDAD_BARCODE, "id_referencia" => $barcode->get_id_barcode(),"gateway_op_id"=>$array["id_mercado_pago_transaction"]));
//                        var_dump(array("id_entidad" => Entidad::ENTIDAD_DEBITO_TCO, "id_referencia" => $barcode->get_id(),"gateway_op_id"=>$array["id_mercado_pago_transaction"]));
                    }
                    if (!isset($rs_transas) OR  !$rs_transas OR $rs_transas->rowCount() ==0 and $debito_tco!=null) {
                        $rs_transas = Transas::select(array("id_entidad" => Entidad::ENTIDAD_DEBITO_TCO, "id_referencia" => $debito_tco->get_id_debito(),"gateway_op_id"=>$array["id_mercado_pago_transaction"]));
		    }
                    if ($rs_transas and $rs_transas->rowCount() >=1 and ($row=$rs_transas->fetchRow())!=false) {
                        developer_log("Transa encontrada actualizando.".$rs_transas->rowCount());
//                        $row=$rs_transas->fetchRow();
//			var_dump($row);
			developer_log(json_encode($row));
			$transa = new Transas($row);
			developer_log("Transa a tratar".$transa->get_id()." entidad: ".$transa->get_id_entidad()." referencia: ".$transa->get_id_referencia());
                        //actualiza solo si esta pendiente.
                        if ($transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_PENDIENTE OR $transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_UFO OR $transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_UFO_AMIGABLE) {
                            $transa->set_id_authstat(Authstat::MERCADOPAGO_TRANSACCION_APROBADA);
                            if ($transa->set()) {
                                if($debito_tco!=null)
                                    if(!$this->actualizar_debito($debito_tco, Authstat::DEBITO_DEBITADO)){
                                            developer_log("no existe la agenda");
                                            //Model::FailTrans();
                                    }
				else
					developer_log("Agenda encontrada y actualizada");
                                developer_log("Transa insertada correctamente.");
                            } else {
                                Model::FailTrans();
                                developer_log("Error al insertar la transa barcode: " . $barcode->get_barcode() . ".");
                            }
                        } elseif (!($transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_PENDIENTE OR $transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_UFO OR $transa->get_id_authstat() == Authstat::MERCADOPAGO_TRANSACCION_UFO_AMIGABLE)) {
                            //var_dump($row);
				developer_log("No hace falta actualizar. :".$transa->get_id());
				if($debito_tco!=null)
                                if(!$this->actualizar_debito($debito_tco, Authstat::DEBITO_DEBITADO)){
                                        developer_log("no existe la agenda");
                                 //Model::FailTrans();
                                }else {developer_log("Debito Actualizado");}
                        }
                    } else {
//var_dump($this->cuentas);
//var_dump($array);
                        developer_log("Transa no encontrada creando.");
                        if (!$this->insertar_transas($array, $this->cuentas[$array["cuenta"]], $barcode,$debito_tco, Authstat::MERCADOPAGO_TRANSACCION_APROBADA)) {
                            developer_log("Error al insertar la transa.");
                            Model::FailTrans();
                        }
                        if($debito_tco!=null)
                            if(!$this->actualizar_debito($debito_tco, Authstat::DEBITO_DEBITADO)){
                                developer_log("Fallo la actualizacion de la agenda");
                                Model::FailTrans();
                            }
                    }
//                    if ($transacciones->crear($barcode->get_id_marchand(), Mp::TARJETA, $array["monto"], $fecha_appro, $barcode->get_id())) {
//                        developer_log("Pago encontrado y costeado." . $barcode->get_barcode());
//                    } else {
//                        developer_log("error al costear " . $barcode->get_barcode());
//                        Model::FailTrans();
//                    }
                }
            } else {
                developer_log("Barcode $barcode_str no encontrado.");
		developer_log(json_encode($array));
                Model::FailTrans();
            }
            if (Model::CompleteTrans()) {
                developer_log("Procesado correctamente.");
                $correctos++;
            } else {
                developer_log("Error al Procesar Barcode:" . $barcode_str);
                $incorrectos++;
            }
        }
/*	else{
                developer_log("Estamos costeando no se puede validar ahora.");
                return array(0,0,0);
            }*/
        developer_log("Procesados correctamente $correctos");
        developer_log("Procesados incorrectamente $incorrectos");
	$this->liberar_semaforo(220);
        return array($correctos,$total,$incorrectos);
    }
    protected function liberar_semaforo($id_mp)
    {  
        $mp=new Mp();
	$mp->set_id_mp($id_mp);
        $mp->set_nops(Mp::SEMAFORO_LIBRE);
        if ($mp->set()) {
            return $mp;
        }
        return false;
    }
    public function obtener_estado($cobranza) {
        return "Acreditado";
    }

}
