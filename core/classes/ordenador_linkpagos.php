<?php

class ordenador_linkpagos extends Ordenador {
    const ACTIVAR_DEBUG=true;
   const CANTIDAD_DE_ARCHIVOS_PROCESABLES=99999;
    const CANTIDAD_DE_REGISTROS_POR_ARCHIVO=99999997;

 //   const CANTIDAD_DE_REGISTROS_POR_ARCHIVO=99;
//    const CANTIDAD_DE_ARCHIVOS_PROCESABLES=2;

    const MAXIMO_MONTO_TOTAL=999999999999999999;
    const DESACTIVAR_BDD =false;
    const LONGITUD_FILA = '131';
    const FORMATO_FECHA = 'ymd';
    const PREFIJO_ARCHIVO = "P";
    const CODIGO_ENTE = 'AC8';
    const PREFIJO_FACTURACION_HEADER = 'HRFACTURACION';
    const PREFIJO_FACTURACION_FOOTER = 'TRFACTURACION';
    const PMC_DEUDA="pmcdeuda";
    const CODIGO_DE_BARRAS="barcode_1";
    const BARCODE_1="barcode_1";
    const BARCODE_2="barcode_2";
    const BARCODE_3="barcode_3";
    const ID_BARCODE_1="id_barcode_1";
    const ID_BARCODE_2="id_barcode_2";
    const ID_BARCODE_3="id_barcode_3";
    const PMC19="pmc19";
    const MONTO_1="monto_1";
    const MONTO_2="monto_2";
    const MONTO_3="monto_3";
    const FECHA_1="fvto_1";
    const FECHA_2="fvto_2";
    const FECHA_3="fvto_3";
    const FECHA_GEN='fechagen';
    const SC="sc";
    const BARRAND="barrand";
    const IDENTIFICADOR_CLIENTE="pmc19";
    const IDENTIFICADOR_CONCEPTO="";
    const ID_MP=Mp::LINKPAGOS;
    const PROCESA_ARCHIVO_CONTROL=TRUE;
    #ver luego como obtener los 3 vencimientos
    const BOLETA="id_boleta_marchand";
    const PREFIJO_NOMBRE_ARCHIVO_CONTROL='CAC8';
    const PREFIJO_HEADER_ARCHIVO_CONTROL='HRPASCTRL';
    const PERMITIR_ZIPEADO=false;
    
    protected $importe_1=0;
    protected $importe_2=0;
    protected $importe_3=0;
    protected $ultimo_vencimiento;
    protected $nro_archivo;
    protected function consultar_bdd(DateTime $fecha_a_ordenar,$nro_archivo) {
        developer_log("| Ordenador Link pagos Consultando datos espere...");
        $recordset=  Barcode::select_barcode_link_pagos($fecha_a_ordenar,(self::CANTIDAD_DE_ARCHIVOS_PROCESABLES*self::CANTIDAD_DE_REGISTROS_POR_ARCHIVO)+1);
        if(!$recordset)
            return false;
        developer_log("| Se han obtenido ".$recordset->rowCount()." registros.");
        return $recordset;
    }
    protected function nombrar_archivo(DateTime $hoy,$nro_archivo) {
        
        $nombre= self::PREFIJO_ARCHIVO . self::CODIGO_ENTE . $nro_archivo . strtoupper(dechex($hoy->format('m'))) . $hoy->format('d');
        return $nombre;
    }
    protected function obtener_encabezado($nro_archivo) {
//        $salto_de_linea = "\n";
        $fecha_generacion = new DateTime("now");
        $encabezado=self::PREFIJO_FACTURACION_HEADER;
        $encabezado.=self::CODIGO_ENTE;
        $encabezado.=$fecha_generacion->format(self::FORMATO_FECHA);
        $this->nro_archivo=$nro_archivo;
        $encabezado.=str_pad($nro_archivo,5,"0", STR_PAD_LEFT );
        $encabezado.=str_pad("", 104," ",STR_PAD_LEFT);
//        $encabezado.=$salto_de_linea;
        if ($encabezado == "" or ! $encabezado) {
            if (self::ACTIVAR_DEBUG)
                developer_log("|| Error no se pudo generar el encabezado.");
            return false;
        }
        if (self::ACTIVAR_DEBUG)
            developer_log("| Encabezado creado correctamente.");
        return $encabezado;
    }
    protected function obtener_fila($row) {
        $registro_link=new Registro_linkpagos("");
        if(($row[self::PMC_DEUDA]=  $this->obtener_pmc_deuda($row[self::FECHA_1]))==false){
            if(self::ACTIVAR_DEBUG)
                developer_log("|| Error al generar el pmcdeuda.");
            return false;
        }
        if(($row[self::PMC19]= Barcode::generar_codelec($row[self::CODIGO_DE_BARRAS]))==false){
            if(self::ACTIVAR_DEBUG)
                developer_log("|| Error al generar el pmcdeuda.");
            return false;
        }
        
        if(isset($row[self::MONTO_1])){
            $importe_1=  number_format($row[self::MONTO_1],2);
            $importe_1= str_replace(",","",  $importe_1);
            $importe_1= str_replace(".","",  $importe_1);
            if(!isset($this->importe_1[$this->nro_archivo])){
                $this->importe_1=array($this->nro_archivo,0);
            }
            $this->importe_1[$this->nro_archivo]=  floatval($importe_1)+floatval($this->importe_1[$this->nro_archivo]);
        }
        if(isset($row[self::MONTO_2])){
            $importe_2=  number_format($row[self::MONTO_2],2);
            $importe_2= str_replace(".","",  $importe_2);
            $importe_2= str_replace(",","",  $importe_2);
            if(!isset($this->importe_2[$this->nro_archivo])){
                $this->importe_2=array($this->nro_archivo,0);
            }
            $this->importe_2[$this->nro_archivo]=floatval($importe_2)+floatval($this->importe_2[$this->nro_archivo]);
            
        }
        if(isset($row[self::MONTO_3])){
            $importe_3=  number_format($row[self::MONTO_3],2);
            $importe_3= str_replace(".","",  $importe_3);
            $importe_3= str_replace(",","",  $importe_3);
            if(!isset($this->importe_3[$this->nro_archivo])){
                $this->importe_3=array($this->nro_archivo,0);
            }
            $this->importe_3[$this->nro_archivo]=floatval($importe_3)+floatval($this->importe_3[$this->nro_archivo]);
        }
        if(isset($row[self::FECHA_1])){
            $fecha_1=new DateTime($row[self::FECHA_1]);
	    if($fecha_1 > $this->ultimo_vencimiento)
            	$this->ultimo_vencimiento=$fecha_1;
        }
        if(isset($row[self::FECHA_2])){
            $fecha_2=new DateTime($row[self::FECHA_2]);
	    if($fecha_2 > $this->ultimo_vencimiento)
            	$this->ultimo_vencimiento=$fecha_2;
        }
        if(isset($row[self::FECHA_3])){
            $fecha_3=new DateTime($row[self::FECHA_3]);
	    if($fecha_3 > $this->ultimo_vencimiento)
            	$this->ultimo_vencimiento=$fecha_3;
        }
        //Si no tiene segundo vencimiento pero si tiene el tercero este no se informara, solo se informara el primero
        if((isset($row[self::FECHA_2]) AND !empty($row[self::FECHA_2]) ) AND (isset($row[self::MONTO_2]) AND !empty($row[self::MONTO_2]) )AND (isset($row[self::FECHA_3]) AND !empty($row[self::FECHA_3]) ) AND (isset($row[self::MONTO_3]) AND !empty($row[self::MONTO_3]) )){
            $registro_link->generar_fila($row[self::PMC_DEUDA], $row[self::PMC19], $row[self::BARCODE_1], $fecha_1, $importe_1, $fecha_2, $importe_2, $row[self::BARCODE_2], $fecha_3, $importe_3,$row[self::BARCODE_3]);
        }
        elseif((isset($row[self::FECHA_2]) AND !empty($row[self::FECHA_2]) ) AND (isset($row[self::MONTO_2]) AND !empty($row[self::MONTO_2]))){
            $registro_link->generar_fila($row[self::PMC_DEUDA], $row[self::PMC19], $row[self::BARCODE_1], $fecha_1, $importe_1, $fecha_2, $importe_2,$row[self::BARCODE_2]);
        }
        else{
            $registro_link->generar_fila($row[self::PMC_DEUDA], $row[self::PMC19], $row[self::BARCODE_1], $fecha_1, $importe_1);
        }
        return $registro_link;
    }
    protected function obtener_pie($nro_archivo) {
        $fecha_generacion = new DateTime("now");
        $pie=self::PREFIJO_FACTURACION_FOOTER;
        //$this->cantidad_registros+2 por que tiene que incluir header y footer
        $pie.=str_pad(($this->cantidad_registros+2),8,"0",STR_PAD_LEFT);
        $importe_1=  number_format($this->importe_1[$nro_archivo],0);
        $importe_1= str_replace(".","",  $importe_1);
        $importe_1= str_replace(",","",  $importe_1);
        
        $importe_2=  number_format($this->importe_2[$nro_archivo],0);
        $importe_2= str_replace(".","",  $importe_2);
        $importe_2= str_replace(",","",  $importe_2);
        
        $importe_3=  number_format($this->importe_3[$nro_archivo],0);
        $importe_3= str_replace(".","",  $importe_3);
        $importe_3= str_replace(",","",  $importe_3);
        
        $pie.=str_pad($importe_1, 18,"0",STR_PAD_LEFT);
        $pie.=str_pad($importe_2, 18,"0",STR_PAD_LEFT);
        $pie.=str_pad($importe_3, 18,"0",STR_PAD_LEFT);
        $pie.=str_pad("",56," ", STR_PAD_LEFT );
        if ($pie== "" or ! $pie) {
            if (self::ACTIVAR_DEBUG)
                developer_log("|| Error no se pudo generar el Pie.");
            return false;
        }
        if (self::ACTIVAR_DEBUG)
            developer_log("| Pie creado correctamente.");
        return $pie;
    }
    protected function actualizar_deuda($row) {
        if(self::DESACTIVAR_BDD)
            return true;
        $barcode=new Barcode();
        if(($pmc_deuda=$this->obtener_pmc_deuda($row[self::FECHA_1]))==false)
            return false;
        $barcode->set_pmcdeuda($pmc_deuda);
        $barcode->set_pmc19($row[self::PMC19]);
        $barcode->set_id_barcode($row[self::ID_BARCODE_1]);
	error_log("linkpagos:".json_encode($row));
        if($barcode->set()){
            if(isset($row[self::BARCODE_2])){
                $barcode_2=new Barcode();
                $barcode_2->set_pmcdeuda($pmc_deuda);
                $barcode_2->set_id_barcode($row[self::ID_BARCODE_2]);
                if($barcode_2->set()){
                    if(isset($row[self::BARCODE_3])){
                        $barcode_3=new Barcode();
                        $barcode_3->set_pmcdeuda($pmc_deuda);
                        $barcode_3->set_id_barcode($row[self::ID_BARCODE_3]);
                        if(!$barcode_3->set()){
                            if (self::ACTIVAR_DEBUG)
                                developer_log("|| Error al actualizar el barcode 3. ".$row[self::CODIGO_DE_BARRAS] );
                            return false;
                        }
                    }
                }
                else {
                    if (self::ACTIVAR_DEBUG)
                                developer_log("|| Error al actualizar el barcode 2. ".$row[self::CODIGO_DE_BARRAS] );
                    return false;
                }
            }
            return true;  
        }
	developer_log("|| Error al actualizar el barcode 1. ".$row[self::CODIGO_DE_BARRAS]);
        return false;
    }
    # SUBIR ESTA FUNCION A ORDENADOR?
    private function obtener_pmc_deuda($fecha_1){
        $fecha_referencia= DateTime::createFromFormat("!Y-m-d", self::FECHA_DE_REFERENCIA);
        $fecha_1=new DateTime($fecha_1);
        $diferencia=$fecha_referencia->diff($fecha_1);
        $dias=$diferencia->format($diferencia->days);
//        if(strlen($dias)<=5){
        $pmcdeuda=  str_pad ($dias, 5,"0",STR_PAD_LEFT);
	developer_log("pmcdeuda:   ".$pmcdeuda);
     return substr($pmcdeuda,0,5);
//        }
//        return substr($string, $start);
    }
    protected function procesar_archivo_control(Datetime $fecha_a_ordenar,$archivos,$nombre){
        $archivo_control="";
        foreach ($archivos as $nro_archivo=>$archivo) {
//            $importe_1=  str_replace(".", "", $this->importe_1[$nro_archivo]);
//            $importe_1=  str_replace(",", "", $importe_1);
            $importe_1=  number_format($this->importe_1[$nro_archivo],0,",","");
            $importe_2=  number_format($this->importe_2[$nro_archivo],0,",","");
            $importe_3=  number_format($this->importe_3[$nro_archivo],0,",","");
            $tamaño_byte=strlen($archivo);
            $cantidad_de_registros=  number_format((strlen($archivo)/self::LONGITUD_FILA),0,",","");//header mas footer
            $nombre[$nro_archivo.'C']=  self::PREFIJO_NOMBRE_ARCHIVO_CONTROL.$nro_archivo.dechex($fecha_a_ordenar->format('m')) . $fecha_a_ordenar->format('d');
            $archivo_control.=self::PREFIJO_HEADER_ARCHIVO_CONTROL.$fecha_a_ordenar->format('Ymd').self::CODIGO_ENTE.$nombre[$nro_archivo].  str_pad(($tamaño_byte),10,"0",STR_PAD_LEFT).  str_pad("", 37," ");
            $archivo_control.="LOTES".str_pad($nro_archivo, 5,"0",STR_PAD_LEFT).str_pad($cantidad_de_registros,8,"0",STR_PAD_LEFT).str_pad($importe_1, 18,"0",STR_PAD_LEFT).str_pad($importe_2, 18,"0",STR_PAD_LEFT).str_pad($importe_3, 18,"0",STR_PAD_LEFT).str_pad("", 3," ",STR_PAD_LEFT);
            if($this->ultimo_vencimiento==null)
                $this->ultimo_vencimiento=new DateTime('today');
            $archivo_control.="FINAL".str_pad($cantidad_de_registros,8,"0",STR_PAD_LEFT).str_pad($importe_1, 18,"0",STR_PAD_LEFT).str_pad($importe_2, 18,"0",STR_PAD_LEFT).str_pad($importe_3, 18,"0",STR_PAD_LEFT).$this->ultimo_vencimiento->format("Ymd");
            if(!$this->insertar_control($fecha_a_ordenar,$nombre[$nro_archivo.'C'], $nro_archivo, $archivo_control))
                return array($archivos,$nombre);
            $archivos[$nro_archivo."C"]=$archivo_control;
            $archivo_control="";
        }
        return array($archivos,$nombre);
    }
}
