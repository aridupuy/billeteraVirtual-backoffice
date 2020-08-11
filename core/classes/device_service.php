<?php

abstract class Device_service extends Webservice{
    const ACTIVAR_DEBUG=true;
    const PARAMETRO_HANDSHAKE='handshake';
    const PARAMETRO_METODO='metodo_service';
    const PARAMETRO_TOKEN='token';
    const RESPUESTA_LOG='log';
    const RESPUESTA_DATOS='datos';
    const RESPUESTA_EJECUCION='ejecucion_correcta';
    const RESPUESTA_EJECUCION_CORRECTA='1';
    const RESPUESTA_EJECUCION_INCORRECTA='0';
    const FORMATO_FECHA="!Ymd";
    const CANTIDAD_DE_DATOS_MAXIMOS_LOG=100;
    protected static $marchand=false;
    protected static $id_marchand; # Para log
    protected $respuesta_log=false;
    protected $respuesta_datos=false;
    protected $respuesta_ejecucion=false;
    protected $parametros_de_entrada=false;
    protected $parametros_de_salida=false;

    const ID_OPS='160';
     public final function __construct($token) 
    {
        if(($token===false AND self::$marchand)){
            return $this;
        }
        elseif($this->autenticar_cliente($token)===true){
            return $this;
        }
        throw new Exception("Acceso denegado. ");
    }
    public function autenticar_cliente($token){
        $recordset= Dispositivo::select(array("token"=>$token));
        if($recordset AND $recordset->rowCount()<=0)
            return false;
        $row=$recordset->fetchRow();
        self::$marchand=new Marchand();
        self::$marchand->get($row['id_marchand']);
        self::$id_marchand= self::$marchand->get_id_marchand();
        return true;
    }
    public static function fabrica($parametros_de_entrada)
    {
        $handshake=false;
        if(isset($parametros_de_entrada[Device_service::PARAMETRO_HANDSHAKE])){
            $handshake=$parametros_de_entrada[Device_service::PARAMETRO_HANDSHAKE];
            unset($parametros_de_entrada[Device_service::PARAMETRO_HANDSHAKE]);
        }
        if($parametros_de_entrada){
            foreach ($parametros_de_entrada as $key => $value) {
                if($value==='' OR ((is_array($value) AND count($value)==0))){
                    # Para unificar el comportamiento de Nusoap VS Post y Get
                    unset($parametros_de_entrada[$key]);
                }
            }
            if((isset($parametros_de_entrada[Device_service::PARAMETRO_METODO]) AND is_string($parametros_de_entrada[Device_service::PARAMETRO_METODO])) ){
                if(!isset($parametros_de_entrada[Device_service::PARAMETRO_TOKEN])){
                    $parametros_de_salida[Device_service::RESPUESTA_EJECUCION]=Device_service::RESPUESTA_EJECUCION_INCORRECTA;
                    $mensaje="No se recibieron datos en el parámetro '".Device_service::PARAMETRO_TOKEN."'. ";
                    $parametros_de_salida[Device_service::RESPUESTA_LOG][]=$mensaje;
                }
                else
                    if(($parametros_de_salida= self::instanciar_service("service_".$parametros_de_entrada[Device_service::PARAMETRO_METODO],$parametros_de_entrada))==null){
                        $parametros_de_entrada_ws=$parametros_de_entrada;
                        if($parametros_de_entrada[Device_service::PARAMETRO_METODO]!="registrar")
//                            unset($parametros_de_entrada_ws[Device_service::PARAMETRO_TOKEN]);
                        if(($parametros_de_salida= self::instanciar_webservice("webservice_".$parametros_de_entrada_ws[Device_service::PARAMETRO_METODO],$parametros_de_entrada_ws))==null){
                            unset($parametros_de_entrada_ws[Device_service::PARAMETRO_TOKEN]);
                            $clase_service=false;
                            $parametros_de_salida[Device_service::RESPUESTA_EJECUCION]=Device_service::RESPUESTA_EJECUCION_INCORRECTA;
                            $mensaje="El método definido en el parámetro '".Device_service::PARAMETRO_METODO."' no es correcto. ";
                            $parametros_de_salida[Device_service::RESPUESTA_LOG][]=$mensaje;
                        }
                    }
                }
                else{
                    $parametros_de_salida[Device_service::RESPUESTA_EJECUCION]=Device_service::RESPUESTA_EJECUCION_INCORRECTA;
                    $mensaje="No se recibieron datos en el parámetro '".Device_service::PARAMETRO_METODO."'. ";
                    $parametros_de_salida[Device_service::RESPUESTA_LOG][]=$mensaje;

                }
            }
        else{
            $parametros_de_entrada='';
            $parametros_de_salida[Device_service::RESPUESTA_EJECUCION]=Device_service::RESPUESTA_EJECUCION_INCORRECTA;
            $mensaje="No se recibieron datos. ";
            $parametros_de_salida[Device_service::RESPUESTA_LOG][]=$mensaje;
        }

        if(count($parametros_de_salida)==0){
            $parametros_de_salida[Device_service::RESPUESTA_EJECUCION]=Device_service::RESPUESTA_EJECUCION_INCORRECTA;
            $mensaje="Ha ocurrido un Error general. ";
            $parametros_de_salida[Device_service::RESPUESTA_LOG][]=$mensaje;
        }
        if(isset($parametros_de_entrada[Device_service::PARAMETRO_SID]) AND is_string($parametros_de_entrada[Device_service::PARAMETRO_SID])){
            $parametros_de_entrada[Device_service::PARAMETRO_SID]=substr($parametros_de_entrada[Device_service::PARAMETRO_SID], 0,5).'...';
        }
        if($handshake){
            $parametros_de_salida[Device_service::PARAMETRO_HANDSHAKE]=$handshake;
        }
        return $parametros_de_salida;
    }
    protected static function instanciar_service($clase,$parametros_de_entrada){
        $parametros_de_salida=null;
        if(class_exists(ucfirst($clase))){
            try {
                $clase_service=new $clase($parametros_de_entrada[Device_service::PARAMETRO_TOKEN]);
                $parametros_de_entrada_metodo=$parametros_de_entrada;
                
                unset($parametros_de_entrada_metodo[Device_service::PARAMETRO_MERCALPHA]);
                unset($parametros_de_entrada_metodo[Device_service::PARAMETRO_SID]);
                unset($parametros_de_entrada_metodo[Device_service::PARAMETRO_METODO]);
                $clase_service->ejecutar($parametros_de_entrada_metodo);
                $parametros_de_salida=$clase_service->obtener_parametros_de_salida();
            } catch (Exception $e) {
                    $parametros_de_salida=array();
                    $parametros_de_salida[Device_service::RESPUESTA_EJECUCION]=Device_service::RESPUESTA_EJECUCION_INCORRECTA;
                    $parametros_de_salida[Device_service::RESPUESTA_LOG][]=$e->getMessage();
                    }
        }
        
        return $parametros_de_salida;
    }
    protected static function instanciar_webservice($clase,$parametros_de_entrada){
        $parametros_de_salida=null;
        if(class_exists(ucfirst($clase))){
            try {
                $service=new instancia_ws($parametros_de_entrada[self::PARAMETRO_TOKEN]);
                $sid=$service->obtener_sid_correcta(self::$marchand->get_id_marchand());
                $mercalpha= self::$marchand->get_mercalpha();
                
                $clase_webservice=new $clase($mercalpha,$sid);
                $parametros_de_entrada_metodo=$parametros_de_entrada;
                unset($parametros_de_entrada_metodo[Webservice::PARAMETRO_MERCALPHA]);
                unset($parametros_de_entrada_metodo[Webservice::PARAMETRO_SID]);
                unset($parametros_de_entrada_metodo[Device_service::PARAMETRO_METODO]);
                $clase_webservice->ejecutar($parametros_de_entrada_metodo);
                $parametros_de_salida=$clase_webservice->obtener_parametros_de_salida();
            } catch (Exception $e) {
                $parametros_de_salida=array();
                $parametros_de_salida[Device_service::RESPUESTA_EJECUCION]=Device_service::RESPUESTA_EJECUCION_INCORRECTA;
                $parametros_de_salida[Device_service::RESPUESTA_LOG][]=$e->getMessage();
            }
        }
        return $parametros_de_salida;
    }
   
}
class instancia_ws extends Device_service{
        
     public function ejecutar($array) {
         return;
    }
}