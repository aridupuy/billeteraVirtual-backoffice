<?php
    error_reporting( E_NOTICE,E_ALL );
header("Access-Control-Allow-Origin:*");
    if( count($_POST, COUNT_RECURSIVE) > ini_get("max_input_vars")){
        error_log('El volumen de datos enviados excede el volumen procesable.');
        exit();        
    }
    header("Access-Control-Allow-Origin:*");
//    var_dump($_POST);
    $parametros_de_entrada = false;
    $utiliza_nusoap = false;
    if (isset($_SERVER['CONTENT_TYPE']) AND ($_SERVER['CONTENT_TYPE'] == "application/json" OR $_SERVER['CONTENT_TYPE'] == "text/plain")) {
        $parametros_de_entrada = file_get_contents('php://input');
//        error_log($parametros_de_entrada);
    } 
    elseif (count($_POST) > 0 AND count($_GET) == 0) {
    $parametros_de_entrada = json_encode($_POST);
} elseif ((count($_GET) > 0 AND count($_POST) == 0)AND ( !isset($_GET['wsdl']))) {
    $parametros_de_entrada = json_encode($_GET); 
} else {
    $utiliza_nusoap = true;
    require_once('../public/nusoap/nusoap.php');
    $server = new soap_server();
    $server->configureWSDL('servicioweb_cobrodigital');
    $server->register('webservice_cobrodigital', array('parametros_de_entrada' => 'xsd:string'), array('output' => 'xsd:string'));
}
unset($_POST);
unset($_GET);
    error_log(json_encode($parametros_de_entrada));
    $parametros_de_salida = service($parametros_de_entrada);
    # SALIDA
    error_log($parametros_de_salida);
    echo $parametros_de_salida;
    function service($parametros_de_entrada) {
        $tiempo_inicio = microtime(true);
        define('PATH_APPS', '../');
        $GLOBALS['SISTEMA'] = 'INTERNO';
        require_once PATH_APPS . 'core/config.ini'; # Si incluimos config antes de nusoap falla el WSDL
        $parametros_de_entrada = json_decode($parametros_de_entrada, true);
        ########################################
        $parametros_de_salida = Device_service::fabrica($parametros_de_entrada);
        ########################################
        $tiempo_fin = microtime(true);
        $duracion = $tiempo_fin - $tiempo_inicio;
        Device_service::grabar_log($parametros_de_entrada, $parametros_de_salida, $duracion);
        return json_encode($parametros_de_salida);
    }
    exit();
