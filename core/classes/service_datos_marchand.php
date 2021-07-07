<?php
class Service_datos_marchand extends Device_service{

    public function ejecutar($array) {
        $marchand= new Marchand();
        $marchand= self::$marchand;
        $datos=array("Nombre"=>$marchand->get_nombre(),"apellido_rs"=>$marchand->get_apellido_rs(),"cuit"=>$marchand->get_documento()
                ,"id"=>$marchand->get_id(),"logo"=>$marchand->get_mlogo(),"mercalpha"=>$marchand->get_mercalpha());
        $this->adjuntar_dato_para_usuario($datos);
        $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
        return;
    }

}