<?php

class Boleta_pagador extends Boleta
{
    const ACTIVAR_VERIFICAR_INEXISTENCIA_DE_CODIGO=true;
    const ID_TIPOPAGO_TARJETA_DE_COBRANZA=900;
    const PERMITIR_CLIMARCHANDS_SIN_TRIX=true; # BORRAR ESTO SI YA ES 2017
    public static $climarchand=false;     #Objeto #OPTIMIZAR
    public static $trix=false;             #Objeto #OPTIMIZAR
    public static $xml=false; # Plantilla HTML #Objeto #OPTIMIZAR
    public static $xml_descartable=false; # Estructura de boletas. #Deprecar #Objeto #OPTIMIZAR
    public static $xml_clientes=false; # Estructura de clientes #OPTIMIZAR
    const DUMMY_ID_CLIMA=1;
    
    public function crear($id_climarchand, $modelo, $fechas_vencimiento, $importes, $concepto, $detalle=null,$id_tipopago=false,$cuotas=false)
    {
        if(!is_array($fechas_vencimiento) OR !is_array($importes)){
            throw new Exception('Las fechas de vencimiento o importes no son vectores.');
        }
        if(count($fechas_vencimiento)!==count($importes)){
            throw new Exception('La cantidad de fechas de vencimiento no coincide con la cantidad de importes.');
        }
        if(count($fechas_vencimiento)>4){
            throw new Exception('No es posible generar Boletas con más de cuatro fechas de vencimiento.');
        }
        for ($aux=0; $aux < count($fechas_vencimiento); $aux++) { 
            if(!isset($importes[$aux])){
                throw new Exception("El vector de importes no es correcto.");   
            }
            if(!isset($fechas_vencimiento[$aux])){
                throw new Exception("El vector de fechas de vencimientos no es correcto.");   
            }
        }
        unset($aux);
	Model::StartTrans();
        if(!$this->optimizar_climarchand($id_climarchand)){
            developer_log("Error al optimizar climarchand");
            Model::FailTrans();
        }
        $conf_bol=Configuracion::obtener_config_tag_concepto("vencimientos iguales", self::$climarchand->get_id_marchand());
        error_log($conf_bol["value"]);
	
        if($conf_bol["value"]!='1'){
            if($importes[0]<0){
                # El igual a cero se verifica despues
                throw new Exception('El primer importe debe ser mayor a cero.');
            }
            if(isset($importes[1]) AND $importes[1]<0){
                throw new Exception('El segundo importe debe ser mayor a cero.');
            }
            if(isset($importes[2]) AND $importes[2]<0){
                throw new Exception('El tercer importe debe ser mayor a cero.');
            }
            if(isset($importes[3]) AND $importes[3]<0){
                throw new Exception('El cuarto importe debe ser mayor a cero.');
            }
        }
       $transaccion=new Transaccion();
        $configuracion=new Configuracion();
        $id_marchand= self::$climarchand->get_id_marchand();
        $conf=$configuracion->obtener_config_tag_concepto("traslada_comision", $id_marchand);
        if($conf["value"]==1){
            developer_log("Trasladando comision");
            foreach ($importes as $clave=>$importe){
                $array=$transaccion->calculo_indirecto($id_marchand,
                        Mp::RAPIPAGO, $importe);
                if($array!=false){
                    list($monto_pagador, $pag_fix, $pag_var, $monto_cd, $cdi_fix, $cdi_var, $monto_marchand)=$array;
                    $importes[$clave]=$monto_pagador;
                }
            }
        }
        if(!Model::HasFailedTrans()){
            if(!$this->optimizar_xml($modelo, $id_marchand)){
                developer_log("Error al optimizar xml");
                Model::FailTrans();
            }
        }
        if(!Model::HasFailedTrans()){
            if(!$this->optimizar_xml_descartable($id_marchand)){
                developer_log("Error al optimizar xml descartable");
                Model::FailTrans();
            }
        }       
        $this->bolemarchand=new Bolemarchand();
        $this->bolemarchand->set_id_marchand($id_marchand);
        $this->bolemarchand->set_emitida(date('Y-m-d H:i:s.u'));
        $this->bolemarchand->set_boleta_concepto($concepto);
        $this->bolemarchand->set_detalle($detalle);
        if(!Model::HasFailedTrans()){
            if(count($fechas_vencimiento)===1 AND $id_tipopago===false) # Un solo vencimiento
                $id_tipopago='100';
            elseif($id_tipopago===false) 
                $id_tipopago='501';
		error_log($id_tipopago);
            if($this->barcode_1===false){
                if(($this->barcode_1=$this->generar_codigo_de_barras(self::$climarchand, $importes[0], $fechas_vencimiento[0], $id_tipopago,$concepto))===false){
                    developer_log("Error al generar barcode1");
                    Model::FailTrans();
                }
	     }
	    else if(!$cuotas and $this->barcode_1!==false){
                 if(($this->barcode_1=$this->generar_codigo_de_barras(self::$climarchand, $importes[0], $fechas_vencimiento[0], $id_tipopago,$concepto))===false){
                    developer_log("Error al generar barcode1 de coutas");
                     Model::FailTrans();
		
                   }
            }
        }
        if(!Model::HasFailedTrans() AND isset($importes[1])){
            $id_tipopago='502';
            error_log("502");
            if($conf_bol["value"]!='1')
                if($importes[0]>=$importes[1] AND $importes[1]!=0){
                    throw new Exception("El segundo importe debe ser superior al primero.");

                }
            if(($this->barcode_2=$this->generar_codigo_de_barras(self::$climarchand, $importes[1], $fechas_vencimiento[1], $id_tipopago,$concepto))===false){
                Model::FailTrans();
            }
        }
        if(!Model::HasFailedTrans() AND isset($importes[2])){
            $id_tipopago='503';
            if($conf_bol["value"]!='1')
                if($importes[1]>=$importes[2] AND $importes[2]!=0){

                    throw new Exception("El tercer importe debe ser superior al segundo.");

                }
            if(($this->barcode_3=$this->generar_codigo_de_barras(self::$climarchand, $importes[2], $fechas_vencimiento[2], $id_tipopago,$concepto))===false){
                Model::FailTrans();
            }
        }
        if(!Model::HasFailedTrans() AND isset($importes[3])){
            $id_tipopago='504';
            if($conf_bol["value"]!='1')
                if($importes[2]>=$importes[3] AND $importes[3]!=0){
                    throw new Exception("El cuarto importe debe ser superior al tercero.");

                }
            if(($this->barcode_4=$this->generar_codigo_de_barras(self::$climarchand, $importes[3], $fechas_vencimiento[3], $id_tipopago,$concepto))===false){
                Model::FailTrans();
            }
        }
        
        if(!Model::HasFailedTrans()){
            $this->bolemarchand->set_boletaid($modelo); # Identifica la plantilla
            $this->bolemarchand->set_id_xml(self::$xml_descartable->get_id());  
            $this->bolemarchand->set_id_authstat(Authstat::BOLETA_PENDIENTE_DE_PAGO);
            $total_de_boletas=Bolemarchand::cantidad_de_boletas($id_marchand);
            $this->bolemarchand->set_nroboleta($total_de_boletas+1);       
            $this->bolemarchand->set_id_climarchand(self::$climarchand->get_id());
            $plantilla=clone self::$xml;
            if(!($boleta_html=$this->cruzar_plantilla_y_datos_de_boleta(self::$climarchand, $this->bolemarchand, $plantilla, $this->barcode_1,$this->barcode_2,$this->barcode_3, $this->barcode_4, $detalle))){
                Model::FailTrans();
            }
            else{
                $this->bolemarchand->set_boleta_html($boleta_html); 
            }
        }

        if(!Model::HasFailedTrans()){
           if(!$this->bolemarchand->set()){
                Model::FailTrans();
           }
        }
        if($this->barcode_1 AND !Model::HasFailedTrans()){
            $this->barcode_1->set_id_boletamarchand($this->bolemarchand->get_id());
            if(!$this->barcode_1->set()){
		error_log("Error barcode 1");
                Model::FailTrans();
            } 
        }
        if($this->barcode_2 AND !Model::HasFailedTrans()){
            $this->barcode_2->set_id_boletamarchand($this->bolemarchand->get_id());
            if(!$this->barcode_2->set()){
                error_log("Error barcode 2");
                Model::FailTrans();
            }
            
        }
        if($this->barcode_3 AND !Model::HasFailedTrans()){
            $this->barcode_3->set_id_boletamarchand($this->bolemarchand->get_id());
            if(!$this->barcode_3->set()) {
                error_log("Error barcode 3");
                Model::FailTrans();
            }
        }
        if($this->barcode_4 AND !Model::HasFailedTrans()){
            $this->barcode_4->set_id_boletamarchand($this->bolemarchand->get_id());
            if(!$this->barcode_4->set()) {
                error_log("Error barcode 4");
                Model::FailTrans();
            }
        }
        if(Model::CompleteTrans() AND !Model::HasFailedTrans()){
            return $this;
        }
        if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error. No se generó la boleta.');
        return false;
    }
    public function generar_codigo_de_barras(Climarchand $climarchand, $importe, $fecha_vencimiento, $id_tipopago,$concepto)
    {
        if(!$this->optimizar_trix($climarchand)) {
            if(self::PERMITIR_CLIMARCHANDS_SIN_TRIX){
                self::$trix=new Trix();
                self::$trix->set_id_marchand($climarchand->get_id_marchand());
                if(!(self::$trixgroup=Pagador::optimizar_trixgroup($climarchand))) {
                    if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error. No fue posible optimizar el --trixgroup-de-excepcion--. ');
                    return false;
                }
                self::$trix->set_id_trixgroup(self::$trixgroup->get_id_trixgroup());
                self::$trix->set_accountid($climarchand->get_accountid());
                self::$trix->set_id_climarchand($climarchand->get_id_climarchand());
                if(!self::$trix->set()){
                    if(self::ACTIVAR_DEBUG){ developer_log('Ha ocurrido un error al generar el --trix-de-excepcion--.'); }
                    return false;
                }
                if(self::ACTIVAR_DEBUG) developer_log('Ha sido generado correctamente el --trix-de-excepcion--. ');
            }
            else{
                if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error. No fue posible obtener el trix. ');
                return false;
            }
        }
        $barcode=new Barcode();
        $barcode->set_id_tipopago($id_tipopago);
        $id_marchand=$climarchand->get_id_marchand();
        if(!self::optimizar_marchand($climarchand->get_id_marchand())){
            if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error. No fue posible obtener el Marchand. ');
            return false;
        }

        if(!$this->optimizar_trixgroup(self::$trix)){
            if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error. No fue posible obtener el Trixgroup. ');
            return false;
        }

        if(!$this->optimizar_sc(self::$trixgroup->get_id_sc())){
            if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error. No fue posible obtener el Sc. ');
            return false;
        }

        unset($recordset);
        unset($trixs);
        unset($trixgroups);

        $prefijo_simple=trim(self::$trixgroup->get_trixgroupid());
        $prefijo=str_pad(self::$trixgroup->get_trixgroupid(), self::$sc->get_ndigstrix(), "0", STR_PAD_LEFT);
        $accountid=str_pad($climarchand->get_accountid(), LONGITUD_PREFIJO_Y_ACCOUNT - self::$sc->get_ndigstrix(), "0", STR_PAD_LEFT);

        $barcode->set_id_marchand($id_marchand);
        $now=new DateTime("now");
        
        if(!($fecha_vto = DateTime::createFromFormat('!d/m/Y', $fecha_vencimiento))) {
            throw new Exception('La fecha de vencimiento no puede ser procesada debido a su formato: Utilice dd/mm/yyyy.');
        }
        if($now->format("Ymd")>$fecha_vto->format("Ymd")){
            throw new Exception('La fecha de vencimiento no puede ser menor que hoy');
        }
        $barcode->set_fecha_vto($fecha_vto->format('Y-m-d'));
        $barcode->set_id_authstat(Authstat::BARCODE_PENDIENTE);
        $barcode->set_id_738(self::$marchand->get_id_738());
        $barcode->set_xml_boleta(self::DUMMY_XML_BOLETA);
        if(!is_numeric($importe)){
            Gestor_de_log::set('El formato del importe no es correcto.',0);
            throw new Exception('El importe debe ser numérico.');
        }
        $importe_ok=number_format($importe,2,'.','');
        $barcode->set_monto($importe_ok);
        $barcode->set_is_posted(self::DUMMY_IS_POSTED);
        $barcode->set_id_clima(self::DUMMY_ID_CLIMA);
        $barcode->set_id_trix(self::$trix->get_id());
        if(!($bc_xml=self::generar_estructura_codigo_de_barras($climarchand, $prefijo, date('Ymd'),self::DUMMY_GENSCRIPT,self::DUMMY_GENUSU,$id_tipopago,self::DUMMY_TIPO_PAGO, self::DUMMY_ITEM_PAGO,$concepto))){
            if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error. No fue posible generar la estructura del codigo de barras.');
            return false;
        }
        $barcode->set_bc_xml($bc_xml->saveXml());
        $barcode->set_id_sc(self::$sc->get_id());

        $fecha_vencimiento_barcode=$fecha_vto->format('dmy');
        $monto_barcode=Barcode::preparar_monto($barcode->get_monto());
        developer_log($monto_barcode);
        $codigo_barcode='738'.self::$marchand->get_id_738().self::$sc->get_id().$prefijo.$accountid.$fecha_vencimiento_barcode.$monto_barcode;
        $digito_verificdor=Barcode::calcular_digito_verificador($codigo_barcode);
        if($digito_verificdor===false) {
            Gestor_de_log::set('No se pudo generar el digito verificador.',0);  
            developer_log($codigo_barcode);
            if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error. No se pudo generar el digito verificador.');
            return false;
        }
        $codigo_barcode=$codigo_barcode.$digito_verificdor;

        $barcode->set_barcode($codigo_barcode);
        $barcode->set_barrand(Barcode::obtener_barrand($codigo_barcode));
        $barcode->set_pmc19(Barcode::generar_codelec($codigo_barcode));

        if(strlen($codigo_barcode)!==Barcode::LONGITUD_BARCODE){
            Gestor_de_log::set('No se puede generar el código de barras.',0);
            if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error. No se pudo generar el código de barras');
            return false;
        }
        if(!self::ACTIVAR_VERIFICAR_INEXISTENCIA_DE_CODIGO OR $this->verificar_inexistencia_de_codigo($barcode))
            return $barcode; # No lo inserta, se hara luego con el id_boletamarchand
        else {
            Gestor_de_log::set('El código de barras ya existe. No se puede continuar.',0);
            Boleta_producto::$codebar=$barcode->get_barcode();
            throw new Exception('Ya existe un código de barras idéntico para dicho Pagador. ');
            
        }
        if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error al menos un error al generar el codigo de barras.');
        return false;
    }
    public static function verificar_inexistencia_de_codigo(Barcode $barcode)
    {
        # Retorna TRUE si no existe un codigo de barras
        # para el mismo CLIMA con el mismo importe y la misma fecha de vencimiento
        $array=array('barcode'=>$barcode->get_barcode());

        $recordset=Barcode::select($array);
        if($recordset AND $recordset->RowCount()===0)
            return true;
        if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error al controlar la inexistencia del codigo de barras.');
        return false;
    }
    public static function generar_estructura_codigo_de_barras(Climarchand $climarchand,$prefijo, $gendate,$genscript,$genusu,$id_tipopago,$tipopago,$itempago,$concepto)
    {
        if(!self::optimizar_marchand($climarchand->get_id_marchand())){
            if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error. No se pudo obtener el Marchand(2)');
            return false;
        }
        $estructura_de_cliente= new View();
        $estructura_de_cliente->loadXML($climarchand->get_cliente_xml());
        $comercio=self::$marchand->get_mercalpha();
        $desc=self::$marchand->get_minirs();
        $gid=$prefijo;
        $xml= new View();
        $barcode=$xml->createElement('barcode');
        $xml->appendChild($barcode);
        $barcode->appendChild($xml->createElement('comercio',$comercio));
        $trix=$xml->createElement('TRIX');
        $barcode->appendChild($trix);
        
        $txi=$xml->createElement('txi');
        $items=$xml->createElement('items');
        $item=$xml->createElement('item');
        $item->setAttribute('label','Concepto');
        $fiel=$xml->createElement('fiel','txi_concepto');
        $value=$xml->createElement('value',$concepto);
        $item->appendChild($fiel);
        $item->appendChild($value);
        $items->appendChild($item);
        $txi->appendChild($items);
        $trix->appendChild($txi);

        $grupodecobro=$xml->createElement('grupodecobro');
        $trix->appendChild($grupodecobro);
        $grupodecobro->appendChild($xml->createElement('desc',$desc));
        $grupodecobro->appendChild($xml->createElement('gid',$gid));
        $grupodecobro->appendChild($xml->createElement('cliente',$climarchand->get_accountid()));

        $trix->appendChild($xml->importNode($estructura_de_cliente->documentElement,true));

        $cd=$xml->createElement('CD');
        $cd->appendChild($xml->createElement('gendate',$gendate));
        $cd->appendChild($xml->createElement('genscript',$genscript));
        $cd->appendChild($xml->createElement('genusu',$genusu));
        $barcode->appendChild($cd);
        $tipopagos=$xml->createElement('tipopagos');
        $tipopagos->appendChild($xml->createElement('idtipopago',$id_tipopago));
        $tipopagos->appendChild($xml->createElement('tipopago',$tipopago));
        $tipopagos->appendChild($xml->createElement('itempago',$itempago));
        $barcode->appendChild($tipopagos);
        return $xml;
    }
    public function optimizar_climarchand($id_climarchand)
    {
        if(!self::$climarchand OR self::$climarchand->get_id_climarchand()!==$id_climarchand){
            self::$climarchand=new Climarchand();
	    developer_log($id_climarchand);
            return self::$climarchand->get($id_climarchand);
            }
        return self::$climarchand;
    }
    private function optimizar_trix(Climarchand $climarchand)
    {
        if(!self::$trix OR self::$trix->get_id_climarchand()!==$climarchand->get_id_climarchand()){
            if((self::$trix=Pagador::obtener_trix(self::$climarchand))===false) {
                if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error. No fue posible obtener el trix del Pagador.');
                return false;
            }

        }
        return self::$trix;
    }
    protected function optimizar_xml($modelo,$id_marchand)
    {
        if((self::$xml===false) OR ($id_marchand!==self::$xml->get_id_marchand() OR $modelo!==self::$xml->get_modelo())){
                    $recordset=Xml::select(array('id_marchand'=>$id_marchand,'id_entidad'=>Entidad::ESTRUCTURA_PLANTILLA_BOLETAS_DE_PAGO,'modelo'=>$modelo));
                    if(!$recordset OR $recordset->RowCount()!==1) {
                        if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error relacionado al xml.');
                        return false;
                    }
                    self::$xml=new Xml($recordset->FetchRow());
                }
        return self::$xml;
    }
    public function optimizar_xml_descartable($id_marchand)
    {
        if((self::$xml_descartable===false) OR ($id_marchand!==self::$xml_descartable->get_id_marchand())) {
                    $recordset=Xml::select(array('id_marchand'=>$id_marchand,'id_entidad'=>Entidad::ESTRUCTURA_BOLETAS_DE_PAGO));
                    if(!$recordset OR $recordset->RowCount()===0) {
                        # Si no hay ninguna, pone un registro DUMMY
                        self::$xml_descartable=new Xml();
                        self::$xml_descartable->set_id_marchand($id_marchand);
                        self::$xml_descartable->set_id(1);
                    }
                    elseif($recordset->RowCount()>=1){
                        # Si hay mas de una retorna la de id mayor
                        self::$xml_descartable=new Xml($recordset->FetchRow());
                    }
                }
        return self::$xml_descartable;
    }
    protected static function optimizar_xml_clientes($id_marchand)
    {
        if((self::$xml_clientes===false) OR ($id_marchand!==self::$xml_clientes->get_id_marchand())) {
                    $recordset=Xml::select(array('id_marchand'=>$id_marchand,'id_entidad'=>Entidad::ESTRUCTURA_CLIENTES));
                    if(!$recordset OR $recordset->RowCount()!==1) {
                        if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error relacionado a la estructura de clientes.');
                        return false;
                    }
                    self::$xml_clientes=new Xml($recordset->FetchRow());
                }
        return self::$xml_clientes;
    }
    protected static function cruzar_plantilla_y_datos_de_boleta(Climarchand $climarchand,Bolemarchand $bolemarchand, Xml $plantilla, Barcode $barcode_1,$barcode_2=null,$barcode_3=null, $barcode_4=null, $detalle=null)
    {
        if(!(self::$xml_clientes=self::optimizar_xml_clientes($bolemarchand->get_id_marchand()))){
            if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error. No se pudo obtener la estructura de clientes.');
            return false;
        }

        $nombre_cliente=Pagador::buscar_por_nombre('sap_apellido',$climarchand->get_cliente_xml());
        $id_cliente=Pagador::buscar_por_nombre('sap_identificador',$climarchand->get_cliente_xml());
        
        $reemplazos_genericos=self::cargar_reemplazos($bolemarchand, $barcode_1,$barcode_2,$barcode_3, $barcode_4, $detalle);
        $reemplazos=array();
        $reemplazos[self::ID_CLIENTE]=$id_cliente;
        $reemplazos[self::NOMBRE_CLIENTE]=$nombre_cliente;

        $reemplazos=array_merge($reemplazos_genericos,$reemplazos);
        
        $cantidad_barcodes=1;
        if($barcode_2!=null){
            $cantidad_barcodes++;
            if($barcode_3!=null){
                $cantidad_barcodes++;
                if($barcode_4!=null){
                    $cantidad_barcodes++;
                }
            }
        }
        $codigos_ajustados=self::ajustar_cantidad_de_codigos_de_barra_en_plantilla($plantilla->get_xmlfield(), $cantidad_barcodes);
        if($codigos_ajustados===false){
            developer_log("Fallo al ajustar codigos");
            return false;
        }
        $plantilla->set_xmlfield($codigos_ajustados);
        
        $paquetes_reemplazados=self::reemplazar_paquetes($plantilla->get_xmlfield());
        $plantilla->set_xmlfield($paquetes_reemplazados);
        $datos_cliente_reemplazados=self::reemplazar_datos_cliente($climarchand, self::$xml_clientes, $plantilla);
        $plantilla->set_xmlfield($datos_cliente_reemplazados);
        return self::reemplazar($plantilla->get_xmlfield(),$reemplazos);
    }
    protected static function reemplazar_datos_cliente(Climarchand $climarchand, Xml $xml_clientes, Xml $plantilla)
    {
        # Reemplaza los labels del cliente por los valores
        $array_pagador=Pagador::armar_array($climarchand->get_cliente_xml(), $xml_clientes->get_xmlfield());
        $patrones=array();
        $reemplazos=array();
        foreach ($array_pagador as $nombre => $label_value) {
            $patron=strtoupper(trim($label_value['label']));
            $patrones[]='/'.self::PATRON_INICIO.'\b'.$patron.'\b'.self::PATRON_FINAL.'/';
            $reemplazos[]=$label_value['value'];
        }
        return preg_replace($patrones, $reemplazos, $plantilla->get_xmlfield());
    }
    protected static function ajustar_cantidad_de_codigos_de_barra_en_plantilla($xmlfield, $cantidad_barcodes)
    {

        $view=new DOMDocument('1.0','utf-8');
    
        if(!$view->loadHTML($xmlfield,LIBXML_NOERROR)){
            return false;
        }
        if($cantidad_barcodes>4 OR $cantidad_barcodes <1){
            return false;
        }
        $prefijo_id='codigo_de_barra_';
        for ($i=1; $i < 5; $i++) { 
            if($i>$cantidad_barcodes){
                $elemento=$view->getElementById($prefijo_id.$i);
                if($elemento){
                    $elemento->parentNode->removeChild($elemento);
                }
            }
        }
        return $view->saveHTML();
    }
}
