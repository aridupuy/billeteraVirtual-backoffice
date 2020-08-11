<?php

class Service_consultar_saldo extends Device_service{
    
    //put your code here
    public function ejecutar($array) {
        $saldo=Moves::select_ultimo_saldo(self::$id_marchand);
        if (!$saldo){
            $this->adjuntar_mensaje_para_usuario("Ha ocurrido un error, no se puede obtener el saldo.");
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
            return;
        }
        if($saldo==0){
            $array["saldo"]=0;
            $this->adjuntar_dato_para_usuario ($saldo);
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
            $this->adjuntar_mensaje_para_usuario("No existe saldo disponible.");
            return;
        }
        $array["saldo"]=$saldo;
        $this->adjuntar_dato_para_usuario($array);
        $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
        return;
        
    }

}
