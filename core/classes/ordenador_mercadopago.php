<?php

class Ordenador_mercadopago
{
	const CLIENT_WAIT=300; # Segundos que espera el cliente
	const HORA_INICIO_ORDENADOR=9;
	const HORA_FIN_ORDENADOR=21;

	const ACTIVAR_DEBUG=true;
	const ACTIVAR_FULL_DEBUG=true;
	const DUMMY_INSTALLMENTS=1;
	const LONGITUD_TOKEN=32;
	const TARJETA_INVALIDA='TARJETA_INVALIDA'; #Usado en Front (Parametrizar!)
	
	const PROCESAR_UN_SOLO_DEBITO=false;
	const FALLAR_CONEXION_REQUEST=false;
	const FALLAR_CONEXION_RESPONSE=false;

	private $clave_privada;
	private $clave_publica;
	
	private $mp=false;

	public function __construct()
	{
    	return $this;
	}
	public function ejecutar($post=null)
	{
        
        //error_log(print_r($post)); 
		if((isset($post['token']) AND isset($post['id_agenda_vinvulo'])) AND isset($post['paymentMethodId'])) {

			if(strlen($post['token'])==32){
				$this->developer_log('Ordenando proximo registro.');
				try {
                                   
					$this->ordenar_debito($post['id_agenda_vinvulo'], $post['token'], $post['paymentMethodId']);
					
				} catch (Exception $e) {
					$this->developer_log('Excepción general: '.$e->getMessage());
				}
			}
			elseif($post['token']==self::TARJETA_INVALIDA){
				$this->developer_log('La tarjeta es invalida');
				$mensaje='Los datos de la tarjeta de crédito no son válidos. ';
				$this->cambiar_estado_debito($post['id_agenda_vinvulo'],Authstat::DEBITO_OBSERVADO, $mensaje);
			}
			else{
				$this->developer_log('Llamado incorrecto:'.json_encode($post));
			}
		}
		if(self::PROCESAR_UN_SOLO_DEBITO){
			exit();
		}
		return $this->preparar_formulario_siguiente();
	}
	private function preparar_formulario_siguiente()
	{
		$ahora=new DateTime('now');

		if($ahora->format('H')>=self::HORA_FIN_ORDENADOR OR $ahora->format('H')<self::HORA_INICIO_ORDENADOR){
			$no_estamos_en_horario=true;
			$view=$this->formulario_espera($no_estamos_en_horario);
		}
		else{

			$recordset=$this->obtener_registros_pendientes();
			if($recordset->RowCount()>0){
				$this->developer_log('Obteniendo proximo registro.');
				$row=$recordset->FetchRow();
				$fecha_vto=DateTime::createFromFormat('Y-m-d',$row['fecha_vto']);			
				$cardExpirationMonth=$fecha_vto->format('m');
				$cardExpirationYear=$fecha_vto->format('Y');
				$cardholderName=$row['titular'];
				switch ($row['id_tipodoc'])  {
					case Tipodoc::CUIT_CUIL: $docType='DNI'; $docNumber=substr($row['documento'], 2,-1); break;
					case Tipodoc::DNI: $docType='DNI'; break;
					default: $docType=false; break;
				}
				if(!$this->obtener_credenciales_mercadopago($row['carrier'])){
                                    developer_log("Error en credenciales");
					return false;
				}
				$view=$this->formulario_buscar_token($row['id_debito'], $row['email'], $row['tco'], $row['cvv'], $cardExpirationMonth, $cardExpirationYear, $cardholderName, $docType, $row['documento']);
			}
			else{
				$this->developer_log('No hay registros. Enviando formulario de espera.');
				$view=$this->formulario_espera();
			}
		}
//                echo $view->saveHTML();
//                exit();
		return $view;
	}
	private function ordenar_debito($id_agenda_vinvulo, $token, $paymentMethodId)
	{
		$recordset=$this->obtener_registro_en_proceso($id_agenda_vinvulo);
		if(!$recordset OR $recordset->RowCount()!==1){
			$this->developer_log('Ha ocurrido un error al obtener el debito en proceso.');
			return false;
		}
		$row=$recordset->FetchRow();
		if(!$this->obtener_credenciales_mercadopago($row['carrier'])){
                    developer_log("Error en credenciales");
			return false;
		}
		require_once PATH_PUBLIC.Preprocesador_mercadopago::LIBRARY;
		$this->mp = new MP_lib ($this->clave_privada);
		

		$this->developer_log('################################');
		$this->developer_log('Ordenando registro;');
		
		if(self::ACTIVAR_FULL_DEBUG) $this->developer_log(json_encode($row));
		$this->developer_log('################################');

		if(!$paymentMethodId){
			$this->developer_log('Ha ocurrido un error al obtener el metodo de pago. ');
			return false;
		}
		switch ($row['id_tipodoc']) {
			case Tipodoc::CUIT_CUIL: 
				$docType='DNI';
				$docNumber=substr($row['documento'], 2,-1);
				break;
			case Tipodoc::DNI: 
				$docType='DNI'; 
				$docNumber=$row['documento'];
				break;
			default: $docType=false; break;
		}
		if(!$docType){
			$this->developer_log('El tipo de documento no es correcto. ');
			return false;
		}
		
		$customer=$this->buscar_customer($row['email'], $row['titular'], $docType, $docNumber);
		//if(!$customer){
        if($customer === -1){//(!)
			$customer=$this->crear_customer($row['email'], $row['nombre'],$row['apellido_rs'], $docType, $docNumber);
		}elseif($customer === -2){//(!)
            $this->developer_log('Retornando false por customer duplicado.');//(!)
            echo "<h3>Atención! El costumer <i>". $row['email'] ."</i> se encuentra duplicado en MercadoPago.<h3>";//(!)
            return false;//(!)
		}//(!)

		if(($payment=$this->crear_payment($id_agenda_vinvulo, $customer, $token, $row['monto'], $paymentMethodId,$row['id_debito'], $row['concepto']))){
			
			$this->developer_log('El estado del payment es: '.$payment['status'].' ('.$payment['status_detail'].') ');

			if($this->cambiar_estado_debito($id_agenda_vinvulo,Authstat::DEBITO_ENVIADO)){
				return true;
			}
			else{
				$this->developer_log('Ha ocurrido un error al cambiar el estado del debito[2]. ');
				return false;
			}
		}

		$this->developer_log('Ha ocurrido un error desconocido. ');
		return false;
	}
	# Retorna un ADO recordset
	private function obtener_registros_pendientes()
	{
		# Se busca estado Activo
		$recordset= Debito_tco::select_ordenador_mercadopago();
		return $recordset;
	}
	# Retorna un ADO recordset
	private function obtener_registro_en_proceso($id_agenda_vinvulo)
	{
		$recordset= Debito_tco::select_ordenador_mercadopago($id_agenda_vinvulo);
		return $recordset;
	}
	# Retorna un DOMDocument
	private function formulario_buscar_token($id_agenda_vinvulo, $email, $cardNumber, $securityCode, $cardExpirationMonth, $cardExpirationYear, $cardholderName, $docType, $docNumber)
	{
            $dom=new DOMDocument('1.0','utf-8');
	    $dom->loadHTMLFile('views/ordenador_mercadopago_buscar_token.html');
	    
	    $dom->getElementById('clave_publica')->setAttribute('value',$this->clave_publica);
	    
	    $form=$dom->getElementById('pay');
	    $elementos=array('id_agenda_vinvulo', 'email','cardNumber','securityCode','cardExpirationMonth','cardExpirationYear','cardholderName','docNumber');
	    foreach ($elementos as $elemento) {
	        $dom->getElementById($elemento)->setAttribute('value',$$elemento);
	    }
	    $dom->getElementById('docType')->setAttribute('selected',$docType);
	    developer_log("Buscando Token");
		return $dom;
	}
	# Retorna un DOMDocument
	private function formulario_espera($no_estamos_en_horario=false)
	{
	    $dom=new DOMDocument('1.0','utf-8');
	    $dom->loadHTMLFile('views/ordenador_mercadopago_espera.html');
	    $dom->getElementById('wait')->setAttribute('value',self::CLIENT_WAIT);
	    if($no_estamos_en_horario){
	    	$mensaje='Son las '.date('H:i').'hs. No estamos en horario: El ordenador se ejecuta de '.self::HORA_INICIO_ORDENADOR.'hs. A '.self::HORA_FIN_ORDENADOR.'hs.';
	    }
	    else{
	    	$mensaje='No hay registros para ordenar.';
	    }
    	$padre=$dom->getElementById('advertencia');
    	$elemento=$dom->createElement('div',$mensaje);
    	$elemento->setAttribute('class','fuera_horario');
    	$padre->appendChild($elemento);
    	if(self::ACTIVAR_DEBUG) developer_log($mensaje);
	    return $dom;
	}
	private function buscar_customer($email, $name, $docType, $docNumber)
	{
	    $parametros=array();
	    $parametros['email']=$email;
	    $response = $this->mp->get ("/v1/customers/search", $parametros);    

	    if(isset($response['status']) AND $response['status']=='200'){
	    	if($response['response']['paging']['total']==1){
	    		$this->developer_log('El customer existe.');
	    		return $response['response']['results'][0];
	    	}
	    	elseif($response['response']['paging']['total']==0){
	    		$this->developer_log('El customer no existe.');
	    		//return false;
                return -1; //(!)
	    	}
	    	else{
	    		//throw new Exception('Hay mas de un customer que coincide con la busqueda.');
                $this->developer_log('Hay mas de un customer que coincide con la busqueda: '. $response['response']['paging']['total'] . ' customers.');//(!)
                return -2;//(!)
	    	}
	    }
	    if(self::ACTIVAR_FULL_DEBUG) $this->developer_log('La respuesta del servidor no es correcta: '.json_encode($response));
    	throw new Exception('La respuesta del servidor no es correcta');
	}
	private function crear_customer($email, $first_name,$last_name, $docType, $docNumber)
	{
	    $parametros=array();
	    $parametros['email']=$email;
	    $parametros['first_name']=$first_name;
	    $parametros['last_name']=$last_name;
	    $parametros['identification']['type']=$docType;
	    $parametros['identification']['number']=$docNumber;

	    $response = $this->mp->post ("/v1/customers", $parametros);
	    if(isset($response['status']) AND $response['status']=='201'){
	    	$this->developer_log('Customer creado correctamente.');
	    	return $response['response'];
	    }
	    elseif(isset($response['status']) AND $response['status']=='200'){
	    	if(self::ACTIVAR_FULL_DEBUG) $this->developer_log('El customer ya existe: '.json_encode($response));
	    	throw new Exception('El customer ya existe. Inconsistencia entre correo electronico y documento. Imposible crearlo nuevamente.');
	    }
	    if(self::ACTIVAR_FULL_DEBUG) $this->developer_log('La respuesta del servidor no es correcta: '.json_encode($response));
    	throw new Exception('La respuesta del servidor no es correcta');
	}
	private function crear_payment($id_agenda_vinvulo, $customer, $token, $transaction_amount, $paymentMethodId, $external_reference, $description)
	{
		if(self::FALLAR_CONEXION_REQUEST) {
			$this->developer_log('Simulo falla de envio de request');
			exit();
		}
		$parametros=array();
	    $parametros['payer']['type']='customer';
	    $parametros['payer']['id']=$customer['id'];
	    $parametros['installments']=self::DUMMY_INSTALLMENTS;
	    $parametros['transaction_amount']=floatval($transaction_amount);
	    $parametros['token']=$token;
	    $parametros['payment_method_id']=$paymentMethodId;
	    $parametros['external_reference']=$external_reference;
	    $parametros['description']=$description;

	    $request = array(
	        "uri" => "/v1/payments",
	        "data" => $parametros,
	        "headers" => array(
	            "x-idempotency-key" => $id_agenda_vinvulo
	        )
	    );
    	$response=$this->mp->post($request);
	    if(self::FALLAR_CONEXION_RESPONSE) {
			$this->developer_log('Simulo falla de recepcion de response');
			exit();
		}
	    if(isset($response['status']) AND $response['status']=='201'){
	    	$this->developer_log('Payment creado correctamente.');
	    	return $response['response'];
	    }
	    elseif(isset($response['status']) AND $response['status']=='200'){
	    	if(self::ACTIVAR_FULL_DEBUG) $this->developer_log('El payment ya existe: '.json_encode($response));
	    	return $response['response'];	
	    }

		if(self::ACTIVAR_FULL_DEBUG) $this->developer_log('La respuesta del servidor no es correcta: '.json_encode($response));
    	throw new Exception('La respuesta del servidor no es correcta');
	}
	private function cambiar_estado_debito($id_debito, $id_authstat, $mensaje=false)
	{
		$debito=new Debito_tco();
		$debito->set_id_debito($id_debito);

		$debito->set_id_authf1($id_authstat);
		$debito->set_fecha_envio('now');
		if($mensaje){
			$debito->set_motivorechazo($mensaje);
		}
		return $debito->set();
	}
	private function obtener_credenciales_mercadopago($carrier)
	{
		if($carrier==Debito::CARRIER_TCDAUT){
			$this->developer_log('Ordenando mediante TCDAUT. ');
			$this->clave_privada=MERCADOPAGO_CLAVE_PRIVADA_TCDAUT;
			$this->clave_publica=MERCADOPAGO_CLAVE_PUBLICA_TCDAUT;
			return true;
		}
		elseif($carrier==Debito::CARRIER_PVP){
			$this->developer_log('Ordenando mediante PVP. ');
			$this->clave_privada=MERCADOPAGO_CLAVE_PRIVADA_PVP;
			$this->clave_publica=MERCADOPAGO_CLAVE_PUBLICA_PVP;	
			return true;
		}
		$this->developer_log('El carrier no es correcto. ');
		return false;
	}
	private function developer_log($mensaje)
	{
		if(self::ACTIVAR_DEBUG){
			developer_log($mensaje);
		}
	}
}
