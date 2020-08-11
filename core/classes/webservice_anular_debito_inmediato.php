<?php

class webservice_anular_debito_inmediato extends Webservice {
    //put your code here
    
    const PARAMETRO_ID="id";
    public function ejecutar($array) {
        if (!isset($array[self::PARAMETRO_ID])) {
            $mensaje = "No se recibieron datos en el parámetro '" . self::PARAMETRO_ID . "'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        $pago_tc=new Ordenador_debitos_decidir();
        try{
        $respuesta = $pago_tc->devolver($array[self::PARAMETRO_ID]);    
        } catch (\Decidir\Exception\SdkException $e){
            $data  = $e->getData();
            if(isset($data["validation_errors"])){
                if(isset($data["validation_errors"]["status"])){
                    if($data["validation_errors"]["status"]=="annulled"){
                        $this->adjuntar_mensaje_para_usuario("El pago ya fue reversado con anterioridad");
                        $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
                        return false;
                    }
                }
            }
        }
        if(isset($respuesta["id"])){
            $this->adjuntar_mensaje_para_usuario("Pago reversado correctamente, En unos minutos se vera reflejado en el sistema");
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
        }
        else{ 
            $this->adjuntar_mensaje_para_usuario("El pago no pudo ser reversado,");
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
        }
        return true;
        
    }

}
