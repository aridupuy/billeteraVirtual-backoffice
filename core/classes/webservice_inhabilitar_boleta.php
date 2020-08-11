<?php
class Webservice_inhabilitar_boleta extends Webservice{
    const PARAMETRO_NRO_BOLETA='nro_boleta';
    
    public function ejecutar($array) {
        $this->parametros_de_entrada=$array;
       
        if(!isset($array[self::PARAMETRO_NRO_BOLETA]) OR !$array[self::PARAMETRO_NRO_BOLETA]){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_NRO_BOLETA."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!is_numeric($array[self::PARAMETRO_NRO_BOLETA])) {
            $mensaje="El parámetro '".self::PARAMETRO_NRO_BOLETA."' no es corecto: debe ser un número. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(count($array)!=1){
            $mensaje="El número de argumentos no es correcto. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        $error=true;
        Model::StartTrans();
        $id_marchand=self::$marchand->get_id_marchand();
        $nro_boleta=$array[self::PARAMETRO_NRO_BOLETA];
        $recordset=Bolemarchand::select_barcodes_boleta($id_marchand,$nro_boleta);
        if($recordset->RowCount()>0){
            foreach ($recordset as $row) {
                if($row['id_authstat']==Authstat::BARCODE_CANCELADO){
                    $mensaje='La boleta se encuentra inhabilitada. ';
                    $this->adjuntar_mensaje_para_usuario($mensaje);
                    Model::FailTrans();
                }
                elseif($row['id_authstat']==Authstat::BARCODE_PAGADO){
                    $mensaje='La boleta se encuentra paga.';
                    $this->adjuntar_mensaje_para_usuario($mensaje);
                    Model::FailTrans();
                }
                else{
                    # El unico estado restante es Barcode_pendiente
                    # Actualizar estado
                    $barcode=new Barcode($row);
                    $barcode->set_id_authstat(Authstat::BARCODE_CANCELADO);
                    if(!$barcode->set()){
                        Model::FailTrans();
                    }
                }
            }
        }
        else{
            Model::FailTrans();
            $mensaje='No existe la boleta. ';
            $this->adjuntar_mensaje_para_usuario($mensaje);
        }
        if(!Model::hasFailedTrans() AND Model::CompleteTrans()){
            $this->adjuntar_mensaje_para_usuario("Boleta inhabilitada correctamente. ");
            $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_CORRECTA;
            return true;
        }
        $this->adjuntar_mensaje_para_usuario("Ha ocurrido un error al inhabilitar la Boleta. ");
        $this->respuesta_ejecucion=self::RESPUESTA_EJECUCION_INCORRECTA;
        return false;
    }
    
}
