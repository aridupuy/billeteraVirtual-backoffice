<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of webservice_generar_deuda_tarjeta
 *
 * @author arieldupuy
 */
class webservice_generar_deuda_tarjeta extends Webservice_generar_boleta {

    const PARAMETRO_BARCODE = "tarjeta";
    const PARAMETRO_VENCIMIENTO1 = "fecha_vencimiento1";
    const PARAMETRO_VENCIMIENTO2 = "fecha_vencimiento2";
    const PARAMETRO_VENCIMIENTO3 = "fecha_vencimiento3";
    const PARAMETRO_VENCIMIENTO4 = "fecha_vencimiento4";
    const PARAMETRO_IMPORTE1 = "importe1";
    const PARAMETRO_IMPORTE2 = "importe2";
    const PARAMETRO_IMPORTE3 = "importe3";
    const PARAMETRO_IMPORTE4 = "importe4";
    

    public function ejecutar($array) {
        $this->parametros_de_entrada = $array;
        if (!isset($array[self::PARAMETRO_BARCODE])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_BARCODE . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if (!isset($array[self::PARAMETRO_VENCIMIENTO1])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_VENCIMIENTO1 . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }


        if (!isset($array[self::PARAMETRO_IMPORTE1])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_IMPORTE1 . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if (!isset($array[self::PARAMETRO_CONCEPTO])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_CONCEPTO . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        
        $boleta = new Boleta_pagador();
        $rsbarcode = Barcode::select(array("barcode" => $array[self::PARAMETRO_BARCODE]));
        if ($rsbarcode->rowCount() == 0) {
            $mensaje = "Error no se encuentra la tarjeta'" . $array[self::PARAMETRO_BARCODE] . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        $barcode = new Barcode($rsbarcode->fetchRow());
        $id_trix = $barcode->get_id_trix();
      
        $trix = new Trix();
        $trix->get($id_trix);
        $id_climarchand = $trix->get_id_climarchand();
        $modelo = "init";
        $fechas_vencimiento = array();
        $fechas_vencimiento[] = $array[self::PARAMETRO_VENCIMIENTO1];
        if (isset($array[self::PARAMETRO_VENCIMIENTO2]))
            $fechas_vencimiento[] = $array[self::PARAMETRO_VENCIMIENTO2];
        if (isset($array[self::PARAMETRO_VENCIMIENTO3]))
            $fechas_vencimiento[] = $array[self::PARAMETRO_VENCIMIENTO3];
        if (isset($array[self::PARAMETRO_VENCIMIENTO4]))
            $fechas_vencimiento[] = $array[self::PARAMETRO_VENCIMIENTO4];
        foreach ($fechas_vencimiento as $key=>$vencimiento){
            $fechas_vencimiento[$key]= DateTime::createFromFormat("Ymd", $vencimiento);
            $fechas_vencimiento[$key]=$fechas_vencimiento[$key]->format("d/m/Y");
        }
        $importes = array();
        $importes[] = $array[self::PARAMETRO_IMPORTE1];
        if (isset($array[self::PARAMETRO_IMPORTE2]))
            $importes[] = $array[self::PARAMETRO_IMPORTE2];
        if (isset($array[self::PARAMETRO_IMPORTE3]))
            $importes[] = $array[self::PARAMETRO_IMPORTE3];
        if (isset($array[self::PARAMETRO_IMPORTE4]))
            $importes[] = $array[self::PARAMETRO_IMPORTE4];
        
        $concepto=$array[self::PARAMETRO_CONCEPTO];
        try{
        if($boleta->crear($id_climarchand, $modelo, $fechas_vencimiento, $importes, $concepto)){
            $dato["barcode1"]=$boleta->barcode_1->get_barcode();
            $dato["pmc"]=$boleta->barcode_1->get_pmc19();
	    if(isset($boleta->barcode_2) and $boleta->barcode_2!=null)
 	           $dato["barcode2"]=$boleta->barcode_2->get_barcode();
	    if(isset($boleta->barcode_3) and $boleta->barcode_3!=null)
            	$dato["barcode3"]=$boleta->barcode_3->get_barcode();
            if(isset($boleta->barcode_4) and $boleta->barcode_4!=null)
		    $dato["barcode4"]=$boleta->barcode_4->get_barcode();
            $dato["nroBoleta"]=$boleta->bolemarchand->get_nroboleta();
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
            $this->adjuntar_dato_para_usuario ($dato) ;
            $this->adjuntar_mensaje_para_usuario("Deuda informada correctamente.");
        }
        else{
             $this->adjuntar_mensaje_para_usuario("Error al informar Deuda ");
        }
        } catch (Exception $e){
             $this->adjuntar_mensaje_para_usuario($e->getMessage());
        }
    }

}
