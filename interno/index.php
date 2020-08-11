<?php
//phpinfo();
//print_r($_SERVER['SSL_TLS_SNI']);
//exit;
//print_r(Barcode::URL_BARCODE);
set_time_limit(0);
$GLOBALS['SISTEMA']='INTERNO';
require dirname(__DIR__).'/core/config.ini';
$id_usuario_cookie=null;
$nav=null;

if(isset($_COOKIE[$GLOBALS['COOKIE_NAME']]))
	$id_usuario_cookie=$_COOKIE[$GLOBALS['COOKIE_NAME']];

$variables=$_POST;

if((isset($variables['nav']) AND $variables['nav'])AND(!is_numeric($variables['nav'])))
	$nav=$variables['nav'];
$application=new Back_office($id_usuario_cookie);
$html=$application->navigate($nav,$variables);
if(!CONSOLA) {
	echo $html;
	exit(0);
}
exit(1);
