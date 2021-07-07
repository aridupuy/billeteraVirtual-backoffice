<?php

set_time_limit(50);
if(!isset($GLOBALS['SISTEMA'])) $GLOBALS['SISTEMA']='INTERNO';
require dirname(__DIR__).'/core/config.ini';
if(isset($_POST["ACTIVAR_LOG_NAVEGADOR"]) and $_POST["ACTIVAR_LOG_NAVEGADOR"]=="0"){
    $GLOBALS["ACTIVAR_LOG_NAVEGADOR"]="0";
    Application::$no_log_navegador=true;
    unset($_POST["ACTIVAR_LOG_NAVEGADOR"]);
}
if(isset($_POST["json"])){
    developer_log("JSON ACTIVADO");
    Application::$json=true;
}
//error_log("viendo globals");
$id_usuario_cookie=null;
$nav=null;

if(isset($_COOKIE[$GLOBALS['COOKIE_NAME']]))
	$id_usuario_cookie=$_COOKIE[$GLOBALS['COOKIE_NAME']];

$variables=$_POST;

if((isset($variables['nav']) AND $variables['nav'])AND(!is_numeric($variables['nav'])))
	$nav=$variables['nav'];
$application=new Efectivo_digital($id_usuario_cookie);
$html=$application->navigate($nav,$variables);
if(!CONSOLA) {
	if($GLOBALS['SISTEMA']!='INTERNO'){
	   exit($html);
	}
	else
	  echo $html;
	exit(0);
}
exit(1);
