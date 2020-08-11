<?php
# TEMP # TEMP # TEMP # TEMP # TEMP # TEMP 
## 
##   OJO CON LOS PRICINGS CON ID=1
##
# TEMP # TEMP # TEMP # TEMP # TEMP # TEMP 
class Boleta_comprador extends Boleta
{
    public static $pricing=false; # OPTIMIZAR # FALTA OPTIMIZAR ESTO!
    public static $pricing_tco=false; # OPTIMIZAR # FALTA OPTIMIZAR ESTO!
    public static $xml=false; # OPTIMIZAR # FALTA OPTIMIZAR ESTO!

    const DUMMY_ID_SC=2040;
    const DUMMY_MODELO='boleta_comprador';
    const DUMMY_ID_XML=1;
    const DUMMY_ID_CLIMARCHAND=1;
    const DUMMY_ID_TIPOPAGO=100;
    const DUMMY_ID_CLIMA=1;
    const DUMMY_ID_TRIX=1;
    const DUMMY_ID_TRIXGROUP=1;
    ###########
    #### INDICES DEL ARRAY $datos ####
    const IMPORTE='importe';
    const VENCIMIENTO='vencimiento';
    const DETALLE='detalle';
    const NOMBRE='nombre';
    const APELLIDO="apellido";
    const CORREO='correo';
    const DOCUMENTO='documento';
    const DIRECCION='direccion';
    const COMPRADOR_TELEFONO="telefono";
    ############
    #### REEMPLAZOS ####
    const REEMPLAZO_IMPORTE='IMPORTE';
    const REEMPLAZO_VENCIMIENTO='VENCIMIENTO';
    const REEMPLAZO_DETALLE='DETALLE';
    const REEMPLAZO_NOMBRE='NOMBRE_COMPRADOR';
    const REEMPLAZO_APELLIDO='APELLIDO_COMPRADOR';
    const REEMPLAZO_CORREO='CORREO_COMPRADOR';
    const REEMPLAZO_DOCUMENTO='DOCUMENTO_COMPRADOR';
    const REEMPLAZO_DIRECCION='DIRECCION_COMPRADOR';
    const REEMPLAZO_TELEFONO='TELEFONO_COMPRADOR';
    #############
    ####### CONSTANTES PARA LA ESTRUCTURA DE BARCODE #######
    const DUMMY_CURRENCY='ARG';
    const DUMMY_ITEM_ID='botonpago_cobrodigital';
    const DUMMY_TIPO_PAGO='Pago único';
    const DUMMY_ID_MP_TARJETA='220';
    const DUMMY_TIPO='pagador';
    const DUMMY_GENSCRIPT='botondepago';
    const DUMMY_ID_USUMARCHAND= '1';
    const DUMMY_IP_REMOTA='1';
    const DUMMY_URL_REMOTA='1';
    const DUMMY_USANDO="Dummy Step";
    const DUMMY_ID_MP='2';
    const DUMMY_STEP='51213131';
    const TIPOPAGO_PAGO_UNICO=100;
    const TIPOPAGO_PRIMER_VENCIMIENTO=501;
    const TIPOPAGO_SEGUNDO_VENCIMIENTO=502;
    const TIPOPAGO_TERCER_VENCIMIENTO=503;
    const TIPOPAGO_CUARTO_VENCIMIENTO=504;
    public function crear($id_marchand, $datos, $importes, $fechas_vencimiento, $concepto,$valores_variables=array())
    {
        
        $datos[self::DIRECCION]="";
        $datos[self::DOCUMENTO]="";
        if(!is_array($fechas_vencimiento) OR !is_array($importes)){
            throw new Exception("Ha ocurrido un error en las fechas y los importes.");
            return false;
        }
        if(count($fechas_vencimiento)!==count($importes)){
            throw new Exception("La cantidad de fechas de vencimiento no coincide con los importes.");
            return false;
        }
        if(count($fechas_vencimiento)<1){
            throw new Exception("Se requiere al menos una fecha de vencimiento.");
            return false;
        }
        if(!isset($importes[0]) OR !isset($fechas_vencimiento[0])){
            throw new Exception("Se requiere la primera fecha de vencimiento y el primer importe.");
            return false;
        }
        
        if($importes[0]<0){
            throw new Exception("El importe debe ser mayor que 0");
            if(self::ACTIVAR_DEBUG){ developer_log('El importe debe ser mayor a cero.'); }   
        }
        $transaccion=new Transaccion();
        $configuracion=new Configuracion();
        $conf=$configuracion->obtener_config_tag_concepto("traslada_comision", $id_marchand);
        if($conf["value"]==1){
            developer_log("Trasladando comision");
            foreach ($importes as $clave=>$importe){
                $array=$transaccion->calculo_indirecto($id_marchand,
                        Mp::TARJETA, $importe);
                if($array!=false){
                    list($monto_pagador, $pag_fix, $pag_var, $monto_cd, $cdi_fix, $cdi_var, $monto_marchand)=$array;
                    $importes[$clave]=$monto_pagador;
                }
            }
        }
        
        Model::StartTrans();
        $this->bolemarchand=new Bolemarchand();
        $this->bolemarchand->set_id_marchand($id_marchand);
        $this->bolemarchand->set_emitida(date('Y-m-d H:i:s.u'));
        $this->bolemarchand->set_boleta_concepto($concepto);
        if(!Model::HasFailedTrans()){
            $tipopago=  self::TIPOPAGO_PAGO_UNICO;
            if(count($fechas_vencimiento)>1 AND count($importes)>1)
                $tipopago=  self::TIPOPAGO_PRIMER_VENCIMIENTO;
            if(($this->barcode_1=$this->generar_codigo_de_barras($id_marchand, $datos, self::DUMMY_ID_SC, $importes[0], $fechas_vencimiento[0], $tipopago, $concepto,$valores_variables))===false){
		developer_log("error al generar el 1° codigo de barras");
                Model::FailTrans();
	   }
            if(isset($fechas_vencimiento[1]) AND isset($importes[1])){
                if(($this->barcode_2=$this->generar_codigo_de_barras($id_marchand, $datos, self::DUMMY_ID_SC, $importes[1], $fechas_vencimiento[1], self::TIPOPAGO_SEGUNDO_VENCIMIENTO, $concepto,$valores_variables))===false){
		developer_log("error al generar el 2° codigo de barras");
                    Model::FailTrans();
		}
            }
            if(isset($fechas_vencimiento[2]) AND isset($importes[2])){
                if(($this->barcode_3=$this->generar_codigo_de_barras($id_marchand, $datos, self::DUMMY_ID_SC, $importes[2], $fechas_vencimiento[2],  self::TIPOPAGO_TERCER_VENCIMIENTO, $concepto,$valores_variables))===false){
		   developer_log("error al generar el 3° codigo de barras");
                    Model::FailTrans();
		}
            }
            if(isset($fechas_vencimiento[3]) AND isset($importes[3])){
                if(($this->barcode_4=$this->generar_codigo_de_barras($id_marchand, $datos, self::DUMMY_ID_SC, $importes[3], $fechas_vencimiento[3], self::TIPOPAGO_CUARTO_VENCIMIENTO, $concepto,$valores_variables))===false){
                    developer_log("error al generar el 4° codigo de barras");
		    Model::FailTrans();
		}
            }
        }
        
        if(!Model::HasFailedTrans()){
            $this->bolemarchand->set_boletaid(self::DUMMY_MODELO); # Identifica la plantilla
            $this->bolemarchand->set_id_xml(self::DUMMY_ID_XML);  
            $this->bolemarchand->set_id_climarchand(self::DUMMY_ID_CLIMARCHAND);
            $this->bolemarchand->set_id_authstat(Authstat::BOLETA_PENDIENTE_DE_PAGO);
            $total_de_boletas=Bolemarchand::cantidad_de_boletas($id_marchand);
            $this->bolemarchand->set_nroboleta($total_de_boletas+1);
            $plantilla=$this->cargar_plantilla();
            $array_barcodes=array("barcode_1"=>  $this->barcode_1,"barcode_2"=>  $this->barcode_2,"barcode_3"=>  $this->barcode_3,"barcode_4"=>  $this->barcode_4);
            if(!($boleta_html=$this->cruzar_plantilla_y_datos_de_boleta($datos, $this->bolemarchand, $plantilla, $array_barcodes))){
		developer_log("Error al cruzar datos");
	        Model::FailTrans();
            }
            else{
                $this->bolemarchand->set_boleta_html($boleta_html); 
            }
        }
        if(!Model::HasFailedTrans()){
           if(!$this->bolemarchand->set()){
           	developer_log("error al guardar la boleta");
		 Model::FailTrans();
	   }
        }
        foreach ($array_barcodes as $clave=>$barcode) {
            if($barcode AND !Model::HasFailedTrans()){
                $barcode->set_id_boletamarchand($this->bolemarchand->get_id());
                $barcode->set_id_trix(self::DUMMY_ID_TRIX);
                if(!$barcode->set()){
                    Model::FailTrans();
                } 
            }
        }
        if(Model::CompleteTrans() AND !Model::HasFailedTrans())
            return $this;
        throw new Exception("Error al generar la boleta.");
        return false;
	}
    private function generar_codigo_de_barras($id_marchand, $datos, $id_sc, $importe, $fecha_vencimiento, $id_tipopago, $boleta_concepto,$valores_variables)
    {
        $barcode=new Barcode();
        if(!self::optimizar_marchand($id_marchand))
            return false;

        if(!self::optimizar_sc($id_sc))
            return false;

        $prefijo=str_pad(rand(0,999),3,'0');
        $accountid=str_pad(rand(0,9999), 4,'0');

        $barcode->set_id_marchand($id_marchand);

        if(!($fecha_vto = DateTime::createFromFormat('!d/m/Y', $fecha_vencimiento))) {
            Gestor_de_log::set('La fecha de vencimiento no puede ser procesada debido a su formato. Pruebe utilizar dd/mm/yyyy.',0);
            return false;
        }

        $barcode->set_fecha_vto($fecha_vto->format('Y-m-d'));
        $barcode->set_id_authstat(Authstat::BARCODE_PENDIENTE);
        // var_dump(self::$marchand);
        $barcode->set_id_738(self::$marchand->get_id_738());
        $barcode->set_xml_boleta(self::DUMMY_XML_BOLETA);
        if(!is_numeric($importe)) {
            throw new Exception("El importe debe ser numerico.");
            return false;
        }
            
        $importe_ok=number_format($importe,2,'.','');
        $barcode->set_monto($importe_ok);
        $barcode->set_is_posted(self::DUMMY_IS_POSTED);
        $barcode->set_id_clima(self::DUMMY_ID_CLIMA);

        $barcode->set_id_sc(self::$sc->get_id());
        $fecha_vencimiento_barcode=$fecha_vto->format('dmy');
        $monto_barcode=Barcode::preparar_monto($barcode->get_monto());
        $codigo_barcode='738'.self::$marchand->get_id_738().self::$sc->get_id().$prefijo.$accountid.$fecha_vencimiento_barcode.$monto_barcode;
        $digito_verificdor=Barcode::calcular_digito_verificador($codigo_barcode);
        if($digito_verificdor===false) {Gestor_de_log::set('No se pudo generar el digito verificador.',0);  return false;}
        $codigo_barcode=$codigo_barcode.$digito_verificdor;

        $barcode->set_barcode($codigo_barcode);
        
        if(!($bc_xml=self::generar_estructura_codigo_de_barras($id_marchand,$codigo_barcode, $datos, $importe, $fecha_vencimiento_barcode,$boleta_concepto,$valores_variables)))
            return false;
        $barcode->set_bc_xml($bc_xml->saveXml());
        $barcode->set_barrand(Barcode::obtener_barrand($codigo_barcode));
        if($this->barcode_1==false)
            $barcode->set_pmc19(Barcode::generar_codelec($codigo_barcode));
        else
            //MODIFICADO POR ARIEL:  
            //Falta analizar mas como deben generarse los pmc19, en ordenador_Pagomiscuentas se toma el pmc19 del barcode tipopago=501,
            //En este caso emulamos esto para para que quede con logica parecida.
            /////////////////////////////////////////////////////////////////////////////////////////
            $barcode->set_pmc19($this->barcode_1->get_pmc19());
        $barcode->set_id_tipopago($id_tipopago);
        if(strlen($codigo_barcode)!==Barcode::LONGITUD_BARCODE){
            Gestor_de_log::set('No se puede generar el código de barras.',0);
            return false;
        }

        return $barcode;
    }
    private function cargar_plantilla()
    {
    	$plantilla=new View();
        $plantilla->cargar('views/boleta_comprador.html');
        $recordset=Asigna_marchand_mp::select(array("id_marchand"=>$this->barcode_1->get_id_marchand()));
        $mps=array(1,2,3,4,5,6,7,8,9,10,11,50,51,220);
        $mps_activos=array();
        foreach ($recordset as $row){
            $mps_activos[]=$row["id_mp"];
        }
        foreach ($mps as $mp){
            if(count($mps_activos)!=0 and !in_array($mp, $mps_activos)){
//                var_dump($mp);
                $elemento=$plantilla->getElementById($mp);
                if($elemento!==null)
                    $elemento->parentNode->removeChild($elemento);
            }
        }
        if($this->barcode_1->get_id_marchand() ==1563){
            $elemento=$plantilla->getElementById("abonar_homebanking");
            if($elemento!==null)
                    $elemento->parentNode->removeChild($elemento);
            
        }
        if($this->barcode_2 == null){
          $elemento=$plantilla->getElementById("codigo_de_barra_2");
          if($elemento!=null)
            $elemento->parentNode->removeChild($elemento);  
        }
        if($this->barcode_3 == null){
          $elemento=$plantilla->getElementById("codigo_de_barra_3");
          if($elemento!=null)
             $elemento->parentNode->removeChild($elemento);  
        }
        if($this->barcode_4 == null){
          $elemento=$plantilla->getElementById("codigo_de_barra_4");
          if($elemento!=null)
            $elemento->parentNode->removeChild($elemento);
        }
        return $plantilla;
    }
    private function cruzar_plantilla_y_datos_de_boleta($datos, Bolemarchand $bolemarchand, DOMDocument $plantilla, $barcodes)
    {
        
        $reemplazos_genericos=$this->cargar_reemplazos($bolemarchand, $barcodes["barcode_1"],$barcodes["barcode_2"],$barcodes["barcode_3"],$barcodes["barcode_4"]);
        $reemplazos=array(
            self::REEMPLAZO_IMPORTE=>$barcodes["barcode_1"]->get_monto(),
            self::REEMPLAZO_VENCIMIENTO=>$barcodes["barcode_1"]->get_fecha_vto(),
            self::REEMPLAZO_DETALLE=>$bolemarchand->get_boleta_concepto(),
            self::REEMPLAZO_NOMBRE=>$datos[self::NOMBRE],
            self::REEMPLAZO_APELLIDO=>$datos[self::APELLIDO],
            self::REEMPLAZO_CORREO=>$datos[self::CORREO],
            self::REEMPLAZO_DOCUMENTO=>$datos[self::DOCUMENTO],
            self::REEMPLAZO_DIRECCION=>$datos[self::DIRECCION],
        );
        if(isset($datos[self::COMPRADOR_TELEFONO]))
            $reemplazos[self::REEMPLAZO_TELEFONO]=$datos[self::COMPRADOR_TELEFONO];
        else
            $reemplazos[self::REEMPLAZO_TELEFONO]="";
        $reemplazos=array_merge($reemplazos_genericos,$reemplazos);
    	
        $string=self::reemplazar_paquetes($plantilla->saveHTML());
    	return self::reemplazar($string, $reemplazos);
    }
    private function generar_estructura_codigo_de_barras($id_marchand,$cod_barras, $datos, $importe, $vencimiento,$concepto,$valores_variables)
    {
        if(!self::optimizar_marchand($id_marchand))
            return false;
        $datos[self::VENCIMIENTO]=$vencimiento;
        $datos[self::IMPORTE]=$importe;
        $datos[self::DETALLE]=$concepto;
        unset($concepto);
        unset($importe);
        unset($vencimiento);

        # Se crean variables locales   
        $array=array(self::NOMBRE,self::APELLIDO,self::IMPORTE,self::DIRECCION,self::DOCUMENTO,self::CORREO,self::DETALLE,self::VENCIMIENTO, 'id_tipopago');
        
        if(!($datos[self::VENCIMIENTO]= datetime::createFromFormat ('dmy', $datos[self::VENCIMIENTO]))){
            return false;
        }
        
       
        # Datos de MercadoPago 
        $select_config=  Xml::select(array('id_marchand'=>$id_marchand,'id_entidad'=>Entidad::ESTRUCTURA_CONFIG_MARCHAND));
        $config=new Xml($select_config->fetchRow());
        $xml_field=  new View();
        $xml_field->loadXML($config->get_xmlfield());
        $elementos=$xml_field->getElementsByTagName('mercadopago');
        $enc=$acc_id='';
        if($elementos->length==1){
            $mercadopago=$elementos->item(0);
            if($mercadopago->hasAttribute('habilitado') AND $mercadopago->getAttribute('habilitado')==1)
            {
                foreach ($mercadopago->childNodes as $child) {
                    if($child->nodeName=='acc_id')
                        $acc_id=$child->firstChild->nodeValue;
                    if($child->nodeName=='enc')
                        $enc=$child->firstChild->nodeValue;
                }
            }
        }
        # Creacion del XML final
        $view=new View();
        $barcode=$view->createElement('barcode');
        $view->appendChild($barcode);
        $comercio=$view->createElement('comercio',self::$marchand->get_mercalpha());
        $barcode->appendChild($comercio);
        $trix= $view->createElement('TRIX');
        $barcode->appendChild($trix);
        $items= $view->createElement('items');
        $trix->appendChild($items);
        $array_trix=array(
            'Importe'=>array('0'=>$datos[self::IMPORTE],'1'=>'txi_importe'),
            'Vencimiento'=>array('0'=>$datos[self::VENCIMIENTO]->format('Ymd'),'1'=>'txi_fecha_calculo'),
            'Calculo fecha'=>array('0'=>$datos[self::VENCIMIENTO]->format('d-m-Y'),'1'=>'txi_fecha_vto'),
            'Detalle'=>array('0'=>$datos[self::DETALLE],'1'=>'txi_detalle'),
            'Id'=>array('0'=>'No indicado','1'=>'txi_id'),
            'Nombre'=>array('0'=>$datos[self::NOMBRE],'1'=>'bopa_nombre'),
            'Apellido'=>array('0'=>$datos[self::APELLIDO],'1'=>'bopa_apellido'),
            'Documento'=>array('0'=>$datos[self::DOCUMENTO],'1'=>'txi_id'),
            'Direccion'=>array('0'=>$datos[self::DIRECCION],'1'=>'txi_direccion'),
            'Email'=>array('0'=>$datos[self::CORREO],'1'=>'bopa_email'),
            );

        foreach ($array_trix as $nom_row=>$row) {
            $item= $view->createElement('item');
            $item->setAttribute('label', $nom_row);
            $items->appendChild($item);
            $fiel= $view->createElement('fiel',$row[1]); 
            $item->appendChild($fiel);
            $value= $view->createElement('value',$row[0]);
            $item->appendChild($value);
        }
        $items=$view->createElement("xml_concepto");
        foreach ($valores_variables as $clave=>$valor){
	    $clave= str_replace(" ", "", $clave);
            $fiel= $view->createElement($clave,$valor);
            $items->appendChild($fiel);
        }
	$trix->appendChild($items);
        $cd= $view->createElement('CD');
        $barcode->appendChild($cd);
        $pricing= $view->createElement('pricing'); 
        $cd->appendChild($pricing);
        $tipo= $view->createElement('tipo',self::DUMMY_TIPO);
        $pricing->appendChild($tipo);
        ################# REVISARLO
        $valor= $view->createElement('valor',$datos[self::IMPORTE]);
        $pricing->appendChild($valor);

        $valido= $view->createElement('valido_desde','');
        $pricing->appendChild($valido);
        $valor_fijo = $view->createElement('valor_fijo','');
        $pricing->appendChild($valor_fijo);
        $valor_variable = $view->createElement('valor_variable','');
        $pricing->appendChild($valor_variable);
        $usando= $view->createElement('usando','');
        $pricing->appendChild($usando);
        
        $tarjeta= $view->createElement('tarjeta');
        $cd->appendChild($tarjeta);
        $items= $view->createElement('items');
        $tarjeta->appendChild($items);

        $array_tarjeta=array(
            array('0'=>'acc_id','1'=>$acc_id),
            array('0'=>'enc','1'=>$enc),
            array('0'=>'cart_surname','1'=>$datos[self::APELLIDO]),
            array('0'=>'cart_name','1'=>$datos[self::NOMBRE]),
            array('0'=>'cart_email','1'=>$datos[self::CORREO]),
            array('0'=>'currency','1'=>self::DUMMY_CURRENCY),
            array('0'=>'shipping_cost','1'=>''),
            array('0'=>'op_retira','1'=>''),
            array('0'=>'ship_cost_mode','1'=>''),
            array('0'=>'item_id','1'=>self::DUMMY_ITEM_ID),
            array('0'=>'price','1'=>$datos[self::IMPORTE]),
            array('0'=>'seller_op_id','1'=>$cod_barras),
            array('0'=>'name','1'=>$datos[self::NOMBRE]),
            array('0'=>'pricebc','1'=>$datos[self::IMPORTE]),
        );
        foreach ($array_tarjeta as $row) {
                $item= $view->createElement('item');
                $fiel= $view->createElement('fiel',$row[0]); 
                $item->appendChild($fiel);
                $value= $view->createElement('value',$row[1]);
                $item->appendChild($value);
                $items->appendChild($item);
        }
        $item= $view->createElement('item');
        $items->appendChild($item);
        $fiel= $view->createElement('fiel','pricing_tco'); 
        $item->appendChild($fiel);
        $value= $view->createElement('value');
        $item->appendChild($value);
        
        $pricing= $view->createElement('pricing_tco'); 
        $value->appendChild($pricing);
        $tipo= $view->createElement('tipo',self::DUMMY_TIPO);
        $usando= $view->createElement('usando',self::DUMMY_USANDO);
        $pricing->appendChild($usando);
        $pricing->appendChild($tipo);
        $step= $view->createElement('step',self::DUMMY_STEP);
        $pricing->appendChild($step);
        $importe_tco= $view->createElement('importe_tco',$datos[self::IMPORTE]);
        $pricing->appendChild($importe_tco);
        
        $valido= $view->createElement('valido_desde','');
        $pricing->appendChild($valido);
        $valor_fijo = $view->createElement('valor_fijo','');
        $pricing->appendChild($valor_fijo);
        $valor_variable = $view->createElement('valor_variable','');
        $pricing->appendChild($valor_variable);

        $gendate= $view->createElement('gendate',$datos[self::VENCIMIENTO]->format("Ymd"));
        $cd->appendChild($gendate);
        $gentime= $view->createElement('gentime',$datos[self::VENCIMIENTO]->format("Hs"));
        $cd->appendChild($gentime);
        $genscript= $view->createElement('genscript',self::DUMMY_GENSCRIPT);
        $cd->appendChild($genscript);
        $genusu= $view->createElement('genusu', self::DUMMY_ID_USUMARCHAND);
        $cd->appendChild($genusu);
        $remoteip = $view->createElement('remoteip',self::DUMMY_IP_REMOTA);
        $cd->appendChild($remoteip);
        $remoteurl= $view->createElement('remoteurl',self::DUMMY_URL_REMOTA);
        $cd->appendChild($remoteurl);
        
        $tipopagos= $view->createElement('tipopagos');
        $barcode->appendChild($tipopagos);
        
        $id_tipopago= $view->createElement('id_tipopagos',self::DUMMY_ID_TIPOPAGO);
        $tipopagos->appendChild($id_tipopago);
        $tipopago = $view->createElement('tipopago',self::DUMMY_TIPO_PAGO);
        $tipopagos->appendChild($tipopago);
        $itempago= $view->createElement('itempago');
        $tipopagos->appendChild($itempago);
        
        return $view;   
    }
}
