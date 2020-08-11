<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of costeador_galicia_reverso
 *
 * @author ariel
 */
class Costeador_galicia_reverso_nuevo extends Costeador_galicia_nuevo{
    
//    protected function obtener_recordset()
//	{
//            error_log("Reversos");
//            $variables=array();
//            $variables[]=Mp::DEBITO_AUTOMATICO_REVERSO;
//            $variables[]=Authstat::SABANA_DEBITO_A_REVERTIR;
//
//            return Sabana::registros_a_costear_galicia($variables,$this->limite_de_registros_por_ejecucion);
//	}
        protected function obtener_y_actualizar_agenda_vinvulo(Sabana $sabana, $estado_original_sabana, Barcode $barcode)
        {
		error_log($barcode->get_barcode());
                # Falta verificar que el la Agenda_vinvulo no esté Pagada!
                $this->developer_log("Actualizando Agenda_vinvulo REVERSO.");
                $recordset=Agenda_vinvulo::select_desde_id_barcode_reverso($barcode->get_id_barcode(),$sabana->get_monto(),$sabana->get_fecha_vto());
                if(!$recordset OR $recordset->RowCount()!=1){
			$this->developer_log("Intentando encontrar la agenda solo por id_barcode ". $barcode->get_id_barcode());
			$recordset=Agenda_vinvulo::select(array("id_barcode"=>$barcode->get_id_barcode()));
                        if($recordset AND $recordset->RowCount()==0){
                                $this->developer_log("No hay ningún débito asociado al código de barras '".$barcode->get_barcode()."'. ");
                                return false;
                        }
                        else {
                                $this->developer_log("Hay mas de un débito para el código de barras '".$barcode->get_barcode()."'. ");
                                if(!self::PERMITIR_ACTUALIZACION_DE_MULTIPLES_AGENDA_VINVULO){
                                        return false;
                                }
                                else{
                                        $this->developer_log("ALERTA: Actualizando un solo registro de agenda_vinvulo. ");
                                }
                        }

                }
                $agenda_vinvulo=new Agenda_vinvulo($recordset->FetchRow());
                $agenda_xml=$this->obtener_motivo_rechazo($sabana, $estado_original_sabana);
                if($agenda_xml){
                        $agenda_vinvulo->set_agenda_xml($agenda_xml);
                }
                if(!($id_authstat=$this->obtener_estado_a_actualizar_agenda_vinvulo($estado_original_sabana))){
                        $this->developer_log('Ha ocurrido un error al obtener el estado a actualizar en la agenda_vinvulo. ');
                        return false;
                }

                $agenda_vinvulo->set_id_authstat($id_authstat);

                if($agenda_vinvulo->set()){
                    if(self::ACTIVAR_DEBUG)
                        developer_log("Actualizando Agendas Adicionales.");
                        $rs_traer= Agenda_vinvulo::select_debitos_vencimientos_pendientes($sabana->get_id_barcode(),1);
                        $error=false;
                        foreach ($rs_traer as $agenda){
                            $agenda_obj=new Agenda_vinvulo($agenda);
                            $id_authstat_vencimiento=$this->obtener_estado_a_actualizar_agenda_adicional($estado_original_sabana,$agenda_obj);
                            $motivo_vencimiento=$this->obtener_motivo_agenda_adicional($estado_original_sabana,$agenda_obj);
                            $agenda_obj->set_id_authstat($id_authstat_vencimiento);
                            $agenda_obj->set_agenda_xml($motivo_vencimiento);
                            if(!$agenda_obj->set()){ 
				$error=true;
                                if (self::ACTIVAR_DEBUG)
                                    developer_log("No se pudo actualizar la agenda adicional MOTIVO: ".$agenda_obj->get_agenda_xml());
                            }
                        }
                        if(!$error){
                            if (self::ACTIVAR_DEBUG)
                                developer_log("Actualizado Correctamente.");
                            return $agenda_vinvulo;
                        }
                }
                return false;
        }





//select_desde_id_barcode_reverso

}
