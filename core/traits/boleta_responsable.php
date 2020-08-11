<?php

class Boleta_responsable extends Boleta
{   
    public static $clima=false;     #Objeto #OPTIMIZAR

    const DUMMY_ID_CLIMARCHAND=1;
    const DUMMY_ID_TIPOPAGO=100;
    const DUMMY_ID_XML=1;
    const DUMMY_ID_TRIX=1;

    const CANTIDAD_DE_INTENTOS_FUNCION_RECURSIVA=10;

    #### REEMPLAZOS ####
    const REEMPLAZO_IMPORTE='IMPORTE';
    const REEMPLAZO_FECHA_DEBITO='FECHA_DEBITO';
    const REEMPLAZO_DETALLE='DETALLE';
    const REEMPLAZO_NOMBRE='NOMBRE_RESPONSABLE';
    const REEMPLAZO_APELLIDO='APELLIDO_RESPONSABLE';
    const REEMPLAZO_CORREO='CORREO_RESPONSABLE';
    const REEMPLAZO_DOCUMENTO='DOCUMENTO_RESPONSABLE';
    const REEMPLAZO_TIPO_DOCUMENTO='TIPO_DOCUMENTO_RESPONSABLE';
    const REEMPLAZO_REFERENCIA='REFERENCIA';
    #############

    public function crear($id_clima, $id_marchand, $id_sc, $modelo, $fechas_vencimiento, $importes, $concepto,$referencia=false, $id_trans=false, $servicio=false, $tipo_pago=false){
        if(!is_array($fechas_vencimiento) OR !is_array($importes))
            return false;
        if(count($fechas_vencimiento)!==count($importes))
            return false;
        if(count($fechas_vencimiento)>1)
            return false;
        if(!isset($importes[0]) OR !isset($fechas_vencimiento[0]))
            return false;
        if($importes[0]<0){
            if(self::ACTIVAR_DEBUG){ developer_log('El importe debe ser mayor a cero.'); }
            return false;
        }
        Model::StartTrans();
        if(!$this->optimizar_clima($id_clima))
            Model::FailTrans();
              
        $this->bolemarchand=new Bolemarchand();
        $this->bolemarchand->set_id_marchand($id_marchand);
        $this->bolemarchand->set_emitida(date('Y-m-d H:i:s.u'));
        $this->bolemarchand->set_boleta_concepto($concepto);
        if(!Model::HasFailedTrans()){
            if(($this->barcode_1=$this->generar_codigo_de_barras(self::$clima, $id_marchand, $id_sc, $importes[0], $fechas_vencimiento[0], self::DUMMY_ID_TIPOPAGO, $concepto))===false)
                Model::FailTrans();
        }
//	error_log(json_encode($this->barcode));
        if(!Model::HasFailedTrans()){
            $this->bolemarchand->set_boletaid($modelo); # Identifica la plantilla
            $this->bolemarchand->set_id_xml(self::DUMMY_ID_XML);
            $this->bolemarchand->set_id_climarchand(self::DUMMY_ID_CLIMARCHAND);
            $this->bolemarchand->set_id_authstat(Authstat::BOLETA_PENDIENTE_DE_PAGO);
            $total_de_boletas=Bolemarchand::cantidad_de_boletas($id_marchand);
            $this->bolemarchand->set_nroboleta($total_de_boletas+1);
            $plantilla=$this->cargar_plantilla();
            if(!($boleta_html=$this->cruzar_plantilla_y_datos_de_boleta(self::$clima, $this->bolemarchand, $plantilla, $this->barcode_1,$referencia, $id_trans, $servicio, $tipo_pago)))
                Model::FailTrans();
            else{
                $this->bolemarchand->set_boleta_html($boleta_html);
            }
            
        }

        if(!Model::HasFailedTrans()){
           if(!$this->bolemarchand->set())
            Model::FailTrans();
        }
        if($this->barcode_1 AND !Model::HasFailedTrans()){
            $this->barcode_1->set_id_boletamarchand($this->bolemarchand->get_id());
            if(!$this->barcode_1->set()){
                Model::FailTrans();
            } 
        }
//	error_log(Model::HasFailedTrans());
        if(Model::CompleteTrans() AND !Model::HasFailedTrans())
            return $this;
        return false;
    }
    # FUNCION RECURSIVA
    private function generar_codigo_de_barras(Clima $clima, $id_marchand, $id_sc, $importe, $fecha_vencimiento, $id_tipopago, $boleta_concepto, $cantidad_de_intentos_funcion_recursiva=false,$ultimo_prefijo=null,$ultimo_accountid=null)
    {
        if($cantidad_de_intentos_funcion_recursiva===false){
            $cantidad_de_intentos_funcion_recursiva=self::CANTIDAD_DE_INTENTOS_FUNCION_RECURSIVA;
        }
        else{
            developer_log($cantidad_de_intentos_funcion_recursiva);
        }
        $barcode=new Barcode();
//	error_log($id_marchand);
//	error_log($id_sc);
        if(!self::optimizar_marchand($id_marchand))
            return false;

        if(!self::optimizar_sc($id_sc))
            return false;

        if($ultimo_prefijo==null OR $ultimo_accountid==null){
            $prefijo=str_pad(mt_rand(0,999),3,'0');
            $accountid=str_pad(mt_rand(0,9999), 4,'0');
        }
        else{
            $prefijo=str_pad(intval($ultimo_prefijo)+1,3,'0');
            $accountid=str_pad(intval($ultimo_accountid)+1, 4,'0');
        }
//	error_log($accountid);
        $barcode->set_id_marchand($id_marchand);

        if(!($fecha_vto = DateTime::createFromFormat('!d/m/Y', $fecha_vencimiento))) {
	    developer_log("ERROR EN EL FORMATO FECHA");
            Gestor_de_log::set('La fecha de vencimiento no puede ser procesada debido a su formato. Pruebe utilizar dd/mm/yyyy.',0);
            return false;
        }

        $barcode->set_fecha_vto($fecha_vto->format(Barcode::FORMATO_FECHA_VTO));
        $barcode->set_id_authstat(Authstat::BARCODE_PENDIENTE);
        $barcode->set_id_738(self::$marchand->get_id_738());
        $barcode->set_xml_boleta(self::DUMMY_XML_BOLETA);
	$importe = str_replace(',', '', $importe);
        if(!is_numeric($importe)) {error_log("El importe no es numerico: ".$importe);return false;}
        $importe_ok=number_format($importe,2,'.','');
        $barcode->set_monto($importe_ok);
        $barcode->set_is_posted(self::DUMMY_IS_POSTED);
        $barcode->set_id_clima($clima->get_id());
        $barcode->set_id_trix(self::DUMMY_ID_TRIX);
        if(!($bc_xml=self::generar_estructura_codigo_de_barras($clima, $id_marchand, $prefijo, date('Ymd'),self::DUMMY_GENSCRIPT,self::DUMMY_GENUSU,$id_tipopago,self::DUMMY_TIPO_PAGO, self::DUMMY_ITEM_PAGO,$boleta_concepto))){
		error_log("error al generar la estructura del barcode");
		throw new Exeption("Error al generar la estructura del barcode");
		return false;
	}
            //return false;
        $barcode->set_bc_xml($bc_xml->saveXml());
        $barcode->set_id_sc(self::$sc->get_id());
        $fecha_vencimiento_barcode=$fecha_vto->format('dmy');
        $monto_barcode=Barcode::preparar_monto($barcode->get_monto());
        $codigo_barcode='738'.self::$marchand->get_id_738().self::$sc->get_id().$prefijo.$accountid.$fecha_vencimiento_barcode.$monto_barcode;
        $digito_verificdor=Barcode::calcular_digito_verificador($codigo_barcode);
        if($digito_verificdor===false) {Gestor_de_log::set('No se pudo generar el digito verificador.',0); error_log("Error al generar digito verificador"); return false;}
        $codigo_barcode=$codigo_barcode.$digito_verificdor;
	error_log($codigo_barcode);
        $barcode->set_barcode($codigo_barcode);
        $barcode->set_barrand(Barcode::obtener_barrand($codigo_barcode));
        $barcode->set_pmc19(Barcode::generar_codelec($codigo_barcode));
	error_log(Model::HasFailedTrans());
        if(strlen($codigo_barcode)!==Barcode::LONGITUD_BARCODE){
	    error_log("El barcode Es demasiado Largo");
            Gestor_de_log::set('No se puede generar el código de barras.',0);
            return false;
        }
        if($this->verificar_inexistencia_de_codigo($id_marchand, $clima, $barcode, $boleta_concepto)) {
            $recordset=Barcode::select(array('barcode'=>$barcode->get_barcode()));
            if(!$recordset){
		developer_log("falla el recorset");
                throw new Exception("Error 3344.");
            }
            $existe_el_codigo_de_barras_pero_para_otro_clima=true;
            if($recordset->RowCount()===0){
                $existe_el_codigo_de_barras_pero_para_otro_clima=false;   
            }
            if($existe_el_codigo_de_barras_pero_para_otro_clima){
                # Puede pasar que un codigo de barras exista (mismo importe, misma fecha) pero para otro clima
                # En este caso, hubo una coincidencia en el random de accountid y prefijo
                # FUNCION RECURSIVA
                if($cantidad_de_intentos_funcion_recursiva==0){
                    if(self::ACTIVAR_DEBUG) developer_log('No quedan cuentas para Responsables disponibles.');
                    throw new Exception('No quedan cuentas para Responsables disponibles.');
                }
                else {
                    if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido una colisión entre responsables. Intentanto nuevamente.');
                    return $this->generar_codigo_de_barras($clima, $id_marchand, $id_sc, $importe, $fecha_vencimiento, $id_tipopago, $boleta_concepto, $cantidad_de_intentos_funcion_recursiva-1,$prefijo,$accountid);
                }
            }
	    error_log("Sale bien");
            return $barcode; # No lo inserta, se hara luego con el id_boletamarchand
        }
        else{
            developer_log('Ya existe un código de barras idéntico para dicho Responsable. ');
            throw new Exception('Ya existe un código de barras idéntico para dicho Responsable. ');
        }
	developer_log("sale con error final");
        return false;
    }
    private function verificar_inexistencia_de_codigo($id_marchand, Clima $clima, Barcode $barcode, $boleta_concepto)
    {
        # Retorna TRUE si no existe un codigo de barras
        # para el mismo CLIMA con el mismo importa y la misma fecha de vencimiento
        $recordset=Barcode::select_id_marchand_id_clima_monto_fecha_vto_concepto($id_marchand, $clima->get_id(), $barcode->get_monto(), $barcode->get_fecha_vto(), $boleta_concepto);
        if($recordset AND $recordset->RowCount()===0)
            return true;
        return false;
    }
    public static function generar_estructura_codigo_de_barras(Clima $clima, $id_marchand, $prefijo, $gendate,$genscript,$genusu,$id_tipopago,$tipopago,$itempago,$concepto)
    {
        if(!self::optimizar_marchand($id_marchand)){
            if(self::ACTIVAR_DEBUG) developer_log('Ha ocurrido un error. No se pudo obtener el Marchand(2)');
            return false;
        }
        
        # Copio la estructura de climarchand
        $estructura_de_clima= new View();
        $pagador=$estructura_de_clima->createElement('pagador');
        $estructura_de_clima->appendChild($pagador);
        $items=$estructura_de_clima->createElement('items');
        $pagador->appendChild($items);
        $item=$estructura_de_clima->createElement('item');
        $items->appendChild($item);
        $nombre=$estructura_de_clima->createElement('nombre','sap_identificador');
        $item->appendChild($nombre);
        $value=$estructura_de_clima->createElement('value', $clima->get_email());
        $item->appendChild($value);
        $item=$estructura_de_clima->createElement('item');
        $items->appendChild($item);
        $nombre=$estructura_de_clima->createElement('nombre','sap_apellido');
        $item->appendChild($nombre);
        $value=$estructura_de_clima->createElement('value', $clima->get_apellido_rs().' '.$clima->get_nombre());
        $item->appendChild($value);

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
        $grupodecobro->appendChild($xml->createElement('cliente',''));

        $trix->appendChild($xml->importNode($estructura_de_clima->documentElement,true));




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
    private function cargar_plantilla()
    {
        $plantilla=new View();
        $plantilla->cargar('views/boleta_responsable.html');
        return $plantilla;
    }
    private function cruzar_plantilla_y_datos_de_boleta(Clima $clima, Bolemarchand $bolemarchand, DOMDocument $plantilla, Barcode $barcode, $referencia=false, $id_trans=false, $servicio=false, $tipo_pago=false)
    {
        $reemplazos_genericos=$this->cargar_reemplazos($bolemarchand, $barcode, null, null, null, null, $id_trans, $servicio, $tipo_pago);
        if(strpos($clima->get_email(), "@")===false) {
            $correo='-';
        }
        else $correo=$clima->get_email();

        if($clima->get_id_tipodoc()==Tipodoc::DNI){
            $tipo_documento='DNI';
        }
        elseif($clima->get_id_tipodoc()==Tipodoc::CUIT_CUIL){
            $tipo_documento='CUIL/CUIL';
        }
        else $tipo_documento='Documento';
        if(!$referencia){
            $referencia='';
        }
        $fecha_vto=DateTime::createFromFormat(Barcode::FORMATO_FECHA_VTO,$barcode->get_fecha_vto());
        if(!$fecha_vto){
            throw new Exception("Error al procesar fecha. ");
        }
        $reemplazos=array(
            self::REEMPLAZO_IMPORTE=>$barcode->get_monto(),
            self::REEMPLAZO_FECHA_DEBITO=>$fecha_vto->format('d/m/Y'),
            self::REEMPLAZO_DETALLE=>$bolemarchand->get_boleta_concepto(),
            self::REEMPLAZO_NOMBRE=>$clima->get_nombre(),
            self::REEMPLAZO_APELLIDO=>$clima->get_apellido_rs(),
            self::REEMPLAZO_CORREO=>$correo,
            self::REEMPLAZO_TIPO_DOCUMENTO=>$tipo_documento,
            self::REEMPLAZO_DOCUMENTO=>$clima->get_documento(),
            self::REEMPLAZO_REFERENCIA=>$referencia
        );
        
        $reemplazos=array_merge($reemplazos_genericos,$reemplazos);
        
        $string=self::reemplazar_paquetes($plantilla->saveHTML());
        return self::reemplazar($string, $reemplazos);
    }
    private function optimizar_clima($id_clima)
    {
        if(!self::$clima OR self::$clima->get_id()!==$id_clima){
            self::$clima=new Clima();
            return self::$clima->get($id_clima);
            }
        return self::$clima;
    }
}
