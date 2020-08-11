<?php

class Preprocesador_mercadopago
{
	const ACTIVAR_DEBUG=true;
	const ACTIVAR_DEBUG_FULL=false;
	const ACTIVAR_TEST=false;
	const ACTIVAR_TEST_STREAM=false;
	const LIBRARY='sdk-php/lib/mercadopago.php';
	const LIMITE_DE_PAGOS_POR_CONSULTA=100;
	const DUMMY_UNID='125';
	const DUMMY_ID_FORMAPAGO='3';
	const PERIODO_DE_CONSULTA_MERCADOPAGO='1HOUR';

	const MERCADOPAGO_CORREO_1='mp';
	const MERCADOPAGO_CORREO_2='collect';
	const MERCADOPAGO_CORREO_3='mp3e';
	const MERCADOPAGO_CORREO_4='clicpagos';
	const MERCADOPAGO_CORREO_5='pvp';
	const MERCADOPAGO_CORREO_6='tcdaut';
	const DOMINIO_CORREO='@cobrodigital.com';

	private static $mp=false; # Optimizar

	private $acumulado_transas_insertadas=0;
	private $acumulado_transas_insertadas_desconocidas=0;
	private $acumulado_transas_actualizadas=0;
	private $acumulado_transas_repetidas=0;
	private $acumulado_transas_con_error=0;

	private static $preprocesar_cuenta_mp=false;
	private static $preprocesar_cuenta_collect=true;
	private static $preprocesar_cuenta_mp3e=false;
	private static $preprocesar_cuenta_clicpagos=false;
	private static $preprocesar_cuenta_pvp=true;
	private static $preprocesar_cuenta_tcdaut=true;


	public static function listado_de_cuentas()
	{
		$cuentas=array();
		if(self::$preprocesar_cuenta_mp){
			$cuentas[self::MERCADOPAGO_CORREO_1]=array(	'client_id'=>'1872102149935260',
								'client_secret'=>'U8SInUyeb58ixtUQanTvTCrMnYWRuEff',
								'correo'=>self::MERCADOPAGO_CORREO_1.self::DOMINIO_CORREO,
								'alias'=>'MP1',
								'id_peucd'=>Peucd::MERCADOPAGO_MP1
							);
		}
		if(self::$preprocesar_cuenta_collect){
			$cuentas[self::MERCADOPAGO_CORREO_2]=array(	'client_id'=>'4812010488902074',
								'client_secret'=>'RSXDuaXJJSI7FSl1OzhfPeu9hKdqIywR',
								'correo'=>self::MERCADOPAGO_CORREO_2.self::DOMINIO_CORREO,
								'alias'=>'MP2',
								'id_peucd'=>Peucd::MERCADOPAGO_MP2
							);
		}
		if(self::$preprocesar_cuenta_mp3e){
			$cuentas[self::MERCADOPAGO_CORREO_3]=array(	'client_id'=>'420311948104044',
								'client_secret'=>'y3MnDnV0aBDXPUR5vmB3kTboyr9sFVbL',
								'correo'=>self::MERCADOPAGO_CORREO_3.self::DOMINIO_CORREO,
								'alias'=>'MP3',
								'id_peucd'=>Peucd::MERCADOPAGO_MP3
							);
		}
		if(self::$preprocesar_cuenta_clicpagos){
			$cuentas[self::MERCADOPAGO_CORREO_4]=array(	'client_id'=>'8255255304938580',
								'client_secret'=>'1f0M14QqNFOXkfqWJPyNOtdl4YdO8zeX',
								'correo'=>self::MERCADOPAGO_CORREO_4.self::DOMINIO_CORREO,
								'alias'=>'MP4',
								'id_peucd'=>Peucd::MERCADOPAGO_MP4
							);
		}
		if(self::$preprocesar_cuenta_pvp){
			$cuentas[self::MERCADOPAGO_CORREO_5]=array(	
								'access_token'=>MERCADOPAGO_CLAVE_PRIVADA_TCDAUT,
								'correo'=>self::MERCADOPAGO_CORREO_5.self::DOMINIO_CORREO,
								'alias'=>'MP5',
								'id_peucd'=>Peucd::MERCADOPAGO_MP5
							);
		}
		if(self::$preprocesar_cuenta_tcdaut){
			$cuentas[self::MERCADOPAGO_CORREO_6]=array(	
								'access_token'=>MERCADOPAGO_CLAVE_PRIVADA_TCDAUT,
								'correo'=>self::MERCADOPAGO_CORREO_6.self::DOMINIO_CORREO,
								'alias'=>'MP6',
								'id_peucd'=>Peucd::MERCADOPAGO_MP6
							);
		}
		return $cuentas;
	}
	public function preprocesar()
	{
		require_once PATH_PUBLIC.self::LIBRARY;
		if(self::ACTIVAR_TEST_STREAM){
			$listado_de_cuentas=array(array('client_id'=>'none','client_secret'=>'none','correo'=>'test_stream@cobrodigital.com','alias'=>'test','id_peucd'=>'1'));
		}
		else{
			$listado_de_cuentas=$this->listado_de_cuentas();
		}
		$resultados=array();
		if(self::ACTIVAR_TEST){
			if(self::ACTIVAR_DEBUG){ developer_log('Es un test: No se modifica la Base de Datos.'); }
			Model::StartTrans();
		}
		foreach ($listado_de_cuentas as $cuenta) {
			developer_log('Preprocesando cuenta '.$cuenta['correo']);
			$this->preprocesar_cobranzas_de_una_cuenta($cuenta);
		}
		
		if(self::ACTIVAR_DEBUG){ 
			developer_log('Resultado acumulado de la ejecución.');
			developer_log('Total Transas insertadas: '.$this->acumulado_transas_insertadas); 
			developer_log('Total Transas insertadas desconocidas: '.$this->acumulado_transas_insertadas_desconocidas); 
			developer_log('Total Transas actualizadas: '.$this->acumulado_transas_actualizadas);
			developer_log('Total Transas Repetidas: '.$this->acumulado_transas_repetidas);
			developer_log('Total Transas con error: '.$this->acumulado_transas_con_error);
		}

		if(self::ACTIVAR_TEST){
			Model::FailTrans();
			Model::CompleteTrans();
			if(self::ACTIVAR_DEBUG){ developer_log('Es un test: Base de Datos no modificada.'); }
		}
	}
	public static function obtener_credenciales($acc_id)
	{
		$indice=false;
		$cuentas=self::listado_de_cuentas();
		switch ($acc_id) {
 			# mp
 			case '27344279':
 				$indice=self::MERCADOPAGO_CORREO_1;
 				break;
 			# collect
 			case '511585208':
 				$indice=self::MERCADOPAGO_CORREO_2;
 				break;
 			# mp3e
 			case '512305941':
 				$indice=self::MERCADOPAGO_CORREO_3;
 				break;
 			# clicpagos
 			case '549098890':
 				$indice=self::MERCADOPAGO_CORREO_4;
 				break;
 			default:
 				developer_log('No esta bien configurada la cuenta de mercadopago que utiliza el cliente. Revisar el acc_id. ');
 				break;
 		}
 		if($indice AND isset($cuentas[$indice])){
 			$client_id=$cuentas[$indice]['client_id'];
 			$client_secret=$cuentas[$indice]['client_secret'];
 			return array($client_id, $client_secret);
 		}
 		return false;
	}
	private function preprocesar_cobranzas_de_una_cuenta($cuenta)
	{	
		try {
			if(isset($cuenta['access_token'])){
				$mp = new MP_lib ($cuenta['access_token']);
			}
			else{
				$mp = new MP_lib ($cuenta['client_id'], $cuenta['client_secret']);
			}
		} catch (Exception $e) {
			if(self::ACTIVAR_DEBUG){ developer_log('Excepción de Construcción: '.$e->getMessage()); }
			return false;
		}
		$offset=0; 
		$limit=self::LIMITE_DE_PAGOS_POR_CONSULTA;
		$transas_insertadas=0;
		$transas_insertadas_desconocidas=0;
		$transas_actualizadas=0;
		$transas_repetidas=0;
		$transas_con_error=0;
		do {
    		$cobranzas=array();
			developer_log('Consultando a MercadoPago.com');
			try {
				if(!self::ACTIVAR_TEST_STREAM){
    				$response = $this->buscar_pagos($mp, $offset, $limit);
				}
				else{
					if(self::ACTIVAR_DEBUG){ developer_log('Usando Datos de Prueba. '); }
					$response_stream=file_get_contents('test_stream');
					$response=json_decode($response_stream, true);
					$limit=0;
					$response['response']['paging']['total']=count($response['response']['results']);
				}
			} catch (Exception $e) {
				if(self::ACTIVAR_DEBUG){ developer_log($e->getMessage()); }
				
			}
    		if((!isset($response['status'])) OR $response['status']!='200'){
				if(self::ACTIVAR_DEBUG){ developer_log('La conexión no fue establecida correctamente. '); }
				return false;
			}
			if(self::ACTIVAR_DEBUG){
    			developer_log('Cantidad de transacciones a procesar: '.$response['response']['paging']['total']);
			}
    		if(self::ACTIVAR_DEBUG_FULL){ 
    			developer_log('Límite de transacciones:  '.$response['response']['paging']['limit']);
    			developer_log('Desplazamiento de transacciones:  '.$response['response']['paging']['offset']);
    			developer_log('Cantidad de transacciones recibidas: '.count($response['response']['results']));
    		}
    		if(isset($response['response']['results'])){
    			$cobranzas=$response['response']['results'];
    		}
        	if(count($cobranzas)>0){
	        	foreach ($cobranzas as $cobranza) {
	        		$registro=new Registro_mercadopago($cobranza);
	        		if(self::ACTIVAR_DEBUG){ developer_log("Procesando cobranza: '".$registro->obtener_codigo_de_barras()."'"); }
	        		
	        		if(!($transas=$this->obtener_transas($registro,$cuenta))){
	        			if(self::ACTIVAR_DEBUG_FULL){ developer_log('La transas no existe. '); }
	        		}
	        		else{
	        			if(self::ACTIVAR_DEBUG_FULL){ developer_log('La transas existe. '); }
	        		}
	        		if(!($barcode=$this->obtener_barcode($registro->obtener_codigo_de_barras()))) {
						if(self::ACTIVAR_DEBUG_FULL){ developer_log('El barcode no existe.'); }
					}
					else{
						if(self::ACTIVAR_DEBUG_FULL){ developer_log('El barcode existe.'); }
					}
					$error=true;
					if($barcode AND $barcode->get_id_marchand()=='856' OR $cuenta['correo']!=self::MERCADOPAGO_CORREO_2.self::DOMINIO_CORREO) {
						if($transas AND $barcode){
							if(self::ACTIVAR_DEBUG_FULL){ developer_log('Actualizando estado de transas. '); }
							if(($aux=$this->actualizar_transas($registro, $transas))){
								$error=false;
								if($aux===true){
									$transas_actualizadas++;
								}
								elseif($aux===1){
									$transas_repetidas++;
									if(self::ACTIVAR_DEBUG_FULL){ developer_log('No hace falta actualizar la transas. '); }
								}
							}
							else{
								if(self::ACTIVAR_DEBUG){ developer_log('Ha ocurrido un error al actualizar la transas. '); }
							}
						}
						elseif(!$transas AND $barcode){
							if(self::ACTIVAR_DEBUG_FULL){ developer_log('Insertando transas.'); }
							if($this->insertar_transas($registro, $cuenta, $barcode)) {
								$error=false;
								$transas_insertadas++;
							}
							else{
								if(self::ACTIVAR_DEBUG){ developer_log('Ha ocurrido un error al insertar la transas. '); }
							}
						}
						elseif($transas AND !$barcode){
							if(self::ACTIVAR_DEBUG){ developer_log('Ha ocurrido un error fatal. '); }
						}
						elseif(!$transas AND !$barcode){
							if(self::ACTIVAR_DEBUG_FULL){ developer_log('Habria que insertar transas desconocida? '); }
						}
					}
					else{
						developer_log('Todavia no migramos. ');
					}
					if($error){
						$transas_con_error++;
					}
	        	}
        	}
			$offset=$offset+$limit;
		} while($limit!=0 AND count($cobranzas)==$limit);

		
		if(self::ACTIVAR_DEBUG){ 
			developer_log('Transas insertadas: '.$transas_insertadas); 
			developer_log('Transas desconocidas insertadas: '.$transas_insertadas_desconocidas); 
			developer_log('Transas actualizadas: '.$transas_actualizadas);
			developer_log('Transas Repetidas: '.$transas_repetidas);
			developer_log('Transas con error: '.$transas_con_error);
		}
		
		$this->acumulado_transas_insertadas+=$transas_insertadas;
		$this->acumulado_transas_insertadas_desconocidas+=$transas_insertadas_desconocidas;
		$this->acumulado_transas_actualizadas+=$transas_actualizadas;
		$this->acumulado_transas_repetidas+=$transas_repetidas;
		$this->acumulado_transas_con_error+=$transas_con_error;
		
		if($transas_insertadas+$transas_actualizadas+$transas_repetidas+$transas_con_error+$transas_insertadas_desconocidas==$response['response']['paging']['total']){
			if(self::ACTIVAR_DEBUG){ developer_log("Todas las transas fueron procesadas para la cuenta '".$cuenta['correo']."'."); }
			return true;
		}
		if(self::ACTIVAR_DEBUG){ 
			developer_log("ERROR: Algunas transas no fueron procesadas para la cuenta '".$cuenta['correo']."'.");
		}
		return false;
	}
	private function obtener_transas(Registro_mercadopago $registro, $cuenta)
	{
		$recordset=Transas::select(array('gateway_op_id'=>$registro->obtener_identificador_de_transaccion()));
		if($recordset AND $recordset->RowCount()==1){
			$row=$recordset->FetchRow();
			$transas=new Transas($row);
			return $transas;
		}
		return false;
	}
	private function obtener_barcode($codigo_de_barras)
	{
		$recordset=Barcode::select(array('barcode'=>$codigo_de_barras));
		if($recordset AND $recordset->RowCount()==1){
			$barcode=new Barcode($recordset->FetchRow());
			return $barcode;
		}
		return false;
	}
	private function insertar_transas(Registro_mercadopago $registro, $cuenta, Barcode $barcode)
	{
		$transas=new Transas();
		$transas->set_id_entidad(Entidad::ENTIDAD_BARCODE);
		$transas->set_id_referencia($barcode->get_id_barcode());
		$id_authstat=$this->obtener_estado_para_transas($registro);
		if(!$id_authstat){
			if(self::ACTIVAR_DEBUG){ developer_log('Estado desconocido'); }
			return false;
		}
		$transas->set_id_authstat($id_authstat);
		$transas->set_id_mp(Mp::TARJETA);
		$fecha_modificacion=$registro->obtener_fecha_de_modificacion();
		if(!$fecha_modificacion){
			if(self::ACTIVAR_DEBUG){ developer_log('Error en la fecha de modificacion. '); }
			return false;
		}
		$transas->set_fecha($fecha_modificacion->format('Y-m-d H:i:s'));
			
		if(!$this->optimizar_mp(Mp::TARJETA)){
			if(self::ACTIVAR_DEBUG){ developer_log('Error al optimizar Mp. '); }
            return false;
        }
		$diasplus_liq=intval(self::$mp->get_diaplus_liq());
		$intervalo=new DateInterval('P'.$diasplus_liq.'D');

		$ahora=new DateTime('now');
		$fecha_liq=$ahora->add($intervalo);

		$transas->set_fecha_liq($fecha_liq->format('Y-m-d'));
		$transaccion=new Transaccion();
		$array=$transaccion->calculo_directo($barcode->get_id_marchand(), Mp::TARJETA, $registro->obtener_monto_bruto(), null, $barcode);
		if(!$array){
			if(self::ACTIVAR_DEBUG){ developer_log('No existe comision.'); }
			return false;
		}
		list($monto_pagador,$pag_fix,$pag_var,$monto_cd,$cdi_fix,$cdi_var,$monto_marchand)=$array;
		$transas->set_monto_pagador($monto_pagador);
		$transas->set_pag_fix($pag_fix);
		$transas->set_pag_var($pag_var);
		$transas->set_monto_cd($monto_cd);
		$transas->set_cdi_fix($cdi_fix);
		$transas->set_cdi_var($cdi_var);
		$transas->set_monto_marchand($monto_marchand);
		$crypt=crypt(sprintf("%01.2f",$monto_marchand),Transaccion::PASSWORD_CIFRADO_SALDOS);
		$transas->set_transa_md5($crypt);
		$transas->set_id_marchand($barcode->get_id_marchand());
		$transas->set_id_pricing($transaccion->pricing_pag->get_id());
		$transas->set_id_pricing_mch($transaccion->pricing_cdi->get_id());
		$transas->set_fecha_move('now');
		$transas->set_unid(self::DUMMY_UNID);
		$transas->set_transas_xml($this->crear_transas_xml($registro, $barcode, $pag_fix+$pag_var, $cdi_var+$cdi_fix, $cuenta, $transaccion->pricing_pag, $transaccion->pricing_cdi));
		$transas->set_gateway_op_id($registro->obtener_identificador_de_transaccion());
		$transas->set_id_gateway($cuenta['id_peucd']);
		$transas->set_id_formapago(self::DUMMY_ID_FORMAPAGO);

		if($transas->set()){
			return $transas;
		}
		return false;
	}
	private function actualizar_transas(Registro_mercadopago $registro, Transas $transas)
	{
		$id_authstat=$this->obtener_estado_para_transas($registro);
		if(!$id_authstat){
			if(self::ACTIVAR_DEBUG){ developer_log('Estado desconocido'); }
			return false;
		}
		
		$transas->set_id_authstat($id_authstat);

		$fecha_modificacion=$registro->obtener_fecha_de_modificacion();
		if(!$fecha_modificacion){
			if(self::ACTIVAR_DEBUG){ developer_log('Error en la fecha de modificacion. '); }
			return false;
		}
		$transas->set_fecha($fecha_modificacion->format('Y-m-d H:i:s'));
		$fecha_anterior=DateTime::createFromFormat(Transas::FORMATO_FECHA, $transas->get_fecha());
		if($id_authstat==$transas->get_id_authstat() AND $fecha_modificacion->format('Y-m-d H:i:s')==$fecha_anterior->format('Y-m-d H:i:s')){
			# Salida correcta 1
			return 1;
		}
		$transas->set_fecha_move('now');
		if($transas->set()){
			# Salida correcta 2
			return true;
		}
		return false;
	}
	private function crear_transas_xml(Registro_mercadopago $registro, Barcode $barcode, $sum_pag, $sum_cdi, $cuenta, Pricing $pricing_pag, Pricing $pricing_cdi)
	{
		$xml=new DOMDocument('1.0','utf-8');
		$transa=$xml->createElement('transa');
		$gateway=$xml->createElement('gateway','Mercado Pago ('.$cuenta['alias'].')');
		$mensaje_para_usuario=$xml->createElement('mensaje_para_usuario',$registro->obtener_mensaje_para_usuario());
		$gateway_op_id=$xml->createElement('gateway_op_id',$registro->obtener_identificador_de_transaccion());
		$transa->appendChild($gateway);
		$transa->appendChild($mensaje_para_usuario);
		$transa->appendChild($gateway_op_id);
		$pricing_tco=$xml->createElement('pricing_tco');
		$tipo=$xml->createElement('tipo','pagador');
		$valor=$xml->createElement('valor',$sum_pag);
		$valido_desde=$xml->createElement('valido_desde');
		$valor_fijo=$xml->createElement('valor_fijo',$pricing_pag->get_pri_fijo());
		$valor_variable=$xml->createElement('valor_variable',$pricing_pag->get_pri_variable());
		if($pricing_pag->get_id_marchand()){
			$mensaje='Pricing dedicado';
		}
		else $mensaje='Pricing generico';
		$usando=$xml->createElement('usando','STEP X ID '.$pricing_pag->get_id().'('.$mensaje.')');
		$pricing_tco->appendChild($tipo);
		$pricing_tco->appendChild($valor);
		$pricing_tco->appendChild($valido_desde);
		$pricing_tco->appendChild($valor_fijo);
		$pricing_tco->appendChild($valor_variable);
		$pricing_tco->appendChild($usando);
		$transa->appendChild($pricing_tco);

		$pricing_marchand=$xml->createElement('pricing_marchand');
		$tipo=$xml->createElement('tipo','pagador');
		$valor=$xml->createElement('valor',$sum_cdi);
		$valido_desde=$xml->createElement('valido_desde');
		$valor_fijo=$xml->createElement('valor_fijo',$pricing_cdi->get_pri_fijo());
		$valor_variable=$xml->createElement('valor_variable',$pricing_cdi->get_pri_variable());
		if($pricing_cdi->get_id_marchand()){
			$mensaje='Pricing dedicado';
		}
		else $mensaje='Pricing generico';
		$usando=$xml->createElement('usando','STEP X ID '.$pricing_cdi->get_id().'('.$mensaje.')');
		$pricing_marchand->appendChild($tipo);
		$pricing_marchand->appendChild($valor);
		$pricing_marchand->appendChild($valido_desde);
		$pricing_marchand->appendChild($valor_fijo);
		$pricing_marchand->appendChild($valor_variable);
		$pricing_marchand->appendChild($usando);
		$transa->appendChild($pricing_marchand);
		$status=strtoupper(substr($registro->obtener_estado(), 0,1));
		$status_ini=$xml->createElement('status_ini',$status);
		$importe_bc=$xml->createElement('importe_bc',$barcode->get_monto());
		$importe_gw=$xml->createElement('importe_gw',$registro->obtener_monto_bruto());
		$importe_exacto='0';
		if($barcode->get_monto()==$registro->obtener_monto_bruto()){
			$importe_exacto='1';
		}
		$importe_exacto=$xml->createElement('importe_exacto',$importe_exacto);
		$transa->appendChild($status_ini);
		$transa->appendChild($importe_bc);
		$transa->appendChild($importe_gw);
		$transa->appendChild($importe_exacto);

		$xml->appendChild($transa);

		return $xml->saveXML();
	}
	private function obtener_estado_para_transas(Registro_mercadopago $registro)
	{
		$id_authstat=false;
		switch ($registro->obtener_estado()) {
			case Registro_mercadopago::ESTADO_APROBADO:
				$id_authstat=Authstat::MERCADOPAGO_TRANSACCION_APROBADA;
				break;
			case Registro_mercadopago::ESTADO_RECHAZADO:
				$id_authstat=Authstat::MERCADOPAGO_TRANSACCION_RECHAZADA;
				break;
			// case Registro_mercadopago::ESTADO_EN_PROCESO:
			// 	$id_authstat=Authstat::MERCADOPAGO_TRANSACCION_PENDIENTE;
			// 	break;
			// case Registro_mercadopago::ESTADO_PENDIENTE:
			// 	$id_authstat=Authstat::MERCADOPAGO_TRANSACCION_PENDIENTE;
			// 	break;
			// case Registro_mercadopago::ESTADO_EN_MEDIACION:
			// 	$id_authstat=Authstat::MERCADOPAGO_TRANSACCION_PENDIENTE;
			// 	break;
			// case Registro_mercadopago::ESTADO_CANCELADO:
			// 	$id_authstat=Authstat::MERCADOPAGO_TRANSACCION_RECHAZADA;
			// 	break;
			// case Registro_mercadopago::ESTADO_REVERTIDO:
			// 	$id_authstat=Authstat::MERCADOPAGO_TRANSACCION_PENDIENTE;
			// 	break;
			// case Registro_mercadopago::ESTADO_CONTRACARGO:
			// 	$id_authstat=Authstat::MERCADOPAGO_TRANSACCION_PENDIENTE;
			// 	break;
		}
		return $id_authstat;
	}
	private function optimizar_mp($id_mp)
    {
        if(!self::$mp){
            self::$mp=new Mp();
            return self::$mp->get($id_mp);
            }
        return self::$mp;
    }
    private function buscar_pagos($mp,$offset, $limit)
    {
	    $parametros=array();
            $parametros["range"]="date_last_updated";
            $parametros["begin_date"]= "NOW-".self::PERIODO_DE_CONSULTA_MERCADOPAGO;
            $parametros["end_date"]="NOW";
            $parametros["offset"] = $offset;
            $parametros["limit"] = $limit;
	    return $mp->get ("/v1/payments/search", $parametros);
    }
}
