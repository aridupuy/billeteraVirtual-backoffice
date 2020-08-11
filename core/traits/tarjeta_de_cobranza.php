<?php

class Tarjeta_de_cobranza extends Boleta_pagador{
	const ID_TIPOPAGO_TARJETA_DE_COBRANZA=900;
	const ANIOS_DE_VENCIMIENTO=10;
    const CONCEPTO='Tarjeta de Cobranza';
    const MODELO='tarjeta_de_cobranza';
    const PATH_PLANTILLA='views/tarjeta_de_cobranza.html';

    public function crear($id_climarchand)
    {
        $concepto=self::CONCEPTO;
        $modelo=self::MODELO;
        $importes=array(0);
        $fecha=date('d/m/Y',strtotime('+'.self::ANIOS_DE_VENCIMIENTO.' year'));
        $fechas_vencimiento=array($fecha);
        $id_tipopago=self::ID_TIPOPAGO_TARJETA_DE_COBRANZA;
        return parent::crear($id_climarchand, $modelo, $fechas_vencimiento, $importes, $concepto,null,$id_tipopago);   
    }
    public static function asociar($id_marchand, $codigo_de_barras, $variables)
    {
        Model::StartTrans();
        # Obtener Objeto Barcode
        if(!Model::HasFailedTrans()){
            $rs_barcode=Barcode::select(array('barcode'=>$codigo_de_barras,'id_marchand'=>$id_marchand));
            if(!$rs_barcode OR $rs_barcode->rowCount()!=1){
                throw new Exception('Ha ocurrido un error al obtener el código de barras '.$codigo_de_barras.".");
            }
            else{
                $barcode=new  Barcode($rs_barcode->FetchRow());
                if($barcode->get_id_tipopago()!=self::ID_TIPOPAGO_TARJETA_DE_COBRANZA){
                    throw new Exception('El código de barras no pertenece a una Tarjeta de Cobranza. ');
                }
            }
        }
        # Obtener Objeto Climarchand
        if(!Model::HasFailedTrans()){
            $rs_climarchand= Climarchand::select_barcode($id_marchand, $barcode->get_barcode());
            if(!$rs_climarchand OR $rs_climarchand->RowCount()!=1){
                throw new Exception('Ha ocurrido un error al obtener al Cliente. ');
            }
            else{
                $climarchand=new Climarchand($rs_climarchand->FetchRow());
            }
        }
        # Actualizar Climarchand
        if(!Model::HasFailedTrans()){
            $pagador=new Pagador();
            # Optimizo
            $pagador::$climarchand=$climarchand;
            try {
                if(!($pagador=$pagador->editar($climarchand->get_id(), $variables))){
                    Model::FailTrans();
                }
                
            } catch (Exception $e) {
                Model::FailTrans();
                throw new Exception($e->getMessage());
            }
        }
        unset($climarchand);
        # Actualizar Barcode
        if(!Model::HasFailedTrans()){
            $barcode_xml_dom=new View();
            $climarchand_xml_dom=new View();
            $barcode_xml_dom->loadXML($barcode->get_bc_xml());
            $climarchand_xml_dom->loadXML($pagador::$climarchand->get_cliente_xml());
            $pagador_xml=$barcode_xml_dom->getElementsByTagName('pagador');
            if(!($pagador_xml->length==1)){
                Model::FailTrans();
            }
            if(!Model::HasFailedTrans()){
                $trix_xml=$pagador_xml->item(0)->parentNode;
                $trix_xml->removeChild($pagador_xml->item(0));
                $pagador_xml_climarchand=$climarchand_xml_dom->getElementsByTagName('pagador');
                $trix_xml->appendChild($barcode_xml_dom->importNode($pagador_xml_climarchand->item(0),true));
                $barcode->set_bc_xml($barcode_xml_dom->saveXML());
                if(!$barcode->set()){
                    Model::FailTrans();
                }
            }
        }
        if(Model::CompleteTrans() AND !Model::HasFailedTrans()) {
            return true;
        }
        return false;
    }
    # Sobre escribo la funcion optimizar_xml para obtener el 
    # HTML de un archivo
    protected function optimizar_xml($modelo,$id_marchand)
    {
        if((self::$xml===false) OR ($id_marchand!==self::$xml->get_id_marchand() OR $modelo!==self::$xml->get_modelo())){
                    
                    self::$xml=new Xml();
                    self::$xml->set_modelo($modelo);
                    self::$xml->set_id_marchand($id_marchand);
                    $xmlfield=new View();
                    $xmlfield->cargar(self::PATH_PLANTILLA);
                    self::$xml->set_xmlfield($xmlfield->saveHTML());
                }
        return self::$xml;
    }
}
?>