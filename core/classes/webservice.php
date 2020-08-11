<?php

abstract class Webservice {
    const ACTIVAR_DEBUG=true;
    const PARAMETRO_HANDSHAKE='handshake';
    const PARAMETRO_METODO='metodo_webservice';
    const PARAMETRO_MERCALPHA='idComercio';
    const PARAMETRO_SID='sid';
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

    public function __construct($mercalpha,$sid) 
    {
        if(($sid===false AND self::$marchand) AND (self::$marchand->get_mercalpha()===$mercalpha)){
            return $this;
        }
        elseif($this->autenticar_cliente($mercalpha, $sid)===true){
            return $this;
        }
        throw new Exception("Acceso denegado. ");
    }
    protected function autenticar_cliente($mercalpha,$sid)
    {
	error_log($sid);
	error_log($mercalpha);
        $recordset=Marchand::select(array('mercalpha'=> strtoupper($mercalpha)));
	error_log($recordset->rowCount());
        if($recordset AND $recordset->RowCount()==1){
            $row=$recordset->FetchRow();
            if(($sid_correcto=$this->obtener_sid_correcta($row['id_marchand']))!=false) {
		error_log($sid."lala");
		error_log($sid_correcto."lala");
                if(trim($sid_correcto)==$sid) {
                    self::$marchand=new Marchand($row);
                    self::$id_marchand=$row['id_marchand'];
                    return true;
                }
            }
        }
        return false;
    }
    protected final function obtener_sid_correcta($idm)
    {
//	error_log($idm);
        $recordset=Afuturo::select(array('id_marchand'=>$idm,'id_ops'=>self::ID_OPS));
  //      error_log("hola");
	if($recordset AND $recordset->RowCount()==1){
            $row=$recordset->FetchRow();
            return $row['sid'];
        }
        if($recordset AND $recordset->RowCount()>1){
            developer_log('Hay más de un registro en afuturo.');
        }
        return false;
    }
    public final function get_marchand()
    {
        return self::$marchand;
    }
    abstract function ejecutar($array);
    protected final function adjuntar_mensaje_para_usuario($mensaje)
    {   
        $this->respuesta_log[]=$mensaje;
    }
    protected final function adjuntar_dato_para_usuario($dato)
    {
        $this->respuesta_datos[]=  $dato;
    }
    public final function obtener_parametros_de_salida()
    {
        $parametros_de_salida=$this->preparar_parametros_de_salida();
        $this->parametros_de_salida=$parametros_de_salida;
        return $this->parametros_de_salida;
    }
    public static final function grabar_log($parametros_de_entrada, $parametros_de_salida, $duracion)
    {
        $mensaje="";
        if(!isset($parametros_de_salida[self::RESPUESTA_LOG])){
            $parametros_de_salida[self::RESPUESTA_LOG][]='No hay respuesta.';
        }
        foreach ($parametros_de_salida[self::RESPUESTA_LOG] as $log) {
            $mensaje.=$log;
        }
        developer_log($parametros_de_salida[self::RESPUESTA_LOG]);
        $dbmenso_array=array();
        $dbmenso_array['ip']=$_SERVER['REMOTE_ADDR'];
        $dbmenso_array['port']=$_SERVER['REMOTE_PORT'];
        $dbmenso_array['script']=basename(__FILE__);
        $dbmenso_array['class']=basename(get_called_class());
        $dbmenso_array['parametros_de_entrada']=$parametros_de_entrada;
        if(isset($parametros_de_salida[self::RESPUESTA_DATOS]) AND 
            strlen(json_encode($parametros_de_salida[self::RESPUESTA_DATOS]))>self::CANTIDAD_DE_DATOS_MAXIMOS_LOG){
            $parametros_de_salida[self::RESPUESTA_DATOS]=array('Muchos datos enviados al cliente.');
        }
        $dbmenso_array['parametros_de_salida']=$parametros_de_salida;
        $dbmenso=json_encode($dbmenso_array);
        $bool=false;
        if($parametros_de_salida[self::RESPUESTA_EJECUCION]==self::RESPUESTA_EJECUCION_CORRECTA){
            $bool=true;
        }
        if((Gestor_de_log::set_webservice(self::$id_marchand, $mensaje, $bool, $dbmenso, $duracion))){
            return true;
        }
        return false;
    }
    protected final function preparar_parametros_de_salida()
    {
        $respuesta=array();
        $respuesta[self::RESPUESTA_EJECUCION]=$this->respuesta_ejecucion;

        if($this->respuesta_log AND count($this->respuesta_log)>0){
            $respuesta[self::RESPUESTA_LOG]=array();
            $respuesta[self::RESPUESTA_LOG]=$this->respuesta_log;
        }
        if($this->respuesta_datos AND count($this->respuesta_datos)>0){
            $respuesta[self::RESPUESTA_DATOS]=array();
            $respuesta[self::RESPUESTA_DATOS]=$this->respuesta_datos;
        }
        return $respuesta;
    }
    # LINDO DILEMA PONER ESTO ACA
    protected function obtener_climarchand($id_marchand, $identificador_nombre,$identificador_valor)
    {
        
        $saps=array($identificador_nombre);
        $variables=array($identificador_nombre=>$identificador_valor);
        $variables['id_authstat']=Authstat::ACTIVO;
        $rs_climarchand=  Climarchand::select_clientes($id_marchand, $saps, $variables);
        if($rs_climarchand){
            if($rs_climarchand->rowCount()==1){
                $climarchand=new Climarchand($rs_climarchand->fetchRow());
                return $climarchand;
            }
            if($rs_climarchand->RowCount()>1){
                throw new Exception("Hay más de un Pagador que coincide con el identificador buscado.");                
            }
            if($rs_climarchand->RowCount()==0){
                throw new Exception("Ningún Pagador coincide con el identificador buscado.");                
            }
        }
        return false;
    }
    protected function obtener_climarchand_desde_barcode($id_marchand,$barcode){
        $rs_climarchand= Climarchand::select_barcode($id_marchand, $barcode);
            if(!$rs_climarchand OR $rs_climarchand->RowCount()!=1){
                throw new Exception('Ha ocurrido un error al obtener al Cliente. ');
            }
            else{
                $climarchand=new Climarchand($rs_climarchand->FetchRow());
            }
            return $climarchand;
    }

    public static function fabrica($parametros_de_entrada)
    {
        $handshake=false;
        if(isset($parametros_de_entrada[Webservice::PARAMETRO_HANDSHAKE])){
            $handshake=$parametros_de_entrada[Webservice::PARAMETRO_HANDSHAKE];
            unset($parametros_de_entrada[Webservice::PARAMETRO_HANDSHAKE]);
        }
	error_log(json_encode($parametros_de_entrada));
        if($parametros_de_entrada){
            foreach ($parametros_de_entrada as $key => $value) {
                if($value==='' OR ((is_array($value) AND count($value)==0))){
                    # Para unificar el comportamiento de Nusoap VS Post y Get
                    unset($parametros_de_entrada[$key]);
                }
            }
            if(isset($parametros_de_entrada[Webservice::PARAMETRO_METODO]) AND is_string($parametros_de_entrada[Webservice::PARAMETRO_METODO])){
                if(!isset($parametros_de_entrada[Webservice::PARAMETRO_MERCALPHA]) OR !is_string($parametros_de_entrada[Webservice::PARAMETRO_MERCALPHA])){
                    $parametros_de_salida[Webservice::RESPUESTA_EJECUCION]=Webservice::RESPUESTA_EJECUCION_INCORRECTA;
                    $mensaje="No se recibieron datos en el parámetro '".Webservice::PARAMETRO_MERCALPHA."'. ";
                    $parametros_de_salida[Webservice::RESPUESTA_LOG][]=$mensaje;
                }
                elseif(!isset($parametros_de_entrada[Webservice::PARAMETRO_SID]) OR is_array($parametros_de_entrada[Webservice::PARAMETRO_SID])){
                    $parametros_de_salida[Webservice::RESPUESTA_EJECUCION]=Webservice::RESPUESTA_EJECUCION_INCORRECTA;
                    $mensaje="No se recibieron datos en el parámetro '".Webservice::PARAMETRO_SID."'. ";
                    $parametros_de_salida[Webservice::RESPUESTA_LOG][]=$mensaje;
                }
                else{
                    $clase_webservice="Webservice_".$parametros_de_entrada[Webservice::PARAMETRO_METODO];
                    if(class_exists($clase_webservice)){
                        try {
                            $clase_webservice=new $clase_webservice($parametros_de_entrada[Webservice::PARAMETRO_MERCALPHA],$parametros_de_entrada[Webservice::PARAMETRO_SID]);
                            $parametros_de_entrada_metodo=$parametros_de_entrada;
                            unset($parametros_de_entrada_metodo[Webservice::PARAMETRO_MERCALPHA]);
                            unset($parametros_de_entrada_metodo[Webservice::PARAMETRO_SID]);
                            unset($parametros_de_entrada_metodo[Webservice::PARAMETRO_METODO]);
			    $limite=Configuracion::obtener_config_tag_concepto("limite", self::$marchand->get_id());
                            $GLOBALS['MAXIMO_REGISTROS_POR_CONSULTA']=$limite["value"];
                            $clase_webservice->ejecutar($parametros_de_entrada_metodo);
                            $parametros_de_salida=$clase_webservice->obtener_parametros_de_salida();
                        } catch (Exception $e) {
                            $parametros_de_salida=array();
                            $parametros_de_salida[Webservice::RESPUESTA_EJECUCION]=Webservice::RESPUESTA_EJECUCION_INCORRECTA;
                            $parametros_de_salida[Webservice::RESPUESTA_LOG][]=$e->getMessage();
                        }
                    }
                    else{
                        $clase_webservice=false;
                        $parametros_de_salida[Webservice::RESPUESTA_EJECUCION]=Webservice::RESPUESTA_EJECUCION_INCORRECTA;
                        $mensaje="El método definido en el parámetro '".Webservice::PARAMETRO_METODO."' no es correcto. ";
                        $parametros_de_salida[Webservice::RESPUESTA_LOG][]=$mensaje;
                    }
                }
            }
            else{
                $parametros_de_salida[Webservice::RESPUESTA_EJECUCION]=Webservice::RESPUESTA_EJECUCION_INCORRECTA;
                $mensaje="No se recibieron datos en el parámetro '".Webservice::PARAMETRO_METODO."'. ";
                $parametros_de_salida[Webservice::RESPUESTA_LOG][]=$mensaje;

            }
        }
        else{
            $parametros_de_entrada='';
            $parametros_de_salida[Webservice::RESPUESTA_EJECUCION]=Webservice::RESPUESTA_EJECUCION_INCORRECTA;
            $mensaje="No se recibieron datos. ";
            $parametros_de_salida[Webservice::RESPUESTA_LOG][]=$mensaje;
        }

        if(count($parametros_de_salida)==0){
            $parametros_de_salida[Webservice::RESPUESTA_EJECUCION]=Webservice::RESPUESTA_EJECUCION_INCORRECTA;
            $mensaje="Ha ocurrido un Error general. ";
            $parametros_de_salida[Webservice::RESPUESTA_LOG][]=$mensaje;
        }
        if(isset($parametros_de_entrada[Webservice::PARAMETRO_SID]) AND is_string($parametros_de_entrada[Webservice::PARAMETRO_SID])){
            $parametros_de_entrada[Webservice::PARAMETRO_SID]=substr($parametros_de_entrada[Webservice::PARAMETRO_SID], 0,5).'...';
        }
        if($handshake){
            $parametros_de_salida[Webservice::PARAMETRO_HANDSHAKE]=$handshake;
        }
        return $parametros_de_salida;
    }
}
