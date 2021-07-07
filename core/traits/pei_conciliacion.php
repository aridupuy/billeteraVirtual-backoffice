<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of pei
 *
 * @author ariel
 */
class Pei_conciliacion extends Pago_electronico {
    private $usuario = "sv_33711566959_pei";
    private $pass = "n7*8p7fTe1y3kLlxbv.Pyi";
    private $client_id= "85d71240-0fd5-41bc-a49e-fd44812ff7c3";
    private $autenticate_token= "";
    const ENDPOINT_AUTENTICACION="https://api.redlink.com.ar/redlink/produccion/conciliacion-pei/0/0/5/sesion/";
    const ENDPOINT_CONSULTA="https://api.redlink.com.ar/redlink/produccion/conciliacion-pei/0/0/9/operaciones/consulta/descarga";
    const ID_COMERCIO = "1481";
    const ESTADO_ACEPTADA = "ACEPTADA";
    const ESTADO_RECHAZADA = "RECHAZADA";
    const ESTADO_DEVUELTO = "DEVUELTO";
    const RESPUESTA_ACEPTADO = 1;
    const RESPUESTA_RECHAZO = 0;
    const RESPUESTA_DEVUELTO=2;
    const enum_concepto=array(
            "COMPRA_DE_BIENES",
            "PAGO_DE_SERVICIOS",
            "EXTRACCION",
            "PAGO_DE_COMBUSTIBLE",
            "COMPRA_DE_DIVISA"
        );
    public function __construct() {
        $this->autenticar();
    }
    public function get_token(){
        return $this->autenticate_token;
    }
    public function autenticar() {
        $usuario = $this->usuario;
        $pass = $this->pass;
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => self::ENDPOINT_AUTENTICACION.self::ID_COMERCIO,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => " { \"usuario\": \"$usuario\",\"password\": \"$pass\"}",
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "content-type: application/json",
            "x-ibm-client-id: ".$this->client_id,
            "cliente:190.184.254.68",
            "requerimiento:190.184.254.74"
          ),
        ));

        $response = curl_exec($curl);
        developer_log($response);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
          echo "cURL Error #:" . $err;
        } 
        $r=(json_decode($response,true));
        $this->autenticate_token = $r["token"];
    }
   
/*$token, $id_transacction, Marchand $marchand, $concepto, $monto, $bin,$tipotarjeta, $email, $id_customer, $entidad, $referencia)*/
    public function generar_pago(...$param) {
        
        throw  new Exception("Use la clase pei para pagos.");
    }
    
   
    public function devolver($param) {
        
        throw  new Exception("Use la clase pei para devoluciones.");
    }
    public function devolver_parcial($param, $monto) {
        throw  new Exception("Use la clase pei para devoluciones.");
        
    }
    public function consultar_pagos(...$param) {
      list($id_operacion ) = $param;
      list($response,$err)=$this->obtener_datos_pago($id_operacion);
      $respuesta = json_decode($response,true);
      if(!isset($respuesta["resultado"])){
          
      }
/*        var_dump(json_encode($respuesta["resultado"]));
    exit();*/
      if($respuesta["resultado"][0]["estado"]==self::ESTADO_ACEPTADA and $respuesta["resultado"][0]["saldo"]==0){
          return self::RESPUESTA_DEVUELTO;
      }
      switch ($respuesta["resultado"][0]["estado"]){
          case self::ESTADO_ACEPTADA:
              return self::RESPUESTA_ACEPTADO;
              break;
          case self::ESTADO_RECHAZADA:
              return self::RESPUESTA_RECHAZO;
              break;
      }
      /*Response tentativo pago correcto*/
      /*
        
       {
    "operaciones": [
        {
            "idCanal": "PEIECOM",
            "sucursal": {
                "codigo": "COCM",
                "descripcion": "Codigo digital virtual"
            },
            "bancoEmisor": {
                "nombre": "Banco Link"
            },
            "idOperacion": "537623",
            "idReferenciaTrxComercio": "73859000000243402033000000003200526_57",
            "idReferenciaOperacionComercio": "632815100000",
            "numeroReferenciaBancaria": "000000222405",
            "estado": "Aceptada",
            "fecha": "2020-05-26T20:19:01Z",
            "tipo": "Pago",
            "modalidad": "Tarjeta No Presente",
            "moneda": "ARS",
            "importe": 1100,
            "pan": {
                "prefijo": "778899",
                "sufijo": "4008",
                "longitud": 18
            },
            "concepto": "PAGO_DE_SERVICIOS",
            "saldo": 1100,
            "tarjetaMarca": "Maestro"
        }
    ]
} */
      /*Pago tentativo rechazo
{
    "operaciones": [
        {
            "idCanal": "PEIECOM",
            "sucursal": {
                "codigo": "COCM",
                "descripcion": "Codigo digital virtual"
            },
            "idOperacion": "537470",
            "idReferenciaTrxComercio": "73859000000243402033000000003200526_21",
            "idReferenciaOperacionComercio": "632815100000",
            "estado": "Rechazada",
            "fecha": "2020-05-26T20:20:28Z",
            "tipo": "Pago",
            "modalidad": "Tarjeta No Presente",
            "moneda": "ARS",
            "importe": 1100,
            "pan": {
                "prefijo": "778899",
                "sufijo": "4008",
                "longitud": 18
            },
            "codigoRechazo": {
                "error": "REFERENCIA_TRX_COMERCIO_REPETIDA"
            },
            "concepto": "PAGO_DE_SERVICIOS",
            "saldo": 0
        }
    ]
}       */
        
    }
    public function obtener_datos_pago($id_operacion){
        $json=array(
            "orden"=>array("criterio"=>"fechaOperacion","sentido"=>"DESC"),
            "filtro"=>array("idOperacion"=>$id_operacion)
        );
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => self::ENDPOINT_CONSULTA,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($json),
        CURLOPT_HTTPHEADER => array(
          "cache-control: no-cache",
          "cliente: 190.184.254.70",
          "content-type: application/json",
          "requerimiento: 12",
          "token:".$this->autenticate_token,
          "x-ibm-client-id:".$this->client_id
        ),
      ));
    
      $response = curl_exec($curl);
      $err = curl_error($curl);
      developer_log($response);
      return array($response,$err);
    }

    public function obtener_pagos() {
        $rs= Pei_transaccion::select_pagos_validacion();
        $array=array();
        foreach ($rs as $row){
            $array[]=new Pei_transaccion($row);
        }
        return $array;
    }

    public function obtener_siguiente_num_transaccion() {
        
    }

     public function guardar_transaccion(...$param) {
        list($response, $entidad, $referencia, $authstat,$id_marchand, $monto, $concepto,$status)=$param;
        $transa = new Pei_transaccion();
        self::$mp = new Mp();
        self::$mp->get(MP::PEI); #ver
        $fecha = new DateTime("now");
        $transa->set_fecha($fecha->format("Y-m-d"));
        $transa->set_fecha_gen("now()");
        $transa->set_id_authstat($authstat);
        $transa->set_id_entidad($entidad);
        $transa->set_id_referencia($referencia);
        $transa->set_concepto($concepto);
        $transa->set_status($status);
        $transa->set_id_marchand($id_marchand);
        $transa->set_monto($monto);
        $transa->set_response($response);
        if ($transa->set()) {
            developer_log("guardado");
            return "Pago realizado correctamente.";
        }
        return "Error al registrar el pago en cobrodigital";
    }
}
