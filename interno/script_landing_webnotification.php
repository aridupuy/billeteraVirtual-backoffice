<?php
//header('Content-type: text/xml');
set_time_limit(100);
if (count($_POST, COUNT_RECURSIVE) > ini_get("max_input_vars")) {
    error_log('El volumen de datos enviados excede el volumen procesable.');
    exit();
}
$parametros_de_entrada = false;
$utiliza_nusoap = false;
if (isset($_SERVER['CONTENT_TYPE']) AND $_SERVER['CONTENT_TYPE'] == "application/json") {
    $parametros_de_entrada = file_get_contents('php://input');
        error_log($parametros_de_entrada);
} elseif (count($_POST) > 0 AND count($_GET) == 0) {
    $parametros_de_entrada = json_encode($_POST);
} elseif ((count($_GET) > 0 AND count($_POST) == 0)AND ( !isset($_GET['wsdl']))) {
    $parametros_de_entrada = json_encode($_GET);
} else {
    $utiliza_nusoap = true;
    require_once('../public/nusoap/nusoap.php');
    $server = new soap_server();
    $server->configureWSDL('servicioweb_cobrodigital');
    $server->register('webnotification_cobrodigital', array('parametros_de_entrada' => 'xsd:string'), array('output' => 'xsd:string'));
}
unset($_POST);
unset($_GET);
if ($utiliza_nusoap == true) {
    # Retorno al cliente NUSOAP
    if (!isset($HTTP_RAW_POST_DATA)) {
        $HTTP_RAW_POST_DATA = file_get_contents('php://input');
    }
  //  header('Content-Type: application/json');
//    header('Content-Encoding: identity');
    # SALIDA
    $server->service($HTTP_RAW_POST_DATA);
//   developer_log($HTTP_RAW_POST_DATA);
} else {
    $parametros_de_salida = webnotification_cobrodigital($parametros_de_entrada);
    # SALIDA
    echo $parametros_de_salida;
}

function webnotification_cobrodigital($parametros_de_entrada) {
    $tiempo_inicio = microtime(true);
    define('PATH_APPS', '../');
    $GLOBALS['SISTEMA'] = 'EXTERNO';
    require_once PATH_APPS . 'core/config.ini'; # Si incluimos config antes de nusoap falla el WSDL
//	error_log(json_encode($parametros_de_entrada));
        if(is_array($parametros_de_entrada))
	 if(isset($parametros_de_entrada[0]))
           $parametros=$parametros_de_entrada[0];
	else
	  $parametros=$parametros_de_entrada;
    elseif(!( $parametros = json_decode($parametros_de_entrada, true))){
        $parametros=$parametros_de_entrada;
    }
    ########################################
    $parametros_de_salida = Webnotification::fabrica($parametros);
    ########################################
    $tiempo_fin = microtime(true);
    $duracion = $tiempo_fin - $tiempo_inicio;
    Webservice::grabar_log($parametros, $parametros_de_salida, $duracion);
//    var_dump(json_encode($parametros_de_salida));
    return json_encode($parametros_de_salida)
;
}


exit();
?>
