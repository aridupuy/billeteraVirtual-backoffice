<?php

class Ordenador_pagomiscuentas extends Ordenador{
    const DESACTIVAR_BDD=false;
    const ACTIVAR_DEBUG=true;
    const DESACTIVAR_PMCABC_VACIO=true;
    const CANTIDAD_DE_ARCHIVOS_PROCESABLES=30;
    const CANTIDAD_DE_REGISTROS_POR_ARCHIVO=15000; //por la longitud del contador de registros 
    const MAXIMO_MONTO_TOTAL=99999999999;
    const LONGITUD_FILA='280';
    const PREFIJO_ARCHIVO="FAC"; 
    const NRO_EMPRESA="4551";
    const RELLENO_NUMERICO='0';
    const IDENTIFICADOR_CLIENTE='pmc19';
    const IDENTIFICADOR_FACTURA="";
    const PMC_ABC="pmcabc";
    const PMC_19="pmc19";
    const FECHA_1="fecha_vto";
    const MONTO_1 = "monto";
    const FECHA_2 = "fvto_2";
    const MONTO_2 = "monto_2";
    const FECHA_3 = "fvto_3";
    const MONTO_3 = "monto_3";
    const ID_BARCODE_1="id_barcode";
    const ID_BARCODE_2="id_barcode_2";
    const ID_BARCODE_3="id_barcode_3";
    const CODIGO_DE_BARRAS = "barcode";
    const CODIGO_DE_BARRAS_2 = "barcode_2";
    const CODIGO_DE_BARRAS_3= "barcode_3";
    const CONCEPTO = "boleta_concepto";
    const FECHA_GEN="fechagen";
    const SC="sc";
    const BARRAND="barrand";
    const ID_MP=Mp::PAGOMISCUENTAS;
    const PROCESA_ARCHIVO_CONTROL=false;
    const PERMITIR_ZIPEADO=true;
    private $total=0;
    protected $pmcabc="";
    protected function consultar_bdd(DateTime $fecha_a_ordenar,$nro_archivo) {
        developer_log("| Ordenador Pago mis Cuentas. Consultando datos espere...");
        $recordset=  Barcode::select_barcode_pagomiscuentas($fecha_a_ordenar,(self::CANTIDAD_DE_ARCHIVOS_PROCESABLES*self::CANTIDAD_DE_REGISTROS_POR_ARCHIVO)+1);
        if(!$recordset)
            return false;
        # TEEEEEEEEEEEEEEEEEMP
        if (self::ACTIVAR_DEBUG)
            developer_log("| Se han obtenido ".$recordset->rowCount()." registros.");
        return $recordset;
    }
    protected function nombrar_archivo(DateTime $hoy,$nro_archivo) {
        if($nro_archivo <10 )
            $nro_archivo="0".$nro_archivo;
        $nombre=  self::PREFIJO_ARCHIVO.self::NRO_EMPRESA.".".$hoy->format('dmy').".$nro_archivo";
        return $nombre;
    }
    protected function obtener_encabezado($nro_archivo) {
        $hoy=new DateTime("now");
        $salto_de_linea="\n";
        $this->total=0;
        $encabezado=str_pad("0400".self::NRO_EMPRESA.$hoy->format("Ymd"),  self::LONGITUD_FILA,  self::RELLENO_NUMERICO);
        if($encabezado=="" or $encabezado==false or $encabezado==null){
            if (self::ACTIVAR_DEBUG)
                developer_log("|| Error al generar el encabezdo.");
            return false;
        }
          if (self::ACTIVAR_DEBUG)
            developer_log("| Encabezado creado correctamente.");
        return $encabezado;
    }
    protected function obtener_fila($row) {
        
        $monto= number_format($row[self::MONTO_1],2);
        $monto=  str_replace(",", "", $monto);
        $this->total=  floatval($monto)+floatval($this->total);
        if(self::MONTO_2!=false){
            $monto2= number_format($row[self::MONTO_2],2);
            $monto2=  str_replace(",", "", $monto2);
        }
        if(self::MONTO_3!=false){
            $monto3= number_format($row[self::MONTO_3],2);
            $monto3=  str_replace(",", "", $monto3);
        }
        $monto=  str_replace(".", "", $monto);
        $monto =str_pad($monto, 5,"0",  STR_PAD_RIGHT);
        if($row[self::PMC_19]=="")
           $row[self::PMC_19]= Barcode::generar_codelec($row[self::CODIGO_DE_BARRAS]); 
        $id_factura=  substr($row[self::CODIGO_DE_BARRAS], 28, 1);
        $id_factura.= substr($row[self::CODIGO_DE_BARRAS], 21,7);
        $id_factura.=$this->generar_pmcabc($row[self::FECHA_1]);
        $registro_pagomiscuentas=new Registro_pagomiscuentas();
        //Si no tiene segundo vencimiento pero si tiene el tercero este no se informara, solo se informara el primero
        if((isset($row[self::FECHA_2]) AND !empty($row[self::FECHA_2]) ) AND (isset($row[self::MONTO_2]) AND !empty($row[self::MONTO_2]) )AND (isset($row[self::FECHA_3]) AND !empty($row[self::FECHA_3]) ) AND (isset($row[self::MONTO_3]) AND !empty($row[self::MONTO_3]) )){
            $registro_pagomiscuentas->construir_fila($row[self::IDENTIFICADOR_CLIENTE], $id_factura,new DateTime($row[self::FECHA_1]), $row[self::MONTO_1], $row[self::CONCEPTO], $row[self::CODIGO_DE_BARRAS], new DateTime($row[self::FECHA_2]),$row[self::MONTO_2], new DateTime($row[self::FECHA_3]),$row[self::MONTO_3]);
        }
        else if((isset($row[self::FECHA_2]) AND !empty($row[self::FECHA_2]) ) AND (isset($row[self::MONTO_2]) AND !empty($row[self::MONTO_2]))){
            $registro_pagomiscuentas->construir_fila($row[self::IDENTIFICADOR_CLIENTE], $id_factura,new DateTime($row[self::FECHA_1]), $row[self::MONTO_1], $row[self::CONCEPTO], $row[self::CODIGO_DE_BARRAS], new DateTime($row[self::FECHA_2]),$row[self::MONTO_2]);
        }
        else{
            $registro_pagomiscuentas->construir_fila($row[self::IDENTIFICADOR_CLIENTE], $id_factura,new DateTime($row[self::FECHA_1]), $row[self::MONTO_1], $row[self::CONCEPTO], $row[self::CODIGO_DE_BARRAS]);
        } 
        return $registro_pagomiscuentas;
    }
    protected function obtener_pie($nro_archivo){
        $hoy=new DateTime("now");
        $salto_de_linea="\n";
        $this->total=  number_format($this->total,2);
        $this->total= str_replace(".","", $this->total);
        $this->total= str_replace(",","", $this->total);
        $pie="9400".self::NRO_EMPRESA.$hoy->format("Ymd").str_pad($this->cantidad_registros,  7,  self::RELLENO_NUMERICO,  STR_PAD_LEFT).  str_pad("", 7,  self::RELLENO_NUMERICO,STR_PAD_LEFT).str_pad(substr($this->total,0,11),11,self::RELLENO_NUMERICO,STR_PAD_LEFT);
        $pie=str_pad($pie,  self::LONGITUD_FILA,  self::RELLENO_NUMERICO);
        $pie=$salto_de_linea.$pie.$salto_de_linea;
        if($pie=="" or $pie==false or $pie==null){
            if (self::ACTIVAR_DEBUG)
                developer_log("|| Error al generar el pie.");
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
        $barcode->set_id_barcode($row[self::ID_BARCODE_1]);
        $pmcabc=strtoupper($this->generar_pmcabc($row[self::FECHA_1]));
        $barcode->set_pmcabc($pmcabc);
//        $barcode->set_pmc19($row[self::PMC_19]);
        if($barcode->set()){
            if(isset($row[self::CODIGO_DE_BARRAS_2])){
                $barcode_2=new Barcode();
                $barcode_2->set_id_barcode($row[self::ID_BARCODE_2]);
                $barcode_2->set_pmcabc($pmcabc);
//                $barcode_2->set_pmc19($row[self::PMC_19]);
                if($barcode_2->set()){
                    if(isset($row[self::CODIGO_DE_BARRAS_3])){
                        $barcode_3=new Barcode();
                        $barcode_3->set_id_barcode($row[self::ID_BARCODE_3]);
                        $barcode_3->set_pmcabc($pmcabc);
//                        $barcode_3->set_pmc19($row[self::PMC_19]);
                        if(!$barcode_3->set()){
                            if (self::ACTIVAR_DEBUG)
                                developer_log("|| Error al actualizar el barcode. ".$row[self::CODIGO_DE_BARRAS] ."pmcabc:".$pmcabc." pmc19: ".$row[self::PMC_19]);
                            return false;
                        }
                        
                    }
                }
                else {
                    if (self::ACTIVAR_DEBUG)
                                developer_log("|| Error al actualizar el barcode. ".$row[self::CODIGO_DE_BARRAS] ."pmcabc:".$pmcabc." pmc19: ".$row[self::PMC_19]);
                    return false;
                }
            }
            return true;    
        }
        if (self::ACTIVAR_DEBUG)
            developer_log("|| Error al actualizar el barcode. ".$row[self::CODIGO_DE_BARRAS] ."pmcabc:".$pmcabc." pmc19: ".$row[self::PMC_19]);
        return false;
    } 
    protected function generar_pmcabc($fecha){
        $fecha_0=DateTime::createFromFormat("!Y-m-d",self::FECHA_DE_REFERENCIA);
        $fecha_vto=new DateTime($fecha);
        $diferencia=$fecha_0->diff($fecha_vto);
        $dias=$diferencia->format($diferencia->days);
        $resultado=base_convert($dias, 10, '35');
        if(strlen($resultado)<=3)
            $pmcabc=str_pad($resultado, 3,"0" ,STR_PAD_LEFT);
        else{
            if (self::ACTIVAR_DEBUG)
                developer_log("El pmc no tiene 3 caracteres."); 
                developer_log("el resultado es $resultado. ");
                developer_log("este registro se exportara cada vez.");
                if(self::DESACTIVAR_PMCABC_VACIO){
                    $pmcabc="000";
                    developer_log("el pmcabc se manda 000.");
                }
                else
                    developer_log("el pmcabc se manda vacio.");
        }
        return $pmcabc;
    }
    protected function procesar_archivo_control (DateTime $fecha_a_ordenar,$archivos,$nombre){
        parent::procesar_archivo_control($fecha_a_ordenar,$archivos, $nombre);
    }
    protected function calcular_monto_total($resultset, $nro_archivo) {
        $current_row=$resultset->currentRow();
        $i = 0;
        $monto_total=array($nro_archivo=>0);
        if (self::ACTIVAR_DEBUG)
            developer_log("Obteniendo el monto total  comenzando por el archivo nro: $nro_archivo");
//        $resultset->move(0);
        foreach ($resultset as $row) {
            if($nro_archivo<=STATIC::CANTIDAD_DE_ARCHIVOS_PROCESABLES AND $i<=STATIC::CANTIDAD_DE_REGISTROS_POR_ARCHIVO){
                $monto_total[$nro_archivo]=  floatval($row[STATIC::MONTO_1])+$monto_total[$nro_archivo];
                $i++;
            }
            else{
                $nro_archivo++;
                $monto_total[$nro_archivo]=0;
                $i=0;
            }
                
        }
        $resultset->move($current_row);
        return $monto_total;    
        
    }
}
