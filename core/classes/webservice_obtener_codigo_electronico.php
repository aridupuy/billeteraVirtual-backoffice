<?php
class Webservice_obtener_codigo_electronico extends Webservice{
    const PARAMETRO_IDENTIFICADOR="identificador";
    const PARAMETRO_IDENTIFICADOR_VALOR="buscar";
    public function ejecutar($array) {
        $this->parametros_de_entrada=$array;
         if(!isset($array[self::PARAMETRO_IDENTIFICADOR])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_IDENTIFICADOR."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!isset($array[self::PARAMETRO_IDENTIFICADOR_VALOR])){
            $mensaje="No se recibieron datos en el parámetro '".self::PARAMETRO_IDENTIFICADOR_VALOR."'. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!is_string($array[self::PARAMETRO_IDENTIFICADOR])){
            $mensaje="El parámetro '".self::PARAMETRO_IDENTIFICADOR."' debe ser una cadena de texto. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(!is_string($array[self::PARAMETRO_IDENTIFICADOR_VALOR])){
            $mensaje="El parámetro '".self::PARAMETRO_IDENTIFICADOR_VALOR."' debe ser una cadena de texto. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        if(count($array)!=2){
            $mensaje="El número de argumentos no es correcto.";
            $this->adjuntar_mensaje_para_usuario($mensaje);
            throw new Exception($mensaje);
        }
        $identificador_label=$array[self::PARAMETRO_IDENTIFICADOR];
        $identificador_valor=$array[self::PARAMETRO_IDENTIFICADOR_VALOR];
        $error=false;
        $pagador=new Pagador();
        $identificador_nombre=$pagador->obtener_nombre_desde_label(self::$marchand->get_id(), $identificador_label);
        if(!$identificador_nombre){
            $error=true;
            $mensaje="El identificador no pertenece a la estructura de clientes. ";
            $this->adjuntar_mensaje_para_usuario($mensaje);
        }
        if(!$error){
            if(($climarchand=$this->obtener_climarchand(self::$marchand->get_id(), $identificador_nombre,$identificador_valor))===false){
                $error=true;
                # Mejorar este mensaje
                $mensaje="No se pudo obtener un unico pagador con los datos suministrados. ";
                $this->adjuntar_mensaje_para_usuario($mensaje);
                developer_log($mensaje);
            }
        }
        if(!$error){
            $recordset=Barcode::select_codelec_id_climarchand($climarchand->get_id());
            if($recordset->rowCount()==0){
                $error=true;
                # CORREGIR EJECUCION CORRECTA O NO
                $mensaje="No se encontro un código electronico para a este pagador.";
                $this->adjuntar_mensaje_para_usuario($mensaje);
                developer_log($mensaje);
            }
            if($error){
               $this->respuesta_ejecucion=  self::RESPUESTA_EJECUCION_INCORRECTA;
               return false;
            }
            else{
                $row=$recordset->fetchRow();
                $pmc=$row['pmc19'];
                $this->adjuntar_dato_para_usuario($pmc);
                $mensaje="Código electronico obtenido correctamente.";
                $this->respuesta_ejecucion=  self::RESPUESTA_EJECUCION_CORRECTA;
                $this->adjuntar_mensaje_para_usuario($mensaje);
                developer_log($mensaje);
                return true;
            }
        }
    }
}
