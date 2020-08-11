<?php

class Service_cuentas_bancarias extends Device_service{
    
    public function ejecutar($array) {
        $recordset= $recordset=Cbumarchand::select_cuentas_id_marchand(self::$id_marchand);
        if($recordset->rowCount()==0){
            $this->adjuntar_mensaje_para_usuario("No existen cuentas bancarias asociadas a su cuenta.");
            $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_INCORRECTA;
            return ;
        }
        foreach ($recordset as $row) {
            $datos_cuenta=array("Nombre"=>$row['banco'],"Titular"=>$row["titular"],"CUIT"=>$row['cuit'],"id"=>$row["id_cbumarchand"]);
            $this->adjuntar_dato_para_usuario($datos_cuenta);
        }
        $this->respuesta_ejecucion= self::RESPUESTA_EJECUCION_CORRECTA;
        return;
    }

}
