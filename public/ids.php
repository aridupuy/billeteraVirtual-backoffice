<?php
#Wal 
if (!session_id()) {
    session_start();
}

require_once 'PHPIDS/lib/IDS/Init.php';
require_once 'PHPIDS/lib/IDS/Monitor.php';
require_once 'PHPIDS/lib/IDS/Filter.php';
require_once 'PHPIDS/lib/IDS/Report.php';
require_once 'PHPIDS/lib/IDS/Converter.php';
require_once 'PHPIDS/lib/IDS/Event.php';
require_once 'PHPIDS/lib/IDS/Filter/Storage.php';
use IDS\Init;
use IDS\Monitor;
try {

    $request = array(
        'REQUEST' => $_REQUEST,
        'GET' => $_GET,
        'POST' => $_POST
    );
    $request['COOKIE']=$_COOKIE; # La cookie suele dar falso positivo
    $init = Init::init(PATH_PUBLIC.'PHPIDS/lib/IDS/Config/Config.ini.php');
    
    $init->config['General']['base_path'] = PATH_PUBLIC.'PHPIDS/lib/IDS/';
    $init->config['General']['use_base_path'] = true;
    $init->config['Caching']['caching'] = 'none';

    $ids = new Monitor($init);
    $result = $ids->run($request);
    # Tenemos demasiados falsos positivos
    # Limitamos el impacto a 50 y lo vemos mas adelante
    if (!$result->isEmpty() AND $result->getImpact()>SENSIBILIDAD_IDS) {
        error_log('Intrusión detectada mediante PHPIDS.');        
        Gestor_de_log::set_intrusion($result,0);
        exit();
    } else {
        if(ACTIVAR_LOG_APACHE_DE_IDS){
            error_log('No se ha detectado una intrusión mediante PHPIDS.');
        }
        // echo '<a href="?test=%22><script>eval(window.name)</script>">No attack detected - click for an example attack</a>';
    }
} catch (Exception $e) {
    error_log( 'An error occured: %s', $e->getMessage() );
    exit();
}
