<?php
class webservice_generar_boleta_comprador extends Webservice{
    const PARAMETRO_IMPORTES="importes";
        const SUB_PARAMETRO_IMPORTE_1="importe_1";
        const SUB_PARAMETRO_IMPORTE_2="importe_2";
        const SUB_PARAMETRO_IMPORTE_3="importe_3";
    const PARAMETRO_FECHAS="fechas";
        const SUB_PARAMETRO_FECHA_1="fecha_1";
        const SUB_PARAMETRO_FECHA_2="fecha_2";
        const SUB_PARAMETRO_FECHA_3="fecha_3";
    const PARAMETRO_CONCEPTO="concepto";
    const PARAMETRO_APELLIDO="apellido";
    const PARAMETRO_CORREO="correo";
    const PARAMETRO_DOCUMENTO="documento";
    const PARAMETRO_DIRECCION="direccion";
    public function ejecutar($array) {
        if(!isset($array[self::PARAMETRO_IMPORTES])){
            $this->adjuntar_mensaje_para_usuario("Falta el Parametro".self::PARAMETRO_IMPORTES);
            return;
        }
        
        if(!isset($array[self::PARAMETRO_FECHAS])){
            $this->adjuntar_mensaje_para_usuario("Falta el Parametro".self::PARAMETRO_FECHAS);
            return;
        }
        
        if(!isset($array[self::PARAMETRO_CONCEPTO])){
            $this->adjuntar_mensaje_para_usuario("Falta el Parametro".self::PARAMETRO_CONCEPTO);
            return;
        }
        if(!isset($array[self::PARAMETRO_APELLIDO])){
            $this->adjuntar_mensaje_para_usuario("Falta el Parametro".self::PARAMETRO_APELLIDO);
            return;
        }
        if(!isset($array[self::PARAMETRO_CORREO])){
            $this->adjuntar_mensaje_para_usuario("Falta el Parametro".self::PARAMETRO_CORREO);
            return;
        }
        if(!isset($array[self::PARAMETRO_DOCUMENTO])){
            $this->adjuntar_mensaje_para_usuario("Falta el Parametro".self::PARAMETRO_DOCUMENTO);
            return;
        }
        if(!isset($array[self::PARAMETRO_DIRECCION])){
            $this->adjuntar_mensaje_para_usuario("Falta el Parametro".self::PARAMETRO_DIRECCION);
            return;
        }
        if(isset($array[self::PARAMETRO_IMPORTES][self::SUB_PARAMETRO_IMPORTE_1]) AND !isset($array[self::PARAMETRO_FECHAS][self::SUB_PARAMETRO_FECHA_1])){
                $this->adjuntar_mensaje_para_usuario("Si existe el parametro ". self::SUB_PARAMETRO_IMPORTE_1." Debe existir el parametro ".self::SUB_PARAMETRO_FECHA_1);
                return;
        }
        if(isset($array[self::PARAMETRO_IMPORTES][self::SUB_PARAMETRO_IMPORTE_2]) AND !isset($array[self::PARAMETRO_FECHAS][self::SUB_PARAMETRO_FECHA_2])){
                $this->adjuntar_mensaje_para_usuario("Si existe el parametro ". self::SUB_PARAMETRO_IMPORTE_2." Debe existir el parametro ".self::SUB_PARAMETRO_FECHA_2);
                return;
        }
        if(isset($array[self::PARAMETRO_IMPORTES][self::SUB_PARAMETRO_IMPORTE_3]) AND !isset($array[self::PARAMETRO_FECHAS][self::SUB_PARAMETRO_FECHA_3])){
                $this->adjuntar_mensaje_para_usuario("Si existe el parametro ". self::SUB_PARAMETRO_IMPORTE_3." Debe existir el parametro ".self::SUB_PARAMETRO_FECHA_3);
                return;
        }
        if(count($array[self::PARAMETRO_IMPORTES])!==count($array[self::PARAMETRO_FECHAS])){
            $this->adjuntar_mensaje_para_usuario("Deben existir la misma cantidad de importes y vencimientos.");
            return;
        }
        $importes=array();
        $fechas_vencimiento=array();
        foreach ($array[self::PARAMETRO_IMPORTES] as $campo=>$impor){
		foreach($impor as $importe){
            	     if(!is_numeric($importe)){
              		$this->adjuntar_mensaje_para_usuario("El importe ".$campo." Debe ser numerico.");
               	    	return;
           	     }
            	     else {
                	$importes[]=$importe;
            	     }
		}
        }
        foreach ($array[self::PARAMETRO_FECHAS] as $campo=>$fechas){
 		error_log("fecha");
		foreach($fechas as $fecha){
			error_log($fecha);
        	    if(false===($date= DateTime::createFromFormat("d/m/Y", $fecha))){
                	$this->adjuntar_mensaje_para_usuario("fecha ".$campo." Debe mantener el formato d/m/Y.");
                	return;
            		}
            	    else
                	$fechas_vencimiento[]=$fecha;
		}
        }
	error_log(json_encode($fechas_vencimiento));
	error_log(json_encode($importes));
        $boleta=new Boleta_comprador();
//        $importes=$array[self::PARAMETRO_IMPORTES];
//        $fechas_vencimiento=$array[self::PARAMETRO_FECHAS];
        error_log(json_encode($importes));
        error_log(json_encode($fechas_vencimiento));
        $concepto=$array[self::PARAMETRO_CONCEPTO];
        unset($array[self::PARAMETRO_IMPORTES]);
        unset($array[self::PARAMETRO_FECHAS]);
        unset($array[self::PARAMETRO_CONCEPTO]);
        if(!($bole=$boleta->crear(self::$marchand->get_id_marchand(), $array, $importes, $fechas_vencimiento, $concepto))){
            $this->adjuntar_mensaje_para_usuario("Error al generar la boleta.");
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        $this->adjuntar_dato_para_usuario(array("Nro_boleta"=>$bole->bolemarchand->get_nroboleta()));
        $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
    }

}
