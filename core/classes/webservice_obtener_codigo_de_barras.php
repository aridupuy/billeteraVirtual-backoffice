<?php
class Webservice_obtener_codigo_de_barras extends Webservice{
    const PARAMETRO_NRO_BOLETA="nro_boleta";
    public function ejecutar($array) {
        if(!isset($array[self::PARAMETRO_NRO_BOLETA])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_NRO_BOLETA."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!is_numeric($array[self::PARAMETRO_NRO_BOLETA])){
            $mensaje="El parámetro '".self::PARAMETRO_NRO_BOLETA."' debe ser un numero. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(count($array)!=1){
            $mensaje="El número de argumentos no es correcto.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        $rs_bolemarchand=  Bolemarchand::select_barcodes_boleta(self::$marchand->get_id(),$array[self::PARAMETRO_NRO_BOLETA]);
        if($rs_bolemarchand->RowCount()<1 ){
            # CORREGIR EJECUCION CORRECTA O NO
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
            $mensaje="No existe boleta con ese número.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        else{
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
            foreach ($rs_bolemarchand as $row){
                $this->adjuntar_dato_para_usuario($row['barcode']);
            }
            $mensaje="Codigos de barra de la boleta encontrados correctamente.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
        }
    }
}