<?php

class webservice_obtener_boleta_html extends Webservice {
    public function ejecutar($array) {
        $nro_boleta=$array["nro_boleta"];
        $recordset=Bolemarchand::select_boleta_html(self::$id_marchand,$nro_boleta);
        if($recordset->rowCount()!==1){
            $this->adjuntar_mensaje_para_usuario("No existe una boleta asociada a ese numero.");
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
        }
        $row=$recordset->fetchRow();
        $boleta_html=new View();
        if(!$boleta_html->loadHTML($row["boleta_html"])){
            $this->adjuntar_mensaje_para_usuario ("Error al mostrar la boleta");
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
        }
        $this->adjuntar_dato_para_usuario(array("boleta"=>$boleta_html->saveHTML()));
        $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
    }

}
