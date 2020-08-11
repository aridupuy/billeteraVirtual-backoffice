<?php

require_once '../public/nusoap/nusoap.php';

abstract class Afip_login {

    const METODOLOGIN = "loginCms";
    const URLLOGIN = "https://wsaa.afip.gov.ar/ws/services/LoginCms";
    const METODO = "LoginCms";
    const URL = "";
    const ARCHIVO_XML = "MiLoguinTicketRequest.xml";
    const ARCHIVO_XML_TMP = "MiLoguinTicketRequest.tmp";
    const DIRECTORIO_CLAVES_SSL = "/home/heisenberg/ssl";
    const DIR_SSL = "/home/heisenberg/ssl2/";
    const CUITREPRESENTADA = '33711566959';
    protected $token;
    protected $sign;
    protected $parametros;
    protected $fecha_expiracion;
//el token  dura 12 
//podria implementar un sistema de cache de claves
    public function __construct() {
        ini_set("soap.wsdl_cache_enabled", "0");
        $this->crearArchivoXMl();
        $credentials=$this->getCredentials();
        if(!$credentials){
            $this->parsearLoguin($this->loguearws($this->crearCms()));
            $this->saveCredentials();
        }
        else{
            $this->token=$credentials->get_token();
            $this->sign=$credentials->get_sign();
            $this->fecha_expiracion = $credentials->get_fecha_expiracion();
        }
        return $this;
    }

    protected function crearArchivoXMl() {
        $xml = new DOMDocument();
        $request = $xml->createElement("loginTicketRequest");
        $xml->appendChild($request);
        $header = $xml->createElement("header");
        $unique = $xml->createElement("uniqueId", numeros_aleatorios(5));
        $fecha = new DateTime("now");
        $expiracion = new DateTime("now");
        $expiracion->modify("+12 hours");
        $fechastr = $fecha->format("Y-m-d") . "T" . $fecha->format("H:i:s");
        $expiracionstr = $expiracion->format("Y-m-d") . "T" . $expiracion->format("H:i:s");
        $generation = $xml->createElement("generationTime", $fechastr);
        $expire = $xml->createElement("expirationTime", $expiracionstr);
        $header->appendChild($unique);
        $header->appendChild($generation);
        $header->appendChild($expire);
        $service = $xml->createElement("service", $this->getMetodoLogin());
        $request->appendChild($header);
        $request->appendChild($service);
	developer_log($xml->saveXML());
        if ($xml->save("/tmp/" . self::ARCHIVO_XML))
            ;
        return "/tmp/" . self::ARCHIVO_XML;
        throw new Exception("Error al generar el xml para ticketRequest");
    }
   protected final function getMetodoLogin(){
	return static::METODOLOGIN;
    }
    protected function crearCms() {
//            exec("openssl cms -sign -in /tmp/".self::ARCHIVO_XML." -out /tmp/".self::ARCHIVO_XML.".cms -signer ".self::DIRECTORIO_CLAVES_SSL."/cobrodigital_com.crt -inkey ".self::DIRECTORIO_CLAVES_SSL."/cobrodigital.com.key -nodetach -outform PEM");

        $STATUS = openssl_pkcs7_sign("/tmp/" . self::ARCHIVO_XML, "/tmp/" . self::ARCHIVO_XML_TMP, "file://" . self::DIRECTORIO_CLAVES_SSL."/WS-AFIP-PROD.crt", array("file://" . self::DIRECTORIO_CLAVES_SSL."/privada.key", ""), array(), !PKCS7_DETACHED
        );
        if (!$STATUS) {
            exit("ERROR generating PKCS#7 signature\n");
        }
        $inf = fopen("/tmp/" . self::ARCHIVO_XML_TMP, "r");
        $i = 0;
        $CMS = "";
        while (!feof($inf)) {
            $buffer = fgets($inf);
            if ($i++ >= 4) {
                $CMS .= $buffer;
            }
        }
        fclose($inf);
#  unlink("TRA.xml");
        unlink("/tmp/" . self::ARCHIVO_XML_TMP);
        return ($CMS);
    }

    protected function loguearws($archivoCms) {
        /*$contenido = file_get_contents($archivoCms);
        $contenido = str_replace("-----END CMS-----", "", str_replace("-----BEGIN CMS-----", "", $contenido));
        *///file_put_contents("/home/ariel/ssl2/cacheCms", $contenido);
        $nusoap = new nusoap_client(self::URLLOGIN);
        $this->add_param("in0", $archivoCms);
        return $nusoap->call(self::METODOLOGIN, $this->parametros);
    }

    protected function parsearLoguin($respuesta) {
        $xml = new DOMDocument();
//var_dump($respuesta);
        $xml->loadXML($respuesta);
        developer_log($respuesta);
	var_dump($respuesta);
        $tokens = $xml->getElementsByTagName("token");
        $signs = $xml->getElementsByTagName("sign");
        $fecha_exp=$xml->getElementsByTagName("expirationTime");
        if ($tokens->length == 0 and $signs->length == 0)
            throw new Exception("Error al loguear con el webservice");
        developer_log($signs->item(0)->nodeValue);
	$this->token = $tokens->item(0)->nodeValue;
        $this->sign = $signs->item(0)->nodeValue;
        $partido= explode('.',$fecha_exp->item(0)->nodeValue);
	developer_log(json_encode($partido));
	//2019-12-13T23:14:39
        $fecha= DateTime::createFromFormat("Y-m-d\TH:i:s", $partido[0]);
        $this->fecha_expiracion = $fecha->format("Y-m-d H:i:s");
    }

    abstract protected function parsear();

    public function CorrerMetodo() {
        $nusoap = new nusoap_client($this->getUrl(), true);
        $nusoap->loadWSDL();
        return $nusoap->call($this->getMetodo(), $this->getParametros());
    }

    protected function getMetodo() {
        return static::METODO;
    }
    protected abstract function getParametros();

    protected function getUrl() {
        return static::URL;
    }

    protected final function set_params($array) {
        $this->parametros = $array;
    }

    protected final function add_param($key, $value) {
        $this->parametros[$key] = $value;
    }
    
    
    protected final function saveCredentials(){
        $credenciales = new Credenciales_afip();
        $credenciales->set_sign($this->sign);
        $credenciales->set_token($this->token);
        $credenciales->set_fecha_gen("now()");
        $credenciales->set_fecha_expiracion($this->fecha_expiracion);
        $credenciales->set_metodo(static::METODO);
        if($credenciales->set()){
            return true;
        }
        return false;
    }
    
    
    protected final function getCredentials(){
        $rs_credentials= Credenciales_afip::select_min(static::METODOLOGIN);
        if($rs_credentials->rowCount()==0){
           developer_log("No se obtuvieron credenciales validas");
	    return false;
        }
        $row=$rs_credentials->fetchRow();
        return new Credenciales_afip($row);
    }
    
    public function obtener_parametros(){
        $params["token"]= $this->token;
        $params["sign"]= $this->sign;
        $params["cuitRepresentada"]= self::CUITREPRESENTADA;
        return $params;
    }

}
